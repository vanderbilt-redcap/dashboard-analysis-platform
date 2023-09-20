<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/ProjectData.php");

class GraphData
{
    /**
     * Function that returns the graph array from a specific question
     */

    public static function getNormalStudyColGraph($question,$project_id, $study_options,$study,$question_1,$conditionDate,$topScoreMax,$graph){
        if ($study == "rpps_s_q62") {
            $graph[$question][$study][$question_1][6] = array();
            $graph[$question][$study][$question_1][6]['graph_top_score_year'] = array();
            $graph[$question][$study][$question_1][6]['graph_top_score_month'] = array();
            $graph[$question][$study][$question_1][6]['graph_top_score_quarter'] = array();
            $graph[$question][$study][$question_1][6]['years'] = array();
            $graph[$question][$study][$question_1][6]['graph_top_score_year']["totalrecords"] = 0;
            $graph[$question][$study][$question_1][6]['graph_top_score_year']["is5"] = 0;
            $graph[$question][$study][$question_1][6]['graph_top_score_month']["totalrecords"] = 0;
            $graph[$question][$study][$question_1][6]['graph_top_score_month']["is5"] = 0;
            $graph[$question][$study][$question_1][6]['graph_top_score_quarter']["totalrecords"] = 0;
            $graph[$question][$study][$question_1][6]['graph_top_score_quarter']["is5"] = 0;
        }
        foreach ($study_options as $index => $col_title) {
            if(($study == "rpps_s_q62" && $index != 6) || $study != "rpps_s_q62"){
                $graph[$question][$study][$question_1][$index] = array();
                $graph[$question][$study][$question_1][$index]['graph_top_score_year'] = array();
                $graph[$question][$study][$question_1][$index]['graph_top_score_month'] = array();
                $graph[$question][$study][$question_1][$index]['graph_top_score_quarter'] = array();
                $graph[$question][$study][$question_1][$index]['years']= array();
            }
            $condition = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getParamOnType($study,$index,$project_id);
            if($question == 2){
                $graph = self::generateResponseRateGraph($project_id, $question,$question_1,$study, $index, $condition.$conditionDate, $graph);
            }else{
                $RecordSet = \REDCap::getData($project_id, 'array', null, array($question_1,'survey_datetime','record_id'), null, null, false, false, false, $condition.$conditionDate);
                $records = ProjectData::getProjectInfoArray($RecordSet);
                foreach ($records as $record){
                    if($question == 1) {
                        if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($record[$question_1], $topScoreMax, $question_1)) {
                            $graph = self::addGraph($graph,$question,$question_1,$study,$index,$record['survey_datetime']);
                        }
                    }else {
                        if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($record[$question_1]) && ($record[$question_1] != '' || array_key_exists($question_1,$record))) {
                            $graph = self::addGraph($graph,$question,$question_1,$study,$index,$record['survey_datetime']);
                        }
                    }
                }
                $graph = self::calculatePercentageGraph($project_id,$graph,$question,$question_1,$study,$index,$topScoreMax,$condition);
            }
        }
        if ($study == "rpps_s_q62") {
            unset($graph[$question][$study][$question_1][6]["graph_top_score_year"]["totalrecords"]);
            unset($graph[$question][$study][$question_1][6]["graph_top_score_year"]["is5"]);
            unset($graph[$question][$study][$question_1][6]["graph_top_score_month"]["totalrecords"]);
            unset($graph[$question][$study][$question_1][6]["graph_top_score_month"]["is5"]);
            unset($graph[$question][$study][$question_1][6]["graph_top_score_quarter"]["totalrecords"]);
            unset($graph[$question][$study][$question_1][6]["graph_top_score_quarter"]["is5"]);
        }
        return $graph;
    }

    public static function getMissingColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph){
        if($question == 2){
            $graph = self::generateResponseRateGraph($project_id, $question, $question_1, $study,"no", "[" . $study."] = ''".$conditionDate, $graph);
        }else if($question == 1) {
            $RecordSetMissing = \REDCap::getData($project_id, 'array', null, array($study,$question_1,'survey_datetime'), null, null, false, false, false, "[" . $question_1 . "] != ''" . $conditionDate);
            $missingRecords = ProjectData::getProjectInfoArray($RecordSetMissing);
            foreach ($missingRecords as $mrecord) {
                if (($mrecord[$study] == '') || (is_array($mrecord[$study]) && array_count_values($mrecord[$study])[1] === 0)) {
                    if ($question == 1) {
                        if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($mrecord[$question_1], $topScoreMax, $question_1)) {
                            $graph = self::addGraph($graph, $question, $question_1, $study, "no", $mrecord['survey_datetime']);
                        }
                    } else {
                        if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($mrecord[$question_1]) && ($mrecord[$question_1] != '' || array_key_exists($question_1, $mrecord))) {
                            $graph = self::addGraph($graph, $question, $question_1, $study, "no", $mrecord['survey_datetime']);
                        }
                    }
                }
            }
            $graph = self::calculatePercentageGraph($project_id, $graph, $question, $question_1, $study, "no", $topScoreMax, "[" . $study . "] = ''");
        }
        return $graph;
    }

    public static function getTotalColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph){
        if($question == 2){
            $graph = self::generateResponseRateGraph($project_id, $question, $question_1, $study,"total", $conditionDate,  $graph);
        }else if($question == 1){
            $RecordSetOverall = \REDCap::getData($project_id, 'array', null,  array($question_1,'survey_datetime'), null, null, false, false, false, "[".$question_1."] <> ''".$conditionDate);
            $recordsoverall = ProjectData::getProjectInfoArray($RecordSetOverall);
            foreach ($recordsoverall as $recordo){
                if($question == 1){
                    if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($recordo[$question_1], $topScoreMax, $question_1)) {
                        $graph = self::addGraph($graph,$question,$question_1,$study,"total",$recordo['survey_datetime']);
                    }
                }else{
                    if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($recordo[$question_1]) && ($recordo[$question_1] != '' || array_key_exists($question_1,$recordo))) {
                        $graph = self::addGraph($graph,$question,$question_1,$study,"total",$recordo['survey_datetime']);
                    }
                }
            }
            $graph = self::calculatePercentageGraph($project_id,$graph,$question,$question_1,"","total",$topScoreMax,"");
        }

        return $graph;
    }

    public static function getMultipleColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph){
        if($question == 2){
            $graph = self::generateResponseRateGraph($project_id, $question, $question_1, $study, "multiple", $conditionDate, $graph);
        }else {
            $RecordSetMultiple = \REDCap::getData($project_id, 'array', null,  array($study,$question_1,'survey_datetime'), null, null, false, false, false, $conditionDate);
            $multipleRecords = ProjectData::getProjectInfoArray($RecordSetMultiple);
            foreach ($multipleRecords as $multirecord) {
                if (array_count_values($multirecord[$study])[1] >= 2) {
                    if ($question == 1) {
                        if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($multirecord[$question_1], $topScoreMax, $question_1) && ($multirecord[$question_1] != '' || array_key_exists($question_1, $multirecord))) {
                            $graph = self::addGraph($graph, $question, $question_1, $study, "multiple", $multirecord['survey_datetime']);
                        }
                    } else {
                        if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($multirecord[$question_1]) && ($multirecord[$question_1] != '' || array_key_exists($question_1, $multirecord))) {
                            $graph = self::addGraph($graph, $question, $question_1, $study, "multiple", $multirecord['survey_datetime']);
                        }
                    }
                }
            }
            $graph = self::calculatePercentageGraph($project_id, $graph, $question, $question_1, $study, "multiple", $topScoreMax, "");
        }
        return $graph;
    }

    public static function createQuartersForYear($graph, $question, $question_1, $study,$studyCol, $date){
        $year = date("Y",strtotime($date));
        if(!array_key_exists('graph_top_score_quarter',$graph[$question][$study][$question_1][$studyCol])){
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter'] = array();
        }
        for($i=1; $i<5 ; $i++){
            if(!array_key_exists("Q".$i." ".$year,$graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter'])){
                $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter']["Q".$i." ".$year] = 0;
            }
        }
        return $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter'];
    }

    public static function setQuarter($graph, $question, $question_1, $study,$studyCol, $date){
        $month = date("m",strtotime($date));
        $year = date("Y",strtotime($date));
        if($month <= 3){
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter']["Q1 ".$year] += 1;
        }else if($month > 3 && $month <= 6) {
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter']["Q2 ".$year] += 1;
        }else if($month > 6 && $month <= 9) {
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter']["Q3 ".$year] += 1;
        }else if($month > 9){
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter']["Q4 ".$year] += 1;
        }
        return $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter'];
    }

    public static function addGraph($graph,$question,$question_1,$study,$studyCol,$survey_datetime){
        if($survey_datetime != "") {
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_year'][date("Y", strtotime($survey_datetime))] += 1;
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_month'][strtotime(date("Y-m", strtotime($survey_datetime)))] += 1;
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter'] = self::createQuartersForYear($graph, $question, $question_1, $study, $studyCol, $survey_datetime);
            $graph[$question][$study][$question_1][$studyCol]['graph_top_score_quarter'] = self::setQuarter($graph, $question, $question_1, $study, $studyCol, $survey_datetime);
            $graph[$question][$study][$question_1][$studyCol]['years'][date("Y", strtotime($survey_datetime))] = 0;
            if ($study == "rpps_s_q62" && $studyCol > 1 && $studyCol < 6) {
                $graph[$question][$study][$question_1][6]['graph_top_score_year'][date("Y", strtotime($survey_datetime))] += 1;
                $graph[$question][$study][$question_1][6]['graph_top_score_month'][strtotime(date("Y-m", strtotime($survey_datetime)))] += 1;
                $graph[$question][$study][$question_1][6]['graph_top_score_quarter'] = self::createQuartersForYear($graph, $question, $question_1, $study, $studyCol, $survey_datetime);
                $graph[$question][$study][$question_1][6]['graph_top_score_quarter'] = self::setQuarter($graph, $question, $question_1, $study, $studyCol, $survey_datetime);
                $graph[$question][$study][$question_1][6]['years'][date("Y", strtotime($survey_datetime))] = 0;
            }
        }
        return $graph;
    }

    public static function addGraphResponseRate($num_questions_answered,$question, $total_questions, $index, $graph, $study, $survey_datetime){
        $percent = number_format((float)($num_questions_answered / $total_questions), 2, '.', '');
        if ($percent >= 0.8) {
            $graph = self::addGraph($graph,$question,"complete",$study,$index,$survey_datetime);
        } else if ($percent < 0.8 && $percent >= 0.5) {
            $graph = self::addGraph($graph,$question,"partial",$study,$index,$survey_datetime);
        } else if ($percent < 0.5 && $percent > 0) {
            $graph = self::addGraph($graph,$question,"breakoffs",$study,$index,$survey_datetime);
        }
        if($percent > 0){
            $graph = self::addGraph($graph,$question,"any",$study,$index,$survey_datetime);
        }
        return $graph;
    }

    public static function createPercentage($graph,$project_id,$study,$question,$question_1,$topScoreMax,$colType,$type,$date,$conditionDate){
        $condition = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getParamOnType($study, $colType, $project_id);

        $RecordSetGraph = \REDCap::getData($project_id, 'json', null, 'record_id', null, null, false, false, false, "[".$question_1."] <>'' AND ".$condition . $conditionDate);
        $TotalRecordsGraph = count(json_decode($RecordSetGraph));

        $score_is_5O_overall_missing = 0;
        if($topScoreMax == 5) {
            $RecordSetisScore5Graph = \REDCap::getData($project_id, 'json', null, array($question_1), null, null, false, false, false, "[" . $question_1 . "] = '5'" . $conditionDate);
            $score_is_5O_overall_missing = count(json_decode($RecordSetisScore5Graph));
        }

        if($study == "rpps_s_q62"){
            if($colType >1 && $colType < 6) {
                $graph[$question][$study][$question_1][6][$type]["totalrecords"] += $TotalRecordsGraph;
                $graph[$question][$study][$question_1][6][$type]["is5"] += $score_is_5O_overall_missing;
            }
        }

        if(($TotalRecordsGraph - $score_is_5O_overall_missing) == 0){
            $percent = 0;
        }else {
            $percent = number_format(($graph[$question][$study][$question_1][$colType][$type][$date] / ($TotalRecordsGraph - $score_is_5O_overall_missing) * 100), 0);
        }
        if($study == "rpps_s_q62" && $colType == 6) {
            $percent = number_format(($graph[$question][$study][$question_1][$colType][$type][$date] / ($graph[$question][$study][$question_1][6][$type]["totalrecords"] - $graph[$question_1][6][$type]["is5"]) * 100), 0);
        }
        if($percent == "nan"){
            $percent = 0;
        }
        $graph[$question][$study][$question_1][$colType][$type][$date] = $percent;

        return $graph;
    }

    public static function calculatePercentageGraph($project_id,$graph,$question,$question_1,$study,$colType,$topScoreMax,$condition){
        if($condition != ""){
            $condition = " AND ".$condition;
        }

        #MONTH
        foreach ($graph[$question][$study][$question_1][$colType]['graph_top_score_month'] as $date => $value) {
            $month = date("Y-m",$date);
            $conditionDate = " AND (contains([survey_datetime], \"" . $month . "\"))";
            $graph = self::createPercentage($graph,$project_id,$study,$question,$question_1,$topScoreMax,$colType,'graph_top_score_month',$date,$conditionDate);
        }

        foreach ($graph[$question][$study][$question_1][$colType]['years'] as $year => $count){
            #QUARTERS
            #Q1
            $conditionDate1 = " AND [survey_datetime] >= '".$year."-01-01". "' AND [survey_datetime] < '".$year."-04-01". "'";
            #Q2
            $conditionDate2 = " AND [survey_datetime] >= '".$year."-04-01". "' AND [survey_datetime] < '".$year."-07-01". "'";
            #Q3
            $conditionDate3 = " AND [survey_datetime] >= '".$year."-07-01". "' AND [survey_datetime] < '".$year."-10-01". "'";
            #Q4
            $conditionDate4 = " AND [survey_datetime] >= '".$year."-10-01"."'";

            for($quarter = 1; $quarter < 5; $quarter++) {
                $graph = self::createPercentage($graph,$project_id,$study,$question,$question_1,$topScoreMax,$colType,'graph_top_score_quarter',"Q".$quarter." ".$year,${"conditionDate".$quarter});
            }

            #YEAR
            $conditionDate = " AND (contains([survey_datetime], \"".$year."\"))";
            $graph = self::createPercentage($graph,$project_id,$study,$question,$question_1,$topScoreMax,$colType,'graph_top_score_year',$year,$conditionDate);
        }

        return $graph;
    }

    public static function calculatePercentageResponseRate($graph,$question, $question_1, $study,$total_records,$index){
        $row_questions_2 = ProjectData::getRowQuestionsResponseRate();
        foreach ($row_questions_2 as $question_2) {
            foreach ($graph[$question][$study][$question_2][$index] as $type=>$graphp){
                foreach ($graphp as $date=>$topscore) {
                    $percent = number_format(($graph[$question][$study][$question_2][$index][$type][$date] / $total_records * 100), 0);
                    $graph[$question][$study][$question_2][$index][$type][$date] = $percent;
                }
            }
        }
        return $graph;
    }

    public static function generateResponseRateGraph($project_id, $question, $question_1, $study, $type, $condition, $graph){
        $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
        $total_questions = count($row_questions_1);
        $data = $row_questions_1;
        array_push($data,$study);
        array_push($data,$question_1);
        array_push($data,"survey_datetime");
        $RecordSet = \REDCap::getData($project_id, 'array', null, $data, null, null, false, false, false, $condition);
        $allRecords = ProjectData::getProjectInfoArray($RecordSet);
        $graph[$question][$study][$question_1]["total_records"][$type] = count($RecordSet);
        foreach ($allRecords as $record) {
            if ($type != "multiple" || ($type == "multiple" && array_count_values($record[$study])[1] >= 2)) {
                $num_questions_answered = 0;
                foreach ($row_questions_1 as $indexQuestion => $question_2) {
                    if ($record[$question_2] != "") {
                        $num_questions_answered++;
                    }
                }
                $graph = self::addGraphResponseRate($num_questions_answered, $question, $total_questions, $type, $graph, $study, $record['survey_datetime']);
            }
        }
        $graph = self::calculatePercentageResponseRate($graph,$question, $question_1, $study,count(ProjectData::getProjectInfoArray($RecordSet)), $type);
        return $graph;
    }

    public static function graphArrays($graph,$question,$study,$study_options){
        if($study_options != null) {
            $study_options_total = $study_options;
            $study_options_total["no"] = "no";
            $study_options_total["multiple"] = "multiple";
        }
        $study_options_total["total"] = "total";
        foreach ($graph[$question][$study] as $question_1=>$single_graph){
            foreach ($study_options_total as $index => $col_title) {
                if($graph[$question][$study][$question_1][$index] != null) {
                    #YEAR
                    ksort($graph[$question][$study][$question_1][$index]['graph_top_score_year']);
                    $graph_top_score_year_values[$question][$study][$question_1][$index] = array();
                    $labels_year[$question_1][$index] = array_keys($graph[$question][$study][$question_1][$index]['graph_top_score_year']);
                    $graph_top_score_year_values[$question_1][$index] = array_values($graph[$question][$study][$question_1][$index]['graph_top_score_year']);

                    #MONTH
                    ksort($graph[$question][$study][$question_1][$index]['graph_top_score_month']);
                    $labels_month[$question_1][$index] = array();
                    $graph_top_score_month_values[$question_1][$index] = array();
                    foreach ($labels_year[$question_1][$index] as $year) {
                        for ($month = 1; $month < 13; $month++) {
                            $found = false;
                            foreach ($graph[$question][$study][$question_1][$index]['graph_top_score_month'] as $date => $value) {
                                if ($year . "-" . sprintf('%02d', $month) == date("Y-m", $date)) {
                                    $found = true;
                                    array_push($labels_month[$question_1][$index], date("Y-m", $date));
                                    array_push($graph_top_score_month_values[$question_1][$index], $value);
                                }
                            }
                            if (!$found) {
                                array_push($labels_month[$question_1][$index], $year . "-" . sprintf('%02d', $month));
                                array_push($graph_top_score_month_values[$question_1][$index], null);
                            }
                        }
                    }

                    #QUARTER
                    ksort($graph[$question][$study][$question_1][$index]['years']);
                    $graph_top_score_quarter_values[$question_1][$index] = array();
                    $labels_quarter[$question_1][$index] = array();
                    $position = 0;
                    foreach ($graph[$question][$study][$question_1][$index]['years'] as $year => $value) {
                        array_push($labels_quarter[$question_1][$index], "Q1 " . $year);
                        array_push($labels_quarter[$question_1][$index], "Q2 " . $year);
                        array_push($labels_quarter[$question_1][$index], "Q3 " . $year);
                        array_push($labels_quarter[$question_1][$index], "Q4 " . $year);

                        array_push($graph_top_score_quarter_values[$question_1][$index], 0);
                        array_push($graph_top_score_quarter_values[$question_1][$index], 0);
                        array_push($graph_top_score_quarter_values[$question_1][$index], 0);
                        array_push($graph_top_score_quarter_values[$question_1][$index], 0);

                        foreach ($graph[$question][$study][$question_1][$index]['graph_top_score_quarter'] as $date => $value) {
                            $quarter = explode(" ", $date)[0];
                            $year_quarter = explode(" ", $date)[1];
                            if ($year == $year_quarter) {
                                $graph_top_score_quarter_values[$question_1][$index][$position] = $value;
                                $position++;
                            }
                        }
                    }
                }
            }
        }
        $labels = array("month" => $labels_month,"quarter" => $labels_quarter,"year" => $labels_year);
        $results = array("month" => $graph_top_score_month_values,"quarter" => $graph_top_score_quarter_values,"year" => $graph_top_score_year_values);
        return array("labels" => $labels,"results" => $results);
    }
}
?>