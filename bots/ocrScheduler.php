<?php
chdir(dirname(__FILE__));
require_once '../db/db_common.php';
require_once '../lib/utility.php';
require_once '../lib/mime.php';
require_once '../lib/synchronizeBots.php';

$pidFile = "ocrScheduler.pid";
if(file_exists($DEFS['TMP_DIR'].'/'.$pidFile)) {
	$pid = file_get_contents($DEFS['TMP_DIR'].'/'.$pidFile);
	if (!isRunning ($pid, $DEFS)) {
		unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
	} else {
		die('ocrScheduler is already running');
	}
}
$fd = fopen ($DEFS['TMP_DIR'].'/'.$pidFile, 'w+');
fwrite ($fd, getmypid ());
fclose ($fd);

error_log ('Started ' . $DEFS['DOC_DIR']."/bots/ocrScheduler.php"); 

$db_doc = getDbObject('docutron');
//$extArr = array('tif','tiff','doc','pdf','txt');

$extArr = array('tif','tiff','doc','txt');
if( isset($DEFS['OCR_EXTENSIONS'])){
	$extArr = split(',',$DEFS['OCR_EXTENSIONS']);
}
if( isset($DEFS['OCRPDF'] ) ){
	$extArr[] = 'pdf';
}

$sArr = array('real_department');
$wArr = array();
if(isSet($argv[1])) {
	$i = 1;
	$depArr = array();
	while(isSet($argv[$i])) {
		$depArr[] = "'".$argv[$i]."'";
		$i++;
	}
	$wArr = array('department IN('.implode(",",$depArr).')');
}
//empty ocr_queue table
$db_doc->query("TRUNCATE TABLE ocr_queue");
$depList = getTableInfo($db_doc,'licenses',$sArr,$wArr,'queryCol');
foreach($depList AS $dep) {
	$db_dept = getDbObject($dep);

	$sArr = array('real_name');
	$wArr = array('deleted' => 0);
	$cabList = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryCol');
	foreach($cabList AS $cab) {
		$sArr = array('doc_id','location');
		$wArr = array('deleted' => 0);
		$folderList = getTableInfo($db_dept,$cab,$sArr,$wArr,'getAssoc');
		foreach($folderList AS $doc_id => $loc) {
			$loc = str_replace(" ","/",$loc);

			$sArr = array('id','filename','subfolder');
			$wArr = array('doc_id' => (int)$doc_id,
						'deleted' => 0,
						'filename' => 'IS NOT NULL',
						'ocr_context' => 'IS NULL');
			$filesArr = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'getAssoc');
			foreach($filesArr AS $id => $fInfo) {
				$fname = $fInfo['filename'];
				$ext = strtolower(getExtension($fname)); 
				if(in_array($ext,$extArr)) {
					$fpath = $loc;
					if($fInfo['subfolder']) {
						$fpath .= "/".$fInfo['subfolder'];
					} 	
					$fpath .= "/".$fname;
					$insertArr = array('location' => $fpath,
								'department' => $dep,
								'cabinet' => $cab,
								'file_id' => $id	);
					$res = $db_doc->extended->autoExecute('ocr_queue',$insertArr);
					dbErr($res);
				}
			}
		}
	}
}
?>
