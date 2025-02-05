<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;


class ProjectData
{
    const MAX_CUSTOM_FILTERS = 11;
    const INSTITUTIONS_ARRAY_KEY = "institutions";
    const NOFILTER_ARRAY_KEY = "nofilter";
    const BYSITE_ARRAY_KEY = "bysite";
    const ETHNICITY_VAR1 = "rpps_s_q62";
    const ETHNICITY_VAR2 = "ethnicity";

    public static function getRandomIdentifier($length = 6) {
        $output = "";
        $startNum = pow(32,5) + 1;
        $endNum = pow(32,6);
        while($length > 0) {

            # Generate a number between 32^5 and 32^6, then convert to a 6 digit string
            $randNum = mt_rand($startNum,$endNum);
            $randAlphaNum = self::numberToBase($randNum,32);

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

    public static function numberToBase($number, $base) {
        $newString = "";
        while($number > 0) {
            $lastDigit = $number % $base;
            $newString = self::convertDigit($lastDigit, $base).$newString;
            $number -= $lastDigit;
            $number /= $base;
        }

        return $newString;
    }

    public static function convertDigit($number, $base) {
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

    public static function decodeData($encryptedCode, $secret_key, $secret_iv, $timestamp){
        $code = self::getCrypt($encryptedCode,"d",$secret_key,$secret_iv);
        if($code == "start_".$timestamp){
            return true;
        }
        return false;
    }

    public static function getCrypt($string, $action = 'e',$secret_key="",$secret_iv="" ) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        }

        return $output;
    }

    public static function startTest($encryptedCode, $secret_key, $secret_iv, $timestamp){
        $code = self::getCrypt($encryptedCode,"d",$secret_key,$secret_iv);
        if($code == "start_".$timestamp){
            return true;
        }
        return false;
    }

    public static function getFilterQuestionsArray()
    {
        $array_questions = array(
            1 => "Participant perception",
            2 => "Response/Completion Rates",
            3 => "Reasons for joining a study",
            4 => "Reasons for leaving a study",
            5 => "Reasons for staying in a study"
        );
        return $array_questions;
    }

    public static function getRowQuestions()
    {
        $row_questions = array(3 => "2-15", 4 => "26-39", 5 => "40-55");
        return $row_questions;
    }

    public static function getStudyArray()
    {
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
            "rpps_s_q66" => "Informed Consent setting",
            "rpps_s_q16" => "Study Type",
            "header2" => "About the survey fielding:",
            "sampling" => "Sampling approach",
            "timing_of_rpps_administration" => "Timing of RPPS administration"
        );
        return $array_study;
    }

    public static function getRowQuestionsParticipantPerception()
    {
        $row_questions_1 = array(
            13 => "rpps_s_q57",
            0 => "rpps_s_q1",
            1 => "rpps_s_q17",
            2 => "rpps_s_q18",
            3 => "rpps_s_q19",
            4 => "rpps_s_q20",
            5 => "rpps_s_q21",
            6 => "rpps_s_q68",
            7 => "rpps_s_q22",
            8 => "rpps_s_q23",
            9 => "rpps_s_q24",
            10 => "rpps_s_q25",
            11 => "rpps_s_q69",
            12 => "rpps_s_q67"
        );
        return $row_questions_1;
    }

    public static function getRowQuestionsResponseRate(){
        $row_questions_2 = array(1 => "any", 2 => "partial", 3 => "complete", 4 => "breakoffs");
        return $row_questions_2;
    }

    public static function getRowQuestionsResponseRateInfo(){
        $row_questions_2 = array(
            1 => "A survey returned with a non-null response provided for at least 1 core question is counted in the calculation of “Any” response.",
            2 => "A survey returned with a non-null response provided for 50-80% of the core questions is considered a “Partial” response.",
            3 => "A survey returned with a non-null response provided for 80-100% of the core questions is considered a “Complete” response.",
            4 => "A survey returned with a non-null response provided for between <50% of the core questions is considered a “Breakoff” response."
        );
        return $row_questions_2;
    }

    public static function getRowQuestionsParticipantPerceptionIs5()
    {
        $row_questions_1_is_5 = array(0 => "rpps_s_q68", 1 => "rpps_s_q23", 2 => "rpps_s_q25", 3 => "rpps_s_q69");
        return $row_questions_1_is_5;
    }

    public static function getAllInstitutions($multipleRecords){
        $array_institutions = [];
        foreach ($multipleRecords as $record){
            $institution = trim(explode("-",$record['record_id'])[0]);
            if(!array_key_exists($institution,$array_institutions)){
				$array_institutions[$institution] = [];
            }
			$array_institutions[$institution][$record['record_id']] = 1;
        }
        return $array_institutions;
    }

    public static function getInstitutionProjectData($multipleRecords, $institutionName = ""){
        $project_data_institutions = [];
        foreach ($multipleRecords as $record){
            $institution = trim(explode("-",$record['record_id'])[0]);
            if($institutionName !== "" && $institutionName == $institution){
                array_push($project_data_institutions, $record);
            }
        }
        return $project_data_institutions;
    }

    public static function getArrayStudyQuestion_1()
    {
        $array_study_1 = array(
            "rpps_s_q60" => "Age",
            "rpps_s_q59" => "Education",
            "rpps_s_q62" => "Ethnicity",
            "rpps_s_q65" => "Gender",
            "rpps_s_q61" => "Race",
            "rpps_s_q63" => "Sex",
            "rpps_s_q58" => "Demands of study",
            "rpps_s_q15" => "Disease/disorder to enroll",
            "rpps_s_q66" => "Informed Consent setting",
            "rpps_s_q16" => "Study Type",
            "sampling" => "Sampling approach",
            "timing_of_rpps_administration" => "Timing of RPPS administration"
        );
        return $array_study_1;
    }

    public static function getArrayStudyQuestion_2()
    {
        $array_study_2 = array(
            "age" => "Age",
            "ethnicity" => "Ethnicity",
            "gender_identity" => "Gender Identity",
            "race" => "Race",
            "sex" => "Sex"
        );
        return $array_study_2;
    }

    public static function getArrayStudyQuestion_3()
    {
        $array_study_3 = array(
            "rpps_s_q60" => "Age",
            "rpps_s_q59" => "Education",
            "rpps_s_q62" => "Ethnicity",
            "rpps_s_q65" => "Gender",
            "rpps_s_q61" => "Race",
            "rpps_s_q63" => "Sex",
            "rpps_s_q58" => "Demands of study",
            "rpps_s_q15" => "Disease/disorder to enroll",
            "rpps_s_q66" => "Informed Consent setting",
            "rpps_s_q16" => "Study Type",
            "sampling" => "Sampling approach",
            "timing_of_rpps_administration" => "Timing of RPPS administration"
        );
        return $array_study_3;
    }

    public static function getExtraColumTitle()
    {
        return "Yes - ALL Spanish/Hispanic/Latino";
    }
	
    public static function getNumberQuestionsTopScore($project_id, $topScoreMax, $question, $condition, $recordIds)
    {
        if ($topScoreMax == 4 || $topScoreMax == 5) {
            if($question == 'rpps_s_q21' || $question == "rpps_s_q25"){
                $val = '1';
            }if($question != 'rpps_s_q21' && $question != "rpps_s_q25"){
                $val = '4';
            }
			$records = R4Report::getR4Report($project_id)->applyFilterToData($condition." AND [".$question."] = ".$val);
        }else if($topScoreMax == 11){
			$records = R4Report::getR4Report($project_id)->applyFilterToData($condition." AND ([".$question."] = '9' OR [".$question."] = '10')");
        }

        $numberQuestions = 0;
        if(!empty($records)){
            $numberQuestions = count($records);
        }
        unset($records);

        return $numberQuestions;
    }

    public static function getTopScorePercent($topScoreFound, $total_records, $score_is_5, $missing_InfoLabel)
    {
        $topScore = 0;
        $calc = ($total_records - $score_is_5 - $missing_InfoLabel);
        if ($topScoreFound > 0 && $calc != 0) {
            $topScore = number_format(($topScoreFound / $calc * 100), 0);
        }

        return $topScore;
    }

    public static function getS3Path($module, $project_id){
        $path = $module->getProjectSetting('path',$project_id);

        if(empty($path)){
            $path = null;
        }else {
            $path = $module->validateS3Url($path);
        }

        return $path;
    }

    public static function getFileData($module, $project_id, $filenametext, $report){
        #Check if we have a different path than edocs
        $path = self::getS3Path($module, $project_id);

        if(!empty($report)){
            $filename = $filenametext . $project_id . "_report_" . $report . ".txt";
        }else{
            $filename = $filenametext . $project_id.".txt";
        }


        if(empty($path)) {
            $q = $module->query("SELECT docs_id FROM redcap_docs WHERE project_id=? AND docs_name=?", [$project_id, $filename]);
            while ($row = db_fetch_assoc($q)) {
                $docsId = $row['docs_id'];
                $q2 = $module->query("SELECT doc_id FROM redcap_docs_to_edocs WHERE docs_id=?", [$docsId]);
                while ($row2 = db_fetch_assoc($q2)) {
                    $docId = $row2['doc_id'];
                    $q3 = $module->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=? AND delete_date is NULL", [$docId]);
                    while ($row3 = $q3->fetch_assoc()) {
                        $path = $module->getSafePath($row3['stored_name'], EDOC_PATH);
                        $strJsonFileContents = file_get_contents($path);
                        $graph = json_decode($strJsonFileContents, true);
                    }
                }
            }
        }else{
            $strJsonFileContents = file_get_contents($module->validateS3Url($path . $filename));
            $graph = json_decode($strJsonFileContents, true);
        }
        return $graph;
    }

    public static function getDataTotalCount($project_id, $recordIds, $condition, $params="record_id"){
		$RecordSet = R4Report::getR4Report($project_id)->applyFilterToData($condition);
        $total_count = count($RecordSet);
        unset($RecordSet);
        return $total_count;
    }

    public static function isMultiplesCheckbox($project_id, $data, $study, $study_options_total, $option=''){
        if(getFieldType($study, $project_id) == "checkbox") {
            $count = 0;
            foreach ($study_options_total as $index => $value){
                if (array_key_exists($study . '___' . $index, $data) && $data[$study . '___' . $index] == '1') {
                    $count++;
                }
                if($count >= 2 && $option != "none")
                    return true;
            }
            if($option == 'none' && $count ==  "0"){
                return true;
            }
        }
        return false;
    }

    public static function getREDCapLogicForMissingCheckboxes($study, $study_options_total, $operatorValue){
        $filterLogic = "";
        if(!empty($study_options_total) && is_array($study_options_total)) {
            $last_index = array_key_last($study_options_total);
            foreach ($study_options_total as $index => $value) {
                if ($index == $last_index)
                    $filterLogic .= "[" . $study . "(" . $index . ")] ".$operatorValue;
                else
                    $filterLogic .= "[" . $study . "(" . $index . ")] ".$operatorValue." AND ";
            }
        }
        return $filterLogic;
    }

    public static function getCriticalQuestions1LogicForMissing($question_1){
        $row_questions_1 = self::getRowQuestionsParticipantPerception();
        $key = array_search($question_1, $row_questions_1);
        unset($row_questions_1[$key]);

        $logic = "";
        if(!empty($row_questions_1)) {
            $logic = "AND (";
            $last_question = count($row_questions_1);
            $count = 1;
            foreach ($row_questions_1 as $question) {
                if ($count == $last_question) {
                    $logic .= "[" . $question . "] != '')";
                } else {
                    $logic .= "[" . $question . "] != '' OR ";
                }
                $count++;
            }
        }
        return $logic;
    }

    public static function getChoiceLabelsArray($module, $study, $project_id){
        $type = getFieldType($study, $project_id);
        $choicesById = [];
        if($type == 'truefalse') {
            $choicesById[0] = "False";
            $choicesById[1] = "True";
        } else if ($type == 'yesno') {
            $choicesById[0] = "No";
            $choicesById[1] = "Yes";
        }else{
            $choicesById = $module->getChoiceLabels($study, $project_id);
        }
        return  $choicesById;
    }

    public static function isEthnicityVar($study){
        if($study == self::ETHNICITY_VAR1 || $study == self::ETHNICITY_VAR2){
            return true;
        }
        return false;
    }
}
?>