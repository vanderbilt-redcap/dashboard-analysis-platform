<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");
require_once (dirname(__FILE__)."/classes/Crons.php");

$project_id = (int)$_GET['pid'];
$report = htmlentities($_GET['report'],ENT_QUOTES);
$banner = $module->getProjectSetting('banner',$project_id);
include_once "reports.php";

if(($_SESSION[$project_id . "_startDate"] == "" || $_SESSION[$project_id . "_startDate"] == "") || (empty($_GET['dash']) || !empty($_GET['dash'])) && !ProjectData::startTest($_GET['dash'], '', '', $_SESSION[$project_id."_dash_timestamp"])){
    if($_SESSION[$project_id . "_question"] == "" || $_SESSION[$project_id . "_study"] == "" || empty($_GET['dash'])){
        $_SESSION[$project_id . "_question"] = "1";
        $_SESSION[$project_id . "_study"] = "nofilter";
    }
}

$array_questions = ProjectData::getFilterQuestionsArray();
$array_study = ProjectData::getStudyArray();

$custom_filters = $module->getProjectSetting('custom-filter',$project_id);
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
if(!empty($_GET['dash']) && ProjectData::startTest($_GET['dash'], '', '', $_SESSION[$project_id."_dash_timestamp"]) || ($_SESSION[$project_id . "_study"] == "nofilter" && $_SESSION[$project_id . "_question"] == "1")) {
    $question = $_SESSION[$project_id . "_question"];
    $study = $_SESSION[$project_id . "_study"];
    $row_questions = ProjectData::getRowQuestions();
    $row_questions_1 = ProjectData::getRowQuestionsParticipantPerception();
    $row_questions_2 = ProjectData::getRowQuestionsResponseRate();
    $study_options = $module->getChoiceLabels($study, $project_id);

    $multipleRecords = \REDCap::getData($project_id, 'json-array');
    $institutions = ProjectData::getAllInstitutions($multipleRecords);

    $graph = array();
    if($question == 2){
        $array_study = ProjectData::getArrayStudyQuestion_2();
    }

    if($study == "rpps_s_q62" || $study == "ethnicity"){
        array_push($study_options,ProjectData::getExtraColumTitle());
    }
    if($_SESSION[$project_id . "_startDate"] != ""){
        $startDate = date("Y-m-d",strtotime($_SESSION[$project_id . "_startDate"]));
    }else{
        $startDate = "";
    }
    if($_SESSION[$project_id . "_endDate"] != ""){
        $endDate = date("Y-m-d",strtotime($_SESSION[$project_id . "_endDate"]));
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
        $score_title = "Percent Responding (% Responding)";
        $top_box_popover_content = "<div style='padding-bottom: 20px'><strong>Percent Responding or response rate</strong> is a measure of the portion of surveys returned compared to those sent out, according to pre-specified criteria. The fraction of responses/total sent is multiple by 100 to calculate a response “rate”.</div>
                                    <div style='padding-bottom: 20px'><strong>Calculating Response Rates:</strong> The numerator for the response rate calculation is the total number of responses meeting criteria for a given response category. The denominator consists of the total number of individuals to whom surveys were sent, regardless of whether they responded. The calculation does not distinguish undeliverable surveys and other reasons for non-response.</div>
                                    <div style='padding-bottom: 20px'><strong>Defining a response:</strong> A “response” is defined by the return of a survey with a response (not null) for at least one of the questions considered key to the validity of the survey. The criteria for whether a response is considered “complete” or “partial” or a “break-off” or “any” are defined below based (“i” icon). A survey returned completely blank is not considered a response. Reference: <a href='https://www.aapor.org/AAPOR_Main/media/MainSiteFiles/Standard-Definitions2015' target='_blank'>https://www.aapor.org/AAPOR_Main/media/MainSiteFiles/Standard-Definitions2015</a></div>
                                    <div style='padding-bottom: 20px'><strong>Key questions:</strong> For the RPPS response rate calculations, 18 questions were selected as key to a meaningful response. The key questions include the 15 questions in the Perception and rating questions drop down menu on the Dashboard, and three questions that characterize the study in terms of intensity, interventional nature, and mode of consent (filter menu).</div>
                                    <div style='padding-bottom: 20px'><strong>Response rates can be calculated for datasets filtered by individual or study characteristics</strong> (e.g., age, race, ethnicity, sex, gender) for which there are data from the EMR or CTMS to inform the denominator (including characteristics of non-responders). All respondents with a given characteristic in their RPPS data are included in the numerator for that characteristic, and all potential recipients with the same characteristic to whom the survey was sent are included in the denominator regardless of whether they returned a response.</div>
                                    <div>When values for characteristics are available from RPPS data, RPPS values are used. When RPPS values are missing, or there is no RPPS data (non-response) available, values from the EMR/CTMS are used. When the RPPS data and site source data are in conflict, RPPS data inform both the numerator and the denominator for response rate calculations.</div>
                                    ";
        $top_box_popover_info = ' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-html="true" data-placement="right" data-content="'.$top_box_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a>';
    }

    $logout = $module->getUrl('index.php?pid='.$project_id.'&sout');
    $table = "";
    if($banner != ""){
        $table .= "<div style='margin-top: 20px;'>
                        <div class='alert alert-info'>".$banner."</div>
                    </div>";
    }
    $table .= "<div class='optionSelect' id='loadTable'>
               <div style='width: 621px;' class='forme'>
                <img src='".$module->getUrl('epv-2colorhorizontal1300__1_.jpg')."' width='300px'>
                <div style='float: right;padding-top: 23px;'>
                    <div style='width: 100%'>
                    <a href='#' style='float: right' onclick='destroy_session(\"".$logout."\")'> Logout</a>
                    </div>
                    <a class='btn btn-default' target='_blank' href='".$module->getUrl('index.php')."&NOAUTH&option=sac"."'>Stats &amp; Charts</a>
                </div>
                <h3 class='header'></h3>
                    <div>
                        <select class='form-control' id='question' onchange='isItResponseRates(this.value,".json_encode($module->getUrl("response_rates_selector_ajax.php")).")'>
                            <option value=''>Question type</option>
                           ";
    foreach ($array_questions as $index => $squestion){
        $selected = "";
        if($index == $_SESSION[$project_id . "_question"]){
            $selected = "selected";
        }
        $table .= '<option value="'.$index.'" '.$selected.'>'.$squestion.'</option>';
    }

    $table .= ' </select>
                        <select class="form-control" id="study">';
        if($study == "nofilter") {
            $table .= '<option value="nofilter" selected>No filter</option>
                       <option value="bysite">By site</option>';
        }else if($study == "bysite") {
            $table .= '<option value="nofilter">No filter</option>
                       <option value="bysite" selected>By site</option>';
        }else{
            $table .= '<option value="nofilter">No filter</option>
                       <option value="bysite">By site</option>';
        }

    foreach ($array_study as $index => $sstudy){
        if(strpos($index, 'header') !== false){
            $number_header = explode('header', strtolower($index));
            if($number_header == "0"){
                $table .= '</optgroup><optgroup label="'.$sstudy.'">';
            }else{
                $table .= '<optgroup label="'.$sstudy.'">';
            }
        }else {
            $selected = "";
            if ($index === $_SESSION[$project_id . "_study"]) {
                $selected = "selected";
            }
            $table .= '<option value="' . $index . '" ' . $selected . '>' . $sstudy . '</option>';
        }
    }
    $table .='</optgroup>';
    if($question != 2) {
        if (isset($custom_filters)) {
            $table .= '<optgroup label="Custom site filters:">';
        }
        $customf_counter = 1;
        foreach ($custom_filters as $index => $sstudy) {
            $selected = "";
            if ($sstudy === $_SESSION[$project_id . "_study"]) {
                $selected = "selected";
            }
            if($customf_counter < 11) {
                $table .= '<option value="' . $sstudy . '" ' . $selected . '>Custom site value ' . $customf_counter . '</option>';
            }
            $customf_counter++;
        }
    }
    $table .='</optgroup>';
    $table .='</select>
                        <button onclick=\'loadTable('.json_encode($module->getUrl("loadTable.php")."&NOAUTH").');\' class="btn btn-primary" id="loadTablebtn">Load Table</button>
                    </div>
                </div>
                <table class="table dal table-bordered pull-left" id="table_archive">
                <thead>
                    <tr>
                    <th class="question"><span style="position: relative; top:23px"><strong>'.$score_title.$top_box_popover_info.'</strong></span></th>'.
        '<th class="dal_task"><div style="width: 197.719px;"><span>TOTAL</span></div></th>';
    if($study != "nofilter") {
        if ($study == "rpps_s_q62" || $study == "ethnicity") {
            foreach ($study_options as $indexstudy => $col_title) {
                $class = "";
                $attribute = "";
                if ($indexstudy != 1 && (($study == "rpps_s_q62"  || $study == "ethnicity") && $indexstudy < count($study_options))) {
                    $class = "hide";
                    $attribute = "etnicity='1'";
                }
                $table .= '<th class="dal_task ' . $class . '" ' . $attribute . '><div style="width: 197.719px;"><span>' . $col_title . '</span></div></th>';
            }
        }else if ($study == "bysite") {
            foreach ($institutions as $institution){
                $table .=  '<th class="dal_task"><div style="width: 197.719px;"><span>'.$institution.'</span></div></th>';
            }
        } else {
            foreach ($study_options as $indexstudy => $col_title) {
                $table .= '<th class="dal_task"><div style="width: 197.719px;"><span>' . $col_title . '</span></div></th>';
            }
        }
        if($study != "bysite") {
            $table .= '<th class="dal_task"><div style="width: 197.719px;"><span>NO ' . strtoupper($array_study[$study]) . ' REPORTED</span></div></th>';
        }
        if ($study == "rpps_s_q61" || $study == "race") {
            $table .= '<th class="dal_task"><div style="width: 197.719px;"><span>MULTIPLE</span></div></th>';
        }
    }
    $table .= '</tr>';

    $table .= '<tr>';
    $table .= '<td class="question"></td>';
    $table .= '<td></td>';
    foreach ($study_options as $indexstudy => $col_title) {
        if($study == "rpps_s_q62" || $study == "ethnicity") {
            $table .="<style>.dal_task>div>span {
                        display: block;
                        margin-left: 38px;
                        color: #5592c6;
                    }</style>";
            if (($study == "rpps_s_q62" || $study == "ethnicity") && $indexstudy == count($study_options)) {
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

    #LOAD THE FILE
    $dash_array = ProjectData::getFileData($module, $project_id, "dashboard_cache_file_", $report);

    $showLegendNoFilter = false;
    if($dash_array != "" && is_array($dash_array)){
        $max = 100;
        if ($question == 1) {
            if($dash_array['data'][$question][$study] != "" || $study == "bysite"){
                if($study == "rpps_s_q61") {
                    $extras = 3;
                }
                foreach ($row_questions_1 as $indexQuestion => $question_1) {
                    #PRINT RESULTS
                    $question_popover_content = returnTopScoresLabels($question_1,$module->getChoiceLabels($question_1, $project_id));
                    $question_popover_info = ' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" title="Field: ['.$question_1.']" data-content="'.$question_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a>';
                    $table .= '<tr><td class="question">'.$module->getFieldLabel($question_1).$question_popover_info.' <i class="fas fa-chart-bar infoChart" id="DashChart_'.$question_1.'" indexQuestion="'.$indexQuestion.'"></i></td>';

                    $study_aux = $study;
                    if($study == "bysite") {
                        $study_aux = "nofilter";
                    }

                    if(!empty($dash_array['data'][$question][$study_aux][$question_1][$indexQuestion]))
                    ksort($dash_array['data'][$question][$study_aux][$question_1][$indexQuestion]);
                    if(!empty($dash_array['tooltip'][$question][$study_aux][$question_1][$indexQuestion]))
                    ksort($dash_array['tooltip'][$question][$study_aux][$question_1][$indexQuestion]);

                    if($study == "nofilter" || $study == "bysite"){
                        if (($dash_array['data'][$question]["nofilter"][$question_1][$indexQuestion][0] == "-" || $dash_array['data'][$question]["nofilter"][$question_1][$indexQuestion][0] == "x") && $dash_array['data'][$question]["nofilter"][$question_1][$indexQuestion][0] != "0") {
                            $color = "#c4c4c4";
                            $showLegendNoFilter = true;
                        } else {
                            if (strpos($dash_array['data'][$question]["nofilter"][$question_1][$indexQuestion][0], '*')) {
                                $showLegendNoFilter = true;
                            }
                            $percent = ($dash_array['data'][$question]["nofilter"][$question_1][$indexQuestion][0] / ($max)) * 100;
                            $color = GetColorFromRedYellowGreenGradient($percent);
                        }
                        $extraSpace100 = '';
                        if ($dash_array['data'][$question]["nofilter"][$question_1][$indexQuestion][0] == "100 *") {
                            $extraSpace100 = " extraSpace100";
                        }
                        $table .= '<td style="background-color:' . $color . '" class="' . $class . '" ' . $attribute . '><div class="red-tooltip extraInfoLabel' . $extraSpace100 . '" data-toggle="tooltip" data-html="true" title="' . $dash_array['tooltip'][$question]["nofilter"][$question_1][$indexQuestion][0] . '">' . $dash_array['data'][$question]["nofilter"][$question_1][$indexQuestion][0] . '</div></td>';

                        if($study == "bysite") {
                            #INSTITUTIONS
                            foreach ($institutions as $institution) {
                                if (($dash_array['data'][$question]["institutions"][$question_1][$indexQuestion][0][$institution] == "-" || $dash_array['data'][$question]["institutions"][$question_1][$indexQuestion][0][$institution] == "x") && $dash_array['data'][$question]["institutions"][$question_1][$indexQuestion][0][$institution] != "0") {
                                    $color = "#c4c4c4";
                                    $showLegendNoFilter = true;
                                } else {
                                    if (strpos($dash_array['data'][$question]["institutions"][$question_1][$indexQuestion][0][$institution], '*')) {
                                        $showLegendNoFilter = true;
                                    }
                                    $percent = ($dash_array['data'][$question]["institutions"][$question_1][$indexQuestion][0][$institution] / ($max)) * 100;
                                    $color = GetColorFromRedYellowGreenGradient($percent);
                                }
                                $extraSpace100 = '';
                                if ($dash_array['data'][$question]["institutions"][$question_1][$indexQuestion][0][$institution] == "100 *") {
                                    $extraSpace100 = " extraSpace100";
                                }
                                $table .= '<td style="background-color:' . $color . '" class="' . $class . '" ' . $attribute . '><div class="extraInfoLabel' . $extraSpace100 . '" style="cursor:default !important">' . $dash_array['data'][$question]["institutions"][$question_1][$indexQuestion][0][$institution] . '</div></td>';
                            }
                        }
                        $table .= '</tr>';
                    }else{
                        foreach ($dash_array['data'][$question][$study][$question_1][$indexQuestion] as $i => $value){
                           if(($value == "-" || $value == "x") && $value != "0"){
                                $color = "#c4c4c4";
                            }else{
                                $percent = ($value/($max))*100;
                                $color = GetColorFromRedYellowGreenGradient($percent);
                            }
                            $class = "";
                            $attribute = "";
                            if($study == "rpps_s_q62" && $i > 1 && $i < count($study_options)){
                                $class = "hide";
                                $attribute = "etnicity = '1'";
                            }
                            $extraSpace100 = '';
                            if($value == "100 *"){
                                $extraSpace100 = " extraSpace100";
                            }
                            if($study != "nofilter" || ($study == "nofilter" && $i == "0")) {
                                $table .= '<td style="background-color:' . $color . '" class="' . $class . '" ' . $attribute . '><div class="red-tooltip extraInfoLabel'.$extraSpace100.'" data-toggle="tooltip" data-html="true" title="' . $dash_array['tooltip'][$question][$study][$question_1][$indexQuestion][$i] . '">' . $value . '</div></td>';
                            }
                        }
                       $table .= '</tr>';
                    }
                }
            }else{
                $found = false;
                foreach ($custom_filters as $index => $sstudy) {
                   if($sstudy == $study){
                       $found = true;
                       break;
                   }
                }
                if($found){
                    $url = $module->getUrl("callCron.php");
                    echo "<div class='optionSelect messageCache' style='margin-top: 20px;'>
                <div class='alert alert-warning fade in col-md-12' id='errMsgContainerModal'>
                The selected custom filter has not been added to the Dashboard Cache file. This file will be automatically generated every day at 23:50pm.<br/>
                To create the file now <a href='javascript:loadCache(".json_encode($project_id).",".json_encode($url).");'>click here</a>. Have in mind that this will take several minutes.</div>
                </div>";
                }
            }
        }else if ($question == 2) {
            $row_questions_2_info = ProjectData::getRowQuestionsResponseRateInfo();
            foreach ($row_questions_2 as $indexQuestion => $question_2) {
                $question_popover_content = $row_questions_2_info[$indexQuestion];
                $question_popover_info = ' <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" title="'.ucfirst($question_2).' Response" data-content="'.$question_popover_content.'"><i class="fas fa-info-circle fa-fw infoIcon" aria-hidden="true"></i></a>';
                $table .= '<tr><td class="question">'.ucfirst($question_2)." response ".$question_popover_info.' <i class="fas fa-chart-bar infoChart" id="DashChart_'.$question_2.'" indexQuestion="'.$indexQuestion.'"></i></td>';

                if($study == "nofilter" || $study == "bysite"){
                    if($dash_array['data'][$question]['nofilter'][$question_2][0] == "100 *"){
                        $extraSpace100 = " extraSpace100";
                    }
                    if(strpos($dash_array['data'][$question]['nofilter'][$question_2][0], '*')){
                        $showLegendNoFilter = true;
                    }
                    $table .= '<td class="' . $class . '" ' . $attribute . '><div class="red-tooltip extraInfoLabel'.$extraSpace100.'" data-toggle="tooltip" data-html="true" title="' . $dash_array['tooltip'][$question]['nofilter'][$question_2][0] . '">' . $dash_array['data'][$question]['nofilter'][$question_2][0] . '</div></td>';

                    if($study == "bysite") {
                        #INSTITUTIONS
                        foreach ($institutions as $institution) {
                            if ($dash_array['data'][$question]['institutions'][$question_2][$institution][0] == "100 *") {
                                $extraSpace100 = " extraSpace100";
                            }
                            if (strpos($dash_array['data'][$question]['institutions'][$question_2][$institution][0], '*')) {
                                $showLegendNoFilter = true;
                            }
                            $table .= '<td class="' . $class . '" ' . $attribute . '><div class="extraInfoLabel' . $extraSpace100 . '" style="cursor:default !important">' . $dash_array['data'][$question]['institutions'][$question_2][$institution][0] . '</div></td>';
                        }
                    }
                    $table .= '</tr>';
                }else{
                    ksort($dash_array['data'][$question][$study][$question_2]);
                    ksort($dash_array['tooltip'][$question][$study][$question_2]);
                    $array = $dash_array['data'][$question][$study][$question_2];
                    end($array);         // move the internal pointer to the end of the array
                    $last_index = key($array);
                    foreach ($dash_array['data'][$question][$study][$question_2] as $i => $value){
                        $class = "";
                        $attribute = "";
                        if($study == "ethnicity" && $i > 1 && $i < count($study_options)){
                            $class = "hide";
                            $attribute = "etnicity = '1'";
                        }
                        $extraSpace100 = "";
                        if($value == "100 *"){
                            $extraSpace100 = " extraSpace100";
                        }
                        if($last_index == $i && $study == "race") {
                            #MULTIPLE
                            $table .= '<td class="' . $class . '" ' . $attribute . '><div class="red-tooltip extraInfoLabel'.$extraSpace100.'" data-toggle="tooltip" data-html="true" title="' . $dash_array['tooltip'][$question][$study][$question_2][$i] . '">' . $value . '</div></td>';

                        }else{
                            $table .= '<td class="' . $class . '" ' . $attribute . '><div class="red-tooltip extraInfoLabel'.$extraSpace100.'" data-toggle="tooltip" data-html="true" title="' . $dash_array['tooltip'][$question][$study][$question_2][$i] . '">' . $value . '</div></td>';
                        }

                    }
                    $table .= '</tr>';
                }
            }
        }else{
            if($dash_array['data'][$question][$study] != "") {
                $option = explode("-", $row_questions[$question]);
                $index = 0;
                for ($i = $option[0]; $i < $option[1]; $i++) {
                    $table .= '<tr><td class="question">' . $module->getFieldLabel("rpps_s_q" . $i) . '</td>';
                    if ($study == "nofilter") {
                        $extraSpace100 = "";
                        if($dash_array['data'][$question]['nofilter']["rpps_s_q" . $i][$index][0] == "100 *"){
                            $extraSpace100 = " extraSpace100";
                        }
                        if(strpos($dash_array['data'][$question]['nofilter']["rpps_s_q" . $i][$index][0], '*')){
                            $showLegendNoFilter = true;
                        }
                        $table .= '<td class="' . $class . '" ' . $attribute . '><div class="red-tooltip extraInfoLabel'.$extraSpace100.'" data-toggle="tooltip" data-html="true" title="' . $dash_array['tooltip'][$question]['institutions']["rpps_s_q" . $i][$index][0] . '">' . $dash_array['data'][$question]['nofilter']["rpps_s_q" . $i][$index][0] . '</div></td>';

                        #INSTITUTIONS
                        foreach ($institutions as $institution) {
                            if($dash_array['data'][$question]['institutions']["rpps_s_q" . $i][$index][0][0][$institution] == "100 *"){
                                $extraSpace100 = " extraSpace100";
                            }
                            if(strpos($dash_array['data'][$question]['institutions']["rpps_s_q" . $i][$index][0][0][$institution], '*')){
                                $showLegendNoFilter = true;
                            }
                            $table .= '<td class="' . $class . '" ' . $attribute . '><div class="extraInfoLabel'.$extraSpace100.'" style="cursor:default !important">' . $dash_array['data'][$question]['institutions']["rpps_s_q" . $i][$index][0][0][$institution] . '</div></td>';

                        }

                        $table .= '</tr>';
                    } else {
                        foreach ($dash_array['data'][$question][$study]["rpps_s_q" . $i] as $singleDataIndex => $singleData) {
                            ksort($dash_array['data'][$question][$study]["rpps_s_q" . $i][$singleDataIndex]);
                            ksort($dash_array['tooltip'][$question][$study]["rpps_s_q" . $i][$singleDataIndex]);
                            foreach ($dash_array['data'][$question][$study]["rpps_s_q" . $i][$singleDataIndex] as $arrayIndex => $value) {
                                $class = "";
                                $attribute = "";
                                if ($study == "rpps_s_q62" && $arrayIndex > 1 && $arrayIndex < count($study_options)) {
                                    $class = "hide";
                                    $attribute = "etnicity = '1'";
                                }
                                if($value == '-' || $value == "*" || $value == 'x'){
                                    $showLegendNoFilter = true;
                                }
                                $extraSpace100 = "";
                                if($value == " *"){
                                    $extraSpace100 = " extraSpace100";
                                }
                                $table .= '<td class="' . $class . '" ' . $attribute . '><div class="red-tooltip extraInfoLabel'.$extraSpace100.'" data-toggle="tooltip" data-html="true" title="' . $dash_array['tooltip'][$question][$study]["rpps_s_q" . $i][$singleDataIndex][$arrayIndex] . '">' . $value . '</div></td>';
                            }
                        }
                    }
                    $index++;
                }
                $table .= '</tr>';
            }else{
                $found = false;
                foreach ($custom_filters as $index => $sstudy) {
                    if($sstudy == $study){
                        $found = true;
                        break;
                    }
                }
                if($found){
                    $url = $module->getUrl("callCron.php");
                    echo "<div class='optionSelect messageCache' style='margin-top: 20px;'>
                <div class='alert alert-warning fade in col-md-12' id='errMsgContainerModal'>
                The selected custom filter has not been added to the Dashboard Cache file. This file will be automatically generated every day at 23:50pm.<br/>
                To create the file now <a href='javascript:loadCache(".json_encode($project_id).",".json_encode($url).");'>click here</a>. Have in mind that this will take several minutes.</div>
                </div>";
                }
            }
        }
    }else if(!empty($report)){
        $q = $module->query("SELECT report_id FROM redcap_reports 
                                    WHERE project_id = ? AND unique_report_name=?",
            [$project_id,$report]);
        $row = $q->fetch_assoc();
        $report_records = \REDCap::getReport($row['report_id']);
        if(empty($report_records)) {
            echo "<div class='optionSelect messageCache' style='margin-top: 20px;'>
                <div class='alert alert-danger fade in col-md-12' id='errMsgContainerModal'>
                The Dashboard Cache file has not been generated.<br/>
                No record IDs found in report.</div>
                </div>";
        }else{
            $url = $module->getUrl("callCron.php")."&report=".$report;
            echo "<div class='optionSelect messageCache' style='margin-top: 20px;'>
                <div class='alert alert-warning fade in col-md-12' id='errMsgContainerModal'>
                The Dashboard Cache file has not been generated. This file will be automatically generated every day at 23:50pm.<br/>
                To create the file now <a href='javascript:loadCache(".json_encode($project_id).",".json_encode($url).");'>click here</a>. Have in mind that this will take several minutes.</div>
                </div>";
            echo '<div class="optionSelect" style="margin-top: 20px;display: none" id="spinner">
                <div class="alert alert-success">
                <em class="fa fa-spinner fa-spin"></em> Updating... Please wait until the process finishes.
            </div></div>';
        }
    }else{
        $url = $module->getUrl("callCron.php");
        echo "<div class='optionSelect messageCache' style='margin-top: 20px;'>
                <div class='alert alert-warning fade in col-md-12' id='errMsgContainerModal'>
                The Dashboard Cache file has not been generated. This file will be automatically generated every day at 23:50pm.<br/>
                To create the file now <a href='javascript:loadCache(".json_encode($project_id).",".json_encode($url).");'>click here</a>. Have in mind that this will take several minutes.</div>
                </div>";
        echo '<div class="optionSelect" style="margin-top: 20px;display: none" id="spinner">
                <div class="alert alert-success">
                <em class="fa fa-spinner fa-spin"></em> Updating... Please wait until the process finishes.
            </div></div>';
    }

    $table .= '</table>';
    if($dash_array['legend'][$question][$study] || $showLegendNoFilter){
        $table .= "<i style='padding-bottom: 20px'>X = fewer than 5 responses <span style='padding-left: 10px'>- = no responses</span><span style='padding-left: 10px'>* = fewer than 20 responses</span></i>";
    }
    $table .= '</div>';
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
            var report = <?=json_encode($report)?>;

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
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Top Box Score'
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

                if((studyOption == "nofilter" || studyOption == "bysite") && datagraph != "" && datagraph != undefined){
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
                    data: "&studyOption="+studyOption+"&question="+question+"&question_1="+question_1+"&study="+studyOption+"&study_options="+JSON.stringify(study_options)+"&conditionDate="+conditionDate+"&report="+report,
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

                if(studyOption == "nofilter" || studyOption == "bysite"){
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
                    <div style="padding-left: 20px;float: left;width: 180px;">
                        <table class='table table-bordered' id='options' style="width: 100%;">
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
                        <?php if(!empty($study_options) && $study != "nofilter" && $study != "bysite"){ ?>
                            <div class='table table-bordered'>
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
                                if($study == "rpps_s_q61"){
                                    $i++;
                                    echo "<div><input type='checkbox' value='multiple' class='category' text='MULTIPLE' color='".$array_colors_graphs[$i]."'> MULTIPLE</div>";
                                }
                                ?>
                            </div>
                        <?php } ?>
                    </div>
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
