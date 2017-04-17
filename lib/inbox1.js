function toggleDelegateDiv() {
	var actionDiv = getEl('addNewActionDiv');
	if(actionDiv.style.display == 'block') {
		actionDiv.style.display = 'none';

		getEl('status').options[0].selected = true; 
		getEl('comments').value = "";

		getEl('delegated_user').disabled = true;
		getEl('status').disabled = true;
		getEl('comments').disabled = true;
		getEl('btnCnl').disabled = true;
		getEl('btnDelegate').disabled = true;
	} else {
		actionDiv.style.display = 'block';
		actionDiv.style.position = 'absolute';
		actionDiv.style.left = '100px';
		actionDiv.style.top = '100px';
		actionDiv.style.width = '300px';
		actionDiv.style.height = 'auto';
		getEl('delegated_user').disabled = false;
		getEl('status').disabled = false;
		getEl('comments').disabled = false;
		getEl('btnCnl').disabled = false;
		getEl('btnDelegate').disabled = false;
		ADD_DHTML("addNewActionDiv"+MAXOFFLEFT+0+MAXOFFTOP+0+MAXOFFBOTTOM+300+MAXOFFRIGHT+400);
	}
}

function showAddDocument(cabinet, docID, selValue, fields,initVal,docTypeDefs) {
  	var addDocumentDiv = $('addDocumentDiv');
	clearDiv(addDocumentDiv);
	var tableEl = document.createElement('table');
	tableEl.className = 'inputTable';
	addDocumentDiv.cabinet = cabinet;
	addDocumentDiv.docID = docID;
	addDocumentDiv.docType = selValue;
	addDocumentDiv.appendChild(tableEl);
	var tr;
	var el;
	var txt, lbl;
	var i = 0;
	for(var key in fields) {
		tr = tableEl.insertRow(tableEl.rows.length);

		el = tr.insertCell(tr.cells.length);
		el.className = 'label';
		lbl = document.createElement('label');
		el.appendChild(lbl);
		lbl.appendChild(document.createTextNode(fields[key]));

		el = tr.insertCell(tr.cells.length);
		if(docTypeDefs[key]) {
			var selBox = document.createElement('select');
			
			var opt = document.createElement('option');
			opt.value = ""; 
			opt.key = key;
			opt.appendChild(document.createTextNode(""));
			selBox.appendChild(opt);
			
			defsList = docTypeDefs[key];
			for(j=0;j<defsList.length;j++) {
				var def = defsList[j];
				var opt = document.createElement('option');
				opt.value = def;
				opt.key = key;
				opt.appendChild(document.createTextNode(def));
				selBox.appendChild(opt);
			}
			el.appendChild(selBox);
		} else {
			txt = document.createElement('input');
			txt.type = 'text';
			txt.key = key;
			if(i == 0) {
				txt.value = initVal;
			}
			txt.onkeypress = chkForEnter;
			el.appendChild(txt);
		}
		i++;
	}
  	$('addNewDocumentDiv').style.display = 'block';
	$('addNewDocumentDiv').style.position = 'absolute';
	$('addNewDocumentDiv').style.left = '100px';
	$('addNewDocumentDiv').style.top = '100px';
	$('addNewDocumentDiv').style.width = '300px';
	$('addNewDocumentDiv').style.height = 'auto';
	ADD_DHTML("addNewDocumentDiv"+MAXOFFLEFT+0+MAXOFFTOP+0+MAXOFFBOTTOM+300+MAXOFFRIGHT+400);
	var myInputs = addDocumentDiv.getElementsByTagName('input');
	for(var i = 0; i < myInputs.length; i++) {
		if(myInputs[i].type == 'text') {
			myInputs[i].focus();
			myInputs[i].select();
			break;
		}
	}
  }

  function addNewDoc() {
  	var addDocDiv = $('addDocumentDiv');
	var domDoc = createDOMDoc();
	var root = domDoc.createElement('root');
	domDoc.appendChild(root);
	var entry = domDoc.createElement('ENTRY');
	var key = domDoc.createElement('KEY');
	key.appendChild(domDoc.createTextNode('function'));
	var value = domDoc.createElement('VALUE');
	value.appendChild(domDoc.createTextNode('xmlInboxAddDocumentToCabinet'));
	entry.appendChild(key);
	entry.appendChild(value);
	root.appendChild(entry);
	
	entry = domDoc.createElement('ENTRY');
	key = domDoc.createElement('KEY');
	key.appendChild(domDoc.createTextNode('document_table_name'));
	value = domDoc.createElement('VALUE');
	value.appendChild(domDoc.createTextNode(addDocDiv.docType));
	entry.appendChild(key);
	entry.appendChild(value);
	root.appendChild(entry);
	
	entry = domDoc.createElement('ENTRY');
	key = domDoc.createElement('KEY');
	key.appendChild(domDoc.createTextNode('cabinet'));
	value = domDoc.createElement('VALUE');
	value.appendChild(domDoc.createTextNode(addDocDiv.cabinet));
	entry.appendChild(key);
	entry.appendChild(value);
	root.appendChild(entry);

	entry = domDoc.createElement('ENTRY');
	key = domDoc.createElement('KEY');
	key.appendChild(domDoc.createTextNode('doc_id'));
	value = domDoc.createElement('VALUE');
	value.appendChild(domDoc.createTextNode(addDocDiv.docID));
	entry.appendChild(key);
	entry.appendChild(value);
	root.appendChild(entry);
	var numFields = 0;
	var fields = new Array();
	var inputs = addDocDiv.getElementsByTagName('input');
	for(var i = 0; i < inputs.length; i++) {
		if(inputs[i].type == 'text') {
			entry = domDoc.createElement('ENTRY');
			key = domDoc.createElement('KEY');
			key.appendChild(domDoc.createTextNode('key' + numFields));
			value = domDoc.createElement('VALUE');
			value.appendChild(domDoc.createTextNode(inputs[i].key));
			entry.appendChild(key);
			entry.appendChild(value);
			root.appendChild(entry);
			
			entry = domDoc.createElement('ENTRY');
			key = domDoc.createElement('KEY');
			key.appendChild(domDoc.createTextNode('field' + numFields));
			value = domDoc.createElement('VALUE');
			value.appendChild(domDoc.createTextNode(inputs[i].value));
			entry.appendChild(key);
			entry.appendChild(value);
			root.appendChild(entry);
			fields[numFields] = inputs[i].value;	
			numFields++;
		}
	}

	var inputs = addDocDiv.getElementsByTagName('select');
	for(var i = 0; i < inputs.length; i++) {
		var selb = inputs[i];

		var k = selb.options[selb.selectedIndex].key;	
		entry = domDoc.createElement('ENTRY');
		key = domDoc.createElement('KEY');
		key.appendChild(domDoc.createTextNode('key' + numFields));
		value = domDoc.createElement('VALUE');
		value.appendChild(domDoc.createTextNode(k));
		entry.appendChild(key);
		entry.appendChild(value);
		root.appendChild(entry);
		
		var v = selb.options[selb.selectedIndex].value;	
		entry = domDoc.createElement('ENTRY');
		key = domDoc.createElement('KEY');
		key.appendChild(domDoc.createTextNode('field' + numFields));
		value = domDoc.createElement('VALUE');
		value.appendChild(domDoc.createTextNode(v));
		entry.appendChild(key);
		entry.appendChild(value);
		root.appendChild(entry);
		fields[numFields] = v;	
		numFields++;
	}

	entry = domDoc.createElement('ENTRY');
	key = domDoc.createElement('KEY');
	key.appendChild(domDoc.createTextNode('field_count'));
	value = domDoc.createElement('VALUE');
	value.appendChild(domDoc.createTextNode(numFields));
	entry.appendChild(key);
	entry.appendChild(value);
	root.appendChild(entry);

	var p = getXMLHTTP();
	p.open('POST', '../documents/documentPostRequest.php');
	p.send(domToString(domDoc));
	p.onreadystatechange = function () {
		if(p.readyState != 4) {
			return;
		}
		var xmlDoc = p.responseXML;

		var log = xmlDoc.getElementsByTagName('LOGOUT');
		if(log.length > 0) {
			top.window.location = '../logout.php';
		}
		var tabName = xmlDoc.documentElement.firstChild.nodeValue;
		parent.searchPanel.setNewTab(tabName, fields);
		//parent.searchPanel.document.getElementById('btnSelect').focus();
		$('addNewDocumentDiv').style.display = 'none';
		clearDiv($('addDocumentDiv'));
	}
  }

  function cancelAddDoc() {
  	parent.searchPanel.resetDocSel();
	$('addNewDocumentDiv').style.display = 'none';
  }
 function chkForEnter(evt) {
	evt = (evt) ? evt : event;
	var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

	if(charCode == 13) {
		$('addDocumentBtn').click();
		return false;
	}
	return true;
}

function checkShowNew () {
	var xmlhttp = getXMLHTTP();
	xmlhttp.open('POST', 'inboxMove.php?openNew=1', true);
	xmlhttp.setRequestHeader('Content-Type',
						'application/x-www-form-urlencoded');
	xmlhttp.send(null);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if(xmlhttp.responseText) {
			}
		}
	};
}
