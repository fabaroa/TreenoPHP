<?php
require_once '../lib/settings.php';
if (!empty ($DEFS['CUSTOM_LIB'])) {
	require_once $DEFS['CUSTOM_LIB'];
}else{
	//hack for indexing (why does DEFS not get SET??? );
	if(isSet($_SESSION['DEFS'])) {
		$DEFS = $_SESSION['DEFS'];
		//require_once $DEFS['CUSTOM_LIB'];
	}
}

function getODBCObject($connID, $db_raw, $dbparam = 'DSN') {
	if( $connID == 0 ){
		return 1;
	}
	global $DEFS;
	$row = getTableInfo($db_raw, 'odbc_connect',
	array(), array('id' => (int) $connID), 'queryRow');
	PEAR :: setErrorHandling(PEAR_ERROR_RETURN);
	$dsn = array ('phptype' => $row['type'], 
				  'dbsyntax' => $row['syntax'],
				  'hostspec' => $row['host'],
				  'database' => $row['dsn'],
				  'username' => $row['username'],
				  'password' => $row['password'],
				  'protocol' => 'tcp',
				  'dbparam' => $dbparam);
	$opts = array ('portability' => MDB2_PORTABILITY_ALL);
	if (isset ($DEFS['USE_ORACLE_OPTS']) and $DEFS['USE_ORACLE_OPTS'] == 1) {
		$opts['emulate_database'] = false;
	}
	$db_odbc = MDB2 :: connect($dsn, $opts);
	if(PEAR::isError($db_odbc)) {
		//dbErr($db_odbc);
		return false;
	}
	$db_odbc->loadModule ('Manager');
	$db_odbc->loadModule ('Reverse');
	$db_odbc->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $db_odbc;
}

function getODBCDbObject($connID, $db_raw, $dbparam = 'DSN') {
	if( $connID == 0 ){
		return 1;
	}
	global $DEFS;
	$row = getTableInfo($db_raw, 'odbc_connect',
	array(), array('id' => (int) $connID), 'queryRow');
	PEAR :: setErrorHandling(PEAR_ERROR_RETURN);
	$dsn = array ('phptype' => $row['type'], 
				  'dbsyntax' => $row['syntax'],
				  'hostspec' => $row['host'],
				  'database' => $row['dsn'],
				  'username' => $row['username'],
				  'password' => $row['password'],
				  'protocol' => 'tcp',
				  'dbparam' => $dbparam);
	
	
	$opts = array ('portability' => MDB2_PORTABILITY_ALL);
	if (isset ($DEFS['USE_ORACLE_OPTS']) and $DEFS['USE_ORACLE_OPTS'] == 1) {
		$opts['emulate_database'] = false;
	}
	$db_odbc = MDB2 :: connect($dsn, $opts);
	dbErr($db_odbc);
	$db_odbc->loadModule ('Manager');
	$db_odbc->loadModule ('Reverse');
	$db_odbc->setFetchMode(MDB2_FETCHMODE_ASSOC);
	return $db_odbc;
}

function checkOdbcLocation($db_object, $location, $cabinet) {
	$query = "select * from odbc_auto_complete where cabinet_name='$cabinet'";
	$query .= " and location = '$location'";
	$row = $db_object->queryRow($query);
	dbErr($row);
	if ($row)
		return true;
	return false;
}

function getODBCRow($db, $searchVal, $cabinet, $db_dept, $location='', $department, $gblStt ) 
{
	global $DEFS;
	$myPad = $gblStt->get ($cabinet . '_pad');
	if ($myPad) {
		$searchVal = sprintf ($myPad, $searchVal);
	}
	$newRow = false;
	if (function_exists ('customGetODBCRow')) {
		$newRow = customGetODBCRow ($db, $db_dept, $searchVal, $cabinet, $department, $location);
		//error_log(" $db, $db_dept, $searchVal, $cabinet, $department, $location -- \n");
	}
	elseif($department == "client_files177")//added to enforce inclusion
	{
		require_once $DEFS['CUSTOM_LIB'];//include file
		//force function.
		$newRow = customGetODBCRow ($db, $db_dept, $searchVal, $cabinet, $department, $location);		
	}
	
	if ($newRow === false) {
		$mapArr = getOdbcMapping($db_dept, $cabinet, $location );
	 	if(!$mapArr) {
	 		$mapArr = getOdbcMapping($db_dept, $cabinet, '' );
	 	}
		$newRow = getSelectStatements($mapArr, $db, $searchVal,$db_dept, $department, $cabinet );
	}
	$newRow2 = array ();
	foreach ($newRow as $myK => $myV) {
		$newRow2[$myK] = substr ($myV, 0, 254);
	}
	return $newRow2;
}

//Hack function that uses the native odbc functions to get a list of tables
//from the odbc database server
function getTableNames($dbObj) {
	$tableRes = odbtp_tables($dbObj->connection);
	while ($tableArray = odbtp_fetch_array($tableRes)) {
		$tables[] = $tableArray['Table_name'];
	}
	return $tables;
}

//Hack function that uses the native odbc functions to get a list of fields
//from the odbc database server in the table
function queryColumnNamesOdbc($dbObj, $table) {
	$colRes = odbc_columns($dbObj->connection, "", "", $table);
	while ($colArray = odbc_fetch_array($colRes)) {
		$cols[] = $colArray['Column_name'];
	}
	return $cols;
}

function testODBCConnect($myArray) {
	extract($myArray);
	PEAR :: setErrorHandling(PEAR_ERROR_RETURN);
	$dsn = "odbc://$userName:$password@$host/$dBaseName";
	$db = MDB2 :: connect($dsn);
	if (PEAR :: isError($db)) {
		print_r($db);
		return false;
	} else {
		$db->disconnect();
		return true;
	}
}

function getOdbcMapping($db_object, $cabinet, $location) {
	$query = "SELECT id FROM odbc_auto_complete WHERE cabinet_name = '$cabinet' and location = '$location'";
	$id = $db_object->queryOne($query);
	if (!$id) {
		$query = "SELECT id FROM odbc_auto_complete WHERE cabinet_name = '$cabinet' and location = ''";
		$id = $db_object->queryOne($query);
	}
	$res = $db_object->queryAll("select * from odbc_mapping where cabinet_name='$cabinet' and odbc_auto_complete_id = $id order by level ASC");
	dbErr($res);
	return $res;
}

function getSelectStatements(& $mapArr, $db_odbc, $searchValue,$db_dept, $department, $cabinet) {
	//set the first level.  each level does a select statement
	$level=$mapArr[0]['level'];
	//$level=0;
	$grouping = $mapArr[0]['grouping'];
	$firstLevel = $level;
	$odbc_trans_level = '';
	$didSearchVal = false;
	$table = $mapArr[0]['table_name'];
	//an array of arrays.  Each array holds odbc_name,where_op,logical_op,grouping,quoted
	//reset after each level
	$prevArr = array ();
	//an array of fields to select foreach select statement
	//reset after each level
	$sel = array ();
	//an array of arrays.  Each array holds odbc_name,where_op,grouping,quoted
	//reset after each level
	$constOpArr = array ();
	//as associative array the odbc_name points to the odbc_trans
	$uniqueArr = array ();
	//an array of each fieldnames selected for each level
	$newRowArr = array ();
	$prevOp = '';
	//go through all the mappings
	foreach ($mapArr as $map) {
		//these are the mappings that existin the cabinet as fields
		//fields that are not in the docutron cabinet are empty strings 
		if ($map['docutron_name']) {
			$namedMappingOdbcDocutron[$map['level']][$map['odbc_name']] = $map['docutron_name'];
		}
		//if on same level keep appending select values
		if( $map['level'] != $level ) {
			$whereStr = '';
			//use prevs because the current map is WRONG!!!the first column is a special case
			foreach($prevArr as $prevs) {
				makeWhereGroupingString($prevs,$newRowArr,$searchValue,$didSearchVal,$prevOp,$level,$firstLevel,$grouping,$whereStr,$uniqueArr,$odbc_trans_level);
			}
			foreach($constOpArr as $constOps) {
				makeWhereOpsString( $constOps, $grouping, $prevOp, $whereStr); 
			}
			if($grouping != 0 and $grouping != '') {
				$whereStr .= ')';
			}
			if($whereStr) {
				$query = "SELECT ".implode(",", $sel)." FROM $table";
				$query .= ' WHERE '.$whereStr;
				$row = $db_odbc->queryRow($query);
				if(PEAR::isError($row)) {
					$query = "SELECT ".implode(",", $sel)." FROM `$table`";
					$query .= ' WHERE '.$whereStr;
					$row = $db_odbc->queryRow($query);
				}
				if (PEAR::isError ($row)) {
					$queryArr = array (
						'username'  => 'admin',
						'datetime'  => date ('Y-m-d H:i:s'),
						'info'      => $query.", ".$row->getMessage (),
						'action'    => 'Bad ODBC Query'
					);
					$res = $db_dept->extended->autoExecute('audit', $queryArr);
					dbErr($res);
					$row = array ();
				}
				//dbErr($row);
				if ($row and is_array($row)) {
					copyOver($newRowArr, $row, $level, $department, $cabinet);
				}
			}
			$prevArr = array ();
			$constOpArr = array ();
			$sel = array ();
			$level = $map['level'];
			$table = $map['table_name'];
			$grouping = '';
		}
		$sel[$map['odbc_name']] = $map['odbc_name'];
		if ($map['where_op']) {
			if ($map['previous_value'] == 1) {
				$prevArr[] = array($map['odbc_name'], $map['where_op'],
						$map['logical_op'], $map['grouping'], $map['quoted']);
				$odbc_trans_level = $map['odbc_trans_level'];
			} else {
				$constOpArr[] = array($map['odbc_name'], $map['where_op'],
						$map['logical_op'], $map['grouping']);
			}
			$uniqueArr[strtolower($map['odbc_name'])] = strtolower($map['odbc_trans']);
		}
	}
	$whereStr = '';
	foreach($prevArr as $prevs) {
		makeWhereGroupingString($prevs,$newRowArr,$searchValue,$didSearchVal,$prevOp,$level,$firstLevel,$grouping,$whereStr,$uniqueArr,$odbc_trans_level);
	}
	foreach($constOpArr as $constOps) {
		makeWhereOpsString($constOps, $grouping, $prevOp, $whereStr );
	}
	if($grouping != 0 and $grouping != '') {
		$whereStr .= ')';
	}
	if($whereStr) {
		$query = "SELECT ".implode(",", $sel)." FROM $table";
		$query .= ' WHERE '.$whereStr;
		$row = $db_odbc->queryRow($query);
		if(PEAR::isError($row)) {
			$query = "SELECT ".implode(",", $sel)." FROM `$table`";
			$query .= ' WHERE '.$whereStr;
			$row = $db_odbc->queryRow($query);
		}
		if (PEAR::isError ($row)) {
			$queryArr = array (
					'username'	=> 'admin',
					'datetime'	=> date ('Y-m-d H:i:s'),
					'info'		=> $query.", ".$row->getMessage (),
					'action'	=> 'Bad ODBC Query'
					);
			$res = $db_dept->extended->autoExecute('audit', $queryArr);
			dbErr($res);
			$row = array ();
		}
		if ($row and is_array($row)) {
			copyOver($newRowArr, $row, $level, $department, $cabinet);
		}
	}
	//return values from array
	return remap($newRowArr, $namedMappingOdbcDocutron);
}

//copy over just the mapped values to a new array
function copyOver(& $newRowArr, & $row, $level, $department, $cabinet) {
	foreach ($row as $key => $value) {
		if (!isset ($newRowArr[$level][$key]) or $newRowArr[$level][$key] == '') {
			if(is_object($value)) {
				//$value = $value->year."-".$value->month."-".$value->day;
				$value = date("Y-m-d", mktime(0,0,0,$value->month,$value->day,$value->year));
			}
			if (function_exists ('customODBC')) {
				customODBC ($department, $cabinet, $key, $value, $newRowArr[$level]);
			} else {
				$newRowArr[$level][strtolower($key)] = $value;
			}

		}
	}
}

function remap(& $newRowArr, & $namedMappings ) {
	$newMap = array ();
	foreach( $newRowArr as $level => $newRow ) {
		if( is_array($namedMappings[$level]))
		foreach ($namedMappings[$level] as $key => $value) {
			//check for aliased expressions in the db
			$pos = strpos( $key, ' as ' );
			if( $pos ===false )
			{
				$newMap[$value] = trim($newRow[strtolower($key)]);
			}else{
			$value = explode( ' ', $value );
			$value = $value[sizeof($value)-1];
			$newMap[$value] = trim($newRow[$value]);
			}
		}
	}
	return $newMap;
}

function makeWhereGroupingString($prevs,$newRowArr,$searchValue,&$didSearchVal,&$prevOp,$level,$firstLevel,&$grouping,&$whereStr,$uniqueArr,$odbc_trans_level)
{
//print_r($prevs);
	list($odbcName, $whereOp, $logicalOp, $myGrouping, $isQuoted) = $prevs;
	//$odbcName = strtolower($odbcName);
	if($myGrouping != $grouping) {
		if($grouping != 0) {
			$whereStr .= ')';
		}
		$whereStr .= ' ';
		if($prevOp) {
			$whereStr .= $prevOp.' ';
		}
		$whereStr .= '(';
		$grouping = $myGrouping;
	} elseif($prevOp) {
		$whereStr .= ' '.$prevOp.' ';
	}

	$tmpVal = "";
	if($firstLevel == $level and !$didSearchVal) {
		$tmpVal = $searchValue;
		$didSearchVal = true;
	} else {
		if($newRowArr[$odbc_trans_level]) {
			$tmpVal = $newRowArr[$odbc_trans_level][$uniqueArr[strtolower($odbcName)]];
		}
	}
	if($tmpVal != '') {
		if($isQuoted) {
			$tmpVal = "'$tmpVal'";
		}

		$pos = strpos( $odbcName, ' as ' );
        if($pos !==false) {
			$odbcName = substr($odbcName,0,$pos);
        }
		$whereStr .= $odbcName.' '.$whereOp.' '.$tmpVal;
	}
	$prevOp = $logicalOp;
}

function makeWhereOpsString( $constOps, &$grouping, &$prevOp, &$whereStr) {
list($odbcName, $myOp, $logicalOp, $myGrouping) = $constOps;
	if($myGrouping != $grouping) {
		if($grouping != 0) {
			$whereStr .= ')';
		}
		$whereStr .= ' ';
		if($prevOp) {
			$whereStr .= $prevOp.' ';
		}
		$whereStr .= '(';
		$grouping = $myGrouping;
	} elseif($prevOp) {
		$whereStr .= ' '.$prevOp.' ';
	}
	$whereStr .= $odbcName.' '.$myOp;
	$prevOp = $logicalOp;
}
?>
