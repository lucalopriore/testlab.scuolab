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

$sql = "SELECT s.id, a.name AS experience, u.username, u.firstname, u.lastname, MIN(e.absolute_time) AS date, MAX(e.relative_time) AS duration
FROM {scuolab_activity_session} s
LEFT JOIN {user} u
ON s.user_id = u.id
LEFT JOIN {scuolab_activity_event} e
ON s.id = e.session_id
LEFT JOIN {course_modules} m
ON m.id = s.activity_id
LEFT JOIN {scuolab} a
ON a.id = m.instance
$whereClause
GROUP BY s.id
ORDER BY s.id DESC";

$sessions = $DB->get_records_sql($sql, array());

$recordData = new ArrayObject();

foreach ($sessions as $session) {
    $user = "$session->firstname $session->lastname";
    $recordData->append(array(
        'date' => userdate($session->date),
        'user' => $user,
        'experience' => $session->experience,
        'duration' => $session->duration
    ));
}

$filename = clean_filename("session_data");
$fields = ['Date', 'User', 'Experience', 'Duration'];
$iterator = $recordData->getIterator();

download_as_dataformat($filename, $dataformat, $fields, $iterator, NULL);
