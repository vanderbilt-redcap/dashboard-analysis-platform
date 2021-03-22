<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");
$project_id = $_GET['pid'];

$daterange = $_SESSION[$_GET['pid'] . "_startDate"]." - ".$_SESSION[$_GET['pid'] . "_endDate"];
if(($_SESSION[$_GET['pid'] . "_startDate"] == "" || $_SESSION[$_GET['pid'] . "_startDate"] == "") || (empty($_GET['dash']) || !empty($_GET['dash'])) && !ProjectData::startTest($_GET['dash'], '', '', $_SESSION[$project_id."_dash_timestamp"])){
    $daterange = "Select a date range...";
    if($_SESSION[$_GET['pid'] . "_question"] == "" || $_SESSION[$_GET['pid'] . "_study"] == "" || empty($_GET['dash'])){
        $_SESSION[$_GET['pid'] . "_question"] = "1";
        $_SESSION[$_GET['pid'] . "_study"] = "nofilter";
    }
}

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
<script>
    $( document ).ready(function() {
        var maxWidthth = Math.max.apply(null, $('.dal>thead th.dal_task>div').map(function() {
            return $(this).outerWidth(true);
        }).get());
        $('.dal>thead th.dal_task>div').width(maxWidthth+30);
        $('.dal').css('margin-top', maxWidthth * .75);

        function RGBToHSL(rgb) {
            //If grey do not convert
            if(rgb != "196,196,196") {
                var rgb = rgb.split(",");

                r = rgb[0];
                g = rgb[1];
                b = rgb[2];

                r /= 255;
                g /= 255;
                b /= 255;

                let cmin = Math.min(r, g, b),
                    cmax = Math.max(r, g, b),
                    delta = cmax - cmin,
                    h = 0,
                    s = 0,
                    l = 0;

                if (delta == 0)
                    h = 0;
                // Red is max
                else if (cmax == r)
                    h = ((g - b) / delta) % 6;
                // Green is max
                else if (cmax == g)
                    h = (b - r) / delta + 2;
                // Blue is max
                else
                    h = (r - g) / delta + 4;

                h = Math.round(h * 60);

                // Make negative hues positive behind 360°
                if (h < 0)
                    h += 360;

                l = (cmax + cmin) / 2;

                // Calculate saturation
                s = delta == 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));

                // Multiply l and s by 100
                s = +(s * 100).toFixed(1);
                l = +(l * 100).toFixed(1);

                return "hsl(" + h + ",70%," + (l + 13) + "%)";
            }
        }

        $('tr td:nth-of-type(1) ~ td').each(function(index, value) {
            clr = $(this).css("background-color");
            clr = clr.replace(/ /g, '', clr);
            clr = clr.replace(')', '', clr);
            if (clr != 'rgba(0,0,0,0') {
                $(this).css('background-color', RGBToHSL(clr.replace('rgb(', '', clr)));
            }
            $(this).addClass('perc');
        });

        $('[data-toggle="popover"]').popover({
            container: 'body'
        })
    });
</script>
<div class="optionSelect">
    <div class="alert alert-danger fade in col-md-12" id="errMsgContainerModal" style="display:none"></div>
    <div style="padding-bottom: 10px">
        <div>
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
                <option value="nofilter" selected>No filter</option>
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
            <input type="daterange" class="form-control" id="daterange" name="daterange" value="<?=$daterange?>">
            <button onclick='loadTable(<?=json_encode($module->getUrl("loadTable.php"))?>);' class="btn btn-primary" id="loadTablebtn">Load Table</button>
        </div>
    </div>
</div>
<?php
if(!empty($_GET['dash']) && ProjectData::startTest($_GET['dash'], '', '', $_SESSION[$project_id."_dash_timestamp"]) || ($_SESSION[$_GET['pid'] . "_study"] == "nofilter" && $_SESSION[$_GET['pid'] . "_question"] == "1")) {
    $project_id = $_GET['pid'];
    $question = $_SESSION[$_GET['pid'] . "_question"];
    $study = $_SESSION[$_GET['pid'] . "_study"];
    $row_questions = array(2 => "2-15", 3 => "26-39", 4 => "40-55");
    $row_questions_1 = array(0 => "rpps_s_q1",1 => "rpps_s_q17", 2 => "rpps_s_q18", 3 => "rpps_s_q19", 4 => "rpps_s_q20", 5 => "rpps_s_q21",
                        6 => "rpps_up_q66", 7 => "rpps_s_q22", 8 => "rpps_s_q23", 9 => "rpps_s_q24", 10 => "rpps_s_q25", 11 => "rpps_up_q65",
                        12 => "rpps_up_q67", 13 => "rpps_s_q57");
    $study_options = $module->getChoiceLabels("rpps_s_q" . $study, $project_id);
    $graph_top_score = array();
    $graph_top_score_year = array();
    $graph_top_score_month = array();
    $graph_top_score_quarter = array();
    $years = array();
    if($_SESSION[$_GET['pid'] . "_startDate"] != ""){
        $startDate = date("Y-m-d",strtotime($_SESSION[$_GET['pid'] . "_startDate"]));
    }else{
        $startDate = "";
    }
    if($_SESSION[$_GET['pid'] . "_endDate"] != ""){
        $endDate = date("Y-m-d",strtotime($_SESSION[$_GET['pid'] . "_endDate"]));
    }else{
        $endDate = "";
    }

    $conditionDate = "";
    if($endDate != "" && $startDate != ""){
        $conditionDate = " AND [survey_datetime] >= '".$startDate. "' AND [survey_datetime] <= '".$endDate."'";
    }

    $RecordSetMissingStudy = \REDCap::getData($project_id, 'array', null, null, null, null, false, false, false,
        "[rpps_s_q" . $study."] = ''".$conditionDate
    );
    $missingStudyTotal = count(ProjectData::getProjectInfoArray($RecordSetMissingStudy));

    $score_title = "% Responding Very or Somewhat Important";
    if ($question == 1) {
        $score_title = "Top Box Score";
    }
    $top_box_popover_content = "<div style='padding-bottom: 20px'><strong>Definitions:</strong> “Top box” scores are created by calculating the percentage of survey respondents who chose the most positive score for a given item response scale.  A variation, the  'Top 2 box' score is the total of the top two categories in a table. For example, the overall Rating question is scores using a Top 2 Box score; participants reply using a numeric scale from “0 (Worst)” to “10 (Best)”.  The Top 2 Box scores based on the combination of the responses “9” and “10”.</div>
                                <div><strong>Top-Box versus mean scores:</strong> Top Box scores are easy to understand because they clearly identify the respondents who fall into a given category, usually the optimal category from a marketing or customer experience perspective. However, taken alone, Top Box scores leave out important information about how many respondents were close to, or far from the top category. In contrast, mean scores provide a summary measure that includes all the data, including lower and lowest scores, but it may be harder to interpret the significance of a 0.1  or 1 or 5 point difference between two mean scores, especially if the sample size is large. The frequency distribution of all of the response data provides the richest data about the range and number of responses, but lacks the simplicity of summary scores. It can be most valuable to use summary scores like Top Box or Means to follow overall trends, and frequency distributions to understand the details when designing interventions for change. When assessing change in a score, it is important to compare measures of the same type.</div>";
    $table = '<div class="optionSelect" style="padding-top: 20px" id="loadTable">
                <table class="table dal table-bordered pull-left" id="table_archive">
                <thead>
                    <tr>
                    <th class="question"><span style="position: relative; top:23px"><strong>'.$score_title.'</strong> <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="'.$top_box_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a></span></th>'.
                    '<th class="dal_task"><div style="width: 197.719px;"><span>TOTAL</span></div></th>';
    if($study != "nofilter") {
        if ($study == 62) {
            foreach ($study_options as $indexstudy => $col_title) {
                $class = "";
                $attibute = "";
                if ($indexstudy == 5) {

                } else if ($indexstudy != 1) {
                    $class = "hide";
                    $attibute = "etnicity='1'";
                }
                $table .= '<th class="dal_task ' . $class . '" ' . $attibute . '><div style="width: 197.719px;"><span>' . $col_title . '</span></div></th>';
            }
        } else {
            foreach ($study_options as $indexstudy => $col_title) {
                $table .= '<th class="dal_task"><div style="width: 197.719px;"><span>' . $col_title . '</span></div></th>';
            }
        }
        $table .= '<th class="dal_task"><div style="width: 197.719px;"><span>NO ' . strtoupper($array_study[$study]) . ' REPORTED</span></div></th>';

        if ($study == 61) {
            $table .= '<th class="dal_task"><div style="width: 197.719px;"><span>MULTIPLE</span></div></th>';
        }
    }
    $table .= '</tr>';

        $table .= '<tr>';
        $table .= '<td class="question"></td>';
        $table .= '<td></td>';
        foreach ($study_options as $indexstudy => $col_title) {
            if($study == 62) {
                $table .="<style>.dal_task>div>span {
                            display: block;
                            margin-left: 38px;
                            color: #5592c6;
                        }</style>";
                if ($indexstudy == 5) {
                    $table .= '<td><i class="fas fa-plus-circle fa-fw" id="etnicityPlus" aria-hidden="true" onclick="etnicity_change_icon(this.id)" symbol="0"></i></td>';
                } else if ($indexstudy != 1) {
                    $table .= '<td class="hide" etnicity="1"></td>';
                } else if ($indexstudy == 1) {
                    $table .= '<td></td>';
                }
            }else{
                $table .="<style>.dal_task>div>span {
                            display: block;
                            margin-left: 20px;
                            color: #5592c6;
                        }</style>";
                $table .= '<td>&nbsp;</td>';
            }
        }
        $table .= '<td></td>';
        $table .= '</tr>';

    $table .= '</thead>';

    $RecordSetMultiple = \REDCap::getData($project_id, 'array');
    $multipleRecords = ProjectData::getProjectInfoArray($RecordSetMultiple);

    if ($question == 1) {
        $array_colors = array();
        $max = 0;
        foreach ($row_questions_1 as $indexQuestion => $question_1) {
            $array_colors[$indexQuestion] = array();
            $tooltipTextArray[$indexQuestion] = array();
            $outcome_labels = $module->getChoiceLabels($question_1, $project_id);
            $topScoreMax = count($outcome_labels);
            $missingOverall = 0;

            #NORMAL STUDY
            $normalStudyCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyCol($question,$project_id, $study_options,$study,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$tooltipTextArray,$array_colors,$max);
            $tooltipTextArray = $normalStudyCol[0];
            $array_colors = $normalStudyCol[1];
            $missingOverall = $normalStudyCol[2];
            $max = $normalStudyCol[3];
            $index = $normalStudyCol[4];

            #MISSING
            $missingCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingCol($question,$project_id, $conditionDate, $multipleRecords,$study,$question_1, $topScoreMax,$indexQuestion,$tooltipTextArray, $array_colors,$index,$max);
            $tooltipTextArray = $missingCol[0];
            $array_colors = $missingCol[1];
            $missing_col = $missingCol[2];
            $max = $missingCol[3];

            #OVERALL COL MISSING
            $totalCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalCol($question, $project_id,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$missing_col,$missingOverall,$tooltipTextArray,$array_colors);
            $tooltipTextArray = $totalCol[0];
            $array_colors = $totalCol[1];

            #MULTIPLE
            if($study == 61) {
                $multipleCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleCol($question,$multipleRecords,$study,$question_1,$topScoreMax,$indexQuestion,$index,$tooltipTextArray, $array_colors);
                $tooltipTextArray = $multipleCol[0];
                $array_colors = $multipleCol[1];
            }
        }
        #COLOR
        $extras = 2;
        if($study == 61) {
            $extras = 3;
        }
        foreach ($row_questions_1 as $indexQuestion => $question_1) {
            $question_popover_content = \Vanderbilt\DashboardAnalysisPlatformExternalModule\returnTopScoresLabels($question_1,$module->getChoiceLabels($question_1, $project_id));
            $table .= '<tr><td class="question">'.$module->getFieldLabel($question_1).' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" title="Field: ['.$question_1.']" data-content="'.$question_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a></td>';
            for ($i = 0;$i<count($study_options)+$extras;$i++) {
                if(($array_colors[$indexQuestion][$i] == "-" || $array_colors[$indexQuestion][$i] == "<5") && $array_colors[$indexQuestion][$i] != "0"){
                    $color = "#c4c4c4";
                }else{
                    $percent = ($array_colors[$indexQuestion][$i]/($max))*100;
                    $color = \Vanderbilt\DashboardAnalysisPlatformExternalModule\GetColorFromRedYellowGreenGradient($percent);
                }
                $class = "";
                $attibute = "";
                if($study == 62 && $i > 1 && $i < 5){
                    $class = "hide";
                    $attibute = "etnicity = '1'";
                }
                if($study != "nofilter" || ($study == "nofilter" && $i == 0)) {
                    $table .= '<td style="background-color:' . $color . '" class="' . $class . '" ' . $attibute . '><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="' . $tooltipTextArray[$indexQuestion][$i] . '">' . $array_colors[$indexQuestion][$i] . '</div></td>';
                }
            }
            $table .= '</tr>';
        }
    }else {
        $option = explode("-",$row_questions[$question]);
        for($i=$option[0];$i<$option[1];$i++) {
            $question_popover_content = \Vanderbilt\DashboardAnalysisPlatformExternalModule\returnTopScoresLabels("rpps_s_q".$i,$module->getChoiceLabels("rpps_s_q".$i, $project_id));
            $table .= '<tr><td class="question">' . $module->getFieldLabel("rpps_s_q".$i).' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" title="Field: ['."rpps_s_q".$i.']" data-content="'.$question_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a></td>';
            $missingOverall = 0;

            #NORMAL STUDY
            $normalStudyCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyCol($question,$project_id, $study_options,$study,"rpps_s_q".$i,$conditionDate,"","","","","");
            $table_b = $normalStudyCol[0];
            $index = $normalStudyCol[1];
            $missingOverall = $normalStudyCol[2];

            #MISSING
            $missingCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingCol($question,$project_id, $conditionDate, $multipleRecords,$study,"rpps_s_q".$i, "","","", "",$index,"");
            $missing_col = $missingCol[2];
            $table_b .= '<td><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$missingCol[1].'">'.$missingCol[0].'</div></td>';

            #OVERAL MISSING
            $totalCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalCol($question, $project_id,"rpps_s_q".$i,$conditionDate,"","",$missing_col,$missingOverall,"","");
            $table .= '<td><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$totalCol[1].'">'.$totalCol[0].'</div></td>';
            $table .= $table_b;

            #MULTIPLE
            if($study == 61) {
                $multiple = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleCol($question,$multipleRecords,$study,"rpps_s_q".$i,"","",$index,"", "");
                $table .= '<td><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$multiple[1].'">'.$multiple[0].'</div></td>';

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
    ksort($years);
    $labels_quarter = array();
    $graph_top_score_quarter_values = array();
    foreach ($years as $year => $valY) {
        foreach ($graph_top_score_quarter as $date => $value) {
            $year_quarter = explode(" ", $date)[1];
            if($year_quarter == $year){
                array_push($graph_top_score_quarter_values, $value);
                array_push($labels_quarter, $date);
            }


        }
    }
    ?>
    <script>
        $(function () {
            //var labels_year = <?//=json_encode($labels_year)?>//;
            //var labels_month = <?//=json_encode($labels_month)?>//;
            //var labels_quarter = <?//=json_encode($labels_quarter)?>//;
            //
            //var results_year = <?//=json_encode($graph_top_score_year)?>//;
            //var results_month = <?//=json_encode($graph_top_score_month_values)?>//;
            //var results_quarter = <?//=json_encode($graph_top_score_quarter_values)?>//;
            //
            //var  ctx_dash = $("#DashChart");
            //var config_dash = {
            //    type: 'line',
            //    data: {
            //        labels: labels_month,
            //        datasets: [
            //            {
            //                label:'Data',
            //                fill: false,
            //                borderColor:'#337ab7',
            //                backgroundColor:'#337ab7',
            //                data:results_month
            //            }
            //        ]
            //    },
            //    options: {
            //        elements: {
            //            line: {
            //                tension: 0, // disables bezier curves
            //            }
            //        },
            //        tooltips: {
            //            mode:'index',
            //            intersect: false
            //        }
            //    }
            //}
            //
            //var dash_chart = new Chart(ctx_dash, config_dash);
            //
            //$('[data-toggle="tooltip"]').tooltip();
            //
            //$("#options td").click(function(){
            //    if($(this).attr('id') == "month"){
            //        $('#quarter').removeClass('selected');
            //        $('#year').removeClass('selected');
            //        $('#month').addClass('selected');
            //        dash_chart.data.labels = labels_month;
            //        dash_chart.data.datasets[0].data = results_month;
            //        dash_chart.update();
            //    }else if($(this).attr('id') == "quarter"){
            //        $('#month').removeClass('selected');
            //        $('#year').removeClass('selected');
            //        $('#quarter').addClass('selected');
            //        dash_chart.data.labels = labels_quarter;
            //        dash_chart.data.datasets[0].data = results_quarter;
            //        dash_chart.update();
            //    }else if($(this).attr('id') == "year"){
            //        $('#quarter').removeClass('selected');
            //        $('#month').removeClass('selected');
            //        $('#year').addClass('selected');
            //        dash_chart.data.labels = labels_year;
            //        dash_chart.data.datasets[0].data = results_year;
            //        dash_chart.update();
            //    }
            //});
        });
    </script>
    <div class="optionSelect">
        <div class="pull-left">
<!--            <canvas id="DashChart" class="canvas_statistics" height="400px" width="700px"></canvas>-->
        </div>
        <?php
           /* echo "<table class='table table-bordered pull-right' id='options'>
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
           */
        }
        ?>
    </div>