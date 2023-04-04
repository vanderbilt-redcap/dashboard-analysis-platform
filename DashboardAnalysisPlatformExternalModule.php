<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class DashboardAnalysisPlatformExternalModule extends AbstractExternalModule
{
    function dashboard_cache_file($cronAttributes){
        foreach ($this->getProjectsWithModuleEnabled() as $project_id){
            try {
                error_log("dashboardCacheFile PID".$project_id);
                #CRONS
                include("callCron.php");

            } catch (Throwable $e) {
                \REDCap::email('datacore@vumc.org', 'datacore@vumc.org',"Cron Error", $e->getMessage());
            }
        }
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