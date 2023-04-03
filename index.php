<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
?>
<meta name="viewport" content="width=device-width, initial-scale=1">


<?php include_once("head_scripts.php");?>
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
    $privacy = $module->getProjectSetting('privacy');

    #SESSION
//    session_write_close();
//    session_name("EPV");
//    session_id($_COOKIE["EPV"]);
//    session_start();

    if($privacy == "private"){
        $project_id = (int)$_GET['pid'];

        #TOKEN
        if(!array_key_exists('token', $_REQUEST) && empty($_SESSION['token']["EPV".$project_id])){

        }else if(empty($_SESSION['token']["EPV".$project_id])){
            session_write_close();
            session_name("EPV".$project_id);
            session_id($_COOKIE["EPV".$project_id]);
            session_start();
        }

        $token = "";
        $project_id_registration = $module->getProjectSetting('registration');
        $option = htmlentities($_REQUEST['option'],ENT_QUOTES);
        if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_REQUEST['token'],$project_id_registration)){
            $token = $_REQUEST['token'];
        }else if(!empty($_SESSION['token']["EPV".$project_id])&& \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_SESSION['token']["EPV".$project_id],$project_id_registration)) {
            $token = $_SESSION['token']["EPV".$project_id];
        }

        //Session OUT
        if(array_key_exists('sout', $_REQUEST)){
            unset($_SESSION['token']["EPV".$project_id]);
            if(isset($_COOKIE["EPV".$project_id])):
                setcookie("EPV".$project_id, '', time()-7000000, '/');
            endif;
            $_SESSION['token']["EPV".$project_id] = "";
        }

        if(array_key_exists('token', $_REQUEST)  && !empty($_REQUEST['token']) && \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_REQUEST['token'],$project_id_registration)) {
            $_SESSION['token']["EPV".$project_id] = $_REQUEST['token'];
        }
        if( !array_key_exists('token', $_REQUEST) && !array_key_exists('request', $_REQUEST) && empty($_SESSION['token']["EPV".$project_id])){
            include('login.php');
        }else if(!array_key_exists('option', $_REQUEST) && !empty($_SESSION['token']["EPV".$project_id]) && \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_SESSION['token']["EPV".$project_id],$project_id_registration)){
            include_once ('dashboard_private.php');
        }else if(array_key_exists('option', $_REQUEST) && $option === 'sac' && !empty($_SESSION['token']["EPV".$project_id]) && \Vanderbilt\DashboardAnalysisPlatformExternalModule\isTokenCorrect($_SESSION['token']["EPV".$project_id],$project_id_registration)) {
            include_once('stats_and_charts.php');
        }else{
            echo "<script>$(document).ready(function() { $('#hub_error_message').show(); $('#hub_error_message').html('<strong>This Access Link has expired. </strong> <br />Please request a new Access Link below.');});</script>";
            include('login.php');
        }
    }else{
        header('Location: '.$module->getUrl('dashboard_public.php'));
    }
    ?>
</div>
</body>
</html>