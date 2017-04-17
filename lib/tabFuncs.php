<?php
include_once '../lib/settings.php';
include_once '../lib/utility.php';
include_once '../lib/quota.php';
include_once '../lib/fileFuncs.php';
include_once '../settings/settings.php';

function getSavedTabs($cabinetName, $db_name, $db_doc) {
	$settings = new GblStt($db_name, $db_doc);
	$result = $settings->get($cabinetName.'_tabs');
	if($result) {
		return explode(',', $result);
	}
	return array();
}

function getNoShowTabs($cabinet, $docID, $db_name) {
	$notShowTab = array();
	if( is_array( $_SESSION['groupAccess'] )){
		foreach($_SESSION['groupAccess'] as $eachAccess) {
			if($cabinet == $eachAccess['cabinet'] and
				($docID == $eachAccess['doc_id'] or !$eachAccess['doc_id'])) {
				$notShowTab[] = $eachAccess['subfolder'];
			}
		}
	}
	return $notShowTab;
}

function addTabsToFolder($cabinetName, $settings, $db_raw, $docID, $db_object, $db_name)
{
	global $DEFS;
	$tabs = $settings->get($cabinetName.'_tabs');
	if($tabs) {
		$allTabs = explode(',', $tabs);
		$location = getTableInfo($db_object,$cabinetName,array('location'),array('doc_id'=>(int)$docID),'queryOne');
		$location = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $location).'/';
		
		foreach($allTabs as $myTab) {
			mkdir($location.$myTab);
			allowWebWrite($location.$myTab,$DEFS);
			$statArr = stat($location.$myTab);
			$fileSize = $statArr[7];
			lockTables($db_raw, array('licenses'));
			$updateArr = array('quota_used'=>'quota_used+'.$fileSize);
			$whereArr = array('real_department'=> $db_name);
			updateTableInfo($db_raw,'licenses',$updateArr,$whereArr,1);
			unlockTables($db_raw);
			$queryArr = array(
				'doc_id'	=> (int) $docID,
				'subfolder'	=> $myTab
			);
			$res = $db_object->extended->autoExecute($cabinetName.'_files', $queryArr);
			dbErr($res);
		}
	}
}
?>
