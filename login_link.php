<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

require_once(dirname(__FILE__)."/classes/ProjectData.php");
include_once(__DIR__ . "/../functions.php");

$project_id = (int)$_GET['pid'];
$project_id_registration = $module->getProjectSetting('registration');
$from = empty($module->getProjectSetting('registration_from'))?"noreply@vumc.org":$module->getProjectSetting('registration_from');
$result = "";

if(!empty($_REQUEST['email'])) {
    $RecordSetEmail = \REDCap::getData($project_id_registration, 'array', null,null,null,null,false,false,false,"[email_2] ='".$_REQUEST['email']."'");
    $records = ProjectData::getProjectInfoArray($RecordSetEmail);
    foreach ($records as $people){
        if(strtolower($people['email_2']) == strtolower($_REQUEST['email']) && $people['active_user'] == '1'){
            $arrayLogin = array(array('record_id' => $people['record_id']));

            $token = ProjectData::getRandomIdentifier(12);
            $access_dur = 7;

            $url = $module->getUrl("index.php?token=".$token."&pid=".$project_id);
            $message = "<html>Here is your link to access the EPV At-A-Glance Dashboard:<br/><a href='".$url."'>".$url."</a><br/><br/><span style='color:#e74c3c'>**This link is unique to you and should not be forwarded to others.</span><br/>".
                "This link will expire in ".$access_dur." days. You can request a new link at any time, which will invalidate the old link. If you are logging into the Hub from a public computer, please remember to log out of the Hub to invalidate the link.</html>";

            \REDCap::email(strtolower($people['email_2']),$from,"EPV At-A-Glance Dashboard Access Link",$message);

            $arrayLogin[0]['token'] = $token;
            $arrayLogin[0]['token_expiration_date'] = date('Y-m-d', strtotime("+".$access_dur." day"));

            $json = json_encode($arrayLogin);
            $results = \Records::saveData($project_id_registration, 'json', $json,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
            \Records::addRecordToRecordListCache($project_id_registration, $people['record_id'],1);
            error_log(json_encode($arrayLogin,JSON_PRETTY_PRINT));
        }else if($people == "" || strtolower($people['email_2']) != strtolower($_POST['email'])){
            $message = "<pre>".print_r($people,true)."</pre><pre>".print_r($records,true)."</pre><br>active: ".$people['active_user']."<br>email: ".$people['email_2']."<html>This email address does not exist in the Hub.<br><br>".
                "Your email address may not be registered in the system or you may be registered under a different email.</html>";

            \REDCap::email(strtolower($_REQUEST['email']),$from,"Access Denied for EPV At-A-Glance Dashboard",$message);
        }
    }
}


echo json_encode($result);
?>