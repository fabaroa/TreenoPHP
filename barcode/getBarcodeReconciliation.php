<?php
include_once '../check_login.php';

if($logged_in and $user->username) {
	$tableTitle = "Barcode Reconciliation";	
echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/settings.js"></script>
<script>
 var direction = 'ASC';
 var page = 1;
 var last = 1;
 var field = 'id';
 function mOver() {
	this.style.backgroundColor = '#888888';	
 }

 function mOut() {
	this.style.backgroundColor = '#ffffff';	
 }

 function sortBy(column) {
	var xmlhttp = getXMLHTTP();
	var postStr = "";
	field = column;
	var search = document.getElementById('searchField').value;
	postStr = 'orderby='+column+'&page='+page;
	postStr += '&direction='+direction;
	if(search) {
		postStr += '&search='+search;
	}
	flipDirection();

	var mess = document.getElementById('message');
	mess.appendChild(document.createTextNode('Searching...please wait'));
	
	xmlhttp.open('POST','barcodeActions.php?history=1',true);
	xmlhttp.setRequestHeader('Content-Type',
								 'application/x-www-form-urlencoded');
	xmlhttp.send(postStr);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			var tableEl = document.getElementById('history');
			clearDiv(tableEl);
			var XML = xmlhttp.responseXML;
			var headerArr = XML.getElementsByTagName('HEADER');
			var row = tableEl.insertRow(tableEl.rows.length);	
			row.style.cursor = 'pointer';
			printCheckBox(row,'default');
			for(var i=0;i<headerArr.length;i++){
				var type = headerArr[i].getAttribute('name');
				var value = headerArr[i].firstChild.nodeValue;
				row.appendChild(printHeaders(type,value));
			}
		
			var root = XML.getElementsByTagName('ROOT');
			var entries = XML.getElementsByTagName('ENTRY');
			createPaging(root[0].getAttribute('total'));
			createDeleteButton();
			document.getElementById('searching').style.visibility = 'visible';
			for(var i=0;i<entries.length;i++) {
				var row = tableEl.insertRow(tableEl.rows.length);	
				printCheckBox(row,entries[i].getAttribute('id'));
				var columnInfo = entries[i].getElementsByTagName('COLUMN');
				for(var j=0;j<columnInfo.length;j++) {
					if(columnInfo[j].firstChild) {
						var value = columnInfo[j].firstChild.nodeValue;
					} else {
						var value = "";
					}
					printInfo(row,value);
				}
			}
			clearDiv(mess);
			adjustHeight();
		}
	};
 }

 function reconcileBarcodes() {
	var xmlhttp = getXMLHTTP();
	var mess = document.getElementById('message');
	mess.appendChild(document.createTextNode('Deleting...please wait'));
	
	var domDoc = createDOMDoc();
	rootDoc = domDoc.createElement('ROOT');
	domDoc.appendChild(rootDoc);
	var chkBox = document.getElementsByTagName('input');
	for(var i=0;i<chkBox.length;i++) {
		if(chkBox[i].type == 'checkbox') {
			if( chkBox[i].value != 'default' && chkBox[i].checked == true ) {
				barcodeRec = domDoc.createElement('RECONCILE');
				barcodeRec.appendChild(domDoc.createTextNode(chkBox[i].value));
				rootDoc.appendChild(barcodeRec);
			}
		}
	}
	var domString = domToString(domDoc);
	xmlhttp.open('POST','barcodeActions.php?reconcile=1',true);
	xmlhttp.setRequestHeader('Content-Type',
								 'application/x-www-form-urlencoded');
	xmlhttp.send(domString);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4) {
			clearDiv(mess);
			flipDirection();
			sortBy(field);	
		}
	};
 }

 function createPaging(total) {
	last = Math.ceil(total/50);
	if(last==0) {
		last = 1;
	}
	var tableEl = document.getElementById('paging');
	clearDiv(tableEl);
	row = tableEl.insertRow(tableEl.rows.length);
	var col = row.insertCell(row.cells.length);
	var img = document.createElement('img');
	img.style.cursor = 'pointer';
	img.onclick = function() {selectPage(1)};
	img.src = '../energie/images/begin_button.gif';
	col.appendChild(img);
	
	var col = row.insertCell(row.cells.length);
	var img = document.createElement('img');
	img.style.cursor = 'pointer';
	img.onclick = function() {selectPage(2)};
	img.src = '../energie/images/back_button.gif';
	col.appendChild(img);

	var col = row.insertCell(row.cells.length);
	var txtField = document.createElement('input');
	txtField.value = page;
	txtField.size = 3;
	txtField.name = 'pageNum';
	txtField.id = 'pageNum';
	txtField.onkeypress = allowDigi;
	col.appendChild(txtField);
	col.appendChild(document.createTextNode(' of '+last+' '));
	
	var col = row.insertCell(row.cells.length);
	var img = document.createElement('img');
	img.style.cursor = 'pointer';
	img.onclick = function() {selectPage(3)};
	img.src = '../energie/images/next_button.gif';
	col.appendChild(img);
	
	var col = row.insertCell(row.cells.length);
	var img = document.createElement('img');
	img.style.cursor = 'pointer';
	img.onclick = function() {selectPage(4)};
	img.src = '../energie/images/end_button.gif';
	col.appendChild(img);
 }

 function printHeaders(type,value) {
	var header = document.createElement('th');
	header.onmouseover = mOver;
	header.onmouseout = mOut;
	header.onclick = function() { sortBy(type) };
	header.appendChild(document.createTextNode(value));
	return header;
 }

 function printInfo(row,value) {
	var col = row.insertCell(row.cells.length);
	col.appendChild(document.createTextNode(value));
 }

 function createDeleteButton() {
	var delDiv = document.getElementById('delBut');
	clearDiv(delDiv);
	var delBut = document.createElement('input');
	delBut.type = 'button';
	delBut.name = 'Reconcile';
	delBut.value = 'Reconcile';
	delBut.onclick = function() {reconcileBarcodes()};
	delDiv.appendChild(delBut);
 }

 function printCheckBox(row,value) {
	var col = row.insertCell(row.cells.length);
	var checkBox = document.createElement('input');
	checkBox.type = 'checkbox';
	checkBox.id = value;
	if(value == 'default') {
		checkBox.onclick = function(){selectAll()};
	}
	checkBox.value = value;
	checkBox.name = 'reconcile[]';
	col.appendChild(checkBox);
 }
 
 function selectPage(type) {
	if(type == 1) {
		page = 1;
	} else if(type == 2) {
		page = page - 1;
	} else if(type == 3) {
		page = page + 1;
	} else if(type == 4) {
		page = last;
	} else {
		page = type;
	}

	if( page == 0 ) {
		page = 1;
	} else if(page > last) { 
		page = last;
	}
	flipDirection();
	sortBy(field);
 }

 function flipDirection() {
	if( direction == 'ASC' ) {
		direction = 'DESC';
	} else {
		direction = 'ASC';
	}
 }

 function allowDigi(evt) {
	evt = (evt) ? evt : event;
	var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	if (((charCode >= 48 && charCode <= 57) // is digit
		|| charCode == 8) || (charCode == 37) || (charCode == 39)) { // is enter or backspace key
		return true;
	} else if( charCode == 13) {
		selectPage(document.getElementById('pageNum').value);
		return true;
	} else { // non-digit
		return false;
	}
 }

 function allowKeys(evt) {
	evt = (evt) ? evt : event;
	var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
	var pool = "1234567890 ";
	pool += "abcdefghijklmnopqrstuvwxyz";
	pool += "ABCDEFGHIJKLMNOPQRSTUVWXYZ_-";
	var character = String.fromCharCode(charCode);

	if( charCode == 13 ) {
		page = 1;
		flipDirection();
		sortBy(field);
		return true;
	} else if( (pool.indexOf(character) != -1) || charCode ==8  || (charCode == 37) || (charCode == 39)) {
		return true;
	} else {
		return false;
	}
 }

 function selectAll() {
	var chkBox = document.getElementsByTagName('input');
	var toggle = document.getElementById('default').checked; 
	for(var i=0;i<chkBox.length;i++) {
		if(chkBox[i].type == 'checkbox') {
			chkBox[i].checked = toggle;
		}
	}
 }

function adjustHeight() {
	var clienth = document.documentElement.clientHeight;
	var outh = getEl('outerDiv').offsetHeight;

	var d = getEl('historyDiv');
	var inh = d.offsetHeight;

	var newh = 0;
	if(clienth > outh ) {
		newh = inh + (clienth - outh) - 50;
	} else {
		newh = inh - (outh - clienth) - 50;
	}

	if(newh < 0) {
		newh = 0;
	}
	d.style.height = newh+'px';
}

</script>
<title>$tableTitle</title>
</head>
<body class="centered" onload='sortBy("id")'>
<div id="outerDiv" class="mainDiv" style="width:80%">
<div class="mainTitle"><span>$tableTitle</span></div>
 <div id='message' style='position:absolute;left:12%;color:red'></div>
 <table class='inputTable' id='paging'>
 </table>
 <div id='delBut' style='position:absolute;left:11%;'></div>
 <table id='searching' class='inputTable' style='visibility:hidden'>
  <tr>
   <td>Search</td>
   <td><input type='text' onkeypress='return allowKeys(event);' name='searchField' id='searchField'></td>
  </tr>
 </table>
 <div id="historyDiv" class="inputForm" style="padding-top:5px;padding-bottom:5px;overflow-y:scroll">
 <table id="history" style="width:97%">
 </table>
</div>
</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
