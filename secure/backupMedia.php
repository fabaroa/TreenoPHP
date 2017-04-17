<?php
include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../modules/modules.php';
include_once '../settings/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin() ) {
    //variables that may need to be translated
     //$tableTitle        = $trans['Hard Drive Settings']; 
     //$selectHD          = $trans['Select Hard Drive'];
     //$currentHDMessage  = $trans['HD Message'];
     //$changeHD          = $trans['Change Hard Drive'];
     $tableTitle="Backup Media Settings";
     $selectWrite="Select Writable Media";
     $currentWriteMessage="Backup Media Changed to";
     $changeWrite="Change Media";
     $db_doc = getDbObject ('docutron');


   $settings=new GblStt( $user->db_name, $db_doc );    //establish the system preferences object
      
   if(isset($_POST['changeWrite']))  //change hard drive was clicked
   {
	$settings->set("CDBackup", $_POST['media']);
	$message="$currentWriteMessage \"{$_POST['media']}\"";
	$user->audit('backup media changed', $message);
   }
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>System Preferences</title>
 </head>
 <body>
  <form name="preferences" method="POST" action="backupMedia.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="2" class="tableheads">$tableTitle</td>
    </tr>
    <tr>
     <td class="admin-tbl">$selectWrite:</td>
     <td>
	<select name="media">

ENERGIE;

//hard drive settings
$current_drive=$settings->get("CDBackup" );   //gets the currently selected drive
//have CDROM be default if other is not selected
if($current_drive==""||$current_drive=="CDR 700MB")
	echo "<option value='CDR 700MB' selected>CDR 700MB</option><option value='DVD+RW 4.7GB'>DVD+RW 4.7GB</option>";
else
	echo "<option value='DVD+RW 4.7GB' selected>DVD+RW 4.7GB</option><option value='CDR 700MB'>CDR 700MB</option>";


echo<<<ENERGIE
	</select>
     </td>
    </tr>
    <tr>
     <td colspan="2">
ENERGIE;

	//display message if a change was successfully made
	if( isSet($message) )
		echo "<div class=\"error\">$message\n";
	else
		echo "<div>\n";

echo<<<ENERGIE
  <input name="changeWrite" type="submit" value="$changeWrite"></div>
	</td>
    </tr>
     </table>
  </center>
    </form>
   </body>
   </html>
ENERGIE;
	setSessionUser($user);
} else {  //log them out
	logUserOut();
}
?>
