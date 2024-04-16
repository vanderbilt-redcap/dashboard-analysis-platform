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
	
	public function calculateCacheCronData($fileName) {
		$this->institutionList = ProjectData::getAllInstitutions($this->getProjectData());
		
		$this->createQuestion_1();
		
		$this->createTableData();
		
		return $this->tableData;
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
		return $topScoreRecords;
	}
	
	public function getNARecords($fieldName) {
		if(!in_array($fieldName,ProjectData::getArrayStudyQuestion_1())) {
			return false;
		}
		$outcome_labels = $this->getFieldChoices($fieldName);
		$topScoreMax = count($outcome_labels);
		
		if($topScoreMax != 5) {
			return [];
		}
		
		return $this->getRecordsByFieldValue($fieldName,5);
	}
	
	public function addTooltipCounts($study,$survey) {
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
		
		$containsSurvey = $this->getRecordsContainField($survey);
		$missingSurvey = $this->getRecordsMissingField($survey);
		$naSurvey = $this->getNARecords($survey);
		$topScoreSurvey = $this->getTopScoreRecords($survey);
		
		$studyOptions = $this->getFieldChoices($study);
		
		## Add label for missing the study field
		$study_options["missing"] = "Missing";
		
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
			
			
			$topScorePercent = 0;
			if(count($applicableRecords) > 0) {
				$topScorePercent = $this->tooltipCounts[$study][$survey][$value]["topScore"] / count($applicableRecords);
			}
			
			$this->tooltipTextArray[$study][$survey][$value] = $tooltip;
			$this->surveyPercentages[$study][$survey][$value] = $topScorePercent;
		}
		
		foreach($this->institutionList as $institutionId => $institutionRecords) {
			$applicableRecords = array_intersect_key($institutionRecords,$containsSurvey);
			if($naSurvey !== false) {
				$applicableRecords = REDCapCalculations::filterRecordsNotInArray($applicableRecords,$naSurvey);
			}
			
			$instTopScoreRecords = array_intersect($topScoreSurvey,$institutionRecords);
			$topScorePercent = 0;
			if(count($applicableRecords) > 0) {
				$topScorePercent = count($instTopScoreRecords) / count($applicableRecords);
			}
			$this->surveyPercentagesInstitutions[$study][$institutionId][$survey] = $topScorePercent;
		}
		
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
		
		## Only done for question 1 list
		$tooltip .= ", ".$NATotal." not applicable";
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
	
	public function createTableData() {
		$this->tableData = [
			$this->tooltipTextArray,
			$this->surveyPercentages,
			$this->surveyPercentagesInstitutions
		];
	}
}