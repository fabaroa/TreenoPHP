<?php
// $Id: cabinets.php 15057 2013-11-26 21:21:04Z cz $
include_once '../lib/random.php';
include_once '../lib/tabFuncs.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/settings.php';
include_once '../db/db_common.php';
include_once '../settings/settings.php';
include_once '../classuser.inc';
include_once '../modules/modules.php';
include_once '../lib/odbc.php';
// This function takes a user, datadir and cabinet and returns a
// new unique folder in the cabinet without slashes (like stored
// in the database)
function makeFolderInCabinet($db_name, $datadir, $cabinet)
{
	// get the path to new folder and create it
	$location = $db_name."/$cabinet";
	$location = getUniqueDirectory($datadir."/".$location);
	if(!$location) {
		$i = 1;
		while(!$location) {
			if(!file_exists($datadir.'/'.$db_name.'/'.$cabinet.'__'.$i)) {
				if(!mkdir($datadir.'/'.$db_name.'/'.$cabinet.'__'.$i)) {
					return false;
				}
			}
			$location = getUniqueDirectory($datadir.'/'.$db_name.'/'.$cabinet.'__'.$i);
			$i++;
		}
	}
	
	// knock off datadir and replace slashes
	$location = str_replace("$datadir/", "", $location);
	$location = str_replace("/", " ", $location);
	$location = trim($location);

	return $location;
}

//function to display list of accessable cabinets for a user -- 
//pass an argument other than "1" as a 3rd argument in order to 
//return only those with read-write access
function cabinetList( $uname, $db_object , $permission=1)
{
  //will split the access value into an array of the cabinet name 
  //separated by a comma and then its rights
	$cabinetInfo = getTableInfo($db_object, 'departments', array(), array('deleted' => 0));
	$cabArr = array();
	while( $result = $cabinetInfo->fetchRow() ) {
		$cabArr[] = $result['real_name'];
	} 
	$list = array();
	if($cabArr)
	{
		if($uname == 'admin') {
			return $cabArr;
		}
		$rightsInfo = getTableInfo($db_object,'access',array(),array('username'=>$uname));
		$access = $rightsInfo->fetchRow();
		$accessRights = unserialize(base64_decode($access['access']));

		foreach($accessRights as $cabinet => $rights) {
			if( $permission == 1 ) {
				if( ( $rights == "ro" || $rights == "rw") && in_array( trim($cabinet), $cabArr ) ) {
					$list[] = $cabinet;
				}
			} else {
				if( $rights == "rw" && in_array( $cabinet, $cabArr ) ) {
					$list[] = $cabinet;
				}
			}
		}
	}

  
	//if( is_array( $list ) )
 	//	usort( $list, "strnatcasecmp" ); 

	//add group permissions to the list
	$groupCabs = array();
	$groupAccessList = queryAllGroupAccess($db_object, $uname);
	foreach( $groupAccessList AS $groupInfo ) {
		$cabinet = $groupInfo['real_name'];
		$rights = $groupInfo['access'];
		if( $rights == 'rw' ) {
			$groupCabs[] = $cabinet;
		} elseif( $rights == 'ro' AND $permission == 1 ) {
			$groupCabs[] = $cabinet;
		}
	}
	$list = array_merge($list, $groupCabs);
	$list = array_unique($list);

	return( $list );
}

function createDropDown( $list, $user )
{ 
  global $trans;
  $optionValue = $trans['Choose Cabinet'];

	// "Choose a Cabinet" must be the first option in the list!
  echo "      <option selected value=\"\">$optionValue</option>\n"; 
  for($i=0;$i<sizeof( $list );$i++)
  {
    $tmp = str_replace("_"," ",$list[$i]);
    echo"      <option value=\"$list[$i]\">".$tmp."</option>\n"; 
  }
}
function checkNumbers($characters,$cab)
{
  $flag = 0;
  $length = strlen($cab);
  for($i=0;$i<$length;$i++)
  {
    $status = strrpos($characters,$cab{$i});
    if($status === false)
      $flag++;
  }
  if($flag == $length)
    return true;
  else
    return false;
}
	
function createFolderInCabinet( 
			$db_object, 
			$gblStt,
			$db_doc,
			$userName, 
			$db_name, 
			$cab, 
			&$indiceArr, 
			$fieldnames, 
			&$tempTable, 
			$needInsertAC = false )
{
	global $DEFS;
	$location = makeFolderInCabinet($db_name, $DEFS['DATA_DIR'], $cab);

	// edit databases to show new folder
	lockTables($db_doc, array('licenses'));
	$updateArr = array('quota_used'=>'quota_used+4096');
	$whereArr = array('real_department'=> $db_name);
	updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
	unlockTables($db_doc);
	$queryArr = array ('location' => $location);
	for($i=0;$i<sizeof($indiceArr);$i++) {
		$tmpValue = $indiceArr[$i];
		if(strpos($tmpValue, '"') === 0) {
			// peel off quotes around string
			$newValue = substr($tmpValue, 1, strlen($tmpValue) - 2);
			$indiceArr[$i] = $newValue;
		}
		if(strcmp($fieldnames[$i], "location") == 0) {
			$queryArr = array ('location' => $location);
		} else {
			//$queryArr[$fieldnames[$i]] = (string)$indiceArr[$i];
			mb_substitute_character('entity');
			$queryArr[$fieldnames[$i]] =  mb_convert_encoding($indiceArr[$i], 'ASCII', 'UTF-8');
		}
	}

	// modify the database
	lockTables($db_object, array($cab));
	$res = $db_object->extended->autoExecute($cab, $queryArr);
	dbErr($res);
	$doc_id = getTableInfo($db_object,$cab,array('MAX(doc_id)'),array(),'queryOne');
	
	if(!empty($DEFS['CUSTOM_LIB'])) {
		require_once $DEFS['CUSTOM_LIB'];

		if(function_exists('customCreateFolder')) {
			$uArr = customCreateFolder($db_object,$db_name,$cab,$doc_id,$queryArr);
			if(!empty($uArr)) {
				$wArr = array('doc_id' => $doc_id);
				updateTableInfo($db_object,$cab,$uArr,$wArr);
			}
		}
	}
	unlockTables($db_object);

	$info = "( ".addSlashes( implode( ", ", $indiceArr ) )." ) in Cabinet: $cab";
 	$insertArr = array ('username' => $userName, 'datetime' => 
				date('Y-m-d G:i:s'), 'info' => $info, 'action' => 'folder added');
 	$res = $db_object->extended->autoExecute ('audit', $insertArr);
 	dbErr($res);
	
	/* ---START NEW AUDIT CODE 7/13/2016 MC----
		id|username		    |datetime	                |info	                                        |action
		14|associatedadmin	|2015-11-09 12:56:26.773	|Add new folder (doc_id=1) to cabinet 'Batches'	|Add folder
		*/
		$info = "Add new folder (doc_id=".$doc_id.") to cabinet '".$cab."'";
		$insertArr = array ('username' => $userName, 'datetime' => 
					date('Y-m-d G:i:s'), 'info' => $info, 'action' => 'Add folder');
		$res = $db_object->extended->autoExecute ('audit', $insertArr);
		dbErr($res);
	// ---END NEW AUDIT CODE----
	
 	addTabsToFolder($cab, $gblStt, $db_doc, $doc_id, $db_object, $db_name);

	if( $tempTable ) {
		$tableArr = $db_object->manager->listTables ();
		if(getDbType() == "db2") {
			$tmp_table = strtoupper($tempTable);
		} else {
			$tmp_table = $tempTable;
		}
		if(!in_array($tmp_table,$tableArr)) {
			$search = new search();	
			$tempTable = $search->getSearch($cab, $_SESSION['searchResArray'], $db_object );
		}

		$sArr = array('COUNT(result_id)');
		$wArr = array('result_id' => (int)$doc_id);
		$ct = getTableInfo($db_object,$tempTable,$sArr,$wArr,'queryOne');
		if($ct == 0) {
			$insertArr = array(	"result_id" => (int)$doc_id );
			$res = $db_object->extended->autoExecute($tempTable,$insertArr);
			dbErr($res);
		}

	}
	if($needInsertAC) {
		$acTable = $gblStt->get('indexing_'.$cab);
		if($acTable != 'odbc_auto_complete' and $acTable != 'sagitta_ws_auto_complete') {
			$acInfo = getTableColumnInfo ($db_object, $acTable);
			for($i = 0; $i < count($indiceArr); $i++) {
				if(in_array($fieldnames[$i], $acInfo)) {
					$queryArr[$fieldnames[$i]] = $indiceArr[$i];
				}
			}
			$res = $db_object->extended->autoExecute($acTable, $queryArr);
			dbErr($res);
		}
	}
return $doc_id;
}

function getThumbPath($db_object, $cabinet, $fileID, $db_name, &$fileLoc)
{
	global $DEFS;
	$whereArr = array(
		"id"			=> (int)$fileID,
		"display"		=> 1,
		"deleted"		=> 0
			 );
	  
	$fileInfo = getTableInfo($db_object,$cabinet."_files",array(),$whereArr);
	$row = $fileInfo->fetchRow();
	$filename = $row['filename'];
	$docID = $row['doc_id'];
	$tab = $row['subfolder'];
	$whereArr = array('doc_id'=>(int)$docID);
	$folderInfo = getTableInfo($db_object,$cabinet,array(),$whereArr);
	$row = $folderInfo->fetchRow ();
	$fileLoc = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $row['location']).'/';
	if($tab) {
		$fileLoc .= $tab.'/';
	}
	$fileLoc .= $filename;
	$thumbLoc = str_replace($DEFS['DATA_DIR'].'/'.$db_name.'/'.$cabinet, $DEFS['DATA_DIR'].'/'.$db_name.'/thumbs/'.$cabinet, $fileLoc).'.jpeg';

	if (!is_dir(dirname($thumbLoc))) {
		makeAllDir(dirname($thumbLoc));
	}
	return $thumbLoc;
}

/* Test if the folder indices already exist in the database according
 *	to the settings of compareCols
 * Returns the docID if it exists
 */
function checkFolderExists($department, $cabinet, $folderIndices, $db_doc, $db_dept) {
	global $DEFS;
	$gblStt = new GblStt ($department,$db_doc);
	$whereArr = array('deleted' => 0);
	$checkCols = $gblStt->get( 'compareCols' );
	$checkCols = explode(",", $checkCols);
	$counter = 0;
	foreach($folderIndices AS $index => $value) {
		if( (strtolower($index) != 'date_indexed') AND (in_array($counter, $checkCols) OR in_array("-1", $checkCols)) ) {
			if($value == "") {
				$whereArr[$index] = "IS NULL";
			} else {
				$whereArr[$index] = $value;
			}
		}
		$counter++;
	}

	$docID = getTableInfo($db_dept, $cabinet, array('doc_id'), $whereArr, 'queryOne');
	return $docID;
}

function searchSpecialAutoComplete($user, $cabinet, $db_object, $db_doc, $whereArr) {
	$gblStt = new GblStt ($user->db_name, $db_doc);
	$acTable = $gblStt->get ('indexing_'.$cabinet);
	$row = array ();
	if ($acTable == 'odbc_auto_complete') {
		$transInfo = getTableInfo($db_object, 'odbc_auto_complete', array(),
			array('cabinet_name' => $cabinet), 'queryRow');
		$myLookup = $transInfo['lookup_field'];
		if (substr_count ($myLookup, ',') > 0) {
			$myLookups = explode (',', $myLookup);
			$searchVal = array ();
			foreach ($myLookups as $myField) {
				if (isset ($whereArr[$myField])) {
					$searchVal[$myField] = $whereArr[$myField];
				} else {
					$searchVal[$myField] = '';
				}
			}
		} else {
			$searchVal = current($whereArr);
		}
		$db_odbc = getOdbcObject($transInfo['connect_id'], $db_doc);
		if ($db_odbc !== false) {
			$row = getODBCRow($db_odbc,$searchVal,$cabinet,$db_object,'inbox', $user->db_name,$gblStt);
		}
	} elseif ($acTable == 'sagitta_ws_auto_complete') {
		$searchVal = current($whereArr);
		$row = getSagRow ($cabinet, $searchVal, $user->db_name);
	} elseif ($acTable) {
		$res = getTableInfo($db_object, $acTable, array(), $whereArr);
		$row = $res->fetchRow();
	}
	if(!$row) {
		$row = $whereArr;
	}
	return $row;
}

function searchAndCreateFolder($user, $cabinet, $db_object, $db_doc, $whereArr) {
	$gblStt = new GblStt ($user->db_name, $db_doc);
	$temp_table = '';
	$created = false;
	if(check_enable('searchResODBC', $user->db_name) && sizeof($whereArr)) {
		$row = searchSpecialAutoComplete($user, $cabinet, $db_object, $db_doc, $whereArr);
		if($row) {
			$doc_id = createFolderInCabinet($db_object,$gblStt,$db_doc,$user->username,$user->db_name,$cabinet,array_values($row),array_keys($row),$temp_table);
			$created = true;
		}
	}
	if(!$created) {
		$doc_id = createFolderInCabinet($db_object,$gblStt,$db_doc,$user->username,$user->db_name,$cabinet,array_values($whereArr),array_keys($whereArr),$temp_table);
	}
	return $doc_id;
}

function createFullCabinet($db_object, $db_doc, $department, $newRealCab, $newArbCab, $indices,$user = NULL) {
	global $DEFS;
	//eliminate spaces before or after cabinet names..
	$newRealCab = trim($newRealCab);
	$newArbCab = trim($newArbCab);

	if(!mkdir($DEFS['DATA_DIR'].'/'.$department.'/'.$newRealCab)) {
		error_log('Faild to create folder: '.$DEFS['DATA_DIR'].'/'.$department.'/'.$newRealCab);
		return false;		
	}
	allowWebWrite($DEFS['DATA_DIR'].'/'.$department.'/'.$newRealCab, $DEFS);
	
	$dirIndexing = $DEFS['DATA_DIR'].'/'.$department.'/indexing';
	//cz mkdir failed to recursively create directory on Windows
	if(!file_exists($dirIndexing))
	{
		error_log('Create folder: '.$dirIndexing);
		if(!mkdir($dirIndexing)) {
	   	error_log('Faild to create folder: '.$dirIndexing);
			return false;
		}
	}
	
	if(!mkdir($dirIndexing.'/'.$newRealCab)) {
	   error_log('Faild to create folder: '.$dirIndexing.'/'.$newRealCab);
		return false;
	}
	
	allowWebWrite($DEFS['DATA_DIR'].'/'.$department.'/indexing/'.$newRealCab, $DEFS);

	$insertArr = array (
			'real_name'         => $newRealCab,
			'departmentname'    => $newArbCab,
			);
	$res = $db_object->extended->autoExecute('departments', $insertArr);
	dbErr($res);

	$sArr = array('departmentid');
	$wArr = array('real_name' => $newRealCab);
	$cabID = getTableInfo($db_object,'departments',$sArr,$wArr,'queryOne');
	
	//get rid of trailing or leading spaces to cabinet indexes.
	array_walk($indices, 'trim');
	
	createCabinet($db_object,$newRealCab,$indices);
	createCabinet_files($db_object,$newRealCab,$cabID);
	createCabinet_Files_Sprocs($db_doc, $department, $newRealCab, $cabID);
	createCabinet_Index_Files($db_object,$newRealCab);
	$updateArr = array('quota_used'=>'quota_used+8192');
	$whereArr = array('real_department'=> $department);
	updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
	$res = getTableInfo($db_object, 'departments',array(),array(),'query',array('departmentname'=>'ASC'));
	$cabArr = array ();
	while ($row = $res->fetchRow()) {
		$cabArr[$row['real_name']] = $row['departmentname'];
	}

	uasort($cabArr,'strnatcasecmp');
	$userlist = getTableInfo($db_object,'access');
	$retBool = false;
	while ($row = $userlist->fetchRow()) {
		$sortedAccess = array();
		$access = unserialize(base64_decode($row['access']));
		$username = $row['username'];
		if($user && $user->isUserDepAdmin($username)) {
			$access[$newRealCab] = 'rw';
		} else if($username == "admin") {
			$access[$newRealCab] = 'rw';
		} else {
			$access[$newRealCab] = 'none';
		}

		foreach( $cabArr as $cab => $arb ) {
			$sortedAccess[$cab] = $access[$cab];
		}
		$retBool = true;
		updateTableInfo($db_object, 'access',
			array('access' => base64_encode(serialize($sortedAccess))),
			array('username' => $username));
	}
	return $retBool;
}
?>
