<?php
require_once '../departments/depfuncs.php';

require_once '../check_login.php';
require_once '../classuser.inc';

if(($logged_in ==1 && strcmp($user->username,"")!=0)) {
	$db_object = $user->getDbObject();
	echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Print User Barcodes</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/barcode.js"></script>
<style type="text/css">
th#barcodeCol {
  width: 150px;
}
td.barcodeCell {
  cursor: pointer;
}
</style>
</head>
<body class="centered">
<div class="mainDiv">
<div class="mainTitle">
<span>Print User Barcodes</span>
</div>
<div class="inputForm">
<table>
<tr>
<th id="barcodeCol">Barcode Page</th>
<th>User</th>
</tr>

ENERGIE;
  	$usernames = getTableInfo($db_object,'access',array(),array(),'query',array('username'=>'ASC'));
	while($row = $usernames->fetchRow()) {
		$myUsername = $row['username'];
		echo <<<ENERGIE
<tr>
<td class="barcodeCell" onclick="printUserBarcode('$myUsername')">
<img alt="Print Barcode" src="../images/barcode.gif"/>
</td>
<td>$myUsername</td>
</tr>

ENERGIE;
	}
	echo <<<ENERGIE
</table>
</div>
</div>
</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
