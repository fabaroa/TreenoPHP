/*
   Previously defined variables:
   pageNum, URL, cabinet, tempTable, docID, folderValue, odbcValue
*/

function extFileMove () {
	if (!parent.mainFrame.someSelected()) {
		alert("Please Select Files or Folders to File into Cabinet");
		return;
	}
	var cab = extFileMove.arguments[0];
	var fieldNames = extFileMove.arguments[1];
	var fieldValues = extFileMove.arguments[2];
	var tabName;
	if (extFileMove.arguments.length >= 4) {
		tabName = extFileMove.arguments[3];
	} else {
		tabName = 'Main';
	}

	var docFieldNames = new Array();
	var docFieldValues = new Array();
	if (extFileMove.arguments.length == 6) {
		docFieldNames = extFileMove.arguments[4];
		docFieldValues = extFileMove.arguments[5];
	}

	xmlDoc = createDOMDoc ();
	var rootDoc = xmlDoc.createElement ('LEGACY');
	xmlDoc.appendChild (rootDoc);
	var cabDoc = xmlDoc.createElement ('CABINET');
	cabDoc.appendChild (xmlDoc.createTextNode (cab));
	rootDoc.appendChild (cabDoc);
	var tabDoc = xmlDoc.createElement ('TAB');
	tabDoc.appendChild (xmlDoc.createTextNode (tabName));
	rootDoc.appendChild (tabDoc);
	for (var i = 0;i < fieldNames.length; i++) {
		var index = xmlDoc.createElement ('INDEX');
		index.appendChild (xmlDoc.createTextNode( fieldValues[i]));
		index.setAttribute ('name',fieldNames[i]);
		rootDoc.appendChild (index);
	}

	for(var i = 0; i < docFieldNames.length; i++) {
		var docIndex = xmlDoc.createElement ('DOCINDEX');
		docIndex.appendChild (xmlDoc.createTextNode(docFieldValues[i]));
		docIndex.setAttribute ('name', docFieldNames[i]);
		rootDoc.appendChild (docIndex);
	}

	var postStr = domToString (xmlDoc);
	var xmlhttp = getXMLHTTP ();	
	var myURL = 'cabinetActions.php?legacyInt=1';
	xmlhttp.open ('POST',myURL,true);
	xmlhttp.setRequestHeader ('Content-Type',
			'application/x-www-form-urlencoded');
	xmlhttp.send (postStr);
	xmlhttp.onreadystatechange = function () { 
		if (xmlhttp.readyState == 4) {
			if (xmlhttp.responseXML) {
				var XML = xmlhttp.responseXML;
				var cabInfo = XML.getElementsByTagName ('CABINET');	
				if (cabInfo.length > 0) {
					var cab = cabInfo[0].getAttribute ('cab');
					var doc_id = cabInfo[0].getAttribute ('doc_id');
					var tab = cabInfo[0].getAttribute ('tab');
					var checkFiles = checkForSelected();
					var checkDelFiles = checkForSelected ('delegated');
					if ( checkFiles || checkDelFiles ) {
						if( checkFiles ) {
							var actionUrl = parent.mainFrame.window.document.filename.action;
							if (actionUrl.indexOf ('?') == -1) {
								actionUrl += '?';
							} else {
								actionUrl += '&';
							}
							actionUrl += 'move=1' + '&cab=' + cab + '&doc_id=' + doc_id
								+ '&tab=' + tab + '&inboxFiles=1';
							parent.mainFrame.window.document.filename.action = actionUrl;
						}

						if(parent.mainFrame.enableAllFilesInFolder && checkDelFiles) {
						parent.mainFrame.enableAllFilesInFolder();
						parent.mainFrame.window.document.filename.action += '?cab=' + cab + '&doc_id=' + doc_id
							+ '&tab=' + tab + '&moveInboxDelegation=1';

						}
						parent.mainFrame.window.document.filename.submit ();
					} else {
						var myDoc = parent.mainFrame.document;
						var errMsg = myDoc.getElementById ('errMsg');
						while (errMsg.hasChildNodes ()) {
							errMsg.removeChild (errMsg.firstChild);
						}
						var message = 'Please Select Files or Folders to ' +
							'File into Cabinet';
						var myTxt = myDoc.createTextNode (message);
						errMsg.appendChild (myTxt);
					}
				}
			}
		}
	};	
}

function folderSelect () {
	var indexKey = document.getFolder.folderID.selectedIndex;
	location = document.getFolder.folderID[indexKey].value;
}

function checkForSelected () {
	var i = 1;
	var el, checkBoxType;
	var anySelected = false;

	if(checkForSelected.arguments.length > 0) {	
		checkBoxType = checkForSelected.arguments[0];
		var frm = parent.mainFrame.window.document;
		var chkbox = frm.filename.getElementsByTagName('input');
		for(var i=0;i<chkbox.length;i++) {
			if(chkbox[i].type == 'checkbox' && chkbox[i].checked == true) {
				if(chkbox[i].name != 'selectdelfolder') {
					anySelected = true;
					break;
				}
			}
		}
	} else {
		checkBoxType = 'fileCheck:';
		while (el = parent.mainFrame.document.getElementById (checkBoxType + i)) {
			if (el.checked) {
				anySelected = true;
				break;
			}
			i++;
		}
	}
	return anySelected;
}

function allowDigits (evt) {
	if ((evt.keyCode >= 48 && evt.keyCode <= 57) || evt.keyCode == 13 ||
			evt.keyCode == 8 || (evt.keyCode == 37) || (evt.keyCode == 39)) {
		return true;
	} else {
		return false;
	}
}

function folderTypeCheck (evt) {
	if(evt.keyCode == Event.KEY_RETURN) {
		$('folderSubmit').click ();
	}
	return true;
//	return false;
}

function odbcTypeCheck (evt) {
	if(evt.keyCode == Event.KEY_RETURN) {
		$('odbcSubmit').click ();
	}
	return true;
//	return false;
}

function setNewTab(tabName, myFields) {
	var addDocSel = $('AddDocumentSel');
	var tabSel = $('tabSelect');
	if(!tabSel.options) {
		var tabSelParent = tabSel.parentNode;
		tabSelParent.removeChild(tabSel);
		tabSelSib = tabSel.nextSibling;
		tabSel = document.createElement('select');
		tabSel.id = 'tabSelect';
		tabSelParent.insertBefore(tabSel, tabSelSib);
		$('btnSelect').disabled = false;
	}
	var docType = addDocSel.options[addDocSel.selectedIndex].firstChild.nodeValue;
	var opt = document.createElement('option');
	opt.value = tabName;
	opt.appendChild(document.createTextNode(docType + ': ' + 
		myFields.join(' - ')));
	tabSel.insertBefore(opt, tabSel.options[0]);
	tabSel.options[0].selected = true;
	resetDocSel();
}

function resetDocSel() {
	addDefault($('AddDocumentSel'));
}
	
var behaviors = {
	'#cabSel' : function (element) {
		element.onchange = function () {
			var cabinet = this.options[this.selectedIndex].value;
			window.location = 'inboxSelect1.php?cab=' + cabinet;
		}
	},
	'#btnAdd' : function (element) {
		element.onclick = function () {
			var myDoc = parent.mainFrame.document;
			myDoc.getElementById ('addNewFolder').src = '../energie/'
				+ 'addFolder.php?cab=' + cabinet + '&parent=inbox&table=' +
				tempTable + '&doc_id=' + docID + '&search=' + folderValue +
				'&odbcSearch=' + odbcValue;
			myDoc.getElementById ('addNewFolderDiv').style.display = 'block';
		}
	},
	'#btnBack' : function (element) {
		element.onclick = function () {
			var newPage = pageNum - 1;
			window.location = URL + '&page=' + newPage;
		}
	},
	'#btnNext' : function (element) {
		element.onclick = function () {
			var newPage = pageNum + 1;
			window.location = URL + '&page=' + newPage;
		}
	},

	'#idpage' : function (element) {
		element.onkeypress = allowDigits.bindAsEventListener(this);
	},

	'#folderID' : function (element) {
		element.onchange = function () {
			var docID = this.options[this.selectedIndex].value;
			window.location = URL + '&doc_id=' + docID;
		}
	},

	'#btnSelect' : function (element) {
		element.onclick = function () {
			var myDoc = parent.mainFrame.window.document;
			var checkFiles = checkForSelected();
			var checkDelFiles = checkForSelected ('delegated');
			if ( checkFiles || checkDelFiles ) {
				var currTab = $('tabSelect').options[$('tabSelect').selectedIndex].value;
				var wf = '';
				if(el = $('workflowSelect')) {
					wf = el.options[el.selectedIndex].value;
				}
				if( checkFiles ) {
					var actionUrl = myDoc.filename.action;
					if (actionUrl.indexOf ('?') == -1) {
						actionUrl += '?';
					} else {
						actionUrl += '&';
					}
					actionUrl += 'move=1' + '&cab=' + cabinet + '&doc_id=' + docID
						+ '&tab=' + currTab + '&wf=' + wf + '&inboxFiles=1';
					myDoc.filename.action = actionUrl;
				}

				if(parent.mainFrame.enableAllFilesInFolder && checkDelFiles) {
					parent.mainFrame.enableAllFilesInFolder();
					myDoc.filename.action += '?cab=' + cabinet +
						'&doc_id=' + docID + '&tab=' + currTab + '&wf=' + wf;
					myDoc.filename.action += '&moveInboxDelegation=1';
				}
//alert("action: " + myDoc.filename.action);
				myDoc.filename.submit ();
			} else {
				var errMsg = myDoc.getElementById ('errMsg');
				while (errMsg.hasChildNodes ()) {
					errMsg.removeChild (errMsg.firstChild);
				}
				var txtStr = 'Please Select Files or Folders to File ' + 
					'into Cabinet';
				var myTxt = myDoc.createTextNode (txtStr);
				errMsg.appendChild (myTxt);
			}
		}
	},
	
	'#folderSearch' : function (element) {
		element.onkeypress = folderTypeCheck.bindAsEventListener (this);
	},
	'#folderSubmit' : function (element) {
		element.onclick = function () {
			$('searchType').value = 'folder';
			document.searchFolder.submit ();
		}
	},
	'#odbcSearch' : function (element) {
		element.onkeypress = odbcTypeCheck.bindAsEventListener (this);
	},
	'#odbcSubmit' : function (element) {
		element.onclick = function () {
			$('searchType').value = 'odbc';
			document.searchFolder.submit ();
		}
	},
	'#AddDocumentSel' : function (element) {
		element.onchange = function () {
			removeDefault(this);
			var selValue = this.value;
			var domDoc = createDOMDoc();
			var root = domDoc.createElement('root');
			domDoc.appendChild(root);
			var entry = domDoc.createElement('ENTRY');
			var key = domDoc.createElement('KEY');
			key.appendChild(domDoc.createTextNode('function'));
			var value = domDoc.createElement('VALUE');
			value.appendChild(domDoc.createTextNode('getDocumentFields'));
			entry.appendChild(key);
			entry.appendChild(value);
			root.appendChild(entry);
			
			entry = domDoc.createElement('ENTRY');
			key = domDoc.createElement('KEY');
			key.appendChild(domDoc.createTextNode('document_table_name'));
			value = domDoc.createElement('VALUE');
			value.appendChild(domDoc.createTextNode(selValue));
			entry.appendChild(key);
			entry.appendChild(value);
			root.appendChild(entry);
			
			entry = domDoc.createElement('ENTRY');
			key = domDoc.createElement('KEY');
			key.appendChild(domDoc.createTextNode('noCallBackFunction'));
			value = domDoc.createElement('VALUE');
			value.appendChild(domDoc.createTextNode('1'));
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

				var initVal = "";	
				var chkboxlist = parent.mainFrame.document.getElementsByTagName('input');
				for(var j=0;j<chkboxlist.length;j++) {
					if(chkboxlist[j].type == 'checkbox' && chkboxlist[j].name != "selectfolder" && chkboxlist[j].checked) {
						initVal = chkboxlist[j].getAttribute("realName");
						break;
					}
				}

				var entries = xmlDoc.getElementsByTagName('FIELD');
				var fields = new Object();
				var docTypeDefs = new Object();
				for (var i = 0; i < entries.length; i++) {
					var n = entries[i].getAttribute('name');
					fields[n] = entries[i].firstChild.nodeValue;

					var defsList = entries[i].getElementsByTagName('DEFINITION');
					if(defsList.length > 0) {
						docTypeDefs[n] = new Array();	
						for(j=0;j<defsList.length;j++) {
							docTypeDefs[n][docTypeDefs[n].length] = defsList[j].firstChild.nodeValue;
						}
					}
				}
				parent.mainFrame.showAddDocument(cabinet, docID, selValue, fields, initVal,docTypeDefs);
			}
		}
	}
};

Behaviour.register(behaviors);
