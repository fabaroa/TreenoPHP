<?php
// $Id: auditSearch.php 14281 2011-03-18 19:57:09Z acavedon $

include_once '../modules/modules.php';
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'audit.php';

if($logged_in ==1 && strcmp($user->username,"")!=0){
	//variables that may need to be translated
	$tableTitle      = $trans['Search Audit Table'];        
    $searchButton    = $trans['Search'];

echo<<<ENERGIE
<html>
<head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
</head>
<body>
<form name="search" method="POST" target=mainFrame action="showAudit.php?index=0&trigger=0">
  <center>	
	<table class='settings' width='315'>
		<tr>
			<td colspan="2" bgcolor="#003b6f">
				<div class="tableheads"><center><font color='white'>$tableTitle</font></center></div>
			</td>
		</tr>
ENERGIE;
showFields($db_object, $user);  //defined in audit.php
echo<<<ENERGIE
	<tr><td>&nbsp;</td><td><p><input type="submit" value="$searchButton" name="auditSearch"></p></td></tr>
	</table>
  </center>	
</form>
</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
} 
?>
