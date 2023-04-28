
<?php
$custom_report_id = $module->getProjectSetting('custom-report-id');
$custom_report_label = $module->getProjectSetting('custom-report-label');
$project_id = (int)$_GET['pid'];
$report = htmlentities($_GET['report'],ENT_QUOTES);

if(!empty($custom_report_id)){?>
<div style="padding-top: 10px">
    <ul class="nav nav-tabs">

        <?php
        $isActive = false;
        $navigation = "";
        foreach ($custom_report_id as $index => $rid){
            $active = "";
            if($report == $rid){
                $active = "active";
                $isActive = true;
            }
            $navigation = '<li class="nav-item">
                    <a class="nav-link '.$active.'" href="'.$module->getUrl("index.php?pid=".$project_id."&report=".$rid).'">'.$custom_report_label[$index].'</a>
                 </li>';
        }
        $active = "";
        if(!$isActive){
            $active = "active";
        }
        ?>
        <li class="nav-item">
            <a class="nav-link <?=$active?>" aria-current="page" href="<?=$module->getUrl("index.php?report=")?>">All Data</a>
        </li>
        <?php echo $navigation;?>
    </ul>
</div>
<?php } ?>