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
use context_system;
use moodle_url;

require_once($CFG->dirroot . '/local/edwiserreports/classes/block_base.php');

/**
 * Active users block.
 */
class learnerblock extends block_base {

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
     * Dates main array.
     *
     * @var array
     */
    public $dates = [];

    /**
     * Get user using secret key or global $USER
     *
     * @return int
     */
    private function get_user() {
        global $USER;
        $secret = optional_param('secret', null, PARAM_TEXT);
        if ($secret !== null) {
            $authentication = new \local_edwiserreports\controller\authentication();
            $userid = $authentication->get_user($secret);
        } else {
            $userid = $USER->id;
        }
        return $userid;
    }

    /**
     * Preapre layout for each block
     * @return object Layout
     */
    public function get_layout() {
        global $CFG, $USER;

        // Layout related data.
        $this->layout->id = 'learnerblock';
        $this->layout->name = get_string('learnerheader', 'local_edwiserreports');
        $this->layout->info = get_string('learnerblockhelp', 'local_edwiserreports');
        $this->layout->morelink = new moodle_url($CFG->wwwroot . "/local/edwiserreports/learner.php");
        $filters = $this->get_learner_filter();
        $graphs = [
            'courseprogress',
            'timespentonlms'
        ];

        $own = !isset($USER->editing) || $USER->editing == false ? '(' . get_string('own', 'local_edwiserreports') . ')' : '';
        $this->block->graphs = [];
        foreach ($graphs as $graph) {
            $this->block->graphs[] = [
                'id' => 'learnerblock',
                'graph' => $graph,
                'header' => get_string($graph, 'local_edwiserreports') . $own,
                'filters' => $filters[$graph],
                'morelink' => new moodle_url($CFG->wwwroot . "/local/edwiserreports/learner.php"),
                'cohortid' => 0,
                'region' => 'block',
                'sesskey' => sesskey(),
                'editing' => isset($USER->editing) ? $USER->editing : 0
            ];
        }

        // Add block view in layout.
        $this->layout->blockview = $this->render_block('learnerblock', $this->block);

        // Set block edit capabilities.
        $this->set_block_edit_capabilities($this->layout->id);

        // Return blocks layout.
        return $this->layout;
    }

    /**
     * Prepare active users block filters
     * @return array filters array
     */
    public function get_learner_filter() {
        global $OUTPUT, $USER, $COURSE, $USER, $DB;

        if (is_siteadmin() || has_capability('moodle/site:configview', context_system::instance())) {
            $courses = get_courses();
        } else {
            $courses = enrol_get_users_courses($USER->id);
        }
        unset($courses[$COURSE->id]);

        // Temporary course table.
        $coursetable = 'tmp_learner_courses';
        // Creating temporary table.
        utility::create_temp_table($coursetable, array_keys($courses));
        $sql = "SELECT c.id
                  FROM {{$coursetable}} ct
                  JOIN {course} c ON ct.tempid = c.id
                 WHERE c.enablecompletion <> 0";
        $records = $DB->get_records_sql($sql);

        // Droppping course table.
        utility::drop_temp_table($coursetable);
        $filtercourses = [
            0 => [
                'id' => 0,
                'fullname' => get_string('fulllistofcourses')
            ]
        ];

        if (!empty($records)) {
            foreach ($records as $record) {
                $filtercourses[] = [
                    'id' => $record->id,
                    'fullname' => $courses[$record->id]->fullname
                ];
            }
        }

        $sql = 'SELECT id, firstname, lastname
                  FROM {user}
                 WHERE confirmed = 1
              ORDER BY firstname asc';
        $recordset = $DB->get_recordset_sql($sql);
        $users = [[
            'id' => 0,
            'name' => get_string('allusers', 'search')
        ]];
        foreach ($recordset as $user) {
            $users[] = [
                'id' => $user->id,
                'name' => $user->firstname . ' ' . $user->lastname
            ];
        }
        return [
            'courseprogress' => $OUTPUT->render_from_template('local_edwiserreports/learnerblockfilters', [
                'filter-id' => 'courseprogress',
                'showcourses' => true,
                'courses' => $filtercourses,
                'showdate' => false
            ]),
            'timespentonlms' => $OUTPUT->render_from_template('local_edwiserreports/learnerblockfilters', [
                'filter-id' => 'timespentonlms',
                'showcourses' => false,
                'showdate' => true
            ])
        ];
    }

    /**
     * Generate labels and dates array for graph
     *
     * @param string $timeperiod Filter time period Weekly/Monthly/Yearly or custom dates.
     */
    private function generate_date_labels($timeperiod) {
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
                if (count($dates) != 2) {
                    $this->singleday = true;
                    $dates = [$timeperiod, $timeperiod];
                }
                $startdate = strtotime($dates[0]." 00:00:00");
                $enddate = strtotime($dates[1]." 23:59:59");
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
     * Get data for courseprogress graph.
     *
     * @param object $filter Filters
     *
     * @return array
     */
    public function get_courseprogress_graph_data($filter) {
        global $DB, $COURSE;
        $course = $filter->course;
        $userid = $this->get_user();
        $labels = [];
        $progress = [];
        if ($course === 0) { // Course is selected in dropdown.
            if (is_siteadmin($userid) || has_capability('moodle/site:configview', context_system::instance(), $userid)) {
                $courses = get_courses();
            } else {
                $courses = enrol_get_users_courses($userid);
            }
            unset($courses[$COURSE->id]);

            // Temporary course table.
            $coursetable = 'tmp_learner_courses';
            // Creating temporary table.
            utility::create_temp_table($coursetable, array_keys($courses));

            $sql = "SELECT c.id
                  FROM {{$coursetable}} ct
                  JOIN {course} c ON ct.tempid = c.id
                 WHERE c.enablecompletion <> 0";
            $filteredcourses = $DB->get_records_sql($sql);

            $sql = "SELECT cp.courseid id, cp.progress
                      FROM {{$coursetable}} ct
                      JOIN {edwreports_course_progress} cp ON ct.tempid = cp.courseid
                      JOIN {course} c ON cp.courseid = c.id
                     WHERE cp.userid = :userid
                       AND c.enablecompletion <> 0";
            $params = ['userid' => $userid];
            $records = $DB->get_records_sql($sql, $params);
            // Droppping course table.
            utility::drop_temp_table($coursetable);
            $hasdata = false;
            if (!empty($records)) {
                foreach ($filteredcourses as $record) {
                    $labels[] = $courses[$record->id]->fullname;
                    $prog = isset($records[$record->id]) ? (int)$records[$record->id]->progress : 0;
                    if ($prog > 0) {
                        $hasdata = true;
                    }
                    $progress[] = $prog;
                }
                if (!$hasdata) {
                    $progress = [];
                }
            }
        } else {
            $sql = "SELECT cp.courseid, cp.totalmodules total, count(cm.id) modules
                      FROM {course_modules} cm
                      JOIN {edwreports_course_progress} cp ON cm.course = cp.courseid
                     WHERE cp.userid = :userid
                       AND cm.course = :courseid
                       AND cm.completion <> 0
                     GROUP BY cp.courseid, cp.totalmodules";
            $params = ['userid' => $userid, 'courseid' => $course];
            $record = $DB->get_record_sql($sql, $params);
            if (!empty($record)) {
                // Completed.
                $labels[] = get_string('completion-y', 'core_completion');
                $progress[] = (int)$record->total;

                // Incomplete.
                $labels[] = get_string('completion-n', 'core_completion');
                $progress[] = (int)$record->modules - (int)$record->total;
            }
        }

        return [
            'labels' => $labels,
            'progress' => $progress
        ];
    }

    /**
     * Get data for timespent on lms graph.
     *
     * @param object $filter Filters
     *
     * @return array
     */
    public function get_timespentonlms_graph_data($filter) {
        global $DB;
        $date = $filter->date;
        $userid = $this->get_user();
        $this->generate_date_labels($date);
        $params = [
            'startdate' => floor($this->startdate / 86400),
            'enddate' => floor($this->enddate / 86400),
            'userid' => $userid
        ];
        $sql = "";
        if (isset($this->singleday)) {
            $sql = "SELECT al.course, c.fullname, sum(" . $DB->sql_cast_char2int("al.timespent") . ") timespent
                      FROM {edwreports_activity_log} al
                      LEFT JOIN {course} c ON al.course = c.id
                     WHERE al.datecreated > :startdate
                       AND al.datecreated <= :enddate
                       AND al.userid = :userid
                       AND al.timespent > 0
                       GROUP BY al.course, c.fullname";
            $logs = $DB->get_records_sql($sql, $params);
            $this->labels = $this->timespent = [];
            foreach ($logs as $log) {
                if ($log->course == 0 || $log->course == 1) {
                    $label = get_string('site');
                } else {
                    $label = get_string('course') . ' - ' . $log->fullname;
                }
                $this->timespent[] = (int)$log->timespent;
                $this->labels[] = $label;
            }

            $response = [
                'timespent' => $this->timespent,
                'labels' => $this->labels
            ];
        } else {
            $sql = "SELECT datecreated, sum(" . $DB->sql_cast_char2int("timespent") . ") timespent
                      FROM {edwreports_activity_log}
                     WHERE datecreated >= :startdate
                       AND datecreated <= :enddate
                       AND userid = :userid
                     GROUP BY datecreated";
            $logs = $DB->get_records_sql($sql, $params);

            foreach ($logs as $log) {
                if (!isset($this->dates[$log->datecreated])) {
                    continue;
                }
                $this->dates[$log->datecreated] = (int)$log->timespent;
            }

            $response = [
                'timespent' => array_values($this->dates),
                'labels' => $this->labels
            ];
        }
        return $response;
    }

    /**
     * Get total timespent on course data for table.
     *
     * @param string $userid        User id
     * @param string $coursetable   Course table name
     *
     * @return array
     */
    private function get_table_timespentoncourse($userid, $coursetable) {
        global $DB;
        $sql = "SELECT al.course id, SUM(al.timespent) timespent
                  FROM {edwreports_activity_log} al
                  JOIN {{$coursetable}} c ON c.tempid = al.course
                  WHERE al.userid = :userid
                 GROUP BY al.course";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Get activities completion count and course progress for courses.
     *
     * @param int       $userid         User id
     * @param string    $coursetable    Course table
     *
     * @return array
     */
    private function get_table_activitiescompleted($userid, $coursetable) {
        global $DB;
        $sql = "SELECT c.tempid, cp.progress, cp.totalmodules activitiescompleted
                  FROM {{$coursetable}} c
                  JOIN {edwreports_course_progress} cp ON c.tempid = cp.courseid
                 WHERE cp.userid = :userid";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Get grade of courses.
     *
     * @param int       $userid         User id
     * @param string    $coursetable    Course table
     *
     * @return array
     */
    private function get_table_grade($userid, $coursetable) {
        global $DB;
        $sql = "SELECT c.tempid, gg.finalgrade grades
                  FROM {{$coursetable}} c
                  JOIN {grade_items} gi ON c.tempid = gi.iteminstance
                  JOIN {grade_grades} gg ON gi.id = gg.itemid
                 WHERE " . $DB->sql_compare_text('gi.itemtype') . " = " . $DB->sql_compare_text(':itemtype') .
                  "AND  gg.userid = :userid";
        $params = [
            'itemtype' => 'course',
            'userid' => $userid
        ];
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get student engagement table data based on filters
     *
     * @param object $filter Table filters.
     *
     * @return array
     */
    public function get_table_data($filter) {
        global $COURSE, $DB;

        $search = $filter->search;
        $start = (int)$filter->start;
        $length = (int)$filter->length;

        $secret = optional_param('secret', null, PARAM_TEXT);

        $authentication = new authentication();
        $userid = $authentication->get_user($secret);

        if (is_siteadmin($userid) || has_capability('moodle/site:configview', context_system::instance(), $userid)) {
            $usercourses = get_courses();
        } else {
            $usercourses = enrol_get_users_courses($userid);
        }
        unset($usercourses[$COURSE->id]);
        $usercourses = array_keys($usercourses);
        $count = count($usercourses);
        // Temporary course table.
        $coursetable = 'tmp_sql_courses';
        // Creating temporary table.
        utility::create_temp_table($coursetable, $usercourses);

        $sql = "SELECT c.id, c.fullname
                  FROM {{$coursetable}} uc
                  JOIN {course} c ON uc.tempid = c.id
                 WHERE " . $DB->sql_like('c.fullname', ':fullname');
        $params = [
            'userid' => $userid,
            'fullname' => "%$search%"
        ];
        $courses = $DB->get_records_sql($sql, $params, $start, $length);
        // Droppping course table.
        utility::drop_temp_table($coursetable);

        // Temporary course table.
        $coursetable = 'tmp_learner_courses';
        // Creating temporary table.
        utility::create_temp_table($coursetable, array_keys($courses));

        $activitiescompleted = $this->get_table_activitiescompleted($userid, $coursetable);
        $timespentoncourse = $this->get_table_timespentoncourse($userid, $coursetable);
        $grades = $this->get_table_grade($userid, $coursetable);

        foreach (array_keys($courses) as $key) {
            unset($courses[$key]->id);
            $courses[$key]->progress = isset($activitiescompleted[$key]) ? $activitiescompleted[$key]->progress . '%' : 0;
            $courses[$key]->activitiescompleted = isset($activitiescompleted[$key]) ?
                                                $activitiescompleted[$key]->activitiescompleted : 0;
            $courses[$key]->timespentoncourse = isset($timespentoncourse[$key]) ? $timespentoncourse[$key]->timespent : 0;
            $courses[$key]->grades = isset($grades[$key]) ? round($grades[$key]->grades, 2) . '%' : '-';
        }

        // Droppping course table.
        utility::drop_temp_table($coursetable);

        return [
            "data" => empty($courses) ? [] : array_values($courses),
            "recordsTotal" => $count,
            "recordsFiltered" => $count
        ];
    }
}
