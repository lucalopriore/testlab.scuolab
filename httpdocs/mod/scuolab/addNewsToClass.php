<?php

require('../../config.php');
require_once('lib.php');

$classid = optional_param('classid', NULL, PARAM_INT);
$newsid = optional_param('newsid', NULL, PARAM_INT);

if ($classid === NULL || $classid<0) exit;
if ($newsid === NULL || $newsid<0) exit;

$sql  = "SELECT id AS cod 
        FROM mdl_probot_class_news
        WHERE classid=$classid AND newsid=$newsid";

$news = $DB->get_records_sql($sql);
if (count($news)==0) {
    $newId = $DB->insert_record(
        'probot_class_news',
        array(
            'classid' => $classid, 
            'newsid' => $newsid
        )
    );
    echo($newId);
} else
    echo (-1);
?>