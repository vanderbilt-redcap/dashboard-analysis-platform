<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");
require_once (dirname(__FILE__)."/classes/Crons.php");
include_once("functions.php");

if($module == ""){
    $module = $this;
}

if($module->isSuperuser()) {
	$forceRun = (bool)$_GET['forceRun'];
}
else {
	$forceRun = false;
}
$report = htmlentities($_GET['report'],ENT_QUOTES);

if($cronAttributes['cron_name'] == ""){
    #MANUAL
    if(!empty($report) && array_key_exists('report', $_GET)) {
        Crons::runCacheReportCron($module, $project_id, $report,$forceRun);
        Crons::runGraphReportCron($module, $project_id, $report,$forceRun);
    }else {
        Crons::runCacheCron($module, $project_id,$forceRun);
        Crons::runCacheReportCron($module, $project_id, null,$forceRun);
        Crons::runGraphCron($module, $project_id,$forceRun);
        Crons::runGraphReportCron($module, $project_id, null,$forceRun);
    }
}else{
    #CRONS
    if ($cronAttributes['cron_name'] == 'dashboard_cache_file'){
        Crons::runCacheCron($module, $project_id,$forceRun);
    }else if ($cronAttributes['cron_name'] == 'dashboard_cache_file_report'){
        Crons::runCacheReportCron($module, $project_id, null,$forceRun);
    }else if ($cronAttributes['cron_name'] == 'dashboard_cache_file_graph'){
        Crons::runGraphCron($module, $project_id,$forceRun);
    }else if ($cronAttributes['cron_name'] == 'dashboard_cache_file_graph_report'){
        Crons::runGraphReportCron($module, $project_id, null,$forceRun);
    }
}