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
 * Add a required information classification declaration to a Moodle assignment submission.
 * @package assignsubmission_datacheck
 * @copyright 2026 HAMK
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Assignment submission plugin for privacy declarations.
 */
class assign_submission_datacheck extends assign_submission_plugin {
    /**
     * Get the declaration associated with a submission.
     *
     * @param int $submissionid Submission ID.
     * @return stdClass|false Declaration record, or false if none exists.
     */
    private function get_response(int $submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_datacheck', ['submission' => $submissionid]);
    }

    /**
     * Get the translated submission type name.
     *
     * @return string Submission type name.
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_datacheck');
    }

    // phpcs:disable moodle.Commenting.ValidTags.Invalid -- Required PHPMD annotation.
    /**
     * Add the privacy question and restore the saved answer.
     *
     * The method name is fixed by Moodle's submission plugin API.
     *
     * @param stdClass|null $submission Submission record, if available.
     * @param MoodleQuickForm $mform Submission form.
     * @param stdClass $data Form data, populated with the saved answer.
     * @return bool Whether form elements were added.
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        // phpcs:enable moodle.Commenting.ValidTags.Invalid
        $field = 'datacheck_answer';
        $submissionid = $submission ? (int)$submission->id : 0;
        if ($submissionid && !isset($data->$field)) {
            $record = $this->get_response($submissionid);
            if ($record) {
                $data->$field = $record->answer;
            }
        }
        $options = [
            '' => get_string('choose', 'assignsubmission_datacheck'),
            'no' => get_string('answer_no', 'assignsubmission_datacheck'),
            'yes' => get_string('answer_yes', 'assignsubmission_datacheck'),
            'unsure' => get_string('answer_unsure', 'assignsubmission_datacheck'),
        ];
        $mform->addElement('select', $field, get_string('question', 'assignsubmission_datacheck'), $options);
        $mform->setType($field, PARAM_ALPHA);
        $mform->addRule($field, get_string('required', 'assignsubmission_datacheck'), 'required', null, 'client');
        $mform->addElement('static', 'datacheck_help', '', get_string('guidance', 'assignsubmission_datacheck'));
        return true;
    }

    /**
     * Save a valid declaration for the submission.
     *
     * @param stdClass $submission Submission record.
     * @param stdClass $data Submitted form data.
     * @return bool Whether the declaration was saved.
     */
    public function save(stdClass $submission, stdClass $data) {
        global $DB;
        $answer = $data->datacheck_answer ?? '';
        if (!in_array($answer, ['yes', 'no', 'unsure'], true)) {
            $this->set_error(get_string('required', 'assignsubmission_datacheck'));
            return false;
        }
        $record = $this->get_response((int)$submission->id);
        if (!$record) {
            $record = (object)[
                'assignment' => (int)$this->assignment->get_instance()->id,
                'submission' => (int)$submission->id,
                'answer' => $answer,
                'timemodified' => time(),
            ];
            return (bool)$DB->insert_record('assignsubmission_datacheck', $record);
        }
        $record->answer = $answer;
        $record->timemodified = time();
        return $DB->update_record('assignsubmission_datacheck', $record);
    }

    /**
     * Render the declaration and any instructions visible to the current user.
     *
     * @param stdClass $submission Submission record.
     * @return string Rendered declaration.
     */
    public function view(stdClass $submission) {
        $record = $this->get_response((int)$submission->id);
        if (!$record) {
            return '-';
        }
        $output = html_writer::tag('strong', s($this->get_name()));
        $output .= html_writer::tag('p', s(get_string('question', 'assignsubmission_datacheck')));
        $answer = get_string('answer_' . $record->answer, 'assignsubmission_datacheck');
        $output .= html_writer::tag('p', s(get_string('response', 'assignsubmission_datacheck', $answer)));
        if (has_capability('mod/assign:grade', $this->assignment->get_context())) {
            $output .= html_writer::tag('p', format_text(
                get_string('teacher_guidance', 'assignsubmission_datacheck'),
                FORMAT_HTML,
                ['para' => false]
            ));
        }
        return html_writer::div($output, 'assignsubmission_datacheck_summary');
    }

    /**
     * Render the declaration in the submission summary.
     *
     * @param stdClass $submission Submission record.
     * @param bool $showviewlink Whether to show a separate view link.
     * @return string Rendered declaration.
     */
    public function view_summary(stdClass $submission, &$showviewlink) {
        $showviewlink = false;
        return $this->view($submission);
    }

    /**
     * Check whether the declaration has real submission content alongside it.
     *
     * @param stdClass $submission Submission record.
     * @return bool Whether the declaration should be treated as empty.
     */
    public function is_empty(stdClass $submission) {
        // Show the declaration alongside real work, without making it count as work itself.
        if (!$this->get_response((int)$submission->id)) {
            return true;
        }
        foreach ($this->assignment->get_submission_plugins() as $plugin) {
            if (
                $plugin !== $this && $plugin->is_enabled() && $plugin->is_visible() &&
                    $plugin->allow_submissions() && !$plugin->is_empty($submission)
            ) {
                return false;
            }
        }
        return true;
    }

    // phpcs:disable moodle.Commenting.ValidTags.Invalid -- Required PHPMD annotation.
    /**
     * A declaration alone never counts as content, regardless of the form data required by the parent API.
     *
     * @param stdClass $data Submission form data required by the parent API.
     * @return bool Always true because a declaration alone is not submission content.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function submission_is_empty(stdClass $data) {
        // phpcs:enable moodle.Commenting.ValidTags.Invalid
        return true;
    }

    /**
     * Require a valid saved declaration before final submission.
     *
     * @param stdClass|null $submission Submission record, if available.
     * @return bool|string True if valid, otherwise the validation error.
     */
    public function precheck_submission($submission) {
        // Moodle may call this before a submission record exists.
        $record = $submission ? $this->get_response((int)$submission->id) : false;
        if (!$record || !in_array($record->answer, ['yes', 'no', 'unsure'], true)) {
            return get_string('required', 'assignsubmission_datacheck');
        }
        return true;
    }

    /**
     * Remove the declaration for one submission.
     *
     * @param stdClass $submission Submission record.
     * @return bool Whether removal succeeded.
     */
    public function remove(stdClass $submission) {
        global $DB;
        $DB->delete_records('assignsubmission_datacheck', ['submission' => $submission->id]);
        return true;
    }

    /**
     * Remove all declarations belonging to this assignment.
     *
     * @return bool Whether deletion succeeded.
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records(
            'assignsubmission_datacheck',
            ['assignment' => $this->assignment->get_instance()->id]
        );
        return true;
    }

    /**
     * Copy a declaration to a new submission attempt.
     *
     * @param stdClass $sourcesubmission Source submission record.
     * @param stdClass $destsubmission Destination submission record.
     * @return bool Whether copying succeeded.
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;
        $record = $this->get_response((int)$sourcesubmission->id);
        if ($record) {
            unset($record->id);
            $record->submission = $destsubmission->id;
            $record->timemodified = time();
            $DB->insert_record('assignsubmission_datacheck', $record);
        }
        return true;
    }
}
