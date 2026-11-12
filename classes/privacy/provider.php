<?php
namespace assignsubmission_datacheck\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;

class provider implements
        \core_privacy\local\metadata\provider,
        \mod_assign\privacy\assignsubmission_provider,
        \mod_assign\privacy\assignsubmission_user_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('assignsubmission_datacheck', [
            'assignment' => 'privacy:metadata:assignment',
            'submission' => 'privacy:metadata:submission',
            'answer' => 'privacy:metadata:answer',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:table');
        return $collection;
    }

    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
        // mod_assign already identifies contexts through assign_submission.
    }

    public static function get_student_user_ids(\mod_assign\privacy\useridlist $useridlist) {
        // User IDs are already represented by assign_submission.
    }

    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // No independent user identifiers are stored in this plugin's table.
    }

    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        global $DB;
        if ($exportdata->get_user() !== null) {
            return;
        }
        $submission = $exportdata->get_pluginobject();
        if (!$submission) {
            return;
        }
        $entry = $DB->get_record('assignsubmission_datacheck', ['submission' => $submission->id]);
        if (!$entry) {
            return;
        }
        $path = $exportdata->get_subcontext();
        $path[] = get_string('privacy:path', 'assignsubmission_datacheck');
        $data = (object)[
            'answer' => get_string('answer_' . $entry->answer, 'assignsubmission_datacheck'),
            'timemodified' => \core_privacy\local\request\transform::date($entry->timemodified),
        ];
        writer::with_context($exportdata->get_context())->export_data($path, $data);
    }

    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        global $DB;
        $DB->delete_records('assignsubmission_datacheck', ['assignment' => $requestdata->get_assignid()]);
    }

    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata) {
        global $DB;
        $submission = $deletedata->get_pluginobject();
        if ($submission) {
            $DB->delete_records('assignsubmission_datacheck', [
                'assignment' => $deletedata->get_assignid(), 'submission' => $submission->id,
            ]);
        }
    }

    public static function delete_submissions(assign_plugin_request_data $deletedata) {
        global $DB;
        $ids = $deletedata->get_submissionids();
        if (!$ids) {
            return;
        }
        list($sql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params['assignmentid'] = $deletedata->get_assignid();
        $DB->delete_records_select('assignsubmission_datacheck',
            "assignment = :assignmentid AND submission $sql", $params);
    }
}
