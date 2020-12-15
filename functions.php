<?php
function isTopScore($value,$topScoreMax){
    if(($topScoreMax == 4 || $topScoreMax == 5) && $value == 4){
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

function lineargradient($ra,$ga,$ba,$rz,$gz,$bz,$iterationnr) {
    $colorindex = array();
    for($iterationc=1; $iterationc<=$iterationnr; $iterationc++) {
        $iterationdiff = $iterationnr-$iterationc;
        $colorindex[] = '#'.
            dechex(intval((($ra*$iterationc)+($rz*$iterationdiff))/$iterationnr)).
            dechex(intval((($ga*$iterationc)+($gz*$iterationdiff))/$iterationnr)).
            dechex(intval((($ba*$iterationc)+($bz*$iterationdiff))/$iterationnr));
    }
    return $colorindex;
}
?>