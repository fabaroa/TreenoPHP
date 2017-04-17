<?php
// $Id: editTabGroupPermissions.php 14209 2011-01-04 15:51:32Z acavedon $

require_once '../check_login.php';
require_once '../lib/tabFuncs.php';
require_once '../groups/groups.php';
if($logged_in and $user->username and $user->isDepAdmin()) {
	if(!isset($_POST['func'])) {
		$noCabMsg = "You must have cabinets created to continue.";
		$selTab = 'Select a Tab';
		$groupWhoCan = 'Group Access';
		$selGroup = 'Select a Group';
		$permSec = 'Permissions Changed Successfully';
		$permNoSec = 'Permissions Not Changed Successfully!';
		$cabList = $user->cabArr; 
		uasort($cabList, 'strnatcasecmp');
		if(count($cabList) == 1) {
			$cabinet = key($cabList);
		}else{
			$cabinet = false;
		}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Edit Tab Group Permissions</title>
		<link rel="stylesheet" type="text/css" href="../lib/style.css" />
		<style type="text/css">
			#mySubmitDiv {
				text-align: center;
				margin: auto;
			}
			#mainFormDiv {
				padding: 1em;
				width: 70%;
				text-align: center;
				margin: auto;
			}
			.eachPanelItem {
				display: none;
				padding-top: 1em;
				padding-bottom: 1em;
			}
			.selDivs {
				text-align: left;
			}

			#grpSelDiv {
				text-align: center;
				margin-top: 1em;
				margin-right: 0;
				margin-left: 0;
			}
			#grpWhoTxtDiv {
				text-align: center;
				font-size: 10pt;
			}
			form {
				margin: 0;
			}
			body {
				font-size: 10pt;
			}
			.error {
				text-align: center;
			}
			table {
				margin-left: auto;
				margin-right: auto;
			}
		</style>
		<script type="text/javascript">
		var p;
		var myURL = '<?php echo $_SERVER['PHP_SELF'] ?>';
		var selTabTxt = '<?php echo $selTab ?>';
		var grpWhoTxt = '<?php echo $groupWhoCan ?>';
		var selGrpTxt = '<?php echo $selGroup ?>';
		var permSec = '<?php echo $permSec ?>';
		var permNoSec = '<?php echo $permNoSec ?>';
		var cabinet, tab, group;
		if(window.XMLHttpRequest) {
			p = new XMLHttpRequest();
		} else if(window.ActiveXObject) {
			p = new ActiveXObject("Microsoft.XMLHTTP");
		}

		function removeCmdFromSel(Elem) {
			for(var i = 0; i < Elem.options.length; i++) {
				if(Elem.options[i].value == '__cmd') {
					Elem.removeChild(Elem.options[i]);
					break;
				}
			}
		}

		function getTabs(e, myEl) {
			if(!myEl) {
				myEl = this;
			}
			var myCab = myEl.options[myEl.selectedIndex].value;
			removeCmdFromSel(myEl);
			cabinet = myCab;
			var cabList = document.getElementById('cabList');
			var groupList = document.getElementById('groupList');
			var tabListDoc = document.getElementById('tabList');
			var tabList = document.createElement('tr');
			if(tab) {
				groupList.style.display = 'none';
			}
			if(tabListDoc) {
				cabList.parentNode.removeChild(tabListDoc);
			}
			tab = '';
			p.open('POST', myURL, true);
			p.setRequestHeader('Content-Type',
				'application/x-www-form-urlencoded');
			p.send("func=getTabs&cabinet=" + myCab);
			p.onreadystatechange = function() {
				if(p.readyState == 4) {
					var xmlDoc = p.responseXML;
					var tabs = xmlDoc.getElementsByTagName('tab');
					if(tabs.length > 0) {
						var lblTd = document.createElement('td');
						lblTd.style.textAlign = 'right';
						var tabLbl = document.createElement('label');
						tabLbl.htmlFor = 'tabSelSelect';
						tabLbl.appendChild(document.createTextNode(selTabTxt));
						tabLbl.className = 'selLabel';
						lblTd.appendChild(tabLbl);
						tabList.appendChild(lblTd);
						var selTd = document.createElement('td');
						selTd.style.textAlign = 'left';
						var mySel = document.createElement('select');
						mySel.id = 'tabSelSelect';
						mySel.onchange = getGroups;
						var myOpt = document.createElement('option');
						myOpt.appendChild(document.createTextNode(selTabTxt));
						myOpt.value = "__cmd";
						mySel.appendChild(myOpt);
						for(var i = 0; i < tabs.length; i++) {
							myOpt = document.createElement('option');
							myOpt.value = tabs[i].getAttribute('realName');
							myOpt.appendChild(document.createTextNode(tabs[i].getAttribute('dispName')));
							mySel.appendChild(myOpt);
						}
						selTd.appendChild(mySel);
						tabList.appendChild(selTd);
					} else {
						var myTd = document.createElement('td');
						myTd.className = 'error';
						myTd.appendChild(document.createTextNode('There are no Saved Tabs!'));
						myTd.colSpan = '2';
						tabList.appendChild(myTd);
					}
					tabList.id = 'tabList';
					cabList.parentNode.appendChild(tabList);
				}
			};
		}

		function getGroups() {
			tab = this.options[this.selectedIndex].value;
			removeCmdFromSel(this);
			group = '';
			p.open('POST', myURL, true);
			p.setRequestHeader('Content-Type',
				'application/x-www-form-urlencoded');
			p.send("func=getGroups&cabinet=" + cabinet + "&tab=" + tab);
			p.onreadystatechange = function() {
				if(p.readyState == 4) {
					var xmlDoc = p.responseXML;
					var groups = xmlDoc.getElementsByTagName('group');
					var groupList = document.getElementById('groupList');
					while(groupList.hasChildNodes()) {
						groupList.removeChild(groupList.firstChild);
					}
					if(groups.length > 0) {
						var myDiv = document.createElement('div');
						myDiv.appendChild(document.createTextNode(grpWhoTxt));
						myDiv.id = 'grpWhoTxtDiv';
						groupList.appendChild(myDiv);
						var masterDiv = document.createElement('div');
						masterDiv.id = 'grpSelDiv';
						var mySel = document.createElement('select');
						mySel.id = 'groupSel';
						mySel.onchange = setGroup;
						var myTxt, myOpt;
						var isSelected = false;
						for(var i = 0; i < groups.length; i++) {
							myOpt = document.createElement('option');
							myOpt.value = groups[i].getAttribute('realName');
							myTxt = document.createTextNode(groups[i].getAttribute('arbName'));
							if(groups[i].getAttribute('selected')) {
								myOpt.selected = true;
								isSelected = true;
							}
							myOpt.appendChild(myTxt);
							mySel.appendChild(myOpt);
						}
						if(!isSelected) {
							myOpt = document.createElement('option');
							myOpt.appendChild(document.createTextNode(selGrpTxt));
							myOpt.value = "__cmd";
							myOpt.selected = true;
							mySel.appendChild(myOpt);
						}
						masterDiv.appendChild(mySel);

						var sp = document.createElement('span');
						sp.style.position = 'relative';
						sp.style.left = '15px';
						sp.style.color = 'blue';
						sp.style.textDecoration = 'underline';
						sp.style.cursor = 'pointer';
						sp.onclick = removePerms;
						sp.appendChild(document.createTextNode('remove'));
						masterDiv.appendChild(sp);

						groupList.appendChild(masterDiv);
						document.getElementById('mySubmitDiv').style.display = 'block';
					} else {
						var myDiv = document.createElement('div');
						myDiv.className = 'error';
						myDiv.appendChild(document.createTextNode('There are no Groups on Your System!'));
						groupList.appendChild(myDiv);
						
					}
					groupList.style.display = 'block';
				}
			}
		}

		function setGroup() {
			removeCmdFromSel(this);
			group = this.options[this.selectedIndex].value;
		}

		function submitPerms() {
			var errDiv = document.getElementById('errDiv');
			errDiv.firstChild.nodeValue = "\u00a0";
			var postStr = 'cabinet=' + cabinet + '&tab=' + tab + '&group=';
			postStr += group + '&func=setGroup';
			p.open('POST', myURL, true);
			p.setRequestHeader('Content-Type',
				'application/x-www-form-urlencoded');
			p.send(postStr);
			p.onreadystatechange = function() {
				if(p.readyState == 4) {
					if(p.responseText == 'OK') {
						errDiv.firstChild.nodeValue = permSec;
					} else {
						errDiv.firstChild.nodeValue = permNoSec;
					}
				}
			}
		}

		function removePerms() {
			var errDiv = document.getElementById('errDiv');
			errDiv.firstChild.nodeValue = "\u00a0";
			var postStr = 'cabinet=' + cabinet + '&tab=' + tab + '&func=removeGroup';
			p.open('POST', myURL, true);
			p.setRequestHeader('Content-Type',
				'application/x-www-form-urlencoded');
			p.send(postStr);
			p.onreadystatechange = function() {
				if(p.readyState == 4) {
					if(p.responseText == 'OK') {
						errDiv.firstChild.nodeValue = permSec;
						myOpt = document.createElement('option');
						myOpt.appendChild(document.createTextNode(selGrpTxt));
						myOpt.value = "__cmd";
						myOpt.selected = true;
						gSel = document.getElementById('groupSel');
						gSel.appendChild(myOpt);
					} else {
						errDiv.firstChild.nodeValue = permNoSec;
					}
				}
			}
		}

		function registerEvents() {
			var cabSelect = document.getElementById('cabSelect');
			if(cabSelect) {
				cabSelect.onchange = getTabs;
			}
		}
		</script>
	</head>
	<body class="centered" onload="registerEvents()">
		<div class="mainDiv" style="width: 500px">
			<div class="mainTitle">
				<span>Folder Tab Access</span>
			</div>
			<div id="mainFormDiv">
				<?php if(!count($cabList)): ?>
					<div><?php echo $noCabMsg ?></div>
				<?php else: ?>
					<table>
						<tr id="cabList">
							<td style="text-align: right">
								<label class="selLabel" for="cabSelect">
									<?php echo $trans['Choose Cabinet'] ?>
								</label>
							</td>
							<td style="text-align: left">
								<select id="cabSelect">
									<?php if(!$cabinet): ?>
										<?php $cabinet=null ?>
										<option value="__cmd">
											<?php echo $trans['Choose Cabinet'] ?>
										</option>
									<?php endif; ?>
									<?php foreach($cabList as $real => $disp): ?>
										<?php if($cabinet == $real): ?>
											<option
												value="<?php echo $real ?>"
												selected="selected"
											>
												<?php echo $disp ?>
											</option>
											<script type="text/javascript">
												getTabs(window.event, document.getElementById('cabSelect'));
											</script>
										<?php else: ?>
											<option value="<?php echo $real ?>">
												<?php echo $disp ?>
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				<?php endif; ?>
				<div id="groupList" class="eachPanelItem"></div>
				<div id="mySubmitDiv" class="eachPanelItem">
					<button onclick="submitPerms()">Save</button>
				</div>
				<div id="errDiv" class="error">&nbsp;</div>
			</div>
		</div>
	</body>
</html>
<?php
	} else {
		$cabinet = $_POST['cabinet'];
		$myFunction = $_POST['func'];
		switch($myFunction) {
		case 'getTabs':
			showTabList($cabinet, $user->db_name, $db_doc, $db_object);
			break;
		case 'getGroups':
			showGroups($user->db_name, $cabinet, $user->getDbObject());
			break;
		case 'setGroup':
			setGroup($cabinet, $user->getDbObject(), $user->db_name);
			break;
		case 'removeGroup':
			removeGroup($cabinet, $user->getDbObject());
			break;
		default:
			break;
		}
	}
}

function showTabList($cabinet, $db_name, $db_doc, $db_dept) {
	$savedTabs = getSavedTabs($cabinet, $db_name, $db_doc);

	$sArr = array ('subfolder');
	$groupTabs = getTableInfo ($db_dept, 'group_tab', $sArr, array (), 'queryCol');

	$allTabs = array_unique (array_merge ($savedTabs, $groupTabs));
	usort ($allTabs, 'strnatcasecmp');
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$tabs = $xmlDoc->create_element('tabs');
		foreach($allTabs as $myTab) {
			$tab = $xmlDoc->create_element('tab');
			$tab->set_attribute('dispName', str_replace('_', ' ', $myTab));
			$tab->set_attribute('realName', $myTab);
			$tabs->append_child($tab);
		}
		$xmlDoc->append_child($tabs);
		$xmlStr = $xmlDoc->dump_mem(false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$tabs = $xmlDoc->createElement('tabs');
		foreach($allTabs as $myTab) {
			$tab = $xmlDoc->createElement('tab');
			$tab->setAttribute('dispName', str_replace('_', ' ', $myTab));
			$tab->setAttribute('realName', $myTab);
			$tabs->appendChild($tab);
		}
		$xmlDoc->appendChild($tabs);
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr; 
}

function showGroups($db_name, $cabinet, $db_object) {
	$groups = new groups($db_object);
	$groupList = $groups->getGroups();
	$groupAccess = getTableInfo($db_object,'group_tab',array(),array(),'queryAll');	
	$tab = $_POST['tab'];
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_new_doc('1.0');
		$groups = $xmlDoc->create_element('groups');
		foreach($groupList as $realName => $arbName) {
			$group = $xmlDoc->create_element('group');
			$group->set_attribute('realName', $realName);
			$group->set_attribute('arbName', $arbName);
			foreach($groupAccess as $i) {
				if($i['authorized_group'] == $realName &&
					$i['cabinet'] == $cabinet &&
						$i['subfolder'] == $tab) {

					$group->set_attribute('selected', 'selected');
					break;
				}
			}
			$groups->append_child($group);
		}
		$xmlDoc->append_child($groups);
		$xmlStr = $xmlDoc->dump_mem(false);
	} else {
		$xmlDoc = new DOMDocument (); 
		$groups = $xmlDoc->createElement('groups');
		foreach($groupList as $realName => $arbName) {
			$group = $xmlDoc->createElement('group');
			$group->setAttribute('realName', $realName);
			$group->setAttribute('arbName', $arbName);
			foreach($groupAccess as $i) {
				if($i['authorized_group'] == $realName &&
					$i['cabinet'] == $cabinet &&
						$i['subfolder'] == $tab) {

					$group->setAttribute('selected', 'selected');
					break;
				}
			}
			$groups->appendChild($group);
		}
		$xmlDoc->appendChild($groups);
		$xmlStr = $xmlDoc->saveXML ();
	}
	header('Content-type: text/xml');
	echo $xmlStr;
}

function setGroup($cabinet, $db_object, $dbName) {
	$tab = $_POST['tab'];
	$group = $_POST['group'];
	$queryArr = array(
		'subfolder'			=> $tab,
		'cabinet'			=> $cabinet,
		'authorized_group'	=> $group,
		'doc_id'			=> 0
	);
	$whereArr = array('subfolder'=>$tab,'cabinet'=>$cabinet);
	$count = getTableInfo($db_object,'group_tab',array('COUNT(*)'),$whereArr,'queryOne');	
	if(!$count) {
		$db_object->extended->autoExecute('group_tab', $queryArr);
	} else {
		$where = "cabinet = '$cabinet' AND subfolder = '$tab'";
		$r = $db_object->extended->autoExecute('group_tab', $queryArr,
			MDB2_AUTOQUERY_UPDATE, $where);
	}
	echo "OK";
}

function removeGroup($cabinet,$db_object) {
	$tab = $_POST['tab'];
	$wArr = array(	'subfolder'	=> $tab,
					'cabinet'	=> $cabinet,
					'doc_id'	=> 0 );
	deleteTableInfo($db_object,'group_tab',$wArr);	
	echo "OK";
}
?>
