define([
    'jquery',
    'core/ajax',
    'core/templates',
    './chart/apexcharts',
    './common',
    './defaultconfig',
    './select2'
], function(
    $,
    Ajax,
    Templates,
    ApexCharts,
    Common,
    CFG
) {
    /**
     * List of graphs in this block.
     */
    var allGraphs = ['visitsonlms', 'timespentonlms', 'timespentoncourse', 'courseactivitystatus'];

    /**
     * Flat picker elements.
     */
    var flatpickr = {
        'visitsonlms': null,
        'timespentonlms': null,
        'timespentoncourse': null,
        'courseactivitystatus': null
    };

    /**
     * Charts list.
     */
    var charts = {
        'visitsonlms': null,
        'timespentonlms': null,
        'timespentoncourse': null,
        'courseactivitystatus': null
    };

    /**
     * Filter for ajax.
     */
    var filter = {
        'visitsonlms': {
            date: 'weekly',
            student: 0
        },
        'timespentonlms': {
            date: 'weekly',
            student: 0
        },
        'timespentoncourse': {
            date: 'weekly',
            course: 0,
            student: 0
        },
        'courseactivitystatus': {
            date: 'weekly',
            course: 0,
            student: 0
        }
    };

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
        },
        noData: {
            text: M.util.get_string('nographdata', 'local_edwiserreports')
        }
    };

    /**
     * Bar chart default config.
     */
    const barChartDefault = {
        series: [],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: true,
                tools: {
                    download: false,
                    selection: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
                    reset: '<i class="fa fa-refresh"></i>'
                }
            },
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
            marker: {
                show: true
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
        plotOptions: {
            bar: {
                columnWidth: '50%'
            }
        },
        grid: {
            borderColor: '#e7e7e7'
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: null,
            labels: {
                hideOverlappingLabels: true,
                trim: true
            },
            tickPlacement: 'on'
        },
        noData: {
            text: M.util.get_string('nographdata', 'local_edwiserreports')
        }
    };

    /**
     * Selectors list.
     */
    var SELECTOR = {
        PANEL: '#studentengagementblock',
        DATE: '.studentengagement-calendar',
        DATEMENU: '.studentengagement-calendar + .dropdown-menu',
        DATEITEM: '.studentengagement-calendar + .dropdown-menu .dropdown-item',
        DATEPICKER: '.studentengagement-calendar + .dropdown-menu .dropdown-calendar',
        DATEPICKERINPUT: '.studentengagement-calendar + .dropdown-menu .flatpickr',
        FORMFILTER: '.download-links [name="filter"]',
        COURSE: '.course-select',
        STUDENT: '.student-select',
        GRAPH: '.graph',
        GRAPHS: '.studentengagement-graphs',
        FILTERS: '.filters',
        VISITSONLMS: '.visitsonlms',
        TIMESPENTONLMS: '.timespentonlms',
        TIMESPENTONCOURSE: '.timespentoncourse',
        COURSEACTIVITYSTATUS: '.courseactivitystatus'
    };

    /**
     * All promises.
     */
    var PROMISE = {
        /**
         * Get students using course id.
         *
         * @param {Integer} courseid Course id
         * @returns {PROMISE}
         */
        GET_STUDENTS: function(courseid) {
            return Ajax.call([{
                methodname: 'local_edwiserreports_get_students_of_course',
                args: {
                    courseid: courseid
                }
            }], false)[0];
        },
        /**
         * Get visits on lms using filters.
         * @param {Object} filter Filter data
         * @returns {PROMISE}
         */
        GET_VISITSONLMS: function(filter) {
            return $.ajax({
                url: CFG.requestUrl,
                type: CFG.requestType,
                dataType: CFG.requestDataType,
                data: {
                    action: 'get_studentengagement_visitsonlms_graph_data_ajax',
                    secret: M.local_edwiserreports.secret,
                    data: JSON.stringify({
                        filter: filter
                    })
                },
            });
        },
        /**
         * Get timespent on lms using filters.
         * @param {Object} filter Filter data
         * @returns {PROMISE}
         */
        GET_TIMESPENTONLMS: function(filter) {
            return $.ajax({
                url: CFG.requestUrl,
                type: CFG.requestType,
                dataType: CFG.requestDataType,
                data: {
                    action: 'get_studentengagement_timespentonlms_graph_data_ajax',
                    secret: M.local_edwiserreports.secret,
                    data: JSON.stringify({
                        filter: filter
                    })
                },
            });
        },
        /**
         * Get timespent on course using filters.
         * @param {Object} filter Filter data
         * @returns {PROMISE}
         */
        GET_TIMESPENTONCOURSE: function(filter) {
            return $.ajax({
                url: CFG.requestUrl,
                type: CFG.requestType,
                dataType: CFG.requestDataType,
                data: {
                    action: 'get_studentengagement_timespentoncourse_graph_data_ajax',
                    secret: M.local_edwiserreports.secret,
                    data: JSON.stringify({
                        filter: filter
                    })
                },
            });
        },
        /**
         * Get timespent on course using filters.
         * @param {Object} filter Filter data
         * @returns {PROMISE}
         */
        GET_COURSEACTIVITYSTATUS: function(filter) {
            return $.ajax({
                url: CFG.requestUrl,
                type: CFG.requestType,
                dataType: CFG.requestDataType,
                data: {
                    action: 'get_studentengagement_courseactivitystatus_graph_data_ajax',
                    secret: M.local_edwiserreports.secret,
                    data: JSON.stringify({
                        filter: filter
                    })
                },
            });
        }
    }

    /**
     * Load graph
     * @param {String} target Graph name
     */
    function loadGraph(target) {
        Common.loader.show('#studentengagementblock .' + target);

        // Set export filter to download link.
        let exportFilter = Object.keys(filter[target]).map(key => filter[target][key]).join("-") + '-' + target;
        $(SELECTOR.PANEL).find(`.${target}`).find(SELECTOR.FORMFILTER).val(exportFilter);

        /**
         * Render graph.
         * @param {DOM} graph Graph element
         * @param {Object} data Graph data
         */
        function renderGraph(graph, data) {
            if (charts[target] !== null) {
                charts[target].destroy();
            }
            charts[target] = new ApexCharts(graph.get(0), data);
            charts[target].render();
            setTimeout(function() {
                Common.loader.hide('#studentengagementblock .' + target);
            }, 1000);
        }

        switch (target) {
            case 'visitsonlms':
                PROMISE.GET_VISITSONLMS(filter[target])
                    .done(function(response) {
                        let data = Object.assign({}, lineChartDefault);
                        data.series = [{
                            name: M.util.get_string('visitsonlms', 'local_edwiserreports'),
                            data: response.visits,
                        }];
                        data.xaxis.categories = response.labels;
                        data.chart.toolbar.show = response.labels.length > 30;
                        data.chart.zoom.enabled = response.labels.length > 30;
                        data.tooltip.y.title.formatter = () => {
                            return M.util.get_string('visits', 'local_edwiserreports') + ': ';
                        }
                        renderGraph($(SELECTOR.PANEL).find(SELECTOR.VISITSONLMS).find(SELECTOR.GRAPH), data)
                    }).fail(function(exception) {
                        Common.loader.hide('#studentengagementblock .' + target);
                    });
                break;
            case 'timespentonlms':
                PROMISE.GET_TIMESPENTONLMS(filter[target])
                    .done(function(response) {
                        let data = Object.assign({}, lineChartDefault);
                        data.series = [{
                            name: M.util.get_string('timespentonlms', 'local_edwiserreports'),
                            data: response.timespent,
                        }];
                        data.yaxis = {
                            labels: {
                                formatter: Common.timeFormatter
                            }
                        };
                        data.xaxis.categories = response.labels;
                        data.chart.toolbar.show = response.labels.length > 30;
                        data.chart.zoom.enabled = response.labels.length > 30;
                        data.tooltip.y.title.formatter = () => {
                            return M.util.get_string('time', 'local_edwiserreports') + ': ';
                        }
                        renderGraph($(SELECTOR.PANEL).find(SELECTOR.TIMESPENTONLMS).find(SELECTOR.GRAPH), data);
                    }).fail(function(exception) {
                        Common.loader.hide('#studentengagementblock .' + target);
                    });
                break;
            case 'timespentoncourse':
                PROMISE.GET_TIMESPENTONCOURSE(filter[target])
                    .done(function(response) {
                        let data;
                        if (filter[target].course == 0) {
                            data = Object.assign({}, barChartDefault);
                        } else {
                            data = Object.assign({}, lineChartDefault);
                        }
                        data.series = [{
                            name: M.util.get_string('timespentoncourse', 'local_edwiserreports'),
                            data: response.timespent,
                        }];
                        data.xaxis.categories = response.labels;
                        data.yaxis = {
                            labels: {
                                formatter: Common.timeFormatter
                            }
                        };
                        data.chart.toolbar.show = response.labels.length > 30;
                        data.chart.zoom = {
                            enabled: response.labels.length > 30
                        };
                        data.tooltip.y.title.formatter = () => {
                            return M.util.get_string('time', 'local_edwiserreports') + ': ';
                        }
                        renderGraph($(SELECTOR.PANEL).find(SELECTOR.TIMESPENTONCOURSE).find(SELECTOR.GRAPH), data);
                    }).fail(function(exception) {
                        Common.loader.hide('#studentengagementblock .' + target);
                    });
                break;
            case 'courseactivitystatus':
                PROMISE.GET_COURSEACTIVITYSTATUS(filter[target])
                    .done(function(response) {
                        let data = Object.assign({}, lineChartDefault);
                        data.series = [{
                            name: M.util.get_string('courseactivitystatus-submissions', 'local_edwiserreports'),
                            data: response.submissions,
                        }, {
                            name: M.util.get_string('courseactivitystatus-completions', 'local_edwiserreports'),
                            data: response.completions,
                        }];
                        data.xaxis.categories = response.labels;
                        data.chart.toolbar.show = response.labels.length > 30;
                        data.chart.zoom.enabled = response.labels.length > 30;
                        data.tooltip.y.title.formatter = null;
                        renderGraph($(SELECTOR.PANEL).find(SELECTOR.COURSEACTIVITYSTATUS).find(SELECTOR.GRAPH), data);
                    }).fail(function(exception) {
                        Common.loader.hide('#studentengagementblock .' + target);
                    });
                break;
            default:
                Common.loader.hide('#studentengagementblock .' + target);
                break;
        }
    }

    /**
     * After Select Custom date get active users details.
     * @param {String} target Targeted graph
     */
    function customDateSelected(target) {
        let date = $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEPICKERINPUT).val(); // Y-m-d format
        let dateAlternate = $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEPICKERINPUT).next().val(); // d/m/Y format

        /* If correct date is not selected then return false */
        if (!dateAlternate.includes("to")) {
            flatpickr[target].clear();
            return;
        }

        // Set active class to custom date selector item.
        $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEITEM).removeClass('active');
        $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEITEM + '.custom').addClass('active');

        // Show custom date to dropdown button.
        $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATE).html(dateAlternate);
        filter[target].date = date;
        loadGraph(target);
    }

    /**
     * Initialize event listeners.
     */
    function initEvents() {

        /* Date selector listener */
        $('body').on('click', SELECTOR.DATEITEM + ":not(.custom)", function() {
            let target = $(this).closest(SELECTOR.FILTERS).data('id');
            // Set custom selected item as active.
            $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEITEM).removeClass('active');
            $(this).addClass('active');

            // Show selected item on dropdown button.
            $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATE).html($(this).text());

            // Clear custom date.
            flatpickr[target].clear();

            // Set date.
            filter[target].date = $(this).data('value');

            // Load graph data.
            loadGraph(target);
        });

        // Course selector listener.
        $('body').on('change', `${SELECTOR.PANEL} ${SELECTOR.COURSE}`, function() {
            let target = $(this).closest(SELECTOR.FILTERS).data('id');
            let courseid = parseInt($(this).val());
            filter[target].course = courseid;
            filter[target].student = 0;

            PROMISE.GET_STUDENTS(courseid)
                .done(function(response) {
                    // Destroy student selector select2 instance.
                    $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.STUDENT).select2('destroy');
                    Templates.render('local_edwiserreports/studentengagement/students_filter', { 'students': response })
                        .done(function(html, js) {
                            Templates.replaceNode($(SELECTOR.PANEL).find('.' + target).find(SELECTOR.STUDENT), html, js);

                            // Reinitialize student selector select2 instance.
                            $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.STUDENT).select2();
                        });
                });

            // Load graph data.
            loadGraph(target);
        });

        // Student selector listener.
        $('body').on('change', `${SELECTOR.PANEL} ${SELECTOR.STUDENT}`, function() {
            let target = $(this).closest(SELECTOR.FILTERS).data('id');
            filter[target].student = parseInt($(this).val());

            // Load graph data.
            loadGraph(target);
        });


        // Initialize date selector.
        allGraphs.forEach(function(target) {
            flatpickr[target] = $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEPICKERINPUT).flatpickr({
                mode: 'range',
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                maxDate: "today",
                appendTo: $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEPICKER).get(0),
                onOpen: function() {
                    $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEMENU).addClass('withcalendar');
                },
                onClose: function() {
                    $(SELECTOR.PANEL).find('.' + target).find(SELECTOR.DATEMENU).removeClass('withcalendar');
                    customDateSelected(target);
                }
            });
        });
    }

    /**
     * Initialize
     * @param {function} invalidUser Callback function
     */
    function init(invalidUser) {
        if ($(SELECTOR.PANEL).length == 0) {
            return;
        }
        $(SELECTOR.PANEL).find('.singleselect').select2();
        initEvents();
        allGraphs.forEach(function(target) {
            loadGraph(target);
        });
    }
    return {
        init: init
    };
});
