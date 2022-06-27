<?php

require('../../config.php');
require_once('lib.php');

$userId = optional_param('userId', NULL, PARAM_INT);

if ($userId === NULL || $userId<0) exit;


$sql = "SELECT mdl_custom_logo_partner.logo_name AS logo
        FROM mdl_user JOIN mdl_cohort_members ON mdl_cohort_members.userid=mdl_user.id 
        JOIN mdl_cohort ON mdl_cohort_members.cohortid=mdl_cohort.id 
        JOIN mdl_custom_cohort_logo_partner ON mdl_custom_cohort_logo_partner.cohortid =mdl_cohort.id 
        JOIN mdl_custom_logo_partner ON mdl_custom_logo_partner.id=mdl_custom_cohort_logo_partner.logo_partnerid 
        WHERE mdl_user.id=$userId";

$logos = $DB->get_records_sql($sql);

$jsonString = "{ \"logos\": [ ";
foreach($logos as $logo) {
    $jsonString = $jsonString . "{ \"logo\": \"" . $logo->logo . "\" },";
}

$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>