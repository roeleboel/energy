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

function saveToPerDiem($input, $dbconnection)
{

    // do we insert or update?
    $result = $dbconnection->prepare("SELECT id FROM per_diem WHERE date = ?");
    $result->bind_param("s", $input['date']);
    while ($row = $result->fetch_assoc()) {

        // new insert
        $updatestmt = $dbconnection->prepare("INSERT INTO per_diem (date,year,month,weeknumber,total_consumption,total_generation,standby_usage) VALUES (?,?,?,?,?,?,?)");
        $updatestmt->bind_param("siiiiii", $date, $year, $month, $weeknumber, $cons, $gen, $standby);
        $updatestmt->execute();

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
            if ($stmt = $mysqli->prepare("INSERT IGNORE INTO energy(date, generated_energy, generated_power, consumed_energy, consumed_power) VALUES (?,?,?,?,?)")) {
//            echo "<br>in statement<br>";
                foreach ($retval as $singlekey) {

                    $singlevalue = $client->mget($singlekey);
//                print_r($singlekey . " gives " . $singlevalue[0]);
//                print("<br>");

                    $object = json_decode($singlevalue[0]);

                    $date = $object->date;
                    $time = $object->time;
                    $energy_generation = $object->energy_generation;
                    $power_generation = $object->power_generation;
                    $energy_consumption = $object->energy_consumption;
                    $power_consumption = $object->power_consumption;

                    $datetime = DateTime::createFromFormat("Ymd-H:i", $date . "-" . $time);
//		echo $datetime->format('Y-m-d H:i:s') . "\n";
                    // Bind parameters
                    //   s - string, b - blob, i - int, etc
                    $stmt->bind_param("sdddd", $datetime->format('Y-m-d H:i:s'), $energy_generation, $power_generation, $energy_consumption, $power_consumption);

                    // Execute it
                    if ($stmt->execute()) {
                        $client->del($singlekey);
                    }
                }
                /* Close statement */
                $stmt->close();

            } else {
//            echo "preparing statement failed: " . mysqli_connect_errno();
            }

            /* Close connection */
            $mysqli->close();
        }
    }
}