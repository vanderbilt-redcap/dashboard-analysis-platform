<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

include_once ("functions.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");

$question_1 = $_REQUEST['question_1'];
$study = $_REQUEST['study'];
$studyOption = $_REQUEST['studyOption'];
$report = $_REQUEST['report'];
$question = $_REQUEST['question'];
$project_id = $_GET['pid'];

#LOAD THE FILE
$graph = ProjectData::getFileData($module, $project_id, "dashboard_cache_graph_file_", $report);

$chartgraph = array();
if($graph != "" && is_array($graph)){
    $chartgraph["results"]['month'][$question_1] = $graph[$question][$study]["results"]["month"][$question_1];
    $chartgraph["results"]['quarter'][$question_1] = $graph[$question][$study]["results"]["quarter"][$question_1];
    $chartgraph["results"]['year'][$question_1] = $graph[$question][$study]["results"]["year"][$question_1];
    $chartgraph["labels"]['month'][$question_1] = $graph[$question][$study]["labels"]["month"][$question_1];
    $chartgraph["labels"]['quarter'][$question_1] = $graph[$question][$study]["labels"]["quarter"][$question_1];
    $chartgraph["labels"]['year'][$question_1] = $graph[$question][$study]["labels"]["year"][$question_1];
}

echo json_encode(array(
        'status' => 'success',
        'studyOption' => htmlspecialchars($studyOption, ENT_QUOTES),
        'question' => htmlspecialchars($question, ENT_QUOTES),
        'chartgraph' => json_encode($chartgraph)
    )
);

?>