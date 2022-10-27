<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/StatsCharts.php");
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include_once("head_scripts.php");?>
<script type='text/javascript'>
    var app_path_webroot = '<?=APP_PATH_WEBROOT?>';
    var app_path_webroot_full = '<?=APP_PATH_WEBROOT_FULL?>';
    var app_path_images = '<?=APP_PATH_IMAGES?>';
    var plotchart = '<?=$module->getUrl('plot_chart.php?NOAUTH')?>';
    var pid = '<?=$_GET['pid']?>';
    var table_pk_label  = '<?php echo js_escape($table_pk_label) ?>';
    var page = '<?=$module->getUrl('stats_and_charts.php')?>';
</script>

<?php
$report_id = "ALL";
$instrument = array();
$events = array();
$page = "research_participant_perception_survey_epv_version";

\DataExport::checkReportHash();

$html = StatsCharts::outputStatsCharts(	$report_id, $instrument, $events, $page);
// Add note about Missing Data Codes for "Missing" values
$html .= \RCView::div(array('class'=>'spacer mt-5'),' ') .
    \RCView::h6(array('class'=>'mt-3', 'style'=>'color:#A00000;'),
        "<span class='em-ast' style='font-size:16px;'>*</span> " . $lang['missing_data_13']
    );

// Tabs
\DataExport::renderTabs();

// Output content
print '<div id="center" class="col" style="padding-bottom: 60px;padding-left: 30px">'.$html.'</div>';

?>
<script>
    $( document ).ready(function() {
        $('#lf1').prev().css('display','none');
        $( 'button:contains(\' View Report\')' ).hide();
        $( 'button:contains(\' Export Data\')' ).hide();
    });
</script>
