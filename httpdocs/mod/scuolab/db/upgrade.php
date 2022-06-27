<?php

/**
 * @package   mod_scuolab
 * @author    Dario Schember
 * @copyright 2020 onwards Protom S.p.A.
 */


function xmldb_scuolab_upgrade($oldversion = 0)
{
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2020040201) {

        // Define table scuolab_exportedfiles to be created.
        $table = new xmldb_table('scuolab_exportedfiles');

        // Adding fields to table scuolab_exportedfiles.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table scuolab_exportedfiles.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for scuolab_exportedfiles.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Scuolab savepoint reached.
        upgrade_mod_savepoint(true, 2020040201, 'scuolab');
    }
    if ($oldversion < 2020040702) {

        // Define field json to be added to scuolab.
        $table = new xmldb_table('scuolab');
        $field = new xmldb_field('json', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'timemodified');

        // Conditionally launch add field json.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Scuolab savepoint reached.
        upgrade_mod_savepoint(true, 2020040702, 'scuolab');
    }
    if ($oldversion < 2020040703) {

        // Define field storeexportedfile to be added to scuolab.
        $table = new xmldb_table('scuolab');
        $field = new xmldb_field('storeexportedfile', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'json');

        // Conditionally launch add field storeexportedfile.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field2 = new xmldb_field('showdescription', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'storeexportedfile');

        // Conditionally launch add field showdescription.
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Scuolab savepoint reached.
        upgrade_mod_savepoint(true, 2020040703, 'scuolab');
    }
    if ($oldversion < 2020062900) {

        // Define table scuolab_license to be created.
        $table = new xmldb_table('scuolab_license');

        // Adding fields to table scuolab_license.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'No Name');
        $table->add_field('start_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('end_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table scuolab_license.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for scuolab_license.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table scuolab_license_experience to be created.
        $table = new xmldb_table('scuolab_license_experience');

        // Adding fields to table scuolab_license_experience.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('license_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('experience_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table scuolab_license_experience.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('license_id', XMLDB_KEY_FOREIGN, ['license_id'], 'scuolab_license', ['id']);

        // Conditionally launch create table for scuolab_license_experience.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table scuolab_license_code to be created.
        $table = new xmldb_table('scuolab_license_code');

        // Adding fields to table scuolab_license_code.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('license_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table scuolab_license_code.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('license_id', XMLDB_KEY_FOREIGN, ['license_id'], 'scuolab_license', ['id']);
        $table->add_key('code', XMLDB_KEY_UNIQUE, ['code']);

        // Conditionally launch create table for scuolab_license_code.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Scuolab savepoint reached.
        upgrade_mod_savepoint(true, 2020062900, 'scuolab');
    }

    if ($oldversion < 2020063000) {

        // Define key unique_association (unique) to be added to scuolab_license_experience.
        $table = new xmldb_table('scuolab_license_experience');
        $key = new xmldb_key('license_id-experience_id', XMLDB_KEY_UNIQUE, ['license_id', 'experience_id']);

        // Launch add key unique_association.
        $dbman->add_key($table, $key);

        // Scuolab savepoint reached.
        upgrade_mod_savepoint(true, 2020063000, 'scuolab');
    }
    if ($oldversion < 2020070300) {

        // Define table scuolab_activity_session to be created.
        $table = new xmldb_table('scuolab_activity_session');

        // Adding fields to table scuolab_activity_session.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('activity_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('frame_license', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table scuolab_activity_session.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table scuolab_activity_session.
        $table->add_index('user_id', XMLDB_INDEX_NOTUNIQUE, ['user_id']);
        $table->add_index('activity_id', XMLDB_INDEX_NOTUNIQUE, ['activity_id']);

        // Conditionally launch create table for scuolab_activity_session.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table scuolab_activity_event to be created.
        $table = new xmldb_table('scuolab_activity_event');

        // Adding fields to table scuolab_activity_event.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('session_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('event_id', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('absolute_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('relative_time', XMLDB_TYPE_NUMBER, '7, 2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table scuolab_activity_event.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('session_id', XMLDB_KEY_FOREIGN, ['session_id'], 'scuolab_activity_session', ['id']);

        // Conditionally launch create table for scuolab_activity_event.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Scuolab savepoint reached.
        upgrade_mod_savepoint(true, 2020070300, 'scuolab');
    }




    return true;
}
