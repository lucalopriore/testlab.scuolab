<?php

require('../../config.php');
require_once('lib.php');

$id = optional_param('id', NULL, PARAM_INT);
$maxnews = optional_param('maxnews', NULL, PARAM_INT);

if ($id === NULL || $id<0) exit;
if ($maxnews === NULL || $maxnews<0) exit;

$DB->update_record(
        'probot_class_maxnews',
        array(
            'id' => $id, 
            'maxnews' => $maxnews
        )
    );
echo($id);
   

?>

