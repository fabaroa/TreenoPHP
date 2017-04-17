<?php
require_once '../check_login.php';
require_once 'barcode.php';
require_once 'barcodeLib.php';
require_once 'c128aobject.php';
require_once '../lib/settings.php';
require_once '../settings/settings.php';
require_once '../lib/cabinets.php';

if ($logged_in and $user->username) {
	if (isset ($_GET['dept'])) {
		$db_name = $_GET['dept'];
		$db_object = getDbObject($db_name);
	} else {
		$db_name = $user->db_name;
	}

	if (isset($_GET['barcode'])) {
		$barcode = $_GET['barcode'];
	} else {
		$barcode = '';
	}

	if (isset($_GET['username'])) {
		$username = $_GET['username'];
	} else {
		$username = '';
	}

	if (isset($_GET['cabinet'])) {
		$cabinet = $_GET['cabinet'];
	} else {
		$cabinet = '';
	}

	if (isset($_GET['docID'])) {
		$docID = $_GET['docID'];
	} else {
		$docID = '';
	}
	
	if (isset($_GET['docIDs'])) {
		$docIDs = $_GET['docIDs'];
	} else {
		$docIDs = '';
	}

	if (isset($_GET['tabID'])) {
		$tabID = $_GET['tabID'];
	} else {
		$tabID = '';
	}

	if (isset($_GET['subfolder'])) {
		$subfolder = $_GET['subfolder'];
	} else {
		$subfolder = '';
	}


	if (isset($_GET['wf'])) {
		$workflow = $_GET['wf'];
		$uid = $_GET['uid'];
	} else {
		$workflow = '';
		$uid = '';
	}

	if (isset($_GET['printAll']) && $_GET['printAll'] == '1') {
		$printAll = true;
	} else {
		$printAll = false;
	}

	if (isset($_GET['subFolders'])) {
		$docID = $docIDs;
		$subFolders = $_GET['subFolders'];
		if($subFolders == 'All tabs') {
			$printAll = true;
		} else {
			$subFolder = $_GET['subFolders'];
		}
		
	} else {
		$subFolders = '';
	}


	//$doAfterPrint = (isset($_GET['NewUiPrintBC']))? true : false; 
	printBarcodeHeader();

	if ($printAll) {
		$subfolderTmp = getTableInfo($db_object, $cabinet.'_files', array('subfolder'),
			array('doc_id' => (int)$docID, 'filename' => 'IS NULL', 
			'subfolder' => 'IS NOT NULL'), 'queryCol');
		sort($subfolderTmp);
		$subfolders = array('main');
		foreach ($subfolderTmp as $mySub) {
			$subfolders[] = $mySub;
		}
		for($i = 0; $i < count($subfolders); $i++) {
			if ($i > 0) {
				echo '<div style="page-break-before:always">';
			} else {
				echo '<div>';
			}
			printBarcode($db_name, $db_object, $barcode, $username, $cabinet,
				$docID, $subfolders[$i], $workflow, $uid, $user, $db_doc);
			echo '</div>';
		}
	} elseif(isSet($_SESSION['barcodeArr']) && count($_SESSION['barcodeArr'])) {
		$bcArr = $_SESSION['barcodeArr'];
		$i = 0;
		foreach($bcArr AS $bcInfo) {
			if($i > 0) {
				echo '<div style="page-break-before:always">';
			} else {
				echo '<div>';
			}
			printBarcode($db_name, $db_object, '', '', $bcInfo['cab'],
			        $bcInfo['doc_id'], $bcInfo['subfolder'], '', '', $user, $db_doc,$tabiD);
			echo '</div>';
			$i++;
		}
		unset($_SESSION['barcodeArr']);
		//cz - 
	} elseif(isset($_GET['subFolders'])) {
		$bcArr = explode(",", $subFolders);
		$i = 0;
		foreach($bcArr AS $subfolder) {
			if($i > 0) {
				echo '<div style="page-break-before:always">';
			} else {
				echo '<div>';
			}
			printBarcode($db_name, $db_object, $barcode, $username, $cabinet,
				$docID, $subfolder, $workflow, $uid, $user, $db_doc, $tabID);
			echo '</div>';
			$i++;
		}
	} elseif(isset($_GET['docIDs'])) {
		$bcArr = explode(",", $docIDs);
		$i = 0;
		foreach($bcArr AS $docID) {
			if($i > 0) {
				echo '<div style="page-break-before:always">';
			} else {
				echo '<div>';
			}
			printBarcode($db_name, $db_object, $barcode, $username, $cabinet,
			$docID, $subfolder, $workflow, $uid, $user, $db_doc, $tabID);
			echo '</div>';
			$i++;
		}
	} else {
		printBarcode($db_name, $db_object, $barcode, $username, $cabinet,
			$docID, $subfolder, $workflow, $uid, $user, $db_doc, $tabID);
	}

	printBarcodeFooter();
	setSessionUser($user);
}


?>
