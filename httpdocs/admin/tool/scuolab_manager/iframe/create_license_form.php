<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class create_license_form extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement('hidden', 'id', $this->_customdata['id']); // Add elements to your form

        $mform->addElement('text', 'name', get_string('iframe_manager_table_name', 'tool_scuolab_manager')); // Add elements to your form
        $mform->setType('name', PARAM_NOTAGS); //Set type of element
        $mform->setDefault('name', $this->_customdata['name']);

        $mform->addElement('date_selector', 'end_date', get_string('iframe_manager_table_end_date', 'tool_scuolab_manager')); // Add elements to your form
        $mform->setDefault('end_date', $this->_customdata['end_date']);

        $mform->addElement('text', 'seats', get_string('iframe_manager_table_seats', 'tool_scuolab_manager'));
        $mform->setType('seats', PARAM_INT); //Set type of element
        $mform->setDefault('seats', $this->_customdata['seats']);

        $experienceList = array();
        foreach ($this->_customdata['experienceList'] as $exp) {
            $experienceList[$exp->id] = $exp->name;
        }
        $options = array('multiple' => true);
        $mform->addElement('autocomplete', 'experienceList', get_string('iframe_manager_table_experienceList', 'tool_scuolab_manager'), $experienceList, $options);
        $mform->setType('experienceList', PARAM_ALPHANUMEXT);

        $this->add_action_buttons();
    }

    //Custom validation should be added here
    function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        if (count($data['experienceList']) == 0) {
            $errors['experienceList'] = "No experiences set";
        }
        if ($data['name'] == 'License name') {
            $errors['name'] = "Please use a different name";
        }
        if (time() > $data['end_date']) {
            $errors['end_date'] = "Please select a date in the future";
        }
        if ($this->_form->_defaultValues['id'] != 0 && intval($data['seats']) < intval($this->_form->_defaultValues['seats'])) {
            $errors['seats'] = "You cannot lower the number of seats under {$this->_form->_defaultValues['seats']} seats";
        }

        return $errors;
    }
}
