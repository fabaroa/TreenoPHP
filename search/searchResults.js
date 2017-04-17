// $Id: searchResults.js 14250 2011-01-14 20:56:04Z fabaroa $

var oldIndices = new Array();
var current = 0;
var bookURL = "";
var submitFunc;
var indexArr = new Array();
var dPage = 1;
var tPages = 1;
var tTable = "";
var sortDir = "DESC";
var sortField = "";
var ocr = false;

function setIndices(indiceStr) {
	indexArr = indiceStr.split(",");
}

function setSelected(rowID) {
  	if (selectedRow == "") {
    	selectedRow = rowID;
		if(row = getEl(rowID)){
	    	row.style.backgroundColor = "#8799e0";
		}
  	} else {
		if(row = getEl(selectedRow))
	    	row.style.backgroundColor = "#ebebeb";
    	if(row = getEl(rowID))
	    	row.style.backgroundColor = "#8799e0";
    	selectedRow = rowID;
  	}
}
function rowMouseover(rowID) {
  	if (selectedRow == rowID) //set color if mouseover is selected row
		getEl(rowID).style.backgroundColor = "#8779E0";
  	else // mouseover is not selected row
		getEl(rowID).style.backgroundColor = "#888888";
}
function rowMouseout(rowID) {
	if(selectedRow == rowID)
		getEl(rowID).style.backgroundColor = "#8799e0";
   	else
		getEl(rowID).style.backgroundColor = "#ebebeb";
}

function createTR( trID ) {
	var newTr = document.createElement("tr");
	newTr.id = trID;
	newTr.onclick = function() {setSelected(trID)};
	newTr.onmouseover = function() {rowMouseover(trID)};
	newTr.onmouseout = function() {rowMouseout(trID)};
	newTr.style.backgroundColor = "#ebebeb";
	
	return newTr;
}

function createFolderImg( cab,doc_id,index,topTerms ) {
	var folderImg = new Image();
    folderImg.src = "images/File.gif";
	folderImg.style.borderWidth = "0";
   	folderImg.alt = "File";
    folderImg.onclick = function(){integrityCheck(cab,doc_id,index,topTerms);
									setSelected(doc_id)};
	folderImg.width = 14;
	folderImg.height = 14;

	return folderImg;
}

function createEditImg() {
	var editImage = new Image();
	editImage.src = "images/file_edit_16.gif";
	editImage.style.borderWidth = "0";
	editImage.alt = "Edit Indices";
	editImage.width = 14;
	editImage.height = 14;
	
	return editImage;
}

function createPubImg(cab,doc_id) {
	var pubImg = new Image();
	pubImg.src = "../images/new_16.gif";
	pubImg.style.borderWidth = "0";
	pubImg.title = "Add folder to publishing";
    pubImg.onclick = function() {
								top.topMenuFrame.addItem(cab,doc_id);
								setSelected(doc_id);
								   };

	return pubImg
}

function createDeleteImg( cab, doc_id, index, topTerms, barcode ) {
	var deleteImg = new Image();
    deleteImg.src = "images/trash.gif";
    deleteImg.style.borderWidth = "0";
    deleteImg.alt = "Delete Folder";
    deleteImg.onclick = function() {
								askAdmin(doc_id,cab,index,topTerms,barcode);
								   };
	deleteImg.width = 14;
	deleteImg.height = 14;

	return deleteImg;
}

function createIndiceTD( doc_id, indiceName, indiceVal, cab, index, topTerms) {
	var newTd = document.createElement("td");
	newTd.id = indiceName+"-"+doc_id;
	var newSpan = document.createElement('span');
	newTd.appendChild(newSpan);
	newSpan.appendChild( document.createTextNode( indiceVal ) );
	newTd.onclick = function() {integrityCheck(cab,doc_id,index,topTerms)};	
	newTd.style.fontSize = "12px";
	newTd.style.paddingLeft = "3px";
	newTd.style.paddingRight = "3px";
	newTd.nowrap = "nowrap";

	return newTd;
}
function modifyResults( type, index ) {
	var resFound = getEl('resultsFound');
	var newResults = resFound.firstChild.nodeValue.split( " - " );

	if( resFound.firstChild.nodeValue == "There were no results found." ) {
		newResults[0] = 1;
	} else if( type == "decrement" ) {
		newResults[0] = Number(newResults[0]) - 1;
	} else {
		newResults[0] = Number(newResults[0]) + 1;
	}
	if( newResults[0] == 0 ) {
		var newRes = "There were no results found.";
		resFound.style.color = "#990000";
	} else if( resFound.firstChild.nodeValue == "There were no results found." ) {
		var newRes = "1 - Result Found";
		resFound.style.color = "#000000";
	}
	else
		var newRes = newResults.join( " - " );
	resFound.firstChild.nodeValue = newRes;
	var pageCount = getEl('top-pageCount');
	var resPerPage = document.results_form.results[document.results_form.results.selectedIndex].value;
	if( pageCount ) {
		var newCount = pageCount.firstChild.nodeValue.split( " " );
		var curPageCount = Number(newCount[newCount.length-1]);

		var newPageCount = Math.ceil( newResults[0] / resPerPage );
		if( newPageCount < curPageCount && newPageCount != 0 ) {
			if( newPageCount == 1 ) {
				arrows = getEl('table-top').style.visibility = 'hidden';
				arrows = getEl('table-bottom').style.visibility = 'hidden';
			}
		
			if( (newPageCount+1) == curPageCount && newPageCount == index )
				return 2;	
			else {	
				pageCount.firstChild.nodeValue = " of "+newPageCount;

				var pageCount = getEl('bottom-pageCount');
				pageCount.firstChild.nodeValue = " of "+newPageCount;
			}
		} else if( newPageCount > curPageCount ) {
			if( newPageCount == 2 ) {
				arrows = getEl('table-top').style.visibility = 'visible';
				arrows = getEl('table-bottom').style.visibility = 'visible';
			}
			pageCount.firstChild.nodeValue = " of "+newPageCount;

			var pageCount = getEl('bottom-pageCount');
			pageCount.firstChild.nodeValue = " of "+newPageCount;
		} else if( Number(index)+1 == newPageCount || newPageCount == 0 ) {
			modifyNowViewing( type, newResults[0] );	
		}
	}
	return 1;
}

function modifyNowViewing( type, numResults ) {
	var nowViewing = getEl('nowViewing');
	if( numResults > 0 ) {
		if( numResults == 1 ) {
			var newNowViewing = "Now Viewing: 1 - 1";
			getEl('searchButtons').style.visibility = "visible";
		} else {
			var message = nowViewing.firstChild.nodeValue.split( ": " );
			var results = message[1].split( " - " );
			if( type == "decrement" ) {
				var newNowViewing = message[0] + ": " + results[0] + " - " + ( Number(results[1]) - 1 );
			} else {
				var newNowViewing = message[0] + ": " + results[0] + " - " + ( Number(results[1]) + 1 );
			}
		}
		nowViewing.style.visibility = "visible";
	} else {
		var newNowViewing = "";
		nowViewing.style.visibility = "hidden";
		getEl('searchButtons').style.visibility = "hidden";
	}
	nowViewing.firstChild.nodeValue = newNowViewing;
}

function integrityCheck( cab, doc_id, page, topTerms ) {
	if(!documentView) {
		var URL = "../search/searchResultsAction.php?cab="+cab;
		URL += "&doc_id="+doc_id+"&table="+tempTable+"&integrityCheck=1";
		if (window.XMLHttpRequest) 
			var xmlhttp = new XMLHttpRequest();
		else if (window.ActiveXObject) 
			var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

		xmlhttp.open('POST', URL, true);
		xmlhttp.setRequestHeader('Content-Type',
								 'application/x-www-form-urlencoded');
		xmlhttp.send( null );
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4) {
				var getVars = '&topTerms='+topTerms+'&index='+page;
				var loc = xmlhttp.responseText.split('\n');	
				if( loc[0] == 1 ) {
					parent.sideFrame.window.location = loc[1]+getVars;
				} else if( loc[0] == 2 ) {
					parent.mainFrame.window.location = loc[1]+getVars;
				} else {
					top.window.location = '../logout.php';
				}
			}
		};
	} else {
		index = page;
		createDocumentView(cab,doc_id,page,topTerms);
	}
}

function deleteFolder( URL ) {
	if (window.XMLHttpRequest) 
		var xmlhttp = new XMLHttpRequest();
	else if (window.ActiveXObject) 
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

	xmlhttp.open('POST', 'deleteFolder.php', true);
    xmlhttp.setRequestHeader('Content-Type',
                             'application/x-www-form-urlencoded');
    xmlhttp.send( URL );

	return xmlhttp;
}

function editFolder( URL, postStr,cab,index ) {
	if (window.XMLHttpRequest) 
		var xmlhttp = new XMLHttpRequest();
    else if (window.ActiveXObject) 
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

	xmlhttp.open('POST', URL, true);
    xmlhttp.send(postStr);
    xmlhttp.onreadystatechange = function() { 
		if (xmlhttp.readyState == 4) {
			if( xmlhttp.responseText != "" ) {
				printMessage(xmlhttp.responseText);	
			} else {
				if(globalEdit) {
					var URL = 'searchResults.php?cab='+cab+'&table='+tempTable+'&index='+index;				
					window.location = URL;
				} else {
					printMessage('Folder successfully updated');
				}
			}
		}
	};
}

function createFolder( URL, postStr ) {
	if (window.XMLHttpRequest) 
		var xmlhttp = new XMLHttpRequest();
    else if (window.ActiveXObject) 
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	xmlhttp.open('POST', 'updateFolder.php?'+URL+'&parent=search', true);
    xmlhttp.setRequestHeader('Content-Type',
                             'application/x-www-form-urlencoded');
    xmlhttp.send(postStr);

	return xmlhttp;
}

function setFields(XML) {
	if(current != 0) {
		closeEditIndices( cabinet, current, topLevel, index );
	}

	var folder = getEl('newFolder-folder');
	var pub = getEl('newFolder-publishing');
	var edit = getEl('newFolder-edit');
	var del = getEl('newFolder-delete');
	var barcode = getEl('newFolder-barcode');

	submitFunc = function() { submitCreateNewFolder(cabinet,index,topLevel,delFold,barcode) };
	if(folder) {	
		folder.onclick = function() { return true; };
		clearDiv(folder);

		var saveImg = new Image();
		saveImg.src = "../energie/images/save.gif";
		saveImg.style.borderWidth = "0";
		saveImg.alt = "Save";
		saveImg.onclick = submitFunc;
		folder.appendChild(saveImg);
	}

	if(pub) {
		clearDiv(pub);
		var cancelImg = new Image();
		cancelImg.src = "../energie/images/cancl_16.gif";
		cancelImg.style.borderWidth = "0";
		cancelImg.alt = "Cancel";
		pub.align = "center";
		pub.appendChild(cancelImg);
		pub.onclick = function(){ closeCreateNewFolder(cabinet,index,topLevel,delFold) };
	} else {
		clearDiv(edit);
		var cancelImg = new Image();
		cancelImg.src = "../energie/images/cancl_16.gif";
		cancelImg.style.borderWidth = "0";
		cancelImg.alt = "Cancel";
		edit.align = "center";
		edit.appendChild(cancelImg);
		edit.onclick = function(){ closeCreateNewFolder(cabinet,index,topLevel,delFold) };
	}	

	var myDtds = new Object();
	var dtdList = XML.getElementsByTagName('DTYPE');
	var j = 0;
	for (var i = 0; i < dtdList.length; i++) {
		var myKey = dtdList[i].getAttribute('key');
		if (!myDtds[myKey]) {
			myDtds[myKey] = new Array();
			j = 0;
		}
		var myVal = '';
		if (dtdList[i].firstChild && dtdList[i].firstChild.nodeValue) {
			myVal = dtdList[i].firstChild.nodeValue;
		}
		myDtds[myKey][j] = myVal;
		j++;
	}

	fieldList = XML.getElementsByTagName('FIELD');
	if(fieldList.length > 0) {
		for(var i=0;i<fieldList.length;i++) {
			var fname = fieldList[i].firstChild.nodeValue;
			var indField = getEl('newFolder-'+fname);
			indField.onclick = function() { return true; };
			clearDiv(indField);

			var check = 0;
			var j = 0;
			if (myDtds[fname]) {
				var box = document.createElement('select');
				var opt = document.createElement ('option');
				box.appendChild (opt);
				for(j=0;j<myDtds[fname].length;j++) {
					var opt = document.createElement('option');
					opt.value = myDtds[fname][j];
					opt.appendChild(document.createTextNode(opt.value));
					box.appendChild(opt);	
				}
				box.validate = function(){return true;};
				indField.appendChild(box);
			} else {
				var box = document.createElement("input");
				box.type = "text";
				if(i == 0 && acTable != '') {
					box.onkeydown = autoCompKeyDown;
					box.cab = cabinet;
				} else {
					box.onkeypress = chgIndexOnEnter; 
				}
				indField.appendChild(box);

				if(dateFunctions && (fname.search(/date/i) != -1 || fname.search(/DOB/) != -1)) {
					setDateInput(box, indField);
				} else {
					box.validate = function(){return true;};
				}
			}
			box.id = "newFolder-"+fname+"-add";
			box.name = fname;
			box.ifValidRegex = 0;
			box.displayDiv = 'disp'+i;
			box.className = "cabinetIndex";
			var req = fieldList[i].getElementsByTagName('REQUIRED');
			if(req.length > 0) {
				if(req[0].firstChild) {
					if(parseInt(req[0].firstChild.nodeValue)) {
						box.style.backgroundColor = 'gold';
						box.required = 1;
					} else {
						box.required = 0;
					}
				}
			}
			var reg = fieldList[i].getElementsByTagName('REGEX');
			if(reg.length > 0) {
				if(reg[0].firstChild) {
					if(reg[0].firstChild.nodeValue != "DISABLED") {
						box.regex = reg[0].firstChild.nodeValue;
						box.onblur = function() { return check4ValidRegex(this) };
					}
				}
			}
			var disp = fieldList[i].getElementsByTagName('DISPLAY');
			if(disp.length > 0) {
				if(disp[0].firstChild) {
					if(disp[0].firstChild.nodeValue != "DISABLED") {
						display = disp[0].firstChild.nodeValue;	
						var sp = document.createElement('span');
						sp.id = 'disp'+i;
						sp.style.color = 'Maroon';
						sp.appendChild(document.createTextNode(display));
						indField.appendChild(sp);
					}
				}
			}
		}
		getEl('newFolder-'+fieldList[0].firstChild.nodeValue+'-add').focus();
	}
	var saveImg = new Image();
	saveImg.src = "../energie/images/save.gif";
	saveImg.style.borderWidth = "0";
	saveImg.style.paddingLeft = "5px";
	saveImg.alt = "Save";
	saveImg.onclick = submitFunc; 
	indField.appendChild(saveImg);
	current = -1;  
}

function openCreateNewFolder( cab, index, topTerms, delFolders ) {
	parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
	parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
	if( current > 0 ) {
		closeEditIndices( cab, current, topTerms, index );
	} else if( current < 0 ) {
		delFolders = 0;
		if( getEl('newFolder-delete') )
			delFolders = 1;
		closeCreateNewFolder(cab,index,topTerms,delFolders);
	}
	closeDocumentView(folderID);
	setSelected('createFolder');

	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(folderEl);

	createKeyAndValue(xmlDoc,folderEl,'include','lib/panelFuncs.php');
	createKeyAndValue(xmlDoc,folderEl,'function','getFields');
	createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
	postXML(domToString(xmlDoc));
}

function autoCompKeyDown(evt) {
	evt = (evt) ? evt : event;
	var charCode = (evt.charCode) ? evt.charCode :
    	((evt.which) ? evt.which : evt.keyCode);
	if (charCode == 13 || charCode == 3 || charCode == 9) {
		var searchTerm = this.value;
		if(searchTerm == '') {
			return;
		}
		var searchVar = this.name;
		var p = getXMLHTTP();
		urlStr = '../lib/settingsFuncs.php?func=searchAutoComplete&v1=' + escape(searchTerm);
		urlStr += '&v2=' + searchVar + '&v3=' + this.cab + '&v4=' + acTable;
		printMessage('Searching in Auto Complete Table...');
		p.open('GET', urlStr, true);
		p.send(null);
		p.onreadystatechange = function () {
			if(p.readyState == 4) {
				var xmlDoc = p.responseXML;
				printMessage('Search Completed');
				var results = xmlDoc.getElementsByTagName('res');
				var el, j, myVal;
				for(var i = 0; i < results.length; i++) {
					for(j = 0; el = getEl("newFolder-"+indexArr[j]+"-add"); j++) {
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
				if(results.length == 0) {
					needInsertAC = true;
				} else {
					needInsertAC = false;
				}
				for(var i = 1; input = getEl("newFolder-"+indexArr[i]+"-add"); i++) {
					if(input.select) {
						input.select();
						break;
					}
				}
			}
		};
	}
	return true;
}

function setDateInput(input, indField) {
	var newImg = document.createElement('img');
	newImg.src = '../images/edit_16.gif';
	newImg.style.cursor = 'pointer';
	newImg.style.verticalAlign = 'middle';
	newImg.input = input;
	newImg.onclick = dispCurrMonth;
	input.validate = validateDate;
	indField.style.whiteSpace = 'nowrap';
	indField.appendChild(newImg);
}

function closeCreateNewFolder( cab, index, topTerms, delFolders ) {
	var folder = getEl('newFolder-folder');
	var pub = getEl('newFolder-publishing');
	var edit = getEl('newFolder-edit');

	if( folder ) {	
		folder.onclick = function(){
								openCreateNewFolder(cab,index,
								topTerms,delFolders);
								   }
		while(folder.childNodes[0]) {
			folder.removeChild( folder.childNodes[0] );
		}
		var saveImg = new Image();
		saveImg.src = "../energie/images/new_folder.gif";
		saveImg.style.borderWidth = "0";
		saveImg.alt = "Create Folder";
		folder.appendChild( saveImg );
	}
	if(pub) {
		while(pub.childNodes[0]) {
			pub.removeChild( pub.childNodes[0] );
		}
		pub.onclick = function() {
								openCreateNewFolder(cab,index,
								topTerms,delFolders);
								 }
	
	} else {
		while(edit.childNodes[0]) {
			edit.removeChild( edit.childNodes[0] );
		}
		edit.onclick = function(){
								openCreateNewFolder(cab,index,
								topTerms,delFolders);
								 }
	}	
	
	for(i=0;i<(indexArr.length);i++) {
		var indField = getEl('newFolder-'+indexArr[i]);
		indField.onclick = function(){
								openCreateNewFolder(cab,index,
								topTerms,delFolders);
								 }
		if( i == 0 )
			var value = "Create New Folder";
		else
			var value = "";	
		while(indField.hasChildNodes()) {
			indField.removeChild( indField.firstChild );
		}
		indField.appendChild( document.createTextNode( value ) );
	}
	current = 0;
}

function checkFolder( cab, postStr ) {
	if (window.XMLHttpRequest) 
		var xmlhttp = new XMLHttpRequest();
    else if (window.ActiveXObject) 
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

	xmlhttp.open('POST', '../search/searchResultsAction.php?cab='+cab+'&checkFolder=1', true);
    xmlhttp.setRequestHeader('Content-type',
                             'application/x-www-form-urlencoded');
    xmlhttp.send(postStr);

	return xmlhttp;

}

function createXML(indList) {
    var domDoc = createDOMDoc();
    var root = domDoc.createElement('ROOT');
    domDoc.appendChild(root);
    for(var i=0;i<indList.length;i++) {
        var folder = domDoc.createElement('FOLDER');
        root.appendChild(folder);

        k = domDoc.createElement('KEY');
        k.appendChild(domDoc.createTextNode(indList[i].name));
        folder.appendChild(k);
		
        v = domDoc.createElement('VALUE');
        v.appendChild(domDoc.createTextNode(indList[i].value));
        folder.appendChild(v);
    }
    return domToString(domDoc);
}

function submitCreateNewFolder( cab, index, topTerms, delFolders, barcode ) {
    var check = false;
    var postStr = "";
    var postValue = "";
    var checkStr = "";

	var indList = document.getElementsByClassName('cabinetIndex');
	for(i=0;i<indList.length;i++) {
        if(!indList[i].validate()) {
            printMessage(indList[i].msg);
			return;
		}

		if(indList[i].required) {
			if(!indList[i].value) {
				indList[i].focus();
				printMessage('Please fill in all required fields');
				return;
			}
		}

		if(indList[i].regex) {
			check4ValidRegex(indList[i]);
			if(!indList[i].ifValidRegex) {
				indList[i].select();
				printMessage('Please fill in the proper format');
				return;
			}
		}
	}

	for(i=0;i<indList.length;i++) {
        indList[i].onkeypress = function() {return true};
	}

    postStr = createXML(indList);
    xmlhttp = checkFolder( cab, postStr )
    xmlhttp.onreadystatechange = function() {
        if(xmlhttp.readyState == 4) {
            if(xmlhttp.responseText > 0) {
                var message = "This Folder Already Exists. Do you wish to add duplicate?";
                var answer = window.confirm( message );
                if(answer == true) {
                    check = true;
				}
            } else {
                check = true;
            }

            if( check == true ) {
    var URL = "cab="+cab+"&table="+tempTable;
    if(needInsertAC) {
        URL += "&needInsertAC=1";
        needInsertAC = false;
    }
    xmlhttp = createFolder( URL, postStr );
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
			var XML = xmlhttp.responseXML;
			var mess = XML.getElementsByTagName('MESSAGE');
			if(mess.length > 0) {
				printMessage(mess[0].firstChild.nodeValue);
				if(mess[0].getAttribute('return') == "1") {
					closeCreateNewFolder(cab,index,
                    topTerms,delFolders);
				} else if(mess[0].getAttribute('return') == "3") {
					var docID = mess[0].getAttribute('doc_id');
					tempTable = mess[0].getAttribute('temp_table');
					modifyResults( "increment", index );
					newTr = createTR( docID );

					var tableElement = getEl('createFolder').parentNode;
					newTd = document.createElement("td");

					folderImg = createFolderImg( cab,docID,index,topTerms );
					newTd.id = "folder-"+docID;
					newTd.appendChild( folderImg );
					newTd.align = "center";
					newTr.appendChild( newTd );

					if(publishFolder) {
						newTd = document.createElement("td");
						pubImg = createPubImg(cab,docID );
						newTd.id = "publishing-"+docID;
						newTd.appendChild( pubImg );
						newTd.align = "center";
						newTr.appendChild( newTd );
					}

					newTd = document.createElement("td");
					newTd.id = "edit-"+docID;
					editImage = createEditImg();
					newTd.onclick = function(){openEditIndices(cab,docID,topTerms,index)};
					newTd.appendChild( editImage );
					newTd.align = "center";
					newTr.appendChild(newTd);

					if( delFolders > 0 ) {
						newTd = document.createElement("td");
						deleteImg = createDeleteImg(cab,docID,index,topTerms,barcode);
						newTd.appendChild( deleteImg );
						newTd.align = "center";
						newTr.appendChild(newTd);
					}
					if( barcode ) {
						newTd = document.createElement("td");
						newTd.id = "barcode-"+docID;
						newTd.align = "center";
						var barcodeImg = new Image();
						barcodeImg.src = "../images/barcode.gif";
						barcodeImg.style.borderWidth = "0";
						barcodeImg.alt = "Get Barcode";
						barcodeImg.onclick = function(){boolBC = false;
														printDocutronBarcode(cab,docID)};
						newTd.appendChild( barcodeImg );
						newTr.appendChild( newTd );
					}
					for(i=0;i<(indexArr.length);i++) {
						postValue = getEl('newFolder-'+indexArr[i]+'-add' ).value;
						newTd = createIndiceTD( docID, indexArr[i], postValue, cab, index, topTerms );
						newTr.appendChild(newTd);
					}

					var Node = getEl('createFolder').nextSibling;
					tableElement.insertBefore( newTr, Node );

					var resPerPage = document.results_form.results[document.results_form.results.selectedIndex].value;
					var nowViewing = getEl('nowViewing');
					var message = nowViewing.firstChild.nodeValue.split( ":" );
					var results = message[1].split( "-" );

					if( ( Number(results[0]) + Number(resPerPage) ) < ( Number(results[1]) + 1 ) ) {
						var elementType = tableElement.lastChild.nodeType;
						var elementNode = tableElement.lastChild;
						while( elementType != 1 ) {
							elementType = elementNode.previousSibling.nodeType;
							elementNode = elementNode.previousSibling;
						}
						tableElement.removeChild( elementNode );
					}
					closeCreateNewFolder(cab,index,
						topTerms,delFolders);
					}
					integrityCheck(cab,docID,index,topTerms);
					setSelected(docID);
			}
        }
    };
        }
    }
    };
}

function printMessage( text ) {
	var message = getEl("sortmsg");
	message.firstChild.data = text;
	message.style.color = "#990000";
	message.style.visibility = "visible";
}
function askAdmin(id,cab,index,topTerms,barcode) {
	URL = "../search/searchResultsAction.php?cab="+cab+"&doc_id="+id+"&fileCount=1";
	if (window.XMLHttpRequest)
		var xmlhttp = new XMLHttpRequest();
    else if (window.ActiveXObject)
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                                                                                                                             
    xmlhttp.open('POST', URL, true);
    xmlhttp.send( null );
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
			message = xmlhttp.responseText;
			answer = window.confirm(message);
			if(answer == true) {
				deleteEntireFolder(cab,id,index,topTerms,barcode);
			}
		}
    };
                                                                                                                             
}

function deleteEntireFolder( cab, doc_id, index, topTerms,barcode ) {
	var selected = parent.mainFrame.selectedRow;
	if( selected == doc_id ) {
		parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
		parent.sideFrame.window.location = '../energie/left_blue_search.php';
		parent.viewFileActions.window.location = '../energie/bottom_white.php';
	}

	var URL = "cab="+cab+"&doc_id="+doc_id+"&temp="+tempTable;
	URL += "&index="+index;
	xmlhttp = deleteFolder( URL );

	tableElement = getEl(doc_id).parentNode;
	trElement = getEl(doc_id);
	tableElement.removeChild( trElement );
	closeDocumentView(folderID);

	var getStr = "searchResults.php?cab="+cab+"&table="+tempTable;
	getStr += "&topTerms="+topTerms+"&index="+(index - 1);
	var reload = modifyResults( "decrement", index );

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
			if( xmlhttp.responseText != "" ) {
				var postArr = new Array();
				postArr = xmlhttp.responseText.split( "\t" );
				if(postArr.length > 1) {
					newTr = createTR( postArr[0] );
					tableElement.appendChild( newTr );

					newTd = document.createElement("td");
					newTd.id = "folder-"+postArr[0];
					folderImg = createFolderImg( cab,postArr[0],index,topTerms );
					newTd.appendChild( folderImg );
					newTd.align = "center";
					newTr.appendChild(newTd);
					
					if(publishFolder) {
						newTd = document.createElement("td");
						pubImg = createPubImg(cab,postArr[0] );
						newTd.id = "publishing-"+postArr[0];
						newTd.appendChild( pubImg );
						newTd.align = "center";
						newTr.appendChild( newTd );
					}

					if(canEditFolder) {
						newTd = document.createElement("td");
						newTd.id = "edit-"+postArr[0];		
						editImage = createEditImg();
						newTd.onclick = function(){openEditIndices(cab,postArr[0],topTerms,index);
													setSelected(postArr[0])};
						newTd.appendChild( editImage );
						newTd.align = "center";
						newTr.appendChild(newTd);
					}
					newTd = document.createElement("td");
					newTd.id = "delete-"+postArr[0];
					deleteImg = createDeleteImg(cab,postArr[0],index,topTerms,barcode);
					newTd.appendChild( deleteImg );
					newTd.align = "center";
					newTr.appendChild(newTd);

					if(barcode == 1) {
						newTd = document.createElement("td");
						newTd.id = "barcode-"+postArr[0];		
						newTd.align = "center";
						var barcodeImg = new Image();
						barcodeImg.src = "../images/barcode.gif";
						barcodeImg.style.borderWidth = "0";
						barcodeImg.alt = "Get Barcode";
						barcodeImg.onclick = function(){boolBC = false;
														printDocutronBarcode(cab,postArr[0]);
														setSelected(postArr[0])};
						newTd.appendChild( barcodeImg );
						newTr.appendChild( newTd );
					}
		
					for(i=1;i<(postArr.length)-1;i++) {
						newTd = createIndiceTD( postArr[0], indexArr[i-1], postArr[i], cab, index, topTerms );
						newTr.appendChild(newTd);
					}
				}
        	}
			printMessage( "Folder deleted successfully" );
			if( reload == 2 )
				navArrowsEnd( getStr );
		}
    };
}

function allowDigi(evt) {
  	evt = (evt) ? evt : event;
  	var charCode = (evt.charCode) ? evt.charCode : 
		((evt.which) ? evt.which : evt.keyCode);
  	if (((charCode >= 48 && charCode <= 57) // is digit
		|| charCode == 13 || charCode == 8) || (charCode == 37) || (charCode == 39)) { // is enter or backspace key   
    	return true;
  	} else // non-digit
    	return false;
}

function navArrowsUp(getStr) { 
  	document.onload = parent.mainFrame.window.location=getStr;
}
function navArrowsDown(getStr) {          
  	document.onload = parent.mainFrame.window.location=getStr;
}
function navArrowsBegin(getStr) {          
  	document.onload = parent.mainFrame.window.location=getStr;
}
function navArrowsEnd(getStr) {
  	document.onload = parent.mainFrame.window.location = getStr;
}
function inOrder(getStr) {
	parent.mainFrame.window.location = getStr;
}

function showSort(name, sortDir) {
	var sortMsg = document.getElementById('sortmsg');
	sortMsg.style.color = "#666666";
	if(sortDir == 'ASC')
		sortMsg.firstChild.data = 'Sort by ' + name + ', Ascending';
	else
		sortMsg.firstChild.data = 'Sort by ' + name + ', Descending';
	sortMsg.style.visibility = 'visible';
}

function removeSort() {
	document.getElementById('sortmsg').style.visibility = 'hidden';
}

function chgIndexOnEnter(evt) {
    evt = (evt) ? evt : event;
    var charCode = (evt.charCode) ? evt.charCode :
        ((evt.which) ? evt.which : evt.keyCode);
    if (charCode == 13 || charCode == 3) {
        submitFunc();
    }
   	return true;
}

function bookmarkswitchback( URL ) {
	var bookmark = getEl('booknamespot');
	while(bookmark.childNodes[0]) {
		bookmark.removeChild( bookmark.childNodes[0] );
	}
	
	var bookImg = new Image();
    bookImg.src = "../images/paste_16.gif";
    bookImg.style.borderWidth = "0";
    bookImg.alt = "Bookmark This Search";
    bookImg.title = "Bookmark This Search";
    bookImg.onclick = function(){bookmarkswitch(URL)};
    bookmark.appendChild(bookImg);
}

function bookmarkswitch( URL ) {
	var bookmark = getEl('booknamespot');
	while(bookmark.childNodes[0]) {
		bookmark.removeChild( bookmark.childNodes[0] );
	}
	bookURL = URL;
	var inputElement = document.createElement("input");
	inputElement.type = "text";
	inputElement.id = "booktxt";
	inputElement.size = 15;
	inputElement.name = "newBookmarkName";
	inputElement.value = "Bookmark Name";
	inputElement.onkeypress = checkEnter;
	bookmark.appendChild(inputElement);
	getEl('booktxt').focus();
	getEl('booktxt').select();

	var cancelImg = new Image();
	cancelImg.src = "../energie/images/cancl_16.gif";
	cancelImg.style.borderWidth = "0";
	cancelImg.style.paddingRight = "2px";
    cancelImg.alt = "Cancel";
	cancelImg.title = "Cancel Bookmark";
	cancelImg.onclick = function(){bookmarkswitchback( URL )};
	bookmark.appendChild(cancelImg);

	var saveImg = new Image();
	saveImg.src = "../energie/images/save.gif";
	saveImg.style.borderWidth = "0";
    saveImg.alt = "Save";
	saveImg.title = "Save Bookmark";
	saveImg.onclick = function(){submitBookmark( URL )};
	bookmark.appendChild(saveImg);
}

function checkEnter(evt) {
    evt = (evt) ? evt : event;
    var charCode = (evt.charCode) ? evt.charCode :
        ((evt.which) ? evt.which : evt.keyCode);

    if (charCode == 13 || charCode == 3) 
   		submitBookmark( bookURL );
   	return true;
}

function submitBookmark( URL ) {
	var bookSearch = getEl('booktxt').value;
	URL += "&bookmarkValue="+bookSearch;

	if (window.XMLHttpRequest) 
		var xmlhttp = new XMLHttpRequest();
    else if (window.ActiveXObject) 
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

	xmlhttp.open('POST', URL, true);
    xmlhttp.setRequestHeader('Content-Type',
                             'application/x-www-form-urlencoded');
    xmlhttp.send( null );
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
			var message = xmlhttp.responseText.split("-");
			if(top.searchPanel.loadBookmarks) {
				top.searchPanel.loadBookmarks(message[0]);
			}
			printMessage(message[1]);
			bookmarkswitchback( URL );
		}
	};
}

function submitAction( URL, type ) {
	if (type=='investor'){
		var selectedIndex = document.investor_form.investor.selectedIndex;
		var investor = document.investor_form.investor[selectedIndex].value;
		top.leftFrame1.location = URL + "&" + type + "=" + investor;
	} else {
		top.leftFrame1.location = URL + "&" + type + "=1";
	}
}

function changeResPerPage() {
	var selectedIndex = document.results_form.results.selectedIndex;
	var urlStr = document.results_form.results[selectedIndex].value;
	window.location = urlStr;
}

function openEditIndices( cab, doc_id, topTerms, index ) {
	parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
	parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
	if( current > 0 ) {
		closeEditIndices( cab, current, topTerms, index );
	} else if( current < 0 ) {
		delFolders = 0;
		if( getEl('newFolder-delete') )
			delFolders = 1;
		closeCreateNewFolder(cab,index,topTerms,delFolders);
	}
	closeDocumentView(folderID);
	
    URL = "../search/searchResultsAction.php?cab="+cab+"&dataType=1";
	if (window.XMLHttpRequest)
		var xmlhttp = new XMLHttpRequest();
    else if (window.ActiveXObject)
		var xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                                                                                                                             
    xmlhttp.open('POST', URL, true);
    xmlhttp.send( null );
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4) {
			var indiceDefs = new Array();
			indiceDefs = xmlhttp.responseText.split( '\n' );

			//adds the form action
			var URL = "../secure/indexEdit.php";
			submitFunc = function(){
							var postStr = setNewIndices(cab,doc_id,topTerms,index);
							if(postStr) {
								editFolder(URL,postStr,cab,index);
							}
						}

	var folder = getEl('folder-'+doc_id);
	var pub = getEl('publishing-'+doc_id);
	var edit = getEl('edit-'+doc_id);
	var del = getEl('delete-'+doc_id);
	var barcode = getEl('barcode-'+doc_id);

	if( folder ) {
		folder.onclick = function() { return true; };
		while(folder.childNodes[0]) {
			folder.removeChild( folder.childNodes[0] );
		}
		var saveImg = new Image();
		saveImg.src = "../energie/images/save.gif";
		saveImg.style.borderWidth = "0";
		saveImg.alt = "Save";
		saveImg.onclick = function(){ submitFunc() };
		folder.appendChild( saveImg );
	}

	while(edit.childNodes[0]) {
		edit.removeChild( edit.childNodes[0] );
	}
	if(pub) {
		while(pub.childNodes[0]) {
			pub.removeChild( pub.childNodes[0] );
		}
		var cancelImg = new Image();
		cancelImg.src = "../energie/images/cancl_16.gif";
		cancelImg.style.borderWidth = "0";
		cancelImg.alt = "Cancel";
		pub.onclick = function(){ closeEditIndices(cab,doc_id,topTerms,index)};
		pub.appendChild( cancelImg );
	} else {
		var cancelImg = new Image();
		cancelImg.src = "../energie/images/cancl_16.gif";
		cancelImg.style.borderWidth = "0";
		cancelImg.alt = "Cancel";
		edit.onclick = function(){ closeEditIndices(cab,doc_id,topTerms,index)};
		edit.appendChild( cancelImg );
	}	
	if( del ) {
		while(del.childNodes[0]) {
			del.removeChild( del.childNodes[0] );
		}
	}
	if( barcode ) {
		while(barcode.childNodes[0]) {
			barcode.removeChild( barcode.childNodes[0] );
		}
	}

		//keep data that is there but put value in text field
		for(var i=0;i<indexArr.length;i++) {
			var tmp = indexArr[i];
			var check = 0;
			var newIndex = getEl(tmp+"-"+doc_id);
			newIndex.onclick = function() { return true; };
			var v = '';
			var mySpans = newIndex.getElementsByTagName('span');
			if(mySpans.length > 0) {
			if( mySpans[0].firstChild ) {
				if(mySpans[0].firstChild.nodeValue) {
					var val = mySpans[0].firstChild.nodeValue;
					v = val.replace(/^\s+/, '' ).replace(/\s+$/, '' );
				}
				mySpans[0].removeChild( mySpans[0].firstChild );
			}
			}
			oldIndices[i] = v;

			var j = 0;
			while( j < indiceDefs.length ) {
				var defsArr = new Array();
				defsArr = indiceDefs[j].split('\t');

				if( defsArr[0] == tmp ) {
					j = indiceDefs.length;
					check = 1;
				}
				else
					j++;
			}

			if( check == 1 )
				mySpans[0].appendChild( createSelect( tmp+"-"+doc_id+"-edit", defsArr[1], v ) );
			else {
				var newInput = document.createElement("input");
				newInput.type = "text";
				newInput.id = tmp+"-"+doc_id+"-edit";
				newInput.value = v;
				newInput.name = tmp;
				newInput.onkeypress = chgIndexOnEnter; 
				mySpans[0].appendChild( newInput );
				if(dateFunctions && (indexArr[i].search(/date/i) != -1 || indexArr[i].search(/DOB/) != -1)) {
					setDateInput(newInput, mySpans[0]);
				} else {
					newInput.validate = function(){return true;};
				}
			}
			v = "";
		}
		var saveImg = new Image();
		saveImg.src = "../energie/images/save.gif";
		saveImg.style.borderWidth = "0";
		saveImg.style.paddingLeft = "5px";
		saveImg.alt = "Save";
		saveImg.onclick = function(){ submitFunc() };
		mySpans[0].appendChild( saveImg );
		current = doc_id;
	}
	};
}

function createSelect( idName, values, curValue ) {
	var selectBox = document.createElement( "select" );
	selectBox.id = idName;
	var valArr = new Array();
	valArr = values.split( ",,," );
	var index = -1;
	if(curValue == null) {
		curValue = '';
	}
	for(var i=0;i<valArr.length;i++) {
		if( valArr[i] == curValue )
			index = i;
	}
	if( index >= 0 )
		valArr.splice(index,1);
		
	valArr.splice(0,0,curValue);
	for(var i=0;i<valArr.length;i++) {
		var sOption = document.createElement( "option" );
		sOption.value = valArr[i];
		sOption.appendChild( document.createTextNode(valArr[i]) );
		selectBox.appendChild( sOption );
	}
	selectBox.validate = function(){return true;};
	return( selectBox );	
}

function closeEditIndices( cab, doc_id, topTerms, index ) {
	//creates the innerHTML when clicking the edit folder
	var folder = getEl('folder-'+doc_id);
	var pub = getEl('publishing-'+doc_id);
	var edit = getEl('edit-'+doc_id);
	var del = getEl('delete-'+doc_id);
	var barcode = getEl('barcode-'+doc_id);
 
	if( folder ) {
		while(folder.childNodes[0]) {
			folder.removeChild( folder.childNodes[0] );
		}
		folderImg = createFolderImg( cab,doc_id,index,topTerms );
		folder.appendChild( folderImg );
	}

	if(pub) {
		while(pub.childNodes[0]) {
			pub.removeChild( pub.childNodes[0] );
		}
		editImage = createPubImg(cab,doc_id);
		pub.onclick = function(){openEditIndices(cab,doc_id,topTerms,index);setSelected(doc_id)};
		pub.appendChild( editImage );
	} else {
		while(edit.childNodes[0]) {
			edit.removeChild( edit.childNodes[0] );
		}
	}	
	editImage = createEditImg();
	edit.onclick = function(){openEditIndices(cab,doc_id,topTerms,index);setSelected(doc_id)};
	edit.appendChild( editImage );

	if( del ) {
		while(del.childNodes[0]) {
			del.removeChild( del.childNodes[0] );
		}
		deleteImg = createDeleteImg(cab,doc_id,index,topTerms,barcode);
		del.appendChild( deleteImg );
	}
	if( barcode ) {
		while(barcode.childNodes[0]) {
			barcode.removeChild( barcode.childNodes[0] );
		}
		var barcodeImg = new Image();
		barcodeImg.src = "../images/barcode.gif";
		barcodeImg.style.borderWidth = "0";
		barcodeImg.alt = "Get Barcode";
		barcodeImg.onclick = function(){boolBC = false;
										printDocutronBarcode(cab,doc_id);setSelected(doc_id)};
		barcode.appendChild( barcodeImg );
	}
	//keep data that is there but put value in text field
	for(i=0;i<indexArr.length;i++) {
		var tmp = indexArr[i];
		var newIndex = getEl( tmp+"-"+doc_id );
		newIndex.onclick = function(){integrityCheck(cab,doc_id,index,topTerms);setSelected(doc_id)};
		var value = oldIndices[i];
		var spans = newIndex.getElementsByTagName('span');
		if(spans.length > 0) {
			while(spans[0].hasChildNodes()) {
				spans[0].removeChild( spans[0].firstChild );	
			}
			spans[0].appendChild( document.createTextNode( value ) );
		}
	}
	current = 0;
}

function setNewIndices( cab, doc_id, topTerms, index ) {
	for(var i = 0; i < indexArr.length; i++) {
		if(!getEl(indexArr[i] + '-' + doc_id + '-edit').validate()) {
			printMessage(getEl(indexArr[i] + '-' + doc_id + '-edit').msg);
			return;
		}
	}
	var domDoc = createDOMDoc();
	var folderEl = domDoc.createElement('FOLDER');
	domDoc.appendChild(folderEl);
	var cabEl = domDoc.createElement('CABINET');
	cabEl.setAttribute('name',cab);
	folderEl.appendChild(cabEl);
	
	var docidEl = domDoc.createElement('DOCID');
	docidEl.setAttribute('id',doc_id);
	folderEl.appendChild(docidEl);
	for(var i=0;i<indexArr.length;i++) {
		var tmp = indexArr[i];
		var newIndex = getEl(tmp+"-"+doc_id+"-edit");
		var fieldEl = domDoc.createElement('FIELD');

		fieldEl.appendChild(domDoc.createTextNode(newIndex.value));
		fieldEl.setAttribute('name',tmp);
		fieldEl.setAttribute('orig_value',oldIndices[i]);
		folderEl.appendChild(fieldEl);
		oldIndices[i] = newIndex.value;
	}
	var postStr = domToString(domDoc);
	closeEditIndices( cab, doc_id, topTerms, index )
	return postStr;
}

function closeDocumentView(doc_id) {
	var check = false;
	if(prevSelected == doc_id) {
		check = true;
	}
	
	if(docRow = getEl('documentView-'+doc_id)) {
		docRow.parentNode.removeChild(docRow);
	}
}

function createDocumentView(cab,doc_id,index,topTerms) {
	closeDocumentView(folderID);
	folderID = doc_id;
	if(prevSelected != folderID) {
		prevSelected = folderID;
		var outertbl = getEl('folderResults');
		var rArr = outertbl.rows;
		for(var i=0;i<rArr.length;i++) {
			if(rArr[i].id == folderID) {
				var index = i + 1;
				break;
			}
		}

		if(getEl('documentView-'+folderID)) {
			var outerRow = getEl('documentView-'+folderID);
			colNum = outerRow.cells.length;
			clearDiv(outerRow);
		} else {
			var outerRow = outertbl.insertRow(index);
			outerRow.id = 'documentView-'+folderID;
			//outerRow.style.display = 'none';
			colNum = (outertbl.rows[0].cells.length - 1);
		}
		var col = outerRow.insertCell(outerRow.cells.length);
		var col = outerRow.insertCell(outerRow.cells.length);
		col.colSpan = colNum;

		var divEl = document.createElement('div');
		divEl.id = 'documentViewDiv-'+folderID;
		divEl.style.width= '100%';
		divEl.style.height = '100%';
		col.appendChild(divEl);

		var div1 = document.createElement('div');
		div1.id = 'loadingMessage';
		div1.style.width= '100%';
		div1.style.backgroundColor = '#6A78AF';
		div1.style.borderBottomStyle = 'solid';
		div1.style.borderBottomColor = '#FFFFFF';
		div1.style.borderBottomWidth = '1px';
		div1.appendChild(document.createTextNode('Loading documents....please wait'));
		divEl.appendChild(div1);
		
		var xmlDoc = createDOMDoc();
		var folderEl = xmlDoc.createElement('FOLDER');
		xmlDoc.appendChild(folderEl);
		createKeyAndValue(xmlDoc,folderEl,'function','xmlGetFolderDocuments');
		createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
		createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
		if(fid) { 
			createKeyAndValue(xmlDoc,folderEl,'tab_id',fid);
		}
		createKeyAndValue(xmlDoc,folderEl,'filter','All');
		postXML(domToString(xmlDoc));
	} else {
		prevSelected = "";
	}
}

function selectFilter() {
	var doc = this.options[this.selectedIndex].value;
	
	if(getEl('loadingMessage')) {
		getEl('loadingMessage').parentNode.removeChild(getEl('loadingMessage'));
	}

	if(getEl('showAllDocuments-'+folderID)) {
		getEl('showAllDocuments-'+folderID).parentNode.removeChild(getEl('showAllDocuments-'+folderID));
	}

	if(doc != 'DEFAULT') {
		createDocFilterSearch();
	} else {
		if(getEl('docFilterSearch')) {
			getEl('docFilterSearch').parentNode.removeChild(getEl('docFilterSearch'));
			getEl('docFilterSearchSpan').parentNode.removeChild(getEl('docFilterSearchSpan'));
			getEl('docFilterSearchButton').parentNode.removeChild(getEl('docFilterSearchButton'));
			if(getEl('fullTextChkBox')) {
				getEl('fullTextChkBox').parentNode.removeChild(getEl('fullTextChkBox'));
				getEl('fullTextSpan').parentNode.removeChild(getEl('fullTextSpan'));
			}
		}
	}
	
		if(getEl('sortDocSel')) {
			getEl('sortDocSel').parentNode.removeChild(getEl('sortDocSel'));
			getEl('sortDocSpan').parentNode.removeChild(getEl('sortDocSpan'));
			getEl('docSortDir').parentNode.removeChild(getEl('docSortDir'));
		}

	var div1 = document.createElement('div');
	div1.id = 'loadingMessage';
	div1.style.width= '100%';
	div1.style.backgroundColor = '#6A78AF';
	div1.style.borderBottomStyle = 'solid';
	div1.style.borderBottomColor = '#FFFFFF';
	div1.style.borderBottomWidth = '1px';
	div1.style.color = 'white';
	div1.appendChild(document.createTextNode('Loading documents....please wait'));
	getEl('docFilterDiv').appendChild(div1);

	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('FOLDER');
	xmlDoc.appendChild(folderEl);
	createKeyAndValue(xmlDoc,folderEl,'function','xmlGetFolderDocuments');
	createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
	createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
	if(fid) {
		createKeyAndValue(xmlDoc,folderEl,'tab_id',fid);
	}
	createKeyAndValue(xmlDoc,folderEl,'filter',doc);
	postXML(domToString(xmlDoc));
}

function createDocFilterSearch() {
	if(getEl('docFilterSearch')) {
		getEl('docFilterSearch').parentNode.removeChild(getEl('docFilterSearch'));
		getEl('docFilterSearchSpan').parentNode.removeChild(getEl('docFilterSearchSpan'));
		getEl('docFilterSearchButton').parentNode.removeChild(getEl('docFilterSearchButton'));
		if(getEl('fullTextChkBox')) {
			getEl('fullTextChkBox').parentNode.removeChild(getEl('fullTextChkBox'));
			getEl('fullTextSpan').parentNode.removeChild(getEl('fullTextSpan'));
		}
	}

	var sp = document.createElement('span');
	sp.id = "docFilterSearchSpan";
	sp.style.paddingLeft = '10px';
	sp.style.paddingRight = '2px';
	sp.style.color = 'white';
	sp.appendChild(document.createTextNode('Search'));
	getEl('docFilterDiv').appendChild(sp);

	var txt = document.createElement('input');
	txt.type = 'text';
	txt.id = 'docFilterSearch';
	txt.name = 'docFilterSearch';
	txt.onkeypress = onDocFilterEnter;
	getEl('docFilterDiv').appendChild(txt);

	var b1 = document.createElement('input');
	b1.type = "button";
	b1.id = 'docFilterSearchButton';
	b1.name = "GO";
	b1.value = "GO";
	b1.onclick = searchDocFilter;
	getEl('docFilterDiv').appendChild(b1);

	if(ocr) {
		var chkBox = document.createElement("input");
		chkBox.type = "checkbox";
		chkBox.id = "fullTextChkBox";
		chkBox.value = "1";
		getEl('docFilterDiv').appendChild(chkBox);

		var sp = document.createElement('span');
		sp.id = "fullTextSpan";
		sp.style.color = 'white';
		sp.appendChild(document.createTextNode('Full-Text Only'));
		getEl('docFilterDiv').appendChild(sp);
	}
}

function searchDocFilter(sDir) {
	if(getEl('showAllDocuments-'+folderID)) {
		getEl('showAllDocuments-'+folderID).parentNode.removeChild(getEl('showAllDocuments-'+folderID));
	}

	var search = getEl('docFilterSearch').value;
		var div1 = document.createElement('div');
		div1.id = 'loadingMessage';
		div1.style.width= '100%';
		div1.style.backgroundColor = '#6A78AF';
		div1.style.borderBottomStyle = 'solid';
		div1.style.borderBottomColor = '#FFFFFF';
		div1.style.borderBottomWidth = '1px';
		div1.style.color = 'white';
		div1.appendChild(document.createTextNode('Loading documents....please wait'));
		getEl('docFilterDiv').appendChild(div1);

		var filter = getEl('docFilterSel').value;

		var xmlDoc = createDOMDoc();
		var folderEl = xmlDoc.createElement('FOLDER');
		xmlDoc.appendChild(folderEl);

		createKeyAndValue(xmlDoc,folderEl,'function','xmlGetFolderDocuments');
		createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
		createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
		createKeyAndValue(xmlDoc,folderEl,'filter',filter);
		if(fid) {
			createKeyAndValue(xmlDoc,folderEl,'tab_id',fid);
		}
		if(search) {
			createKeyAndValue(xmlDoc,folderEl,'search',search);
		}

		if(getEl('fullTextChkBox') && getEl('fullTextChkBox').checked) {
			createKeyAndValue(xmlDoc,folderEl,'fullTextSearch','1');
		}

		if(sDir) {
			createKeyAndValue(xmlDoc,folderEl,'sortDir',sDir);
			createKeyAndValue(xmlDoc,folderEl,'sortBy',getEl('sortDocSel').value);
		}
		postXML(domToString(xmlDoc));
}

function createDocFilter(pDiv,docElements) {
	if(!getEl('docFilterDiv')) {
		var fDiv = document.createElement('div');
		fDiv.id = 'docFilterDiv';
		fDiv.style.backgroundColor = '#6A78AF';
		fDiv.style.borderBottomStyle = 'solid';
		fDiv.style.borderBottomColor = '#FFFFFF';
		fDiv.style.borderBottomWidth = '1px';

		var sp = document.createElement('span');
		sp.style.color = 'white';
		sp.style.paddingLeft = '2px';
		sp.style.paddingRight = '2px';
		sp.appendChild(document.createTextNode('Filter By'));
		fDiv.appendChild(sp);

		var docSel = document.createElement('select');
		docSel.id = 'docFilterSel';
		docSel.onchange = selectFilter;
		var opt = document.createElement('option');
		opt.value = 'All';
		opt.appendChild(document.createTextNode('All'));
		docSel.appendChild(opt);

		if(docElements) {
			for(i=0;i<docElements.length;i++) {
				var n = docElements[i].firstChild.nodeValue;
				var t = docElements[i].getAttribute('table');

				var opt = document.createElement('option');
				opt.value = t;
				opt.appendChild(document.createTextNode(n));
				docSel.appendChild(opt);
			}
		}
		fDiv.appendChild(docSel);
		pDiv.appendChild(fDiv);

		createDocFilterSearch();
	}
}

function setFolderDocuments(XML) {
	getEl('loadingMessage').parentNode.removeChild(getEl('loadingMessage'));

	docTypeList = document.getElementsByClassName('docType');
	if(docTypeList.length > 0) {
		for(i=0;i<docTypeList.length;i++) {
			getEl('documentViewDiv-'+folderID).removeChild(docTypeList[i]);
		}
	}

	var rt = XML.getElementsByTagName('ENTRY');
	var divEl = getEl('documentViewDiv-'+folderID);
	if(rt.length == 1) {
		if(rt[0].getAttribute('add') == "1") {
			createNewDocTable(divEl);
		}
	
		var editDoc = false;
		if(rt[0].getAttribute('edit') == "1") {
			editDoc = true;
		}

		var deleteDoc = false;
		if(rt[0].getAttribute('delete') == "1") {
			deleteDoc = true;
		}

		ocr = false;
		if(rt[0].getAttribute('ocr') == "1") {
			ocr = true;
		}
	}
	
	var d = XML.getElementsByTagName('DOCUMENT');
	if(d.length > 0) {
		createDocFilter(divEl,XML.getElementsByTagName('DOCLIST'));	
		
		var dCt = rt[0].getAttribute('document_count');
		tTable = rt[0].getAttribute('tempTable');
		tPages = Math.ceil(dCt/25);
		var len = d.length;
		if(dCt > 25) {
			var div1 = document.createElement('div');
			div1.id = 'showAllDocuments-'+folderID;
			div1.style.width= '100%';
			div1.style.color= 'white';
			div1.style.backgroundColor = '#6A78AF';
			div1.style.borderBottomStyle = 'solid';
			div1.style.borderBottomColor = '#FFFFFF';
			div1.style.borderBottomWidth = '1px';

			var img = document.createElement('img');
			img.style.paddingLeft = '2px';
			img.style.paddingRight = '2px';
			img.style.cursor = 'pointer';
			img.style.verticalAlign = 'middle';
			img.src = "../energie/images/begin_button.gif";
			img.title = 'First';
			img.alt = 'First';
			img.width = '16';
			img.height = '16';
			img.onclick = function() {	prevSelected = "";
										dPage = 1;
										pageDocuments() };
			div1.appendChild(img);

			var img = document.createElement('img');
			img.style.paddingLeft = '2px';
			img.style.paddingRight = '2px';
			img.style.cursor = 'pointer';
			img.style.verticalAlign = 'middle';
			img.src = "../energie/images/back_button.gif";
			img.title = 'Previous';
			img.alt = 'Previous';
			img.width = '16';
			img.height = '16';
			img.onclick = function() {	prevSelected = "";
										dPage--;
										pageDocuments() };
			div1.appendChild(img);

			var inp = document.createElement('input');
			inp.type = 'text';
			inp.id = 'newPage';
			inp.style.height = '12px';
			inp.style.fontSize = '9pt';
			inp.size = '2';
			inp.value = '1';
            inp.onkeypress = pageDocuments2;
			div1.appendChild(inp);
/*
                    <input id="pageNum"
                        type="hidden"
                        totalPages="<?php echo $ct; ?>"
                        value="<?php echo $page; ?>"
                    />
*/
			var sp = document.createElement('span');
			sp.id = 'pageDetail';
			sp.style.verticalAlign = 'middle';
			sp.style.fontSize = '9pt';
			sp.appendChild(document.createTextNode(' of '+tPages));
			div1.appendChild(sp);

			var img = document.createElement('img');
			img.style.paddingLeft = '2px';
			img.style.paddingRight = '2px';
			img.style.cursor = 'pointer';
			img.style.verticalAlign = 'middle';
			img.src = "../energie/images/next_button.gif";
			img.title = 'Next';
			img.alt = 'Next';
			img.width = '16';
			img.height = '16';
			img.onclick = function() {	prevSelected = "";
										dPage++;
										pageDocuments() };
			div1.appendChild(img);

			var img = document.createElement('img');
			img.style.paddingLeft = '2px';
			img.style.paddingRight = '2px';
			img.style.cursor = 'pointer';
			img.style.verticalAlign = 'middle';
			img.src = "../energie/images/end_button.gif";
			img.title = 'Last';
			img.alt = 'Last';
			img.width = '16';
			img.height = '16';
			img.onclick = function() {	prevSelected = "";
										dPage = tPages;
										pageDocuments() };
			div1.appendChild(img);
			divEl.appendChild(div1);
		}

		for(var i=0;i<len;i++) {
			var file_id = d[i].getAttribute('id');
			var date_created = d[i].getAttribute('date_created');
			var fieldList = d[i].getElementsByTagName('FIELD');
			if(fieldList.length > 0) {
				addDocumentToView(fieldList,d[i].getAttribute('name'),file_id,editDoc,deleteDoc,0,date_created);
			}
		}
		if(d.length == 1) {
			openDocument(file_id);
		} else if(fid) {
			openDocument(d[0].getAttribute('id'));	
		} else {
			closeRightFrame();
		}

		if(!getEl('sortDocSel')) {
			var df = XML.getElementsByTagName('DOC_FIELD');
			if(df.length > 0) {
				var selBox = document.createElement('select');
				selBox.id = "sortDocSel";
				selBox.onchange = selectSort;

				var opt = document.createElement("option");
				opt.value = "__default";
				opt.appendChild(document.createTextNode("Choose One"));
				selBox.appendChild(opt);
				for(var j=0;j<df.length;j++) {
					var fid = df[j].getAttribute('fid');
					
					var opt = document.createElement('option');
					opt.value = fid;
					opt.appendChild(document.createTextNode(df[j].firstChild.nodeValue));
					selBox.appendChild(opt);
				}
				var sp = document.createElement('span');
				sp.id = "sortDocSpan";
				sp.style.color = 'white';
				sp.style.paddingLeft = '5px';
				sp.style.paddingRight = '2px';
				sp.appendChild(document.createTextNode('Sort By'));
				getEl('docFilterDiv').appendChild(sp);
				getEl('docFilterDiv').appendChild(selBox);

				var img = document.createElement("img");
				img.id = "docSortDir";
				img.src = "../images/down.gif";
				img.onclick = sortDocuments;
				getEl('docFilterDiv').appendChild(img);
			}	
		}
	} else {
		if(!getEl('noDocuments-'+folderID)) {
			var div1 = document.createElement('div');
			div1.id = 'noDocuments-'+folderID;
			div1.style.width= '100%';
			div1.style.backgroundColor = '#6A78AF';
			div1.style.borderBottomStyle = 'solid';
			div1.style.borderBottomColor = '#FFFFFF';
			div1.style.borderBottomWidth = '1px';
			div1.appendChild(document.createTextNode('There are no documents in folder'));
			divEl.appendChild(div1);
			closeRightFrame();
		}
	}	
}

function selectSort() {
	removeDefault(getEl('sortDocSel'));
	searchDocFilter(sortDir);
}

function sortDocuments() {
	if(sortDir == "DESC") {
		sortDir = "ASC";
		searchDocFilter(sortDir);
		getEl('docSortDir').src = "../images/up.gif";
	} else {
		sortDir = "DESC";
		searchDocFilter(sortDir);
		getEl('docSortDir').src = "../images/down.gif";
	}
}

function pageDocuments2(e) {
    var evt = (e) ? e : event;
    var code = (evt.keyCode) ? evt.keyCode : evt.charCode;
    var pool = "1234567890";
    if(code == 13) {
		dPage = getEl('newPage').value;
       	pageDocuments(); 
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

function pageDocuments() {
	var vPage = dPage;
	if(dPage > tPages) {
		vPage = tPages;	
	} else if(dPage < 1) {
		vPage = 1;
	}
	dPage = vPage;
	getEl('newPage').value = dPage;
	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('FOLDER');
	xmlDoc.appendChild(folderEl);
	createKeyAndValue(xmlDoc,folderEl,'function','xmlPageFolderDocuments');
	createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
	createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
	createKeyAndValue(xmlDoc,folderEl,'page',vPage);
	createKeyAndValue(xmlDoc,folderEl,'tempTable',tTable);
	if(getEl('sortDocSel') && getEl('sortDocSel').value != "__default") {
		createKeyAndValue(xmlDoc,folderEl,'sortDir',sortDir);
	}
	postXML(domToString(xmlDoc));
}

function setDocumentPage(XML) {
	clearDocuments();
	var rt = XML.getElementsByTagName('ENTRY');
	if(rt.length == 1) {
		var editDoc = false;
		if(rt[0].getAttribute('edit') == "1") {
			editDoc = true;
		}

		var deleteDoc = false;
		if(rt[0].getAttribute('delete') == "1") {
			deleteDoc = true;
		}
	}
	var d = XML.getElementsByTagName('DOCUMENT');
	if(d.length > 0) {
		for(var i=0;i<d.length;i++) {
			var file_id = d[i].getAttribute('id');
			var date_created = d[i].getAttribute('date_created');
			var fieldList = d[i].getElementsByTagName('FIELD');
			if(fieldList.length > 0) {
				addDocumentToView(fieldList,d[i].getAttribute('name'),file_id,editDoc,deleteDoc,0,date_created);
			}
		}
	}
}

function clearDocuments() {
	var dList = document.getElementsByClassName('docType');
	if(dList.length > 0) {
		for(var i=0;i<dList.length;i++) {
			dList[i].parentNode.removeChild(dList[i]);	
		}
	}
}

function closeRightFrame() {
	top.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
}

function addFolderDocument(XML) {
	var d = XML.getElementsByTagName('ENTRY');
	docType = d[0].getAttribute('doc_type');
	docTable = d[0].getAttribute('doc_table');
	file_id = d[0].getAttribute('folderID');
	prepend = d[0].getAttribute('prepend');
	if(d[0].getAttribute('date_created')) {
		var date_created = d[0].getAttribute('date_created');
	}
	if(d.length > 0) {
		docExists = false;
		if(getEl('docFilterSel')) {
			var optList = getEl('docFilterSel').options;
			for(i=0;i<optList.length;i++) {
				if(optList[i].value == docTable) {
					docExists = true;	
					break;
				}
			}
		}

		if(getEl('docFilterSel') && !docExists) {
			var opt = document.createElement('option');
			opt.value = docTable;
			opt.appendChild(document.createTextNode(docType));
			getEl('docFilterSel').appendChild(opt);
		}

		var editDoc = false;
		if(d[0].getAttribute('edit') == "1") {
			editDoc = true;
		}

		var deleteDoc = false;
		if(d[0].getAttribute('delete') == "1") {
			deleteDoc = true;
		}
		for(var i=0;i<d.length;i++) {
			var fieldList = d[i].getElementsByTagName('FIELD');
			if(fieldList.length > 0) {
				addDocumentToView(fieldList,docType,file_id,editDoc,deleteDoc,prepend,date_created,docTable);
				openDocument(file_id);
			}
		}
	}	
}

function addDocumentToView(fieldList,docType,file_id, editDoc,deleteDoc,prepend,date_created,docTable) {
	if(el = getEl('noDocuments-'+folderID)) {
		el.parentNode.removeChild(el);
	}

	var docDiv = getEl('documentViewDiv-'+folderID);
	if(!getEl('docFilterSel')) {
		createDocFilter(docDiv);	
		var opt = document.createElement('option');
		opt.value = docTable;
		opt.appendChild(document.createTextNode(docType));
		getEl('docFilterSel').appendChild(opt);
	}

	var divEl = document.createElement('div');
	divEl.id = file_id+"-"+folderID;
	divEl.style.borderBottom = '1px solid #000000';
	divEl.style.width= '100%';
	divEl.style.height = '100%';
	divEl.style.backgroundColor = '#D0D4E0';
	divEl.style.fontSize = '12px';
	divEl.className = 'docType';
	divEl.onmouseover = function () {this.style.backgroundColor = '#91AEC9'};
	divEl.onmouseout = function () {	if(documentOpened != file_id) {
											this.style.backgroundColor = '#D0D4E0';
										} 
									};
	if(prepend) {
		if(getEl('showAllDocuments-'+folderID)) { 
			docDiv.insertBefore(divEl,docDiv.childNodes[3]);
		} else if(docDiv.childNodes[2]) {
			docDiv.insertBefore(divEl,docDiv.childNodes[2]);
		} else {
			docDiv.appendChild(divEl);
		}
	} else {
		docDiv.appendChild(divEl);
	}

	printDocumentActions(divEl,docType,file_id,editDoc,deleteDoc);
	var tblDiv = document.createElement('div');
	tblDiv.style.borderLeft = '1px solid #6A78AF';
	tblDiv.style.marginLeft = "9%";
	tblDiv.style.width = '89%';
	divEl.appendChild(tblDiv);
	
	var tbl = document.createElement('table');
	tbl.id = file_id+'-table-'+folderID;
	tbl.style.width = '99%';
	tbl.style.marginTop = "0px";
	tbl.style.marginBottom = "0px";
	tbl.style.marginLeft = "auto";
	tbl.style.marginRight = "auto";

	tbl.onclick = function() {openDocument(file_id)};
	tblDiv.appendChild(tbl);

	var row = tbl.insertRow(tbl.rows.length);
	var col = row.insertCell(row.cells.length);
	col.onclick = function() {openDocument(file_id)};
	col.appendChild(document.createTextNode('Document Type'));
	var col = row.insertCell(row.cells.length);
	col.id = file_id+'-docType';
	col.onclick = function() {openDocument(file_id)};
	col.appendChild(document.createTextNode(docType));

	if(date_created) {
		var sp = document.createElement('span');
		sp.style.paddingLeft = "20px";
		sp.style.fontStyle = "italic";
		sp.appendChild(document.createTextNode("created: "+date_created));
		col.appendChild(sp);
	}
	for(var j=0;j<fieldList.length;j++) {
		var name = fieldList[j].getAttribute('name');
		var value = "";
		if(fieldList[j].firstChild) {
			if(fieldList[j].firstChild.nodeValue) {
				value = fieldList[j].firstChild.nodeValue;
			}
		}
		
		var row = tbl.insertRow(tbl.rows.length);
		var col = row.insertCell(row.cells.length);
		col.style.whiteSpace = 'nowrap';
		col.style.width = '150px';
		col.appendChild(document.createTextNode(name));

		var col = row.insertCell(row.cells.length);
		col.id = file_id+'-'+name;
		col.appendChild(document.createTextNode(value));
	}
	
	divEl.style.display = 'block';
}

function printDocumentActions(outerDiv,docType,file_id,editDoc,deleteDoc) {
	var divEl1 = document.createElement('div');
	divEl1.id = file_id+'-actions';
	if(document.all) {
		divEl1.style.styleFloat = "left";
	} else {
		divEl1.style.cssFloat = "left";
	}
	divEl1.style.width = '8%';
	divEl1.style.overflow = 'hidden';
	divEl1.style.whiteSpace = 'noWrap';
	divEl1.style.textAlign = 'center';
	outerDiv.appendChild(divEl1);

	var tblDiv = document.createElement('div');
	tblDiv.id = file_id+'-modify';
	tblDiv.style.paddingTop = '5px';
	tblDiv.style.marginRight = 'auto';
	tblDiv.style.marginLeft = 'auto';
	tblDiv.style.textAlign = 'center';
	divEl1.appendChild(tblDiv);

	var tbl = document.createElement('table');
	tblDiv.appendChild(tbl);

	var row = tbl.insertRow(tbl.rows.length);
	var col = row.insertCell(row.cells.length);
	col.id = "publishingID-"+file_id;
	col.style.textAlign = 'center';
	if(publishDocument) {
		if(docType != 'DEFAULT') {
			var pubImg = new Image();
			pubImg.src = "../images/new_16.gif";
			pubImg.style.borderWidth = "0";
			pubImg.alt = "Add document to publishing";
			pubImg.title = "Add document to Publishing";
			pubImg.onclick = function () { top.topMenuFrame.addItem(cabinet,folderID,file_id) };
			col.appendChild(pubImg);
		}
	}

	var col = row.insertCell(row.cells.length);
	col.id = "editTD-"+file_id;
	col.style.textAlign = 'center';
	if(editDoc && file_id != -1) {
		var editImg = new Image();
		editImg.className = 'no';
		editImg.id = file_id+'-edit';
		editImg.src = "../energie/images/file_edit_16.gif";
		editImg.style.borderWidth = "0";
		editImg.alt = "Edit";
		editImg.title = "Edit";
		editImg.width = 14;
		editImg.onclick = function () {	editDocumentBool = true;
										subfolderID = file_id;
										documentType = docType;
										if(docType != 'DEFAULT') {
											editDocument(file_id);
										} else {
											getDocTypes(file_id);								
										}
									  };
		col.appendChild(editImg);
	}

	var col = row.insertCell(row.cells.length);
	col.id = "deleteTD-"+file_id;
	col.style.textAlign = 'center';

	if(deleteDoc && file_id != -1) {
		var deleteImg = new Image();
		deleteImg.id = file_id+'-delete';
		deleteImg.src = "../energie/images/trash.gif";
		deleteImg.style.borderWidth = "0";
		deleteImg.alt = "Delete";
		deleteImg.title = "Delete";
		deleteImg.width = 14;
		if(delDoc) {
			deleteImg.onclick = function() { deleteDocument(file_id)};
		} else {
			deleteImg.onclick = function() {};
			deleteImg.style.visibility = 'hidden';
		}
		col.appendChild(deleteImg);
	}

	var divEl2 = document.createElement('div');
	divEl2.id = file_id+'-detail';
	divEl2.style.width = '100%';
	divEl2.style.textAlign = "center";
	divEl1.appendChild(divEl2);
}

function openDocument(tab_id) {
	if(!editDocumentBool) {
		if(getEl(documentOpened+'-'+folderID)) {
			getEl(documentOpened+'-'+folderID).style.backgroundColor = "#D0D4E0";
		}
		documentOpened = tab_id;
		var URL = '../documents/viewDocuments.php?cab='+cabinet+'&doc_id='
				+folderID+'&table='+tempTable+'&index='+index+'&tab_id='+tab_id;
		top.sideFrame.window.location = URL; 
		parent.document.getElementById('rightFrame').setAttribute('rows', '*,0');
		getEl(tab_id+'-'+folderID).style.backgroundColor = "#91AEC9";
		parent.viewFileActions.window.location = '../energie/bottom_white.php';
	}
}

function editDocument(file_id) {
	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('FOLDER');
	xmlDoc.appendChild(folderEl);
	createKeyAndValue(xmlDoc,folderEl,'function','getDocumentFieldsAndValue');
	createKeyAndValue(xmlDoc,folderEl,'subfolderID',file_id);
	createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
	createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
	postXML(domToString(xmlDoc));
}

function getDocTypes(file_id) {
	cancelNewDocument();
	if(prevSelectedFileID) {
		if(prevDocumentType != "DEFAULT") {
			cancelEditDocumentFields(prevSelectedIndices,prevSelectedFileID);
		} else {
			cancelDefaultDocument(prevSelectedFileID);
		}
	}
	prevSelectedFileID = file_id;
	prevDocumentType = documentType;
	currentSubfolder = getEl(file_id+'-subfolder').firstChild.nodeValue;

	var dType = getEl(file_id+'-docType');
	if(dType) {
		clearDiv(dType);
	}
	var selBox = document.createElement('select');
	selBox.id = 'docTypeSelect-'+folderID;
	selBox.onchange = selectDocumentType;
	addDefault(selBox);
	dType.appendChild(selBox);

	var eImg = getEl(file_id+'-edit');
	eImg.src = '../energie/images/save.gif';
	eImg.title = "Save";
	eImg.onclick = saveNewDocument;
	
	if(dImg = getEl(file_id+'-delete')) {
		dImg.src = '../energie/images/cancl_16.gif';
		dImg.title = "Cancel";
		dImg.onclick = function() {cancelDefaultDocument(file_id)};
		dImg.style.visibility = 'visible';
		dImg.style.width = '14px';
	} else {
		var deleteTD = getEl('deleteTD-'+file_id);
        var cImg = new Image();
        cImg.id = file_id+'-delete';
        cImg.src = '../energie/images/cancl_16.gif';
        cImg.alt = "Cancel";
        cImg.title = "Cancel";
        cImg.width = 14;
        cImg.onclick = function () {cancelDefaultDocument(file_id)};
        cImg.style.borderWidth = "0";
        cImg.style.visibility = 'visible';
        deleteTD.appendChild(cImg);
	}

	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('FOLDER');
	xmlDoc.appendChild(folderEl);
	createKeyAndValue(xmlDoc,folderEl,'function','xmlGetDocumentTypes');
	createKeyAndValue(xmlDoc,folderEl,'cab',cabinet);
	postXML(domToString(xmlDoc));
}

function cancelDefaultDocument(file_id) {
	var eImg = getEl(file_id+'-edit');
	eImg.src = '../energie/images/file_edit_16.gif';
	eImg.title = "Edit";
	eImg.onclick = function () {
							editDocumentBool = true;
							subfolderID = file_id;
							documentType = "DEFAULT";
							getDocTypes(file_id) };
	
	if(dImg = getEl(file_id+'-delete')) {
		dImg.src = '../energie/images/trash.gif';
		dImg.title = "Delete";
		if(delDoc) {
			dImg.onclick = function () {deleteDocument(file_id)};
		} else {
			dImg.onclick = function () {};
			dImg.style.visibility = 'hidden';
			dImg.style.width = '0px';
		}
	}

	var dType = getEl(file_id+'-docType');
	if(dType) {
		clearDiv(dType);
	}
	dType.appendChild(document.createTextNode('DEFAULT'));

	var tbl = getEl(file_id+'-table-'+folderID);
	while(tbl.rows.length > 1) {
		tbl.deleteRow(1);
	}

	var row = tbl.insertRow(tbl.rows.length);
	var name = 'subfolder'; 
	var col = row.insertCell(row.cells.length);
	col.style.width = '150px';
	col.appendChild(document.createTextNode(name));

	var value = currentSubfolder;
	var col = row.insertCell(row.cells.length);
	col.id = file_id+'-subfolder';
	col.appendChild(document.createTextNode(value));

	prevSelectedFileID = "";
	prevSelectedIndices = "";
	editDocumentBool = false;
	prevDocumentType = "";
	currentSubfolder = "";
	subfolderID = "";
}

function deleteDocument(file_id) {
	var answer = true;
	ruSure = "Are you sure you want to delete this document?";
	answer = window.confirm( ruSure );
			
	if(answer) {
		var xmlDoc = createDOMDoc();
		var folderEl = xmlDoc.createElement('FOLDER');
		xmlDoc.appendChild(folderEl);
		createKeyAndValue(xmlDoc,folderEl,'function','deleteDocument');
		createKeyAndValue(xmlDoc,folderEl,'subfolderID',file_id);
		createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
		createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
		postXML(domToString(xmlDoc));

		getEl(file_id+'-'+folderID).parentNode.removeChild(getEl(file_id+'-'+folderID));
	}
}

function displayAllInformation(file_id) {
	var tbl = getEl(file_id+'-table-'+folderID);
	if(getEl(file_id+'-down')) {
		var h = tbl.rows[tbl.rows.length-1].offsetHeight + tbl.rows[tbl.rows.length-1].offsetTop;
		getEl(file_id+'-'+folderID).style.height = h+'px';
		getEl(file_id+'-actions').style.height = (h-2)+'px';
		getEl(file_id+'-modify').style.height = '22px';
		getEl(file_id+'-detail').style.height = (h-22)+'px';

		getEl(file_id+'-down').style.display = 'none';	
		getEl(file_id+'-up').style.display = 'block';	
	}
}

function hideInformation(file_id) {
	var tbl = getEl(file_id+'-table-'+folderID);
	if(getEl(file_id+'-down')) {
		getEl(file_id+'-down').style.display = 'block';	
		getEl(file_id+'-up').style.display = 'none';	

		var h = tbl.rows[2].offsetHeight + tbl.rows[2].offsetTop;
		getEl(file_id+'-'+folderID).style.height = h+'px';
	}
}

function createNewDocTable(div) {
	if(!getEl('createDocumentDiv')) {
		var divEl = document.createElement('div');
		divEl.id = 'createDocumentDiv';
		divEl.style.width= '100%';
		divEl.style.backgroundColor = '#6A78AF';
		divEl.style.borderBottomStyle = 'solid';
		divEl.style.borderBottomColor = '#000000';
		divEl.style.borderBottomWidth = '1px';
		divEl.style.fontSize = "12px";
		div.appendChild(divEl);

		var newDocTbl = document.createElement('table');
		newDocTbl.id = 'newDocTable-'+folderID;
		newDocTbl.style.width= '100%';
		divEl.appendChild(newDocTbl);

		var row = newDocTbl.insertRow(newDocTbl.rows.length);
		row.id = 'createDocument-'+folderID;
		row.onclick = openNewDocument;
		var col = row.insertCell(row.cells.length);
		col.appendChild(document.createTextNode('Create New Document'));
	}
}	

function openNewDocument() {
	closeRightFrame();
	var row = getEl('createDocument-'+folderID);
	clearDiv(row);

	if(prevSelectedFileID) {
		if(prevDocumentType != "DEFAULT") {
			cancelEditDocumentFields(prevSelectedIndices,prevSelectedFileID);
		} else {
			cancelDefaultDocument(prevSelectedFileID);
		}
	}

	row.onclick = function () {};

	var col = row.insertCell(row.cells.length);
	col.style.textAlign = 'center';
	var saveImg = new Image();
	saveImg.src = "../energie/images/save.gif";
	saveImg.style.borderWidth = "0";
	saveImg.alt = "Save";
	saveImg.title = "Save";
	saveImg.width = 16;
	saveImg.onclick = saveNewDocument;
	col.style.width = "25px";
	col.appendChild(saveImg);

	var col = row.insertCell(row.cells.length);
	col.style.textAlign = 'center';
	var cancelImg = new Image();
	cancelImg.src = "../energie/images/cancl_16.gif";
	cancelImg.style.borderWidth = "0";
	cancelImg.alt = "Cancel";
	cancelImg.title = "Cancel";
	cancelImg.width = 16;
	cancelImg.onclick = cancelNewDocument;
	col.style.width = "25px";
	col.appendChild(cancelImg);

	var col = row.insertCell(row.cells.length);
	col.style.width = '150px';
	col.style.fontSize = '12px';
	col.appendChild(document.createTextNode('Document Type'));
	
	var col = row.insertCell(row.cells.length);
	var selBox = document.createElement('select');
	selBox.id = 'docTypeSelect-'+folderID;
	selBox.onchange = selectDocumentType;
	addDefault(selBox);

	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('FOLDER');
	xmlDoc.appendChild(folderEl);

	createKeyAndValue(xmlDoc,folderEl,'function','xmlGetDocumentTypes');
	createKeyAndValue(xmlDoc,folderEl,'cab',cabinet);
	postXML(domToString(xmlDoc));

	col.appendChild(selBox);
}

function cancelNewDocument() {
	if(tbl = getEl('newDocTable-'+folderID)) {
		if(tbl.rows.length > 0) {
			while(tbl.rows.length > 0) {
				tbl.deleteRow(0);
			}
			var row = tbl.insertRow(tbl.rows.length);
			row.id = 'createDocument-'+folderID;
			row.onclick = openNewDocument;

			var col = row.insertCell(row.cells.length);
			col.appendChild(document.createTextNode('Create New Document'));
		}
	}
}

function saveNewDocument() {
	if(getEl('docTypeSelect-'+folderID).value != "__default") {
		var xmlDoc = createDOMDoc();
		var folderEl = xmlDoc.createElement('FOLDER');
		xmlDoc.appendChild(folderEl);

		createKeyAndValue(xmlDoc,folderEl,'function','xmlAddDocumentToCabinet');
		createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
		createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
		if(subfolderID) {
			createKeyAndValue(xmlDoc,folderEl,'subfolderID',subfolderID);
			var tbl = getEl(subfolderID+'-table-'+folderID);
		} else {
			var tbl = getEl('newDocTable-'+folderID);
		}

		var docType = getEl('docTypeSelect-'+folderID).value;
		createKeyAndValue(xmlDoc,folderEl,'document_table_name',docType);

		createKeyAndValue(xmlDoc,folderEl,'field_count',tbl.rows.length-1);
		for(var i=0;i<tbl.rows.length-1;i++) {
			var val = "";
			var key = "";
			var fid = 'field'+i+'-'+folderID;

			if(getEl(fid).type == "text") {
				val = getEl(fid).value;	
				key = getEl(fid).name;
			} else {
				var sBox = getEl(fid);
				val = sBox.options[sBox.selectedIndex].value;
				key = sBox.name;
			}
			if(getEl(fid).required) {
				if(!val) {
					getEl(fid).focus();
					printMessage('Please fill in all required fields');
					return;
				}
			}

			if(getEl(fid).regex) {
				if(!getEl(fid).ifValidRegex) {
					if((getEl(fid).required) || (!getEl(fid).required && val)) {
						getEl(fid).select();
						printMessage('Please fill in the proper format');
						return;
					}
				}
			}
			createKeyAndValue(xmlDoc,folderEl,'key'+i,key);
			createKeyAndValue(xmlDoc,folderEl,'field'+i,val);
		}
		
		postXML(domToString(xmlDoc));
		if(subfolderID) {
			var divEl = getEl(subfolderID+'-'+folderID);
			divEl.parentNode.removeChild(divEl);
			editDocumentBool = false;
		} else {
			cancelNewDocument();
		}
		subfolderID = "";
	} else {
		printMessage('Must select a document type');
	}
}

function fillDocumentTypeDropDown(XML) {
	var d = XML.getElementsByTagName('DOCUMENT');
	var selBox = getEl('docTypeSelect-'+folderID);
	if(d.length > 0) {
		for(var i=0;i<d.length;i++) {
			var opt = document.createElement('option');
			opt.value = d[i].getAttribute('name');
			opt.appendChild(document.createTextNode(d[i].firstChild.nodeValue));
			selBox.appendChild(opt);
			if(d.length == 1) {
				opt.selected = true;
				selectDocumentType();
			}
		}
	}
}

function editDocumentTypeFields(XML) {
	var ind = XML.getElementsByTagName('FIELD');
	cancelNewDocument();
	if(prevSelectedFileID) {
		if(prevDocumentType != "DEFAULT") {
			cancelEditDocumentFields(prevSelectedIndices,prevSelectedFileID);
		} else {
			cancelDefaultDocument(prevSelectedFileID);
		}
	}
	prevSelectedIndices = ind;
	prevSelectedFileID = subfolderID;
	prevDocumentType = documentType;
	if(ind.length > 0) {
		for(var i=0;i<ind.length;i++) {
			var col = getEl(subfolderID+'-'+ind[i].getAttribute('name'));
			if(col) {
				clearDiv(col);
			}

			var defsList = ind[i].getElementsByTagName('DEFINITION');
			if(defsList.length > 0) { 
				var box = document.createElement('select');
				box.id = "editField"+i+"-"+folderID;
				box.name = ind[i].getAttribute('name');

				var opt = document.createElement('option');
				opt.value = ""; 
				opt.appendChild(document.createTextNode(""));
				box.appendChild(opt);
				for(j=0;j<defsList.length;j++) {
					var def = defsList[j].firstChild.nodeValue;
					var opt = document.createElement('option');
					opt.value = def; 
					opt.appendChild(document.createTextNode(def));
					if(ind[i].firstChild) {
						if(ind[i].firstChild.nodeValue == def) {
							opt.selected = true;
						}
					}
					box.appendChild(opt);
				}
			} else {
				var box = document.createElement('input');
				box.type = 'text';
				box.name = ind[i].getAttribute('name');
				if(ind[i].firstChild) {
					var v = "";
					if(ind[i].firstChild.nodeValue) {
						box.value = ind[i].firstChild.nodeValue;
					}
				}
				box.id = 'editField'+i+'-'+folderID;
				box.size = 40;
				box.onkeypress = onEnter;
			}
			col.appendChild(box);

			box.ifValidRegex = 0;
			box.displayDiv = 'disp'+i;
			var req = ind[i].getElementsByTagName('REQUIRED');
			if(req.length > 0) {
				if(req[0].firstChild) {
					if(parseInt(req[0].firstChild.nodeValue)) {
						box.style.backgroundColor = 'gold';
						box.required = 1;
					} else {
						box.required = 0;
					}
				}
			}
			var reg = ind[i].getElementsByTagName('REGEX');
			if(reg.length > 0) {
				if(reg[0].firstChild) {
					if(reg[0].firstChild.nodeValue != "DISABLED") {
						box.regex = reg[0].firstChild.nodeValue;
						box.onblur = function() { return check4ValidRegex(this) };
					}
				}
			}
			var disp = ind[i].getElementsByTagName('DISPLAY');
			if(disp.length > 0) {
				if(disp[0].firstChild) {
					if(disp[0].firstChild.nodeValue != "DISABLED") {
						display = disp[0].firstChild.nodeValue;	
						var sp = document.createElement('span');
						sp.id = 'disp'+i;
						sp.style.color = 'Maroon';
						sp.appendChild(document.createTextNode(display));
						col.appendChild(sp);
					}
				}
			}
		}
	}
	displayAllInformation(subfolderID);
	cImg = getEl(subfolderID+'-edit');
	cImg.src = '../energie/images/save.gif';
	cImg.title = 'Save';
	cImg.onclick = function () {updateDocumentFields(subfolderID)};
	
	if(dImg = getEl(subfolderID+'-delete')) {
		dImg.src = '../energie/images/cancl_16.gif';
		dImg.title = "Cancel";
		dImg.onclick = function () {cancelEditDocumentFields(ind,subfolderID)};
		dImg.style.visibility = 'visible';
		dImg.style.width = '14px';
	} else {
		var deleteTD = getEl('deleteTD-'+subfolderID);
        var cImg = new Image();
        cImg.id = subfolderID+'-delete';
        cImg.src = '../energie/images/cancl_16.gif';
        cImg.alt = "Cancel";
        cImg.title = "Cancel";
        cImg.width = 14;
        cImg.onclick = function () {cancelEditDocumentFields(ind,subfolderID)};
        cImg.style.borderWidth = "0";
        cImg.style.visibility = 'visible';
        deleteTD.appendChild(cImg);
	}
	
	if(getEl(subfolderID+'-up')) {
		var downArrow = getEl(subfolderID+'-up');
		new Effect.Fade(downArrow);
	}
}

function onEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
		if(prevDocumentType && prevDocumentType != "DEFAULT") {
			updateDocumentFields(prevSelectedFileID);
		} else {
			saveNewDocument();
		}
	}
	return true;
}

function onDocFilterEnter(e) {
	var evt = (e) ? e : event;
	var charCode = (evt.keyCode) ? evt.keyCode : evt.charCode;
	if(charCode == 13) {
			searchDocFilter();
	}
	return true;
}

function cancelEditDocumentFields(fieldArr,file_id) {
	if(fieldArr.length > 0) {
		for(var i=0;i<fieldArr.length;i++) {
			var col = getEl(file_id+'-'+fieldArr[i].getAttribute('name'));
			if(col) {
				clearDiv(col);
				if(fieldArr[i].firstChild) {
					var v = "";
					if(fieldArr[i].firstChild.nodeValue) {
						v = fieldArr[i].firstChild.nodeValue;
					}
					col.appendChild(document.createTextNode(v));
				} else {
					col.appendChild(document.createTextNode(""));
				}
			}
		}
	}
	displayAllInformation(file_id);
	cImg = getEl(file_id+'-edit');
	cImg.src = '../energie/images/file_edit_16.gif';
	cImg.title = "Edit";
	cImg.onclick = function () {	editDocumentBool = true;
									subfolderID = file_id;
									documentType = getEl(file_id+'-docType').firstChild.nodeValue;
									if(getEl(file_id+'-docType').firstChild.nodeValue != 'DEFAULT') {
										editDocument(file_id);
									} else {
										getDocTypes(file_id);								
									}
								  };
	if(dImg = getEl(file_id+'-delete')) {
		dImg.src = '../energie/images/trash.gif';
		dImg.title = "Delete";
		if(delDoc) {
			dImg.onclick = function () {deleteDocument(file_id)};
		} else {
			dImg.onclick = function () {};
			dImg.style.visibility = 'hidden';
			dImg.style.width = "0px";
		}
	}

	if(getEl(file_id+'-up')) {
		var upArrow = getEl(file_id+'-up');
		new Effect.Appear(upArrow);
	}
	prevSelectedFileID = "";
	prevSelectedIndices = "";
	editDocumentBool = false;
	prevDocumentType = "";
	currentSubfolder = "";
	subfolderID = "";
}

function updateDocumentFields(file_id) {
	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('FOLDER');
	xmlDoc.appendChild(folderEl);

	createKeyAndValue(xmlDoc,folderEl,'function','xmlUpdateDocumentFields');
	createKeyAndValue(xmlDoc,folderEl,'subfolderID',file_id);
	createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
	createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
	
	var i = 0;
	while( inputEl = getEl('editField'+i+'-'+folderID)) {
		createKeyAndValue(xmlDoc,folderEl,'key'+i,inputEl.name);
		createKeyAndValue(xmlDoc,folderEl,inputEl.name,inputEl.value);
		if(inputEl.required) {
			if(!inputEl.value) {
				inputEl.focus();
				printMessage('Please fill in all required fields');
				return;
			}
		}

		if(inputEl.regex) {
			if(!inputEl.ifValidRegex) {
				if((inputEl.required) || (!inputEl.required && inputEl.value)) {
					inputEl.select();
					printMessage('Please fill in the proper format');
					return;
				}
			}
		}
		var col = getEl(file_id+'-'+inputEl.name);
		if(col) {
			clearDiv(col);
			col.appendChild(document.createTextNode(inputEl.value));
		}
		i++;
	}
	createKeyAndValue(xmlDoc,folderEl,'field_count',i);
	postXML(domToString(xmlDoc));
	displayAllInformation(file_id);
	cImg = getEl(file_id+'-edit');
	cImg.src = '../energie/images/file_edit_16.gif';
	cImg.onclick = function () {	editDocumentBool = true;
									subfolderID = file_id;
									documentType = getEl(file_id+'-docType').firstChild.nodeValue;
									if(getEl(file_id+'-docType').firstChild.nodeValue != 'DEFAULT') {
										editDocument(file_id);
									} else {
										getDocTypes(file_id);								
									}
								};
	if(dImg = getEl(file_id+'-delete')) {
		dImg.src = '../energie/images/trash.gif';
		if(delDoc) {
			dImg.onclick = function () {deleteDocument(file_id)};
		} else {
			dImg.onclick = function () {};
			dImg.style.visibility = 'hidden';
		}
	}

	if(getEl(file_id+'-up')) {
		var upArrow = getEl(file_id+'-up');
		new Effect.Appear(upArrow);
	}

	prevSelectedFileID = "";
	prevSelectedIndices = "";
	editDocumentBool = false;
	prevDocumentType = "";
	subfolderID = "";
}

function fillEditDocumentTypeFields(XML) {
	var ind = XML.getElementsByTagName('FIELD');
	var tbl = getEl(editDocType+'-'+folderID);

	while(tbl.rows.length > 1) {
		tbl.deleteRow(1);
	}
	
	if(ind.length > 0) {
		for(var i=0;i<ind.length;i++) {
			var row = tbl.insertRow(tbl.rows.length);
			var col = row.insertCell(row.cells.length);	
			var col = row.insertCell(row.cells.length);	
			var col = row.insertCell(row.cells.length);	
			col.style.whiteSpace = 'nowrap';
			col.style.width = '150px';
			col.appendChild(document.createTextNode(ind[i].firstChild.nodeValue));

			var col = row.insertCell(row.cells.length);	
			var t = document.createElement('input');
			t.type = 'text';
			t.name = ind[i].getAttribute('name');
			t.id = 'editField'+i+'-'+folderID;
			t.size = 40;
			col.appendChild(t);
		}
	}
}

function fillDocumentTypeFields(XML) {
	var ind = XML.getElementsByTagName('FIELD');
	var newDoc = false;
	if(subfolderID) {
		var tbl = getEl(subfolderID+'-table-'+folderID);
	} else {
		var tbl = getEl('newDocTable-'+folderID);
		newDoc = true;
	}

	while(tbl.rows.length > 1) {
		tbl.deleteRow(1);
	}
	
	if(ind.length > 0) {
		for(var i=0;i<ind.length;i++) {

			var row = tbl.insertRow(tbl.rows.length);
			if(newDoc) {
				var col = row.insertCell(row.cells.length);	
				var col = row.insertCell(row.cells.length);	
			}
			var col = row.insertCell(row.cells.length);	
			col.style.whiteSpace = 'nowrap';
			col.style.width = '150px';
			col.appendChild(document.createTextNode(ind[i].firstChild.nodeValue));

			var col = row.insertCell(row.cells.length);	
			var defsList = ind[i].getElementsByTagName('DEFINITION');
			if(defsList.length > 0) { 
				var box = document.createElement('select');

				var opt = document.createElement('option');
				opt.value = ""; 
				opt.appendChild(document.createTextNode(""));
				box.appendChild(opt);
				for(j=0;j<defsList.length;j++) {
					var def = defsList[j].firstChild.nodeValue;
					var opt = document.createElement('option');
					opt.value = def; 
					opt.appendChild(document.createTextNode(def));
					box.appendChild(opt);
				}
			} else {
				var box = document.createElement('input');
				box.type = 'text';
				box.value = getFolderFieldValue(ind[i].firstChild.nodeValue);
				box.size = 40;
				box.onkeypress = onEnter;
			}
			col.appendChild(box);

			box.id = "field"+i+"-"+folderID;
			box.name = ind[i].getAttribute('name');
			box.ifValidRegex = 0;
			box.displayDiv = 'disp'+i;
			var req = ind[i].getElementsByTagName('REQUIRED');
			if(req.length > 0) {
				if(req[0].firstChild) {
					if(parseInt(req[0].firstChild.nodeValue)) {
						box.style.backgroundColor = 'gold';
						box.required = 1;
					} else {
						box.required = 0;
					}
				}
			}
			var reg = ind[i].getElementsByTagName('REGEX');
			if(reg.length > 0) {
				if(reg[0].firstChild) {
					if(reg[0].firstChild.nodeValue != "DISABLED") {
						box.regex = reg[0].firstChild.nodeValue;
						box.onblur = function() { return check4ValidRegex(this) };
					}
				}
			}
			var disp = ind[i].getElementsByTagName('DISPLAY');
			if(disp.length > 0) {
				if(disp[0].firstChild) {
					if(disp[0].firstChild.nodeValue != "DISABLED") {
						display = disp[0].firstChild.nodeValue;	
						var sp = document.createElement('span');
						sp.id = 'disp'+i;
						sp.style.color = 'Maroon';
						sp.appendChild(document.createTextNode(display));
						col.appendChild(sp);
					}
				}
			}
		}

		if(el = getEl('field0-'+folderID)) {
            el.focus();
        }
	}
}

// Used to validate input value against the regular expression. It
// also colors the example string if it exists.
function check4ValidRegex(el) {
	if(el.value) {
		var regExpObj = new RegExp(el.regex);
		v = regExpObj.exec(el.value);
		if( regExpObj.test(el.value) ) {
			// don't change color if the example label doesn't exist
			if( $(el.displayDiv) != null ) {
				$(el.displayDiv).style.color = 'Lime';
			}
			el.ifValidRegex = 1;
		} else {
			// don't change color if the example label doesn't exist
			if( $(el.displayDiv) != null ) {
				$(el.displayDiv).style.color = 'Maroon';
			}
			el.ifValidRegex = 0;
			el.select();
			printMessage('Invalid '+el.name);
			return false;
		}
	} else {
		// don't change color if the example label doesn't exist
		if( $(el.displayDiv) != null ) {
			$(el.displayDiv).style.color = 'Lime';
		}
		el.ifValidRegex = 1;
	}
	printMessage('');
	return true;
}

function getFolderFieldValue(documentField) {
	for(var i=0;i<indexArr.length;i++) {
		if(indexArr[i] == documentField) {
			if(el = getEl(documentField+'-'+folderID)) {
				var textVal = "";
				if(sp = el.firstChild) {
					if(sp.childNodes[0]) {
						textVal = sp.childNodes[0].nodeValue;
					}
				}
				return textVal;
			}
		}
	}
	return "";
}

function selectDocumentType() {
	var docType = getEl('docTypeSelect-'+folderID).value;	

	var xmlDoc = createDOMDoc();
	var folderEl = xmlDoc.createElement('FOLDER');
	xmlDoc.appendChild(folderEl);

	createKeyAndValue(xmlDoc,folderEl,'function','getDocumentFields');
	createKeyAndValue(xmlDoc,folderEl,'document_table_name',docType);
	createKeyAndValue(xmlDoc,folderEl,'cabinet',cabinet);
	createKeyAndValue(xmlDoc,folderEl,'doc_id',folderID);
	if(subfolderID) {
		createKeyAndValue(xmlDoc,folderEl,'subfolderID',subfolderID);
	}
	postXML(domToString(xmlDoc));

	removeDefault(getEl('docTypeSelect-'+folderID));
}
