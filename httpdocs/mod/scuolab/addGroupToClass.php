<?php

require('../../config.php');
require_once('lib.php');

$classId = optional_param('classId', NULL, PARAM_INT);
$groupName = optional_param('groupName', NULL, PARAM_TEXT);

if ($classId === NULL || $classId<0) exit;
if ($groupName === NULL) exit;

$sql  = "SELECT userid AS cod 
        FROM mdl_cohort_members
        WHERE cohortid=$classId";

$users = $DB->get_records_sql($sql);
if (count($users)>0) {
    
    $newId = $DB->insert_record(
        'classgroup',
        array(
            'cohortid' => $classId, 
            'groupname' => $groupName
        )
    );

    foreach($users as $user) {
        $DB->insert_record(
            'classgroup_user',
            array(
                'classgroupid' => $newId, 
                'userid' => $user->cod
            )
        );
    }

    $jsonString = "{ \"groups\": [ { \"groupId\": \"" . $newId . "\", \"groupName\": \"" . $groupName . "\" } ] }";
    echo($jsonString);
} else
    echo ("-1");
?>