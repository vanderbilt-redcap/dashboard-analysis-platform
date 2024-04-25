<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");
require_once (dirname(__FILE__)."/classes/Crons.php");
include_once("functions.php");

if($module == ""){
    $module = $this;
}
$report = htmlentities($_GET['report'],ENT_QUOTES);

if($cronAttributes['cron_name'] == ""){
    #MANUAL
    if(!empty($report) && array_key_exists('report', $_GET)) {
        Crons::runCacheReportCron($module, $project_id, $report);
        Crons::runGraphReportCron($module, $project_id, $report);
    }else {
        Crons::runCacheCron($module, $project_id,true);
        Crons::runCacheReportCron($module, $project_id, null,true);
        Crons::runGraphCron($module, $project_id,true);
        Crons::runGraphReportCron($module, $project_id, null,true);
    }
}else{
    #CRONS
    if ($cronAttributes['cron_name'] == 'dashboard_cache_file'){
        Crons::runCacheCron($module, $project_id,true);
    }else if ($cronAttributes['cron_name'] == 'dashboard_cache_file_report'){
        Crons::runCacheReportCron($module, $project_id, null,true);
    }else if ($cronAttributes['cron_name'] == 'dashboard_cache_file_graph'){
        Crons::runGraphCron($module, $project_id,true);
    }else if ($cronAttributes['cron_name'] == 'dashboard_cache_file_graph_report'){
        Crons::runGraphReportCron($module, $project_id, null,true);
    }
}