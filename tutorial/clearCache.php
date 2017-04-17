<?php
include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in ==1 && strcmp($user->username,"")!=0) {
 	$db_name = $user->db_name;
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
 </head>
 <body>
  <center>
   <table class="settings" width="500">
    <tr class="tableheads">
	 <td colspan="2">Steps To Clearing Cache</td>
	</tr>
    <tr>
	 <td>Step 1</td>
	 <td>Open an internet browser
	</td>
	</tr>
	<tr>
	 <td>Step 2</td>
	 <td>Select The "Tools" Menu</td>
	</tr>
	 <td>Step 3</td>
	 <td>Select the option "Internet Options"</td>
	</tr>
	 <td>Step 4</td>
	 <td>A new page will load with several tabs.  Select the "General" tab.  Then select "Delete Files" and click OK.</td>
	</tr>
	 <td>Step 5</td>
	 <td>Click OK to close the new page that loaded</td>
	</tr>
   </table>
  </center>
 </body>
</html>\n
ENERGIE;
	setSessionUser($user);
} else { //end of if that checks for security
	logUserOut();
}
?>
