<?php
include_once '../check_login.php'; 
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/searchLib.php';

if($logged_in == 1 && strcmp($user->username,"") != 0) {
	
	if( isset($_GET['start_date']) and isISODate ($_GET['start_date'])) {
		$user_date_start = $_GET['start_date']." 00:00:00";
	} else {
		$user_date_start = date ('Y') . '-01-01 00:00:00';
	}

	if( isset($_GET['end_date']) and isISODate($_GET['start_date'])) {
	    $user_date_end = $_GET['end_date']." 23:59:59";
	} else {
		$user_date_end = date("Y-m-d")." 23:59:59";
	}

	if( isset($_GET['user'])) {
		$username = $_GET['user'];
	}

	$user_date_start_out = date("F j, Y",strtotime($user_date_start));
	$user_date_end_out = date("F j, Y",strtotime($user_date_end));

	$file_path = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/".$user->username."/";
	$file=$username."_report".date("Y_m_d_H_i_s").".xls";
	$fp = fopen($file_path.$file,"w+");

	$wArr = array(	"username = '".$username."'",
					"action != 'Workflow reassigned'",
					"action != 'notified'",
					"date_time > '".$user_date_start."'",
					"date_time < '".$user_date_end."'" );
	$histArr = getTableInfo($db_object,'wf_history',array(),$wArr,'queryAll');
	
	$ct = 0;
	$outputArr = array();
	foreach($histArr AS $info) {
		$document_id = $info['wf_document_id'];

		$sArr = array('doc_id','cab');
		$wArr = array('id' => (int)$document_id);
		$docArr = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryRow');

		$sArr = array('node_name');
		$wArr = array('id' => (int)$info['wf_node_id']);
		$nodeName = getTableInfo($db_object,'wf_nodes',$sArr,$wArr,'queryOne');
		if($docArr['doc_id']) {
			$folderIndices = getCabIndexArr($docArr['doc_id'],$docArr['cab'],$db_object);
			$folder = implode("\t",$folderIndices);
			$outputStr = $info['action']."\t".$info['date_time']."\t";
			$outputStr .= $nodeName."\t".$info['notes']."\t".$docArr['cab']."\t".$folder;
			$outputArr[] = $outputStr;
			$ct++;
		}
	}

	$fp = fopen($file_path.$file,"w+");
	$outputStr = "Report for $username from $user_date_start_out to $user_date_end_out\n\t";
	$outputStr .= $ct." total items processed\n\n";
	$outputStr .= "action\tdate_time\tnode name\tnotes\tcabinet\tfolder\n";
	fwrite($fp,$outputStr);
	fwrite($fp,implode("\n",$outputArr));
	fclose($fp);
	downloadFile($file_path,$file,1,0,$file);

	setSessionUser($user);
} else {
	logUserOut();
}
?>
