<?php
// $Id: treenoServicesFuncs.php 15019 2013-07-11 20:31:27Z fabaroa $
/*
 * These are the current list of functions that are needed to support treenoServices.php
 * The functions are grouped by the file in which they came from. Here is this list of
 * files: 
 * 		lib/treenoServicesFuncs.php (unique to this file)
 * 		documents/documents.php
 * 		lib/webServices.php
 * 		lib/mime.php
 * 		movefiles/moveFiles2.php
 * 		lib/PDF.php
 * 		
 */

//---------- lib/treenoServicesFuncs.php (unique to this file) -------------


/*
 * - PRIVATE
 */
function do_offset($level){
    $offset = "";             // offset for subarry 
    for ($i=1; $i<$level;$i++){
    $offset = $offset . "<td></td>";
    }
    
    return $offset;

}	// end do_offset()

/*
 * - PRIVATE
 */
function show_array($array, $level, $sub){
    if (is_array($array) == 1){          // check if input is an array
       foreach($array as $key_val => $value) {
           $offset = "";
           if (is_array($value) == 1){   // array is multidimensional
           echo "<tr>";
           $offset = do_offset($level);
           echo $offset . "<td>" . $key_val . "</td>";
           show_array($value, $level+1, 1);
           }
           else{                        // (sub)array is not multidim
           if ($sub != 1){          // first entry for subarray
               echo "<tr nosub>";
               $offset = do_offset($level);
           }
           $sub = 0;
           echo $offset . "<td main ".$sub." width=\"120\">" . $key_val . 
               "</td><td width=\"120\">" . $value . "</td>"; 
           echo "</tr>\n";
           }
       } //foreach $array
    }  
    else{ // argument $array is not an array
        return;
    }
    
}	// end show_array()


/*
 * - PRIVATE
 */
function html_show_array($array){
	echo "<table cellspacing=\"0\" border=\"2\">\n";
	show_array($array, 1, 0);
	echo "</table>\n";
	
}	// end html_show_array()

/*
 * - PRIVATE
 * 		pulled from webServices.php
 */
function getFilesFromCabinet($db, $cab, $doc_id, $subfolder) {
	if($subfolder == null)
		$subfolder = "IS NULL";

	$res = getTableInfo($db, $cab.'_files', array('filename'), 
						array('doc_id' => $doc_id, 'filename' => 'IS NOT NULL', 
							  'subfolder' => $subfolder), 
						'queryCol');

	if(PEAR::isError($res)) {
		die('Error connection to cabinet files table inside getFilesFromCabinet');
	}
	return $res;
}


//---------- documents/documents.php -------------

/*
 * ...check that the User's groups has permission to use this document type
 * - PRIVATE
 */
function getDocumentPermissions($documentID, $db_dept) {
	$sArr = array('permissions_id');
	$wArr = array("id" => (int)$documentID);
	$permID = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryOne');

	$sArr = array('group_list_id');
	$wArr = array('permission_id' => (int)$permID);
	$groupID = getTableInfo($db_dept,'document_permissions',$sArr,$wArr,'queryOne');

	$sArr = array('real_groupname');
	$wArr = array();
	$realGroupList = getTableInfo($db_dept,'groups',$sArr,$wArr,'queryCol');

	$sArr = array('groupname');
	$wArr = array('list_id' => (int)$groupID);
	$groupList = getTableInfo($db_dept,'group_list',$sArr,$wArr,'queryCol');
	if(!$groupList) {
		$groupList = array();
	} else {
		$fixedGroupList=array();
		foreach($groupList as $groupName) {
			if (in_array($groupName,$realGroupList)) $fixedGroupList[]=$groupName;
		}
		$groupList = $fixedGroupList;
	}

	return $groupList;
	
}	// end getDocumentPermissions()


//---------- lib/webServices.php -------------

/*
 * - PRIVATE
 * 		pulled from webServices.php
 */
function getFolderLocation($db_dept, $cabinet, $doc_id)
{
	return getTableInfo($db_dept, $cabinet, array('location'), array('doc_id' => $doc_id), 'queryOne');
}

/*
 * - PRIVATE
 */
function hasAccess($db_dept, $userName, $cabinetID = 0, $needsRWAccess = true, $needsAccess = true) {
	if($needsRWAccess) {
		$cabArr = cabinetList($userName, $db_dept, 0);
	} else {
		$cabArr = cabinetList($userName, $db_dept, 1);
	}
	if(!$cabinetID and $cabArr) {
		return true;
	} elseif (!$cabinetID) {
		return false;
	}

	//$cabInfo = getCabinets($db_dept, '', $cabinetID);
	$cabInfo = getTableInfo($db_dept, 'departments', array(), array('departmentid' => (int)$cabinetID));
	if($row = $cabInfo->fetchRow()) {
		if($needsAccess and in_array($row['real_name'], $cabArr)) {
			return $row['real_name'];
		} elseif(!$needsAccess or $userName == 'admin') {
			return $row['real_name'];
		} else {
			return false;
		}
	} else {
		return false;
	}
	
}	// end hasAccess()

/*
 * - PRIVATE
 * 		if the file name is not unique, add a '-#' between filename and extension
 */
function getSafeFilename($db_dept, $cab, $docID, $destTab, $filename)
{
	// list of all files in this folders tab/document
	$fileArr = getFilesFromCabinet($db_dept, $cab, $docID, $destTab);
    
	// init
	$st      = 1;
	$nameArr = explode(".", $filename);
	$name    = $nameArr[sizeof($nameArr)-2];
	$ext     = $nameArr[sizeof($nameArr)-1];
    
	// loop through the files looking for a match
	while(is_array($fileArr) && in_array_case_insensitive($filename, $fileArr)) {
		$filename = $name."-".$st.".".$ext;
		//error_log("getSafeFilename() renamed filename: ".$filename);
 		$st++;
	}
	
	return $filename;
    
}	// end getSafeFilename()


function in_array_case_insensitive($needle, $haystack) 
{ 
	return in_array( strtolower($needle), array_map('strtolower', $haystack) );
}

/*
 * - PRIVATE
 * 		pulled from webServices.php
 */
function createTabForDocument($db_dept, $department, $cabName, $docID, $docType, 
							  &$name, $db_raw, $mkdir=true) {
 	global $DEFS;
	
	$whereArr = array(  'doc_id'    => (int)$docID);
	$loc = getTableInfo($db_dept, $cabName, array('location'), $whereArr, 'queryOne');
	if (!$loc) {
		return false;
	}

	$user = new user();

	$docType = str_replace(' ', '_', $docType);
	$docType = $user->replaceInvalidCharacters($docType, "");
	$docType = str_replace("@", "", $docType);

    $whereArr = array(  'doc_id'    => (int)$docID,
       	                'filename'  => 'IS NULL' );
    $tabArr   = getTableInfo($db_dept, $cabName.'_files',
    						 array('subfolder'), 
    						 $whereArr, 
    						 'queryCol');
    $i = 1;
	$name       = $docType.$i;
    $tabLoc     = $DEFS['DATA_DIR']."/".str_replace(" ", "/", $loc);
	$tempTabLoc = $tabLoc."/".$name;
	$i = mt_rand( 10000000,99999999 );
	// if tabArr is empty then just use the name we have
	if(is_array($tabArr)) {
	    while(in_array($docType.$i, $tabArr) OR file_exists($tempTabLoc)) {
			$i          = mt_rand( 10000000,99999999 );
	    	$name       = $docType.$i;
	    	$tempTabLoc = $tabLoc."/".$name;
	    }
    }
	$tabLoc = $tempTabLoc;
    $insertArr = array( 'doc_id'		=> (int)$docID,
                        'subfolder'		=> $name,
                        'date_created'	=> date('Y-m-d G:i:s'),
                        'file_size'		=> 4096 );
    $res = $db_dept->extended->autoExecute($cabName.'_files', $insertArr);
	dbErr($res);
    $whereArr    = array('doc_id' => (int)$docID,
                         'subfolder' => $name );
    
    $subfolderID = getTableInfo($db_dept, $cabName.'_files', 
    							array('MAX(id)'), 
    							$whereArr, 
    							'queryOne');
	if($mkdir) {
	    mkdir($tabLoc, 0777);
	}
    $updateArr = array('quota_used'=>'quota_used+4096');
    $whereArr  = array('real_department'=> $department);
	updateTableInfo($db_raw,'licenses', $updateArr, $whereArr,1);

	return $subfolderID;
    
}	// end createTabForDocument()


/*
 * - PRIVATE
 * 		pulled from webServices.php
 */
function putContents($filename, $data)
{
    $f = fopen($filename, 'w+');
    fwrite($f, $data);
    fclose($f);
}


/*
 * - PRIVATE
 * 		pulled from webServices.php
 */
function getGUICabList($db_dept, $cabList) {
	//$res = getCabinets($db_dept);
	$res = getTableInfo($db_dept, 'departments', array(), array('deleted' => 0), 'query', array( 'departmentname'=>'ASC'));
	$myCabs = array ();
	while($row = $res->fetchRow()) {
		if(in_array($row['real_name'], $cabList)) {
			$myCabNames = array();
            $myCabNames['departmentname'] = $row['departmentname'];
            $myCabNames['real_name'] = $row['real_name'];
            $myCabs[(int) $row['departmentid']] = $myCabNames;
		}
	}
	return $myCabs;
}


//---------- lib/mime.php -------------

/*
 * - PRIVATE
 * 		returns a files extension
 */
function getExtension($str) {
	$pos = strrchr($str, '.');
	if ($pos !== false) {
		$ext = substr($pos, 1);
	} else {
		$ext = '';
	}
	return $ext;
	
}	// end getExtension()


//---------- lib/mime.php -------------

/*
 * - PRIVATE
 * 		pulled from moveFiles2.php
 */
function getOrderType($department, $cabinet, $doc_id, $tab, $username, 
					  $prepend, $db_doc, $db_object) {
	//create function
	$settings = new GblStt($department, $db_doc);
	$ordering = $settings->get("indexing_ordering_$cabinet");

	if($ordering == "") {
		$usrStt   = new Usrsettings($username, $department, $db_doc);
		$ordering = $usrStt->get('indexing_ordering');
		if($ordering == "")
			$ordering = 0; // Default to prepend if none
	}
//PROBLEM with ordering setting not coming back, so hardcoded...ALS:
		$orderType = "MAX(ordering)+1";
//if( $ordering == 1 ) {
//		$orderType = "MAX(ordering)+1";
//	} else {
//		$orderType = "MIN(ordering)-$prepend";
//	}

	$whereArr = array('doc_id'=>(int)$doc_id);
	if( $tab ) {
		$whereArr['subfolder'] = $tab;
    } else {
		$whereArr['subfolder'] = 'IS NULL';
    }
    
	return (getTableInfo($db_object, $cabinet."_files", array($orderType), $whereArr, 'queryOne'));
}




?>