<?php
/**
 * Regression tests for assignment data declarations.
 *
 * @package assignsubmission_datacheck
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_datacheck;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/** Tests saving, display eligibility and submission readiness. */
final class submission_test extends \advanced_testcase {
    /** Create an assignment and an empty student submission. */
    private function create_submission(): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $activity = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 5,
            'assignsubmission_file_maxsizebytes' => 0,
            'assignsubmission_onlinetext_enabled' => 0,
            'assignsubmission_datacheck_enabled' => 1,
        ]);
        $context = \context_module::instance($activity->cmid);
        $assignment = new \assign($context, null, null);
        $submission = $assignment->get_user_submission($student->id, true);
        return [$assignment, $assignment->get_submission_plugin_by_type('datacheck'), $submission, $student, $course];
    }

    /** Save all allowed answers without creating duplicate declaration rows. */
    public function test_save_and_update(): void {
        global $DB;
        [$assignment, $plugin, $submission] = $this->create_submission();
        foreach (['yes', 'no', 'unsure'] as $answer) {
            $this->assertTrue($plugin->save($submission, (object)['datacheck_answer' => $answer]));
            $record = $DB->get_record('assignsubmission_datacheck', ['submission' => $submission->id], '*', MUST_EXIST);
            $this->assertSame($answer, $record->answer);
            $this->assertEquals($assignment->get_instance()->id, $record->assignment);
            $this->assertSame(1, $DB->count_records('assignsubmission_datacheck', ['submission' => $submission->id]));
            $this->assertTrue($plugin->precheck_submission($submission));
        }
    }

    /** Invalid input is rejected and readiness failures contain readable text. */
    public function test_missing_and_invalid_answers(): void {
        global $DB;
        [, $plugin, $submission] = $this->create_submission();
        $message = get_string('required', 'assignsubmission_datacheck');
        $this->assertSame($message, $plugin->precheck_submission(false));
        $this->assertSame($message, $plugin->precheck_submission($submission));
        foreach ([new \stdClass(), (object)['datacheck_answer' => 'invalid']] as $data) {
            $this->assertFalse($plugin->save($submission, $data));
            $this->assertSame($message, $plugin->get_error());
        }
        $this->assertFalse($DB->record_exists('assignsubmission_datacheck', ['submission' => $submission->id]));
    }

    /** Match Moodle's summary visibility condition without accepting metadata as work. */
    public function test_visibility_requires_real_submission_content(): void {
        [$assignment, $plugin, $submission] = $this->create_submission();
        $this->assertTrue($plugin->allow_submissions()); // The question must be added to the edit form.
        $this->assertTrue($plugin->is_empty($submission));
        $this->assertTrue($plugin->save($submission, (object)['datacheck_answer' => 'yes']));
        $this->assertTrue($plugin->is_empty($submission));
        $this->assertTrue($assignment->submission_empty($submission));
        $this->assertTrue($plugin->submission_is_empty((object)['datacheck_answer' => 'yes']));

        $file = get_file_storage()->create_file_from_string([
            'contextid' => $assignment->get_context()->id,
            'component' => 'assignsubmission_file',
            'filearea' => 'submission_files',
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'Assignment content');
        $this->assertFalse($plugin->is_empty($submission));
        $this->assertFalse($assignment->submission_empty($submission));
        $this->assertTrue(!$plugin->is_empty($submission) || !$plugin->allow_submissions());
        $showviewlink = true;
        $summary = $plugin->view_summary($submission, $showviewlink);
        $this->assertStringContainsString(s($plugin->get_name()), $summary);
        $this->assertStringContainsString(s(get_string('question', 'assignsubmission_datacheck')), $summary);
        $this->assertStringContainsString(s(get_string('response', 'assignsubmission_datacheck',
            get_string('answer_yes', 'assignsubmission_datacheck'))), $summary);
        $guidance = get_string('teacher_guidance', 'assignsubmission_datacheck');
        $this->assertStringContainsString(strip_tags($guidance), strip_tags($summary));
        $this->assertMatchesRegularExpression('/<b>[^<]+<\/b>/', $summary);
        $this->assertMatchesRegularExpression('/<br\s*\/?\s*>/', $summary);
        $this->assertStringNotContainsString(s($guidance), $summary);
        $this->assertFalse($showviewlink);

        $file->delete();
        $this->assertTrue($plugin->is_empty($submission));
        $this->assertTrue($assignment->submission_empty($submission));
    }

    /** The form displays the question and recalls an existing answer. */
    public function test_form_question_and_saved_selection(): void {
        [, $plugin, $submission] = $this->create_submission();
        $form = new \MoodleQuickForm('datachecktest', 'post', '');
        $data = new \stdClass();
        $this->assertTrue($plugin->get_form_elements_for_user(null, $form, $data, 0));
        $this->assertTrue($form->elementExists('datacheck_answer'));
        $this->assertEquals(get_string('question', 'assignsubmission_datacheck'),
            $form->getElement('datacheck_answer')->getLabel());
        $plugin->save($submission, (object)['datacheck_answer' => 'unsure']);
        $form = new \MoodleQuickForm('datacheckedit', 'post', '');
        $plugin->get_form_elements_for_user($submission, $form, $data, 0);
        $this->assertSame('unsure', $data->datacheck_answer);
    }

    /** Rejecting an edit must preserve the previously saved declaration. */
    public function test_invalid_edit_preserves_answer(): void {
        global $DB;
        [, $plugin, $submission] = $this->create_submission();
        $plugin->save($submission, (object)['datacheck_answer' => 'no']);
        foreach (['', 'YES', '<script>', ['yes'], null] as $answer) {
            $this->assertFalse($plugin->save($submission, (object)['datacheck_answer' => $answer]));
            $this->assertSame('no', $DB->get_field('assignsubmission_datacheck', 'answer',
                ['submission' => $submission->id]));
        }
    }

    /** Readiness checks also reject invalid data already in the database. */
    public function test_precheck_rejects_corrupt_answer(): void {
        global $DB;
        [, $plugin, $submission] = $this->create_submission();
        $plugin->save($submission, (object)['datacheck_answer' => 'yes']);
        $DB->set_field('assignsubmission_datacheck', 'answer', 'invalid', ['submission' => $submission->id]);
        $this->assertSame(get_string('required', 'assignsubmission_datacheck'),
            $plugin->precheck_submission($submission));
    }

    /** Students get context for their answer, but not the teacher instructions. */
    public function test_student_summary_excludes_teacher_guidance(): void {
        [, $plugin, $submission, $student] = $this->create_submission();
        $plugin->save($submission, (object)['datacheck_answer' => 'unsure']);
        $this->setUser($student);
        $summary = $plugin->view($submission);
        $this->assertStringContainsString(s(get_string('question', 'assignsubmission_datacheck')), $summary);
        $this->assertStringContainsString(s(get_string('answer_unsure', 'assignsubmission_datacheck')), $summary);
        $this->assertStringNotContainsString(
            strip_tags(get_string('teacher_guidance', 'assignsubmission_datacheck')), strip_tags($summary));
    }

    /** Online text is substantive content just like a file submission. */
    public function test_visibility_with_online_text(): void {
        [$assignment, $plugin, $submission] = $this->create_submission();
        $textplugin = $assignment->get_submission_plugin_by_type('onlinetext');
        $textplugin->enable();
        $textplugin->save($submission, (object)['onlinetext_editor' => [
            'text' => 'Real assignment content', 'format' => FORMAT_PLAIN, 'itemid' => 0,
        ]]);
        $plugin->save($submission, (object)['datacheck_answer' => 'yes']);
        $this->assertFalse($plugin->is_empty($submission));
        $textplugin->disable();
        $this->assertTrue($plugin->is_empty($submission));
    }

    /** Copying a declaration and then removing it does not affect the source. */
    public function test_copy_and_remove_submission(): void {
        global $DB;
        [$assignment, $plugin, $source] = $this->create_submission();
        $other = $this->getDataGenerator()->create_user();
        $destination = $assignment->get_user_submission($other->id, true);
        $plugin->save($source, (object)['datacheck_answer' => 'unsure']);
        $this->assertTrue($plugin->copy_submission($source, $destination));
        $this->assertSame('unsure', $DB->get_field('assignsubmission_datacheck', 'answer',
            ['submission' => $destination->id]));
        $this->assertTrue($plugin->remove($destination));
        $this->assertFalse($DB->record_exists('assignsubmission_datacheck', ['submission' => $destination->id]));
        $this->assertTrue($DB->record_exists('assignsubmission_datacheck', ['submission' => $source->id]));
    }

    /** Deleting an assignment removes only its own declaration rows. */
    public function test_delete_instance_is_scoped(): void {
        global $DB;
        [, $plugin, $submission] = $this->create_submission();
        $plugin->save($submission, (object)['datacheck_answer' => 'yes']);
        [, $otherplugin, $other] = $this->create_submission();
        $otherplugin->save($other, (object)['datacheck_answer' => 'no']);
        $this->assertTrue($plugin->delete_instance());
        $this->assertFalse($DB->record_exists('assignsubmission_datacheck', ['submission' => $submission->id]));
        $this->assertTrue($DB->record_exists('assignsubmission_datacheck', ['submission' => $other->id]));
    }

    /** Moodle's assignment settings help icon requires both language strings. */
    public function test_settings_help_strings_exist(): void {
        $this->resetAfterTest();
        foreach (['enabled', 'enabled_help', 'response', 'teacher_guidance'] as $key) {
            $this->assertTrue(get_string_manager()->string_exists($key, 'assignsubmission_datacheck'));
        }
    }

    /** Group members share one declaration, matching Moodle's shared submission. */
    public function test_group_submission_uses_one_shared_declaration(): void {
        global $DB;
        [$assignment, $plugin, , $student, $course] = $this->create_submission();
        $peer = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($peer->id, $course->id, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $student);
        groups_add_member($group, $peer);
        $DB->set_field('assign', 'teamsubmission', 1, ['id' => $assignment->get_instance()->id]);
        $assignment = new \assign($assignment->get_context(), null, null);
        $plugin = $assignment->get_submission_plugin_by_type('datacheck');
        $first = $assignment->get_group_submission($student->id, $group->id, true);
        $second = $assignment->get_group_submission($peer->id, $group->id, true);
        $this->assertEquals($first->id, $second->id);
        $plugin->save($first, (object)['datacheck_answer' => 'yes']);
        $plugin->save($second, (object)['datacheck_answer' => 'no']);
        $this->assertSame(1, $DB->count_records('assignsubmission_datacheck', ['submission' => $first->id]));
        $this->assertSame('no', $DB->get_field('assignsubmission_datacheck', 'answer', ['submission' => $first->id]));
    }
}
