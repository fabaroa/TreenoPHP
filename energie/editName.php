<?php

include_once '../check_login.php';
include_once ('../classuser.inc');
include_once '../lib/mime.php';
include_once '../energie/energiefuncs.php';

echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Edit Filename</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
</head>
<body class="centered">
ENERGIE;

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$errMsg = "";
	$flag = 0;
	$cab = $_GET['cab'];
	$str = $_GET['str'];
	$newFilenamePart = $_GET['newFilenamePart'];
	$newFilenamePart = urldecode($newFilenamePart); //converts %hex values to non-alphanumeric characters
	$count = $_GET['count'];
	$temp_table = $_GET['table'];
	if (isset ($_GET['fileID'])) {
		$fileID = $_GET['fileID'];
	} else {
		$fileID = 0;
	}
	$mfURL = $_GET['mfURL'];
	if (strpos($mfURL, $_SERVER['PHP_SELF']) !== false) {
		$mfURL = $_SESSION['editNameMFURL'];
	} else {
		$_SESSION['editNameMFURL'] = $mfURL;
	}
	$requestURI = getRequestURI (); 
	//only get these 5 variables from file_search_results.php
	if (strpos($mfURL, "file_search_results") !== false) {
		$cancelEvent = "top.topMenuFrame.removeBackButton();parent.mainFrame.window.location='$mfURL'";
	} else {
		$cancelEvent = "parent.mainFrame.window.location='$mfURL'";
	}
	if ($_POST) {
		$newFilenamePart = $_POST['fname'];
		$newFilenamePart = urldecode($newFilenamePart); //converts %hex values to non-alphanumeric characters

		$doc_id = str_replace("s-", "", strtok($str, ":"));
		$tab = strtok(":");
		$ID = strtok(":");
		$auditTabInfo = "and tab Main";
		if (strcmp($tab, "main") != 0) {
			$tabDir = "$tab/";
			$auditTabInfo = "and tab $tab";
		}

		//check for invalid characters
		$newFilenamePart = str_replace(" ", "_", $newFilenamePart);
		$tmpfilename = str_replace(".", "_", $newFilenamePart);
		$status = $user->invalidCharacter($tmpfilename, ' ');
		if ($status === true)
			$flag = 1;

		if (!$fileID) {
			$whereArr = array(
				"doc_id"		=> (int)$doc_id,
				"ordering"		=> (int)$ID,
				"display"		=> 1,
				"deleted"		=> 0
					 );
			if(strtolower($tab) != "main") {
				$whereArr['subfolder'] = $tab;
			} else {
				$whereArr['subfolder'] = 'IS NULL';
			}
			$fileInfo = getTableInfo($db_object,$cab."_files",array(),$whereArr);
			$result = $fileInfo->fetchRow();
			$filename = $result['parent_filename'];
			$fileID = $result['id'];
			$ext = getExtension($filename);
		} else {
			$whereArr = array(
				"id"		=> (int)$fileID,
				"display"		=> 1,
				"deleted"		=> 0
					 );
			$fileInfo = getTableInfo($db_object,$cab."_files",array(),$whereArr);
			$result = $fileInfo->fetchRow();
			$filename = $result['parent_filename'];
			$ext = getExtension($filename);
		}
		$newFilename = $newFilenamePart;
		if ($ext) {
			$newFilename .= '.'.$ext;
		}
		if (strcmp(strtolower($newFilename), strtolower($filename)) == 0) {
			$errMsg = "Filename not Changed";
			$newFilenamePart = $newFilename;
		} else {
			$whereArr = array ('doc_id' => (int) $doc_id, 'deleted' => 0);
			if(strtolower($tab) != "main") {
				$whereArr['subfolder'] = $tab;
			} else {
				$whereArr['subfolder'] = 'IS NULL';
			}

			lockTables ($db_object, array($cab.'_files'));
			$allNames = getTableInfo ($db_object, $cab.'_files', array
					('DISTINCT(parent_filename)'), $whereArr, 'queryCol');
			if (in_array ($newFilename, $allNames)) {
				$errMsg = "Filename Already Exists";
				$newFilenamePart = $newFilename;
				unlockTables($db_object);
			} elseif ($flag) {
				$errMsg = "Invalid Characters Typed";
				$newFilenamePart = $newFilename;
				unlockTables($db_object);
			} else {
				$whereArr['parent_filename'] = $filename;
				updateTableInfo ($db_object, $cab.'_files', array
						('parent_filename' => $newFilename), $whereArr);
				unlockTables($db_object);
				$user->audit("renamed file", "renamed $filename to $newFilename in cabinet $cab $auditTabInfo");
					echo "<script type=\"text/javascript\">\n";
					if (strpos($mfURL, "file_search_results") !== false) { //redirect back to files_search_results.php
						echo "parent.mainFrame.location = \"$mfURL\";";
					} else { //redirect back to allthumbs.php
						echo<<<ENERGIE
var mySelected = parent.sideFrame.selectedRow;
if(mySelected)
    parent.sideFrame.window.location = "allthumbs.php?cab=$cab&doc_id=$doc_id&table=$temp_table&count=$count&selected="+mySelected;
else
    parent.sideFrame.window.location = "allthumbs.php?cab=$cab&doc_id=$doc_id&table=$temp_table&count=$count";
	//parent.mainFrame.location = "$mfURL";
ENERGIE;
					}
					echo "</script>";
					$errMsg = "New Filename: $newFilenamePart";
				}
		}
	}

	//The following added to not display extensions in text field
	$position = strrpos($newFilenamePart, ".");
	if ($position !== false)
		$displayName = substr($newFilenamePart, 0, $position);
	else
		$displayName = $newFilenamePart;

	echo<<<ENERGIE
<div class="mainDiv" style="height: 128px; width: 288px">
<div class="mainTitle"><span>Edit Filename</span></div>
<div class="error">&nbsp;$errMsg</div>
<form name="editfilename" method="post" action="$requestURI">
<div>
<p><input type="text" name="fname" value="$displayName"/></p>
<p>
<input type="submit" name="B1" value="Submit"/>
<input type="button" name="cancel" onclick="$cancelEvent" value="Cancel"/>
</p>
</div>
</form>
</div>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
</body>
</html>

