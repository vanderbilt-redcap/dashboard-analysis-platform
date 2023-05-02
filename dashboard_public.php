<?php
$project_id = (int)$_GET['pid'];
$privacy = $module->getProjectSetting('privacy',$project_id);
if($privacy == "public") {
    include_once('head_html.php');
    include_once('dashboard_cache.php');
}
?>