<?php
#initialize connection to database and settings...
include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once 'auditBackup.php';


#-------- opening html tags ------------
echo"
<html><head><title>TruncateConfirmPage</title></head>
<body><center><h3></h3></center>";

#--------- closing html tags --------------

	setSessionUser($user);

echo"
</body></html>";
?>
