<?php

/**
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */

require('../../config.php');
//global $USER, $DB;

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'scuolab');
$experience = $DB->get_record('scuolab', array('id' => $cm->instance), '*', MUST_EXIST);

if ($experience->storeexportedfile == 0) {
    echo "Service unavailable";
    return;
}

$url = new moodle_url('/mod/scuolab/export.php', array('id' => $cm->id));
$PAGE->set_url($url);
require_login($course, false, $cm);
$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);
$usercontext = context_user::instance($USER->id);

$export = $_POST['export'];

$fs = get_file_storage();
$timestamp = date('Y-m-d H-i', time());

// Prepare file record object
$fileinfo = array(
    'contextid' => $contextmodule->id,     // ID of context
    'component' => 'mod_scuolab',       // usually = table name
    'filearea' => 'export',            // usually = table name
    'itemid' => $USER->id,     // usually = ID of row in table
    'filepath' => '/',               // any path beginning and ending in /
    'filename' =>  "{$USER->lastname} {$USER->firstname} [{$timestamp}].xlsx",   // any filename
    'userid' => $USER->id
);

$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

if ($file) {
    $file->delete();
    $DB->delete_record('scuolab_exportedfiles', array('moduleid' => $cm->id, 'userid' => $USER->id));
}
// Create file containing text 'hello world'
$file = $fs->create_file_from_string($fileinfo, $export, $USER->id);

// $exportRecord = new stdClass();
// $exportRecord->moduleid = $cm->id;
// $exportRecord->userid = $USER->id;

// $exportId = $DB->insert_record('scuolab_exportedfiles', $exportRecord);

echo "stored";
