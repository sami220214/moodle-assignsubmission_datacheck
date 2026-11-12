@assignsubmission_datacheck @mod @mod_assign @javascript
Feature: Students declare submission data and teachers understand the answer
  In order to handle assignment material appropriately
  As a student and teacher
  I need a required declaration and a clearly explained answer

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | teacher1 | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activity" exists:
      | activity                            | assign     |
      | course                              | C1         |
      | name                                | Data check |
      | submissiondrafts                    | 0          |
      | assignsubmission_onlinetext_enabled  | 1          |
      | assignsubmission_file_enabled        | 0          |
      | assignsubmission_datacheck_enabled   | 1          |

  Scenario Outline: Every allowed answer permits a substantive submission
    Given I am on the "Data check" Activity page logged in as student1
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text                                                                                      | My assignment content |
      | Does your submission contain personal data, sensitive information, or confidential information? | <answer>              |
    And I press "Save changes"
    Then I should see "Submitted for grading"
    And I should see "Student response: <answer>"
    And I should see "Submission data protection check"
    And I should not see "This is the student's own declaration"

    Examples:
      | answer   |
      | Yes      |
      | No       |
      | Not sure |

  Scenario: The declaration is required before saving
    Given I am on the "Data check" Activity page logged in as student1
    When I press "Add submission"
    And I set the field "Online text" to "My assignment content"
    And I press "Save changes"
    Then I should see "Answer the data protection question before saving."
    And I should not see "Submitted for grading"

  Scenario: Editing recalls the previous selection and saves its replacement
    Given I am on the "Data check" Activity page logged in as student1
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text                                                                                      | My assignment content |
      | Does your submission contain personal data, sensitive information, or confidential information? | Not sure              |
    And I press "Save changes"
    When I press "Edit submission"
    Then the field "Does your submission contain personal data, sensitive information, or confidential information?" matches value "unsure"
    When I set the field "Does your submission contain personal data, sensitive information, or confidential information?" to "No"
    And I press "Save changes"
    Then I should see "Student response: No"
    And I should not see "Student response: Not sure"

  Scenario: Teachers see context and guidance in the individual grading panel
    Given I am on the "Data check" Activity page logged in as student1
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text                                                                                      | My assignment content |
      | Does your submission contain personal data, sensitive information, or confidential information? | Not sure              |
    And I press "Save changes"
    And I log out
    When I am on the "Data check" Activity page logged in as teacher1
    And I open the data check grading panel for "student1" in "Data check"
    Then I should see "Student response: Not sure"
    And I should see "This is the student's own declaration"
    And I should see "The answer does not prevent submission."

  Scenario: A disabled declaration plugin adds no question
    Given the following "activity" exists:
      | activity                           | assign         |
      | course                             | C1             |
      | name                               | Ordinary task  |
      | assignsubmission_onlinetext_enabled | 1              |
      | assignsubmission_file_enabled       | 0              |
      | assignsubmission_datacheck_enabled  | 0              |
    And I am on the "Ordinary task" Activity page logged in as student1
    When I press "Add submission"
    Then I should not see "Does your submission contain personal data, sensitive information, or confidential information?"
