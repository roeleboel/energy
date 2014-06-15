<?php

require '/var/non-public-part-websites/predis/autoload.php';
require 'config.php';
srand();

$single_server = array(
    'host' => '127.0.0.1',
    'port' => 6379,
    'database' => 13
);

$client = new Predis\Client($single_server);

// gather data from redis
$retval = $client->keys('measurements*');


function saveAllToDatabase($database_connection, $datetime, $generated_energy, $generated_power, $consumed_energy, $consumed_power)
{
    $returnvalue = null;
    $stmt = $database_connection->prepare("INSERT IGNORE INTO energy(date, generated_energy, generated_power, consumed_energy, consumed_power) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sdddd", $datetime->format('Y-m-d H:i:s'), $generated_energy, $generated_power, $consumed_energy, $consumed_power);
    $returnvalue = $stmt->execute();

    // insert into || update per_diem
    $date = $datetime->format('Y-m-d');
    $per_diem_info = findIdInPerDiem($database_connection, $date);
    $standby = $consumed_power;

    if (is_array($per_diem_info)) {
        $id = $per_diem_info[0];
        $standby = $per_diem_info[1];
        updatePerDiem($database_connection, $consumed_energy, $generated_energy, $standby, $id);
    } else {
//        $fulldate = DateTime::createFromFormat('Y-m-d', $date);
        $year = $datetime->format('Y');
        $month = $datetime->format('n');
        $weeknumber = determineWeekNumber($date);
        $updatestmt = $database_connection->prepare("INSERT INTO per_diem (date,year,month,weeknumber,total_consumption,total_generation,standby_usage) VALUES (?,?,?,?,?,?,?)");
        $updatestmt->bind_param("siiiiii", $date, $year, $month, $weeknumber, $consumed_energy, $generated_energy, $standby);
        if (!$updatestmt->execute()) {
            echo 'oeps -> ' . $datetime . '<br>';
            print_r($updatestmt->errorInfo());
        }
    }

}

function findIdInPerDiem($mysqli, $date)
{
    $to_return = null;
    $result = $mysqli->query("SELECT id, standby_usage FROM per_diem WHERE date = '" . $date . "'");
    while ($row = $result->fetch_assoc()) {
        $to_return = array($row['id'], $row['standby_usage']);
    }
    return $to_return;
}

function updatePerDiem($mysqli, $consumed_energy, $generated_energy, $standby_usage, $id)
{
    // insert into per_diem WORKS
//    $date=0; $year=0;$month=0;$weeknumber=0;$cons=0;$gen=0;$standby=0;
    $result = $mysqli->query("SELECT * FROM per_diem WHERE id = " . $id);
    $updatestmt = $mysqli->prepare("UPDATE per_diem SET total_consumption =?,total_generation =?, standby_usage =? WHERE id=?");

    if ($row = $result->fetch_assoc()) {
        $generated_energy = $generated_energy > $row['total_generation'] ? $generated_energy : $row['total_generation'];
        $consumed_energy = $consumed_energy > $row['total_consumption'] ? $consumed_energy : $row['total_consumption'];
        $standby_usage = $standby_usage > $row['standby_usage'] ? $standby_usage : $row['standby_usage'];
        $updatestmt->bind_param("iiii", $consumed_energy, $generated_energy, $standby_usage, $id);
        $updatestmt->execute();
    }
}

if (isset($_REQUEST['v1'])) {
    $input = array();
    $input['energy_generation'] = $_REQUEST['v1'];
    $input['power_generation'] = $_REQUEST['v2'];
    $input['energy_consumption'] = $_REQUEST['v3'];
    $input['power_consumption'] = $_REQUEST['v4'];
    $input['date'] = $_REQUEST['d'];
    $input['time'] = $_REQUEST['t'];

    $key = "measurements:" . $input['date'] . '_' . $input['time'];
    $kv = array($key => json_encode($input));


    $client->mset($kv);
} else {

    /* Create a new mysqli object with database connection parameters */
    $mysqli = new mysqli($dbserver, $user, $pass, $database);

    if (mysqli_connect_errno()) {
        echo "Connection Failed: " . mysqli_connect_errno();
        exit();
    }
    $retval = $client->keys('measurements*');

    if (!empty($retval)) {
        // Create a prepared statement
        // get redis data to vars
        foreach ($retval as $singlekey) {

            $singlevalue = $client->mget($singlekey);
//                print_r($singlekey . " gives " . $singlevalue[0]);
//                print("<br>");

            $object = json_decode($singlevalue[0]);

            $date = $object->date;
            $time = $object->time;
            $generated_energy = $object->energy_generation;
            $generated_power = $object->power_generation;
            $consumed_energy = $object->energy_consumption;
            $consumed_power = $object->power_consumption;

            $datetime = DateTime::createFromFormat("Ymd-H:i", $date . "-" . $time);


            // insert all into db
            saveAllToDatabase($mysqli, $datetime, $generated_energy, $generated_power, $consumed_energy, $consumed_power);
        }
        // close db connection
        $mysqli->close();
    }
}