// $Id: groups.js 14779 2012-04-02 12:36:41Z fabaroa $

function makeSelect(URL) {
	var selVal = document.getElementById('permissionSelect').value;	
	if( selVal && selVal != 'default' ) {
		var xmlhttp = getXMLHTTP();
		var postStr = 'selName='+selVal;
		xmlhttp.open('POST', URL, true);
		xmlhttp.setRequestHeader('Content-Type',
									  'application/x-www-form-urlencoded');
		xmlhttp.send(postStr);
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
				if( xmlhttp.responseXML ) {
					var xml = xmlhttp.responseXML;
					entries = xml.getElementsByTagName('ENTRY');
					var outerDiv = document.getElementById('editGroupPermissions');								
					innerDiv = document.getElementById('permissions');
					if( innerDiv ) {
						clearDiv(innerDiv);
					} else {
						var innerDiv = document.createElement('div');
						innerDiv.id = 'permissions';
						innerDiv.className = 'inputForm';
						outerDiv.appendChild(innerDiv);
					}
					if( editGroupName != "" ) {
						innerDiv.appendChild(createEditGroupTextBox());
					}
					var tableEl = document.createElement('table');
					tableEl.width = '100%';
					innerDiv.appendChild(tableEl);
					var row = tableEl.insertRow(tableEl.rows.length);
					row.style.fontWeight = 'bold';
					columnHeaders = xml.getElementsByTagName('HEADER');
					headerArr = new Array();
					for(var i=0;i<columnHeaders.length;i++) {
						var colType = columnHeaders[i].getAttribute('type');	
						var colTxt = columnHeaders[i].firstChild.nodeValue;	
						if( colType ) {
							createHeaderColumnCell(row,colTxt,colType);	
							headerArr[headerArr.length] = colType;
						} else {
							createHeaderColumnCell(row,colTxt,'');	
						}
					}
					for(var i=0;i<entries.length;i++) {
						var row = tableEl.insertRow(tableEl.rows.length);
						row.style.padding = '5px';
						createHeaderColumnCell(row,entries[i].getAttribute('arb'),'');	
						for(var j=0;j<headerArr.length;j++) {
							createRadioButtons(row,headerArr[j],entries[i]);
						}
					}
					innerDiv.appendChild(createSaveButton());
				} else {
					top.window.location = '../logout.php';
				}
			}
		};
	}
}

function createHeaderColumnCell(element,text,type) {
	var col = element.insertCell(element.cells.length);
	col.appendChild(document.createTextNode(text));
	if( type != '' ) {
		col.onclick = function() {selectAllGroups(type)};
		col.onmouseover = mOver;
		col.onmouseout = mOut;
		col.style.cursor = 'pointer';
	}
}

function mOut() {
	this.style.backgroundColor = '#ffffff';
}

function mOver() {
	this.style.backgroundColor = '#888888';
}

function selectAllGroups(type) {
	var radioEl = document.getElementsByTagName('input');
	for(var i=0;i<radioEl.length;i++) {
		if( radioEl[i].type == 'radio' && radioEl[i].value == type ) {
			radioEl[i].checked = true;
		}
	}
}

function createEditGroupTextBox() {
	var selBox = document.getElementById('permissionSelect');
	var arbGroupName = selBox.options[selBox.selectedIndex].text;
	var editDiv = document.createElement('div');
	editDiv.className = 'inputForm';
	editDiv.appendChild(document.createTextNode('Edit Group Name '));
	var txtBox = document.createElement('input');
	txtBox.id = 'editName';
	txtBox.type = 'text';
	txtBox.value = arbGroupName; 
	editDiv.appendChild(txtBox);
	return editDiv;
}

function createSaveButton() {
	var bottomDiv = document.createElement('div');
	bottomDiv.style.height = '1.5em';
	var deleteDiv = document.createElement('div');
	bottomDiv.appendChild(deleteDiv);
	if( editGroupName != "" ) {
		var b2 = document.createElement('input');
		b2.type = 'button';
		b2.value = 'Delete Group';
		b2.onclick = function() {submitGroupPermissions('delete')};
		deleteDiv.appendChild(b2);
	}
	if( document.all ) {
		deleteDiv.style.styleFloat = 'left';
	} else {
		deleteDiv.style.cssFloat = 'left';
	}
	var buttonDiv = document.createElement('div');
	var b1 = document.createElement('input');
	b1.type = 'button';
	b1.value = 'Save';
	b1.onclick = function() {submitGroupPermissions('edit')};
	buttonDiv.appendChild(b1);
	bottomDiv.appendChild(buttonDiv);
	if( document.all ) {
		buttonDiv.style.styleFloat = 'right';
	} else {
		buttonDiv.style.cssFloat = 'right';
	}
	var messageDiv = document.createElement('div');
	messageDiv.appendChild(document.createTextNode('\u00a0'));
	bottomDiv.appendChild(messageDiv);
	messageDiv.id = 'errorDiv';
	messageDiv.style.textAlign = 'center';
	messageDiv.style.color = 'red';
	messageDiv.style.width = '15em';
	messageDiv.style.marginLeft = 'auto';
	messageDiv.style.marginRight = 'auto';
	bottomDiv.style.width = '100%';

	return(bottomDiv);
}

//function createUpdateButton() {
//	var bottomDiv = document.createElement('div');
//	bottomDiv.style.height = '1.5em';
//	var deleteDiv = document.createElement('div');
//	bottomDiv.appendChild(deleteDiv);
//	if( editGroupName != "" ) {
//		var b2 = document.createElement('input');
//		b2.type = 'button';
//		b2.value = 'Delete Group';
//		b2.onclick = function() {submitGroupPermissions('delete')};
//		deleteDiv.appendChild(b2);
//	}
//	//deleteDiv.align = 'left';
//	if( document.all ) {
//		deleteDiv.style.styleFloat = 'left';
//	} else {
//		deleteDiv.style.cssFloat = 'left';
//	}
//	//deleteDiv.style.width = '15%';
//	//deleteDiv.style.paddingLeft = '0px';
//	var buttonDiv = document.createElement('div');
//	var b1 = document.createElement('input');
//	b1.type = 'button';
//	b1.value = 'Update';
//	b1.onclick = function() {submitGroupPermissions('edit')};
//	buttonDiv.appendChild(b1);
//	bottomDiv.appendChild(buttonDiv);
//	//buttonDiv.align = 'right';
//	//buttonDiv.style.paddingRight = '10px';
//	if( document.all ) {
//		buttonDiv.style.styleFloat = 'right';
//	} else {
//		buttonDiv.style.cssFloat = 'right';
//	}
//	var messageDiv = document.createElement('div');
//	messageDiv.appendChild(document.createTextNode('\u00a0'));
//	bottomDiv.appendChild(messageDiv);
//	messageDiv.id = 'errorDiv';
//	messageDiv.style.textAlign = 'center';
//	messageDiv.style.color = 'red';
//	messageDiv.style.width = '15em';
//	messageDiv.style.marginLeft = 'auto';
//	messageDiv.style.marginRight = 'auto';
//	/*messageDiv.align = 'center';
//	if( document.all ) {
//		messageDiv.style.styleFloat = 'left';
//	} else {
//		messageDiv.style.cssFloat = 'left';
//	}
//	*/
//	//buttonDiv.style.width = '15%';
//	bottomDiv.style.width = '100%';
//	//bottomDiv.style.height = '20px';
///*
//	var b2 = document.createElement('input');
//	b2.type = 'button';
//	b2.value = 'Delete Group';
//	b2.onclick = function() {submitGroupPermissions('delete')};
//	deleteDiv.appendChild(b2);
//
//	var b1 = document.createElement('input');
//	b1.type = 'button';
//	b1.value = 'Update';
//	b1.onclick = function() {submitGroupPermissions('edit')};
//	buttonDiv.appendChild(b1);
//*/
//	return(bottomDiv);
//}

function createRadioButtons(element,type,value) {
	var col = element.insertCell(element.cells.length);
	var rv = -1;
	if (navigator.appName == 'Microsoft Internet Explorer')
  {
    var ua = navigator.userAgent;
    var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
    if (re.exec(ua) != null)
      rv = parseFloat( RegExp.$1 );
  }
	if( !document.all || rv >= 9.0 ) {
		var radioEl = document.createElement('input');
		radioEl.type = 'radio';
		radioEl.name = value.getAttribute('name');
	} else {
		var radioEl = document.createElement('<input type="radio" name="'+value.getAttribute('name')+'">');
	}
	radioEl.value = type;
	col.appendChild(radioEl);
	if( value.firstChild.nodeValue == type ) {
		radioEl.checked = true;
	}
}

function submitGroupPermissions(type) {
	var errDiv = document.getElementById('errorDiv');
	clearDiv(errDiv);
	errDiv.appendChild(document.createTextNode('Updating....please wait'));

	var xmlhttp = getXMLHTTP();
	postStr = createPostStr(type);
	xmlhttp.open('POST', postURL, true);
	xmlhttp.setRequestHeader('Content-Type',
								  'application/x-www-form-urlencoded');
	xmlhttp.send(postStr);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			if( xmlhttp.responseText != "" ) {
				top.window.location = '../logout.php';
			}
			clearDiv(errDiv);
			errDiv.appendChild(document.createTextNode('Updated Successfully'));
			if(redirect!="") {
				parent.mainFrame.window.location = redirect;
			}
			if( type == 'delete' ) {
				parent.mainFrame.window.location = '../groups/editGroups.php';
			}
		}
	};
}

function createPostStr(type) {
	var domdoc = createDOMDoc();
	var root = domdoc.createElement('ROOT');
	root.setAttribute('name',document.getElementById('permissionSelect').value);
	domdoc.appendChild(root);
	if( type == 'delete' ) {
		var del = domdoc.createElement("DELETE");
		root.appendChild(del);
		var selBox = document.getElementById('permissionSelect');
		selBox.options[selBox.selectedIndex].text = document.getElementById('editName').value;
	} else {
		if( editGroupName != "" ) {
			var edit = domdoc.createElement("EDIT");
			edit.appendChild(domdoc.createTextNode(document.getElementById('editName').value));
			root.appendChild(edit);
			var selBox = document.getElementById('permissionSelect');
			selBox.options[selBox.selectedIndex].text = document.getElementById('editName').value;
		}
		var radioEl = document.getElementsByTagName('input');
		for(var i=0;i<radioEl.length;i++) {
			if( radioEl[i].type == 'radio' && radioEl[i].checked == true ) {
				var group = domdoc.createElement('PERM');
				group.setAttribute('name',radioEl[i].name);
				group.appendChild(domdoc.createTextNode(radioEl[i].value));
				root.appendChild(group);
			}
		}
	}
	return domToString(domdoc);
}
