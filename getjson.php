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

//$mysqli = new mysqli($dbserver, $user, $pass, $database);
$db_connection = new PDO("pgsql:host=$dbserver;port=5432;dbname=$database;user=$user;password=$pass");

$page = 1;
$groupby = 'live';
$do_standby_power = false;
$do_power = false;
$periods = 12;
$end_date = new DateTime();
//$end_date = DateTime::createFromFormat('Y-m-d', '2014-04-14');

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
        $new_dates = setRelevantDates($groupby, $periods, $end_date, $page);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "month":
        $new_dates = setRelevantDates($groupby, $periods, $end_date, $page);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "week":
        $new_dates = setRelevantDates($groupby, $periods, $end_date, $page);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "days":
        $do_standby_power = true;
        $periods = 40;
        $new_dates = setRelevantDates($groupby, $periods, $end_date, $page);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
        break;
    case "live":
    default:
        $groupby = 'live';
        $do_standby_power = true;
        $do_power = true;
        $periods = 1;
        $new_dates = setRelevantDates($groupby, $periods, $end_date, $page);
        $begin_date = $new_dates['begin_date'];
        $end_date = $new_dates['end_date'];
//        echo "$begin_date - $end_date<br>";
}

// mysql variant
//$limit_start = ($page - 1) * $periods;
//$limit_end = $page * $periods;
// postgres variant
$limit_start = ($page - 1) * $periods; // OFFSET
$limit_end = $periods; // LIMIT


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

    // $standby_power
    $result = $db_connection->query("SELECT date, consumed_power FROM energy WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "'  ORDER BY consumed_power ASC LIMIT 1");
    while ($row = $result->fetch()) {
        $date = strtotime($row['date']) * 1000;
        $standby_power = array($date, (float)$row['consumed_power']);
    }
    $masterarray['standby_power'] = $standby_power;

    // max_power_usage
    $result = $db_connection->query("SELECT date, consumed_power FROM energy WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "'  ORDER BY consumed_power DESC LIMIT 1");
    while ($row = $result->fetch()) {
        $date = strtotime($row['date']) * 1000;
        $max_power_usage = array($date, (float)$row['consumed_power']);
    }
    $masterarray['max_power_usage'] = $max_power_usage;

}

if ($groupby == 'live') {
    if ($do_power) {
        $gen_pow_array = array();
        $used_pow_array = array();
    }

    $result = $db_connection->query("SELECT date, generated_energy, generated_power,consumed_energy, consumed_power FROM energy WHERE date >= '" . $begin_date . "' AND date < '" . $end_date . "' ORDER BY date ASC");
    while ($row = $result->fetch()) {
        $date = strtotime($row['date']) * 1000;
        array_push($gen_ener_array, array($date, (float)$row['generated_energy']/1000));
        array_push($used_ener_array, array($date, (float)$row['consumed_energy']/1000));
        if ($do_power) {
            array_push($gen_pow_array, array($date, (float)$row['generated_power']));
            array_push($used_pow_array, array($date, (float)$row['consumed_power']));
        }
    }
    if ($do_power) {
        $max_power_used = 0;
        foreach ($used_pow_array as $val) {
            $max_power_used = max($max_power_used, $val[1]);
        }

        $max_power_generated = 0;
        foreach ($gen_pow_array as $val) {
            $max_power_generated = max($max_power_generated, $val[1]);
        }
        $max_power = max($max_power_used, $max_power_generated);

        $masterarray['generated_power'] = $gen_pow_array;
        $masterarray['used_power'] = $used_pow_array;
        $masterarray['max_power'] = $max_power;
    }
    $result_settings['total_results'] = 1;
} else {
    // first we get query totals
    $sql = '';
    $sqltotal = '';
    if ($groupby == 'days') {
        $sql = "SELECT date, total_generation AS generated, total_consumption AS consumed FROM per_diem  WHERE date > '" . $begin_date . "' AND date <= '" . $end_date . "' ORDER BY date ASC"; //. " OFFSET " . $limit_start . " LIMIT " . $limit_end;;
        $sqltotal = "SELECT date, total_generation AS generated, total_consumption AS consumed FROM per_diem";
    } elseif ($groupby == 'week') {
        $sql = "SELECT min(date) AS date, SUM(total_generation) AS generated,SUM(total_consumption) AS consumed FROM per_diem  WHERE weeknumber > " . $begin_date . " AND weeknumber <= " . $end_date . " GROUP BY weeknumber ORDER BY weeknumber ASC"; //. " OFFSET " . $limit_start . " LIMIT " . $limit_end;
        $sqltotal = "SELECT min(date) AS date, SUM(total_generation) AS generated,SUM(total_consumption) AS consumed FROM per_diem GROUP BY weeknumber";
    } elseif ($groupby == 'month') {
        $sql = "SELECT concat(year, '-', month, '-01') AS date, SUM(total_generation) AS generated,SUM(total_consumption) AS consumed FROM per_diem  WHERE date > '" . $begin_date . "' AND date <= '" . $end_date . "' GROUP BY year,month  ORDER BY year ASC, month ASC"; //. " OFFSET " . $limit_start . " LIMIT " . $limit_end;
        $sqltotal = "SELECT concat(year, '-', month, '-01') AS date, SUM(total_generation) AS generated,SUM(total_consumption) AS consumed FROM per_diem GROUP BY year,month";
    } elseif ($groupby == 'year') {
        $sql = "SELECT concat(year,'-01-01') AS date, SUM(total_generation) AS generated,SUM(total_consumption) AS consumed FROM per_diem  WHERE date > '" . $begin_date . "' AND date <= '" . $end_date . "' GROUP BY year  ORDER BY year ASC"; //. " OFFSET " . $limit_start . " LIMIT " . $limit_end;
        $sqltotal = "SELECT concat(year,'-01-01') AS date, SUM(total_generation) AS generated,SUM(total_consumption) AS consumed FROM per_diem  GROUP BY year";
    }

//    echo $sqltotal."<br>";
    //  get totals
    $result = $db_connection->query($sqltotal);
    $counter = 0;
    foreach ($result as $row) {
        $counter++;
    }
    $result_settings['total_results'] = $counter;

//    $sql = $sqltotal . " OFFSET " . $limit_start . " LIMIT " . $limit_end;
    // get actual results
    $result = $db_connection->query($sql);
    while ($row = $result->fetch()) {
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

