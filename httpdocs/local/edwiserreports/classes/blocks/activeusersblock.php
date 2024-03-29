<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Reports abstract block will define here to which will extend for each repoers blocks
 *
 * @package     local_edwiserreports
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edwiserreports;

defined('MOODLE_INTERNAL') or die;

use stdClass;
use moodle_url;
use cache;
use html_writer;
use html_table;
use core_user;

require_once($CFG->dirroot . '/local/edwiserreports/classes/block_base.php');

/**
 * Active users block.
 */
class activeusersblock extends block_base {
    /**
     * Get the first site access data.
     *
     * @var null
     */
    public $firstsiteaccess;

    /**
     * Current time
     *
     * @var int
     */
    public $timenow;

    /**
     * Active users block labels
     *
     * @var array
     */
    public $labels;

    /**
     * No. of labels for active users.
     *
     * @var int
     */
    public $xlabelcount;

    /**
     * Cache
     *
     * @var object
     */
    public $cache;

    /**
     * Dates main array.
     *
     * @var array
     */
    public $dates = [];

    /**
     * Instantiate object
     *
     * @param int $blockid Block id
     */
    public function __construct($blockid = false) {
        parent::__construct($blockid);
        // Set cache for active users block.
        $this->cache = cache::make('local_edwiserreports', 'activeusers');
        $this->precalculated = get_config('local_edwiserreports', 'precalculated');
    }

    /**
     * Preapre layout for each block
     * @return object Layout
     */
    public function get_layout() {
        global $CFG;

        // Layout related data.
        $this->layout->id = 'activeusersblock';
        $this->layout->name = get_string('activeusersheader', 'local_edwiserreports');
        $this->layout->info = get_string('activeusersblocktitlehelp', 'local_edwiserreports');
        $this->layout->morelink = new moodle_url($CFG->wwwroot . "/local/edwiserreports/activeusers.php");
        $this->layout->downloadlinks = $this->get_block_download_links();
        $this->layout->filters = $this->get_activeusers_filter();

        // Selected default filters.
        $this->layout->filter = 'weekly';
        $this->layout->cohortid = '0';

        // Block related data.
        $this->block = new stdClass();
        $this->block->displaytype = 'line-chart';

        // Add block view in layout.
        $this->layout->blockview = $this->render_block('activeusersblock', $this->block);

        // Set block edit capabilities.
        $this->set_block_edit_capabilities($this->layout->id);

        // Return blocks layout.
        return $this->layout;
    }

    /**
     * Prepare active users block filters
     */
    public function get_activeusers_filter() {
        // Add last updated text in header.
        $lastupdatetext = html_writer::start_tag('small', array(
            'id' => 'updated-time',
            'class' => 'font-size-12'
        ));
        $lastupdatetext .= get_string('lastupdate', 'local_edwiserreports');
        $lastupdatetext .= html_writer::tag('i', '', array(
            'class' => 'refresh fa fa-refresh px-1',
            'data-toggle' => 'tooltip',
            'title' => get_string('refresh', 'local_edwiserreports')
        ));
        $lastupdatetext .= html_writer::end_tag('small');

        // Prepare filter HTML for active users block.
        $filterhtml = html_writer::start_tag('form', array("action" => "javascript:void(0)"));
        $filterhtml .= html_writer::start_tag('div', array('class' => 'd-flex mt-1'));
        $filterhtml .= html_writer::tag('button', get_string('lastweek', 'local_edwiserreports'), array(
            'type' => 'button',
            'class' => 'btn btn-sm dropdown-toggle',
            'data-toggle' => 'dropdown',
            'id' => 'filter-dropdown',
            'aria-expanded' => 'false'
        ));
        $filterhtml .= html_writer::start_tag('div', array(
            'class' => 'dropdown-menu',
            'aria-labelledby' => 'filter-dropdown',
            'role' => 'menu'
        ));
        $filterhtml .= html_writer::tag('div', '', array(
            'id' => 'activeUser-calendar',
            'class' => 'dropdown-calendar'
        ));
        $filterhtml .= html_writer::start_tag('div', array('class' => 'dropdown-body'));

        // Prepare filter link.
        $datefilter = html_writer::empty_tag('input', array(
            'class' => 'dropdown-item border-0 custom p-0',
            'id' => 'flatpickrCalender',
            'placeholder' => get_string('custom', 'local_edwiserreports'),
            'data-input'
        ));
        $filteropt = array(
            'weekly' => array(
                'name' => get_string('lastweek', 'local_edwiserreports'),
                'value' => 'weekly',
                'classes' => ''
            ),
            'monthly' => array(
                'name' => get_string('lastmonth', 'local_edwiserreports'),
                'value' => 'monthly',
                'classes' => ''
            ),
            'yearly' => array(
                'name' => get_string('lastyear', 'local_edwiserreports'),
                'value' => 'yearly',
                'classes' => ''
            ),
            'custom' => array(
                'name' => $datefilter,
                'value' => 'custom',
                'classes' => 'custom'
            )
        );

        // Prepare dropdown items for active users filter.
        foreach ($filteropt as $value) {
            $filterhtml .= html_writer::link('javascript:void(0)', $value['name'], array(
                'class' => 'dropdown-item ' . $value['classes'],
                'role' => 'menuitem',
                'value' => $value['value']
            ));
        }

        // End tags.
        $filterhtml .= html_writer::end_tag('div');
        $filterhtml .= html_writer::end_tag('div');
        $filterhtml .= html_writer::end_tag('div');
        $filterhtml .= html_writer::end_tag('form');

        // Create filter for active users block.
        $filters = html_writer::start_tag('div');
        $filters .= html_writer::tag('div', $lastupdatetext);
        $filters .= html_writer::start_tag('div');
        $filters .= $filterhtml;
        $filters .= html_writer::end_tag('div');
        $filters .= html_writer::end_tag('div');

        return $filters;
    }

    /**
     * Get the first log from the log table
     * @return stdClass | bool firstlog
     */
    public function get_first_log() {
        global $DB;
        $cachekey = "activeusers-first-log";

        // Get logs from cache.
        if (!$firstlogs = $this->cache->get($cachekey)) {
            $fields = 'id, userid, timecreated';
            $firstlogs = $DB->get_record('logstore_standard_log', array(), $fields, IGNORE_MULTIPLE);

            // Set cache if log is not available.
            $this->cache->set($cachekey, $firstlogs);
        }

        return $firstlogs;
    }

    /**
     * Generate labels for active users block.
     */
    public function generate_labels($timeperiod) {

        $this->dates = [];
        $this->labels = [];
        $this->enddate = floor(time() / 86400 + 1) * 86400 - 1;
        switch ($timeperiod) {
            case 'weekly':
                // Monthly days.
                $this->xlabelcount = LOCAL_SITEREPORT_WEEKLY_DAYS;
                $this->startdate = (floor($this->enddate / 86400) - LOCAL_SITEREPORT_WEEKLY_DAYS) * 86400;
                break;
            case 'monthly':
                // Yearly days.
                $this->xlabelcount = LOCAL_SITEREPORT_MONTHLY_DAYS;
                $this->startdate = (floor($this->enddate / 86400) - LOCAL_SITEREPORT_MONTHLY_DAYS) * 86400;
                break;
            case 'yearly':
                // Weekly days.
                $this->xlabelcount = LOCAL_SITEREPORT_YEARLY_DAYS;
                $this->startdate = (floor($this->enddate / 86400) - LOCAL_SITEREPORT_YEARLY_DAYS) * 86400;
                break;
            default:
                // Explode dates from custom date filter.
                $dates = explode(" to ", $timeperiod);
                if (count($dates) == 2) {
                    $startdate = strtotime($dates[0]." 00:00:00");
                    $enddate = strtotime($dates[1]." 23:59:59");
                }
                // If it has correct startdat and end date then count xlabel.
                if (isset($startdate) && isset($enddate)) {
                    $days = round(($enddate - $startdate) / LOCAL_SITEREPORT_ONEDAY);
                    $this->xlabelcount = $days;
                    $this->startdate = $startdate;
                    $this->enddate = $enddate;
                } else {
                    $this->xlabelcount = LOCAL_SITEREPORT_WEEKLY_DAYS; // Default one week.
                    $this->startdate = (floor($this->enddate / 86400) - LOCAL_SITEREPORT_WEEKLY_DAYS) * 86400;
                }
                break;
        }

        // Generate date label.
        $labelcallback = function($value) {
            return date('d M y', $value);
        };
        if ($this->graphajax == true) {
            $labelcallback = function($value) {
                return $value * 1000;
            };
        }

        // Get all lables.
        for ($i = $this->xlabelcount - 1; $i >= 0; $i--) {
            $time = $this->enddate - $i * LOCAL_SITEREPORT_ONEDAY;
            $this->dates[floor($time / LOCAL_SITEREPORT_ONEDAY)] = 0;
            $this->labels[] = $labelcallback($time);
        }
    }

    /**
     * Generate cache key for blocks
     * @param  string $blockname Block name
     * @param  string    $filter    Filter
     * @param  int    $cohortid  Cohort id
     * @return string            Cache key
     */
    public function generate_cache_key($blockname, $filter, $cohortid = 0) {
        $cachekey = $blockname . "-" . $this->filter . "-";

        if ($cohortid) {
            $cachekey .= $cohortid;
        } else {
            $cachekey .= "all";
        }

        return $cachekey;
    }

    /**
     * Get active user, enrolment, completion
     * @param  object $params date filter to get data
     * @return object         Active users graph data
     */
    public function get_data($params = false) {
        ob_start();

        // Get data from params.
        $this->filter = isset($params->filter) ? $params->filter : false;
        $this->cohortid = isset($params->cohortid) ? $params->cohortid : false;
        $this->graphajax = isset($params->graphajax) ? $params->graphajax : false;

        // Generate active users data label.
        $this->generate_labels($this->filter);

        // Check pre calculated data.
        if ($this->precalculated) {
            $data = get_config('local_edwiserreports', 'activeusersdata');
            $data = json_decode($data, true);
            if ($data !== null && isset($data[$this->filter])) {
                $response = new stdClass();
                $response->data = new stdClass();

                $response->data->activeUsers = $data[$this->filter]['activeusers'];
                $response->data->enrolments = $data[$this->filter]['enrolments'];
                $response->data->completionRate = $data[$this->filter]['completionrate'];
                $response->labels = $this->labels;

                return $response;
            }
        }

        // Get cache key.
        $cachekey = $this->generate_cache_key("activeusers-response", $this->filter . '-' . $this->graphajax, $this->cohortid);

        // If response is in cache then return from cache.
        if (!$response = $this->cache->get($cachekey)) {
            $response = new stdClass();
            $response->data = new stdClass();

            $response->data->activeUsers = $this->get_active_users();
            $response->data->enrolments = $this->get_enrolments();
            $response->data->completionRate = $this->get_course_completionrate();
            $response->labels = $this->labels;

            // Set response in cache.
            $this->cache->set($cachekey, $response);
        }

        ob_clean();
        return $response;
    }

    /**
     * Get users list data for active users block
     * Columns are (Full Name, Email)
     * @param  string $filter   Time filter to get users for this day
     * @param  string $action   Get users list for this action
     * @param  int    $cohortid Cohort Id
     * @return array            array of users list
     */
    public static function get_userslist($filter, $action, $cohortid = false) {
        global $DB;
        // If cohort ID is there then add cohort filter in sqlquery.
        $sqlcohort = "";
        $cohortcondition = "";
        if ($cohortid) {
            $sqlcohort .= " JOIN {cohort_members} cm
                   ON cm.userid = l.relateduserid";
            $cohortcondition = "AND cm.cohortid = :cohortid";
            $params["cohortid"] = $cohortid;
        }
        // Based on action prepare query.
        switch($action) {
            case "activeusers":
                $sql = "SELECT DISTINCT l.userid as relateduserid
                   FROM {logstore_standard_log} l $sqlcohort
                   WHERE l.timecreated >= :starttime
                   AND l.timecreated < :endtime
                   AND l.action = :action
                   AND l.userid > 1 $cohortcondition";
                $params["action"] = 'viewed';
                break;
            case "enrolments":
                $sql = "SELECT DISTINCT(CONCAT(CONCAT(l.courseid, '-'), l.relateduserid )) as id,
                                l.relateduserid,
                                l.courseid
                        FROM {logstore_standard_log} l $sqlcohort
                        WHERE l.timecreated >= :starttime
                        AND l.timecreated < :endtime
                        AND l.eventname = :eventname $cohortcondition
                        GROUP BY l.relateduserid, l.courseid";
                $params["eventname"] = '\core\event\user_enrolment_created';
                break;
            case "completions";
                $sqlcohort = "";
                if ($cohortid) {
                    $sqlcohort .= " JOIN {cohort_members} cm
                           ON cm.userid = l.userid";
                    $params["cohortid"] = $cohortid;
                }
                $sql = "SELECT CONCAT(CONCAT(l.userid, '-'), l.courseid) as id,
                             l.userid as relateduserid,
                             l.courseid as courseid
                        FROM {edwreports_course_progress} l $sqlcohort
                        WHERE l.completiontime IS NOT NULL
                        AND l.completiontime >= :starttime
                        AND l.completiontime < :endtime $cohortcondition";
        }

        $params["starttime"] = $filter;
        $params["endtime"] = $filter + LOCAL_SITEREPORT_ONEDAY;
        $data = array();
        $records = $DB->get_records_sql($sql, $params);
        if (!empty($records)) {
            foreach ($records as $record) {
                $user = core_user::get_user($record->relateduserid);
                $userdata = new stdClass();
                $userdata->username = fullname($user);
                $userdata->useremail = $user->email;
                if ($action == "completions" || $action == "enrolments") {
                    if ($DB->record_exists('course', array('id' => $record->courseid))) {
                        $course = get_course($record->courseid);
                        $userdata->coursename = $course->fullname;
                    } else {
                        $userdata->coursename = get_string('eventcoursedeleted');
                    }
                }
                $data[] = array_values((array)$userdata);
            }
        }

        return $data;
    }

    /**
     * Get all active users
     * @return array           Array of all active users based
     */
    public function get_active_users() {
        global $DB;

        $params = array(
            "starttime" => $this->startdate,
            "endtime" => $this->enddate,
            "action" => "viewed"
        );


        // Get Logs to generate active users data.
        $activeusers = $this->dates;

        // Query to get activeusers from logs.
        $cohortjoin = "";
        $cohortcondition = "";
        if ($this->cohortid) {
            $cohortjoin = "JOIN {cohort_members} cm ON l.userid = cm.userid";
            $cohortcondition = "AND cm.cohortid = :cohortid";
            $params["cohortid"] = $this->cohortid;
        }
        $sql = "SELECT FLOOR(l.timecreated/86400) as userdate,
                    COUNT( DISTINCT l.userid ) as usercount
                    FROM {logstore_standard_log} l
                    $cohortjoin
                WHERE l.action = :action
                    $cohortcondition
                    AND l.timecreated >= :starttime
                    AND l.timecreated < :endtime
                    AND l.userid > 1
                GROUP BY FLOOR(l.timecreated/86400)";

        $logs = $DB->get_records_sql($sql, $params);
        // Get active users for every day.
        foreach (array_keys($activeusers) as $key) {
            if (!isset($logs[$key])) {
                continue;
            }
            $activeusers[$key] = $logs[$key]->usercount;
        }

        $activeusers = array_values($activeusers);

        /* Reverse the array because the graph take
        value from left to right */
        return $activeusers;
    }

    /**
     * Get all Enrolments
     * @return array            Array of all active users based
     */
    public function get_enrolments() {
        global $DB;

        $params = array(
            "starttime" => $this->startdate,
            "endtime" => $this->enddate,
            "eventname" => '\core\event\user_enrolment_created',
            "actionname" => "created"
        );

        $cohortjoin = "";
        $cohortcondition = "";
        if ($this->cohortid) {
            $cohortjoin = "JOIN {cohort_members} cm ON l.relateduserid = cm.userid";
            $cohortcondition = "AND cm.cohortid = :cohortid";
            $params["cohortid"] = $this->cohortid;
        }

        $sql = "SELECT FLOOR(l.timecreated/86400) as userdate,
                    COUNT(
                        DISTINCT(
                            CONCAT(
                                CONCAT(l.courseid, '-')
                                , l.relateduserid
                            )
                        )
                    )
                    as usercount
                FROM {logstore_standard_log} l
                $cohortjoin
                WHERE l.eventname = :eventname
                $cohortcondition
                AND l.action = :actionname
                AND l.timecreated >= :starttime
                AND l.timecreated < :endtime
                GROUP BY FLOOR(l.timecreated/86400)";


        // Get enrolments log.
        $logs = $DB->get_records_sql($sql, $params);
        $enrolments = $this->dates;

        // Get enrolments from every day.
        foreach (array_keys($enrolments) as $key) {
            if (!isset($logs[$key])) {
                continue;
            }
            $enrolments[$key] = $logs[$key]->usercount;
        }

        $enrolments = array_values($enrolments);

        /* Reverse the array because the graph take
        value from left to right */
        return $enrolments;
    }

    /**
     * Get all Enrolments
     * @return array            Array of all active users based
     */
    public function get_course_completionrate() {
        global $DB;

        $params = array(
            "starttime" => $this->startdate,
            "endtime" => $this->enddate,
        );

        // Prepare cache key for completion rate.
        $cachekey = $this->generate_cache_key('activeusers-completionrate', $this->filter, $this->cohortid);

        $cohortjoin = "";
        $cohortcondition = "";
        if ($this->cohortid) {
            $cohortjoin = "JOIN {cohort_members} cm ON cc.userid = cm.userid";
            $cohortcondition = "AND cm.cohortid = :cohortid";
            $params["cohortid"] = $this->cohortid;
        }

        $sql = "SELECT FLOOR(cc.completiontime/86400) as userdate,
                       COUNT(
                           CONCAT(
                               CONCAT(cc.courseid, '-'),
                               cc.userid
                           )
                       ) as usercount
                  FROM {edwreports_course_progress} cc
                       $cohortjoin
                 WHERE cc.completiontime IS NOT NULL
                    AND cc.completiontime >= :starttime
                    AND cc.completiontime < :endtime
                       $cohortcondition
                 GROUP BY FLOOR(cc.completiontime/86400)";

        $completionrate = $this->dates;
        $logs = $DB->get_records_sql($sql, $params);

        // Get completion for each day.
        foreach (array_keys($completionrate) as $key) {
            if (!isset($logs[$key])) {
                continue;
            }
            $completionrate[$key] = $logs[$key]->usercount;
        }

        $completionrate = array_values($completionrate);

        /* Reverse the array because the graph take
        value from left to right */
        return $completionrate;
    }


    /**
     * Get Exportable data for Active Users Block
     * @param  string $filter Filter to get data from specific range
     * @return array          Array of exportable data
     */
    public function get_exportable_data_block($filter) {

        // Get exportable data for active users block.
        $export = array();

        $obj = new self();
        $export[] = self::get_header();
        $activeusersdata = $obj->get_data((object) array("filter" => $filter));

        // Generate active users data.
        foreach ($activeusersdata->labels as $key => $lable) {
            $export[] = array(
                $lable,
                $activeusersdata->data->activeUsers[$key],
                $activeusersdata->data->enrolments[$key],
                $activeusersdata->data->completionRate[$key],
            );
        }

        return $export;
    }

    /**
     * Get Exportable data for Active Users Page
     * @param  string $filter Filter to get data from specific range
     * @return array          Array of exportable data
     */
    public static function get_exportable_data_report($filter) {

        $export = array();

        $blockobj = new self();
        $export[] = self::get_header_report();
        $cohortid = optional_param('cohortid', 0, PARAM_INT);
        $activeusersdata = $blockobj->get_data((object) array(
            "filter" => $filter,
            'cohortid' => $cohortid
        ));
        foreach ($activeusersdata->labels as $lable) {
            $export = array_merge($export,
                self::get_usersdata($lable, "activeusers", $cohortid),
                self::get_usersdata($lable, "enrolments", $cohortid),
                self::get_usersdata($lable, "completions", $cohortid)
            );
        }

        return $export;
    }

    /**
     * Get User Data for Active Users Block
     * @param  string $lable    Date for lable
     * @param  string $action   Action for getting data
     * @param  string $cohortid Cohortid
     * @return array            User data
     */
    public static function get_usersdata($lable, $action, $cohortid) {
        $usersdata = array();
        $users = self::get_userslist(strtotime($lable), $action, $cohortid);

        foreach ($users as $user) {
            $user = array_merge(
               array($lable),
               $user
            );

            // If course is not set then skip one block for course
            // Add empty value in course header.
            if (!isset($user[3])) {
                $user = array_merge($user, array(''));
            }

            $user = array_merge($user, array(get_string($action . "_status", "local_edwiserreports")));
            $usersdata[] = $user;
        }

        return $usersdata;
    }

    /**
     * Get header for export data actvive users
     * @return array Array of headers of exportable data
     */
    public static function get_header() {
        $header = array(
            get_string("date", "local_edwiserreports"),
            get_string("noofactiveusers", "local_edwiserreports"),
            get_string("noofenrolledusers", "local_edwiserreports"),
            get_string("noofcompletedusers", "local_edwiserreports"),
        );

        return $header;
    }

    /**
     * Get header for export data actvive users individual page
     * @return array Array of headers of exportable data
     */
    public static function get_header_report() {
        $header = array(
            get_string("date", "local_edwiserreports"),
            get_string("fullname", "local_edwiserreports"),
            get_string("email", "local_edwiserreports"),
            get_string("coursename", "local_edwiserreports"),
            get_string("status", "local_edwiserreports"),
        );

        return $header;
    }

    /**
     * Get popup modal header by action
     * @param  string $action Action name
     * @return array          Table header
     */
    public static function get_modal_table_header($action) {
        switch($action) {
            case 'completions':
                // Return table header.
                $str = array(
                    get_string("fullname", "local_edwiserreports"),
                    get_string("email", "local_edwiserreports"),
                    get_string("coursename", "local_edwiserreports")
                );
                break;
            case 'enrolments':
                // Return table header.
                $str = array(
                    get_string("fullname", "local_edwiserreports"),
                    get_string("email", "local_edwiserreports"),
                    get_string("coursename", "local_edwiserreports")
                );
                break;
            default:
                // Return table header.
                $str = array(
                    get_string("fullname", "local_edwiserreports"),
                    get_string("email", "local_edwiserreports")
                );
        }

        return $str;
    }

    /**
     * Create users list table for active users block
     * @param  string $filter   Time filter to get users for this day
     * @param  string $action   Get users list for this action
     * @param  int    $cohortid Get users list for this action
     * @return array            Array of users data fields (Full Name, Email)
     */
    public static function get_userslist_table($filter, $action, $cohortid) {
        // Make cache.
        $cache = cache::make('local_edwiserreports', 'activeusers');
        // Get values from cache if it is set.
        $cachekey = "userslist-" . $filter . "-" . $action . "-" . "-" . $cohortid;
        if (!$table = $cache->get($cachekey)) {
            $table = new html_table();

            // Get table header.
            $table->head = self::get_modal_table_header($action);

            // Set table attributes.
            $table->attributes = array (
                "class" => "modal-table table",
                "style" => "min-width: 100%;"
            );

            // Get Users data.
            $data = self::get_userslist($filter, $action, $cohortid);

            // Set table cell.
            if (!empty($data)) {
                $table->data = $data;
            }

            // Set cache for users list.
            $cache->set($cachekey, $table);
        }

        return html_writer::table($table);
    }
}
