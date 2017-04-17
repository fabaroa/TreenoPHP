var afterRename;

function changeNodeType()
{
	var newType = document.getElementById('newType');
	var extraArea = document.getElementById('extraArea');
	var prevSelect = document.getElementById('prev2');
	var nextSelect = document.getElementById('next2');
	var i = 0;
	var nodeTxt;
	if(newType.options[newType.selectedIndex].value == 'VALUE') {
		while(nodeTxt = document.getElementById('nodeTxt' + i)) {
			document.getElementById('nodeSel' + i).disabled = false;
			nodeTxt.disabled = false;
			i++;
		}
		var rbArr = document.getElementsByName('userOption');
		var rbAll;
		var rbAny;
		//alert("in changeNodeType()");
		for (var n = 0; n < rbArr.length; n++){
			if(rbArr[n].value == 'All'){
				rbAll = rbArr[n];
				}
			if(rbArr[n].value == 'Any'){
				rbAny = rbArr[n];
				}
			}	
			if(rbAll != null && rbAll != undefined && rbAny != null && rbAny != undefined){
					if(rbAll.checked){ 
					//alert("rbAll.checked = true");
						rbAll.checked = false;
						rbAny.checked = true;
						}
				}
			rbAll.disabled = 'true';
			
		nextSelect.disabled = true;
		prevSelect.disabled = true;
		document.getElementById('valueNodeDiv').style.display = 'block';
	} else if(newType.options[newType.selectedIndex].value == 'CUSTOM') {
		while(nodeTxt = document.getElementById('nodeTxt' + i)) {
			document.getElementById('nodeSel' + i).disabled = false;
			nodeTxt.disabled = false;
			i++;
		}
		document.getElementById('valueNodeDiv').style.display = 'none';
		nextSelect.disabled = true;
		prevSelect.disabled = true;
		document.getElementById('valueNodeDiv').style.display = 'block';
	} else if(newType.options[newType.selectedIndex].value == 'FINAL') {
		while(nodeTxt = document.getElementById('nodeTxt' + i)) {
			document.getElementById('nodeSel' + i).disabled = true;
			nodeTxt.disabled = true;
			i++;
		}
		document.getElementById('valueNodeDiv').style.display = 'none';
		nextSelect.disabled = true;
		prevSelect.disabled = true;
	} else {
		while(nodeTxt = document.getElementById('nodeTxt' + i)) {
			document.getElementById('nodeSel' + i).disabled = true;
			nodeTxt.disabled = true;
			i++;
		}
		document.getElementById('valueNodeDiv').style.display = 'none';
		nextSelect.disabled = false;
		prevSelect.disabled = false;
	}
}

function setupRenameWorkflow(mySpan) {
	mySpan.style.display = 'none';
	var formTable = document.getElementById('formTable');
	var myRow = formTable.insertRow(2);
	var el = document.createElement('input');
	var cancel = document.createElement('span');
	cancel.className = 'link';
	cancel.appendChild(document.createTextNode('Cancel Rename Workflow'));
	cancel.onclick = cancelRename;
	var myCell = myRow.insertCell(0);
	myCell.className = 'label'
	var el2 = document.createElement('label');
	el2.appendChild(document.createTextNode('New Workflow Name'));
	myCell.appendChild(el2);
	myCell = myRow.insertCell(1);
	myCell.appendChild(el);
	myCell.appendChild(cancel);
	el.onkeypress = checkEditWFKey;
	el.origName = document.getElementById('defsName').value;
	el.mySpan = mySpan;
	cancel.mySpan = mySpan;
}

function checkEditWFKey(e) {
	var charCode = e ? e.which : window.event.keyCode;
	if(charCode == 13) {
		var defsName = document.getElementById('defsName');
		var formTable = document.getElementById('formTable');
		var newName = this.value;
		var origName = this.origName;
		var p = getXMLHTTP();
		var myEl = this;
		for(var i = 0; i < defsName.options.length; i++) {
			if(defsName.options[i].value == newName) {
				document.getElementById('errDiv').firstChild.nodeValue = 'Workflow Name Already Exists!';
				return false;
			}
		}
		p.open('POST', '../lib/settingsFuncs.php?func=changeWFName&v1=' + newName + '&v2=' + origName, true);
		p.send(null);
		p.onreadystatechange = function() {
			if(p.readyState == 4) {
				if(p.responseText == '') {
					defsName.options[defsName.selectedIndex].text = newName;
					defsName.options[defsName.selectedIndex].value = newName;
					document.getElementById('errDiv').firstChild.nodeValue = ' ';
				} else {
					document.getElementById('errDiv').firstChild.nodeValue = p.reponseText;
				}
				formTable.deleteRow(2);
				myEl.mySpan.style.display = 'inline';
			}
		};
		return false;
	}
	return true;
}

function cancelRename() {
	document.getElementById('formTable').deleteRow(2);
	this.mySpan.style.display = 'inline';
}

function submitForm() {
	document.getElementById('wfForm').submit();
}
function submitAction() {
	var defsSelect = document.getElementById('wfForm').defsName;
	
	//When the action is changed, do not submit the name of the definition
	//currently selected -- but only if the defsSelect is rendered.
	if(defsSelect) {
		defsSelect.disabled = true;
	}
	submitForm();
}
function editNode(nodeID) {
	document.location.href = "editWFNode.php?nodeID=" + nodeID;
}

function loadEditDefs(defsName) {
	var urlStr = 'createWorkflow.php?defsAction=editWF&defsName=' + defsName;
	window.location = urlStr;
}

function whatAction() {
	var editNode = document.getElementById('editNode');
	var delNode = document.getElementById('delNode');
	var myForm = document.getElementById('myForm');
	var nodeDesc = document.getElementById('nodeDesc');

	var userOption = document.getElementsByName('userOption');
	var email = document.getElementsByName('email');
	var ownerEmail = document.getElementsByName('ownEmail');
	
	var i;
	var tmpVar;
	if(editNode.checked == true && myForm.changeName.disabled == true) {
		myForm.changeName.disabled = false;
		if(myForm.newType) {
			myForm.newType.disabled = false;
		}
		myForm.prev2.disabled = false;
		myForm.next2.disabled = false;

		nodeDesc.disabled = false;
		for(i=0;i<userOption.length;i++)
			userOption[i].disabled = false;
			
		for(i=0;i<10;i++) {
			document.getElementById('nodeTxt'+i).disabled = false;
			document.getElementById('nodeSel'+i).disabled = false;
		}
			
		for(i=0;i<email.length;i++)
			email[i].disabled = false;
			
		for(i=0;i<ownerEmail.length;i++)
			ownerEmail[i].disabled = false;

		for(i = 0; tmpVar = document.getElementById('group' + i); i++) {
			tmpVar.disabled = false;
		}
		for(i = 0; tmpVar = document.getElementById('user' + i); i++) {
			tmpVar.disabled = false;
		}
	} else if(delNode.checked == true && myForm.changeName.disabled == false) {
		myForm.changeName.disabled = true;
		if(myForm.newType) {
			myForm.newType.disabled = true;
		}
		myForm.prev2.disabled = true;
		myForm.next2.disabled = true;

		nodeDesc.disabled = true;
		for(i=0;i<userOption.length;i++)
			userOption[i].disabled = true;

		for(i=0;i<10;i++) {
			document.getElementById('nodeTxt'+i).disabled = true;
			document.getElementById('nodeSel'+i).disabled = true;
		}
		
		for(i=0;i<email.length;i++)
			email[i].disabled = true;
			
		for(i=0;i<ownerEmail.length;i++)
			ownerEmail[i].disabled = true;
		
		for(i = 0; tmpVar = document.getElementById('group' + i); i++) {
			tmpVar.disabled = true;
		}
		for(i = 0; tmpVar = document.getElementById('user' + i); i++) {
			tmpVar.disabled = true;
		}
	}
}

function indexingErrDiv(errStr) {
	var errDiv = document.getElementById('errDiv');
	if(errDiv) {
		errDiv.parentNode.removeChild(errDiv);
	}
	errDiv = document.createElement('div');
	errDiv.id = 'errDiv';
	errDiv.className = 'error';
	errDiv.appendChild(document.createTextNode(errStr));
	document.body.appendChild(errDiv);
}

function removeErrDiv() {
		var errDiv = document.getElementById('errDiv');
		if(errDiv) {
			document.body.removeChild(errDiv);
		}
}

function searchAutoComplete(inputID, searchVar, acTable) {
	var input = document.getElementById(inputID);
	var searchTerm = input.value;
	var p = getXMLHTTP();
	urlStr = '../lib/settingsFuncs.php?func=searchAutoComplete&v1=' + searchTerm;
	urlStr += '&v2=' + searchVar + '&v3=' + cabinet + '&v4=' + acTable;
	indexingErrDiv('Searching In Auto Complete Table...');
	p.open('GET', urlStr, true);
	p.send(null);
	p.onreadystatechange = function () {
		if(p.readyState == 4) {
			removeErrDiv();
			var xmlDoc = p.responseXML;
			var results = xmlDoc.getElementsByTagName('res');
			var searchBtn = document.getElementById('searchBtn');
			var el, j, myVal;
			for(var i = 0; i < results.length; i++) {
				for(j = 0; el = document.getElementById('field-' + j); j++) {
					if(el.name == results[i].getAttribute('field')) {
						myVal = results[i].getAttribute('value');
						if(el.tagName == 'SELECT') {
							for(var k = 0; k < el.options.length; k++) {
								if(el.options[k].value == myVal) {
									el.options[k].selected = true;
								} else {
									el.options[k].selected = false;
								}
							}
						} else {
							el.value = myVal;
						}
						break;
					}
				}
			}
			removeErrDiv();
			if(results.length == 0) {
				indexingErrDiv('No Results Found');
				needInsert = true;
			} else {
				needInsert = false;
				for(var i = 1; input = document.getElementById('field-' + i); i++) {
					if(input.select) {
						input.select();
						break;
					}
				}
			}
		}
	};
}

function acKeys(e) {
	var code;
	if (!e) var e = window.event;
	if (e.keyCode) code = e.keyCode;
	else if (e.which) code = e.which;
	if(code == 13) {
		document.getElementById('searchBtn').click();
	}
	return true;
}

function acKeysSubmit(e) {
	var code;
	if (!e) var e = window.event;
	if (e.keyCode) code = e.keyCode;
	else if (e.which) code = e.which;
	if(code == 13) {
		afterRename = function () {};
		submitRenameFolder();
	}
	return true;
}

function submitRenameFolder() {
	var p = getXMLHTTP();
	var input, i;
	var domDoc = createDOMDoc();
	var folderEl = domDoc.createElement('FOLDER');
	domDoc.appendChild(folderEl);
	var cabEl = domDoc.createElement('CABINET');
	cabEl.setAttribute('name',cabinet);
	folderEl.appendChild(cabEl);
	
	var docidEl = domDoc.createElement('DOCID');
	var el;
	if(needInsert) {
		el = domDoc.createElement('AUTOCOMPLETE');
		el.setAttribute('insert', '1');
		folderEl.appendChild(el);
	}
	docidEl.setAttribute('id',docID);
	folderEl.appendChild(docidEl);
	for(i = 0; input = document.getElementById('field-' + i); i++) {
		var fieldEl = domDoc.createElement('FIELD');
		fieldEl.appendChild(domDoc.createTextNode(input.value));
		fieldEl.setAttribute('name',input.name);
		folderEl.appendChild(fieldEl);
	}
	var postStr = domToString(domDoc);
	var urlStr = '../secure/indexEdit.php';
	
	p.open('POST', urlStr, true);
	p.send(postStr);
	p.onreadystatechange = function() {
		if(p.readyState == 4) {
			if(p.responseText != "") {
				indexingErrDiv(p.responseText);
			} else {
				indexingErrDiv('Folder successfully updated');
			}
			afterRename();
		}
	}
}
var currShowing;

function loadInitialImg() {
	var p = getXMLHTTP();
	var urlStr = '../lib/settingsFuncs.php?func=getFileIDs&v1=' + cabinet + '&v2=' + docID + '&v3='+file_id;
	p.open('GET', urlStr, true);
	p.send(null);
	p.onreadystatechange = function() {
		if(p.readyState == 4) {
			var xmlDoc = p.responseXML;
			var tmpArr = xmlDoc.getElementsByTagName('file');
			for(var i = 0; i < tmpArr.length; i++) {
				fileIDs[i] = tmpArr[i].getAttribute('fileID');
			}
			if(fileIDs.length > 0) {
				displayCurrPage();
			} else {
				getEl('navDiv').style.display = 'none';
			}
		}
	};
	if(document.getElementById('field-0').focus) {
		document.getElementById('field-0').focus();
	}
	var i = 0;
	var el;
	while(el = document.getElementById('field-' + i)) {
		if(dateFunctions && el.name.search(/date/i) != -1 || el.name.search(/DOB/i) != -1) {
			el.validate = validateDate;
			newImg = document.createElement('img');
			newImg.src = '../images/edit_16.gif';
			newImg.style.cursor = 'pointer';
			newImg.style.verticalAlign = 'middle';
			newImg.input = el;
			newImg.onclick = dispCurrMonth;
			newImg.whereID = 'rowDiv';
			el.parentNode.insertBefore(newImg, el.nextSibling);
		} else {
			el.validate = function(){return true;};
		}
		i++;
	}
}

function goToNextPage() {
	currPage++;
	if(currPage == fileIDs.length) {
		currPage--;
	} else {
		displayCurrPage();
	}
}

function goToPrevPage() {
	currPage--;
	if(currPage == -1) {
		currPage++;
	} else {
		displayCurrPage();
	}
}

function displayCurrPage() {
	var urlStr = '../energie/readfile.php?cab=' + cabinet + '&fileID=' + 
				 fileIDs[currPage] + '&doc_id=' + docID;
	document.getElementById('myEmbed').src = urlStr;
	document.getElementById('currPageInput').value = currPage + 1;
}

function goToFirstPage() {
	if(currPage != 0) {
		currPage = 0;
		displayCurrPage();
	}
}

function goToLastPage() {
	if(currPage != (fileIDs.length - 1)) {
		currPage = fileIDs.length - 1;
		displayCurrPage();
	}
}

function checkKeyForPage(e) {
	var input = document.getElementById('currPageInput');
	var charCode = e ? e.which : window.event.keyCode;
	if(charCode > 47 && charCode < 58) {
		return true;
	} else if(charCode == 13) {
		var oldPage = currPage;
		if(input.value != '') {
			if(input.value >= fileIDs.length) {
				currPage = fileIDs.length - 1;
			} else if(input.value < 1) {
				currPage = 0;
			} else {
				currPage = input.value - 1;
			}
		}
		if(oldPage != currPage) {
			displayCurrPage();
		}
	}
	return false;
}

function clearArea(box) {
	if(box.value == 'Enter notes here') {
		box.value = '';
	}
}

function acceptIndexingNode() {
	afterRename = function () {
		document.getElementById('submitField').value = 'Accept';
		document.getElementById('notesField').value = document.getElementById('notes').value;
		document.getElementById('indexingForm').submit();
	};
	submitRenameFolder();
}

function rejectIndexingNode() {
	var notes = document.getElementById('notes').value;
	if(notes == '' || notes == 'Enter notes here') {
		indexingErrDiv("You Must Enter Notes To Reject!");
	} else {
		afterRename = function () {
			document.getElementById('submitField').value = 'Reject';
			document.getElementById('notesField').value = notes;
			document.getElementById('indexingForm').submit();
		};
		submitRenameFolder();
	}
}
