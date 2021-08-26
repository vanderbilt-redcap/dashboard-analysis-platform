<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

include_once ("functions.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");

$question_1 = $_GET['question_1'];
$study = $_GET['study'];
$studyOption = $_GET['studyOption'];
$study_options = json_decode($_GET['study_options']);
$conditionDate = $_GET['conditionDate'];
$question = $_GET['question'];
$project_id = (int)$_GET['pid'];

$outcome_labels = $module->getChoiceLabels($question_1, $project_id);
$topScoreMax = count($outcome_labels);

$graph = array();
$graph[$question_1] = array();
$graph[$question_1]["total"] = array();
$graph[$question_1]["total"]['graph_top_score_year'] = array();
$graph[$question_1]["total"]['graph_top_score_month'] = array();
$graph[$question_1]["total"]['graph_top_score_quarter'] = array();
$graph[$question_1]["total"]['years']= array();
$graph[$question_1]["no"] = array();
$graph[$question_1]["no"]['graph_top_score_year'] = array();
$graph[$question_1]["no"]['graph_top_score_month'] = array();
$graph[$question_1]["no"]['graph_top_score_quarter'] = array();
$graph[$question_1]["no"]['years']= array();


if($studyOption != "nofilter"){
    $graph = GraphData::getNormalStudyColGraph($question,$project_id,$study_options,$study,$question_1,$conditionDate,$topScoreMax,$graph);
    $graph = GraphData::getMissingColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph);
}
$graph = GraphData::getTotalColGraph($question,$project_id,$question_1,$conditionDate,$topScoreMax,$graph);
if($study == 61){
    $graph = GraphData::getMultipleColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph);
}
$chartgraph = GraphData::graphArrays($graph,$study_options);
echo json_encode(array(
        'status' => 'success',
        'graph' => json_encode($graph),
        'studyOption' => htmlspecialchars($studyOption,ENT_QUOTES),
        'question' => htmlspecialchars($question,ENT_QUOTES),
        'chartgraph' => json_encode($chartgraph)
    )
);
?>