function nextPage(pageNum,prev) {
	var npg = pageNum + 1;
	if(prev > 0) {
		getEl('prevPage').disabled = false;
	}
	hideDivs([getEl('page'+prev)]);	
	getEl('prevPage').onclick = function () {	prevPage(prev)};
	getEl('nextPage').onclick = function () {	documentController(npg)};
	
	//getEl('cancelWizard').onclick = function () {cancelWizard(pageNum)};
}

function prevPage(pageNum) {
	var npg = pageNum + 1;
	var ppg = pageNum - 1;
	if(ppg < 1) {
		getEl('prevPage').disabled = true;
		getEl('nextPage').disabled = false;
	}

	for(var i=5;i>pageNum;i--) {
		hideDivs([getEl('page'+i)]);	
	}
	showDivs([getEl('page'+pageNum)]);
	getEl('prevPage').onclick = function () {	prevPage(ppg)};
	getEl('nextPage').onclick = function () {	documentController(npg)};
	//getEl('nextPage').disabled = false;	
	resetPage(pageNum);
	//getEl('cancelWizard').disabled = false;	
	//getEl('cancelWizard').onclick = function () {cancelWizard(pageNum)};
}

function resetPage(page) {
	switch(page) {
		case 1: 
				getEl('documentName').value = '';
				getEl('indexName').value = '';
				getEl('addDocType').value = "Add";

				var text = 'Index List';
				clearDiv(getEl('leg'));
				getEl('leg').appendChild(document.createTextNode(text));
				clearDiv(getEl('currentIndices'));
				break;
		default:
				break;
	}
}

function cancelWizard() {
	for(i=1;i<5;i++) {
		resetPage(i);	
	}
	showDivs([getEl('page1')]);
	hideDivs([getEl('page2'),getEl('page3'),getEl('page4')]);	
	getEl('prevPage').disabled = true;
	getEl('nextPage').disabled = false;
	getEl('nextPage').onclick = function () {	documentController(2)};
}

function documentController(page) {
	switch(page) {
		case 2:
				if(getEl('rdEdit').checked) {
					var xmlDoc = createDOMDoc();
					var root = xmlDoc.createElement('ROOT');
					xmlDoc.appendChild(root);

					doc = getEl('editDoc').value;	
					if(doc) {
						createKeyAndValue(xmlDoc,root,'function','getDocumentInfo');
						createKeyAndValue(xmlDoc,root,'document_table_name',doc);
						postXML(domToString(xmlDoc));
						nextPage(page,page-1);
						showDivs([getEl('page'+page)]);	
						//getEl('nextPage').disabled = true;
					} else {
						clearDiv(getEl('errorMsg'));
						getEl('errorMsg').appendChild(document.createTextNode('Must select a document type'));
					}
				} else if(getEl('rdDisable').checked) {
					nextPage(3,1);		
					documentController(3);
					showDivs([getEl('page'+3)]);	
/*				} else if(getEl('rdDelete').checked) {
					doc = getEl('editDoc').options[getEl('editDoc').selectedIndex].firstChild.nodeValue;	
					var textStr = 'Deleting Document Type: '+doc+'...Are you sure?';
					getEl('confirmMessage').appendChild(document.createTextNode(textStr));
					nextPage(4,1);		
					showDivs([getEl('page'+4)]);	
*/				} else if(getEl('rdCopy').checked) {
					var xmlDoc = createDOMDoc();
					var root = xmlDoc.createElement('ROOT');
					xmlDoc.appendChild(root);

					createKeyAndValue(xmlDoc,root,'function','copyDocumentInfo');

					doc = getEl('editDoc').value;	
					createKeyAndValue(xmlDoc,root,'document_table_name',doc);
					postXML(domToString(xmlDoc));
					nextPage(page,page-1);
					showDivs([getEl('page'+page)]);	
					getEl('nextPage').disabled = true;
					$('documentName').select();
				} else {
					nextPage(page,page-1);		
					showDivs([getEl('page'+page)]);	
					getEl('nextPage').disabled = true;
					$('documentName').focus();

				}
				break;
		case 3:
				var listArr = document.getElementsByTagName('li');
				if(listArr.length > 0) {
					var xmlDoc = createDOMDoc();
					var root = xmlDoc.createElement('ROOT');
					xmlDoc.appendChild(root);
					createKeyAndValue(xmlDoc,root,'function','reorderDocumentFields');
					createKeyAndValue(xmlDoc,root,'document_table_name',docTableName);
					createKeyAndValue(xmlDoc,root,'field_count',listArr.length);
					for(var i=0;i<listArr.length;i++) {
						var val = listArr[i].childNodes[0].childNodes[2].firstChild.nodeValue;
						createKeyAndValue(xmlDoc,root,'f'+i,val);
					}
					postXML(domToString(xmlDoc));
				}

				if(el = getEl('editDoc')) {
					if(el.selectedIndex != -1) {
						doc = el.value;	
						var xmlDoc = createDOMDoc();
						var root = xmlDoc.createElement('ROOT');
						xmlDoc.appendChild(root);
						
						createKeyAndValue(xmlDoc,root,'function','getDocumentDisableInfo');
						createKeyAndValue(xmlDoc,root,'document_table_name',doc);
						postXML(domToString(xmlDoc));
					}
				}
				if(!getEl('rdDisable').checked)
				//if(listArr.length > 0) {
					nextPage(4,page-1);		
				//} else {
				//	nextPage(4,1);		
				//}
				showDivs([getEl('page'+page)]);	
				break;
		case 4:
				nextPage(page,page-1);
				break;
		case 5:
				if (getEl('editDoc')) {
					if(getEl('editDoc').value) {
						doc = getEl('editDoc').value;	
					} else {
						doc = docTableName;
					}	
				} else {
					doc = docTableName;
				}	
				var xmlDoc = createDOMDoc();
				var root = xmlDoc.createElement('ROOT');
				xmlDoc.appendChild(root);
				if(getEl('rdDisable').checked || getEl('rdEdit').checked || getEl('rdAdd').checked) {
					if(getEl('rdDisableDoc').checked) {
						var disable = 0;
					} else {
						var disable = 1;
					}
					createKeyAndValue(xmlDoc,root,'function','disableDocumentType');
					createKeyAndValue(xmlDoc,root,'document_table_name',doc);
					createKeyAndValue(xmlDoc,root,'disable',disable);
					hideDivs([getEl('page3')]);	
				} else {
					if(getEl('rdYesDeleteDoc').checked) {
						createKeyAndValue(xmlDoc,root,'function','deleteDocumentType');
						createKeyAndValue(xmlDoc,root,'document_table_name',doc);
						createKeyAndValue(xmlDoc,root,'delete',1);
						hideDivs([getEl('page4')]);	
					}
				}
				postXML(domToString(xmlDoc));
				
				showDivs([getEl('page'+page)]);	
				getEl('nextPage').disabled = true;
				getEl('prevPage').disabled = true;
				//getEl('cancelWizard').disabled = true;
				getEl('cancelWizard').value = "Restart";
				getEl('cancelWizard').onclick = function () {window.location = '../documents/documentsWizard.php'};
				break;
		default: 
				break;
	}
}

function createKeyAndValue(xmlDoc,root,key,value) {
	var entry = xmlDoc.createElement('ENTRY');
	root.appendChild(entry);

	var k = xmlDoc.createElement('KEY');
	k.appendChild(xmlDoc.createTextNode(key));
	entry.appendChild(k);
	
	var v = xmlDoc.createElement('VALUE');
	v.appendChild(xmlDoc.createTextNode(value));
	entry.appendChild(v);
}

function check4Next() {
	var td = getEl('errorMsg');
	var textNodeContents = [];
	for(var chld = td.firstChild; chld; chld = chld.nextSibling) {
		if (chld.nodeType == 3) { // text node
			var temp=chld.nodeValue;
			if (temp=="Document type created successfully")
			getEl('nextPage').disabled = false;
		}
	}
}
function addDocumentType() {
	var docType = getEl('documentName').value;		
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);
	createKeyAndValue(xmlDoc,root,'function','xmlAddDocType');

	if(docTableName) {
		createKeyAndValue(xmlDoc,root,'document_table_name',docTableName);
	}
	
	createKeyAndValue(xmlDoc,root,'document_type_name',docType);
	postXML(domToString(xmlDoc));
	setTimeout("check4Next()",1000);
}
function addDocumentField() {
	name = getEl('indexName').value;	
	getEl('indexName').select();
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);

	var eMsg = getEl('errorMsg');
	clearDiv(eMsg);

	createKeyAndValue(xmlDoc,root,'function','xmlAddDocumentField');
	createKeyAndValue(xmlDoc,root,'document_table_name',docTableName);
	createKeyAndValue(xmlDoc,root,'field_name',name);
	postXML(domToString(xmlDoc));
}

function deleteDocumentField(index) {
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);
	createKeyAndValue(xmlDoc,root,'function','deleteDocumentField');
	createKeyAndValue(xmlDoc,root,'arb_field_name',index);
	createKeyAndValue(xmlDoc,root,'document_table_name',docTableName);
	postXML(domToString(xmlDoc));
}


function cancelEditIndex(name) {
	prevSelected = "";
	var listElement = getEl('name-'+name);
	new Effect.Fade(listElement);
	listElement.parentNode.removeChild(listElement);		
	
	var oldListElement = getEl('oldname-'+name);
	oldListElement.id = 'name-'+name;
	new Effect.Appear(oldListElement);
	reInitialize();
	getEl('nextPage').disabled = false;
}

function setDocumentInfo(docTable,docType) {
	docTableName = docTable;
	docTypeName = docType;
	getEl('documentName').value = docTypeName;
	getEl('addDocType').value = "Edit";

	var text = 'Index List of '+getEl('documentName').value;
	clearDiv(getEl('leg'));
	getEl('leg').appendChild(document.createTextNode(text));

	getEl('indexName').disabled = false;
	getEl('B2').disabled = false;
	setTimeout('focusField()',25);
}

function focusField() {

	getEl('indexName').focus();
	getEl('indexName').select();
}

function receiveXML(req) {
	eMsg = getEl('errorMsg');
	clearDiv(eMsg);
	if(req.responseXML) {
		var XML = req.responseXML;

		var log = XML.getElementsByTagName('LOGOUT');
		if(log.length > 0) {
			top.window.location = '../logout.php';
		}

		var ind = XML.getElementsByTagName('INDICE');
		if(ind.length > 0) {
			for(var i=0;i<ind.length;i++) {
				addIndexElement(ind[i].firstChild.nodeValue);
			}
		}

		var func = XML.getElementsByTagName('FUNCTION');
		if(func.length > 0) {
			eval(decodeURI(func[0].firstChild.nodeValue));
		}

		var mess = XML.getElementsByTagName('MESSAGE');
		if(mess.length > 0) {
			eMsg.appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
		}
	} else {
		clearDiv(eMsg);
		eMsg.appendChild(document.createTextNode('An Error Occured Loading the XML'));
	}
	document.body.style.cursor = 'default';
} 

function reportError(req) {
	if(getEl('errorMsg')) {
		getEl('errorMsg').appendChild(document.createTextNode('An Error Occured Loading the XML'));
	}
}

function postXML(xmlStr,u) {
	var URL = '../documents/documentPostRequest.php';
	if(u) {
		URL = u;
	}
	var eMsg = getEl('errorMsg');
	if(eMsg) {
		clearDiv(eMsg);
		document.body.style.cursor = 'wait';
		eMsg.appendChild(document.createTextNode('Please Wait....'));
	}
	//alert(xmlStr);
	var newAjax = new Ajax.Request( URL,
								{   method: 'post',
									postBody: xmlStr,
									onComplete: receiveXML,
									onFailure: reportError} );	

}

function openEditIndex(name) {
	getEl('nextPage').disabled = true;
	if(prevSelected) {
		cancelEditIndex(prevSelected);
	}
	prevSelected = name;
	var oldElement = getEl('name-'+name);

	var listElement = document.createElement('li');
	listElement.id = 'name-'+name;
	
	var divEl = document.createElement('div');
	listElement.appendChild(divEl);

	var saveImg = new Image();
	saveImg.src = "../energie/images/save.gif";
	saveImg.alt = "Save";
	saveImg.onclick = function() { saveEditIndex(name)}; 
	saveImg.width = 16;
	saveImg.height = 16;
	saveImg.align = 'left';
	saveImg.style.cursor = 'pointer';
	divEl.appendChild(saveImg);

	var cancelImg = new Image();
	cancelImg.src = "../energie/images/cancl_16.gif";
	cancelImg.alt = "Cancel";
	cancelImg.onclick = function() { cancelEditIndex(name)};
	cancelImg.width = 16;
	cancelImg.height = 16;
	cancelImg.align = 'left';
	cancelImg.style.cursor = 'pointer';
	cancelImg.style.paddingLeft = '2px';
	cancelImg.style.paddingRight = '2px';
	divEl.appendChild(cancelImg);

	var textEl = document.createElement('input');
	textEl.type = 'text';
	textEl.id = 'edit-'+name;
	textEl.value = name;
	textEl.style.width = '200px';
	textEl.style.height = '15px';
	textEl.onkeypress = onEnter;
	divEl.appendChild(textEl);

	listElement.style.display = 'none';
	getEl('currentIndices').insertBefore(listElement,oldElement);
	oldElement.id = 'oldname-'+name;
	oldElement.style.display = 'none';
	new Effect.Appear(listElement);
}

function saveEditIndex(name) {
	var newName = getEl('edit-'+name).value;
	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);

	createKeyAndValue(xmlDoc,root,'function','renameDocumentField');
	createKeyAndValue(xmlDoc,root,'arb_field_name',name);
	createKeyAndValue(xmlDoc,root,'new_field_name',newName);
	createKeyAndValue(xmlDoc,root,'document_table_name',docTableName);
	postXML(domToString(xmlDoc));

	prevSelected = "";
	var listElement = getEl('name-'+name);
	new Effect.Fade(listElement);
	listElement.parentNode.removeChild(listElement);		
	var oldListElement = getEl('oldname-'+name);
	oldListElement.id = 'oldname-'+newName;
	addIndexElement(newName);
	getEl('nextPage').disabled = false;
}

function setSelectedChkBox(elementID) {
	if(getEl(elementID)) {
		getEl(elementID).checked = "checked";
	}
}

function reInitialize(){
// <![CDATA[
Sortable.create("currentIndices",
{dropOnEmpty:true,containment:["currentIndices"],constraint:false});
// ]]>
}
