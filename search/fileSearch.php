<?php
include_once '../classuser.inc';
include_once '../search/search.php';
include_once '../lib/searchLib.php';
include_once '../search/fileSearchResult.php';
include_once '../groups/groups.php';

class fileSearch {
	var $tempTableName;
	var $cabinetName;
	var $numResults = 0;
	var $user;

	function fileSearch($user) {
		$this->user = $user;
	}

	function findFile($cab, $fileList, $contextList, $subList, $myDate, $myDate2, $whoList, $notes, $contextbool) {
		global $DEFS;
		$ct = count($fileList);

		$user = $this->user;
		$this->cabinetName = $cab;
		$cab_files = $cab."_files";
		$resultListIndex = 0;
		$db_object = $user->getDbObject();
		$temp_table2 = '';

		//////////////////////////////////////////////////////////
		//This section searchs for index fields
		//This function is located in lib/utility.php
		$cabinetIndices = getCabinetInfo($db_object, $cab);

		$searchArray = array ();

		foreach ($cabinetIndices as $index) {
			$bigIndex = strtoupper($index);
			if (isset ($_POST[$bigIndex]) and $_POST[$bigIndex]) {
				$searchArray[$index] = $_POST[$bigIndex];
			}
			if (isset ($_POST[$bigIndex.'-dRng'])) {
				$searchArray[$index.'-dRng'] = $_POST[$bigIndex.'-dRng'];
			}

		}

		$search = new search();
		$temp_table1 = $search->getSearch($cab, $searchArray, $db_object);
		$count = getTableInfo($db_object, $temp_table1, array('COUNT(*)'), array(), 'queryOne');
		$this->tempTableName = $temp_table1;
		$this->numResults = $count;
		//if there are any results
		if ($count > 0) {
			$auditArr = array();
			if(count($contextList)) {
				$temp_table2 = $this->searchOCRContext($db_object,$cab,$contextList);
			}

			if(count($notes)) {
				$temp_table2 = $this->searchNotes($db_object,$cab,$notes,$temp_table2);
			}

			if($subList) {
				$temp_table2 = $this->searchSubfolder($db_object,$cab,$subList,$temp_table2);
			}

			if(count($fileList)) {
				$temp_table2 = $this->searchFilename($db_object,$cab,$fileList,$temp_table2);
			}

			if($myDate) {
				$temp_table2 = $this->searchDate($db_object,$cab,$myDate,$myDate2,$temp_table2);
			}
			if(count($whoList)) {
				$temp_table2 = $this->searchWhoIndexed($db_object,$cab,$whoList,$temp_table2);
			}

			$temp_table3 = createTemporaryFileSearchTable($db_object);
			$this->tempTableName = $temp_table3;
			if ($temp_table2) {
				$insCol		= array("result_id","doc_id");
				$selCol		= array($temp_table2.".result_id",$temp_table2.".doc_id");
				$selTable	= array($temp_table1,$temp_table2);
				$whereArr	= array("$temp_table1.result_id=$temp_table2.doc_id");
				$oArr 		= array('doc_id' => 'DESC');
//				insertFromSelect($db_object,$temp_table3,$insCol,$selTable,$selCol,$whereArr,$oArr,0,5000);
				insertFromSelect($db_object,$temp_table3,$insCol,$selTable,$selCol,$whereArr,$oArr);
				$this->numResults = getTableInfo($db_object, $temp_table3, array('COUNT(*)'), array(), 'queryOne');
			} else {
				$this->numResults = 0;
			}
			$user->audit('file search', $search->auditStr.",".implode(",",$auditArr));
		}

		if ($count > 0 && count($contextList) && $contextbool) {
			$usertmp = base64_encode(serialize($user));
			$thistmp = base64_encode(serialize($this));
			$temp_table3 = createFileSearchTempTable($db_object, $temp_table3);
			$this->tempTableName = $temp_table3;
			$this->numResults = 0;
			$gbl = new Usrsettings($user->username, $user->db_name);
			$gbl->set('context', 'update', $user->db_name); //tell the polling page to keep polling (loading results)

			$counthits = $gbl->get('context_hits');
			if ($counthits == "")
				$counthits = false;

			$cmd = escapeshellarg($DEFS['PHP_EXE']) . ' -q ' .
				escapeshellarg($DEFS['DOC_DIR'].'/bots/contextBot.php') . ' ' .
				escapeshellarg($cab) . ' ' . escapeshellarg($contextList) .
				' '. escapeshellarg ($temp_table2) . ' ' .
				escapeshellarg($usertmp) . ' ' . escapeshellarg($thistmp) .' ' .
				escapeshellarg($temp_table3) . ' ' . 
				escapeshellarg($counthits) . ' 2>&1';
			if (substr(PHP_OS, 0, 3) == 'WIN') {
				$cmd = $DEFS['BGRUN_EXE'] . ' ' . $cmd . ' > NUL';
			} else {
				$cmd .= ' > /dev/null &';
			}
			shell_exec($cmd);
		}
	}

	function searchSubfolder($db_dept,$cab,$subList,$temp_table1=NULL) {
		$temp_table2 = createTemporaryFileSearchTable($db_dept);

		$insCol		= array("result_id","doc_id");
		if($temp_table1) {
			$selCol		= array("id",$temp_table1.".doc_id");
			$selTable	= array($temp_table1,$cab."_files");
			$whereArr	= array("result_id=id","filename IS NOT NULL","display=1");
		} else {
			$selCol		= array("id","doc_id");
			$selTable	= array($cab."_files");
			$whereArr	= array("deleted=0","filename IS NOT NULL","display=1");
		}

		//create query to match all specified fields
//			$specificTab = false;
//			$specificTab = true;
		$auditStr = "Subfolder: ".str_replace("%","",implode(" OR ", $subList));
		$queryTab = array();
		foreach($subList AS $tab) {
			if($tab) {
				if (strcmp($tab, "'main'") == 0 || strcmp($tab, "'%main%'") == 0) {//subfolder field
					$queryTab[] = "subfolder IS NULL";
				} else {
					$queryTab[] = "subfolder " . LIKE . " $tab";
				}
			}
		}
		$whereArr[] = "(".implode(" OR ",$queryTab).")";

/*
		$groups = new groups($db_dept);
		$subfolderAccess = '';
		if($_SESSION['groupAccess']) {
			$queryArr = array();
			foreach ($_SESSION['groupAccess'] as $eachAccess) {
				if ($cab == $eachAccess['cabinet']) {
					if ($eachAccess['doc_id']) {
						$queryPart = "NOT ({$cab}_files.doc_id = ";
						$queryPart .= $eachAccess['doc_id']." AND ";
						$queryPart .= "{$cab}_files.subfolder = ";
						$queryPart .= "'".$eachAccess['subfolder']."'";
						$queryArr[] = $queryPart;
					} else {
						$queryPart = "{$cab}_files.subfolder <> ";
						$queryPart .= "'".$eachAccess['subfolder']."'";
						$queryArr[] = $queryPart;
					}
				}
			}
			if($queryArr) {
				$subfolderAccess = '('.implode(' AND ', $queryArr).')';
			}
		}
		
		if($subfolderAccess) {
			$query .= " AND (";
			if(!$specificTab) {
				$query .= "{$cab}_files.subfolder IS NULL OR ";
			}
			$query .= $subfolderAccess.')';
		}
*/
		insertFromSelect($db_dept,$temp_table2,$insCol,$selTable,$selCol,$whereArr);
		$this->user->audit('file search', $auditStr);
		return $temp_table2;
	}

	function searchFilename($db_dept,$cab,$fileList,$temp_table1=NULL) {
		$temp_table2 = createTemporaryFileSearchTable($db_dept);

		$insCol		= array("result_id","doc_id");
		if($temp_table1) {
			$selCol		= array("id",$temp_table1.".doc_id");
			$selTable	= array($temp_table1,$cab."_files");
			$whereArr	= array("result_id=id","display=1");
		} else {
			$selCol		= array("id","doc_id");
			$selTable	= array($cab."_files");
			$whereArr	= array("deleted=0","display=1");
		}

		//add filename search to query
		$auditStr = "Filename: ".str_replace("%","",implode(" OR ", $fileList));
		$queryFilenameArr = array();
		foreach($fileList AS $f) {
			$queryFilenameArr[] = "parent_filename " . LIKE . " $f";
		}
		$whereArr[] = "(".implode(" OR ",$queryFilenameArr).")";

		insertFromSelect($db_dept,$temp_table2,$insCol,$selTable,$selCol,$whereArr);
		$this->user->audit('file search', $auditStr);
		return $temp_table2;
	}

	function searchDate($db_dept,$cab,$myDate,$myDate2,$temp_table1=NULL) {
		$temp_table2 = createTemporaryFileSearchTable($db_dept);

		$insCol		= array("result_id","doc_id");
		if($temp_table1) {
			$selCol		= array('id',$temp_table1.'.doc_id');
			$selTable	= array($temp_table1,$cab."_files");
			$whereArr	= array("result_id=id");
		} else {
			$selCol		= array('id','doc_id');
			$selTable	= array($cab."_files");
			$whereArr	= array("deleted=0","filename is not NULL","display=1");
		}

		$auditStr = "Date: ".$myDate;
		$myDateArr = explode(" ",trim($myDate));
		if($myDate2 == -1) {
			$queryDateArr = array();
			foreach($myDateArr AS $d) {
				if (isISODate ($d)) {
					$queryDateArr[] = "(date_created >= " . $db_dept->quote ("$d 00:00:00") . " AND date_created <= " . $db_dept->quote ("$d 23:59:59") . ")";
				}
			}
			if ($queryDateArr) {
				$whereArr[] = "(".implode(" OR ",$queryDateArr).")";
			}
		} else {
			if (isISODate ($myDateArr[0])) {
				$whereArr[] = "date_created >= " . $db_dept->quote ("{$myDateArr[0]} 00:00:00");
			}
		}
		
		if ($myDate2 and $myDate2 != -1) {
			$myDateArr2 = explode(" ",trim($myDate2));
			if (isISODate ($myDateArr2[0])) {
				$whereArr[] = "date_created <= " . $db_dept->quote ("{$myDateArr2[0]} 23:59:59");
			}
		}

		if (count($whereArr) > 1) {
			insertFromSelect($db_dept,$temp_table2,$insCol,$selTable,$selCol,$whereArr);
			$this->user->audit('file search', $auditStr);
		}
		return $temp_table2;
	}

	function searchWhoIndexed($db_dept,$cab,$whoList,$temp_table1=NULL) {
		$temp_table2 = createTemporaryFileSearchTable($db_dept);

		$insCol		= array("result_id","doc_id");
		if($temp_table1) {
			$selCol		= array("id",$temp_table1.".doc_id");
			$selTable	= array($temp_table1,$cab."_files");
			$whereArr	= array("result_id=id");
		} else {
			$selCol		= array("id","doc_id");
			$selTable	= array($cab."_files");
			$whereArr	= array("deleted=0");
		}
		
		$auditStr = "Who Indexed: ".str_replace("%","",implode(" OR ", $whoList));
		$queryWhoIndexedArr = array();
		foreach($whoList AS $w) {
			$queryWhoIndexedArr[] = "who_indexed LIKE $w";
		}
		$whereArr[] = "(".implode(" OR ",$queryWhoIndexedArr).")";

		insertFromSelect($db_dept,$temp_table2,$insCol,$selTable,$selCol,$whereArr);
		$this->user->audit('file search', $auditStr);
		return $temp_table2;
	}

	function searchNotes($db_dept,$cab,$notes,$temp_table1=NULL) {
		$temp_table2 = createTemporaryFileSearchTable($db_dept);

		$insCol		= array("result_id","doc_id");
		if($temp_table1) {
			$selCol		= array("id",$temp_table1.".doc_id");
			$selTable	= array($temp_table1,$cab."_files");
			$whereArr	= array("result_id=id");
		} else {
			$selCol		= array("id","doc_id");
			$selTable	= array($cab."_files");
			$whereArr	= array("deleted=0");
		}

		$auditStr = "Notes: ".str_replace("%","",implode(" OR ", $notes));
		$noteArr = array();
		if(getdbType() == 'mysql' || getdbType() == 'mysqli') {
			foreach($notes AS $n) {
				$str = str_replace("%","",$n);
				$noteArr[] .= substr($str,1,strlen($str)-2);
			}
			$whereArr[] = "MATCH(notes) AGAINST(\"".implode(" ",$noteArr)."\")";
		} else {
			foreach($notes AS $n) {
				$noteArr[] = "notes " . LIKE . " $n";
			}
			$whereArr[] = "(".implode(" OR ",$noteArr).")";
		}

		insertFromSelect($db_dept,$temp_table2,$insCol,$selTable,$selCol,$whereArr);
		$this->user->audit('file search', $auditStr);
		return $temp_table2;
	}

	function searchOCRContext($db_dept,$cab,$contextList) {
		$temp_table2 = createTemporaryFileSearchTable($db_dept);
		$insCol		= array("result_id","doc_id");
		$selCol		= array("id","doc_id");
		$selTable	= array($cab."_files");
		$whereArr	= array("deleted=0");
		$auditStr = "OCR Context: ".str_replace("%","",implode(" OR ", $contextList));
		$context = array();
/*		
		if(getdbType() == 'mysql' || getdbType() == 'mysqli') {
			foreach($contextList AS $c) {
				$str = str_replace("%","",$c);
				$context[] .= substr($str,1,strlen($str)-2);
			}
			$whereArr[] = "MATCH(ocr_context) AGAINST(\"".implode(" ",$context)."\")";
		} else {
*/		
			foreach($contextList AS $c) {
				if (strpos ( $c ,'%' )==FALSE){
					$c=str_replace ("'","",$c);
					$c="'%".$c."%'";
				}
				$context[] = "ocr_context " . LIKE . " $c";
			}
			$whereArr[] = "(".implode(" OR ",$context).")";

//		}
		insertFromSelect($db_dept,$temp_table2,$insCol,$selTable,$selCol,$whereArr);
		$this->user->audit('file search',$auditStr);
		return $temp_table2;
	}

	// Arguments:  An page number and a number of results per page
	// Returns: A corresponding list of fileSearchResults objects
	function getResults($pageNumber, $pageSize, $numResults, $sorttype, $sortdir) {
		global $DEFS;
		$user = $this->user;
		$db_object = $user->getDbObject();
		$cab_files = $this->cabinetName."_files";
		$this->numResults = $numResults;

		$temp_table = $this->tempTableName;

		$pagesInTable = ceil($numResults / $pageSize);

		$limit = $pageSize;
		if ($pageNumber < $pagesInTable)
			$start = ($pageNumber -1) * $pageSize;
		else {
			$start = ($pagesInTable -1) * $pageSize;

			$pageNumber = $pagesInTable;
		}
		
		if($start < 0) {
			$start = 0;
		}
		
		if ($sorttype == 'name')
			$sortBy = 'parent_filename';
		if ($sorttype == 'size')
			$sortBy = 'size';
		if ($sorttype == 'index')
			$sortBy = 'index';
		if ($sorttype == 'date')
			$sortBy = 'date_created';
		if ($sorttype == 'who')
			$sortBy = 'who_indexed';
		if ($sorttype == 'hits')
			$sortBy = 'hits';

		$i = 0;
		$resultList = array ();
		if ($limit > 0) {
			$indexHeaders = getCabinetInfo($db_object, $this->cabinetName);
			$resultListIndex = 0;
			$tableName = $this->cabinetName;


/*			$tableArr = array($tableName,$temp_table);
			$selArr = array($tableName.'.doc_id','location');
			$whereArr = array($temp_table.".doc_id = ".$tableName.".doc_id");
			$folderList = getTableInfo($db_object,$tableArr,$selArr,$whereArr,'getAssoc');
*//*
			$tableArr = array($cab_files,$temp_table);
			$whereArr = array($temp_table.".result_id = ".$cab_files.".id");
			$orderArr = array($sortBy => $sortdir);
			$res = getTableInfo($db_object,$tableArr,array(),$whereArr,'query', $orderArr, $start, $limit);
*/
/*
			$selArr = array ($temp_table.'.doc_id AS doc_id',
				'parent_filename', 'subfolder', 'file_size',
				'date_created', 'who_indexed', 'ordering',
				$cab_files.'.id AS id');
*/
			$ttableCols = getTableColumnInfo ($db_object, $temp_table);
			$sArr = array (
				$tableName.'.doc_id AS doc_id',
				$tableName.'.location AS location',
				$cab_files.'.parent_filename AS parent_filename',
				$cab_files.'.subfolder AS subfolder',
				$cab_files.'.file_size AS file_size',
				$cab_files.'.date_created AS date_created',
				$cab_files.'.who_indexed AS who_indexed',
				$cab_files.'.ordering AS ordering',
				$cab_files.'.id AS id',
			);
			if (in_array ('hits', $ttableCols)) {
				$sArr[] = 'hits';
			}
			foreach ($indexHeaders as $myIndex) {
				$sArr[] = $tableName.".".$myIndex;
			}
			$res = getTableInfo($db_object, array($tableName, $cab_files, $temp_table), $sArr,
				array(
					$tableName.'.doc_id = '.$cab_files.'.doc_id',
					$cab_files.'.id = '.$temp_table.'.result_id'
				),
				'query', array($sortBy => $sortdir), $start, $limit);
			while ($fileInfo = $res->fetchRow()) {
//				$location = trim($folderList[$fileInfo['doc_id']]);
				$location = trim($fileInfo['location']);
				$name = trim($fileInfo['parent_filename']);
				$tab = trim($fileInfo['subfolder']);
				$path = str_replace(" ", "/", $location)."/".$tab;
				$pathFromHere = $DEFS['DATA_DIR']."/"."$path"."/"."$name";
				$size = $fileInfo['file_size'];
				$sizes = Array ('B', 'KB', 'MB', 'GB');
				$ext = $sizes[0];
				for ($i = 1;(($i < count($sizes)) && ($size >= 1024)); $i ++) {
					$size = $size / 1024;
					$ext = $sizes[$i];
				}
				$size = round($size, 1).$ext;
				if (!isset ($fileInfo['hits'])) {
					$fileInfo['hits'] = '';
				}
				// prepare result for addition to list
				$result = new fileSearchResult();
				$result->setCreationDate($fileInfo['date_created']);
				$result->setWhoCreated($fileInfo['who_indexed']);
				$result->setFileSize($size);
				$result->setPath($path);
				$result->setFileName($name);
				$result->setDocID($fileInfo['doc_id']);
				$result->setTab($tab);
				$result->setOrdering($fileInfo['ordering']);
				$result->setFileID($fileInfo['id']);
				$result->setHits($fileInfo['hits']);

				// add inDEX Headers to result
				$result->setIndexHeaders($indexHeaders);

				// add indices to result
				for ($j = 0; $j < sizeof($indexHeaders); $j ++) {
					$h = $indexHeaders[$j];
					$tempIndices[$j] = $fileInfo[$h];
				}

				$result->setIndices($tempIndices);

				// add result to list    
				$resultList[$resultListIndex] = $result;
				$resultListIndex ++;
			}
		}
		return $resultList;
	}

	//returns the number of results
	function getNumResults() {
		return $this->numResults;
	}
	function getTempTable() {
		return $this->tempTableName;
	}
}
?>
