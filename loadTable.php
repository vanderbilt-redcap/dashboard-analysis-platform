<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;
require_once (dirname(__FILE__)."/classes/ProjectData.php");

session_start();
$timestamp = strtotime(date("Y-m-d H:i:s"));
$_SESSION[$_GET['pid']."_dash_timestamp"] = $timestamp;
$_SESSION[$_GET['pid']."_question"] = $_POST['question'];
$_SESSION[$_GET['pid']."_study"] = $_POST['study'];
$daterange = explode(" - ",$_POST['daterange']);
$_SESSION[$_GET['pid']."_startDate"] = $daterange[0];
$_SESSION[$_GET['pid']."_endDate"] = $daterange[1];

$codeCrypt = ProjectData::getCrypt("start_".$timestamp,'e',$secret_key,$secret_iv);

echo json_encode($codeCrypt);
?>