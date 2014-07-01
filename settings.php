<?php


defined('MOODLE_INTERNAL') || die;

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reporttoverview', get_string('toverview', 'report_toverview'), "$CFG->wwwroot/report/toverview/index.php"));

// no report settings
$settings = null;