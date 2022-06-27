<?php

require('../../config.php');

$unityMessage = local_scuolib_tokenGenerator::getMessage();

$id = $unityMessage->activityId;
list($course, $cm) = get_course_and_cm_from_cmid($id, 'scuolab');
$experience = $DB->get_record('scuolab', array('id' => $cm->instance), '*', MUST_EXIST);

$sql = "SELECT * 
        FROM {scuolab_license} l 
        JOIN {scuolab_license_experience} e ON l.id = e.license_id 
        JOIN {scuolab_license_code} c ON l.id = c.license_id 
        WHERE experience_id={$id} 
        AND code = '$unityMessage->iframe'";

$isValidIFrameLicense = isset($unityMessage->iframe) && $DB->get_records_sql($sql, array()) != NULL;

if ($isValidIFrameLicense == false) {
    require_login($course, false, $cm, true, true);
}

echo local_scuolib_tokenGenerator::generateToken($USER);
