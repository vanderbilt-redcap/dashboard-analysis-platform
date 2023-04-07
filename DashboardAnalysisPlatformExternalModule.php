<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class DashboardAnalysisPlatformExternalModule extends AbstractExternalModule
{
    /**
     * This cron will only perform actions once a day between the hours
     * of midnight and 6am, regardless of how often it is scheduled.
     * Scheduling it more often is required to ensure it runs at a predictable time.
     * For example, scheduling it for once every 10 minutes will guarantee that
     * it runs at the first available minute after 12:10am,
     * though it may run as soon as 12am.
     * This produces behavior similar to timed crons.
     * See the API Sync module's per project sync settings for a more advanced
     * example of this same concept.
     */
    function dashboard_cache_file($cronAttributes){
        $hourRange = 6;
        if(date('G') > $hourRange){
            // Only perform actions between 12am and 6am.
            return;
        }
        $lastRunSettingName = 'last-cron-run-time';
        $lastRun = empty($this->getSystemSetting($lastRunSettingName)) ? $this->getSystemSetting($lastRunSettingName) : 0;
        $hoursSinceLastRun = (time()-$lastRun)/60/60;
        if($hoursSinceLastRun < $hourRange){
            // We're already run recently
            return;
        }

        // Perform cron actions here
        foreach ($this->getProjectsWithModuleEnabled() as $project_id){
            try {
                error_log("dashboardCacheFile PID".$project_id);
                error_log("dashboardCacheFile hourRange: ".date('G')." > ".$hourRange);
                error_log("dashboardCacheFile lastRunSettingName: ".$lastRunSettingName);
                #CRONS
                include("callCron.php");

            } catch (Throwable $e) {
                \REDCap::email('datacore@vumc.org', 'datacore@vumc.org',"Cron Error", $e->getMessage());
            }
        }
        $this->setSystemSetting($lastRunSettingName, time());
    }


    public function redcap_module_link_check_display($project_id, $link) {
        $privacy = $this->getProjectSetting('privacy');
        if($privacy == "public"){
            $link['url'] = $this->getUrl("index.php?NOAUTH");
//            return parent::redcap_module_link_check_display($project_id,$link);
        }
        #Let users always see the link/page if they are added into the project
        return true;
    }
}
?>