<?php
// $Id: folderCreationSettings.php 14214 2011-01-04 16:11:57Z acavedon $

/* This page handles the file_into_existing setting int the settings table
	0 == disable file_into_existing
	1 == enable file_into_existing with comparison on all index columns
	(0) OR (1) OR (0,1) == enable file_into_existing on indices
*/
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';

if($logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin())
{
	//translated variables
	$systemPref			= $trans['System Preferences'];
	$fileExistingCh		= $trans['File Into Existing Change'];
	$fileExistingSetts	= $trans['Folder Creation Settings'];
	$selectSetts		= $trans['File Into Existing Folder'];
	$enable				= $trans['Enable'];
	$disable			= $trans['Disable'];

	$db_doc = getDbObject ('docutron');
	$sett = new GblStt( $user->db_name, $db_doc );
	if(isset($_POST['changeExisting']))
	{
		$sett->set( "file_into_existing", $_POST['enable'], $user->db_name  );
		$setVal = $sett->get( "compareCols" );
		if( isSet($_POST['compareAll']) ) {
			$setVal = "-1";
		} elseif( isSet($_POST['compareOne']) AND isSet($_POST['compareTwo']) ) {
			$setVal = "0,1";
		} elseif( isSet($_POST['compareOne']) ) {
			$setVal = "0";
		} elseif( isSet($_POST['compareTwo']) ) {
			$setVal = "1";
		}
		
		$sett->set( "compareCols", $setVal, $user->db_name  );
		$compareCols = $setVal;
		$message = $fileExistingCh;
		$enabled = $_POST['enable'];
	}
	else
	{
		$message = '';
		$enabled = $sett->get( "file_into_existing" );
		if( $enabled == "" )
		{
			$sett->set( "file_into_existing", "1", $user->db_name  );
			$enabled = "1";
		}
		$compareCols = $sett->get( "compareCols" );
		if( $compareCols == "" )
		{
			$sett->set( "compareCols", "-1", $user->db_name  );
			$compareCols = $sett->get( "compareCols" );
		}
	}
	
	$statusAll = '';
	$statusOne = '';
	$statusTwo = '';	
	$colArr = explode(",", $compareCols);
	foreach( $colArr AS $column ) {
		if( $column == "-1" ) {
			$statusAll = "checked";
			$statusOne = '';
			$statusTwo = '';
		} elseif( $column == "0" ) {
			$statusOne = "checked";
		} elseif( $column == "1" ) {
			$statusTwo = "checked";
		}
	}

echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>$systemPref</title>
	<script>
		function selectedRadio(compareCol)
		{
			if( compareCol == "compareAll" ) {
				document.getElementById("compareOne").checked = false;
				document.getElementById("compareTwo").checked = false;
			} else if( compareCol == "compareOne" ) {
				document.getElementById("compareAll").checked = false;
			} else if( compareCol == "compareTwo" ) {
				document.getElementById("compareAll").checked = false;
			}
		}

		function disableEnable(radioBut)
		{
			if( radioBut == "enable" ) {
				document.getElementById("compareOne").disabled = false;
				document.getElementById("compareTwo").disabled = false;
				document.getElementById("compareAll").disabled = false;
			} else {
				document.getElementById("compareOne").disabled = true;
				document.getElementById("compareTwo").disabled = true;
				document.getElementById("compareAll").disabled = true;
			}
		}

		function checkDisabled()
		{
			if( $enabled == 0 ) {
				document.getElementById("compareOne").disabled = true;
				document.getElementById("compareTwo").disabled = true;
				document.getElementById("compareAll").disabled = true;
			}
		}
	</script>
 </head>
 <body onload="checkDisabled()">
  <form name="movef" method="POST" action="folderCreationSettings.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="4" class="tableheads">
      Duplicate Folder Notification
     </td>
    </tr>
    <tr>
     <td class="admin-tbl" align="left">
      $selectSetts:
     </td>
ENERGIE;
	if( $enabled == 1 ) {
		$status1 = "checked";
		$status2 = '';
	} else {
		$status2 = "checked";
		$status1 = '';
	}
echo<<<ENERGIE
     <td>
      $enable: 
      <input type=radio value="1" $status1 onclick="disableEnable('enable')" name=enable>
     </td>
     <td>
      $disable: 
      <input type=radio value="0" $status2 onclick="disableEnable('disable')" name=enable>
     </td>
	 <td>&nbsp;</td>
    </tr>
	<tr><td colspan="4">&nbsp</td></tr>
	<tr>
		<td>Compare columns:</td>
		<td>All 
			<input id="compareAll" type=radio value="-1" $statusAll onclick="selectedRadio('compareAll')" name="compareAll">
		</td>
		<td>Column 1 
			<input id="compareOne" type=radio value="0" $statusOne onclick="selectedRadio('compareOne')" name="compareOne">
		</td>
		<td>Column 2 
			<input id="compareTwo" type=radio value="1" $statusTwo onclick="selectedRadio('compareTwo')" name="compareTwo">
		</td>
	</tr>
    <tr>
     <td colspan="4">
ENERGIE;
		//display message if a change was successfully made
		if( $message )
			echo "<div class=\"error\">$message\n";
		else
			echo "<div>\n";

echo<<<ENERGIE
		<input name="changeExisting" type="submit" value="Save"></div>
     </td>
    </tr>
   </table>
  </center>
  </form>
 </body>
</html>
ENERGIE;

	setSessionUser($user);

}
else
{
echo<<<ENERGIE
<html>
 <body bgcolor="#FFFFFF">
  <script>
   document.onload = top.window.location = "../logout.php";
  </script>
 </body>
</html>
ENERGIE;
}
?>
