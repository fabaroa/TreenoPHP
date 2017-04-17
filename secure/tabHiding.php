<?php
// $Id: tabHiding.php 14291 2011-03-21 17:33:32Z acavedon $

require_once '../check_login.php';
require_once '../settings/settings.php';

if($logged_in and $user->username and $user->isDepAdmin()) {
	$db = $user->getDbObject();
	$db_doc = getDbObject ('docutron');
	$settings = new GblStt($user->db_name, $db_doc);
	$hideArr = array ();
	$showArr = array ();
	foreach ($user->cabArr as $cabinet => $dispName) {
		$hiding = $settings->get('tab_hiding_'.$cabinet);
		if($hiding == '0') {
			$showArr[$cabinet] = 'checked="checked"';
			$hideArr[$cabinet] = '';
		} else {
			$hideArr[$cabinet] = 'checked="checked"';
			$showArr[$cabinet] = '';
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<title>Show/Hide Empty Tabs</title>
		<link rel="stylesheet" href="../lib/style.css" type="text/css" />
		<style type="text/css">
			#hideHeader, #showHeader {
				cursor: pointer;
			}
		</style>
		<script type="text/javascript" src="../lib/settings.js"></script>
		<script type="text/javascript">
		var p = getXMLHTTP();
		
		function submitEmptyTabs() {
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('hideTabs');
			var el, tmp, type, cabinet;
			var inputElems = document.getElementsByTagName('INPUT');
			for(var i = 0; i < inputElems.length; i++) {
				if(inputElems[i].checked) {
					tmp = inputElems[i].id.split('-');
					cabinet = inputElems[i].name;
					type = tmp[0];
					el = xmlDoc.createElement('cabinet');
					el.setAttribute('name', cabinet);
					el.setAttribute('action', type);
					root.appendChild(el);
				}
			}
			xmlDoc.appendChild(root);
			var xmlStr = domToString(xmlDoc);
			p.open('POST', '../lib/settingsFuncs.php?func=emptyTabs', true);
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
			if(col.id == 'hideHeader') {
				for(var i = 0; el = document.getElementById('hide-' + i); i++) {
					el.checked = true;
				}
			} else {
				for(var i = 0; el = document.getElementById('show-' + i); i++) {
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
				<span>Show/Hide Empty Tabs</span>
			</div>
			<div class="inputForm">
				<table>
					<tr>
						<th></th>
						<th onclick="selectCol(this)" onmouseover="mOver(this)" onmouseout="mOut(this)" id="hideHeader">Hide Empty Tabs</th>
						<th onclick="selectCol(this)" onmouseover="mOver(this)" onmouseout="mOut(this)" id="showHeader">Show Empty Tabs</th>
					</tr>
					<?php $i = 0; ?>
					<?php foreach ($user->cabArr as $real => $arb): ?>
						<tr>
							<td><?php echo $arb ?></td>
							<td><input type="radio" id="<?php echo 'hide-'.$i ?>" name="<?php echo $real ?>" <?php echo $hideArr[$real] ?> /></td>
							<td><input type="radio" id="<?php echo 'show-'.$i ?>" name="<?php echo $real ?>" <?php echo $showArr[$real] ?> /></td>
						</tr>
						<?php $i++; ?>
					<?php endforeach; ?>
				</table>
			</div>
			<div><button onclick="submitEmptyTabs()">Save</button>
		</div>
	</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
