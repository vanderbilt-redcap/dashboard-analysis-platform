<?php

namespace Vanderbilt\DashboardAnalysisPlatformExternalModule;

use ExternalModules\AbstractExternalModule;
use Vanderbilt\REDCapDataCore\REDCapCalculations;

class R4Report extends AbstractExternalModule
{
	## Set by constructor
	private $projectId;
	private $recordIds;
	private $recordIdField;
	private $eventId;
	
	
	private $projectMetadata;
	private $projectData;
	private $recordIdFlipped;
	private $institutionList;
	
	## Output data for report
	private $tableData = [];
	private $tooltipCounts = [];
	private $tooltipTextArray = [];
	private $surveyPercentages = [];
	private $surveyPercentagesInstitutions = [];
	
	## Caching of processed data
	private $cachedFieldOptions = [];
	private $cachedRecordContainsField = [];
	private $cachedRecordMissingField = [];
	private $cachedRecordWithFieldValue = [];
	private $cachedNARecordsByField = [];
	private $cachedTopScoreRecordsByField = [];
	
	private $cachedCompleteRecords = [];
	private $cachedPartialRecords = [];
	private $cachedBreakoffRecords = [];
	private $cachedAnyRecords = [];
	
	public function __construct($projectId, $recordIds = []) {
		## Since we're extended AbstractExternalModule, need a PREFIX and VERSION from the parent module
		$parentModule = new DashboardAnalysisPlatformExternalModule();
		
		$this->PREFIX = $parentModule->PREFIX;
		$this->VERSION = $parentModule->VERSION;
		
		$this->projectId = $projectId;
		$this->recordIds = $recordIds;
		$this->recordIdField = $this->getRecordIdField($this->projectId);
	
		REDCapCalculations::$recordIdField = $this->recordIdField;
	}
	
	public function calculateCacheCronData() {
		
		$this->createQuestion_1();
		$this->createQuestion_3();
		
		$this->createTableData();
		
		return $this->tableData;
	}
	
	public function getInstitutionData() {
		if(!isset($this->institutionList)) {
			$this->institutionList = ProjectData::getAllInstitutions($this->getProjectData());
		}
		return $this->institutionList;
	}
	
	public function getProjectMetadata() {
		if(!isset($this->projectMetadata)) {
			$this->projectMetadata = \REDCap::getDataDictionary($this->projectId,"array");
		}
		return $this->projectMetadata;
	}
	
	public function getProjectData() {
		if(!isset($this->projectData)) {
			$this->projectData = \REDCap::getData($this->projectId, "json-array", $this->recordIds);
			
			$this->recordIdFlipped = REDCapCalculations::mapFieldByRecord($this->projectData,$this->recordIdField);
		}
		return $this->projectData;
	}
	
	public function getFieldType($field_name)
	{
		// Array to translate back-end field type to front-end (some are different, e.g. "textarea"=>"notes")
		$fieldTypeTranslator = array('textarea'=>'notes', 'select'=>'dropdown');
		// Get field type
		$fieldType = $this->getProjectMetadata()[$field_name]['element_type'];
		// Translate field type, if needed
		if (isset($fieldTypeTranslator[$fieldType])) {
			$fieldType = $fieldTypeTranslator[$fieldType];
		}
		// Return field type
		return $fieldType;
	}
	
	public function getFieldChoices($fieldName) {
		if(!array_key_exists($fieldName,$this->cachedFieldOptions)) {
			$study_options = $this->getChoiceLabels($fieldName, $this->projectId);
			if ($fieldName == "rpps_s_q62") {
				array_push($study_options, ProjectData::getExtraColumTitle());
			}
			
			$this->cachedFieldOptions[$fieldName] = $study_options;
		}
		
		return $this->cachedFieldOptions[$fieldName];
	}
	
	public function getRecordsContainField($fieldName) {
		if(!array_key_exists($fieldName,$this->cachedRecordContainsField)) {
			$fieldType = $this->getFieldType($fieldName);
			
			if($fieldType == "checkbox") {
				$fieldLabels = $this->getFieldChoices($fieldName);
				$studyFieldList = [];
				foreach($fieldLabels as $value => $label) {
					$studyFieldList[] = $fieldName."___".$value;
				}
				$containsData = REDCapCalculations::mapFieldByRecord($this->projectData,$studyFieldList,[],false);
			}
			else {
				$containsData = REDCapCalculations::mapFieldByRecord($this->projectData,$fieldName,[],false);
			}
			
			$this->cachedRecordContainsField[$fieldName] = $containsData;
		}
		
		return $this->cachedRecordContainsField[$fieldName];
	}
	
	public function getRecordsMissingField($fieldName) {
		if(!array_key_exists($fieldName,$this->cachedRecordMissingField)) {
			$containsData = $this->getRecordsContainField($fieldName);
			$this->cachedRecordMissingField[$fieldName] = [];
			
			foreach($this->recordIdFlipped as $recordId => $value) {
				if(!array_key_exists($recordId,$containsData)) {
					$this->cachedRecordMissingField[$fieldName][$recordId] = 1;
				}
			}
		}
		
		return $this->cachedRecordMissingField[$fieldName];
	}
	
	public function getRecordsByFieldValue($fieldName,$value) {
		if(!array_key_exists($fieldName,$this->cachedRecordWithFieldValue)) {
			$this->cachedRecordWithFieldValue[$fieldName] = [];
		}
		
		if(!array_key_exists($value,$this->cachedRecordWithFieldValue[$fieldName])) {
			if($value == "missing") {
				$this->cachedRecordWithFieldValue[$fieldName][$value] = $this->getRecordsMissingField($fieldName);
			}
			else {
				$fieldType = $this->getFieldChoices($fieldName);
				
				if($fieldType == "checkbox") {
					$matchingFieldName = $fieldName."___".$value;
					$value = 1;
				}
				else {
					$matchingFieldName = $fieldName;
				}
				
				## Special handling for the category "Are you of Spanish or Hispanic..."
				## with value 6 => "Yes - ALL Spanish/Hispanic/Latino"
				if($fieldName == "rpps_s_62" && $value == 6) {
					$this->cachedRecordWithFieldValue[$fieldName][$value] = REDCapCalculations::mapFieldByRecord(
						$this->projectData,$fieldName,["2","3","4","5"],false
					);
				}
				else {
					$this->cachedRecordWithFieldValue[$fieldName][$value] = REDCapCalculations::mapFieldByRecord(
						$this->projectData,$matchingFieldName,[$value],false
					);
				}
			}
		}
		
		return $this->cachedRecordWithFieldValue[$fieldName][$value];
	}
	
	public function getTopScoreRecords($fieldName) {
		if(!array_key_exists($fieldName,$this->cachedTopScoreRecordsByField)) {
			$outcome_labels = $this->getFieldChoices($fieldName);
			$topScoreMax = count($outcome_labels);
			
			$topScoreValues = ProjectData::getTopScoreValues($topScoreMax,$fieldName);
			
			$topScoreRecords = [];
			foreach($topScoreValues as $thisVal) {
				$topScoreRecords = array_merge(
					$topScoreRecords,
					$this->getRecordsByFieldValue($fieldName,$thisVal)
				);
			}
			
			$this->cachedTopScoreRecordsByField[$fieldName] = $topScoreRecords;
		}
		return $this->cachedTopScoreRecordsByField[$fieldName];
	}
	
	public function getNARecords($fieldName) {
		if(!array_key_exists($fieldName,$this->cachedNARecordsByField)) {
			if(!$this->isNAField($fieldName)) {
				return false;
			}
			$outcome_labels = $this->getFieldChoices($fieldName);
			$topScoreMax = count($outcome_labels);
			
			if($topScoreMax != 5) {
				return [];
			}
			
			$this->cachedNARecordsByField[$fieldName] = $this->getRecordsByFieldValue($fieldName,5);
		}
		return $this->cachedNARecordsByField[$fieldName];
	}
	
	public function isNAField($fieldName) {
		return in_array($fieldName,ProjectData::getRowQuestionsParticipantPerception());
	}
	
	public function calculateRecordCompletion() {
		$completionFields = ProjectData::getRowQuestionsParticipantPerception();
		
		foreach($completionFields as $thisField) {
			$this->getRecordsContainField($thisField);
		}
		
		foreach($this->recordIdFlipped as $recordId => $value) {
			$countInField = 0;
			foreach($completionFields as $thisField) {
				if(array_key_exists($recordId,$this->cachedRecordContainsField[$thisField])) {
					$countInField++;
				}
			}
			
			$percentComplete = $countInField / count($completionFields);
			if($percentComplete >= 0.8) {
				$this->cachedCompleteRecords[$recordId] = 1;
			}
			else if($percentComplete >= 0.5) {
				$this->cachedPartialRecords[$recordId] = 1;
			}
			else if($percentComplete > 0) {
				$this->cachedBreakoffRecords[$recordId] = 1;
			}
			
			if($percentComplete > 0) {
				$this->cachedAnyRecords[$recordId] = 1;
			}
		}
	}
	
	public function addTooltipCounts($study,$survey) {
		$this->addArrayIndexes($study,$survey);
		
		$this->calculateStudyScores($study, $survey);
		
		$this->calculateInstitutionScores($study,$survey);
		
		$this->calculateTotalScores($study,$survey);
	}
	
	public function addArrayIndexes($study,$survey) {
		if(!array_key_exists($study,$this->tooltipCounts)) {
			$this->tooltipTextArray[$study] = [];
			$this->surveyPercentages[$study] = [];
			$this->tooltipCounts[$study] = [];
			$this->surveyPercentagesInstitutions[$study] = [];
		}
		
		if(!array_key_exists($survey,$this->tooltipCounts[$study])) {
			$this->tooltipTextArray[$study][$survey] = [];
			$this->surveyPercentages[$study][$survey] = [];
			$this->tooltipCounts[$study][$survey] = [];
			$this->surveyPercentagesInstitutions[$study][$survey] = [];
		}
	}
	
	public function calculateStudyScores($study, $survey) {
		$containsSurvey = $this->getRecordsContainField($survey);
		$missingSurvey = $this->getRecordsMissingField($survey);
		$naSurvey = $this->getNARecords($survey);
		$topScoreSurvey = $this->getTopScoreRecords($survey);
		
		$studyOptions = $this->getFieldChoices($study);
		
		## Add label for missing the study field
		$studyOptions["missing"] = "Missing";
		
		foreach($studyOptions as $value => $label) {
			$matchesStudyValue = $this->getRecordsByFieldValue($study,$value);
			$applicableRecords = array_intersect_key($matchesStudyValue,$containsSurvey);
			
			$this->tooltipCounts[$study][$survey][$value] = [
				"responses" => count($applicableRecords),
				"missing" => count(array_intersect_key($matchesStudyValue,$missingSurvey)),
				"topScore" => count(array_intersect_key($matchesStudyValue,$topScoreSurvey))
			];
			
			$tooltip = $this->tooltipCounts[$study][$survey][$value]["responses"]." responses";
			$tooltip .= ", ".$this->tooltipCounts[$study][$survey][$value]["missing"]." missing";
			if($naSurvey !== false) {
				$this->tooltipCounts[$study][$survey][$value]["NA"] =
					count(array_intersect_key($matchesStudyValue,$naSurvey));
				
				$tooltip .= ", ".$this->tooltipCounts[$study][$survey][$value]["NA"]." not applicable";
				
				$applicableRecords = REDCapCalculations::filterRecordsNotInArray($applicableRecords,$naSurvey);
			}
			
			$this->tooltipTextArray[$study][$survey][$value] = $tooltip;
			$this->surveyPercentages[$study][$survey][$value] =
				CronData::calcScorePercent($this->tooltipCounts[$study][$survey][$value]["topScore"], count($applicableRecords));
		}
	}
	
	public function calculateInstitutionScores($study, $survey) {
		$containsSurvey = $this->getRecordsContainField($survey);
		$naSurvey = $this->getNARecords($survey);
		$topScoreSurvey = $this->getTopScoreRecords($survey);
		
		foreach($this->getInstitutionData() as $institutionId => $institutionRecords) {
			$applicableRecords = array_intersect_key($institutionRecords,$containsSurvey);
			if($this->isNAField($survey)) {
				$applicableRecords = REDCapCalculations::filterRecordsNotInArray($applicableRecords,$naSurvey);
			}
			
			$instTopScoreRecords = array_intersect($topScoreSurvey,$institutionRecords);
			$this->surveyPercentagesInstitutions[$study][$institutionId][$survey] =
				CronData::calcScorePercent(count($instTopScoreRecords),count($applicableRecords));
		}
	}
	
	public function calculateTotalScores($study,$survey) {
		$responsesTotal = 0;
		$missingTotal = 0;
		$NATotal = 0;
		$topScoreTotal = 0;
		foreach($this->tooltipCounts[$study][$survey] as $value => $counts) {
			## Skip the missing value
			if($value != "missing") {
				$missingTotal += $counts["missing"];
			}
			$responsesTotal += $counts["responses"];
			$NATotal += $counts["NA"];
			$topScoreTotal += $counts["topScore"];
		}
		$tooltip = $responsesTotal." responses, ". $missingTotal ." missing";
		
		if($this->isNAField($survey)) {
			## Only done for question 1 list
			$tooltip .= ", ".$NATotal." not applicable";
		}
		$this->tooltipTextArray[$study][$survey][0] = $tooltip;
		
		$topScorePercent = CronData::calcScorePercent($topScoreTotal,$responsesTotal);
		
		$this->surveyPercentages[$study][$survey][0] = $topScorePercent;
	}
	
	public function createQuestion_1() {
		$studyQuestions = ProjectData::getArrayStudyQuestion_1();
		$surveyQuestions = ProjectData::getRowQuestionsParticipantPerception();
		
		$custom_filters = $this->getProjectSetting('custom-filter',$this->projectId);
		$count = 1;
		foreach($custom_filters as $sstudy) {
			if($count < 11 && $sstudy != "") {
				$studyQuestions[$sstudy] = "Custom site value " . $count;
			}
			else {
				break;
			}
			$count++;
		}
		
		foreach($studyQuestions as $study => $label) {
			if($study == "") continue;
			
			foreach($surveyQuestions as $indexQuestion => $survey) {
				$this->addTooltipCounts($study,$survey);
			}
		}
		
	}
	
	public function createQuestion_2() {
		$studyQuestions = ProjectData::getArrayStudyQuestion_2();
		$surveyQuestions = ProjectData::getRowQuestionsResponseRate();
		$surveyQuestions2 = ProjectData::getRowQuestionsParticipantPerception();
		
	}
	
	public function createQuestion_3() {
		$studyQuestions = ProjectData::getArrayStudyQuestion_3();
		$surveyQuestions = $this->getQuestion3SurveyFields();
		
		$custom_filters = $this->getProjectSetting('custom-filter',$this->projectId);
		$count = 1;
		foreach($custom_filters as $sstudy) {
			if($count < 11 && $sstudy != "") {
				$studyQuestions[$sstudy] = "Custom site value " . $count;
			}
			else {
				break;
			}
			$count++;
		}
		
		foreach($studyQuestions as $study => $label) {
			foreach($surveyQuestions as $indexQuestion => $survey) {
				$this->addTooltipCounts($study,$survey);
			}
		}
	}
	
	public function getQuestion3SurveyFields() {
		$surveyQuestions = ProjectData::getRowQuestions();
		$surveyFields = [];
		
		for ($question = 2; $question < 5; $question++) {
			$option = explode("-", $surveyQuestions[$question]);
			for ($i = $option[0]; $i < $option[1]; $i++) {
				$surveyFields[] = "rpps_s_q".$i;
			}
		}
		
		return $surveyFields;
	}
	public function createTableData() {
		$this->tableData = [
			$this->tooltipTextArray,
			$this->surveyPercentages,
			$this->surveyPercentagesInstitutions
		];
	}
	
	public function getEventId() {
		if(!isset($this->eventId)) {
			$this->eventId = $this->getFirstEventId();
		}
		return $this->eventId;
	}
	
	public function applyFilterToData($filterLogic) {
		// Instantiate logic parse
		$parser = new \LogicParser();
		
		list ($funcName, $argMap) = $parser->parse($filterLogic, [], true, false, false, true);
		
		$matchingData = [];
		
		foreach($this->getProjectData() as $projectRow) {
			if(\LogicTester::applyLogic($funcName,$argMap,[$this->getEventId() => $projectRow])) {
				$matchingData[] = $projectRow;
			}
		}
		
		return $matchingData;
	}
}