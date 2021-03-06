<?php
include_once '../check_login.php'; 
include_once '../classuser.inc';
include_once '../lib/mime.php';
include_once '../lib/searchLib.php';

if($logged_in == 1 && strcmp($user->username,"") != 0) {
	$db_dept = $user->getDbObject();

	if(isset($_GET['wf_name'])){
		$wf_name = $_GET['wf_name'];
	} else {
		die("Must specify a workflow");
	}

	if(isset($_GET['sort_by'])){
        	$sortBy = $_GET['sort_by'];
    	} else {
		$sortBy = "total";
    	}

	if(isset($_GET['start_date']) and isISODate ($_GET['start_date'])){
        	$start_date = $_GET['start_date']." 00:00:00";
    	} else {
    	   	$start_date = "2000-01-01 00:00:00";
   	}

	$sArr = array('MIN(node_id)');
	$wArr = array('defs_name' => $wf_name);
	$wf_def_id = getTableInfo($db_dept,'wf_defs',$sArr,$wArr,'queryOne');

	$sArr = array('id','doc_id','cab','status','owner');
	$wArr = array(	'status'	=> 'IN PROGRESS',
					'wf_def_id'	=> (int)$wf_def_id);
	$resArr = getTableInfo($db_dept,'wf_documents',$sArr,$wArr,'getAssoc');

	$file="report".date("Y_m_d_H_i_s").".xls";
	$file_path = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/".$user->username."/";
	$file_full = $file_path.$file;
	$fp = fopen($file_full,"w+");

	$outputHeader = array(	'Total',
				'Start Time',
				'Current Time',
				'Total Since Last Notification',
				'Last Notification',
				'Status',
				'Username',
				'Owner',
				'Node Name',
				'Cabinet',
				'Folder' );
	fwrite($fp,implode("\t",$outputHeader)."\n");
	$outputArr = array();

	$sArr = array(	'DISTINCT(wf_document_id) AS wf_doc_id',
			'MIN(date_time) AS min',
			'MAX(date_time) AS max',
			'(CURRENT_TIMESTAMP - MIN(date_time)) AS total',
			'(CURRENT_TIMESTAMP - MAX(date_time)) as diff_hours');
	$wArr = array("date_time > '".$start_date."'");
	$gArr = array('wf_document_id');
	if($sortBy == "total") {
		$oArr = array('total' => 'DESC');
	} else {
		$oArr = array('min' => 'DESC');
	}
	$timesArr = getTableInfo($db_dept,'wf_history',$sArr,$wArr,'getAssoc',$oArr,0,0,$gArr);
//addDays(strtotime($startDate), 20, $skipdays,$skipdates = NULL);
	$curTime = time();
	foreach($timesArr AS $wf_doc_id => $info) {
		if(isset ($resArr[$wf_doc_id])) {
			$th = round(($curTime - strtotime($info['min'])) / 3600); 
			$days = round($th / 24);
			$hours = $th % 24;
			$time = "$days days $hours hours";

			$outputArr['total_time'] = $time;
			$outputArr['mintime'] = $info['min'];
			$outputArr['currtime'] = date("n/j/Y G:i");

			$df = round(($curTime - strtotime($info['max'])) / 3600); 
			$days = round($df / 24);
			$hours = $df % 24;
			$time = "$days days $hours hours";
			$outputArr['diff_time'] = $time;
		
			$outputArr['maxtime'] = $info['max'];
			$outputArr['status'] = $resArr[$wf_doc_id]['status'];
			$outputArr['owner'] = $resArr[$wf_doc_id]['owner'];

			$tArr = array('wf_nodes','wf_history');
			$sArr = array('node_name','username');
			$wArr = array(	"wf_nodes.id=wf_history.wf_node_id",
					"wf_document_id=".(int)$wf_doc_id,
					"date_time ='".$outputArr['maxtime']."'",
					"(action like 'notified%' OR action='Workflow reassigned' or action='notified via email' or action='no notification selected')");
			$resOut = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryRow');
			$outputArr['username'] = $resOut['username'];
			$outputArr['node_name'] = $resOut['node_name'];
			$outputArr['cab'] = $resArr[$wf_doc_id]['cab'];

			//get the index values for the doc_id
			$folderIndices = getCabIndexArr($resArr[$wf_doc_id]['doc_id'],$resArr[$wf_doc_id]['cab'],$db_object);
			$outputArr['folder'] = implode("\t",$folderIndices);
			
			fwrite($fp,implode("\t",$outputArr)."\n");
		}//end of if
	}
	fclose($fp);
	downloadFile($file_path,$file,1,0,$file);

	setSessionUser($user);
} else {
	logUserOut();
}
    function addDays($timestamp, $days, $skipdays = array("Saturday", "Sunday"), $skipdates = NULL) {
        // $skipdays: array (Monday-Sunday) eg. array("Saturday","Sunday")
        // $skipdates: array (YYYY-mm-dd) eg. array("2012-05-02","2015-08-01");
       //timestamp is strtotime of ur $startDate
        $i = 1;

        while ($days >= $i) {
            $timestamp = strtotime("+1 day", $timestamp);
            if (in_array(date("l", $timestamp), $skipdays)) {
                $days++;
            }
            $i++;
        }

        return $timestamp;
        //return date("m/d/Y",$timestamp);
    }
?>
