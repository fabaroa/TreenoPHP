function initialize() {
	var behaviors = {
		'#submit'	: function (element) {
			element.onclick = (autoComp) ? getAutoComplete : indexBatch;
		},
		'#delete'	: function (element) {
			element.onclick = confirmDelete;
		},
		'#skip'	: function (element) {
			element.onclick = loadInitData;
		}
	};
	Behaviour.register(behaviors);
	Behaviour.apply();
}

function loadInitData() {
	var xmlArr = {	"include" : "secure/indexingFuncs.php",
					"function" : "xmlGetIndexingType" };
	postXML(xmlArr);
}

function loadIndexingType(XML) {
	$('addNewDocumentDiv').style.display = 'none';
	$('cabFieldsDiv').className = 'cabFieldsDivFocus';
	removeElementsChildren($('addDocumentDiv'));

	var rt = XML.getElementsByTagName('ROOT');	
	var fds = XML.getElementsByTagName('FIELD');
	var wfs = XML.getElementsByTagName('WORKFLOW');
	var tabs = XML.getElementsByTagName('TAB');
	var docs = XML.getElementsByTagName('DOCUMENT');

	printMessage('');
	if(fds.length > 0) {
		autoComp = parseInt(rt[0].getAttribute('auto_complete'));
		scroll = parseInt(rt[0].getAttribute('scroll'));
		dateFuncs = parseInt(rt[0].getAttribute('date_functions'));
		totalPages = parseInt(rt[0].getAttribute('pages'));
		var file = rt[0].getAttribute('viewing');
		var dateIndexed = rt[0].getAttribute('date_indexed');
		var quickView = rt[0].getAttribute('quickView');

		if(quickView == "0") {
			$('quickView').checked = false;
		}
		
		removeElementsChildren($('viewingFile'));
		$('viewingFile').appendChild(document.createTextNode(file));
		addPaging(totalPages);

		var count = rt[0].getAttribute('count');
		removeElementsChildren($('foldersLeft'));
		$('foldersLeft').appendChild(document.createTextNode(count));

		removeElementsChildren($('cabFieldsTable'));
		var URL = "viewIndexFile.php?page=1";
		$('indexFile').src = URL; 
	
		var tbl = $('cabFieldsTable');
		var headerRow = tbl.insertRow(tbl.rows.length);
		var bodyRow = tbl.insertRow(tbl.rows.length);
		bodyRow.id = 'body';
		for(i=0;i<fds.length;i++) {
			var dispName = fds[i].firstChild.nodeValue;
			var realName = fds[i].getAttribute('name');
			indArr[indArr.length] = realName;

			var col1 = headerRow.insertCell(headerRow.cells.length);
			col1.style.width = "200px";
			if(i >= 1 && autoComp) {
				var level = 50;
				col1.style.opacity = level/100;
				col1.style.filter = 'alpha(opacity='+level+')';
			}
			var sp = document.createElement('span');
			sp.appendChild(document.createTextNode(dispName));
			col1.appendChild(sp);	

			var col2 = bodyRow.insertCell(bodyRow.cells.length);
			col2.style.width = "200px";
			if(i >= 1 && autoComp) {
				var level = 50;
				col2.style.opacity = level/100;
				col2.style.filter = 'alpha(opacity='+level+')';
			}

			dt = rt[0].getAttribute(realName);
			if(realName != "date_indexed" && dt) {
				addDataTypeDef(col2,realName,dt);
			} else {
				var tBox = document.createElement('input');
				tBox.type = "text"; 
				tBox.id = "field-"+realName; 
				tBox.name = realName; 

				if(realName == 'date_indexed') {
					tBox.value = dateIndexed;
				}

				if(autoComp) {
					if(i) {
						tBox.disabled = true;
					} else {
						tBox.onkeydown = autoCompOnEnter;
					}
				} else {
					tBox.onkeydown = indexOnEnter;
				}
				col2.appendChild(tBox);	

				if(dateFuncs && (realName.search(/date/i) != -1 || realName.search(/DOB/i) != -1)) {
					tBox.validate = validateDate;

					var img = document.createElement('img');
					img.id = 'date-'+realName;
					img.src = '../images/edit_16.gif';
					img.style.cursor = 'pointer';
					img.style.verticalAlign = 'middle';
					img.input = tBox;
					if(!autoComp) {
						img.onclick = dispCurrMonthIndex;
					}
					col1.appendChild(img);	
				}
			}
		}
		addWorkflowList(headerRow,bodyRow,wfs);
		$('cabFieldsDiv').style.display = 'block';

		removeElementsChildren($('viewingFile'));
		removeElementsChildren($('tabSelect'));
		removeElementsChildren($('tabSpan'));

		$('tabSelect').style.display = 'none';

/* Removed, because we only index to the Main tab in the new product
		var docView = parseInt(rt[0].getAttribute('docView'));
		var spMsg = "";
		if(docView) {
			if(docs.length > 0) {
				var opt = document.createElement('option');
				opt.value = '__default'; 
				opt.appendChild(document.createTextNode('Choose One'));
				$('tabSelect').appendChild(opt);
				for(var i=0;i<docs.length;i++) {
					var doc = docs[i].firstChild.nodeValue;
					var opt = document.createElement('option');
					opt.value = docs[i].getAttribute('name'); 
					opt.appendChild(document.createTextNode(doc));
					$('tabSelect').appendChild(opt);
				}
			}
			$('tabSelect').onchange = selectDocument;
			spMsg = "Choose a Document";
		} else {
			if(tabs.length > 0) {
				for(var i=0;i<tabs.length;i++) {
					var tab = tabs[i].firstChild.nodeValue;
					var opt = document.createElement('option');
					opt.value = tab; 
					opt.appendChild(document.createTextNode(tab));
					$('tabSelect').appendChild(opt);
				}
			}
			$('tabSelect').onchange = function() {};

			if(!$('newTabButton')) {
				var bt = document.createElement('input');
				bt.id = 'newTabButton';
				bt.type = 'button';
				bt.name = "New";
				bt.value = "New"
				bt.onclick = selectNewTab;
				$('tabDiv').appendChild(bt);
			}
			spMsg = "Choose a Subfolder";
			$('submit').disabled = false;
		}
		$('tabSelect').docView = docView;
		$('tabSpan').appendChild(document.createTextNode(spMsg));
*/
		initialize();
		
		// Only the 'Main' subfolder is supported in new GUI for indexing
		var opt = document.createElement('option');
		opt.value = "Main"; 
		opt.selected = true;
		$('tabSelect').appendChild(opt);

	} else {
		window.location = "indexing.php?mess=No Files to Index";
	}
}

function selectNewTab() {
	$('submit').disabled = true;
	var addDocumentDiv = $('addDocumentDiv');
	removeElementsChildren(addDocumentDiv);
	var tableEl = document.createElement('table');
	tableEl.id = 'newTabTable';
	tableEl.className = 'inputTable';
	addDocumentDiv.appendChild(tableEl);

	var tr = tableEl.insertRow(tableEl.rows.length);
	var el = tr.insertCell(tr.cells.length);
	el.className = 'label';
	var lbl = document.createElement('label');
	el.appendChild(lbl);
	lbl.appendChild(document.createTextNode('Subfolder'));

	var el = tr.insertCell(tr.cells.length);
	var txt = document.createElement('input');
	txt.type = 'text';
	txt.id = 'newTab';
	el.appendChild(txt);

	var bt = document.createElement('input');
	bt.type = 'button';
	bt.name = "Add";
	bt.value = "Add"
	bt.onclick = checkSubfolder;
	el.appendChild(bt);

	var bt = document.createElement('input');
	bt.type = 'button';
	bt.name = "Cancel";
	bt.value = "Cancel"
	bt.onclick = cancelNewSubfolder;
	el.appendChild(bt);

	removeElementsChildren($('indexSpan'));
	$('indexSpan').appendChild(document.createTextNode('New Subfolder'));

	$('cabFieldsDiv').className = 'cabFieldsDivUnFocus';
	$('addNewDocumentDiv').style.display = 'block';
}

function checkSubfolder() {
	var newTab = $('newTab').value;
	var xmlArr = {	"include" : "secure/indexingFuncs.php",
					"function" : "xmlCheckSubfolder",
					"subfolder" : newTab};
	postXML(xmlArr);
}

function addSubFolder(XML) {
	var tabCheck = XML.getElementsByTagName('SUCCESS');
	var mess = XML.getElementsByTagName('MESSAGE');
	if(tabCheck[0].firstChild.nodeValue == '0') {
		if($('tabErrorMsg')) {
			removeElementsChildren($('tabErrorMsg'));
			$('tabErrorMsg').appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
		} else {
			var tbl = $('newTabTable');
			var row = tbl.insertRow(tbl.rows.length);
			var col = row.insertCell(row.cells.length);
			col.colSpan = 2;
			col.style.textAlign = 'center';
			var sp = document.createElement('span');
			sp.className = 'error';
			sp.id = 'tabErrorMsg';
			sp.appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
			col.appendChild(sp);
		}
	} else {
		var newTab = XML.getElementsByTagName('TAB');
		newTab = newTab[0].firstChild.nodeValue;

		var opt = document.createElement('option');
		opt.value = newTab;
		opt.selected = true;
		opt.appendChild(document.createTextNode(newTab));
		$('tabSelect').appendChild(opt);

		$('cabFieldsDiv').className = 'cabFieldsDivFocus';
		$('addNewDocumentDiv').style.display = 'none';
		$('submit').disabled = false;
	}
}

function cancelNewSubfolder() {
	$('cabFieldsDiv').className = 'cabFieldsDivFocus';
	$('addNewDocumentDiv').style.display = 'none';
	$('submit').disabled = false;
}

function selectDocument() {
	removeDefault($('tabSelect'));
	$('cabFieldsDiv').className = 'cabFieldsDivUnFocus';
	$('addNewDocumentDiv').style.display = 'block';
	$('submit').disabled = false;
	var docName = $('tabSelect').value;
	var xmlArr = {	"include" : "documents/documents.php",
					"function" : "getDocumentFields",
					"noCallBackFunction" : 1,
					"document_table_name" : docName};
	postXML(xmlArr);
}

function setDocument(XML) {
	var docs = XML.getElementsByTagName('FIELD');
	if(docs.length > 0) {
		var addDocumentDiv = $('addDocumentDiv');
		removeElementsChildren(addDocumentDiv);
		removeElementsChildren($('indexSpan'));
		$('indexSpan').appendChild(document.createTextNode('Index Document'));


		var tableEl = document.createElement('table');
		tableEl.className = 'inputTable';
		addDocumentDiv.appendChild(tableEl);

		var tr;
		var el;
		var txt, lbl;
		for(var i=0;i<docs.length;i++) {
			var name = docs[i].firstChild.nodeValue; 
			var key = docs[i].getAttribute('name'); 

			tr = tableEl.insertRow(tableEl.rows.length);
			el = tr.insertCell(tr.cells.length);
			el.className = 'label';
			lbl = document.createElement('label');
			el.appendChild(lbl);
			lbl.appendChild(document.createTextNode(name));

			el = tr.insertCell(tr.cells.length);
			var defsList = docs[i].getElementsByTagName('DEFINITION'); 
			if(defsList.length > 0) {
				var selBox = document.createElement('select');
				selBox.key = key;
				selBox.className = 'docField';
				
				var opt = document.createElement('option');
				opt.value = ""; 
				opt.appendChild(document.createTextNode(""));
				selBox.appendChild(opt);
				
				for(j=0;j<defsList.length;j++) {
					var def = defsList[j].firstChild.nodeValue;
					var opt = document.createElement('option');
					opt.value = def;
					opt.appendChild(document.createTextNode(def));
					selBox.appendChild(opt);
				}
				el.appendChild(selBox);
			} else {
				txt = document.createElement('input');
				txt.type = 'text';
				txt.key = key;
				txt.className = 'docField';
				txt.onkeydown = indexOnEnter;
				el.appendChild(txt);
			}

			if((i+1) == docs.length) {
				tr = tableEl.insertRow(tableEl.rows.length);
				el = tr.insertCell(tr.cells.length);
				el.colSpan = 2;
				el.style.textAlign = 'center';

				var bt = document.createElement('input');
				bt.type = 'button';
				bt.name = "Cancel";
				bt.value = "Cancel"
				bt.onclick = cancelDocument;
				el.appendChild(bt);
			}
		}
	}
}

function cancelDocument() {
	$('cabFieldsDiv').className = 'cabFieldsDivFocus';
	$('addNewDocumentDiv').style.display = 'none';
	$('submit').disabled = true;
	addDefault($('tabSelect'));
}
		
function addPaging(totalPages) {
	if(scroll && totalPages > 1) {
		$('pageDiv').style.visibility = 'visible';
		$('pageNum').value = 1;
		$('pageNum').disabled = false;
		$('pageNum').onkeydown = pageOnEnter;
		removeElementsChildren($('totalPages'));
		$('totalPages').appendChild(document.createTextNode(totalPages));

		$('first').onclick = function () {loadPage(1)};
		$('back').onclick = function () {loadPage(1)};
		$('next').onclick = function () {loadPage(2)};
		$('last').onclick = function () {loadPage(totalPages)};
	} else {
		$('pageDiv').style.visibility = 'hidden';
		$('pageNum').disabled = true;
	}
}

function addDataTypeDef(col,realName,dt) {
	dtlist = dt.split(",,,");
	var sBox = document.createElement('select');
	sBox.id = "field-"+realName;
	if(autoComp) {
		sBox.disabled = true;
	}
	var opt = document.createElement('option');
	opt.value = "__default";
	opt.appendChild(document.createTextNode(''));
	sBox.appendChild(opt);
	for(j=0;j<dtlist.length;j++) {
		var opt = document.createElement('option');
		opt.value = dtlist[j];
		opt.appendChild(document.createTextNode(dtlist[j]));
		
		sBox.appendChild(opt);
	}
	col.appendChild(sBox);
}

function addWorkflowList(headerRow,bodyRow,wfs) {
	if(wfs.length > 0) {
		var col = headerRow.insertCell(headerRow.cells.length);
		col.style.width = "200px";
		if(autoComp) {
			var level = 50;
			col.style.opacity = level/100;
			col.style.filter = 'alpha(opacity='+level+')';
		}
		var sp = document.createElement('span');
		sp.appendChild(document.createTextNode('Workflow'));
		col.appendChild(sp);	

		var col = bodyRow.insertCell(bodyRow.cells.length);
		if(autoComp) {
			var level = 50;
			col.style.opacity = level/100;
			col.style.filter = 'alpha(opacity='+level+')';
		}
		var sBox = document.createElement('select');
		sBox.id = "workflow";
		if(autoComp) {
			sBox.disabled = true;
		}
		var opt = document.createElement('option');
		opt.value = "__default";
		opt.appendChild(document.createTextNode(''));
		sBox.appendChild(opt);
		
		for(k=0;k<wfs.length;k++) {
			var opt = document.createElement('option');
			opt.value = wfs[k].firstChild.nodeValue;
			opt.appendChild(document.createTextNode(wfs[k].firstChild.nodeValue));
			sBox.appendChild(opt);
		}
		col.appendChild(sBox);
	}
}

function loadPage(p) {
	p = parseInt(p);
	var page;
	if(!isNaN(p)) {
		if(p < 1) {
			page = 1;
		} else if(p > totalPages) {
			page = totalPages;
		} else {
			page = p;
		}
	} else {
		page = 1
	}

	var xmlArr = {	"include" : "secure/indexingFuncs.php",
					"function" : "xmlGetIndexingFilename",
					"page" : page };
	postXML(xmlArr);
	
	var URL = "viewIndexFile.php?page="+page;
	$('indexFile').src = URL; 
	$('pageNum').value = page;

	$('next').onclick = function () {loadPage((page+1))};
	$('back').onclick = function () {loadPage((page-1))};
}

function confirmDelete() {
	var level = 30;
	$('outerDiv').style.opacity = level/100;
	$('outerDiv').style.filter = 'alpha(opacity='+level+')';
	$('confirmDelete').style.display = 'block';
	$('confirmDelete').style.zIndex = 100;
	$('submit').disabled = true;
	$('delete').disabled = true;
	$('skip').disabled = true;

	$('yesDelete').onclick = function () { 	var xmlArr = {	"include" : "secure/indexingFuncs.php",
															"function" : "xmlDeleteIndexingBatch" };
											postXML(xmlArr);
											clearIndexValues();
											noDelete();	
										}
	$('noDelete').onclick = noDelete;
}

function noDelete() {
	var level = 100;
	$('outerDiv').style.opacity = level/100;
	$('outerDiv').style.filter = 'alpha(opacity='+level+')';
	$('confirmDelete').style.display = "none";	

	$('yesDelete').onclick = function () {};
	$('noDelete').onclick = function () {};

	$('submit').disabled = false;
	$('delete').disabled = false;
	$('skip').disabled = false;
}

function pageOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		loadPage($('pageNum').value);
	}
}

function indexOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		indexBatch();
	}
}

function autoCompOnEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13 || charCode == 3 || charCode == 9 || (charCode == 37) || (charCode == 39)) {
		getAutoComplete();
	}
	return true;
}

function getAutoComplete() {
	printMessage('Searching....please wait');
	if(el = $('field-'+indArr[0])) {
		var val = (el.value) ? el.value : ""; 
		var xmlArr = {	"include" : "secure/indexingFuncs.php",
						"function" : "xmlGetAutoComplete",
						"key" : indArr[0],
						"value" : val };
		postXML(xmlArr);
	}
}

function setAutoComplete(XML) {
	var fds = XML.getElementsByTagName('FIELD');
	var tbl = $('cabFieldsTable');
	if(fds.length >	0) {
		for(var i=0;i<fds.length;i++) {
			var fVal = "";
			if(fds[i].firstChild) {
				fVal = fds[i].firstChild.nodeValue;
			}
			var fName = fds[i].getAttribute('name');

			if(el = $('field-'+fName)) {
				if(i) {
					el.value = fVal;
					if(dateFuncs && (fName.search(/date/i) != -1 || fName.search(/DOB/i) != -1)) {
						if($('date-'+fName)) {
							$('date-'+fName).onclick = dispCurrMonthIndex;	
						}
					}
				}
			}

			el.disabled = false;

			var level = 100;
			tbl.rows[0].cells[i].style.opacity = level/100;
			tbl.rows[0].cells[i].style.filter = 'alpha(opacity='+level+')';
			tbl.rows[1].cells[i].style.opacity = level/100;
			tbl.rows[1].cells[i].style.filter = 'alpha(opacity='+level+')';
		}
		var level = 100;
		var ln = tbl.rows[0].cells.length - 1;
		tbl.rows[0].cells[ln].style.opacity = level/100;
		tbl.rows[0].cells[ln].style.filter = 'alpha(opacity='+level+')';
		tbl.rows[1].cells[ln].style.opacity = level/100;
		tbl.rows[1].cells[ln].style.filter = 'alpha(opacity='+level+')';
		if($('workflow')) {
			$('workflow').disabled = false;
		}
	}
	$('submit').onclick = indexBatch;
	for(var i = 1; input = $('field-'+indArr[i]); i++) {
		if(input.select) {
			input.select();
			break;
		}
	}

	printMessage('Search complete');
}

function clearIndexValues() {
	for(var i=0;i<indArr.length;i++) {
		indArr[i].value = "";
	}
}

function setIndexingFilename(XML) {
	var name = XML.getElementsByTagName('NAME');
	file = name[0].firstChild.nodeValue;
	
	removeElementsChildren($('viewingFile'));
	$('viewingFile').appendChild(document.createTextNode(file));
}

function adjustHeight() {
	var clienth = document.documentElement.clientHeight;
	$('fileContainer').style.height = (clienth-150)+'px';
	$('confirmDelete').style.top = (clienth-145)+'px';
	$('outerDiv').style.top = (clienth-145)+'px';
	$('cabFieldsDiv').style.top = (clienth-115)+'px';
	$('actionDiv').style.top = (clienth-35)+'px';
}

function indexBatch() {
	var xmlArr = new Object();
	xmlArr["include"] = "secure/indexingFuncs.php";
	xmlArr["function"] = "xmlIndexBatch";

	$('tabSelect').value = "Main";
/*
	if($('tabSelect').docView) {
		if($('tabSelect').value != '__default') {
			xmlArr["docType"] = $('tabSelect').value;
			fieldList = document.getElementsByClassName('docField');
			if(fieldList.length > 0) {
				for(var i=0;i<fieldList.length;i++) {
					xmlArr["docKey"+i] = fieldList[i].key;
					xmlArr["docVal"+i] = fieldList[i].value;
				}
			}
		} else {
			printMessage('Please select a Document');	
			return;
		}
	} else {
		xmlArr['tab'] = $('tabSelect').value;
	}
*/
	
	if(wf = $('workflow')) {
		if(wf.value != '__default') {
			xmlArr["workflow"] = wf.value;
		}
	}
	
	for(i=0;i<indArr.length;i++) {
		var val = "";
		if(fd = $('field-'+indArr[i])) {
			if(fd.value != "__default") {
				val = fd.value;
			}
			var realName = indArr[i];
			if(dateFuncs && (realName.search(/date/i) != -1 || realName.search(/DOB/i) != -1)) {
				if(fd = $('field-'+realName)) {
					if(!fd.validate()) {
						printMessage(fd.msg);
						fd.select();
						return;
					}
				}
			}
		}
		xmlArr[indArr[i]] = val;
	}
	postXML(xmlArr);
}

function printMessage(mess) {
	removeElementsChildren($('messDiv'));

	var sp = document.createElement('span');
	sp.appendChild(document.createTextNode(mess));
	$('messDiv').appendChild(sp);
}

function dispCurrMonthIndex() {
	var inputBox = this.input;
	if(currShowing[inputBox.id]) {
		if (currShowing[inputBox.id].shim) {
			document.body.removeChild (currShowing[inputBox.id].shim);
		}
		document.body.removeChild(currShowing[inputBox.id]);
		currShowing[inputBox.id] = null;
	} else {
		var currDate = new Date();
		var newDiv = document.createElement('div');
		newDiv.style.visibility = 'hidden';
		new Calendar(currDate.getMonth(), currDate.getFullYear(), newDiv, inputBox);
		document.body.appendChild(newDiv);
		newDiv.style.position = 'absolute';
		newDiv.style.zIndex = 100;
		var tmpVal = 0;
		var el = inputBox;
		while (el) {
			tmpVal += el.offsetLeft;
			el = el.offsetParent;
		}
		tmpVal += inputBox.offsetWidth - 30;
		newDiv.style.left = tmpVal + 'px';
		if(newDiv.offsetLeft < 0) {
			newDiv.style.left = '0px';
		}
		newDiv.style.bottom = '10px';
		var iframe = document.createElement ('iframe');
		iframe.style.display = 'none';
		iframe.style.left = '0px';
		iframe.style.position = 'absolute';
		iframe.style.bottom = '10px';
		iframe.src = 'javascript:false;';
		iframe.frameborder = '0';
		iframe.style.border = '0px';
		iframe.scrolling = 'no';
		document.body.appendChild(iframe);
		iframe.style.top = newDiv.style.top;
		iframe.style.left = newDiv.style.left;
		iframe.style.width = newDiv.offsetWidth + 'px';
		iframe.style.height = newDiv.offsetHeight + 'px';
		iframe.style.zIndex = newDiv.style.zIndex - 1;
		newDiv.style.visibility = 'visible';
		iframe.style.display = 'block';
		newDiv.shim = iframe;
		currShowing[inputBox.id] = newDiv;
	}
}

function setFirstPage() {
	var showPage = 0;
	if($('quickView').checked) {
		showPage = 1;
	}
	var xmlArr = {	"include" : "secure/indexingFuncs.php",
					"function" : "xmlSetFirstPage",
					"quickView" : showPage };
	postXML(xmlArr);
}
