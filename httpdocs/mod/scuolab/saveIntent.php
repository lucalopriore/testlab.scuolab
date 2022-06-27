<?php

require('../../config.php');
require_once('lib.php');

$intentId = optional_param('intentId', NULL, PARAM_TEXT);
$userId = optional_param('userId', NULL, PARAM_INT);
$classId = optional_param('classId', NULL, PARAM_INT);
$agentId = optional_param('agentId', NULL, PARAM_INT);

if ($intentId === NULL) exit;
if ($userId === NULL || $userId<0) exit;
if ($classId === NULL || $classId<0) exit;
if ($agentId === NULL || $agentId<0) exit;

$updateTime = time();

$newId = $DB->insert_record(
    'intent',
    array(
        'intentid' => $intentId, 
        'userid' => $userId, 
        'cohortid' => $classId, 
        'agentid' => $agentId,
        'updatetime' => $updateTime
    )
);

echo($newId);
?>