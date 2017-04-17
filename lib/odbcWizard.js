function nextPage(pageNum) {
	var npg = pageNum + 1;
	var ppg = pageNum - 1;
	if(ppg > 0) {
		getEl('prevPage').disabled = false;
	}

	hideDivs([getEl('page'+ppg)]);	
	showDivs([getEl('page'+pageNum)]);	
	getEl('prevPage').onclick = function () {prevPage(ppg)};
	getEl('nextPage').onclick = function () {
						nextPage(npg);
						retrieveMappingInfo(npg);
						};
	if(pageNum != 3) {
		getEl('nextPage').disabled = true;	
	}
	
	getEl('cancelWizard').onclick = function () {cancelWizard(pageNum)};
}

function prevPage(pageNum) {
	var npg = pageNum + 1;
	var ppg = pageNum - 1;
	if(ppg < 1) {
		getEl('prevPage').disabled = true;
	}

	hideDivs([getEl('page'+npg)]);	
	showDivs([getEl('page'+pageNum)]);	
	getEl('prevPage').onclick = function () {prevPage(ppg)};
	getEl('nextPage').onclick = function () {
						nextPage(npg)
						retrieveMappingInfo(npg);
						};
	getEl('nextPage').disabled = false;	
	resetPage(pageNum);
	getEl('cancelWizard').disabled = false;	
	getEl('cancelWizard').onclick = function () {cancelWizard(pageNum)};
}

function cancelWizard(num) {
	if(num > 0) {
		hideDivs([getEl('page'+num)]);
		showDivs([getEl('page1')]);
	}
	getEl('nextPage').disabled = false;	
	getEl('nextPage').onclick = function () {	
						nextPage(2);
						retrieveMappingInfo(2);
						};
	getEl('prevPage').disabled = true;	
	for(var i=num;i>0;i--) {
		j = i+1;
		if(getEl('page'+j)) {
			showDivs([getEl('page'+j)]);
		}
		
		resetPage(i);

		if(getEl('page'+j)) {
			hideDivs([getEl('page'+j)]);
		}
	}
}

function connSelected(el) {
	removeDefault(el);
	getEl('cabinet_name').disabled = false;
}

function cabSelected(el) {
	removeDefault(el);
	getEl('nextPage').disabled = false;
}

function resetPage(num) {
	switch(num) {
		case 1:
			addDefault(getEl('whichConn'));
			addDefault(getEl('cabinet_name'));
			getEl('cabinet_name').disabled = true;
			break;
		case 2:
			var rd = getEl('page3').getElementsByTagName('input');
			for(var i=0;i<rd.length;i++) {
				if(rd[i].type == 'radio' && rd[i].value == 'add') {
					rd[i].checked = true;
				}
			}
			clearDiv(getEl('selectedCabinet'));
			break;
		case 3:
			var odbcTableSel = getEl('odbcTableSel');
			clearDiv(odbcTableSel);
			addDefault(getEl('odbcTableSel'));
			clearDiv(getEl('odbcColumnsTBody'));
			selectedArr = new Array();

			hideDivs([getEl('odbc_trans')]);
			clearDiv(getEl('odbc_trans_sel'));
			addDefault(getEl('odbc_trans_sel'));
			trans = false;
			odbc_level = 1;
			previousTrans = '';
			break;
		case 4:
			clearDiv(getEl('cabinetMappingTBody'));
			clearDiv(getEl('mappingTestTBody'));
			getEl('searchValue').value = '';
			break;
		case 5:
			getEl('nextPage').value = 'Next';
			var rd = getEl('page6').getElementsByTagName('input');
			for(var i=0;i<rd.length;i++) {
				if(rd[i].type == 'radio' && rd[i].value == '1') {
					rd[i].checked = true;
				}
			}
			break;
		default:
			break;
	}
}

function receiveXMLForMapping(req) {
	//alert(req.responseText);
	if(req.responseXML) {
		var XML = req.responseXML;
		var rt = XML.getElementsByTagName('ROOT');
		
		if(rt.length > 0) {
			var err = rt[0].getAttribute('error');
			if(err) {
				clearDiv($('errorMsg'));
				$('errorMsg').appendChild(document.createTextNode(err));
			} else {
			setXMLForMapping(pg,XML);
			clearDiv($('errorMsg'));

		}



	}
} else {
	clearDiv($('errorMsg'));
	$('errorMsg').appendChild(document.createTextNode('An Error Occured Loading the XML'));
}
document.body.style.cursor = 'default';
}

function reportError(req) {
$('errorMsg').appendChild(document.createTextNode('An Error Occured Loading the XML'));
}

function debugXML(req) {
//alert('done');
}

function retrieveMappingInfo(num) {
var xmlStr = getXMLForMapping(num);
//alert(xmlStr);
if(xmlStr) {
	var eMsg = getEl('errorMsg');
	clearDiv(eMsg);
	pg = num;
	document.body.style.cursor = 'wait';
	eMsg.appendChild(document.createTextNode('Please Wait....'));
	var newAjax = new Ajax.Request(	'../secure/odbcPostRequest.php',
					{	method: 'post',
						postBody: xmlStr,
						onComplete: debugXML,
						onSuccess: receiveXMLForMapping,
						onFailure: reportError} );
	}
} 

function getXMLForMapping(num) {
	var xmlDoc = '';
	switch(num) {
		case 3:
			getEl('selectedCabinet').appendChild(document.createTextNode('Cabinet: '+getEl('cabinet_name').value));
			break;
		case 4:
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);

			var mappingAction = getEl('page3').getElementsByTagName('input');
			for(var i=0;i<mappingAction.length;i++) {
				if(mappingAction[i].type == 'radio' && mappingAction[i].checked) {
					var action = mappingAction[i].value;
				}
			}
			if(action == 1) {
				if(getEl('odbcTableSel').length == 1 || prev_bool) {
					prev_bool = false;
					root.appendChild(createKeyAndValue(xmlDoc,'function','getODBCTables'));
					root.appendChild(createKeyAndValue(xmlDoc,'connection_id',getEl('whichConn').value));
					root.appendChild(createKeyAndValue(xmlDoc,'cab_name',getEl('cabinet_name').value));
					root.appendChild(createKeyAndValue(xmlDoc,'odbc_level',odbc_level));
				} else {
					root.appendChild(createKeyAndValue(xmlDoc,'function','getODBCTableColumns'));
					root.appendChild(createKeyAndValue(xmlDoc,'connection_id',getEl('whichConn').value));
					root.appendChild(createKeyAndValue(xmlDoc,'odbc_table',getEl('odbcTableSel').value));
					root.appendChild(createKeyAndValue(xmlDoc,'cab_name',getEl('cabinet_name').value));
					root.appendChild(createKeyAndValue(xmlDoc,'odbc_level',odbc_level));
				}
			} else {
				root.appendChild(createKeyAndValue(xmlDoc,'function','removeMapping'));
				root.appendChild(createKeyAndValue(xmlDoc,'connection_id',getEl('whichConn').value));
				root.appendChild(createKeyAndValue(xmlDoc,'cab_name',getEl('cabinet_name').value));
			}
			break;
		case 5:
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);
			root.appendChild(createKeyAndValue(xmlDoc,'function','setODBCMapping'));
			root.appendChild(createKeyAndValue(xmlDoc,'connection_id',getEl('whichConn').value));
			root.appendChild(createKeyAndValue(xmlDoc,'odbc_field_count',selectedArr.length));
	
			if(getEl('odbc_trans_sel').value != '__default') {
				root.appendChild(createKeyAndValue(xmlDoc,'odbc_trans_name',getEl('odbc_trans_name').firstChild.nodeValue));
				root.appendChild(createKeyAndValue(xmlDoc,'odbc_trans_value',getEl('odbc_trans_sel').value));
				//root.appendChild(createKeyAndValue(xmlDoc,'odbc_trans_value',getEl('odbcTableSel').value+'.'+getEl('odbc_trans_sel').value));
			}

			for(var i=0;i<selectedArr.length;i++) {
				root.appendChild(createKeyAndValue(xmlDoc,'odbc_fieldname'+i,selectedArr[i]));
				if(getEl(selectedArr[i]+'-fk').checked) {
					root.appendChild(createKeyAndValue(xmlDoc,'fk'+i,1));
				} else {
					root.appendChild(createKeyAndValue(xmlDoc,'fk'+i,0));
				}

				if(getEl(selectedArr[i]+'-pk').checked) {
					root.appendChild(createKeyAndValue(xmlDoc,'pk'+i,1));
					root.appendChild(createKeyAndValue(xmlDoc,'op'+i,'='));
				} else {
					root.appendChild(createKeyAndValue(xmlDoc,'pk'+i,0));
					root.appendChild(createKeyAndValue(xmlDoc,'op'+i,''));
				}
	
				if(getEl(selectedArr[i]+'-quoted').checked) {
					root.appendChild(createKeyAndValue(xmlDoc,'quoted'+i,1));
				} else {
					root.appendChild(createKeyAndValue(xmlDoc,'quoted'+i,0));
				}
			}
			root.appendChild(createKeyAndValue(xmlDoc,'cab_name',getEl('cabinet_name').value));
			root.appendChild(createKeyAndValue(xmlDoc,'odbc_table',getEl('odbcTableSel').value));
			root.appendChild(createKeyAndValue(xmlDoc,'odbc_level',odbc_level));
			break;
		case 6:
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);

			root.appendChild(createKeyAndValue(xmlDoc,'function','updateODBCMapping'));
			root.appendChild(createKeyAndValue(xmlDoc,'auto_id',odbc_auto_id));
			var count = 0;
			for(var i=0;i<cabIndices.length;i++) {
				var ind = getEl(cabIndices[i]+'-mapping');
				if(ind.value != '') {
					root.appendChild(createKeyAndValue(xmlDoc,'id'+count,ind.value));
					root.appendChild(createKeyAndValue(xmlDoc,'docutron_name'+count,cabIndices[i]));
					count++;
				}
			}
			root.appendChild(createKeyAndValue(xmlDoc,'mapping_count',count));
			root.appendChild(createKeyAndValue(xmlDoc,'cab_name',getEl('cabinet_name').value));
			if(test_connection == 1) {
				root.appendChild(createKeyAndValue(xmlDoc,'searchValue',getEl('searchValue').value));
				getEl('prevPage').onclick = function () {	prevPage(num-2);
															retrieveMappingInfo(num-2) };
				getEl('nextPage').onclick = function () {	nextPage(num);
															retrieveMappingInfo(num) };
			}
			break;
		case 7:
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);
			
			root.appendChild(createKeyAndValue(xmlDoc,'function','xmlEnableDisableODBCMapping'));
			root.appendChild(createKeyAndValue(xmlDoc,'cab_name',getEl('cabinet_name').value));
			var mappingControl = getEl('page6').getElementsByTagName('input');
			for(var i=0;i<mappingControl.length;i++) {
				if(mappingControl[i].type == 'radio' && mappingControl[i].checked) {
					root.appendChild(createKeyAndValue(xmlDoc,'enabled',mappingControl[i].value));
				}
			}
			break;
		default:
			break;
	}	

	return domToString(xmlDoc);
}

function createKeyAndValue(xmlDoc,keyStr,valueStr) {
	var conn = xmlDoc.createElement('ENTRY');	

	var key = xmlDoc.createElement('KEY');
	key.appendChild(xmlDoc.createTextNode(keyStr));
	conn.appendChild(key);

	var value = xmlDoc.createElement('VALUE');
	value.appendChild(xmlDoc.createTextNode(valueStr));
	conn.appendChild(value);

	return conn;
}

function setXMLForMapping(num,XML) {
	var root = XML.getElementsByTagName('ROOT');
	if(root.length > 0 && root[0].getAttribute('page')) {
		num = parseInt(root[0].getAttribute('page'));
	}
	switch(num) {
		case 4:
			previousTrans = '';
			selectedArr = new Array();
			tableArr = createXMLArray(XML,'TABLE');
			levelArr = createXMLArray(XML,'COLUMN');
			var tbdy = getEl('odbcColumnsTBody');				
			clearDiv(tbdy);

			if(root[0]) {
				if(root[0].getAttribute('odbc_level')) {
					odbc_level = root[0].getAttribute('odbc_level');
				}
			}


			if(odbc_level > 1) {
				getEl('prevPage').onclick = function () {
							odbc_level--;
							prev_bool = true;
							prevPage(num)
							retrieveMappingInfo(num);
						};
			}
			
			if(root[0].getAttribute('multi_level') == '1') {
				hideDivs([getEl('page5')]);
				showDivs([getEl('page4')]);
				getEl('nextPage').onclick = function () {	nextPage(num+1);
										retrieveMappingInfo(num+1); };
				trans = true;
			}

			//list of odbc tables
			if(tableArr.length > 0) {
				clearDiv(getEl('odbcTableSel'));
				addDefault(getEl('odbcTableSel'));
				for(var i=0;i<tableArr.length;i++) {
					addOptionElement(	getEl('odbcTableSel'),
								tableArr[i]['odbc_table'],
								tableArr[i]['odbc_table'],
								root[0].getAttribute('table_name'));
				}
			} 
			//shows odbc trans
			var odbc_trans_name = getEl('odbc_trans_name');
			var odbc_trans_sel = getEl('odbc_trans_sel');
			if(root[0].getAttribute('odbc_trans') && root[0].getAttribute('multi_level') > 0) {
				var odbc_name = root[0].getAttribute('odbc_trans');
				clearDiv(odbc_trans_name);
				odbc_trans_name.appendChild(document.createTextNode(odbc_name));	
				showDivs([getEl('odbc_trans')]);
				clearDiv(odbc_trans_sel);
				addDefault(odbc_trans_sel);
				odbc_trans_sel.disabled = true;
			} else if(odbc_level == 1)  {
				hideDivs([getEl('odbc_trans')]);
			}
		
			//list of odbc columns from an odbc table
			if(levelArr.length > 0) {
				//clears odbc_trans select box
				if(trans) {
					clearDiv(odbc_trans_sel);
					addDefault(odbc_trans_sel);
				}
				for(var i=0;i<levelArr.length;i++) {
					//fills odbc_trans select box
					if(trans) {
						addOptionElement(odbc_trans_sel,levelArr[i]['odbc_name'],levelArr[i]['odbc_name']);
					}
					addODBCColumn(levelArr[i]['odbc_name'],tbdy);
					setODBCColumns(levelArr[i]);
				}				

				if(root[0].getAttribute('odbc_trans_value') && root[0].getAttribute('multi_level') > 0) {
					getEl(root[0].getAttribute('odbc_trans_value')+'-name').onclick();
					setSelected(odbc_trans_sel,root[0].getAttribute('odbc_trans_value'));
					odbc_trans_sel.onchange();
				}
			}
			if(root[0].getAttribute('table_name')) {
				odbc_trans_sel.disabled = false;
			}
			break;
   		case 5:
			if(odbc_level > 1) {
				getEl('prevPage').onclick = function () {
							prev_bool = true;
							prevPage(num-1)
							retrieveMappingInfo(num-1);
						};
			}

			if(test_connection == 1) {
				var tbody = getEl('mappingTestTBody');
				clearDiv(tbody);

				var testArr = new Array();
				testArr = createXMLArray(XML,'TEST');
				for(var i=0;i<testArr.length;i++) {
					for(var key in testArr[i]) {
						var row = tbody.insertRow(tbody.rows.length);
						row.style.height = '20px';
						
						var col1 = row.insertCell(row.cells.length);	
						col1.style.width = '125px';
						col1.style.textAlign = 'left';
						col1.appendChild(document.createTextNode(key));

						var col2 = row.insertCell(row.cells.length);	
						col2.style.width = '175px';
						col2.style.textAlign = 'left';
						col2.appendChild(document.createTextNode(testArr[i][key]));
					}
				}
				test_connection = 0;
			} else {
				cabIndices = new Array();
				odbcIndices = new Array();
				var cabColTable = getEl('cabinetMappingTBody');
				clearDiv(cabColTable);

				var odbc_names = XML.getElementsByTagName('ODBC_NAME');
				for(var j=0;j<odbc_names.length;j++) {
					odbcIndices[j] = new Array();
					odbcIndices[j][0] = odbc_names[j].getAttribute('id');
					odbcIndices[j][1] = odbc_names[j].firstChild.nodeValue;
				}

				var indices = XML.getElementsByTagName('INDEX');
				for(var i=0;i<indices.length;i++) {
					var row = cabColTable.insertRow(cabColTable.rows.length);	
					row.style.height = '30px';

					var col1 = row.insertCell(row.cells.length);
					col1.style.width = "125px";
					col1.appendChild(document.createTextNode(indices[i].firstChild.nodeValue));

					var col2 = row.insertCell(row.cells.length);
					col2.style.width = "279px";
					var selectBox = document.createElement('select');
					selectBox.id = indices[i].firstChild.nodeValue+'-mapping';
					selectBox.name = indices[i].firstChild.nodeValue+'-mapping';
					selectBox.onchange = selectMapping;
					selectBox.style.width = '200px';
					addOptionElement(selectBox,'','none');
					var selectedVal = '';
					for(var j=0;j<odbc_names.length;j++) {
						addOptionElement(selectBox,odbc_names[j].getAttribute('id'),odbc_names[j].firstChild.nodeValue);
						if(indices[i].firstChild.nodeValue == odbc_names[j].getAttribute('value')) {
							selectedVal = odbc_names[j].getAttribute('id');
						}
					}
					if(selectedVal) {
						setSelected(selectBox,selectedVal);
						getEl('nextPage').disabled = false;
					}
					col2.appendChild(selectBox);
					cabIndices[cabIndices.length] = indices[i].firstChild.nodeValue;
					selectBox.onchange();
					
					odbc_auto_id = root[0].getAttribute('auto_id'); 
				}
				getEl('nextPage').value = 'Next';
			}
			break;
		case 6: 
			//if(odbc_level > 1) {
				getEl('prevPage').onclick = function () {
							prevPage(num-1);
							retrieveMappingInfo(num-1);
						};
			//}
			//getEl('nextPage').value = 'Finish';
			getEl('nextPage').disabled = false;
			break;
		case 7:
			clearDiv(getEl('selectedCabinet'));
			getEl('nextPage').disabled = true;
			getEl('prevPage').disabled = true;
			getEl('cancelWizard').disabled = true;
			break;
		case 8:
			clearDiv(getEl('selectedCabinet'));
			getEl('nextPage').disabled = true;
			getEl('prevPage').disabled = true;
			getEl('cancelWizard').disabled = true;
			hideDivs([getEl('page4')]);	
			showDivs([getEl('page8')]);	
			break;
		default:
			break;
	}	
}

function mOver() {
	this.style.cursor = 'pointer';
	if(!ifSelected(this.id)) {	
		this.style.backgroundColor = '#eeeeee';
	}
}

function mOut() {
	if(!ifSelected(this.id)) {	
		this.style.backgroundColor = '#ffffff';
	}
}

function selectODBCColumn() {
	var pnode = '';
	if(this.type == 'checkbox' || this.type == 'radio') {
		pnode = this.parentNode.parentNode;
	} else {
		pnode = this.parentNode;
	}
	var rowid = pnode.id

	if(!ifSelected(rowid)) {
		pnode.style.backgroundColor = '#888888';
		selectedArr[selectedArr.length] = rowid; 
		deactivateCol(rowid);
	} else {
		pnode.style.backgroundColor = '#ffffff';
		getEl(rowid+'-fk').checked = false;
		getEl(rowid+'-pk').checked = false;
		selectedArr.splice(selectedNum,1);
		activateCol(rowid);
	}
	buttonControl();	
}

function ifSelected(id) {
	for(var i=0;i<selectedArr.length;i++) {
		if(selectedArr[i] == id) {
			selectedNum = i;		
			return true;
		}	
	}
	return false;
}

function deactivateCol(id) {
	getEl(id+'-fk').onclick = function () {};
	getEl(id+'-pk').onclick = function () {buttonControl()};
	getEl(id+'-quoted').onclick = function () {};
}

function activateCol(id) {
	getEl(id+'-fk').onclick = selectODBCColumn;
	getEl(id+'-pk').onclick = selectODBCColumn;
	getEl(id+'-quoted').onclick = selectODBCColumn;
}

function priSearchVal() {
	for(var i=0;i<selectedArr.length;i++) {
		if(getEl(selectedArr[i]+'-pk').checked || getEl('odbc_trans_sel').value != '__default') {
			return true;
		}
	}
	return false;
}

function buttonControl() {
	if(selectedArr.length > 0 && priSearchVal()) {
		getEl('nextPage').disabled = false;	
	} else {
		getEl('nextPage').disabled = true;	
	}
}

function selectMapping() {
	var bool = true;
	if(this.value == '') {
		for(var i=0;i<cabIndices.length;i++) {
			if(getEl(cabIndices[i]+'-mapping').value != '') {
				bool = false;
				break;
			}	
		}
	} else {
		bool = false;
	}
	removeSelected();
	getEl('nextPage').disabled = bool;	
}

function addOptionElement(el,value,txt,sel) {
	var opt = document.createElement('option');
	opt.value = value;
	opt.appendChild(document.createTextNode(txt));
	if(sel == value) {
		opt.selected = true;
	}
	el.appendChild(opt);
}

function removeSelected() {
	checkSelectedArr = new Array();
	for(var i=0;i<cabIndices.length;i++) {
		var odbcNameSel = getEl(cabIndices[i]+'-mapping');
		if(odbcNameSel.value != '') {
			checkSelectedArr[checkSelectedArr.length] = odbcNameSel.value;
		}
		
	}

	//this will remove nodes that are already selected
	for(var i=0;i<cabIndices.length;i++) {
		var odbcNameSel = getEl(cabIndices[i]+'-mapping');
		for(var j=0;j<odbcNameSel.length;j++) {
			for(var k=0;k<checkSelectedArr.length;k++) {
				if(odbcNameSel.options[j] && odbcNameSel.value != checkSelectedArr[k] && 
				   odbcNameSel.options[j].value == checkSelectedArr[k]) {
					odbcNameSel.removeChild(odbcNameSel.options[j]);
				}
			}
		}	
	}

	//this will add nodes that were unchecked
	for(var j=0;j<cabIndices.length;j++) {
		var odbcNameSel = getEl(cabIndices[j]+'-mapping');
		for(var i=0;i<odbcIndices.length;i++) {
			if(!checkForODBCSelected(odbcIndices[i][0])) {
				if(!checkForODBCIndice(odbcNameSel,odbcIndices[i][1])) {
					addOptionElement(odbcNameSel,odbcIndices[i][0],odbcIndices[i][1]);
				}	
			}	
		}
	}
}

function checkForODBCSelected(odbcInd) {
	for(var i=0;i<checkSelectedArr.length;i++) {
		if(odbcInd == checkSelectedArr[i]) {
			return true;
		}
	}
	return false;
}

function checkForODBCIndice(odbcSel, odbcInd) {
	for(var i=0;i<odbcSel.length;i++) {
		if(odbcSel.options[i].text == odbcInd) {
			return true;
		}
	}
	return false;	
}	

function disableODBCColumn(el) {
	removeDefault(el);
	if(previousTrans) {
		getEl(previousTrans+'-name').onclick = selectODBCColumn;
		getEl(previousTrans+'-fk').onclick = selectODBCColumn;
		getEl(previousTrans+'-pk').onclick = selectODBCColumn;
		getEl(previousTrans+'-quoted').onclick = selectODBCColumn;

		getEl(previousTrans+'-name').firstChild.nodeValue = previousTrans;
		getEl(previousTrans+'-pk').checked = false;
		getEl(previousTrans+'-name').onclick();
	}	
	previousTrans = getEl(el.value+'-name').firstChild.nodeValue;
	getEl(el.value+'-name').firstChild.nodeValue += ' ('+getEl('odbc_trans_name').firstChild.nodeValue+')';		
	getEl(el.value+'-pk').checked = true;

	var checkIfSelected = true; 
	for(var i=0;i<selectedArr.length;i++) {
		if(selectedArr[i] == el.value) {
			checkIfSelected = false;	
		}
	}

	if(checkIfSelected) {
		getEl(el.value+'-name').onclick();
	}

	getEl(el.value+'-name').onclick = function (){};
	getEl(el.value+'-fk').onclick = function (){};
	getEl(el.value+'-pk').onclick = function (){};
	getEl(el.value+'-quoted').onclick = function (){};
}

function setODBCColumns(columnArr) {
	var k = columnArr['odbc_name'];

	//sets fk column
	if(columnArr['fk'] == 1) {
		getEl(k+'-fk').checked = true;
	}

	//sets pk column
	if(columnArr['previous_value'] == 1) {
		getEl(k+'-pk').checked = true;
	}

	//sets quoted column
	if(columnArr['quoted'] == 1) {
		getEl(k+'-quoted').checked = true;
	}

	if(columnArr['quoted'] && columnArr['quoted'] != '') {
		getEl(k+'-name').onclick();
	}
}

function addODBCColumn(odbc_column,tbdy) {
	var row = tbdy.insertRow(tbdy.rows.length);
	row.id = odbc_column;
	row.onmouseover = mOver;
	row.onmouseout = mOut;
	row.style.height = '20px';

	var col1 = row.insertCell(row.cells.length);
	col1.style.width = '320px';
	col1.id = odbc_column+'-name';
	col1.onclick = selectODBCColumn;
	col1.appendChild(document.createTextNode(odbc_column));

	var col2 = row.insertCell(row.cells.length);
	col2.style.textAlign = 'center';
	col2.style.width = '30px';

	var chkbox1 = document.createElement('input');
	chkbox1.type = 'checkbox';
	chkbox1.id = odbc_column+'-fk';
	chkbox1.name = odbc_column;
	chkbox1.value = odbc_column;
	chkbox1.onclick = selectODBCColumn;
	col2.appendChild(chkbox1);

	var col3 = row.insertCell(row.cells.length);
	col3.style.textAlign = 'center';
	col3.style.width = '30px';

	var rd1 = createRadio('primarySearch');
	rd1.id = odbc_column+'-pk';
	rd1.value = odbc_column;
	if(odbc_level > 1) {
		rd1.disabled = true;
		col3.onclick = selectODBCColumn;
	} else {
		rd1.onclick = selectODBCColumn;
	}
	col3.appendChild(rd1);

	var col4 = row.insertCell(row.cells.length);
	col4.style.textAlign = 'center';
	col4.style.width = '30px';

	var chkbox2 = document.createElement('input');
	chkbox2.type = 'checkbox';
	chkbox2.id = odbc_column+'-quoted';
	chkbox2.name = odbc_column;
	chkbox2.value = odbc_column;
	chkbox2.onclick = selectODBCColumn;
	col4.appendChild(chkbox2);
}

function createXMLArray(XML,entry) {
	var newArray = new Array();
	var entries = XML.getElementsByTagName(entry);
	for(var i=0;i<entries.length;i++) {
		newArray[i] = new Object();
		var children = entries[i].childNodes;
		for(var j=0;j<children.length;j=j+2) {
			if(children.item(j).firstChild) {
				if(children.item(j+1).firstChild) {
					newArray[i][children.item(j).firstChild.nodeValue] = children.item(j+1).firstChild.nodeValue;
				} else {
					newArray[i][children.item(j).firstChild.nodeValue] = '';
				}
			}
		}
	}
	return newArray;
}

function setSelected(el,val) {
	for(var i=0;i<el.length;i++) {
		if(el.options[i].value == val) {
			//alert(el.options[i].value+'--'+val);
			try {
				el.options[i].selected = true;
			} catch(e) {
				//var eMsg = getEl('errorMsg');
				//clearDiv(eMsg);
				//eMsg.appendChild(document.createTextNode('Error reading XML data...please select back'));
			}
			//break;
		}
	}
}

function testMapping() {
	test_connection = 1;
	retrieveMappingInfo(6);
}

function addMappingColumn() {

}
