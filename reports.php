<div>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="<?=$module->getUrl("index.php?report=")?>">Home</a>
        </li>
        <?php
        $custom_report_id = $module->getProjectSetting('custom-report-id');
        $custom_report_label = $module->getProjectSetting('custom-report-label');
        $project_id = (int)$_GET['pid'];
        foreach ($custom_report_id as $index => $rid){
            echo '<li class="nav-item">
                    <a class="nav-link" href="'.$module->getUrl("index.php?pid=".$project_id."&report=".$rid).'">'.$custom_report_label[$index].'</a>
                 </li>';
        }
        ?>
    </ul>
</div>