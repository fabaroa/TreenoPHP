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
	 <td colspan="2">Steps To Map a Network Drive</td>
	</tr>
    <tr>
	 <td>Step 1</td>
	 <td>Open "My Computer"
	</td>
	</tr>
	<tr>
	 <td>Step 2</td>
	 <td>Select The "Tools" Menu</td>
	</tr>
	 <td>Step 3</td>
	 <td>Select the option "Map Network Drive"</td>
	</tr>
	 <td>Step 4</td>
	 <td>Select a Drive (Usually the drive will already be entered.  If you are mapping the indexing folder make sure you remember the drive letter you have selected for the scanning software.)</td>
	</tr>
	 <td>Step 5</td>
	 <td><div>Enter a Folder</div><div style="font-style: italic">For example, the public inbox folder will be</div><div><b style="font-size:14">\\\\IP address\\{$user->db_name}-inbox</b></div><div style="font-style: italic">If you want to map the indexing folder, replace 'inbox' with 'indexing'.</div>
	</td>
	</tr>
	 <td>Step 6</td>
	 <td>You will be prompted for a password.
ENERGIE;
if($user->isSuperUser() and substr(PHP_OS, 0, 3) != 'WIN') {
	echo <<<ENERGIE
	To change the password to be secure and not the default,
	 <a target="" href="../departments/changeSambaPasswd.php?default=0">click here</a>
ENERGIE;
}

echo <<<ENERGIE
</td>
	</tr>
	 <td>Step 7</td>
	 <td>If you have already connected using the default password and username, you might notice that you are not getting prompted with a username and password.  You must then click on the link that says Connect using a different username.  You will then be able to retype the username and the new password.</td>
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
