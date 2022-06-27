<?php

/**
 * External Web Service Template
 *
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */

require_once($CFG->libdir . "/externallib.php");

class mod_scuolab_external extends external_api
{

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hello_world_parameters()
    {
        return new external_function_parameters(
            array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Nome Cognome, '))
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function hello_world($welcomemessage = 'Hello World, ')
    {
        global $USER;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(
            self::hello_world_parameters(),
            array('welcomemessage' => $welcomemessage)
        );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $returnObj = new stdClass();
        $returnObj->message = $params['welcomemessage'] . $USER->firstname;
        return $returnObj;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns()
    {
        //return new external_value(PARAM_TEXT, 'The welcome message + user first name');
        return new external_single_structure(array(
            "message" => new external_value(PARAM_TEXT, 'The welcome message + user first name')
        ));
    }
}
