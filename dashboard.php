<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");
$project_id = $_GET['pid'];

$array_questions = array(
        1 => "Participant perception",
        2 => "Reasons for joining a study",
        3 => "Reasons for leaving a study",
        4 => "Reasons for staying in a study"
);

$array_study = array(
    60 => "Age",
    61 => "Race",
    62 => "Ethnicity",
    59 => "Education level",
    63 => "Sex",
    65 => "Gender",
    16 => "Study type",
    15 => "Disease required",
    66 => "nrollment setting",
    58 => "Demand of study"
);
?>
<div class="optionSelect">
    <div class="alert alert-danger fade in col-md-12" id="errMsgContainerModal" style="display:none"></div>
    <div style="padding-bottom: 10px">
        <select class="form-control" id="question">
            <option value="">Question type</option>
            <?php
            foreach ($array_questions as $index => $squestion){
                $selected = "";
                if($index == $_SESSION[$_GET['pid'] . "_question"]){
                    $selected = "selected";
                }
                echo '<option value="'.$index.'" '.$selected.'>'.$squestion.'</option>';
            }
            ?>
        </select>
        <select class="form-control" id="study">
            <option value="">Study type</option>
            <?php
            foreach ($array_study as $index => $sstudy){
                $selected = "";
                if($index == $_SESSION[$_GET['pid'] . "_study"]){
                    $selected = "selected";
                }
                echo '<option value="'.$index.'" '.$selected.'>'.$sstudy.'</option>';
            }
            ?>
            <option value="">RPPS administration timing</option>
            <option value="">RPPS sampling approach</option>
        </select>
    <button onclick='loadTable(<?=json_encode($module->getUrl("loadTable.php"))?>);' class="btn btn-primary" style="float:right" id="loadTablebtn">Load Table</button>
</div>
<div class="optionSelect" style="padding-top: 20px" id="loadTable">
    <?php
    if(!empty($_GET['dash']) && ProjectData::startTest($_GET['dash'], $secret_key, $secret_iv, $_SESSION[$project_id."_dash_timestamp"])) {
        $project_id = $_GET['pid'];
        $question = $_SESSION[$_GET['pid'] . "_question"];
        $study = $_SESSION[$_GET['pid'] . "_study"];
        $row_questions = array(2 => "2-15", 3 => "26-39", 4 => "40-55");
        $row_questions_1 = array(0 => "rpps_s_q1",1 => "rpps_s_q66", 2 => "rpps_s_q17", 3 => "rpps_s_q18", 4 => "rpps_s_q19", 5 => "rpps_s_q20", 6 => "rpps_s_q21",
                            7 => "rpps_up_q66", 8 => "rpps_s_q22", 9 => "rpps_s_q23", 10 => "rpps_s_q24", 11 => "rpps_s_q25", 12 => "rpps_up_q65",
                            13 => "rpps_up_q67", 14 => "rpps_s_q57");
        $study_options = $module->getChoiceLabels("rpps_s_q" . $study, $project_id);

        $table = '<table class="table table-bordered pull-left" id="table_archive"><thead>
            <tr><th class="question"><strong>% Top score</strong></th>';
        foreach ($study_options as $col_title) {
            $table .= '<th>' . $col_title . '</th>';
        }
        $table .= '<th>MISSING</th>';
        $table .= '</tr>';

        if ($question == 1) {
            $array_colors = array();
            $max = 0;
            foreach ($row_questions_1 as $indexQuestion => $question_1) {
                $array_colors[$indexQuestion] = array();
                foreach ($study_options as $index => $col_title) {
                    $outcome_labels = $module->getChoiceLabels($question_1, $project_id);
                    $topScoreMax = count($outcome_labels);
                    $condition = getParamOnType("rpps_s_q" . $study,$index);

                    $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $condition);
                    $records = ProjectData::getProjectInfoArray($RecordSet);

                    $topScoreFound = 0;
                    $score_is_5 = 0;
                    foreach ($records as $record){
                        if(isTopScore($record[$question_1],$topScoreMax)) {
                            $topScoreFound += $record[$question_1];
                        }
                        if($record[$question_1] == 5 && $topScoreMax == 5){
                            $score_is_5 += 1;
                        }
                    }
                    if($topScoreFound > 0){
                        $topScore = number_format(($topScoreFound/(count($records)-$score_is_5)*100),0);
                    }else{
                        $topScore = 0;
                    }
                    $array_colors[$indexQuestion][$index] = $topScore;
                    if($topScore > $max){
                        $max = $topScore;
                    }
                }
                #MISSING
                $RecordSetMissing = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false,
                    "[".$question_1."] != ''"
                );
                $missingRecords = ProjectData::getProjectInfoArray($RecordSetMissing);
                $missing = 0;
                foreach ($missingRecords as $mrecord){
                    if(($mrecord["rpps_s_q" . $study] == '') || (is_array($mrecord["rpps_s_q" . $study]) && array_count_values($mrecord["rpps_s_q" . $study])[1] == 0)){
                        $missing += 1;
                    }
                }
                if($missing > $max){
                    $max = $missing;
                }

                $array_colors[$indexQuestion][$index+1] = $missing;
            }
            #COLOR
            foreach ($row_questions_1 as $indexQuestion => $question_1) {
                $table .= '<tr><td class="question">'.$module->getFieldLabel($question_1).'</td>';
                for ($i = 1;$i<count($study_options)+2;$i++) {
                    $percent = ($array_colors[$indexQuestion][$i]/($max))*100;
                    $color = GetColorFromRedYellowGreenGradient($percent);
                    $table .= '<td style="background-color:'.$color.'">'.$array_colors[$indexQuestion][$i].'</td>';
                }
                $table .= '</tr>';
            }
        }else {
            $option = explode("-",$row_questions[$question]);
            for($i=$option[0];$i<$option[1];$i++) {
                $table .= '<tr><td class="question">' . $module->getFieldLabel("rpps_s_q".$i).'</td>';
                foreach ($study_options as $index => $col_title) {
                    $condition = getParamOnType("rpps_s_q" . $study,$index);

                    $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false,$condition);
                    $records = ProjectData::getProjectInfoArray($RecordSet);

                    $topScoreFound = 0;
                    foreach ($records as $record){
                        if(isTopScoreVeryOrSomewhatImportant($record["rpps_s_q".$i])) {
                            $topScoreFound += 1;
                        }
                    }
                    if($topScoreFound > 0){
                        $topScore = number_format(($topScoreFound/count($records)*100),0);
                    }else{
                        $topScore = 0;
                    }
                    $table .= '<td>'.$topScore.'</td>';
                }
                #MISSING
                $RecordSetMissing = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false,
                    "[".$i."] != ''"
                );
                $missingRecords = ProjectData::getProjectInfoArray($RecordSetMissing);
                $missing = 0;
                foreach ($missingRecords as $mrecord){
                    if(($mrecord["rpps_s_q" . $study] == '') || (is_array($mrecord["rpps_s_q" . $study]) && array_count_values($mrecord["rpps_s_q" . $study])[1] == 0)){
                        $missing += 1;
                    }
                }
                $table .= '<td>'.$missing.'</td>';
                $table .= '</tr>';
            }
            $table .= '</tr>';
        }
        $table .= '</table>';
        echo $table;
    }
    ?>
</div>