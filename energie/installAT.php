<?php
include_once '../lib/settings.php';

if(isSet($DEFS['DISABLE_AT']) && $DEFS['DISABLE_AT'] == 1) {
	die("Alternatiff has been disabled");
}

$ID = $_GET['ID'];
if (isset ($_GET['file_id'])) {
	$file_id = $_GET['file_id'];
} else {
	$file_id = '';
}
$download = $_GET['download'];
$cab = $_GET['cab'];
if (isset ($_GET['filename'])) {
	$filename = $_GET['filename'];
} else {
	$filename = '';
}
$doc_id = $_GET['doc_id'];
$tab = $_GET['tab'];
$refPage = $_GET['refPage'];
//for displayInbox
if (isset ($_GET['name'])) {
	$name = $_GET['name'];
} else {
	$name = '';
}
if (isset ($_GET['table1'])) {
	$table1 = $_GET['table1'];
} else {
	$table1 = '';
}
if (isset ($_GET['table2'])) {
	$table2 = $_GET['table2'];
} else {
	$table2 = '';
}
if (isset ($_GET['foldname'])) {
	$foldername = $_GET['foldname'];
} else {
	$foldername = '';
}

	echo <<<ENERGIE
	<html>
	<head>
	<script language="JavaScript">
					function reloadPage() {
						parent.mainFrame.window.location = "installAT.php?doc_id=$doc_id&file_id=$file_id&tab=$tab&cab=$cab&ID=$ID&download=$download&filename=$filename&tmp=0/$filename";
					}
	</script>
	</head>
	<body>
	<center>
	AlternaTIFF Image viewer is now being installed on your browser, please wait.....<br/><br/>
	If a window appears asking if it is ok to accept this install, please click Yes to continue.<br/><br/>
	After installation, if you have never installed AlternaTIFF on your computer before, you will need to register AlternaTIFF. If that is the case, you will see the message "TIFF viewer not registered. Click here to register." inside of the viewer. Click where it says to, and follow the instructions.<br/>
	<object width=400 height=114 classid="CLSID:106E49CF-797A-11D2-81A2-00E02c015623" codebase="../atinstall/altiff.cab#version=1,8,4,1">
				  <param name=src value="../atinstall/atifxinst.tif">
ENERGIE;
	if ($refPage == 0) {
		echo <<<ENERGIE
					 <param name=href value="display.php?doc_id=$doc_id&file_id=$file_id&tab=$tab&cab=$cab&ID=$ID&download=$download&filename=$filename">
ENERGIE;
	} else {
		echo <<<ENERGIE
					<param name=href value="displayInbox.php?name=$name&table1=$table1&table2=$table2&foldname=$foldername">
ENERGIE;
		}
		
		echo <<<ENERGIE
		<param name=target value="mainFrame">
		Unable to install ActiveX control.  
		<a onClick="reloadPage()">Click Here</a>
		to try again.  (Requires Internet Explorer 4+.)
		<br/>
		Make sure your security settings permit ActiveX controls to be downloaded.<br/>
		Also try deleting Temporary Internet Files <nobr>(Tools -&gt;</nobr>
		<nobr>Internet Options -&gt;</nobr>
		<nobr>Temporaty Internet files -&gt;</nobr> <nobr>Delete Files).</nobr>
		</object>
		</center>
		</body>
		</html>
ENERGIE;

?>
