<?php
include_once '../check_login.php';
include_once 'tabChecks.php';
include_once '../lib/quota.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$db_object = $user->getDbObject();
	$db_doc = getDbObject('docutron');
	//variables that may need to be translated
	$success = $trans['Tab successfully created'];
	$limit = "This Operation Will Exceed Quota Limit";
	$cab = $_GET['cab'];
	if ($cab == null) {
		echo<<<ENERGIE
		<script>
			document.onload = window.location = "addTabs.php";
		</script>
ENERGIE;
		die();
	}

	$path = $user->db_name."/".$cab;
	$path = $DEFS['DATA_DIR'].'/'.$path;

	$allTabs = array ();
	$nonSavedTabs = array();
	for ($i=0;isset($_POST['tab'.$i]);$i++) {
		//removes more than one space/underscore together
		if($_POST['tab'.$i]) {
			$_POST['tab'.$i] = $user->parseStr($_POST['tab'.$i]);
			//error checks tab in tabChecks.php
			$status = tabCheck($_POST['tab'.$i], $user);
			if ($status !== false) {
			echo<<<ENERGIE
<script>
    	 document.onload = window.location = "addTabs.php?cab=$cab&mess=$status";
</script>
ENERGIE;
			die();
			}		
			if (isset ($_POST['save'.$i])) {
				if (!in_array($_POST['tab'.$i], $allTabs)) {
					$allTabs[] = $_POST['tab'.$i];
				}
			} else {
				$nonSavedTabs[] = $_POST['tab'.$i];
			}
		}
	}
	addTabsToCabinet($cab, $allTabs, $user->db_name);
	$allTabs = array_merge($allTabs, $nonSavedTabs);

	for ($i = 0; $i < sizeof($allTabs); $i ++) {
		$tab = $allTabs[$i];

		//count the number of total folders
		$numFolders = getTableInfo($db_object,$cab,array('COUNT(doc_id)'),array(),'queryOne');
		//counts the number of folders that already have the tab
		$whereArr = array('filename'=>'IS NULL');
		if(strtolower($tab) != 'main') {
			$whereArr['subfolder'] = $tab;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$numFoldersWTabs = getTableInfo($db_object,$cab."_files",array('COUNT(id)'),$whereArr,'queryOne');
		//number of folders without tabs
		$foldersWOTabs = $numFolders - $numFoldersWTabs;

		if (checkQuota($db_doc, $foldersWOTabs * 4096, $user->db_name)) {
			$folderInfo = getTableInfo($db_object,$cab);
			$existingTabs = getTableInfo($db_object, $cab.'_files',
				array('doc_id', 'subfolder'), array('subfolder' => $tab),
				'getAssoc');

			while ($results = $folderInfo->fetchRow()) {
				$doc_id = $results['doc_id'];
				if (!isset($existingTabs[$doc_id])) {
					$insertArr = array(
						"doc_id"	=> (int)$doc_id,
						"subfolder"	=> $tab,
						"file_size"	=> (int)4096
							  );
					$res = $db_object->extended->autoExecute($cab."_files",$insertArr);
					dbErr($res);
					$updateArr = array('quota_used'=>'quota_used+4096');
					$whereArr = array('real_department'=> $user->db_name);
					updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
										
					$loc = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $results['location']);
					$loc .= '/'.$tab;
					mkdir($loc);
				}
			}
		} else {
			echo<<<ENERGIE
  <script>
      document.onload = window.location = "addTabs.php?cab=$cab&mess=$limit";
      </script>
ENERGIE;
		}
		/*
		echo "tab $tab has been created in the $cab cabinet. ";
		echo "<a href= admin.php>click here</a> to continue.";*/
		$user->audit("created tab", "tab $tab in cabinet $cab");
		
	} //end of the for loop

	echo<<<ENERGIE
<script>
	document.onload = window.location.href = "addTabs.php?mess=$success";
</script>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}

function addTabsToCabinet($cabinetName, $allTabs, $db_name) {
	$db_doc = getDbObject('docutron');
	$gblStt = new GblStt($db_name, $db_doc);
	$result = $gblStt->get($cabinetName.'_tabs');
	// End
	$tabs = array ();
	$tabStr = implode(',', $allTabs);
	if (!$tabStr) {
		if ($result) {
			$gblStt->removeKey($cabinetName.'_tabs');
		}
	} else {
		$gblStt->set($cabinetName.'_tabs', $tabStr);
	}
}
?>
