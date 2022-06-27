<?php

require('../../config.php');
require_once('lib.php');

$userId = optional_param('userId', NULL, PARAM_INT);
$groupId = optional_param('groupId', NULL, PARAM_INT);

if ($userId === NULL || $userId<0) exit;
if ($groupId === NULL || $groupId<0) exit;

$sql  = "SELECT id AS cod 
        FROM mdl_classgroup_user
        WHERE classgroupid=$groupId AND userid=$userId";

$users = $DB->get_records_sql($sql);
if (count($users)==0) {
    $newId = $DB->insert_record(
        'classgroup_user',
        array(
            'classgroupid' => $groupId, 
            'userid' => $userId
        )
    );
    echo($newId);
} else
    echo (-1);
?>