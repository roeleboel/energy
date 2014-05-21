<?php
/**
 * Created by PhpStorm.
 * User: rpaesen
 * Date: 24/04/14
 * Time: 14:32
 */

require('config.php');

$allowed_groupbys = array('live', 'days', 'week', 'month', 'year');
$groupby = 'live';
$no_paging_suffix = '';
$page = 1;
// build suffix for json-url
$jsonurl = 'getjson.php?';
if (isset($_GET['page'])) {
    $jsonurl .= '&page=' . (int)$_GET['page'];
    $page = (int)$_GET['page'];
}
if (isset($_GET['groupby'])) {
    $jsonurl .= '&groupby=' . $_GET['groupby'];
    $groupby = in_array($_GET['groupby'], $allowed_groupbys) ? $_GET['groupby'] : 'live';
    $no_paging_suffix .= '&groupby=' . $_GET['groupby'];
}
if (isset($_GET['date'])) {
    $jsonurl .= '&date=' . $_GET['date'];
    $no_paging_suffix .= '&date=' . $_GET['date'];
}
$no_paging_url = 'index.php?' . $no_paging_suffix;
$no_paging_json_url = 'getjson.php?' . $no_paging_suffix;

?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Energy readings from hg38</title>

<link type="text/css" rel="stylesheet" href="bootstrap.css">
<script type="text/javascript" src="jquery-1.8.2.min.js"></script>
<script src="js/highcharts.js"></script>
<script src="js/modules/exporting.js"></script>
<script type="text/javascript" src="date.format.js"></script>
<script type="text/javascript" src="jquery.easyPaginate.js"></script>
<script>var options = {
        global: {
            useUTC: true,
            timezoneOffset: -2 * 60
        },
        tooltip: {
            shared: true,
            crosshairs: true
        },
        legend: {
            borderWidth: 1,
            borderRadius: 5,
            borderColor: '#aaaaaa'
        },
        yAxis: [
            { // Primary yAxis
                min: 0,
                labels: {
                    format: '{value} kWh'
                },
                title: {
                    text: 'Energy'
                },
                opposite: true
            },
            { // Secondary yAxis
                min: 0,
                title: {
                    text: 'Power'
                },
                labels: {
                    format: '{value} Watt'
                }
            }
        ],
        xAxis: {
            type: 'datetime',
            dateTimeLabelFormats: { // don't display the dummy year
                minute: '%e. %b<br>%H:%M',
                hour: '%e. %b<br>%H:%M',
                day: '%e. %b',
                week: '%e. %b',
                month: '%e. %b',
                year: '%b'
            }
        },
        chart: {
            renderTo: 'container'
        },
        title: {text: 'Energy monitoring of hg38'},
        plotOptions: {
            spline: {
                lineWidth: 2,
                shadow: false,
                states: {
                    hover: {
                        lineWidth: 2
                    }
                },
                marker: {
                    enabled: false,
                    states: {
                        hover: {
                            enabled: true,
                            symbol: 'circle',
                            radius: 4,
                            lineWidth: 1
                        }
                    }
                }
            },
            areaspline: {
                fillOpacity: .60,
                lineWidth: 2,
                shadow: false,
                states: {
                    hover: {
                        lineWidth: 2
                    }
                },
                marker: {
                    enabled: false,
                    states: {
                        hover: {
                            enabled: true,
                            symbol: 'circle',
                            radius: 4,
                            lineWidth: 1
                        }
                    }
                }
            },
            column: {pointPadding: 0.0, borderWidth: 0}
        },
        series: []
    };</script>
<script>
    var page = 1;
    function buildJsonUrl(page) {
        var jsonUrl = '<?php echo $no_paging_json_url; ?>';

        if (typeof page !== "undefined") {
            jsonUrl = jsonUrl + "&page=" + page;
        }
        return jsonUrl;
    }

    function fixData(data_array) {
        console.log("fixing data");
        console.log(new Date().getTimezoneOffset());

        var time_offset_to_apply = (new Date().getTimezoneOffset()) * -1 * 60 * 1000;

        // prepare series arrays
        var used_energy_data_array = [];
        $.each(data_array, function (key, value) {
//            console.log(key);
//            console.log(value);
//            var timestamp = new Date(value[0]);
            var timestamp = value[0] + time_offset_to_apply;
            var energy_value = value[1];
            var element = [timestamp, energy_value];
            console.log(timestamp);
            used_energy_data_array.push(element);
        });
        return used_energy_data_array;
    }

    function updateGraph(page) {

        options.series = [];

        var newJsonUrl = buildJsonUrl(page);
//        console.debug(newJsonUrl);
        $.getJSON(newJsonUrl, function (fulldata) {
//            console.debug("getting json");
            var used_energy_data = fixData(fulldata['used_energy']);
            var generated_energy_data = fixData(fulldata['generated_energy']);
//
//            $.each(val, function(key,val) {
//                var d = val.split(",");
//                var x = Date.UTC(d[0],d[1],d[2]);
//                series.data.push([x,d[3]]);
//            });


            var energy_type = 'column';
            if (fulldata['settings']['groupby'] == 'live') {
                energy_type = 'areaspline'
            }


            options.series.push({
                name: 'used energy',
                data: used_energy_data,
                yAxis: 0,
                type: energy_type,
                tooltip: {
                    valueSuffix: ' kWh'
                },
                marker: {
                    enabled: false,
                    states: {
                        hover: {
                            enabled: true
                        }
                    }
                },
                color: '#ff94a1'
            });
            options.series.push({
                name: 'generated energy',
                data: generated_energy_data,
                yAxis: 0,
                type: energy_type, //'column',
                tooltip: {
                    valueSuffix: ' kWh'
                },
                marker: {
                    enabled: false,
                    states: {
                        hover: {
                            enabled: true
                        }
                    }
                },
                color: '#ccff66'

            });
            if (fulldata['settings']['do_power'] == true) {
                var used_power_data = fixData(fulldata['used_power']);
                var generated_power_data = fixData(fulldata['generated_power']);

                options.series.push({
                    name: 'used power',
                    data: used_power_data,
                    yAxis: 1,
                    type: 'line',
                    tooltip: {
                        valueSuffix: ' Watt'
                    },
                    marker: {
                        enabled: false,
                        states: {
                            hover: {
                                enabled: true
                            }
                        }
                    },
                    color: '#ff5065'
                });
                options.series.push({
                    name: 'generated power',
                    data: generated_power_data,
                    yAxis: 1,
                    type: 'line',
                    tooltip: {
                        valueSuffix: ' Watt'
                    },
                    marker: {
                        enabled: false,
                        states: {
                            hover: {
                                enabled: true
                            }
                        }
                    },
                    color: '#339933'
                });
            }
            var chart = new Highcharts.Chart(options);

            if (fulldata['settings']["do_standby_power"]) {
                // standby usage
                var st = fulldata['standby_power'];
                var standby_power_time = new Date(st[0]);
                var standby_power_usage = st[1];
                $('#standby_usage').replaceWith("<p id='standby_usage'>Standby power: at " + standby_power_time.format("dS mmmm HH:MM") + " we used " + standby_power_usage + " watts</p>");
            }
            if (fulldata['settings']["groupby"] != 'live') {
                // only show pagination if there is something to paginate...
                if (fulldata['settings']['total_results'] > fulldata['settings']['results_per_page']) {
                    $('#pagination').easyPaginate({
                        items: fulldata['settings']['total_results'],
                        itemsOnPage: fulldata['settings']['results_per_page'],
                        edges: 1,
                        sides: 2,
                        currentPage: page,
                        onClickcallback: function (page) {
                            updateGraph(page);
                        },
                        onInit: function () {
                            $("#pagedisp").html("Current Page is " + 1);
                        }
                    });
                }
            }
        });
    }
    $(document).ready(function () {
        updateGraph();
    });
</script>
</head>
<body>

<div id="container" style='padding-top:5px;margin-bottom:4px;width:950px;height:375px'></div>

<div id="pagedisp"></div>
<div id="pagination"></div>

<p id='standby_usage'></p>

<p>
    <?php
    foreach ($allowed_groupbys as $currentGroupby) {
        if ($currentGroupby != $groupby) {
            echo '<a href="index.php?groupby=' . $currentGroupby . '">' . $currentGroupby . '</a> ';
        }
    }
    ?>
</p>
</body>
</html>