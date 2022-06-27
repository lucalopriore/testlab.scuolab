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


$id = required_param("id", PARAM_INT);
$dataformat = optional_param("format", "csv", PARAM_ALPHA);

$licenseRecord  = $DB->get_record('scuolab_license', array('id' => $id));
$codeRecordList  = $DB->get_records('scuolab_license_code', array('license_id' => $id));

$sql = "SELECT le.experience_id, s.name
        FROM {scuolab_license_experience} le
        JOIN {course_modules} m ON m.id=le.experience_id 
        JOIN {scuolab} s ON s.id = m.instance
        WHERE le.license_id = {$id}
        ORDER BY le.experience_id;";

$experienceList = $DB->get_records_sql($sql, array());

$recordData = new ArrayObject();

foreach ($experienceList as $experience) {
    foreach ($codeRecordList as $codeRecord) {
        $recordData->append(array(
            'experience name' => $experience->name,
            'link' => "{$CFG->wwwroot}/mod/scuolab/view.php?id={$experience->experience_id}&iframe={$codeRecord->code}"
        ));
    }
}

$filename = clean_filename($licenseRecord->name);
$fields = ['Experience name', 'Link'];
$iterator = $recordData->getIterator();

download_as_dataformat($filename, $dataformat, $fields, $iterator, NULL);
