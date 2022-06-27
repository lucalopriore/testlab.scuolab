<?php

require('../../config.php');
require_once('lib.php');

$classid = optional_param('classid', NULL, PARAM_INT);
$maxnews = optional_param('maxnews', NULL, PARAM_INT);

if ($classid === NULL || $classid<0) exit;
if ($maxnews === NULL || $maxnews<0) exit;

$sql  = "SELECT id AS cod 
        FROM mdl_probot_class_maxnews
        WHERE classid=$classid AND maxnews=$maxnews";

$max = $DB->get_records_sql($sql);
if (count($max)==0) {
    $newId = $DB->insert_record(
        'probot_class_maxnews',
        array(
            'classid' => $classid, 
            'maxnews' => $maxnews
        )
    );
    echo($newId);
   
} else
    echo (-1);
?>

