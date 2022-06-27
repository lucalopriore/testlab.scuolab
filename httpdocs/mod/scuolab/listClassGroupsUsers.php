<?php

require('../../config.php');
require_once('lib.php');

$classId = optional_param('classId', NULL, PARAM_INT);

if ($classId === NULL || $classId<0) exit;

$sql  = "SELECT  mdl_classgroup_user.id, mdl_agent_settings.id AS agid, mdl_agent_settings.agentname AS agname, 
        mdl_classgroup.id AS grid, mdl_classgroup.groupname AS grname,
        mdl_user.id AS usid, mdl_user.username AS usname
        FROM mdl_classgroup
        JOIN mdl_cohort ON mdl_cohort.id = mdl_classgroup.cohortid
        JOIN mdl_cohort_members ON mdl_cohort.id = mdl_cohort_members.cohortid
        JOIN mdl_user ON mdl_user.id = mdl_cohort_members.userid
        JOIN mdl_classgroup_user ON mdl_classgroup_user.classgroupid = mdl_classgroup.id
        JOIN mdl_cohort_agent ON mdl_cohort_agent.groupid = mdl_classgroup.id
        JOIN mdl_agent_settings ON mdl_agent_settings.id = mdl_cohort_agent.agentid
        WHERE mdl_classgroup_user.userid = mdl_user.id AND mdl_cohort.id=$classId AND isgroupassigned=1";

$users = $DB->get_records_sql($sql);

$jsonString = "{ \"users\": [ ";
foreach($users as $user) {
    $jsonString = $jsonString . "{ \"agentId\": \"" . $user->agid .
        "\", \"agentName\": \"" . $user->agname .
        "\", \"groupId\": \"" . $user->grid . 
        "\", \"groupName\": \"" . $user->grname .
        "\", \"userId\": \"" . $user->usid . 
        "\", \"userName\": \"" . $user->usname ."\" },";
}

$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>