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
    #SESSION
    session_write_close();
    session_name("EPV");
    session_id($_COOKIE["EPV"]);
    session_start();
    ?>
<?php include_once ('dashboard_cache.php'); ?>