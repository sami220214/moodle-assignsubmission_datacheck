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
 * Privacy metadata, export and deletion for submission declarations.
 *
 * @package assignsubmission_datacheck
 * @copyright 2026 HAMK
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_datacheck\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;

/**
 * Privacy provider for assignment submission declarations.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \mod_assign\privacy\assignsubmission_provider,
    \mod_assign\privacy\assignsubmission_user_provider {
    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection to populate.
     * @return collection Updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('assignsubmission_datacheck', [
            'assignment' => 'privacy:metadata:assignment',
            'submission' => 'privacy:metadata:submission',
            'answer' => 'privacy:metadata:answer',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:table');
        return $collection;
    }

    // phpcs:disable moodle.Commenting.ValidTags.Invalid -- Required PHPMD annotation.
    /**
     * Context discovery is handled by mod_assign; parameters are required by its privacy interface.
     *
     * @param int $userid User whose submission contexts are being collected.
     * @param contextlist $contextlist Context collection managed by mod_assign.
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
        // phpcs:enable moodle.Commenting.ValidTags.Invalid
        // Moodle already identifies contexts through assign_submission.
    }

    // phpcs:disable moodle.Commenting.ValidTags.Invalid -- Required PHPMD annotation.
    /**
     * Student discovery is handled by mod_assign; the parameter is required by its privacy interface.
     *
     * @param \mod_assign\privacy\useridlist $useridlist Student ID collection managed by mod_assign.
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function get_student_user_ids(\mod_assign\privacy\useridlist $useridlist) {
        // phpcs:enable moodle.Commenting.ValidTags.Invalid
        // User IDs are already represented by assign_submission.
    }

    // phpcs:disable moodle.Commenting.ValidTags.Invalid -- Required PHPMD annotation.
    /**
     * No independent user IDs need adding; the parameter is required by the privacy interface.
     *
     * @param \core_privacy\local\request\userlist $userlist User collection for the requested context.
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // phpcs:enable moodle.Commenting.ValidTags.Invalid
        // No independent user identifiers are stored in this plugin's table.
    }

    /**
     * Export the declaration for the requested student submission.
     *
     * @param assign_plugin_request_data $exportdata Submission export request.
     * @return void
     */
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

    /**
     * Delete declarations in the requested assignment context.
     *
     * @param assign_plugin_request_data $requestdata Context deletion request.
     * @return void
     */
    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        global $DB;
        $DB->delete_records('assignsubmission_datacheck', ['assignment' => $requestdata->get_assignid()]);
    }

    /**
     * Delete the declaration for the requested user submission.
     *
     * @param assign_plugin_request_data $deletedata User deletion request.
     * @return void
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $deletedata) {
        global $DB;
        $submission = $deletedata->get_pluginobject();
        if ($submission) {
            $DB->delete_records('assignsubmission_datacheck', [
                'assignment' => $deletedata->get_assignid(), 'submission' => $submission->id,
            ]);
        }
    }

    /**
     * Delete only the requested submissions within the assignment.
     *
     * @param assign_plugin_request_data $deletedata Bulk deletion request.
     * @return void
     */
    public static function delete_submissions(assign_plugin_request_data $deletedata) {
        global $DB;
        $ids = $deletedata->get_submissionids();
        if (!$ids) {
            return;
        }
        [$sql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $params['assignmentid'] = $deletedata->get_assignid();
        $DB->delete_records_select(
            'assignsubmission_datacheck',
            "assignment = :assignmentid AND submission $sql",
            $params
        );
    }
}
