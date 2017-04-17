<?php
// $Id: editDBName.php 14867 2012-07-03 13:05:47Z fabaroa $
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';
if($logged_in ==1 && strcmp($user->username,"")!=0) {
  $badChar         = "Invalid Character";
  $DBExists = "Department Already Exists";
  $createDB = "Department Created";
  $DBPermissions = "Permissions Successfully Changed";
  $DBChange = "Department Name Successfully Changed";
 
 	if (isset ($_GET['message'])) {
		$mess = $_GET['message'];
	} else {
		$mess = '';
	}
  if( $user->isDepAdmin() ) {
  //get arbitrary names for each department
  $arbList = getLicensesInfo( $db_doc, 'real_department', 'arb_department', 1 );
  uasort( $arbList, "strnatcasecmp" );
  $depList = array_keys( $arbList );
	if (isset($_GET['department'])) {
		$department = $_GET['department'];
	} else {
		$department = '';
	}
  if( isset( $_POST['newDep'] ) ) {
	$newDep = $_POST['newDep'];
 	$realDep = $_POST['realDep'];	

	$check = $newDep;	
	//checks for invalid characters
	$pool = $user->characters( 4 );
	$numbers = $user->characters( 2 );
	$statusCheck = false;
	for( $c=0;$c<strlen($check);$c++ ) {
 		$status = strrpos($pool, $check{$c});
        if($status === false) {
			echo "-$c-";
        	$statusCheck = true;
		}
    }
    if( isset($check{0}) and is_numeric(strrpos($numbers,$check{0})) )
        	$statusCheck = true;
		
	if($statusCheck == true) {
echo<<<ENERGIE
<script>
   onload = parent.mainFrame.window.location = "editDBName.php?message=$badChar";
</script>
ENERGIE;
die();
	}
	if(getTableInfo($db_doc,'licenses',array('COUNT(real_department)'),array('arb_department'=>$newDep),'queryOne') == 0) {
		$updateArr = array('arb_department'=>$newDep);
		$whereArr = array('real_department'=> $realDep);
		updateTableInfo($db_doc,'licenses',$updateArr,$whereArr);
echo<<<ENERGIE
<script>
	onload = parent.topMenuFrame.window.location = "../energie/menuSlide_NewUI.php";
	onload = parent.mainFrame.window.location = "editDBName.php?message=$DBChange";
</script>
ENERGIE;
	} else {
echo<<<ENERGIE
<script>
	onload = parent.mainFrame.window.location = "editDBName.php?message=$DBExists $newDep";
</script>
ENERGIE;
	}
  }
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <script>
   function getSelected() {
		val = document.editDep.DepartmentList[document.editDep.DepartmentList.selectedIndex].value;
		if( val != "default") {
			onload = parent.mainFrame.window.location = 'editDBName.php?department='+val;
		}
   }
   function ent( e ) {
		if(window.event)
			key = window.event.keyCode;
		else if (e) 
			key = e.which;
		else return true;

		if (key == 13) {
			editDep.submit();
			return false;
		} else
			return true;
   }
  </script>
 </head>
ENERGIE;
	if( $department )
		echo "<body onload=\"document.editDep.newDep.focus();\">\n";
	else
		echo "<body>\n";
echo<<<ENERGIE
  <form name="editDep" method="POST" action="editDBName.php">
  <center>
  <table class="settings" width="315">
   <tr>
    <td class="tableheads" colspan="2">Edit Department Name</td>
   </tr>
   <tr>
    <td colspan="2">	
     <select name="DepartmentList" onchange="getSelected()">
      <option value="default">Choose Department</option>
ENERGIE;
		for($i=0;$i<sizeof($depList);$i++) {
			$tmp = $depList[$i]; 
			$var = $arbList[$tmp];
			if( $user->isUserDepAdmin( $user->username, $tmp ) ) {
				if($tmp != $department )
					echo"\n           <option value=\"$tmp\">$var</option>\n";
				else
					echo"\n           <option selected value=\"$tmp\">$var</option>\n";
			}	
		}
echo<<<ENERGIE
      </select>
     </td>
    </tr>
ENERGIE;
	if( $department ) {
		echo "<tr>";
		echo "<td><input type='text' name='newDep' onKeyPress=\"return ent(event)\">";
		echo "<input type='hidden' name='realDep' value='$department'></td>";
		echo "<td><input type='submit' name='B1' value='Save'></td>";
		echo "</tr>";
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
  </center>
  </form>
 </body>
</html>
ENERGIE;
	} else {
		logUserOut();
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
