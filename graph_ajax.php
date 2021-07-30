<?php
namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

include_once ("functions.php");
require_once (dirname(__FILE__)."/classes/GraphData.php");

$question_1 = $_REQUEST['question_1'];
$study = $_REQUEST['study'];
$studyOption = $_REQUEST['studyOption'];
$study_options = json_decode($_REQUEST['study_options']);
$conditionDate = $_REQUEST['conditionDate'];
$question = $_REQUEST['question'];
$project_id = $_GET['pid'];

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
    if($question == 2){

    }else{
        $graph = GraphData::getNormalStudyColGraph($question,$project_id,$study_options,$study,$question_1,$conditionDate,$topScoreMax,$graph);
        $graph = GraphData::getMissingColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph);
    }
}
$graph = GraphData::getTotalColGraph($question,$project_id,$question_1,$conditionDate,$topScoreMax,$graph);
if($study == 61){
    $graph = GraphData::getMultipleColGraph($question,$project_id,$study,$question_1,$conditionDate,$topScoreMax,$graph);
}
$chartgraph = GraphData::graphArrays($graph,$study_options);
echo json_encode(array(
        'status' => 'success',
        'graph' => json_encode($graph),
        'studyOption' => $studyOption,
        'question' => $question,
        'chartgraph' => json_encode($chartgraph)
    )
);
?>