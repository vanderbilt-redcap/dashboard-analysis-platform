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
$project_id = (int)$_GET['pid'];

#LOAD THE FILE
$graph = ProjectData::getFileData($module, $project_id, "dashboard_cache_graph_file_", $report);

$chartgraph = array();
if($graph != "" && is_array($graph)){
    $responses_percent = array();
    $responses_na = array();
    $date_array = ["month","quarter", "year"];
    foreach ($date_array as $date) {
        $responses_percent[$date] = array();
        $responses_na[$date] = array();
        foreach ($graph[$question][$study]["results"][$date][$question_1] as $question_data => $data) {
            $aux[$question_data] = array();
            $aux_n[$question_data] = array();
            foreach ($data as $index => $value) {
                $percent_values = explode(",", $value);
                $aux[$question_data][$index] = $percent_values[0];
                $aux_n[$question_data][$index] = $percent_values[1];
            }
        }
        $responses_percent[$date] = $aux;
        $responses_na[$date] = $aux_n;
        $chartgraph["results"][$date][$question_1] = $responses_percent[$date];
        $chartgraph["responses_na"][$date][$question_1] = $responses_na[$date];
    }
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