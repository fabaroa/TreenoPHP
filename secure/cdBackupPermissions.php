<?php
// $Id: cdBackupPermissions.php 14203 2011-01-04 15:45:11Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin() )
{
$enable      = $trans['Enable'];
$disable     = $trans['Disable'];

$db_doc = getDbObject ('docutron');
$settings = new GblStt($user->db_name, $db_doc);

//get values if form was submitted
if (isset ($_POST['changePermissions'])) {

	//admin permissions
	if ($_POST['admins']==1)
		$settings->set('adminBackup','1');
	else
		$settings->set('adminBackup','0');

	//user permissions

	if ($_POST['rw']==1)	//set RWorRO permission
		$settings->set('userBackup','2');
	else if($_POST['ro']==1)	//set RW permission only
		$settings->set('userBackup','1');
	else	//no permissions
		$settings->set('userBackup','0');

	$message = $_GET['message'];
		
}

//get current settings
$setAdmins = $settings->get('adminBackup' );
$setUsers = $settings->get('userBackup' );

//if these are null, give them initial values
if($setAdmins == null) {

	$settings->set('adminBackup','0');
}
if($setUsers == null) {
	
	$settings->set('userBackup','0');
}

//determine permissions to display
if ($setAdmins == '1') {

	$admin_enable="checked";
	$admin_disable="";
}
else {
	$admin_enable="";
	$admin_disable="checked";
}

if ($setUsers == '2') {	//RWorRO
	
	$ro_enable="checked";
	$ro_disable="";
	$rw_enable="checked";
	$rw_disable="";
}
else if ($setUsers=='1') {	//RO only
	$ro_enable="checked";
	$ro_disable="";
	$rw_enable="";
	$rw_disable="checked";
}
else {				//no permisssions
	$rw_enable="";
	$rw_disable="checked";
	$ro_enable="";
	$ro_disable="checked";
}

echo<<<ENERGIE
<html>
<head><title>CD Backup Permissions</title>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
</head>
  <form name="permissions" method="POST" action="cdBackupPermissions.php?message=CD Backup Permissions Updated">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="3" class="tableheads">CD Backup Permissions</td>
    </tr>
    <tr>
     <td class="admin-tbl">
      Administrators
     </td>
     <td>
      <input type="radio" name="admins" value=1 $admin_enable>
      $enable
     </td>
     <td>
      <input type="radio" name="admins" value=0 $admin_disable>
      $disable
     </td>
    </tr>
    <tr>
     <td class="admin-tbl">
      Read/Write Permissions
     </td>
     <td>
      <input type="radio" name="rw" value=1 $rw_enable>
      $enable
     </td>
     <td>
      <input type="radio" name="rw" value=0 $rw_disable>
      $disable
     </td>
    </tr>
<tr>
     <td class="admin-tbl">
      Read Only Permissions
     </td>
     <td>
      <input type="radio" name="ro" value=1 $ro_enable>
      $enable
     </td>
     <td>
      <input type="radio" name="ro" value=0 $ro_disable>
      $disable
     </td>
    </tr>

    <tr>
     <td colspan="3">
ENERGIE;

	//displays confirmation message
	if( isSet($message) )
        echo "<div class=\"error\">$message\n";
	else
		echo "<div>\n";

echo<<<ENERGIE
      <input name="changePermissions" type="submit" value="Save"></div>
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
else{
echo<<<ENERGIE
<html>
<body bgcolor="#FFFFFF">
<script>
document.onload = top.window.location = "../logout.php"
</script>
</body>
</html>
ENERGIE;
}
?>
