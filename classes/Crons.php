<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
use Vanderbilt\REDCapDataCore\REDCapCalculations;

require_once (dirname(__FILE__)."/ProjectData.php");
require_once (dirname(__FILE__)."/GraphData.php");
require_once (dirname(__FILE__)."/CronData.php");
require_once (dirname(__FILE__)."/REDCapCalculations.php");
include_once(__DIR__ . "/../functions.php");


class Crons
{
    /**
     * Function that calculates the table values
     * @param $module
     * @param $project_id
     */
    public static function runCacheCron($module,$project_id)
    {
        $filename = "dashboard_cache_file_" . $project_id . ".txt";
        if(!self::doesFileAlreadyExist($module, $project_id, $filename)) {
            self:self::runCacheCronData($module, $project_id, $filename, null);
        }
    }

    /**
     * Function that calculates the table values of the report tabs
     * @param $module
     * @param $project_id
     * @param $report, the report id saved in the EM configuration page
     */
    public static function runCacheReportCron($module, $project_id, $report)
    {
        $custom_report_id = $module->getProjectSetting('custom-report-id',$project_id);
        if(!empty($custom_report_id)) {
            if($report != null){
                $custom_report_id = array(0=>$report);
            }
            foreach ($custom_report_id as $rid) {
                $recordIds = array();
                $filename = "dashboard_cache_file_" . $project_id . "_report_" . $rid . ".txt";
                if(!self::doesFileAlreadyExist($module, $project_id, $filename)) {
                    $q = $module->query("SELECT report_id FROM redcap_reports 
                                    WHERE project_id = ? AND unique_report_name=?",
                        [$project_id, $rid]);
                    $row = $q->fetch_assoc();
                    $reports = \REDCap::getReport($row['report_id']);
                    if (!empty($reports)) {
                        foreach ($reports as $record => $data) {
                            array_push($recordIds, $record);
                        }
                        self:self::runCacheCronData($module, $project_id, $filename, $recordIds);
                    }
                }
            }
        }

    }

    /**
     * Function that runs all the table calculations including the report ones
     * @param $module
     * @param $project_id
     * @param $filename
     * @param $recordIds
     */
    public static function runCacheCronData($module, $project_id, $filename, $recordIds){
        # Increase time to run 3h to avoid the Maximum execution time of 7200 seconds exceeded error
        # !! ONLY do this if the cron is running at night
        $module->increaseProcessingMax(3);

		REDCapCalculations::$recordIdField = $module->getRecordIdField($project_id);
        $multipleRecords = \REDCap::getData($project_id, 'json-array', $recordIds);
        $institutions = ProjectData::getAllInstitutions($multipleRecords);
        $table_data = array();

        #QUESTION = 1 PARTICIPANT PERCEPTION
        $table_data = self::createQuestion_1($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds);
        #QUESTION = 2 RESPONSE/COMPLETION RATES
        $table_data = self::createQuestion_2($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds);
        #QUESTION = 3,4,5 REASONS FOR JOINING/LEAVING/STAYING IN A STUDY
        $table_data = self::createQuestion_3($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds);

        #CREATE & SAVE FILE
        $filereponame = "Dashboard Cache File";
        self::saveRepositoryFile($module, $project_id, $filename, $table_data, $filereponame, "");
    }

    /**
     * Function that calculates the graph values by MONTH/QUARTER/YEAR
     * @param $module
     * @param $project_id
     */
    public static function runGraphCron($module,$project_id)
    {
        $filename = "dashboard_cache_graph_file_" . $project_id . ".txt";
        if(!self::doesFileAlreadyExist($module, $project_id, $filename)) {
            $array_study_1 = ProjectData::getArrayStudyQuestion_1();
            $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
            $array_study_2 = ProjectData::getArrayStudyQuestion_2();
            $row_questions_2 = ProjectData::getRowQuestionsResponseRate();
            $custom_filters = $module->getProjectSetting('custom-filter', $project_id);

            self::runGraphCronData($module, $project_id, $filename, null, $custom_filters, $array_study_1, $row_questions_1, $array_study_2, $row_questions_2);
        }
    }

    /**
     * Function that calculates the graph values by MONTH/QUARTER/YEAR of the report tabs
     * @param $module
     * @param $project_id
     * @param $report, the report id saved in the EM configuration page
     */
    public static function runGraphReportCron($module, $project_id, $report)
    {
        $custom_report_id = $module->getProjectSetting('custom-report-id',$project_id);
        if(!empty($custom_report_id)) {
            if ($report != null) {
                $custom_report_id = array(0 => $report);
            }

            $array_study_1 = ProjectData::getArrayStudyQuestion_1();
            $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
            $array_study_2 = ProjectData::getArrayStudyQuestion_2();
            $row_questions_2 = ProjectData::getRowQuestionsResponseRate();
            $custom_filters = $module->getProjectSetting('custom-filter', $project_id);

            foreach ($custom_report_id as $rid) {
                $recordIds = array();
                $filename = "dashboard_cache_graph_file_" . $project_id . "_report_" . $rid . ".txt";
                if (!self::doesFileAlreadyExist($module, $project_id, $filename)) {
                    $q = $module->query("SELECT report_id FROM redcap_reports 
                                    WHERE project_id = ? AND unique_report_name=?",
                        [$project_id, $rid]);
                    $row = $q->fetch_assoc();
                    $reports = \REDCap::getReport($row['report_id']);
                    if (!empty($reports)) {
                        foreach ($reports as $record => $data) {
                            array_push($recordIds, $record);
                        }
                        self::runGraphCronData($module, $project_id, $filename, $recordIds, $custom_filters, $array_study_1, $row_questions_1, $array_study_2, $row_questions_2);
                    }
                }
            }
        }
    }

    /**
     * Function that runs all the graph calculations including the report ones
     * @param $module
     * @param $project_id
     * @param $filename
     * @param $recordIds
     * @param $custom_filters
     * @param $array_study_1
     * @param $row_questions_1
     * @param $array_study_2
     * @param $row_questions_2
     */
    public static function runGraphCronData($module, $project_id, $filename, $recordIds, $custom_filters, $array_study_1, $row_questions_1, $array_study_2, $row_questions_2){
        # Increase time to run 3h to avoid the Maximum execution time of 7200 seconds exceeded error
        # !! ONLY do this if the cron is running at night
        $module->increaseProcessingMax(3);

        #Create Calculations
        $chartgraph = array();
        $chartgraph = self::createGraphData($module,$project_id,$chartgraph,$custom_filters,$array_study_1,$row_questions_1,$array_study_2,$row_questions_2,$recordIds);

        #CREATE & SAVE FILE
        $filereponame = "Dashboard Cache Graph File";
        self::saveRepositoryFile($module, $project_id, $filename, $chartgraph, $filereponame, "graph");
    }

    /**
     * Function that makes the table calculations for PARTICIPANT PERCEPTION
     * @param $module
     * @param $project_id
     * @param $multipleRecords
     * @param $institutions
     * @param $table_data
     * @param $recordIds
     * @return mixed
     */
    public static function createQuestion_1($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds)
    {
        $question = 1;
        $array_study_1 = ProjectData::getArrayStudyQuestion_1();
        $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();

		$recordIdField = $module->getRecordIdField();
		$allRecords = REDCapCalculations::mapFieldByRecord($multipleRecords,$recordIdField);
		
		$allCompletedSurvey = REDCapCalculations::mapFieldByRecord($multipleRecords,"survey_datetime",[],false);
		$allCompletedData = REDCapCalculations::filterDataByArray($multipleRecords, $allCompletedSurvey);
	
		$allData_array = array();
        $allDataTooltip_array = array();
        $allLabel_array = array();
        $conditionDate = "";
        $max = 100;

        $custom_filters = $module->getProjectSetting('custom-filter', $project_id);

        $count = 1;
        foreach ($custom_filters as $index => $sstudy) {
            if ($count < 11 && $sstudy != "") {
                $array_study_1[$sstudy] = "Custom site value " . $count;
            } else {
                break;
            }
            $count++;
        }
		$showLegend = false;
		$isnofiltercalculated = false;
		$tooltipTextArray = [];
		$tooltipCounts = [];
		$array_colors = [];
		$array_colors_institutions = [];
		$table_b = "";
		
        foreach ($array_study_1 as $study => $label) {
			if($study == "") continue;
			
			$tooltipTextArray[$study] = [];
			$array_colors[$study] = [];
			$tooltipCounts[$study] = [];
			$array_colors_institutions[$study] = [];
			
			$fieldType = getFieldType($study,$project_id);
            $study_options = $module->getChoiceLabels($study, $project_id);
            if ($study == "rpps_s_q62") {
                array_push($study_options, ProjectData::getExtraColumTitle());
            }
			
			if($fieldType != "checkbox") {
				$answeredStudyQuestion = REDCapCalculations::mapFieldByRecord($multipleRecords,$study,[],false);
			}
			else {
				$studyFieldList = [];
				foreach($study_options as $value => $label) {
					$studyFieldList[] = $study."___".$value;
				}
				$answeredStudyQuestion = REDCapCalculations::mapFieldByRecord($multipleRecords,$studyFieldList,[],false);
			}
			
			$emptyStudyQuestion = [];
			foreach($allRecords as $recordId => $isOne) {
				if(!array_key_exists($recordId, $answeredStudyQuestion)) {
					$emptyStudyQuestion[$recordId] = 1;
				}
			}
			
			## Add label for missing the study field
			$study_options["missing"] = "Missing";
			
			$answeredStudyData = REDCapCalculations::filterDataByArray($multipleRecords,$answeredStudyQuestion);
			$emptyStudyData = REDCapCalculations::filterDataByArray($multipleRecords,$emptyStudyQuestion);
			
			$missingBySurveyQuestion = [];
			foreach($study_options as $value => $label) {
				if($value == "" ) continue;
				
				if($value == "missing") {
					$filteredData = $emptyStudyData;
				}
				else {
					if($fieldType == "checkbox") {
						$studyField = $study."___".$value;
						$value = 1;
					}
					else {
						$studyField = $study;
					}
					$recordsInCategory = REDCapCalculations::mapFieldByRecord($answeredStudyData,$studyField,[$value],false);
					
					## Special handling for the category "Are you of Spanish or Hispanic..."
					## with value 6 => "Yes - ALL Spanish/Hispanic/Latino"
					if($study == "rpps_s_62" && $value == 6) {
						$recordsInCategory = REDCapCalculations::mapFieldByRecord($answeredStudyData,$study,["2","3","4","5"],false);
					}
					$filteredData = REDCapCalculations::filterDataByArray($answeredStudyData,$recordsInCategory);
				}
				
				$countThisAnswer = count($filteredData);
				
				foreach($row_questions_1 as $indexQuestion => $question_1) {
					## Get list of records that have data for the row question
					$containsSurveyData = REDCapCalculations::filterDataByField($filteredData,$question_1);
					$containsSurveyCount = count($containsSurveyData);
					
					$missingCount = $countThisAnswer - $containsSurveyCount;
					if(!array_key_exists($indexQuestion,$missingBySurveyQuestion)) {
						$missingBySurveyQuestion[$indexQuestion] = 0;
					}
					$missingBySurveyQuestion[$indexQuestion] += $missingCount;
					
					$doesNotApplyCount = CronData::getDoesNotApplyCount($filteredData,$question_1,$project_id);
					
					$topScoreRecords = CronData::getTopScoreRecords($filteredData,$question_1,$project_id);
					$topScorePercent = CronData::calcScorePercent(count($topScoreRecords),$containsSurveyCount - $doesNotApplyCount);
					
					$showLegend = CronData::getShowLegend($containsSurveyCount, $showLegend);
					
					$tooltip = $containsSurveyCount." responses, ". $missingCount ." missing";
					
					## Only done for question 1 list
					$tooltip .= ", ".$doesNotApplyCount." not applicable";
					
					if(!array_key_exists($indexQuestion,$tooltipTextArray[$study])) {
						$tooltipTextArray[$study][$indexQuestion] = [];
						$array_colors[$study][$indexQuestion] = [];
						$tooltipCounts[$study][$indexQuestion] = [];
					}
					$tooltipCounts[$study][$indexQuestion][$value] = [
						"responses" => $containsSurveyCount,
						"missing" => $missingCount,
						"NA" => $doesNotApplyCount,
						"topScore" => count($topScoreRecords)
					];
					$tooltipTextArray[$study][$indexQuestion][$value] = $tooltip;
					$array_colors[$study][$indexQuestion][$value] = $topScorePercent;
				}
			}
	
			foreach($row_questions_1 as $indexQuestion => $question_1) {
				## Institution totals
				foreach($institutions as $institutionId => $institutionRecords) {
					$institutionData = REDCapCalculations::filterDataByArray($answeredStudyData,$institutionRecords);
					$institutionHasSurvey = REDCapCalculations::mapFieldByRecord($institutionData,$question_1,[],false);
					$doesNotApplyCount = CronData::getDoesNotApplyCount($institutionData,$question_1,$project_id);
			
					$institutionDataCount = count($institutionHasSurvey) - $doesNotApplyCount;
					
					$topScoreRecords = CronData::getTopScoreRecords($institutionData,$question_1,$project_id);
					$topScorePercent = CronData::calcScorePercent(count($topScoreRecords),$institutionDataCount);
					
					$showLegendxTotal = CronData::getShowLegend($institutionDataCount, $showLegendxTotal);
					if(!array_key_exists($institutionId,$array_colors_institutions[$study])) {
						$array_colors_institutions[$study][$institutionId] = [];
					}
					$array_colors_institutions[$study][$institutionId][$indexQuestion] = $topScorePercent;
				}
				
				$responsesTotal = 0;
				$missingTotal = 0;
				$NATotal = 0;
				$topScoreTotal = 0;
				foreach($tooltipCounts[$study][$indexQuestion] as $value => $counts) {
					## Skip the missing value
					if($value != "missing") {
						$missingTotal += $counts["missing"];
					}
					$responsesTotal += $counts["responses"];
					$NATotal += $counts["NA"];
					$topScoreTotal += $counts["topScore"];
				}
				$tooltip = $responsesTotal." responses, ". $missingTotal ." missing";
				
				## Only done for question 1 list
				$tooltip .= ", ".$NATotal." not applicable";
				$tooltipTextArray[$study][$indexQuestion][0] = $tooltip;
				
				$topScorePercent = CronData::calcScorePercent($topScoreTotal,$responsesTotal);
				
				$array_colors[$study][$indexQuestion][0] = $topScorePercent;
			}
		}
		echo "<br /><pre>";
		var_dump($tooltipTextArray);
		echo "</pre><br />";
		echo "<br /><pre>";
		var_dump($array_colors);
		echo "</pre><br />";
		echo "<br /><pre>";
		var_dump($array_colors_institutions);
		echo "</pre><br />";
		die();
		$table_data['data'] = $allData_array;
		$table_data['tooltip'] = $allDataTooltip_array;
		$table_data['legend'] = $allLabel_array;
		return $table_data;
	}
	
	/**
	 * Function that makes the table calculations for RESPONSE/COMPLETION RATES
	 * @param $module
	 * @param $project_id
	 * @param $multipleRecords
	 * @param $institutions
	 * @param $table_data
	 * @param $recordIds
	 * @return mixed
	 */
	public static function createQuestion_2($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds)
	{
		$question = 2;
		$array_study_2 = ProjectData::getArrayStudyQuestion_2();
		$row_questions_2 = ProjectData::getRowQuestionsResponseRate();
		$row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
		
		$allData_array = $table_data['data'];
		$allDataTooltip_array = $table_data['tooltip'];
		$allLabel_array = $table_data['legend'];
		$conditionDate = "";
		$max = 100;
		$graph = array();
		
		#INSTITUTIONS
		$graph = CronData::getTotalStudyInstitutionColRate($project_id, $conditionDate, $row_questions_1, $institutions, $graph, $recordIds);
		foreach ($row_questions_2 as $indexQuestion => $question_2) {
			foreach ($institutions as $institution) {
				$totalInstitution = CronData::getResponseRate($graph["institutions"][$institution][$question_2], $graph["institutions"][$institution]["total_records"]);
				$allData_array[$question]["institutions"][$question_2][$institution][0] = $totalInstitution[0];
			}
		}
		
		foreach ($array_study_2 as $study => $label) {
			$study_options = $module->getChoiceLabels($study, $project_id);
			if ($study == "ethnicity") {
				array_push($study_options, ProjectData::getExtraColumTitle());
			}
			$graph = CronData::getNormalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $study_options, $recordIds);
			$graph = CronData::getMissingStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $multipleRecords);
			$graph = CronData::getTotalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $recordIds);
			if($study == "race"){
				$graph = CronData::getMultipleStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $multipleRecords, $recordIds);
			}
			foreach ($row_questions_2 as $indexQuestion => $question_2) {
				$array_colors = array();
				$tooltipTextArray = array();
				$total = CronData::getResponseRate($graph[$question_2]["total"], $graph["total_records"]["total"]);
				$allData_array[$question]["nofilter"][$question_2][0] = $total[0];
				$allDataTooltip_array[$question]["nofilter"][$question_2][0] = $total[1];
				array_push($array_colors, $total[0]);
				array_push($tooltipTextArray, $total[1]);
				
				#NORMAL
				foreach ($study_options as $index => $col_title) {
					$normal = CronData::getResponseRate($graph[$question_2][$index], $graph["total_records"][$index]);
					array_push($array_colors, $normal[0]);
					array_push($tooltipTextArray, $normal[1]);
				}
				#MISSING
				$missing = CronData::getResponseRate($graph[$question_2]["missing"], $graph["total_records"]["missing"]);
				$array_colors[count($study_options) + 1] = $missing[0];
				$tooltipTextArray[count($study_options) + 1] = $missing[1];
				
				if($study == "race") {
					#MULTIPLE
					$multiple = CronData::getResponseRate($graph[$question_2]["multiple"], $graph["total_records"]["multiple"]);
					$array_colors[count($study_options) + 2] = $multiple[0];
					$tooltipTextArray[count($study_options) + 2] = $multiple[1];
				}
				
				$allData_array[$question][$study][$question_2] = $array_colors;
				$allDataTooltip_array[$question][$study][$question_2] = $tooltipTextArray;
			}
		}
		$table_data['data'] = $allData_array;
		$table_data['tooltip'] = $allDataTooltip_array;
		$table_data['legend'] = $allLabel_array;
		return $table_data;
	}
	
	/**
	 * Function that makes the table calculations for REASONS FOR JOINING/LEAVING/STAYING IN A STUDY
	 * @param $module
	 * @param $project_id
	 * @param $multipleRecords
	 * @param $institutions
	 * @param $table_data
	 * @param $recordIds
	 * @return mixed
	 */
	public static function createQuestion_3($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds){
		$custom_filters = $module->getProjectSetting('custom-filter', $project_id);
		$row_questions = ProjectData::getRowQuestions();
		$array_study_3 = ProjectData::getArrayStudyQuestion_3();
		
		$allData_array = $table_data['data'];
		$allDataTooltip_array = $table_data['tooltip'];
		$allLabel_array = $table_data['legend'];
		$conditionDate = "";
		$max = 100;
		
		$count = 1;
		foreach ($custom_filters as $index => $sstudy) {
			if ($count < 11 && $sstudy != "") {
				$array_study_3[$sstudy] = "Custom site value " . $count;
			} else {
				break;
			}
			$count++;
		}
		foreach ($array_study_3 as $study => $label) {
			$study_options = $module->getChoiceLabels($study, $project_id);
			if ($study == "rpps_s_q62") {
				array_push($study_options, ProjectData::getExtraColumTitle());
			}
			for ($question = 3; $question < 6; $question++) {
				$option = explode("-", $row_questions[$question]);
				$indexQuestion = 0;
				$showLegend = false;
				for ($i = $option[0]; $i < $option[1]; $i++) {
					$array_colors = array();
					$tooltipTextArray = array();
					$missingOverall = 0;
					#NORMAL STUDY
					$normalStudyCol = CronData::getNormalStudyCol($question, $project_id, $study_options, $study, "rpps_s_q" . $i, $conditionDate, "", $indexQuestion, $tooltipTextArray, $array_colors, "", $recordIds);
					$index = $normalStudyCol[1];
					$missingOverall = $normalStudyCol[2];
					$showLegendNormal = $normalStudyCol[5];
					$array_colors = $normalStudyCol[6];
					$tooltipTextArray = $normalStudyCol[7];
					
					#MISSING
					$missingCol = CronData::getMissingCol($question, $project_id, $conditionDate, $multipleRecords, $study, "rpps_s_q" . $i, "", $indexQuestion, $tooltipTextArray, $array_colors, $index, "", $recordIds);
					$missing_col = $missingCol[2];
					$showLegendMissing = $missingCol[3];
					$array_colors = $missingCol[4];
					$tooltipTextArray = $missingCol[5];
					
					#OVERALL MISSING
					$totalCol = CronData::getTotalCol($question, $project_id, "rpps_s_q" . $i, $conditionDate, "", $indexQuestion, $tooltipTextArray, $array_colors, $institutions, $recordIds);
					
					$showLegendTotal = $totalCol[2];
					$array_colors = $totalCol[3];
					$tooltipTextArray = $totalCol[4];
					$allData_array[$question]["nofilter"]["rpps_s_q" . $i] = $totalCol[3];
					$allDataTooltip_array[$question]["nofilter"]["rpps_s_q" . $i] = $totalCol[4];
					$allData_array[$question]["institutions"]["rpps_s_q" . $i] = $totalCol[5];
					
					#MULTIPLE
					if ($study == "rpps_s_q61") {
						$multiple = CronData::getMultipleCol($question, $project_id, $multipleRecords, $study, "rpps_s_q" . $i, "", $indexQuestion, $index, $tooltipTextArray, $array_colors);
						$showLegendMultiple = $multiple[2];
						$array_colors = $multiple[3];
						$tooltipTextArray = $multiple[4];
					}
					$allData_array[$question][$study]["rpps_s_q" . $i] = $array_colors;
					$allDataTooltip_array[$question][$study]["rpps_s_q" . $i] = $tooltipTextArray;
					
					if ($showLegendNormal || $showLegendMissing || $showLegendMultiple || $showLegendTotal) {
						$showLegend = true;
					}
					$indexQuestion++;
				}
				$allLabel_array[$question][$study] = $showLegend;
			}
		}
		$table_data['data'] = $allData_array;
		$table_data['tooltip'] = $allDataTooltip_array;
		$table_data['legend'] = $allLabel_array;
		return $table_data;
	}
	
	/**
	 * Function that creates the graph data
	 * @param $module
	 * @param $project_id
	 * @param $chartgraph
	 * @param $custom_filters
	 * @param $array_study_1
	 * @param $row_questions_1
	 * @param $array_study_2
	 * @param $row_questions_2
	 * @param $recordIds
	 * @return mixed
	 */
	public static function createGraphData($module,$project_id,$chartgraph,$custom_filters,$array_study_1,$row_questions_1,$array_study_2,$row_questions_2,$recordIds){
		$conditionDate = "";
		$graph = array();
		for ($question = 1; $question < 3; $question++) {
			$array_study_number = ${"array_study_" . $question};
			$question_number = ${"row_questions_" . $question};
			
			$count = 1;
			foreach ($custom_filters as $index => $sstudy) {
				if ($count < 11 && $sstudy != "") {
					$array_study_number[$sstudy] = "Custom site value " . $count;
				} else {
					break;
				}
				$count++;
			}
			foreach ($array_study_number as $study => $label) {
				$study_options = $module->getChoiceLabels($study, $project_id);
				if ($study == "ethnicity") {
					array_push($study_options, ProjectData::getExtraColumTitle());
				}
				foreach ($question_number as $indexQuestion => $question_1) {
					$graph[$question][$study][$question_1] = array();
					$graph[$question][$study][$question_1]["total"] = array();
					$graph[$question][$study][$question_1]["total"]['graph_top_score_year'] = array();
					$graph[$question][$study][$question_1]["total"]['graph_top_score_month'] = array();
					$graph[$question][$study][$question_1]["total"]['graph_top_score_quarter'] = array();
					$graph[$question][$study][$question_1]["total"]['years'] = array();
					$graph[$question][$study][$question_1]["no"] = array();
					$graph[$question][$study][$question_1]["no"]['graph_top_score_year'] = array();
					$graph[$question][$study][$question_1]["no"]['graph_top_score_month'] = array();
					$graph[$question][$study][$question_1]["no"]['graph_top_score_quarter'] = array();
					$graph[$question][$study][$question_1]["no"]['years'] = array();
					
					$outcome_labels = $module->getChoiceLabels($question_1, $project_id);
					$topScoreMax = count($outcome_labels);
					$graph = GraphData::getNormalStudyColGraph($question, $project_id, $study_options, $study, $question_1, $conditionDate, $topScoreMax, $graph, $recordIds);
					$topScoreMax = count($outcome_labels);
					$graph = GraphData::getMissingColGraph($question, $project_id, $study, $question_1, $conditionDate, $topScoreMax, $graph, $recordIds);
					$graph = GraphData::getTotalColGraph($question, $project_id, $study, $question_1, $conditionDate, $topScoreMax, $graph, $recordIds);
					if ($study == "rpps_s_q61") {
						$graph = GraphData::getMultipleColGraph($question, $project_id, $study, $question_1, $conditionDate, $topScoreMax, $graph, $recordIds);
					}
				}
				$chartgraph[$question][$study] = GraphData::graphArrays($graph, $question, $study, $study_options);
				$chartgraph[$question]["nofilter"] = GraphData::graphArrays($graph, $question, $study, null);
			}
		}
		return $chartgraph;
	}
	
	/**
	 * Function that saves the JSON file for the different calculations
	 * @param $module
	 * @param $project_id
	 * @param $filename
	 * @param $table_data
	 * @param $filereponame
	 * @param string $type
	 */
	public static function saveRepositoryFile($module, $project_id, $filename, $table_data, $filereponame, $type = ""){
		if (($type == "" && $table_data != "" && $table_data['data'] != "" && $table_data['tooltip'] != "") || ($type == "graph" && !empty($table_data))) {
			#SAVE DATA IN FILE
			#create and save file as a json
			
			#Check if we have a different path than edocs
			$path = ProjectData::getS3Path($module, $project_id);
			$storedName = $path == null ? date("YmdHis") . "_pid" . $project_id . "_" . ProjectData::getRandomIdentifier(6) . ".txt" : $filename;
			$filePath = $path == null ? $module->getSafePath($storedName, APP_PATH_TEMP) : $path . $storedName;
			
			#delete previous file
			unlink($filePath);
			$file = fopen($filePath, "wb");
			fwrite($file, json_encode($table_data, JSON_FORCE_OBJECT));
			fclose($file);
			
			$output = file_get_contents($filePath);
			$filesize = file_put_contents($filePath, $output);
			
			if(empty($path)) {
				//Check if file already Exists and Delete old one
				$q = $module->query("SELECT docs_id FROM redcap_docs WHERE project_id=? AND docs_name=?", [$project_id, $filename]);
				while ($row = db_fetch_assoc($q)) {
					$docsId = $row['docs_id'];
					$q2 = $module->query("SELECT doc_id FROM redcap_docs_to_edocs WHERE docs_id=?", [$docsId]);
					while ($row2 = db_fetch_assoc($q2)) {
						$docId = $row2['doc_id'];
						$module->query("DELETE FROM redcap_edocs_metadata WHERE project_id = ? AND doc_id=?", [$project_id, $docId]);
						$module->query("DELETE FROM redcap_docs_to_edocs WHERE docs_id=?", [$docsId]);
						$module->query("DELETE FROM redcap_docs WHERE project_id = ? AND docs_id=?", [$project_id, $docsId]);
					}
				}
				
				//Save document on DB
				if (\REDCap::versionCompare(REDCAP_VERSION, '13.11.3') >= 0) {
					$docId = \REDCap::storeFile($filePath, $project_id, $filename);
				} else {
					$docId = \REDCap::storeFile($filePath, $project_id);
					$module->query("UPDATE redcap_edocs_metadata SET doc_name = ? WHERE doc_id = ?", [$filename, $docId]);
				}
				#we clean the extra copy
				unlink($filePath);
				
				//Save document in File Repository
				$q = $module->query("INSERT INTO redcap_docs (project_id,docs_date,docs_name,docs_size,docs_type,docs_comment) VALUES(?,?,?,?,?,?)",
					[$project_id, date('Y-m-d'), $filename, $filesize, 'application/octet-stream', $filereponame]);
				$docsId = db_insert_id();
				
				$q = $module->query("INSERT INTO redcap_docs_to_edocs (docs_id,doc_id) VALUES(?,?)", [$docsId, $docId]);
			}
		}
		#clear data
		unset($table_data);
	}
	
	/**
	 * Function that checks if the file has already been generated that day
	 * @param $module
	 * @param $project_id
	 * @param $filename
	 * @return bool
	 */
	public static function doesFileAlreadyExist($module, $project_id, $filename){
		$path = ProjectData::getS3Path($module, $project_id);
		$today = date("Y-m-d");
		$doesfileExist = false;
		if(empty($path)){
			//Check if file already Exists
			$q = $module->query("SELECT docs_id FROM redcap_docs WHERE project_id=? AND docs_name=? AND docs_date=?", [$project_id, $filename, $today]);
			while ($row = db_fetch_assoc($q)) {
				return true;
			}
		}else{
			if($today == date ("Y-m-d", filemtime($path.$filename))){
				return true;
			}
		}
		return $doesfileExist;
	}
}
?>