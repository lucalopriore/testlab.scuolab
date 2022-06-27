<?php

require('../../config.php');

require_once('lib.php');


$agentName = optional_param('agentName', NULL, PARAM_TEXT);
$userId = optional_param('userId', NULL, PARAM_INT);

if ($agentName === NULL) exit;

$sql = "";
if ($userId === NULL) {
        $sql  = "SELECT id AS cod, agentname AS aname, 
                projectid AS proj, credentialsname AS cname, serviceaccount AS serv, 
                originalkey AS okey, credentialskey AS ckey 
                FROM mdl_agent_settings
                WHERE agentname=$agentName";
} else {
        $sql  = "SELECT id AS cod, agentname AS aname, 
                projectid AS proj, credentialsname AS cname, serviceaccount AS serv, 
                originalkey AS okey, credentialskey AS ckey 
                FROM mdl_agent_settings
                WHERE agentname=$agentName AND creatorid=$userId";
}

$agentSettings = $DB->get_records_sql($sql);
$success = "";
if (count($agentSettings)>0)
        $success = "True";
else
        $success = "False";

$jsonString = "{ \"success\": \"" . $success . "\", \"settings\": [ ";

if (count($agentSettings)==0 && $userId !== NULL) {
        $sql  = "SELECT id AS cod, agentname AS aname, 
                projectid AS proj, credentialsname AS cname, serviceaccount AS serv, 
                originalkey AS okey, credentialskey AS ckey 
                FROM mdl_agent_settings
                WHERE creatorid=$userId";
                
        $agentSettings = $DB->get_records_sql($sql);       
}

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