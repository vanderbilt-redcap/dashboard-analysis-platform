<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$project_id = $_GET['pid'];
$selector = $_REQUEST['selector'];

if($selector == 2){
    $array_study = array(
        "age" => "Age",
        "ethnicity" => "Ethnicity",
        "gender_identity" => "Gender Identity",
        "race" => "Race",
        "sex" => "Sex"
    );
}else{
    $array_study = array(
        "header0" => "About the participants:",
        "rpps_s_q60" => "Age",
        "rpps_s_q59" => "Education",
        "rpps_s_q62" => "Ethnicity",
        "rpps_s_q65" => "Gender",
        "rpps_s_q61" => "Race",
        "rpps_s_q63" => "Sex",
        "header1" => "About the research study:",
        "rpps_s_q58" => "Demands of study",
        "rpps_s_q15" => "Disease/disorder to enroll",
        "details_of_study" => "Informed Consent setting",
        "rpps_s_q16" => "Study Type",
        "header2" => "About the survey fielding:",
        "sampling" => "Sampling approach",
        "timing_of_rpps_administration" => "Timing of RPPS administration"
    );
}

$selector = '<option value="nofilter" selected>No filter</option>';
$selector = '<option value="bysite" selected>By site</option>';
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