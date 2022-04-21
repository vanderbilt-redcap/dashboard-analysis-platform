<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class DashboardAnalysisPlatformExternalModule extends AbstractExternalModule
{
    function dashboard_cache_file($cronAttributes){
       error_log("dashboardCacheFile");
        foreach ($this->getProjectsWithModuleEnabled() as $project_id){
            try {
                #CRONS
                include("callCron.php");

            } catch (Throwable $e) {
                \REDCap::email('datacore@vumc.org', 'datacore@vumc.org',"Cron Error", $e->getMessage());
            }
        }
    }
}
?>