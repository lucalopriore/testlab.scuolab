<?php

require('../../config.php');
require_once('lib.php');

$userId = optional_param('userId', NULL, PARAM_INT);
$groupId = optional_param('groupId', NULL, PARAM_INT);

if ($userId === NULL || $userId<0) exit;
if ($groupId === NULL || $groupId<0) exit;

$DB->delete_records(
    'classgroup_user',
    array(
        'classgroupid' => $groupId, 
        'userid' => $userId
    )
);
?>