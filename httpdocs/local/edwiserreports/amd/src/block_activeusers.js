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
/* eslint-disable no-console */
define([
    'jquery',
    './chart/apexcharts',
    'core/notification',
    './defaultconfig',
    './variables',
    './common',
    './flatpickr'
], function($, ApexCharts, Notification, cfg, V, common) {
    /* Varible for active users block */
    var activeUsersData = null;
    var activeUsersGraph = null;
    var panel = cfg.getPanel("#activeusersblock");
    var panelBody = cfg.getPanel("#activeusersblock", "body");
    var panelTitle = cfg.getPanel("#activeusersblock", "title");
    var panelFooter = cfg.getPanel("#activeusersblock", "footer");
    var dropdownMenu = panel + " .dropdown-menu[aria-labelledby='filter-dropdown']:not('custom')";
    var dropdownItem = dropdownMenu + " .dropdown-item";
    var dropdownToggle = panel + " #filter-dropdown.dropdown-toggle";
    var flatpickrCalender = panel + " #flatpickrCalender";
    var chart = panelBody + " .ct-chart";
    var loader = panelBody + " .loader";
    var dropdownButton = panel + " button#filter-dropdown";
    var refreshBtn = panelBody + " .refresh";
    var exportUrlLink = panel + V.exportUrlLink;
    var filter = null;
    var dropdownInput = panelBody + " input.form-control.input";

    /**
     * Line chart default config.
     */
    const lineChartDefault = {
        series: [],
        chart: {
            type: 'area',
            height: 350,
            dropShadow: {
                enabled: true,
                color: '#000',
                top: 18,
                left: 7,
                blur: 10,
                opacity: 0.2
            },
            toolbar: {
                show: false,
                tools: {
                    download: false,
                    reset: '<i class="fa fa-refresh"></i>'
                }
            },
            zoom: {
                enabled: false
            }
        },
        tooltip: {
            enabled: true,
            enabledOnSeries: undefined,
            shared: true,
            followCursor: false,
            intersect: false,
            inverseOrder: false,
            fillSeriesColor: false,
            onDatasetHover: {
                highlightDataSeries: false,
            },
            y: {
                formatter: undefined,
                title: {},
            },
            items: {
                display: 'flex'
            },
            fixed: {
                enabled: false,
                position: 'topRight',
                offsetX: 0,
                offsetY: 0,
            },
        },
        stroke: {
            curve: 'smooth'
        },
        grid: {
            borderColor: '#e7e7e7'
        },
        markers: {
            size: 1
        },
        xaxis: {
            categories: null,
            type: 'datetime',
            labels: {
                hideOverlappingLabels: true,
                datetimeFormatter: {
                    year: 'yyyy',
                    month: 'MMM \'yy',
                    day: 'dd MMM',
                    hour: ''
                }
            },
            tooltip: {
                enabled: false
            }
        },
        legend: {
            position: 'top',
            floating: true
        },
        dataLabels: {
            enabled: false
        }
    };

    /**
     * Initialize
     * @param {function} invalidUser Callback function
     */
    function init(invalidUser) {

        /* Custom Dropdown hide and show */
        activeUsersData = cfg.getActiveUsersBlock();

        // If course progress block is there
        if (activeUsersData) {
            /* Show custom dropdown */
            $(dropdownToggle).on("click", function() {
                $(dropdownMenu).addClass("show");
            });

            /* Added Custom Value in Dropdown */
            $(dropdownInput).ready(function() {
                var placeholder = $(dropdownInput).attr("placeholder");
                $(dropdownInput).val(placeholder);
            });

            /* Hide dropdown when click anywhere in the screen */
            $(document).click(function(e) {
                if (!($(e.target).hasClass("dropdown-menu") ||
                        $(e.target).parents(".dropdown-menu").length)) {
                    $(dropdownMenu).removeClass('show');
                }
            });

            /* Select filter for active users block */
            $(dropdownItem + ":not(.custom)").on('click', function() {
                filter = $(this).attr('value');
                $(dropdownMenu).removeClass('show');
                $(dropdownButton).html($(this).text());
                getActiveUsersBlockData(filter);
                $(flatpickrCalender).val("Custom");
                $(dropdownInput).val("Custom");
            });

            /* Refresh when click on the refresh button */
            $(refreshBtn).on('click', function() {
                $(this).addClass("refresh-spin");
                getActiveUsersBlockData(filter);
            });

            createDropdownCalendar();
            /* Call function to initialize the active users block graph */
            getActiveUsersBlockData()
        }

        /**
         * Create Calender in dropdown tp select range.
         */
        function createDropdownCalendar() {

            $(flatpickrCalender).flatpickr({
                mode: 'range',
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                maxDate: "today",
                appendTo: document.getElementById("activeUser-calendar"),
                onOpen: function() {
                    $(dropdownMenu).addClass('withcalendar');
                },
                onClose: function() {
                    $(dropdownMenu).removeClass('withcalendar');
                    $(dropdownMenu).removeClass('show');
                    selectedCustomDate();
                }
            });
        }

        /**
         * After Select Custom date get active users details.
         */
        function selectedCustomDate() {
            filter = $(flatpickrCalender).val();
            var date = $(dropdownInput).val();

            /* If correct date is not selected then return false */
            if (!filter.includes("to")) {
                return;
            }

            cfg.changeExportUrl(filter, exportUrlLink, V.filterReplaceFlag);
            $(dropdownButton).html(date);
            $(flatpickrCalender).val("");
            getActiveUsersBlockData(filter);
        }

        /**
         * Get data for active users block.
         * @param {String} filter Filter string
         */
        function getActiveUsersBlockData(filter) {
            $(chart).hide();
            $(loader).show();

            /* If filter is not set then select all */
            if (!filter) {
                filter = "weekly";
            }
            // Show loader.
            common.loader.show('#activeusersblock');
            $.ajax({
                url: cfg.requestUrl,
                type: cfg.requestType,
                dataType: cfg.requestDataType,
                data: {
                    action: 'get_activeusers_graph_data_ajax',
                    secret: M.local_edwiserreports.secret,
                    data: JSON.stringify({
                        precalculated: ['weekly', 'monthly', 'yearly'].indexOf(filter) !== -1,
                        filter: filter,
                        graphajax: true
                    })
                },
            }).done(function(response) {
                if (response.error === true && response.exception.errorcode === 'invalidsecretkey') {
                    invalidUser('activeusersblock', response);
                    return;
                }
                activeUsersData.graph.data = response.data;
                activeUsersData.graph.labels = response.labels;
            }).fail(function(error) {
                Notification.exception(error);
            }).always(function() {
                activeUsersGraph = generateActiveUsersGraph();
                // V.changeExportUrl(filter, exportUrlLink, V.filterReplaceFlag);
                $(panelFooter).find('.download-links input[name="filter"]').val(filter);

                // Change graph variables
                resetUpdateTime();
                setInterval(inceamentUpdateTime, 1000 * 60);
                $(refreshBtn).removeClass('refresh-spin');
                $(loader).hide();
                $(chart).fadeIn("slow");

                // Hide loader.
                common.loader.hide('#activeusersblock');
            });
        }

        /**
         * Reset Update time in panel header.
         */
        function resetUpdateTime() {
            $(panelBody + " #updated-time > span.minute").html(0);
        }

        /**
         * Increament update time in panel header.
         */
        function inceamentUpdateTime() {
            $(panelBody + " #updated-time > span.minute")
                .html(parseInt($(panelBody + " #updated-time > span.minute").text()) + 1);
        }

        /**
         * Generate Active Users graph.
         * @returns {Object} Active users graph
         */
        function generateActiveUsersGraph() {
            if (activeUsersGraph) {
                activeUsersGraph.destroy();
            }
            activeUsersGraph = new ApexCharts($("#apex-chart-active-users").get(0), getGraphData());
            activeUsersGraph.render();
            return activeUsersGraph;
        }

        /**
         * Get graph data.
         * @return {Object}
         */
        function getGraphData() {
            let data = Object.assign({}, lineChartDefault);
            try {
                data.series = [{
                    name: activeUsersData.graph.labelName.activeUsers,
                    data: activeUsersData.graph.data.activeUsers,
                }, {
                    name: activeUsersData.graph.labelName.enrolments,
                    data: activeUsersData.graph.data.enrolments,
                }, {
                    name: activeUsersData.graph.labelName.completionRate,
                    data: activeUsersData.graph.data.completionRate,
                }];
                data.xaxis.categories = activeUsersData.graph.labels;
                data.chart.toolbar.show = activeUsersData.graph.labels.length > 30;
                data.chart.zoom.enabled = activeUsersData.graph.labels.length > 30;
            } catch (error) {
                data.series = [];
                data.xaxis.categories = [];
                data.chart.toolbar.show = false;
                data.chart.zoom.enabled = false;
            }
            return data;
        }
    }

    // Must return the init function
    return {
        init: init
    };
});
