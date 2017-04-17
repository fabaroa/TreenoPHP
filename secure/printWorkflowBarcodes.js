var behaviors = {
	'#wfDefs' : function (element) {
		element.onchange = removeDefault;
	},
	'#cabList' : function (element) {
		element.onchange = removeDefault;
	},
	'#userList' : function (element) {
		element.onchange = removeDefault;
	},
	'#depts' : function (element) {
		element.onchange = function () {
			dbName = this.options[this.selectedIndex].value;
			changeDept ();
		}
	},
	'#btnPrint' : function(element) {
		element.onclick = function () {
			var mainDiv = $('mainDiv');
			var errDiv = $('errDiv');
			if(errDiv) {
				Element.remove(errDiv);
			}
			var errMsg = '';
			var defsSel = $('wfDefs');
			var cabSel = $('cabList');
			var userList = $('userList');
			if(defsSel.options) {
				var workflow = defsSel.options[defsSel.selectedIndex].value;
				if(workflow == '__default') {
					errMsg += " Select a Workflow. ";
				}
			} else {
				errMsg += " You have no Workflow to Print. ";
			}

			if(cabSel.options) {
				var cabinet = cabSel.options[cabSel.selectedIndex].value;
				if(cabinet == '__default') {
					errMsg += " Select a Cabinet. ";
				}
			} else {
				errMsg += ' You have no Cabinets. ';
			}
			var uid = userList.options[userList.selectedIndex].value;
			if(uid == '__default') {
				errMsg += " Select an Owner. ";
			}
			if (errMsg != '') {
				var el = document.createElement('div');
				el.appendChild(document.createTextNode(errMsg));
				el.id = 'errDiv';
				el.className = 'error';
				mainDiv.appendChild(el);
			} else {
				boolBC = false;
				printDocutronBarcode(cabinet, '', '', workflow, uid,
						dbName);
			}
			Behaviour.apply ();
		}
	}
};

window.onload = function () {
	var defsSel = $('wfDefs');
	var cabSel = $('cabList');
	var userList = $('userList');
	var el;
	if(numDefs == 0) {
		el = document.createElement('span');
		el.appendChild (document.createTextNode 
				('No Workflow Definitions Found'));
		el.className = 'error';
		el.id = 'wfDefs';
		defsSel.parentNode.replaceChild(el, defsSel);
	}
	if(numCabs == 0) {
		el = document.createElement('span');
		el.appendChild(document.createTextNode('No Cabinets Found'));
		el.className = 'error';
		el.id = 'cabList';
		cabSel.parentNode.replaceChild(el, cabSel);
	}
	$('mainDiv').style.display = 'block';
	if (dbName) {
		changeDept ();
	}
	Behaviour.apply ();
}

Behaviour.register(behaviors);

function removeDefault() {
	for (var i = 0; i < this.options.length; i++) {
		if (this.options[i].value == '__default') {
			Element.remove(this.options[i]);
			break;
		}
	}
}

function changeDept() {
	var urlStr = '../lib/settingsFuncs.php';
	var myAjax = new Ajax.Request (urlStr, {method: 'get', parameters:
			'func=getDeptInfo&v1=' + dbName, onComplete:
			changeDeptResponse});
}

function changeDeptResponse (response) {
	var defsSel = $('wfDefs');
	var cabSel = $('cabList');
	var userSel = $('userList');
	var xmlDoc = response.responseXML;
	var el, i;
	var myDefs = xmlDoc.getElementsByTagName('def');
	if(myDefs.length > 0) {
		if(defsSel.tagName != 'SELECT') {
			el = document.createElement('select');
			el.onchange = removeDefault;
			el.id = 'wfDefs';
			
			parentEl = defsSel.parentNode;
			parentEl.removeChild(defsSel);
			parentEl.appendChild(el);
			defsSel = el;
		}
		while(defsSel.hasChildNodes()) {
			defsSel.removeChild(defsSel.firstChild);
		}
		el = document.createElement('option');
		el.value = '__default';
		el.appendChild(document.createTextNode('Select a Workflow'));
		defsSel.appendChild(el);
		for(i = 0; i < myDefs.length; i++) {
			el = document.createElement('option');
			el.value = myDefs[i].getAttribute('name');
			el.appendChild(document.createTextNode(myDefs[i].getAttribute('name')));
			defsSel.appendChild(el); 
		}
	} else {
		el = document.createElement('span');
		el.appendChild(document.createTextNode('No Workflow Definitions Found'));
		el.className = 'error';
		el.id = 'wfDefs';
		defsSel.parentNode.replaceChild(el, defsSel);
	}
	if(cabSel.tagName != 'SELECT') {
		el = document.createElement('select');
		el.id = 'cabList';
			
		parentEl = cabSel.parentNode;
		parentEl.removeChild(cabSel);
		parentEl.appendChild(el);
		cabSel = el;
	}
	while(cabSel.hasChildNodes()) {
		cabSel.removeChild(cabSel.firstChild);
	}
	var myCabs = xmlDoc.getElementsByTagName('cab');
	if(myCabs.length > 0) {
		el = document.createElement('option');
		el.value = '__default';
		el.appendChild(document.createTextNode('Select a Cabinet'));
		cabSel.appendChild(el);
		for(i = 0; i < myCabs.length; i++) {
			el = document.createElement('option');
			el.value = myCabs[i].getAttribute('real');
			el.appendChild(document.createTextNode(myCabs[i].getAttribute('arb')));
			cabSel.appendChild(el); 
		}
	} else {
		el = document.createElement('span');
		el.appendChild(document.createTextNode('No Cabinets Found'));
		el.className = 'error';
		el.id = 'cabList';
		cabSel.parentNode.replaceChild(el, cabSel);
	}

	while(userSel.hasChildNodes()) {
		userSel.removeChild(userSel.firstChild);
	}
	var myUsers = xmlDoc.getElementsByTagName('user');
	if(myUsers.length > 0) {
		el = document.createElement('option');
		el.value = '__default';
		el.appendChild(document.createTextNode('Select an Owner'));
		userSel.appendChild(el);
		for(i = 0; i < myUsers.length; i++) {
			el = document.createElement('option');
			el.value = myUsers[i].getAttribute('id');
			el.appendChild(document.createTextNode(myUsers[i].getAttribute('name')));
			userSel.appendChild(el); 
		}
	}
	Behaviour.apply();
}
