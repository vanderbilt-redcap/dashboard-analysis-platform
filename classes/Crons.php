<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/ProjectData.php");
require_once (dirname(__FILE__)."/GraphData.php");
include_once("functions.php");


class Crons
{
    public static function runCacheCron($module,$project_id)
    {
        $row_questions = ProjectData::getRowQuestions();
        $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
        $row_questions_2 = ProjectData::getRowQuestionsResponseRate();
        $graph = array();

        $allData_array = array();
        $allDataTooltip_array = array();
        $allLabel_array = array();

        $custom_filters = $module->getProjectSetting('custom-filter', $project_id);

        $RecordSetMultiple = \REDCap::getData($project_id, 'array');
        $multipleRecords = ProjectData::getProjectInfoArray($RecordSetMultiple);

        #QUESTION = 1
        $question = 1;
        $array_study = array(
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
        $count = 1;
        foreach ($custom_filters as $index => $sstudy) {
            if ($count < 11) {
                $array_study[$sstudy] = "Custom site value " . $count;
            } else {
                break;
            }
            $count++;
        }
        foreach ($array_study as $study => $label) {
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
                $normalStudyCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyCol($question, $project_id, $study_options, $study, $question_1, $conditionDate, $topScoreMax, $indexQuestion, $tooltipTextArray, $array_colors, $max);
                $tooltipTextArray = $normalStudyCol[0];
                $array_colors = $normalStudyCol[1];
                $missingOverall = $normalStudyCol[2];
                $index = $normalStudyCol[4];
                $showLegendNormal = $normalStudyCol[5];

                #MISSING
                $missingCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingCol($question, $project_id, $conditionDate, $multipleRecords, $study, $question_1, $topScoreMax, $indexQuestion, $tooltipTextArray, $array_colors, $index, $max);
                $tooltipTextArray = $missingCol[0];
                $array_colors = $missingCol[1];
                $missing_col = $missingCol[2];
                $graph = $missingCol[4];
                $showLegendMissing = $missingCol[5];

                #OVERALL COL MISSING
                $totalCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalCol($question, $project_id, $question_1, $conditionDate, $topScoreMax, $indexQuestion, $missing_col, $missingOverall, $tooltipTextArray, $array_colors);
                $tooltipTextArray = $totalCol[0];
                $array_colors = $totalCol[1];
                $showLegendTotal = $totalCol[5];
                $allData_array[$question]["nofilter"][$question_1] = $totalCol[1];
                $allDataTooltip_array[$question]["nofilter"][$question_1] = $totalCol[0];

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
            $allLabel_array[$question][$study] = $showLegend;
        }


        #QUESTION = 2
        $question = 2;
        $array_study = array(
            "age" => "Age",
            "ethnicity" => "Ethnicity",
            "gender_identity" => "Gender Identity",
            "race" => "Race",
            "sex" => "Sex"
        );
        foreach ($array_study as $study => $label) {
            $study_options = $module->getChoiceLabels($study, $project_id);
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $study_options);
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study);
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph);
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

                #MULTIPLE
                $multiple = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getResponseRate($graph[$question_2]["multiple"], $graph["total_records"]["multiple"]);
                $array_colors[count($study_options) + 2] = $multiple[0];
                $tooltipTextArray[count($study_options) + 2] = $multiple[1];

                $allData_array[$question][$study][$question_2] = $array_colors;
                $allDataTooltip_array[$question][$study][$question_2] = $tooltipTextArray;
            }
        }


        #QUESTION = 3,4,5
        $array_study = array(
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
        $count = 1;
        foreach ($custom_filters as $index => $sstudy) {
            if ($count < 11) {
                $array_study[$sstudy] = "Custom site value " . $count;
            } else {
                break;
            }
            $count++;
        }
        foreach ($array_study as $study => $label) {
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
                    $normalStudyCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyCol($question, $project_id, $study_options, $study, "rpps_s_q" . $i, $conditionDate, "", $indexQuestion, $tooltipTextArray, $array_colors, "");
                    $index = $normalStudyCol[1];
                    $missingOverall = $normalStudyCol[2];
                    $showLegendNormal = $normalStudyCol[5];
                    $array_colors = $normalStudyCol[6];
                    $tooltipTextArray = $normalStudyCol[7];

                    #MISSING
                    $missingCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingCol($question, $project_id, $conditionDate, $multipleRecords, $study, "rpps_s_q" . $i, "", $indexQuestion, $tooltipTextArray, $array_colors, $index, "");
                    $missing_col = $missingCol[2];
                    $showLegendMissing = $missingCol[3];
                    $array_colors = $missingCol[4];
                    $tooltipTextArray = $missingCol[5];

                    #OVERALL MISSING
                    $totalCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalCol($question, $project_id, "rpps_s_q" . $i, $conditionDate, "", $indexQuestion, $missing_col, $missingOverall, $tooltipTextArray, $array_colors);
                    $showLegendTotal = $totalCol[2];
                    $array_colors = $totalCol[3];
                    $tooltipTextArray = $totalCol[4];
                    $allData_array[$question]["nofilter"]["rpps_s_q" . $i] = $totalCol[3];
                    $allDataTooltip_array[$question]["nofilter"]["rpps_s_q" . $i] = $totalCol[4];

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

        $table_data = array();
        $table_data['data'] = $allData_array;
        $table_data['tooltip'] = $allDataTooltip_array;
        $table_data['legend'] = $allLabel_array;

        if ($table_data != "") {
            #SAVE DATA IN FILE
            #create and save file with json
            $filename = "dashboard_cache_file_" . $project_id . ".txt";
            $storedName = date("YmdHis") . "_pid" . $project_id . "_" . \Vanderbilt\DashboardAnalysisPlatformExternalModule\getRandomIdentifier(6) . ".txt";

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
                [$storedName, $filename, $filesize, 'txt', 'application/octet-stream', '0', $project_id, date('Y-m-d h:i:s')]);
            $docId = db_insert_id();

            //Save document in File Repository
            $q = $module->query("INSERT INTO redcap_docs (project_id,docs_date,docs_name,docs_size,docs_type,docs_comment) VALUES(?,?,?,?,?,?)",
                [$project_id, date('Y-m-d'), $filename, $filesize, 'application/octet-stream', 'Dashboard Cache File']);
            $docsId = db_insert_id();

            $q = $module->query("INSERT INTO redcap_docs_to_edocs (docs_id,doc_id) VALUES(?,?)", [$docsId, $docId]);
        }
    }
}
?>