<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/ProjectData.php");
require_once (dirname(__FILE__)."/GraphData.php");
include_once(__DIR__ . "/../functions.php");


class Crons
{
    public static function runCacheCron($module,$project_id)
    {
        $RecordSetMultiple = \REDCap::getData($project_id, 'array');
        $multipleRecords = ProjectData::getProjectInfoArray($RecordSetMultiple);
        $institutions = ProjectData::getAllInstitutions($multipleRecords);
        $table_data = array();

        #QUESTION = 1
        $table_data = self::createQuestion_1($module, $project_id, $multipleRecords, $institutions, $table_data, null);
        #QUESTION = 2
        $table_data = self::createQuestion_2($module, $project_id, $multipleRecords, $institutions, $table_data, null);
        #QUESTION = 3,4,5
        $table_data = self::createQuestion_3($module, $project_id, $multipleRecords, $institutions, $table_data, null);
        #CREATE & SAVE FILE
        $filename = "dashboard_cache_file_" . $project_id .".txt";
        $filereponame = "Dashboard Cache File";
        self::saveRepositoryFile($module, $project_id, $filename, $table_data,$filereponame);
    }

    public static function runCacheReportCron($module, $project_id, $report)
    {
        $custom_report_id = $module->getProjectSetting('custom-report-id',$project_id);
        $recordIds = array();
        if(!empty($custom_report_id)) {
            if($report != null){
                $custom_report_id = array(0=>$report);
            }
            foreach ($custom_report_id as $rid) {
                $q = $module->query("SELECT report_id FROM redcap_reports 
                                    WHERE project_id = ? AND unique_report_name=?",
                                    [$project_id,$rid]);
                $row = $q->fetch_assoc();
                $reports = \REDCap::getReport($row['report_id']);
                if(!empty($reports)) {
                    foreach ($reports as $record => $data) {
                        array_push($recordIds, $record);
                    }

                    //We create the data & file
                    $RecordSetMultiple = \REDCap::getData($project_id, 'array',$recordIds);
                    $multipleRecords = ProjectData::getProjectInfoArray($RecordSetMultiple);
                    $institutions = ProjectData::getAllInstitutions($multipleRecords);
                    $table_data = array();

                    #QUESTION = 1
                    $table_data = self::createQuestion_1($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds);
                    #QUESTION = 2
                    $table_data = self::createQuestion_2($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds);
                    #QUESTION = 3,4,5
                    $table_data = self::createQuestion_3($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds);
                    #CREATE & SAVE FILE
                    $filename = "dashboard_cache_file_" . $project_id . "_report_".$rid.".txt";
                    $filereponame = "Dashboard Cache File - Report: ".$rid;
                    self::saveRepositoryFile($module, $project_id, $filename, $table_data,$filereponame);
                }
            }
        }

    }

    public static function createQuestion_1($module, $project_id, $multipleRecords, $institutions, $table_data, $recordIds)
    {
        $question = 1;
        $array_study_1 = ProjectData::getArrayStudyQuestion_1();
        $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();

        $allData_array = array();
        $allDataTooltip_array = array();
        $allLabel_array = array();
        $graph = array();
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
        $isnofiltercalculated = false;
        foreach ($array_study_1 as $study => $label) {
            $study_options = $module->getChoiceLabels($study, $project_id);
            if ($study == "rpps_s_q62") {
                array_push($study_options, "Yes - Spanish/Hispanic/Latino");
            }
            $showLegend = false;
            foreach ($row_questions_1 as $indexQuestion => $question_1) {
                $array_colors = array();
                $tooltipTextArray = array();
                $outcome_labels = $module->getChoiceLabels($question_1, $project_id);
                $topScoreMax = count($outcome_labels);
                $missingOverall = 0;

                #NORMAL STUDY
                $normalStudyCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyCol($question, $project_id, $study_options, $study, $question_1, $conditionDate, $topScoreMax, $indexQuestion, $tooltipTextArray, $array_colors, $max, $recordIds);
                $tooltipTextArray = $normalStudyCol[0];
                $array_colors = $normalStudyCol[1];
                $missingOverall = $normalStudyCol[2];
                $index = $normalStudyCol[4];
                $showLegendNormal = $normalStudyCol[5];

                #MISSING
                $missingCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingCol($question, $project_id, $conditionDate, $multipleRecords, $study, $question_1, $topScoreMax, $indexQuestion, $tooltipTextArray, $array_colors, $index, $max, $recordIds);
                $tooltipTextArray = $missingCol[0];
                $array_colors = $missingCol[1];
                $missing_col = $missingCol[2];
                $graph = $missingCol[4];
                $showLegendMissing = $missingCol[5];

                #OVERALL COL MISSING
                $totalCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalCol($question, $project_id, $question_1, $conditionDate, $topScoreMax, $indexQuestion, $missing_col, $missingOverall, $tooltipTextArray, $array_colors,$institutions, $recordIds);
                $tooltipTextArray = $totalCol[0];
                $array_colors = $totalCol[1];
                $showLegendTotal = $totalCol[2];
                if(!$isnofiltercalculated) {
                    $allData_array[$question]["nofilter"][$question_1] = $totalCol[1];
                    $allDataTooltip_array[$question]["nofilter"][$question_1] = $totalCol[0];
                }

                #INSTITUTIONS
                $allData_array[$question]["institutions"][$question_1] = $totalCol[3];

                #MULTIPLE
                if ($study == "rpps_s_q61") {
                    $multipleCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleCol($question, $project_id, $multipleRecords, $study, $question_1, $topScoreMax, $indexQuestion, $index, $tooltipTextArray, $array_colors);
                    $tooltipTextArray = $multipleCol[0];
                    $array_colors = $multipleCol[1];
                    $showLegendMultiple = $multipleCol[2];
                }
                $allData_array[$question][$study][$question_1] = $array_colors;
                $allDataTooltip_array[$question][$study][$question_1] = $tooltipTextArray;

                if ($showLegendNormal || $showLegendMissing || $showLegendMultiple || $showLegendTotal) {
                    $showLegend = true;
                }
            }
            $isnofiltercalculated = true;
            $allLabel_array[$question][$study] = $showLegend;
        }
        $table_data['data'] = $allData_array;
        $table_data['tooltip'] = $allDataTooltip_array;
        $table_data['legend'] = $allLabel_array;
        return $table_data;
    }

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

        #INSTITUTIONS
        $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalStudyInstitutionColRate($project_id, $conditionDate, $row_questions_1, $institutions, $graph, $recordIds);
        foreach ($row_questions_2 as $indexQuestion => $question_2) {
            foreach ($institutions as $institution) {
                $totalInstitution = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getResponseRate($graph["institutions"][$institution][$question_2], $graph["institutions"][$institution]["total_records"]);
                $allData_array[$question]["institutions"][$question_2][$institution][0] = $totalInstitution[0];
            }
        }

        foreach ($array_study_2 as $study => $label) {
            $study_options = $module->getChoiceLabels($study, $project_id);
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $study_options, $recordIds);
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $multipleRecords);
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $recordIds);
            if($study == "race"){
                $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $multipleRecords, $recordIds);
            }
            foreach ($row_questions_2 as $indexQuestion => $question_2) {
                $array_colors = array();
                $tooltipTextArray = array();
                $total = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getResponseRate($graph[$question_2]["total"], $graph["total_records"]["total"]);
                $allData_array[$question]["nofilter"][$question_2][0] = $total[0];
                $allDataTooltip_array[$question]["nofilter"][$question_2][0] = $total[1];
                array_push($array_colors, $total[0]);
                array_push($tooltipTextArray, $total[1]);

                #NORMAL
                foreach ($study_options as $index => $col_title) {
                    $normal = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getResponseRate($graph[$question_2][$index], $graph["total_records"][$index]);
                    array_push($array_colors, $normal[0]);
                    array_push($tooltipTextArray, $normal[1]);
                }
                #MISSING
                $missing = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getResponseRate($graph[$question_2]["missing"], $graph["total_records"]["missing"]);
                $array_colors[count($study_options) + 1] = $missing[0];
                $tooltipTextArray[count($study_options) + 1] = $missing[1];

                if($study == "race") {
                    #MULTIPLE
                    $multiple = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getResponseRate($graph[$question_2]["multiple"], $graph["total_records"]["multiple"]);
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
            if ($count < 11) {
                $array_study_3[$sstudy] = "Custom site value " . $count;
            } else {
                break;
            }
            $count++;
        }
        foreach ($array_study_3 as $study => $label) {
            $study_options = $module->getChoiceLabels($study, $project_id);
            if ($study == "rpps_s_q62") {
                array_push($study_options, "Yes - Spanish/Hispanic/Latino");
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
                    $normalStudyCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyCol($question, $project_id, $study_options, $study, "rpps_s_q" . $i, $conditionDate, "", $indexQuestion, $tooltipTextArray, $array_colors, "", $recordIds);
                    $index = $normalStudyCol[1];
                    $missingOverall = $normalStudyCol[2];
                    $showLegendNormal = $normalStudyCol[5];
                    $array_colors = $normalStudyCol[6];
                    $tooltipTextArray = $normalStudyCol[7];

                    #MISSING
                    $missingCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingCol($question, $project_id, $conditionDate, $multipleRecords, $study, "rpps_s_q" . $i, "", $indexQuestion, $tooltipTextArray, $array_colors, $index, "", $recordIds);
                    $missing_col = $missingCol[2];
                    $showLegendMissing = $missingCol[3];
                    $array_colors = $missingCol[4];
                    $tooltipTextArray = $missingCol[5];

                    #OVERALL MISSING
                    $totalCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalCol($question, $project_id, "rpps_s_q" . $i, $conditionDate, "", $indexQuestion, $missing_col, $missingOverall, $tooltipTextArray, $array_colors, $institutions, $recordIds);
                    $showLegendTotal = $totalCol[2];
                    $array_colors = $totalCol[3];
                    $tooltipTextArray = $totalCol[4];
                    $allData_array[$question]["nofilter"]["rpps_s_q" . $i] = $totalCol[3];
                    $allDataTooltip_array[$question]["nofilter"]["rpps_s_q" . $i] = $totalCol[4];
                    $allData_array[$question]["institutions"]["rpps_s_q" . $i] = $totalCol[5];

                    #MULTIPLE
                    if ($study == "rpps_s_q61") {
                        $multiple = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleCol($question, $project_id, $multipleRecords, $study, "rpps_s_q" . $i, "", $indexQuestion, $index, $tooltipTextArray, $array_colors);
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

    public static function saveRepositoryFile($module, $project_id, $filename, $table_data, $filereponame){
        if ($table_data != "" && $table_data['data'] != "" && $table_data['tooltip'] != "") {
            #SAVE DATA IN FILE
            #create and save file with json
            $storedName = date("YmdHis") . "_pid" . $project_id . "_" . ProjectData::getRandomIdentifier(6) . ".txt";

            $file = fopen(EDOC_PATH . $storedName, "wb");
            fwrite($file, json_encode($table_data, JSON_FORCE_OBJECT));
            fclose($file);

            $output = file_get_contents(EDOC_PATH . $storedName);
            $filesize = file_put_contents(EDOC_PATH . $storedName, $output);

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
            $q = $module->query("INSERT INTO redcap_edocs_metadata (stored_name,doc_name,doc_size,file_extension,mime_type,gzipped,project_id,stored_date) VALUES(?,?,?,?,?,?,?,?)",
                [$storedName, $filename, $filesize, 'txt', 'text/plain', '0', $project_id, date('Y-m-d H:i:s')]);
            $docId = db_insert_id();

            //Save document in File Repository
            $q = $module->query("INSERT INTO redcap_docs (project_id,docs_date,docs_name,docs_size,docs_type,docs_comment) VALUES(?,?,?,?,?,?)",
                [$project_id, date('Y-m-d'), $filename, $filesize, 'application/octet-stream', $filereponame ]);
            $docsId = db_insert_id();

            $q = $module->query("INSERT INTO redcap_docs_to_edocs (docs_id,doc_id) VALUES(?,?)", [$docsId, $docId]);
        }
    }
}
?>