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

use local_edwiserreports\controller\authentication;
use context_helper;
use context_course;
use context_system;
use moodle_url;
use cache;
use stdClass;

require_once($CFG->dirroot . '/local/edwiserreports/classes/block_base.php');

/**
 * Active users block.
 */
class studentengagementblock extends block_base {
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
    public $enddate;

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
        // Set cache for student engagement block.
        $this->sessioncache = cache::make('local_edwiserreports', 'studentengagement_session');
        $this->precalculated = get_config('local_edwiserreports', 'precalculated');
    }

    /**
     * Preapre layout for each block
     * @return object Layout
     */
    public function get_layout() {
        global $CFG, $USER;

        // Layout related data.
        $this->layout->id = 'studentengagementblock';
        $this->layout->name = get_string('studentengagementheader', 'local_edwiserreports');
        $this->layout->info = get_string('studentengagementblockhelp', 'local_edwiserreports');
        $filters = $this->get_studentengagement_filter();
        $exportfilters = [
            'visitsonlms' => 'weekly-0',
            'timespentonlms' => 'weekly-0',
            'timespentoncourse' => 'weekly-0-0',
            'courseactivitystatus' => 'weekly-0-0'
        ];
        $graphs = [
            'visitsonlms',
            'timespentonlms',
            'timespentoncourse',
            'courseactivitystatus'
        ];
        $this->block->graphs = [];
        foreach ($graphs as $graph) {
            $this->block->graphs[] = [
                'id' => 'studentengagementblock',
                'graph' => $graph,
                'header' => get_string($graph, 'local_edwiserreports'),
                "downloadurl" => $CFG->wwwroot . "/local/edwiserreports/download.php",
                'filter' => $exportfilters[$graph] . '-' . $graph,
                'filters' => $filters[$graph],
                'downloadlinks' => $this->get_block_download_links(),
                'morelink' => new moodle_url($CFG->wwwroot . "/local/edwiserreports/studentengagement.php"),
                'cohortid' => 0,
                'region' => 'block',
                'sesskey' => sesskey(),
                'editing' => isset($USER->editing) ? $USER->editing : 0
            ];
        }

        // Selected default filters.
        $this->layout->filter = 'weekly';
        $this->layout->cohortid = '0';

        // Add block view in layout.
        $this->layout->blockview = $this->render_block('studentengagementblock', $this->block);

        // Set block edit capabilities.
        $this->set_block_edit_capabilities($this->layout->id);

        // Return blocks layout.
        return $this->layout;
    }

    /**
     * Prepare active users block filters
     * @param  $onlycourses Return only courses dropdown for current user.
     * @return array filters array
     */
    public function get_studentengagement_filter($onlycourses = false) {
        global $OUTPUT, $USER, $COURSE, $USER, $DB;

        $courses = $this->get_courses_of_user($USER->id);

        unset($courses[$COURSE->id]);

        $users = $this->get_users_of_courses($USER->id, $courses);

        array_unshift($users, (object)[
            'id' => 0,
            'fullname' => get_string('allusers', 'search')
        ]);

        array_unshift($courses, (object)[
            'id' => 0,
            'fullname' => get_string('fulllistofcourses')
        ]);

        // Return only courses array if $onlycourses is true.
        if ($onlycourses == true) {
            return $courses;
        }

        return [
            'visitsonlms' => $OUTPUT->render_from_template('local_edwiserreports/studentengagementblockfilters', [
                'filter-id' => 'visitsonlms',
                'showcourses' => false,
                'students' => $users
            ]),
            'timespentonlms' => $OUTPUT->render_from_template('local_edwiserreports/studentengagementblockfilters', [
                'filter-id' => 'timespentonlms',
                'showcourses' => false,
                'students' => $users
            ]),
            'timespentoncourse' => $OUTPUT->render_from_template('local_edwiserreports/studentengagementblockfilters', [
                'filter-id' => 'timespentoncourse',
                'showcourses' => true,
                'courses' => $courses,
                'students' => $users
            ]),
            'courseactivitystatus' => $OUTPUT->render_from_template('local_edwiserreports/studentengagementblockfilters', [
                'filter-id' => 'courseactivitystatus',
                'showcourses' => true,
                'courses' => $courses,
                'students' => $users
            ])
        ];
    }

    /**
     * Generate cache key for blocks
     * @param  string $blockname Block name
     * @param  string    $filter    Filter
     * @param  int    $cohortid  Cohort id
     * @return string            Cache key
     */
    public function generate_cache_key($blockname, $filter, $cohortid = 0) {
        $cachekey = $blockname . "-" . $filter . "-";

        if ($cohortid) {
            $cachekey .= $cohortid;
        } else {
            $cachekey .= "all";
        }

        return $cachekey;
    }

    /**
     * Generate labels and dates array for graph
     *
     * @param string $timeperiod Filter time period Weekly/Monthly/Yearly or custom dates.
     */
    private function generate_labels($timeperiod) {
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

        // Get all lables.
        for ($i = $this->xlabelcount - 1; $i >= 0; $i--) {
            $time = $this->enddate - $i * LOCAL_SITEREPORT_ONEDAY;
            $this->dates[floor($time / LOCAL_SITEREPORT_ONEDAY)] = 0;
            $this->labels[] = $time * 1000;
        }
    }

    /**
     * Get data for visitsonlms graph.
     *
     * @param object $filter Filters
     *
     * @return array
     */
    public function get_visitsonlms_graph_data($filter) {
        global $DB;
        $userid = $filter->student;
        $timeperiod = $filter->date;
        $cachekey = $this->generate_cache_key('studentengagement', 'visitsonlms-' . $timeperiod . '-' . $userid);

        if (!$response = $this->sessioncache->get($cachekey)) {
            $this->generate_labels($timeperiod);
            $params = [
                'startdate' => floor($this->startdate / 86400),
                'enddate' => floor($this->enddate / 86400)
            ];
            $courses = $this->get_courses_of_user($this->get_current_user());
            // Temporary course table.
            $coursetable = 'tmp_stengage_courses';
            // Creating temporary table.
            utility::create_temp_table($coursetable, array_keys($courses));
            switch ($timeperiod . '-' . $userid . '-' . $this->precalculated) {
                case 'weekly-0-1':
                case 'monthly-0-1':
                case 'yearly-0-1':
                    $sql = "SELECT sd.datecreated, sum(" . $DB->sql_cast_char2int("sd.datavalue", true) . ") visits
                              FROM {edwreports_summary_detailed} sd
                              JOIN {{$coursetable}} ct ON sd.course = ct.tempid
                             WHERE " . $DB->sql_compare_text('sd.datakey', 255) . " = " . $DB->sql_compare_text(':datakey', 255) . "
                               AND sd.datecreated >= :startdate
                               AND sd.datecreated <= :enddate
                             GROUP BY sd.datecreated";
                    $params['datakey'] = 'studentengagement-visits';
                    break;
                default:

                    $wheresql = " JOIN {{$coursetable}} ct ON al.course = ct.tempid
                                 WHERE al.datecreated >= :startdate
                                   AND al.datecreated <= :enddate
                                   AND al.userid <> 0";

                    if ($userid !== 0) { // User is selected in dropdown.
                        $params['userid'] = $userid;
                        $wheresql .= ' AND al.userid = :userid ';
                    }

                    $sql = "SELECT al.datecreated, count(al.id) visits
                            FROM {edwreports_activity_log} al
                            $wheresql
                            GROUP BY al.datecreated";

                    break;
            }
            $logs = $DB->get_records_sql($sql, $params);
            utility::drop_temp_table($coursetable);
            foreach ($logs as $log) {
                if (!isset($this->dates[$log->datecreated])) {
                    continue;
                }
                $this->dates[$log->datecreated] = $log->visits;
            }
            $response = [
                'visits' => array_values($this->dates),
                'labels' => $this->labels
            ];

            // Set response in cache.
            $this->sessioncache->set($cachekey, $response);
        }
        return $response;
    }

    /**
     * Get data for timespentonlms graph.
     *
     * @param object $filter Filters
     *
     * @return array
     */
    public function get_timespentonlms_graph_data($filter) {
        global $DB;
        $userid = $filter->student;
        $timeperiod = $filter->date;
        $cachekey = $this->generate_cache_key('studentengagement', 'timespentonlms-' .$timeperiod . '-' . $userid);

        if (!$response = $this->sessioncache->get($cachekey)) {
            $this->generate_labels($timeperiod);

            $params = [
                'startdate' => floor($this->startdate / 86400),
                'enddate' => floor($this->enddate / 86400)
            ];

            $courses = $this->get_courses_of_user($this->get_current_user());
            // Temporary course table.
            $coursetable = 'tmp_stengage_courses';
            // Creating temporary table.
            utility::create_temp_table($coursetable, array_keys($courses));
            switch ($timeperiod . '-' . $userid . '-' . $this->precalculated) {
                case 'weekly-0-1':
                case 'monthly-0-1':
                case 'yearly-0-1':
                    $sql = "SELECT sd.datecreated, sum(" . $DB->sql_cast_char2int("sd.datavalue", true) . ") timespent
                              FROM {edwreports_summary_detailed} sd
                              JOIN {{$coursetable}} ct ON sd.course = ct.tempid
                             WHERE sd.datecreated >= :startdate
                               AND sd.datecreated <= :enddate
                               AND " . $DB->sql_compare_text('sd.datakey', 255) . " = " . $DB->sql_compare_text(':datakey', 255) . "
                             GROUP BY sd.datecreated";
                    $params['datakey'] = 'studentengagement-timespent';
                    break;
                default:
                    $wheresql = ' AND al.userid <> 0 ';
                    if ($userid !== 0) { // User is selected in dropdown.
                        $params['userid'] = $userid;
                        $wheresql = ' AND al.userid = :userid ';
                    }

                    $sql = "SELECT al.datecreated, sum(" . $DB->sql_cast_char2int("al.timespent") . ") timespent
                              FROM {edwreports_activity_log} al
                              JOIN {{$coursetable}} ct ON al.course = ct.tempid
                             WHERE al.datecreated >= :startdate
                               AND al.datecreated <= :enddate
                               $wheresql
                            GROUP BY al.datecreated";
                    break;
            }
            $logs = $DB->get_records_sql($sql, $params);

            utility::drop_temp_table($coursetable);
            foreach ($logs as $log) {
                if (!isset($this->dates[$log->datecreated])) {
                    continue;
                }
                $this->dates[$log->datecreated] = $log->timespent;
            }

            $response = [
                'timespent' => array_values($this->dates),
                'labels' => $this->labels
            ];

            // Set respose in cache.
            $this->sessioncache->set($cachekey, $response);
        }
        return $response;
    }

    /**
     * Generate courses labels and date boundaries for sql.
     *
     * @param string $timeperiod Filter time period Weekly/Monthly/Yearly or custom dates.
     * @param array $courses     Courses array
     */
    private function generate_courses_labels($timeperiod, $courses) {
        $this->enddate = floor(time() / 86400 + 1) * 86400 - 1;
        switch ($timeperiod) {
            case 'weekly':
                // Monthly days.
                $this->startdate = (($this->enddate / 86400) - LOCAL_SITEREPORT_WEEKLY_DAYS) * 86400;
                break;
            case 'monthly':
                // Yearly days.
                $this->startdate = (($this->enddate / 86400) - LOCAL_SITEREPORT_MONTHLY_DAYS) * 86400;
                break;
            case 'yearly':
                // Weekly days.
                $this->startdate = (($this->enddate / 86400) - LOCAL_SITEREPORT_YEARLY_DAYS) * 86400;
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
                    $this->startdate = $startdate;
                    $this->enddate = $enddate;
                } else {
                    $this->startdate = (($this->enddate / 86400) - LOCAL_SITEREPORT_WEEKLY_DAYS) * 86400;
                }
                break;
        }
        $this->courses = [];
        $this->labels = [];
        if (!empty($courses)) {
            foreach ($courses as $id => $course) {
                $this->courses[$id] = 0;
                $this->labels[$id] = $course->fullname;
            }
        }
    }

    /**
     * Get data for timespentoncourse graph.
     *
     * @param object $filter Filters
     *
     * @return array
     */
    public function get_timespentoncourse_graph_data($filter) {
        global $DB, $USER, $COURSE;
        $userid = $filter->student;
        $course = $filter->course;
        $timeperiod = $filter->date;

        $cachekey = $this->generate_cache_key(
            'studentengagement',
            'timespentoncourse-' . $timeperiod . '-' . $userid . '-' . $course
        );

        if (!$response = $this->sessioncache->get($cachekey)) {
            if ($course !== 0) { // Course is selected in dropdown.
                $this->generate_labels($timeperiod);
            } else {
                $courses = $this->get_courses_of_user($this->get_current_user());
                unset($courses[$COURSE->id]);
                $this->generate_courses_labels($timeperiod, $courses);
            }

            $params = [
                'startdate' => floor($this->startdate / 86400),
                'enddate' => floor($this->enddate / 86400)
            ];
            $wheresql = 'WHERE datecreated >= :startdate
            AND datecreated <= :enddate';

            switch ($timeperiod . '-' . $userid . '-' . $this->precalculated) {
                case 'weekly-0-1':
                case 'monthly-0-1':
                case 'yearly-0-1':
                    if ($course !== 0) { // Course is selected in dropdown.
                        $params['course'] = $course;
                        $wheresql .= ' AND course = :course ';

                        $sql = "SELECT datecreated, sum(" . $DB->sql_cast_char2int("datavalue", true) . ") timespent
                                  FROM {edwreports_summary_detailed}
                                       $wheresql
                                 GROUP BY datecreated";
                    } else {
                        $sql = "SELECT course, sum(" . $DB->sql_cast_char2int("datavalue", true) . ") timespent
                                  FROM {edwreports_summary}
                                   WHERE " . $DB->sql_compare_text('datakey', 255) . " = " . $DB->sql_compare_text(':datakey', 255) . "
                                 GROUP BY course";
                        $params['datakey'] = 'studentengagement-timespent-' . $timeperiod;
                    }
                    break;
                default:
                    if ($userid !== 0) { // User is selected in dropdown.
                        $params['userid'] = $userid;
                        $wheresql .= ' AND userid = :userid ';
                    }
                    if ($course !== 0) { // Course is selected in dropdown.
                        $params['course'] = $course;
                        $wheresql .= ' AND course = :course ';

                        $sql = "SELECT datecreated, sum(" . $DB->sql_cast_char2int("timespent") . ") timespent
                                FROM {edwreports_activity_log}
                                $wheresql
                                GROUP BY datecreated";

                    } else {
                        $sql = "SELECT course, sum(" . $DB->sql_cast_char2int("timespent") . ") timespent
                                FROM {edwreports_activity_log}
                                $wheresql
                                GROUP BY course";
                    }
                    break;
            }
            $logs = $DB->get_records_sql($sql, $params);
            if ($course !== 0) { // Course is selected in dropdown.

                foreach ($logs as $log) {
                    if (!isset($this->dates[$log->datecreated])) {
                        continue;
                    }
                    $this->dates[$log->datecreated] = $log->timespent;
                }
                $response = [
                    'timespent' => array_values($this->dates),
                    'labels' => array_values($this->labels)
                ];
            } else {
                $hasdata = false;
                foreach ($logs as $log) {
                    if (!isset($this->courses[$log->course])) {
                        continue;
                    }
                    if ($log->timespent > 0) {
                        $hasdata = true;
                    }
                    $this->courses[$log->course] = $log->timespent;
                }
                if (!$hasdata) {
                    $this->courses = [];
                }
                $response = [
                    'timespent' => array_values($this->courses),
                    'labels' => array_values($this->labels)
                ];
            }

            // Set response in cache.
            $this->sessioncache->set($cachekey, $response);
        }

        return $response;
    }

    /**
     * Get data for courseactivitystatus graph.
     *
     * @param object $filter Filters
     *
     * @return array
     */
    public function get_courseactivitystatus_graph_data($filter) {
        global $DB;
        $userid = $filter->student;
        $course = $filter->course;
        $timeperiod = $filter->date;
        $cachekey = $this->generate_cache_key('studentengagement', 'courseactivitystatus-' . $timeperiod . '-' . $userid);

        if (!$response = $this->sessioncache->get($cachekey)) {
            $this->generate_labels($timeperiod);
            $params = [
                'startdate' => $this->startdate,
                'enddate' => $this->enddate
            ];

            if ($course == 0) {
                $courses = $this->get_courses_of_user($this->get_current_user());
            } else {
                $courses = [$course => 'Dummy'];
            }
            // Temporary course table.
            $coursetable = 'tmp_stengage_courses';
            // Creating temporary table.
            utility::create_temp_table($coursetable, array_keys($courses));
            switch ($timeperiod . '-' . $course . '-' . $userid . '-' . $this->precalculated) {
                case 'weekly-0-0-1':
                case 'monthly-0-0-1':
                case 'yearly-0-0-1':
                    $subsql = "SELECT esd.datecreated subdate, sum(" . $DB->sql_cast_char2int("esd.datavalue", true) . ") submission
                                 FROM {{$coursetable}} ct
                                 JOIN {edwreports_summary_detailed} esd ON ct.tempid = esd.course
                                WHERE " . $DB->sql_compare_text('datakey', 255) . " = " . $DB->sql_compare_text(':subdatakey', 255) . "
                                GROUP BY esd.datecreated";
                    $params['subdatakey'] = 'studentengagement-courseactivity-submissions';

                    $comsql = "SELECT esd.datecreated subdate, sum(" . $DB->sql_cast_char2int("esd.datavalue", true) . ") completed
                                 FROM {{$coursetable}} ct
                                 JOIN {edwreports_summary_detailed} esd ON ct.tempid = esd.course
                                WHERE " . $DB->sql_compare_text('esd.datakey', 255) . " = " . $DB->sql_compare_text(':comdatakey', 255) . "
                                GROUP BY esd.datecreated";
                    $params['comdatakey'] = 'studentengagement-courseactivity-completions';
                    break;
                default:
                    $subsql = "SELECT floor(asub.timecreated / 86400) subdate, count(asub.id) submission
                              FROM {{$coursetable}} ct
                              JOIN {assign} a ON ct.tempid = a.course
                              JOIN {assign_submission} asub ON a.id = asub.assignment
                             WHERE asub.timecreated >= :startdate
                               AND asub.timecreated <= :enddate ";
                    $comsql = "SELECT floor(cmc.timemodified / 86400) comdate, count(cmc.id) completed
                                 FROM {{$coursetable}} ct
                                 JOIN {course_modules} cm ON ct.tempid = cm.course
                                 JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                                WHERE cmc.completionstate <> 0
                                  AND cmc.timemodified >= :startdate
                                  AND cmc.timemodified <= :enddate ";
                    if ($userid !== 0) { // User is selected in dropdown.
                        $subsql .= ' AND asub.userid = :userid ';
                        $comsql .= ' AND cmc.userid = :userid ';
                        $params['userid'] = $userid;
                    }
                    $subsql .= " GROUP BY floor(asub.timecreated / 86400)";
                    $comsql .= " GROUP BY floor(cmc.timemodified / 86400)";
                    break;
            }
            $sublogs = $DB->get_records_sql($subsql, $params);
            $comlogs = $DB->get_records_sql($comsql, $params);
            utility::drop_temp_table($coursetable);
            $completions = $submissions = $this->dates;
            $hasdata = false;
            foreach ($sublogs as $date => $log) {
                if (isset($submissions[$date])) {
                    $submissions[$date] = $log->submission;
                    if ($log->submission > 0) {
                        $hasdata = true;
                    }
                }
            }
            if ($hasdata == false) {
                $submissions = [];
            }

            $hasdata = false;
            foreach ($comlogs as $date => $log) {
                if (isset($completions[$date])) {
                    $completions[$date] = $log->completed;
                    if ($log->completed > 0) {
                        $hasdata = true;
                    }
                }
            }

            if ($hasdata == false) {
                $completions = [];
            }

            if (empty($submissions) && empty($completions)) {
                $this->labels = [];
            }

            $response = [
                'submissions' => array_values($submissions),
                'completions' => array_values($completions),
                'labels' => $this->labels
            ];

            // Set response in cache.
            $this->sessioncache->set($cachekey, $response);
        }
        return $response;
    }

    /**
     * Get users for more details table.
     *
     * @param int       $cohort         Cohort id
     * @param array     $coursetable    Courses table name
     * @param string    $search         Search query
     * @param int       $start          Starting row index of page
     * @param int       $length         Number of roows per page
     *
     * @return array
     */
    private function get_table_users($cohort, $coursetable, $search, $start, $length) {
        global $DB;

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'archetype' => 'student'
        ];

        $searchquery = '';
        $fullname = $DB->sql_fullname("u.firstname", "u.lastname");
        if (trim($search) !== '') {
            $params['search'] = "%$search%";
            $searchquery = 'AND ' . $DB->sql_like($fullname, ':search');
        }

        // If cohort ID is there then add cohort filter in sqlquery.
        $sqlcohort = "";
        if ($cohort) {
            $sqlcohort .= "JOIN {cohort_members} cm ON cm.userid = u.id AND cm.cohortid = :cohortid";
            $params["cohortid"] = $cohort;
        }

        $sql = "SELECT DISTINCT u.id, $fullname student
                  FROM {{$coursetable}} c
                  JOIN {context} ctx ON c.tempid = ctx.instanceid
                  JOIN {role_assignments} ra ON ctx.id = ra.contextid
                  JOIN {role} r ON ra.roleid = r.id
                  JOIN {user} u ON ra.userid = u.id
                  $sqlcohort
                 WHERE ctx.contextlevel = :contextlevel
                   AND r.archetype = :archetype
                   $searchquery";
        $users = $DB->get_records_sql($sql, $params, $start, $length);

        $countsql = "SELECT count(DISTINCT u.id)
                       FROM {{$coursetable}} c
                       JOIN {context} ctx ON c.tempid = ctx.instanceid
                       JOIN {role_assignments} ra ON ctx.id = ra.contextid
                       JOIN {role} r ON ra.roleid = r.id
                       JOIN {user} u ON ra.userid = u.id
                       $sqlcohort
                      WHERE ctx.contextlevel = :contextlevel
                        AND r.archetype = :archetype
                        $searchquery";
        $count = $DB->count_records_sql($countsql, $params);
        return [$users, $count];
    }

    /**
     * Get total timespent on lms data for table.
     *
     * @param string $userstable User table name
     *
     * @return array
     */
    private function get_table_timespentonmls($userstable) {
        global $DB;
        $sql = "SELECT al.userid id, sum(" . $DB->sql_cast_char2int("al.timespent") . ") timespent
                  FROM {{$userstable}} u
                  JOIN {edwreports_activity_log} al ON u.tempid = al.userid
                 GROUP BY al.userid";
        return $DB->get_records_sql($sql);
    }

    /**
     * Get total timespent on course data for table.
     *
     * @param string $userstable    User table name
     * @param string $coursetable   Course table name
     *
     * @return array
     */
    private function get_table_timespentoncourse($userstable, $coursetable) {
        global $DB;
        $sql = "SELECT al.userid id, sum(" . $DB->sql_cast_char2int("al.timespent") . ") timespent
                  FROM {{$userstable}} u
                  JOIN {edwreports_activity_log} al ON u.tempid = al.userid
                  JOIN {{$coursetable}} c ON c.tempid = al.course
                 GROUP BY al.userid";
        return $DB->get_records_sql($sql);
    }

    /**
     * Get total assingment submmited in course data for table.
     *
     * @param int    $userid        Current user id
     * @param string $userstable    User table name
     * @param string $coursetable   Course table name
     *
     * @return array
     */
    private function get_table_assignmentsubmitted($userid, $usertable, $coursetable) {
        global $DB;
        $sql = "SELECT u.tempid id, count(sub.id) submitted
                FROM {{$usertable}} u
                JOIN {assign_submission} sub ON u.tempid = sub.userid
                JOIN {assign} a ON sub.assignment = a.id ";
        if (!is_siteadmin($userid) && has_capability('moodle/site:configview', context_system::instance(), $userid)) {
            $sql .= "JOIN {{$coursetable}} c ON c.tempid = a.course ";
        }
        $sql .= "GROUP BY u.tempid";
        return $DB->get_records_sql($sql);
    }

    /**
     * Get total number of activities completed in course data for table.
     *
     * @param int    $userid        Current user id
     * @param string $userstable    User table name
     * @param string $coursetable   Course table name
     *
     * @return array
     */
    private function get_table_activitiescompleted($userid, $usertable, $coursetable) {
        global $DB;
        $sql = "SELECT u.tempid id, count(cmc.id) completed
                FROM {{$usertable}} u
                JOIN {course_modules_completion} cmc ON u.tempid = cmc.userid
                JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id ";
        if (!is_siteadmin($userid) && has_capability('moodle/site:configview', context_system::instance(), $userid)) {
            $sql .= "JOIN {{$coursetable}} c ON c.tempid = cm.course ";
        }
        $sql .= "WHERE cmc.completionstate <> 0
                 GROUP BY u.tempid";
        return $DB->get_records_sql($sql);
    }

    /**
     * Get visits count on course data for table.
     *
     * @param string $userstable    User table name
     * @param string $coursetable   Course table name
     *
     * @return array
     */
    private function get_table_visitsoncourse($usertable, $coursetable) {
        global $DB;
        $sql = "SELECT u.tempid id, count(al.activity) visits
                  FROM {{$usertable}} u
                  JOIN {edwreports_activity_log} al ON u.tempid = al.userid
                  JOIN {{$coursetable}} c ON c.tempid = al.course
                 GROUP BY u.tempid";
        return $DB->get_records_sql($sql);
    }

    /**
     * Get student engagement table data based on filters
     *
     * @param object $filter Table filters.
     *
     * @return array
     */
    public function get_table_data($filter) {
        global $COURSE;

        $cohort = (int)$filter->cohort;
        $course = (int)$filter->course;
        $search = $filter->search;
        $start = (int)$filter->start;
        $length = (int)$filter->length;
        $secret = optional_param('secret', null, PARAM_TEXT);

        $authentication = new authentication();
        $userid = $authentication->get_user($secret);

        if ($course === 0) {
            $courses = $this->get_courses_of_user($userid);
            unset($courses[$COURSE->id]);
            $courses = array_keys($courses);
        } else {
            $courses = [$course];
        }

        // Temporary course table.
        $coursetable = 'tmp_stengage_courses';
        // Creating temporary table.
        utility::create_temp_table($coursetable, $courses);

        list($users, $count) = $this->get_table_users(
            $cohort,
            $coursetable,
            $search,
            $start,
            $length
        );

        // Temporary user table.
        $usertable = 'tmp_stengage_users';
        // Creating temporary table.
        utility::create_temp_table($usertable, array_keys($users));

        $timespentonlms = $this->get_table_timespentonmls($usertable);
        $timespentoncourse = $this->get_table_timespentoncourse($usertable, $coursetable);
        $assignmentsubmitted = $this->get_table_assignmentsubmitted($userid, $usertable, $coursetable);
        $activitiescompleted = $this->get_table_activitiescompleted($userid, $usertable, $coursetable);
        $visitsoncourse = $this->get_table_visitsoncourse($usertable, $coursetable);

        foreach (array_keys($users) as $key) {
            unset($users[$key]->id);
            $users[$key]->timespentoncourse = isset($timespentoncourse[$key]) ? $timespentoncourse[$key]->timespent : 0;
            $users[$key]->timespentonlms = isset($timespentonlms[$key]) ? $timespentonlms[$key]->timespent : 0;
            $users[$key]->assignmentsubmitted = isset($assignmentsubmitted[$key]) ? $assignmentsubmitted[$key]->submitted : 0;
            $users[$key]->activitiescompleted = isset($activitiescompleted[$key]) ? $activitiescompleted[$key]->completed : 0;
            $users[$key]->visitsoncourse = isset($visitsoncourse[$key]) ? $visitsoncourse[$key]->visits : 0;
        }

        // Droppping course table.
        utility::drop_temp_table($coursetable);

        // Droppping user table.
        utility::drop_temp_table($usertable);

        return [
            "data" => empty($users) ? [] : array_values($users),
            "recordsTotal" => $count,
            "recordsFiltered" => $count
        ];
    }

    /**
     * Get exportable data for report.
     *
     * @param string $filter Filter parameter
     *
     * @return array
     */
    public static function get_exportable_data_report($filter) {
        global $COURSE, $USER;

        $filter = explode('-', $filter);
        $cohortid = (int)$filter[0];
        $course = (int)$filter[1];
        $obj = new self();

        if ($course === 0) {
            $courses = $obj->get_courses_of_user($USER->id);
            unset($courses[$COURSE->id]);
            $courses = array_keys($courses);
        } else {
            $courses = [$course];
        }

        // Temporary course table.
        $coursetable = 'tmp_stengage_courses';
        // Creating temporary table.
        utility::create_temp_table($coursetable, $courses);

        list($users) = $obj->get_table_users(
            $cohortid,
            $coursetable,
            '',
            0,
            0
        );

        // Temporary user table.
        $usertable = 'tmp_stengage_users';
        // Creating temporary table.
        utility::create_temp_table($usertable, array_keys($users));

        $timespentonlms = $obj->get_table_timespentonmls($usertable);
        $timespentoncourse = $obj->get_table_timespentoncourse($usertable, $coursetable);
        $assignmentsubmitted = $obj->get_table_assignmentsubmitted($USER->id, $usertable, $coursetable);
        $activitiescompleted = $obj->get_table_activitiescompleted($USER->id, $usertable, $coursetable);
        $visitsoncourse = $obj->get_table_visitsoncourse($usertable, $coursetable);

        $export = [];
        $export[] = [
            get_string('student', 'core_grades'),
            get_string('timespentonlms', 'local_edwiserreports'),
            get_string('timespentoncourse', 'local_edwiserreports'),
            get_string('assignmentsubmitted', 'local_edwiserreports'),
            get_string('activitiescompleted', 'local_edwiserreports'),
            get_string('visitsoncourse', 'local_edwiserreports')
        ];
        foreach (array_keys($users) as $key) {
            $export[] = [
                $users[$key]->student,
                isset($timespentoncourse[$key]) ? date('H:i:s', mktime(0, 0, $timespentoncourse[$key]->timespent)) : 0,
                isset($timespentonlms[$key]) ? date('H:i:s', mktime(0, 0, $timespentonlms[$key]->timespent)) : 0,
                isset($assignmentsubmitted[$key]) ? $assignmentsubmitted[$key]->submitted : 0,
                isset($activitiescompleted[$key]) ? $activitiescompleted[$key]->completed : 0,
                isset($visitsoncourse[$key]) ? $visitsoncourse[$key]->visits : 0
            ];
        }

        // Droppping course table.
        utility::drop_temp_table($coursetable);

        // Droppping user table.
        utility::drop_temp_table($usertable);

        return $export;
    }

    /**
     * Get exportable graph data.
     *
     * @param string $filter Filter string
     *
     * @return array
     */
    public function get_exportable_data_block($filter) {
        // Exploding filter string to get parameters.
        $filter = explode('-', $filter);

        // Filter object for graph methods.
        $filterobject = new stdClass;

        // Type of graph data we want to export.
        $type = array_pop($filter);

        // Student id.
        $filterobject->student = (int) array_pop($filter);

        // Get course id for timespentoncourse graph.
        if (array_search($type, ['timespentoncourse', 'courseactivitystatus']) !== false) {
            $filterobject->course = (int) array_pop($filter);
        }

        // Time period.
        $filterobject->date = implode('-', $filter);

        // Graph method.
        $method = "get_" . $type . "_graph_data";

        // Fetching graph record.
        $records = $this->$method($filterobject);
        $valuecallback = function($time) {
            return date('H:i:s', mktime(0, 0, $time));
        };

        $labelcallback = function($label) {
            return date('d-m-Y', $label / 1000);
        };
        switch ($type) {
            case 'visitsonlms':
                $export = [[
                    get_string('date'),
                    get_string($type, 'local_edwiserreports')
                ]];
                $recordname = 'visits';
                $valuecallback = null;
                break;
            case 'timespentonlms':
                $export = [[
                    get_string('date'),
                    get_string($type, 'local_edwiserreports')
                ]];
                $recordname = 'timespent';
                break;
            case 'timespentoncourse':
                if ($filterobject->course === 0) {
                    $export = [[
                        get_string('course'),
                        get_string($type, 'local_edwiserreports')
                    ]];
                    $labelcallback = null;
                } else {
                    $export = [[
                        get_string('date'),
                        get_string($type, 'local_edwiserreports')
                    ]];
                }
                $recordname = 'timespent';
                break;
            case 'courseactivitystatus':
                $export = [[
                    get_string('date'),
                    get_string($type . '-submissions', 'local_edwiserreports'),
                    get_string($type . '-completions', 'local_edwiserreports')
                ]];
                $recordname = ['submissions', 'completions'];
                $valuecallback = null;
                break;
        }
        if (is_array($recordname)) {
            $datacallback = function(&$row, $recordnames, $key, $records, $valuecallback) {
                foreach ($recordnames as $recordname) {
                    $value = isset($records[$recordname][$key]) ? $records[$recordname][$key] : 0;
                    $row[] = $valuecallback == null ? $value : $valuecallback($value);
                }
            };
        } else {
            $datacallback = function(&$row, $recordname, $key, $records, $valuecallback) {
                $value = isset($records[$recordname][$key]) ? $records[$recordname][$key] : 0;
                $row[] = $valuecallback == null ? $value : $valuecallback($value);
            };
        }
        foreach ($records['labels'] as $key => $label) {
            $row = [$labelcallback == null ? $label : $labelcallback($label)];
            $datacallback($row, $recordname, $key, $records, $valuecallback);
            $export[] = $row;
        }
        return $export;
    }
}
