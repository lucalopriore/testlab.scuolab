<?php

require('../../config.php');
require_once('lib.php');

$classId = optional_param('classId', NULL, PARAM_INT);
$agentId = optional_param('agentId', NULL, PARAM_INT);

if ($classId === NULL || $classId<0) exit;
if ($agentId === NULL || $agentId<0) exit;

$sql  = "SELECT intentid AS intentcod, userid AS usercode, username AS usernome 
        FROM mdl_intent
        JOIN mdl_user ON userid=mdl_user.id
        WHERE cohortid=$classId AND agentid=$agentId";

$intents = $DB->get_records_sql($sql);
   
$jsonString = "{ \"intents\": [ ";
foreach($intents as $intent) {
    $jsonString = $jsonString . "{ \"intentId\": \"" . $intent->intentcod .
        "\", \"userId\": \"" . $intent->usercode . 
        "\", \"username\": \"" . $intent->usernome ."\" },";
}
$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>