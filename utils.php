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

function setRelevantDates($groupby, $periods, $enddate, $pageoffset)
{
    $result = array();
    $date = DateTime::createFromFormat('Y-m-d', $enddate);
    $begindate_offset = ($pageoffset * $periods);
    $enddate_offset = $periods;
//    if($pageoffset>1){
//        $enddate_offset = ($pageoffset-1) * $periods;
//    }

//    echo $groupby."<br>".$periods."<br>".$enddate."<br>";

    switch ($groupby) {
        case 'year':
            $date->sub(new DateInterval('P' . $begindate_offset . 'Y'));
            $result['begin_date'] = $date->format('Y') . '-01-01';
            $result['end_date'] = DateTime::createFromFormat('Y-m-d', $enddate)->format('Y') . '-12-31';
            break;
        case 'month':
            $result['end_date'] = $date->format('Y-m-t');
            $date->sub(new DateInterval('P' . $begindate_offset . 'M'));
            $result['begin_date'] = $date->format('Y-m') . '-01';
            break;
        case 'week': // special case: here we need the week-number!
            $result['begin_date'] = determineWeekNumber($enddate) - $begindate_offset;
            $result['end_date'] = $result['begin_date'] + $enddate_offset; //determineWeekNumber($enddate);
            break;
        case 'days':
            $date->sub(new DateInterval('P' . $begindate_offset . 'D'));
            $result['begin_date'] = $date->format('Y-m-d');
            $date->add(new DateInterval('P' . $enddate_offset . 'D'));
            $result['end_date'] = $date->format('Y-m-d');
            break;
        case 'live':
        default:
            $date->add(new DateInterval('P1D'));
            $result['begin_date'] = $enddate;
            $result['end_date'] = $date->format('Y-m-d');
    }
    return $result;
}
