<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

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

function getParamOnType($field_name,$index,$project_id){
    $type = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getFieldType($field_name,$project_id);
    if($type == "checkbox"){
        return "[".$field_name."(".$index.")] = '1'";
    }
    return "[".$field_name."] = '".$index."'";
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

function getNormalStudyCol($question,$project_id, $study_options,$study,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$tooltipTextArray,$array_colors,$max){
    $table_b = '';
    $missingOverall = 0;
    $study_62_array = array(
        "topscore" => 0,
        "totalcount" => 0,
        "responses" => 0,
        "missing" => 0,
        "score5" => 0,
    );
    $showLegend = false;
    foreach ($study_options as $index => $col_title) {
        $condition = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getParamOnType($study,$index,$project_id);

        $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $condition.$conditionDate);
        $records = ProjectData::getProjectInfoArray($RecordSet);

        $RecordSetMissing= \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $condition." AND [".$question_1."] = ''".$conditionDate);
        $missing_InfoLabel = count(ProjectData::getProjectInfoArray($RecordSetMissing));

        $topScoreFound = 0;
        $score_is_5 = 0;
        foreach ($records as $record){
            if($question == 1) {
                if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($record[$question_1], $topScoreMax, $question_1)) {
                    $topScoreFound += 1;
                }
                if ($record[$question_1] == 5 && $topScoreMax == 5) {
                    $score_is_5 += 1;
                }
            }else{
                if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($record[$question_1]) && ($record[$question_1] != '' || array_key_exists($question_1,$record))) {
                    $topScoreFound += 1;
                }
            }
        }

        if($topScoreFound > 0){
            $topScore = number_format(($topScoreFound/(count($records)-$score_is_5-$missing_InfoLabel)*100),0);
        }else{
            $topScore = 0;
        }

        if($topScore > $max){
            $max = $topScore;
        }

        $missingOverall += $missing_InfoLabel;
        $responses = count($records) - $missing_InfoLabel;

        #Etnicity Case
        if($study == "rpps_s_q62") {
            if ($index > 1 && $index < 6) {
                $study_62_array['topscore'] += $topScoreFound;
                $study_62_array['totalcount'] += count($records);
                $study_62_array['responses'] += $responses;
                $study_62_array['missing'] += $missing_InfoLabel;
                $study_62_array['score5'] += $score_is_5;
            } else if ($index == 6) {
                $responses = $study_62_array['responses'];
                $topScore = 0;
                if(($study_62_array['responses'] - $study_62_array['score5']) != 0) {
                    $topScore = number_format(($study_62_array['topscore'] / ($study_62_array['responses'] - $study_62_array['score5']) * 100), 0);
                }
                $missing_InfoLabel = $study_62_array['missing'];
                $score_is_5 = $study_62_array['score5'];
            }
        }
        if($responses == 0 || $responses == $score_is_5){
            $percent = "-";
            $showLegend = true;
        }else if(($responses + $missing_InfoLabel + $score_is_5) < 5){
            $percent = "x";
            $showLegend = true;
        }else if(($responses + $missing_InfoLabel + $score_is_5) < 20){
            $percent = $topScore . " *";
            $showLegend = true;
        }else{
            $percent = $topScore;
        }
        $tooltip = $responses." responses, ".$missing_InfoLabel." missing";

        if($question == 1) {
            $tooltipTextArray[$indexQuestion][$index] = $tooltip.", ".$score_is_5 . " not applicable";
            $array_colors[$indexQuestion][$index] = $percent;
        }else{
            if($indexQuestion != ""){
                $array_colors[$indexQuestion][$index] = $percent;
                $tooltipTextArray[$indexQuestion][$index] = $tooltip.", ".$score_is_5 . " not applicable";
            }
            $attibute = "";
            $class = "";
            if($study == "rpps_s_q62" && $index> 1 && $index < 6){
                $class = "hide";
                $attibute = "etnicity = '1'";
            }
            $table_b .= '<td class="'.$class.'" '.$attibute.'><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$tooltip.'">'.$percent.'</div></td>';
        }
    }

    if($question == 1) {
        $aux = array(0=>$tooltipTextArray,1=>$array_colors,2=>$missingOverall,3=>$max,4=>$index,5=>$showLegend);
    }else{
        $aux = array(0=>$table_b,1=>$index,2=>$missingOverall,5=>$showLegend,6=>$array_colors,7=>$tooltipTextArray);
    }

    return $aux;

}

function getMissingCol($question,$project_id, $conditionDate, $multipleRecords,$study,$question_1, $topScoreMax,$indexQuestion,$tooltipTextArray, $array_colors, $index,$max){
    $RecordSetOverall5 = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] = '5'".$conditionDate);
    $missingRecords = ProjectData::getProjectInfoArray($RecordSetOverall5);
    $score_is_5O_overall = 0;
    $showLegendexMissing = false;
    foreach($missingRecords as $misRecord){
        if(is_array($misRecord[$question_1])){
            $value_found = false;
            foreach ($misRecord[$question_1] as $array_checkbox_val){
                if($array_checkbox_val != "0"){
                    $value_found = true;
                }
            }
            if($value_found){
                if ($topScoreMax == 5) {
                    $score_is_5O_overall += 1;
                }
            }
        }else{
            if ($misRecord[$study] == "") {
                $score_is_5O_overall += 1;
            }
        }
    }
    $RecordSetMissing = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] != ''".$conditionDate);
    $missingRecords = ProjectData::getProjectInfoArray($RecordSetMissing);

    $missing = 0;
    $missingTop = 0;
    $missingTopAll = 0;
    foreach ($missingRecords as $mrecord){
        if (($mrecord[$study] == '') || (is_array($mrecord[$study]) && array_count_values($mrecord[$study])[1] == 0)) {
            $missing += 1;
            if($question == 1){
                if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($mrecord[$question_1], $topScoreMax, $question_1)) {
                    $missingTop += 1;
                }
            }else{
                if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($mrecord[$question_1]) && ($mrecord[$question_1] != '' || array_key_exists($question_1,$mrecord))) {
                    $missingTop += 1;
                }
            }
        } else {
            $missingTopAll += 1;
        }
    }

    $missing_col = 0;
    $type = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getFieldType($study,$project_id);
    $Proj = new \Project($project_id);
    $event_id = $Proj->firstEventId;
    foreach ($multipleRecords as $mmrecord){
        if($mmrecord['survey_datetime'] != ""){
            if(($mmrecord[$question_1] == '' || !array_key_exists($question_1,$mmrecord)) && ($mmrecord[$study] == '' || !array_key_exists($study,$mmrecord) || (is_array($mmrecord[$study]) && array_count_values($mmrecord[$study])[1] == 0 && $type == "checkbox"))){
                $missing_col += 1;
            }
        }
    }

    $missingPercent = 0;
    if($missingTop > 0){
        $missingPercent = number_format(($missingTop/($missing-$score_is_5O_overall))*100);
    }

    if($missingPercent > $max){
        $max = $missingPercent;
    }

    if($missing == 0 || $missing == $score_is_5O_overall){
        $percent = "-";
        $showLegendexMissing = true;
    }else if(($missing + $missing_col + $score_is_5O_overall) < 5) {
        $percent = "x";
        $showLegendexMissing = true;
    }else if(($missing + $missing_col + $score_is_5O_overall) < 20){
        $percent = $missingPercent." *";
        $showLegendexMissing = true;
    }else{
        $percent = $missingPercent;
    }
    $tooltip = $missing." responses, ".$missing_col." missing";

    if($question == 1) {
        $tooltipTextArray[$indexQuestion][intval($index)+1] = $tooltip.", ".$score_is_5O_overall . " not applicable";
        $array_colors[$indexQuestion][intval($index)+1] = $percent;
        return array(0=>$tooltipTextArray,1=>$array_colors,2=>$missing_col,3=>$max,5=>$showLegendexMissing);
    }else{
        if($indexQuestion != "") {
            $tooltipTextArray[$indexQuestion][intval($index) + 1] = $tooltip . ", " . $score_is_5O_overall . " not applicable";
            $array_colors[$indexQuestion][intval($index) + 1] = $percent;
        }
        return array(0=>$percent,1=>$tooltip,2=>$missing_col,3=>$showLegendexMissing,4=>$array_colors,5=>$tooltipTextArray);
    }
}

function getTotalCol($question,$project_id,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$missing_col,$missingOverall,$tooltipTextArray,$array_colors){
    $RecordSetOverall = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] <> ''".$conditionDate);
    $recordsoverall = ProjectData::getProjectInfoArray($RecordSetOverall);
    $recordsoverallTotal = count($recordsoverall);
    $topScoreFoundO = 0;
    $showLegendexTotal = false;
    foreach ($recordsoverall as $recordo){
        if($question == "1"){
            if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($recordo[$question_1], $topScoreMax, $question_1)) {
                $topScoreFoundO += 1;
            }
        }else{
            if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($recordo[$question_1]) && ($recordo[$question_1] != '' || array_key_exists($question_1,$recordo))) {
                $topScoreFoundO += 1;
            }
        }
    }

    $RecordSetOverall5Missing = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] = '5'".$conditionDate);
    $missingRecords = ProjectData::getProjectInfoArray($RecordSetOverall5Missing);
    $score_is_5O_overall_missing = 0;
    foreach($missingRecords as $misRecord){
        if ($misRecord[$question_1] == 5 && $topScoreMax == 5) {
            $score_is_5O_overall_missing += 1;
        }
    }

    $missingOverall += $missing_col;
    $overall = 0;
    if($topScoreFoundO > 0){
        $overall = number_format(($topScoreFoundO/($recordsoverallTotal-$score_is_5O_overall_missing)*100),0);
    }

    if($recordsoverallTotal == 0 || $recordsoverallTotal == $score_is_5O_overall_missing){
        $percent = "-";
        $showLegendexTotal = true;
    }else if(($recordsoverallTotal + $missingOverall + $score_is_5O_overall_missing) < 5){
        $percent = "x";
        $showLegendexTotal = true;
    }else if(($recordsoverallTotal + $missingOverall + $score_is_5O_overall_missing) < 20){
        $percent = $overall." *";
        $showLegendexTotal = true;
    }else{
        $percent = $overall;
    }
    $tooltip = $recordsoverallTotal . " responses, " . $missingOverall . " missing";

    if($question == 1) {
        $tooltipTextArray[$indexQuestion][0] = $tooltip.", ".$score_is_5O_overall_missing . " not applicable";
        $array_colors[$indexQuestion][0] = $percent;
        return array(0=>$tooltipTextArray,1=>$array_colors,2=>$showLegendexTotal);
    }else{
        if($indexQuestion != ""){
            $tooltipTextArray[$indexQuestion][0] = $tooltip.", ".$score_is_5O_overall_missing . " not applicable";
            $array_colors[$indexQuestion][0] = $percent;
        }
        return array(0=>$percent,1=>$tooltip,2=>$showLegendexTotal,3=>$array_colors,4=>$tooltipTextArray);
    }
}

function getMultipleCol($question,$project_id,$multipleRecords,$study,$question_1,$topScoreMax,$indexQuestion,$index,$tooltipTextArray,$array_colors){
    $multiple = 0;
    $multipleTop = 0;
    $multiple_not_applicable = 0;
    $multiple_missing = 0;
    $showLegendexMultiple = false;
    foreach ($multipleRecords as $multirecord){
        if(array_count_values($multirecord[$study])[1] >= 2){
            $multiple += 1;
            if($question == 1){
                if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($multirecord[$question_1], $topScoreMax, $question_1) && ($multirecord[$question_1] != '' || array_key_exists($question_1,$multirecord))) {
                    $multipleTop += 1;
                }
                if($multirecord[$question_1] == "5" && $topScoreMax == 5){
                    $multiple_not_applicable += 1;
                }
            }else{
                if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($multirecord[$question_1]) && ($multirecord[$question_1] != '' || array_key_exists($question_1,$multirecord))) {
                    $multipleTop += 1;
                }
            }

            if($multirecord[$question_1] == '' || !array_key_exists($question_1,$multirecord)){
                $multiple_missing += 1;
            }

        }
    }

    $multiplePercent = 0;
    if($multipleTop > 0){
        $multiplePercent = number_format(($multipleTop/($multiple-$multiple_not_applicable))*100);
    }

    $responses = $multiple - $multiple_missing;
    if($responses == 0){
        $percent = "-";
        $showLegendexMultiple = true;
    }else if(($responses + $multiple_missing + $multiple_not_applicable) < 5){
        $percent = "x";
        $showLegendexMultiple = true;
    }else if(($responses + $multiple_missing + $multiple_not_applicable) < 20){
        $percent = $multiplePercent." *";
        $showLegendexMultiple = true;
    }else{
        $percent = $multiplePercent;
    }
    $tooltip = $responses . " responses, " . $multiple_missing . " missing";

    if($question == 1) {
        $tooltipTextArray[$indexQuestion][$index+2] = $tooltip.", ".$multiple_not_applicable . " not applicable";
        $array_colors[$indexQuestion][$index+2] = $percent;
        return array(0=>$tooltipTextArray,1=>$array_colors,2=>$showLegendexMultiple);
    }else{
        if($indexQuestion != ""){
            $tooltipTextArray[$indexQuestion][$index+2] = $tooltip.", ".$multiple_not_applicable . " not applicable";
            $array_colors[$indexQuestion][$index+2] = $percent;
        }
        return array(0=>$percent,1=>$tooltip,2=>$showLegendexMultiple,3=>$array_colors,4=>$tooltipTextArray);
    }
}

function calculateResponseRate($num_questions_answered, $total_questions, $index, $graph){
    $percent = number_format((float)($num_questions_answered / $total_questions), 2, '.', '');
    if ($percent >= 0.8) {
        $graph["complete"][$index]++;
    } else if ($percent < 0.8 && $percent >= 0.5) {
        $graph["partial"][$index]++;
    } else if ($percent < 0.5 && $percent > 0) {
        $graph["breakoffs"][$index]++;
    }
    if($percent > 0){
        $graph["any"][$index]++;
    }
    return $graph;
}

function getNormalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $study_options){
    $graph["any"] = array();
    $graph["complete"] = array();
    $graph["partial"] = array();
    $graph["breakoffs"] = array();
    foreach ($study_options as $index => $col_title) {
        $condition = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getParamOnType($study, $index,$project_id);
        $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $condition.$conditionDate);
        $allRecords = ProjectData::getProjectInfoArray($RecordSet);
        $total_records = count(ProjectData::getProjectInfoArray($RecordSet));
        $total_questions = count($row_questions_1);
        $graph["total_records"][$index] = $total_records;
        foreach ($allRecords as $record) {
            $num_questions_answered = 0;
            foreach ($row_questions_1 as $indexQuestion => $question_1) {
                if ($record[$question_1] != "") {
                    $num_questions_answered++;
                }
            }
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\calculateResponseRate($num_questions_answered, $total_questions, $index, $graph);
        }
    }
    return $graph;
}

function getMissingStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study){
    $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\addZeros($graph, "missing");
    $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[" . $study."] = ''".$conditionDate);
    $allRecords = ProjectData::getProjectInfoArray($RecordSet);
    $total_records = count(ProjectData::getProjectInfoArray($RecordSet));
    $total_questions = count($row_questions_1);
    $graph["total_records"]["missing"] = $total_records;
    foreach ($allRecords as $record) {
        $num_questions_answered = 0;
        foreach ($row_questions_1 as $indexQuestion => $question_1) {
            if ($record[$question_1] != "") {
                $num_questions_answered++;
            }
        }
        $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\calculateResponseRate($num_questions_answered, $total_questions, "missing", $graph);
    }
    return $graph;
}

function getTotalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph){
    $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\addZeros($graph, "total");
    $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $conditionDate);
    $allRecords = ProjectData::getProjectInfoArray($RecordSet);
    $total_records = count(ProjectData::getProjectInfoArray($RecordSet));
    $total_questions = count($row_questions_1);
    $graph["total_records"]["total"] = $total_records;
    foreach ($allRecords as $record) {
        $num_questions_answered = 0;
        foreach ($row_questions_1 as $indexQuestion => $question_1) {
            if ($record[$question_1] != "") {
                $num_questions_answered++;
            }
        }
        $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\calculateResponseRate($num_questions_answered, $total_questions, "total", $graph);
    }
    return $graph;
}

function getMultipleStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study){
    $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\addZeros($graph, "multiple");
    $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $conditionDate);
    $allRecords = ProjectData::getProjectInfoArray($RecordSet);
    $total_records = count(ProjectData::getProjectInfoArray($RecordSet));
    $total_questions = count($row_questions_1);
    $graph["total_records"]["multiple"] = $total_records;
    foreach ($allRecords as $record) {
        if (array_count_values($record[ $study])[1] >= 2) {
            $num_questions_answered = 0;
            foreach ($row_questions_1 as $indexQuestion => $question_1) {
                if ($record[$question_1] != "") {
                    $num_questions_answered++;
                }
            }
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\calculateResponseRate($num_questions_answered, $total_questions, "multiple", $graph);
        }
    }

    return $graph;
}

function addZeros($graph, $index){
    $graph["any"][$index] = 0;
    $graph["complete"][$index] = 0;
    $graph["partial"][$index] = 0;
    $graph["breakoffs"][$index] = 0;
    return $graph;
}

function printResponseRate($questions, $total_records){
    if ($questions == "") {
        $questions = 0;
    }
    if($total_records != 0){
        $percent = number_format((float)($questions / $total_records), 2, '.', '') * 100;
    }else{
        $percent = 0;
    }
    $tooltipTextArray = $questions . " out of " . $total_records . " records";
    $table_row = '<td><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="' . $tooltipTextArray . '">' . $percent . '</td>';
    return $table_row;
}

function getResponseRate($questions, $total_records){
    if ($questions == "") {
        $questions = 0;
    }
    if($total_records != 0){
        $percent = number_format((float)($questions / $total_records), 2, '.', '') * 100;
    }else{
        $percent = 0;
    }
    $tooltipTextArray = $questions . " out of " . $total_records . " records";
     return array(0=>$percent,1=>$tooltipTextArray);
}

function getRandomIdentifier($length = 6) {
    $output = "";
    $startNum = pow(32,5) + 1;
    $endNum = pow(32,6);
    while($length > 0) {

        # Generate a number between 32^5 and 32^6, then convert to a 6 digit string
        $randNum = mt_rand($startNum,$endNum);
        $randAlphaNum = \Vanderbilt\DashboardAnalysisPlatformExternalModule\numberToBase($randNum,32);

        if($length >= 6) {
            $output .= $randAlphaNum;
        }
        else {
            $output .= substr($randAlphaNum,0,$length);
        }
        $length -= 6;
    }

    return $output;
}

function numberToBase($number, $base) {
    $newString = "";
    while($number > 0) {
        $lastDigit = $number % $base;
        $newString = \Vanderbilt\DashboardAnalysisPlatformExternalModule\convertDigit($lastDigit, $base).$newString;
        $number -= $lastDigit;
        $number /= $base;
    }

    return $newString;
}

function convertDigit($number, $base) {
    if($base > 192) {
        chr($number);
    }
    else if($base == 32) {
        $stringArray = "ABCDEFGHJLKMNPQRSTUVWXYZ23456789";

        return substr($stringArray,$number,1);
    }
    else {
        if($number < 192) {
            return chr($number + 32);
        }
        else {
            return "";
        }
    }
}
?>