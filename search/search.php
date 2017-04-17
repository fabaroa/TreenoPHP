<?php
include_once '../classuser.inc';
include_once '../lib/searchLib.php';
include_once '../lib/random.php';
class search {
	var $fields;
	var $tempTable;
	var $auditStr;

	/******************************************************************/
	function search() {
		$this->fields = array (); //initializes array
		$this->tempTable = "";
	}
	/******************************************************************/
	function getSearch($cabinetName, $searchArray, $db_object, $sortType = NULL, $sortDir = NULL) {
		$this->setFields($searchArray);
		$auditStr = array();
		if(sizeof($this->fields)) {
			foreach($this->fields AS $key => $search) {
				$audit[] = $key."[".trim(implode(" ",$search))."]";	
			} 
		} else {
			$audit[] = "All";	
		}
		
		$this->doSearch($db_object, $cabinetName, $sortType, $sortDir);
		$this->auditStr = "Cabinet:$cabinetName Folder search: ".implode(",",$audit);
		return $this->tempTable;
	}

	function doSearch($db_object, $cabinetName, $sortType, $sortDir) {
		$tempTables = array ();
		if (sizeof($this->fields))
			$limit = sizeof($this->fields);
		else
			$limit = 1;

		$date = date('Y-m-d G:i:s', time() + 3600);
		//need this to set pointer to the beginning of the array
		reset($this->fields);
		for ($i = 0; $i < $limit; $i ++) {
			$tempTables[$i] = createTemporaryTable($db_object);
			$tableArr = array($cabinetName);
			if ($i > 0) {
				$lastTempTable = $tempTables[$i - 1];
				$tableArr[] = $lastTempTable;
			} else {
				$lastTempTable = '';
			}
			$key = key($this->fields);
			$isDateRange = false;
			if(strpos($key, '-dRng') !== false) {
				$dateKey = substr($key, 0, strpos($key, '-dRng'));
				if(isset($this->fields[$dateKey]) and $this->fields[$dateKey]) {
					$fromDate = $this->fields[$dateKey][0];
					unset($this->fields[$dateKey]);
					$limit--;
				} else {
					$fromDate = '';
				}
				$toDate = current(current($this->fields));
				$isDateRange = true;
			} elseif(isset($this->fields[$key.'-dRng'])) {
				$dateKey = $key;
				$fromDate = current(current($this->fields));
				$toDate = $this->fields[$key.'-dRng'][0];
				unset($this->fields[$key.'-dRng']);
				$limit--;
				$isDateRange = true;
			}
			if($isDateRange) {
				$whereArr = $this->makeDateRangeQuery($db_object, $dateKey, $fromDate, $toDate);
			} elseif (sizeof($this->fields)) {
				$whereArr = $this->makeQuery($db_object, $cabinetName, key($this->fields), current($this->fields));
			} else
				$whereArr = $this->makeQuery($db_object, $cabinetName);
			if($lastTempTable) {
				$whereArr[] = "$cabinetName.doc_id=$lastTempTable.result_id";
			}
			if($sortType and $sortDir) {
				$orderArr = array($sortType => $sortDir);
			} else {
				$orderArr = array('doc_id' => 'DESC');
			}
			insertFromSelect($db_object, $tempTables[$i], array('result_id'),
					$tableArr, array('doc_id'), $whereArr, $orderArr);

			next ($this->fields);
		}
		$this->tempTable = $tempTables[$limit - 1];
	}

	function makeQuery($db, $cabinetName, $index = NULL, $searchTerms = array ()) {
		$whereArr = array('deleted=0');
		$orBunch = array ();
		foreach ($searchTerms as $term) {
			if( $index == "doc_id" ) {
				$orBunch[] = "$index=$term";
			} elseif($term == "is_null") {
				$orBunch[] = "$index is null";
			} elseif($term == "is_not_null") {
				$orBunch[] = "$index is not null";
			} elseif(strpos($term, '"') === 0) {
				$newTerm = substr($term, 1, strlen($term) - 2);
				$orBunch[] = "$index = '".$db->escape($newTerm)."'";
			} else {
				$orBunch[] = "$index " . LIKE . " '%".$db->escape($term)."%'";
			}
		}
		if (sizeof($orBunch)) {
			$whereArr[] = '('.implode(" OR ", $orBunch).')';
		}
		return $whereArr;
	}
	
	function makeDateRangeQuery($db, $index, $fromDate, $toDate) {
		$andBunch = array ('deleted=0');
		if ($fromDate) {
			$andBunch[] = "$index >= '".$db->escape($fromDate)."'";
		}
		if ($toDate) {
			$andBunch[] = "$index <= '".$db->escape($toDate)."'";
		}
		return $andBunch;
	}

	/******************************************************************/
	//$exactSearchArr takes index fields that needs an exact search
	function getAudit($db_user, $exactSearchArr=array()) {
		$select = "";
		$fieldValues = $this->setAuditFields($db_user);
		return $this->createAuditSelect($db_user, $exactSearchArr);
	}
	/******************************************************************/
	function createAuditSelect($db_object, $exactSearchArr=array()) {
		$andBunch = array ();
		foreach ($this->fields as $fieldName => $fieldValues) {
			foreach ($fieldValues as $fieldValue) {
				$fieldValue = $db_object->escape ($fieldValue, true);
				if( in_array($fieldName, $exactSearchArr) ) {
					$andBunch[] = "$fieldName = '$fieldValue'";
				} elseif ($fieldName == 'datetime' && isISODate ($fieldValue)) {
					$andBunch[] = 'datetime >= ' . "'$fieldValue 00:00:00' AND " .
						"datetime <= '$fieldValue 23:59:59'";
				} else {
					$andBunch[] = "$fieldName " . LIKE . " '%$fieldValue%'";
				}
			}
		}
		return $andBunch;
	}

	function setFields($searchArray) {
		foreach ($searchArray as $key => $value) {
			$this->fields[$key] =& $this->splitSearch ($value);
		}
	}
	
	function &splitSearch ($str) {
		$str = stripslashes($str);
		$exact = false;
		$searchStr = '';
		$searchArr = array ();
		for ($i = 0; $i < strlen ($str); $i++) {
			if ($exact) {
				if ($str{$i} == '"') {
					if ($searchStr) {
						$searchArr[] = '"'.$searchStr.'"';
						$searchStr = '';
					}
					$exact = false;
				} else {
					$searchStr .= $str{$i};
				}
			} else {
				if ($str{$i} == '"') {
					$searchStr = trim ($searchStr);
					if ($searchStr) {
						$searchArr[] = $searchStr;
						$searchStr = '';
					}
					$exact = true;
				} elseif ($str{$i} == ' ') {
					$searchStr = trim ($searchStr);
					if ($searchStr) {
						$searchArr[] = $searchStr;
						$searchStr = '';
					}
				} else {
					$searchStr .= $str{$i};
				}
			}
		}
		$searchStr = trim ($searchStr);
		if ($searchStr) {
			if ($exact) {
				$searchArr[] = '"'.$searchStr.'"';
			} else {
				$searchArr[] = $searchStr;
			}
		}
		return $searchArr;
	}

	/******************************************************************/
	function setAuditFields($db_user) {
		$tableInfo = getTableColumnInfo ($db_user, 'audit');
		$fields = array();
		foreach($tableInfo as $column) {
			if($_POST[$column]) {
				$searchValues = explode(' ', $_POST[$column]);
				$fields[$column] = $searchValues;
			} 
		}
		$this->fields = $fields;
	}
}
?>
