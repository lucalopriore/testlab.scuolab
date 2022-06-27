<?php

require('../../config.php');
require_once('lib.php');

$groupId = optional_param('groupId', NULL, PARAM_INT);

if ($groupId === NULL || $groupId<0) exit;

$sql  = "SELECT mdl_user.id AS cod, mdl_user.username AS nome 
        FROM mdl_user
        JOIN mdl_classgroup_user ON mdl_user.id = mdl_classgroup_user.userid
        WHERE mdl_classgroup_user.classgroupid=$groupId";

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