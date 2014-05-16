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
$user = "root";
$pass = "";
$dbh = new PDO('mysql:host=localhost;dbname=utilities', $user, $pass);



$stmt = $dbh->prepare("SELECT DATE(`date`) DateOnly, MAX(`generated_energy`) generated_energy,MAX(`consumed_energy`) consumed_energy, MIN(consumed_power) standy_usage FROM energy GROUP BY DateOnly ORDER BY DateOnly ASC");
if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
        set_time_limit(50);

        $fulldate = DateTime::createFromFormat('Y-m-d', $row['DateOnly']);
        $date = $row['DateOnly'];
        $year = $fulldate->format('Y');
        $month = $fulldate->format('n');

        $weeknumber=determineWeekNumber($date);

        $gen =$row['generated_energy'];
        $cons = $row['consumed_energy'];
        $standby=$row['standy_usage'];

        $updatestmt = $dbh->prepare("INSERT INTO per_diem (date,year,month,weeknumber,total_consumption,total_generation,standby_usage) VALUES (?,?,?,?,?,?,?)");
        if (!$updatestmt->execute(array($date,$year,$month,$weeknumber,$cons,$gen,$standby))) {

            echo 'oeps -> '.$row['DateOnly'].'<br>';
            print_r($updatestmt->errorInfo());
            exit();
        }
    }
}
