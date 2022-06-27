<?php

require('../../config.php');

require_once('lib.php');


$courseId = optional_param('courseId', NULL, PARAM_INT);
$bbbId = optional_param('bbbId', NULL, PARAM_INT);
$userId = optional_param('userId', NULL, PARAM_INT);
$meetId = optional_param('meetId', NULL, PARAM_TEXT);
$log = optional_param('log', NULL, PARAM_TEXT);
$meta = optional_param('meta', NULL, PARAM_TEXT);

if ($courseId === NULL || $courseId<0) exit;
if ($bbbId === NULL || $bbbId<0) exit;
if ($userId === NULL || $bbbId<0) exit;
if ($meetId === NULL) exit;
if ($log === NULL) exit;
if ($meta === NULL) exit;

$updateTime = time();

$newId = $DB->insert_record(
    'bigbluebuttonbn_logs',
    array(
        'courseid' => $courseId,
        'bigbluebuttonbnid' => $bbbId, 
        'userid' => $userId, 
        'timecreated' => $updateTime, 
        'meetingid' => $meetId,
        'log' => $log,
        'meta' => $meta
    )
);

echo($newId);
?>