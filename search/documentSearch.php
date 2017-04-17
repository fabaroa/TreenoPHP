<?php
include_once '../lib/utility.php';

class documentSearch {
	var $docType;
	var $docName;
	var $docTypeID;
	var $fields;
	var $fieldDispNames;
	var $tempTable;
	var $numResults;
	var $auditStr;
	var $db;
	var $filterTable;
	var $username;

	function documentSearch($db_dept,$docType,$filterTable = NULL, $username = NULL) {
		$this->docType		= $docType;
		$this->db			= $db_dept;
		
		$sArr = array('id','document_type_name');
		$wArr = array('document_table_name' => $this->docType);
		$docTypeInfo = getTableInfo($this->db,'document_type_defs',$sArr,$wArr,'queryRow');
		$this->docTypeID 	= $docTypeInfo['id'];
		$this->docName 		= $docTypeInfo['document_type_name'];

		$sArr = array('real_field_name','id','arb_field_name');
		$oArr = array('ordering' => 'ASC');
		$this->fields 		= getTableInfo($this->db,'document_field_defs_list',$sArr,$wArr,'getAssoc',$oArr);

		$this->fieldDispNames = $this->setFieldDispNames();

		$this->tempTable 	= "";
		$this->numResults	= "";
		$this->auditStr		= "";
		$this->filterTable	= $filterTable;
		$this->username		= $username;
	}

	function setFieldDispNames() {
		$fieldArr = array();
		foreach($this->fields AS $k => $info) {
			$fieldArr[$info['id']] = $info['arb_field_name'];
		}
		
		return $fieldArr;
	}
	
	function getResults($limit,$count,$sDir=NULL) {
		$tArr = array($this->tempTable,$this->docType,'document_field_value_list');
		$sArr = array(	'document_id',
						'document_field_defs_list_id AS field_id',
						'document_field_value AS field_value',
						//'date_created',
						//'date_modified',
						//'created_by',
						'cab_name',
						'doc_id',
						'file_id');
		$wArr = array(	'result_id = document_id',
						'document_defs_list_id = '.(int)$this->docTypeID,
					  	'document_id = '.$this->docType.'.id');

		$oStr = "";
		if($sDir) {
			$oStr = "ORDER BY doc_val $sDir";
		}
		if(getdbType() == 'mysql') {
			$q = implode(",",$sArr)." FROM ".implode(",",$tArr);
			$q .= " WHERE ".implode(" AND ",$wArr);
			$query = "SELECT ".$q." $oStr LIMIT $limit, $count";
		} elseif(getdbType() == 'pgsql' or getdbType() == 'mysqli') {
			$q = implode(",",$sArr)." FROM ".implode(",",$tArr);
			$q .= " WHERE ".implode(" AND ",$wArr);
			$query = "SELECT ".$q." $oStr LIMIT $count OFFSET $limit";
		} elseif(getdbType() == 'mssql') {
			$q = " FROM ".implode(",",$tArr);
			$q .= " WHERE ".implode(" AND ",$wArr);
			$query = 'SELECT * FROM (SELECT TOP ' . $count . 
				' * FROM (SELECT TOP ' . ($count + $limit) . 
				' '.implode(",",$sArr).$q.') AS FOO) AS BAR';
		} elseif(getdbType() == 'db2') {
			if(!$oStr) {
				$oStr = "document_id ASC";
			}
			$q = " FROM ".implode(",",$tArr);
			$q .= " WHERE ".implode(" AND ",$wArr);
			$query = 'SELECT * FROM (SELECT ' .
				implode(",",$sArr).', ROW_NUMBER() ' .
				'OVER(ORDER BY '.$oStr.' ASC) AS rownumber'.$q.')'.
				' AS foo WHERE rownumber ' .
				'> '.$limit.' AND rownumber <= '.($count + $limit);
		}

		$fieldValueArr = $this->db->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, true, true);
		dbErr ($fieldValueArr);
		return $fieldValueArr;
	}
	
	function search($cabArr, $searchArray = array(), $cab = NULL) {
		if(count($cabArr) > 1) {
			$this->tempTable	= createTemporaryTable($this->db);
				$sArr = array('cabinet','index1','search');
				$wArr = array('username' => $this->username);
				$oArr = array('index1' => 'ASC');
				$gArr = array('cabinet','index1','search');
				$filterList = getTableInfo($this->db,'cabinet_filters',$sArr,$wArr,'getAssoc',$oArr,0,0,$gArr,true);
				if(count($filterList)) {
					$fInfo = array();
					foreach($filterList AS $cabinet => $sList) {
						foreach($sList AS $s) {
							$fInfo[$cabinet][$s['index1']][] = $cabinet.'.'.$s['index1']."='".$s['search']."'";
						}
					}
				}
			foreach($cabArr AS $c) {
				$insCol 			= array('result_id');
				$sArr 				= array("$this->docType.id");

				$tableArr 	= array($this->docType, $c);
				$wArr		= array("$c.deleted = 0",
								"$this->docType.deleted = 0",
								"$c.doc_id = ".$this->docType.".doc_id",
								"cab_name = '$c'" );
				if(isSet($fInfo[$c])) {
					foreach($fInfo[$c] AS $k => $v) {
						$wArr[] = "(".implode(" OR ",$v).")";
					}
				}

				insertFromSelect($this->db,$this->tempTable,$insCol,$tableArr,$sArr,$wArr);
			}
		}

		if(count($searchArray)) {
			foreach($searchArray AS $key => $value) {
				if($value) {
					$tempTable = createTemporaryTable($this->db);
					
					$insCol 	= array('result_id');
					$sArr 		= array('document_id');
					$wArr 		= array("deleted = 0",
										"document_defs_list_id = ".(int)$this->docTypeID,
										"document_id = ".$this->docType.".id");

					$wArr[] = "document_field_defs_list_id = ".(int)$this->fields[$key]['id'];
					$docVal = $this->parseValue($value);
					if($docVal) {
						$wArr[] = $docVal; 
					}
					
					if($this->tempTable) {
						$tableArr 	= array($this->tempTable,'document_field_value_list',$this->docType);
						$wArr[] = "document_id = ".$this->tempTable.".result_id"; 
					} else {
						$tableArr 	= array($this->filterTable,'document_field_value_list',$this->docType);
						$wArr[] = "document_id = ".$this->filterTable.".result_id"; 
					}

			//		print_r($tableArr);
					insertFromSelect($this->db,$tempTable,$insCol,$tableArr,$sArr,$wArr);
					$this->tempTable = $tempTable;
			//		echo $tempTable;
				}
			}
		}

		if(!$this->tempTable) {
			$this->tempTable = $this->filterTable;
		}

		if($cab) {
			$tempTable = createTemporaryTable($this->db);
			
			$insCol 	= array('result_id');
			$sArr 		= array('id');
			$wArr 		= array("cab_name = '$cab'");
			
			if($this->tempTable) {
			//	echo $this->tempTable."</br>";
				$tableArr 	= array($this->tempTable,$this->docType);
				$wArr[] = "id = ".$this->tempTable.".result_id"; 
				$wArr[] = "deleted = 0"; 
			} else {
				$tableArr 	= array($this->docType);
			}

			insertFromSelect($this->db,$tempTable,$insCol,$tableArr,$sArr,$wArr);
			$this->tempTable = $tempTable;
			//print_r($sArr);
			//print_r($wArr);
			//echo $tempTable."</br>";
		}
		$sArr = array('COUNT(result_id)');
		$this->numResults = getTableInfo($this->db,$this->tempTable,$sArr,array(),'queryOne');
		//echo $this->numResults."</br>";
		//return $this->tempTable;
	}

	function parseValue($searchVal) {
		$searchArr = $this->splitSearch($searchVal);	
		$wArr = array();
		foreach($searchArr AS $val) {
			if(strlen($val) > 1 && strpos($val, '"') === 0) {
				$newVal = substr($val, 1, strlen($val) - 2);
				$wArr[] = "document_field_value = '".addslashes($newVal)."'";
			} else {
				$wArr[] = "document_field_value " . LIKE . " '%".addslashes($val)."%'";
			}
		}

		if(count($wArr)) {
			return '('.implode(" AND ",$wArr).')'; 
		}
		return false;
	}

	function &splitSearch ($str) {
		$exact = false;
		$searchStr = '';
		$searchArr = array ();

		for ($i = 0; $i < strlen ($str); $i++) {
			if ($exact) {
				if ($str{$i} == '"') {
					$searchStr = trim ($searchStr);
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
}
