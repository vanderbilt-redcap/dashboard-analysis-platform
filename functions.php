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
    $hexa = sprintf("#%02x%02x%02x", $red, $green, $blue);
    return $hexa;
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
            $graph['graph_top_score_quarter']["Q".$i." ".$year] = 0;
        }
    }
    return $graph_top_score_quarter;
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
    foreach ($study_options as $index => $col_title) {
        $condition = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getParamOnType("rpps_s_q" . $study,$index);

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
        if($study == 62) {
            if ($index > 1 && $index < 6) {
                $study_62_array['topscore'] += $topScoreFound;
                $study_62_array['totalcount'] += count($records);
                $study_62_array['responses'] += $responses;
                $study_62_array['missing'] += $missing_InfoLabel;
                $study_62_array['score5'] += $score_is_5;
            } else if ($index == 6) {
                $responses = $study_62_array['responses'];
                $topScore = number_format(($study_62_array['topscore'] / ($study_62_array['responses'] - $study_62_array['score5']) * 100), 0);
                $missing_InfoLabel = $study_62_array['missing'];
                $score_is_5 = $study_62_array['score5'];
            }
        }
        if($responses == 0){
            $percent = "-";
        }else{
            $percent = $topScore;
        }
        if(($responses + $missing_InfoLabel + $score_is_5) < 5){
            $percent = "x";
        }
        $tooltip = $responses." responses, ".$missing_InfoLabel." missing";

        if($question == 1) {
            $tooltipTextArray[$indexQuestion][$index] = $tooltip.", ".$score_is_5 . " not applicable";
            $array_colors[$indexQuestion][$index] = $percent;
        }else{
            $attibute = "";
            $class = "";
            if($study == 62 && $index> 1 && $index < 6){
                $class = "hide";
                $attibute = "etnicity = '1'";
            }
            $table_b .= '<td class="'.$class.'" '.$attibute.'><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$tooltip.'">'.$percent.'</div></td>';
        }
    }
    if($question == 1) {
        $aux = array(0=>$tooltipTextArray,1=>$array_colors,2=>$missingOverall,3=>$max,4=>$index);
    }else{
        $aux = array(0=>$table_b,1=>$index,2=>$missingOverall);
    }

    return $aux;

}

function getMissingCol($question,$project_id, $conditionDate, $multipleRecords,$study,$question_1, $topScoreMax,$indexQuestion,$tooltipTextArray, $array_colors, $index,$max){
    $RecordSetOverall5 = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] = '5' AND [rpps_s_q" . $study."] = ''".$conditionDate);
    $missingRecords = ProjectData::getProjectInfoArray($RecordSetOverall5);
    $score_is_5O_overall = 0;
    foreach($missingRecords as $misRecord){
        if ($topScoreMax == 5) {
            $score_is_5O_overall += 1;
        }
    }

    $RecordSetMissing = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false,
        "[".$question_1."] != ''".$conditionDate
    );
    $missingRecords = ProjectData::getProjectInfoArray($RecordSetMissing);

    $missing = 0;
    $missingTop = 0;
    $missingTopAll = 0;
    foreach ($missingRecords as $mrecord){
        if (($mrecord["rpps_s_q" . $study] == '') || (is_array($mrecord["rpps_s_q" . $study]) && array_count_values($mrecord["rpps_s_q" . $study])[1] == 0)) {
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
    $type = \REDCap::getFieldType("rpps_s_q" . $study);
    foreach ($multipleRecords as $mmrecord){
        if(($mmrecord[$question_1] == '' || !array_key_exists($question_1,$mmrecord)) && ($mmrecord["rpps_s_q" . $study] == '' || !array_key_exists("rpps_s_q" . $study,$mmrecord) || (array_count_values($mmrecord["rpps_s_q" . $study])[1] == 0 && $type == "checkbox"))){
            $missing_col += 1;
        }
    }

    $missingPercent = 0;
    if($missingTop > 0){
        $missingPercent = number_format(($missingTop/($missing-$score_is_5O_overall))*100);
    }

    if($missingPercent > $max){
        $max = $missingPercent;
    }

    if($missing == 0){
        $percent = "-";
    }else{
        $percent = $missingPercent;
    }
    $tooltip = $missing." responses, ".$missing_col." missing";

    if($question == 1) {
        $tooltipTextArray[$indexQuestion][$index+1] = $tooltip.", ".$score_is_5O_overall . " not applicable";
        $array_colors[$indexQuestion][$index+1] = $percent;
        return array(0=>$tooltipTextArray,1=>$array_colors,2=>$missing_col,3=>$max);
    }else{
        return array(0=>$percent,1=>$tooltip,2=>$missing_col);
    }
}

function getTotalCol($question,$project_id,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$missing_col,$missingOverall,$tooltipTextArray,$array_colors,$graph){
    $RecordSetOverall = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] <> ''".$conditionDate);
    $recordsoverall = ProjectData::getProjectInfoArray($RecordSetOverall);
    $topScoreFoundO = 0;
    foreach ($recordsoverall as $recordo){
        if($question == 1){
            if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($recordo[$question_1], $topScoreMax, $question_1)) {
                $topScoreFoundO += 1;
                $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\addGraph($graph,$question_1,$recordo['survey_datetime']);
            }
        }else{
            if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($recordo[$question_1]) && ($recordo[$question_1] != '' || array_key_exists($question_1,$recordo))) {
                $topScoreFoundO += 1;
                $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\addGraph($graph,$question_1,$recordo['survey_datetime']);
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
        $overall = number_format(($topScoreFoundO/(count($recordsoverall)-$score_is_5O_overall_missing)*100),0);
    }

    if(count($recordsoverall) == 0){
        $percent = "-";
    }else{
        $percent = $overall;
    }
    $tooltip = count($recordsoverall) . " responses, " . $missingOverall . " missing";

    $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\calculatePercentageGraph($project_id,$graph,$question_1);

    if($question == 1) {
        $tooltipTextArray[$indexQuestion][0] = $tooltip.", ".$score_is_5O_overall_missing . " not applicable";
        $array_colors[$indexQuestion][0] = $percent;
        return array(0=>$tooltipTextArray,1=>$array_colors,2=>$graph);
    }else{
        return array(0=>$percent,1=>$tooltip,2=>$graph);
    }
}

function addGraph($graph,$question_1,$survey_datetime){
    $graph[$question_1]['graph_top_score_year'][date("Y", strtotime($survey_datetime))] += 1;
    $graph[$question_1]['graph_top_score_month'][strtotime(date("Y-m", strtotime($survey_datetime)))] += 1;
    $graph[$question_1]['graph_top_score_quarter'] = \Vanderbilt\DashboardAnalysisPlatformExternalModule\createQuartersForYear($graph['graph_top_score_quarter'], $survey_datetime);
    $graph[$question_1]['graph_top_score_quarter'] = \Vanderbilt\DashboardAnalysisPlatformExternalModule\setQuarter($graph['graph_top_score_quarter'], $survey_datetime);
    $graph[$question_1]['years'][date("Y", strtotime($survey_datetime))] = 0;
    return $graph;
}

function calculatePercentageGraph($project_id,$graph,$question_1){
    foreach ($graph[$question_1] as $type=>$graphp){
        $percent = 0;
        foreach ($graphp as $date=>$topscore) {
            if($type == 'graph_top_score_month'){
                $month = date("Y-m",$date);
                $conditionDate = " AND (contains([survey_datetime], \"".$month."\"))";
            }else if($type == 'graph_top_score_quarter'){
                $quarter = explode(" ",$date)[0];
                $year = explode(" ",$date)[1];

                if($quarter == "Q1"){
                    $conditionDate = " AND [survey_datetime] >= '".$year."-01-01". "' AND [survey_datetime] < '".$year."-04-01". "'";
                }else if($quarter == "Q2") {
                    $conditionDate = " AND [survey_datetime] >= '".$year."-04-01". "' AND [survey_datetime] < '".$year."-07-01". "'";
                }else if($quarter == "Q3") {
                    $conditionDate = " AND [survey_datetime] >= '".$year."-07-01". "' AND [survey_datetime] < '".$year."-10-01". "'";
                }else if($quarter == "Q4"){
                    $conditionDate = " AND [survey_datetime] >= '".$year."-10-01"."'";
                }
            }else if($type == 'graph_top_score_year'){
                $conditionDate = " AND (contains([survey_datetime], \"".$date."\"))";
            }
            if($type != "years") {
                $RecordSetGraph = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] <> ''".$conditionDate);
                $TotalRecordsGraph = count(ProjectData::getProjectInfoArray($RecordSetGraph));

                $RecordSetisScore5Graph = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[" . $question_1 . "] = '5'" . $conditionDate);
                $isScore5Graph = count(ProjectData::getProjectInfoArray($RecordSetisScore5Graph));
                $score_is_5O_overall_missing = 0;
                foreach ($isScore5Graph as $misRecord) {
                    if ($misRecord[$question_1] == 5 && $isScore5Graph == 5) {
                        $score_is_5O_overall_missing += 1;
                    }
                }

                $percent = number_format(($graph[$question_1][$type][$date] / ($TotalRecordsGraph - $score_is_5O_overall_missing) * 100), 0);
                $graph[$question_1][$type][$date] = $percent;
            }
        }
    }
    return $graph;
}

function getMultipleCol($question, $multipleRecords,$study,$question_1,$topScoreMax,$indexQuestion,$index,$tooltipTextArray, $array_colors){
    $multiple = 0;
    $multipleTop = 0;
    $multiple_not_applicable = 0;
    $multiple_missing = 0;
    foreach ($multipleRecords as $multirecord){
        if(array_count_values($multirecord["rpps_s_q" . $study])[1] >= 2){
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
    }else{
        $percent = $multiplePercent;
    }
    $tooltip = $responses . " responses, " . $multiple_missing . " missing";

    if($question == 1) {
        $tooltipTextArray[$indexQuestion][$index+2] = $tooltip.", ".$multiple_not_applicable . " not applicable";
        $array_colors[$indexQuestion][$index+2] = $percent;
        return array(0=>$tooltipTextArray,1=>$array_colors);
    }else{
        return array(0=>$percent,1=>$tooltip);
    }
}
?>