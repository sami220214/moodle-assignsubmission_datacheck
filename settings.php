<?php
defined('MOODLE_INTERNAL') || die();
$settings->add(new admin_setting_configcheckbox(
    'assignsubmission_datacheck/default',
    new lang_string('default', 'assignsubmission_datacheck'),
    new lang_string('default_help', 'assignsubmission_datacheck'),
    1
));
