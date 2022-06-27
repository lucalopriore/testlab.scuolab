<?php

require('../../config.php');

require_once('lib.php');


$userId = optional_param('userId', NULL, PARAM_INT);

if ($userId === NULL) exit;

$sql  = "SELECT mdl_cohort.id AS classeid, mdl_cohort.name AS classename,
        mdl_role.shortname AS ruolo, mdl_company.id AS scuolaid, mdl_company.name AS scuolaname 
        FROM mdl_user
        JOIN mdl_role_assignments ON mdl_role_assignments.userid=mdl_user.id
        JOIN mdl_role ON mdl_role_assignments.roleid=mdl_role.id
        JOIN mdl_context ON mdl_role_assignments.contextid=mdl_context.id
        JOIN mdl_course ON mdl_context.instanceid=mdl_course.id AND mdl_context.contextlevel=50
        JOIN mdl_company_users ON mdl_company_users.userid=mdl_user.id
        JOIN mdl_company ON mdl_company_users.companyid=mdl_company.id
        JOIN mdl_cohort_members ON mdl_cohort_members.userid=mdl_user.id
        JOIN mdl_cohort ON mdl_cohort_members.cohortid=mdl_cohort.id
        WHERE mdl_user.id=$userId";

$userInfos = $DB->get_records_sql($sql);

$jsonString = "{ \"usersInfo\": [ ";          
foreach($userInfos as $userInfo) {
    $jsonString = $jsonString . "{ \"role\": \"" . $userInfo->ruolo . 
        "\", \"scuolaId\": \"" . $userInfo->scuolaid .
        "\", \"scuolaName\": \"" . $userInfo->scuolaname .
        "\", \"classeId\": \"" . $userInfo->classeid .  
        "\", \"classeName\": \"" . $userInfo->classename ."\" },";
}
$jsonString = substr($jsonString, 0 , -1);
$jsonString = $jsonString . " ] }";
echo ($jsonString);
?>