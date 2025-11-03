<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

require_once(dirname(__FILE__)."/classes/ProjectData.php");

function isTopScore($value,$topScoreMax,$var =""){
    if(($topScoreMax == 4 || $topScoreMax == 5) && $value == 1 && ($var == "rpps_s_q21" || $var == "rpps_s_q25")){
        return true;
    } else if(($topScoreMax == 4 || $topScoreMax == 5) && $value == 4 && ($var != "rpps_s_q21" && $var != "rpps_s_q25")){
        return true;
    } else if($topScoreMax == 11 && ($value == 9 || $value == 10)) {
        return true;
    }
    return false;
}
function returnTopScoresLabels($var,$outcome_labels){
    $topScoreMax = count($outcome_labels);
    $question_popover_content = "";
    if($topScoreMax == 4 || $topScoreMax == 5 || $topScoreMax == 11){
        if($topScoreMax == 5 || $topScoreMax == 4){
            if($var == "rpps_s_q21" || $var == "rpps_s_q25"){
                $question_popover_content .= "Top Score: ".$outcome_labels[1];
            }else{
                $question_popover_content .= "Top Score: ".$outcome_labels[4];
            }
        }else if($topScoreMax == 11){
            $question_popover_content .= "Top box 2: ".$outcome_labels[9]." | ".$outcome_labels[10];
        }
    }
    return $question_popover_content;
}
function isTopScoreVeryOrSomewhatImportant($value){
    if($value == 1 || $value == 2) {
        return true;
    }
    return false;
}

function getParamOnType($field_name,$index,$project_id)
{
    $type = getFieldType($field_name, $project_id);
    if ($type == "checkbox") {
        return "[" . $field_name . "(" . $index . ")] = '1'";
    }
    return "[" . $field_name . "] = '" . $index . "'";
}

function getEthnicityCondition($colTypeMax,$study,$project_id)
{
    $condition = "";
    $type = getFieldType($study, $project_id);
    for($i=2;$i<$colTypeMax;$i++){
        if($type == "checkbox"){
            $condition .= "[" . $study . "(".$i.")] = '1'";
        }else{
            $condition .= "[" . $study . "] = '".$i."'";
        }

        if($i != ($colTypeMax-1)){
            $condition .= " OR ";
        }
    }
    return $condition;
}

function getFieldType($field_name,$project_id)
{
    $Proj = new \Project($project_id);
    // If field is invalid, return false
    if (!isset($Proj->metadata[$field_name])) return false;
    // Array to translate back-end field type to front-end (some are different, e.g. "textarea"=>"notes")
    $fieldTypeTranslator = array('textarea'=>'notes', 'select'=>'dropdown');
    // Get field type
    $fieldType = $Proj->metadata[$field_name]['element_type'];
    // Translate field type, if needed
    if (isset($fieldTypeTranslator[$fieldType])) {
        $fieldType = $fieldTypeTranslator[$fieldType];
    }
    unset ($Proj);
    // Return field type
    return $fieldType;
}

function GetColorFromRedYellowGreenGradient($percentage)
{
    $red = ($percentage > 50 ? 1 - 2 * ($percentage - 50) / 100.0 : 1.0) * 255;
    $green = ($percentage > 50 ? 1.0 : 2 * $percentage / 100.0) * 255;
    $blue = 0.0;
    $hexa = sprintf("#%02x%02x%02x", $red, $green, $blue);
    return $hexa;
}



/**
 * Function that checks if the token is correct or not
 * @param $token
 * @return bool
 */
function isTokenCorrect($token,$pidPeople){
    $people = \REDCap::getData($pidPeople, 'json-array', null,null,null,null,false,false,false,"[token_1] = '".$token."' or [token_2] = '".$token."' or [token_3] = '".$token."' or [token_4] = '".$token."'")[0];
    $numberUsers = 4;
    if(!empty($people)){
        for($i=1;$i<$numberUsers+1;$i++){
            if((strtotime($people['token_expiration_date_'.$i]) > strtotime(date('Y-m-d')) && $people['token_'.$i] == $token)){
                return true;
            }
        }
    }
    return false;
}

function arrayKeyExistsReturnValue($array, $keys) {
    if (!is_array($keys)) {
        return null;
    }

    foreach ($keys as $key) {
        if (is_array($array) && array_key_exists($key, $array)) {
            $array = $array[$key];
        } else {
            return null;
        }
    }
    return $array;
}
?>