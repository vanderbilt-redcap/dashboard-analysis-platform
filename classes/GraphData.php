<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/ProjectData.php");

class GraphData
{
    /**
     * Function that returns the graph array from a specific question
     */
    public static function getNormalStudyColGraph($question,$project_id, $study_options,$study,$question_1,$conditionDate,$topScoreMax,$graph){
        if($study == 62){
            $graph[$question_1][6] = array();
            $graph[$question_1][6]['graph_top_score_year'] = array();
            $graph[$question_1][6]['graph_top_score_month'] = array();
            $graph[$question_1][6]['graph_top_score_quarter'] = array();
            $graph[$question_1][6]['years']= array();
            $graph[$question_1][6]['graph_top_score_year']["totalrecords"] = 0;
            $graph[$question_1][6]['graph_top_score_year']["is5"] = 0;
            $graph[$question_1][6]['graph_top_score_month']["totalrecords"] = 0;
            $graph[$question_1][6]['graph_top_score_month']["is5"] = 0;
            $graph[$question_1][6]['graph_top_score_quarter']["totalrecords"] = 0;
            $graph[$question_1][6]['graph_top_score_quarter']["is5"] = 0;
        }
        foreach ($study_options as $index => $col_title) {
            if($study == 62 && $index != 6 || $study != 62){
                $graph[$question_1][$index] = array();
                $graph[$question_1][$index]['graph_top_score_year'] = array();
                $graph[$question_1][$index]['graph_top_score_month'] = array();
                $graph[$question_1][$index]['graph_top_score_quarter'] = array();
                $graph[$question_1][$index]['years']= array();
            }
            $condition = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getParamOnType("rpps_s_q" . $study,$index);

            $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $condition.$conditionDate);
            $records = ProjectData::getProjectInfoArray($RecordSet);
            foreach ($records as $record){
                if($question == 1) {
                    if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($record[$question_1], $topScoreMax, $question_1)) {
                        $graph = self::addGraph($graph,$question_1,$study,$index,$record['survey_datetime']);
                    }
                }else{
                    if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($record[$question_1]) && ($record[$question_1] != '' || array_key_exists($question_1,$record))) {
                        $graph = self::addGraph($graph,$question_1,$study,$index,$record['survey_datetime']);
                    }
                }
            }

            $graph = self::calculatePercentageGraph($project_id,$graph,$question_1,$study,$index,$topScoreMax,$condition);
        }
        if($study == 62) {
            unset($graph[$question_1][6]["graph_top_score_year"]["totalrecords"]);
            unset($graph[$question_1][6]["graph_top_score_year"]["is5"]);
            unset($graph[$question_1][6]["graph_top_score_month"]["totalrecords"]);
            unset($graph[$question_1][6]["graph_top_score_month"]["is5"]);
            unset($graph[$question_1][6]["graph_top_score_quarter"]["totalrecords"]);
            unset($graph[$question_1][6]["graph_top_score_quarter"]["is5"]);
        }

        return $graph;
    }

    public static function getMissingColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph){
        $RecordSetMissing = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] != ''".$conditionDate);
        $missingRecords = ProjectData::getProjectInfoArray($RecordSetMissing);
        foreach ($missingRecords as $mrecord){
            if (($mrecord["rpps_s_q" . $study] == '') || (is_array($mrecord["rpps_s_q" . $study]) && array_count_values($mrecord["rpps_s_q" . $study])[1] == 0)) {
                if($question == 1){
                    if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($mrecord[$question_1], $topScoreMax, $question_1)) {
                        $graph = self::addGraph($graph,$question_1,$study,"no",$mrecord['survey_datetime']);
                    }
                }else{
                    if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($mrecord[$question_1]) && ($mrecord[$question_1] != '' || array_key_exists($question_1,$mrecord))) {
                        $graph = self::addGraph($graph,$question_1,$study,"no",$mrecord['survey_datetime']);
                    }
                }
            }
        }


        $graph = self::calculatePercentageGraph($project_id,$graph,$question_1,$study,"no",$topScoreMax,"[rpps_s_q" . $study."] = ''");

        return $graph;
    }

    public static function getTotalColGraph($question,$project_id,$question_1,$conditionDate,$topScoreMax,$graph){
        $RecordSetOverall = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] <> ''".$conditionDate);
        $recordsoverall = ProjectData::getProjectInfoArray($RecordSetOverall);
        foreach ($recordsoverall as $recordo){
            if($question == 1){
                if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($recordo[$question_1], $topScoreMax, $question_1)) {
                    $graph = self::addGraph($graph,$question_1,"","total",$recordo['survey_datetime']);
                }
            }else{
                if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($recordo[$question_1]) && ($recordo[$question_1] != '' || array_key_exists($question_1,$recordo))) {
                    $graph = self::addGraph($graph,$question_1,"","total",$recordo['survey_datetime']);
                }
            }
        }
        $graph = self::calculatePercentageGraph($project_id,$graph,$question_1,"","total",$topScoreMax,"");

        return $graph;
    }

    public static function getMultipleColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph){
        $RecordSetMultiple = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $conditionDate);
        $multipleRecords = ProjectData::getProjectInfoArray($RecordSetMultiple);
        foreach ($multipleRecords as $multirecord){
            if(array_count_values($multirecord["rpps_s_q" . $study])[1] >= 2){

                if($question == 1){
                    if (\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScore($multirecord[$question_1], $topScoreMax, $question_1) && ($multirecord[$question_1] != '' || array_key_exists($question_1,$multirecord))) {
                        $graph = self::addGraph($graph,$question_1,$study,"multiple",$multirecord['survey_datetime']);
                    }
                }else{
                    if(\Vanderbilt\DashboardAnalysisPlatformExternalModule\isTopScoreVeryOrSomewhatImportant($multirecord[$question_1]) && ($multirecord[$question_1] != '' || array_key_exists($question_1,$multirecord))) {
                        $graph = self::addGraph($graph,$question_1,$study,"multiple",$multirecord['survey_datetime']);
                    }
                }
            }
        }
        $graph = self::calculatePercentageGraph($project_id,$graph,$question_1,$study,"multiple",$topScoreMax,"");

        return $graph;
    }

    function createQuartersForYear($graph, $question_1, $study, $date){
        $year = date("Y",strtotime($date));
        for($i=1; $i<5 ; $i++){
            if(!array_key_exists("Q".$i." ".$year,$graph[$question_1][$study]['graph_top_score_quarter'])){
                $graph[$question_1][$study]['graph_top_score_quarter']["Q".$i." ".$year] = 0;
            }
        }
        return $graph[$question_1][$study]['graph_top_score_quarter'];
    }

    function setQuarter($graph, $question_1, $study, $date){
        $month = date("m",strtotime($date));
        $year = date("Y",strtotime($date));

        if($month <= 3){
            $graph[$question_1][$study]['graph_top_score_quarter']["Q1 ".$year] += 1;
        }else if($month > 3 && $month <= 6) {
            $graph[$question_1][$study]['graph_top_score_quarter']["Q2 ".$year] += 1;
        }else if($month > 6 && $month <= 9) {
            $graph[$question_1][$study]['graph_top_score_quarter']["Q3 ".$year] += 1;
        }else if($month > 9){
            $graph[$question_1][$study]['graph_top_score_quarter']["Q4 ".$year] += 1;
        }
        return $graph[$question_1][$study]['graph_top_score_quarter'];
    }

    public static function addGraph($graph,$question_1,$study,$studyCol,$survey_datetime){
        $graph[$question_1][$studyCol]['graph_top_score_year'][date("Y", strtotime($survey_datetime))] += 1;
        $graph[$question_1][$studyCol]['graph_top_score_month'][strtotime(date("Y-m", strtotime($survey_datetime)))] += 1;
        $graph[$question_1][$studyCol]['graph_top_score_quarter'] = self::createQuartersForYear($graph,$question_1,$studyCol, $survey_datetime);
        $graph[$question_1][$studyCol]['graph_top_score_quarter'] = self::setQuarter($graph,$question_1,$studyCol, $survey_datetime);
        $graph[$question_1][$studyCol]['years'][date("Y", strtotime($survey_datetime))] = 0;
        if($study == 62 && $studyCol > 1 && $studyCol < 6) {
            $graph[$question_1][6]['graph_top_score_year'][date("Y", strtotime($survey_datetime))] += 1;
            $graph[$question_1][6]['graph_top_score_month'][strtotime(date("Y-m", strtotime($survey_datetime)))] += 1;
            $graph[$question_1][6]['graph_top_score_quarter'] = self::createQuartersForYear($graph,$question_1,$study, $survey_datetime);
            $graph[$question_1][6]['graph_top_score_quarter'] = self::setQuarter($graph,$question_1,$study, $survey_datetime);
            $graph[$question_1][6]['years'][date("Y", strtotime($survey_datetime))] = 0;
        }
        return $graph;
    }

    public static function calculatePercentageGraph($project_id,$graph,$question_1,$study,$colType,$topScoreMax,$condition){
        if($condition != ""){
            $condition = " AND ".$condition;
        }
        foreach ($graph[$question_1][$colType] as $type=>$graphp){
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
                if($type != "years" && $date != "totalrecords" && $date != "is5") {
                    $RecordSetGraph = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] <> ''". $condition . $conditionDate);
                    $TotalRecordsGraph = 0;
                    $score_is_5O_overall_missing = 0;


                    if($colType == "multiple"){
                        foreach (ProjectData::getProjectInfoArray($RecordSetGraph) as $totalR) {
                            if(array_count_values($totalR["rpps_s_q61"])[1] >= 2){
                                $TotalRecordsGraph += 1;
                                if($totalR[$question_1] == "5" && $topScoreMax == 5){
                                    $score_is_5O_overall_missing += 1;
                                }
                            }
                        }
                    }else{
                        if($colType == "no"){
                            $RecordSetMissingGraph = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] != ''" .$conditionDate);
                            $missingRecords = ProjectData::getProjectInfoArray($RecordSetMissingGraph);
                            foreach ($missingRecords as $mrecord) {
                                if (($mrecord["rpps_s_q".$study] == '') || (is_array($mrecord["rpps_s_q".$study]) && array_count_values($mrecord["rpps_s_q".$study])[1] == 0)) {
                                    $TotalRecordsGraph += 1;
                                }
                            }
                        }else{
                            $TotalRecordsGraph = count(ProjectData::getProjectInfoArray($RecordSetGraph));
                        }


                        $RecordSetisScore5Graph = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[" . $question_1 . "] = '5'" . $condition . $conditionDate);
                        $isScore5Graph = ProjectData::getProjectInfoArray($RecordSetisScore5Graph);
                        foreach ($isScore5Graph as $misRecord) {
                            if ($misRecord[$question_1] == 5 && $topScoreMax == 5) {
                                $score_is_5O_overall_missing += 1;
                            }
                        }
                    }
                    if($study == 62){
                        if($colType >1 && $colType < 6) {
                            $TotalRecordsGraph_62 += $TotalRecordsGraph;
                            $graph[$question_1][6][$type]["totalrecords"] += $TotalRecordsGraph;
                            $graph[$question_1][6][$type]["is5"] += $score_is_5O_overall_missing;
                        }
                    }

                    $percent = number_format(($graph[$question_1][$colType][$type][$date] / ($TotalRecordsGraph - $score_is_5O_overall_missing) * 100), 0);
                    if($study == 62 && $colType == 6) {
                        $percent = number_format(($graph[$question_1][$colType][$type][$date] / ($graph[$question_1][6][$type]["totalrecords"] - $graph[$question_1][6][$type]["is5"]) * 100), 0);
                    }
                    $graph[$question_1][$colType][$type][$date] = $percent;
                }
            }
        }
        return $graph;
    }

    public static function graphArrays($graph,$study_options){
        $study_options_total = get_object_vars($study_options);
        $study_options_total["total"] = "total";
        $study_options_total["no"] = "no";
        $study_options_total["multiple"] = "multiple";
        foreach ($graph as $question=>$single_graph){
            foreach ($study_options_total as $index => $col_title) {
                #YEAR
                ksort($graph[$question][$index]['graph_top_score_year']);
                $graph_top_score_year_values[$question][$index] = array();
                $labels_year[$question][$index] = array_keys($graph[$question][$index]['graph_top_score_year']);
                $graph_top_score_year_values[$question][$index] = array_values($graph[$question][$index]['graph_top_score_year']);

                #MONTH
                ksort($graph[$question][$index]['graph_top_score_month']);
                $labels_month[$question][$index] = array();
                $graph_top_score_month_values[$question][$index] = array();
                foreach ($labels_year[$question][$index] as $year) {
                    for ($month = 1; $month < 13; $month++) {
                        $found = false;
                        foreach ($graph[$question][$index]['graph_top_score_month'] as $date => $value) {
                            if($year."-".sprintf('%02d', $month) == date("Y-m", $date)) {
                                $found = true;
                                array_push($labels_month[$question][$index], date("Y-m", $date));
                                array_push($graph_top_score_month_values[$question][$index], $value);
                            }
                        }
                        if(!$found) {
                            array_push($labels_month[$question][$index], $year . "-" . sprintf('%02d', $month));
                            array_push($graph_top_score_month_values[$question][$index], null);
                        }
                    }
                }

                #QUARTER
                ksort($graph[$question][$index]['years']);
                $graph_top_score_quarter_values[$question][$index] = array();
                $labels_quarter[$question][$index] = array();
                foreach ($graph[$question][$index]['years'] as $year => $value){
                    array_push($labels_quarter[$question][$index], "Q1 ".$year);
                    array_push($labels_quarter[$question][$index], "Q2 ".$year);
                    array_push($labels_quarter[$question][$index], "Q3 ".$year);
                    array_push($labels_quarter[$question][$index], "Q4 ".$year);

                    array_push($graph_top_score_quarter_values[$question][$index], 0);
                    array_push($graph_top_score_quarter_values[$question][$index], 0);
                    array_push($graph_top_score_quarter_values[$question][$index], 0);
                    array_push($graph_top_score_quarter_values[$question][$index], 0);

                    foreach ($graph[$question][$index]['graph_top_score_quarter'] as $date => $value) {
                        $quarter = explode(" ",$date)[0];
                        $year_quarter = explode(" ",$date)[1];
                        if($year == $year_quarter){
                            if($quarter == "Q1"){
                                $position = 0;
                            }else if($quarter == "Q2"){
                                $position = 1;
                            }else if($quarter == "Q3"){
                                $position = 2;
                            }else if($quarter == "Q4"){
                                $position = 3;
                            }
                            $graph_top_score_quarter_values[$question][$index][$position] = $value;
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