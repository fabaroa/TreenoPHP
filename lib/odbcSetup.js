function changeType(el) {
	clearInputs();
	myConnID = '';
	removeDefault(el);
	myType = el.value;
	displayLayers();
}

function changeConn(el) {
	removeDefault(el);
	myConnID = el.value;
	p.open('GET', '../lib/settingsFuncs.php?func=getODBCConnInfo&v1=' + myConnID, true);
	alertBox('Communicating with server...');
	disableInputs(true);
	try {
		p.send(null);
	} catch(e) {
		alertBox('oh no3!');
	}
	p.onreadystatechange = function () {
		if(p.readyState != 4) {
			return;
		}
		var xmlDoc = p.responseXML;
		var connArr = xmlDoc.getElementsByTagName('connect');
		for(var i = 0; i < connArr.length; i++) {
			getEl(connArr[i].getAttribute('key')).value = connArr[i].getAttribute('value');
		}
		clearAlert();
		disableInputs(false);
		displayLayers(true);
	}
}

function displayLayers(noClear) {
	if(!noClear) {
		clearAlert();
	} 
	switch(myType) {
		case 'Add':
			showDivs([editConnDiv, submitConnBtn]);
			hideDivs([whichConnDiv]);
			break;
		case 'Edit':
			showDivs([whichConnDiv]);
			if(myConnID) {
				showDivs([editConnDiv, submitConnBtn]);
			} else {
				hideDivs([editConnDiv, submitConnBtn]);
				clearEl(whichConnSel);
				addDefault(whichConnSel);
				getConnectors();
			}
			break;
		default:
			hideDivs([editConnDiv, whichConnDiv, submitConnBtn]);
			addDefault(whichTypeSel);
			myType = '';
			myConnID = '';
	}
}

function clearInputs() {
	var inputArr = document.getElementsByTagName('input');
	for(var i = 0; i < inputArr.length; i++) {
		inputArr[i].value = '';
	}
}

function disableInputs(val) {
	var inputArr = document.getElementsByTagName('input');
	for(var i = 0; i < inputArr.length; i++) {
		inputArr[i].disabled = val;
	}
}

function registerVars() {
	editConnDiv = getEl('editConnDiv');
	whichConnSel = getEl('whichConn');
	whichConnDiv = getEl('whichConnDiv');
	submitConnBtn = getEl('submitConnBtn');
	errDiv = getEl('errDiv');
	whichTypeSel = getEl('whichType');
}

function clearEl(el) {
	while(el.hasChildNodes()) {
		el.removeChild(el.firstChild);
	}
}

function getConnectors() {
	whichConnSel.disabled = true;
	var URL = '../lib/settingsFuncs.php?func=getODBCConnList';
	p.open('GET', URL, true);
	alertBox('Communicating with Server...');
	try {
		p.send(null);
	} catch(e) {
		alertBox('oh no!');
	}
	p.onreadystatechange = function () {
		if(p.readyState != 4) {
			return;
		}
		try {
			clearAlert();
			var xmlDoc = p.responseXML;
			var arr = xmlDoc.getElementsByTagName('connect');
			var el;
			for(var i = 0; i < arr.length; i++) {
				el = newEl('option');
				el.value = arr[i].getAttribute('id');
				el.appendChild(newTxt(arr[i].firstChild.nodeValue));
				whichConnSel.appendChild(el);
			}
			whichConnSel.disabled = false;
		} catch(e) {
			alertBox('on no2!');
		}
	};
}

function clearAlert() {
	errDiv.firstChild.nodeValue = String.fromCharCode(160);
}

function alertBox(str) {
	errDiv.firstChild.nodeValue = str;
}

function testConn() {
	var xmlStr = getXMLForConn();
	p.open('POST', '../lib/settingsFuncs.php?func=testODBCConn', true);
	alertBox('Testing Connection...');
	try {
		p.send(xmlStr);
	} catch(e) {
		alertBox('bad stuff happened');
	}
	p.onreadystatechange = function() {
		if(p.readyState != 4) {
			return;
		}
		alertBox(p.responseText);
	}
}

function getXMLForConn() {
	var connArr = new Object();
	connArr['connect_name'] = getEl('connect_name').value;
	connArr['type'] = getEl('type').value;
	connArr['host'] = getEl('host').value;
	connArr['dsn'] = getEl('dsn').value;
	connArr['username'] = getEl('username').value;
	connArr['password'] = getEl('password').value;
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('root');
	xmlDoc.appendChild(root);
	var el = xmlDoc.createElement('update_id');
	el.appendChild(xmlDoc.createTextNode(myConnID));
	root.appendChild(el);
	
	for(var prop in connArr) {
		el = xmlDoc.createElement('connect');		
		el.setAttribute('key', prop);
		el.setAttribute('value', connArr[prop]);
	
		root.appendChild(el);
	}
	var xmlStr = domToString(xmlDoc);
	return xmlStr;
}

function submitConn() {
	var xmlStr = getXMLForConn();
	p.open('POST', '../lib/settingsFuncs.php?func=submitConn', true);
	alertBox('Communicating with server...');
	try {
		p.send(xmlStr);
	} catch(e) {
		alertBox('bad stuff happened');
	}
	p.onreadystatechange = function() {
		if(p.readyState != 4) {
			return;
		}
		var xmlDoc = p.responseXML;
		var retVal = xmlDoc.getElementsByTagName('return');
		alertBox(retVal[0].getAttribute('text'));
		if(myType == 'Add') {
			myType = '';
		}
		if(retVal[0].getAttribute('val') == 1) {
			window.location = "odbcWizard.php";
		}
	}
}
