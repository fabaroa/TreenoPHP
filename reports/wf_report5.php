<?php
include_once '../db/db_common.php';
include_once '../lib/settings.php';
include_once '../lib/utility.php';
	
$uname = $argv[1];
$dep = $argv[2];

$filePath = $DEFS['DATA_DIR']."/$dep/personalInbox/$uname";
$filePath .= "/{$uname}_report".date("Y_m_d_H_i_s").".xls";
$db_dept = getDbObject($dep);

$sArr = array('real_name','departmentname');
$wArr = array('deleted' => 0);
$cabList = getTableInfo($db_dept,'departments',$sArr,$wArr,'getAssoc');

$sArr = array('cab','doc_id');
$gArr = array('cab','doc_id');
$wfList = getTableInfo($db_dept,'wf_documents',$sArr,array(),'getAssoc',array(),0,0,$gArr,true);

$fp = fopen($filePath,'w+');
foreach($cabList AS $real => $arb) {
	$needHeaders = true;
	$wArr = array('deleted = 0');
	$oArr = array('doc_id' => 'ASC');
	$folderList = getTableInfo($db_dept,$real,array(),$wArr,'queryAll',$oArr);

	$idList = array();
	if(isSet($wfList[$real])) {
		$idList = $wfList[$real];
	}
	fwrite($fp,"$arb\n");
	if(is_array($idList)) {
		foreach($folderList AS $folder) {
			if(!in_array($folder['doc_id'],$idList)) { 
					unset($folder['doc_id']);
					unset($folder['location']);
					unset($folder['deleted']);

					if($needHeaders) {
						fwrite($fp,implode("\t",array_keys($folder))."\n");
					}
					fwrite($fp,implode("\t",array_values($folder))."\n");
					$needHeaders = false;
			}
		}
	}
	fwrite($fp,"\n");
}
?>
