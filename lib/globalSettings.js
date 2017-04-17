function registerVars() {
	whichSettDiv = getEl('whichSettDiv');
	whichSett = getEl('whichSett');
	errDiv = getEl('errDiv');
	infoDiv = getEl('infoDiv');
	globalSettings = getEl('globalSettings');
	submitBtn = getEl('submitBtn');
}

function changeType(el) {
	myType = el.options[el.selectedIndex].value;
	showDivs([whichSettDiv]);
	removeDefault(el);
	addDefault(whichSett);
	hideDivs([globalSettings, submitBtn]);
	clearAlert();
	if(whichSett.options[0].value != '__default' || whichSett.options.length > 1) {
		return;
	}
	whichSett.disabled = true;
	alertBox('Communicating with Server...');
	p.open('GET', '../lib/settingsFuncs.php?func=getAllNonCabinetSettings', true);
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
				el.value = settArr[i].getAttribute('settingName');
				el.appendChild(newTxt(settArr[i].getAttribute('text')));
				whichSett.appendChild(el);
			}
			whichSett.disabled = false;
		}
	} catch(e) {
		top.location.href = '../logout.php';
	}
}

function changeSett(el) {
	mySett = el.options[el.selectedIndex].value;
	removeDefault(el);
	alertBox('Communicating with Server...');
	p.open('GET', '../lib/settingsFuncs.php?func=getNonCabinetSettings&v1=' + myType + '&v2=' + mySett, true);
	try {
		p.send(null);
		p.onreadystatechange = function () {
			if(p.readyState != 4) {
				return;
			}
			var xmlDoc = p.responseXML;
			clearAlert();
			clearDiv(globalSettings);
			var tbl = newEl('table');
			tbl.id = 'sysTable';
			globalSettings.appendChild(tbl);
			var tr = tbl.insertRow(tbl.rows.length);
			tr.className = 'myTblHead';
			var cell;
			if(myType != 'System') {
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(newTxt(myType));
			}
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
			if(myType != 'System') {
				cell = tr.insertCell(tr.cells.length);
				cell.className = 'myHover';
				cell.onmouseover = mOver;
				cell.onmouseout = mOut;
				cell.onclick = selectAllRadios;
				cell.id = 'inherit';
				cell.appendChild(newTxt('Inherit'));
			}
			var xmlDoc = p.responseXML;
			var settArr = xmlDoc.getElementsByTagName('setting');
			var input;
			for(var i = 0; i < settArr.length; i++) {
				tr = tbl.insertRow(tbl.rows.length);
				if(myType != 'System') {
					cell = tr.insertCell(tr.cells.length);
					cell.appendChild(newTxt(settArr[i].getAttribute('name')));
				}
				cell = tr.insertCell(tr.cells.length);
				cell.appendChild(input = createRadio('radio-' + i));
				if(myType == 'Groups') {
					input.setAttribute('group', settArr[i].getAttribute('real_name'));
				}
				if(myType == 'Users') {
					input.setAttribute('user', settArr[i].getAttribute('name'));
				}
				input.id = 'enabled-' + i;
				input.value = 'enabled';
				if(settArr[i].getAttribute('enabled') == 1) {
					input.checked = true;
				}
				if(myType != 'Groups') {
					cell = tr.insertCell(tr.cells.length);
					cell.appendChild(input = createRadio('radio-' + i));
					input.id = 'disabled-' + i;
					input.value = 'disabled';
					if(settArr[i].getAttribute('enabled') == 0) {
						input.checked = true;
					}
					if(myType == 'Users') {
						input.setAttribute('user', settArr[i].getAttribute('name'));
					}
				}
				if(myType != 'System') {
					cell = tr.insertCell(tr.cells.length);
					cell.appendChild(input = createRadio('radio-' + i));
					if(myType == 'Groups') {
						input.setAttribute('group', settArr[i].getAttribute('real_name'));
					}
					input.id = 'inherit-' + i;
					input.value = 'inherit';
					if(settArr[i].getAttribute('enabled') == 2) {
						input.checked = true;
					}
					if(myType == 'Users') {
						input.setAttribute('user', settArr[i].getAttribute('name'));
					}
				}
			}
			showDivs([globalSettings, submitBtn]);
		}
	} catch(e) {
		top.location.href = '../logout.php';
	}
}

function submitSettings() {
	clearAlert();
	var xmlDoc = createDOMDoc();
	var root, el;
	xmlDoc.appendChild(root = xmlDoc.createElement('settings'));
	root.appendChild(el = xmlDoc.createElement('type'));
	el.setAttribute('name', myType);
	el.setAttribute('setting', mySett);
//	if(myType == 'Users') {
//		el.setAttribute('value', myUser);
//	} else if(myType == 'Groups') {
//		el.setAttribute('value', myGroup);
//	}
	var inputArr = document.getElementsByTagName('input');
	for(var i = 0; i < inputArr.length; i++) {
		if(inputArr[i].checked && (inputArr[i].value == 'enabled' || 
			inputArr[i].value == 'disabled' || inputArr[i].value == 'inherit')) {

			root.appendChild(el = xmlDoc.createElement('setting'));
			el.setAttribute('state', inputArr[i].value);
			if(myType == 'Users') {
				el.setAttribute('name', inputArr[i].getAttribute('user'));
			}
			if(myType == 'Groups') {
				el.setAttribute('name', inputArr[i].getAttribute('group'));
			}
		}
	}
	p.open('POST', '../lib/settingsFuncs.php?func=setNonCabinetSettings', true);
	alertBox('Communicating with server...');
	try {
		var xmlStr = domToString(xmlDoc);
		p.send(xmlStr);
		p.onreadystatechange = function () {
			if(p.readyState != 4) {
				return;
			}
			alertBox(p.responseText);
		}
	} catch (e) {
		alertBox('oh no!');
	}
}

function clearAlert() {
	errDiv.firstChild.nodeValue = String.fromCharCode(160);
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

