var myrules = {
	'ul.regular li' : function(el) {
				if(el.className != "empty") {
					el.onmouseover = function() {
						el.className = 'mouseover';
					}
					
					el.onmouseout = function() {
						el.className = 'mouseout';
					}
					
					el.onclick = function() {
						var id = el.id.replace('id-','');
						viewFile(id,this);
					}
				}
			},
			
	'ul.reorder li' : function(el) {
				el.onmouseover = function() {
					el.className = 'mouseover';
				}
				
				el.onmouseout = function() {
					el.className = 'mouseout';
				}
				
				el.onclick = function() {}
			},
			
	'td.no input'	: function(el) {
				el.onclick = function() {
					boolSelect = false;
					idInfo = el.id.split("-");
					var num = idInfo[1];
					selectCheck(num);
				}
			},

	'fieldset.classical'	: function(el) {
				el.style.borderStyle = 'none';
				el.style.height = '0px';
				el.parentNode.style.height = '35px';
				getEl('fileView-'+el.id).style.display = 'none';
			}				
};

function openToolBar() {
	getEl('toolBarActions').style.display = 'block';	
}

function closeToolBar() {
	getEl('toolBarActions').style.display = 'none';	
}

function addHighLight(el) {
	el.style.fontWeight	= 'bold';
	el.style.backgroundColor = '#888888';
}

function remHighLight(el,color) {
	el.style.fontWeight = 'normal';
	el.style.backgroundColor = color;
}

function selectDocument(el) {
	if(!el) {
		el = this;
	}
	if(boolSelect) {
		var cab = el.getAttribute('cab');
		var doc_id = el.getAttribute('doc_id');
		var tab_id = el.getAttribute('file_id');
		var document_id = el.getAttribute('document_id');
		var URL = '../documents/viewDocuments.php?cab='+cab+'&doc_id='
				+doc_id+'&tab_id='+tab_id;
		top.sideFrame.window.location = URL;
		parent.document.getElementById('mainFrameSet').setAttribute('cols', '*,215');
		parent.document.getElementById('rightFrame').setAttribute('rows', '*,0');
		parent.document.getElementById('fileFrame').scrolling="no";
		getEl('document-'+document_id).style.backgroundColor = "#91AEC9";	
		if(prevDoc) {
			getEl('document-'+prevDoc).style.backgroundColor = "#ebebeb";
		}
		prevDoc = document_id;
	}
}	

function mOverTR(el) {
	if('document-'+prevDoc != el.id) {
		el.style.backgroundColor = '#888888';
	}
}

function mOutTR(el) {
	if('document-'+prevDoc != el.id) {
		el.style.backgroundColor = '#ebebeb';
	}
}

function changePage(type,total) {
	var pageNum = getEl('pageNum');
	var newPage = getEl('newPage');
	var page = parseInt(pageNum.value);
	if(type == "FIRST") {
		page = 1;
	} else if(type == "PREV") {
		if((page-1) < 1) {
			page = 1;
		} else {
			page--;
		}
	} else if(type == "NEXT") {
		if((page+1) > total) {
			page = total;
		} else {
			page++;
		}
	} else if(type == "LAST") {
		page = total;
	} else {
		//this occurs when they type a number in the text box
		if(newPage.value < 1) {
			page = 1;
		} else if(parseInt(newPage.value) > parseInt(total)) {
			page = total;
		} else {
			page = newPage.value;
		}
	}

	newPage.value = page;
	pageNum.value = page;
	getPageResults(page);
}

function getPageResults(page) {
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);
	
	total = parseInt(perPage);
	page = parseInt(page);

	var t = total * numCols;
	var p = t * (page - 1);
	createKeyAndValue(xmlDoc,root,'function','xmlGetPageResults');
	createKeyAndValue(xmlDoc,root,'page',p);
	createKeyAndValue(xmlDoc,root,'total',t);
	if(curField) {
		createKeyAndValue(xmlDoc,root,'sortField',curField);
		createKeyAndValue(xmlDoc,root,'sortDir',dir);
	}
	postXML(domToString(xmlDoc));
}

function loadPage(XML) {
	var docList = XML.getElementsByTagName('DOCUMENT');
	if(docList.length) {
		clearTableForResults();
		for(var i=0;i<docList.length;i++) {
			var document_id = docList[i].getAttribute('document_id');
			var cab = docList[i].getAttribute('cab');
			var arbCab = docList[i].getAttribute('arb_cab');
			var doc_id = docList[i].getAttribute('doc_id');
			var file_id = docList[i].getAttribute('file_id');
			var fieldList = docList[i].getElementsByTagName('FIELD');

			var docTable = getEl('docTable');
			var row = docTable.insertRow(docTable.rows.length);
			row.id = "document-"+document_id;
			row.style.cursor = 'pointer';
			row.onmouseover = function () {mOverTR(this)};
			row.onmouseout = function () {mOutTR(this)};
			
			var col = row.insertCell(row.cells.length);
			col.style.textAlign = 'center';
			col.style.width = '25px';

			var chkBox = document.createElement('input');
			chkBox.type = 'checkbox';
			chkBox.id = "documentCheck:"+(i+1);
			chkBox.value = document_id;
			chkBox.setAttribute('cab',cab);
			chkBox.setAttribute('doc_id',doc_id);
			chkBox.setAttribute('file_id',file_id);
			col.appendChild(chkBox);
			
			var col = row.insertCell(row.cells.length);
			col.setAttribute('document_id',document_id);
			col.setAttribute('cab',cab);
			col.setAttribute('doc_id',doc_id);
			col.setAttribute('file_id',file_id);
			col.onclick = selectDocument;
			col.appendChild(document.createTextNode(arbCab));
			if(fieldList.length) {
				for(var j=0;j<fieldList.length;j++) {
					var val = "";
					if(fieldList[j].firstChild) {
						val = fieldList[j].firstChild.nodeValue;
					}
					var col = row.insertCell(row.cells.length);
					col.id = fieldList[j].getAttribute('field_id');
					col.setAttribute('document_id',document_id);
					col.setAttribute('cab',cab);
					col.setAttribute('doc_id',doc_id);
					col.setAttribute('file_id',file_id);
					col.onclick = selectDocument;

					var sp = document.createElement('span');
					sp.appendChild(document.createTextNode(val));
					col.appendChild(sp);
				}
			}
		}
	}
}

function clearTableForResults(rowNum) {
	getEl('docCheck').checked = false;
	var docTable = getEl('docTable');
	if(rowNum) {
		docTable.deleteRow(rowNum);
	} else {
		while(docTable.rows.length > 1) {
			docTable.deleteRow(1);
		}
	}
}

function submitPage(e,total) {
	var evt = (e) ? e : event;
	var code = (evt.keyCode) ? evt.keyCode : evt.charCode;
	var pool = "1234567890";
	if(code == 13) {
		changePage('',total);
		return true;
	}

	var character = String.fromCharCode(code);
	if(pool.indexOf(character) != -1
		|| (code == 8) || (code == 37)
		|| (code == 39) || (code == 46)){
		return true;
	}
	return false;
}

function selectAll() {
	var toggle = getEl('docCheck').checked;
	var ct = 1;
	while(el = getEl('documentCheck:'+ct)) {
		el.checked = toggle;
		ct++;
	}
}

function openEditDocument(id) {
	toggleCheckboxes(true);
	boolSelect = false;
	documentID = id;
	var docRow = getEl('document-'+id).cells;	
	for(var j=2;j<docRow.length;j++) {
		while(el = docRow[j].childNodes[0]) {
			if(el.nodeName == "SPAN") {
				var val = (el.childNodes[0]) ? el.childNodes[0].nodeValue : "";
			}
			docRow[j].removeChild(el);
		}

		var txtBox = document.createElement('input');
		txtBox.type = 'text';
		txtBox.id = 'field'+j+'-'+id;
		txtBox.name = 'field'+j;
		txtBox.value = val;
		txtBox.style.width = "150px";
		txtBox.style.height = '15px';
		txtBox.style.fontSize = '9pt';
		txtBox.onkeypress = onEnter;
		docValArr[j] = val;
		docRow[j].appendChild(txtBox);
		if(j == 2) {
			txtBox.focus();
			txtBox.select();
		}

		getEl('cancelBtn').onclick = function () {cancelEditDocument()};
		getEl('saveBtn').onclick = function () {saveEditDocument()};
		getEl('actionDiv').style.display = 'block';
	}
	adjustDocumentTable();
}

function cancelEditDocument() {
	var docRow = getEl('document-'+documentID).cells;	
	for(var j=2;j<docRow.length;j++) {
		while(el = docRow[j].childNodes[0]) {
			docRow[j].removeChild(el);
		}
		
		var val = docValArr[j];
		var sp = document.createElement('span');
		sp.appendChild(document.createTextNode(val));
		docRow[j].appendChild(sp);
	}
	docValArr = new Array();
	adjustDocumentTable();
	getEl('actionDiv').style.display = 'none';
	documentID = '';
	boolSelect = true;
	toggleCheckboxes(false);
}

function toggleCheckboxes(toggle) {
	var ct = 1;
	getEl('docCheck').disabled = toggle;
	while(el = getEl('documentCheck:'+ct)) {
		el.disabled = toggle;
		ct++;
	}
}

function saveEditDocument() {
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);

	createKeyAndValue(xmlDoc,root,'function','xmlUpdateDocumentInfo');
	createKeyAndValue(xmlDoc,root,'docType',docType);
	createKeyAndValue(xmlDoc,root,'documentID',documentID);

	var docRow = getEl('document-'+documentID).cells;	
	var i = 1;
	for(var j=2;j<docRow.length;j++) {
		var fieldID = docRow[j].id.replace("field:","");
		var val = getEl('field'+j+'-'+documentID).value;	
		createKeyAndValue(xmlDoc,root,'id'+i,fieldID);
		createKeyAndValue(xmlDoc,root,'field-'+fieldID,val);
		docValArr[j] = val;
		i++;
	}
	postXML(domToString(xmlDoc));
	cancelEditDocument();
}

function adjustDocumentTable() {
	var w = getEl('docTable').offsetWidth;
	getEl('docTypeDiv').style.width = w+'px';	
	getEl('docResDiv').style.width = w+'px';	
}

function onEnter(e) {
	var evt = (e) ? e : event;
	var code = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(code == 27) {
		cancelEditDocument();
	} else if(code == 13) {
		saveEditDocument();
	}
	return true;
}

function showRelatedDocuments(el) {
	var cab 	= el.getAttribute('cab');
	var doc_id 	= el.getAttribute('doc_id');

	var page = (getEl('pageNum')) ? getEl('pageNum').value : 1;
	window.location = '../energie/searchResults.php?cab='+cab+'&doc_id='+doc_id+'&allthumbs=1';
	var backButton = parent.topMenuFrame.getEl('up');
	parent.topMenuFrame.addOnClick('documentSearch','','',page);
	backButton.style.display = 'block';
}
