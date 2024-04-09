<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use Exception;
use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class DashboardAnalysisPlatformExternalModule extends AbstractExternalModule
{
	public static $cachedLabels = [];
	
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
    function dashboardCacheCron($cronAttributes){
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
                $stop_cron = $this->getProjectSetting('stop-cron',$project_id);
                if(!$stop_cron) {
                    #CRONS
                    $filename = "dashboard_cache_file_" . $project_id . ".txt";
                    $q = $this->query("SELECT em.stored_name FROM redcap_edocs_metadata em 
                                    LEFT JOIN redcap_docs_to_edocs de ON em.doc_id = de.doc_id 
                                    LEFT JOIN redcap_docs rd ON rd.docs_id = de.docs_id 
                                    WHERE rd.project_id=? AND rd.docs_name=?",
                        [$project_id, $filename]);
                    $found = false;
                    while ($row = db_fetch_assoc($q)) {
                        $storedName = $row['stored_name'];
                        $today = strtotime(date("Ymd"));
                        $file_date = strtotime(date("Ymd", strtotime(explode("_pid" . $project_id . "_", $storedName)[0])));
                        #We make sure we only do this once a day
                        if ($today > $file_date) {
                            $found = true;
                            error_log("dashboardCacheFile PID " . $project_id . ", " . $cronAttributes['cron_name']);
                            include("callCron.php");
                        }
                    }
                    if (!$found) {
                        error_log("dashboardCacheFile PID " . $project_id . ", " . $cronAttributes['cron_name']);
                        include("callCron.php");
                    }
                }

            } catch (Throwable $e) {
                \REDCap::email('datacore@vumc.org', 'datacore@vumc.org',"Cron Error", $e->getMessage());
            }
        }
        $this->setSystemSetting($lastRunSettingName, time());
    }
	
	public function getCachedChoiceLabels($fieldName, $projectId) {
		if(!array_key_exists($projectId,self::$cachedLabels)) {
			self::$cachedLabels[$projectId] = [];
		}
		
		if(!array_key_exists($fieldName,self::$cachedLabels[$projectId])) {
			self::$cachedLabels[$projectId][$fieldName] = $this->getChoiceLabels($fieldName,$projectId);
		}
		return self::$cachedLabels[$projectId][$fieldName];
	}

    public function redcap_module_link_check_display($project_id, $link) {
        $privacy = $this->getProjectSetting('privacy',$project_id);
        if($privacy == "public"){
            $link['url'] = $this->getUrl("index.php"). "&NOAUTH";
//            return parent::redcap_module_link_check_display($project_id,$link);
        }
        #Let users always see the link/page if they are added into the project
        return true;
    }

    /**
     * Function to increase the Maximum Execution Time
     * @param $hours
     */
    public function increaseProcessingMax($hours) {
        require_once(APP_PATH_DOCROOT."Classes/System.php");

        \System::increaseMaxExecTime($hours * 3600);
    }
}
?>