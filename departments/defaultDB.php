<?php
// $Id: defaultDB.php 14867 2012-07-03 13:05:47Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';

if($logged_in ==1 && strcmp($user->username,"")!=0) {
	if (isset ($_GET['message'])) {
		$mess = $_GET['message'];
	} else {
		$mess = '';
	}
	if( isSet( $_GET['changeDef'] ) ) {
		$DO_user = DataObject::factory('users', $db_doc);
		$DO_user->get('username', $user->username);
		$DO_user->changeDefaultDepartment($_GET['changeDef']);
	} elseif( isset( $_GET['default'] ) ) {
		switchDepartments($_GET['default'],$user,$db_doc);
echo<<<ENERGIE
<script>
  onload = parent.searchPanel.window.location = "../secure/leftAdmin.php";
  onload = parent.topMenuFrame.window.location = "../energie/menuSlide_NewUI.php";
</script>
ENERGIE;
	}
	//get list of departments --> function found in depfuncs.php
	$depList = getDatabases( $db_doc );
	//get arbitrary names for each department
	$arbList = getLicensesInfo( $db_doc, 'real_department', 'arb_department', 1 );
	uasort( $arbList, "strnatcasecmp" );
	$depOrder = array_keys( $arbList );
	//get the rights for each user in each database --> function found in depfuncs.php
	$rights = getUserDepartmentInfo( $db_doc, $depList );
	//get the default DB for the user
	$defaultDB = getDefaultDB( $db_doc, $user->username );
	//get concurrent licenses
	$licenses = getLicensesInfo( $db_doc, 'real_department', 'max', 1 );

	if($defaultDB)
		$defArb = $arbList[$defaultDB]; 
	else
		$defArb = $arbList[$user->db_name];
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <script>
   function setDefault( DB_name ) {
	  onload = parent.mainFrame.window.location = 'defaultDB.php?changeDef='+DB_name;
   }
   function changeDefault( DB_name ) {
	  onload = parent.mainFrame.window.location = 'defaultDB.php?default='+DB_name;
   }
   function displayMessage( department ) {
  	  var message = document.getElementById(department);
 	  message.firstChild.data = department + ' Has No Licenses.  Unable to set department as default';
   }
   function removeMessage( department ) {
  	  var message = document.getElementById(department);
 	  message.firstChild.data = department;
   }
  </script>
  <style>
  tr.highlight:hover {	background-color: #ebebeb;
						cursor: pointer; }
  </style>
 </head>
 <body>
  <center>
  Current default department is $defArb 
  <form name="def" method="POST" action="defaultDB.php">
  <table class="settings" width="566">
	<tr>
     <td class="tableheads">Department Selection</td>
     <td class="tableheads" width="15" noWrap="yes">Default</td>
    </tr>\n
ENERGIE;
  //print out the list of departments
	for($i=0;$i<sizeof($depOrder);$i++) {
		$DBname = $depOrder[$i];
		$checked = "";

		if( $rights[strtolower($DBname)][$user->username] == "yes" ) {
		//retrieves the database that is set to default
		if( strtolower($defaultDB) == strtolower($DBname) )
			$checked = "checked";

			$var = $arbList[$DBname];
			echo "   <tr class=\"lnk_black highlight\">\n";
			echo "    <td id='$var' style=\"cursor: pointer\" ";
			echo "onmouseover=\"this.style.backgroundColor = '#eeeeee'\" ";
			echo "onmouseout=\"this.style.backgroundColor = '#ffffff'\" ";
			echo "onclick=\"changeDefault('$DBname')\" align='middle'>$var</td>\n";
			if( $licenses[$DBname] == 0 ) {
				echo "    <td width='12' align='middle' onmouseover=\"displayMessage('$var')\""; 
				echo " onmouseout=\"removeMessage('$var')\">&nbsp;\n";
			} else {
				echo "    <td width='12' align='middle'>\n";
				echo "    <input onclick=\"setDefault('$DBname')\" type='radio' ";
				echo " name='defaultDB' value=\"$DBname\" $checked>\n";
			}
echo<<<ENERGIE
	 </td>
    </tr>\n
ENERGIE;
		}
	}

	if( $mess != null ) {
echo<<<ENERGIE
	 <tr>
	  <td>
		<div class="error" id="DBmess">$mess</div>
	  </td>
	 </tr>
ENERGIE;
	}

echo<<<ENERGIE
  </table>
  </form>
  </center>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
