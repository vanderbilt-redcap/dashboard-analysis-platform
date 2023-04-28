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

if(!empty($report) && array_key_exists('report', $_GET)){
    Crons::runCacheReportCron($module, $project_id, $report);
}else{
    Crons::runCacheCron($module, $project_id);
    Crons::runCacheReportCron($module, $project_id, null);
}