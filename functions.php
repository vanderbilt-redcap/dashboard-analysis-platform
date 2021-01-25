<?php
namespace FunctionsDAP;

function isTopScore($value,$topScoreMax,$var =""){
    if(($topScoreMax == 4 || $topScoreMax == 5) && $value == 4){
        return true;
    }else if(($topScoreMax == 4 || $topScoreMax == 5) && $value == 1 && ($var == "rpps_s_q21" || $var == "rpps_s_q25")) {
        return true;
    }else if($topScoreMax == 11 && ($value == 9 || $value == 10)) {
        return true;
    }
    return false;
}
function isTopScoreVeryOrSomewhatImportant($value){
    if($value == 1 || $value == 2) {
        return true;
    }
    return false;
}

function getParamOnType($field_name,$index){
    $type = \REDCap::getFieldType($field_name);
    if($type == "checkbox"){
        return "[".$field_name."(".$index.")] = '1'";
    }
    return "[".$field_name."] = '".$index."'";
}

function GetColorFromRedYellowGreenGradient($percentage)
{
    $red = ($percentage > 50 ? 1 - 2 * ($percentage - 50) / 100.0 : 1.0) * 255;
    $green = ($percentage > 50 ? 1.0 : 2 * $percentage / 100.0) * 255;
    $blue = 0.0;
    $result = sprintf("#%02x%02x%02x", $red, $green, $blue);
    return $result;
}

function date_compare($element1, $element2) {
    $datetime1 = strtotime($element1['datetime']);
    $datetime2 = strtotime($element2['datetime']);
    return $datetime1 - $datetime2;
}

function setQuarter($graph_top_score_quarter,$date){
    $month = date("m",strtotime($date));
    $year = date("Y",strtotime($date));

    if($month <= 3){
        $graph_top_score_quarter["Q1 ".$year] += 1;
    }else if($month > 3 && $month <= 6) {
        $graph_top_score_quarter["Q2 ".$year] += 1;
    }else if($month > 6 && $month <= 9) {
       $graph_top_score_quarter["Q3 ".$year] += 1;
    }else if($month > 9){
        $graph_top_score_quarter["Q4 ".$year] += 1;
    }
    return $graph_top_score_quarter;
}

function createQuartersForYear($graph_top_score_quarter, $date){
    $year = date("Y",strtotime($date));
    for($i=1; $i<5 ; $i++){
        if(!array_key_exists("Q".$i." ".$year,$graph_top_score_quarter)){
            $graph_top_score_quarter["Q".$i." ".$year] = 0;
        }
    }
    return $graph_top_score_quarter;
}
?>