<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'depfuncs.php';
include_once '../updates/updatesFuncs.php';
include_once '../lib/quota.php';

if($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isSuperUser()) {

	$sArr = array('real_department','arb_department');
	$depList = getTableInfo($db_doc,'licenses',$sArr,array(),'getAssoc');
	uasort($depList,"strnatcasecmp");

	$tArr = array('db_list','users');
	$sArr = array('db_name','username');
	$wArr = array("priv='D'",
				"list_id=db_list_id");
	$oArr = array('db_name' => 'ASC');
	$gArr = array('db_name','username');
	$privArr = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc',array(),0,0,$gArr,true);
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<script type="text/javascript">
		function highlightRow(t) {
			t.style.backgroundColor = '#ebebeb';
		}

		function unhighlightRow(t) {
			t.style.backgroundColor = '#ffffff';
		}

		function highlightUser(t) {
			t.style.color = "red";
		}

		function unhighlightUser(t) {
			t.style.color = "black";
		}

		function selectDepartment() {
			var depSel = getEl('department');
			var dep = depSel.options[depSel.selectedIndex].value;
		
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);

			var depEl = xmlDoc.createElement('DEPARTMENT');
			root.appendChild(depEl);
			depEl.appendChild(xmlDoc.createTextNode(dep));

			var xmlStr = domToString(xmlDoc);
			postXML('getDepUsers',xmlStr);
			removeDefault(depSel);
			getEl('B1').disabled = true;
		}

		function selectUser() {
			getEl('B1').disabled = false;
			removeDefault(getEl('username'));
		}

		function addDepartmentAdmin() {
			var depSel = getEl('department');
			var dep = depSel.options[depSel.selectedIndex].value;
			
			var userSel = getEl('username');
			var uname = userSel.options[userSel.selectedIndex].value;

			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);

			var depEl = xmlDoc.createElement('DEPARTMENT');
			root.appendChild(depEl);
			depEl.appendChild(xmlDoc.createTextNode(dep));

			var userEl = xmlDoc.createElement('USERNAME');
			root.appendChild(userEl);
			userEl.appendChild(xmlDoc.createTextNode(uname));

			var xmlStr = domToString(xmlDoc);
			postXML('addAdmin',xmlStr);

			for(i=0;i<userSel.length;i++) {
				if(userSel.options[i].value == uname) {
					userSel.remove(i);
					break;
				}
			}

			getEl('B1').disabled = true;
			if(userSel.length) {
				addDefault(userSel);
			} else {
				var opt = document.createElement('option');
				opt.value = '__default';
				opt.appendChild(document.createTextNode('Choose One'));
				userSel.appendChild(opt);
				userSel.disabled = true;
			}

			if(getEl(dep+'-none')) {
				clearDiv(getEl(dep));
			}
	
			var sp = document.createElement('span');
			sp.style.cursor = 'pointer';
			sp.style.paddingLeft = '3px';
			sp.setAttribute('username', uname);
			sp.setAttribute('department', dep);
			sp.onclick = function() {deleteDepartmentAdmin(this)}; 
			sp.onmouseover = function() {highlightUser(this)}; 
			sp.onmouseout = function() {unhighlightUser(this)}; 
			sp.appendChild(document.createTextNode(uname));
			getEl(dep).appendChild(sp);
		}

		function deleteDepartmentAdmin(t) {
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);

			var depEl = xmlDoc.createElement('DEPARTMENT');
			root.appendChild(depEl);
			depEl.appendChild(xmlDoc.createTextNode(t.getAttribute('department')));

			var userEl = xmlDoc.createElement('USERNAME');
			root.appendChild(userEl);
			userEl.appendChild(xmlDoc.createTextNode(t.getAttribute('username')));

			var xmlStr = domToString(xmlDoc);
			postXML('deleteAdmin',xmlStr);

			var dep = t.getAttribute('department');
			t.parentNode.removeChild(t);
			var td = getEl(t.getAttribute('department'));
			var spList = td.getElementsByTagName('span');
			if(spList.length == 0) {
				var sp = document.createElement('span');
				sp.id = dep+"-none";
				sp.appendChild(document.createTextNode('none'));
				getEl(dep).appendChild(sp);
			}
			addDefault(getEl('department'));
			addDefault(getEl('username'));
			getEl('username').disabled = true;
		}

		function postXML(type,xmlStr) {
			var URL = 'departmentActions.php?'+type+'=1';
			var newAjax = new Ajax.Request( URL,
										{	method: 'post',
											postBody: xmlStr,
											onComplete: receiveXML,
											onFailure: reportError } );	
		}

		function receiveXML(req) {
			var XML = req.responseXML;
			var userList = XML.getElementsByTagName('USERNAME');
			clearDiv(getEl('errorMsg'));
			//getEl('username').disabled = true;
			if(userList.length > 0) {
				clearDiv(getEl('username'));
				addDefault(getEl('username'));
				for(var i=0;i<userList.length;i++) {
					var uname = userList[i].firstChild.nodeValue;
					var opt = document.createElement('option');
					opt.value = uname;

					var tnode = document.createTextNode(uname);
					opt.appendChild(tnode);
					getEl('username').appendChild(opt);
				}
				getEl('username').disabled = false;
			}

			var mess = XML.getElementsByTagName('MESSAGE');
			if(mess.length > 0) {
				clearDiv(getEl('errorMsg'));
				getEl('errorMsg').appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
			}
		}

		function reportError(req) {
			clearDiv(getEl('errorMsg'));
			var mess = "An error occured loading the XML"
			getEl('errorMsg').appendChild(document.createTextNode(mess));
		}
	</script>
</head>
<body>
	<div class="mainDiv" style="width:600px">
		<div class="mainTitle">
			<span>Add Department Administrator</span>
		</div>
		<div>
			<table style="width:100%">
				<tr>
					<td style="text-align:right">
						<span>Select Department:</span>
					</td>
					<td style="text-align:left">
						<select id='department' 
							name='department' 
							onchange="selectDepartment()"
						>
							<option value="__default">Choose One</option>
							<?php foreach($depList AS $real => $arb): ?>
							<option value="<?php echo $real; ?>"><?php echo $arb; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td style="text-align:right">
						<span>Select User:</span>
					</td>
					<td style="text-align:left">
						<select id="username" 
							name='username' 
							disabled="disabled" 
							style="width:150px"
							onchange="selectUser()"
						>
							<option value="__default">Choose One</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='right' class='error'>
						<span>&nbsp;</span>
						<input type='button' 
							id='B1' 
							name='B1' 
							value='ADD' 
							disabled="disabled" 
							onclick="addDepartmentAdmin()"
						/>
					</td>
				</tr>
			</table>
		</div>
		<div id="errorMsg" class="error" style="padding-top:5px;height:15px">&nbsp;</div>
		<div style="padding:5px">
			<fieldset style="padding:5px">
				<legend>
					<span>Department Administrators</span>
				</legend>
				<div class="inputForm">
				<table style="width:100%">
					<?php foreach($depList AS $real => $arb): ?> 
					<tr onmouseover="highlightRow(this)"
						onmouseout="unhighlightRow(this)"
					>
						<td><?php echo $arb; ?></td>
						<td id="<?php echo $real; ?>" style="text-align:right">
						<?php if(isSet($privArr[$real]) && count($privArr[$real])): ?>
							<?php foreach($privArr[$real] AS $uname): ?>
							 <span style="cursor:pointer;"
								username="<?php echo $uname; ?>"
								department="<?php echo $real; ?>"
								onclick="deleteDepartmentAdmin(this)"
								onmouseover="highlightUser(this)"
								onmouseout="unhighlightUser(this)"><?php echo $uname; ?></span>
							<?php endforeach; ?>
						<?php else: ?>
							<span id="<?php echo $real."-none"; ?>">none</span>
						<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>
				</div>
				<div style="padding-top:10px">
					<span class="error">Click to remove administrator</span>
				</div>
			</fieldset>
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
