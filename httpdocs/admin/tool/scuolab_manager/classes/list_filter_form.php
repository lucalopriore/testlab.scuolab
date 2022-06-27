<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */



class tool_scuolab_manager_list_filter_form extends moodleform
{
    /**
     * Form definition method.
     */
    public function definition()
    {
        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $mform->addElement('header', 'displayinfo', get_string('filter', 'tool_scuolab_manager'));

        $mform->addElement('date_selector', 'start_date', get_string('filter_start_date', 'tool_scuolab_manager'), array('startyear' => 2020, 'optional' => true)); // Add elements to your form

        $mform->addElement('date_selector', 'end_date', get_string('filter_end_date', 'tool_scuolab_manager'), array('startyear' => 2020, 'optional' => true)); // Add elements to your form

        $mform->addElement('select', 'experience', get_string('filter_experience', 'tool_scuolab_manager'), $this->_customdata['experiences']);


        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('filter', 'tool_scuolab_manager'));
        $buttonarray[] = $mform->createElement('cancel');
        //$buttonarray[] = $mform->createElement('button', 'download', get_string('download'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function clear()
    {
        $mform = $this->_form;
        $mform->setConstants(array('start_date' => array(), 'end_date' => array(), 'experience' => "all"));
    }

    public function load($start_date, $end_date, $experience)
    {
        $mform = $this->_form;
        $constants = array();
        if (is_array($start_date) == false && $start_date > 0) {
            $constants['start_date'] = $start_date;
        }
        if (is_array($end_date) == false && $end_date > 0) {
            $constants['end_date'] = $end_date;
        }
        if (is_numeric($experience) && $experience >= 0) {
            $constants['experience'] = $experience;
        }
        $mform->setConstants($constants);
    }
}
