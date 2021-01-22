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
    66 => "Enrollment setting",
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
</div>
<?php
if(!empty($_GET['dash']) && ProjectData::startTest($_GET['dash'], $secret_key, $secret_iv, $_SESSION[$project_id."_dash_timestamp"])) {
    $project_id = $_GET['pid'];
    $question = $_SESSION[$_GET['pid'] . "_question"];
    $study = $_SESSION[$_GET['pid'] . "_study"];
    $row_questions = array(2 => "2-15", 3 => "26-39", 4 => "40-55");
    $row_questions_1 = array(0 => "rpps_s_q1",1 => "rpps_s_q17", 2 => "rpps_s_q18", 3 => "rpps_s_q19", 4 => "rpps_s_q20", 5 => "rpps_s_q21",
                        6 => "rpps_up_q66", 7 => "rpps_s_q22", 8 => "rpps_s_q23", 0 => "rpps_s_q24", 10 => "rpps_s_q25", 11 => "rpps_up_q65",
                        12 => "rpps_up_q67", 13 => "rpps_s_q57");
    $study_options = $module->getChoiceLabels("rpps_s_q" . $study, $project_id);
    $graph_top_score = array();
    $graph_top_score_year = array();
    $graph_top_score_month = array();

    $score_title = "% Responding Very or Somewhat Important";
    if ($question == 1) {
        $score_title = "% Best score";
    }
    $table = '<div class="optionSelect" style="padding-top: 20px" id="loadTable">
                <table class="table table-bordered pull-left" id="table_archive">
                <thead>
                    <tr>
                    <th class="question"><strong>'.$score_title.'</strong></th>'.
                    '<th>OVERALL</th>';
    foreach ($study_options as $col_title) {
        $table .= '<th>' . $col_title . '</th>';
    }
    $table .= '<th>MISSING</th>';

    if($study == 61){
        $table .= '<th>MULTIPLE</th>';
        $RecordSetMultiple = \REDCap::getData($project_id, 'array');
        $multipleRecords = ProjectData::getProjectInfoArray($RecordSetMultiple);
    }
    $table .= '</tr>';

    if ($question == 1) {
        $array_colors = array();
        $max = 0;
        foreach ($row_questions_1 as $indexQuestion => $question_1) {
            $array_colors[$indexQuestion] = array();
            $tooltipTextArray[$indexQuestion] = array();
            $outcome_labels = $module->getChoiceLabels($question_1, $project_id);
            $topScoreMax = count($outcome_labels);

            #OVERALL
            $RecordSetOverall = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] <> ''");
            $recordsoverall = ProjectData::getProjectInfoArray($RecordSetOverall);

            $RecordSetOverall5 = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] = '5'");
            $score_is_5O = count(ProjectData::getProjectInfoArray($RecordSetOverall5));

            $RecordSetMissingOverall = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[".$question_1."] = ''");
            $missingO = count(ProjectData::getProjectInfoArray($RecordSetMissingOverall));

            $topScoreFoundO = 0;
            foreach ($recordsoverall as $recordo){
                if(\FunctionsDAP\isTopScore($recordo[$question_1],$topScoreMax,$question_1)) {
                    $topScoreFoundO += 1;
                }
            }
            $overall = 0;
            if($topScoreFoundO > 0){
                $overall = number_format(($topScoreFoundO/(count($recordsoverall)-$score_is_5O)*100),0);
            }
            $tooltipTextArray[$indexQuestion][0]  = count($recordsoverall)." responses, ".$missingO." missing, ".$score_is_5O." not applicable";
            $array_colors[$indexQuestion][0] = $overall;

            #NORMAL STUDY
            foreach ($study_options as $index => $col_title) {
                $condition = \FunctionsDAP\getParamOnType("rpps_s_q" . $study,$index);

                $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, $condition);
                $records = ProjectData::getProjectInfoArray($RecordSet);

                $topScoreFound = 0;
                $score_is_5 = 0;
                $missing_InfoLabel = 0;
                foreach ($records as $record){
                    if(\FunctionsDAP\isTopScore($record[$question_1],$topScoreMax,$question_1)) {
                        $topScoreFound += 1;
                        $graph_top_score[date("Y-m",strtotime($record['survey_datetime']))] += 1;
                        $graph_top_score_year[date("Y",strtotime($record['survey_datetime']))] += 1;
                        $graph_top_score_month[strtotime(date("Y-m",strtotime($record['survey_datetime'])))] += 1;
                    }
                    if($record[$question_1] == 5 && $topScoreMax == 5){
                        $score_is_5 += 1;
                    }
                    if($record[$question_1] == '' || !array_key_exists($question_1,$record)){
                        $missing_InfoLabel += 1;
                    }
                }
                $tooltipTextArray[$indexQuestion][$index]  = count($records)." responses, ".$missing_InfoLabel." missing, ".$score_is_5." not applicable";
                if($topScoreFound > 0){
                    $topScore = number_format(($topScoreFound/(count($records)-$score_is_5-$missing_InfoLabel)*100),0);
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

            #MULTIPLE
            if($study == 61) {
                $multiple = 0;
                foreach ($multipleRecords as $multirecord){
                    if(\FunctionsDAP\isTopScore($multirecord[$question_1],$topScoreMax,$question_1) && array_count_values($multirecord["rpps_s_q" . $study])[1] == 0){
                        $multiple += 1;
                    }
                }
                $array_colors[$indexQuestion][$index+2] = $multiple;
            }
        }
        #COLOR
        $extras = 2;
        if($study == 61) {
            $extras = 3;
        }
        foreach ($row_questions_1 as $indexQuestion => $question_1) {
            $table .= '<tr><td class="question">'.$module->getFieldLabel($question_1).'</td>';
            for ($i = 0;$i<count($study_options)+$extras;$i++) {
                $percent = ($array_colors[$indexQuestion][$i]/($max))*100;
                $color = \FunctionsDAP\GetColorFromRedYellowGreenGradient($percent);
                $table .= '<td style="background-color:'.$color.'"><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$tooltipTextArray[$indexQuestion][$i].'">'.$array_colors[$indexQuestion][$i].'</div></td>';
            }
            $table .= '</tr>';
        }
    }else {
        $option = explode("-",$row_questions[$question]);
        for($i=$option[0];$i<$option[1];$i++) {
            $table .= '<tr><td class="question">' . $module->getFieldLabel("rpps_s_q".$i).'</td>';
            #OVERALL
            $RecordSetOverall = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[rpps_s_q".$i."] <> ''");
            $recordsoverall = ProjectData::getProjectInfoArray($RecordSetOverall);

            $RecordSetMissingOverall = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false, "[rpps_s_q".$i."] = ''");
            $missingO = count(ProjectData::getProjectInfoArray($RecordSetMissingOverall));

            $topScoreFoundO = 0;
            $notTopScoreFoundO = 0;
            foreach ($recordsoverall as $recordo){
                if(\FunctionsDAP\isTopScoreVeryOrSomewhatImportant($recordo["rpps_s_q".$i])) {
                    $topScoreFoundO += 1;
                }else{
                    $notTopScoreFoundO += 1;
                }
            }
            $overall = 0;
            if($topScoreFoundO > 0){
                $overall = number_format(($topScoreFoundO/(count($recordsoverall))*100),0);
            }
            $tooltipText = count($recordsoverall)." responses, ".$missingO." missing";
            $table .= '<td><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$tooltipText.'">'.$overall.'</div></td>';

            #NORMAL STUDY
            foreach ($study_options as $index => $col_title) {
                $condition = \FunctionsDAP\getParamOnType("rpps_s_q" . $study,$index);

                $RecordSet = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false,$condition);
                $records = ProjectData::getProjectInfoArray($RecordSet);

                $topScoreFound = 0;
                $missing_InfoLabel = 0;
                foreach ($records as $record){
                    if(\FunctionsDAP\isTopScoreVeryOrSomewhatImportant($record["rpps_s_q".$i])) {
                        $topScoreFound += 1;
                        $graph_top_score[date("Y-m",strtotime($record['survey_datetime']))] += 1;
                        $graph_top_score_year[date("Y",strtotime($record['survey_datetime']))] += 1;
                        $graph_top_score_month[strtotime(date("Y-m",strtotime($record['survey_datetime'])))] += 1;
                    }
                    if($record["rpps_s_q".$i] == '' || !array_key_exists("rpps_s_q".$i,$record)){
                        $missing_InfoLabel += 1;
                    }
                }

                if($topScoreFound > 0){
                    $topScore = number_format(($topScoreFound/(count($records)-$missing_InfoLabel)*100),0);
                }else{
                    $topScore = 0;
                }
                $tooltipText = count($records)." responses, ".$missing_InfoLabel." missing";
                $table .= '<td><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$tooltipText.'">'.$topScore.'</div></td>';
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

            #MULTIPLE
            if($study == 61) {
                $multiple = 0;
                foreach ($multipleRecords as $multirecord){
                    if(\FunctionsDAP\isTopScoreVeryOrSomewhatImportant($multirecord["rpps_s_q".$i]) && array_count_values($multirecord["rpps_s_q" . $study])[1] == 0){
                        $multiple += 1;
                    }
                }
                $table .= '<td>'.$multiple.'</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</tr>';
    }
    $table .= '</table></div>';
    echo $table;

    #YEAR
    ksort($graph_top_score_year);
    $labels_year = array_keys($graph_top_score_year);
    $graph_top_score_year = array_values($graph_top_score_year);

    #MONTH
    ksort($graph_top_score_month);
    $labels_month = array();
    $graph_top_score_month_values = array();
    foreach($graph_top_score_month as $date => $value){
        array_push($labels_month,date("Y-m",$date));
        array_push($graph_top_score_month_values,$value);
    }

    #QUARTER

    ?>
    <script>
        $(function () {
            var labels_year = <?=json_encode($labels_year)?>;
            var labels_month = <?=json_encode($labels_month)?>;
            var labels_quarter= ["Q1","Q2","Q3","Q4"];

            var results_year = <?=json_encode($graph_top_score_year)?>;
            var results_month = <?=json_encode($graph_top_score_month_values)?>;
            var results_quarter = <?=json_encode($graph_top_score_year)?>;

            var  ctx_dash = $("#DashChart");
            var config_dash = {
                type: 'line',
                data: {
                    labels: labels_month,
                    datasets: [
                        {
                            label:'Data',
                            fill: false,
                            borderColor:'#337ab7',
                            backgroundColor:'#337ab7',
                            data:results_month
                        }
                    ]
                },
                options: {
                    elements: {
                        line: {
                            tension: 0, // disables bezier curves
                        }
                    },
                    tooltips: {
                        mode:'index',
                        intersect: false
                    }
                }
            }

            var dash_chart = new Chart(ctx_dash, config_dash);

            $('[data-toggle="tooltip"]').tooltip();

            $("#options td").click(function(){
                if($(this).attr('id') == "month"){
                    $('#quarter').removeClass('selected');
                    $('#year').removeClass('selected');
                    $('#month').addClass('selected');
                    dash_chart.data.labels = labels_month;
                    dash_chart.data.datasets[0].data = results_month;
                    dash_chart.update();
                }else if($(this).attr('id') == "quarter"){
                    $('#month').removeClass('selected');
                    $('#year').removeClass('selected');
                    $('#quarter').addClass('selected');
                    dash_chart.data.labels = labels_quarter;
                    dash_chart.data.datasets[0].data = results_quarter;
                    dash_chart.update();
                }else if($(this).attr('id') == "year"){
                    $('#quarter').removeClass('selected');
                    $('#month').removeClass('selected');
                    $('#year').addClass('selected');
                    dash_chart.data.labels = labels_year;
                    dash_chart.data.datasets[0].data = results_year;
                    dash_chart.update();
                }
            });
        });
    </script>
    <div class="optionSelect">
        <div class="pull-left">
            <canvas id="DashChart" class="canvas_statistics" height="400px" width="700px"></canvas>
        </div>
        <?php
            echo "<table class='table table-bordered pull-right' id='options'>
                        <tr>
                            <td class='selected' id='month'>Month</td>
                        </tr>
                        <tr>
                            <td id='quarter'>Quarter</td>
                        </tr>
                         <tr>
                            <td id='year'>Year</td>
                        </tr>
                  </table>";
        }
        ?>
    </div>