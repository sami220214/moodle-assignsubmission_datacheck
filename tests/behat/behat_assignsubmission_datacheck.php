<?php
/**
 * Navigation shared by Moodle 4.5 and later grading screens.
 *
 * @package assignsubmission_datacheck
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

/** Open the real AJAX grading screen without relying on version-specific menu labels. */
class behat_assignsubmission_datacheck extends behat_base {
    /**
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
