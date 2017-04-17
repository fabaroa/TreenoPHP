function chooseCabinet() {
	var pSearchCab = $('pubSearchCab');
	$('pubSearchIndex').disabled = true;
	removeDefault(pSearchCab);

	var cab = pSearchCab.options[pSearchCab.selectedIndex].value;

	var xmlArr = { "include" : "publishing/publishing.php",
					"function" : "xmlGetCabinetFields",
					"cabinet" : cab };
	postXML(xmlArr);
}

function fillCabinetIndices(XML) {
	var pSearchIndex = $('pubSearchIndex');
	removeElementsChildren(pSearchIndex);
	addDefault(pSearchIndex);

	var selData = XML.getElementsByTagName('ENTRY');
	var fieldList = XML.getElementsByTagName('INDEX');
	if(fieldList.length > 0) {
		for(var i=0;i<fieldList.length;i++) {
			var ind = fieldList[i].firstChild.nodeValue;
			var name = fieldList[i].getAttribute('name');
			var opt = document.createElement('option');
			opt.value = name;
			if((selData[0]) && opt.value == selData[0].getAttribute('field')) {
				opt.selected = true;
			}
			opt.appendChild(document.createTextNode(ind));
			pSearchIndex.appendChild(opt);
		} 
	}	
	fillPreSelectedData(XML);
	pSearchIndex.disabled = false;	
	//pSearchIndex.onchange = function() { removeDefault(pSearchIndex) };
}

function fillPreSelectedData(XML) {
	var selData = XML.getElementsByTagName('ENTRY');
	if(selData.length > 0) {
		var fill = selData[0].getAttribute('fill');
		if(fill == "1") {
			var type = selData[0].getAttribute('type');
			if(type == "folder_search") {
				var selCab = $('pubSearchCab');
				var selIndex = $('pubSearchIndex');

				var cab = selData[0].getAttribute('cabinet');
				setSelected(selCab,cab);

				var term = selData[0].getAttribute('term');
				$('pubSearchTerm').value = term;

				var enabled = selData[0].getAttribute('enabled');
				setEnabled($('enable'),enabled);
			} else if(type == "workflow") {
				var selWF = $('pubWFList');
				var selCab = $('pubCabList');
				var selUser = $('pubUserList');
				var pubType = $('pubUpload');

				setSelected(pubType,type);

				var wf = selData[0].getAttribute('workflow');
				setSelected(selWF,wf);

				var cab = selData[0].getAttribute('cabinet');
				setSelected(selCab,cab);

				var enabled = selData[0].getAttribute('enabled');
				setEnabled($('enable'),enabled);

				var owner = selData[0].getAttribute('owner');
				setSelected(selUser,owner);
			} else {
				var selCab = $('pubCabList');
				var pubType = $('pubUpload');
				var selUser = $('pubUserList');

				setSelected(pubType,type);

				var cab = selData[0].getAttribute('cabinet');
				setSelected(selCab,cab);

				var enabled = selData[0].getAttribute('enabled');
				setEnabled($('enable'),enabled);

				var owner = selData[0].getAttribute('owner');
				setSelected(selUser,owner);
			}	
		}
	}
}

function setSelected(el,val) {
	var opts = el.options;
	for(var i=0;i<opts.length;i++) {
		if(opts[i].value == val) {
			opts[i].selected = true;
			break;
		}
	}	
	removeDefault(el);
}

function setEnabled(el,value) {
	if(value == "1") {
		el.checked = true;
	} else {
		el.checked = false;
	}
}

function chooseType() {
	var pUpload = $('pubUpload');
	removeDefault(pUpload);

	var type = pUpload.options[pUpload.selectedIndex].value;

	var xmlArr = { "include" : "publishing/publishing.php",
					"function" : "xmlGetWorkflowsAndCabinets",
					"type" : type };
	postXML(xmlArr);
}

function fillWorkflowsAndCabinets(XML) {
	var pubTable = $('publishTable');
	var rowIndex = $('pubTypeTR').sectionRowIndex;
	
	var root = XML.getElementsByTagName('ENTRY');
	var type = root[0].getAttribute('type');

	removeAddRows();	
	if(type == "workflow") {
		var wfList = XML.getElementsByTagName('WORKFLOW');
		if(wfList.length > 0) {
			rowIndex++;
			wfSelect = createSelect("pubWFList");
			createTableRow(pubTable,rowIndex,'pubWFTR','Workflow',wfSelect);
			for(var i=0;i<wfList.length;i++) {
				var wf = wfList[i].firstChild.nodeValue;
				var opt = document.createElement('option');
				opt.value = wfList[i].getAttribute('id');
				opt.appendChild(document.createTextNode(wf));
				wfSelect.appendChild(opt);	
			}
		}
	}

	var userList = XML.getElementsByTagName('USER');
	if(userList.length > 0) {
		rowIndex++;
		userSelect = createSelect("pubUserList");
		createTableRow(pubTable,rowIndex,'pubUserTR','Owner',userSelect);
		for(var i=0;i<userList.length;i++) {
			var user = userList[i].firstChild.nodeValue;
			var opt = document.createElement('option');
			opt.value = user;
			opt.appendChild(document.createTextNode(user));
			userSelect.appendChild(opt);	
		}
	}

	var cabList = XML.getElementsByTagName('CABINET');
	if(cabList.length > 0) {
		rowIndex++;
		cabSelect = createSelect("pubCabList");
		createTableRow(pubTable,rowIndex,'pubCabTR','Cabinet',cabSelect);
		for(var i=0;i<cabList.length;i++) {
			var cab = cabList[i].firstChild.nodeValue;
			var opt = document.createElement('option');
			opt.value = cabList[i].getAttribute('name');
			opt.appendChild(document.createTextNode(cab));
			cabSelect.appendChild(opt);	
		}
	}
	fillPreSelectedData(XML);
}

function removeAddRows() {
	if(el = $('pubWFTR')) {
		var rowIndex = el.sectionRowIndex;
		$('publishTable').deleteRow(rowIndex);
	}

	if(el = $('pubUserTR')) {
		var rowIndex = el.sectionRowIndex;
		$('publishTable').deleteRow(rowIndex);
	}

	if(el = $('pubCabTR')) {
		var rowIndex = el.sectionRowIndex;
		$('publishTable').deleteRow(rowIndex);
	}
}

function createSelect(id) {
	var selBox = document.createElement('select');
	selBox.id = id;
	selBox.name = id;
	selBox.onchange = function() { removeDefault(selBox) };
	addDefault(selBox);

	return selBox;
}

function createTableRow(table,rowIndex,id,labelStr,selBox) {
	var row = table.insertRow(rowIndex);
	row.id = id;

	var col = row.insertCell(row.cells.length);
	col.className = 'label';

	var label = document.createElement('label');
	label.appendChild(document.createTextNode(labelStr));
	col.appendChild(label);

	var col = row.insertCell(row.cells.length);
	col.className = 'input';
	col.appendChild(selBox);
}

function addPublishSearch() {
	var xmlArr = { "include" : "publishing/publishing.php",
					"function" : "xmlPublishSearch"};

	var errMsg = $('errorMsg');
	if(el = $('editPubSearch')) {
		xmlArr["requestType"] = "update";

		var pubID = el.value;
		if(pubID == "__default") {
			removeElementsChildren(errMsg);
			var txtNode = document.createTextNode('Choose a Name');
			errMsg.appendChild(txtNode);
			return false;
		}		
		xmlArr["pubID"] = pubID;
	} else {
		xmlArr["requestType"] = "add";

		var pubName = $('publishName').value;
		if(!pubName) {
			removeElementsChildren(errMsg);
			errMsg.appendChild(document.createTextNode('Name is empty'));
			return false;
		} 
		xmlArr["name"] = pubName;
	}

	if(el = $('pubUpload')) {
		var type = el.value;
		if(type == "__default") {
			removeElementsChildren(errMsg);
			errMsg.appendChild(document.createTextNode('Choose a Type'));
			return false;
		}
		xmlArr["type"] = type;

		if(type == "workflow") {
			if(el = $('pubWFList')) {
				var wfName = el.value;
				if(wfName == "__default") {
					removeElementsChildren(errMsg);
					var txtNode = document.createTextNode('Choose a Workflow');
					errMsg.appendChild(txtNode);
					return false;
				}
				xmlArr["wf_def_id"] = wfName;
			}

			if(el = $('pubUserList')) {
				var wfOwner = el.value;
				if(wfOwner == "__default") {
					removeElementsChildren(errMsg);
					var txtNode = document.createTextNode('Choose an Owner');
					errMsg.appendChild(txtNode);
					return false;
				}
				xmlArr["owner"] = wfOwner;
			}
		} 

		if(el = $('pubCabList')) {
			var cabName = el.value;
			if(cabName == "__default") {
				removeElementsChildren(errMsg);
				var txtNode = document.createTextNode('Choose a Cabinet');
				errMsg.appendChild(txtNode);
				return false;
			}
			xmlArr["cab"] = cabName;
		}

		if(el = $('pubUserList')) {
			var uname = el.value;
			if(uname == "__default") {
				removeElementsChildren(errMsg);
				var txtNode = document.createTextNode('Choose One');
				errMsg.appendChild(txtNode);
				return false;
			}
			xmlArr["owner"] = uname;
		}
	} else {
		xmlArr["type"] = "folder_search";
		if(el = $('pubSearchCab')) {
			var cabName = el.value;
			if(cabName == "__default") {
				removeElementsChildren(errMsg);
				var txtNode = document.createTextNode('Choose a Cabinet');
				errMsg.appendChild(txtNode);
				return false;
			}
			xmlArr["cab"] = cabName;
		}

		if(el = $('pubSearchIndex')) {
			var indexName = el.value;
			if(indexName == "__default") {
				removeElementsChildren(errMsg);
				var txtNode = document.createTextNode('Choose an Index');
				errMsg.appendChild(txtNode);
				return false;
			}
			xmlArr["field"] = indexName;
		}

		if(el = $('pubSearchTerm')) {
			var searchTerm = el.value;
			if(!searchTerm) {
				removeElementsChildren(errMsg);
				var txtNode = document.createTextNode('Enter Search Term');
				errMsg.appendChild(txtNode);
				return false;
			}
			xmlArr["term"] = searchTerm;
		}
	}
	
	if(el = $('enable')) {
		var enable = (el.checked) ? el.value : 0;
		xmlArr["enabled"] = enable;
	}
	postXML(xmlArr);
} 

function editPublishSearch() {
	var editPub = $('editPubSearch');
	removeDefault(editPub);

	var pubID = editPub.options[editPub.selectedIndex].value;
	var xmlArr = { "include" : "publishing/publishing.php",
					"function" : "xmlGetPublishSearch",
					"id" : pubID };
	postXML(xmlArr);
}

function addPublishUser() {
	var xmlArr = { "include" : "publishing/publishing.php",
					"function" : "xmlPublishUser" };

	var errMsg = $('errorMsg');
	if(el = $('editPubUser')) {
		xmlArr["requestType"] = "update";

		var pubUserID = el.value;
		if(pubUserID == "__default") {
			removeElementsChildren(errMsg);
			var txtNode = document.createTextNode('Choose a Name');
			errMsg.appendChild(txtNode);
			return false;
		}		
		xmlArr["pubUserID"] = pubUserID;
	} else {
		xmlArr["requestType"] = "add";

		var pubUsername = $('pubUser').value;
		if(!pubUsername) {
			removeElementsChildren(errMsg);
			errMsg.appendChild(document.createTextNode('Name is empty'));
			return false;
		} 
		xmlArr["email"] = pubUsername;
	}

	var ct = 1;
	var num = 1;
	while(el = $('check-'+ct)) {
		if(el.checked) {
			xmlArr["pubSearch"+num] = el.value;
			num++;
		}
		ct++;
	}	

	if(el = $('upload')) {
		var upload = (el.checked) ? el.value : 0;
		xmlArr["upload"] = upload;
	}

	if(el = $('publish')) {
		var publish = (el.checked) ? el.value : 0;
		xmlArr["publish"] = publish;
	}

	postXML(xmlArr);
}

function selectAllPubSearch() {
	var toggle = $('pubSearchAll').checked;
	var chkBoxes = document.getElementsByClassName('checkbox');
	for(i=0;i<chkBoxes.length;i++) {
		chkBoxes[i].checked = toggle;
	}	
}

function editPublishUser() {
	var editPub = $('editPubUser');
	removeDefault(editPub);

	var pubID = editPub.options[editPub.selectedIndex].value;

	var xmlArr = { "include" : "publishing/publishing.php",
					"function" : "xmlGetPublishUser",
					"id" : pubID };
	postXML(xmlArr);
}

function fillPublishUser(XML) {
	$('pubSearchAll').checked = false;
	selectAllPubSearch();

	var searchList = XML.getElementsByTagName('SEARCH');
	var chkList = document.getElementsByClassName('checkbox');
	if(searchList.length > 0) {
		for(var i=0;i<searchList.length;i++) {
			var id = searchList[i].firstChild.nodeValue;
			for(var j=0;j<chkList.length;j++) {	
				if(id == chkList[j].value) {
					chkList[j].checked = true;
					break;
				}
			}

		}
	}
	var root = XML.getElementsByTagName('ENTRY');
	var upload = root[0].getAttribute('upload');
	$('upload').checked = (upload == "1") ? true : false;

	var publish = root[0].getAttribute('publish');
	$('publish').checked = (publish == "1") ? true : false;
}

function managePublishUser(action) {
	var xmlArr = {	"include" : "publishing/publishing.php",
					"function" : "xmlManagePublishUser",
					"action" : action};

	var rowIndexList = new Array();
	var chkBoxes = $('pubSearchDiv').getElementsByTagName('input');
	var j = 1;
	for(i=0;i<chkBoxes.length;i++) {
		if(chkBoxes[i].type == "checkbox" && chkBoxes[i].value != "all") {
			el = chkBoxes[i];
			if(el.checked) {
				xmlArr["userID-"+j] = el.value;
				if(action == "toggle") {
					toggle = ($('status-'+el.value).value == "active") ? "suspended" : "active"; 
					$('status-'+el.value).value = toggle;

					var statusSpan = $('statusSpan-'+el.value);
					removeElementsChildren(statusSpan);
					statusSpan.appendChild(document.createTextNode(toggle));

					xmlArr["userStatus-"+j] = toggle;
				} else if(action == "delete") {
					rowIndexList.push(el.value);
				} else if(action == "password") {
					var pubName = $('email-'+el.value).value;
					xmlArr["email-"+el.value] = pubName;
				}
				j++;
			}	
		}
	}
	postXML(xmlArr);

	for(i=0;i<rowIndexList.length;i++) {
		var rowIndex = $('pubUser-'+rowIndexList[i]).sectionRowIndex;
		$('pubUserManageTable').deleteRow(rowIndex);
	}
}

function removePublishSearch() {
	var xmlArr = {	"include" : "publishing/publishing.php",
					"function" : "xmlRemovePublishSearch" };

	var rowIndexList = new Array();
	var chkBoxes = $('pubSearchDiv').getElementsByTagName('input');
	var j = 1;
	for(i=0;i<chkBoxes.length;i++) {
		if(chkBoxes[i].type == "checkbox" && chkBoxes[i].value != "all") {
			el = chkBoxes[i];
			if(el.checked) {
				xmlArr["searchID-"+j] = el.value;
				rowIndexList.push(el.value);
				j++;
			}	
		}
	}
	postXML(xmlArr);

	for(i=0;i<rowIndexList.length;i++) {
		var rowIndex = $('pubSearch-'+rowIndexList[i]).sectionRowIndex;
		$('pubUserManageTable').deleteRow(rowIndex);
	}
}

function setMessage(XML) {
	var mess = XML.getElementsByTagName("MESSAGE");
	removeElementsChildren($('errorMsg'));

	var sp = document.createElement('span');
	sp.appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
	$('errorMsg').appendChild(sp);
}
