<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */

define('NO_OUTPUT_BUFFERING', true);

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup("scuolab_event_report_list");

$title = get_string('event_report', 'tool_scuolab_manager');
$pagenumber = optional_param('page', 0, PARAM_INT);

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);


$experiencesRecord = $DB->get_records($table = "scuolab", $conditions = null, $sort = "name", $fields = "id,name");
$experiences = array("all" => get_string("filter_all", "tool_scuolab_manager"));
foreach ($experiencesRecord as $key => $record) {
    $experiences[$key] = $record->name;
}

// Create the filter form for the table.
$filterSection = new tool_scuolab_manager_list_filter_form(null, array(
    'experiences' => $experiences
), 'post');

$start_date = optional_param('start_date', 0, PARAM_INT);
$end_date = optional_param('end_date', 0, PARAM_INT);
$experience = optional_param('experience', 'all', PARAM_ALPHA);



if ($filterSection->is_cancelled()) {
    $filterSection->clear();
} else if ($filterSection->is_submitted() == false) {
    $filterSection->load($start_date, $end_date, $experience);
}
$filterSection->display();
if ($filterSection->is_submitted()) {
    $filterData = $filterSection->get_data();
} else {
    $filterData = new stdClass();
    $filterData->start_date = $start_date;
    $filterData->end_date = $end_date;
    $filterData->experience = $experience;
}

$downloadParams = "?start=$filterData->start_date&end=$filterData->end_date&id=$filterData->experience";
echo $OUTPUT->single_button("download.php$downloadParams", get_string('download_bare', 'tool_scuolab_manager'), $method = 'get', array());
echo $OUTPUT->single_button("downloaddetails.php$downloadParams", get_string('download_detailed', 'tool_scuolab_manager'), $method = 'get', array());

$whereClauses = array();
if ($filterData->start_date > 0) {
    $whereClauses[] = "e.absolute_time >= $filterData->start_date";
}

if ($filterData->end_date > 0) {
    $whereClauses[] = "e.absolute_time <= $filterData->end_date";
}

if (is_numeric($filterData->experience)) {
    $whereClauses[] = "s.activity_id = $filterData->experience";
}

$whereClause = "";
if (count($whereClauses) > 0) {
    $whereClause = "WHERE " . join(' AND ', $whereClauses);
}

$pagesize = 15;
$offset = $pagesize * $pagenumber;
$totalCount = $DB->get_record_sql(
    "SELECT COUNT(DISTINCT s.id) AS c
    FROM {scuolab_activity_session} s
    JOIN {scuolab_activity_event} e
    ON s.id = e.session_id   
    $whereClause"
)->c;

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
        ORDER BY s.id DESC
        LIMIT $pagesize
        OFFSET $offset";

$sessions = $DB->get_records_sql($sql, array());



$table = new flexible_table('tool_event_report_table');
$url = new moodle_url($PAGE->url, array(
    'start_date' => $filterData->start_date,
    'end_Date' => $filterData->end_date,
    'experience' => $filterData->experience,
));
$table->define_baseurl($url);
$table->pagesize($pagesize, $totalCount);
$table->define_columns(array('date', 'user', 'experience', 'duration', 'view'));
$table->define_headers(array(
    get_string('event_report_table_date', 'tool_scuolab_manager'),
    get_string('event_report_table_user', 'tool_scuolab_manager'),
    get_string('event_report_table_experience', 'tool_scuolab_manager'),
    get_string('event_report_table_duration', 'tool_scuolab_manager'),
    get_string('event_report_table_view', 'tool_scuolab_manager'),
));


$table->set_attribute('id', 'tool_event_report_table');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

foreach ($sessions as $item) {
    $item->user = $item->firstname . " " . $item->lastname;
    $table->add_data(array(
        userdate($item->date), $item->user, $item->experience, $item->duration,
        $OUTPUT->single_button("details.php?id={$item->id}", get_string('event_report_table_view', 'tool_scuolab_manager'), $method = 'get', array()),
    ));
}

$table->finish_output();

echo $OUTPUT->footer();
