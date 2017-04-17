<?php

include_once '../check_login.php';
include_once '../classuser.inc';
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
<script type="text/javascript">	
function GotoDocuSignApp()
{
	//alert(document.dsfhForm.box.value);
	//return;
	var c_value = "";
	if(document.dsfhForm.box != null)
	{
		if(document.dsfhForm.box.length == undefined)
		{
			if (document.dsfhForm.box.checked)
			{
				c_value = c_value + document.dsfhForm.box.value;
			}
		}
		else
		{
			for (var i=0; i < document.dsfhForm.box.length; i++)
			{
			   if (document.dsfhForm.box[i].checked)
			   {
			      if(c_value != "" )
			      {
			    	  c_value = c_value + "_next-file-id_";
			      }
			      c_value = c_value + document.dsfhForm.box[i].value;
			      
			   }
			}
		}
	}

	if (c_value == "")
	{
		alert("No document is selected.");
		return;
	}
	else
	{
		//alert("Selected documents: "+ c_value);
	}
/*
	var frames = "";
	for (var i=0; i < top.frames.length; i++)
	{
		frames = frames + "Frame "+ i+ ": "+ top.frames[i].name+"; \n";
	}
	alert(frames);
*/
		
	var test = document.dsfhForm.prevQuery.value + "checked_files=" + c_value;
	//alert("Go to docuSignLogin.php?"+test);
	top.mainFrame.window.location = 'docuSignLogin.php?' + test;
	
	top.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
	top.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
	
	//parent.viewFileActions.window.location = '../energie/bottom_white.php';
	top.sideFrame.window.location = '../energie/left_blue_search.php';
}
</script>
</head>
<body>
<?php

$db_object = $user->getDbObject();

$prevQuery = $_SERVER['QUERY_STRING'];
//echo($prevQuery."\n");
$predQuery = substr($prevQuery, 0, strpos($prevQuery, 'checked_files'));
//echo($predQuery."\n");

$eSign_cab = (isset($_GET['cab']))? $_GET['cab']:'';
$eSign_docid = (isset($_GET['doc_id']))? $_GET['doc_id']:'';
$eSign_tabid = (isset ($_GET['tab_id']))? $_GET['tab_id']:'';
$eSign_checkedfiles = (isset($_GET['checked_files'])) ? $_GET['checked_files']:'';
	
//$cabinetName = $_GET['cab'];
$cabSecurity = $user->checkSecurity($eSign_cab);

if ($logged_in == 1 and strcmp($user->username, "") != 0 and $cabSecurity and	$user->checkSetting('docuSign', $eSign_cab)) 
{
	$th_view = 'View';
	$th_status = 'Status';
	$th_EnvCreated = 'Env_Created';
	$th_envid = 'Env_ID';
	$th_filename = 'file';
?>
	<div>
	<form name=dsfhForm><!-- method=post action='' -->
	<input type=hidden name=prevQuery value="<?php echo($predQuery); ?>" />
	<table id="table">
	<tr class="tableheads">
	<th id="fileCol"><?php echo($th_filename); ?></th>
	<th id="versCol"><?php echo($th_status); ?></th>
	<th><?php echo($th_EnvCreated); ?></th>
	<th><?php echo($th_envid); ?></th>
	<th id="viewCol" class="iconCol"><?php echo($th_view); ?></th>
	</tr>
	
<?php

	$_SESSION['lastURL'] = getRequestURI (); 
	$reloadArgs = h($_SESSION['lastURL']);
	
	$docView = ($user->checkSetting('documentView', $eSign_cab)) ? 1 : 0;
	
	$i = 1;

	$res = getTableInfo($db_object,$eSign_cab,array(),array('doc_id'=>(int)$eSign_docid));

	$row2 = $res->fetchRow();
	$folderLoc = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $row2['location']).'/';
	//
	$myFileIDs = explode("_next-file-id_", $eSign_checkedfiles);
	
	//error_log("Number of file(s) selected to view DocuSignstatus: ".count($myFileIDs));
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
			
		//error_log("query: ".$query);
		$fileArr = $db_object->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, true, true);
		
		$row = getTableInfo($db_object, $eSign_cab.'_files', array(), array('id' => (int) $fileID), 'queryRow');				
		$filename = $row['filename'];
		
		$filenames[$index] = $filename;
		
		$subfolder = $row['subfolder'];
		$dispArgs = "'$folderLoc','$subfolder', '$filename'";
		
		if($row['subfolder']) {
			$loc = $folderLoc.$row['subfolder'].'/';
		}
		else
		{
			$loc = $folderLoc;
		}
		
		$arrFullFilePathName[$index] = $loc.$filename.'_fileid_'.$fileID;
		$index += 1;		
		
		//error_log("File ".$index.": ".$loc.$filename);

		$j = 0;
		$id = $fileID;

		$fArr = $fileArr[$fileID][0];
		$filename = isset($fArr['filename'])? $fArr['filename']:'';
		$date_created = isset($fArr['tmcreate'])? $fArr['tmcreate']:'';	
		$status = isset($fArr['status'])? $fArr['status']:'';
		$who_created = isset($fArr['who_indexed'])? $fArr['who_indexed']:'';
		$envid = isset($fArr['envid'])? $fArr['envid']:'';
		
		$rowNum = $i;
		$rowID = 'row'.$rowNum;
		echo "<tr class=\"lnk_black\" id=\"$rowID\" ";
		echo "onmouseover=\"mOver('$rowID');\" ";
		echo "onmouseout=\"mOut('$rowID');\">\n";
		
		echo "<td id=\"$rowID-$j\" >\n";//class=\"versEdit\"
		if($envid == '')
		{
			echo "<p><input type=checkbox name=box value='$fileID' checked>$filename</p>\n";	
		}
		else
		{
			echo "<p>$filename</p>\n";	
		}
		echo "</td>\n";
		$j ++;
		
		echo "<td id=\"$rowID-$j\" style=\"text-align:center\">\n";
		echo "<p>$status</p>\n";	
		echo "</td>\n";
		//$j ++;
		//echo "<td id=\"$rowID-$j\" style=\"text-align:center\"><span>$who_created</span></td>";
		$j ++;
		echo "<td id=\"$rowID-$j\" class=\"Date\" >\n";
		echo "<p>$date_created</p>\n";
		echo "</td>\n";
		$j ++;
		echo "<td id=\"$rowID-$j\" style=\"text-align:center\">\n";
		echo "<p>$envid</p>\n";
		echo "</td>\n";
			
		$j ++;
		//echo "<td id=\"$rowID-$j\" class=\"icon clickMe\">\n";
		echo "<td id=\"$rowID-$j\" class=\"icon clickMe\" onclick=\"top.topMenuFrame.dispSignedFile($dispArgs, '$reloadArgs', '$docView');\">\n";
		echo "<img src=\"../energie/images/smallpaper.gif\" alt=\"$th_view\" />\n";
		echo "</td>\n";	
		
		echo "</tr>\n";
		$i ++;
	}
?>
	</table>
	<br/>
	<input type=button onclick="GotoDocuSignApp()" value="Proceed"/>
	<!-- input name="Submit" id="submit" tabindex="7" value="Proceed" type="submit" align="left"/><br/-->
	<br/>
	</form>
	</div>
<?php
	setSessionUser($user);
} 
else 
{
	echo "<script type=\"text/javascript\">document.onload = top.window.location ";
	echo "= \"../logout.php\"</script>\n";
}
?>
</body>
</html>
