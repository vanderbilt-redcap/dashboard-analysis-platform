<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/ProjectData.php");

class CronData
{
    /**
     * Function that calculates the percentages for the filter studies like age, ethnicity,... for PARTICIPANT PERCEPTION
     * @param $question
     * @param $project_id
     * @param $study_options
     * @param $study
     * @param $question_1
     * @param $conditionDate
     * @param $topScoreMax
     * @param $indexQuestion
     * @param $tooltipTextArray
     * @param $array_colors
     * @param $max
     * @param $recordIds
     * @return array
     */
     public static function getNormalStudyCol($question,$project_id, $study_options,$study,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$tooltipTextArray,$array_colors,$max,$recordIds)
    {
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
            if ($study !== "" && $index !== "") {
                $condition = getParamOnType($study, $index, $project_id);

                $total_records = ProjectData::getDataTotalCount($project_id, $recordIds, $condition." ".ProjectData::getCriticalQuestions1LogicForMissing($question_1));
                $missing_InfoLabel = ProjectData::getDataTotalCount($project_id, $recordIds, $condition." AND [" . $question_1 . "] = '' ".ProjectData::getCriticalQuestions1LogicForMissing($question_1));

                $score_is_5 = 0;
                if ($question == 1) {
                    $topScoreFound = ProjectData::getNumberQuestionsTopScore($project_id, $topScoreMax, $question_1, $condition . $conditionDate, $recordIds);
                    if ($topScoreMax == 5) {
                        $score_is_5 = ProjectData::getDataTotalCount($project_id, $recordIds, $condition . " AND [" . $question_1 . "] = '5'");
                    }
                } else {
                    #Get number of questions: Top Score, Very Or SomewhatImportant
                    $topScoreFound = ProjectData::getDataTotalCount($project_id, $recordIds, $condition." AND ([".$question_1."] = '1' OR [".$question_1."] = '2')");
                }
                $topScore = ProjectData::getTopScorePercent($topScoreFound, $total_records, $score_is_5, $missing_InfoLabel);
                if ($topScore > $max) {
                    $max = $topScore;
                }
                $missingOverall += $missing_InfoLabel;
                $responses = $total_records - $missing_InfoLabel;

                #Etnicity Case
                if (ProjectData::isEthnicityVar($study)) {
                    if ($index > 1 && $index < 6) {
                        $study_62_array['topscore'] += $topScoreFound;
                        $study_62_array['totalcount'] += $total_records;
                        $study_62_array['responses'] += $responses;
                        $study_62_array['missing'] += $missing_InfoLabel;
                        $study_62_array['score5'] += $score_is_5;
                    } else if ($index == 6) {
                        $responses = $study_62_array['responses'];
                        $topScore = 0;
                        if (($study_62_array['responses'] - $study_62_array['score5']) != 0) {
                            $topScore = number_format(($study_62_array['topscore'] / ($study_62_array['responses'] - $study_62_array['score5']) * 100), 0);
                        }
                        $missing_InfoLabel = $study_62_array['missing'];
                        $score_is_5 = $study_62_array['score5'];
                    }
                }
                $percent_array = self::getPercent($responses, $score_is_5, $topScore, $showLegend, "");
                $percent = $percent_array[0];
                $showLegend = $percent_array[1];
                $tooltip = $responses . " responses, " . $missing_InfoLabel . " missing";
                if ($question == 1) {
                    $tooltipTextArray[$indexQuestion][$index] = $tooltip . ", " . $score_is_5 . " not applicable";
                    $array_colors[$indexQuestion][$index] = $percent;
                } else {
                    if ($indexQuestion !== "") {
                        $array_colors[$indexQuestion][$index] = $percent;
                        $tooltipTextArray[$indexQuestion][$index] = $tooltip . ", " . $score_is_5 . " not applicable";
                    }
                    $attibute = "";
                    $class = "";
                    if (ProjectData::isEthnicityVar($study) && $index > 1 && $index < 6) {
                        $class = "hide";
                        $attibute = "etnicity = '1'";
                    }
                    $table_b .= '<td class="' . $class . '" ' . $attibute . '><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="' . $tooltip . '">' . $percent . '</div></td>';
                }
            }
        }
        $max_index = max(array_keys($study_options));
        if ($question == 1) {
            $aux = array(0 => $tooltipTextArray, 1 => $array_colors, 2 => $missingOverall, 3 => $max, 4 => $max_index, 5 => $showLegend);
        } else {
            $aux = array(0 => $table_b, 1 => $max_index, 2 => $missingOverall, 5 => $showLegend, 6 => $array_colors, 7 => $tooltipTextArray);
        }
        return $aux;
    }

    /**
     * Function that calculates the percentages for the no added column for PARTICIPANT PERCEPTION
     * @param $question
     * @param $project_id
     * @param $conditionDate
     * @param $multipleRecords
     * @param $study
     * @param $question_1
     * @param $topScoreMax
     * @param $indexQuestion
     * @param $tooltipTextArray
     * @param $array_colors
     * @param $index
     * @param $max
     * @param $recordIds
     * @return array
     */
     public static function getMissingCol($question, $project_id, $conditionDate, $multipleRecords, $study, $question_1, $topScoreMax, $indexQuestion, $tooltipTextArray, $array_colors, $index, $max, $recordIds, $study_options_total){
        $showLegendexMissing = false;
        #Only calculate is 5 for certain questions
        if(!in_array($question_1,ProjectData::getRowQuestionsParticipantPerceptionIs5())){
            $score_is_5O_overall = 0;
        }else {
            if (getFieldType($study, $project_id) == "checkbox") {
                $filterLogic = ProjectData::getREDCapLogicForMissingCheckboxes($study, $study_options_total, "= ''");
            } else {
                $filterLogic = "[" . $study . "] = ''";
            }
            $score_is_5O_overall = ProjectData::getDataTotalCount($project_id, $recordIds, "[" . $question_1 . "] = '5' AND " . $filterLogic);
        }
		$missingRecords = R4Report::getR4Report($project_id)->applyFilterToData("[".$question_1."] != ''".$conditionDate);

        $missing = 0;
        $missingTop = 0;
        foreach ($missingRecords as $key => $mrecord){
            if (($mrecord[$study] == '' && getFieldType($study, $project_id) != "checkbox") || ProjectData::isMultiplesCheckbox($project_id, $mrecord, $study, $study_options_total, 'none')) {
                $missing += 1;
                if($question == 1){
                    if (isTopScore($mrecord[$question_1], $topScoreMax, $question_1)) {
                        $missingTop += 1;
                    }
                }else{
                    if(isTopScoreVeryOrSomewhatImportant($mrecord[$question_1]) && ($mrecord[$question_1] != '' || array_key_exists($question_1,$mrecord))) {
                        $missingTop += 1;
                    }
                }
            }
        }
        unset($missingRecords);

        $type = getFieldType($study,$project_id);
        if($type != "checkbox"){
            $missing_col = ProjectData::getDataTotalCount($project_id, $recordIds, "[".$study."] = '' AND [" . $question_1 . "] = '' ".ProjectData::getCriticalQuestions1LogicForMissing($question_1));
        }else{
            $missing_col = ProjectData::getDataTotalCount($project_id, $recordIds, "[" . $question_1 . "] = '' AND ".ProjectData::getREDCapLogicForMissingCheckboxes($study, $study_options_total, " != '1'")." ".ProjectData::getCriticalQuestions1LogicForMissing($question_1));
        }

        $missingPercent = ProjectData::getTopScorePercent($missingTop, $missing, $score_is_5O_overall, 0);
        if($missingPercent > $max){
            $max = $missingPercent;
        }
        $percent_array = self::getPercent($missing, $score_is_5O_overall, $missingPercent, $showLegendexMissing, "missing");
        $percent = $percent_array[0];
        $showLegendexMissing = $percent_array[1];
        $tooltip = $missing." responses, ".$missing_col." missing";

        if($question == 1) {
            $tooltipTextArray[$indexQuestion][intval($index)+1] = $tooltip.", ".$score_is_5O_overall . " not applicable";
            $array_colors[$indexQuestion][intval($index)+1] = $percent;
            return array(0=>$tooltipTextArray,1=>$array_colors,2=>$missing_col,3=>$max,5=>$showLegendexMissing);
        }else{
            if($indexQuestion !== "") {
                $tooltipTextArray[$indexQuestion][intval($index) + 1] = $tooltip . ", " . $score_is_5O_overall . " not applicable";
                $array_colors[$indexQuestion][intval($index) + 1] = $percent;
            }
            return array(0=>$percent,1=>$tooltip,2=>$missing_col,3=>$showLegendexMissing,4=>$array_colors,5=>$tooltipTextArray);
        }
    }

    /**
     * Function that calculates the percentages for the total column for PARTICIPANT PERCEPTION
     * @param $question
     * @param $project_id
     * @param $question_1
     * @param $conditionDate
     * @param $topScoreMax
     * @param $indexQuestion
     * @param $tooltipTextArray
     * @param $array_colors
     * @param $institutions
     * @param $recordIds
     * @return array
     */
     public static function getTotalCol($question,$project_id,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$tooltipTextArray,$array_colors,$institutions,$recordIds){
		$recordsoverall = R4Report::getR4Report($project_id)->applyFilterToData("[".$question_1."] <> ''".$conditionDate);
        $recordsoverallTotal = count($recordsoverall);
        $topScoreFoundO = 0;
        $showLegendexTotal = false;

        #INSTITUTIONS
        $array_institutions = array();
        $array_institutions_percent = array();
        foreach ($institutions as $institution => $institutionRecords){
            $array_institutions[$institution] = array();
            $array_institutions[$institution]['topScore'] = 0;
            $array_institutions[$institution]['missing'] = 0;
            $array_institutions[$institution]['recordsTotal'] = 0;
            $array_institutions_percent[$institution] = 0;
        }

        foreach ($recordsoverall as $recordo){
            $institution = trim(explode("-",$recordo['record_id'])[0]);
            $array_institutions[$institution]['recordsTotal'] += 1;
            if($question == "1"){
                if (isTopScore($recordo[$question_1], $topScoreMax, $question_1)) {
                    $topScoreFoundO += 1;
                    $array_institutions[$institution]['topScore'] += 1;
                }
            }else{
                if(isTopScoreVeryOrSomewhatImportant($recordo[$question_1]) && ($recordo[$question_1] != '' || array_key_exists($question_1,$recordo))) {
                    $topScoreFoundO += 1;
                    $array_institutions[$institution]['topScore'] += 1;
                }
            }
        }
        unset($recordsoverall);
	
		$missingRecords = R4Report::getR4Report($project_id)->applyFilterToData("[".$question_1."] = '5'".$conditionDate);
        $score_is_5O_overall_missing = 0;
        foreach($missingRecords as $misRecord){
            if ($misRecord[$question_1] == 5 && $topScoreMax == 5) {
                $institution = trim(explode("-",$misRecord['record_id'])[0]);
                $score_is_5O_overall_missing += 1;
                $array_institutions[$institution]['missing'] += 1;
            }
        }
        unset($missingRecords);

        $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
		$missingRecordsNoFilter = R4Report::getR4Report($project_id)->applyFilterToData("[".$question_1."] = ''".$conditionDate);
        $missingOverall = 0;
        foreach($missingRecordsNoFilter as $misRecordNF) {
            foreach ($row_questions_1 as $questionNF){
                if($misRecordNF[$questionNF] !== ""){
                    $missingOverall += 1;
                    break;
                }
            }
        }
        unset($missingRecordsNoFilter);
        $overall = ProjectData::getTopScorePercent($topScoreFoundO, $recordsoverallTotal, $score_is_5O_overall_missing, 0);

        #Institutions Data
        foreach ($array_institutions as $institution=>$data) {
            $overall_institution = 0;
            if($array_institutions[$institution]['topScore']  > 0) {
                $overall_institution = number_format(($array_institutions[$institution]['topScore'] / ($array_institutions[$institution]['recordsTotal'] - $array_institutions[$institution]['missing']) * 100), 0);
            }

            $percent_array = self::getPercent($array_institutions[$institution]['recordsTotal'], $array_institutions[$institution]['missing'], $overall_institution, $showLegendexTotal, "total");
            $array_institutions_percent[$institution] = $percent_array[0];
            $showLegendexTotal = $percent_array[1];
        }

        $percent_array = self::getPercent($recordsoverallTotal, $score_is_5O_overall_missing, $overall, $showLegendexTotal, "total");
        $percent = $percent_array[0];
        $showLegendexTotal = $percent_array[1];
        $tooltip = $recordsoverallTotal . " responses, " . $missingOverall . " missing";

        if($question == 1) {
            $tooltipTextArray[$indexQuestion][0] = $tooltip.", ".$score_is_5O_overall_missing . " not applicable";
            $array_colors[$indexQuestion][0] = $percent;
            $array_colors_intitutions[$indexQuestion][0] = $array_institutions_percent;
            return array(0=>$tooltipTextArray,1=>$array_colors,2=>$showLegendexTotal,3=>$array_colors_intitutions);
        }else{
            if($indexQuestion !== ""){
                $tooltipTextArray[$indexQuestion][0] = $tooltip.", ".$score_is_5O_overall_missing . " not applicable";
                $array_colors[$indexQuestion][0] = $percent;
                $array_colors_intitutions[$indexQuestion][0] = $array_institutions_percent;
            }
            return array(0=>$percent,1=>$tooltip,2=>$showLegendexTotal,3=>$array_colors,4=>$tooltipTextArray,5=>$array_colors_intitutions);
        }
    }

    /**
     * Function that calculates the percentages for the Ethnicity study for PARTICIPANT PERCEPTION
     * @param $question
     * @param $project_id
     * @param $multipleRecords
     * @param $study
     * @param $question_1
     * @param $topScoreMax
     * @param $indexQuestion
     * @param $index
     * @param $tooltipTextArray
     * @param $array_colors
     * @return array
     */
     public static function getMultipleCol($question,$project_id,$multipleRecords,$study,$question_1,$topScoreMax,$indexQuestion,$index,$tooltipTextArray,$array_colors,$study_options_total){
        $multiple = 0;
        $multipleTop = 0;
        $multiple_not_applicable = 0;
        $multiple_missing = 0;
        $showLegendexMultiple = false;
        foreach ($multipleRecords as $multirecord){
            if (ProjectData::isMultiplesCheckbox($project_id, $multirecord, $study, $study_options_total)) {
                $multiple += 1;
                if ($question == 1) {
                    if (isTopScore($multirecord[$question_1], $topScoreMax, $question_1) && ($multirecord[$question_1] != '' || array_key_exists($question_1, $multirecord))) {
                        $multipleTop += 1;
                    }
                    if ($multirecord[$question_1] == "5" && $topScoreMax == 5) {
                        $multiple_not_applicable += 1;
                    }
                } else {
                    if (isTopScoreVeryOrSomewhatImportant($multirecord[$question_1]) && ($multirecord[$question_1] != '' || array_key_exists($question_1, $multirecord))) {
                        $multipleTop += 1;
                    }
                }

                if ($multirecord[$question_1] == '' || !array_key_exists($question_1, $multirecord)) {
                    $multiple_missing += 1;
                }
            }
        }

        $multiplePercent = ProjectData::getTopScorePercent($multipleTop, $multiple, $multiple_not_applicable, 0);

        $responses = $multiple - $multiple_missing;
        $percent_array = self::getPercent($responses, $multiple_not_applicable, $multiplePercent, $showLegendexMultiple, "multiple");
        $percent = $percent_array[0];
        $showLegendexMultiple = $percent_array[1];
        $tooltip = $responses . " responses, " . $multiple_missing . " missing";

        $index = (int)$index;
        if($question == 1) {
            $tooltipTextArray[$indexQuestion][$index+2] = $tooltip.", ".$multiple_not_applicable . " not applicable";
            $array_colors[$indexQuestion][$index+2] = $percent;
            return array(0=>$tooltipTextArray,1=>$array_colors,2=>$showLegendexMultiple);
        }else{
            if($indexQuestion !== ""){
                $tooltipTextArray[$indexQuestion][$index+2] = $tooltip.", ".$multiple_not_applicable . " not applicable";
                $array_colors[$indexQuestion][$index+2] = $percent;
            }
            return array(0=>$percent,1=>$tooltip,2=>$showLegendexMultiple,3=>$array_colors,4=>$tooltipTextArray);
        }
    }
	
	public static function getPercent($recordsTotal, $missing, $overall, $showLegend, $option){
		if($recordsTotal == 0 || ($recordsTotal == $missing && $option != "multiple")){
			//No responses
			$percent = "-";
			$showLegend = true;
		}else if(($recordsTotal - $missing) < 5){
			//Fewer than 5 responses
			$percent = "x";
			$showLegend = true;
		}else if(($recordsTotal - $missing) < 20){
			//Fewer than 20 responses
			$percent = $overall." *";
			$showLegend = true;
		}else{
			$percent = $overall;
			$showLegend = true;
		}
		return array(0=>$percent,1=>$showLegend);
	}
	
     public static function calculateResponseRate($num_questions_answered, $total_questions, $index, $graph){
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

    /**
     * Function that calculates the percentages for the filter studies like age, ethnicity,... for RESPONSE/COMPLETION RATES
     * @param $project_id
     * @param $conditionDate
     * @param $row_questions_1
     * @param $graph
     * @param $study
     * @param $study_options
     * @param $recordIds
     * @return mixed
     */
     public static function getNormalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $study_options, $recordIds){
        $graph["any"] = array();
        $graph["complete"] = array();
        $graph["partial"] = array();
        $graph["breakoffs"] = array();
        $study_62_array = array(
            "totalcount" => 0,
            "responses" => 0
        );
        foreach ($study_options as $index => $col_title) {
            $condition = getParamOnType($study, $index,$project_id);
            #Etnicity Case
            if (ProjectData::isEthnicityVar($study) && $index == count($study_options)) {
                $condition = getEthnicityCondition(count($study_options),$study,$project_id);
            }

			$allRecords = R4Report::getR4Report($project_id)->applyFilterToData($condition.$conditionDate);
            $total_records = count($allRecords);
            $total_questions = count($row_questions_1);
            $graph["total_records"][$index] = $total_records;
            foreach ($allRecords as $record) {
                $num_questions_answered = 0;
                foreach ($row_questions_1 as $indexQuestion => $question_1) {
                    if ($record[$question_1] !== "") {
                        $num_questions_answered++;
                    }
                }
                $graph = self::calculateResponseRate($num_questions_answered, $total_questions, $index, $graph);
            }
        }
        return $graph;
    }

    /**
     * Function that calculates the percentages for the no added column for RESPONSE/COMPLETION RATES
     * @param $project_id
     * @param $conditionDate
     * @param $row_questions_1
     * @param $graph
     * @param $study
     * @param $multipleRecords
     * @return mixed
     */
     public static function getMissingStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $multipleRecords){
        $graph = self::addZeros($graph, "missing");
        $type = getFieldType($study, $project_id);

        $total_records = 0;
        $total_questions = count($row_questions_1);

        foreach ($multipleRecords as $record) {
            if(!empty($record[$study]) && $type == "checkbox" && !in_array('1', $record[$study], '1') || $type != "checkbox" && $record[$study] == '') {
                $total_records += 1;
                $num_questions_answered = 0;
                foreach ($row_questions_1 as $indexQuestion => $question_1) {
                    if ($record[$question_1] !== "") {
                        $num_questions_answered++;
                    }
                }
                $graph = self::calculateResponseRate($num_questions_answered, $total_questions, "missing", $graph);
            }
        }
        $graph["total_records"]["missing"] = $total_records;
        return $graph;
    }

    /**
     * Function that calculates the percentages for the total column for RESPONSE/COMPLETION RATES
     * @param $project_id
     * @param $conditionDate
     * @param $row_questions_1
     * @param $graph
     * @param $recordIds
     * @return mixed
     */
     public static function getTotalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $recordIds){
        $graph = self::addZeros($graph, "total");
		$allRecords = R4Report::getR4Report($project_id)->applyFilterToData($conditionDate);
//        $allRecords = \REDCap::getData($project_id, 'json-array', $recordIds, null, null, null, false, false, false, $conditionDate);
        $total_records = count($allRecords);
        $total_questions = count($row_questions_1);
        $graph["total_records"]["total"] = $total_records;
        foreach ($allRecords as $record) {
            $num_questions_answered = 0;
            foreach ($row_questions_1 as $indexQuestion => $question_1) {
                if ($record[$question_1] !== "") {
                    $num_questions_answered++;
                }
            }
            $graph = self::calculateResponseRate($num_questions_answered, $total_questions, "total", $graph);
        }
        return $graph;
    }

    /**
     * Function that calculates the percentages by institutions for RESPONSE/COMPLETION RATES
     * @param $project_id
     * @param $conditionDate
     * @param $row_questions_1
     * @param $institutions
     * @param $graph
     * @param $recordIds
     * @return mixed
     */
     public static function getTotalStudyInstitutionColRate($project_id, $conditionDate, $row_questions_1, $institutions, $graph, $recordIds){
        $graph = self::addZeros($graph, "total");
        $data = $row_questions_1;
        array_push($data, "record_id");
		$allRecords = R4Report::getR4Report($project_id)->applyFilterToData($conditionDate);
        $total_records = count($allRecords);
        $total_questions = count($row_questions_1);
        $graph["total_records"]["total"] = $total_records;
        $array_institutions = array();
        $graph[ProjectData::INSTITUTIONS_ARRAY_KEY] = array();

        foreach($institutions as $institution => $institutionRecords) {
            $array_institutions[$institution]['any'] = 0;
            $array_institutions[$institution]['complete'] = 0;
            $array_institutions[$institution]['partial'] = 0;
            $array_institutions[$institution]['breakoffs'] = 0;
            $array_institutions[$institution]['total_records'] = 0;
            $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution] = array();
            $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]['any'] = 0;
            $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]['complete'] = 0;
            $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]['partial'] = 0;
            $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]['breakoffs'] = 0;
            $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]['total_records'] = 0;
            foreach ($allRecords as $record) {
                $institution_record = trim(explode("-",$record['record_id'])[0]);
                if($institution_record == $institution){
                    $array_institutions[$institution]['total_records'] += 1;
                    $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]['total_records'] += 1;
                    $num_questions_answered = 0;
                    foreach ($row_questions_1 as $indexQuestion => $question_1) {
                        if ($record[$question_1] !== "") {
                            $num_questions_answered++;
                        }
                    }
                    $percent = number_format((float)($num_questions_answered / $total_questions), 2, '.', '');
                    if ($percent >= 0.8) {
                        $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]["complete"]++;
                    } else if ($percent < 0.8 && $percent >= 0.5) {
                        $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]["partial"]++;
                    } else if ($percent < 0.5 && $percent > 0) {
                        $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]["breakoffs"]++;
                    }
                    if($percent > 0){
                        $graph[ProjectData::INSTITUTIONS_ARRAY_KEY][$institution]["any"]++;
                    }
                }
            }
        }
        return $graph;
    }

    /**
     * Function that calculates the percentages for the Ethnicity study for RESPONSE/COMPLETION RATES
     * @param $project_id
     * @param $conditionDate
     * @param $row_questions_1
     * @param $graph
     * @param $study
     * @param $multipleRecords
     * @return mixed
     */
     public static function getMultipleStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $multipleRecords, $study_options_total){
        $graph = self::addZeros($graph, "multiple");
        $graph["total_records"]["multiple"] = 0;
        $total_questions = count($row_questions_1);
        foreach ($multipleRecords as $multirecord){
            if (ProjectData::isMultiplesCheckbox($project_id, $multirecord, $study, $study_options_total)) {
                $graph["total_records"]["multiple"] += 1;
            }
        }

        foreach ($multipleRecords as $multirecord){
            $num_questions_answered = 0;
            if (ProjectData::isMultiplesCheckbox($project_id, $multirecord, $study, $study_options_total)) {
                foreach ($row_questions_1 as $indexQuestion => $question_1) {
                    if ($multirecord[$question_1] !== "") {
                        $num_questions_answered++;
                    }
                }
                $graph = self::calculateResponseRate($num_questions_answered, $total_questions, "multiple", $graph);
            }
        }

        return $graph;
    }

     public static function addZeros($graph, $index){
        $graph["any"][$index] = 0;
        $graph["complete"][$index] = 0;
        $graph["partial"][$index] = 0;
        $graph["breakoffs"][$index] = 0;
        return $graph;
    }

     public static function printResponseRate($questions, $total_records){
        if ($questions === "") {
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

     public static function getResponseRate($questions, $total_records){
        if ($questions === "") {
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

    public static function arrangeArrayIndexToStartBy1($array, $indexQuestion){
        #Rearrange index to start at 1 to make sure 0 it's used only for TOTAL column
        $array[$indexQuestion] = array_combine(range(1, count($array[$indexQuestion])), array_values($array[$indexQuestion]));
        return $array;
    }
}
?>