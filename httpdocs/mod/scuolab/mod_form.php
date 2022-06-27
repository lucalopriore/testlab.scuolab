<?php

/**
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/scuolab/lib.php');

class mod_scuolab_mod_form extends moodleform_mod
{

    function definition()
    {
        global $CFG, $DB, $OUTPUT;

        $mform = &$this->_form;

        $experience_types = getExperiencesNames();

        // Name.
        $mform->addElement('hidden', 'name', "Stub value. To fill with json data");
        $mform->setType("name", PARAM_FILE);

        $mform->addElement('select', 'experience', get_string('experience', 'scuolab'), $experience_types);
        $mform->setDefault('experience', 0);
        $mform->addHelpButton('experience', 'experience', 'scuolab');

        //Export options
        $exportOptions = array(0 => get_string("downloadExport", 'scuolab'), 1 => get_string('storeExport', 'scuolab'));
        $mform->addElement('select', 'storeexportedfile', get_string('storeexportedfile', 'scuolab'), $exportOptions);
        $mform->setDefault('storeexportedfile', 0);

        // Summary.
        $showDescriptionOptions = array(-1 => get_string("descriptionUp", 'scuolab'), 0 => get_string("descriptionHide", 'scuolab'), 1 => get_string("descriptionDown", 'scuolab'));
        $mform->addElement('select', 'showdescription', get_string('showdescription', 'scuolab'), $showDescriptionOptions);
        $mform->setDefault('showdescription', 1);

        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    function set_data($default_values)
    {
        $jsonNames = array_map(function ($obj) {
            return $obj->jsonName;
        }, getExperiencesObjects());
        $id = isset($default_values->json) ? array_search($default_values->json, $jsonNames) : false;
        if ($id != false) {
            $default_values->experience = $id;
        }
        parent::set_data($default_values);
    }
}
