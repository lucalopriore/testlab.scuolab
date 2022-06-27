<?php

require('../../config.php');


$sessionId = optional_param('sessionId', NULL, PARAM_INT);
$activityId = optional_param('activityId', NULL, PARAM_INT);
$eventId = required_param('eventId', PARAM_INT);
$relativeTime = optional_param('relativeTime', NULL, PARAM_FLOAT);
$data = optional_param('data', NULL, PARAM_TEXT);
$frameLicense = optional_param('iframe', NULL, PARAM_ALPHANUM);

if ($sessionId === NULL && $activityId === NULL) exit;
if ($sessionId === NULL && $eventId != 0) exit;
if ($sessionId != NULL && $eventId == 0) exit;
if ($sessionId != NULL && $relativeTime  === NULL) exit;


//Who am I?
$userId = getUserOrLicenseId($frameLicense, $activityId);
//If noone, exit
if ($userId == NULL) exit;

$absoluteTime = time();

if ($sessionId == NULL) {

    $permissionOk = checkActivityPermission($userId, $activityId, $frameLicense != NULL);
    if ($permissionOk == false) exit;

    //If starting a new session, create it as me as its owner
    $sessionId = $DB->insert_record(
        'scuolab_activity_session',
        array('user_id' => $userId, 'activity_id' => $activityId, 'frame_license' => $frameLicense != NULL)
    );
    $DB->insert_record(
        'scuolab_activity_event',
        array(
            'session_id' => $sessionId, 'event_id' => 0, 'absolute_time' => $absoluteTime,
            'relative_time' => 0, 'data' => $data
        )
    );
    echo ($activityId);
} else {

    //If adding a new event, verify this session is mine
    $permissionOk = $DB->get_record(
        'scuolab_activity_session',
        array('id' => $sessionId, 'user_id' => $userId),
        'id'
    ) != NULL;

    if ($permissionOk == false) exit;

    $DB->insert_record(
        'scuolab_activity_event',
        array(
            'session_id' => $sessionId, 'event_id' => $eventId, 'absolute_time' => $absoluteTime,
            'relative_time' => $relativeTime, 'data' => $data
        )
    );
}


function getUserOrLicenseId($frameLicense)
{
    try {
        if ($frameLicense != NULL) {
            global $DB;
            $licenseRecord = $DB->get_record('scuolab_license_code', array('code' => $frameLicense), 'license_id');
            return $licenseRecord->license_id;
        } else {
            global $USER;
            return $USER->id;
        }
    } catch (Exception $e) {
        return NULL;
    }
}

function checkActivityPermission($userId, $activityId, $isFrameLicense)
{
    try {
        if ($isFrameLicense) {
            global $DB;
            $DB->get_record('scuolab_license_experience', array('license_id' => $userId, 'experience_id' => $activityId), MUST_EXIST);
            return true;
        } else {
            list($course, $cm) = get_course_and_cm_from_cmid($activityId, 'scuolab');
            require_login($course, false, $cm);
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
}
