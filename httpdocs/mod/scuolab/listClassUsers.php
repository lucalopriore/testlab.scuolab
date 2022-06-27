<?php

require('../../config.php');
require_once('lib.php');

$classId = optional_param('classId', NULL, PARAM_INT);

if ($classId === NULL || $classId<0) exit;

$sql  = "SELECT mdl_user.id AS cod, mdl_user.username AS nome 
        FROM mdl_user
        JOIN mdl_cohort_members ON mdl_user.id = mdl_cohort_members.userid
        WHERE mdl_cohort_members.cohortid=$classId";

$users = $DB->get_records_sql($sql);

$jsonString = "{ \"users\": [ ";
foreach($users as $user) {
    $jsonString = $jsonString . "{ \"userId\": \"" . $user->cod .
        "\", \"username\": \"" . $user->nome ."\" },";
}

$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>