<?php

require('../../config.php');
require_once('lib.php');


$sql  = "SELECT mdl_probotnews.id AS cod, mdl_probotnews.site AS nome 
        FROM mdl_probotnews";

$sites = $DB->get_records_sql($sql);

$jsonString = "{ \"sites\": [ ";
        foreach($sites as $user) {
            $jsonString = $jsonString . "{ \"siteId\": \"" . $user->cod .
                "\", \"sitelink\": \"" . $user->nome ."\" },";
        }
        
        $jsonString = substr($jsonString, 0 , -1);
        $jsonString = $jsonString . " ] }";
        echo ($jsonString);
?>


