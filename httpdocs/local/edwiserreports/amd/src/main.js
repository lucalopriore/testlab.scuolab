// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Plugin administration pages are defined here.
 *
 * @package     local_edwiserreports
 * @copyright   2021 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/modal_factory',
    'core/modal_events',
    './modal-migration',
    './block_accessinfo',
    './block_activecourses',
    './block_activeusers',
    './block_courseprogress',
    './block_inactiveusers',
    './block_realtimeusers',
    './block_studentengagement',
    './block_todaysactivity',
    './block_learner',
    './block_grade'
], function(
    $,
    ModalFactory,
    ModalEvents,
    Migration,
    accessInfo,
    activeCourses,
    activeUsers,
    courseProgress,
    inactiveUsers,
    realTimeUsers,
    studentEngagement,
    todaysActivity,
    learner,
    grade
) {
    /**
     * Blocks list.
     */
    var blocks = [
        accessInfo,
        activeCourses,
        activeUsers,
        courseProgress,
        inactiveUsers,
        realTimeUsers,
        studentEngagement,
        todaysActivity,
        learner,
        grade
    ];

    /**
     * This function will show validation error in block card.
     * @param {String} blockid Block id
     * @param {Object} response User validation response
     */
    function validateUser(blockid, response) {
        $(`#${blockid} .panel-body`).html(response.exception.message);
    }

    /**
     * Init main.js
     */
    var init = function() {
        $(document).ready(function() {
            blocks.forEach(block => {
                block.init(validateUser);
            });
        });
    };

    function initMigration() {
        ModalFactory.create({
            type: Migration.TYPE
        }, $('#create'));
    }

    // Must return the init function
    return {
        init: init,
        initMigration: initMigration
    };
});