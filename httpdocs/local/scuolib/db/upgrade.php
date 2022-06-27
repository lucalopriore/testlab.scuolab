<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_scuolib_upgrade($oldversion = 0)
{
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2020042304) {

        // Define table local_scuolib to be created.
        $table = new xmldb_table('local_scuolib');

        // Adding fields to table local_scuolib.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('clienttoken', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null);
        $table->add_field('webglversion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_scuolib.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_scuolib.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Scuolib savepoint reached.
        upgrade_plugin_savepoint(true, 2020042304, 'local', 'scuolib');
    }
}
