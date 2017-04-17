<?php
require_once '../db/db_common.php';
require_once '../lib/utility.php';
require_once '../lib/webServices.php';
require_once '../lib/random.php';
require_once '../lib/settings.php';

$department = 'client_files';
$cabinetID = 4;
$db_dept = getDbObject ($department);
$goodDocs = array ();
$badDocs = array ();

$query = "SELECT policy_id, doc_id, location FROM Policies";
$allDocs = $db_dept->queryAll ($query);
dbErr ($allDocs);

foreach ($allDocs as $myDoc) {
	if (!array_key_exists ($myDoc['policy_id'], $goodDocs)) {
		$goodDocs[$myDoc['policy_id']] = $myDoc;
	} else {
		$badDocs[] = $myDoc;
	}
}

foreach ($badDocs as $myDoc) {
	$myGood = $goodDocs[$myDoc['client_code']];
	$query = "SELECT * FROM Policies_files WHERE deleted = 0 AND filename IS NULL AND doc_id = " . $myDoc['doc_id'];
	$docList = $db_dept->queryAll ($query);
	dbErr ($docList);
	foreach ($docList as $eachDoc) {
		$query = "SELECT * FROM Policies_files WHERE deleted = 0 AND filename IS NOT NULL AND doc_id = " . 
			$myDoc['doc_id'] . " AND subfolder = '" . $eachDoc['subfolder'] . "'";
		$res = $db_dept->queryAll ($query);
		dbErr ($res);
		if (count ($res) > 0) {
			$query = "SELECT real_field_name, document_field_value FROM " .
				"document_field_defs_list, document_field_value_list, document_type_defs " .
				"WHERE document_field_defs_list.document_table_name = '{$eachDoc['document_table_name']}' AND " .
				"document_field_defs_list_id = document_field_defs_list.id AND " .
				"document_type_defs.id = document_defs_list_id AND " .
				"document_id = " . $eachDoc['document_id'] . " " .
				"ORDER BY ordering ASC";
			$fieldList = $db_dept->extended->getAssoc ($query);
			dbErr ($fieldList);
			
			echo "create document info with $department, $cabinetID, {$myGood['doc_id']}, {$eachDoc['document_table_name']}, fieldList, admin\n";
			print_r($fieldList);
//			$tabID = createDocumentInfo ($department, $cabinetID, $myGood['doc_id'], $eachDoc['document_table_name'], $fieldList, 'admin');
//			$locDir = $DEFS['DATA_DIR'].'/'.str_replace (' ', '/', $myDoc['location']).'/'.$eachDoc['subfolder'];
//			$tmpDir = getUniqueDirectory ($DEFS['TMP_DIR'].'/');
			foreach ($res as $myFile) {
				$srcFile = $locDir .'/'.$myFile['filename'];
				if (file_exists ($srcFile) and is_file ($srcFile)) {
//					copy ($srcFile, $tmpDir.'/'.basename ($srcFile));
					echo "\t\t$srcFile\n";
				} else {
					echo "BADBADBAD$srcFile\n";
				}
			}
//			$fd = fopen ($tmpDir.'/INDEX.DAT', 'w+');
//			fwrite ($fd, "0 $cabinetID {$myGood['doc_id']} $tabID");
//			fclose ($fd);
//			rename ($tmpDir, $DEFS['DATA_DIR'].'/Scan/'.basename ($tmpDir));
		} else {
			echo "NOTHING THERE\n";
		}
		echo "set deleted in {$eachDoc['document_table_name']} where id is {$eachDoc['document_id']}\n";
//		$res = $db_dept->query ("UPDATE {$eachDoc['document_table_name']} SET deleted = 1 WHERE id = {$eachDoc['document_id']}");
//		dbErr ($res);
		echo "set deleted in Policies_files WHERE id = {$eachDoc['id']}\n";
//		$res = $db_dept->query ("UPDATE Policies_files SET deleted = 1, display = 0 WHERE id = {$eachDoc['id']}");
//		dbErr($res);
	}
	echo "set deleted in Policies where doc_id = {$myDoc['doc_id']}\n";
//	$res = $db_dept->query ("UPDATE Policies SET deleted = 1 WHERE doc_id = {$myDoc['doc_id']}");
//	dbErr($res);
}

?>
