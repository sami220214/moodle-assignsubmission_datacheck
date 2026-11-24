# HAMK – Submission privacy check

[Suomeksi](README.md)

HAMK Submission privacy check helps students identify personal data, sensitive information, or confidential information in their Moodle assignment submissions. It adds a required declaration to the submission form and shows the saved answer to students and graders, helping teachers decide when to discuss the material with a student before processing or sharing it further.

Plugin component: `assignsubmission_datacheck`. Current release: stable version 1.0.3. Licensed under the [GNU GPL v3 or later](LICENSE).

## Features

- Asks students whether their submission contains personal data, sensitive information, or confidential information, with **Yes**, **No**, and **Not sure** options.
- Requires an answer when saving through the assignment submission form and restores the saved selection when editing.
- Displays the declaration in the submission summary and provides graders with guidance on interpreting the answer, including considerations before using AI to assist with feedback.
- Lets teachers enable or disable the check for each assignment; administrators can set the default for new assignments.
- Works alongside **File submissions** or **Online text** for the actual assignment content.
- Includes English and Finnish language strings and Privacy API methods for exporting and deleting stored declaration data.

## Requirements and Moodle versions

- Requires Moodle 4.5 or later, with the Assignment activity available. Use the PHP and database versions required by your Moodle installation.
- The intended Moodle version range is 4.5 through 5.3. PHPUnit tests have been run on Moodle 4.5.15+ and 5.3+. Moodle 5.0, 5.1, and 5.2 have undergone source code and API reviews, but have not been runtime tested.
- Enable **File submissions** or **Online text** in each assignment as well as this plugin: a declaration alone does not count as submission content.

Browser acceptance testing is incomplete. See [QA.md](QA.md) (in Finnish) for test results and outstanding checks, and **Key limitations** below before deployment. Course backup and restore support for declaration data is not implemented. Mobile app and web service submission workflows still require integration testing.

## How it works

Adds a required question with three answer options to the **Edit submission** form in assignments where the plugin is enabled. The answer is stored in the `assignsubmission_datacheck` table and linked to the Moodle assignment and submission ID (`assign_submission.id`). The saved answer is shown to the student and grader in the submission summary. Answers **Yes** and **Not sure** do not prevent saving the submission: the question provides guidance.

## Installation

**Moodle 4.5 and 5.0:** place the `datacheck` directory under the Moodle root at `mod/assign/submission/datacheck/`. Run `php admin/cli/upgrade.php` as the web server user, or open **Site administration → Notifications** in Moodle.

**Moodle 5.1 and later:** use the path `public/mod/assign/submission/datacheck/` and the command `php public/admin/cli/upgrade.php`.

Enable the submission type in the site administration plugin settings (**Plugins → Assignment plugins → Submission plugins**) and in the test assignment's **Submission types** settings. Also keep file submissions or online text enabled for the actual assignment content. The privacy answer is displayed alongside the saved assignment content; an answer alone does not count as submission content.

If upgrading an existing installation, replace the plugin files and run the upgrade again. Version 0.1.2 does not require database schema changes. If the new language strings do not appear, purge Moodle caches at **Site administration → Development → Purge caches**.

Version 0.1.2 adds the Finnish and English language strings (`enabled` and `enabled_help`) required by the help icon in the assignment settings. This fixes the message `Help title string does not exist: [enabled, assignsubmission_datacheck]`.

Version 0.1.5-beta displays HTML formatting in the teacher's instructions, such as bold text and line breaks. This change does not require database schema changes.

## Stored data

In version 0.1.3, the submission summary displays the privacy check title, the question presented to the student, and the answer label. Users with grading permission also see instructions on interpreting the answer and taking further action. Students see the title, question, and answer.

Fields in the `assignsubmission_datacheck` table:

- `id`: record ID.
- `assignment`: assignment ID (`assign.id`).
- `submission`: submission ID (`assign_submission.id`); unique.
- `answer`: answer (`yes` / `no` / `unsure`).
- `timemodified`: timestamp of the latest change.

Moodle's `assign_submission` record identifies the user or group and the submission attempt. Submission content is not copied. Changing the answer replaces the previous value; no change history is stored.

## Key limitations

- PHPUnit tests cover Moodle 4.5.15+ and 5.3+; browser test results and the plugin versions tested are documented separately in [QA.md](QA.md). Moodle 5.0, 5.1, and 5.2 have undergone source code and API reviews, but no runtime tests. The tests do not guarantee correct operation in every use case.
- Students must answer the question the first time they edit a submission. A previously saved selection is restored on the form on subsequent visits.
- The question provides *guidance*. It does not automatically inspect content, prevent data leaks (DLP), or provide a legal basis for processing data.
- Moodle group submissions share an `assign_submission` record. Group-specific consent and individual declarations require separate design work.
- Behaviour in the mobile app, web service APIs, and quick submissions requires separate integration testing. It may be possible to bypass the question, so the requirement to answer must not be claimed to cover every submission method.
- **Backup and restore support has not been implemented** for the plugin's database table. Do not use the plugin in production for data that must survive restoring a course from a backup.
- The export and deletion methods of Moodle's Privacy API have been tested with PHPUnit. The complete Moodle data request process and privacy requests involving group submissions still require separate acceptance tests.
- The plugin is separate from Moodle's submission statement and does not replace it.

## Suggested acceptance tests

1. Create an assignment with the privacy check and file submissions enabled.
2. As a student, open **Add submission**. The question requires selecting **Yes**, **No**, or **Not sure**.
3. Try saving without selecting an answer. The form should report the missing answer.
4. Upload a test file and save with the answer **Yes**. Check the answer in the student's submission summary, the teacher's grading table, and the individual submission grading view. Repeat with **No** and **Not sure**.
5. Edit the submission. Check that the saved selection appears on the form. Change the answer and verify that the change is saved.
6. Save only a privacy answer, without a file or online text. The submission should remain empty. Also remove all files and online text from an existing submission and verify that the remaining privacy answer does not count as assignment content.
7. Enable the separate final submission button and verify that a valid answer allows the submission to be submitted. Enable the privacy check in an assignment that already has a file submission but no privacy answer. Final submission should be blocked with the question's error message until the student edits the submission and answers the question.
8. Separately test a new submission attempt, the teacher's grading view, group submissions, and the Privacy API. Backup and restore support is not available, so answers cannot be relied upon to survive restoring from a backup.

## GitHub Actions CI

The workflow in [`.github/workflows/ci.yml`](.github/workflows/ci.yml) runs Moodle Plugin CI on pushes and pull requests, and can also be started manually from GitHub's **Actions** tab. It checks Moodle 4.5 and Moodle's `main` development branch with PHP 8.3 on PostgreSQL and MariaDB, plus Moodle 4.5 with PHP 8.1 on MariaDB. Testing `main` does not establish support for a future Moodle release.

Checks include PHP syntax, PHP Mess Detector, Moodle coding standards and PHPDoc, plugin validation, upgrade savepoints, Mustache and Grunt checks, PHPUnit, and Behat using Chrome. Failed Behat runs upload diagnostic artifacts for seven days. Actual results appear in GitHub Actions after the workflow is pushed; adding the configuration does not mean the checks have passed.

## Moodle PHPUnit regression tests

Once the Moodle PHPUnit test environment and test database have been configured, run the following from the Moodle 4.5 root directory:

```sh
vendor/bin/phpunit --test-suffix _test.php mod/assign/submission/datacheck/tests
```

From Moodle 5.1 onwards, prefix the test path with `public/`. The tests cover saving, editing, invalid answers, the form field, displaying answers, the group's shared answer, and exporting and deleting privacy data. They do not replace browser-based acceptance tests.

## Behat browser tests

The plugin's `tests/behat/declaration.feature` contains student and teacher acceptance scenarios. Run them only in a separate Moodle Behat environment whose test database and test data directory have been initialised according to Moodle's instructions:

```sh
vendor/bin/behat --config /path/to/behatdata/behatrun/behat/behat.yml --tags=@assignsubmission_datacheck --format=progress
```

The tests use JavaScript and require a working Selenium browser service. [QA.md](QA.md) lists the test cases, execution environments, results, and items still requiring manual checks.
