<?php
// $Id: webServices.php 15058 2013-12-05 19:25:42Z cz $
include_once '../lib/utility.php';
include_once '../groups/groups.php';
include_once '../classuser.inc';
include_once '../lib/versioning.php';
include_once '../workflow/outlookNode.inc.php';
include_once '../workflow/customWorkflowNode.php';
include_once '../lib/random.php';
include_once '../lib/searchLib.php';
include_once '../lib/fileFuncs.php';
#include_once '../lib/tabFuncs.php';
include_once '../search/search.php';
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../movefiles/moveFiles2.php';
include_once '../lib/cabinets.php';
include_once '../departments/depfuncs.php';
include_once '../DataObjects/DataObject.inc.php';
include_once '../documents/documents.php';
include_once '../lib/odbc.php';
include_once '../lib/xmlObj.php';
include_once '../webservices2/getBarcode.php';
include_once '../lib/documentObj.inc.php';
include_once '../lib/folderObj.inc.php';
include_once '../secure/tabChecks.php';
include_once '../lib/tables.php';
include_once '../canada/readRepo.php';
include_once '../search/fileSearch.php';
include_once '../publishing/publishUser.php';
include_once '../publishing/publishSearch.php';
include_once 'Cache/Lite.php';
/*SOAP Functions */
function getUniqueFolderFileCount($department,$cab) {
    $db_dept = getDbObject($department);
    $cabResArr = array();
    $fieldNames = getCabinetInfo($db_dept,$cab);
    $sArr = array($fieldNames[0],'doc_id');
    $wArr = array('deleted' => 0);
    $gArr = array($fieldNames[0],'doc_id');
    $res = getTableInfo($db_dept,$cab,$sArr,$wArr,'getAssoc',array(),0,0,$gArr,true);
    foreach($res AS $k => $v) {
        $sArr = array('count(id)');
        $wArr = array('doc_id IN('.implode(",",$v).')');
        $ct = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'queryOne');
        $cabResArr[$k] = $ct;
    }
    return $cabResArr;
}

function getTabFileListByID2($db_dept, $cabinetID, $docID, $userName, $tabID = 0) {
        $cabName = hasAccess($db_dept, $userName, $cabinetID, false);
        if($cabName !== false) {
                if($tabID === 0 || $tabID == 'main') {
                        $tab = "IS NULL";
                } else {
                        $tab = getTableInfo($db_dept, $cabName.'_files',
                                array('subfolder'), array('id' => $tabID), 'queryOne');
                }
                $res = getTableInfo($db_dept, $cabName.'_files',
                        array('id', 'filename', 'file_size', 'ordering'),
                        array(  'display' => (int)1,
                                'deleted' => (int)0,
                                'doc_id' => (int)$docID,
                                'filename' => 'IS NOT NULL',
                                'subfolder' => $tab )
                );

                $tabFileList = array();
                while($row = $res->fetchRow()) {
                        $tabFileList[] = $row;
                }
                return $tabFileList;
        } else {
                return false;
        }
}
function getTabFileListByID($db_dept, $cabinetID, $docID, $userName, $tabID = 0) {
        $cabName = hasAccess($db_dept, $userName, $cabinetID, false);
        if($cabName !== false) {
                if($tabID === 0 || $tabID == 'main') {
                        $tab = "IS NULL";
                } else {
                        $tab = getTableInfo($db_dept, $cabName.'_files',
                                array('subfolder'), array('id' => $tabID), 'queryOne');
                }
                $res = getTableInfo($db_dept, $cabName.'_files',
                        array('id', 'filename', 'file_size'),
                        array(  'display' => (int)1,
                                'deleted' => (int)0,
                                'doc_id' => (int)$docID,
                                'filename' => 'IS NOT NULL',
                                'subfolder' => $tab )
                );

                $tabFileList = array();
                while($row = $res->fetchRow()) {
                        $tabFileList[] = $row;
                }
                return $tabFileList;
        } else {
                return false;
        }
}


function searchAndReplace($department,$cabinetID,$searchArr,$replaceArr,$username) {
	$user = new user();
	$user->db_name = $department;
	$user->username = $username;

	$db_dept = getDbObject($department);
	$cabinetName = hasAccess($db_dept, $username, $cabinetID, true);
	if(!$cabinetName) {
//		$db_dept->disconnect();
		$user->audit("ILLEGAL ACCESS", "user does not have permissions to $cabinetName");
		return false;
	}

	$sArr = array('doc_id');
	$searchRes = getTableInfo($db_dept,$cabinetName,$sArr,$searchArr,'queryCol');	
	if(!count($searchRes)) {
		$auditArr = array();
		foreach($searchArr AS $key => $value) {
			$auditArr[] = $key."=".$value; 
		}
		$user->audit("Cabinet Search", "searching $cabinetName with { ".implode(",",$auditArr)." } ");
		return 2;
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("USERNAME",$username);
	$xmlObj->createKeyAndValue("DEPARTMENT",$department);
	$xmlObj->createKeyAndValue("CABINET",$cabinetName,array('cabinetID' => $cabinetID));
	foreach($searchArr AS $key => $value) {
		$parentEl = $xmlObj->createKeyAndValue("SEARCH");
		$xmlObj->createKeyAndValue("KEY",$key,array(),$parentEl);
		$xmlObj->createKeyAndValue("VALUE",$value,array(),$parentEl);
	}

	foreach($replaceArr AS $key=> $value) {
		$parentEl = $xmlObj->createKeyAndValue("REPLACE");
		$xmlObj->createKeyAndValue("KEY",$key,array(),$parentEl);
		$xmlObj->createKeyAndValue("VALUE",$value,array(),$parentEl);
	}
	foreach($searchRes AS $doc_id) {
		$wArr = array('doc_id' => (int)$doc_id);
		updateTableInfo($db_dept,$cabinetName,$replaceArr,$wArr);
		$xmlObj->createKeyAndValue("DOC_ID",$doc_id);
	}
	$user->audit("Search and Replace", $xmlObj->createDOMString());
	return 1;
}

function searchAudit($department,$searchTerms, $dateTimeArr) {
	$db_dept = getDbObject($department);
	$oArr = array('id' > 'ASC');
	$wArr = array();
	foreach($searchTerms AS $field => $value) {
		$wArr[] = $field." " . LIKE . " '%$value%'";
	}
	foreach($dateTimeArr AS $dateTime) {
		//for security reasons to prevent additional queries
		$tempDateTime = strtolower($dateTime);
		if( strpos($tempDateTime, "and") === false AND strpos($tempDateTime, "or") === false ) {
			$wArr[] = "datetime $dateTime";
		}
	}
	$result = getTableInfo($db_dept,'audit',array(),$wArr,'queryAll');

	return $result;
}

function GetTabList($db_dept, $cabinetID, $docID, $userName) {
	$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
	if(!$cabinetName) {
		$db_dept->disconnect();
		return false;
	}
	$res = getCabinetTabInfo($db_dept, $cabinetName, $docID);
	#$noTabs = getNoShowTabs($cabinetName, $docID, $department);
	$arr = array(array ('id' => 0, 'subfolder' => 'main'));
	while($row = $res->fetchRow()) {
		$arr[] = $row;
	}
	return $arr;
}

function GetTabFileList($db_dept, $cabinetID, $docID, $userName, $tab = '') {
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName !== false) {
		if($tab == '' || $tab == 'main')
			$tab = "IS NULL";

		$tabFileList = getTableInfo($db_dept, $cabName.'_files', 
			array('id', 'filename', 'parent_filename','file_size'), 
			array(	'display' => (int)1, 
				'deleted' => (int)0, 
				'doc_id' => (int)$docID, 
				'filename' => 'IS NOT NULL', 
				'subfolder' => $tab ),
			'queryAll',
			array('ordering' => 'ASC')
		);
		return $tabFileList;
	} else {
		return false;
	}
}

/*
 *	This function selects all todo items from wf_todo table for a specific user
 *	Foreach item returned from the wf_todo table, 
 *		a check is performed in that department for 
 *		that specific workflow id to see if the current 
 *		node is an outlookNode 
 *	The function returns a list of wf_todo ids for all workdflows 
 *		that are on the outlookNode for that specific user		
 */
function getUserWFIDs($db,$username,$nodeName) {
	$todoIDArr = array();
    $sArr = array('id', 'department', 'wf_document_id');
    $wArr = array('username'=>$username);
	$oArr = array('department' => 'ASC', 'id' => 'ASC');
    
	$todoInfo = getTableInfo($db,'wf_todo',$sArr,$wArr,'getAssoc',$oArr);
	if(PEAR::isError($todoInfo)) {
		die('Error connecting to the wf_todo table inside getUserWFIDs');
	}

	$db_dept = '';
	$curWFDep = '';
	foreach($todoInfo AS $id => $data) {
		$wfDep = $data['department'];
		if($curWFDep != $wfDep) {
			if( is_resource($db_dept) ) {
				$db_dept->disconnect();
			}
			$db_dept = getDbObject($wfDep);
			$curWFDep = $wfDep;
		}
		$whereArr = array(	"wf_documents.id = ".(int)$data['wf_document_id'],
				"state_wf_def_id = wf_defs.id",
				"wf_defs.node_id = wf_nodes.id",
		);
		if ($nodeName) {
			$whereArr[] = "node_type = '$nodeName'";
		}
		$ct = getTableInfo($db_dept, 
			array('wf_documents', 'wf_defs', 'wf_nodes'), 
			array('COUNT(*)'),
			$whereArr,
			'queryOne'
		);

		if(PEAR::isError($ct)) {
			die('Error connecting to the wf_documents,wf_defs,wf_nodes table inside getUserWFIDs');
		}
		if($ct) {
			$todoIDArr[] = $id;	
		}
	}
	//$db_dept->disconnect();
	return($todoIDArr);
}

function getUserWorkflowTodoList ($db_doc, $userName, &$arbList, &$allArbCabs,
		&$allIndexNames) {
	$bigTodoArr = array ();
	$todoList =  getTableInfo($db_doc,'wf_todo',array(),array('username' =>
				$userName),'queryAll',array('department'=>'ASC','id'=>'ASC'));
	$arbList = getTableInfo($db_doc, 'licenses',
		array('real_department', 'arb_department'), array(), 'getAssoc', 
		array('arb_department' => 'ASC'));
	$wfDep = '';
	$curWFDep = '';
	$curWFCab = '';
	$allIndexNames = array ();
	$allArbCabs = array ();
	foreach ($todoList as $todoArr) {
		$wfDep = $todoArr['department'];
		if($wfDep != $curWFDep) {
			$clientDB = getDbObject ($wfDep);
			$bigTodoArr[$wfDep] = array ();
			$arbCabArr = getTableInfo($clientDB,'departments',array
					('real_name', 'departmentname'), array(), 'getAssoc');
			$allArbCabs[$wfDep] = $arbCabArr;
		}
		$wfInfo = getWFLink ($clientDB, $todoArr['wf_document_id']);
		$wfCab = $wfInfo['cab'];
		if($wfInfo) {
			if( $wfCab != $curWFCab || $wfDep != $curWFDep) { 
				//$arbCab = $arbCabArr[$wfCab];
				if (!isset ($bigTodoArr[$wfDep][$wfCab])) {
					$bigTodoArr[$wfDep][$wfCab] = array ();
				}
				$indiceNames = getCabinetInfo( $clientDB, $wfCab );	
				$allIndexNames[$wfDep][$wfCab] = $indiceNames;
/*
				$indexArr = array();
				foreach($indiceNames AS $name) {
					$indexArr[] = "max($wfCab.$name) as $name";
				}
				$wfFolderArr = array();
				$query = "SELECT max($wfCab.doc_id) as doc_id,".implode(',', $indexArr)." FROM wf_documents,$wfCab ";
				$query .= "WHERE cab='$wfCab' AND wf_documents.doc_id=$wfCab.doc_id GROUP BY wf_documents.doc_id";
				$wfFolderArr = $clientDB->getAssoc($query, true);
*/
			}
			
			$wArr = array('doc_id' => (int)$wfInfo['doc_id']);
			$folderArr = getTableInfo($clientDB,$wfCab,$indiceNames,$wArr,'queryRow');
			
			$link = getLinkExt($wfDep, $wfCab, $wfInfo['doc_id'], $todoArr['id']);
			$date_notified = $wfInfo['date_time'];

			$tmpArr = array (
						"todoID"		=> $todoArr['id'],
						"notified"		=> $date_notified,
						"cabinet"		=> $arbCabArr[$wfCab],
						"department"	=> $arbList[$todoArr['department']],
						'folder'		=> $folderArr,
						"nodeName"		=> str_replace( "_", " ", $wfInfo['node_name'] ),
						"nodeType"		=> $wfInfo['node_type'],
						"link"			=> $link,
					);
			
			$bigTodoArr[$wfDep][$wfCab][] = $tmpArr;
			$curWFCab = $wfCab;
			$curWFDep = $wfDep;
		}
		//$clientDB->disconnect();

	}
	return $bigTodoArr;
	
}

/*
 *	This function finds the department and wf_document_id of a specific wf_todo id
 *	With department and wf_document_id, 
 *		the cab and doc_id of the wf_document_id from the department is retrieved 
 *	With the cab and doc_id, all the files located in the folder are retrieved
 *	This function returns a list of file ids for the 
 *		specific workflow for a specific user
 */
function getTodoId($db, $wf_todo_id) {
	$res = getTableInfo($db, 'wf_todo', array('department', 'wf_document_id'), array('id' => (int)$wf_todo_id), 'queryRow');
	if(PEAR::isError($res)) {
		die('Error connecting to the wf_todo table inside getTodoId');
	}
	return $res;
}

function updateTodoItem($dep, $wf_todo, $priority, $notes, $dateDue) {
	$db_doc = getDbObject('docutron');
	$uArr = array('priority' => $priority,
				'notes' => $notes,
				'date' => $dateDue);
	$wArr = array('department' => $dep,
			'wf_document_id' => $wf_todo);
	updateTableInfo($db_doc,'wf_todo',$uArr,$wArr);
}

/* With department and wf_document_id,
 *      the cab and doc_id of the wf_document_id from the department is retrieved
 */
function getWf_doc($db_dept, $wf_doc_id) {
	$res = getTableInfo($db_dept, 
		array('wf_documents', 'departments'),
		array('departmentid', 'cab', 'doc_id','file_id'),
		array(	"wf_documents.cab = departments.real_name",
				"id = ".(int)$wf_doc_id
		),
		'queryRow'
	);

	if(PEAR::isError($res)) {
		die('Error connecting to the wf_documents table inside getWf_doc');
	}
	return $res;
}
//this function returns the details of the specific node, including the next node
//the previous node, and the id
function getWorkflowNode($db, $db_dept, $wf_todo_id)
{
	$defID = getTableInfo($db, array('wf_todo'), array('wf_def_id'), 
					array('id' => (int)$wf_todo_id), 'queryOne');
	if(PEAR::isError($defID)) {
		die('Error connecting to the wf_todo table inside getWorkflowNodeID');
	}
	$node = getTableInfo($db_dept, array('wf_defs'), array(), 
					array('id' => (int)$defID), 'queryRow');
	if(PEAR::isError($node)) {
		die('Error connecting to the wf_defs table inside getWorkflowNodeID');
	}	
	return $node;
	
}

/* With the cab and doc_id, all the files located in the folder are retrieved
 *  This function returns a list of file ids for the
 *      specific workflow for a specific user
 */
function getFolderInfo($db_dept, $cab, $doc_id) {
	$idArr = getTableInfo($db_dept, $cab.'_files', 
		array('id', 'filename', 'subfolder'),
		array(	'doc_id' => (int)$doc_id,
				'filename' => 'IS NOT NULL'
		)
	);

	if(PEAR::isError($idArr)) {
		die('Error connecting to the cabinet table inside getFolderInfo');
	}
	return $idArr;
}

function getCabinetTabInfo( $db_dept, $cab, $doc_id ) {
	$res = getTableInfo($db_dept, $cab.'_files', array('id', 'subfolder'), array('doc_id' => (int)$doc_id, 'filename' => 'IS NULL', 'subfolder' => 'IS NOT NULL'),'query',array('subfolder'=>'ASC'));
	return $res;
}

//Returns doc_id and filename query results for given file_id 
function getFileQuery($db_dept, $cab, $fileId) {
	$res = getTableInfo($db_dept, $cab.'_files', array('doc_id', 'filename', 'subfolder'), array('id' => (int)$fileId), 'queryRow');
	if(PEAR::isError($res)) {
		die('Error connecting to cabinet files table inside getFileQuery');
	}
	return $res;
}

/*
 *	This function finds the department and wf_document_id of a specific wf_todo id
 *	With department and wf_document_id, 
 *		the cab,doc_id,file_id,and state_wf_def_id corresponding 
 *		to the wf_document_id from the department is retrieved 
 *	With all of this information, an outlookNode is created and 
 *		this node calls its accept function pointing the workflow to the next node
 *	This function returns a boolean true or false, 
 *		whether or not the accept function exceeds or fails
 */
function finishWorkflow($db,$wf_todo_id, $myUser) {
	$res = getTableInfo($db, 'wf_todo', array(), array("id = ".(int)$wf_todo_id), 'queryRow');
	if(PEAR::isError($res)) {
		return false;
	}
	$dept = $res['department'];
	$username = $res['username'];
	$wf_doc_id = $res['wf_document_id'];
	
	if($username !== $myUser) {
		return false;
	}
	$db_dept = getDbObject($dept);
	$res = getTableInfo($db_dept, 'wf_documents', 
		array('cab', 'doc_id', 'state_wf_def_id', 'file_id'), 
		array("id = ".(int)$wf_doc_id), 'queryRow');
	if(PEAR::isError($res)) {
		return false;
	}
	$cab = $res['cab'];
	$doc_id = $res['doc_id'];
	$file_id = $res['file_id'];
	$state_wf_def_id = $res['state_wf_def_id'];
	$cabDispName = getTableInfo($db_dept, 'departments', array('departmentname'),
		array('real_name' => $cab), 'queryOne');

	$nodeObj = new outlookNode($db_dept,$dept,$username,$wf_doc_id,$state_wf_def_id,$cab,$cabDispName,$doc_id,$db,$file_id);
	$nodeObj->accept();
	$db_dept->disconnect();
	return true;
}

function getFolderLocation($db_dept, $cabinet, $doc_id)
{
	return getTableInfo($db_dept, $cabinet, array('location'), array('doc_id' => $doc_id), 'queryOne');
}

function getSafeFilename($db_dept, $cab, $docID, $destTab, $filename)
{
	$st = 1;
    $fileArr = getFilesFromCabinet($db_dept,$cab,$docID,$destTab);
    while(is_array($fileArr) && in_array($filename,$fileArr)) {
        $nameArr = explode(".",$filename);
        $ext = $nameArr[sizeof($nameArr)-1];
        unset($nameArr[sizeof($nameArr)-1]);
        $name = implode(".",$nameArr);
        $filename = $name."-".$st.".".$ext;
        $st++;
    }

    return $filename;
}

function putContents($filename, $data)
{
    $f = fopen($filename, 'w+');
    fwrite($f, $data);
    fclose($f);
}

function uploadFileToFolder($username, $department, $cabinetID, $docID, $tabID, $filename, $encodedFile, $db_doc, $db_dept)
{
    global $DEFS;
    $cab = hasAccess($db_dept,$username,$cabinetID,true,false);
    if($cab !== false) {
		$destTab = null;
        if($tabID != 0) {
			$destTab = getTableInfo($db_dept, $cab.'_files', array('subfolder'), 
				array('id' => (int)$tabID), 'queryOne');
        }
        $ordering = getOrderType($department,$cab,$docID,$destTab,$username,1, $db_doc, $db_dept);
        if($ordering == NULL) {
            $ordering = 1;
        }

		$result = getTableInfo($db_dept, $cab, array(), array('doc_id' => $docID));
        if(PEAR::isError($result)) {
            return false;
        }

        $filename = getSafeFilename($db_dept, $cab, $docID, $destTab, $filename);
        $location = str_replace(" ","/",getFolderLocation($db_dept,$cab,$docID));
        $location = $DEFS['DATA_DIR']."/".$location."/".$destTab."/".$filename;
        //Puts the file on disk
        putContents($location, $encodedFile);

        //Values for placing query into db
        $res['filename'] = $filename;
        $res['doc_id'] = $docID;
		if($destTab) {
			$res['subfolder'] = $destTab;
		}
        $res['ordering'] = $ordering;
        $res['date_created'] = date('Y-m-d G:i:s');
        $res['who_indexed'] = $username;
        $res['parent_filename'] = $filename;
        $res['file_size'] = filesize($location);

        $result = $db_dept->extended->autoExecute($cab."_files",$res);
        if(PEAR::isError($result)) {
            return false;
        }

        //audit
//      $auditMessage = "$filename created through webservices to Doc ID: $docID"; //need to change later
//      auditMoveFile($username, $department, $auditMessage);
    }
    $fileID = 0;
	$fileID = getTableInfo($db_dept, $cab.'_files', array('MAX(id)'), 
		array('filename' => $filename, 'doc_id' => $docID), 'queryOne');
    return $fileID;
}


/*
 *	
 */
function copyDocuments($username, $curInfo, $destInfo, $db_doc, $db_dept, $dest_db_dept) {
	global $DEFS;
	$cab = hasAccess($db_dept,$username,$curInfo['cabinetID'],true,false);
	if($cab !== false) {
		$destCab = hasAccess($dest_db_dept,$username,$destInfo['cabinetID']);
		if($destCab !== false) {
			$doc_id = $curInfo['docID'];
			$destDoc_id = $destInfo['docID'];
			if($destInfo['tabID'] != 0) {
				$destTab = getTableInfo($dest_db_dept, $destCab.'_files', array('subfolder'), array('id' => $destInfo['tabID']), 'queryOne');
			} else {
				$destTab="";
			}
			//need to get current location
			$cur_location = str_replace(" ","/",getFolderLocation($db_dept,$cab,$doc_id));
			$dest_location = str_replace(" ","/",getFolderLocation($dest_db_dept,$destCab,$destDoc_id));
			//need to get destination location

			//check to see if any files exist in folder for ordering
			//if there are create an array to hold all files
			$fileArr = getFilesFromCabinet($dest_db_dept,$destCab,$destDoc_id,$destTab);
			$res = getTableInfo($db_dept, $cab.'_files', array(), array('id' => $curInfo['fileID']), 'queryRow');
			if(PEAR::isError($res)) {
				return false;	
			}
			unset($res['id']);
			unset($res['parent_id']);
			unset($res['timestamp']);
			$res['v_major']=1;
			$res['v_minor']=0;
			$res['doc_id'] = $destDoc_id;
		
			$st = 1;
			$orig_tab = $res['subfolder'];
			if($destTab) {
				$res['subfolder'] = $destTab;
			} else {
				unset($res['subfolder']);
			}
			$orig_parentname = $res['parent_filename'];
			if ($res['redaction']=='Default')
			{
				unset($res['redaction']);
				$rnameArr = explode(".",$res['filename']);
				$ext = $rnameArr[sizeof($rnameArr)-1];
				$orig_name = $res['filename'].'.ann.'.$ext;
			} else {
				$orig_name = $res['filename'];
			}
//			$orig_name = $res['filename'];
			$full_name = $res['filename']; 
			while(is_array($fileArr) && in_array($full_name,$fileArr)) {
				$nameArr = explode(".",$full_name);
				$ext = $nameArr[sizeof($nameArr)-1];
				unset($nameArr[sizeof($nameArr)-1]);
				$name = implode(".",$nameArr);
				$oldstVal = explode("-", $name);
				$endVal = $oldstVal[count($oldstVal)-1];
				if( (count($oldstVal) > 1) AND (is_numeric($endVal)) ) {
					$name = substr($name, 0, strrpos($name, "-"));
				}
				$full_name = $name."-".$st.".".$ext; 
				$st++;
			}
			$fileArr[] = $full_name;
			$res['filename'] = $full_name;
			$res['parent_filename'] = $full_name;
			$res['ordering'] = getOrderType($destInfo['department'],$destCab,$destDoc_id,$destTab,$username,1, $db_doc, $db_dept);
			if($res['ordering'] == NULL) {
				$res['ordering'] = 1;
			}
			$res['date_created'] = date('Y-m-d G:i:s'); 
			$res['who_indexed'] = $username;
// is this where the file record gets updated? if so need to set new redaction here.
			if ($res['redaction_id']) {
				// need to add the redaction record to the new cabinet
				$xml = getTableInfo($db_dept, 'redactions', array('xml_data'), array('id' => $res['redaction_id']), 'queryOne');			    
				$xml = str_replace("<fileName>".$orig_parentname."</fileName>", "<fileName>".$res['filename']."</fileName>", $xml);
				$xml = str_replace("<cabinet>".$cab."</cabinet>", "<cabinet>".$destCab."</cabinet>", $xml);
				$xml = str_replace("<docID>".$doc_id."</docID>", "<docID>".$destDoc_id."</docID>", $xml);
				$xml = str_replace("<calledDocID>".$doc_id."</calledDocID>", "<calledDocID>".$destDoc_id."</calledDocID>", $xml);
				$xml = str_replace("<subfolder>".$orig_tab."</subfolder>", "<subfolder>".$destTab."</subfolder>", $xml);
				$newredactionrecord=array();
				$newredactionrecord['xml_data']=$xml;
				$newredactionrecord['cabinet']=$destCab;
				$newredactionrecord['doc_id']=$destDoc_id;
				$newredactionrecord['subfolder']=$destTab;
				$newredactionrecord['filename']=$res['parent_filename'];
				$redactionResult = $db_dept->extended->autoExecute("redactions",$newredactionrecord);
				$res['redaction_id'] = getTableInfo($db_dept, 'redactions', array('id'), array('cabinet' => $destCab,'doc_id' => $destDoc_id,'subfolder' => $destTab,'filename' => $res['parent_filename']), 'queryOne');			    
			}
			$result = $db_dept->extended->autoExecute($destCab."_files",$res);
			if(PEAR::isError($result)) {
				return false;
			}
			
			if(!copy(	$DEFS['DATA_DIR']."/".$cur_location."/".$orig_tab."/".$orig_name,
						$DEFS['DATA_DIR']."/".$dest_location."/".$destTab."/".$res['filename']) ) {
				return false;
			} else {
				if (file_exists($DEFS['DATA_DIR']."/".$cur_location."/".$orig_tab."/".$orig_name.".adminRedacted")) {
					copy(	$DEFS['DATA_DIR']."/".$cur_location."/".$orig_tab."/".$orig_name.".adminRedacted",
											$DEFS['DATA_DIR']."/".$dest_location."/".$destTab."/".$res['filename'].".adminRedacted");	
				} 
				$curFolder = getCabIndexArr($doc_id,$cab,$db_dept);
				$destFolder = getCabIndexArr ($destDoc_id, $destCab,$dest_db_dept);
				$auditMsg1 = "Copied: ".$res['filename']." From: cabinet=$cab folder=".implode(" : ", $curFolder);
				$auditMsg2 = "Copied: ".$res['filename']." To: cabinet=$destCab folder=".implode(" : ", $destFolder);
				$tmpUsr = new user();
				$tmpUsr->username = $username;
				$tmpUsr->db_name = $curInfo['department'];
				$tmpUsr->audit('copied file',$auditMsg1);
				$tmpUsr->db_name = $destInfo['department'];
				$tmpUsr->audit('copied file',$auditMsg2);
			}
			return true;
		} else { 
			return false;
		}
	} else { 
		return false;
	}

}

function getFilesFromCabinet($db,$cab,$doc_id,$subfolder) {
	if($subfolder == null)
		$subfolder = "IS NULL";

	$res = getTableInfo($db, $cab.'_files', array('filename'), 
		array('doc_id' => $doc_id, 'filename' => 'IS NOT NULL', 'subfolder' => $subfolder), 
		'queryCol');

	if(PEAR::isError($res)) {
		die('Error connection to cabinet files table inside getFilesFromCabinet');
	}
	return $res;
}

function getCabinetIndiceNames($db,$cab) {
	$indArr = getTableColumnInfo ($db, $cab);
	if(PEAR::isError($indArr)) {
		die('Error using tableInfo on a cabinet inside getCabinetIndiceNames');
	}
	
	$cabinetIndices = array();
	foreach($indArr AS $indice) {
		$cabinetIndices[] = $indice;
	}
	$db->disconnect();
	return $cabinetIndices;
}

function createCabinetFolder($dept, $cabinetID, $indiceArr, $userName, $db_doc, $db_dept) {
	$gblStt = new GblStt($dept, $db_doc);
	
	$existing = $gblStt->get( 'file_into_existing' );
	error_log("createCabinetFolder() existing: ".$existing);
	if( !$existing )
		$existing = 0;
		
	$cabArr = cabinetList($userName, $db_dept, 1);
	//$cabInfo = getCabinets($db_dept, '', $cabinetID);
	$cabInfo = getTableInfo($db_dept, 'departments', array(), array('departmentid' => (int)$cabinetID));
	if($row = $cabInfo->fetchRow() and in_array($row['real_name'], $cabArr)) {
		$cabRealName = $row['real_name'];
	}
	
	if( $existing > 0){				
		$docID = checkFolderExists($dept, $cabRealName, $indiceArr, $db_doc, $db_dept);
		error_log("createCabinetFolderAfterChcecking() checkFolderExists() docID: ".$docID);
		if($docID > 0){
			return -2;
		}		
	}

	if($cabRealName) {
		$temp_table = '';
		$docID = (int) createFolderInCabinet($db_dept, $gblStt, $db_doc, $userName, $dept, $cabRealName, array_values($indiceArr), array_keys($indiceArr), $temp_table);
	} else {
		$docID = -1;
	}
	return $docID;
}

function getOcrFiles( $numfiles ){ 
	$db_doc = getDbObject ('docutron');
	$oArr = array('id' => 'DESC');
	$wArr = array();
	lockTables( $db_doc, array('ocr_queue') );
	$qList = getTableInfo($db_doc,'ocr_queue',array(),$wArr,'queryAll',$oArr,$numfiles);
	foreach( $qList as $row ){
		$del = 'delete from ocr_queue where id = '.$row['id'];
		$db_doc->query( $del );
	}
	unlockTables( $db_doc );
	$tabFileList = array ();
	$cache = getCacheLiteObject();
	$fileIdList = array();
	foreach($qList as $row) {
		if( !$cabid = $cache->get($row['department'].$row['cabinet'].'_ID') ){
			$db_dept = getDbObject( $row['department'] );
			$wArr = array('real_name'=>$row['cabinet']);
			$sArr = array( 'departmentid');
			//get cabid
			$cabid = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryOne');
			//set cabid
			$cache->save( $cabid,$row['department'].$row['cabinet'].'_ID');
			//for use when setting the ocr_text
			$cache->save( $row['cabinet'],$row['department'].$cabid.'_ID');
			$db_dept->disconnect();
		}
		$fileIdList[]=new fileIdInfo(basename($row['location']),(int)$row['file_id'],$row['department'],$cabid); 
	}
	$db_doc->disconnect();
	return $fileIdList;
}
function getAttachFiles( $numfiles ){ 
	$db_doc = getDbObject ('docutron');
	$oArr = array('id' => 'DESC');
	$wArr = array();
	lockTables( $db_doc, array('attach_queue') );
	$qList = getTableInfo($db_doc,'attach_queue',array(),$wArr,'queryAll',$oArr,$numfiles);
	foreach( $qList as $row ){
		$del = 'delete from attach_queue where id = '.$row['id'];
		$db_doc->query( $del );
	}
	unlockTables( $db_doc );
	$tabFileList = array ();
	$cache = getCacheLiteObject();
	$fileIdList = array();
	foreach($qList as $row) {
		if( !$cabid = $cache->get($row['department'].$row['cabinet'].'_ID') ){
			$db_dept = getDbObject( $row['department'] );
			$wArr = array('real_name'=>$row['cabinet']);
			$sArr = array( 'departmentid');
			//get cabid
			$cabid = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryOne');
			//set cabid
			$cache->save( $cabid,$row['department'].$row['cabinet'].'_ID');
			//for use when setting the ocr_text
			$cache->save( $row['cabinet'],$row['department'].$cabid.'_ID');
		}
		$fileIdList[]=new fileIdInfo(basename($row['location']),(int)$row['file_id'],$row['department'],$cabid); 
	}
	return $fileIdList;
}
function updateOcrContext( $db_dept, $username, $cabid, $fileid, $ocrcontext ){
	$cabName=hasAccess( $db_dept, $username, $cabid, true );
	$whereArr=array( 'id'=>$fileid );
	$indicesArr = array( 'ocr_context'=>$ocrcontext );
	if( $cabName !== false ){
		$res = updateTableInfo( $db_dept, $cabName.'_files', $indicesArr, $whereArr );
		dbErr($res);
		return true;
	}
	return false;
}
function updateAttachmentEntry( $db_dept, $username, $cabid, $fileid, $attachment, $attachmentNames ){
	$cabName=hasAccess( $db_dept, $username, $cabid, true );
	//get document type and see if it has an attachment indices
	//start by getting document Type table name if any from files record. also het document_id of document type
	$res = getTableInfo($db_dept, $cabName.'_files', array('doc_id','subfolder'), array('id' => (int)$fileid), 'queryRow');
	if (count($res)==0) {
		return $fileid." not found in ".$cabName."_files";
	}
	$subfolder=$res['subfolder'];
	$doc_id=$res['doc_id'];
	$res = getTableInfo($db_dept, $cabName.'_files', array('document_id','document_table_name'), array('doc_id' => (int)$doc_id,'subfolder' => $subfolder,'filename' => 'IS NULL'), 'queryRow');
	if (count($res)==0 || $res['document_table_name']=="" || $res['document_table_name']==NULL) {
		return $subfolder." has no document type";
	}
	$document_table_name=$res['document_table_name'];
	$document_id=$res['document_id'];
	//next get document type id number used in document_field_value_list. generally the number found in the document type table name
	$res = getTableInfo($db_dept, 'document_type_defs', array('id'), array('document_table_name' => $document_table_name), 'queryRow');
	if (count($res)==0) {
		return $document_table_name." not found";
	}
	$id=$res['id'];
	//need to see if attachment is a field name. if so get the "Real" field name	
	$res = getTableInfo($db_dept, 'document_field_defs_list', array('id','real_field_name','arb_field_name'), array('document_table_name' => $document_table_name,'arb_field_name'=>'Attachment'), 'queryRow');
	if (count($res)==0) {
		return "no attachment field associated with this document type";
	}
	//with the information collect we can now update the field in the document value table
	$document_field_defs_list_id = $res['id'];
	$whereArr=array( 'document_field_defs_list_id' => $document_field_defs_list_id,'document_id' => $document_id,'document_defs_list_id' => $id );
	$indicesArr = array( 'document_field_value');
	$res = getTableInfo( $db_dept, 'document_field_value_list', $indicesArr, $whereArr, 'queryRow');
	if ($attachment==1) {
		$attachment = "Yes";
	} else {
		$attachment = "No";
	}
	$indicesArr = array( 'document_field_value' => $attachment );
	if( $cabName !== false ){
		if (count($res)==0){
			$query="insert into document_field_value_list (document_field_defs_list_id,document_id,document_defs_list_id,document_field_value) value('".$document_field_defs_list_id."','".$document_id."','".$id."','".$attachment."')";
			$res = $db_dept->query($query);
		} else {
			$res = updateTableInfo( $db_dept, 'document_field_value_list', $indicesArr, $whereArr );
		}		
		dbErr($res);
		//*************Do all this again to find the field "Attachment Names"
		//need to see if attachment is a field name. if so get the "Real" field name	
		$res = getTableInfo($db_dept, 'document_field_defs_list', array('id','real_field_name','arb_field_name'), array('document_table_name' => $document_table_name,'arb_field_name'=>'Attachment Names'), 'queryRow');
		if (count($res)==0) {
			return "no attachment field associated with this document type";
		}
		//with the information collect we can now update the field in the document value table
		$document_field_defs_list_id = $res['id'];
		$whereArr=array( 'document_field_defs_list_id' => $document_field_defs_list_id,'document_id' => $document_id,'document_defs_list_id' => $id );
		$indicesArr = array( 'document_field_value');
		$res = getTableInfo( $db_dept, 'document_field_value_list', $indicesArr, $whereArr, 'queryRow');
		if (count($res)==0){
			$query="insert into document_field_value_list (document_field_defs_list_id,document_id,document_defs_list_id,document_field_value) value('".$document_field_defs_list_id."','".$document_id."','".$id."','".$attachmentNames."')";
			$res = $db_dept->query($query);
		} else {
			$indicesArr = array( 'document_field_value'=>$attachmentNames );
			$res = updateTableInfo( $db_dept, 'document_field_value_list', $indicesArr, $whereArr );
		}		

		dbErr($res);
		return "attachment set";
	}
	return "no access to $cabName";
}
function updateCabinetFolder($db_dept, $cabinetID, $docID, $indicesArr, $userName) {
	$cabName = hasAccess($db_dept, $userName, $cabinetID, true);
	if($cabName !== false) {
		$whereArr = array('doc_id' => $docID);
		$res = updateTableInfo($db_dept, $cabName, $indicesArr, $whereArr);
		dbErr($res);

		$date = date('Y-m-d H:i:s');
		$info = "";
		$keys = array_keys($indicesArr);
		$values = array_values($indicesArr);
		for( $i = 0; $i < count($keys); $i++) {
			$info .= $keys[$i]."=".$values[$i].", ";
		}
		$info .= " in $cabName docID: $docID";
		$action = "Webservices Edited Folder";
		auditWebServices($userName, $date, $info, $action, NULL, $db_dept);
		return true;
	} else {
		return false;
	}
}

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
} 

function testCabinetAccess($userName, $department, $cabinet, $cabList) {
	if( in_array($cabinet, $cabList) )
		return true;
	return false;
}

function getCabinetList($userName, $department) {
	$db_dept = getDbObject ($department);
	$cabList = cabinetList($userName, $db_dept, 0);
	$cabList = getGUICabList($db_dept, $cabList);
	return $cabList;
}
	
function getROCabinetList($userName, $department) {
	$db_dept = getDbObject ($department);
	$cabList = cabinetList($userName, $db_dept, 1);
	$RWcabList = cabinetList($userName, $db_dept, 0);
	$cabList = array_diff($cabList, $RWcabList);
	$cabList = getGUICabList($db_dept, $cabList);
	return $cabList;
}

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

function getDepartmentList($db_doc, $userName) {
	$query = "SELECT l.real_department, l.arb_department FROM dbo.users u JOIN dbo.db_list dl ON u.db_list_id = dl.list_id ";
	$query .= " JOIN dbo.licenses l on dl.[db_name] = l.real_department Where u.username ='".$userName;
	$query .= "' ORDER BY dl.default_dept desc, l.arb_department asc";
	$depList = $db_doc->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, false, true);
	//error_log("getDepartmentList() depList: ".print_r($depList,true));
	$retArr = array();
	foreach($depList as $dept => $arb) {
		$retArr[$dept] = $arb[0];		
	}
	//error_log("getDepartmentList() retArr: ".print_r($retArr,true));
	return $retArr;
}

function searchTopLevel($db_dept, $searchStr, $userName) {
	$cabArr = cabinetList($userName, $db_dept, 0);
	//$cabsInfo = getCabinets($db_dept);
	$cabsInfo = getTableInfo($db_dept, 'departments');
	$cabAssoc = array ();
	while($row = $cabsInfo->fetchRow()) {
		$cabAssoc[$row['real_name']] = (int)$row['departmentid'];
	}
	$terms = splitOnQuote($db_dept, $searchStr, true);
	$tlsArr = array ();
	$ctArr = array ();

	foreach($cabArr as $myCab) {
		$tempTable = searchTable($db_dept, $myCab, false, $terms);
		$count = getTableInfo($db_dept, $tempTable, array('COUNT(*)'), array(), 'queryOne');
		$tlsArr[$cabAssoc[$myCab]] = $tempTable;
		$ctArr[$cabAssoc[$myCab]] = (int)$count;
	}
	return array($tlsArr, $ctArr);
}

function searchCabinet($department, $cabinetID, $searchArr, $userName) { 
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName) {
		$search = new search();
		$tempTable = $search->getSearch($cabName, $searchArr, $db_dept);

		$count = getTableInfo($db_dept, $tempTable, array('COUNT(*)'), array(), 'queryOne');
		$db_dept->disconnect();
		return array($tempTable, (int) $count);	
	} else {
		$db_dept->disconnect();
		return array(false, false);
	}
}

function getResultSet($department, $cabinetID, $resultID, $startIndex, $numberToFetch, $userName) {
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName) {
		$result = getListfromDualTable(
			$db_dept,
			$cabName,
			$resultID,
			$startIndex,
			$numberToFetch
		);
		$cabIndices = getCabinetInfo($db_dept, $cabName);
		$retArr = array();
		while($row = $result->fetchRow()) {
			$newRow = array ();
			foreach($cabIndices as $index) {
				if (!isset($row[$index]))
				{
//					error_log($department."|Index:".$index."* in ".$cabName."\n".print_r($row,true));
				}
				else
				{
					$newRow[$index] = $row[$index];
				}
			}
			$retArr[$row['doc_id']] = $newRow;
		}
		$db_dept->disconnect();
		return $retArr;
	} else {
		$db_dept->disconnect();
		return false;
	}
}

function getDocumentList($department, $cabinetID, $docID, $userName) {
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName) {
		$sArr = array('document_table_name','document_id','subfolder','id');
		$query = "SELECT ".implode(",",$sArr)." FROM ".$cabName."_files ";
		$query .= "WHERE doc_id = $docID AND document_id != 0 AND deleted = 0 ";
		$query .= "ORDER BY document_table_name ASC , document_id DESC";
		$docList = $db_dept->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, false, true);

		$sArr = array('document_table_name','document_type_name');
		$wArr = array('enable' => 1);
		$oArr = array('document_type_name' => 'ASC');
		$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr);
		$retArr = array();
		foreach($docArr AS $k => $d) {
			if(array_key_exists($k,$docList)) {
				foreach($docList[$k] AS $doc) {
					$docInfo = array();
					$tableArr = array('document_field_defs_list','document_field_value_list');
					$sArr = array('document_field_value');
					$whereArr = array(	'document_field_defs_list_id=document_field_defs_list.id',
										'document_id='.(int)$doc['document_id'],
										"document_table_name='$k'" );
					$docInfo = getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'queryCol');
					$retArr[$doc['id']] = implode(" ",$docInfo);
				}
			}
		}
		$db_dept->disconnect();
		return $retArr;
	} else {
		$db_dept->disconnect();
		return false;
	}
}

function getDetailedDocumentList($department, $cabinetID, $docID, $userName) {
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName) {
		$sArr = array('document_table_name','document_id','subfolder','id');
		$query = "SELECT ".implode(",",$sArr)." FROM ".$cabName."_files ";
		$query .= "WHERE doc_id = " . $docID . " AND document_id <> 0 AND deleted = 0 ";
		$query .= "ORDER BY document_table_name ASC , document_id DESC";
		$docList = $db_dept->extended->getAssoc($query, null, array (), null, MDB2_FETCHMODE_DEFAULT, false, true);
		dbErr($docList);
		$sArr = array('document_table_name','document_type_name');
		$wArr = array('enable' => 1);
		$oArr = array('document_type_name' => 'ASC');
		$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr);
		$retArr = array();
		foreach($docArr AS $k => $d) {
			if(array_key_exists($k,$docList)) {
				foreach($docList[$k] AS $doc) {
					$docInfo = getDetailedDocumentIndexList ($db_dept, $doc['document_id'], $k);
					//This is to fix a bug in editing document types
					if (!$docInfo) {
						$realIDs = getTableInfo ($db_dept, array ('document_field_defs_list'),
							array ('id'), array ('document_table_name' => $k), 'queryCol');
						$documentTypeID = getTableInfo ($db_dept, array ('document_type_defs'), 
							array ('id'), array ('document_table_name' => $k), 'queryOne');
						foreach ($realIDs as $indexID) {
							$queryArr = array (
								'document_defs_list_id'		=> (int) $documentTypeID,
								'document_id'			=> (int) $doc['document_id'],
								'document_field_defs_list_id'	=> (int) $indexID,
								'document_field_value'		=> ''
							);
							$res = $db_dept->extended->autoExecute ('document_field_value_list', $queryArr);
							dbErr($res);
						}
						$docInfo = getDetailedDocumentIndexList ($db_dept, $doc['document_id'], $k);
					}
					$indArr = array ();
					foreach ($docInfo as $indInfo) {
						$indArr[] = array (
							'indexName' => $indInfo['real_field_name'],
							'displayName' => $indInfo['arb_field_name'],
							'value' => $indInfo['document_field_value'],
						);
					}
					$retArr[$doc['id']] = array (
						'documentName'	=> $doc['subfolder'],
						'type'		=> $d,
						'indices'	=> $indArr
					);
				}
			}
		}
		//error_log('Return array: '.print_r($retArr, true));
		$db_dept->disconnect();
		return $retArr;
	} else {
		$db_dept->disconnect();
		return false;
	}
}

function getDetailedDocumentPartialList($department, $cabinetID, $docID, $userName, $start, $count) {
//error_log('getDetailedDocumentPartialList(). department: '.$department.', cabinetID: '.$cabinetID.', docID: '.$docID.', userName: '.$userName.', start: '.$start.', count: '.$count); 
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName) {
		$sArr = array('id', 'document_table_name','document_id','subfolder');
		if(getdbType() == 'mssql') {
			
			$query0 = "SELECT ROW_NUMBER() OVER(ORDER BY date_created DESC, id DESC) RowNumber, ".implode(",",$sArr)." FROM ".$cabName."_files ";
			$query0 .= "WHERE doc_id = " . $docID . " AND document_id <> 0 AND deleted = 0 ";
			$end = $start + $count;
			$query = "SELECT ".implode(",",$sArr)." FROM (".$query0.") myTable WHERE RowNumber > $start AND RowNumber <= $end";		
		}
	    else if(getdbType() == 'mysql' or getdbType() == 'mysqli' or getdbType() == 'pgsql') {
			$query = "SELECT ".implode(",",$sArr)." FROM ".$cabName."_files ";
			$query .= "WHERE doc_id = " . $docID . " AND document_id <> 0 AND deleted = 0 ";
			$query .= "ORDER BY date_created DESC, id DESC LIMIT $count OFFSET $start";
		}
		else{
			$query = "SELECT ".implode(",",$sArr)." FROM ".$cabName."_files ";
			$query .= "WHERE doc_id = " . $docID . " AND document_id <> 0 AND deleted = 0 ";
			$query .= "ORDER BY date_created DESC";
		}
		//error_log("getDetailedDocumentPartialList() query: ".$query);
		$docList = $db_dept->extended->getAssoc($query, null, array (), null, MDB2_FETCHMODE_DEFAULT, false, true);
		//error_log('function getDetailedDocumentPartialList: docList: '.print_r($docList, true));
		dbErr($docList);
		$sArr = array('document_table_name','document_type_name');
		$wArr = array('enable' => 1);
		$oArr = array('document_type_name' => 'ASC');
		$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr);
		//error_log('function getDetailedDocumentPartialList: docArr: '.print_r($docArr , true));
		$retArr = array();
		
		$tmpCount = 0;
		$index = $start-1;//0;
		//for($index = $start; $index <= count($docList); $index++)
		foreach($docList as $k1 => $arrDoc)
		{
			$index = $index + 1;
			if($index < $start) {
				continue;
			}
			$doc = $arrDoc[0];
			//error_log($k1." - ".print_r($doc, true));
			
			//$doc = $docList[$index];
			$myDocTableName = $doc['document_table_name'];
			//error_log('document_table_name: '.$myDocTableName);
			if(array_key_exists($myDocTableName,$docArr))
			{
				$k = $myDocTableName;
				$docInfo = getDetailedDocumentIndexList ($db_dept, $doc['document_id'], $k);
				//This is to fix a bug in editing document types
				if (!$docInfo) {
					$realIDs = getTableInfo ($db_dept, array ('document_field_defs_list'),
						array ('id'), array ('document_table_name' => $k), 'queryCol');
					$documentTypeID = getTableInfo ($db_dept, array ('document_type_defs'), 
						array ('id'), array ('document_table_name' => $k), 'queryOne');
					foreach ($realIDs as $indexID) {
						$queryArr = array (
							'document_defs_list_id'		=> (int) $documentTypeID,
							'document_id'			=> (int) $doc['document_id'],
							'document_field_defs_list_id'	=> (int) $indexID,
							'document_field_value'		=> ''
						);
						$res = $db_dept->extended->autoExecute ('document_field_value_list', $queryArr);
						dbErr($res);
					}
					$docInfo = getDetailedDocumentIndexList ($db_dept, $doc['document_id'], $k);
				}
				$indArr = array ();
				//error_log('docInfo: count='.count($docInfo));
				foreach ($docInfo as $indInfo) {
					$indArr[] = array (
						'indexName' => $indInfo['real_field_name'],
						'displayName' => $indInfo['arb_field_name'],
						'value' => $indInfo['document_field_value'],
					);
				}				
				$retArr[$k1] = array (
					'sequence'		=> $index,
					'documentName'	=> $doc['subfolder'],
					'type'		=> $docArr[$myDocTableName],
					'indices'	=> $indArr
				);
				
				$tmpCount++;			
				//error_log("tmpCount: ".$tmpCount);
				if($tmpCount >= $count) {
					break;
				}
			}
		}
		//error_log('getDetailedDocumentPartialList() Return array: '.print_r($retArr, true));
		$db_dept->disconnect();
		return $retArr;
	} else {
		$db_dept->disconnect();
		return false;
	}
}


function getDetailedDocumentIndexList($db_dept, $documentID, $docTableName) {
	$docInfo = array();
	$tableArr = array('document_field_defs_list','document_field_value_list');
	$sArr = array('real_field_name', 'arb_field_name', 'document_field_value');
	$whereArr = array(	'document_field_defs_list_id=document_field_defs_list.id',
						'document_id='.(int)$documentID,
						"document_table_name='$docTableName'" );
	$oArr = array ('ordering' => 'ASC');
	return getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'queryAll', $oArr);
}

function getDocumentTypeList($department, $cabinet=NULL, $userName=NULL) {
	$db_dept = getDbObject($department);

	$sArr = array('id','document_table_name','document_type_name');
	$wArr = array('enable' => 1);
	$oArr = array('document_type_name' => 'ASC');
	$docArr = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr);
	if($cabinet != NULL) {
		$filteredList = getDocumentFilters ($cabinet, 'filter', $db_dept);
	} else {
		$filteredList = array();
	}

/*	if (count($filteredList)) {
		$fetchAll = false;
	} else {
		$fetchAll = true;
	}*/
	//by default, only return the document types that have been "filtered" for a cabinet.
	$fetchAll = false;

	$userInGroups = getGroupsForUser($db_dept,$userName);

	$retArr = array();

	$tmpList = getTableInfo ($db_dept, 'definition_types',
		array ('document_type_id', 'document_type_field', 'definition'),
		array (), 'getAll', array ('document_type_id' => 'ASC',
		'document_type_field' => 'ASC', 'definition' => 'ASC'));

	$defsList = array ();
	foreach ($tmpList as $docDefs) {
		$docTypeID = $docDefs['document_type_id'];
		if (!isset ($defsList[$docTypeID])) {
			$defsList[$docTypeID] = array ();
		}
		if (!isset ($defsList[$docTypeID][$docDefs['document_type_field']])) {
			$defsList[$docTypeID][$docDefs['document_type_field']] = array ();
		}
		$defsList[$docTypeID][$docDefs['document_type_field']][] = $docDefs['definition'];
	}


	foreach($docArr AS $k => $d) {
		$groupArr = getDocumentPermissions($k,$db_dept);
		$inGroup = array_intersect($groupArr, $userInGroups);
		if( sizeof($groupArr) > 0 AND sizeof($inGroup) < 1) {
			continue;
		}

		if ($fetchAll or in_array ($k, $filteredList) ) {
			$docInfo	= array();
			$tableArr	= array('document_field_defs_list');
			$sArr		= array('real_field_name','arb_field_name');
			$whereArr	= array('document_table_name' => $d['document_table_name']);
			$oArr		= array('ordering' => 'ASC');
			$docInfo	= getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'getAssoc', array ('ordering' => 'ASC'));
			$myArr		= array(	'realName'	=> $d['document_table_name'],
									'arbName'	=> $d['document_type_name'],
									'indices'	=> $docInfo);
			if (isset ($defsList[$k])) {
				$myArr['definitions'] = $defsList[$k];
			} else {
				$myArr['definitions'] = array ();
			}
			$retArr[$k] = $myArr;
		}
	}
	$db_dept->disconnect();
	return $retArr;
}

//cz
function getGenericDocumentTypeList($department, $cabinet=NULL, $userName=NULL, $docID=0) {

	$db_dept = getDbObject($department);
	
	$sArr = array('document_table_name','document_id','subfolder','id');
	$query = "SELECT ".implode(",",$sArr)." FROM ".$cabinet."_files ";
	$query .= "WHERE doc_id = $docID AND document_id != 0 AND deleted = 0 ";
	$query .= "ORDER BY document_table_name ASC , document_id DESC";
	$docList = $db_dept->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, false, true);
	$myDocArr = array_keys($docList);
	//error_log("getGenericDocumentTypeList() - docList: ".print_r($myDocArr, true).", count: ".count($myDocArr));

	$docArr = array();
	for($index = 0; $index < count($myDocArr); $index++) {
		$myDocType = $myDocArr[$index];
		error_log("getGenericDocumentTypeList() - myDocType: ".$myDocType);
		$sArr = array('id','document_table_name','document_type_name');
		$wArr = array('enable' => 1, 'document_table_name' =>$myDocType);
		$oArr = array('document_type_name' => 'ASC');
		$docArr = array_merge($docArr, getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'getAssoc',$oArr));
	}
	//error_log("getGenericDocumentTypeList() - docArr: ".print_r($docArr, true));
/*	
	if($cabinet != NULL) {
		$filteredList = getDocumentFilters ($cabinet, 'filter', $db_dept);
	} else {
		$filteredList = array();
	}

	//$fetchAll = 0;
	error_log("count of filteredList: ".count($filteredList));
	if (count($filteredList) > 0) {
		$fetchAll = false;
	} else {
		$fetchAll = true;
	}
	error_log("fetchAll: ".$fetchAll);
*/
	$userInGroups = getGroupsForUser($db_dept,$userName);

	$retArr = array();

	$tmpList = getTableInfo ($db_dept, 'definition_types',
		array ('document_type_id', 'document_type_field', 'definition'),
		array (), 'getAll', array ('document_type_id' => 'ASC',
		'document_type_field' => 'ASC', 'definition' => 'ASC'));

	$defsList = array ();
	foreach ($tmpList as $docDefs) {
		$docTypeID = $docDefs['document_type_id'];
		if (!isset ($defsList[$docTypeID])) {
			$defsList[$docTypeID] = array ();
		}
		if (!isset ($defsList[$docTypeID][$docDefs['document_type_field']])) {
			$defsList[$docTypeID][$docDefs['document_type_field']] = array ();
		}
		$defsList[$docTypeID][$docDefs['document_type_field']][] = $docDefs['definition'];
	}

	$chkArr = array();
	foreach($docArr AS $k => $d) {
		$groupArr = getDocumentPermissions($k,$db_dept);
		$inGroup = array_intersect($groupArr, $userInGroups);
		if( sizeof($groupArr) > 0 AND sizeof($inGroup) < 1) {
			continue;
		}

		//if ($fetchAll or in_array ($k, $filteredList) ) {
			$docInfo	= array();
			$tableArr	= array('document_field_defs_list');
			$sArr		= array('real_field_name','arb_field_name');
			$whereArr	= array('document_table_name' => $d['document_table_name']);
			$oArr		= array('ordering' => 'ASC');
			$docInfo	= getTableInfo($db_dept,$tableArr,$sArr,$whereArr,'getAssoc', array ('ordering' => 'ASC'));
			$myArr		= array(	'realName'	=> $d['document_table_name'],
									'arbName'	=> $d['document_type_name'],
									'indices'	=> $docInfo);
			if (isset ($defsList[$k])) {
				$myArr['definitions'] = $defsList[$k];
			} else {
				$myArr['definitions'] = array ();
			}
			$retArr[$k] = $myArr;
			$chkArr[] = $d['document_table_name'];
		//}
	}
	

	//get subfolders
	if($docID > 0) {
		$oArr   = array('subfolder' => 'ASC');
		$tabArr = getTableInfo($db_dept, $cabinet."_files",
					  array('id', 'subfolder'),
					  array('doc_id'   => $docID, 
					  		'deleted'  => '0', 'display' => '1',
					  		'filename' => 'IS NULL', 'document_table_name' => 'IS NULL'),
					  'getAssoc',$oArr);
		//error_log("getGenericDocumentTypeList(): ".print_r($tabArr, true));
		foreach($tabArr AS $k => $d) {
			if(isset($d) && $d != ""){
				$retArr[] = array(	'realName'	=> "tab_fileId_".$k,
									'arbName'	=>  $d);
			}
		}
	}
	
	if($docID == 0 || !in_array("Main", $tabArr))
	{
		$retArr[] = array(	'realName'	=> "tab_fileId_0",
				'arbName'	=>  "Main");	
	}
	error_log("getGenericDocumentTypeList(): ".print_r($retArr, true));
	$db_dept->disconnect();
	return $retArr;
}


function createTabForDocument($db_dept,$department,$cabName,$docID,$docType,&$name, $db_raw, $mkdir=true) {
 	global $DEFS;
	
	$whereArr = array(  'doc_id'    => (int)$docID);
	$loc = getTableInfo($db_dept,$cabName,array('location'),$whereArr,'queryOne');
	if (!$loc) {
		return false;
	}

	$user = new user();

	$docType = str_replace(' ', '_', $docType);
	$docType = $user->replaceInvalidCharacters($docType,"");
	$docType = str_replace("@","",$docType);
	
    $whereArr = array(  'doc_id'    => (int)$docID,
       	                'filename'  => 'IS NULL' );
    $tabArr = getTableInfo($db_dept,$cabName.'_files',array('subfolder'),$whereArr,'queryCol');

    $i = 1;
	$name = $docType.$i;
    $tabLoc = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
	$tempTabLoc = $tabLoc."/".$name;
	$i = mt_rand( 10000000,99999999 );
    while(in_array($docType.$i,$tabArr) OR file_exists($tempTabLoc)) {
		$i = mt_rand( 10000000,99999999 );
    	$name = $docType.$i;
    	$tempTabLoc = $tabLoc."/".$name;
    }
	$tabLoc = $tempTabLoc;
    $insertArr = array( 'doc_id'		=> (int)$docID,
                        'subfolder'		=> $name,
                        'date_created'	=> date('Y-m-d G:i:s'),
                        'file_size'		=> 4096 );
    $res = $db_dept->extended->autoExecute($cabName.'_files',$insertArr);
	dbErr($res);
    $whereArr = array(  'doc_id'    => (int)$docID,
                        'subfolder' => $name );
    $subfolderID = getTableInfo($db_dept,$cabName.'_files',array('MAX(id)'),$whereArr,'queryOne');
	if($mkdir) {
	    mkdir($tabLoc, 0777);
	}
    $updateArr = array('quota_used'=>'quota_used+4096');
    $whereArr = array('real_department'=> $department);
	updateTableInfo($db_raw,'licenses',$updateArr,$whereArr,1);
	//$db_raw->disconnect();
    return $subfolderID;
}

function createDocumentInfo($department,$cabinetID,$docID,$documentName,$indices,$userName, $db_doc) 
{
	//error_log("createDocumentInfo(".$department.", ".$cabinetID.", ".$docID.", ".$documentName.",...\n");
	//error_log("indices: ".print_r($indices, true)."\n");
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, true);
	if($cabName !== false) {
		$sArr = array('id','document_type_name');
		$whereArr = array('document_table_name' => $documentName);
		$typeDefsID = getTableInfo($db_dept,'document_type_defs',$sArr,$whereArr,'queryRow');
		$docType = $typeDefsID['document_type_name'];
		$tabName = "";
		lockTables($db_dept,array($documentName,$cabName.'_files',$cabName));
		$subfolderID = createTabForDocument($db_dept,$department,$cabName,$docID,$docType,$tabName, $db_doc);
	
		$date = date('Y-m-d G:i:s');
		$insertArr = array( "cab_name"      => $cabName,
							"doc_id"        => (int)$docID,
							"file_id"       => (int)$subfolderID,
							"date_created"  => $date,
							"date_modified" => $date,
							"created_by"    => $userName );
		$res = $db_dept->extended->autoExecute($documentName,$insertArr);
		dbErr($res);
		$documentID = getTableInfo($db_dept,$documentName,array('MAX(id)'),array(),'queryOne');
		unlockTables($db_dept);

		$sArr = array(  'document_id'           => (int)$documentID,
						'document_table_name'   => $documentName);
		$whereArr = array('id' => (int)$subfolderID);
		updateTableInfo($db_dept,$cabName.'_files',$sArr,$whereArr);

		$sArr = array('real_field_name','id','arb_field_name');
		$whereArr = array('document_table_name' => $documentName);
		$fieldArr = getTableInfo($db_dept,'document_field_defs_list',$sArr,$whereArr,'getAssoc');

		$insertArr = array( "document_defs_list_id" => (int)$typeDefsID['id'],
							"document_id"           => (int)$documentID, 
							"document_field_defs_list_id" => '',
							"document_field_value" => ''  );
		foreach($indices AS $k => $v) {
			$insertArr['document_field_defs_list_id'] = (int)$fieldArr[$k]['id'];
			if( strlen( $v ) > 255 ){
				$v = substr( $v, 0, 251 ). '...';
			}
			$insertArr['document_field_value'] = $v;
			
			//error_log('About to update '.$department.': '.print_r($insertArr, true)."\n");
			$res = $db_dept->extended->autoExecute('document_field_value_list',$insertArr);
			dbErr($res);
		}
		$db_dept->disconnect();
		return $subfolderID;
	} else {
		$db_dept->disconnect();
		return false;
	}
}

function getCabinetIndexFields($department, $cabinetID, $userName) {
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName !== false) {
		$indices = getCabinetInfo($db_dept, $cabName);
		$db_dept->disconnect ();
		return $indices;
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function getCabinetIndexDefinitions($department, $cabinetID, $userName) {
	$db_dept = getDbObject($department);
	$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabName !== false) {
		$indices = getCabinetInfo($db_dept, $cabName);
		$sArr = array('field_name', 'required', 'regex', 'display');
        $wArr = array('cabinet_id' => $cabinetID);
        $fieldInfo = getTableInfo($db_dept,'field_format',$sArr,$wArr,'getAssoc');
		$fInfo = array();
		foreach($indices AS $ind) {
			if(isSet($fieldInfo[$ind])) {
				$fInfo[] = array($ind,
								$fieldInfo[$ind]['required'],
								$fieldInfo[$ind]['regex'],
								$fieldInfo[$ind]['display']);
			} else {
				$fInfo[] = array($ind,0,'','');
			}
		}
		$db_dept->disconnect ();
		return $fInfo;
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function getDocumentIndexDefinitions($department, $documentType, $userName) 
{
	//error_log("getDocumentIndexDefinitions(".$department.",".$documentType.",".$userName.")\n");
	$db_dept = getDbObject($department);
	
	$wArr = array('document_table_name' => $documentType);
	$indices = getTableInfo($db_dept, 'document_field_defs_list', array('real_field_name'), $wArr, 'queryCol' );
	//error_log("Index fields for ".$documentType.": ".print_r($indices, true)."\n");	
	$sArr = array('field_name', 'required', 'regex', 'display');   
    $fieldInfo = getTableInfo($db_dept,'field_format',$sArr,$wArr, 'getAssoc');      
    //error_log("IndicesDefs for ".$documentType.": ".print_r($fieldInfo, true)."\n");
        
	$fInfo = array();	
	foreach($indices AS $ind) 
	{
		$fInfo[] = (isSet($fieldInfo[$ind]))?array($ind,$fieldInfo[$ind]['required'],$fieldInfo[$ind]['regex'], $fieldInfo[$ind]['display']):array($ind,0,'','');
	}
	$db_dept->disconnect ();
	return $fInfo;
}

/**
  * This function getting total records from two tables
  * based on the values of $sortType and $sortDir.
  * @author Rambabu Manukonda.
  * @param  Object  Database    $db_object
  * @params $cabinet,$tempTable,$sortType,$sortDir,$start,$resultsPerPage
  * @see    /search/searchResultsExtras.php
  */
function getListfromDualTable($db_object, $cabinet, $tempTable, $start, $resultsPerPage) {
	$res = getTableInfo($db_object, 
			array($cabinet, $tempTable),  //table
			array(),  // SELECT *
			array($cabinet.'.doc_id='.$tempTable.'.result_id', 'deleted=0'), //WHERE
			'query', // type of query
			array('doc_id' => 'DESC'), //ordering
			$start, //limit
			$resultsPerPage //count
	);

/*
    if (getdbType() == "mysql") {
		$res = getTableInfo($db_object, 
				array($cabinet, $tempTable),  //table
				array(),  // SELECT *
				array($cabinet.'.doc_id='.$tempTable.'.result_id', 'deleted=0'), //WHERE
				'getAll', // type of query
				array(), //ordering
				$start, //limit
				$resultsPerPage //count
		);
    } else
        if (getdbType() == "db2") {
            $query = "SELECT * FROM $cabinet  C1, $tempTable T1, TABLE(SELECT count(*) AS  ROW# FROM ";
            $query .= "$cabinet  C2  WHERE C2.doc_id < C1.doc_id) AS TEMP_TAB WHERE C1.doc_id = T1.result_id " .
                    "AND C1.deleted = 0 AND ROW#>=$start AND ROW#<=$resultsPerPage ";
    		$res = $db_object->query($query);
		dbErr($res);
        }
*/
    return $res;
}

//Returns true if given cabinet is auto_complete enabled
//Returns false if given cabinet is not auto_complete enabled
//Returns -1 if cabinet does not exist or cabinet permissions denied
//  Check for return value === -1, not == -1
function isAutoComplete($userName, $department, $cabinetID, $db_doc)
{
    $db_dept = getDbObject($department);
    $cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
    if($cabinetName !== false) {
        $settings = new GblStt($department, $db_doc);
        $cabIndex = "indexing_$cabinetName";
        $autoComplete = $settings->get($cabIndex);
        if( strcmp($autoComplete, "auto_complete_$cabinetName") == 0 ) {
			$db_dept->disconnect ();
			return "auto_complete";
		} elseif(	strcmp($autoComplete, "odbc_auto_complete") == 0 ) {
			$sArr = array('lookup_field');
			$wArr = array('cabinet_name' => $cabinetName);
			$lookup = getTableInfo($db_dept,'odbc_auto_complete',$sArr,$wArr,'queryOne');
			$lookupArr = explode(",",$lookup);
			if(count($lookupArr) == 1) {
				$db_dept->disconnect ();
				return "odbc_auto_complete";
			} else {
				$db_dept->disconnect ();
				return false;
			}
		} elseif ($autoComplete == 'sagitta_ws_auto_complete') {
			$db_dept->disconnect ();
			return 'sagitta_ws_auto_complete';
		} else {
			$db_dept->disconnect ();
            return false;
		}
    }
	$db_dept->disconnect();
    return -1;
}

//Gets the list of indices that have values in the auto_complete table 
function getAutoComplete( $userName, $department, $cabinetID, $autoCompleteTerm, $db_doc )
{
 	global $DEFS;
	if(isSet($DEFS['WS_STRIP_CHARS'])) {
		$badChars = $DEFS['WS_STRIP_CHARS'];
		for($i=0;$i<strlen($badChars);$i++) {
			$autoCompleteTerm = str_replace($badChars{$i},"",$autoCompleteTerm);
		}
	}

    $autoCompleteValues = array();
    $autoComplete = isAutoComplete($userName, $department, $cabinetID, $db_doc);
    if( $autoComplete === -1 )
    {
        //if there no permissions/cabinet, returns -1
        return $autoComplete;
    }
    elseif( $autoComplete === false)
    {
        //if there is not autoComplete for cabinet return empty array
        return $autoCompleteValues;
    }
    elseif( strcmp($autoComplete, "auto_complete") == 0 )
    {
		$db_dept = getDbObject($department);
		//isAutoComplete() already tests for cabinet exists
		$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
		$indices = getCabinetInfo($db_dept, $cabinetName);

		//find where the first column value == $autoCompleteTerm
		$res = getTableInfo($db_dept, 'auto_complete_'.$cabinetName, 
			array(), array($indices[0] => $autoCompleteTerm), 'queryRow');
		$db_dept->disconnect();
        if(PEAR::isError($res))
            return false;

        if($res)
        {
            foreach($indices AS $arrKey => $cabIndex)
                $autoCompleteValues[$cabIndex] = $res[$cabIndex];
        }
        return $autoCompleteValues;
    }
	elseif( strcmp($autoComplete, "odbc_auto_complete") == 0 )
	{
		$db_dept = getDbObject($department);
		//isAutoComplete() already tests for cabinet exists
		$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
		$indices = getCabinetInfo($db_dept, $cabinetName);
	
		$odbcRes = getODBC_auto_complete( $userName, $department, $cabinetID, $autoCompleteTerm, $db_doc, $db_dept );
		if( !is_array($odbcRes) ) {
			return false;
		}

		foreach($indices AS $arrKey => $cabIndex) {
			$autoCompleteValues[$cabIndex] = $odbcRes[$cabIndex];
		}
		return $autoCompleteValues;
	} elseif ($autoComplete == 'sagitta_ws_auto_complete') {
		$db_dept = getDbObject($department);
		$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
		return getSagRow($cabinetName, $autoCompleteTerm, $department);
	}

	//Returns empty array
	return $autoCompleteValues;
}

//Returns the odbc auto complete row from the given value
function getODBC_auto_complete($userName, $department, $cabinetID, $searchTerm, $db_docutron, $db_dept=null )
{
/*	if( !check_enable('searchResODBC', $department) ) {
		return false;
	}
*/
	$disconnect=0;
	if( $db_dept == null ) {
		$db_dept = getDbObject($department);
		$disconnect = 1;
	}
	//isAutoComplete() already tests for cabinet exists
	$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);

	$transInfo = getTableInfo($db_dept, 'odbc_auto_complete',
		array(), array('cabinet_name' => $cabinetName), 'queryRow');
	if($transInfo) {
		$searchVal = $searchTerm;
		if(strpos($searchVal, '"') === 0) {
			$searchVal = substr($searchVal, 1, strlen($searchVal) - 2);
		}

		$db_odbc = getODBCObject($transInfo['connect_id'], $db_docutron);
		if(PEAR::isError($db_odbc)) {
		 	return "Error connecting to ODBC Database!";
		}
		$gblStt = new GblStt ($department, $db_docutron);
		$row = getODBCRow($db_odbc, $searchVal, $cabinetName, $db_dept, '', $department, $gblStt);
		if( is_object( $db_odbc ) )
			$db_odbc->disconnect();
		if ($disconnect==1) $db_dept->disconnect ();
		if($row) {
			return $row;
		} else {
			return array();
		}
	}
}

//Returns the datatype definitions key for the settings table
function getDatatypeKey($department, $cabinetID, $cabIndex)
{
	$key = "dt,$department,$cabinetID,$cabIndex";
	return $key;
}

//Returns the list of indices with datatype definitions
function getDatatypeDefinitions( $userName, $department, $cabinetID, $db_doc )
{
    $autoCompArr = array();
    $db_dept = getDbObject($department);
    $cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
    if($cabinetName === false) {
		$db_dept->disconnect();
        return false;
    }

    $settings = new GblStt($department, $db_doc);
    $indices = getCabinetInfo($db_dept, $cabinetName);
    foreach($indices As $arrKey => $cabIndex)
    {
		$key = getDatatypeKey( $department, $cabinetID, $cabIndex );
        $autoComplete = $settings->get($key);
        if( strcmp($autoComplete, "") != 0 )
            $autoCompArr[$cabIndex] = $autoComplete;
    }
	$db_dept->disconnect();
    return $autoCompArr;
}

//Security check for cabinet access and index existence when
//	modifiying the datatype definitions for a cabinet
function datatypeSecurity($department, $userName, $cabinetID, $cabIndex)
{
	$message = "";
    $db_dept = getDbObject($department);
    $cabinetName = hasAccess($db_dept, $userName, $cabinetID, true);
    if($cabinetName !== false) {
    	$indices = getCabinetInfo($db_dept, $cabinetName);
		if( !in_array($cabIndex, $indices) ) {
			$message = "Cabinet index does not exist";
		}
    } else {
        $message = "Cabinet access denied";
	}

	$db_dept->disconnect();
	if( strcmp($message, "") == 0 ) {
		return true;
	} else {
		return $message;
	}
}

//Append datatype definitions to a cabinet index, create entry if it does not exist
function addDatatypeDefinitions( $userName, $department, $cabinetID, $cabIndex, $definitions, $db_doc )
{
	$message = datatypeSecurity($department, $userName, $cabinetID, $cabIndex);
	if($message !== true) {
		return $message;
	}

	$settings = new GblStt($department, $db_doc);
	$key = getDatatypeKey($department, $cabinetID, $cabIndex);
	$autoComplete = $settings->get($key);

	$value = "";
	$delimiter = ",,,";
	$acList = explode($delimiter, $autoComplete);
	foreach($definitions AS $item => $def) {
		if( !in_array($def, $acList) && (strcmp($def, "") != 0) ) {
			if( strcmp($value, "") != 0 )
				$value .= $delimiter;
			$value .= $def;
		}
	}

	//if there are values to put into the db
	if( strcmp($value, "") != 0 ) {
		//if datatypes exist, append new values
		if( strcmp($autoComplete, "") != 0 ) {
			$value = $autoComplete.$delimiter.$value;
		}
		$valueArr = explode(",,,", $value);
		usort($valueArr, "strnatcasecmp");
		$value = implode(",,,", $valueArr);
		$settings->set($key, $value);
	} else {
		return "No datatypes added";
	}
	
	return "Datatypes added successfully";
}

//Remove all datatype definitions for the given index
function clearDatatypeDefinitions( $userName, $department, $cabinetID, $cabIndex, $db_doc )
{
	$message = datatypeSecurity($department, $userName, $cabinetID, $cabIndex);
	if($message !== true) {
		return $message;
	}

	$settings = new GblStt($department, $db_doc);
	$key = getDatatypeKey($department, $cabinetID, $cabIndex);
	$settings->removeKey($key);

	return "Datatypes cleared successfully";
}

//Delete given datatype definitions from the db if they exist
//Remove entry from the db if all definitions are deleted
function deleteDataTypeDefinitions( $userName, $department, $cabinetID, $cabIndex, $definitions, $db_doc )
{
	$message = datatypeSecurity($department, $userName, $cabinetID, $cabIndex);
	if($message !== true) {
		return $message;
	}

	$delimiter = ",,,";
	$settings = new GblStt($department, $db_doc);
	$key = getDatatypeKey($department, $cabinetID, $cabIndex);
	$autoComplete = $settings->get($key);
	//if datatypes does not exist
	if( strcmp($autoComplete, "") == 0 ) {
		return "Index definition does not exist";
	}

	$autoComplete = explode($delimiter, $autoComplete);
	//Remove each definition from the db list that matches given defs
	foreach($definitions AS $datatype) {
		$acKey = array_search($datatype, $autoComplete);
		if( $acKey !== false ) {
			unset($autoComplete[$acKey]);
		}
	}

	//Remove the entry completely if there are no definitions
	if( sizeof($autoComplete) < 1 ) {
		$settings->removeKey($key);
	} else { //else set the remaining defs in the db
		$autoComplete = implode($delimiter, $autoComplete);
		$settings->set($key, $autoComplete);
	}

	return "Datatypes removed successfully";
}

//Create a barcode with the info passed in the parameters
//	Currently no tests for accurate parameter info
//	Returns the barcode, ie the id of the barcode_reconciliation table
function createBarcode($barcodeInfo, $barcode_field, $cabinetName,
	$userName, $date, $department, $deleteBarcode, $splitType, $compress, $printUsername, $db_dept, $db_doc)
{
	$barcode = 0;

	$settingsList = new settingsList($db_doc, $department, $db_dept);
	$settingsPermissions = $settingsList->getSettingsList();
	if (isset ($settingsPermissions[$cabinetName])) {
		$cabSettings = $settingsPermissions[$cabinetName];
	} else {
		$cabSettings = array ();
	}
	if( isset($cabSettings['deleteBC']) AND $deleteBarcode == NULL ) {
		$deleteBarcode = $cabSettings['deleteBC'];
	} elseif( $deleteBarcode === NULL ) {
		$deleteBarcode = 1;
	}
	if( isset($cabSettings['compress']) AND $compress == NULL ) {
		$compress = $cabSettings['compress'];
	} elseif( $compress == NULL ) {
		$compress = 1;
	}
	if(!empty ($cabSettings['bcFormat-mtif']) AND $splitType == NULL) {
		$splitType = 'mtif';
	} elseif (!empty ($cabSettings['bcFormat-asis']) AND $splitType == NULL) {
		$splitType = 'asis';
	} elseif (!empty ($cabSettings['bcFormat-pdf']) AND $splitType == NULL) {
		$splitType = 'pdf';
	} elseif( $splitType == NULL ) {
		$splitType = 'stif';
	}

	lockTables($db_doc,array('barcode_reconciliation'));
	$insertArr = array(
		"barcode_info"		=> $barcodeInfo,
		"barcode_field"		=> $barcode_field,
		"cab"				=> $cabinetName,
		"username"			=> $userName,
		'delete_barcode'	=> $deleteBarcode,
		'split_type'		=> $splitType,
		'compress'			=> $compress,
		"date_printed"		=> $date,
		"department"		=> $department
	);
	$db_doc->extended->autoExecute("barcode_reconciliation", $insertArr);
	$barcode = getTableInfo($db_doc,'barcode_reconciliation',array('MAX(id)'),array(),'queryOne');
	unlockTables($db_doc);
	$db_doc->disconnect();

	$auditString = "Barcode field: $barcode_field\n";
	$auditString .= "Cabinet: $cabinetName\n";
	$auditString .= "Printed by user: $userName\n";
	$auditString .= "Date printed: $date\n";
	$auditString .= "Department: $department\n";
	$action = "Webservices Barcode Printed";
	auditWebServices($userName, $date, $auditString, $action, $department);
	return $barcode;
}

function getFolderBarcode( $userName, $department, $cabinetID, $docID, $tabID=0, $db_doc, $db_dept=NULL,
	$deleteBC=null, $splitType=null, $compress=null, $printUsername="admin" )
{
	$disconnect=0;
	if(!$db_dept) {
		$db_dept = getDbObject($department);
		$disconnect = 1;
	}
	$barcode = 0;
    $cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
    if($cabinetName !== false) {
		preg_match('/[0-9].*/', $department, $match);
		if($match) {
			$dbID = $match[0];
		} else {
			$dbID = 0;
		}
		
		//Prep info to create barcode
		if ($username=="tvs"){
			$barcodeInfo = "V $dbID $cabinetID $docID";
		} else {
			$barcodeInfo = "$dbID $cabinetID $docID";
		}
		$uniqueArray = getCabIndexArr($docID, $cabinetName, $db_dept);
		$barcode_field = implode(' ',$uniqueArray);
		$date = date('Y-m-d H:i:s');
		if($tabID != 0) {
			$tab = getTableInfo($db_dept, $cabinetName.'_files', array('subfolder'),
				array('id' => (int)$tabID), 'queryOne');
			$barcodeInfo .= " $tabID";
			$barcode_field .= " Tab: $tab";
		}

		//Create the barcode
		$barcode = createBarcode($barcodeInfo, $barcode_field, $cabinetName, 
			$userName, $date, $department, $deleteBC, $splitType, $compress, $printUsername, $db_dept, $db_doc);
	}
	if ($disconnect==1) $db_dept->disconnect();
	return $barcode;
}

function auditWebServices($userName, $date, $info, $action, $department=NULL, $db_dept=NULL)
{
	$disconnect=0;
	if($db_dept == NULL) {
		$db_dept = getDbObject($department);
		$disconnect=1;
	}
	$insertArr = array(
		"username"		=> $userName,
		"datetime"		=> $date,
		"info"			=> $info,
		"action"		=> $action
	);
	$db_dept->extended->autoExecute("audit", $insertArr);
	if ($disconnect==1) $db_dept->disconnect ();
}

//Check for department access for a user
function checkDeptAccess($department, $userName, $db_doc)
{
	$DO_user = DataObject::factory('users', $db_doc);
//	$db_object->disconnect();
	$DO_user->get('username', $userName);

	$temp = strtolower($department);
	if( in_array($temp,array_keys($DO_user->departments)) )
		return true;
	else
		return false;
}

//Builds the path of the inbox based on the given info
//TODO: need to make greaterThanUser security checks
function buildInboxPath($userName, $department, $inboxUser, $folder)
{
    global $DEFS;
	$path = $DEFS['DATA_DIR']."/".$department;
	//regular inbox
	if( strcmp($inboxUser, "") == 0) {
		$path .= "/inbox";
	//TODO: Need to add security check here
	} elseif( strtolower($userName) == strtolower($inboxUser) ) { //personal inbox
		$path .= "/personalInbox/$inboxUser";
	} else {
		return false;
	}

	//if looking for folder list
	if( strcmp($folder, "") != 0 ) {
		$path .= "/".$folder;
	}

	if( !is_dir($path) )
		return false;
	return $path;
}

//Returns the list of files in the inbox
function getInboxFileList($userName, $department, $inboxUser, $folder, $db_doc)
{
	//Security check for department
	if( !checkDeptAccess($department, $userName, $db_doc) ) {
		return false;
	}

	$path = buildInboxPath($userName, $department, $inboxUser, $folder);
	if($path === false)
		return false;

	$returnFileList = array();
	$handle = opendir($path);
	while (false !== ($file = readdir($handle))) {
		// Check if file, then count or add it
		if(is_file($path."/".$file)) {
			$returnFileList[$file] = false;
		} elseif($file != "." && $file != "..") {
			$returnFileList[$file] = true;
		}
	}

	return $returnFileList;	
}

//Returns a filename that is not already in use
//TODO: Need protection for users adding the same filename at once
function getSafeInboxName($tempDir, $destName)
{
	$destName = str_replace(" ", "_", $destName);
	//If the filename exists in the final directory place or the
	//temporary filing space, we need to augment the name.
	while(file_exists($tempDir."/".$destName)) {
		$ct++;
		$tempArr = explode('.', $destName);
		$size = count($tempArr);
		if($size > 1) {
			$tempArr[$size - 2] = $tempArr[$size - 2]."-$ct";
		} else {
			$tempArr[$size - 1] = $tempArr[$size - 1]."-$ct";
		}
		$destName = implode('.', $tempArr);
	}
    return $destName;
}

//Upload a file to the inbox
function uploadToInbox($userName, $department, $inboxUser, $folder, $filename, $encodedFile, $db_doc)
{
    global $DEFS;
	//Security check for department
	if( !checkDeptAccess($department, $userName, $db_doc) ) {
		return false;
	}

	$path = buildInboxPath($userName, $department, $inboxUser, $folder);
	if($path === false)
		return false;

	$filename = getSafeInboxName($path, $filename);
	$fullPath = $path."/".$filename;
	file_put_contents($fullPath, $encodedFile);
	//chmod($fullPath, 777);
	allowWebWrite($fullPath,$DEFS);

	return $filename;
}

//Downloads a file from the inbox
function downloadFromInbox($userName, $department, $inboxUser, $folder, $filename, $db_doc)
{
	//Security check for department
	if( !checkDeptAccess($department, $userName, $db_doc) ) {
		return false;
	}

	$path = buildInboxPath($userName, $department, $inboxUser, $folder);
	if($path === false)
		return false;

	$fullPath = $path."/".$filename;
	if( is_file($fullPath) )
		return file_get_contents($fullPath);
	else
		return false;
}

//Creates an empty folder in the inbox based on the given info
//Empty folders get deleted from the inbox upon first inbox viewing
function createInboxFolder($userName, $department, $inboxUser, $folder, $db_doc)
{
    global $DEFS;
	//Security check for department
	if( !checkDeptAccess($department, $userName, $db_doc) ) {
		return false;
	}

	$path = buildInboxPath($userName, $department, $inboxUser, "");
	if($path === false){
		return false;
	}

	$foldername = getSafeInboxName($path, $folder);
	$fullPath = $path."/".$foldername;
	$res = mkdir($fullPath, 0777);
	if( $res ) {
		allowWebWrite($fullPath,$DEFS);

		return $foldername;
	} else {
		return false;
	}
}

//Returns array of saved tabs for a cabinet if they exist
//	else return empty array
//Returns -1 if cabinet does not exist or cabinet permissions denied
//  Check for return value === -1, not == -1
function getCabSavedTabs($userName, $department, $cabinetID, $db_doc)
{
    $db_dept = getDbObject($department);
    $cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
    if($cabinetName !== false) {
		$savedTabs = getSavedTabs($cabinetName, $department, $db_doc);
		$groupAccess = getTableInfo($db_dept,'group_tab',array(),array(),'queryAll');	
		$groups = new groups($db_dept);
		$notShowTab = array();
		foreach($groupAccess as $myRule) {
			$inGrp = $groups->getMembers($myRule['authorized_group']);
			if( !in_array($userName, $inGrp)) {
				if($cabinetName == $myRule['cabinet'] and
					!$myRule['doc_id']) {
					$notShowTab[] = $myRule['subfolder'];
				}
			}
		}
		$goodTabs = array ();
		foreach($savedTabs as $myTab) {
			if(!in_array($myTab, $notShowTab)) {
				$goodTabs[] = $myTab;
			}
		}
		$db_dept->disconnect ();
		return $goodTabs;
    }
	$db_dept->disconnect ();
    return -1;
}

//Returns the upload username
//	else returns false
function getUploadUsername($userName, $department, $db_doc)
{
	//Security check for department
	if( !checkDeptAccess($department, $userName, $db_doc) ) {
		return false;
	}
	$settings = new GblStt($department, $db_doc);
	$key = "upload_username";
	$upload_username = $settings->get($key);
	if( strcmp($upload_username, "") == 0 ) 
		return 'upload';
	else
		return $upload_username;
}

//Returns the upload password
//	else returns false
function getUploadPassword($userName, $department, $db_doc)
{
	//Security check for department
	if( !checkDeptAccess($department, $userName, $db_doc) ) {
		return false;
	}

	$settings = new GblStt($department, $db_doc);
	$key = "upload_password";
	$upload_password = $settings->get($key);
	if( strcmp($upload_password, "") == 0 ) 
		return 'docutron';
	else
		return $upload_password;
}

//Returns xml version of cabinet info
function getCabinetInfoXML( $userName, $department, $cabinetID, $db_doc )
{
	$db_dept = getDbObject ($department);
	$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabinetName === false) {
		$db_dept->disconnect ();
		return false;
	}

	$indices = getCabinetInfo($db_dept, $cabinetName);
	$dataDefs = getDatatypeDefinitions( $userName, $department, $cabinetID, $db_doc );
	$cabSavedTabs =  getCabSavedTabs($userName, $department, $cabinetID, $db_doc);
	$isAutoComplete = isAutoComplete($userName, $department, $cabinetID, $db_doc);

	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$root = $xmlDoc->create_element('cabinetInfo');
		$xmlDoc->append_child($root); //<cabinetInfo>

		$cabID = $xmlDoc->create_element('cabinet_id');
		$root->append_child($cabID); //<cabinet_id>
		$cabID->append_child($xmlDoc->create_text_node($cabinetID));

		$cabName = $xmlDoc->create_element('cabinet_name');
		$root->append_child($cabName); //<cabinet_name>
		$cabName->append_child($xmlDoc->create_text_node($cabinetName));

		$cabIndices = $xmlDoc->create_element('indices');
		$root->append_child($cabIndices); //<indices>
		foreach($indices AS $index) {
			$cabIndex = $xmlDoc->create_element('index');
			$cabIndex->set_attribute('name', $index);
			$cabIndices->append_child($cabIndex); //<index name=$index>

			if( !empty ($dataDefs[$index]))
			{
				$defs = $xmlDoc->create_element('datatype_defs');
				$cabIndex->append_child($defs); //<<datatype_defs>

				$dataDefsArr = explode(",,,", $dataDefs[$index]);
				foreach($dataDefsArr AS $dataDef) {
					$def = $xmlDoc->create_element('def');
					$defs->append_child($def); //<def>

					$defNode = $xmlDoc->create_text_node($dataDef);
					$def->append_child($defNode);
				}
			}
		}

		if( count($cabSavedTabs) > 0 ) {
			$savedTabs = $xmlDoc->create_element('tabs');
			$root->append_child($savedTabs); //<tabs>
			foreach($cabSavedTabs AS $tab) {
				$savedTab = $xmlDoc->create_element('tab');
				$savedTabs->append_child($savedTab); //<tab>
				$savedTab->append_child($xmlDoc->create_text_node($tab));
			}
		}

		
		$autoComplete = $xmlDoc->create_element('isAutoComplete');
		$root->append_child($autoComplete); //<isAutoComplete>
		if( $isAutoComplete )
			$autoComplete->append_child($xmlDoc->create_text_node(1));
		else
			$autoComplete->append_child($xmlDoc->create_text_node(0));
		$xmlStr = $xmlDoc->dump_mem(true);
	} else {
		$xmlDoc = new DOMDocument ();
		$root = $xmlDoc->createElement('cabinetInfo');
		$xmlDoc->appendChild($root); //<cabinetInfo>

		$cabID = $xmlDoc->createElement('cabinet_id');
		$root->appendChild($cabID); //<cabinet_id>
		$cabID->appendChild($xmlDoc->createTextNode($cabinetID));

		$cabName = $xmlDoc->createElement('cabinet_name');
		$root->appendChild($cabName); //<cabinet_name>
		$cabName->appendChild($xmlDoc->createTextNode($cabinetName));

		$cabIndices = $xmlDoc->createElement('indices');
		$root->appendChild($cabIndices); //<indices>
		foreach($indices AS $index) {
			$cabIndex = $xmlDoc->createElement('index');
			$cabIndex->setAttribute('name', $index);
			$cabIndices->appendChild($cabIndex); //<index name=$index>

			if( !empty ($dataDefs[$index]))
			{
				$defs = $xmlDoc->createElement('datatype_defs');
				$cabIndex->appendChild($defs); //<<datatype_defs>

				$dataDefsArr = explode(",,,", $dataDefs[$index]);
				foreach($dataDefsArr AS $dataDef) {
					$def = $xmlDoc->createElement('def');
					$defs->appendChild($def); //<def>
					$dataDef = iconv(mb_detect_encoding($dataDef, mb_detect_order(), true), "UTF-8", $dataDef);
					$defNode = $xmlDoc->createTextNode($dataDef);
					$def->appendChild($defNode);
				}
			}
		}

		if( count($cabSavedTabs) > 0 ) {
			$savedTabs = $xmlDoc->createElement('tabs');
			$root->appendChild($savedTabs); //<tabs>
			foreach($cabSavedTabs AS $tab) {
				$savedTab = $xmlDoc->createElement('tab');
				$savedTabs->appendChild($savedTab); //<tab>
				$savedTab->appendChild($xmlDoc->createTextNode($tab));
			}
		}

		
		$autoComplete = $xmlDoc->createElement('isAutoComplete');
		$root->appendChild($autoComplete); //<isAutoComplete>
		if( $isAutoComplete )
			$autoComplete->appendChild($xmlDoc->createTextNode(1));
		else
			$autoComplete->appendChild($xmlDoc->createTextNode(0));
		$xmlStr = $xmlDoc->saveXML ();
	}
	$db_dept->disconnect ();
	return $xmlStr;
}
/**
 * return folder indices and their values for the provided doc_id.
 */
function getFolderValues($userName, $department, $cabinetID, $docID)
{
	$db_dept = getDbObject($department);

	$cabinetName = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabinetName === false) {
		$db_dept->disconnect ();
		return false;
	}
	$indices = getCabinetInfo($db_dept, $cabinetName);
	$values = getTableInfo($db_dept, $cabinetName, array(), array('doc_id'=>(int)$docID), 'queryRow');
	
	$folderInfo = array();
	foreach($indices as $index)
	{
		$folderInfo[$index] = trim($values[$index]);
	}
	$db_dept->disconnect ();
	return $folderInfo;
}

function isDocumentView($userName, $department, $cabinetID = 0, $cabinet = "")
{
	$db_dept = getDbObject($department);
	if(!$cabinet) {
		$cabinet = hasAccess($db_dept, $userName, $cabinetID, false);
		if(!$cabinet) {
			$db_dept->disconnect();
			return false;
		}
	}
	$db_dept->disconnect ();
	$user = new user();
	$user->username = $userName;
	$user->fillUser(NULL, $department);
	return $user->checkSetting('documentView', $cabinet);
}

function getBarcodeInfo( $userName, $xmlStr )
{
	$retXML = "";
	if (substr (PHP_VERSION, 0, 1) == '4') {
		if( $domDoc = domxml_open_mem($xmlStr) ) {
			$barcodeObj = new webServicesBarcode($userName, $domDoc);
			$retXML = $barcodeObj->getRetXML();
		}
	} else {
		$domDoc = new DOMDocument();
		if( $domDoc->loadXML($xmlStr) ) {
			$barcodeObj = new webServicesBarcode($userName, $domDoc);
			$retXML = $barcodeObj->getRetXML();
		}
	}
	return $retXML;
}

function getBarcodeImage( $userName, $barcode )
{
	$barcodeStr = generateBCImage($barcode);
	return $barcodeStr;
}

//Creates a subfolder/tab in the selected folder given by the parameters
//	Returns error meesages if the tab is invalid
//	Returns the tabID if the tab was created successfully
function createSubfolder( $userName, $department, $cabinetID, $docID, $subfolderName, $db_raw=false, $mkdir=true )
{
	global $DEFS;
	$db_dept = getDbObject($department);
	$db_doc = getDbObject('docutron');
	$cabinetName = hasAccess($db_dept, $userName, $cabinetID, true);
	if($cabinetName === false) {
			$db_dept->disconnect();
			$db_doc->disconnect();
		return "Folder access denied";
	}
	
	$tmpUser = new user();
	$tmpUser->username = $userName;
	$tabName = strip_tags($subfolderName);
	//removes more than one space/underscore together
	$tabName = $tmpUser->parseStr($tabName);
	//error checks tab in tabChecks.php
	$status = tabCheck($tabName, $tmpUser);
	if( $status !== false ) {
			$db_dept->disconnect();
			$db_doc->disconnect();
		return $status;
	}

	// if the docutron DB id ($db_raw) not passed in, then go get it
//	$db_raw = $db_raw instanceOf MDB2 ? $db_raw : getDbObject('docutron');
	
	$whereArr = array('doc_id'=>(int)$docID);
    $docLocation = getTableInfo($db_dept,$cabinetName,array('location'),$whereArr, 'queryOne'); 
    $docLocation = str_replace(" ","/",$docLocation);
	$tabLoc = $DEFS['DATA_DIR']."/".$docLocation."/".$tabName."/";
	if(file_exists($tabLoc)) {
			$db_dept->disconnect();
			$db_doc->disconnect();
		return "Tab already exists";
	}

	if( checkQuota($db_doc, 4096, $department) ) {
		if($mkdir) {
			mkdir($tabLoc, 0777);
		}
		allowWebWrite($tabLoc,$DEFS);
		$updateArr = array('quota_used'=>'quota_used+4096');
		$whereArr = array('real_department'=> $department);
		updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
	} else {
			$db_dept->disconnect();
			$db_doc->disconnect();
		return "Quota full.  Please contact the system administrator.";
	}

	$insertArr = array(
		"doc_id"	=> (int)$docID,
		"subfolder"	=> $tabName,
		"file_size"	=> (int)4096
	);
	$res = $db_dept->extended->autoExecute($cabinetName."_files",$insertArr);
	dbErr($res);

	$whereArr = array(
		"doc_id"	=> (int)$docID,
		"subfolder"	=> $tabName,
		"filename"	=> "IS NULL",
		"deleted"	=> 0
	);
	$tabID = getTableInfo($db_dept, $cabinetName."_files", array('id'), $whereArr, 'queryOne');
	$db_dept->disconnect();
	$db_doc->disconnect();

	return $tabID;
}

//Removes invalid characters from filenames
function stripInvalidChars($string)
{
	$retStr = $string;
	$invalidChars = array(",", "/", "?", "+", '`', '&', "'");
	foreach($invalidChars AS $char) {
		$retStr = str_replace($char, "_", $retStr);
	}
	return $retStr;
}

function getPersonalInboxBarcode($userName, $department, $db) {
	preg_match('/[0-9].*/', $department, $match);
	if($match) {
		$dbID = $match[0];
	} else {
		$dbID = 0;
	}
	$DO_user = DataObject::factory('users', $db);
	$DO_user->get('username', $userName);
	return $dbID . ' 0 ' . $DO_user->id;
}

function getUserBarcode($userName, $department, $db_dept, $db_doc) {
	$barcode_field = " ";
	$cabinetName = " ";
	$date = date('Y-m-d H:i:s');
	$deleteBC = NULL;
	$splitType = NULL;
	$compress = NULL;
	$printUsername = "admin";
	$barcodeInfo = getPersonalInboxBarcode( $userName, $department, $db_doc);
	//Create the barcode
	$barcode = createBarcode($barcodeInfo, $barcode_field, $cabinetName, 
		$userName, $date, $department, $deleteBC, $splitType, $compress, 
		$printUsername, $db_dept, $db_doc);
	return $barcode;
}

function getDepartmentUserList ($userName, $department) {
	$db_dept = getDbObject ($department);
	if (hasAccess ($db_dept, $userName)) {
		$stuff=getTableInfo ($db_dept, 'access', array ('username'),
			array (), 'queryCol', array ('username' => 'ASC'));
		$db_dept->disconnect ();
		return $stuff;
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function getWorkflowDefs ($userName, $department) {
	$db_dept = getDbObject ($department);
	if (hasAccess ($db_dept, $userName)) {
		$stuff=getTableInfo ($db_dept, 'wf_defs', array ('MIN(id)', 'defs_name'),
			array (), 'getAssoc', array (), 0, 0, array ('defs_name'));
		$db_dept->disconnect ();
		return $stuff;
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function assignWorkflow ($userName, $department, $cabinetID, $docID, $tabID, $wfDefsID, $wfOwner, $db_doc) {
	$db_dept = getDbObject ($department);
	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);
	if ($cabinet !== false) {
		$wfDocID = (int)addToWorkflow ($db_dept, $wfDefsID, $docID, $tabID, $cabinet, $wfOwner);
		if ($wfDocID != -1) {
			$stateNode = new stateNode ($db_dept, $department, $wfOwner, $wfDocID, $wfDefsID, $cabinet, $cabinet, $docID, $db_doc);
			$stateNode->notify ();
			$stateNode->accept(true);
			$db_dept->disconnect ();
			return true;
		} else {
			$db_dept->disconnect ();
			return false;
		}
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function hasWorkflowInProgress($userName, $department, $cabinetID, $docID, $tabID)
{
	$db_dept = getDbObject ($department);
	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);
	if ($cabinet !== false) {
		$db_dept->disconnect ();
		return IsWorkflowInProgress ($db_dept, $docID, $tabID, $cabinet); 
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function getFileVersion ($userName, $department, $cabinetID, $fileID)
{
	global $DEFS;
	$db_dept = getDbObject ($department);
	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);
	if ($cabinet !== false) {
		$fileVer = getFileVer($cabinet, $fileID, $db_dept);
		return $fileVer['v_major'].".".$fileVer['v_minor'];
	} else {
		$db_dept->disconnect ();
		return -1;
	}
}

function getFolderID ($userName, $department, $cabinetID, $fileID)
{
	//error_log("getFolderID() userName: ".$userName.", department: ".$department.", cabinetID: ".$cabinetID.", fileID: ".$fileID);
	global $DEFS;
	$db_dept = getDbObject ($department);
	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);
	if ($cabinet !== false) {
		$docID = getTableInfo($db_dept, $cabinet."_files", array('doc_id'), array('id'=>(int)$fileID), 'queryOne');
		//error_log("getFolderID() docID: ".$docID);
		return $docID;
	} else {
		$db_dept->disconnect ();
		return -1;
	}
}

function checkOutFile ($userName, $department, $cabinetID, $fileID, &$encFileData)
{
	global $DEFS;
	$db_dept = getDbObject ($department);
	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);
	if ($cabinet !== false) {
		$parentID = getParentID($cabinet, $fileID, $db_dept);
		if($parentID == 0) {
		    makeVersioned($cabinet, $fileID, $db_dept);
		    $parentID = $fileID;
		}
		$gotlock = checkAndSetLock($cabinet, $parentID, $db_dept, $userName);
		$fileID = getRecentID($cabinet, $parentID, $db_dept);
		$who = whoLocked($cabinet, $parentID, $db_dept);
		// Get information for the file name if check out for writing
		if($gotlock || ($who == $userName)){
				$fileRow = getTableInfo($db_dept, $cabinet.'_files', array(), array('id' => (int) $fileID), 'queryRow');
				$whereArr = array('doc_id'=>(int)$fileRow['doc_id']);
				$result = getTableInfo($db_dept, $cabinet, array(), $whereArr);
				$row = $result->fetchRow();
				$path = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/{$row['location']}");
				if(isset($fileRow['subfolder']) and $fileRow['subfolder']) {
						$path = $path."/".$fileRow['subfolder'];
				}
				$file = $path ."/".$fileRow['filename'];
				$encFileData = file_get_contents ($file);
				$depID = str_replace('client_files', '', $department);
				$db_dept->disconnect ();
				return "[$depID-$cabinetID-$fileID]".$fileRow['filename'];
		} else {
			$db_dept->disconnect ();
			return false;
		}
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function checkInFile ($userName, $department, $cabinetID, $fileID, $fileName, $encFileData, $db_doc)
{
	$db_dept = getDbObject ($department);
	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);
	if ($cabinet !== false) {
		$parentID = getParentID($cabinet, $fileID, $db_dept);
		//$fileID = getRecentID($cabinet, $parentID, $db_dept);
		$who = whoLocked($cabinet, $parentID, $db_dept);
		// Get information for the file name if check out for writing
		if($who == $userName) {
			$fileArr = getCheckInDetails ($cabinet, 
				$parentID, $db_dept, $userName, 
				$fileName);
			if (file_put_contents ($fileArr['path'], 
				$encFileData)) {

				$fileArr['notes'] = getFileNotes($cabinet, $fileID, $db_dept);
				$user = new user ();
				$user->username = $userName;
				$user->db_name = $department;
				$rtn= checkInVersion ($db_dept, $fileArr, 
					$cabinet, $parentID, $user, $db_doc);
				$db_dept->disconnect ();
				return $rtn; 
			} else {
				$db_dept->disconnect ();
				return false;
			}
		} else {
			$db_dept->disconnect ();
			return false;
		}
	} else {
		$db_dept->disconnect ();
		return false;
	}
}

function updateDocumentIndices ($userName, $department, $cabinetID, $docID, $tabID, $updatedIndices)
{
	$db_dept = getDbObject ($department);
	$db_doc = getDbObject ("docutron");
	$cabinet = hasAccess ($db_dept, $userName, $cabinetID);
	if ($cabinet !== false) {
		$enArr = array (
			'subfolderID'	=> $tabID,
			'cabinet'	=> $cabinet,
			'doc_id'	=> $docID,
			'field_count'	=> count ($updatedIndices),
			'subfolder'	=> '',
			'new_subfolder'	=> '',
		);
		$i = 0;
		foreach ($updatedIndices as $myKey => $myValue) {
			$enArr['key'.$i] = $myKey;
			$enArr[$myKey] = $myValue;
			$i++;
		}
		updateDocumentFields($enArr,$db_doc,$db_dept, true);
		//updateDocumentFields ($enArr, $department, true);
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return true;
	} else {
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return false;
	}
}

if (!function_exists ('file_put_contents')) {
	function file_put_contents($filename, $data)
	{
	    $f = fopen($filename, 'w+');
		if (!$f) {
			return false;
		}
		if (!fwrite($f, $data)) {
			return false;
		}
	    fclose($f);
		return true;
	}
}

//Make sure the folder exists
function validateFolder($department, $cabinet, $docID, $db_dept=null)
{
	$disconnect=0;
	if($db_dept == null) {
		$db_dept = getDBObject($department);
		$disconnect = 1;
	}
	$doc_id = getTableInfo($db_dept, $cabinet, array('doc_id'), array('doc_id' => $docID), 'queryOne');
	if ($disconnect==1)	$db_dept->disconnect ();
	if( ($doc_id > 0) AND (is_numeric($doc_id)) ) {
		return true;
	} else {
		return false;
	}
}

//Moves a document/tab from one location to another
//Only allows moves within one department 
function moveDocumentBetweenCabs($userName, $department, $cabinetID, $docID, $subfolderID, $destCabID, $destDocID, $copy=true, $documentObj=null)
{
	global $DEFS;
	$db_dept = getDbObject($department);
	$db_doc = getDbObject('docutron');
	$cabinet = hasAccess($db_dept, $userName, $cabinetID);
	$destCabinet = hasAccess($db_dept, $userName, $destCabID);
	if($cabinet === false OR $destCabinet === false) {
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return "Cabinet access denied";
	}

	//If the folder does not exist, return error message
	if( !validateFolder($department, $cabinet, $docID, $db_dept) ) {
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return "Source folder does not exist";
	}
	if( !validateFolder($department, $destCabinet, $destDocID, $db_dept) ) {
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return "Destination folder does not exist";
	}

	//Builds the document object
	if($documentObj == null) {
		$documentObj = new documentObj($department, $cabinet, $docID, $subfolderID, $db_dept);
	}

	$destSubfolderID;
	//Creates a new subfolder in the database
	if($documentObj->isDocument) {
		$destSubfolderName=$documentObj->subfolderName;
		$destSubfolderID = createTabForDocument($db_dept, $department, $destCabinet, $destDocID, 
			$documentObj->documentType, $destSubfolderName, $db_doc, false);
	} else {
		$destSubfolderID = createSubfolder( $userName, $department, $destCabID, $destDocID, 
			$documentObj->subfolderName, $db_doc, false );
	}
	if($destSubfolderID > 0)
	{
		$destObj = new documentObj($department, $destCabinet, $destDocID, $destSubfolderID, $db_dept);
	}
	
	$cur_location = $DEFS['DATA_DIR']."/".str_replace(" ","/",getFolderLocation($db_dept,$cabinet,$docID))."/".$documentObj->subfolderName;
	$dest_location = $DEFS['DATA_DIR']."/".str_replace(" ","/",getFolderLocation($db_dept,$destCabinet,$destDocID)."/".$destObj->subfolderName);

	if($copy) {
		$stuff=copyDocument($db_dept, $userName, $documentObj, $cur_location, $dest_location, 
			$destCabinet, $destDocID, $destSubfolderID, $destObj->subfolderName);
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return $stuff;
	} else {
		$stuff = cutDocument($db_dept, $documentObj, $cur_location, $dest_location, $destCabinet, 
			$destDocID, $destSubfolderID, $destObj->subfolderName);
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return $stuff;
	}
}

//Copies the document from one location to another
//Helper function for moveDocumentBetweenCabs()
//Returns true is completed successfully
function copyDocument($db_dept, $userName, $documentObj, $cur_location, $dest_location, 
	$destCabinet, $destDocID, $destSubfolderID, $destSubfolderName) {
	
	$retMessage = "";
	global $DEFS;
	if( copyDir($cur_location, $dest_location) ) {
		allowWebWrite ($dest_location, $DEFS);

		if($documentObj->isDocument) {
			$insertDocument = array(
				'cab_name' 		=> $destCabinet,
				'doc_id' 		=> $destDocID,
				'file_id'		=> $destSubfolderID,
				'date_created'	=> date('Y-m-d G:i:s'),
				'date_modified'	=> date('Y-m-d G:i:s'),
				'created_by'	=> $userName,
				'deleted'		=> 0
			);
			lockTables($db_dept, array($documentObj->documentTableName));
			//insert into the documents table
			$res = $db_dept->extended->autoExecute($documentObj->documentTableName, $insertDocument);
			$destDocumentID = getTableInfo($db_dept, $documentObj->documentTableName, array('max(id)'), array(), 'queryOne');
			unlockTables($db_dept);

			//update the $document_field_value_list
			$documentValueList = $documentObj->documentFieldValues;
			foreach($documentValueList AS $row) {
				unSet($row['id']);
				unset($row['timestamp']);
				$row['document_id'] = $destDocumentID;
				if($row['document_field_value'] == null) {
					unSet($row['document_field_value']);
				}
				$res = $db_dept->extended->autoExecute('document_field_value_list', $row);
				dbErr($res);
			}

			//update the new document info to the $cabinet_files table	
			updateTableInfo($db_dept, $destCabinet."_files", 
				array('document_table_name' => $documentObj->documentTableName, 'document_id' => $destDocumentID),
				array('id' => $destSubfolderID)
			);
		}

		//update $cabinet_files table for the new files	
		$filesArr = $documentObj->filesArr;
		moveFilesInDB($db_dept, $documentObj->cabinet, $destCabinet, $destDocID, $filesArr, $destSubfolderName, true);
		$retMessage = true;
	} else {
		$retMessage = "Document/Tab failed to copy";
	}
	return $retMessage;
}

//Cuts the given document and puts into a different location
//Helper function for moveDocumentBetweenCabs()
//Returns true if completed successfully
function cutDocument($db_dept, $documentObj, $cur_location, $dest_location, $destCabinet, $destDocID, $destSubfolderID, $destSubfolderName) {
	$retMessage = "";
	//if moving the files succeed
	if(file_exists($cur_location)){
		if( rename("$cur_location", "$dest_location") ) {
			if($documentObj->isDocument) {
				//update $cabinet_files to the new document settings
				updateTableInfo($db_dept, $destCabinet."_files",
					array('document_table_name' => $documentObj->documentTableName, 'document_id' => $documentObj->documentID),
					array('id' => $destSubfolderID)
				);
				//update documents table to reflect the new owner of the document
				updateTableInfo($db_dept, $documentObj->documentTableName,
					array('cab_name' => $destCabinet, 'doc_id' => $destDocID, 'file_id' => $destSubfolderID),
					array('id' => $documentObj->documentID)
				);
			}

			//move the subfolder files from one cabinet db table to another table
			$filesArr = $documentObj->filesArr;
			moveFilesInDB($db_dept, $documentObj->cabinet, $destCabinet, $destDocID, $filesArr, $destSubfolderName, false);
			//delete the old document entry in the cabinet files table
			deleteTableInfo($db_dept, $documentObj->cabinet."_files", array('id' => $documentObj->subfolderID));
			$retMessage = true;
		} else {
			$retMessage = "Document/Tab failed to move";
		}
	}else{
		$retMessage = 'Location does not exist '.$cur_location;
		error_log($retMessage);
	}
	return $retMessage;
}

//Moves the subfolder file entries from $cabinet_files table to another destination $cabinet_files
//Only handles the database entries and not the actual physical move
function moveFilesInDB($db_dept, $cabinet, $destCabinet, $destDocID, $filesArr, $destSubfolderName=NULL, $copy=true)
{
	foreach($filesArr AS $index => $fileInfo) {
		$fileID = $fileInfo['id'];
		unSet($fileInfo['id']);
		unSet($fileInfo['timestamp']);
		$fileInfo['doc_id'] = $destDocID;
		if($destSubfolderName != NULL) {
			$fileInfo['subfolder'] = $destSubfolderName;
		}
		foreach($fileInfo AS $index => $value) {
			if($fileInfo[$index] == null) {
				unSet($fileInfo[$index]);
			}
		}

		$res = $db_dept->extended->autoExecute($destCabinet."_files", $fileInfo);
		dbErr($res);
		if( !$copy ) {
			deleteTableInfo($db_dept, $cabinet."_files", array('id' => $fileID));
		}
	}
}

//Moves the files in the main tab
//Handles the files on the disk and the database move
//Returns true if completed successfully
function moveMainSubfolderFiles($db_dept, $cabinet, $docID, $destCabinet, $destDocID, $mainFilesArr, $copy=true)
{
	global $DEFS;
	$cur_location = $DEFS['DATA_DIR']."/".str_replace(" ","/",getFolderLocation($db_dept,$cabinet,$docID));
	$dest_location = $DEFS['DATA_DIR']."/".str_replace(" ","/",getFolderLocation($db_dept,$destCabinet,$destDocID));
	foreach($mainFilesArr AS $filesRow) {
		$filename = $filesRow['filename'];
		if($copy) {
			if(is_file($cur_location."/".$filename)  && copy($cur_location."/".$filename, $dest_location."/".$filename)) {
				moveFilesInDB($db_dept, $cabinet, $destCabinet, $destDocID, array($filesRow), NULL, $copy);
			} else {
				return $cur_location."/"."$filename failed to be copied from the Main tab";
			}
		} else {
			if(is_file($cur_location."/".$filename)  && rename($cur_location."/".$filename, $dest_location."/".$filename)) {
				moveFilesInDB($db_dept, $cabinet, $destCabinet, $destDocID, array($filesRow), NULL, $copy);
			} else {
				return $cur_location."/"."$filename failed to be moved from the Main tab";
			}
		}
	}
	return true;
}

//Move the given folder to a different cabinet within the same department
//Maintains all data within the folder including documents, tabs, and files
//$copy parameter to true to leave a copy of the folder behind, set to false to cut
//Returns the new doc_id if completed successfully
function moveFolderBetweenCabs($userName, $department, $cabinetID, $docID, $destCabID, $copy=true)
{
	$db_dept = getDbObject($department);
	$db_doc = getDbObject('docutron');
	$cabinet = hasAccess( $db_dept, $userName, $cabinetID );
	$destCabinet = hasAccess( $db_dept, $userName, $destCabID );
	if($cabinet === false OR $destCabinet === false) {
		return "Cabinet access denied";
	}
	$folderRow = getCabIndexArr($docID, $cabinet, $db_dept);
	$indices = getCabinetInfo($db_dept, $cabinet);
	$destIndices = array_keys($folderRow);
	$diffArr = array_diff($destIndices, $indices);
	//Verify that the cabinet indices match, or fail the move
	if( sizeof($diffArr) > 0 ) {
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return "Cabinet indices do not match to move folder";
	}

	$folderObj = new folderObj($department, $cabinetID, $docID, $db_dept);
	$newDocID =	createCabinetFolder($department, $destCabID, $folderRow, $userName, $db_doc, $db_dept);

	//Move the files in the main tab
	$mainFilesMove = moveMainSubfolderFiles($db_dept, $cabinet, $docID, $destCabinet, 
		$newDocID, $folderObj->mainFilesArr, $copy);
	if( $mainFilesMove !== true ) {
		//Return error message if operation did complete successfully
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return $mainFilesMove;
	}

	//Move the subfolders and the files in the folder
	foreach($folderObj->documentObjArr AS $documentObj) {
		$documentMove = moveDocumentBetweenCabs($userName, $department, $cabinetID, $docID, $documentObj->subfolderID, 
			$destCabID, $newDocID, $copy, $documentObj);
		if( $documentMove !== true ) {
			//Return error message if operation did complete successfully
			$db_dept->disconnect ();
			$db_doc->disconnect ();
			return $documentMove;
		}
	}

	if( !$copy ) { 
		deleteTableInfo($db_dept, $cabinet, array('doc_id' => $docID));
	}
	$db_dept->disconnect ();
	$db_doc->disconnect ();
	return $newDocID;
}

//Creates a zip of the given temp_table
//Returns the filename if zip was successful
//Return false if there was an error
function zipSearchResults($userName, $department, $cabinetID, $tempTable, $fileName)
{
	global $DEFS;
	$db_dept = getDbObject($department);
	$db_doc = getDbObject('docutron');

	$cabinet = hasAccess( $db_dept, $userName, $cabinetID );
	if($cabinet === false) {
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return "Cabinet access denied";
	}

	$tempFileTable = createTemporaryTable($db_dept);
	$results = getTableInfo($db_dept, 
		array($tempTable, $cabinet.'_files'), 
		array($cabinet.'_files.id'),
		array($tempTable.'.result_id='.$cabinet.'_files.doc_id', $cabinet.'_files.deleted=0', 'display=1', 'filename IS NOT NULL'),
		'queryCol'
	);
	foreach($results AS $fileID) {
		$entry = array("result_id" => (int)$fileID);
		$res = $db_dept->extended->autoExecute($tempFileTable,$entry);
		dbErr($res);
	}

	$key = "docDaemon_execute";
	$fileName = $fileName."_".date('Y-m-d_H-i-s');
	$fileName = str_replace(":", "_", $fileName);
	$fileName = str_replace("/", "_", $fileName);
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$value = "php -q ".$DEFS['DOC_DIR']."/bots/buildZipDaemon.php $department $cabinet $tempFileTable $fileName";
	} else {
		$value = "nice -17 php -q ".$DEFS['DOC_DIR']."/bots/buildZipDaemon.php $department $cabinet $tempFileTable $fileName";
	}
	$insertArr = array(
		"k"				=> $key,
		"value"			=> $value,
		"department"	=> $department
	);
	$res = $db_doc->extended->autoExecute("settings",$insertArr);
	dbErr($res);

	$db_dept->disconnect ();
	$db_doc->disconnect ();
	return $fileName.".zip";
}

//Creates a zip of the given doc_ids for the given cabinet
//Returns the filename and filesize in an associative array if zip was successful
//Return false if there was an error
function zipFileIDArray($userName, $department, $cabinetID, $fileIDArray, $fileName)
{
	global $DEFS;
	$db_dept = getDbObject($department);
	$db_doc = getDbObject('docutron');

	$cabinet = hasAccess( $db_dept, $userName, $cabinetID );
	if($cabinet === false) {
		$db_dept->disconnect ();
		$db_doc->disconnect ();
		return "Cabinet access denied";
	}

	$tempFileTable = createTemporaryTable($db_dept);
	foreach($fileIDArray AS $fileID) {
		$entry = array("result_id" => (int)$fileID);
		$res = $db_dept->extended->autoExecute($tempFileTable,$entry);
		dbErr($res);
	}

	$key = "docDaemon_execute";
	$fileName = $fileName."_".date('Y-m-d_H-i-s');
	$fileName = str_replace(":", "_", $fileName);
	$fileName = str_replace("/", "_", $fileName);
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$value = "php -q ".$DEFS['DOC_DIR']."/bots/buildZipDaemon.php $department $cabinet $tempFileTable $fileName";
	} else {
		$value = "nice -17 php -q ".$DEFS['DOC_DIR']."/bots/buildZipDaemon.php $department $cabinet $tempFileTable $fileName";
	}
	$insertArr = array(
		"k"				=> $key,
		"value"			=> $value,
		"department"	=> $department
	);
	$res = $db_doc->extended->autoExecute("settings",$insertArr);

	$db_dept->disconnect ();
	$db_doc->disconnect ();
	return $fileName.".zip";
}

//Generates a list of zip files created for the given department
//Returns an array of filenames and filesizes in an associative array
//Returns an empty array if there are no zip files
function listZipFiles($department, $cabinetID, $userName)
{
	global $DEFS;
	$retArr = array();
	$db_dept = getDbObject($department);

	$cabinet = hasAccess( $db_dept, $userName, $cabinetID, false);
	if($cabinet === false) {
		return "Cabinet access denied";
	}

	$zipDir = $DEFS['DATA_DIR']."/$department/zipTemp/$cabinet";
	if( !file_exists($zipDir) ) {
		makeAllDir($zipDir);
	}

	if( $dh = opendir($zipDir) ) {
		while( false !== ($file = readdir($dh)) ) {
			if($file != "." AND $file != "..") {
				$filePath = $zipDir."/".$file;
				$fileType = getMimeType($filePath, $DEFS);
				$fileExt = getExtension($filePath);
				$fileSize = filesize($filePath);
				if($fileType == "application/x-zip" 
					AND strcmp($fileExt, "zip") == 0 ) {
					$zipInfo = array(	'fileName' 	=> $file,
										'fileSize'	=> (double)$fileSize
					);
					$retArr[] = $zipInfo;
				}
			}
		}
		closedir($dh);
	}
	$db_dept->disconnect ();
	return $retArr;
}

//Deletes the given filename from the zip directory
//Returns true on success
//Returns error message if delete zip failed
function deleteZipFile($department, $cabinetID, $zipFileName, $userName)
{
	global $DEFS;
	$db_dept = getDbObject($department);

	$cabinet = hasAccess( $db_dept, $userName, $cabinetID );
	if($cabinet === false) {
		return "Cabinet access denied";
	}

	$zipDir = $DEFS['DATA_DIR']."/$department/zipTemp/$cabinet";
	if( !file_exists($zipDir) ) {
		makeAllDir($zipDir);
	}

	//strips the filename of any invalid directories
	$zipFileName = basename($zipFileName);
	if( file_exists($zipDir."/".$zipFileName) ) {
		unlink($zipDir."/".$zipFileName);
	} else {
		$db_dept->disconnect ();
		return "File does not exist";
	}
	$db_dept->disconnect ();
	return true;
}

//Returns the list of directories that are in the import directory
//Checks that the user has RW access to the import cabinet
function getImportDirList($userName, $department) {
	global $DEFS;
	if($department == NULL OR $department == '') {
		$department = "client_files";
	}
/*	$cabinetID = $DEFS['IMPORT_CABINET_ID'];
	$db_dept = getDBObject($department);
	$cabinet = hasAccess( $db_dept, $userName, $cabinetID );
	if($cabinet === false) {
		return false;
	}
*/
	$retArr = array();
	$path = $DEFS['DATA_DIR']."/".$department."/import";
	//Create the import directory if it does not exist
	if( !file_exists($path) ) {
		makeAllDir($path);
		allowWebWrite($path, $DEFS, 0777);
	} else {
		$dh = opendir( $path );
		$dirArr = listDir( $path );
		foreach($dirArr AS $dir) {
			if( is_dir($path."/".$dir) ) {
				$retArr[] = $dir;
			}
		}
	}
	return $retArr;
}

function importDirectory($userName, $department, $cabinetID, $opCabinetID, $opDocID, $maxFileSize, $fileExtensions, $generateTemplate, $postAction) {
	global $DEFS;
	if($department == NULL OR $department == '') {
		$department = "client_files";
	}

	$db_dept = getDBObject($department);
	$db_doc = getDBObject("docutron");
	$cabinet = hasAccess( $db_dept, $userName, $cabinetID );
	if($cabinet === false) {
		return array(false, "Error: Cabinet access denied");
	}
	$opCabinet = hasAccess( $db_dept, $userName, $opCabinetID );
	if($opCabinet === false) {
		return array(false, "Error: Operational cabinet access denied");
	}

	$directory = getDirName($db_dept, $opCabinet, $opDocID);
	$path = $DEFS['DATA_DIR']."/".$department."/import/".$directory;
	if($directory == NULL OR $directory == "") {
		return array(false, "Error: Directory $directory does not exist");
	//Return error message if the given path does not exist
	} elseif( !file_exists($path) ) {
		return array(false, "Error: Path $path does not exist");
	} elseif( $generateTemplate ) {
		updateStatus($db_dept, $opCabinet, $opDocID, "pending");
		$retXML = generateTemplate($department, $cabinet, $cabinetID, $directory, $userName, $maxFileSize, $fileExtensions);
		return array(true, "$retXML");
	} else {
		$indexArr = array(
			"id INT DEFAULT 0",
			"extensions VARCHAR(255)"
		);
		$tempTable = createDynamicTempTable($db_dept, $indexArr);
		foreach($fileExtensions AS $ext) {
			$entry = array("extensions" => $ext);
			$res = $db_dept->extended->autoExecute($tempTable,$entry);
			dbErr($res);
		}

		updateStatus($db_dept, $opCabinet, $opDocID, "processing");
		$key = "docDaemon_execute";
		$cmd = "";
		if (substr (PHP_OS, 0, 3) != 'WIN') {
			$cmd = "nice -17 ";
		}

		$cmd .= "php -q ".$DEFS['DOC_DIR']."/canada/importBot.php $department $cabinet $cabinetID $directory $userName ";
		$cmd .= "$maxFileSize $tempTable $opCabinet $opCabinetID $opDocID $postAction";

		$insertArr = array(
			"k"				=> $key,
			"value"			=> $cmd,
			"department"	=> $department
		);
		$res = $db_doc->extended->autoExecute("settings",$insertArr);

		return array(true, "Directory $directory successfully scheduled to be imported");
	}
}

function filenameAdvSearch($userName, $department, $cabinetID, $filename) {
	$db_dept = getDbObject($department);
	$cabinet = hasAccess( $db_dept, $userName, $cabinetID, false );
	if($cabinet === false) {
		return "Cabinet access denied";
	}

	$context = NULL;
	$subfolder = NULL;
	$date = NULL;
	$date2 = NULL;
	$who = NULL;
	$notes = NULL;
	$contextbool = false;

	$tmpUsr = new user();
	$tmpUsr->username = $userName;
	$tmpUsr->db_name = $department;

	$search = new fileSearch($tmpUsr);
	$search->findFile($cabinet, array("'%".$filename."%'"), $context, $subfolder, $date, $date2, $who, $notes, $contextbool);
	$tempTable = $search->tempTableName;
	$numResults = $search->numResults;
	return array($tempTable, (int)$numResults);
}

function getFileResultSet($department, $cabinetID, $resultID, $startIndex, $numberToFetch, $userName) {
	$db_dept = getDbObject($department);
	$cabinet = hasAccess($db_dept, $userName, $cabinetID, false);
	if($cabinet === false) {
		return false;
	}

	$res = getTableInfo($db_dept,
		array($cabinet."_files", $resultID),  //table
		array('id', 'filename', 'file_size', $cabinet.'_files.doc_id'),  // SELECT columns
		array($cabinet.'_files.doc_id='.$resultID.'.doc_id', 'deleted=0', $cabinet.'_files.id='.$resultID.'.result_id'), //WHERE
		'getAssoc', // type of query
		array(), //ordering
		$startIndex, //limit
		$numberToFetch //count
	);
	return $res;
}

function isPublishUser($department,$email) {
	$db_doc = getDbObject('docutron');

	$sArr = array('COUNT(id)');
	$wArr = array('email' => $email,
				'department' => $department);
	$ct = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'queryOne');
	if($ct) {
		return true;
	}
	return false;
}

function addPublishUser($department,$email,$upload,$publish) {
	$pubUserObj = new publishUser();
			
	$flag = 0;
	if($upload) {
		$flag += 1;
	}

	if($publish) {
		$flag += 2;
	}

	if($pubUserObj->setPublishUser($email,$department,$flag)) {
		return $pubUserObj->id;
	}
	return 0;
}
//type is either workflow, upload or folder_search
function addPublishSearch($department,$name,$enabled,$expireTime=NULL,$cabinet,$field="",$search="",$username,$type='folder_search',$wf_def_id=0) {
	$pubSearchObj = new publishSearch($username);
	$pubSearchObj->setPublishName($name,$enabled);
	if($expireTime) {
		$pubSearchObj->setExpireTime($expireTime);
	}
	$pubSearchObj->addItemToList($department,$cabinet,0,0,$field,$search,$wf_def_id,$type);
	if($pubSearchObj->id) {
		return $pubSearchObj->id;
	}
	return false;
}

function getPublishSearchList($department) {
	$db_doc = getDbObject('docutron');

	$sArr = array('publish_search.id','name','cab','doc_id','file_id','field','term');
	$tArr = array('publish_search','publish_search_list');
	$wArr = array(  'publish_search.ps_list_id = publish_search_list.ps_list_id',
					"type = 'folder_search'",
					"publish_search_list.department = '".$department."'");
	$pubSearchList = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc');
	return $pubSearchList;
}

function getPublishUserList($department) {
	global $DEFS;

	$db_doc = getDbObject('docutron');

	$sArr = array('id','email');
	$wArr = array('status' => 'active');
	if(!isSet($DEFS['PORTAL_MDEPS']) || $DEFS['PORTAL_MDEPS'] != 1) {
		if($department) {
			$wArr['department'] = $department;
		}
	}
	$pubUserList = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'getAssoc');
	if(count($pubUserList)) {
		return $pubUserList;
	}
	return array();
}

function bindPublishSearchWithUser($uid,$sid,$username) {
	$pubSearch = new publishSearch($username,$sid);
	$pubSearch->addToUserList($uid,$username);
	return true;
}

function addWorkflowHistoryAudit($dep, $uname, $wf_document_id, $action, $notes) {
	$db_dept = getDbObject($dep);
	$arr = getCurrentWFNodeInfo($db_dept, $wf_document_id);
	if(is_array($arr)) {
		list($wf_node_id, $state) = array_values($arr);
		$insertArr = array(	"wf_document_id"	=> (int)$wf_document_id,
							"wf_node_id"		=> (int)$wf_node_id,
							"action"			=> $action,
							"username"			=> $uname,
							"date_time"			=> date('Y-m-d H:i:s'),
							"state"				=> (int)$state,
							"notes"				=> $notes );
		$res = $db_dept->extended->autoExecute('wf_history',$insertArr);
		dbErr($res);
		return true;
	}
	return false;
}

function getADPFileList($department,$cabinetID,$contract_number,$ccan_number) {
		$db_dept = getDbObject ($department);
		$query="select id, ADPCL_20100525.doc_id as docid,file_size,parent_filename,subfolder,filename from ADPCL_20100525,ADPCL_20100525_files where display=1 and filename is not NULL and ADPCL_20100525.doc_id=ADPCL_20100525_files.doc_id";
		$query=$query." and subfolder!='Canada AutoDebit - English'";
		$query=$query." and subfolder!='Canada AutoDebit - French'";
		$query=$query." and subfolder!='Equipment Release Form'";
		$query=$query." and subfolder!='Funding Sheet'";
		$query=$query." and subfolder!='SECURITY AGREEMENT'";
		$query=$query." and subfolder!='Third Party Payoff Agreement'";
		$query=$query." and subfolder!='US AutoDebit'";
// if there was not a number sent ignore it
		if ($ccan_number) $query=$query." and ccan_number='".$ccan_number."'";
		if ($contract_number) $query=$query." and contract_number='".$contract_number."'";
		$results=$db_dept->queryAll($query);
		$db_dept->disconnect ();
		return $results;
}

function getDetailedWorkflowDefinitions($dep,$wf_def_id) {
	$db_dept = getDbObject($dep);

	$sArr = array('defs_name');
	$wArr = array('id' => $wf_def_id);
	$dname = getTableInfo($db_dept,'wf_defs',$sArr, $wArr,'queryOne');

	$tArr = array('wf_defs','wf_nodes');
	$sArr = array('wf_nodes.id','node_name');
	$wArr = array("defs_name ='$dname'", 
				'node_id=wf_nodes.id');
	$wfList = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryAll');

	if(count($wfList)) {
		return $wfList;
	}
}

function workflowAccept($dep,$uname,$wf_doc_id,$wf_node_id) {
	$db_doc = getDbObject('docutron');
	$db_dept = getDbObject($dep);
	$sArr = array('cab','doc_id','file_id','state_wf_def_id');
	$wArr = array('id' => $wf_doc_id);
	$wfInfo = getTableInfo($db_dept,'wf_documents',$sArr,$wArr,'queryRow');

	$sArr = array('departmentname');
	$wArr = array('real_name' => $wfInfo['cab']);
	$cabDispName = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryOne');

	$nodeObj = new customNode($db_dept,$dep,$uname,$wf_doc_id,$wfInfo['state_wf_def_id'],
					$wfInfo['cab'],$cabDispName,$wfInfo['doc_id'],$db_doc,$wfInfo['file_id']);
	$nodeObj->accept($wf_node_id);
}

function workflowReject($dep,$uname,$wf_doc_id,$wf_node_id, $notes = null) {
	$db_doc = getDbObject('docutron');
	$db_dept = getDbObject($dep);
	$sArr = array('cab','doc_id','file_id','state_wf_def_id');
	$wArr = array('id' => $wf_doc_id);
	$wfInfo = getTableInfo($db_dept,'wf_documents',$sArr,$wArr,'queryRow');

	$sArr = array('departmentname');
	$wArr = array('real_name' => $wfInfo['cab']);
	$cabDispName = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryOne');

	$nodeObj = new customNode($db_dept,$dep,$uname,$wf_doc_id,$wfInfo['state_wf_def_id'],
					$wfInfo['cab'],$cabDispName,$wfInfo['doc_id'],$db_doc,$wfInfo['file_id']);
					
	if($notes) { $nodeObj->addToWFHistory('Rejection Notice', $notes); }
	
	$nodeObj->reject();
}

function copyFileToDownloadDir($fileLocation, $filename, $downloadDir)
{
	if(!file_exists($fileLocation))
	{
		return "File not found";	
	} 
	$host = $_SERVER['HTTP_HOST'];
	$root = $_SERVER['DOCUMENT_ROOT'];
	if(!is_dir("$root/$downloadDir"))
		mkdir("$root/$downloadDir", 0777);
	
	

}
?>
