<?php
/**
 * Web service mod plugin for scuolab
 *
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */

// We defined the web service functions to install.
$functions = array(
        'mod_scuolab_hello_world' => array(
                'classname'   => 'mod_scuolab_external',
                'methodname'  => 'hello_world',
                'classpath'   => 'mod/scuolab/externallib.php',
                'description' => 'Return Hello World FIRSTNAME. Can change the text (Hello World) sending a new text as parameter',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Hello Service' => array(
                'functions' => array ('mod_scuolab_hello_world'),
                'restrictedusers' => 0,
                'enabled'=>1,
                'shortname'=>"hello_service",
        )
);
