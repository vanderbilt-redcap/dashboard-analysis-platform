<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
?>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script type="text/javascript" src="<?=$module->getUrl('js/jquery-3.3.1.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/jquery-ui.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('bootstrap-3.3.7/js/bootstrap.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/fontawesome-free-5.15.2-web/js/all.js')?>"></script>

<script type="text/javascript" src="<?=$module->getUrl('js/jquery.tablesorter.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/sortable.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/Chart.min.js')?>"></script>

<script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
<script type="text/javascript" src="<?=$module->getUrl('js/dataTables.buttons.min.js')?>"></script>

<script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>

<link type='text/css' href='<?=$module->getUrl('bootstrap-3.3.7/css/bootstrap.min.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/sortable-theme-bootstrap.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/style.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/jquery-ui.min.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('css/jquery.dataTables.min.css')?>' rel='stylesheet' media='screen' />
<link type='text/css' href='<?=$module->getUrl('js/fontawesome-free-5.15.2-web/css/fontawesome.css')?>' rel='stylesheet' media='screen' />

<link rel='stylesheet' href='https://use.typekit.net/nxh7lyk.css'>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<?php include_once ("functions.php");?>

<script type='text/javascript'>
    var app_path_webroot = '<?=APP_PATH_WEBROOT?>';
    var app_path_webroot_full = '<?=APP_PATH_WEBROOT_FULL?>';
    var app_path_images = '<?=APP_PATH_IMAGES?>';
    $(document).ready(function() {
        Sortable.init();
        $('[data-toggle="tooltip"]').tooltip();

        $('input[name="daterange"]').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        });

        $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        });


        $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

    } );
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta http-equiv="Cache-control" content="public">
    <meta name="theme-color" content="#fff">
    <link rel="icon" href="">

    <title>At-A-Glance Dashboard - Empowering the Participant Voice</title>
</head>
<body>
<div class="container">
    <?php
    $token = "";
    $project_id = (int)$_GET['pid'];
    $project_id_registration = $module->getProjectSetting('registration');
    if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_REQUEST['token'],$project_id_registration)){
        $token = $_REQUEST['token'];
    }else if(!empty($_SESSION['token']["EPV".$project_id])&& \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_SESSION['token']["EPV".$project_id],$project_id_registration)) {
        $token = $_SESSION['token']["EPV".$project_id];
    }

    //Session OUT
    if(array_key_exists('sout', $_REQUEST)){
        unset($_SESSION['token']["EPV".$project_id]);
    }

    if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_REQUEST['token'],$project_id_registration)) {
        $_SESSION['token']["EPV".$project_id] = $_REQUEST['token'];
    }
    if( !array_key_exists('token', $_REQUEST) && !array_key_exists('request', $_REQUEST) && empty($_SESSION['token']["EPV".$project_id])){
        include('login.php');
    }else if(!empty($_SESSION['token']["EPV".$project_id]) && \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_SESSION['token']["EPV".$project_id],$project_id_registration)){
        include_once ('dashboard_cache.php');
    }else{
        echo "<script>$(document).ready(function() { $('#hub_error_message').show(); $('#hub_error_message').html('<strong>This Access Link has expired. </strong> <br />Please request a new Access Link below.');});</script>";
        include('login.php');
    }
    ?>
</div>
</body>
</html>