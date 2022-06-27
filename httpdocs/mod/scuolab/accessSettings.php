<?php

require('../../config.php');

require_once('lib.php');


$avatarId = optional_param('avatarId', NULL, PARAM_INT);
$classId = optional_param('classId', NULL, PARAM_INT);
$groupId = optional_param('groupId', NUll, PARAM_INT);

if (($classId === NULL && $groupId === NULL) ||
    ($classId !== NULL && $groupId !== NULL) ||
    ($classId !==NULL && $classId<0) || 
    ($groupId !==NULL && $groupId<0)) exit;
if ($avatarId === NULL || $avatarId<0) exit;

$sql = "";

if ($classId!==NULL)
    $sql  = "SELECT mdl_agent_settings.id AS cod, mdl_agent_settings.agentname AS aname, 
             projectid AS proj, credentialsname AS cname, serviceaccount AS serv, 
             originalkey AS okey, credentialskey AS ckey 
             FROM mdl_agent_settings
             JOIN mdl_cohort_agent ON mdl_agent_settings.id=mdl_cohort_agent.agentid
             WHERE mdl_cohort_agent.cohortid=$classId AND isgroupassigned=0 AND mdl_agent_settings.avatarid=$avatarId AND isfavorite=1";

if ($groupId!==NULL)
    $sql  = "SELECT mdl_agent_settings.id AS cod, mdl_agent_settings.agentname AS aname, 
             projectid AS proj, credentialsname AS cname, serviceaccount AS serv, 
             originalkey AS okey, credentialskey AS ckey 
             FROM mdl_agent_settings
             JOIN mdl_cohort_agent ON mdl_agent_settings.id=mdl_cohort_agent.agentid
             WHERE mdl_cohort_agent.groupid=$groupId AND isgroupassigned=1 AND mdl_agent_settings.avatarid=$avatarId AND isfavorite=1";

$agentSettings = $DB->get_records_sql($sql);

$jsonString = "{ \"settings\": [ ";
    
foreach($agentSettings as $agentSetting) {
    $jsonString = $jsonString . "{ \"agentId\": \"" . $agentSetting->cod .
        "\", \"agentName\": \"" . $agentSetting->aname .
        "\", \"projectId\": \"" . $agentSetting->proj . 
        "\", \"credentialsName\": \"" . $agentSetting->cname .
        "\", \"serviceAccount\": \"" . $agentSetting->serv . 
        "\", \"originalKey\": \"" . $agentSetting->okey .
        "\", \"credentialsKey\": \"" . $agentSetting->ckey ."\" },";
}
$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>