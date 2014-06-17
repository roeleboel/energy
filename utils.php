<?php
/**
 * Created by PhpStorm.
 * User: rpaesen
 * Date: 16/05/14
 * Time: 11:52
 */

function determineWeekNumber($date1)
{
    $first = DateTime::createFromFormat('Y-m-d', '1900-01-01');
    $second = DateTime::createFromFormat('Y-m-d', $date1);
    return abs(floor($first->diff($second)->days / 7));
}

function setRelevantDates($groupby, $periods, $enddate)
{
    $result = array();
    $date = DateTime::createFromFormat('Y-m-d', $enddate);
//    $periods--;
//    echo $groupby."<br>".$periods."<br>".$enddate."<br>";

    switch ($groupby) {
        case 'year':
            $date->sub(new DateInterval('P' . $periods . 'Y'));
            $result['begin_date'] = $date->format('Y') . '-01-01';
            $result['end_date'] = DateTime::createFromFormat('Y-m-d', $enddate)->format('Y') . '-12-31';
            break;
        case 'month':
            $result['end_date'] = $date->format('Y-m-t');
            $date->sub(new DateInterval('P' . $periods . 'M'));
            $result['begin_date'] = $date->format('Y-m') . '-01';
            break;
        case 'week': // special case: here we need the week-number!
            $result['begin_date'] = determineWeekNumber($enddate) - $periods;
            $result['end_date'] = determineWeekNumber($enddate);
            break;
        case 'days':
            $date->sub(new DateInterval('P' . $periods . 'D'));
            $result['begin_date'] = $date->format('Y-m-d');
            $result['end_date'] = $enddate;
            break;
        case 'live':
        default:
            $date->add(new DateInterval('P1D'));
            $result['begin_date'] = $enddate;
            $result['end_date'] = $date->format('Y-m-d');
    }
    return $result;
}
