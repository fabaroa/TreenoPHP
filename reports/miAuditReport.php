<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/mime.php';

if($logged_in && $user->username) {
	$db_dept = $user->getDbObject();

	$sdate = $_GET['start_date'];
	$edate = $_GET['end_date'];

	$fpath = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/".$user->username."/auditReport.csv";
	$headerArr = array('UserID','Cabinet','Date','# of Items Batched');
	file_put_contents($fpath,implode(",",$headerArr)."\n");
	
	$sArr = array('username','info');
	$wArr = array("datetime >= '$sdate 00:00:00'",
				"datetime <= '$edate 23:59:59'",
				"action = 'Batch Routed'");
	$aArr = getTableInfo($db_dept,'audit',$sArr,$wArr,'getAssoc',array(),0,0,array(),true);
	foreach($aArr AS $uname => $info) {
		$cabArr = array();
		foreach($info AS $r) {
			$key1 = stripos($r,"cabinet:");	
			if($key1) {
				$cabStr = substr($r,$key1+9);
				$key2 = stripos($cabStr,",");
				$c = substr($cabStr,0,$key2);
				if(isSet($user->cabArr[$c])) {
					$cab = $user->cabArr[$c];
					if(isSet($cabArr[$cab])) {
						$cabArr[$cab]++;	
					} else {
						$cabArr[$cab] = 1;	
					}
				}
			}
		}
		if(count($cabArr)) {
			foreach($cabArr AS $cab => $ct) {
				$rowArr = array($uname,$cab,$sdate." - ".$edate,$ct);
				file_put_contents($fpath,implode(",",$rowArr)."\n",FILE_APPEND);
			}
		}
	}
	downloadFile(dirname($fpath),basename($fpath),1,1,basename($fpath));
	setSessionUser($user);
} else {
	logUserOut();
}
