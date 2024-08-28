<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once (dirname(__FILE__)."/classes/ProjectData.php");

$project_id = $_GET['pid'];
$selector = $_REQUEST['selector'];

if($selector == 2){
    $array_study = ProjectData::getArrayStudyQuestion_2();
}else{
    $array_study = ProjectData::getStudyArray();
}

$selector = '<option value="'.ProjectData::NOFILTER_ARRAY_KEY.'" selected>No filter</option>';
$selector .= '<option value="bysite">By site</option>';
foreach ($array_study as $index => $sstudy){
    if(strpos($index, 'header') !== false){
        $number_header = explode('header', strtolower($index));
        if($number_header == "0"){
            $selector .= '</optgroup><optgroup label="'.$sstudy.'">';
        }else{
            $selector .= '<optgroup label="'.$sstudy.'">';
        }
    }else {
        $selected = "";
        if ($index === $_SESSION[$project_id . "_study"]) {
            $selected = "selected";
        }
        $selector .= '<option value="' . $index . '" ' . $selected . '>' . $sstudy . '</option>';
    }
}

echo json_encode($selector);