<?php

require('../../config.php');
require_once('lib.php');

$userId = optional_param('userId', NULL, PARAM_INT);

if ($userId === NULL || $userId<0) exit;

$sql  = "SELECT mdl_classgroup.id AS cod, mdl_classgroup.groupname AS nome 
        FROM mdl_classgroup
        JOIN mdl_classgroup_user ON mdl_classgroup.id = mdl_classgroup_user.classgroupid
        WHERE mdl_classgroup_user.userid=$userId";

$groups = $DB->get_records_sql($sql);

$jsonString = "{ \"groups\": [ ";
foreach($groups as $group) {
    $jsonString = $jsonString . "{ \"groupId\": \"" . $group->cod .
        "\", \"groupName\": \"" . $group->nome ."\" },";
}

$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>