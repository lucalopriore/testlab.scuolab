<?php

/**
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */


require_once('../../config.php');

print_error("Page not found");


//List of exepreriences for a certain course
/*

$id = required_param('id', PARAM_INT);           // Course ID

// Ensure that the course specified is valid
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('Course ID is incorrect');
}


$PAGE->set_url('/mod/scuolab/index.php', array('id' => $id));

if (!empty($id)) {
    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('invalidcourseid');
    }
} else {
    print_error('missingparameter');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_title("Titolo");
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add("Navbar");
echo $OUTPUT->header();
echo $OUTPUT->heading("Heading");

$usesections = course_format_uses_sections($course->format);

if ($usesections) {
    $sortorder = "cw.section ASC";
} else {
    $sortorder = "m.timemodified DESC";
}


if (!$experiences = get_all_instances_in_course("scuolab", $course)) {
    notice("No scuolab", "../../course/view.php?id=$course->id");
    exit;
}

$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head  = array($strsectionname, $strname, $strsummary, $strreport);
    $table->align = array("center", "left", "left", "left");
} else {
    $table->head  = array($strlastmodified, $strname, $strsummary, $strreport);
    $table->align = array("left", "left", "left", "left");
}

foreach ($experiences as $experience) {
    $context = context_module::instance($experience->coursemodule);
    $tt = "";
    if ($usesections) {
        if ($experience->section) {
            $tt = get_section_name($course, $experience->section);
        }
    } else {
        $tt = userdate($experience->timemodified);
    }
    $report = '&nbsp;';
    $reportshow = '&nbsp;';
    $options = (object) array('noclean' => true);
    if (!$experience->visible) {
        // Show dimmed if the mod is hidden.
        $table->data[] = array(
            $tt, html_writer::link(
                'view.php?id=' . $experience->coursemodule,
                format_string($experience->name),
                array('class' => 'dimmed')
            ),
            format_module_intro('scuolab', $experience, $experience->coursemodule), $reportshow
        );
    } else {
        // Show normal if the mod is visible.
        $table->data[] = array(
            $tt, html_writer::link('view.php?id=' . $experience->coursemodule, format_string($experience->name)),
            format_module_intro('scuolab', $experience, $experience->coursemodule), $reportshow
        );
    }
}

echo html_writer::empty_tag('br');

echo html_writer::table($table);

echo $OUTPUT->footer();
*/