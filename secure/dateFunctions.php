<?php
// $Id: dateFunctions.php 14205 2011-01-04 15:46:56Z acavedon $

require_once '../check_login.php';
require_once '../settings/settings.php';
require_once '../DataObjects/DataObject.inc.php';

if($logged_in and $user->username and $user->isDepAdmin()) {
	$db = $user->getDbObject();
	$db_doc = getDbObject('docutron');
	$DO_user = DataObject::factory('users', $db_doc);
	$DO_user->get('username', $user->username);
	$licensesArr = getTableInfo($db_doc, 'licenses',
		array('real_department', 'arb_department'), array(), 'getAssoc', 
		array('arb_department' => 'ASC'));
	$dateArr = array ();
	$noDateArr = array ();
	$licArr = array();
	foreach($licensesArr as $realName => $arbName) {
		if(isSet($DO_user->departments[$realName]) && $DO_user->departments[$realName] == "D") {
			$settings = new GblStt($realName, $db_doc);
			$dateFuncs = $settings->get('date_functions');
			if($dateFuncs == 1) {
				$dateArr[$realName] = 'checked="checked"';
				$noDateArr[$realName] = '';
			} else {
				$noDateArr[$realName] = 'checked="checked"';
				$dateArr[$realName] = '';
			}
			$licArr[$realName] = $arbName;
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<title>Date Functions</title>
		<link rel="stylesheet" href="../lib/style.css" type="text/css" />
		<style type="text/css">
			#dateHeader, #noDateHeader {
				cursor: pointer;
			}
		</style>
		<script type="text/javascript" src="../lib/settings.js"></script>
		<script type="text/javascript">
		var p = getXMLHTTP();
		
		function submitDateFuncs() {
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('dateFuncs');
			var el, tmp, type, dept;
			var inputElems = document.getElementsByTagName('INPUT');
			for(var i = 0; i < inputElems.length; i++) {
				if(inputElems[i].checked) {
					tmp = inputElems[i].id.split('-');
					dept = inputElems[i].name;
					type = tmp[0];
					el = xmlDoc.createElement('dept');
					el.setAttribute('name', dept);
					el.setAttribute('action', type);
					root.appendChild(el);
				}
			}
			xmlDoc.appendChild(root);
			var xmlStr = domToString(xmlDoc);
			p.open('POST', '../lib/settingsFuncs.php?func=dateFuncs', true);
			p.send(xmlStr);
			p.onreadystatechange = function () {
				if(p.readyState == 4) {
					var errDiv = document.getElementById('errDiv');
					if(errDiv) {
						document.getElementById('mainDiv').removeChild(errDiv);
					}
					errDiv = document.createElement('div');
					errDiv.appendChild(document.createTextNode(p.responseText));
					errDiv.id = 'errDiv';
					errDiv.className = 'error';
					document.getElementById('mainDiv').appendChild(errDiv);
				}
			};

		}
		
		function selectCol(col) {
			var el;
			if(col.id == 'dateHeader') {
				for(var i = 0; el = document.getElementById('date-' + i); i++) {
					el.checked = true;
				}
			} else {
				for(var i = 0; el = document.getElementById('noDate-' + i); i++) {
					el.checked = true;
				}
			}
		}
		
		function mOver(col) {
			col.style.backgroundColor = '#999999';
		}
		
		function mOut(col) {
			col.style.backgroundColor = '#ffffff';
		}
		</script>
	</head>
	<body class="centered">
		<div id="mainDiv" class="mainDiv">
			<div class="mainTitle">
				<span>Date Functions</span>
			</div>
			<div class="inputForm">
				<table>
					<tr>
						<th></th>
						<th onclick="selectCol(this)" onmouseover="mOver(this)" onmouseout="mOut(this)" id="dateHeader">Use Date Functions</th>
						<th onclick="selectCol(this)" onmouseover="mOver(this)" onmouseout="mOut(this)" id="noDateHeader">Do Not Use Date Functions</th>
					</tr>
					<?php $i = 0; ?>
					<?php foreach ($licArr as $real => $arb): ?>
						<tr>
							<td><?php echo $arb ?></td>
							<td><input type="radio" id="<?php echo 'date-'.$i ?>" name="<?php echo $real ?>" <?php echo $dateArr[$real] ?> /></td>
							<td><input type="radio" id="<?php echo 'noDate-'.$i ?>" name="<?php echo $real ?>" <?php echo $noDateArr[$real] ?> /></td>
						</tr>
						<?php $i++; ?>
					<?php endforeach; ?>
				</table>
			</div>
			<div><button onclick="submitDateFuncs()">Save</button>
		</div>
	</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
