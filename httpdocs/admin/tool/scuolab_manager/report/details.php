<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */

define('NO_OUTPUT_BUFFERING', true);

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');
admin_externalpage_setup("scuolab_event_report_list");

$sessionId = required_param('id', PARAM_INT);

$title = get_string('event_report', 'tool_scuolab_manager');

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);




echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$sql = "SELECT e.relative_time AS time, event_id, data
        FROM {scuolab_activity_event} e
        WHERE e.session_id = $sessionId
        ORDER BY e.relative_time";

$events = $DB->get_records_sql($sql, array());


$table = new flexible_table('tool_event_report_details_table');
$table->define_columns(array('time', 'action'));
$table->define_headers(array(
    get_string('event_report_table_time', 'tool_scuolab_manager'),
    get_string('event_report_table_action', 'tool_scuolab_manager'),
));

$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'tool_event_report_table_details');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

foreach ($events as $item) {
    $action = actionToString($item->event_id, $item->data);
    $table->add_data(array(
        $item->time, $action
    ));
}

$table->finish_output();

echo $OUTPUT->footer();
