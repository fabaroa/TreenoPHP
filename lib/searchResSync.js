function createSearchResFolder() {
	var p = getXMLHTTP();
	var urlStr = '../lib/searchResSync.php?func=createSearchResFolder&cabinet=' + cabinet;
	p.open('GET', urlStr, true);
	p.send(null);
	p.onreadystatechange = function () {
		if (p.readyState == 4) {
			parent.searchPanel.document.getElementById('submitBtn').click();
		}
	};
}

function getUnSyncedFolders(sf) {
	var searchVal = "";
	if(sf) {
		var v = sf.toUpperCase();
		searchVal = parent.searchPanel.document.getElementById('field-'+v).value;
	} else {
		searchVal = getEl('syncFolder').value;
	}

	var p = getXMLHTTP();
	var urlStr = '../lib/searchResSync.php?func=getUnSyncedFolders&cabinet=' + cabinet +
		'&searchVal=' + searchVal;
	p.open('GET', urlStr, true);
	p.send(null);
	p.onreadystatechange = function () {
		if (p.readyState == 4) {
			displayCabinetFolders(p.responseXML);
		}
	};
}

function displayCabinetFolders(XML) {
	var inputType = "radio";
	var syncCab = XML.getElementsByTagName('sync_cabinet');	
	if(syncCab[0].firstChild.nodeValue == "1") {
		inputType = "checkbox";
	}

	clearDiv($('syncDiv'));
	$('syncDiv').style.height = '100%';

	var resArr = XML.getElementsByTagName('result_set');	
	if(resArr.length > 0) {
		for(var i=0;i<resArr.length;i++) {
			var cab = getXMLCabValue(resArr[i].getElementsByTagName('cabinet'));
			var cabDisp = getXMLCabValue(resArr[i].getElementsByTagName('display'));
			var cabHeader = getCabIndices(resArr[i].getElementsByTagName('indices'));

			var cabDiv = document.createElement('div');
			if(i > 0) {
				cabDiv.className = "cabSyncDiv";
			} else {
				cabDiv.className = "cabSyncDivTop";
			}
			$('syncDiv').appendChild(cabDiv);

			var cabTbl = document.createElement('table');
			cabTbl.className = "resTable";
			cabDiv.appendChild(cabTbl);

			var row = cabTbl.insertRow(cabTbl.rows.length);
			row.className = "tableHeader";

			var col = row.insertCell(row.cells.length);
			col.colSpan = cabHeader.length + 1;

			var sp = document.createElement('span');
			sp.appendChild(document.createTextNode(cabDisp));
			col.appendChild(sp);

			var row = cabTbl.insertRow(cabTbl.rows.length);
			row.className = "tableHeader";

			var col = row.insertCell(row.cells.length);
			col.style.width = "3%";

			for(j=0;j<cabHeader.length;j++) {
				var col = row.insertCell(row.cells.length);
				var sp = document.createElement('span');
				sp.appendChild(document.createTextNode(cabHeader[j]));
				col.appendChild(sp);
			}

			fRes = resArr[i].getElementsByTagName('folder');
			if(fRes.length > 0) {
				for(j=0;j<fRes.length;j++) {
					var doc_id = fRes[j].getAttribute('doc_id');
					var indVal = getFolderIndiceValues(fRes[j],cabHeader);

					var row = cabTbl.insertRow(cabTbl.rows.length);
					var col = row.insertCell(row.cells.length);
					col.style.textIndent = '0px';
					col.style.width = "3%";
					col.style.textAlign = "center";

					if(inputType == "checkbox") {
						var chkBox = document.createElement('input');
						chkBox.type = inputType;
						chkBox.name = "folder";
					} else {
						chkBox = createRadio('folder');
					}
					chkBox.value = "yes";
					chkBox.doc_id = doc_id;
					chkBox.cab = cab;
					col.appendChild(chkBox);

					if(inputType == "checkbox") {
						chkBox.checked = true;
					} else if(j == 0) {
						chkBox.checked = true;
					}

					for(k=0;k<indVal.length;k++) {
						var col = row.insertCell(row.cells.length);
						//if() {
							var sp = document.createElement('span');
							sp.appendChild(document.createTextNode(indVal[k]));
						//}
						col.appendChild(sp);
					}
				}
			}
		}
		$('syncBtn').style.visibility = 'visible';
		if(inputType == "checkbox") {
			$('syncAllDiv').style.visibility = 'visible';
		} else {
			$('syncAllDiv').style.visibility = 'hidden';
		}
		$('syncBtn').onclick = function () { syncFolders(inputType) };
	} else {

	}
	var divTop = $('syncDiv').offsetTop + 45;
	var divHeight = $('syncDiv').offsetHeight;
	var clienth = document.documentElement.clientHeight;

	if(divHeight > clienth) {
		$('syncDiv').style.height = (clienth - divTop)+'px';
	} else {
		$('syncDiv').style.height = divHeight+'px';
	}
}

function syncFolders(inputType) {
    var domDoc = createDOMDoc();
    var root = domDoc.createElement('searchResSync');
    domDoc.appendChild(root);

	var inpArr = $('syncDiv').getElementsByTagName('input');
	for(i=0;i<inpArr.length;i++) {
		if(inpArr[i].type == inputType) {
			if(inpArr[i].checked && inpArr[i].doc_id) {
				var folder = domDoc.createElement('update_set');
				root.appendChild(folder);

				k = domDoc.createElement('cabinet');
				k.appendChild(domDoc.createTextNode(inpArr[i].cab));
				folder.appendChild(k);
				
				v = domDoc.createElement('doc_id');
				v.appendChild(domDoc.createTextNode(inpArr[i].doc_id));
				folder.appendChild(v);
			}
		}
	}
    postStr = domToString(domDoc);

	var URL = "../lib/searchResSync.php?func=syncFolders&cabinet="+cabinet;
	var p = getXMLHTTP();
	p.open('POST', URL, true);
	p.send(postStr);
	p.onreadystatechange = function () {
		if (p.readyState == 4) {
			parent.searchPanel.document.getElementById('submitBtn').click();
		}
	};
}

function getXMLCabValue(el) {
	if(el.length > 0) {
		if(el[0].firstChild) {
			return el[0].firstChild.nodeValue;
		}
	}
}

function getCabIndices(el) {
	var cHeader = new Array();

	if(el.length > 0) {
		indArr = el[0].getElementsByTagName('index');
		if(indArr.length > 0) {
			for(i=0;i<indArr.length;i++) {
				if(indArr[i].firstChild) {
					cHeader[i] = indArr[i].firstChild.nodeValue;
				}
			}
		}
	}
	return cHeader;
}

function getFolderIndiceValues(folderEl, cabHeader) {
	var iVal = new Array();

	for(var i=0;i<cabHeader.length;i++) {
		myVal = folderEl.getElementsByTagName(cabHeader[i]);
		if(myVal.length > 0) {
			iVal[i] = "";
			if(myVal[0].firstChild) {
				iVal[i] = myVal[0].firstChild.nodeValue;
			}
		}
	}
	return iVal;
}

function reloadSearchRes(cab) {
	resetSearchPanel();
	window.location = "searchResults.php?cab="+cab;	
}

function toggleSearchResults() {
	var toggle = false;
	if($('syncAllBox').checked) {
		toggle = true;	
	}
	folderList = $('syncDiv').getElementsByTagName('input');
	if(folderList.length > 0) {
		for(i=0;i<folderList.length;i++) {
			if(folderList[i].type == "checkbox") {
				folderList[i].checked = toggle;
			}
		}
	}
}

function syncOnEnter(e,sf) {
    var evt = (e) ? e : event;
    var code = (evt.keyCode) ? evt.keyCode : evt.charCode;
    if(code == 13) {
        getUnSyncedFolders(sf);
    }
    return true;
}

function resetSearchPanel() {
	cabFields = parent.searchPanel.fieldsArr;
	for(i=0;i<cabFields.length;i++) {
		field = cabFields[i].toUpperCase();
		if(parent.searchPanel.document.getElementById('field-'+field).type == 'text') {
			parent.searchPanel.document.getElementById('field-'+field).value = "";
		} else {
			parent.searchPanel.document.getElementById('field-'+field).selectedIndex = 0;
		}
	}
}
