var tabEl = "";

function initialize() {
	var behaviors = {
			'#docList'	: function (el) {
				el.onchange = function () { selectDocument() };
			},
			'#docIndex'	: function (el) {
				el.disabled = true;
				el.onchange = function () { };
			},
			'#table1'	: function (el) {
				el.divname = "div1";
			},
			'#table2'	: function (el) {
				el.divname = "div2";
			},
			'#table3'	: function (el) {
				el.divname = "div3";
			},
			'#newDocValue'	: function (el) {
				el.disabled = true;
			},
			'#docIndexValue'	: function (el) {
				el.disabled = true;
				el.onchange = function () { selectIndexValue() };
			},
			'#docValue'	: function (el) {
				el.disabled = true;
			},
			'#rmDocIndexValue'	: function (el) {
				el.disabled = true;
			},
			'#saveBtn'	: function (el) {
				el.disabled = false;
			}
	}
	Behaviour.register(behaviors);
	Behaviour.apply();
}

Behaviour.addLoadEvent(
	function() {
		initialize();
		getDocumentList();
	}
);

function setMessage(mess) {
	removeElementsChildren($('errMsg'));

	$('errMsg').appendChild(document.createTextNode(mess));
}

function getDocumentList() {
	document.body.style.cursor = 'wait';
	setMessage('Retrieve Document Types');

	var xmlArr = {	"include" : "documents/documents.php",
					"function" : "getDocTypeList" };
	postXML(xmlArr);
}

function setDocTypeList(XML) {
	dList = XML.getElementsByTagName('DOCTYPE');
	if(dList.length > 0) {
		removeElementsChildren($('docList'));

		var opt = document.createElement('option');
		opt.docTypeID = '__default';
		opt.value = '__default';
		opt.appendChild(document.createTextNode('Choose One'));
	
		$('docList').appendChild(opt);
		for(i=0;i<dList.length;i++) {
			var opt = document.createElement('option');
			opt.docTypeID = dList[i].getAttribute('id');
			opt.value = dList[i].getAttribute('name');
			opt.appendChild(document.createTextNode(dList[i].firstChild.nodeValue));
		
			$('docList').appendChild(opt);
		}
	}
	removeElementsChildren($('errMsg'));
	document.body.style.cursor = 'default';
}

function selectDocument() {
	document.body.style.cursor = 'wait';
	setMessage('Retrieving Document Indexes');
	var dList = $('docList');
	var docName = dList.options[dList.selectedIndex].value;

	var xmlArr = {	"include" : "documents/documents.php",
					"function" : "getDocTypeFields",
					"docName" : docName };
	postXML(xmlArr);
	removeDefault($('docList'));
	resetFields();
	unlockBackground();
	disableBehavior();
	$('div1').className = "addIndexDivFade";
	$('div2').className = "addIndexDivFade";
	$('div3').className = "addIndexDivFade";
}

function setDocTypeFieldList(XML) {
	dfList = XML.getElementsByTagName('DOCFIELD');
	if(dfList.length > 0) {
		removeElementsChildren($('docIndex'));

		addDefault($('docIndex'));
		for(i=0;i<dfList.length;i++) {
			var val = dfList[i].getAttribute('real_name');
			var t = dfList[i].firstChild.nodeValue;
			createOptElement($('docIndex'),val,t);
		}
		$('docIndex').disabled = false;
		$('docIndex').onchange = selectIndex;
	}
	removeElementsChildren($('errMsg'));
	document.body.style.cursor = 'default';
}

function selectIndex() {
	document.body.style.cursor = 'wait';
	setMessage('Retrieving Document Index Definitions');
	var dList = $('docList');
	var docTypeID = dList.options[dList.selectedIndex].docTypeID;

	var dList = $('docIndex');
	var indexField = dList.options[dList.selectedIndex].value;
	
	var xmlArr = {	"include" : "documents/documents.php",
					"function" : "getDocIndexTypeDefs",
					"document_type_id" : docTypeID,
					"document_type_field" : indexField };
	postXML(xmlArr);
	removeDefault($('docIndex'));
	resetFields();
	unlockBackground();
	enableBehavior();
	$('div1').className = "addIndexDivShow";
	$('div2').className = "addIndexDivShow";
	$('div3').className = "addIndexDivShow";
}

function setDocTypeDefs(XML) {
	dfList = XML.getElementsByTagName('DEFINITION');

	removeElementsChildren($('docIndexValue'));
	removeElementsChildren($('rmDocIndexValue'));

	addDefault($('docIndexValue'));
	addDefault($('rmDocIndexValue'));
	if(dfList.length > 0) {
		for(i=0;i<dfList.length;i++) {
			var val = dfList[i].getAttribute('id');
			var t = dfList[i].firstChild.nodeValue;
			createOptElement($('docIndexValue'),val,t);
			createOptElement($('rmDocIndexValue'),val,t);
		}
	}
	removeElementsChildren($('errMsg'));
	document.body.style.cursor = 'default';
}

function addDocIndexValue() {
	document.body.style.cursor = 'wait';
	setMessage('Adding Document Index Definition');
	var dList = $('docList');
	var docTypeID = dList.options[dList.selectedIndex].docTypeID;

	var dList = $('docIndex');
	var indexField = dList.options[dList.selectedIndex].value;

	var newDocVal = $('newDocValue').value;	
	if(newDocVal) {
		var xmlArr = {	"include" : "documents/documents.php",
						"function" : "addNewDocDefinition",
						"document_type_id" : docTypeID,
						"document_type_field" : indexField, 
						"definition" : newDocVal };
		postXML(xmlArr);
	} else {
		setMessage("Index value cannot be blank");
	}
}

function setNewDocIndexValue(XML) {
	err = XML.getElementsByTagName('ERROR');
	if(err.length == 0) {
		newDef = XML.getElementsByTagName('DEFINITION_ID');
		if(newDef.length > 0) {
			var val = newDef[0].firstChild.nodeValue;
			var t = $('newDocValue').value;
			createOptElement($('docIndexValue'),val,t);
			createOptElement($('rmDocIndexValue'),val,t);
		}
		$('newDocValue').select();	
	}

	mess = XML.getElementsByTagName('MESSAGE');
	if(mess.length > 0) {
		setMessage(mess[0].firstChild.nodeValue);
	}
	document.body.style.cursor = 'default';
}

function selectIndexValue() {
	var dList = $('docIndexValue');
	var indexDef = dList.options[dList.selectedIndex].text;

	$('docValue').value = indexDef;
	$('docValue').select();
	
	removeDefault(dList);
}

function editDocIndexValue() {
	document.body.style.cursor = 'wait';
	setMessage('Editing Document Index Definition');
	var dList = $('docIndexValue');
	var indexDefID = dList.options[dList.selectedIndex].value;

	var dList = $('docList');
	var docTypeID = dList.options[dList.selectedIndex].docTypeID;

	var dList = $('docIndex');
	var indexField = dList.options[dList.selectedIndex].value;

	var newDocVal = $('docValue').value;	
	if(newDocVal) {
		var xmlArr = {	"include" : "documents/documents.php",
						"function" : "editDocTypeDefinition",
						"definition_id" : indexDefID,
						"document_type_id" : docTypeID,
						"document_type_field" : indexField,
						"definition" : newDocVal };
		postXML(xmlArr);
	} else {
		setMessage("Index value cannot be blank");
	}
}

function setDocIndexValue(XML) {
	err = XML.getElementsByTagName('ERROR');
	if(!err) {
		var newDocVal = $('docValue').value;	

		var dList = $('docIndexValue');
		dList.options[dList.selectedIndex].text = newDocVal;
		var indexDefVal = dList.options[dList.selectedIndex].value;

		var dList = $('rmDocIndexValue').options;
		for(i=0;i<dList.length;i++) {
			if(dList[i].value == indexDefVal) {
				dList[i].text = newDocVal;
			}
		}
	}

	mess = XML.getElementsByTagName('MESSAGE');
	if(mess.length > 0) {
		setMessage(mess[0].firstChild.nodeValue);
	}
	document.body.style.cursor = 'default';
}

function deleteDocIndexValue() {
	document.body.style.cursor = 'wait';
	setMessage('Communicating with server...attempting to remove document index definition');
	var dList = $('rmDocIndexValue');
	var indexDefID = dList.options[dList.selectedIndex].value;

	var xmlArr = {	"include" : "documents/documents.php",
					"function" : "deleteDocTypeDefinition",
					"definition_id" : indexDefID };
	postXML(xmlArr);
}

function removeDocIndexValue(XML) {
	var dList = $('rmDocIndexValue');
	var ind = dList.selectedIndex;
	dList.remove(ind);	
	
	var dList = $('docIndexValue');
	dList.remove(ind);	

	mess = XML.getElementsByTagName('MESSAGE');
	if(mess.length > 0) {
		setMessage(mess[0].firstChild.nodeValue);
	}
	document.body.style.cursor = 'default';
}

function createOptElement(parentEl,val,text) {
	var opt = document.createElement('option');
	opt.value = val;
	opt.appendChild(document.createTextNode(text));
	parentEl.appendChild(opt);
}

function onEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;

	if(charCode == 13) {
		$('saveBtn').click();  
		return false;
	}
}

function mOver() {
	if(this.divname != tabEl.divname) {
		this.style.backgroundColor = "#517ca3";
		this.style.color = "white";
		this.className = 'addIndexTableShow';
	}
}

function mOut() {
	if(this.divname != tabEl.divname) {
		this.style.backgroundColor = "white";
		this.style.color = "black";
		this.className = 'addIndexTableFade';
	}
}

function unlockBackground() {
	if(tabEl) {
		tabEl.style.backgroundColor = "white";
		tabEl.style.color = "black";
		tabEl.style.cursor = 'pointer';
		tabEl.className = 'addIndexTableFade';
		tabEl.onclick = lockBackground;
		toggleElements(tabEl,true);
	}
	tabEl = "";
}

function lockBackground() {
	unlockBackground();

	tabEl = this;
	tabEl.style.cursor = 'default';
	tabEl.onclick = function() {};
	toggleElements(tabEl,false);

	if(tabEl.divname == "div1") {
		$('newDocValue').onkeypress = onEnter;
		$('saveBtn').onclick = addDocIndexValue;
	} else if(tabEl.divname == "div2") {
		$('docValue').onkeypress = onEnter;
		$('saveBtn').onclick = editDocIndexValue;
	} else {
		$('saveBtn').onclick = deleteDocIndexValue;
	}
}

function enableBehavior() {
	el = $('table1');
	el.style.cursor = 'pointer';
	el.onmouseover = mOver;
	el.onmouseout = mOut;
	el.onclick = lockBackground; 

	el = $('table2');
	el.style.cursor = 'pointer';
	el.onmouseover = mOver;
	el.onmouseout = mOut;
	el.onclick = lockBackground; 

	el = $('table3');
	el.style.cursor = 'pointer';
	el.onmouseover = mOver;
	el.onmouseout = mOut;
	el.onclick = lockBackground; 
}

function disableBehavior() {
	el = $('table1');
	el.style.cursor = 'default';
	el.onmouseover = function() {};
	el.onmouseout = function() {};
	el.onclick = function() {}; 

	el = $('table2');
	el.style.cursor = 'default';
	el.onmouseover = function() {};
	el.onmouseout = function() {};
	el.onclick = function() {}; 

	el = $('table3');
	el.style.cursor = 'default';
	el.onmouseover = function() {};
	el.onmouseout = function() {};
	el.onclick = function() {}; 
}

function resetFields() {
	$('newDocValue').value = "";
	$('docValue').value = "";

	removeElementsChildren($('docIndexValue'));
	removeElementsChildren($('rmDocIndexValue'));
}

function toggleElements(el,t) {
	inputList = el.getElementsByTagName('input');
	if(inputList.length > 0) {
		for(i=0;i<inputList.length;i++) {
			if(inputList[i].type == "text") {
				inputList[i].disabled = t;
			}
		}
	}

	selectList = el.getElementsByTagName('select');
	if(selectList.length > 0) {
		for(i=0;i<selectList.length;i++) {
			selectList[i].disabled = t;
		}
		if(!t) {
			selectList[0].focus();
		}
	} else {
		if(!t) {
			inputList[0].select();
		}
	}
}
