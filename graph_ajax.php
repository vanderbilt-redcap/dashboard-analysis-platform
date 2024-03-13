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
$report = $_REQUEST['report'];
$question = $_REQUEST['question'];
$project_id = $_GET['pid'];
$path = $module->getProjectSetting('path',$project_id);

#LOAD THE FILE
if(!empty($report)){
    $filename = "dashboard_cache_graph_file_" . $project_id . "_report_" . $report . ".txt";
}else{
    $filename = "dashboard_cache_graph_file_".$project_id.".txt";
}

if(empty($path)) {
    $q = $module->query("SELECT docs_id FROM redcap_docs WHERE project_id=? AND docs_name=?", [$project_id, $filename]);
    while ($row = db_fetch_assoc($q)) {
        $docsId = $row['docs_id'];
        $q2 = $module->query("SELECT doc_id FROM redcap_docs_to_edocs WHERE docs_id=?", [$docsId]);
        while ($row2 = db_fetch_assoc($q2)) {
            $docId = $row2['doc_id'];
            $q3 = $module->query("SELECT doc_name,stored_name,doc_size,file_extension,mime_type FROM redcap_edocs_metadata WHERE doc_id=? AND delete_date is NULL", [$docId]);
            while ($row3 = $q3->fetch_assoc()) {
                $path = $module->getSafePath($row3['stored_name'], EDOC_PATH);
                $strJsonFileContents = file_get_contents($path);
                $graph = json_decode($strJsonFileContents, true);
            }
        }
    }
}else{
    $strJsonFileContents = file_get_contents($path);
    $graph = json_decode($strJsonFileContents, true);
}

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