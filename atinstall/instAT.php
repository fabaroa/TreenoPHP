<?php
require_once '../check_login.php';

if($logged_in and $user->username) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Install Alternatiff</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
</head> 
<body class="centered">
<p>AlternaTIFF Image viewer is now being installed in your browser, please wait.....</p>
<p>If a window appears asking if it is ok to accept this install, please click Yes to continue.</p>
<p>After installation, if you have never installed AlternaTIFF on your computer before, you will
need to register AlternaTIFF. If that is the case, you will see the message "TIFF viewer not
registered. Click here to register." inside of the viewer. Click where it says to, and follow the 
instructions.</p>
<object style="width: 400px; height: 114px"
		classid="CLSID:106E49CF-797A-11D2-81A2-00E02c015623"
		codebase="../atinstall/altiff.cab#version=1,8,4,1">
<param name="src" value="../atinstall/atifxinst.tif">
</object>
</body>
</html>
<?php

}

?>
