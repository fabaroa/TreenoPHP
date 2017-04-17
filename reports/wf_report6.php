<?php
include_once '../check_login.php'; 
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/searchLib.php';

if($logged_in == 1 && strcmp($user->username,"") != 0) {
	$db_dept = $user->getDbObject($user->db_name);

	if( isset($_GET['start_date']) and isISODate ($_GET['start_date'])) {
		$user_date_start = $_GET['start_date']." 00:00:00";
	} else {
		$user_date_start = date('Y')."-01-01" . " 00:00:00";
	}

	if( isset($_GET['end_date']) and isISODate ($_GET['end_date'])) {
		$user_date_end = $_GET['end_date']." 23:59:59";
	} else {
		$user_date_end = date("Y-m-d")." 23:59:59";
	}

	$user_date_start_out = date("F j, Y",strtotime($user_date_start));
	$user_date_end_out = date("F j, Y",strtotime($user_date_end));
	$inboxPath = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/".$user->username."/";

	$file="report".date("Y_m_d_H_i_s").".xls";
	$fp = fopen($inboxPath.$file,"w+");
	fwrite($fp,"Workflows started from ".$user_date_start_out." to ".$user_date_end_out."\n\n");

	$tArr = array('wf_history','wf_nodes');
	$sArr = array('username');
	$wArr = array(	"date_time >= '".$user_date_start."'",
					"date_time <= '".$user_date_end."'",
					"wf_node_id=wf_nodes.id",
					"(node_name='quoted final' OR node_name='lost final' OR node_name='decline final'");
	$uArr = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryCol');
	$uList = array();
	foreach($uArr AS $uname) {
		if(!array_key_exists($uname,$uList)) {
			$uList[$uname] = 0;
		}
		$uList[$uname]++;
	}

	foreach($uList AS $uname => $ct) {
		fwrite($fp,"$uname\t$ct\n");
	}
	fwrite($fp,"\nTotal: ".array_sum($uList));
	fclose($fp);
	downloadFile($inboxPath,$file,1,0,$file);

	setSessionUser($user);
} else {
	logUserOut();
}
?>
