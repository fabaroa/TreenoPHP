var currTab = '';
var alertBoxID = null;

function setSelectedRow( newRow )
{
	selRow = selectedRow;
  	if(selRow == "")
  	{
      	document.getElementById(newRow).style.backgroundColor = '#ebebeb';
      	document.getElementById('filerow:'+newRow).style.backgroundColor = '#ebebeb';
      	document.getElementById('A:'+newRow).style.color='#000000';
      	document.getElementById(newRow).style.color='#000000';
      	selectedRow = newRow;
    }
    else
    {
      	document.getElementById(selRow).style.backgroundColor = '#003b6f';
      	document.getElementById('filerow:'+selRow).style.backgroundColor = '#003b6f';
      	document.getElementById('A:'+selRow).style.color='#ffffff';
      	document.getElementById(selRow).style.color='#ffffff';
      	document.getElementById(newRow).style.backgroundColor = '#ebebeb';
      	document.getElementById('filerow:'+newRow).style.backgroundColor = '#ebebeb';
      	document.getElementById('A:'+newRow).style.color='#000000';
      	document.getElementById(newRow).style.color='#000000';
      	selectedRow = newRow;
    }
    currPage = Math.floor(document.getElementById('filerow:' + selectedRow).rowIndex / 2);
}
function changeColor( id, row )
{
  	if(row == "")
    	row = "filerow:";
                                                                                                                             
	selRow = selectedRow;
  	if( selRow == id) 
	{
    	document.getElementById(selRow).style.backgroundColor = '#888888';
    	document.getElementById('filerow:' + selRow).style.backgroundColor = '#888888';
  	}
  	else 
	{
    	document.getElementById(id).style.backgroundColor = '#6a78af';
	   	document.getElementById(row + id).style.backgroundColor = '#6a78af';
  	}
}
function resetColor( id, row )
{
  	if(row == "")
    	row = "filerow:";
                                                                                                                             
	selRow = selectedRow;
  	if(selRow == id)
  	{
    	document.getElementById(selRow).style.backgroundColor = '#ebebeb';
    	document.getElementById('filerow:' + selRow).style.backgroundColor = '#ebebeb';
  	}	
  	else
  	{
    	document.getElementById(id).style.backgroundColor = '#003b6f';
    	document.getElementById(row + id).style.backgroundColor = '#003b6f';
  	}
}

function refresh()
{
    self.close();
}
function noScroll()
{
	document.body.style.overflowX = 'hidden';
  	document.body.style.overflowY = 'auto';
}

function flipVisibleThumbs(obj)
{
	var el = document.getElementById(obj);
	var otherTabEl;
	if (el && el.style.display == 'none') {
		for(var i = 0; i < tabArr.length; i++) {
			if(tabArr[i] != obj) {
				var otherTabEl = document.getElementById(tabArr[i]);
				if(otherTabEl) {
					otherTabEl.style.display = 'none';
				}
			} else {
				currTab = obj;
				if(el) {
					el.style.display = 'block';	  
				}
			}
		}
		adjustFilenames(currTab);
		parent.bottomFrame.window.location="files.php?cab="+cab+"&doc_id="+did+"&ID=0&tab="+obj+"&index="+index+"&table="+temp_table+"&count=0";

	} else {
		currTab = '';
		if(el) {
			el.style.display = 'none';
		}
		parent.bottomFrame.window.location="files.php?cab="+cab+"&doc_id="+did+"&ID=0&tab=''&index="+index+"&table="+temp_table+"&count=0";
	}
    }

function allowDigi(e, value, tab, orderNum, currentPos, fileID)
{
    if (e.keyCode)
        code = e.keyCode;
    else if (e.which)
        code = e.which;
                                                                                                                             
    if( code )
    {
        var pool = "1234567890";
        var character = String.fromCharCode(code);
                                                                                                                             
        //13 is for enter to submit the form
        if( code == 13 )
        {
            parent.leftFrame1.window.location = "reorderThumbs.php?value=" + value
                + "&tab=" + tab + "&orderNum=" + orderNum + "&currentPos="
                + currentPos + "&fileID=" + fileID;
            return true;
        }
                                                                                                                             
        //if code is for a characters, backspace, tab, and delete respectively
        if( (pool.indexOf(character) != -1) || (code == 8) || (code == 9) || (code == 46)  || (charCode == 37) || (charCode == 39) )
            return true;
        else
            return false;
    }
    return false;
}
function loadFiles( cab, doc_id, ID, tab, file_id, count )
{
  addBackButton();
  parent.bottomFrame.window.location = "files.php?cab=" + cab + "&doc_id="
        + doc_id + "&ID=" + ID + "&tab=" + tab + "&fileID=" + file_id
        + "&index="+ index + "&table=" + temp_table + "&count=" + count;
  tmp_count = count;
}

function addAction(cab,did,tab,type)
{
	var answer;
  //disable back button when downloading a zip file
  if( type!="ZIP" && type != "DELETE" )
    addBackButton();
 
  //must give a warning on file deletion (warnings depend on deleteFileOptions -Management)
  if( type == "DELETE" ) {
	var counter = 0; 
	var check = document.getElementsByName("check[]");
	for( var i = 0; i < check.length; i++ ) {
		if( check[i].checked == true )
			counter++;
	}
	
	var ruSure;
	if( counter > 1 ) {
		ruSure = "Are you sure you want to delete all " + counter + " files?";
		answer = window.confirm( ruSure );
	} else if( counter == 1 ) {
		ruSure = "Are you sure you want to delete " + counter + " file?";
		answer = window.confirm( ruSure );
	} else {
		parent.sideFrame.alertBox('No Files Have Been Checked')
		answer = false;
	}
  } else {
  	answer = true;
  }
                                                                                                                             
  if(answer == true)
  {
  	var expNonRedactEl = document.getElementById('exportNonRedact');
  	var otherOpt = "";
  	if(expNonRedactEl) {
  		otherOpt = "&expNonRedact=";
  		if(expNonRedactEl.checked) {
  			otherOpt += "1";
  		} else {
  			otherOpt += "0";
  		}
  	}
    document.thumbs.action = "allFiles.php?cab=" + cab + "&doc_id="
        + did + "&tab=" + tab + "&type=" + type + "&referer=" + referer + "&table=" + temp_table + "&index=" + index + otherOpt;
    document.thumbs.submit();
  }
}
function addBackButton()
{
  var myObj = parent.topMenuFrame.document.getElementById('up');
  if(myObj.style.display == "none") {
    parent.topMenuFrame.addOnClick( referer, cab, resPerPage, pageNum, numResults, temp_table, index );
    myObj.style.display = "block";
  }
}

function showThumbs()
{
	currThumb++;
	var img;
	if(img = document.getElementById('img:' + currThumb)) {
		var infoStr = document.getElementById('imgInfo:' + currThumb).firstChild.nodeValue;
				 var infoArray = infoStr.split(',');
				 var cabinet = infoArray[0];
				 var fileID = infoArray[1];
		var urlStr = 'readfileThumbs.php?' + 'cab=' + cabinet + '&fileID=' + fileID;
		img.onload = function() {
			setTimeout('try {showThumbs();}catch(e){}', 50);
			}
		img.src = urlStr; 
    }
	return true;
}

function loadRedaction(cabinet, docID, fileID, thumbCt, divID, tab) {
	thumbCt--;
	var urlStr = 'editRedaction.php?cabinet=' + cabinet + '&docID=' + docID + '&fileID=' + fileID + "&thumbCt=" + thumbCt;
	urlStr = urlStr + "&divID=" + divID;
	top.mainFrame.window.location = urlStr;
}

function allowOrdering(orderSentry)
{
	parent.leftFrame1.location = 'moveThumb.php?&orderSentry='+orderSentry;
}

function enterWorkflow()
{
	addBackButton();
	parent.mainFrame.window.location = '../workflow/getSignature.php?cab='
				+ cab+'&doc_id='+did;
}

function editWorkflow( cab, doc_id )
{
	addBackButton();
	parent.mainFrame.window.location = '../workflow/ownerAction.php?cab='
				+ cab+'&doc_id='+doc_id;
}

function viewWorkflowHistory() {
	addBackButton();
	parent.mainFrame.window.location = '../workflow/viewHistory.php?cab='
				+ cab+'&doc_id='+did;

}

function assignWorkflow( cab, doc_id )
{
	addBackButton();
	parent.mainFrame.window.location = '../workflow/assignWorkflow.php?cab='
				+ cab+'&doc_id='+doc_id;
}

function enterUploadFile( doc_id, tab, cab, temp_table, currTab )
{
	addBackButton();
	parent.topMenuFrame.removeVersButton();

	var xmlDoc = createDOMDoc();
	var root = xmlDoc.createElement('ROOT');
	xmlDoc.appendChild(root);

	var entry = xmlDoc.createElement('ENTRY');
	root.appendChild(entry);
	var k = xmlDoc.createElement('KEY');
	k.appendChild(xmlDoc.createTextNode('include'));
	entry.appendChild(k);
	var v = xmlDoc.createElement('VALUE');
	v.appendChild(xmlDoc.createTextNode('secure/uploadFuncs.php'));
	entry.appendChild(v);
	root.appendChild(entry);

	var entry = xmlDoc.createElement('ENTRY');
	root.appendChild(entry);
	var k = xmlDoc.createElement('KEY');
	k.appendChild(xmlDoc.createTextNode('function'));
	entry.appendChild(k);
	var v = xmlDoc.createElement('VALUE');
	v.appendChild(xmlDoc.createTextNode('setUploadPath'));
	entry.appendChild(v);
	root.appendChild(entry);

	var entry = xmlDoc.createElement('ENTRY');
	root.appendChild(entry);
	var k = xmlDoc.createElement('KEY');
	k.appendChild(xmlDoc.createTextNode('cabinet'));
	entry.appendChild(k);
	var v = xmlDoc.createElement('VALUE');
	v.appendChild(xmlDoc.createTextNode(cab));
	entry.appendChild(v);
	root.appendChild(entry);

	var entry = xmlDoc.createElement('ENTRY');
	root.appendChild(entry);
	var k = xmlDoc.createElement('KEY');
	k.appendChild(xmlDoc.createTextNode('doc_id'));
	entry.appendChild(k);
	var v = xmlDoc.createElement('VALUE');
	v.appendChild(xmlDoc.createTextNode(doc_id));
	entry.appendChild(v);
	
	var entry = xmlDoc.createElement('ENTRY');
	root.appendChild(entry);
	var k = xmlDoc.createElement('KEY');
	k.appendChild(xmlDoc.createTextNode('temp_table'));
	entry.appendChild(k);
	var v = xmlDoc.createElement('VALUE');
	v.appendChild(xmlDoc.createTextNode(temp_table));
	entry.appendChild(v);

	if(currTab) {
		var entry = xmlDoc.createElement('ENTRY');
		root.appendChild(entry);
		var k = xmlDoc.createElement('KEY');
		k.appendChild(xmlDoc.createTextNode('tab'));
		entry.appendChild(k);
		var v = xmlDoc.createElement('VALUE');
		v.appendChild(xmlDoc.createTextNode(currTab));
		entry.appendChild(v);
	}
	var xmlStr = domToString(xmlDoc);

	p = getXMLHTTP();
	p.open('POST', '../lib/ajaxPostRequest.php', true);
	p.send(xmlStr);
	p.onreadystatechange = function () {
		if(p.readyState == 4) {
			openUploadPage();
		}
	};
}

function openUploadPage() {
	parent.mainFrame.window.location = '../secure/uploadInbox2.html';
}

function enterMoveFiles2( cab, doc_id, temp_table )
{
	var inputTag = parent.sideFrame.document.getElementsByTagName('input');
	var postStr = "";
	for(var i=0;i<inputTag.length;i++) {
		if( inputTag[i].type == 'checkbox' ){
			if( inputTag[i].checked == true ) {
				if( postStr != "" )
					postStr += "&"
				postStr += "check[]="+inputTag[i].value;
			}	
		}
	}
	if( postStr != "" ) {
		addBackButton();
		parent.mainFrame.window.location = '../movefiles/departmentContents.php?cab='+cab
				+ '&doc_id='+doc_id+'&temp_table='+temp_table;
/*		if (window.XMLHttpRequest)
			xmlhttp = new XMLHttpRequest();
		else if (window.ActiveXObject)
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

		xmlhttp.open('POST', 'allthumbActions.php?cab='+cab+'&doc_id='+doc_id+'&action=moveFiles', true);
		xmlhttp.setRequestHeader('Content-Type',
								 'application/x-www-form-urlencoded');
		xmlhttp.send(postStr);
*/
	} else {
		parent.sideFrame.alertBox( 'No Files Have Been Checked' )
	}
}

function enterCreateTabs( doc_id, tab, cab, temp_table )
{
	addBackButton();
	parent.topMenuFrame.removeVersButton();
	parent.mainFrame.window.location='addTab.php?doc_id='+doc_id+'&tab='+tab
				+ '&cab='+cab+'&table='+temp_table;
}

function alertBox(alertTxt)
{
	var myDiv;
	if(alertBoxID != null) {
		myDiv = document.getElementById('alertBox');
		myDiv.parentNode.removeChild(myDiv);
		clearTimeout(alertBoxID);
		alertBoxID = null;
	} else {
	myDiv = document.createElement('div');
	myDiv.style.width = '80%';
	myDiv.style.marginLeft = 'auto';
	myDiv.style.marginRight = 'auto';
	myDiv.id = 'alertBox';
	myDiv.style.backgroundColor = '#eeeeee';
	myDiv.style.borderStyle = 'double';
	myDiv.style.borderColor = 'black';
	myDiv.style.overflow = 'hidden';
	var newDiv = document.createElement('div');
	newDiv.className = 'error';
	newDiv.style.margin = '1em';
	newDiv.style.textAlign = 'center';
	newDiv.appendChild(document.createTextNode(alertTxt));
	myDiv.appendChild(newDiv);
	var btnTbl = document.getElementById('btnTbl');
	btnTbl.parentNode.insertBefore(myDiv, btnTbl.nextSibling);
	var myHeight = myDiv.offsetHeight;
	myDiv.style.height = '0';
	showAlertBox(myDiv, myHeight);
	}
}

function showAlertBox(myBox, height)
{
	myBox.style.height = (myBox.offsetHeight + 20) + 'px';
	if(myBox.offsetHeight <= height) {
		alertBoxID = setTimeout(function(){showAlertBox(myBox, height)}, 40);
	} else {
		alertBoxID = setTimeout(function(){hideAlertBox(myBox)}, 10000);
	}
}

function hideAlertBox(myBox)
{
	myBox.style.height = (myBox.offsetHeight - 20) + 'px';
	if(myBox.offsetHeight > 20) {
		alertBoxID = setTimeout(function(){hideAlertBox(myBox)}, 40);
	} else {
		myBox.parentNode.removeChild(myBox);
		alertBoxID = null;
	}
}

function toggleSelectTab(check, tabName) {
	var checkVal = check.checked;
	var el;
	for (var i = 0; el = document.getElementById('tab:' + tabName + '-' + i); i++) {
		el.checked = checkVal;
	}
}

function printTabBarcodes(sel) {
	var tabName = sel.value;
	if(sel.value != '__default') {
		printDocutronBarcode(cab, did, tabName);
	}
//Cannot remove the default message like below,
//	causes race condition that prints the next selected barcode
/*	for(var i = 0; i < sel.options.length; i++) {
		if(sel.options[i].value == '__default') {
			sel.removeChild(sel.options[i]);
			break;
		}
	}
*/
}

function reorderThumbs() {
	var myStr = '';
	var el, el2, el3, j;
	var myArr = new Object();
	var tmp, fileID, tmpArr;
	for(var i = 0; i < tabArr.length; i++) {
		myArr[tabArr[i]] = new Object();
		for(j = 1; el = document.getElementById('reorder-' + tabArr[i] + '-' + j); j++) {
			tmp = parseInt(el.value);
			if(isNaN(tmp)) {
				tmp = 0;
			}
			tmpArr = el.name.split('-');
			fileID = tmpArr[tmpArr.length - 1];
			while(myArr[tabArr[i]][tmp]) {
				tmp -= 0.05;
			}
			myArr[tabArr[i]][tmp] = fileID;
		}
	}
	var xmlDoc = createDOMDoc();
	el = xmlDoc.createElement('reordering');
	el2 = xmlDoc.createElement('cabinet');
	el2.setAttribute('name', cab);
	el.appendChild(el2);
	el2 = xmlDoc.createElement('docID');
	el2.setAttribute('id', did);
	el.appendChild(el2);
	for(var a in myArr) {
		el2 = xmlDoc.createElement('tab');
		el2.setAttribute('name', a);
		for(var b in myArr[a]) {
			el3 = xmlDoc.createElement('file');
			el3.setAttribute('order', b);
			el3.setAttribute('fileID', myArr[a][b]);
			el2.appendChild(el3);
		}
		el.appendChild(el2);
	}
	xmlDoc.appendChild(el);
	tmp = domToString(xmlDoc);
	p = getXMLHTTP();
	p.open('POST', '../lib/settingsFuncs.php?func=setOrdering', true);
	p.send(tmp);
	p.onreadystatechange = function () {
		if(p.readyState == 4) {
			var urlStr = '../energie/allthumbs.php?cab=' + cab + '&doc_id=' + did + '&table=' + temp_table;
			parent.sideFrame.location = urlStr;
		}
	};
}

function submitReorder(e) {
	var code;
	if (!e) var e = window.event;
	if (e.keyCode) code = e.keyCode;
	else if (e.which) code = e.which;
	if(code == 13) {
		reorderThumbs();
	}
	return true;
}
