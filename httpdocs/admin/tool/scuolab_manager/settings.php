<?php

/**
 * @package    tool
 * @subpackage scuolab_manager
 * @copyright  2020 Protom
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('root', new admin_externalpage(
    'iframe_manager',
    get_string('iframe_manager', 'tool_scuolab_manager'),
    "$CFG->wwwroot/$CFG->admin/tool/scuolab_manager/iframe/index.php"
));

$ADMIN->add('reports', new admin_externalpage(
    'scuolab_event_report_list',
    get_string('event_report', 'tool_scuolab_manager'),
    "$CFG->wwwroot/$CFG->admin/tool/scuolab_manager/report/index.php"
));
