<?php
include_once '../centera/centera.php';
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/versioning.php';
include_once '../lib/redaction.php';
include_once '../lib/settings.php';
include_once '../modules/modules.php';

if($logged_in==1 && strcmp($user->username,"")!=0) {
	$cab = $_GET['cab'];	
	if (isset ($_GET['doc_id'])) {
		$doc = $_GET['doc_id'];//doc is the department
	} else {
		$doc = '';
	}
	if (isset ($_GET['ID'])) {
		$ID = $_GET['ID'];
	} else {
		$ID = '';
	}
	$fileID = $_GET['fileID'];
	if (isset ($_GET['tab'])) {
		$tab = $_GET['tab'];
	} else {
		$tab = '';
	}
	if (isset ($_GET['download'])) {
		$download = $_GET['download'];
	} else {
		$download = '';
	}
	if($user->db_name) {
		$doDisconnect = false;
		$department = $user->db_name;
	} else {
		$doDisconnect = true;
		$db_object = getDbObject($user->department);
		$department = $user->department;
	}

	if (isset($_GET['filename'])) {
		$filename = $_GET['filename'];
	} else {
		$filename = ''; 
	}
	$realFilename = '';
	if($tab == "main") unset($tab);
	if(isset($_GET['delete'])) {
		$delete = $_GET['delete'];
	} else {
		$delete = '';
	}

	if(!$delete) {
		if(!$fileID) {
			if($tab == "")
				$tab = "main";
			$whereArr = array(
				"doc_id"		=> (int)$doc,
				"ordering"		=> (int)$ID,
				"display"		=> 1,
				"deleted"		=> 0,
				'filename'		=> 'IS NOT NULL'
					 );
			if(!empty($tab) and strtolower($tab) != "main") {
				$whereArr['subfolder'] = $tab;
			} else {
				$whereArr['subfolder'] = 'IS NULL';
			}
			$fInfo = getTableInfo($db_object,$cab."_files",array(),$whereArr);
			$row = $fInfo->fetchRow();
			$fileID = $row['id'];
		} else {
			$row = getTableInfo($db_object, $cab.'_files', array(), array('id' => (int) $fileID), 'queryRow');
		}
		$filename = $row['filename'];
		$tab = $row['subfolder'];
		$realFilename = $row['parent_filename'];
		$ca_hash = $row['ca_hash'];
		$fsize = $row['file_size'];
		$whereArr = array('doc_id'=>(int)$row['doc_id']);
		$res = getTableInfo($db_object,$cab,array(),$whereArr);
		$row = $res->fetchRow();
		if(!$row) {
			die('FILE DOES NOT EXIST');
		}
		$path = str_replace(" ", "/", "{$DEFS['DATA_DIR']}/".$row['location']);
		if(check_enable('redaction', $department)) {
			if(checkRedaction($db_object, $db_doc, $cab, $fileID, $department, $DEFS['DATA_DIR']) != '') {
				if($user->checkSetting('viewNonRedact', $cab)) {
					$filename .= '.adminRedacted';
				}	
			}
		}
	}

	if( $user->checkSecurity($cab)==0) {
		$user->audit("ILLEGAL ACCESS","Page: $path/$filename");
		die( "FILE DOES NOT EXIST" );
	}
	if( check_enable('centera', $user->db_name )){
		if(isSet($DEFS['CENT_HOST'])) {
			centget( $DEFS['CENT_HOST'], $ca_hash, $fsize, "$path/$tab/$filename", $user, $cab ); 
		}
	}
	if( file_exists( $path."/".$tab.'/'.$filename )) {
		if($delete and $download) {
			$user->audit("downloaded page","Page: $path/$filename",$db_object);
			downloadFile($path."/".$tab, $filename, true, true, $realFilename);
		} else if(!$delete and $download) {
			$user->audit("viewed page","Page: $path/$filename",$db_object);
			downloadFile($path."/".$tab, $filename, true, false, $realFilename);
		} else if($delete and !$download) {
			$user->audit("viewed page","Page: $path/$filename",$db_object);
			downloadFile($path."/".$tab, $filename, false, true, $realFilename);
		} else {
			$user->audit("viewed page","Page: $path/$filename",$db_object);
			downloadFile($path."/".$tab, $filename, false, false, $realFilename);
		}
	} else {
		echo "File Does Not Exist <br>$path/$tab/$filename";
	}	
	$db_object->disconnect ();
	setSessionUser($user);
} else{
	logUserOut();
}
?>
