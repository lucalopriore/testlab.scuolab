<?php

require('../../config.php');
require_once('lib.php');

$classid = optional_param('classid', NULL, PARAM_INT);
$newsid = optional_param('newsid', NULL, PARAM_INT);

if ($classid === NULL || $classid<0) exit;
if ($newsid === NULL || $newsid<0) exit;

$DB->delete_records(
    'probot_class_news',
    array(
        'classid' => $classid, 
        'newsid' => $newsid
    )
);
?>