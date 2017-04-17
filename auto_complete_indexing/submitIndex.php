<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/filename.php';
include_once '../settings/settings.php';
include_once '../lib/indexing2.php';
include_once '../lib/settings.php';
include_once '../lib/indexing.inc.php';
include_once '../lib/fileFuncs.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$gbl = new GblStt($user->db_name, $db_doc);
	$id = $_GET['ID'];
	$cab = $_GET['cab'];
	$rootPath = $user->getRootPath();
	$db_name = $user->db_name;
	$username = $user->username;

	$result = getTableInfo($db_object, $cab."_indexing_table", array (), array ('id' => (int) $id));
	$row = $result->fetchRow();
	if ($row and !empty ($row['folder'])) {
		$batchLoc = $user->db_name."/indexing/".$cab."/".$row['folder'];
		$batchLoc = $DEFS['DATA_DIR']."/".$batchLoc;
		if ($_POST['delete'] == "Delete") {
			if (is_dir($batchLoc)) {
				delDir($batchLoc);
				$whereArr = array ('id' => (int) $id);
				deleteTableInfo($db_object, $cab."_indexing_table", $whereArr);
			}
		} else {
			$cabInfo = getCabinetInfo($db_object, $cab);
			//check if exists statement
			$key = "indexing_".$cab;
			$table_name = $gbl->get($key);

			if ($table_name !== 'odbc_auto_complete' and
				$table_name !== 'sagitta_ws_auto_complete') {

				$existsRes = getTableInfo($db_object, $table_name, array (), array ($cabInfo[0] => $_POST[$cabInfo[0]]));
				if (!$existsRes->fetchRow()) {
					$insertArr = array ();
					foreach ($cabInfo as $index) {
						if (!empty ($_POST[$index])) {
							$insertArr[$index] = $_POST[$index];
						}
					}
					if ($insertArr) {
						$db_object->extended->autoExecute($table_name, $insertArr);
					}
				}
			}
			
			$cabinetFolder = array ();
			
			foreach($cabInfo as $index) {
				$cabinetFolder[$index] = $_POST[$index];
			}
			
			Indexing::index($db_object, $db_doc, $cabinetFolder, $cab, $user->username, $user->db_name, $DEFS, $batchLoc, $gbl);
				$whereArr = array ('id' => (int) $id);
				deleteTableInfo($db_object, $cab.'_indexing_table', $whereArr);
			unset ($_SESSION['indexFileArray']);
		}
		echo<<<ENERGIE
		<script>
			onload = location.href = "../secure/getImage.php?cab=$cab&type=auto_complete_indexing";
		</script>
ENERGIE;
	} else {
		$user->audit ("Batch Does Not Exist", "cabinet: $cab, id: $id");
		echo<<<ENERGIE
		<script>
			onload = location.href = "../secure/indexing.php?mess=Batch Does Not Exist";
		</script>
ENERGIE;
		
	}
} else {
	echo "<script> onload = location.href = \"../logout.php\";</script>";
}
?>

