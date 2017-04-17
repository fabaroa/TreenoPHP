<?php

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/filename.php';
include_once '../lib/indexing2.php';
include_once '../settings/settings.php';
include_once '../lib/indexing.inc.php';
include_once '../lib/fileFuncs.php';

if($logged_in==1 && strcmp($user->username,"")!=0) {
	$db_doc = getDbObject ('docutron');
	$gblStt = new GblStt($user->db_name, $db_doc);
	$Delete = $trans['Delete'] ;
	$Submit = $trans['Submit'] ;

	$var = $_GET['ID'];
	$cab = $_GET['cab'];
	$db_object = $user->getDbObject();
	$query = getTableInfo($db_object,$cab."_indexing_table",array(),array('id'=>(int)$var));
	$myrow = $query->fetchRow();
	$finished = $myrow['finished'];
	//This function is located in lib/utility.php
	$cab_id = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cab), 'queryOne');

	$result = getTableInfo($db_object,$cab."_indexing_table",array(),array('id'=>(int)$var));
	$row = $result->fetchRow() ;
	if ($row and !empty ($row['folder'])) {
		$batchLoc = $user->db_name."/indexing/".$cab."/".$row['folder'];
		$batchLoc = $DEFS['DATA_DIR']."/".$batchLoc;

		if($_POST['Submit'] == $Delete){ // Delete entry and files
			if(is_dir($batchLoc)) {
				delDir($batchLoc) ;
				$whereArr = array('id'=>(int)$var);
				deleteTableInfo($db_object,$cab."_indexing_table",$whereArr);
			}
		} else if($finished == $myrow['total']) {
			echo<<<ENERGIE
	<script>
	 onload = location.href = "../secure/getImage.php?cab=$cab&workflow=$workflow";
	</script>
ENERGIE;
		} else {// Index the files away
			if($_GET['ID']) {
				$var = $_GET['ID'];
				$cab = $_GET['cab'];
				$rootPath = $user->getRootPath();
				$db_name = $user->db_name;
				$username = $user->username;

				$cabinetFolder = array ();

				$fields = getCabinetInfo( $db_object, $cab );

				
				foreach($fields as $index) {
					$cabinetFolder[$index] = $_POST[$index];
				}
				
				if(!empty($_POST['extIndexTab'])) {
					$myTab = $_POST['extIndexTab'];
					if (!file_exists ($batchLoc.'/'.$myTab)) {
						mkdir ($batchLoc.'/'.$myTab);
						$dh = safeOpenDir($batchLoc);
						$myEntry = readdir ($dh);
						while ($myEntry !== false) {
							if (is_file ($batchLoc.'/'.$myEntry)) {
								rename ($batchLoc.'/'.$myEntry,
										$batchLoc.'/'.$myTab.'/'.$myEntry);
							}
							$myEntry = readdir ($dh);
						}
						closedir ($dh);
					}
				}
				
				if( $var=="" || $cab=="") {
					$user->audit( "submit indexing - indexing", "error missing info" );
					die('error - illegal access');
				}
				$user->audit( "Indexing", "Indexed Folder" );
				Indexing::index($db_object, $db_doc, $cabinetFolder, $cab, 
					$username, $db_name, $DEFS, $batchLoc, $gblStt,
					$_POST['Workflow']);
				$whereArr = array('id'=>(int)$var);
				deleteTableInfo($db_object,$cab.'_indexing_table',$whereArr);
				unset ($_SESSION['indexFileArray']);
			}
		}
	//fclose($fp) ; // ERROR
		echo<<<ENERGIE
<script>
 onload = location.href = "../secure/getImage.php?cab=$cab&workflow=$workflow" ;
</script>
ENERGIE;
	} else {
		$user->audit ("Batch Does Not Exist", "cabinet: $cab, id: $var");
		echo<<<ENERGIE
<script>
 onload = location.href = "../secure/indexing.php?mess=Batch Does Not Exist";
</script>
ENERGIE;
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
