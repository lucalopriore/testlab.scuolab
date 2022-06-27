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
 * Plugin administration pages js.
 *
 * @package     local_edwiserreports
 * @copyright   2021 wisdmlabs <support@wisdmlabs.com>
 * @author      Yogesh Shirsath
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('local_edwiserreports/settings', ['jquery', 'core/ajax'], function($, Ajax) {
    return {
        init: function() {
            /**
             * Selectors.
             */
            let SELECTORS = {
                TAB: '.edwiserreportstab',
                ACTIVE: '[name="activetab"]',
                LICENSE: '.edwiserreportstab-license',
                SUBMITBUTTON: '.settingsform button[type="submit"]'
            };

            /**
             * Check active tab is license.
             * If license tab then hide submit button.
             */
            function checkLicenseTab() {
                console.log($(SELECTORS.TAB + '.active'));
                if ($(SELECTORS.TAB + '.active').is(SELECTORS.LICENSE)) {
                    $(SELECTORS.SUBMITBUTTON).hide();
                } else {
                    $(SELECTORS.SUBMITBUTTON).show();
                }
                $(SELECTORS.ACTIVE).val($(SELECTORS.TAB + '.active').attr('href').replace('#', ''));
            }

            $(document).ready(function() {
                // Preventing reload notification
                window.onbeforeunload = null;

                // Tab change.
                $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
                    checkLicenseTab();
                });

                checkLicenseTab();
            });
        }
    };
});