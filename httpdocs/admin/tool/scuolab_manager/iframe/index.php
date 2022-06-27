<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */

define('NO_OUTPUT_BUFFERING', true);

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$title = get_string('iframe_manager', 'tool_scuolab_manager');

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);


echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$sql = "SELECT sl.id,sl.name,sl.start_date,sl.end_date,COUNT(DISTINCT sle.id) as exp_num, COUNT(DISTINCT slc.id) as seat_num
        FROM {scuolab_license} sl
        LEFT JOIN {scuolab_license_experience} as sle ON sl.id = sle.license_id
        LEFT JOIN {scuolab_license_code} as slc on sl.id = slc.license_id
        GROUP BY sl.id";

$licenseData = $DB->get_records_sql($sql, array());



$table = new flexible_table('tool_iframemanager_table');
$table->define_columns(array('name', 'start date', 'end date', 'experiences', 'seats', "edit", 'delete', 'download'));
$table->define_headers(array(
    get_string('iframe_manager_table_name', 'tool_scuolab_manager'),
    get_string('iframe_manager_table_start_date', 'tool_scuolab_manager'),
    get_string('iframe_manager_table_end_date', 'tool_scuolab_manager'),
    get_string('iframe_manager_table_experiences', 'tool_scuolab_manager'),
    get_string('iframe_manager_table_seats', 'tool_scuolab_manager'),
    get_string('edit', 'tool_scuolab_manager'),
    get_string('delete', 'tool_scuolab_manager'),
    get_string('download'),
));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'tool_iframemanager_table');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();
foreach ($licenseData as $item) {
    $table->add_data(array(
        $item->name, userdate($item->start_date), userdate($item->end_date), $item->exp_num, $item->seat_num,
        $OUTPUT->single_button("edit.php?id={$item->id}", get_string('edit', 'tool_scuolab_manager'), $method = 'get', array()),
        $OUTPUT->single_button("delete.php?id={$item->id}", get_string('delete', 'tool_scuolab_manager'), $method = 'get', array()),
        $OUTPUT->single_button("download.php?id={$item->id}", get_string('download'), $method = 'get', array())
    ));
}
$table->finish_output();

echo $OUTPUT->single_button("edit.php", get_string('createnew', 'tool_scuolab_manager'), $method = 'post', array());

echo $OUTPUT->footer();
