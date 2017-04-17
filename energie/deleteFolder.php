<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';
include_once '../lib/audit.php';

if ($logged_in == 1 && strcmp($user -> username, "") != 0) {
	$cab 			= $_POST['cab'];
	$doc_id 		= $_POST['doc_id'];	
	$temp_table 	= $_POST['temp'];  
	$index 			= $_POST['index'];

	$recBin = true;
	$gblSettings = new GblStt($user->db_name, $db_doc);
	if($gblSettings->get('deleteRecyclebin') === "0") {
		$recBin = false;
	}

	$userSettings = new Usrsettings($user->username,$user->db_name);
	if($userSettings->get('deleteRecyclebin') === "0") {
		$recBin = false;
	}

	$folderAuditStr = getFolderAuditStr($db_object, $cab, $doc_id);
	$auditMsg = "Cabinet: ".$cab.", Folder: ".$folderAuditStr;
	if($recBin) {
		$updateArr = array('deleted'=>1);
		$whereArr = array('doc_id'=>(int)$doc_id);
		updateTableInfo($db_object,$cab,$updateArr,$whereArr);
		$user->audit("folder marked for deletion", $auditMsg);
	} else {
		$quota = 0;
		$tabArr = array();
		$loc = getTableInfo($db_object,$cab,array('location'),array('doc_id'=>(int)$doc_id),'queryOne');	
		$location = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
		
		$sArr = array('id','filename','subfolder','file_size');	
		$wArr = array('doc_id'=>(int)$doc_id);
		$fileArr = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'getAssoc');
		foreach($fileArr AS $id => $fileInfo) {
			if($fileInfo['filename']) {
				$myDelFile = $location . '/' .
					$fileInfo['subfolder'] . '/' .
					$fileInfo['filename'];
				if(file_exists($myDelFile)) {
					if(unlink($myDelFile)) {
						$quota += $fileInfo['file_size'];
						deleteTableInfo($db_object,$cab.'_files',array('id'=>(int)$id));
						$myDelRedactFile = $myDelFile . '.adminRedacted';
						if (file_exists ($myDelRedactFile)) {
							$quota += filesize ($myDelRedactFile);
							unlink ($myDelRedactFile);
						}
					}
				} else {
					deleteTableInfo($db_object,$cab.'_files',array('id'=>(int)$id));
				}
			} else {
				$tabArr[] = $fileInfo['subfolder'];
			}
		}

		foreach($tabArr AS $tab) {
			if(is_dir($location."/".$tab)) {
				if(rmdir($location."/".$tab)) {
					$quota += 4096;
				}
			}
		}

		if(is_dir($location)) {
			if(rmdir($location)) {
				$quota += 4096;
				$whereArr = array('doc_id'=>(int)$doc_id);
				deleteTableInfo($db_object,$cab.'_files',$whereArr);
				deleteTableInfo($db_object,$cab,$whereArr);
			}
		}

		$user->audit("folder permanently deleted", $auditMsg);
		if($quota) {
			$uArr = array('quota_used' => 'quota_used-'.(int)$quota);
			$wArr = array('real_department' => $user->db_name);
			updateTableInfo($db_doc,'licenses',$uArr,$wArr,1);
		}
	}
	deleteTableInfo($db_object,$temp_table,array('result_id'=>(int)$doc_id));

	$realpath = getFolderAuditStr($db_object, $cab, $doc_id);
    $user->audit("deleted folder", "$realpath in Cabinet: $cab, Doc ID: $doc_id");
  	
  	$whereArr = array('cab'=>$cab,'doc_id'=>(int)$doc_id);
  	$idList = getTableInfo($db_object,'wf_documents',array('id'),$whereArr,'queryCol');
	deleteTableInfo($db_object,'wf_documents',$whereArr);

    $whereArr = array('department'=>$user->db_name);
    foreach($idList AS $id) {
        $whereArr['wf_document_id'] = (int)$id;
    	deleteTableInfo($db_doc,'wf_todo',$whereArr);
    }

	$indiceArr = getCabinetInfo( $db_object, $cab );
	$userSettings = new Usrsettings($user->username, $user->db_name);
	$resArray = $userSettings->get('results_per_page');
	if(!$resArray) {
        $resultsPerPage = 25;
    } else {
        $resArray = explode(',', $resArray);
        $resultsPerPage = $resArray[0];
    }
	$start = ($index * $resultsPerPage) + $resultsPerPage - 1;

	$sArr = array('COUNT(result_id)');
	$ct = getTableInfo($db_object,$temp_table,$sArr,array(),'queryOne');
	//must get the next folder to display
	if($ct > $resultsPerPage) {
		$tArr = array($cab, $temp_table);
		$wArr = array(	"$cab.doc_id = result_id", 
				'deleted = 0');
		$oArr = array('result_id' => 'DESC');
		$folderInfo = getTableInfo($db_object,$tArr,array(),$wArr,'queryRow',$oArr,$start,1);

		if($folderInfo) {
			$string = $folderInfo['doc_id']."\t";
			for($i=0;$i<sizeof($indiceArr);$i++) {	
				$indiceName = $indiceArr[$i];
				$resStr = "";
				if($folderInfo[$indiceName]) {
					$resStr = $folderInfo[$indiceName];
				}
				$string .= $resStr."\t";
			}
			echo $string;
		}
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
