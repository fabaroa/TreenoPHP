<?php
include_once '../db/db_common.php';
include_once 'XMLCabinetFuncs.php';
include_once '../check_login.php';

$uname = $user->username;
//these variables are passed to let this page know where to redirect upon exiting
$temp_table=$_GET['temp_table'];
$is_files=$_GET['is_files'];

echo<<<ENERGIE
<html>
<head>
<script>
//function to animate while writing CD progress
function animateProgress( id ) {

	var next_state;
	//get current symbol
	var current_state;
	if(parent.process.window.document.getElementById(id+'_progress'))
		current_state=parent.process.window.document.getElementById(id+'_progress').innerHTML;
	else
		current_state="";

	if(current_state==null||current_state=="")
		next_state=".";
	else if(current_state==".")
		next_state="..";
	else if(current_state=="..")
		next_state="...";
	else if(current_state=="...")
		next_state="....";
	else if(current_state=="....")
		next_state=".....";
	else if(current_state==".....")
		next_state="......";
	else if(current_state=="......")
		next_state=".......";
	else if(current_state==".......")
		next_state="........";
	else if(current_state=="........")
		next_state=".........";
	else
		next_state="";

	//set the new state
	if(parent.process.window.document.getElementById(id+'_progress'))
		parent.process.window.document.getElementById(id+'_progress').innerHTML=next_state;

}
</script>
ENERGIE;
if (!empty ($_SESSION[$user->db_name. '-' . $user->username . '-Usrsettings'])) {
	$_SESSION[$user->db_name. '-' . $user->username . '-Usrsettings'] = array ();
}
$userStt = new Usrsettings($uname, $user->db_name);
//get the CDBackup status entry
$status = $userStt->get('cd_backup');
if($status) {

	//if storage shortage, abort and print error message
   if($status=="out_of_mem") {
	$userStt->removeKey('cd_backup');
	if($temp_table!="") {
		if($is_files!="1") {
			$next_page="../energie/searchResults.php";
		} else {
			$next_page="../energie/file_search_results.php";
		}
	} else {
		$next_page="backupCabinet.php";
	}

	$next_page.="?mess=Not Enough Server Storage to Process Backup";

	echo "<script>parent.window.location='$next_page'</script>";
	die();
   }
   $pieces=explode("_disks=",$status);
	//get number of disks
   if(isset($pieces[1])) {
	$disks=$pieces[1];
	$status=$pieces[0];
   }

   switch ($status) {

      case "all_done":
         break;
      //files have been completed and tree is ready
      case "files_done":
         echo "<script>
		parent.process.window.document.getElementById('memory').innerHTML='Checking Available Storage';
		parent.process.window.document.getElementById('memory_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('memory_progress').innerHTML='';

		parent.process.window.document.getElementById('tree').innerHTML='Creating Cabinet Tree Structure';
		parent.process.window.document.getElementById('tree_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('files').innerHTML='Preparing Cabinet Files';
		parent.process.window.document.getElementById('files_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('tree_progress').innerHTML='';
		parent.process.window.document.getElementById('files_progress').innerHTML='';
		</script>";
	$userStt->removeKey('cd_backup');
/*
	echo<<<ENERGIE
<script>window.open('confirm_frames.php?files=$disks','confirm','width=600,height=200,scrollbars=no,menubar=no,status=no,toolbar=no,titlebar=no,alwaysRaised=yes');</script>

ENERGIE;
*/
echo<<<ENERGIE
<script>document.onload=parent.window.location='confirm_backup.php?files=$disks&temp_table=$temp_table&is_files=$is_files';</script>
ENERGIE;
	die();
	break;
      //files are currently being setup in a disk format for burning
      case "files":
         echo "<script>
		parent.process.window.document.getElementById('memory').innerHTML='Checking Available Storage';
		parent.process.window.document.getElementById('memory_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('memory_progress').innerHTML='';

		parent.process.window.document.getElementById('tree').innerHTML='Creating Cabinet Tree Structure';
		parent.process.window.document.getElementById('tree_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('tree_progress').innerHTML='';
		parent.process.window.document.getElementById('files').innerHTML='Preparing Cabinet Files';
		animateProgress('files');</script>";
         break;
      case "tree_done":
         echo "<script>
		parent.process.window.document.getElementById('memory').innerHTML='Checking Available Storage';
		parent.process.window.document.getElementById('memory_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('memory_progress').innerHTML='';

		parent.process.window.document.getElementById('tree').innerHTML='Creating Cabinet Tree Structure';
		parent.process.window.document.getElementById('tree_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('tree_progress').innerHTML='';
		</script>";
         break;
      case "tree":
         echo "<script>
		parent.process.window.document.getElementById('memory').innerHTML='Checking Available Storage';
		parent.process.window.document.getElementById('memory_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('memory_progress').innerHTML='';

		parent.process.window.document.getElementById('tree').innerHTML='Creating Cabinet Tree Structure';
		animateProgress('tree')</script>";
         break;
     case "memory_done":
	echo "<script>
		parent.process.window.document.getElementById('memory').innerHTML='Checking Available Storage';
		parent.process.window.document.getElementById('memory_done').innerHTML='[DONE]';
		parent.process.window.document.getElementById('memory_progress').innerHTML='';
	</script>";
     case "memory":
        echo "<script>
		parent.process.window.document.getElementById('memory').innerHTML='Checking Available Storage';
		animateProgress('memory');
	</script>";
        break;
   }
}
	
echo<<<ENERGIE
<script>
t=1000;
function timer() {

	setTimeout("location.href='poll.php?temp_table=$temp_table&is_files=$is_files'",t);
}

</script>
</head>
<body>
<script>
timer();
</script>
</body>
</html>
ENERGIE;


	setSessionUser($user);

?>
