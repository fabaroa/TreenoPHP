if (window.XMLHttpRequest)
	var xmlhttp = new XMLHttpRequest();
else if (window.ActiveXObject)
	var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

function showRow() {
	this.style.backgroundColor = "#6a78af";	
	this.style.cursor = 'pointer';
}

function hideRow() {
	if( this.id != isFolderSelected ) {
		if(this.id != isTabSelected) {
			this.style.backgroundColor = "#ffffff";	
		}
	}
}

function selectFolderRow(id) {
	if( isFolderSelected != "" ) {
		getEl(isFolderSelected).style.backgroundColor = "#ffffff";
	}

	if(this.id && this.id != 'undefined') {
		isFolderSelected = this.id;
	} else {
		isFolderSelected = id;
	}
	getEl(isFolderSelected).style.backgroundColor = "#6a78af";
	selectFolder(isFolderSelected);	
}

function selectTabRow() {
	if( isTabSelected != "" ) {
		getEl(isTabSelected).style.backgroundColor = "#ffffff";
	}

	isTabSelected = this.id;
	getEl(this.id).style.backgroundColor = "#6a78af";
	selectTab( this.id );	
}

function addFolder() {
	var cab = getEl('cabSelect').value;
	if(origDocumentView) {
		parent.viewFileActions.window.location = '../energie/addFolder.php?'
					 + 'cab='+cab+'&tab_id='+tabID+'&original='+cabinet+'&table='+temp_table+'&doc_id='+folderID+'&parent=movefiles';
	} else {
		parent.mainFrame.window.location = '../energie/addFolder.php?'
					 + 'cab='+cab+'&table='+temp_table+'&original='+cabinet+'&doc_id='+folderID+'&parent=movefiles';
	}
}
									   
function selectCabinet( searchValue )
{
	var postStr = "";
	if( searchValue ) {
		var postStr = "searchValue="+escape(searchValue.value);
	}
	
	var cabSelect = getEl('cabSelect').value;
	if( cabSelect != "inbox" && cabSelect != "personal" )
	{
		var eMsg = getEl('errMsg');
		clearDiv(eMsg);
		eMsg.appendChild(document.createTextNode('Searching...'));

		var URL = '../lib/movefileActions.php?cab='+cabSelect+'&action=folderlist&'+deleted;
		if (window.XMLHttpRequest)
			var xmlhttp = new XMLHttpRequest();
		else if (window.ActiveXObject)
			var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

		xmlhttp.open('POST', URL, true);
		xmlhttp.setRequestHeader('Content-Type',
									  'application/x-www-form-urlencoded');
		xmlhttp.send(postStr);
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
			//alert(xmlhttp.responseText);
				if(xmlhttp.responseXML) {
					var XML = xmlhttp.responseXML;
					if( getEl('folderList') ) {
						var selectBox = getEl('folderList');
						while(selectBox.childNodes[0]) {
							selectBox.removeChild( selectBox.childNodes[0] );
						}
						isFolderSelected = "";
						isTabSelected = "";
					}
					var root = XML.getElementsByTagName('CABINET');
					documentView = parseInt(root[0].getAttribute('docView'));
					var folderlist = XML.getElementsByTagName('FOLDER');
					var headerlist = XML.getElementsByTagName('INDICE');
					if(headerlist) {
						var headerArr = new Array();
						for(var i=0;i<headerlist.length;i++) {
							headerArr[i] = headerlist[i].firstChild.nodeValue;
						}
					}
					
					if( folderlist.length > 0 ) {
						for(var i=0;i<folderlist.length;i++) {
							var row = selectBox.insertRow(selectBox.rows.length);
							row.id = folderlist[i].getAttribute('doc_id');
							row.onmouseover = showRow; 
							row.onmouseout = hideRow; 
							row.onclick = selectFolderRow;

							if(divType == 'deletefiles') {	
								var col = row.insertCell(row.cells.length);
								col.style.width = '10px';
								var checkBox = document.createElement("input");
								checkBox.type = "checkbox";
								checkBox.name = "foldersToDelete[]";
								checkBox.value = folderlist[i].getAttribute('doc_id');
								col.appendChild(checkBox);
							}	
							indiceArr = folderlist[i].getElementsByTagName('FIELD');
							for(var j=0;j<headerArr.length;j++) {
								var col = row.insertCell(row.cells.length);
								if(indiceArr[j].firstChild) {
									var val = indiceArr[j].firstChild.nodeValue;
								} else {
									var val = "";
								}
								col.appendChild( document.createTextNode(val) );
							}
						}
						var tableDiv = getEl('tableDiv');
						if( selectBox.offsetHeight < tableDiv.offsetHeight ) {
							tableDiv.style.height = '150px';
						} else {
							tableDiv.style.height = '300px';
						}

					} else {
						var row = selectBox.insertRow(0);
						var col = row.insertCell(0);
						col.appendChild( document.createTextNode('No folders in cabinet') );
					}
					getEl('tabDisplay').style.display = 'none';
					getEl('documentDisplay').style.display = 'none';
					getEl('buttonsDisplay').style.display = 'none';
					getEl('folderDisplay').style.display = 'block';
					clearDiv(eMsg);
					
					var message = XML.getElementsByTagName('MESSAGE');
					if(message.length > 0) {
						clearDiv(eMsg);
						eMsg.appendChild(document.createTextNode(message[0].firstChild.nodeValue));	
					} 

					if(!searchValue) {
						if(cabSelect == cabinet && folderID) {
							if(getEl(folderID)) {
								selectFolderRow(folderID);
							}
						} else if(cabSelect == moveCabinet) {
							if(getEl(moveFolder)) {
								selectFolderRow(moveFolder);
							}
							moveCabinet = "";
							moveFolder = "";
						}
					}
				}
			}
		};
	} else {
		getEl('folderDisplay').style.display = 'none';
		getEl('tabDisplay').style.display = 'none';
		getEl('documentDisplay').style.display = 'none';
		getEl('buttonsDisplay').style.display = 'block';
	}
}

function selectFolder(folderSelected) {
//	folderID = folderSelected;
	var cab = getEl('cabSelect').value;
	var URL = '../lib/movefileActions.php?cab='+cab+'&doc_id='
			+folderSelected;
	if(documentView) {
			URL += '&action=doclist';
	} else {
			URL += '&action=tablist';
	}

	if (window.XMLHttpRequest)
		var xmlhttp = new XMLHttpRequest();
	else if (window.ActiveXObject)
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

	xmlhttp.open('GET', URL,true);
	xmlhttp.setRequestHeader('Content-Type',
							  'application/x-www-form-urlencoded');
	xmlhttp.send(null);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			//alert(xmlhttp.responseText);
			if( xmlhttp.responseXML ) {
				var XML = xmlhttp.responseXML;
				var tablist = XML.getElementsByTagName('TAB');
				var doclist = XML.getElementsByTagName('DOCUMENT');

				if( tablist.length > 0 ) {
					if(tlist = getEl('tabList')) {
						while(tlist.childNodes[0]) {
							tlist.removeChild( tlist.childNodes[0] );
						}
					}

					for(var i=0;i<tablist.length;i++) {
						if(divType == 'movefiles') {
							var sOption = document.createElement( "option" );
							sOption.value = tablist[i].firstChild.nodeValue;
							sOption.appendChild( document.createTextNode(tablist[i].firstChild.nodeValue) );
							tlist.appendChild( sOption );
						} else {
							var row = tlist.insertRow(tlist.rows.length);
							row.onmouseover = showRow; 
							row.onmouseout = hideRow; 
							row.onclick = selectTabRow;
							row.id = tablist[i].getAttribute('name');
							var col = row.insertCell(row.cells.length);
							col.style.width = '10px';
							var checkBox = document.createElement("input");
							checkBox.type = "checkbox";
							checkBox.name = "filesToDelete[]";
							checkBox.value = tablist[i].firstChild.nodeValue;
							
							parentCheck(checkBox,folderSelected);
							col.appendChild(checkBox);
							var col = row.insertCell(row.cells.length);
							col.appendChild(document.createTextNode(tablist[i].firstChild.nodeValue));
						}
					}
					getEl('buttonsDisplay').style.display = 'block';
					getEl('documentDisplay').style.display = 'none';
					getEl('tabDisplay').style.display = 'block';
				} else {
					if(docSelDiv = getEl('docSelDiv2')) {
						while(docSelDiv.childNodes[0]) {
							docSelDiv.removeChild( docSelDiv.childNodes[0] );
						}
					}
					var docTypeList = XML.getElementsByTagName('DOCTYPE');
					if(docTypeList.length > 0) {
						dtlist = document.createElement('select');
						dtlist.id = 'docSel';
						dtlist.name = 'docSel';
						dtlist.onchange = selectDocument;

						var opt = document.createElement('option');
						opt.value = "__default";
						opt.key = "__default";
						opt.appendChild(document.createTextNode('Choose One'));
						dtlist.appendChild(opt);
						for(i=0;i<docTypeList.length;i++) {
							var dt = docTypeList[i].firstChild.nodeValue;
							var name = docTypeList[i].getAttribute('name');
							var opt = document.createElement('option');
							opt.value = name;
							opt.key = dt;
							opt.appendChild(document.createTextNode(dt));

							dtlist.appendChild(opt);
						}
						docSelDiv.appendChild(dtlist);
					} else {
						getEl('docuemtDiv').display = 'block';		
					}

					if(docDiv = getEl('documentDiv')) {
						while(docDiv.childNodes[0]) {
							docDiv.removeChild( docDiv.childNodes[0] );
						}
					}
					if( doclist.length > 0 ) {
						dlist = document.createElement('select');
						dlist.id = 'existingDocs';
						dlist.name = 'existingDocs';
						for(var i=0;i<doclist.length;i++) {
							var docName = doclist[i].getAttribute('name');
							var docVal = "";
							if(doclist[i].firstChild) {
								docVal = doclist[i].firstChild.nodeValue; 
							}

							var opt = document.createElement('option');
							opt.value = docName;
							opt.appendChild(document.createTextNode(docVal));
							dlist.appendChild(opt);
						}
						docDiv.appendChild(dlist);
					} else {
						var sp = document.createElement('span');
						sp.appendChild(document.createTextNode('No documents in folder'));
						docDiv.appendChild(sp);
					}

					getEl('buttonsDisplay').style.display = 'block';
					getEl('documentDisplay').style.display = 'block';
					getEl('tabDisplay').style.display = 'none';
					toggleAddDocument(true);
				}
			}
		}
	};
}

function parentCheck(element,checkValue) {
	var check = document.getElementsByTagName('input');
	for(var z=0;z<check.length;z++){
		if(check[z].type == "checkbox" && 
			check[z].value == checkValue && check[z].checked == true){
			element.checked = true;
		}
	}
}

function moveFiles( action ) {
	var check = false;
	var cab = getEl('cabSelect').value;
	var postStr = "destCab="+cab;
	if( cab != "inbox" && cab != "personal" ) {
		var doc_id = isFolderSelected;
		if(documentView) {
			var tab = getEl('existingDocs').value;
		} else {
			var tab = getEl('tabList').value;
		}
		postStr += "&destDoc_id="+doc_id+"&destTab="+escape(tab);
	}

	var inputTag = parent.sideFrame.document.getElementsByTagName('input');
	for(var i=0;i<inputTag.length;i++) {
		if( inputTag[i].type == 'checkbox' ){
			if( inputTag[i].checked == true ) {
				check = true;	
				if( postStr != "" )
					postStr += "&"
				postStr += "check[]="+inputTag[i].value;
			}
		}
	}

	if( check ) {
		var eMsg = getEl('errMsg');
		clearDiv(eMsg);
		eMsg.appendChild(document.createTextNode('Please Wait...'));

		var URL = '../lib/movefileActions.php?cab='+cabinet+'&doc_id='+folderID+'&action=selectedFiles&'+deleted;
		if (window.XMLHttpRequest)
			var xmlhttp = new XMLHttpRequest();
		else if (window.ActiveXObject)
			var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

		xmlhttp.open('POST', URL, true);
		xmlhttp.setRequestHeader('Content-Type',
								 'application/x-www-form-urlencoded');
		xmlhttp.send(postStr+"&moveType="+action);
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
				if( action == "cut" || ( cabinet == cab && folderID == doc_id ) ) {
					if(origDocumentView) {
						parent.sideFrame.window.location = '../documents/viewDocuments.php?cab='+cabinet
								+ '&doc_id='+folderID+'&tab_id='+tabID;
					} else {
						parent.sideFrame.window.location = '../energie/allthumbs.php?cab='+cabinet
								+ '&doc_id='+folderID+'&temp_table='+temp_table+'&index='+index;
					}
				}

				clearDiv(eMsg);
				eMsg.appendChild(document.createTextNode('Files moved successfully'));
			}
		};
	} else {
		var eMsg = getEl('errMsg');
		clearDiv(eMsg);
		eMsg.appendChild(document.createTextNode('No Files Have Been Cheecked'));
	}
}
/*
function allowDigi(e) {
	if (window.event)
		code = window.event.keyCode;
	else if (e.which)
		code = e.which;
	if( code ) {
		var pool = "1234567890 ";
		pool += "abcdefghijklmnopqrstuvwxyz";
		pool += "ABCDEFGHIJKLMNOPQRSTUVWXYZ-:,.";
		var character = String.fromCharCode(code);

		if( code == 13 || code == 3) {
			selectCabinet(getEl('folderSearch'));
			return true;
		}

		if( (pool.indexOf(character) != -1) 
				|| (code == 8) || (code == 9) || (code == 46) ) 
			return true;
		else
			return false;
	}
}
*/

function allowDigi(e) {
	if (window.event)
		code = window.event.keyCode;
	else if (e.which)
		code = e.which;
	if( code ) {
		if( code == 13 || code == 3) {
			selectCabinet(getEl('folderSearch'));
		}
		return true;
	}
}

function toggleAddDocument(force) {
	if(force || getEl('addDocOuterDiv').style.display == "block") {
		getEl('addDocOuterDiv').style.display = "none";
		getEl('buttonsDisplay').style.display = 'block';
	} else {
		getEl('addDocOuterDiv').style.display = "block";
		getEl('buttonsDisplay').style.display = 'none';
	}
	var docDiv = getEl('addDocumentDiv');
	clearDiv(docDiv);
	var docSel = getEl('docSel');
}

function cancelAddDoc() {
	toggleAddDocument();
}

function selectDocument() {
	removeDefault(getEl('docSel'));
	docType = getEl('docSel').value;
	var URL = '../lib/movefileActions.php?docType='+docType+'&action=getDocInfo';
	if (window.XMLHttpRequest)
		var xmlhttp = new XMLHttpRequest();
	else if (window.ActiveXObject)
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

	xmlhttp.open('GET', URL, true);
	xmlhttp.setRequestHeader('Content-Type',
							 'application/x-www-form-urlencoded');
	xmlhttp.send(null);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			XML = xmlhttp.responseXML;
			var doclist = XML.getElementsByTagName('DOCFIELD');
			if(doclist.length > 0) {
				var docDiv = getEl('addDocumentDiv');
				docDiv.cabinet = getEl('cabSelect').value;
				docDiv.docID = isFolderSelected;

				clearDiv(docDiv);
				var tbl = document.createElement('table');
				tbl.className = 'inputTable';
				docDiv.appendChild(tbl);
				for(i=0;i<doclist.length;i++) {
					var name = doclist[i].firstChild.nodeValue;
					var docField = doclist[i].getAttribute('name');

					row = tbl.insertRow(tbl.rows.length);
					col = row.insertCell(row.cells.length);
					col.className = 'label';

					var lbl = document.createElement('label');	
					lbl.appendChild(document.createTextNode(name));
					col.appendChild(lbl);

					col = row.insertCell(row.cells.length);

					docDefs = doclist[i].getElementsByTagName('DEFINITION');
					if(docDefs.length > 0) {
						var selBox = document.createElement('select');
						selBox.id = 'field'+i;

						var opt = document.createElement('option');
						opt.value = "";
						opt.key = docField;
						opt.appendChild(document.createTextNode(""));

						selBox.appendChild(opt);
						for(j=0;j<docDefs.length;j++) {
							var val = docDefs[j].firstChild.nodeValue;
							var opt = document.createElement('option');
							opt.value = val;
							opt.key = docField;
							opt.appendChild(document.createTextNode(val));

							selBox.appendChild(opt);
						}
						col.appendChild(selBox);
					} else {
						var txt = document.createElement('input');
						txt.id = 'field'+i;
						txt.type = 'text';
						txt.className = "docField";
						txt.key = docField;
						col.appendChild(txt);
					}
				}
			}
		}
	};
}

function addNewDoc() {
  	var addDocDiv = getEl('addDocumentDiv');
	var domDoc = createDOMDoc();
	var root = domDoc.createElement('root');
	domDoc.appendChild(root);
	var entry = domDoc.createElement('ENTRY');
	var key = domDoc.createElement('KEY');
	key.appendChild(domDoc.createTextNode('function'));
	var value = domDoc.createElement('VALUE');
	value.appendChild(domDoc.createTextNode('xmlMoveFilesAddDocumentToCabinet'));
	entry.appendChild(key);
	entry.appendChild(value);
	root.appendChild(entry);
	
	entry = domDoc.createElement('ENTRY');
	key = domDoc.createElement('KEY');
	key.appendChild(domDoc.createTextNode('document_table_name'));
	value = domDoc.createElement('VALUE');
	value.appendChild(domDoc.createTextNode(getEl('docSel').value));
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
	var isEmpty = true;
	var numFields = 0;
	var fieldStr = "";

	while(el = getEl('field'+numFields)) {
		if(el.type == "text") {
			if(el.value != "") {
				isEmpty = false;
			}
			var k = el.key;
			var v = el.value;
		} else {
			if(el.options[el.selectedIndex].value != "") {
				isEmpty = false;
			}
			var k = el.options[el.selectedIndex].key;	
			var v = el.options[el.selectedIndex].value;	
		}
		entry = domDoc.createElement('ENTRY');
		key = domDoc.createElement('KEY');
		key.appendChild(domDoc.createTextNode('key' + numFields));
		value = domDoc.createElement('VALUE');
		value.appendChild(domDoc.createTextNode(k));
		entry.appendChild(key);
		entry.appendChild(value);
		root.appendChild(entry);
		
		entry = domDoc.createElement('ENTRY');
		key = domDoc.createElement('KEY');
		key.appendChild(domDoc.createTextNode('field' + numFields));
		value = domDoc.createElement('VALUE');
		value.appendChild(domDoc.createTextNode(v));
		entry.appendChild(key);
		entry.appendChild(value);
		root.appendChild(entry);
		fieldStr += ' '+v;
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
	if(!isEmpty) {	
		if (window.XMLHttpRequest)
			var xmlhttp = new XMLHttpRequest();
		else if (window.ActiveXObject)
			var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

		xmlhttp.open('POST', '../documents/documentPostRequest.php',true);
		xmlhttp.setRequestHeader('Content-Type',
									  'application/x-www-form-urlencoded');
		xmlhttp.send(domToString(domDoc));
		xmlhttp.onreadystatechange = function () {
			if(xmlhttp.readyState == 4) {
				var XML = xmlhttp.responseXML;

				var log = XML.getElementsByTagName('LOGOUT');
				if(log.length > 0) {
					top.window.location = '../logout.php';
				}

				var tabInfo = XML.getElementsByTagName('TAB');
				if(tabInfo.length > 0) {
					var tabName = tabInfo[0].firstChild.nodeValue;
				}
				var dSel = getEl('docSel');
				var dt = dSel.options[dSel.selectedIndex].key;

				if(!getEl('existingDocs')) {
					if(docDiv = getEl('documentDiv')) {
						while(docDiv.childNodes[0]) {
							docDiv.removeChild( docDiv.childNodes[0] );
						}
					}

					dlist = document.createElement('select');
					dlist.id = 'existingDocs';
					dlist.name = 'existingDocs';

					docDiv.appendChild(dlist);
				}
				var opt = document.createElement('option');
				opt.value = tabName;
				opt.appendChild(document.createTextNode(dt+':'+fieldStr));
				getEl('existingDocs').appendChild(opt);

				getEl('existingDocs').options[getEl('existingDocs').options.length -1].selected = true;
				//var xmlDoc = p.responseXML;
				//var tabName = xmlDoc.documentElement.firstChild.nodeValue;
				toggleAddDocument();
			}
		};
	} else {
//		$('addDocErr').firstChild.nodeValue = 'Please Enter In Field Values.';
	}
}
