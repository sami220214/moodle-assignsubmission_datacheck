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
 * Navigation shared by Moodle 4.5 and later grading screens.
 *
 * @package assignsubmission_datacheck
 * @copyright 2026 HAMK
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

/**
 * Open the real AJAX grading screen without relying on version-specific menu labels.
 */
class behat_assignsubmission_datacheck extends behat_base {
    /**
     * Open the AJAX grading panel for the specified student and assignment.
     *
     * @Given /^I open the data check grading panel for "([^"]*)" in "([^"]*)"$/
     * @param string $username Student username.
     * @param string $assignmentname Assignment name, unique in this scenario.
     */
    public function open_grading_panel(string $username, string $assignmentname): void {
        global $DB;
        $student = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $assignment = $DB->get_record('assign', ['name' => $assignmentname], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('assign', $assignment->id, $assignment->course, false, MUST_EXIST);
        $url = new moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'grader', 'userid' => $student->id]);
        $this->getSession()->visit($url->out(false));
    }
}
