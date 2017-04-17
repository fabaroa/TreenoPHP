<?php
// $Id: editCabinet.php 15033 2013-09-18 18:28:27Z fabaroa $

include_once '../check_login.php';
include_once ( '../classuser.inc');

if($logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin()) {
	$tableTitle               = $trans['Edit Cabinet']; 
	$selectCabLabel           = $trans['Choose Cabinet'];
	$newLabel                 = "New Indices";
	$db_object = $user->getDbObject();

echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript">
	var domdoc = "";
	var rootDoc = "";
	var isValid = true;
	var errorMess = "";

	function selectCabinet() {
		cab = document.getElementById('cabSelect').value;
		if( cab != "default" ) {
			var xmlhttp1 = getXMLHTTP();
			xmlhttp1.open('POST','cabinetActions.php?cabinfo=1',true);
			xmlhttp1.setRequestHeader('Content-Type',
			                             'application/x-www-form-urlencoded');
			xmlhttp1.send( 'cab='+cab );
			xmlhttp1.onreadystatechange = function() {
        		if (xmlhttp1.readyState == 4) {
					var XML = xmlhttp1.responseXML;
					var indexArr = XML.getElementsByTagName("INDEX");	
					var extraIndices = document.getElementById('extraIndices');	
					if( extraIndices ) {
						clearDiv(extraIndices);
						var currentIndices = document.getElementById('currentIndices');	
						clearDiv(currentIndices);
						var editCab = document.getElementById('editCab');	
						clearDiv(editCab);
						createEditCabinetName();
						createCurrentIndexField(indexArr);
						createExtraIndices(indexArr.length);
					} else {
						createEditCabinetName();
						createCurrentIndexField(indexArr);
						createExtraIndices(indexArr.length);
						createButton();
					}
        		}
    		};
		}
	}

	function createEditCabinetName() {
		if( document.getElementById('editCab') ) {
			indDiv = document.getElementById('editCab');
		} else {
			outerDiv = document.getElementById('editIndices');	
			var indDiv = document.createElement('div');
			indDiv.id = 'editCab';
			outerDiv.appendChild(indDiv);
		}
		cab = document.getElementById('cabSelect');
		arbCab = cab.options[cab.selectedIndex].text;
		indDiv.appendChild(document.createTextNode('Edit Cabinet Name '));
		var editCab = document.createElement('input');
		editCab.type = 'text';
		editCab.id = cab.value;
		editCab.value = arbCab;
		editCab.onkeypress = allowKeys;
		indDiv.appendChild(editCab);	
    var input = document.getElementById (cab.value);
    input.maxLength = 70;
	}
	
	function createCurrentIndexField(indexArr) {
		if( document.getElementById('currentIndices') ) {
			indDiv = document.getElementById('currentIndices');	
		} else {
			outerDiv = document.getElementById('editIndices');	
			var indDiv = document.createElement('div');
			indDiv.id = 'currentIndices';
			outerDiv.appendChild(indDiv);
		}
		indDiv.className = 'inputForm';
		var editTable = document.createElement('table');
		editTable.className = 'inputTable';
		indDiv.appendChild(editTable);
		var row = editTable.insertRow(editTable.rows.length);
		row.style.fontWeight = 'bold';
		var col1 = row.insertCell(row.cells.length);
		col1.align = 'center';
		col1.style.width = "15%";
		col1.appendChild(document.createTextNode('Delete'));
		var col2 = row.insertCell(row.cells.length);
		col2.align = 'center';
		col2.style.width = "20%";
		col2.appendChild(document.createTextNode('Index'));
		var col3 = row.insertCell(row.cells.length);
		col3.align = 'center';
		col3.style.width = "65%";
		col3.appendChild(document.createTextNode('Current'));
		for(var i=0;i<indexArr.length;i++) {
			var indName = indexArr[i].firstChild.nodeValue;
			var row = editTable.insertRow(editTable.rows.length);
			
			var col = row.insertCell(row.cells.length);
			col.align = 'center';
			
			var check = document.createElement('input');
			check.type = 'checkbox';
			check.value = indName;
			check.indiceName = indName;
			check.onclick = disableRow;
			col.appendChild(check);
			
			var col = row.insertCell(row.cells.length);
			col.align = 'center';
			col.style.whiteSpace = 'nowrap';
			
			var numText = document.createElement('input');
			numText.type = 'text';
			numText.id = indName+"-num";
			numText.size = 2;
			numText.indiceName = indName; 
			numText.value = i+1;
			numText.origVal = i+1;
			numText.origType = "indiceNum";
			numText.onkeypress = allowDigi;
			col.appendChild(document.createTextNode('index '));
			col.appendChild(numText);
			
			var col = row.insertCell(row.cells.length);
			col.align = 'center';
			
			var textIndex = document.createElement('input');
			textIndex.type = 'text';
			textIndex.id = indName+"-indice";
			textIndex.name = indName; 
			textIndex.value = indName;
			textIndex.origType = "currentIndice";
			textIndex.onkeypress = allowKeys;
			col.appendChild(textIndex);
		}
	}

	function createExtraIndices(start) {
		if( document.getElementById('extraIndices') ) {
			indDiv = document.getElementById('extraIndices');	
		} else {
			outerDiv = document.getElementById('editIndices');	
			var indDiv = document.createElement('div');
			indDiv.id = 'extraIndices';
			outerDiv.appendChild(indDiv);
		}
		indDiv.className = 'inputForm';
		var labelDiv = document.createElement('div');
		labelDiv.className = 'subTitle';
		var labelSpan = document.createElement('span');
		labelSpan.appendChild(document.createTextNode('$newLabel'));	
		labelDiv.appendChild(labelSpan);
		indDiv.appendChild(labelDiv);

		var editTable = document.createElement('table');
		editTable.className = 'inputTable';
		indDiv.appendChild(editTable);
		for(var i=start;i<10+start;i++) {
			var row = editTable.insertRow(editTable.rows.length);	
			var col1 = row.insertCell(row.cells.length);
			col1.align = 'center';
			col1.style.whiteSpace = 'nowrap';
			
			var numText = document.createElement('input');
			numText.type = 'text';
			numText.size = 2;
			numText.name = 'newIndice-'+i; 
			numText.value = i+1;
			numText.onkeypress = allowDigi;
			col1.appendChild(document.createTextNode('index '));
			col1.appendChild(numText);
			
			var col2 = row.insertCell(row.cells.length);
			col2.align = 'center';
			var textIndex = document.createElement('input');
			textIndex.type = 'text';
			textIndex.name = 'indexfield'+i; 
			textIndex.origType = "newIndice";
			textIndex.onkeypress = allowKeys;
			col2.appendChild(textIndex);
		}
	}
	function createButton() {
		outerDiv = document.getElementById('editIndices');	
		var messageDiv = document.createElement('div');
		messageDiv.align = 'center';
		messageDiv.id = 'errorDiv';
		messageDiv.style.color = 'red';
		outerDiv.appendChild(messageDiv);
		var buttonDiv = document.createElement('div');
		buttonDiv.align = 'right';
		var subBut = document.createElement('input');
		subBut.type = 'button';
		subBut.name = 'update'; 
		subBut.value = 'Save'; 
		subBut.onclick = function() {submitEditCabinet()}  
		buttonDiv.appendChild(subBut);
		outerDiv.appendChild(buttonDiv);
	}

	function disableRow() {
		var val = this.indiceName;
		if( this.checked == true ) {
			document.getElementById(val+'-num').disabled = true;
			document.getElementById(val+'-indice').disabled = true;
		} else {
			document.getElementById(val+'-num').disabled = false;
			document.getElementById(val+'-indice').disabled = false;
		}
	}

	function submitEditCabinet() {
		var errDiv = document.getElementById('errorDiv');
		clearDiv(errDiv);
		errDiv.appendChild(document.createTextNode('Updating....please wait'));
		var xmlhttp = getXMLHTTP();
		xmlhttp.open('POST','cabinetActions.php?editCabinet=1',true);
		domdoc = createDOMDoc();
		rootDoc = domdoc.createElement("ROOT");
		cabDoc = domdoc.createElement("CABINET"); 
		rootDoc.appendChild(cabDoc);
		cab = document.getElementById('cabSelect');
		cabDoc.setAttribute('name',cab.value);
		cabDoc.appendChild(domdoc.createTextNode(document.getElementById(cab.value).value));
		domdoc.appendChild(rootDoc);
		getMarkedDeleted();
		getChangedIndiceNum();
		getChangedIndiceValue();
		getNewIndices();
		if(isValid) {
			var domString = domToString(domdoc);
			xmlhttp.setRequestHeader('Content-Type',
										 'application/x-www-form-urlencoded');
			xmlhttp.send( domString );
			xmlhttp.onreadystatechange = function() {
				if (xmlhttp.readyState == 4) {
					var message = new Array();
					var errDiv = document.getElementById('errorDiv');
					clearDiv(errDiv);
					if( xmlhttp.responseText != "" ) {
						message = xmlhttp.responseText.split('\\n');
						for(var i=0;i<message.length;i++) {
							errDiv.appendChild(document.createTextNode(message[i]));
							errDiv.appendChild(document.createElement('br'));
						}
					} else {
						errDiv.appendChild(document.createTextNode('Updated successfully'));
						cab.options[cab.selectedIndex].text = document.getElementById(cab.value).value;
					}
					selectCabinet();
				}
			};
		} else {
			if(el = getEl('errorDiv')) {
				clearDiv(el);
				el.appendChild(document.createTextNode(errorMess));
			}
		}
	}

	function getMarkedDeleted() {
		var deletedInd = document.getElementsByTagName('input');	
		for(var i=0;i<deletedInd.length;i++) {
			if( deletedInd[i].type == "checkbox" && deletedInd[i].checked == true ) {
				var deletedEl = domdoc.createElement("DELETED");
				deletedEl.appendChild(domdoc.createTextNode(deletedInd[i].value));
				rootDoc.appendChild(deletedEl);
			}
		}
	}

	function getChangedIndiceNum() {
		var indiceChange = document.getElementsByTagName('input');	
		for(var i=0;i<indiceChange.length;i++) {
			if( indiceChange[i].type == "text" && 
				indiceChange[i].origVal != indiceChange[i].value && 
				indiceChange[i].origType == "indiceNum" && indiceChange[i].disabled == false) {

				var changedInd = domdoc.createElement("INDICE_NUM");
				changedInd.setAttribute('current',indiceChange[i].origVal);
				changedInd.setAttribute('name',indiceChange[i].indiceName);
				changedInd.appendChild(domdoc.createTextNode(indiceChange[i].value));
				rootDoc.appendChild(changedInd);
			}
		}
	}
	
	function getNewIndices() {
		var indiceChange = document.getElementsByTagName('input');	
		for(var i=0;i<indiceChange.length;i++) {
			if( indiceChange[i].type == "text" && 
				indiceChange[i].value != "" && 
				indiceChange[i].origType == "newIndice") {

				var addInd = domdoc.createElement("NEW_INDICE");
				addInd.setAttribute('indiceNum',indiceChange[i-1].value);
				addInd.appendChild(domdoc.createTextNode(indiceChange[i].value));
				rootDoc.appendChild(addInd);
			}
		}
	}

	function getChangedIndiceValue() {
		var indiceChange = document.getElementsByTagName('input');	
		for(var i=0;i<indiceChange.length;i++) {
			if( indiceChange[i].type == "text" && 
				indiceChange[i].origType == "currentIndice" && 
				indiceChange[i].disabled == false) {

				if(indiceChange[i].value == "") {
					isValid = false;
					errorMess = "Indice cannot be blank";
					return;
				} else if(indiceChange[i].name != indiceChange[i].value) {
					var changedInd = domdoc.createElement("INDICE_VALUE");
					changedInd.setAttribute('current',indiceChange[i].name);
					changedInd.appendChild(domdoc.createTextNode(indiceChange[i].value));
					rootDoc.appendChild(changedInd);
				}
			}
		}
		isValid = true;
		errorMess = "";
	}

	function allowDigi(evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		if (((charCode >= 48 && charCode <= 57) // is digit
			|| charCode == 13 || charCode == 8 || charCode == 9) || (charCode == 37) || (charCode == 39)) { // is enter or backspace key or arrowkeys  
			return true;
		}
		else { // non-digit
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
								
		if( (pool.indexOf(character) != -1)
                || (charCode == 8) || (charCode == 9) || (charCode == 37) || (charCode == 39) || (charCode == 46) ) {
            return true;
		} else if(charCode == 13) {
			submitEditCabinet();
			return true;
		} 
        return false;
	}
</script>
<title>$tableTitle</title>
</head>
<body class="centered">
<div class="mainDiv">
<div class="mainTitle"><span>$tableTitle</span></div>
<div style="padding-top:5px;padding-bottom:5px">$selectCabLabel
 <select id="cabSelect" onchange="selectCabinet()">
  <option value="default">$selectCabLabel</option>\n
ENERGIE;
	$user->setSecurity();
	foreach( $user->cabArr AS $real => $arb ) {
		echo "<option value='$real'>$arb</option>\n";	
	}
echo<<<ENERGIE
 </select>
</div>
<div id="editIndices"></div>
</div>
</body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
