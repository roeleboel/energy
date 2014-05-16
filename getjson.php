<?php
/**
 * Created by PhpStorm.
 * User: rpaesen
 * Date: 24/04/14
 * Time: 13:25
 */
error_reporting(E_ALL);
require('config.php');
require('utils.php');

$mysqli = new mysqli($dbserver, $user, $pass, $database);

$page = 1;
$groupby = 'live';
$do_standby_power = false;
$do_power = false;
$periods = 12;
//$startdate = new DateTime();
$end_date = DateTime::createFromFormat('Y-m-d', '2014-04-14');

if (isset($_GET['page'])) {
    $page = (int)$_GET['page'];
}
if (isset($_GET['groupby'])) {
    $groupby = $_GET['groupby'];
}
if (isset($_GET['date'])) {
    $end_date = DateTime::createFromFormat('Y-m-d', $_GET['date']);
}
$end_date = $end_date->format('Y-m-d');
// prepare sql depending on groupby
// which info do we even pass on? -> depending on group by...

switch ($groupby) {
    case "year":
        $new_dates = setRelevantDates($groupby, $periods, $end_date);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "month":
        $new_dates = setRelevantDates($groupby, $periods, $end_date);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "week":
        $new_dates = setRelevantDates($groupby, $periods, $end_date);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "days":
        $do_standby_power = true;
        $periods = 40;
        $new_dates = setRelevantDates($groupby, $periods, $end_date);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "live":
    default:
        $groupby = 'live';
        $do_standby_power = true;
        $do_power = true;
        $periods = 1;
        $new_dates = setRelevantDates($groupby, $periods, $end_date);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
//        echo "$begin_date - $end_date<br>";
}

$limit_start = ($page - 1) * $periods;
$limit_end = $page * $periods;

$masterarray = array();
$gen_ener_array = array();
$used_ener_array = array();

$result_settings = array();
$result_settings['groupby'] = $groupby;
$result_settings['begindate'] = $begin_date;
$result_settings['enddate'] = $end_date;
$result_settings['do_standby_power'] = $do_standby_power;
$result_settings['do_power'] = $do_power;
$result_settings['page'] = $page;
$result_settings['total_results'] = -1;
$result_settings['results_per_page'] = $periods;


if ($do_standby_power) {
    $standby_power = null;

    $result = $mysqli->query("SELECT date, consumed_power FROM energy WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "'  ORDER BY `consumed_power` ASC LIMIT 1");
    while ($row = $result->fetch_assoc()) {
        $date = strtotime($row['date']) * 1000;
        $standby_power = array($date, (float)$row['consumed_power']);
    }
    $masterarray['standby_power'] = $standby_power;
}

if ($groupby == 'live') {
    if ($do_power) {
        $gen_pow_array = array();
        $used_pow_array = array();
    }

    $result = $mysqli->query("SELECT date, generated_energy, generated_power,consumed_energy, consumed_power FROM energy WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "' ORDER BY date ASC");
    while ($row = $result->fetch_assoc()) {
        $date = strtotime($row['date']) * 1000;
        array_push($gen_ener_array, array($date, (float)$row['generated_energy']/1000));
        array_push($used_ener_array, array($date, (float)$row['consumed_energy']/1000));
        if ($do_power) {
            array_push($gen_pow_array, array($date, (float)$row['generated_power']));
            array_push($used_pow_array, array($date, (float)$row['consumed_power']));
        }
    }
    if ($do_power) {
        $masterarray['generated_power'] = $gen_pow_array;
        $masterarray['used_power'] = $used_pow_array;
    }
    $result_settings['total_results'] = 1;
} else {
    // first we get query totals
    $sql = '';
    $sqltotal = '';
    if ($groupby == 'days') {
//        $sqltotal = "SELECT DATE(`date`) date, MAX(`generated_energy`) generated,MAX(`consumed_energy`) consumed FROM energy WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "' GROUP BY date";
        $sqltotal = "SELECT date, `total_generation` generated, `total_consumption` consumed FROM per_diem  WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "'";
    } elseif ($groupby == 'week') {
        $sqltotal = "SELECT min(`date`) date, SUM(`total_generation`) generated,SUM(`total_consumption`) consumed FROM per_diem  WHERE weeknumber >= '" . $begin_date . "' AND weeknumber <= '" . $end_date . "' GROUP BY weeknumber";
    } elseif ($groupby == 'month') {
        $sqltotal = "SELECT concat(year, '-', month, '-01') date, SUM(`total_generation`) generated,SUM(`total_consumption`) consumed FROM per_diem  WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "' GROUP BY year,month";
    } elseif ($groupby == 'year') {
        $sqltotal = "SELECT concat(year,'-01-01') date, SUM(`total_generation`) generated,SUM(`total_consumption`) consumed FROM per_diem  WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "' GROUP BY year";
    }

//    echo $sqltotal."<br>";
    //  get totals
    $result = $mysqli->query($sqltotal);
    $result_settings['total_results'] = mysqli_num_rows($result);

    $sql = $sqltotal . " LIMIT " . $limit_start . ", " . $limit_end;
    // get actual results
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $date = strtotime($row['date']) * 1000;
        array_push($gen_ener_array, array($date, (float)$row['generated']/1000));
        array_push($used_ener_array, array($date, (float)$row['consumed']/1000));
    }
}

$masterarray['settings'] = $result_settings;
$masterarray['generated_energy'] = $gen_ener_array;
$masterarray['used_energy'] = $used_ener_array;
header('Content-type: application/json');
echo json_encode($masterarray);

