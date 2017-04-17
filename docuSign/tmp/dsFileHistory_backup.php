<?php
//viewFileHistory.php
include_once '../check_login.php';
include_once '../classuser.inc';

//include_once '../lib/versioning.php';
include_once '../lib/cabinets.php';
include_once '../lib/filter.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>View File Status</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<link rel="stylesheet" type="text/css" href="../versioning/viewFileHistory.css"/>
<script type="text/javascript" src="../versioning/versioning.js"></script>
</head>
<body>
<?php

$db_object = $user->getDbObject();
//$cabinetID = $_GET['cabinetID'];
//$cabinetName = getTableInfo($db_object, 'departments', array('departmentname'), array('departmentid' => (int) $cabinetID), 'queryOne');
//$cabinetName = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $cabinetID), 'queryOne');

	$eSign_cab = (isset($_GET['cab']))? $_GET['cab']:'';
	$eSign_docid = (isset($_GET['doc_id']))? $_GET['doc_id']:'';
	$eSign_tabid = (isset ($_GET['tab_id']))? $_GET['tab_id']:'';
	$eSign_checkedfiles = (isset($_GET['checked_files'])) ? $_GET['checked_files']:'';
	
$cabinetName = $_GET['cab'];
$cabSecurity = $user->checkSecurity($cabinetName);

if ($logged_in == 1 and strcmp($user->username, "") != 0 and $cabSecurity and	$user->checkSetting('docuSign', $cabinetName)) {
		
	$docView = ($user->checkSetting('documentView', $cabinetName)) ? 1 : 0;
	$isRWUser = $cabSecurity == 2;
	$isRWAdmin = ($user->isAdmin() and $isRWUser);
	
	/*$fileID = $_GET['fileID'];
	$parentID = getParentID($cabinetName, $fileID, $db_object);
	if(!is_numeric($parentID)) {
		die('<p style="font-weight: bold; font-size: 11pt">File Not Found</p></body></html>');
	}
	if ($parentID === '0') {
		makeVersioned($cabinetName, $fileID, $db_object);
		$parentID = $fileID;
	}
	$parentAudit = getParentAuditStr($cabinetName, $parentID, $db_object);*/
	$reloadAllThumbs = $_SESSION['allThumbsURL'];

	/*
	//Check-out file
	if (isset ($_POST['checkoutrw']) or isset ($_POST['checkoutro'])) 
	{
		if (isset ($_POST['checkoutrw']))
			$access = 'rw';
		else
			$access = 'ro';
		checkOut($cabinetName, $parentID, $db_object, $access, $user->username);
		$checkOutArgs = "'$cabinetID','$parentID'";
		echo "<script type=\"text/javascript\">\n";
		echo "clickDL($checkOutArgs);</script>\n";
		$user->audit('file checked out', "$parentAudit, access: $access");
	} 
	else	if (isset ($_POST['cancelco'])) 
	{
		$unLocked = unLock($cabinetName, $parentID, $db_object);
		if ($unLocked) 
		{
			$user->audit('file unlocked', $parentAudit);
		}
		//Change the version number
	} 
	else	if (isset ($_POST['chgVer'])) 
	{
		$chgID = $_POST['myID'];
		$newVersion = $_POST['chgVer'];
		$oldVers = changeVersion($parentID, $cabinetName, $chgID, $newVersion, $user, $db_object);
		$myAudit = "Old Version: $oldVers, New Version: $newVersion";
		$user->audit('version changed', "$parentAudit, $myAudit");
		echo "<script type=\"text/javascript\">\n";
		echo "top.sideFrame.location = \"$reloadAllThumbs\";\n";
		echo "</script>\n";
		$parentID = getParentID($cabinetName, $fileID, $db_object);
		//Check-in file
	} 
	else	if (isset ($_FILES['userfile'])) 
	{
		$newVers = checkIn($cabinetName, $parentID, $user, $db_object, $db_doc, $DEFS);
		$user->audit('file checked in', "$parentAudit, Version: $newVers");
		echo "<script type=\"text/javascript\">\n";
		echo "top.sideFrame.location = '$reloadAllThumbs'\n";
		echo "</script>\n";
	} 
	else if (isset ($_POST['yesfreeze'])) 
	{
		freezeFile($cabinetName, $parentID, $user, $db_object);
		$user->audit("file frozen", $parentAudit);
	} else	if (isset ($_POST['unfreeze']))
	{
		unFreezeFile($cabinetName, $parentID, $user, $db_object);
		$user->audit("file unfrozen", $parentAudit);
	}
	*/
	//For Future Translations
	$checkoutrw = "Check-Out File (read/write)";
	$checkoutro = "Check-Out File (read-only)";
	$checkinFile = "Check-In File";
	$version = "Version";
	$versions = "Versions";
	$rollback = "Rollback";
	$delete = "Delete";
	$edit = "Edit";
	$view = "View";
	$save = "Save";
	$submit = "Submit";
	$dateCheckedIn = "Date Checked-In";
	$cancelco = "Cancel Check-Out";
	$freeze = "Freeze File";
	$unfreeze = "Un-Freeze File";
	$fileFrozen = "This file is Frozen to all changes.";
	$addNote = "Add a note with the changed file:";
/*
	$parentInfo = getTableInfo($db_object, $cabinetName.'_files', array(), array('id' => (int) $parentID), 'queryRow');
	//Get filename without extension
	$extPos = strrpos($parentInfo['parent_filename'], '.');
	$myPName = substr($parentInfo['parent_filename'], 0, $extPos);
	$tabName = $parentInfo['subfolder'];
	$docID = $parentInfo['doc_id'];
	$orderingID = $parentInfo['ordering'];
	$user->audit("versioning viewed", $parentAudit);
	*/
	$_SESSION['lastURL'] = getRequestURI (); 
	$reloadArgs = h($_SESSION['lastURL']);
	
/*	$recentID = getRecentID($cabinetName, $parentID, $db_object);
	echo "<script type=\"text/javascript\">\n";
	echo "displayCurrVersNotes($recentID);\n";
	echo "</script>\n";

	$versFileList = getTableInfo($db_object, $cabinetName.'_files', array(),
		array('deleted' => 0, 'parent_id' => (int) $parentID), 'queryAll',
		array('v_major' => 'DESC', 'v_minor' => 'DESC'));
	$numberVersions = numberOfVersions($cabinetName, $parentID, $db_object);
	if (isLocked($cabinetName, $parentID, $db_object)) {
		$checkedOut = true;
	} else {
		$checkedOut = false;
	}
	echo "<div id=\"fileName\" class=\"myTitle\">File: $myPName</div>\n";
	echo "<div class=\"myTitle\">$numberVersions ";
	if ($numberVersions > 1) {
		echo $versions;
	} else {
		echo $version;
	}

	$whoLockedIt = whoLocked($cabinetName, $parentID, $db_object);
	echo "</div>\n";*/
	
/*	echo "<div id=\"buttonDiv\">\n";
	echo "<form method=\"post\" action=\"$reloadArgs\">\n";
	echo "<div>\n";
	$isNotFrozen = !fileIsFrozen($cabinetName, $parentID, $db_object);

	if (!$checkedOut and $isRWUser) {
		echo "<input type=\"submit\" name=\"checkoutrw\"";
		echo " value=\"$checkoutrw\"/>\n";
		if ($isRWAdmin) {
			echo "<input type=\"button\" onclick=\"askfreeze();\"";
			echo " value=\"$freeze\"/>\n";
		}
	} else
		if ($isNotFrozen) {
			echo "<input type=\"submit\" name=\"checkoutro\"";
			echo " value=\"$checkoutro\"/>\n";
		}
	echo "</div>\n";
	echo "</form>\n";
	$checkedOutByUser = $whoLockedIt == $user->username;
	if ($isNotFrozen and ($checkedOutByUser or ($checkedOut and $isRWAdmin))) {
		echo "<form method=\"post\" action=\"$reloadArgs\">\n";
		echo "<div>\n";
		if ($checkedOutByUser) {
			echo "<input type=\"button\" id=\"checkinButton\" ";
			echo "value=\"$checkinFile\" onclick=\"toggleCheckin();\"/>\n";
		}
		echo "<input type=\"submit\" name=\"cancelco\" ";
		echo "value=\"$cancelco\" />\n";
		echo "</div>\n";
		echo "</form>\n";
	}
	if (!$isNotFrozen) {
		echo "<h1>$fileFrozen</h1>\n";
		if ($isRWAdmin) {
			echo "<form method=\"post\" action=\"$reloadArgs\">\n";
			echo "<div style=\"text-align: center\"><input type=\"submit\" name=\"unfreeze\" value=\"$unfreeze\" /></div>\n";
			echo "</form>\n";
		}
	}
	$delAudit = $parentAudit;
	$dispAudit = urlencode($parentAudit);
	echo "</div>\n";
	echo "<div id=\"delDiv\">\n";
	echo "<p class=\"myTitle\">The following will be deleted.</p>\n";
	echo "<form method=\"post\" action=\"confDel.php?cabinetID=$cabinetID\">\n";
	echo "<div>\n";
	echo "<input type=\"submit\" name=\"yesdelete\" value=\"Delete\"/>\n";
	echo "<input type=\"submit\" name=\"nodelete\" value=\"Do not delete\"/>\n";
	echo "<input type=\"hidden\" id=\"myaction\" name=\"myaction\"/>\n";
	echo "<input type=\"hidden\" id=\"delID\" name=\"delID\"/>\n";
	echo "<input type=\"hidden\" id=\"delVer\" name=\"delVer\"/>\n";
	echo "<input type=\"hidden\" name=\"pAudit\" value=\"$delAudit\"/>\n";
	echo "</div>\n";
	echo "</form>\n";
	echo "</div>\n";
	echo "<div id=\"freezeDiv\">\n";
	echo "<p class=\"myTitle\">This file will be frozen to all changes.</p>\n";
	echo "<div class=\"error\" style=\"font-weight: bold\">Only the Super Administrator can unfreeze a file.</div>\n";
	echo "<div class=\"error\" style=\"font-weight: bold\">Continue?</div>\n";
	echo "<form method=\"post\" action=\"$reloadArgs\">\n";
	echo "<div>\n";
	echo "<input type=\"submit\" name=\"yesfreeze\" value=\"Freeze File\"/>\n";
	echo "<input type=\"submit\" name=\"nofreeze\" value=\"Do not Freeze File\"/>\n";
	echo "</div>\n";
	echo "</form>\n";
	echo "</div>\n";
	echo "<div id=\"mainVersDiv\">\n";
	echo "<div class=\"error\" id=\"pageErrMsg\">X</div>\n";
	echo "<div id=\"flocked\" class=\"error\">File Locked by $whoLockedIt at {$parentInfo['date_locked']}</div>\n";
	if ($checkedOut and strcmp($whoLockedIt, "9FROZEN") != 0) {
		echo "<script type=\"text/javascript\">\n";
		echo "document.getElementById('flocked').style.visibility = 'visible';\n";
		echo "</script>\n";
	}
	$allNotes = getTableInfo($db_object, $cabinetName.'_files', array('v_major', 'v_minor', 'notes'),
		array('parent_id' => (int) $parentID, 'deleted' => 0), 'queryAll',
		array('v_major' => 'DESC', 'v_minor' => 'DESC'));
	$hasVersions = false;
	foreach ($allNotes as $note) {
		if ($note['notes']) {
			$hasVersions = true;
			break;
		}
	}
	if ($hasVersions) {
		echo "<div>\n";
		echo "<div id=\"noteBar\" onclick=\"showVersNotes();\">\n";
		echo "<img id=\"clkMore\" src=\"../energie/images/next.gif\" alt=\"Notes\" />\n";
		echo "<span class=\"myTitle\">Notes</span>\n";
		echo "</div>\n";
		echo "<div id=\"noteDiv\">\n";
		foreach ($allNotes as $note) {
			$note['notes'] = h($note['notes']);
			if ($note['notes']) {
				$myNotes = $note['notes'];
				$tmp = strtok($myNotes, "{");
				$m_username = str_replace(" ", "", $tmp);
				$showprev = '';
				$showprev .= "<div class=\"lnk_black showVers\">\n";
				$showprev .= "<span>$version ".$note['v_major'].'.';
				$showprev .= $note['v_minor']."</span>\n";
				$showprev .= "</div>\n";
				while ($tmp == ",") {
					$m_username = strtok(",");
					$time = strtok(",");
					$currNote = strtok("}");
					$showprev .= "<div class=\"lnk_black\">";
					$showprev .= "$m_username - $time - $currNote</div>\n";
					$tmp = "";
					$tmp = strtok("{");
				}
				echo $showprev;
			}
		}
		echo "</div>\n";
		echo "</div>\n";
	}
*/
	$save = 'Refresh';
	$version = 'Status';
	$dateCheckedIn = 'Env_Created';
	
	echo "<table id=\"table\">\n";
	echo "<tr class=\"tableheads\">\n";
	echo "<th id=\"saveCol\" class=\"iconCol\">$save</th>\n";
	echo "<th id=\"viewCol\" class=\"iconCol\">$view</th>\n";
	/*if ($user->username=='admin' or ($isRWAdmin and $isNotFrozen and $user->checkSetting('deleteFiles', $cabinetName))) {
		echo "<th id=\"rbCol\" class=\"iconCol\">$rollback</th>\n";
		echo "<th id=\"delCol\" class=\"iconCol\">$delete</th>\n";
	}*/
	echo "<th id=\"versCol\">$version</th>\n";
	echo "<th>Username</th>\n";
	echo "<th>$dateCheckedIn</th>\n";
	echo "</tr>\n";
	$i = 1;

	$res = getTableInfo($db_object,$eSign_cab,array(),array('doc_id'=>(int)$eSign_docid));

	$row2 = $res->fetchRow();
	$folderLoc = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $row2['location']).'/';
	//
	$myFileIDs = explode("_next-file-id_", $eSign_checkedfiles);
	
	error_log("Number of file(s) selected to be signed: ".count($myFileIDs));
	$index = 0;
	// find subfolder and filename
	$usedVersions = '';
	foreach($myFileIDs as $fileID)
	{
		$query = "SELECT t12.*, status, tmCreate FROM (SELECT t1.*, envID FROM ";
		$query .= "(SELECT id, filename, subfolder, who_indexed FROM ".$eSign_cab."_files " ;
		$query .= "WHERE id=$fileID) AS t1 ";
		//$query .= "display=1 AND deleted=0 AND subfolder='$tab' ORDER BY ordering ASC) AS t1 ";
		$query .= "LEFT OUTER JOIN {$eSign_cab}_dsfiles AS t2 ON t1.id=t2.origfileid) AS t12 ";
		$query .= "LEFT OUTER JOIN {$eSign_cab}_envelopes AS t3 ON t12.envid=t3.envid";		
			
		error_log("query: ".$query);
		$fileArr = $db_object->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, true, true);
		
		error_log("query result: ".count($query));
		error_log("query result: ".print_r($query, false));
		
		$row = getTableInfo($db_object, $eSign_cab.'_files', array(), array('id' => (int) $fileID), 'queryRow');				
		$filename = $row['filename'];
		
		$filenames[$index] = $filename;
		
		if($row['subfolder']) {
			$loc = $folderLoc.$row['subfolder'].'/';
		}
		else
		{
			$loc = $folderLoc;
		}
		
		$arrFullFilePathName[$index] = $loc.$filename.'_fileid_'.$fileID;
		$index += 1;		
		
		error_log("File ".$index.": ".$loc.$filename);
		//continue;
	/*}	
	
	
	$usedVersions = '';
	foreach ($versFileList as $currFile) 
	{*/
		$j = 0;
		$id = $fileID;	//$currFile['id'];
		//$ordering = $currFile['ordering'];
		//$subfolder = $currFile['subfolder']; 
		$fArr = $fileArr[$fileID][0];
		//continue;
		$date_created = $fArr['tmcreate'];	//$currFile['date_created'];
		$status = $fArr['status'];
		$who_created = $fArr['who_indexed'];//$currFile['who_indexed'];
		
		//$clkArgDel = "'$cabinetID','$id'";
		//$clkArgOther = $clkArgDel.","."'$reloadArgs'";
		//$dispArgs = "'$cabinetName','$docID', '$ordering', '$subfolder', '$id', '$reloadArgs'";
		//$clkArgDel .= ",'true'";
		$rowNum = $i;
		$rowID = 'row'.$rowNum;
		$paraVerID = 'paraVer'.$id;
		//$currVer = $currFile['v_major'].'.'.$currFile['v_minor'];
		//$dispCurrVer = urlencode($currVer);
		echo "<tr class=\"lnk_black\" id=\"$rowID\" ";
		echo "onmouseover=\"mOver('$rowID');\" ";
		echo "onmouseout=\"mOut('$rowID');\">\n";
		echo "<td id=\"$rowID-$j\" class=\"icon clickMe\">\n";
		//echo "<td id=\"$rowID-$j\" class=\"icon clickMe\" onclick=\"saveVers('$cabinetID', '$id', '$dispAudit', '$dispCurrVer');\">\n";
		echo "<img src=\"../energie/images/save.gif\" alt=\"$save\" />\n";
		echo "</td>\n";
		$j ++;
		echo "<td id=\"$rowID-$j\" class=\"icon clickMe\">\n";
		//echo "<td id=\"$rowID-$j\" class=\"icon clickMe\" onclick=\"top.topMenuFrame.dispVers($dispArgs, '$dispAudit', '$dispCurrVer','$docView');\">\n";
		echo "<img src=\"../energie/images/smallpaper.gif\" alt=\"$view\" />\n";
		echo "</td>\n";
		$j ++;
		/*if ($user->username=='admin' or ($isRWAdmin and $isNotFrozen and $user->checkSetting('deleteFiles', $cabinetName)))
		{
			if ($usedVersions) {
				$usedVersions .= ','.$currFile['v_major'].'.'.$currFile['v_minor'];
			} else {
				$usedVersions = $currFile['v_major'].'.'.$currFile['v_minor'];
			}
			echo "<td id=\"$rowID-rb\" class=\"icon clickMe\" ";
			echo "onclick=\"clickRollBack($clkArgOther, '$rowNum', '$currVer');\">\n";
			echo "<img src=\"../images/arrow.red.gif\" alt=\"$rollback\" />\n";
			echo "</td>\n";
			$j ++;
			$jScriptArgs = "onclick=\"clickDelete($clkArgOther, '$rowNum', '$currVer');\"";
			echo "<td id=\"$rowID-delete\" class=\"icon clickMe\" $jScriptArgs>\n";
			echo "<img src=\"../energie/images/trash.gif\" ";
			echo "alt=\"$delete\" />\n";
			echo "</td>\n";
			$j ++;
			echo "<td id=\"$rowID-$j\" class=\"versEdit\">\n";
			echo "<p id=\"$paraVerID\">$currVer</p>\n";
			echo "<img id=\"$rowID-versEdit\" class=\"clickMe\" ";
			echo "src=\"../energie/images/file_edit_16.gif\" ";
			echo "alt=\"$edit\" ";
			echo "onclick=\"toggleEdit('$paraVerID', $id);\"/>\n";
		} 
		else */
		//{
			echo "<td class=\"versEdit\">\n";
			echo "<p>$status</p>\n";
		//}
		echo "</td>\n";
		$j ++;
		echo "<td id=\"$rowID-$j\" style=\"text-align:center\"><span>$who_created</span></td>";
		$j ++;
		echo "<td id=\"$rowID-$j\" class=\"Date\" >\n";
		echo "<p>$date_created</p>\n";
		echo "</td>\n";
		echo "</tr>\n";
		$i ++;
	}

	echo "</table>\n";
	echo "</div>\n";
/*	if ($isRWAdmin) {
		echo "<div class=\"myDlg\" id=\"versionDiv\">\n";
		echo "<p class=\"myTitle\" id=\"chgTitle\">X</p>\n";
		echo "<form id=\"vForm\" action=\"$reloadArgs\" method=\"post\" ";
		echo "onsubmit=\"return submitEdit();\">\n";
		echo "<div>\n";
		echo "<input size=\"6\" name=\"chgVer\" id=\"chgVer\" ";
		echo "type=\"text\"/>\n";
		echo "<input type=\"hidden\" name=\"myID\" id=\"myID\"/>\n";
		echo "<input id=\"usedVersions\" type=\"hidden\" ";
		echo "value=\"$usedVersions\"/>\n";
		echo "<input name=\"submitButton\" type=\"button\" value=\"Submit\" ";
		echo "onclick=\"return submitEdit();\"/>\n";
		echo "</div>\n";
		echo "</form>\n";
		echo "<div id=\"errMsg\" class=\"error\">X</div>\n";
		echo "</div>\n";
	}*/

/*	echo "<form enctype=\"multipart/form-data\" id=\"checkinForm\" ";
	echo "action=\"$reloadArgs\" method=\"post\">\n";
	echo "<div class=\"myDlg\" id=\"checkinDiv\">\n";
	echo "<p class=\"myTitle\">Check-In File</p>\n";
	echo "<p><input id=\"userfile\" name=\"userfile\" type=\"file\"/></p>\n";
	echo "<div class=\"subheading\">$addNote</div>\n";
	echo "<div>\n";
	echo "<textarea rows=\"5\" cols=\"30\" name=\"addednote\"></textarea>\n";
	echo "</div>\n";
	echo "<p><input type=\"submit\" value=\"$submit\"/></p>\n";
	echo "</div>\n";
	echo "</form>\n";
*/
	setSessionUser($user);
} else {
	echo "<script type=\"text/javascript\">document.onload = top.window.location ";
	echo "= \"../logout.php\"</script>\n";
}
?>
</body>
</html>
