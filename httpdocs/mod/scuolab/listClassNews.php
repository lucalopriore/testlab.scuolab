<?php

require('../../config.php');
require_once('lib.php');

$classId = optional_param('classId', NULL, PARAM_INT);

if ($classId === NULL || $classId<0) exit;

$sql  = "SELECT mdl_probot_class_news.id AS cod, mdl_probotnews.id AS nid, mdl_probotnews.site AS nsite
        FROM mdl_probot_class_news
        JOIN mdl_probotnews ON mdl_probot_class_news.newsid = mdl_probotnews.id
        WHERE mdl_probot_class_news.classid=$classId";

$classNews = $DB->get_records_sql($sql);

$jsonString = "{ \"classNews\": [ ";
foreach($classNews as $classnew) {
        $jsonString = $jsonString . "{ \"classNewsId\": \"" . $classnew->cod .
            "\", \"newsId\": \"" . $classnew->nid .
            "\", \"newsSite\": \"" . $classnew->nsite ."\" },";
}

$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>