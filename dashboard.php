<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");
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
        2 => "Response/Completion Rates",
        3 => "Reasons for joining a study",
        4 => "Reasons for leaving a study",
        5 => "Reasons for staying in a study"
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

$array_colors_graphs = array(0=>"337ab7",1=>"F8BD7F",2=>"EF3054",3=>"43AA8B",4=>"BD93D8",5=>"3F386B",6=>"A23F47",7=>"DE7CBC",8=>"CA3C25",9=>"B3DEE2");
?>
<script>
    $( document ).ready(function() {
        $('.forme').width($('table.dal thead tr .question:first-of-type').width());
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
<div class="optionSelect" style="margin-bottom: 0px;">
    <div class="alert alert-danger fade in col-md-12" id="errMsgContainerModal" style="display:none"></div>
</div>
<?php
if(!empty($_GET['dash']) && ProjectData::startTest($_GET['dash'], '', '', $_SESSION[$project_id."_dash_timestamp"]) || ($_SESSION[$_GET['pid'] . "_study"] == "nofilter" && $_SESSION[$_GET['pid'] . "_question"] == "1")) {
    $project_id = $_GET['pid'];
    $question = $_SESSION[$_GET['pid'] . "_question"];
    $study = $_SESSION[$_GET['pid'] . "_study"];
    $row_questions = ProjectData::getRowQuestions();
    $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
    $row_questions_2 = ProjectData::getRowQuestionsResponseRate();
    $study_options = $module->getChoiceLabels("rpps_s_q" . $study, $project_id);
    $graph = array();

    if($study == 62){
        array_push($study_options,"Yes - Spanish/Hispanic/Latino");
    }
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

    $score_title = "% Responding Very or Somewhat Important";
    $top_box_popover_info = '';
    if ($question == 1) {
        $score_title = "Top Box Score";
        $top_box_popover_content = "<div style='padding-bottom: 20px'><strong>Definitions:</strong> “Top box” scores are created by calculating the percentage of survey respondents who chose the most positive score for a given item response scale.  A variation, the  'Top 2 box' score is the total of the top two categories in a table. For example, the overall Rating question is scores using a Top 2 Box score; participants reply using a numeric scale from “0 (Worst)” to “10 (Best)”.  The Top 2 Box scores based on the combination of the responses “9” and “10”.</div>
                                <div><strong>Top-Box versus mean scores:</strong> Top Box scores are easy to understand because they clearly identify the respondents who fall into a given category, usually the optimal category from a marketing or customer experience perspective. However, taken alone, Top Box scores leave out important information about how many respondents were close to, or far from the top category. In contrast, mean scores provide a summary measure that includes all the data, including lower and lowest scores, but it may be harder to interpret the significance of a 0.1  or 1 or 5 point difference between two mean scores, especially if the sample size is large. The frequency distribution of all of the response data provides the richest data about the range and number of responses, but lacks the simplicity of summary scores. It can be most valuable to use summary scores like Top Box or Means to follow overall trends, and frequency distributions to understand the details when designing interventions for change. When assessing change in a score, it is important to compare measures of the same type.</div>";
        $top_box_popover_info = ' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="'.$top_box_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a>';
    }else if($question == 2){
        $score_title = "% Responding";
    }

    $table = '<div class="optionSelect" id="loadTable">
               <div style="/* padding-bottom: 10px; */width: 621px;" class="forme">
                <img src="'.$module->getUrl('epv-2colorhorizontal1300__1_.jpg').'" width="300px" style="/* padding-top: 20px */">
                <div style="float: right;padding-top: 23px;">
                    <a class="btn btn-default" target="_blank" href="'.APP_PATH_WEBROOT_FULL.APP_PATH_WEBROOT."DataExport/index.php?pid=".$_GET['pid']."&report_id=ALL&stats_charts=1&page=research_participant_perception_survey_sp".'">Stats &amp; Charts</a>
                </div>
                <h3 class="header"></h3>
                    <div>
                        <select class="form-control" id="question">
                            <option value="">Question type</option>
                           ';
                            foreach ($array_questions as $index => $squestion){
                                $selected = "";
                                if($index == $_SESSION[$_GET['pid'] . "_question"]){
                                    $selected = "selected";
                                }
                                $table .= '<option value="'.$index.'" '.$selected.'>'.$squestion.'</option>';
                            }

                       $table .= ' </select>
                        <select class="form-control" id="study">
                            <option value="">Study type</option>
                            <option value="nofilter" selected>No filter</option>';

                            foreach ($array_study as $index => $sstudy){
                                $selected = "";
                                if($index == $_SESSION[$_GET['pid'] . "_study"]){
                                    $selected = "selected";
                                }
                                $table .= '<option value="'.$index.'" '.$selected.'>'.$sstudy.'</option>';
                            }
                            $table .='
                            <option value="">RPPS administration timing</option>
                            <option value="">RPPS sampling approach</option>
                        </select>
                        <input type="daterange" class="form-control" id="daterange" name="daterange" value="'.$daterange.'">
                        <button onclick=\'loadTable('.json_encode($module->getUrl("loadTable.php")).');\' class="btn btn-primary" id="loadTablebtn">Load Table</button>
                    </div>
                </div>
                <table class="table dal table-bordered pull-left" id="table_archive">
                <thead>
                    <tr>
                    <th class="question"><span style="position: relative; top:23px"><strong>'.$score_title.$top_box_popover_info.'</strong></span></th>'.
                    '<th class="dal_task"><div style="width: 197.719px;"><span>TOTAL</span></div></th>';
    if($study != "nofilter") {
        if ($study == 62) {
            foreach ($study_options as $indexstudy => $col_title) {
                $class = "";
                $attibute = "";
                if ($indexstudy != 1 && $indexstudy < 6) {
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
            if ($indexstudy == 6) {
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
        #COLOR
        $extras = 2;
        if($study == 61) {
            $extras = 3;
        }
        foreach ($row_questions_1 as $indexQuestion => $question_1) {
            $array_colors[$indexQuestion] = array();
            $tooltipTextArray[$indexQuestion] = array();
            $outcome_labels = $module->getChoiceLabels($question_1, $project_id);
            $topScoreMax = count($outcome_labels);
            $missingOverall = 0;

            #GRAPH
            $graph[$question_1]["total"] = array();
            $graph[$question_1]["total"]['graph_top_score_year'] = array();
            $graph[$question_1]["total"]['graph_top_score_month'] = array();
            $graph[$question_1]["total"]['graph_top_score_quarter'] = array();
            $graph[$question_1]["total"]['years']= array();
            $graph[$question_1]["no"] = array();
            $graph[$question_1]["no"]['graph_top_score_year'] = array();
            $graph[$question_1]["no"]['graph_top_score_month'] = array();
            $graph[$question_1]["no"]['graph_top_score_quarter'] = array();
            $graph[$question_1]["no"]['years']= array();

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
            $graph = $missingCol[4];

            #OVERALL COL MISSING
            $totalCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalCol($question, $project_id,$question_1,$conditionDate,$topScoreMax,$indexQuestion,$missing_col,$missingOverall,$tooltipTextArray,$array_colors);
            $tooltipTextArray = $totalCol[0];
            $array_colors = $totalCol[1];

            #MULTIPLE
            if($study == 61) {
                $graph[$question_1]["multiple"] = array();
                $graph[$question_1]["multiple"]['graph_top_score_year'] = array();
                $graph[$question_1]["multiple"]['graph_top_score_month'] = array();
                $graph[$question_1]["multiple"]['graph_top_score_quarter'] = array();
                $graph[$question_1]["multiple"]['years']= array();

                $multipleCol = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleCol($question,$project_id,$multipleRecords,$study,$question_1,$topScoreMax,$indexQuestion,$index,$tooltipTextArray, $array_colors);
                $tooltipTextArray = $multipleCol[0];
                $array_colors = $multipleCol[1];
            }

            #PRINT RESULTS
            $question_popover_content = \Vanderbilt\DashboardAnalysisPlatformExternalModule\returnTopScoresLabels($question_1,$module->getChoiceLabels($question_1, $project_id));
            $question_popover_info = ' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" title="Field: ['.$question_1.']" data-content="'.$question_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a>';
            $table .= '<tr><td class="question">'.$module->getFieldLabel($question_1).$question_popover_info.' <i class="fas fa-chart-bar infoChart" id="DashChart_'.$question_1.'" indexQuestion="'.$indexQuestion.'"></i></td>';
            for ($i = 0;$i<count($study_options)+$extras;$i++) {
                if(($array_colors[$indexQuestion][$i] == "-" || $array_colors[$indexQuestion][$i] == "x") && $array_colors[$indexQuestion][$i] != "0"){
                    $color = "#c4c4c4";
                }else{
                    $percent = ($array_colors[$indexQuestion][$i]/($max))*100;
                    $color = \Vanderbilt\DashboardAnalysisPlatformExternalModule\GetColorFromRedYellowGreenGradient($percent);
                }
                $class = "";
                $attibute = "";
                if($study == 62 && $i > 1 && $i < 6){
                    $class = "hide";
                    $attibute = "etnicity = '1'";
                }
                if($study != "nofilter" || ($study == "nofilter" && $i == 0)) {
                    $table .= '<td style="background-color:' . $color . '" class="' . $class . '" ' . $attibute . '><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="' . $tooltipTextArray[$indexQuestion][$i] . '">' . $array_colors[$indexQuestion][$i] . '</div></td>';
                }
            }
            $table .= '</tr>';
        }
    }else if($question == 2){

        $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getNormalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study, $study_options);
        $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMissingStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study);
        $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getTotalStudyColRate($project_id, $conditionDate, $row_questions_1, $graph);
        if($study == 61) {
            #MULTIPLE
            $graph = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleStudyColRate($project_id, $conditionDate, $row_questions_1, $graph, $study);
        }
        foreach ($row_questions_2 as $indexQuestion => $question_2) {
            $question_popover_content = "";
            $question_popover_info = ' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" title="'.$question_2.'" data-content="'.$question_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a>';
            $table .= '<tr><td class="question">'.ucfirst($question_2)." response ".$question_popover_info.' <i class="fas fa-chart-bar infoChart" id="DashChart_'.$question_2.'" indexQuestion="'.$indexQuestion.'"></i></td>';

            #TOTAL
            $table .= \Vanderbilt\DashboardAnalysisPlatformExternalModule\printResponseRate($graph[$question_2]["total"], $graph["total_records"]["total"]);
            if($study != "nofilter") {
                #NORMAL
                foreach ($study_options as $index => $col_title) {
                   $table .= \Vanderbilt\DashboardAnalysisPlatformExternalModule\printResponseRate($graph[$question_2][$index], $graph["total_records"][$index]);
                }
                #MISSING
                $table .= \Vanderbilt\DashboardAnalysisPlatformExternalModule\printResponseRate($graph[$question_2]["missing"], $graph["total_records"]["missing"]);
                if($study == 61) {
                    #MULTIPLE
                    $table .= \Vanderbilt\DashboardAnalysisPlatformExternalModule\printResponseRate($graph[$question_2]["multiple"], $graph["total_records"]["multiple"]);
                }
            }
            $table .= '</tr>';
        }
    }else {
        $option = explode("-",$row_questions[$question]);
        for($i=$option[0];$i<$option[1];$i++) {
            $table .= '<tr><td class="question">' . $module->getFieldLabel("rpps_s_q".$i).'</td>';
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
                $multiple = \Vanderbilt\DashboardAnalysisPlatformExternalModule\getMultipleCol($question,$project_id,$multipleRecords,$study,"rpps_s_q".$i,"","",$index,"", "");
                $table .= '<td><div class="red-tooltip extraInfoLabel" data-toggle="tooltip" data-html="true" title="'.$multiple[1].'">'.$multiple[0].'</div></td>';

            }
            $table .= '</tr>';
        }
        $table .= '</tr>';
    }
    $table .= '</table></div>';
    echo $table;
    ?>
    <script>
        $(function () {
            var datagraph;
            var graph_url = <?=json_encode($module->getUrl("graph_ajax.php"))?>;
            var study_options = <?=json_encode($study_options)?>;
            var question = <?=json_encode($question)?>;
            var conditionDate = <?=json_encode($conditionDate)?>;
            var studyOption = <?=json_encode($study)?>;

            var config_dash = {
                type: 'line',
                data: {
                    labels: [""],
                    datasets: []
                },
                options: {
                    elements: {
                        line: {
                            tension: 0, // disables bezier curves
                        }
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    },
                    scales : {
                        yAxes : [{
                            ticks: {
                                stepSize: 10,
                                beginAtZero:true,
                                max: 100
                            }
                        }]
                    }
                }
            }
            dash_chart_big = new Chart($("#modal-big-graph-body"), config_dash);

            $('[data-toggle="tooltip"]').tooltip();

            $(".category").change(function(){
                var question_1 = $("#question_num").val();
                var study = $(this).val();
                var timeline = $("#options td").closest(".selected").attr("id");
                var color = "#"+$(this).attr("color");
                var ds1 = {
                    label: $(this).attr("text"),
                    fill: false,
                    spanGaps: true,
                    id: study,
                    borderColor: color,
                    backgroundColor: color,
                    data: datagraph["results"][timeline][question_1][study]
                };

                if($(this).is(":checked")){
                    dash_chart_big.data.datasets.push(ds1);
                }else{
                    dash_chart_big.data.datasets.find((dataset, index) => {
                        if (dataset.id === study) {
                            dash_chart_big.data.datasets.splice(index, 1);
                            return true; // stop searching
                        }
                    });
                }
                dash_chart_big.update();
            });

            $(".infoChart").click(function(){
                var question_1 = $(this).attr('id').split("DashChart_")[1];
                var question_text = $(this).parent("td").text();
                $("#category").val("total");

                $("#question_num").val(question_1);
                $('#modal-big-graph-title').text(question_text);
                $('#modal-spinner').modal('show');

                if(studyOption == "nofilter" && datagraph != "" && datagraph != undefined){
                    dash_chart_big.data.datasets.find((dataset, index) => {
                        if (dataset.id === "total") {
                            dash_chart_big.data.datasets.splice(index, 1);
                            return true; // stop searching
                        }
                    });
                }else {
                    //Clean checkboxes & data in graph
                    $(".category:checked").each(function () {
                        if ($(this).attr("text") != "Total") {
                            $(this).prop("checked", false);
                        }
                        dash_chart_big.data.datasets.find((dataset, index) => {
                            if (dataset.id === $(this).val()) {
                                dash_chart_big.data.datasets.splice(index, 1);
                                return true; // stop searching
                            }
                        });
                    });
                }
                dash_chart_big.update();

                $.ajax({
                    url: graph_url,
                    data: "&studyOption="+studyOption+"&question="+question+"&question_1="+question_1+"&study="+studyOption+"&study_options="+JSON.stringify(study_options)+"&conditionDate="+conditionDate,
                    type: 'POST',
                    success: function(returnData) {
                        var data = JSON.parse(returnData);
                        $('#modal-spinner').modal('hide');
                        if (data.status == 'success') {
                            datagraph = JSON.parse(data.chartgraph);
                            dash_chart_big.data.labels = datagraph["labels"]["month"][question_1]["total"];
                            var ds1 = {
                                label: "Total",
                                borderColor: '#337ab7',
                                backgroundColor: '#337ab7',
                                fill: false,
                                spanGaps: true,
                                id: "total",
                                data: datagraph["results"]["month"][question_1]["total"]
                            };
                            dash_chart_big.data.datasets.push(ds1);
                            dash_chart_big.update();
                            $('#quarter').removeClass('selected');
                            $('#year').removeClass('selected');
                            $('#month').addClass('selected');
                            $('#modal-big-graph').modal('show');
                        }
                    }
                });
            });

            $("#options td").click(function(){
                var question_1 = $("#question_num").val();
                var timeline = $(this).attr('id');

                if($(this).attr('id') == "month"){
                    $('#quarter').removeClass('selected');
                    $('#year').removeClass('selected');
                }else if($(this).attr('id') == "quarter"){
                    $('#month').removeClass('selected');
                    $('#year').removeClass('selected');
                }else if($(this).attr('id') == "year"){
                    $('#quarter').removeClass('selected');
                    $('#month').removeClass('selected');
                }

                if(studyOption == "nofilter"){
                    study = "total";
                    dash_chart_big.data.labels = datagraph["labels"][timeline][question_1][study];
                    dash_chart_big.data.datasets[0].data = datagraph["results"][timeline][question_1][study];
                }else {
                    $(".category:checked").each(function () {
                        var study = $(this).val();
                        var indexdataset = 0;
                        dash_chart_big.data.datasets.find((dataset, index) => {
                            if (dataset.id === study) {
                                indexdataset = index;
                            }
                        });
                        dash_chart_big.data.labels = datagraph["labels"][timeline][question_1][study];
                        dash_chart_big.data.datasets[indexdataset].data = datagraph["results"][timeline][question_1][study];
                    });
                }
                $('#'+timeline).addClass('selected');
                dash_chart_big.update();
            });
        });
    </script>
    <!-- MODAL GRAPH-->
    <div class="modal fade" id="modal-big-graph" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width:900px !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-big-graph-title"></h4>
                    <input type="hidden" id="question_num" value=""/>
                </div>
                <div class="modal-body">
                    <canvas id="modal-big-graph-body" class="infoChartBig pull-left"></canvas>
                    <table class='pull-righ table table-bordered' id='options'>
                        <tr>
                            <td class='selected' id='month'>Month</td>
                        </tr>
                        <tr>
                            <td id='quarter'>Quarter</td>
                        </tr>
                        <tr>
                            <td id='year'>Year</td>
                        </tr>
                    </table>
                        <?php if(!empty($study_options) && $study != "nofilter"){ ?>
                            <div class='pull-righ table table-bordered'>
                                <div><input type="checkbox" value="total" class="category" text="Total" color="<?=$array_colors_graphs[0]?>" checked> Total</div>
                                <?php
                                $i = 1;
                                foreach ($study_options as $indexstudy => $col_title) {
                                    echo "<div><input type='checkbox' value='".$indexstudy."' class='category' text='".$col_title."' color='".$array_colors_graphs[$i]."'> ".$col_title."</div>";
                                    $i++;
                                }
                                ?>
                                <div><input type="checkbox" value="no" class="category" text="NO <?=strtoupper($array_study[$study])?> REPORTED" color="<?=$array_colors_graphs[$i]?>"> NO <?=strtoupper($array_study[$study])?> REPORTED</div>
                               <?php
                                if($study == 61){
                                    $i++;
                                    echo "<div><input type='checkbox' value='multiple' class='category' text='MULTIPLE' color='".$array_colors_graphs[$i]."'> MULTIPLE</div>";
                                }
                                ?>
                            </div>
                        <?php } ?>
                </div>
                <div class="modal-footer" style="padding-top: 30px">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- MODAL SPINNER-->
    <div class="modal fade" id="modal-spinner" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Graph in progress</h4>
                </div>
                <div class="modal-body">
                    <div style="padding-top: 20px">
                        <div class="alert alert-success">
                            <em class="fa fa-spinner fa-spin"></em> Processing... Please wait until the process finishes.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                </div>
            </div>
        </div>
    </div>
<?php
}
?>
