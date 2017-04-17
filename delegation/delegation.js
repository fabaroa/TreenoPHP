var behaviors = {
	'.delegatedTable tr' : function(el) {
			if(el.id) {
				el.onmouseover = function() {
											if('delFile:'+selRow != el.id && el.id) {
												el.style.backgroundColor = '#888888';
											}
										}
				el.onmouseout = function() {
											if('delFile:'+selRow != el.id && el.id) {
												el.style.backgroundColor = '#ebebeb';
											}
										}
			}
		}
};
Behaviour.register(behaviors);

function selectRow(delegateID) {
	if(selRow != delegateID) {
		getEl('delFile:'+delegateID).style.backgroundColor = '#8799E0';
		if(selRow) {
			getEl('delFile:'+selRow).style.backgroundColor = '#ebebeb';
		}
	}
	selRow = delegateID;
}

function viewDelegatedFile(delegateID) {
	if(delegateID) {
		parent.document.getElementById('rightFrame').setAttribute('rows', '*,20');
		parent.document.getElementById('mainFrameSet').setAttribute('cols', '40%,60%');

		parent.bottomFrame.location='../secure/back.php';
		parent.sideFrame.location='../secure/displayInbox.php?delegateID='+delegateID;
		selectRow(delegateID);
	}
}

function toggleDelegatedFolder(owner,fname) {
	if(getEl(owner+'-'+fname).style.display == 'block') {
		getEl(owner+'-'+fname).style.display = 'none';
	} else {
		getEl(owner+'-'+fname).style.display = 'block';
	}
}

function selectDelegatedFolder(own,name,ct) {
	if(divEl = getEl(own+'-'+name)) {
		chkbox = divEl.getElementsByTagName('input');
		var toggle = getEl('fileCheck:'+ct).checked;
		for(var i=0;i<chkbox.length;i++) {
			if(chkbox[i].type == 'checkbox') {
				chkbox[i].checked = toggle;
			}
		}
	}
}

function removeInboxDelegation() {
	var chkbox = document.getElementsByTagName('input');
	for(i=0;i<chkbox.length;i++) {
		if(chkbox[i].checked == true && chkbox[i].name != 'selectdelfolder') {
			document.filename.action += "?deleteInboxDelegation=1";
			enableAllFilesInFolder();
			document.filename.submit();
			return;
		}
	}

	clearDiv(getEl('errMsg'));
	getEl('errMsg').appendChild(document.createTextNode('No files have been selected'));
}

function enableAllFilesInFolder() {
	var inputEl = document.getElementsByTagName('input');
	for(var i=0;i<inputEl.length;i++) {
		if(inputEl[i].type == "checkbox") {
			inputEl[i].disabled = false;	
		}
	}
}

function toggleAllDelegation(t) {
	chkbox = document.getElementsByTagName('input');
	for(var i=0;i<chkbox.length;i++) {
		if(chkbox[i].type == 'checkbox') {
			chkbox[i].checked = t.checked;
		}
	}
}

function changeResults() {
	var selBox = getEl('results');
	var newRes = selBox.options[selBox.selectedIndex].value;
	window.location = 'viewDelegation.php?results='+newRes; 
}

function changePage(page) {
	if(page > totalPages) {
		page = totalPages;
	} else if(page < 1) {
		page = 1;
	}
	window.location = 'viewDelegation.php?page='+page;
}

function disableOnclick(ID) {
	funcPtr = getEl('name-del'+ID).parentNode.onclick;
	
	getEl('file-del'+ID).parentNode.onclick = function() {};
	getEl('name-del'+ID).parentNode.onclick = function() {};
	getEl('delegatedBy-del'+ID).parentNode.onclick = function() {};
	getEl('delegatedTo-del'+ID).parentNode.onclick = function() {};
	getEl('date-del'+ID).parentNode.onclick = function() {};
	getEl('status-del'+ID).parentNode.onclick = function() {};
	getEl('comments-del'+ID).parentNode.onclick = function() {};
	getEl('info-del'+ID).parentNode.onclick = function() {};
}

function enableOnclick(ID) {
	getEl('file-del'+ID).onclick = funcPtr; 
	getEl('name-del'+ID).parentNode.onclick = funcPtr; 
	getEl('delegatedBy-del'+ID).parentNode.onclick = funcPtr;
	getEl('delegatedTo-del'+ID).parentNode.onclick = funcPtr;
	getEl('date-del'+ID).parentNode.onclick = funcPtr;
	getEl('status-del'+ID).parentNode.onclick = funcPtr;
	getEl('comments-del'+ID).parentNode.onclick = funcPtr;
	getEl('info-del'+ID).parentNode.onclick = funcPtr;
}

function restoreDelegatedItem(ID) {
	var editImg = getEl('edit-del'+ID);	
	editImg.src = '../energie/images/file_edit_16.gif';
	if(folderOpened) {
		editImg.onclick = function() { editDelegatedFile(ID,'folder') };
	} else {
		editImg.onclick = function() { editDelegatedFile(ID) };
	}

	var fileImg = getEl('file-del'+ID);	
	if(folderOpened) {
		fileImg.src = '../images/folder.png';
		fileImg.height = 16;
	} else {
		fileImg.src = '../images/docs_16.gif';
		fileImg.height = 16;
	}
	fileImg.onclick = function() {};

	var nameSpan = getEl('name-del'+ID);
	clearDiv(nameSpan);
	if(ext) {
	 	tmpName = nameVal+'.'+ext;
	} else {
		tmpName = nameVal;
	}
	nameSpan.appendChild(document.createTextNode(tmpName));
	
	var delegatedToSpan = getEl('delegatedTo-del'+ID);
	clearDiv(delegatedToSpan);
	delegatedToSpan.appendChild(document.createTextNode(delegatedVal));
	
	var statusSpan = getEl('status-del'+ID);
	clearDiv(statusSpan);
	statusSpan.appendChild(document.createTextNode(statusVal));
	
	var commentsSpan = getEl('comments-del'+ID);
	clearDiv(commentsSpan);
	commentsSpan.appendChild(document.createTextNode(commentsVal));
	
	enableOnclick(ID);
}

function editDelegatedFile(ID,folder) {
	if(prevID) {
		restoreDelegatedItem(prevID);
	}
	ext = '';
	prevID = ID;

	folderOpened = false;
	if(folder) {
		folderOpened = true;
	}

	var editImg = getEl('edit-del'+ID);	
	editImg.src = '../energie/images/cancl_16.gif';
	editImg.onclick = function() { restoreDelegatedItem(ID) };

	var fileImg = getEl('file-del'+ID);	
	fileImg.src = '../energie/images/save.gif';
	fileImg.width = 16;
	fileImg.height = 16;
	fileImg.onclick = function() { saveDelegatedFile(ID) };

	disableOnclick(ID);
	var nameSpan = getEl('name-del'+ID);
	nameVal = nameSpan.firstChild.nodeValue;
	clearDiv(nameSpan);
	var n = nameVal.split('.');
	if(n.length > 1) {
		ext = n.pop();
	}
	nameVal = n.join('.');
	nameSpan.appendChild(createInputElement('name',nameVal,ID));

	var delegatedToSpan = getEl('delegatedTo-del'+ID);
	delegatedVal = delegatedToSpan.firstChild.nodeValue;
	clearDiv(delegatedToSpan);

	delegatedToSpan.appendChild(createSelectBox('delegatedTo',userlist,ID,delegatedVal));

	var statusSpan = getEl('status-del'+ID);
	statusVal = statusSpan.firstChild.nodeValue;
	clearDiv(statusSpan);
	var statuslist = new Array('In Progress', 'Incomplete','Complete','Reject');
	statusSpan.appendChild(createSelectBox('status',statuslist,ID,statusVal));
	
	var commentsSpan = getEl('comments-del'+ID);
	commentsVal = "";
	if(commentsSpan.firstChild) {
		commentsVal = commentsSpan.firstChild.nodeValue;
	}
	clearDiv(commentsSpan);
	commentsSpan.appendChild(createTextAreaBox('comments',commentsVal,ID));
}

function createInputElement(type,val,ID) {
	var inputEl = document.createElement('input');
	inputEl.type = 'text';
	inputEl.id = 'edit-'+type+'-'+ID;
	inputEl.name = 'edit-'+type+'-'+ID;
	inputEl.value = val;
	inputEl.onkeypress = onEnter;

	return inputEl;
}

function createSelectBox(type,valArr,ID,selVal) {
	var selBox = document.createElement('select');
	selBox.id = 'edit-'+type+'-'+ID;
	selBox.name = 'edit-'+type+'-'+ID;

	for(var i=0;i<valArr.length;i++) {
		var opt = document.createElement('option');
		opt.value = valArr[i];
		opt.appendChild(document.createTextNode(valArr[i]));
		if(valArr[i] == selVal) {
			opt.selected = true;
		}
		selBox.appendChild(opt);
	}

	return selBox;
}

function createTextAreaBox(type,cmt,ID) {
	var txtBox = document.createElement('textarea');
	txtBox.id = 'edit-'+type+'-'+ID;
	txtBox.name = 'edit-'+type+'-'+ID;
	txtBox.value = cmt;
	txtBox.onkeypress = onEnter;

	return txtBox;
}
