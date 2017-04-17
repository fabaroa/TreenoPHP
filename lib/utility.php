<?php
// $Id: utility.php 15103 2014-06-02 21:04:15Z fabaroa $
include_once 'Cache/Lite.php';
if (file_exists('../lib/tables.php')) {
	include_once '../lib/tables.php';
} elseif (file_exists ('lib/tables.php')) {
	include_once 'lib/tables.php';
} else {
	include_once '../../lib/tables.php';
}

/** 
  * This function returns a cache lite object with default options set if
  * no options are passed
  */
function getCacheLiteObject( $opts = null ){
	global $DEFS;
	if( $opts == null ){
		$cacheDir = $DEFS['TMP_DIR'].'/cache/';
		if(!file_exists($cacheDir)) {
			mkdir($cacheDir);
		}
		$opts['cacheDir']=$cacheDir;
		$opts['caching']=true;
		$opts['lifetime']=3600;
		$opts['automaticSerialization']=true;
	}
	return new Cache_Lite( $opts );
}

/**
  * This function will check the argument for if it is an error object, and
  * if it is, it will log to a file, then die.
  * 
  * @param  object	$res	DB_Error Object, DB_Result Object, or DB_OK
  * 
  */
function dbErr(&$res,$kill=1) {
	global $DEFS;
	if(PEAR::isError($res)) {
		$bt = $res->backtrace;
		$date = date('Y-m-d G:i:s');
		$errLine = $date .' DB ERROR: message: '.$res->getDebugInfo().', calling file: ' .
				$bt[count($bt) - 1]['file'].', line: '.$bt[count($bt) - 1]['line'];
		error_log($errLine);
		if(count($_SESSION) > 1) {
			echo "
<div style='text-align: left; font-size: 12px;
	background-color: white; color: black; margin: auto; 
	border-style: double; border-width: 6px; border-color: #003b6f;width:380px'>
<p style='text-align: center; font-style: italic'>An unexpected result has been found. Please contact your support administrator.</p>
</div>";
		}
		if( $kill )
		die();
	}
}
/*
function docutronErrorHandler($errno, $errstr, $errfile, $errline) {
	$date = date ("F j, Y, g:ia");
	$error =<<<HTML
<script type="text/javascript">
	var errWin = window.open('','name','height=300,width=450,toolbar=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no');
	errWin.document.write("<div><p>An error has occured in our program.</p>" +
		"<p>Please send the following error details to support@docutronsystems.com:</p>" +
		"<ul>" +
		"<li>Date: $date</li>" +
		"<li>Error No: $errno</li>" +
		"<li>Error Text: $errstr</li>" +
		"<li>File: $errfile</li>" +
		"<li>Line: $errline</li>" +
		"</ul></div>");
</script>
HTML;
	echo $error;
	exit(1);
}
*/

function getCabinetIDList($db_object) {
	$query = "SELECT real_name,DepartmentID FROM departments WHERE deleted=0";
	$result = $db_object->extended->getAssoc($query);
	dbErr($result);
	return $result;
}

/*
 *	This function will return the cabinet indices 
 */
function getCabinetInfo($db_object, $cabinet) {
	$indiceInfo = getTableColumnInfo ($db_object, $cabinet);
	dbErr($indiceInfo);
	for ($i = 3; $i < (sizeof($indiceInfo)-1); $i ++) {
		$indiceNames[] = $indiceInfo[$i];
	}
	return ($indiceNames);
}
function queryAllFilesInFolder($db_object, $cabinet, $docID,$subfolder="") {
	$query = "SELECT subfolder,id,filename,doc_id,ordering,date_created," ;
	$query .= "date_to_delete,who_indexed,access,OCR_context,notes,deleted,";
	$query .= "parent_id,v_major,v_minor,parent_filename,who_locked,date_locked,";
	$query .= "display,file_size,ca_hash FROM {$cabinet}_files WHERE doc_id = $docID ";
	if($subfolder) {
		$query .= "AND subfolder='$subfolder' ";
	}
	$query .= "AND filename IS NOT NULL AND deleted = 0 AND display = 1 ORDER BY ordering";
	$res = $db_object->queryAll($query);
	if (PEAR :: isError($res)) {
		error_log(print_r( $res ).'Query Error in queryAllFilesInFolder() in file lib/utility.php');
	}
	$retRes = array ();
	foreach ($res as $eachFile) {
		if ($eachFile['subfolder'] == '') {
			$tab = 'main';
		} else {
			$tab = $eachFile['subfolder'];
		}
		if (!isset ($retRes[$tab])) {
			$retRes[$tab] = array ();
		}
		$retRes[$tab][] = $eachFile;
	}
	return $retRes;
}

/* 
 * $updateArr is an associative array of all indices and values
 * $whereArr is an associative array(indice=>value) 
 */
function updateTableInfo(&$db_object,$table,$updateArr,$whereArr,$set=0,$where=0, $quoted=0) {
	$indiceArr = getTableColumnInfo ($db_object, $table);
	$query = "UPDATE $table";
	$queryArr = array();
	$setArr = array();
	foreach($updateArr AS $key => $value) {
		if( (in_array($value,$indiceArr) || $set) && !$quoted) {
			$setArr[] = $key.'='.$value;
		} else {
			$setArr[] = $key.'='.$db_object->quote ($value);
		}
		$queryArr[] = $key;
		$queryArr[] = $value;
	}
	$query .= " SET ".implode(',',$setArr);
	$wArr = array();
	if(isset($whereArr[0])) {
		$wArr[] = $whereArr[0];
	} else {
		foreach($whereArr AS $key => $value) {
			if(strcmp($value,"IS NULL") != 0 && !$where) {
				$wArr[] = $key.'='.$db_object->quote($value);
			} else {
				$wArr[] = $key . ' ' . $value;
			}
			$queryArr[] = $key;
			$queryArr[] = $value;
		}
	}
	if($wArr) {
		$query .= " WHERE ".implode(' AND ',$wArr);
	}
	$res = $db_object->query($query);
	dbErr($res);
	return true;
}

function deleteTableInfo(&$db_object,$cabinet,$whereArr,$where=0) { 
	$query = "DELETE FROM $cabinet";
	$wArr = array();
	if(isset($whereArr[0])) {
		$wArr[] = $whereArr[0];
	} else {
		foreach($whereArr AS $key => $value) {
			if($value !== "IS NULL" && !$where) {
				$wArr[] = $key.'='.$db_object->quote ($value);
			} else {
				$wArr[] = $key . ' ' . $value;
			}
			$queryArr[] = $key;
			$queryArr[] = $value;
		}
	}
	if($wArr) {
		$query .= " WHERE ".implode(' AND ',$wArr);
	}
	$res =& $db_object->query($query);
	dbErr($res);
	return true;
}

function insertFromSelect(&$db,$destTable,$destColumns = array(),$selTable,$selArr = array(),$whereArr = array(),$ordering = array(),$limit=0,$count=0) {
	$queryArr = array();
	$query = 'INSERT INTO '.$destTable.' ';
	if($destColumns) {
		$query .= '('.implode(',', $destColumns).') ';
	}
	$query .= getSelectQuery($selTable, $selArr, $whereArr, $ordering, $limit, $count, $db);
	$res =& $db->query($query);
	dbErr($res);
}

function &getTableInfo(&$db,$table,$selArr=array(),$whereArr=array(),$func='query',$ordering=array(),$limit=0,$count=0,$groupBy=array(),$group=false) {
	$query = getSelectQuery($table, $selArr, $whereArr, $ordering, $limit, $count, $db,$groupBy);
	if(strtolower($func) == strtolower('queryCol')) {
		$res =& $db->$func($query, null, 0);
	} elseif(strtolower($func) == strtolower('getAssoc')) {
		$res =& $db->extended->$func($query, null, array (), null, MDB2_FETCHMODE_ASSOC, false, $group);
	} elseif(strtolower($func) == strtolower('queryRow')) {
		$res =& $db->$func($query);
	} else {
		$res =& $db->$func($query);
	}
	dbErr($res);
	return $res;
}

function getSelectQuery($table,$selArr=array(),$whereArr=array(),$ordering=array(),$limit=0,$count=0,$db,$groupBy=array()) {
	$query = '';
	$selectWhat = '';
	if($selArr) {
		$selectWhat .= implode(',', $selArr);
	} else {
		if(is_array($table)) {
			$selFrom = array ();
			foreach($table as $myTable) {
				$selFrom[] = $myTable.'.*';
			}
			$selectWhat .= implode(',', $selFrom);
		} else {
			$selectWhat .= $table.'.*';
		}
	}
	if(is_array($table)) {
		$query .= ' FROM '.implode(',', $table);
	} else {
		$query .= ' FROM '.$table;
	}
	if(isset($whereArr[0])) {
		$query .= ' WHERE '.implode(' AND ', $whereArr);
	} else {
		$wArr = array ();
		foreach($whereArr as $key => $value) {
			if($value === 'IS NULL' or $value === 'IS NOT NULL') {
				$wArr[] = $key . ' ' . $value;
			} else {
			//error_log(print_r($db));
				$wArr[] = $key . '=' . $db->quote($value);
			}
		}
		if($wArr) {
			$query .= ' WHERE '.implode(' AND ', $wArr);
		}
	}
	
	$groupStr = '';
	if($groupBy) {
		$groupStr = ' GROUP BY '.implode(',',$groupBy);
	}
	$query .= $groupStr;
	
	$orderStr = '';
	$orderStr2 = '';
	if($ordering) {
		$oArr = array ();
		$oArr2 = array ();
		$orderStr = 'ORDER BY ';
		$orderStr2 = 'ORDER BY ';
		foreach($ordering as $columnName => $direction) {
			$oArr[] = $columnName.' '.$direction;
			if ($direction == 'ASC') {
				$oArr2[] = $columnName . ' DESC';
			} else {
				$oArr2[] = $columnName . ' ASC';
			}
		}
		$orderStr .= implode(',', $oArr);
		$orderStr2 .= implode(',', $oArr2);
	}
	if($limit and !$count) {
		if(getdbType() == 'mysql' or getdbType() == 'pgsql' or getdbType() == 'mysqli') {
			$query .= ' ' . $orderStr;
			$query = 'SELECT '. $selectWhat . ' ' .$query.' LIMIT '.$limit;
		} elseif(getdbType() == 'db2') {
			$query = 'SELECT * FROM (SELECT ' . $selectWhat . ', ROW_NUMBER() ' .
			'OVER('.$orderStr.') AS rownumber '.$query.')' .
			' AS foo WHERE rownumber ' .
			'> 0 AND rownumber <= '.$limit;
		} elseif(getdbType() == 'mssql') {
			$query .= ' ' . $orderStr;
			$query = 'SELECT TOP ' . $limit . ' ' . $selectWhat 
				. ' ' . $query;
		}
	} elseif($count) {
		if(getdbType() == 'mysql') {
			$query .= ' ' . $orderStr;
			$query = 'SELECT '. $selectWhat . ' ' . $query." LIMIT $limit, $count";
		} elseif(getdbType() == 'pgsql' or getdbType() == 'mysqli') {
			$query .= ' ' . $orderStr;
			$query = 'SELECT '. $selectWhat . ' ' . $query." LIMIT $count OFFSET $limit";
		} elseif(getdbType() == 'db2') {
			$query = 'SELECT * FROM (SELECT ' . $selectWhat . ', ROW_NUMBER() ' .
			'OVER ('.$orderStr.') AS rownumber '.$query.')' .
			' AS foo WHERE rownumber ' .
			'> '.$limit.' AND rownumber <= '.($count + $limit);
		} elseif(getdbType() == 'mssql') {
			$ct = $db->queryOne ('SELECT COUNT(*) ' . $query);
			dbErr($ct);
			if ($ct < ($count + $limit)) {
				$count = $ct - $limit;				
			}
			if ($count < 0) {
				$count = 0;
			}
			$query = 'SELECT * FROM (SELECT TOP ' . $count . 
				' * FROM (SELECT TOP ' . ($count + $limit) . 
				' ' . $selectWhat . ' ' . $query . ' ' . 
				$orderStr . ') AS FOO ' . $orderStr2 . 
				') AS BAR ' . $orderStr;
		}
	} else {
		$query .= ' ' . $orderStr;
		$query = 'SELECT ' . $selectWhat . ' ' .$query;
	}
	return $query;
}

function getAssocSelectedList( $db_object, $cabinet, $doc_id ) {
	$query = 'SELECT id, subfolder, parent_filename ' .
		" FROM ".$cabinet."_files WHERE doc_id = " . $db_object->quote ($doc_id) . " AND filename IS NOT NULL";
	$res = $db_object->queryAll ($query);
	dbErr($res);
	$retArr = array ();
	foreach ($res as $myFile) {
		if ($myFile['subfolder']) {
			$tmpNam = $myFile['subfolder'] . '/' . $myFile['parent_filename'];
		} else {
			$tmpNam = $myFile['parent_filename'];
		}
		$retArr[$myFile['id']] = $tmpNam;
	}
	return $retArr;
}

//------------------------------------------------------------------------------
//----------------Functions That Select From The licenses Table-----------------
//------------------------------------------------------------------------------
/*
 *	This function will return licenses info 
 */
function getLicensesInfo($db_object, $value1 = '', $value2 = '', $arrType = '', $order = '') {
	if ($arrType) {
		$res = getTableInfo ($db_object, 'licenses',
			array ($value1, $value2), array (), 'getAssoc', 
			array ('arb_department' => 'ASC'));
		return $res;
	}
	$whereArr = array ();
	$orderArr = array ();
	$query = "SELECT * FROM licenses ";

	if ($value1)
		$whereArr['real_department'] = $value1;
	if ($value2)
		$whereArr['arb_department'] = $value2;
	if (!$order)
		$orderArr['arb_department'] = 'ASC';
	else
		$orderArr['quota_allowed'] = 'DESC';

	return getTableInfo ($db_object, 'licenses', array (), $whereArr,
		'query', $orderArr);
}
function getCabIndexArr($docID, $cabinetName, $db_object) {
	$tmpArr = getTableInfo ($db_object, $cabinetName, array (),
		array ('doc_id' => (int) $docID), 'queryRow');
	unset ($tmpArr['location']);
	unset ($tmpArr['doc_id']);
	unset ($tmpArr['deleted']);
	unset ($tmpArr['timestamp']);
	return $tmpArr;
}

//------------------------------------------------------------------------------
//----------------------Functions that deal with Workflow-----------------------
//------------------------------------------------------------------------------
function getUserListID($db_object) {
	$query = "SELECT username,uid FROM access";
	$result = $db_object->extended->getAssoc($query);
	dbErr($result);
	return ($result);
}

/*
 * This function will retrieve workflow IDs 
 */
function getWorkflowIDs($db_object, $cab, $doc_id, $file_id = -1) {
	$tableArr = array ('wf_documents', 'wf_defs');
	$selArr = array (
		'wf_documents.id AS id',
		'wf_defs.state',
		'wf_documents.state_wf_def_id',
		'wf_defs.node_id',
		'wf_documents.owner'
	);
	$whereArr = array (
		'cab=' . $db_object->quote ($cab),
		'doc_id=' . $db_object->quote ((int) $doc_id),
		'file_id=' . $db_object->quote ($file_id),
		'wf_documents.state_wf_def_id=wf_defs.id'
	);
	$documentInfo = getTableInfo ($db_object, $tableArr, $selArr, $whereArr,
		'queryRow', array ('id' => 'DESC'));

	return ($documentInfo);
}
/*
 * This function will retrieve all users for a specific group_list
 */
function getGroupUsers($db_object, $wf_document_id) {
	$tableArr = array (
		'wf_documents',
		'wf_defs',
		'wf_nodes',
		'group_list',
		'groups',
		'users_in_group',
		'access'
	);
	$selArr = array ('DISTINCT(access.username)');
	$whereArr = array (
		'wf_documents.id=' . $db_object->quote ((int) $wf_document_id),
		'wf_documents.state_wf_def_id=wf_defs.id',
		'wf_defs.node_id=wf_nodes.id',
		'wf_nodes.group_list_id=group_list.list_id',
		'group_list.groupname=groups.real_groupname',
		'groups.id=users_in_group.group_id',
		'users_in_group.uid=access.uid'
	);
	$groupInfo = getTableInfo ($db_object, $tableArr, $selArr, $whereArr,
		'queryCol');
	return ($groupInfo);
}
/*
 * This function will retrieve all users for a specific user_list
 */
function getUsers($db_object, $wf_document_id) {
	$tableArr = array (
		'wf_documents',
		'wf_defs',
		'wf_nodes',
		'user_list'
	);
	$whereArr = array (
		'wf_documents.id=' . $db_object->quote ((int) $wf_document_id),
		'wf_documents.state_wf_def_id=wf_defs.id',
		'wf_defs.node_id=wf_nodes.id',
		'wf_nodes.user_list_id=user_list.list_id'
	);

	$res = getTableInfo ($db_object, $tableArr, array('user_list.username'),
		$whereArr, 'queryCol');
	return $res;
}
/*
 *	This function will retrieve the number of signatures for that document
 */
function getSignatureCount($db_object, $wf_document_id, $wf_node_id, $state, $uname) {
	$tableArr = array ('signatures', 'wf_history');
	$whereArr = array (
		'signatures.wf_history_id=wf_history.id',
		'wf_document_id=' . $db_object->quote ((int) $wf_document_id),
		'wf_node_id=' . $db_object->quote ((int) $wf_node_id),
		'wf_history.state=' . $db_object->quote ((int) $state),
		'username=' . $db_object->quote ($uname)
	);
	$count = getTableInfo ($db_object, $tableArr,
		array ('COUNT(signatures.id)'), $whereArr, 'queryOne');
	return ($count);
}
/*
 *	This function will retrieve the list_ids for the user_list and group_list
 */
function getListIDs($db_object, $id) {
	$query = "SELECT wf_nodes.user_list_id,wf_nodes.group_list_id ";
	$query .= "FROM wf_nodes,wf_documents,wf_defs ";
	$query .= "WHERE wf_documents.id=$id AND wf_documents.state_wf_def_id=wf_defs.id ";
	$query .= "AND wf_defs.node_id=wf_nodes.id";
	$nodeInfo = $db_object->query($query);
	dbErr($nodeInfo);

	return ($nodeInfo->fetchRow(MDB2_FETCHMODE_ORDERED));
}

function getWFWhichUser($db_object, $wf_document_id) {
	$query = "SELECT which_user ";
	$query .= "FROM wf_documents,wf_defs,wf_nodes WHERE ";
	$query .= "wf_documents.id=$wf_document_id ";
	$query .= "AND wf_documents.state_wf_def_id=wf_defs.id ";
	$query .= "AND wf_defs.node_id=wf_nodes.id ";

	$res = $db_object->queryOne($query);
	dbErr($res);
	return $res;
}

function getWFNodeMessage($db_object, $wf_document_id, $alert = 0) {
	$query = "SELECT message FROM ";
	$query .= "wf_documents, wf_defs, wf_nodes ";
	$query .= "WHERE wf_documents.id = " . $db_object->quote ($wf_document_id, 'integer') . " AND state_wf_def_id=wf_defs.id ";
	$query .= "AND wf_defs.node_id=wf_nodes.id ";
	if($alert) {
		$query .= "AND message_alert=$alert";
	}
	$res = $db_object->queryOne($query);
	dbErr($res);
	return $res;
}

function getWFValueNodes($db_object, $wf_document_id) {
	$query = "SELECT wf_value_list.id,wf_value_list.message FROM ";
	$query .= "wf_documents, wf_defs, wf_nodes, wf_value_list ";
	$query .= "WHERE wf_documents.id=$wf_document_id AND state_wf_def_id=wf_defs.id ";
	$query .= "AND wf_defs.node_id=wf_nodes.id AND wf_nodes.value_list_id=wf_value_list.value_list_id ORDER BY wf_value_list.id";
	$res = $db_object->queryAll($query);
	dbErr($res);
	return $res;
	
}

/*
 *	Gets all usernames that have sign the document
 */
function getWFSignatures($db_object, $wf_document_id, $state, $uname = NULL) {
	$query = "SELECT wf_history.username FROM signatures,wf_history ";
	$query .= "WHERE signatures.wf_history_id=wf_history.id ";
	$query .= "AND wf_history.wf_document_id=$wf_document_id ";
	$query .= "AND wf_history.state=$state ";
	if ($uname != NULL)
		$query .= "AND username='$uname'";

	$res = $db_object->queryCol($query);
	dbErr($res);
	return $res;
}

function getWFDefsName( $db_object, $wf_document_id ) {
	$query = "SELECT defs_name FROM wf_defs,wf_documents WHERE ";
	$query .= "wf_documents.id=$wf_document_id AND ";
	$query .= "wf_documents.wf_def_id=wf_defs.id";
	$res = $db_object->queryOne($query);
	dbErr($res);
	return $res;
}

function getWFDefsInfo($db_object, $defsName) {
	$query = 'SELECT MAX(state), MIN(id), owner FROM wf_defs WHERE ';
	$query .= "defs_name = " . $db_object->quote ($defsName) . " GROUP BY owner";
	$result = $db_object->queryRow($query);
	dbErr($result);
	return array_values($result);
}

function lockTables($db_object, $tables) {
	$dbType = getdbType();
	if($dbType == 'mysql' or $dbType == 'mysqli'){ 
		$lockArr = array ();
		$query = 'LOCK TABLES';
		foreach ($tables as $myTable) {
			$lockArr[] = "$myTable WRITE";
		}
		$query .= ' '.implode(',', $lockArr);
		$result =& $db_object->query($query);
		dbErr($result);
	} elseif ($dbType == 'pgsql' or $dbType == 'db2') {
		$res =& $db_object->beginTransaction ();
		dbErr ($res);
		foreach ($tables as $myTable) {
			$query = "LOCK TABLE $myTable IN EXCLUSIVE MODE";
			$result =& $db_object->query($query);
			dbErr($result);
		}
	} elseif ($dbType == 'mssql') {
		$res = $db_object->beginTransaction ();
		dbErr($res);
		foreach ($tables as $myTable) {
			$query = "SELECT TOP 1 * FROM $myTable WITH (TABLOCKX)";//,HOLDLOCK)";
			$result = $db_object->query ($query);
			dbErr($result);
		}
	}
	return true;
}

function unlockTables(&$db_object) {
	$dbType = getdbType();
	if($dbType == 'mysql' or $dbType == 'mysqli') {
		$query = "UNLOCK TABLES";
		$result =& $db_object->query($query);
		dbErr($result);
	} else {
		$res = $db_object->commit();
		dbErr($res);
	}
	return true;
}

function addWFNode($db_object, $stateNum, $type = NULL) {
	if (!lockTables($db_object, array ('wf_nodes'))) {
		return false;
	}
	$queryArr = array ();
	if ($type) {
		$queryArr['node_type'] = $type;
	} else {
		$queryArr['node_type'] = 'SIGNATURE';
	}
	if ($type == 'STATE') {
		$queryArr['node_name'] = 'STATE'.$stateNum;
	}
	$result = $db_object->extended->autoExecute('wf_nodes', $queryArr);
	if (PEAR :: isError($result)) {
		unlockTables($db_object);
		return false;
	}
	$query = "SELECT MAX(id) FROM wf_nodes";
	$nodeID = $db_object->queryOne($query);
	if (PEAR :: isError($nodeID)) {
		unlockTables($db_object);
		return false;
	}
	if (!unlockTables($db_object)) {
		return false;
	}
	return $nodeID;
}

function addNodetoWFDefs($db_object, $queryArr) {
	if (!lockTables($db_object, array ('wf_defs'))) {
		return false;
	}
	$result = $db_object->extended->autoExecute('wf_defs', $queryArr);
	if (PEAR :: isError($result)) {
		unlockTables($db_object);
		return false;
	}
	$query = "SELECT MAX(id) FROM wf_defs";
	$myID = $db_object->queryOne($query);
	if (PEAR :: isError($myID)) {
		unlockTables($db_object);
		return false;
	}
	if (!unlockTables($db_object)) {
		return false;
	}
	$query = "UPDATE wf_defs SET next = $myID WHERE id = {$queryArr['prev']}";
	$result = $db_object->query($query);
	if (PEAR :: isError($result)) {
		return false;
	}
	$query = "UPDATE wf_defs SET prev = $myID WHERE id = " . $queryArr['next'];
	$result = $db_object->query($query);
	if (PEAR :: isError($result)) {
		return false;
	}
	return true;
}

function addWFNodeName($db_object, $nodeID, $parentID, $numStates) {
	$query = "SELECT COUNT(*) FROM wf_defs WHERE parent_id = " . $parentID;
	$count = $db_object->queryOne($query);
	if (PEAR :: isError($count)) {
		return false;
	}

	$query = "SELECT node_name FROM wf_defs,wf_nodes WHERE ";
    	$query .= "parent_id=$parentID AND node_id=wf_nodes.id";
    	$nodeArr = $db_object->queryCol($query);

    	$count -= $numStates;
    	$name = 'NODE'.$count;
    	while(in_array($name,$nodeArr)) {
        	$name = 'NODE'.$count++;
    	}

	$query = "UPDATE wf_nodes SET node_name = '$name' WHERE id = " . $nodeID;
	$result = $db_object->query($query);
	if (PEAR :: isError($result)) {
		return false;
	}
	return $name;
}

function getWFStateLines($db_object, $parentID) {
	$query = 'SELECT wf_defs.id, state, prev, next, node_id, node_type, ';
	$query .= 'node_name FROM wf_defs, wf_nodes WHERE parent_id = ' . 
		$parentID . ' AND ';
	$query .= 'node_id = wf_nodes.id ORDER BY state,node_id ASC';
	$result = $db_object->queryAll($query);
	if (PEAR :: isError($result)) {
		return false;
	}
	return $result;
}

function getCurrentWFNodeInfo($db_object, $wf_doc_id) {
	$q = "SELECT node_id,state FROM wf_defs, wf_documents WHERE ";
	$q .= "wf_documents.id = $wf_doc_id AND ";
	$q .= "state_wf_def_id = wf_defs.id";
	$res = $db_object->queryRow($q);
	dbErr($res);
	return $res;
}

function getWFNodeInfo($db_object, $nodeID) {
	$query = 'SELECT wf_defs.id as wfid,parent_id,node_name, prev, next, user_list_id, group_list_id,' .
			'node_name, node_type, message,which_user,email,message_alert FROM wf_defs, ' .
			'wf_nodes WHERE node_id = wf_nodes.id AND node_id = ' . $nodeID;
	$myRow = $db_object->queryRow($query);
	dbErr($myRow);
	$dept = $db_object->database_name;
  $db_doc = getDbObject( "docutron" );
	$queryDoc = "select * from settings where k='wfNotesRequired".$nodeID."' and department='".$dept."'";
	$required = $db_doc->queryRow($queryDoc);
	if (isset($required["value"])) $myRow["notes_required"] = $required["value"]; else $myRow["notes_required"] = "0";
	return $myRow;
}

//Traces the wf_history to find the previous node
function getNodeFromHistory($db_object, $wf_document_id, $state_wf_def_id) {
	$query = "SELECT wf_defs.id FROM wf_history, wf_defs, wf_nodes";
	$query .= " WHERE (wf_history.action='accepted' OR wf_history.action='rejected')";
	$query .= " AND wf_history.wf_document_id = $wf_document_id";
	$query .= " AND wf_defs.node_id = wf_history.wf_node_id";
	$query .= " AND wf_defs.node_id = wf_nodes.id"; 
	$query .= " AND wf_nodes.node_type != 'STATE'"; //checks that its not a state node
//TODO Fix this problem
if( $state_wf_def_id!='' ){
		$query .= " AND wf_defs.id != $state_wf_def_id"; //checks that its not looping to itself
}else{
error_log( "wf_document_id=$wf_document_id" );
}
	$query .= " ORDER BY wf_history.id DESC LIMIT 1";
	$oneRes = $db_object->queryOne($query);
	dbErr($oneRes);
	return $oneRes;
}

///////////////////////////////////////////
//this will return the next list_id for either the
//user_list or group_list table
function getWFMaxListID($db_object, $type_list) {
	$query = "SELECT MAX(list_id)+1 FROM ".$type_list;
	$max = $db_object->queryOne($query);
	if (PEAR :: isError($max)) {
		return false;
	}
	if (!$max)
		$max = 1;
	return ($max);
}

///////////////////////////////////////////
//this will add to an existing group list
function addToWFGroupList($db_object, $listID, $groupname) {
	$lockTables = false;
	if (!$listID) {
		$lockTables = true;
		if (!lockTables($db_object, array ('group_list'))) {
			return false;
		}
		$listID = getWFMaxListID($db_object, 'group_list');
	}
	$queryArr = array ('list_id' => $listID, 'groupname' => $groupname);
	$result = $db_object->extended->autoExecute('group_list', $queryArr);
	if (PEAR :: isError($result)) {
		if ($lockTables) {
			unlockTables($db_object);
		}
		return false;
	}
	if ($lockTables) {
		unlockTables($db_object);
	}
	return $listID;
}

///////////////////////////////////////////
//this will add to an existing user list
function addToWFUserList($db_object, $listID, $uname) {
	$lockTables = false;
	if (!$listID) {
		$lockTables = true;
		if (!lockTables($db_object, array ('user_list'))) {
			return false;
		}
		$listID = getWFMaxListID($db_object, 'user_list');
	}
	$queryArr = array ('list_id' => (int)$listID, 'username' => $uname);
	$result = $db_object->extended->autoExecute('user_list', $queryArr);
	if (PEAR :: isError($result)) {
		if ($lockTables) {
			unlockTables($db_object);
		}
		return false;
	}
	if ($lockTables) {
		unlockTables($db_object);
	}
	return $listID;
}

/*
 *	This function will return the nodeType
 */
function getWFPrevNodeType($db_object, $wf_document_id) {
	$query = "SELECT node_type FROM wf_documents,wf_defs,wf_nodes ";
	$query .= "WHERE wf_documents.id=$wf_document_id AND wf_documents.state_wf_def_id=wf_defs.id ";
	$query .= "AND wf_defs.prev=wf_nodes.id";
	$result = $db_object->queryOne($query);
	dbErr($result);
	return str_replace(' ', '', $result);
}

function getWFNodeType($db_object, $wf_document_id) {
	$query = "SELECT node_type FROM wf_documents,wf_defs,wf_nodes ";
	$query .= "WHERE wf_documents.id=$wf_document_id AND wf_documents.state_wf_def_id=wf_defs.id ";
	$query .= "AND wf_defs.node_id=wf_nodes.id";
	$res = $db_object->queryOne($query);
	dbErr($res);
	$res = str_replace(' ', '', $res);
	return $res;
}
/* 
 *	This function will return the status of the workflow document
 */
function getWFStatus($db_object, $wf_document_id) {
	$query = "SELECT status FROM wf_documents WHERE id=$wf_document_id";
	$statusInfo = $db_object->query($query);
	dbErr($statusInfo);
	$status = $statusInfo->fetchRow(MDB2_FETCHMODE_ORDERED);

	return ($status[0]);
}
/* 
 *	This function will return the history of the workflow document
 */
function getWFlowHistory($db_object, $whereClause, $searchDep = false) {
	//here don't use wf_history.notes and wf_history.action in query since
	//those are clob data types. We are taking care for these with the 
	//alternate method 'getWFHistData' to view workflow history correctly.
	$query = "SELECT wf_history.id,wf_history.date_time,wf_history.username, ";
	$query .= "wf_history.state,wf_defs.defs_name, ";
	$query .= "wf_documents.cab,wf_documents.status,wf_documents.owner, ";
	$query .= "wf_documents.doc_id,wf_nodes.node_name,wf_nodes.node_type ";
	$query .= "FROM wf_documents,wf_nodes,wf_history,wf_defs";
	if($searchDep) {
		$query .= ',departments';
	}
	$query .= ' WHERE ';
	$query .= "wf_history.wf_document_id=wf_documents.id AND ";
	$query .= "wf_history.wf_node_id=wf_nodes.id AND ";
	$query .= "wf_documents.wf_def_id=wf_defs.id".$whereClause;
	$res = $db_object->query($query);
	dbErr($res);
	return $res;
}
/* 
 *	This function will return the owner of the workflow document
 */
function getWFOwner($db_object, $wf_document_id) {
	$query = "SELECT owner FROM wf_documents WHERE id=$wf_document_id";
	$ownerInfo = $db_object->query($query);
	dbErr($ownerInfo);
	$owner = $ownerInfo->fetchRow(MDB2_FETCHMODE_ORDERED);

	return ($owner[0]);
}

function getWFLink2($db_object) {
	$query = "SELECT wf_documents.id,max(cab) as cab,max(doc_id) as " .
			"doc_id,max(wf_documents.file_id) as file_id, max(wf_defs.defs_name) as defs_name,";
	$query .= "max(wf_nodes.node_name) as node_name,max(wf_nodes.node_type) as".
			" node_type ,MAX(date_time) AS date_time ";
	$query .= "FROM wf_documents,wf_defs,wf_nodes,wf_history WHERE ";
   	$query .= "state_wf_def_id=wf_defs.id AND wf_defs.node_id=wf_nodes.id ";
   	$query .= "AND wf_document_id=wf_documents.id AND wf_node_id = wf_nodes.id ";
	$query .= "AND action " . LIKE . " 'notified'";
	if(getdbType() == 'mysql' or getdbType() == 'mysqli' or getdbType() == 'pgsql') {
		$query .= " GROUP BY wf_documents.id ORDER BY cab";
	} elseif(getdbType()=='db2' or getdbType() == 'mssql') {
		$query .= " GROUP BY wf_documents.id,cab ORDER BY cab";
	}
   $res = $db_object->extended->getAssoc($query);
   dbErr($res);
   return $res;
}


function getWFLink($db_object, $wf_document_id) {
	$query = "SELECT wf_documents.id,max(cab) as cab,max(doc_id) as " .
			"doc_id,max(wf_documents.file_id) as file_id, max(wf_defs.defs_name) as defs_name,";
	$query .= "max(wf_nodes.node_name) as node_name,max(wf_nodes.node_type) as".
			" node_type ,MAX(date_time) AS date_time ";
	$query .= "FROM wf_documents,wf_defs,wf_nodes,wf_history WHERE wf_documents.id=$wf_document_id AND ";
   	$query .= "state_wf_def_id=wf_defs.id AND wf_defs.node_id=wf_nodes.id ";
   	$query .= "AND wf_document_id=wf_documents.id ";
  // 	$query .= "AND wf_document_id=wf_documents.id AND wf_node_id = wf_nodes.id ";
	$query .= "AND action " . LIKE . " 'notified'";
	if(getdbType() == 'mysql' or getdbType() == 'mysqli' or getdbType() == "pgsql") {
		$query .= " GROUP BY wf_documents.id ORDER BY cab";
	} elseif(getdbType()=='db2' or getdbType() == 'mssql') {
		$query .= " GROUP BY wf_documents.id,cab ORDER BY cab";
	}
	$res = $db_object->queryRow($query);
	dbErr($res);
	return $res;
}

function getWFNodeEmail($db_object, $wf_document_id) {
	$query = "SELECT email FROM ";
	$query .= "wf_documents, wf_defs, wf_nodes ";
	$query .= "WHERE wf_documents.id=$wf_document_id ";
	$query .= "AND state_wf_def_id=wf_defs.id ";
	$query .= "AND wf_defs.node_id=wf_nodes.id";
	$res = $db_object->queryOne($query);
	dbErr($res);
	return $res;
}

/*
 * This function will be placed in utilityMysql.php
 * addToWorkflow adds a document to workflow
 */
function addToWorkflow( $db_object, $wf_def_id, $doc_id, $file_id, $cab, $owner ) {
	if(sizeof(trim($file_id))==0 || ($file_id==NULL) ) {
		$file_id = -1;
	}

	lockTables($db_object,array('wf_documents','wf_defs'));
	$sArr = array('COUNT(id)');
	$wArr = array(	"cab='".$cab."'",
					"doc_id=".(int)$doc_id,
					"file_id=".(int)$file_id,
					"status!='COMPLETED'");
	$ct = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryOne');
	if($ct < 1) {
		$insertArr = array(
			"wf_def_id"			=> (int)$wf_def_id,
			"cab"				=> $cab,
			"doc_id"			=> (int)$doc_id,
			"file_id"			=> (int)$file_id,
			"state_wf_def_id"	=> (int)$wf_def_id,
			"owner"				=> $owner,
			"status"			=> "IN PROGRESS"
				  );
		$res = $db_object->extended->autoExecute('wf_documents',$insertArr);
		dbErr($res);	
				
		$sArr = array('MAX(id)');
        $id = getTableInfo($db_object,'wf_documents',$sArr,array(),'queryOne');
	} else {
		$id = -1;	
	}
	unlockTables($db_object);

	return $id;
}

function IsWorkflowInProgress( $db_object, $doc_id, $file_id, $cab ) {
	if(sizeof(trim($file_id))==0 || ($file_id==NULL) ) {
		$file_id = -1;
	}

	$sArr = array('COUNT(id)');
	$wArr = array(	"cab='".$cab."'",
					"doc_id=".(int)$doc_id,
					"file_id=".(int)$file_id,
					"status!='COMPLETED'");
	$ct = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryOne');
	if($ct < 1) {
		return false;
	} else {
		return true;	
	}
}

function getWorkflowReport($db_object, $wf_document_id) {
	$query = "SELECT username,action,date_time,notes FROM wf_history ";
	$query .= "WHERE wf_document_id=$wf_document_id";
	$historyInfo = $db_object->query($query);
	dbErr($historyInfo);
	$histReport = '';
	while ($result = $historyInfo->fetchRow()) {
		$uname = $result['username'];
		$action = $result['action'];
		$date = $result['date_time'];
		$notes = $result['notes'];

		$histReport .= $uname."\t\t".$action."\t\t".$date."\t\t".$notes."\n";
	}

	return ($histReport);
}

function getGroupIDList($db_object) {
	$query = "SELECT real_groupname,id FROM groups";
	$result = $db_object->extended->getAssoc($query);
	dbErr($result);
	return $result;
}

/**
  *  This function will get the Records from table.
  *  @param     Object Database  $db_object Database
  *  @param     String           $cabinetName.
  *  @param     String           $tempTable
  *  @param     String           $isFiles
  *  @see       ./CDBackup/XMLCabinetFunc.php
  */
function getFoldersForBackup($db_object, $cabinetName, $tempTable, $isFiles) {
	if ($tempTable == "") {
		$q = "SELECT * FROM $cabinetName ORDER by doc_id";
	} else {
		if ($isFiles == 1) {
//			$q = "SELECT * FROM $cabinetName ORDER by doc_id";
			$q = "SELECT distinct $cabinetName.* FROM $cabinetName,$tempTable WHERE ";
			$q .= "$cabinetName.doc_id=$tempTable.doc_id ORDER by doc_id";
		} else {
			$q = "SELECT $cabinetName.* FROM $cabinetName,$tempTable WHERE ";
			$q .= "$cabinetName.doc_id=$tempTable.result_id ORDER by doc_id";
		}
	}
	$result = $db_object->query($q);
	dbErr($result);
	return $result;
}

/**
  *  This function will get the records from the specified table.
  *  @param  Object Database   $db_object Database
  *  @param  String            $isFiles
  *  @param  String            $cabinetName
  *  @param  String            $$tempTable
  *  @see ./CDBackup/XMLCabinetFunc.php
  */
function getCabinetSearch($db_object, $cabinetName, $tempTable, $isFiles) {
	$cabinetName = trim($cabinetName);
	if ($isFiles == 1) { //run query in files for file search

		$ft = $cabinetName."_files";
		$query = "SELECT $ft.id,$ft.filename,$ft.doc_id,$ft.subfolder,";
		$query .= "$ft.ordering FROM $ft,$tempTable WHERE ";
		$query .= "$ft.id=$tempTable.result_id AND deleted = 0 AND display = 1 ORDER by doc_id,id";
	} else {
		$query = "SELECT id,filename, doc_id,subfolder,ordering from ";
		$query .= "$cabinetName"."_files WHERE deleted = 0 AND display = 1 ORDER BY doc_id,ordering";
	}
	$res = $db_object->query($query);
	dbErr($res);
	return $res;
}

function getBarcodeReconciliationInfo($db_object,$start,$displayNum,$department,$orderBy,$search,$userArr=array()) {
	$sArr = array('id','barcode_info','username','cab','barcode_field','date_printed','department');
	$wArr = array("department='$department'");

	if( sizeof($userArr) > 0 ) {
		$searchUsers = array();
		foreach($userArr AS $username) {
			$searchUsers[] = "username='$username'";
		}
		$wArr[] = "(".implode(" OR ", $searchUsers).")";
	}

	if($search) {
		$whereStr = "(barcode_info " . LIKE . " '%$search%' OR cab " . LIKE . " '%".str_replace(" ", "_", $search)."%' ";
		//can't use LIKE when value is a timestamp in db2
		if(is_numeric($search)) {
			$whereStr .= "OR id=$search ";
		}
		$whereStr .= "OR barcode_field " . LIKE . " '%$search%' ";
		$whereStr .= "OR username " . LIKE . " '%$search%'";
		$whereStr .= ") ";
		$wArr[] = $whereStr;
	}
	$allResult = getTableInfo($db_object,'barcode_reconciliation',$sArr,$wArr,'queryAll',$orderBy,$start,$displayNum);
	return $allResult;
}

function countBarcodeReconciliation($db_object,$department,$search,$userArr=array()) {
	$query = "SELECT COUNT(id) FROM barcode_reconciliation WHERE department='$department' "; 
	if( sizeof($userArr) > 0 ) {
		$searchUsers = array();
		foreach($userArr AS $username) {
			$searchUsers[] = "username='$username'";
		}
		$query .= "AND (".implode(" OR ", $searchUsers).") ";
	}
	if($search) {
		$query .= "AND (barcode_info " . LIKE . " '%$search%' OR cab " . LIKE . " '%$search%' ";
		//can't use LIKE when value is a timestamp in db2
		if(is_numeric($search)) {
			$query .= "OR id=$search ";
		}
		$query .= "OR barcode_field " . LIKE . " '%$search%' ";//OR date_printed " . LIKE . " '%$search%' ";
		$query .= "OR username " . LIKE . " '%$search%'";
		$query .= ") ";
	}
	$result = $db_object->queryOne($query);
	dbErr($result);
	return $result;
}

function getDelegationHistory($db_object,$start,$displayNum,$where) {
	$allResult = getTableInfo($db_object,'inbox_delegation_history',array(),$where,'queryAll',array(),$start,$displayNum);
	return $allResult;
}

function getBarcodeHistory($db_object,$start,$displayNum,$where) {
	$allResult = getTableInfo($db_object,'barcode_history',array(),$where,'queryAll',array('id' => 'ASC'),$start,$displayNum);
	return $allResult;
}

/**
  *  This function returns the sequence number for a barcode. It only works if the
  *  first index column contains the unique sequence numbers.
  *  @param  Object Database	$db_object
  *  @param  String 		$cabinet
  *  @param  String		$docID
  *  @see    /energie/enegiefuncs.php
  */
function getSeqNumber($db_object, $cabinet, $docID) {

	$query = "SELECT * FROM $cabinet WHERE doc_id = $docID";
	$result = $db_object->queryRow($query, null, MDB2_FETCHMODE_ORDERED);
	dbErr($result);
	if (is_numeric($result[2]))
		return $result[2];
}
/**
  *  This function  will get the distinct subfolder no of filenames from cabinet table.
  *  @param  Object Database	$db_object
  *  @params String 		$cab
  *  @params String		$doc_id
  *  @see    /energie/enegiefuncs.php
  */
function getDistinctFolder($db_object, $cab, $doc_id) {
	$query = "SELECT DISTINCT(subfolder), COUNT(filename) AS filename FROM $cab";
	$query .= "_files WHERE doc_id = $doc_id AND display = 1 GROUP BY subfolder";
	$res = $db_object->query($query);
	dbErr($res);
	return $res;
}

/**
  * This function will get total no of ids from the specified table
  * @param  Object    Database         $db_object
  * @param  String      $cab
  * @param  String      $dep
  * @param  String	$uname
  * @param  String	$temp_table
  * @see    /search/searchResultsAction.php
  */
function getCabinetAll($db_object, $cab, $dep, $uname, $temp_table, $cols=array()) {
	global $DEFS;
	if( sizeof($cols) > 0 ) {
		$query = "SELECT ".implode(",", $cols);
	} else {
		$query = "SELECT $cab.*";
	}

	if(getdbType() == 'mysql' or getdbType() == 'mysqli') {
		$query .= " INTO OUTFILE '{$DEFS['DATA_DIR']}";
		$query .= "/$dep/$uname"."_backup/searchData.xls' ";
		$query .= "FIELDS TERMINATED BY '"."\\t"."' ESCAPED BY '";
		$query .= addslashes("\\");
		$query .= "' LINES TERMINATED BY '"."\\n"."' STARTING BY '' ";
		$query .= "FROM $cab,$temp_table WHERE $cab.doc_id=$temp_table.result_id";
		$res = $db_object->query($query);
		dbErr($res);
		return $res;
	} else {
		$query .= " from $cab,$temp_table WHERE $cab.doc_id=$temp_table.result_id";
		$res = $db_object->query($query);
		dbErr($res);
		$full_dump_path = "{$DEFS['DATA_DIR']}/$dep/$uname"."_backup/searchData.xls";
		$fd = fopen($full_dump_path, 'w');
		if (!$fd) {
			die("failed to create a files $filename");
		}
		while ($row = $res->fetchRow()) {
			foreach($row as $value) {
				fwrite($fd, $value."\t");
			}
			fwrite($fd, "\n");
		}
		fclose($fd);
		return '';
	}
}
/**
  * This function will get records from specified table
  * @param  Object    Database         $db_object
  * @param  String		       $full_dump_path
  * @param  String		       $low	
  * @param  String		       $high
  * @see    /audit/auditBackup.php
  */
function getOutfile($db_object, $full_dump_path, $low, $high) {
	if (getdbType() == 'mysql' or getdbType() == 'mysqli') {
		$query = "SELECT * INTO OUTFILE '$full_dump_path' FIELDS TERMINATED BY ',' LINES TERMINATED BY \"\\n\" FROM audit WHERE id>=$low AND id<=$high";
		$res = $db_object->query($query);
		dbErr($res);
	} else {
		$query = "SELECT * FROM audit WHERE id>=$low AND id<=$high";
		$res = $db_object->query($query);
		dbErr($res);
		$fd = fopen($full_dump_path, 'w+');
		if ($fd == null) {
			die("failed to create a files $full_dump_path");
		}

		while ($row = $res->fetchRow()) {
			$value = $row['id'];
			$value .= ",".$row['username'];
			$value .= ",".$row['datetime'];
			$value .= ",".$row['info'];
			$value .= ",".$row['action'];
			if ($value != "")
				fwrite($fd, $value."\n");

		}
		fclose($fd);
	}

}
/**
  * This functions will get records from specified table
  * @param  Object    Database         $db_object
  * @param  String 		       $table
  * @param  String  		       $start
  * @param  String		       $end
  * @see    /audit/audit.php
  */
function getIndex1($db_object, $table, $start, $end) {
	$tArr = array ('audit', $table);
	$wArr = array ('audit.id='.$table.'.result_id');
	$res = getTableInfo ($db_object, $tArr, array (), $wArr, 'query', array ('id' => 'ASC'), $start, $end);
/*	if (getdbType() == "mysql" or getdbType() == 'mysqli') {
		$query = "select audit.* from audit, $table where audit.id=$table.result_id " .
				"LIMIT $start, $end";
	} else {

		//above syntax is not working in db2 so alternate provided below .. bala

		$query = "select * from audit  where id in (select result_id from ";
		$query .= "$table where table_id>$start and table_id<$end)";

	}
	$res = $db_object->query($query);
	dbErr($res);
*/
	return $res;
}

/**
  * This function getting all records from two  tables.
  * @param  Object      Database        $db_object
  * @param  String                      $cabinetName
  * @param  String                      $tempTable
  * @see    /energie/searchResults.php
  */
function getListFromDual($db_object, $cabinetName, $tempTable) {
	$query = "SELECT COUNT(*) FROM $cabinetName, $tempTable";
	$query .= " WHERE $cabinetName.doc_id=$tempTable.result_id";
	$res = $db_object->query($query);
	dbErr($res);
	return $res;
}

/**
  * This fuction will get users values from groups table.
  * @param  Object  Database	$db_object
  * @param  String		$groupName.
  * @see ./groups/groups.php 
  */
function getUsersFromGroup($db_object, $groupName) {
	$query = "SELECT username FROM access,groups,users_in_group ";
	$query .= "WHERE real_groupname='$groupName' AND groups.id=users_in_group.group_id ";
	$query .= "AND users_in_group.uid=access.uid";
	$res = $db_object->queryCol($query);
	dbErr($res);
	return $res;
}

function getGroupsForUser($db_object,$username) {
	$query = "SELECT real_groupname FROM groups,users_in_group,access ";
	$query .= "WHERE access.username='$username' AND ";
	$query .= "access.uid=users_in_group.uid AND ";
	$query .= "groups.id=users_in_group.group_id";
	$res = $db_object->queryCol($query);
	dbErr($res);
	return $res;
}

function getUserlistFromGroups($db_object) {
	$query = "SELECT real_groupname,users FROM groups";
	$result = $db_object->extended->getAssoc($query);
	dbErr($result);
	return($result);
}
/**
  * This function getting arb groupname from the group table.
  * @param  Object  Database            $db_object
  * @param  String			$department
  * @see    /groups/groups.php
  */
function getRealGroupNames($db_object) {
	$query = "SELECT real_groupname,arb_groupname FROM groups";
	$res = $db_object->extended->getAssoc($query);
	dbErr($res);
	uasort($res, 'strnatcasecmp');
	return $res;
}

function getGroupAccess($db_object,$column,$key=NULL,$value=NULL) {
	$query = "SELECT $column,access ";
	$query .= "FROM group_access,groups,departments WHERE ";
	$query .= "groups.id=group_access.group_id AND departmentid=cabid ";
	if( $key ) {
		if($key != 'departmentid') {
			$query .= "AND $key='$value'";
		} else {
			$query .= "AND $key=$value";
		}
	}
	$results = $db_object->extended->getAssoc($query);
	dbErr($results);
	return $results;
}

function getGroupForUsers($db_object,$username) {
	$query = "SELECT real_groupname FROM groups,users_in_group,access ";
	$query .= "WHERE access.username='$username' AND ";
	$query .= "access.uid=users_in_group.uid ";
	$query .= "AND users_in_group.group_id=groups.id";
	$result = $db_object->queryCol($query);
	dbErr($result);
	return $result;
}

function queryAllGroupAccess(&$db_object,$username) {
	$query = "SELECT real_name,real_groupname,group_access.access ";
	$query .= "FROM group_access,groups,departments,users_in_group,access WHERE ";
	$query .= "groups.id=group_access.group_id AND DepartmentID=cabID ";
	$query .= "AND groups.id=users_in_group.group_id ";
	$query .= "AND users_in_group.uid=access.uid AND access.username='$username'";
	$results = $db_object->queryAll($query);
	dbErr($results);
	return $results;
}

function getMaxQuota($db_object,$col) {
	return getTableInfo ($db_object, 'quota', array ($col), array (),
		'queryOne');
}

/**
 * This function will get records from outfile.
 * @param  Object  Database    $db_object
 * @param  String              $department.
 * @param  String	       $username.
 * @param  String	       $cabinet
 * @see ./auto_completing_indexing/viewAutoCompleteTable.php
 */
function queryAllFromOutFile($db_object, $department, $username, $cabinet) {
	global $DEFS;
	if(getdbType() == 'mysql' or getdbType() == 'mysqli') {
		$query = "SELECT * INTO OUTFILE ";
		$query .= "'{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete/autoCompleteData.xls' ";
		$query .= "FIELDS TERMINATED BY '"."\\t"."' ESCAPED BY '".addslashes("\\")."' ";
		$query .= "LINES TERMINATED BY '"."\\n"."' STARTING BY '' ";
		$query .= "FROM auto_complete_$cabinet";
		$res = $db_object->query($query);
		dbErr($res);
	} else {
		$query = "select * from auto_complete_$cabinet";
		$res = $db_object->query($query);
		dbErr($res);
		$full_dump_path = "{$DEFS['DATA_DIR']}/$department/$username"."_autoComplete/autoCompleteData.xls";
		$fd = fopen($full_dump_path, 'w');
		if (!$fd) {
			die("failed to create a files $full_dump_path");
		}
		while ($row = $res->fetchRow()) {
			foreach($row as $value) {
				fwrite($fd, $value."\t");
			}
			fwrite($fd, "\n");
		}
		fclose($fd);
		return '';
	}
}

/**
  * This function will get doc_id,location,subfolder and filename values from the table.
  * @param  Object  Database    $client_files
  * @param  String              $cabinet.
  * @param  String 		$files_table.
  * @see    /install/ upgrade25-30.php
  */
function getIdLocSFolderFile($client_files, $cabinet, $files_table) {
	$res = getTableInfo ($client_files, array ($cabinet, $files_table),
		array ($cabinet.'.doc_id', 'location', 'subfolder', 'filename'),
		array ($cabinet.'.doc_id='.$files_table.'.doc_id',
		'filename IS NOT NULL'));
	return $res;
}
/**
  * This function moved from versioning.php.
  * This function getting v_major,v_moir,parent_id  values from the table.
  * @author Tristan McCann
  * @param  String              $cabinetName
  * @param  Int 	            $fileID
  * @param  Object  Database    $db_object
  * @see    /lib/versioning.php
  */
function getNewerList($cabinetName, $fileID, $db_object) {
	$cabinetName = trim($cabinetName);
	$selArr = array ('v_major', 'v_minor', 'parent_id');
	$row = getTableInfo ($db_object, $cabinetName.'_files',
		$selArr, array ('id' => (int) $fileID), 'queryRow');
	$query = "SELECT id FROM $cabinetName"."_files WHERE ";
	$query .= "((v_major > {$row['v_major']}) OR ";
	$query .= "(v_major = {$row['v_major']} AND ";
	$query .= "v_minor > {$row['v_minor']})) AND ";
	$query .= "parent_id=" . $row['parent_id'] . " AND ";
	$query .= "deleted = 0";
	$newerList = $db_object->queryAll($query);
	dbErr($newerList);
	return $newerList;
}
/**
  * This function getting id,OCR_context  values from the $cabinetName table
  * @param  Object  Database    $db_object
  * @param  String		$cab
  * @param  String		$temp_table1.
  * @see ./bots/contextBot.php
  */
function getIDOCRfromCab($db_object, $cab, $temp_table1) {
	$cab = trim($cab);
	$selArr = array ('id', 'OCR_context');
	$tableArr = array ($cab.'_files', $temp_table1);
	$whereArr = array (
		'result_id=id',
		'deleted=0',
		'display=1'
	);
	$res = getTableInfo ($db_object, $tableArr, $selArr, $whereArr);
	return $res;
}

function getPathFromFileID($db, $cabinet, $fileID, $dataDir) {
	$query = "SELECT ".dbConcat(array(
		$dataDir,
		"'/'",
		"REPLACE(location,' ', '/')",
		"'/'",
		"COALESCE(".dbConcat('subfolder', "'/'").",'')",
		'filename'
	))." FROM $cabinet, {$cabinet}_files where id = $fileID AND {$cabinet}_files.doc_id = $cabinet.doc_id";
	$res = $db->queryOne($query);
	dbErr($res);
	return $res;
}

function insertIntoBarcodeHistory($db_object, $ID) {
	//TOD NEED TO FIX FOR db2 and others
	$insert = "INSERT INTO barcode_history(barcode_info,username,cab,";
	$insert .= "barcode_field,date_printed,date_processed,description,";
	$insert .= "delete_barcode,split_type,compress) ";
	$insert .= "SELECT barcode_info,username,cab,barcode_field,";
	$insert .= "date_printed,NOW(),CONCAT('deleted'),description,delete_barcode, ";
	$insert .= "split_type,compress ";
	$insert .= "FROM docutron.barcode_reconciliation WHERE id=$ID";
	$res = $db_object->query($insert);
	dbErr($res);
}

function dbConcat($concatArr, $sep = '') {
	$concat = '';
	if(getdbType() == 'mysql' or getdbType() == 'mysqli') {
		if($sep) {
			$concat = 'CONCAT('.implode(",'$sep',", $concatArr).')';
		} else {
			$concat = 'CONCAT('.implode(',', $concatArr).')';
		}
	} elseif(getdbType() == 'db2' or getdbType() == 'pgsql') {
		if($sep) {
			$concat = implode("||'$sep'||", $concatArr);
		} else {
			$concat = implode('||', $concatArr);
		}
	} elseif(getdbType() == 'mssql') {
		if($sep) {
			$concat = implode("+'$sep'+", $concatArr);
		} else {
			$concat = implode('+', $concatArr);
		}
	}
	return $concat;
}

function getFilePathsFromDocID($db_object, $cabinet, $dataDir, $docID) {
	$query = <<<QUERY
		SELECT 
			CONCAT('$dataDir/', 
				CONCAT(
					REPLACE(location, ' ', '/'), 
					CONCAT('/', 
						CONCAT(
							COALESCE(
								CONCAT(subfolder, '/'),
								''
							),
							filename
						)
					)
				)
			) FROM {$cabinet}_files, $cabinet WHERE 
				$cabinet.doc_id = {$cabinet}_files.doc_id AND 
					$cabinet.doc_id = $docID AND 
						filename IS NOT NULL
QUERY;

	$res = $db_object->queryCol($query, null, 0);
	dbErr($res);
	return $res;
}

function getRequestURI () {
	if (isset($_SERVER['REQUEST_URI'])) {
		if(substr($_SERVER['REQUEST_URI'],0,2) == "//") {
			return substr($_SERVER['REQUEST_URI'],1);
		}
		return $_SERVER['REQUEST_URI'];
	} else {
		$str = $_SERVER['PHP_SELF'];
		if ($_SERVER['QUERY_STRING']) {
			$str .= '?' . $_SERVER['QUERY_STRING'];
		}
		return $str;
	}
}

function insertIntoSignixUsers(&$db_object, $table, $id, $signix_userid, $signix_password, $signix_sponsor, $signix_client) {
	$insert = "INSERT INTO $table(id, signix_userid, signix_password, signix_sponsor, signix_client) ";
	$insert .= "VALUES($id, '$signix_userid', '$signix_password', '$signix_sponsor', '$signix_client');";
	$res = $db_object->query($insert);
	dbErr($res);
}


?>
