<?php

require('../../config.php');

require_once('lib.php');


$classId = optional_param('classId', NULL, PARAM_INT);
$groupId = optional_param('groupId', NULL, PARAM_INT);
$avatarId = optional_param('avatarId', NULL, PARAM_INT);
$agentName = optional_param('agentName', NULL, PARAM_TEXT);
$projectId = optional_param('projectId', NULL, PARAM_TEXT);
$credentialsName = optional_param('credentialsName', NULL, PARAM_TEXT);
$serviceAccount = optional_param('serviceAccount', NULL, PARAM_TEXT);
$originalKey = optional_param('originalKey', NULL, PARAM_TEXT);
$credentialsKey = optional_param('credentialsKey', NULL, PARAM_TEXT);

if ($classId === NULL || $classId<0) exit;
if ($avatarId === NULL || $avatarId<0) exit;
if ($agentName === NULL) exit;
if ($projectId === NULL) exit;
if ($credentialsName === NULL) exit;
if ($serviceAccount === NULL) exit;
if ($originalKey === NULL) exit;
if ($credentialsKey === NULL) exit;

$favorite = 1;
$isgroupassigned = 0;

if ($groupId !== NULL) {
    if ($groupId<0) exit;
    else $isgroupassigned = 1;
} else
    $groupId = 0;

$newId = $DB->insert_record(
    'agent_settings',
    array(
        'avatarid' => $avatarId,
        'agentname' => $agentName, 
        'projectid' => $projectId, 
        'credentialsname' => $credentialsName, 
        'serviceaccount' => $serviceAccount,
        'originalkey' => $originalKey,
        'credentialskey' => $credentialsKey,
        'isfavorite' => $favorite
    )
);

$DB->insert_record(
    'cohort_agent',
    array(
        'agentid' => $newId,
        'cohortid' => $classId,
        'groupid' => $groupId,
        'isgroupassigned' => $isgroupassigned
    )
);

echo($newId);
?>