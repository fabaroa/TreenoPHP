<?php
include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in ==1 && strcmp($user->username,"")!=0) {
	$whiteLabel = "Setup Help";
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
  <script type="text/javascript">
  function showIE()
  {
	if(document.all) {
		if(document.getElementById('iePlugins')) {
			document.getElementById('iePlugins').style.display = 'block';
		}
	}
  }
  </script>
 </head>
 <body onload="showIE()">
  <center>
   <table class="settings" width="500">
    <tr class="tableheads">
	 <td colspan="2">$whiteLabel</td>
	</tr>
    	<tr class="tableheads">
	 <td>Topic</td>
	 <td>Description</td>
	</tr>
	<tr>
	 <td nowrap="yes"><a href="clearCache.php">Clearing Internet Browser Cache</a></td>
	 <td>Clearing your internet browser cache will removed old and static html pages that may cause errors in this application.  Some common errors are failure to login and javascript errors.</td>
	</tr>
ENERGIE;
	if(!check_enable('lite', $user->db_name)) {
		echo <<<ENERGIE
	<tr>
	 <td nowrap="yes"><a href="mappingDrive.php">Mapping a Drive</a></td>
	 <td>Mapping a Drive will allow you easy access to a folder in this application.  Mapping the inbox folder will allow the user to drag and drop files into the inbox making it easier on the user.  Mapping the indexing folder will allow the user to connect a scanner to the map drive and all files scanned will be move automatically to the folder.</td>
	</tr>
	<tr>
	<td nowrap="yes"><a target="_blank" href="http://www.alternatiff.com/">Alternatiff TIFF Viewer</a></td>
	 <td>Alternatiff is a TIFF image viewer for Windows web browsers. Please visit this site to install it.</td></tr>
<tr id="iePlugins" style="display: none">
<td><a href="alternatiff.html">Plugin Troubles</a></td>
<td>
If you are unable to view images or PDF files.
</td>
</tr>
ENERGIE;
	}
	echo<<<ENERGIE
<tr>
<td><a target="_blank" href="http://www.adobe.com/products/acrobat/readstep2.html">Install Acrobat Reader</a></td>
<td>
Please click this to be redirected to download Adobe Reader.
</td>
</tr>
<tr>
<td><a href="../modules/support.php">Send In A Support Ticket</a></td>
<td>
Please click this to be redirected to the Treeno Support Ticket System.
</td>
</tr>
   </table>
  </center>
 </body>
</html>

ENERGIE;
	setSessionUser($user);
} else {//end of if that checks for security
	logUserOut();
}
?>
