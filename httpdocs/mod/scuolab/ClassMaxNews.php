<?php

require('../../config.php');
require_once('lib.php');

$classId = optional_param('classId', NULL, PARAM_INT);

if ($classId === NULL || $classId<0) exit;

$sql  = "SELECT mdl_probot_class_maxnews.id AS cod, mdl_probot_class_maxnews.maxnews AS numbernews
        FROM mdl_probot_class_maxnews
        WHERE mdl_probot_class_maxnews.classid=$classId";

$classMaxNews = $DB->get_records_sql($sql);

$jsonString = "{ \"classMaxNews\": [ ";
foreach($classMaxNews as $classMaxNew) {
        $jsonString = $jsonString . "{ \"classMaxNewsId\": \"" . $classMaxNew->cod .
            "\", \"newsMax\": \"" . $classMaxNew->numbernews ."\" },";
}

$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>