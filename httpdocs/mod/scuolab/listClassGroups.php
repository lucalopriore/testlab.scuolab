<?php

require('../../config.php');
require_once('lib.php');

$classId = optional_param('classId', NULL, PARAM_INT);

if ($classId === NULL || $classId<0) exit;

$sql  = "SELECT id AS cod, groupname AS nome 
        FROM mdl_classgroup
        WHERE cohortid=$classId";

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