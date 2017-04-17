<?php

include_once '../check_login.php';
include_once ('../classuser.inc');
include_once 'energiefuncs.php';
include_once '../secure/tabChecks.php';
include_once '../lib/fileFuncs.php';
include_once '../lib/audit.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	//translations
	$tabExists = $trans['Tab Already Exists'];
	$tabWarning = $trans['Tab Contains Files'];
	$cantDelete = $trans['Cannot Delete'];

	$cab = $_GET['cab'];
	$doc_id = $_GET['doc_id'];
	$ID = $_GET['ID'];
	$tab = $_GET['tab'];
	$temp_table = $_GET['table'];
	$index = $_GET['index'];
	$location = getTableInfo($db_object,$cab,array('location'),array('doc_id'=>(int)$doc_id),'queryOne');	
	$location = str_replace(" ", "/", $location);
	$location = $DEFS['DATA_DIR']."/".$location."/";
	$indexArray = getCabIndexArr($doc_id, $cab, $db_object);
	$indexArray = implode(' ',$indexArray);

	if (isset ($_POST['edit'])) {
		//////////HACK FOR NOW
		$gblStt = new GblStt($user->db_name, $db_doc);
		$whereArr = array('filename'=>'IS NULL','doc_id'=>(int)$doc_id,'display'=>1,'deleted'=>0);
		$orderArr = array('subfolder'=>$gblStt->get('tab_ordering'));
		$tabs = getTableInfo($db_object,$cab."_files",array('DISTINCT(subfolder)'),$whereArr,'query',$orderArr);
		while ($tabList = $tabs->fetchRow()) {
			$tmp = $tabList['subfolder'];
			if ($tmp) {
				$tabname[] = $tmp;
			}
		}
		/////////////////////////
		for ($i = 0; $i < sizeof($tabname); $i++) {
			$tmp = $_POST[$tabname[$i]];
			//removes more than one space/underscore together
			$tmp = $user->parseStr($tmp);

			//error checks tab in tabChecks.php
			$status = tabCheck($tmp, $user);
			if ($status !== false) {
				echo<<<ENERGIE
<script>
    document.onload = parent.mainFrame.window.location = "addTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&error=$status&table=$temp_table&index=$index";
</script>
ENERGIE;
				die();
			}

			if (strcmp($tmp, $tabname[$i]) != 0) {
				$fileLocation = $location.$tmp;
				if (file_exists($fileLocation)) //checks for existing tab name
					{
					$mess = $tabExists;
					echo<<<ENERGIE
      <script>
          document.onload = parent.mainFrame.window.location = "addTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&error=$mess&table=$temp_table&index=$index";
      </script>
ENERGIE;
					die();
				}

				$oldTab = $tabname[$i];
				$newTab = $tmp;
				$from = $location.$tabname[$i];
				$to = $location.$tmp;
				
				$updateArr = array('subfolder' => $newTab);
				$whereArr = array(	'doc_id' => (int)$doc_id,
									'subfolder' => $oldTab);
				updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr);
				rename($from, $to);
				$user->audit("tab edited", "name changed from $oldTab to $newTab in $cab Folder: $indexArray");

				$fromLoc = str_replace($DEFS['DATA_DIR']."/", "", $from);
				$ex = explode("/", $fromLoc);
				$thumbPath = "";
				for ($j = 0; $j < sizeof($ex); $j++) {
					if ($ex[$j] != NULL) {
						if ($ex[$j] == $cab) {
							$thumbPath .= "/thumbs";
						}
						$thumbPath .= "/".$ex[$j];
					}
				}
				if($thumbPath) {
					if (file_exists($DEFS['DATA_DIR'].$thumbPath."/".$oldTab)) {
						delDir($DEFS['DATA_DIR'].$thumbPath."/".$oldTab);
					}
				}
			}
		}
		//this reloads addTab and allThumbs.
		echo<<<ENERGIE
<script> 
document.onload = parent.sideFrame.location = "allthumbs.php?cab=$cab&doc_id=$doc_id&table=$temp_table&index=$index"; 
document.onload = parent.mainFrame.window.location = "addTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&table=$temp_table&index=$index";
</script>
ENERGIE;
	} else { // deletion
		$recBin = true;
		$gblStt = new GblStt($user->db_name, $db_doc);
		if($gblStt->get('deleteRecyclebin') === "0") {
			$recBin = false;
		}

		$userSettings = new Usrsettings($user->username,$user->db_name);
		if($userSettings->get('deleteRecyclebin') === "0") {
			$recBin = false;
		}

		$check = $_POST['tab'];
		$delTab = array();
		for ($i = 0; $i < sizeof($check); $i ++) {
			$delTab[] = $check[$i];
		}

		$folderAuditStr = getFolderAuditStr($db_object, $cab, $doc_id);
		$audit = "Cabinet: ".$cab.", Folder: ".$folderAuditStr;
		for ($i = 0; $i < sizeof($delTab); $i ++) {
			$loc = $location.$delTab[$i];
			$subfolder = $delTab[$i];
			$auditMsg = $audit.", Tab: ".$subfolder . " Folder: $indexArray";
			if($recBin) {
				$updateArr = array(	'deleted' => 1,
									'display' => 0);
				$whereArr = array(	'doc_id'	=> (int)$doc_id,
									'subfolder' => $delTab[$i],
									'filename'	=> 'IS NULL');
				updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr);
				$user->audit("tab marked for deletion", $auditMsg);
			} else {
				$sArr = array('id','filename','file_size');	
				$wArr = array(	'doc_id'	=> (int)$doc_id,
								'subfolder'	=> $subfolder,
								'filename'	=> 'IS NOT NULL' );
				$fileArr = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'getAssoc');
				foreach($fileArr AS $id => $fileInfo) {
					if(file_exists($loc."/".$fileInfo['filename'])) {
						if(unlink($loc."/".$fileInfo['filename'])) {
							$quota += $fileInfo['file_size'];
							deleteTableInfo($db_object,$cab.'_files',array('id' => (int)$id));
						}
					} else {
						deleteTableInfo($db_object,$cab.'_files',array('id' => (int)$id));
					}
				}

				if(is_dir($loc)) {
					if(rmdir($loc)) {
						$quota += 4096;
						$whereArr = array(	'doc_id'	=> (int)$doc_id,
											'subfolder' => $subfolder,
											'filename'	=> 'IS NULL');
						deleteTableInfo($db_object,$cab.'_files',$whereArr);
					}
				}

				$user->audit("tab permanently deleted", $auditMsg);
				if($quota) {
					$uArr = array('quota_used' => 'quota_used-'.(int)$quota);
					$wArr = array('real_department' => $user->db_name);
					updateTableInfo($db_doc,'licenses',$uArr,$wArr,1);
				}
			}
		}
		echo<<<ENERGIE
      <script>
       var tabs = new Array();
ENERGIE;
		for ($i = 0; $i < sizeof($delTab); $i ++) {
			echo<<<ENERGIE
        tabs[$i] = "$delTab[$i]";
ENERGIE;
		}
		echo<<<ENERGIE
       document.onload = parent.mainFrame.window.location = "addTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&table=$temp_table&index=$index";
       document.onload = parent.sideFrame.location = "allthumbs.php?cab=$cab&doc_id=$doc_id&table=$temp_table&index=$index";
      </script>
ENERGIE;
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
