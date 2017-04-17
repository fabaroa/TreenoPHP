function changeType(el) {
	myType = el.value;
	myUser = myGroup = myView = myCab = mySett = '';
	removeDefault(el);
	showRightLayers();
}

function changeUser(el) {
	myUser = el.value;
	myGroup = myView = myCab = mySett = '';
	removeDefault(el);
	showRightLayers();
}

function changeGroup(el) {
	myGroup = el.value;
	myArbGroup = el.options[el.selectedIndex].firstChild.nodeValue;
	myUser = myView = myCab = mySett = '';
	removeDefault(el);
	showRightLayers();
}

function changeCab(el) {
	myCab = el.value;
	myArbCab = el.options[el.selectedIndex].firstChild.nodeValue;	
	mySett = '';
	removeDefault(el);
	showRightLayers();
}

function changeSett(el) {
	mySett = el.value;
	myArbSett = el.options[el.selectedIndex].firstChild.nodeValue;
	myCab = '';
	removeDefault(el);
	showRightLayers();
}

function changeView(el) {
	myView = el.value;
	myCab = '';
	mySett = '';
	removeDefault(el);
	showRightLayers();
}

function showRightLayers() {
	clearInfoDiv();
	clearAlert();
	if(myType == 'System') {
		showDivs([viewByDiv]);
		hideDivs([whichUserDiv, whichGroupDiv]);
	} else if(myType == 'Groups') {
		if(myGroup == '') {
			loadGroupList();
			addDefault(whichGroup);
			showDivs([whichGroupDiv]);
			hideDivs([viewByDiv, whichUserDiv, systemSettings, submitBtn]);
		} else {
			showDivs([viewByDiv]);
		}
	} else if(myType == 'Users') {
		if(myUser == '') {
			loadUserList();
			addDefault(whichUser);
			showDivs([whichUserDiv]);
			hideDivs([viewByDiv, whichGroupDiv, systemSettings, submitBtn]);
		} else {
			showDivs([viewByDiv]);
		}
	}
	if(myView == '') {
		addDefault(viewBy);
		hideDivs([whichCabDiv, whichSettDiv, systemSettings, submitBtn]);
	} else if(myView == 'Cabinet') {
		if(myCab == '') {
			loadCabinetList();
			addDefault(whichCab);
			showDivs([whichCabDiv]);
			hideDivs([whichSettDiv, systemSettings, submitBtn]);
		} else {
			clearDiv(systemSettings);
			showSettingsByCab();
			showDivs([systemSettings, submitBtn]);
			showInfoDiv();
		}
	} else if(myView == 'Setting') {
		if(mySett == '') {
			loadSettingList();
			addDefault(whichSett);
			showDivs([whichSettDiv]);
			hideDivs([whichCabDiv, systemSettings, submitBtn]);
		} else {
			clearDiv(systemSettings);
			showSettingsBySetting();
			showDivs([systemSettings, submitBtn]);
			showInfoDiv();
		}
	} else if(myView == 'Global') {
		clearDiv(systemSettings);
		showGlobalSettings();
		showDivs([systemSettings, submitBtn]);
		hideDivs([whichCabDiv, whichSettDiv]);
		showInfoDiv();
	}
}

function loadUserList() {
	if(whichUser.options[0].value != '__default' || whichUser.options.length > 1) {
		return;
	}
	whichUser.disabled = true;
	alertBox('Communicating with Server...');
	p.open('GET', '../secure/userActions.php?getDeptUserList=1', true);
	try {
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState != 4) {
				return;
			}
			clearAlert();
			var xmlDoc = p.responseXML;
			var userArr = xmlDoc.getElementsByTagName('USER');
			var el;
			for(var i = 0; i < userArr.length; i++) {
				el = newEl('option');
				el.value = userArr[i].firstChild.nodeValue;
				el.appendChild(document.createTextNode(el.value));
				whichUser.appendChild(el);
			}
			whichUser.disabled = false;
		}
	} catch(e) {
		top.location.href = '../logout.php';
	}
}

function loadCabinetList() {
	if(whichCab.options[0].value != '__default' || whichCab.options.length > 1) {
		return;
	}
	whichCab.disabled = true;
	alertBox('Communicating with Server...');
	p.open('GET', '../secure/cabinetActions.php?getCabinetList=1', true);
	try {
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState != 4) {
				return;
			}
			clearAlert();
			var xmlDoc = p.responseXML;
			var cabArr = xmlDoc.getElementsByTagName('cabinet');
			var el;
			for(var i = 0; i < cabArr.length; i++) {
				el = newEl('option');
				el.value = cabArr[i].getAttribute('real_name');
				el.appendChild(document.createTextNode(cabArr[i].getAttribute('arb_name')));
				whichCab.appendChild(el);
			}
			whichCab.disabled = false;
		}
	} catch(e) {
		top.location.href = '../logout.php';
	}
}

function loadGroupList() {
	if(whichGroup.options[0].value != '__default' || whichGroup.options.length > 1) {
		return;
	}
	whichGroup.disabled = true;
	alertBox('Communicating with Server...');
	p.open('GET', '../groups/groupActions.php?getGroupList=1', true);
	try {
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState != 4) {
				return;
			}
			clearAlert();
			var xmlDoc = p.responseXML;
			var groupArr = xmlDoc.getElementsByTagName('group');
			var el;
			for(var i = 0; i < groupArr.length; i++) {
				el = newEl('option');
				el.value = groupArr[i].getAttribute('real_name');
				el.appendChild(document.createTextNode(groupArr[i].getAttribute('arb_name')));
				whichGroup.appendChild(el);
			}
			whichGroup.disabled = false;
		}
	} catch(e) {
		top.location.href = '../logout.php';
	}
}

function loadSettingList() {
	if(whichSett.options[0].value != '__default' || whichSett.options.length > 1) {
		return;
	}
	whichSett.disabled = true;
	alertBox('Communicating with Server...');
	p.open('GET', '../lib/settingsFuncs.php?func=getSettingList', true);
	try {
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState != 4) {
				return;
			}
			clearAlert();
			var xmlDoc = p.responseXML;
			var settArr = xmlDoc.getElementsByTagName('setting');
			var el;
			for(var i = 0; i < settArr.length; i++) {
				el = newEl('option');
				el.value = settArr[i].getAttribute('name');
				el.appendChild(document.createTextNode(settArr[i].getAttribute('disp_name')));
				whichSett.appendChild(el);
			}
			whichSett.disabled = false;
		}
	} catch(e) {
		top.location.href = '../logout.php';
	}
}

function clearInfoDiv() {
	clearDiv(infoDiv);
	hideDivs([infoDiv]);
}

function showInfoDiv() {
	clearInfoDiv();
	showDivs([infoDiv]);
	var str = myType + ' Settings';
	if(myUser != '') {
		str += ', User: ' + myUser;
	} else if(myGroup != '') {
		str += ', Group: ' + myArbGroup;
	}
	if(myCab != '') {
		str += ', Cabinet: ' + myArbCab;
	} else if(mySett != '') {
		str += ', Setting: ' + myArbSett;
	}
	infoDiv.appendChild(newTxt(str));
}

function showSettingsByCab() {
	var urlStr = '../lib/settingsFuncs.php?func=getSettingsByCab&v1=' + myCab + 
		'&v2=' + myUser + '&v3=' + myGroup;
	p.open('GET', urlStr, true);
	alertBox('Communicating with Server...');
	try {
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState != 4) {
				return;
			}
			clearAlert();
			var tbl = newEl('table');
			tbl.id = 'sysTable';
			systemSettings.appendChild(tbl);
			var tr = tbl.insertRow(tbl.rows.length);
			tr.className = 'myTblHead';
			var cell = tr.insertCell(tr.cells.length);
			cell.appendChild(newTxt('Setting'));
			cell = tr.insertCell(tr.cells.length);
			cell.className = 'myHover';
			cell.onmouseover = mOver;
			cell.onmouseout = mOut;
			cell.onclick = selectAllRadios;
			cell.id = 'enabled';
			cell.appendChild(newTxt('Enabled'));
			if(myType != 'Groups') {
				cell = tr.insertCell(tr.cells.length);
				cell.className = 'myHover';
				cell.onmouseover = mOver;
				cell.onmouseout = mOut;
				cell.onclick = selectAllRadios;
				cell.id = 'disabled';
				cell.appendChild(newTxt('Disabled'));
			}
			cell = tr.insertCell(tr.cells.length);
			cell.className = 'myHover';
			cell.onmouseover = mOver;
			cell.onmouseout = mOut;
			cell.onclick = selectAllRadios;
			cell.id = 'inherit';
			cell.appendChild(newTxt('Inherit'));
			var xmlDoc = p.responseXML;
			var settArr = xmlDoc.getElementsByTagName('setting');
			var input;
			for(var i = 0; i < settArr.length; i++) {
				tr = tbl.insertRow(tbl.rows.length);
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(newTxt(settArr[i].getAttribute('disp_name')));
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(input = createRadio('radio-' + i));
				input.cabinet = myCab;
				input.setAttribute('setting', settArr[i].getAttribute('name'));
				input.id = 'enabled-' + i;
				input.value = 'enabled';
				if(settArr[i].getAttribute('enabled') == 1) {
					input.checked = true;
					input.defaultChecked = true;
				}
				if(myType != 'Groups') {
					cell = tr.insertCell(tr.cells.length);
					cell.appendChild(input = createRadio('radio-' + i));
					input.cabinet = myCab;
					input.setAttribute('setting', settArr[i].getAttribute('name'));
					input.id = 'disabled-' + i;
					input.value = 'disabled';
					if(settArr[i].getAttribute('enabled') == 0) {
						input.checked = true;
						input.defaultChecked = true;
					}
				}
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(input = createRadio('radio-' + i));
				input.cabinet = myCab;
				input.setAttribute('setting', settArr[i].getAttribute('name'));
				input.id = 'inherit-' + i;
				input.value = 'inherit';
				if(settArr[i].getAttribute('enabled') == 2) {
					input.checked = true;
					input.defaultChecked = true;
				}
			}
		}
	} catch(e) {
		alertBox('oh no!');
	}
}


function showSettingsBySetting() {
	var urlStr = '../lib/settingsFuncs.php?func=getSettingsBySetting&v1=' + mySett + 
		'&v2=' + myUser + '&v3=' + myGroup;
	p.open('GET', urlStr, true);
	alertBox('Communicating with Server...');
	try {
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState != 4) {
				return;
			}
			clearAlert();
			var tbl = newEl('table');
			tbl.id = 'sysTable';
			systemSettings.appendChild(tbl);
			var tr = tbl.insertRow(tbl.rows.length);
			tr.className = 'myTblHead';
			var cell = tr.insertCell(tr.cells.length);
			cell.appendChild(newTxt('Cabinet'));
			cell = tr.insertCell(tr.cells.length);
			cell.className = 'myHover';
			cell.onmouseover = mOver;
			cell.onmouseout = mOut;
			cell.onclick = selectAllRadios;
			cell.id = 'enabled';
			cell.appendChild(newTxt('Enabled'));
			if(myType != 'Groups') {
				cell = tr.insertCell(tr.cells.length);
				cell.className = 'myHover';
				cell.onmouseover = mOver;
				cell.onmouseout = mOut;
				cell.onclick = selectAllRadios;
				cell.id = 'disabled';
				cell.appendChild(newTxt('Disabled'));
			}
			cell = tr.insertCell(tr.cells.length);
			cell.className = 'myHover';
			cell.onmouseover = mOver;
			cell.onmouseout = mOut;
			cell.onclick = selectAllRadios;
			cell.id = 'inherit';
			cell.appendChild(newTxt('Inherit'));
			var xmlDoc = p.responseXML;
			var settArr = xmlDoc.getElementsByTagName('cabinet');
			var input;
			for(var i = 0; i < settArr.length; i++) {
				tr = tbl.insertRow(tbl.rows.length);
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(newTxt(settArr[i].getAttribute('arb_name')));
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(input = createRadio('radio-' + i));
				input.cabinet = settArr[i].getAttribute('real_name');
				input.setAttribute('setting', mySett);
				input.id = 'enabled-' + i;
				input.value = 'enabled';
				if(settArr[i].getAttribute('enabled') == 1) {
					input.checked = true;
					input.defaultChecked = true;
				}
				if(myType != 'Groups') {
					cell = tr.insertCell(tr.cells.length);
					cell.appendChild(input = createRadio('radio-' + i));
					input.cabinet = settArr[i].getAttribute('real_name');
					input.setAttribute('setting', mySett);
					input.id = 'disabled-' + i;
					input.value = 'disabled';
					if(settArr[i].getAttribute('enabled') == 0) {
						input.checked = true;
						input.defaultChecked = true;
					}
				}
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(input = createRadio('radio-' + i));
				input.cabinet = settArr[i].getAttribute('real_name');
				input.setAttribute('setting', mySett);
				input.id = 'inherit-' + i;
				input.value = 'inherit';
				if(settArr[i].getAttribute('enabled') == 2) {
					input.checked = true;
					input.defaultChecked = true;
				}
			}
		}
	} catch(e) {
		alertBox('oh no!');
	}
}

function showGlobalSettings() {
	var urlStr = '../lib/settingsFuncs.php?func=getGlobalSettings&v1=' + myUser + '&v2=' + myGroup;
	p.open('GET', urlStr, true);
	try {
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState != 4) {
				return;
			}
			clearAlert();
			var tbl = newEl('table');
			tbl.id = 'sysTable';
			systemSettings.appendChild(tbl);
			var tr = tbl.insertRow(tbl.rows.length);
			tr.className = 'myTblHead';
			var cell = tr.insertCell(tr.cells.length);
			cell.appendChild(newTxt('Setting'));
			cell = tr.insertCell(tr.cells.length);
			cell.className = 'myHover';
			cell.onmouseover = mOver;
			cell.onmouseout = mOut;
			cell.onclick = selectAllRadios;
			cell.id = 'enabled';
			cell.appendChild(newTxt('Enabled'));
			if(myType != 'Groups') {
				cell = tr.insertCell(tr.cells.length);
				cell.className = 'myHover';
				cell.appendChild(newTxt('Disabled'));
				cell.onmouseover = mOver;
				cell.onmouseout = mOut;
				cell.onclick = selectAllRadios;
				cell.id = 'disabled';
			}
			if(myType != 'System') {
				cell = tr.insertCell(tr.cells.length);
				cell.className = 'myHover';
				cell.appendChild(newTxt('Inherit'));
				cell.onmouseover = mOver;
				cell.onmouseout = mOut;
				cell.onclick = selectAllRadios;
				cell.id = 'inherit';
			}
			var xmlDoc = p.responseXML;
			var settArr = xmlDoc.getElementsByTagName('setting');
			var input;
			for(var i = 0; i < settArr.length; i++) {
				tr = tbl.insertRow(tbl.rows.length);
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(newTxt(settArr[i].getAttribute('disp_name')));
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(input = createRadio('radio-' + i));
				input.setAttribute('setting', settArr[i].getAttribute('name'));
				input.id = 'enabled-' + i;
				input.value = 'enabled';
				if(settArr[i].getAttribute('state') == 'enabled') {
					input.checked = true;
					input.defaultChecked = true;
				}
				if(myType != 'Groups') {
					cell = tr.insertCell(tr.cells.length);
					cell.appendChild(input = createRadio('radio-' + i));
					input.setAttribute('setting', settArr[i].getAttribute('name'));
					input.id = 'disabled-' + i;
					input.value = 'disabled';
					if(settArr[i].getAttribute('state') == 'disabled') {
						input.checked = true;
						input.defaultChecked = true;
					}
					if ((myType == 'System') && settArr[i].getAttribute ('state') == 'inherit') {
						input.checked = true;
						input.defaultChecked = true;
					}
				}
				if(myType != 'System') {
					cell = tr.insertCell(tr.cells.length);
					cell.appendChild(input = createRadio('radio-' + i));
					input.setAttribute('setting', settArr[i].getAttribute('name'));
					input.id = 'inherit-' + i;
					input.value = 'inherit';
					if(settArr[i].getAttribute('state') == 'inherit') {
						input.checked = true;
						input.defaultChecked = true;
					}
				}
				if(settArr[i].getAttribute('mixed') == 1) {
					tr.style.backgroundColor = '#ff8888';
				}
			}
		}
	} catch(e) {
		alertBox('oh no!');
	}
}

function submitSettings() {
	var xmlDoc = createDOMDoc();
	var root, el;
	xmlDoc.appendChild(root = xmlDoc.createElement('settings'));
	root.appendChild(el = xmlDoc.createElement('type'));
	el.setAttribute('name', myType);
	if(myType == 'Users') {
		el.setAttribute('value', myUser);
	} else if(myType == 'Groups') {
		el.setAttribute('value', myGroup);
	}
	var inputArr = document.getElementsByTagName('input');
	for(var i = 0; i < inputArr.length; i++) {
		if(inputArr[i].checked && (inputArr[i].value == 'enabled' || 
			inputArr[i].value == 'disabled' || inputArr[i].value == 'inherit')) {
			if(inputArr[i].checked != inputArr[i].defaultChecked) {
				root.appendChild(el = xmlDoc.createElement('setting'));
				el.setAttribute('name', inputArr[i].getAttribute('setting'));
			
				if(inputArr[i].cabinet && inputArr[i].cabinet != '') {
					el.setAttribute('cabinet', inputArr[i].cabinet);
				}
				el.setAttribute('state', inputArr[i].value);
			}
		}
	}
	p.open('POST', '../lib/settingsFuncs.php?func=setSettings', true);
	try {
		p.send(domToString(xmlDoc));
		p.onreadystatechange = function () {
			if(p.readyState != 4) {
				return;
			}
			alertBox(p.responseText);
			for(var i = 0; i < getEl('sysTable').rows.length; i++) {
				getEl('sysTable').rows[i].style.backgroundColor = '#ffffff';
			}
		}
	} catch (e) {
		alertBox('oh no!');
	}
}

function registerVars() {
	whichUserDiv = getEl('whichUserDiv');
	whichGroupDiv = getEl('whichGroupDiv');
	whichCabDiv = getEl('whichCabDiv');
	whichSettDiv = getEl('whichSettDiv');
	viewByDiv = getEl('viewByDiv');
	whichUser = getEl('whichUser');
	whichGroup = getEl('whichGroup');
	whichCab = getEl('whichCab');
	whichSett = getEl('whichSett');
	viewBy = getEl('viewBy');
	errDiv = getEl('errDiv');
	infoDiv = getEl('infoDiv');
	systemSettings = getEl('systemSettings');
	submitBtn = getEl('submitBtn');
}

function alertBox(str) {
	errDiv.firstChild.nodeValue = str;
}

function mOver() {
	this.style.backgroundColor = '#999999';
}

function mOut() {
	this.style.backgroundColor = '#FFFFFF';
}

function selectAllRadios() {
	var el;
	for(var i = 0; el = getEl(this.id + '-' + i); i++) {
		el.checked = true;
	}
}

function clearAlert() {
	errDiv.firstChild.nodeValue = String.fromCharCode(160);
}

function testConn() {
	var connArr = new Object();
	connArr['dbHost'] = getEl('connHost').value;
	connArr['dbDSN'] = getEl('connDSN').value;
	connArr['dbUser'] = getEl('connUser').value;
	connArr['dbPasswd'] = getEl('connPasswd').value;
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('testConn');
	xmlDoc.appendChild(root);
	var el;
	for(var prop in connArr) {
		el = xmlDoc.createElement(prop);
		el.appendChild(xmlDoc.createTextNode(connArr[prop]));
		root.appendChild(el);
	}
}
