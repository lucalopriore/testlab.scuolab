<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */

define('NO_OUTPUT_BUFFERING', true);
require_once('../../../../config.php');
require_once($CFG->libdir . '/dataformatlib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');
require_login();


$id = optional_param("id", NULL, PARAM_INT);
$start_date = optional_param("start_date", NULL, PARAM_INT);
$end_date = optional_param("end_date", NULL, PARAM_INT);
$dataformat = optional_param("format", "csv", PARAM_ALPHA);


$whereClauses = array();
if (is_numeric($start_date) && $start_date > 0) {
    $whereClauses[] = "e.absolute_time >= $filterData->start_date";
}

if (is_numeric($end_date) && $end_date > 0) {
    $whereClauses[] = "e.absolute_time <= $filterData->end_date";
}

if (is_numeric($filterData->experience)) {
    $whereClauses[] = "s.activity_id = $filterData->experience";
}

$whereClause = "";
if (count($whereClauses) > 0) {
    $whereClause = "WHERE " . join(' AND ', $whereClauses);
}

$sql = "SELECT e.id as event_id, s.id as session_id, a.id as activity_id, a.name as activity_name, u.id as user_id, u.firstname, u.lastname, e.absolute_time, e.event_id as event_type, e.data, e.relative_time
        FROM {scuolab_activity_event} e
        JOIN {scuolab_activity_session} s
        ON s.id = e.session_id
        JOIN {course_modules} m
        ON m.id = s.activity_id
        JOIN {scuolab} a
        ON a.id = m.instance
        JOIN {user} u
        ON u.id = s.user_id
        $whereClause
        ORDER BY e.session_id, e.relative_time";

$events = $DB->get_records_sql($sql, array());

$recordData = new ArrayObject();

foreach ($events as $event) {
    $user = "$event->firstname $event->lastname";
    $recordData->append(array(
        'id' => $event->session_id,
        'experience' => $event->activity_name,
        'user' => $user,
        'date' => userdate($event->absolute_time),
        'action' => actionToString($event->event_type, $event->data),
        'time' => $event->relative_time,
        'activity_id' => $event->activity_id,
        'user_id' => $event->user_id,
        'event_type' => $event->event_type,
        'event_data' => $event->data,
    ));
}

$filename = clean_filename("session_data");
$fields = ['ID', 'Experience', 'User', 'Date', 'Action', 'Time', 'Activity ID', 'User ID', 'Event Type ID', 'Event data'];
$iterator = $recordData->getIterator();

download_as_dataformat($filename, $dataformat, $fields, $iterator, NULL);
