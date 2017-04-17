<?php
require_once '../check_login.php';
require_once '../groups/groups.php';
require_once '../lib/mime.php';
require_once '../lib/versioning.php';
require_once '../centera/centera.php';

$maxHeight = 800;
$groups = new groups($db_object);

if ($logged_in and $user->username) {

	$cabinet = $_GET['cabinet'];
	$fileID = $_GET['fileID'];
	$docID = $_GET['docID'];
	$allThumbCt = 0;
	if(isSet($_GET['thumbCt'])) {
		$allThumbCt = $_GET['thumbCt'];
	}
	$divID = $_GET['divID'];
	$location = getTableInfo($db_object, $cabinet, array('location'), array('doc_id' => (int) $docID), 'queryOne');
	$parentID = getParentID($cabinet, (int) $fileID, $db_object);
	$row = getTableInfo($db_object, $cabinet.'_files', array(),
		array('id' => (int) $parentID), 'queryRow');

	$filename = $row['filename'];
	$parent_filename = $row['parent_filename'];
	if ($row['subfolder']) {
		$subfolderStr = $row['subfolder'].'/';
		$subfolder = $row['subfolder'];
	} else {
		$subfolderStr = '';
		$subfolder = '';
	}
	$fileLoc = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $location).'/';
	$fileLoc .= $subfolderStr.$filename;
	if(check_enable('centera', $user->db_name)) {
		centget($DEFS['CENT_HOST'], $row['ca_hash'], $row['file_size'], $fileLoc, $user, $cabinet);
	}
	if (!in_array(strtolower(getExtension($filename)), array('tif', 'tiff', 'jpeg', 'jpg'))) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Error</title>
	</head>
	<body>
		<div>Only tiff and jpeg files can be redacted or highlighted.</div>
	</body>
</html>
<?php

		die();
	}

	$ct = 0;
	$cmd = $DEFS['TIFFINFO_EXE']." ".$fileLoc;
	$tiffInfo = explode("\n",shell_exec($cmd));
	foreach($tiffInfo AS $info) {
		if(strrpos($info,"TIFF Directory") !== false) {
			$ct++;
		}
	}

	if($ct > 1) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Error</title>
	</head>
	<body>
		<div>Only single page tiffs can be redacted</div>
	</body>
</html>
<?php
		die();
	}

	list ($width, $height) = getimagesize($fileLoc);
	$newWidth = ceil(($maxHeight * $width) / $height);
	$urlStr = "fetchImgToRedact.php?w=$newWidth&amp;h=$maxHeight&amp;oldW=$width&amp;oldH=$height";
	$_SESSION['redactFile'] = $fileLoc;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Edit Redaction</title>
		<script type="text/javascript" src="../lib/settings.js"></script>
		<script type="text/javascript">
			var myHeight = '<?php echo $maxHeight ?>px';
			var myWidth = '<?php echo $newWidth ?>px';
			var docID = <?php echo $docID ?>;
			var subfolder = '<?php echo $subfolder ?>';
			var fileName = '<?php echo $filename ?>';
			var pFileName = '<?php echo $parent_filename ?>';
			var cabinet = '<?php echo $cabinet ?>';
			var divID = '<?php echo $divID ?>';
			var coord = new Object();
			var allThumbCt = '<?php echo $allThumbCt ?>';
			var myFunc = '';
			var counter = 0;
			var tempDiv, redactImg, drawArea, toolBox;
			var stampDiv, chooseStampSel, chooseColorBtn, colorDiv;
			var drawAreaOffTop, drawAreaOffLeft, errDiv;
			var drawing = false;
			var moveStuff, otherSel;
			var moveNow = false;
			var currMove = new Object();
			var currStamp = '';
			var redactColor = 'black';
			var highlightColor = 'yellow';
			var stampImg = '';
			var user = '<?php echo $user->username ?>';
			var readScroll = {
				scrollTop: 0,
				scrollLeft: 0,
				clientLeft: 0,
				clientTop: 0
			};
			if(document.documentElement) {
				readScroll = document.documentElement;
			}else if(document.body) {
				readScroll = document.body;
			}
			coord.x = -1;
			coord.y = -1;
			var p = getXMLHTTP();

			parent.bottomFrame.location.href = "../energie/printRedaction.php";
		</script>
		<script type="text/javascript" src="../lib/redaction.js"></script>
		<link rel="stylesheet" type="text/css" href="../lib/style.css" />
		<style type="text/css">
			body, html {
				height: 100%;
				font-size: 9pt;
			}
			
			.redact, .highlight {
				cursor: pointer;
				font-size: 1px;
			}
			
			.highlight {
				-moz-opacity: 0.5;
				filter: alpha(opacity=50);
			}

			#drawArea, #redactImg {
				cursor: crosshair;
			}

			label {
				margin-right: 2em;
			}
			
			.colorDivs {
				height: 25px;
				width: 100%;
				-moz-opacity: 0.5;
				filter: alpha(opacity=50);
			}
			#textDiv {
				height: 210px;
				width: 635px;
				border: 1px double #003B6F;
				background-color: white;
				position: absolute;
				top: 65px;
				left: 2px;
				display: none;
				z-index: 1;
				padding: 5px;
			}
			#textDiv label{
				font: 12px "arial black" #003B6F;
				padding: 4px;
				display: block;
			}
			#textDiv textarea {
				width: 545px;
				height: 150px;
				float: left;
				margin: 3px;
			}
			
		</style>
	</head>
	<body onload="registerEvents()">
		<div id="toolBox" style="white-space: nowrap; width: 100%; height:55px">
			<fieldset>
				<legend>Draw Function</legend>
				<div style="width:150%">
				<div style="float:left;white-space:nowrap;width:5%">
					<select id="selToggle" onchange="changeToggle()">
						<option value="redact">Draw Redaction</option>
						<option value="highlight">Draw Highlight</option>
						<option value="stamp">Stamp Picture</option>
						<option value="text">Text Overlay</option>
						<option value="move">Move Overlay</option>
						<option value="delete">Delete Overlay</option>
					</select>
				</div>
				<div style="float:left;white-space:nowrap;width:25%;text-align:left">
					<input style="visibility:hidden" type="checkbox" id="allDocs" />
					<label style="visibility:hidden" for="allDocs">Use as Template</label>
					<span id="otherSel"></span>
				</div>
				<div style="float:left;white-space:nowrap;width:15%;text-align:right">
					<img src="../images/print_24.gif" title="Print Page" alt="Print Page" 
						onclick="printRedactedPage()" style="vertical-align:bottom; cursor:pointer"/>
					<input type="button" value="Process" style="" onclick="postXML()" />
				</div>
				</div>
				<div></div>
			</fieldset>
		</div>
		<div id="redactionContainer">
			<div id="redactImg" style="background-image: url(<?php echo $urlStr ?>); height: <?php echo $maxHeight ?>px; width: <?php echo $newWidth ?>px"></div>
			<div id="textDiv">
				<label>Text Size: 
				<select id="fontSize" name="fontsize">
					<option value="7">7</option>
					<option value="8">8</option>
					<option value="9">9</option>
					<option value="10">10</option>
					<option value="11">11</option>
					<option value="12">12</option>
					<option value="13">13</option>
					<option value="14">14</option>
					<option value="15">15</option>
				</select>
				</label>
				<textarea name="dynamicText" wrap="hard"></textarea>
				<input type="button" value="Add Text" id="drawTextOverlay" />
				<input type="button" value="Clear" onclick="clearDynamicText()" />
				<input type="button" id="CancelBtn" value="Cancel" onclick="cancelTextOverlay();" />
			</div>
			<div id="drawArea">
			</div>
		</div>
	</body>
</html>
<?php

}
?>

