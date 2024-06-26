<?php
namespace Vanderbilt\REDCapDataCore;

## All functions here assume that the data is in json-array format with "redcap_record_id" as the record id field
class REDCapCalculations
{
	public static $recordIdField = "record_id";
	
	public static function calculateBracketStats($recordData, $fieldName, $brackets) {
		$counts = [];
		foreach($brackets as $label => $range) {
			$counts[$label] = self::mapFieldByRecordInRange($recordData,$fieldName,$range[1],$range[0]);
		}
		
		return self::summarizeStats($counts);
	}
	
	public static function calculateRadioStats($recordData, $fieldName, $fieldOptions) {
		$counts = [];
		foreach($fieldOptions as $rawValue => $label) {
			$counts[$label] = self::mapFieldByRecord($recordData,[$fieldName],[$rawValue],false);
		}
		
		return self::summarizeStats($counts);
	}
	
	public static function calculateCheckboxStats($recordData, $fieldName, $fieldOptions) {
		$rawValues = [];
		
		foreach($fieldOptions as $rawValue => $thisLabel) {
			$rawValues[$thisLabel] = self::mapFieldByRecord($recordData,[$fieldName."___".$rawValue]);
		}
		
		return self::summarizeStats($rawValues);
	}
	
	public static function summarizeStats($rawValues) {
		$counts = [];
		$totalRecords = [];
		foreach($rawValues as $validRecords) {
			foreach($validRecords as $recordId => $value) {
				$totalRecords[$recordId] = 1;
			}
		}
		
		$nOf = count($totalRecords);
		
		if($nOf == 0) {
			foreach($rawValues as $thisLabel => $records) {
				$counts[$thisLabel] = 0;
			}
		}
		else {
			foreach($rawValues as $thisLabel => $records) {
				$counts[$thisLabel] = array_sum($records);
			}
		}
		
		return [
			"counts" => $counts,
			"nOf" => $nOf
		];
	}
	
	public static function mergeStats($stats1,$stats2) {
		$combinedStats = [];
		$combinedNOf = $stats1["nOf"] + $stats2["nOf"];
		$combinedLabels = array_unique(array_merge(
			array_keys($stats1["percentages"]),
			array_keys($stats2["percentages"])
		));
		
		foreach($combinedLabels as $thisLabel) {
			$count = 0;
			if(array_key_exists($thisLabel,$stats1["percentages"])) {
				$count += $stats1["percentages"][$thisLabel] * $stats1["nOf"];
			}
			if(array_key_exists($thisLabel,$stats1["percentages"])) {
				$count += $stats2["percentages"][$thisLabel] * $stats2["nOf"];
			}
			if($combinedNOf == 0) {
				$combinedStats[$thisLabel] = 0;
			}
			else {
				$combinedStats[$thisLabel] = $count / $combinedNOf;
			}
		}
		return [
			"percentages" => $combinedStats,
			"nOf" => $combinedNOf
		];
	}
	
	## Take a json-array set of record data and re-map a field with redcap_record_id as the key
	/**
	 * @param $recordData array return from \REDCap::getData in json-array format
	 * @param $fieldNames string|array Field names to check/return (only one value is returned per record)
	 * @param $validValues array If filtering on certain values for the given field, values should be in this array
	 * @param $returnValues bool If counting valid records and not needing actual field data, set to false
	 * @return array
	 */
	public static function mapFieldByRecord($recordData, $fieldNames, $validValues = [], $returnValues = true) {
		$returnData = [];
		
		## Convert field name to array if single field passed
		if(!is_array($fieldNames)) {
			$fieldNames = [$fieldNames];
		}
		
		foreach($fieldNames as $fieldName) {
			## Reduce down to rows with actual data
			$thisRecordData = self::filterDataByField($recordData,$fieldName);
			foreach($thisRecordData as $thisRow) {
				## Skip values not in the validValues list if provided
				if(!empty($validValues) && !in_array($thisRow[$fieldName],$validValues)) {
					continue;
				}
				
				if($returnValues) {
					$returnData[$thisRow[self::$recordIdField]] = $thisRow[$fieldName];
				}
				else {
					$returnData[$thisRow[self::$recordIdField]] = 1;
				}
			}
		}
		return $returnData;
	}
	
	public static function mapFieldByRecordInRange($recordData, $fieldName, $upperCutoff, $minimumValue) {
		$returnData = [];
		
		$recordData = self::filterDataByField($recordData,$fieldName);
		foreach($recordData as $thisRow) {
			if($thisRow[$fieldName] >= $minimumValue && $thisRow[$fieldName] < $upperCutoff) {
				$returnData[$thisRow[self::$recordIdField]] = 1;
			}
		}
		return $returnData;
	}
	
	## Take a json-array set of record data and filter down to rows with a particular field set
	public static function filterDataByField($recordData, $fieldName) {
		return array_filter($recordData, function($thisRow) use ($fieldName) {
			if($thisRow[$fieldName] !== NULL && $thisRow[$fieldName] !== "") {
				return true;
			}
			return false;
		});
	}
	
	## Compare an array of data with record ID keys to a json-array format record data and only return matches
	public static function filterDataByArray($recordData, $filterRecords) {
		return array_filter($recordData,function($thisRow) use ($filterRecords) {
			return array_key_exists($thisRow[self::$recordIdField],$filterRecords);
		});
	}
	
	public static function filterRecordsByArray($recordList, $filterRecords) {
		return array_intersect_key($recordList,$filterRecords);
	}
	
	public static function filterRecordsNotInArray($recordList, $filterRecords) {
		$recordList = array_filter($recordList,function($key) use ($filterRecords) {
			if(array_key_exists($key,$filterRecords)) {
				return false;
			}
			return true;
		},ARRAY_FILTER_USE_KEY);
		
		return $recordList;
	}
}
