<?php
// $Id: cabinetAccess.php 14300 2011-03-21 17:36:50Z acavedon $

include_once '../check_login.php';
include_once ( '../classuser.inc');

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ) {
  $dieMessage        = $trans['dieMessage']; 
  $selectCabLabel    = $trans['Choose Cabinet'];
  $tableTitle        = $trans['Cabinet Access']; 
  $cabLabel          = $trans['Cabinet'];   
  $usernamed         = $trans['Username'];
  $r_w               = $trans['read_write']; 
  $r_o               = $trans['read_only'];
  $none              = $trans['no_permissions'];
  //$update            = $trans['Update'];
  $update            = "Save";

  $db_object = $user->getDbObject();
	if (isset ($_GET['default'])) {
  		$default = $_GET['default'];
	} else {
		$default = '';
	}
	if (isset ($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = '';
	}
	if (isset ($_GET['DepID'])) {
		$DepID = $_GET['DepID'];
	} else {
		$DepID = '';
	}
echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
ENERGIE;
  $user->addCabinetJscript("getDepartment");	
echo<<<ENERGIE
<script type="text/javascript">
   function selectRights(type) {
	var checkList = document.getElementsByTagName('input');
	for(var i=0;i<checkList.length;i++) {
		if(checkList[i].type == 'radio' && checkList[i].value == type) {
			checkList[i].checked = true;
		}
	}
   }
	
  	function mOver(t) {
		t.style.backgroundColor = '#888888';	
	}

	function mOut(t) {
		t.style.backgroundColor = '#ffffff';	
	}
  </script> 
  <title>User Permissions</title>
 </head>
 <body class="centered">
ENERGIE;
if($user->noCabinets()){
  die("<div class=\"error\">$dieMessage</div></body></html>");
}
  if($DepID != NULL) {
	echo <<<ENERGIE
<div class="mainDiv">
<div class="mainTitle">
<span>$tableTitle</span>
</div>
<form name="getDepartment" action="{$_SERVER['PHP_SELF']}">
<table class="inputTable">
<tr>
<td class="label">
<label for="cabSel">$cabLabel</label>
</td>
<td>
ENERGIE;
    //Displays Drop Down Menu of Cabinets
	//only cabinets that the user has RW permissions will be displayed
    $user->getDropDown( "cabinetAccess.php?default=$default",$user,1 );
    echo "\n    </td>\n   </tr>\n   </table></form>\n";
	//This function is located in lib/utility.php
	$cab = getTableInfo($db_object,'departments',array('real_name'),array('departmentid'=>(int)$DepID),'queryOne');
  	$userList = getTableInfo($db_object,'access', array (), array (), 'query',
			array('username' => 'ASC'));
    echo "\n<form name=\"changePermissions\" method=\"post\" action=\"updateCabinetPermissions.php?cab=$cab&amp;default=$default\">\n";
	echo "<div class=\"inputForm\">\n";
	echo "<table>\n";
    echo "   <tr>\n";
    echo "    <th>$usernamed</th>\n";
    echo "    <th style='cursor:pointer' onmouseover='mOver(this)' onmouseout='mOut(this)' onclick=\"selectRights('rw');\">$r_w</th>\n";
    echo "    <th style='cursor:pointer' onmouseover='mOver(this)' onmouseout='mOut(this)' onclick=\"selectRights('ro');\">$r_o</th>\n";
    echo "    <th style='cursor:pointer' onmouseover='mOver(this)' onmouseout='mOut(this)' onclick=\"selectRights('none');\">$none</th>\n";
    echo "   </tr>\n";
    while($users = $userList->fetchRow()) {
		$accessArray[$users['username']] = unserialize(base64_decode($users['access']));
	}
	 //uksort( $accessArray, "strnatcasecmp" );
	 foreach( $accessArray as $myUser => $myAccess ) {
	//list cabinet permissions for non-admins, unless it is the superuser
      if($user->greaterThanUser( $myUser ) && $user->username!=$myUser) { 
      	$status1 = '';
      	$status2 = '';
      	$status3 = '';	
/*      	if(strcmp($myAccess[$cab],"rw") == 0) {
        	$status1 = 'checked="checked"';
      	} elseif(strcmp($myAccess[$cab],"ro") ==0 ) {
        	$status2 = 'checked="checked"';
      	} else {
        	$status3 = 'checked="checked"';
		}
*/
      	if (isset ($myAccess[$cab]) and $myAccess[$cab] == 'rw') {
        	$status1 = 'checked="checked"';
      	} elseif (isset ($myAccess[$cab]) and $myAccess[$cab] == 'ro') {
        	$status2 = 'checked="checked"';
      	} else {
        	$status3 = 'checked="checked"';
	}
        echo "\n<tr>\n<td>$myUser</td>";
		echo "\n<td><input type='radio' value='rw' $status1 name='$myUser'/></td>";
        echo "\n<td><input type='radio' value='ro' $status2 name='$myUser'/></td>";
        echo "\n<td><input type='radio' value='none' $status3 name='$myUser'/></td>\n</tr>";
      }
    }
	echo "</table></div><div>\n";
	echo '<div style="float: right">';
	echo "<input type='submit' name='Update' value='$update'/></div>\n";
	if( $mess != NULL ) {
		echo "<div class='error'>$mess</div>\n";
	} else {
		echo "<div class='error'>&nbsp;</div>\n";
	}
	echo "</div>\n";
    echo "\n</form>\n</div>\n</body>\n</html>";
  }	else {//DepID has not been set yet so display Select Cabinet
	echo <<<ENERGIE
<div class="mainDiv">
<div class="mainTitle">
<span>$tableTitle</span>
</div>
<form name="getDepartment" action="{$_SERVER['PHP_SELF']}">
<table class="inputTable">
<tr>
<td class="label"><label for="cabSel">$selectCabLabel</label</td>
<td>\n
ENERGIE;
    //Displays Drop Down Menu of Cabinets
	//only cabinets that the user has RW permissions will be displayed
    $idCabinet = $user->getDropDown( "cabinetAccess.php?default=$default",$user,1 );
    echo "\n</td>\n</tr></table></form>\n";

	if( $mess != null ) {
		echo "<div class=\"error\">$mess</div>\n";
	} 
	echo "</div>";
    if( isset( $idCabinet ) )
        echo "<script type=\"text/javascript\">document.onload=window.location.href=\"cabinetAccess.php?default=$default&mess=$mess&DepID=$idCabinet\";</script>";
    echo "</body>\n</html>";
  }
	setSessionUser($user);
} else {
	logUserOut();
}
?>
