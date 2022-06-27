<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */

require_once('../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('./create_license_form.php');
require_login();

$id = optional_param("id", NULL, PARAM_INT);

$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);


$sql = "SELECT cm.id AS id, CONCAT(s.name,' (',c.shortname,')') AS name
        FROM {scuolab} s 
        JOIN {course_modules} cm ON s.id = cm.instance 
        JOIN {modules} m ON m.id = cm.module 
        JOIN {course} c ON c.id=s.course 
        WHERE m.name = 'scuolab';";

$allExperiencesData = $DB->get_records_sql($sql, array());

$endDate = time() + ((52 * 7 + 1) * 24 * 60 * 60);
$defaults = array('id' => 0, 'name' => 'License name', 'end_date' => $endDate, 'seats' => 500, 'experienceList' => $allExperiencesData);
$form = new create_license_form(null, $defaults);

if ($id !=  NULL) {
    $licenseData = $DB->get_record('scuolab_license', array('id' => $id));
} else {
    $licenseData = NULL;
}
if ($licenseData != NULL) {

    $currentExperiences = $DB->get_records('scuolab_license_experience', array('license_id' => $id), $sort = 'experience_id');
    $currentExperiencesId = array_column($currentExperiences, 'experience_id');
    $licenseData->experiences = join(", ", $currentExperiencesId);
    $licenseData->experienceList = $currentExperiencesId;

    $existingSeatCount = $DB->get_record('scuolab_license_code', array('license_id' => $id), $fields = 'COUNT(*) count');
    $existingSeatCount = $existingSeatCount->count;
    $licenseData->seats = $existingSeatCount;

    $form->set_data($licenseData);
}

if ($form->is_cancelled()) {
    // You need this section if you have a cancel button on your form
    // here you tell php what to do if your user presses cancel
    // probably a redirect is called for!
    // PLEASE NOTE: is_cancelled() should be called before get_data().
    redirect("index.php");
} else if ($fromform = $form->get_data()) {
    // This branch is where you process validated data.
    if (intval($fromform->id) <= 0) {
        //Create N new licenses code

        $licenseRecord = new stdClass();
        $licenseRecord->name = $fromform->name;
        $licenseRecord->start_date = time();
        $licenseRecord->end_date = $fromform->end_date;

        $newId = $DB->insert_record('scuolab_license', $licenseRecord);

        $experiencesIds = $fromform->experienceList;

        $experiencesRecords = array();

        foreach ($experiencesIds as $experienceId) {
            $experiencesRecord = new stdClass();
            $experiencesRecord->license_id = $newId;
            $experiencesRecord->experience_id = $experienceId;
            $experiencesRecords[] = $experiencesRecord;
        }
        $DB->insert_records('scuolab_license_experience', $experiencesRecords);

        $serialCodeRecords = array();
        $seats = intval($fromform->seats);
        for ($i = 0; $i < $seats; $i++) {
            $serialCodeRecord = new stdClass();
            $serialCodeRecord->license_id = $newId;
            $serialCodeRecord->code = generateRandomString(120);
            $serialCodeRecord->active = 1;
            $serialCodeRecords[] = $serialCodeRecord;
        }

        $DB->insert_records('scuolab_license_code', $serialCodeRecords);
    } else {

        $licenseRecord = new stdClass();
        $licenseRecord->id = $id;
        $licenseRecord->name = $fromform->name;
        $licenseRecord->end_date = $fromform->end_date;

        $DB->update_record('scuolab_license', $licenseRecord);



        $experiencesIds = $fromform->experienceList;

        $idsRequested = join(", ", $experiencesIds);
        $DB->delete_records_select('scuolab_license_experience', "license_id = {$id} AND experience_id NOT IN ({$idsRequested})");

        $experiencesToAdd =  array_diff($experiencesIds, $currentExperiencesId);
        $experienceToAddRecords = array();
        foreach ($experiencesToAdd as $experienceToAdd) {
            $record = new stdClass();
            $record->license_id = $id;
            $record->experience_id = $experienceToAdd;
            $experienceToAddRecords[] = $record;
        }

        $DB->insert_records('scuolab_license_experience', $experienceToAddRecords);

        $serialCodeRecords = array();
        $seats = intval($fromform->seats) - intval($existingSeatCount);
        if ($seats > 0) {
            for ($i = 0; $i < $seats; $i++) {
                $serialCodeRecord = new stdClass();
                $serialCodeRecord->license_id = $id;
                $serialCodeRecord->code = generateRandomString(120);
                $serialCodeRecord->active = 1;
                $serialCodeRecords[] = $serialCodeRecord;
            }

            $DB->insert_records('scuolab_license_code', $serialCodeRecords);
        }
    }

    // Typically you finish up by redirecting to somewhere where the user
    // can see what they did.
    redirect("index.php");
}


echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('iframe_manager', 'tool_scuolab_manager'));

$form->display();

echo $OUTPUT->footer();

function generateRandomString($length = 25)
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
