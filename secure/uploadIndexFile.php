<?php
// $Id: uploadIndexFile.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../lib/cabinets.php';
include_once '../lib/mime.php';
include_once 'uploadActions.php';

if($logged_in ==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin()) {
	$db_doc = getDbObject ('docutron');
	/*TO DO LIST
	- when appending something allow user to undo
	*/
	//variables that may have to be translated (more are interspersed in the code)
	$uploadFile         = $trans['Upload a File'];
	$_upload            = $trans['Upload'];
	$fileNotSupported   = $trans['File Type Not Supported'];
	$selectFile 		= $trans['Upload a File'];
	$noFile             = $trans['File Not Selected'];
	$title				= $trans['auto complete indexing'];

	$_copy = "Copy";
	$previousUpload = "Copy Auto-Complete Mapping to Cabinet";
	$_remove = "Remove Auto Complete Indexing";
	$autoCompleteEnabled = "Auto Complete Indexing Is Enabled For ->";
	$selectOpt = $trans['Choose Cabinet'];

	$user->setSecurity();
	$cabList = array_keys($user->cabArr);

	$gblStt = new GblStt($user->db_name, $db_doc);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>$title</title>
	<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script>
		var p = getXMLHTTP();	
		var URL = 'uploadActions.php';
		function selectCabinet(type) {
			removeDefault(getEl('cabinetAC'));
			var errMsg = getEl('errorMsg');
			var cab = getEl('cabinetAC').value;	
			if(type == 'copy') {
				var copyCab = getEl('prevACCabinet').value;
				var newURL = URL+'?cab='+cab+'&copyACCabinet=1&copyCab='+copyCab;
			} else if(type == 'remove') {
				var newURL = URL+'?cab='+cab+'&removeACCabinet=1';
			} else {
				var newURL = URL+'?cab='+cab+'&checkForACCabinet=1';
			}

			p.open('POST', newURL, true);
			clearDiv(errMsg);
			showDivs([errMsg]);
			try {
				document.body.style.cursor = 'wait';
				errMsg.appendChild(document.createTextNode('Please Wait....'));
				p.send(null);
			} catch(e) {
				errMsg.appendChild(document.createTextNode('Error occured connecting'));
				document.body.style.cursor = 'default';
			}
			p.onreadystatechange = function() {
				if(p.readyState != 4)  {
					return;
				}
				
				if(p.responseXML) {
					clearDiv(errMsg);
					var XML = p.responseXML;
					var removeAC = XML.getElementsByTagName('REMOVE_AC');
					if(removeAC) {
						if(removeAC.length > 0 ) {
							showDivs([getEl('removeAC')]);
						} else {
							hideDivs([getEl('removeAC')]);
						}
					}

					var copyAC = XML.getElementsByTagName('AC_CABINET');
					if(copyAC) {
						if(copyAC.length > 0 ){
							showDivs([getEl('copyAC')]);
							copyCab = getEl('prevACCabinet');
							clearDiv(copyCab);
							for(var i=0;i<copyAC.length;i++) {
								var opt = document.createElement('option');
								opt.value = copyAC[i].getAttribute('name'); 
								opt.appendChild(document.createTextNode(copyAC[i].firstChild.nodeValue)); 
								copyCab.appendChild(opt);
							}
						} else {
							hideDivs([getEl('copyAC')]);
						}
					}

					if(type == 'copy') {
						//verifyACCabinet();
						errMsg.appendChild(document.createTextNode('Auto compelete table was copied successfully'));
					} else if(type == 'remove') {
						mess = XML.getElementsByTagName('MESSAGE');
						if(mess.length > 0 ) {
							errMsg.appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
						}
						hideDivs([getEl('removeAC')]);
					} else {
						showDivs([getEl('uploadAC')]);
					}
					document.body.style.cursor = 'default';
				} else {
					clearDiv(errMsg);
					errMsg.appendChild(document.createTextNode('An Error Occured Loading the XML'));
				}
				document.body.style.cursor = 'default';
			};
		}

		function uploadACFile() {
			var tmpURL = '?cab='+getEl('cabinetAC').value;
			tmpURL += '&uploadACFile=1';
			var rd = document.radioForm.option;
			for(var i=0;i<rd.length;i++) {
				if(rd[i].checked) {
					tmpURL += '&type='+rd[i].value;
				}
			}
			document.upload.action += tmpURL; 
			document.body.style.cursor = 'wait';
			
			
			if(getEl('fileUpload').value == '') {
				clearDiv(getEl('errorMsg'));
				getEl('errorMsg').appendChild(document.createTextNode('<?php echo $noFile; ?>'));
				document.body.style.cursor = 'default';
				return;
			}
			
			var filename = getEl('fileUpload').value;
			var fArr = filename.split('.');
			var ext = fArr.pop();
			if(ext.toLowerCase() != 'txt' && ext.toLowerCase() != 'dat') {
				clearDiv(getEl('errorMsg'));
				getEl('errorMsg').appendChild(document.createTextNode('<?php echo $fileNotSupported; ?>'));
				document.body.style.cursor = 'default';
				return;
			}
			document.upload.submit();
		}

		function verifyACCabinet() {
			var cab = getEl('cabinetAC').value;
            answer = window.confirm('Verify File?');
            if(answer == true) {
			  window.location = 'verifyUpload.php?cab='+cab;
			} else {
				clearDiv(getEl('errorMsg'));
				var arbCab = getEl('cabinetAC').options[getEl('cabinetAC').selectedIndex].text;
				getEl('errorMsg').appendChild(document.createTextNode('<?php echo $autoCompleteEnabled; ?> '+arbCab));
				showDivs([getEl('removeAC')]);
				document.body.style.cursor = 'default';
			}
		}

		function uploadComplete(mess) {
			if(mess != '') {
				clearDiv(getEl('errorMsg'));
				getEl('errorMsg').appendChild(document.createTextNode(mess));
				document.body.style.cursor = 'default';
			} else {
				verifyACCabinet();
			}
		}
	</script>				
    <style type="text/css">
		div.hideDiv {
			display: none;
		}
		
		div.innerDiv div {
			padding: 5px;
		}
    </style>
</head>
<body>
	<div class='mainDiv'>
		<div class='mainTitle'>
			<span><?php echo $title; ?></span>
		</div>
		<div class='innerDiv'>
			<div>
				<select id='cabinetAC' onchange="selectCabinet()">
					<option value='__default'><?php echo $selectOpt; ?></option>
					<?php foreach($cabList AS $c) : ?>
						<?php if($gblStt->get('indexing_'.$c) != 'odbc_auto_complete' and $gblStt->get('indexing_'.$c) != 'sagitta_ws_auto_complete') : ?>
							<option value='<?php echo $c; ?>'><?php echo $user->cabArr[$c]; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
			<div id='removeAC' class='hideDiv'>
				<input type='button' name='B1' onclick="selectCabinet('remove')" value='<?php echo $_remove; ?>'>
			</div>
			<div id='uploadAC' class='hideDiv'>
				<fieldset>
					<legend><?php echo $selectFile; ?></legend>
					<div>
						<form name="upload" method="post" target="leftFrame1" 
							enctype="multipart/form-data" action="uploadActions.php">
							<input id='fileUpload' type='file' name='f1' size='20'>
							<input type='button' name='B2' onclick='uploadACFile()' value='<?php echo $_upload; ?>'>
						</form>
					</div>
					<div>
						<form name="radioForm" style="padding:0px">
							<input type='radio' value='new' name='option' checked>New Table
							<input type='radio' value='append' name='option'>Append Table
						</form>
					</div>
				</fieldset>
			</div>
			<div id='copyAC' class='hideDiv'>
				<fieldset>
					<legend><?php echo $previousUpload; ?></legend>
					<div>
						<select id='prevACCabinet' name='previousSelected'>
						</select>
						<input type='button' name='B3' onclick="selectCabinet('copy')" value='<?php echo $_copy; ?>'>
					</div>
				</fieldset>
			</div>
			<div id='errorMsg' class='error hideDiv'></div>
		</div>
	</div>
</body>
</html>
<?php 
	setSessionUser($user);
} else {
	logUserOut();
}
?>
