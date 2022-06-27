<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */


require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');


$id = required_param("id", PARAM_INT);
$delete = optional_param("delete", NULL, PARAM_ALPHANUM);

$licenseData = $DB->get_record('scuolab_license', array('id' => $id));


$PAGE->set_url('/admin/tool/iframe/delete.php', array('id' => $id));
$PAGE->set_pagelayout('admin');
navigation_node::override_active_url(new moodle_url('/admin/tool/iframe/index.php', array()));

// Check if we've got confirmation.
if ($delete === md5($licenseData->start_date)) {
    // We do - time to delete the course.
    require_sesskey();

    $str_deletinglicense = get_string("deletinglicense", "tool_scuolab_manager", $licenseData->name);

    $PAGE->navbar->add($str_deletinglicense);
    $PAGE->set_title("$SITE->shortname: $str_deletinglicense");
    $PAGE->set_heading($SITE->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($str_deletinglicense);
    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();

    $DB->delete_records('scuolab_license_code', array('license_id' => $licenseData->id));
    $DB->delete_records('scuolab_license_experience', array('license_id' => $licenseData->id));
    $DB->delete_records('scuolab_license', array('id' => $licenseData->id));

    echo $OUTPUT->heading(get_string("deletedlicense", "tool_scuolab_manager", $licenseData->name));

    echo $OUTPUT->continue_button('./index.php');
    echo $OUTPUT->footer();
    exit; // We must exit here!!!
}

$str_deletecheck = get_string("deletecheck", "tool_scuolab_manager", $licenseData->name);
$PAGE->navbar->add($str_deletecheck);
$PAGE->set_title("$SITE->shortname: $str_deletecheck");
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

$exphirationDate = userdate($licenseData->end_date);
$str_deletelicensecheck = get_string("deletelicensecheck", 'tool_scuolab_manager');
$message = "{$str_deletelicensecheck}<br /><br />{$licenseData->name} expiring on {$exphirationDate}";

$continueurl = new moodle_url('./delete.php', array('id' => $licenseData->id, 'delete' => md5($licenseData->start_date)));
$continuebutton = new single_button($continueurl, get_string('delete', 'tool_scuolab_manager'), 'post');
echo $OUTPUT->confirm($message, $continuebutton, './index.php');


echo $OUTPUT->footer();
