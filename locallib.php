<?php
/**
 * Add a required information classification declaration to a Moodle assignment submission.
 * @package assignsubmission_datacheck
 */
defined('MOODLE_INTERNAL') || die();

class assign_submission_datacheck extends assign_submission_plugin {
    private function get_response(int $submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_datacheck', ['submission' => $submissionid]);
    }

    public function get_name() {
        return get_string('pluginname', 'assignsubmission_datacheck');
    }

    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
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

    public function view_summary(stdClass $submission, &$showviewlink) {
        $showviewlink = false;
        return $this->view($submission);
    }

    public function is_empty(stdClass $submission) {
        // Show the declaration alongside real work, without making it count as work itself.
        if (!$this->get_response((int)$submission->id)) {
            return true;
        }
        foreach ($this->assignment->get_submission_plugins() as $plugin) {
            if ($plugin !== $this && $plugin->is_enabled() && $plugin->is_visible() &&
                    $plugin->allow_submissions() && !$plugin->is_empty($submission)) {
                return false;
            }
        }
        return true;
    }

    public function submission_is_empty(stdClass $data) {
        return true;
    }

    public function precheck_submission($submission) {
        // Moodle may call this before a submission record exists.
        $record = $submission ? $this->get_response((int)$submission->id) : false;
        if (!$record || !in_array($record->answer, ['yes', 'no', 'unsure'], true)) {
            return get_string('required', 'assignsubmission_datacheck');
        }
        return true;
    }

    public function remove(stdClass $submission) {
        global $DB;
        $DB->delete_records('assignsubmission_datacheck', ['submission' => $submission->id]);
        return true;
    }

    public function delete_instance() {
        global $DB;
        $DB->delete_records('assignsubmission_datacheck',
            ['assignment' => $this->assignment->get_instance()->id]);
        return true;
    }

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
