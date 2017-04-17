<?php
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../secure/tabChecks.php';
include_once '../lib/quota.php';

if($logged_in ==1 && strcmp($user->username,"")!=0){

	$doc_id = $_GET['doc_id'];
	$cab = $_GET['cab'];
	$ID = $_GET['ID'];
	$tab = $_GET['tab'];
	$tabName = $_POST['tab'];
	$temp_table = $_GET['table'];
	$index = $_GET['index'];

	$fileExists = "File Exists";
	$quota = "This Operation Will Exceed The Quota Limit";

	$tabName = strip_tags($tabName);
	//removes more than one space/underscore together
	$tabName = $user->parseStr($tabName);
	//error checks tab in tabChecks.php
	$status = tabCheck($tabName, $user);
	if( $status !== false ) {
echo<<<ENERGIE
<script>
    	 document.onload = parent.mainFrame.window.location = "addTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&mess=$status&table=$temp_table&index=$index";
</script>
ENERGIE;
		die();
	} else {
		$whereArr = array('doc_id'=>(int)$doc_id);
//		$department = getTableInfo($db_object,$cab,array(),$whereArr);
//    	$myrow1 = $department->fetchRow();
//    	$docLocation = $myrow1['location'];
    	$docLocation = getTableInfo($db_object,$cab,array('location'),$whereArr, 'queryOne'); 
    	$docLocation = str_replace(" ","/",$docLocation);
		$tabLoc = $DEFS['DATA_DIR']."/".$docLocation."/".$tabName."/";
		if(file_exists($tabLoc)) {
			echo<<<ENERGIE
<script>
    	 document.onload = parent.mainFrame.window.location = "addTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&mess=$fileExists&table=$temp_table&index=$index";
</script>
ENERGIE;
			die();
		} else {
			lockTables ($db_doc, array ('licenses'));
			if(checkQuota($db_doc, 4096, $user->db_name ) ) {
				mkdir($tabLoc);
				$updateArr = array('quota_used'=>'quota_used+4096');
				$whereArr = array('real_department'=> $user->db_name);
				updateTableInfo($db_doc,'licenses',$updateArr,$whereArr,1);
				unlockTables($db_doc);
			} else {
				unlockTables($db_doc);
				echo<<<ENERGIE
<script>
    	 document.onload = parent.mainFrame.window.location = "addTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&mess=$quota&table=$temp_table&index=$index";
</script>
ENERGIE;
				die();
			}
		}
	$insertArr = array(
		"doc_id"	=> (int)$doc_id,
		"subfolder"	=> $tabName,
		"file_size"	=> (int)4096
			  );
	$res = $db_object->extended->autoExecute($cab."_files",$insertArr);
	dbErr($res);
	
	$indexArray = getCabIndexArr($doc_id, $cab, $db_object);
	$indexArray = implode(' ',$indexArray);
	$info = "Cabinet: ".$cab.", ";
	$info .= "Tab Name: ".$tabName." Folder: $indexArray";
    $user->audit("tab created", "$info" );
	//update the list of tabs
echo<<<ENERGIE
<script>
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
