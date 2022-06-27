<?php

/**
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */

require('../../config.php');
require_once('lib.php');
require(__DIR__.'../../../local/scuolib/lang/parsing_language_text.php');

global $DB;

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'scuolab');
$experience = $DB->get_record('scuolab', array('id' => $cm->instance), '*', MUST_EXIST);
$coursecat = \core_course_category::get($course->category);
$frameLicense = optional_param('iframe', NULL, PARAM_ALPHANUM);


$url = new moodle_url('/mod/scuolab/view.php', array('id' => $cm->id));
$PAGE->set_url($url);

$sql = "SELECT * 
        FROM {scuolab_license} l 
        JOIN {scuolab_license_experience} e ON l.id = e.license_id 
        JOIN {scuolab_license_code} c ON l.id = c.license_id 
        WHERE experience_id={$id} 
        AND code = '$frameLicense'";

$isValidIFrameLicense = $frameLicense != NULL && $DB->get_records_sql($sql, array()) != NULL;

if ($frameLicense == NULL) {
  require_login($course, false, $cm);
} else if ($isValidIFrameLicense == false) {
  die("Invalid License");
}

$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);

$PAGE->requires->js(new moodle_url('TemplateData/detectWebGL.js'), true);
$PAGE->requires->js(new moodle_url('TemplateData/UnityProgress.js'), true);
$PAGE->requires->js(new moodle_url('Build/UnityLoader.js'), true);
$PAGE->requires->css(new moodle_url('TemplateData/style.css'));



if ($isValidIFrameLicense) {
  $PAGE->set_pagelayout('popup');
}

$PAGE->set_title("$course->fullname");
$PAGE->set_heading($course->fullname);

$header = $OUTPUT->header();
echo $header;
$heading = $OUTPUT->heading(format_string($course->fullname));
echo $heading;

if ($experience->showdescription == -1 && $isValidIFrameLicense == false) {
  showDescription($OUTPUT, $experience, $cm);
}

$moodleLanguageCode = current_language();
switch ($moodleLanguageCode) {
  case 'it':
    $scuolabLanguageCode = "IT";
    break;
  case 'en':
    $scuolabLanguageCode = "EN";
    break;
  case 'es':
    $scuolabLanguageCode = "ES";
    break;
  case 'pt':
    $scuolabLanguageCode = "PT";
    break;
  case 'de':
    $scuolabLanguageCode = "DE";
    break;
  case 'fr':
    $scuolabLanguageCode = "FR";
    break;
  default:
    $scuolabLanguageCode = "IT";
    break;
}

$scuolabCanvas = "";
$scuolabCanvas .= html_writer::start_tag("canvas", array('id' => 'webgl-test'));
$scuolabCanvas .= html_writer::end_tag("canvas");
$scuolabCanvas .= html_writer::start_div("webgl-content", array("id" => "webgl-content"));
$scuolabCanvas .= html_writer::start_div("footer");
$scuolabCanvas .= html_writer::div("", "webgl-logo");
$scuolabCanvas .= html_writer::div("", "fullscreen", array("onclick" => "unityInstance.SetFullscreen(1)"));
$scuolabCanvas .= html_writer::div(parsing_language($coursecat->name,$moodleLanguageCode), 'title');
$scuolabCanvas .= html_writer::end_div();
$scuolabCanvas .= html_writer::div('', '', array('id' => 'unityContainer'));
$scuolabCanvas .= html_writer::end_div();
$scuolabCanvas .= html_writer::start_div("no-webgl", array("id" => "no-webgl"));
$scuolabCanvas .= html_writer::span("Le esperienze sono compatibili solo su Desktop e solo con le ultime versioni dei seguenti browser:<br />");
$scuolabCanvas .= html_writer::span("- Edge: ");
$scuolabCanvas .= html_writer::link('https://www.microsoft.com/en-us/edge', '(https://www.microsoft.com/en-us/edge)<br />');
$scuolabCanvas .= html_writer::span("- Chrome: ");
$scuolabCanvas .= html_writer::link('https://www.google.com/chrome/', '(https://www.google.com/chrome/)<br />');
$scuolabCanvas .= html_writer::span("- Firefox: ");
$scuolabCanvas .= html_writer::link('https://www.mozilla.org/en-US/firefox/new/', '(https://www.mozilla.org/en-US/firefox/new/)<br />');
$scuolabCanvas .= html_writer::span("- Opera: ");
$scuolabCanvas .= html_writer::link('https://www.opera.com/it', '(https://www.opera.com/it)<br />');
$scuolabCanvas .= html_writer::end_div();

$scuolabCanvas .= html_writer::script("
      var MoodleData = {
        language: \"{$scuolabLanguageCode}\",
        userId: \"{$USER->id}\"
      }
      var isSupported = detectWebGL(\"webgl-test\");
      var element = document.getElementById(\"webgl-test\");
      element.parentNode.removeChild(element);      
      if (isSupported){
         var element = document.getElementById(\"no-webgl\");
         element.parentNode.removeChild(element);

         unityInstance = UnityLoader.instantiate(\"unityContainer\", \"Build/{$experience->json}.json\", { onProgress: UnityProgress });

         window.onbeforeunload = function(e) {
          unityInstance.SendMessage(\"EventLogger\", \"OnWebClose\");
         };

      }else{
         var element = document.getElementById(\"webgl-content\");
         element.parentNode.removeChild(element);
      }
");

echo $scuolabCanvas;

if ($experience->showdescription == 1 && $isValidIFrameLicense == false) {
  showDescription($OUTPUT, $experience, $cm);
}

if ($isValidIFrameLicense == false) {

  $sql  = "SELECT * FROM {files} JOIN {user} ON {user}.`id` = {files}.`itemid` WHERE `contextid` = $contextmodule->id AND `component` = 'mod_scuolab' AND `filearea` = 'export' AND `filesize` > 0";
  if (!has_capability('mod/scuolab:viewallexports', $context, $USER->id, false)) {
    $sql .= " AND `userid` = $USER->id";
  }

  $submissions = $DB->get_records_sql($sql);

  if (count($submissions) > 0) {

    $table = new html_table();
    $table->head = array("Studente", "Download");
    $table->data = array();

    foreach ($submissions as $submission) {
      $row = new html_table_row();
      if ($submission->filename == ".") {
        continue;
      }
      $name =  new html_table_cell("{$submission->firstname} {$submission->lastname}");
      $filename = $submission->filename;
      $url = moodle_url::make_pluginfile_url(
        $submission->contextid,
        $submission->component,
        $submission->filearea,
        $submission->itemid,
        $submission->filepath,
        $submission->filename,
        false
      );
      $link = new html_table_cell(html_writer::link($url, "{$filename}"));
      $row->cells = array($name, $link);
      array_push($table->data, $row);
    }
  }

  if (isset($table)) {
    echo html_writer::table($table);
  }
}

echo $OUTPUT->footer();


function showDescription($OUTPUT, $experience, $cm) {
  if ($experience->intro) {
    echo $OUTPUT->box(format_module_intro('scuolab', $experience, $cm->id), 'generalbox', 'intro');
  }
}