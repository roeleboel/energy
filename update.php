<?php
/**
 * Created by PhpStorm.
 * User: rpaesen
 * Date: 9/05/14
 * Time: 12:06
 */


function determineWeekNumber($date1)
{
    $first = DateTime::createFromFormat('Y-m-d', '1900-01-01');
    $second = DateTime::createFromFormat('Y-m-d', $date1);
    return abs(floor($first->diff($second)->days/7));
}

error_reporting(E_ALL);
require('config.php');
$mysqli = new mysqli($dbserver, $user, $pass, $database);

// insert into per_diem WORKS
$date = 0;
$year = 0;
$month = 0;
$weeknumber = 0;
$cons = 0;
$gen = 0;
$standby = 0;
$result = $mysqli->query("SELECT DATE(`date`) DateOnly, MAX(`generated_energy`) generated_energy,MAX(`consumed_energy`) consumed_energy, MIN(consumed_power) standy_usage FROM energy GROUP BY DateOnly ORDER BY DateOnly ASC");
$updatestmt = $mysqli->prepare("INSERT INTO per_diem (date,year,month,weeknumber,total_consumption,total_generation,standby_usage) VALUES (?,?,?,?,?,?,?)");
$updatestmt->bind_param("siiiiii", $date, $year, $month, $weeknumber, $cons, $gen, $standby);

while ($row = $result->fetch_assoc()) {
    set_time_limit(50);

    $date = $row['DateOnly'];
    $gen = $row['generated_energy'];
    $cons = $row['consumed_energy'];
    $standby = $row['standy_usage'];

    $id = findIdInPerDiem($mysqli, $date);
    if ($id != null) {
        updatePerDiem($mysqli, $cons, $gen, $standby, $id);
    } else {
        $fulldate = DateTime::createFromFormat('Y-m-d', $date);
        $year = $fulldate->format('Y');
        $month = $fulldate->format('n');
        $weeknumber=determineWeekNumber($date);
        if (!$updatestmt->execute()) {
            echo 'oeps -> '.$row['DateOnly'].'<br>';
            print_r($updatestmt->errorInfo());
        }
    }
}

function findIdInPerDiem($mysqli, $date)
{
    $to_return = null;
    $result = $mysqli->query("SELECT id FROM per_diem WHERE date = '" . $date . "'");
    while ($row = $result->fetch_assoc()) {
        $to_return = $row['id'];
    }
    return $to_return;
}

function updatePerDiem($mysqli, $cons, $gen, $standby, $id)
{
    // insert into per_diem WORKS
//    $date=0; $year=0;$month=0;$weeknumber=0;$cons=0;$gen=0;$standby=0;
    $result = $mysqli->query("SELECT * FROM per_diem WHERE id = " . $id);
    $updatestmt = $mysqli->prepare("UPDATE per_diem SET total_consumption =?,total_generation =?, standby_usage =? WHERE id=?");
    $updatestmt->bind_param("iiii", $cons, $gen, $standby, $id);

    if ($row = $result->fetch_assoc()) {
        $gen = $gen > $row['total_generation'] ? $gen : $row['total_generation'];
        $cons = $cons > $row['total_consumption'] ? $cons : $row['total_consumption'];
        $standby = $standby > $row['standby_usage'] ? $standby : $row['standby_usage'];

        if (!$updatestmt->execute()) {
            echo 'oeps -> ' . $row['DateOnly'] . '<br>';
            print_r($updatestmt->errorInfo());
            exit();
        }
    }
}