<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Privacy export and deletion regression tests.
 *
 * @package assignsubmission_datacheck
 * @copyright 2026 HAMK
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_datacheck\privacy;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use mod_assign\privacy\assign_plugin_request_data;
use core_privacy\local\request\writer;

/**
 * Verify that declarations are exported and removed within the requested scope.
 *
 * @covers \assignsubmission_datacheck\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Create two students with declarations in each of two assignments.
     */
    private function create_declarations(): array {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $students = [$this->getDataGenerator()->create_user(), $this->getDataGenerator()->create_user()];
        foreach ($students as $student) {
            $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        }
        $assignments = [];
        $submissions = [];
        for ($i = 0; $i < 2; $i++) {
            $activity = $this->getDataGenerator()->create_module('assign', [
                'course' => $course->id,
                'assignsubmission_file_enabled' => 0,
                'assignsubmission_onlinetext_enabled' => 0,
                'assignsubmission_datacheck_enabled' => 1,
            ]);
            $assignment = new \assign(\context_module::instance($activity->cmid), null, null);
            $assignments[] = $assignment;
            foreach ($students as $j => $student) {
                $submission = $assignment->get_user_submission($student->id, true);
                $assignment->get_submission_plugin_by_type('datacheck')->save(
                    $submission,
                    (object)['datacheck_answer' => $j ? 'no' : 'unsure']
                );
                $submissions[$i][$j] = $submission;
            }
        }
        return [$assignments, $submissions, $students];
    }

    /**
     * Metadata describes the stored answer table.
     */
    public function test_metadata(): void {
        $collection = new \core_privacy\local\metadata\collection('assignsubmission_datacheck');
        $items = provider::get_metadata($collection)->get_collection();
        $this->assertCount(1, $items);
        $this->assertSame('assignsubmission_datacheck', reset($items)->get_name());
    }

    /**
     * A student's export contains their declaration, but not another student's.
     */
    public function test_export_submission(): void {
        [$assignments, $submissions] = $this->create_declarations();
        $assignment = $assignments[0];
        $request = new assign_plugin_request_data($assignment->get_context(), $assignment, $submissions[0][0], ['Attempt 1']);
        provider::export_submission_user_data($request);
        $data = writer::with_context($assignment->get_context())->get_data([
            'Attempt 1', get_string('privacy:path', 'assignsubmission_datacheck'),
        ]);
        $this->assertSame(get_string('answer_unsure', 'assignsubmission_datacheck'), $data->answer);
        $this->assertNotEmpty($data->timemodified);
    }

    /**
     * Exporting grader data does not disclose the student's declaration.
     */
    public function test_grader_export_excludes_declaration(): void {
        [$assignments, $submissions] = $this->create_declarations();
        $assignment = $assignments[0];
        $request = new assign_plugin_request_data(
            $assignment->get_context(),
            $assignment,
            $submissions[0][0],
            [],
            get_admin()
        );
        provider::export_submission_user_data($request);
        $this->assertFalse(writer::with_context($assignment->get_context())->has_any_data());
    }

    /**
     * A user's deletion preserves other users and other assignments.
     */
    public function test_delete_user_submission(): void {
        global $DB;
        [$assignments, $submissions, $students] = $this->create_declarations();
        $assignment = $assignments[0];
        provider::delete_submission_for_userid(new assign_plugin_request_data(
            $assignment->get_context(),
            $assignment,
            $submissions[0][0],
            [],
            $students[0]
        ));
        $this->assertFalse($DB->record_exists('assignsubmission_datacheck', ['submission' => $submissions[0][0]->id]));
        $this->assertTrue($DB->record_exists('assignsubmission_datacheck', ['submission' => $submissions[0][1]->id]));
        $this->assertSame(3, $DB->count_records('assignsubmission_datacheck'));
    }

    /**
     * Context deletion must leave declarations in other assignments intact.
     */
    public function test_delete_context(): void {
        global $DB;
        [$assignments] = $this->create_declarations();
        $assignment = $assignments[0];
        provider::delete_submission_for_context(new assign_plugin_request_data($assignment->get_context(), $assignment));
        $this->assertSame(0, $DB->count_records('assignsubmission_datacheck', ['assignment' => $assignment->get_instance()->id]));
        $this->assertSame(2, $DB->count_records('assignsubmission_datacheck'));
    }

    /**
     * Bulk deletion selects only the requested users within the requested assignment.
     */
    public function test_bulk_delete(): void {
        global $DB;
        [$assignments, $submissions, $students] = $this->create_declarations();
        $assignment = $assignments[0];
        $request = new assign_plugin_request_data($assignment->get_context(), $assignment);
        $request->set_userids([$students[0]->id]);
        $request->populate_submissions_and_grades();
        provider::delete_submissions($request);
        $this->assertFalse($DB->record_exists('assignsubmission_datacheck', ['submission' => $submissions[0][0]->id]));
        $this->assertTrue($DB->record_exists('assignsubmission_datacheck', ['submission' => $submissions[0][1]->id]));
        $this->assertTrue($DB->record_exists('assignsubmission_datacheck', ['submission' => $submissions[1][0]->id]));
        $this->assertSame(3, $DB->count_records('assignsubmission_datacheck'));
    }

    /**
     * Empty bulk requests cannot delete any records.
     */
    public function test_empty_bulk_delete(): void {
        global $DB;
        [$assignments] = $this->create_declarations();
        $assignment = $assignments[0];
        provider::delete_submissions(new assign_plugin_request_data($assignment->get_context(), $assignment));
        $this->assertSame(4, $DB->count_records('assignsubmission_datacheck'));
    }
}
