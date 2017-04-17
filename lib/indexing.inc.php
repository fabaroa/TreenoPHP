<?php
/**
 * @package DMS
 */
/**
 * class GblStt
 */
require_once '../settings/settings.php';

/**
 * createFolderInCabinet()
 */
require_once '../lib/cabinets.php';

require_once '../documents/documents.php';

/**
 * class Barcode
 */
require_once '../lib/barcode.inc.php';

/**
 * @package Indexing
 */
class Indexing {
	
	/**
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param array
	 * @param string
	 * @param string
	 */
	function index($db, $db_doc, $folderIndices, $cabinet, $userName, $dbName, $DEFS,
			$batchDir, $gblStt, $workflow = null, $tab = null, $docType = null, $docFields = null,$user) {
		if(is_null ($workflow) and $gblStt->get('file_into_existing')) {
			$docID = checkFolderExists($dbName, $cabinet, $folderIndices, $db_doc, $db);
		}
		if(!isSet($docID)) {
			$temp_table = "";
			$docID = createFolderInCabinet($db, $gblStt, $db_doc, $userName, $dbName, $cabinet, array_values($folderIndices), array_keys($folderIndices),$temp_table);
		}
		$docID = (int) $docID;

		if($docType) {
			$enArr = array('cabinet' => $cabinet,
						'document_table_name' => $docType,
						'doc_id' => $docID,
						'field_count' => count($docFields));
			$i = 0;
			foreach($docFields AS $key => $value) {
				$enArr['key'.$i] = $key;
				$enArr['field'.$i] = $value;
				$i++;
			}
			$tab = addDocumentToCabinet($enArr,$user,$db_doc,$db);		
		} else {
			$sArr = array('location');
			$wArr = array('doc_id' => (int)$docID);
			$loc = getTableInfo($db,$cabinet,$sArr,$wArr,'queryOne');
			$loc = str_replace(" ","/",$loc);
			$tPath = $DEFS['DATA_DIR']."/$loc/$tab";
			if(!is_dir($tPath)) {
				$insertArr = array(
					"doc_id"	=> (int)$docID,
					"subfolder"	=> $tab,
					"file_size"	=> (int)4096
						  );
				$res = $db->extended->autoExecute($cabinet."_files",$insertArr);
				dbErr($res);
				
				mkdir($tPath);	
			}
		}

		$barcode = Barcode::createBarcode($db, $db_doc, $userName, $dbName, $cabinet, $docID, $workflow, $tab);

		$fd = fopen($batchDir.'/INDEX.DAT', 'w+');
		fwrite($fd, $barcode);
		fclose($fd);
		
		$destDir = Indexing::makeUnique($DEFS['DATA_DIR'].'/Scan/'.basename($batchDir));
		rename($batchDir, $destDir);
		allowWebWrite ($destDir, $DEFS);
	}
		
	/**
	 * Helper function to make sure file or directory is unique
	 * 
	 * @param string $dir base directory or file to make sure it is unique
	 * @return string unique directory name.
	 */
	function makeUnique($dir) {
		if(!file_exists($dir)) {
			return $dir;
		}
		$i = 1;
		$str = sprintf('%05d', $i);
		$pos = strrpos($dir, '.');
		if($pos !== false) {
			$tmpName = substr($dir, 0, $pos) . '-' . $str . substr($dir, $pos);
		} else {
			$tmpName = $dir . '-' . $str;
		}
		while(file_exists($tmpName)) {
			$i++;
			$str = sprintf('%05d', $i);
			$pos = strrpos($dir, '.');
			if($pos !== false) {
				$tmpName = substr($dir, 0, $pos) . '-' . $str . substr($dir, $pos);
			} else {
				$tmpName = $dir . '-' . $str;
			}
		}
		return $tmpName; 
	}
	
	function &orderByTime ($fArr) {
		if (count($fArr) == 1) {
			return $fArr;
		}
		$retArr = array ();
		$newFArr = array ();
		foreach ($fArr as $file) {
			if (file_exists ($file)) {
				clearstatcache ();
				$ctime = filectime ($file);
				if (!isset ($newFArr[$ctime])) {
					$newFArr[$ctime] = array ();
				}
				$newFArr[$ctime][] = $file;
			}
		}
		ksort ($newFArr);
		$newFArr = array_values ($newFArr);
		for ($i = 0; $i < count ($newFArr); $i++) {
			usort ($newFArr[$i], 'strnatcasecmp');
			for ($j = 0; $j < count ($newFArr[$i]); $j++) {
				$retArr[] = $newFArr[$i][$j];
			}
		}
		return $retArr;
	}
}

?>
