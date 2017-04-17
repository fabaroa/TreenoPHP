<?php
// $Id: addDepartment.php 14867 2012-07-03 13:05:47Z fabaroa $
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';
include_once '../updates/updatesFuncs.php';
include_once '../db/db_common.php';
include_once '../settings/settings.php';
include_once '../lib/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isSuperUser()) {
	$uNames = getUsernames( $db_doc );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>New Department</title>
	<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script>
		var p = getXMLHTTP();
		function mOver(t) {
			t.style.backgroundColor = '#888888';
		}

		function mOut(t) {
			t.style.backgroundColor = '#ffffff';
		}

		function selectRights(type) {
			var checkList = document.getElementsByTagName('input');
			for(var i=0;i<checkList.length;i++) {
                if(checkList[i].type == 'radio' && checkList[i].value == type) {
                        checkList[i].checked = true;
                }
			}
		}

		function submitDepartment() {
			var eMsg = getEl('errMsg');
			clearDiv(eMsg);	
			if(getEl('newDepName').value) {
				p.open('POST', 'departmentActions.php?addDepartment=1', true);
				try {
					document.body.style.cursor = 'wait';
					eMsg.appendChild(document.createTextNode('Please Wait....'));
					p.send(createXML());
				} catch(e) {
					clearDiv(eMsg);	
					document.body.style.cursor = 'default';
					eMsg.appendChild(document.createTextNode('Error occured connecting'));
				}
				p.onreadystatechange = function() {
					if(p.readyState != 4) {
						return;
					}
					clearDiv(eMsg);
					var XML  = p.responseXML;
					var mess = XML.getElementsByTagName('MESSAGE');
					if(mess.length > 0) {
						var text = document.createTextNode(mess[0].firstChild.nodeValue);
						parent.topMenuFrame.window.location = '../energie/menuSlide_NewUI.php';
					} else {
						var text = document.createTextNode('Error occured processing XML');
					}
					eMsg.appendChild(text);
					document.body.style.cursor = 'default';
				};			
			} else {
				var text = document.createTextNode('Department name is empty');
				eMsg.appendChild(text);
			}
		}

		function createXML() {
			var xmlDoc = createDOMDoc();
	
			var dep = xmlDoc.createElement('DEPARTMENT');	
			xmlDoc.appendChild(dep);

			var name = xmlDoc.createElement('NAME');
			var text = xmlDoc.createTextNode(getEl('newDepName').value);
			name.appendChild(text);
			dep.appendChild(name);

			var user = xmlDoc.createElement('USER');
			user.appendChild(xmlDoc.createTextNode('admin'));
			dep.appendChild(user);
			var rd = document.getElementsByTagName('input');
			for(var i=0;i<rd.length;i++) {
				if(rd[i].type == 'radio' && rd[i].value == 'yes' && rd[i].checked == true) {
					var user = xmlDoc.createElement('USER');
					var text = xmlDoc.createTextNode(rd[i].name);
					user.appendChild(text);
					dep.appendChild(user);
				}
			}

			return domToString(xmlDoc);
		}
	</script>
</head>
<body>
	<div class="mainDiv" style="width:350px">
		<div class="mainTitle">
			<span>Add Department</span>
		</div>
		<div id="depName">
			<table width='100%'>
				<tr>
					<th style='text-align:right'>Department Name</th>
					<td><input type='text' id='newDepName' name='newDepName'></td>
				</tr>
			</table>
		</div>
		<?php if(sizeof($uNames) > 0): ?>
			<div style="padding-top: 10px">
				<table width='100%'>
					<tr>
						<th>Username</th>
						<th style='cursor:pointer' onmouseover='mOver(this)' 
							onmouseout='mOut(this)'
							onclick="selectRights('yes')">Grant</th>
						<th style='cursor:pointer' onmouseover='mOver(this)' 
							onmouseout='mOut(this)'
							onclick="selectRights('no')">Deny</th>
					</tr>
					<?php foreach($uNames AS $u): ?>
						<tr style="cursor:pointer" onmouseover='mOver(this)' onmouseout='mOut(this)'>
							<td style='width:80%;text-align:center'><?php echo $u; ?></td>
							<td style='width:10%'>
								<input type='radio' name='<?php echo $u; ?>' value='yes'>
							</td>
							<td style='width:10%'>
								<input type='radio' name='<?php echo $u; ?>' value='no' checked>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>
		<?php endif; ?>
		<div style='width:100%;height:25px'>
			<div style='width:75%;float:left;text-align:center'>
				<span id='errMsg' class='error'></span>
			</div>
			<div style='float:right'>
				<input type='button' name='submit' onclick='submitDepartment()' value='Save'>
			</div>
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
