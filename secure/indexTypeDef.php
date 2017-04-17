<?php

include_once '../check_login.php';
include_once ('../classuser.inc');
include_once '../modules/modules.php';
include_once '../settings/settings.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isDepAdmin()) {
	//variables that may have to be translated
	$tableTitle = $trans['Indexing Type Definitions'];
	$defErased = $trans['Definitions Erased'];
	$updatedStrings = $trans['Updated Strings'];
	$addDefinition = $trans['Add Definition'];
	$updateButton = $trans['Update'];
	$selectIndex = $trans['Select an Index'];
	$cabinet = $trans['Cabinet'];
	$add = $trans['Add'];
	$indexField = $trans['Index Field'];
	$updateDef = "Definition has been added ";
	$duplicateDef = "Definition already exists ";

	$db_doc = getDbObject ('docutron');
	$settings = new GblStt($user->db_name, $db_doc); //establish the system preferences object
	//Gets cabinet names for Indexing Type Definitions 
	if (!empty ($_GET['DepID'])) {
		$DepID = $_GET['DepID'];
	} elseif (!empty ($_POST['DepID'])) {
		$DepID = $_POST['DepID'];
	} else {
		$DepID = '';
	}
	$user->addCabinetJscript("preferences");
	//Gets the indices for selected cabinet in Indexing Type Definitions
	if (!empty ($_POST['fieldname'])) {
		$fieldname = $_POST['fieldname'];
	} else {
		$fieldname = '';
	}
	//variable for definition to be added
	if (!empty ($_POST['nDef'])) {
		$nDef = $_POST['nDef'];
	} else {
		$nDef = '';
	}
	//submit button for add definitions
	if (!empty ($_POST['update'])) {
		$update = $_POST['update'];
	} else {
		$update = '';
	}
	if (!empty ($_POST['delete'])) {
		$delete = $_POST['delete']; //submit button for delete definitions
	} else {
		$delete = '';
	}
	if (!empty ($_POST['dropDef'])) {
		$dropDef = $_POST['dropDef']; //variable for drop value selected
	} else {
		$dropDef = '';
	}
	if (!empty ($_POST['MultiSelect'])) {
		$MultiSelect = $_POST['MultiSelect']; //variable for checkbox\ selected
	} else {
		$MultiSelect = '';
	}

	if ($fieldname != NULL) {
		$str = "dt,".$user->db_name.",".$_POST['DepID'].",".$_POST['fieldname'];
		$tmp = $settings->get($str);
		$defList = array ();
		if($tmp) {
			$defList = explode(",,,", $tmp);
		}
	}
	$message = '';
	//if either submit buttons were pressed
	if (($update != null) || ($delete != null) || isset ($nDef)) {
		//variables for $settings object
		$key = "dt,".$user->db_name.",".$DepID.",".$fieldname;
		$cab = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $DepID), 'queryOne');
		$cbKey = $user->db_name."_".$cab."_CheckBox_Index,".$fieldname;
		$cbValue = $MultiSelect;
		$MultiSelect="";
		//if submitted and there is a definition variable to be deleted
		if ($dropDef && $delete) {
			$defList = array_diff($defList, array ($dropDef));
			$message = "$updateDef $nDef";
			usort($defList, 'strnatcasecmp');
			$defStr = implode(",,,", $defList);
error_log($cbKey."|".$MultiSelect);
			if ($defStr != "")
			{
				$settings->set($key, $defStr);
				if ($cbValue) $settings->set($cbKey, $cbValue);
				$MultiSelect = $settings->get($cbKey);
			}
			else
			{
				$settings->removekey($key);
				$settings->removekey($cbKey);
			}
			$message = "$defErased: $dropDef";
		}
		//if submitted and there is a definiton variable to be added
		elseif ($nDef != null) 
		{
			if (!in_array(strtolower($nDef), $defList)) {
				$defList[] = trim($nDef);
				$message = "$updateDef $nDef";
			} else {
				$message = "$duplicateDef $nDef";
			}
			usort($defList, 'strnatcasecmp');
			$defStr = implode(",,,", $defList);
			$settings->set($key, $defStr);
			if ($cbValue) $settings->set($cbKey, $cbValue);
		}
error_log($cbKey."|".$MultiSelect);
	}

	echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>System Preferences</title>
  <script>
	function setFocus() {
		if(document.getElementById('nDef')) {
			document.getElementById('nDef').focus();
		}
	}
  </script>
 </head>
 <body onload="setFocus()">
  <form name="preferences" method="POST" action="indexTypeDef.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="2" class="tableheads">$tableTitle</td>
    </tr>
    <tr>
     <td class="admin-tbl">$cabinet: 
ENERGIE;
	//Displays Drop Down Menu of Cabinets
	$user->getDropDown("indexTypeDef.php", $user, 1);
	$db_object = $user->getDbObject();
	echo "</td>\n";

	if ($DepID != NULL) {
		echo "<td class=\"admin-tbl\">$indexField: ";
		echo "<select name=\"fieldname\" ";
		echo "onchange=\"submit();\">\n";

		if ($fieldname != null)
			echo "<option selected value=\"$fieldname\">".str_replace("_"," ",$fieldname)."</option>\n";
		else
			echo "<option selected value=\"\">$selectIndex</option>\n";

		//This function is located in lib/utility.php
		$cab = getTableInfo($db_object, 'departments', array('real_name'), array('departmentid' => (int) $DepID), 'queryOne');
		//This function is located in lib/utility.php
		$fieldnames = getCabinetInfo($db_object, $cab);
		usort($fieldnames, 'strnatcasecmp');
		for ($i = 0; $i < sizeof($fieldnames); $i ++) {
			$field = $fieldnames[$i];
			if (strcmp($fieldname, $field) != 0)
				echo "<option value=\"$field\">".str_replace("_"," ",$field)."</option>\n";
		}
		echo "</select>";
		//sends the DepID
		echo "<input type =\"hidden\" name=\"DepID\" value=\"$DepID\">";
		echo "<input type=\"checkbox\" name=\"MultiSelect\" value=\"checked\" $MultiSelect onchange=\"submit();\" />Make Checkbox";
		echo "</td>\n";

		//if there is a DepID and an index selected
		if ($fieldname != null) {
			echo "</tr>";
			echo "<tr><td class=\"admin-tbl\">";
			echo $addDefinition;
			echo "</td><td class=\"admin-tbl\">";
			//lets users add definitons in variable "nDef"
			//echo "<input type=\"text\" name=\"nDef\" onKeyPress=\"if(event.keyCode == 13){submit();}\">";
			echo "<input type=\"text\" id=\"nDef\" name=\"nDef\">"; //onKeyPress=\"if(event.keyCode == 13){submit();}\">";
			echo "<input type=\"submit\" name=\"update\" value=\"$add\">";
			echo "</td></tr>\n";

			//if there is a string in the DB, allow user to select them to be deleted in variable "dropDef"
			if ($defList) {
				//translations needed down here because page refreshes
				$deleteDefs = $trans['Delete Definition'];
				$delete = $trans['Delete'];
				
				echo "<tr><td class=\"admin-tbl\">\n";
				echo "$deleteDefs: ";
				echo "</td><td class=\"admin-tbl\">";
				echo "<select name=\"dropDef\">";
				echo "<option selected value=\"\">$selectIndex</option>";
				foreach ($defList as $def)
					echo "<option value =\"$def\">$def</option>\n";

				echo "</select>\n";
				echo "<input type=\"submit\" name=\"delete\" value=\"$delete\">";
				echo "</td></tr>\n";
			}
		}

	} //end of check for DepID	

	echo "</tr>\n";

	//display message if a change was successfully made
	if ($message) {
		echo<<<ENERGIE
		<tr>
		 <td colspan='2'>
			<div class="error">$message</div>
		 </td>
		</tr>
ENERGIE;
	}

	echo<<<ENERGIE
   </table>
  </center>
  </form>
 </body>
</html>
ENERGIE;

	setSessionUser($user);

	//stuff
} else //log them out
	{
	//redirect them to login
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
