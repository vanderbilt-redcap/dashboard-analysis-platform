<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");


$timestamp = strtotime(date("Y-m-d H:i:s"));
$_SESSION[$_GET['pid']."_dash_timestamp"] = $timestamp;
$_SESSION[$_GET['pid']."_question"] = $_POST['question'];
$_SESSION[$_GET['pid']."_study"] = $_POST['study'];

$codeCrypt = ProjectData::getCrypt("start_".$timestamp,'e',$secret_key,$secret_iv);

echo json_encode($codeCrypt);
?>