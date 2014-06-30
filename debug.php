<?php
/**
 * Created by PhpStorm.
 * User: rpaesen
 * Date: 22/05/14
 * Time: 9:56
 */
include('utils.php');

$end_date = new DateTime();
if (isset($_GET['date'])) {
    $end_date = DateTime::createFromFormat('Y-m-d', $_GET['date']);
}
$end_date = $end_date->format('Y-m-d');

$periods = 40;
$groupby = 'days';

print_r($end_date);

print('<br>');
$new_dates = setRelevantDates($groupby, $periods, $end_date);


print_r($new_dates);