<?php
include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
echo<<<ENERGIE
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
 <head>
  <link rel="stylesheet" type="text/css" href="../lib/style.css"/>
  <script type="text/javascript" src="../lib/settings.js"></script>
  <script type="text/javascript" src="../lib/moveFiles.js"></script>
  <script>
	var isFolderSelected = "";
	var isTabSelected = "";
	var deleted = 'deleted=1';
	var divType = 'deletefiles';
  </script>
 </head>
 <body class="centered">
  <div class="mainDiv" style="width:80%">
   <div class="mainTitle">
    <span>Recycle Bin</span>
   </div>
   <div class="inputForm" style="padding:5px" id="deletefiles">
	<div style="padding-bottom:5px;font-weight:bold;font-size:12px">Cabinet:</div>
	<div>
	 <select id="cabSelect" name="cab" onchange="selectCabinet()">
	 <option selected value="default">Choose a Cabinet</option>\n
ENERGIE;
	$delObj = new filesToDelete($user->db_name); 
	$cabList = $delObj->getCabinets();
	foreach( $cabList AS $cabinet ) {
		echo "<option value=\"$cabinet\">{$user->cabArr[$cabinet]}</option>\n";
	}
echo<<<ENERGIE
	 </select>
	</div>
   </div>
  </div>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
}
else{
	logUserOut();
}
?>
