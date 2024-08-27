<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

include_once ("functions.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");

$question_1 = $_REQUEST['question_1'];
$study = ($_REQUEST['study'] != "bysite") ? $_REQUEST['study'] : ProjectData::INSTITUTIONS_ARRAY_KEY;
$studyOption = $_REQUEST['studyOption'];
$report = $_REQUEST['report'];
$question = $_REQUEST['question'];
$project_id = (int)$_GET['pid'];

#LOAD THE FILE
$graph = ProjectData::getFileData($module, $project_id, "dashboard_cache_graph_file_", $report);
$chartgraph = array();
if($graph != "" && is_array($graph)){
    $responses_percent = array();
    $responses_na = array();
    $date_array = ["month","quarter", "year"];
    $aux = array();
    $aux_n = array();
    foreach ($date_array as $date) {
        $responses_percent[$date] = array();
        $responses_na[$date] = array();
        [$aux,$aux_n] = GraphData::createChartArray($graph, $question, $question_1, $study, $date, $aux, $aux_n);

        $responses_percent[$date] = $aux;
        $responses_na[$date] = $aux_n;
        $chartgraph["results"][$date][$question_1] = $responses_percent[$date];
        $chartgraph["responses_na"][$date][$question_1] = $responses_na[$date];
    }
    if($study == ProjectData::INSTITUTIONS_ARRAY_KEY){
        $study = "nofilter";
    }
    $chartgraph = GraphData::createChartArrayLabels($graph, $question, $question_1, $study, $date_array, $chartgraph);
}

echo json_encode(array(
        'status' => 'success',
        'studyOption' => htmlspecialchars($studyOption, ENT_QUOTES),
        'question' => htmlspecialchars($question, ENT_QUOTES),
        'chartgraph' => json_encode($chartgraph)
    )
);

?>