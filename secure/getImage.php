<?php
include_once '../check_login.php';
include_once '../lib/filename.php';
include_once ('../classuser.inc');

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	//translated variables
	$nothing = $trans['Nothing to Index'];
	$db_object = $user->getDbObject();
	$cab = $_GET['cab'];
	if (!empty ($_GET['page'])) {
		$page = $_GET['page']; //passed from indexValues.php to view different images
	} else {
		$page = '';
	}
	
	if (isset ($_GET['ID'])) {
		$ID = $_GET['ID']; //should only get this if scrolling through pages of images
	} else {
		$ID = '';
	}
	
	if (isset ($_GET['numIndex'])) {
		$num_indices = $_GET['numIndex']; //returns number of indices of folder from indexValues.php
	} else {
		$num_indices = '';
	}
	
	if (isset ($_GET['name'])) {
		$fname = $_GET['name']; //passed from dblookup.php only
	} else {
		$fname = '';
	}
	
	if (isset ($_GET['banner'])) {	
		$bannerID = $_GET['banner']; //passed from dblookup.php only
	} else {
		$bannerID = '';
	}
	
	if (isset ($_GET['blanker'])) { 
		$blanker = $_GET['blanker'];
	} else {
		$blanker = '';
	}
	
	if (isset ($_GET['type']) and $_GET['type']) { 
		$type = $_GET['type'];
		$typeDir = "../$type/";
	} else {
		$type = '';
		$typeDir = '../secure/';
	}
	//This function is located in lib/utility.php
	if (!$page) {
		lockTables($db_object,array($cab."_indexing_table"));
		$whereArr = array('flag=0','finished<total');
		$query = getTableInfo($db_object,$cab."_indexing_table",array(),$whereArr,'query',array('id'=>'ASC'));
		if ($myrow = $query->fetchRow()) {
			$path = $myrow['path'];
			$path = $DEFS['DATA_DIR']."/".str_replace(" ", "/", $path,3);
			while (!is_dir($path) && $myrow) {
				//delete from indexing_table
				$whereArr = array('id'=>(int)$myrow['id']);
				deleteTableInfo($db_object,$cab."_indexing_table",$whereArr);
				//get next path
				if (!$myrow = $query->fetchRow()) {
					$mess = "";
					unlockTables($db_object);
					echo '<script type="text/javascript">';
					echo " top.mainFrame.window.location.href = '../secure/indexing.php?mess=$mess';";
					echo "</script>";
					die();
				}
				$path = $myrow['path'];
				$path = $DEFS['DATA_DIR']."/".str_replace(" ", "/", $path,2);
			}
			$filesArray = getFilesFromIndexingFolder($path);
			$_SESSION['indexFileArray'] = $filesArray;
			$ID = $myrow['id'];
			$updateArr = array('flag'=>1);
			$whereArr = array('id'=>(int)$ID);
			updateTableInfo($db_object,$cab."_indexing_table",$updateArr,$whereArr);
			$page = 1;
		}
		unlockTables($db_object);
	}
	if (!$ID) {
		$mess = $nothing;
		echo<<<ENERGIE
<script type="text/javascript">
top.mainFrame.window.location = "../secure/indexing.php?mess=$mess";
parent.bottomFrame.window.location.href = "../energie/bottom_white.php";
</script>
ENERGIE;
	} else {
		if ($num_indices) {
			for ($i = 0; $i < $num_indices; $i ++) {
				if ($i == ($num_indices -1))
					$passString = $passString."$i=".$_GET[$i];
				else
					$passString = $passString."$i=".$_GET[$i]."&";
			}
		} else
			$passString = "passString=";

		echo<<<ENERGIE
<script type="text/javascript" src="../lib/prototype.js"></script> 
<script src="../lib/windowTitle.js"></script>
<script>
setTitle(1, "{$user->cabArr[$cab]}");
function redir() {
	parent.IndexMainFrame.window.location.href = "../secure/indexingDisplay.php?page=$page";
ENERGIE;
		if( ($bannerID || $blanker) && $page) {//need banner and page number to pass to dblookup
			echo<<<ENERGIE
	parent.bottomFrame.window.location= "../auto_complete_indexing/dblookup.php?cab=$cab&ID=$ID&name=$fname&page=$page&$passString&banner=$bannerID&blanker=$blanker";

ENERGIE;
		} else {//else, do normally, pass to indexValues displays bottomFrame for standard indexing
//	parent.bottomFrame.window.location.href= "{$typeDir}indexValues.php?cab=$cab&ID=$ID&page=$page&$passString";
			echo<<<ENERGIE
	parent.bottomFrame.window.location.href= "{$typeDir}indexValues.html";
ENERGIE;
		}
		echo<<<ENERGIE
}
document.onload = redir();
</script>
ENERGIE;
	} //end of else
	setSessionUser($user);
} else {
	logUserOut();
}
?>
