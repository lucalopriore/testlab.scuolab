<?php


/**
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */


function getExperiencesJsons()
{

    global $CFG;

    $files = scandir($CFG->dirroot . "/mod/scuolab/Build");
    $jsons = array_filter($files, function ($element) {
        $ext = pathinfo($element, PATHINFO_EXTENSION);
        return $ext == "json";
    });
    $jsons = array_values($jsons);
    return $jsons;
}

function getExperiencesObjects()
{
    $jsonNames = getExperiencesJsons();

    $objects = array_map(function ($jsonName) {
        global $CFG;
        $basePath = $CFG->dirroot . "/mod/scuolab/Build/";
        $pathName =  $basePath . $jsonName;
        $content = file_get_contents($pathName);
        $obj = json_decode($content);
        $obj->name = jsonToExperienceName($jsonName);
        $obj->jsonName = pathinfo($jsonName, PATHINFO_FILENAME);
        return $obj;
    }, $jsonNames);

    usort($objects, function ($a, $b) {
        if ($a->productName == $b->productName) {
            return $a->name > $b->name;
        } else {
            return $a->productName > $b->productName;
        }
    });


    return $objects;
}

function jsonToExperienceName($json)
{
    return  mb_convert_case(pathinfo($json, PATHINFO_FILENAME), MB_CASE_TITLE);
}

function selectObjectName($object)
{
    return "{$object->productName} - {$object->name}";
}

function getExperiencesNames()
{
    $objects = getExperiencesObjects();
    $names = array_map("selectObjectName", $objects);
    return $names;
}

function scuolab_add_instance($experience)
{
    global $CFG, $DB;

    $index = (int) $experience->experience;
    $objects = getExperiencesObjects();
    $obj = $objects[$index];
    $experience->name = $obj->name;
    $experience->json = $obj->jsonName;

    $cmid = $experience->coursemodule;
    $id = $DB->insert_record('scuolab', $experience);
    // Update course module record - from now on this instance properly exists and all function may be used.

    $DB->set_field('course_modules', 'instance', $id, array('id' => $cmid));

    return $id;
}


function scuolab_update_instance($experience)
{
    global $DB;
    $cmid = $experience->coursemodule;
    $id = $DB->get_field('course_modules', 'instance', array('id' => $cmid));
    $experience->id = $id;

    $index = (int) $experience->experience;
    $objects = getExperiencesObjects();
    $obj = $objects[$index];
    $experience->name = $obj->name;
    $experience->json = $obj->jsonName;

    $DB->update_record('scuolab', $experience);

    return true;
}

function mod_scuolab_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $sendfileoptions)
{
    $fs = get_file_storage();
    $filename = array_pop($args);
    $itemid = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = $fs->get_file($context->id, 'mod_scuolab', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        send_file_not_found();
    }
    if ($file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, null, 0, false, $sendfileoptions);
}

// function scuolab_extend_navigation(navigation_node $navref, $course, $module, $cm)
// {
//     //$PAGE->navigation->add("Exports", new moodle_url('/'));
//     $link = new moodle_url('view.php');
//     $node = $navref->add("Exports", $link, navigation_node::TYPE_SETTING);
//     $node->mainnavonly = false;
// }


function scuolab_delete_instance($id)
{
    global $CFG, $DB;

    if (!$experience = $DB->get_record('scuolab', array('id' => $id))) {
        return false;
    }


    $result = true;

    // We must delete the module record after we delete the grade item.
    if (!$DB->delete_records('scorm', array('id' => $experience->id))) {
        $result = false;
    }

    return $result;
}

function mod_scuolab_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return null;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main page display
 */
function scuolab_get_coursemodule_info($coursemodule)
{
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$page = $DB->get_record('scuolab', array('id' => $coursemodule->instance))) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $page->name;

    //Code to open experience in a popup
    // if ($page->display != RESOURCELIB_DISPLAY_POPUP) {
    //     return $info;
    // }

    // $fullurl = "$CFG->wwwroot/mod/scuolab/view.php?id=$coursemodule->id&amp;inpopup=1";
    // $options = empty($page->displayoptions) ? array() : unserialize($page->displayoptions);
    // $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
    // $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
    // $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
    // $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    return $info;
}
