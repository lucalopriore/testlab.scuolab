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
    './common',
    './defaultconfig',
    './select2'
], function(
    $,
    Common,
    CFG
) {

    /**
     * Selector
     */
    var SELECTOR = {
        PAGE: '#studentengagement',
        TABLE: '#studentengagement table',
        COURSE: '#studentengagement .course-select',
        FORMFILTER: '#studentengagement .download-links [name="filter"]',
        COHORT: '#studentengagement #cohortfilter',
        COHORTITEM: '#studentengagement #cohortfilter + div .dropdown-item'
    };

    /**
     * Datatable object.
     */
    var dataTable = null;

    /**
     * Cohort id.
     */
    var cohortid = 0;

    /**
     * All promises.
     */
    var PROMISE = {
        /**
         * Get student engagement table data based on filters.
         * @param {Object} filter Filter data
         * @returns {PROMISE}
         */
         GET_DATA: function(filter) {
            return $.ajax({
                url: CFG.requestUrl,
                type: CFG.requestType,
                dataType: CFG.requestDataType,
                data: {
                    action: 'get_studentengagement_table_data_ajax',
                    secret: M.local_edwiserreports.secret,
                    data: JSON.stringify({
                        filter: filter
                    })
                },
            });
        },
    }

    /**
     * Initialize datable.
     */
    function initializeDatatable() {
        dataTable = $(SELECTOR.TABLE).DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            rowId: 'DT_RowId',
            deferRendering: true,
            scrollY: "400px",
            scrollX: true,
            scrollCollapse: true,
            autoWidth: true,
            ordering: false,
            columns: [
                {data: "student"},
                {data: "timespentonlms"},
                {data: "timespentoncourse"},
                {data: "assignmentsubmitted"},
                {data: "activitiescompleted"},
                {data: "visitsoncourse"}
            ],
            dom: '<"edw-studentengagement-top"<"edw-listing"l><"edw-list-filtering"f>>t<"edw-bottom"<' +
            '"edw-list-pagination"p>>i',
            language: {
                sSearch: M.util.get_string('searchuser', 'local_edwiserreports'),
                emptyTable: M.util.get_string('emptytable', 'local_edwiserreports')
            },
            rowCallback: function(row, data) {
                $('td:eq(1)', row).html(data.timespentonlms == 0 ? '-' : Common.timeFormatter(data.timespentonlms));
                $('td:eq(2)', row).html(data.timespentoncourse == 0 ? '-' : Common.timeFormatter(data.timespentoncourse));
            },
            // eslint-disable-next-line no-unused-vars
            ajax: function(data, callback, settings) {
                Common.loader.show(SELECTOR.PAGE);
                PROMISE.GET_DATA({
                    'cohort': cohortid,
                    'course': $(SELECTOR.COURSE).val(),
                    'search': data.search.value,
                    'start': data.start,
                    'length': data.length
                }).done(function(response) {
                    Common.loader.hide(SELECTOR.PAGE);
                    callback(response);
                }).fail(function(exception) {
                    Common.loader.hide(SELECTOR.PAGE);
                });
            }
        });
    }

    /**
     * Initialize
     */
    function init() {
        // Initialize select2.
        $(SELECTOR.PAGE).find('.singleselect').select2();

        // Observer course change event.
        $(SELECTOR.COURSE).on('change', function() {
            $(SELECTOR.FORMFILTER).val([cohortid, $(SELECTOR.COURSE).val()].join('-'));
            if (dataTable === null) {
                initializeDatatable();
                return;
            }
            dataTable.ajax.reload();
        });

        // Observer cohort change.
        $(SELECTOR.COHORTITEM).on('click', function() {
            $(SELECTOR.COHORT).text($(this).text());
            cohortid = $(this).data('cohortid');
            $(SELECTOR.FORMFILTER).val([cohortid, $(SELECTOR.COURSE).val()].join('-'));
            if (dataTable === null) {
                initializeDatatable();
                return;
            }
            dataTable.ajax.reload();
        });

        initializeDatatable();

    }

    return {
        init: function() {
            $(document).ready(function() {
                init();
            });
        }
    };

});
