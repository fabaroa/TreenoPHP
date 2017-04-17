<?PHP
// $Id: documentFieldFormat.php 14326 2011-04-11 20:31:25Z fabaroa $
include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in == 1 AND strcmp($user->username, "") != 0) {
	$tableTitle = "Index Requirements";
?>

<html>
<head>
<title>Index Requirements</title>
<script type="text/javascript" src="../lib/settings2.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript">
	var headerArr = new Array("Index", "Required", "isDate", "Regular Expression", "Example", "Test");
	var tableArr = new Array("index", "required", "isdate", "regex", "display");

	function loadDocuments() {
		var xmlArr = {	"include"	: "documents/documents.php",
						"function"	: "xmlGetDocumentTypes" };
		postXML(xmlArr);
	}

	function fillDocumentTypeDropDown(XML) {
		var docList = XML.getElementsByTagName('DOCUMENT');	
		if(docList.length > 0) {
			var docSel = $('docList');
			addDefault(docSel);
			for(var i=0;i<docList.length;i++) {
				var docType = docList[i].firstChild.nodeValue;
				var opt = document.createElement('option');
				opt.value = docList[i].getAttribute('name');
				opt.appendChild(document.createTextNode(docType));
				docSel.appendChild(opt);
			}
		}
	}

	function selectDocument() {
		var selBox = $('docList');
		removeDefault(selBox);
		docType = selBox.options[selBox.selectedIndex].value;

		var xmlArr = {	"include"				: "documents/documents.php",
						"function"				: "getDocumentFields",
						"document_table_name"	: docType };
		postXML(xmlArr);
	}

	function saveValues() {
		var xmlArr = new Object();
		var formatTable = $('formatTable');
		docType = $('docList').value;

		xmlArr['include'] = 'secure/fieldFormatAction.php';	
		xmlArr['function'] = 'xmlAddFieldFormats';
		xmlArr['document'] = docType;

		xmlArr['numIndex'] = formatTable.rows.length-1;		
		for(var k = 0; k < formatTable.rows.length-1; k++) { 
			xmlArr['index-'+k] = $('index-'+k).name;
			if($('required-'+k).checked) {
				xmlArr['required-'+k] = "1";
			} else {
				xmlArr['required-'+k] = "0";
			}

			if($('isdate-'+k).checked) {
				xmlArr['isdate-'+k] = "1";
			} else {
				xmlArr['isdate-'+k] = "0";
			}

			xmlArr['regex-'+k] = $('regex-'+k).value;
			xmlArr['display-'+k] = $('display-'+k).value;
		}
		postXML(xmlArr);	
	}

	function setMessage(XML) {
		var errNode = XML.getElementsByTagName('message');
		var err = errNode[0].firstChild.nodeValue;
		removeElementsChildren($('errMsg'));
		$('errMsg').appendChild(document.createTextNode(err));
	}

	function fillDocumentTypeFields(XML) {
		$('inputDiv').style.display = 'none';
		createFormatTable();
		var formatTable = $('formatTable');
		var indices = XML.getElementsByTagName('FIELD');
		if(indices.length > 0) {
			for(var j = 0; j < indices.length; j++) {
				var dtDefs = indices[j].getElementsByTagName('DEFINITION');

				var row = formatTable.insertRow(formatTable.rows.length);

				var name = indices[j].getAttribute('name');
				var field = indices[j].firstChild.nodeValue;
				var col = row.insertCell(row.cells.length);
				col.style.fontSize = '8pt';
				col.style.whiteSpace = 'nowrap';

				var sp = document.createElement('span');
				sp.id = 'index-'+j;
				sp.name = name;
				sp.appendChild(document.createTextNode(field));
				col.appendChild(sp);

				var required = 0;
				var req = indices[j].getElementsByTagName('REQUIRED');
				if(req.length > 0) {
					required = parseInt(req[0].firstChild.nodeValue);	
				}

				var col = row.insertCell(row.cells.length);
				col.style.fontSize = '8pt';
				col.style.whiteSpace = 'nowrap';

				var checkBox = document.createElement('input');
				checkBox.type = 'checkbox';
				checkBox.id = 'required-'+j;
				col.appendChild(checkBox); 
				if( required == 1 ) {
					checkBox.checked=true;
				} else {
					checkBox.checked=false;
				}

				var isDate = 0;
				var dateElement = indices[j].getElementsByTagName('ISDATE');
				if(dateElement.length > 0) {
					isDate = parseInt(dateElement[0].firstChild.nodeValue);
				}

				var dateCol = row.insertCell(row.cells.length);
				dateCol.style.fontSize = '8pt';
				dateCol.style.whiteSpace = 'nowrap';

				var dateCheckBox = document.createElement('input');
				dateCheckBox.type = 'checkbox';
				dateCheckBox.id = 'isdate-'+j;
				dateCol.appendChild(dateCheckBox);
				if( isDate == 1 ) {
					dateCheckBox.checked=true;
				} else {
					dateCheckBox.checked=false;
				}

				var regex = "";
				var reg = indices[j].getElementsByTagName('REGEX');
				if(reg.length > 0) {
					if(reg[0].firstChild) {	
						regex = reg[0].firstChild.nodeValue;	
					}
				}

				var col = row.insertCell(row.cells.length);
				col.style.fontSize = '8pt';
				col.style.whiteSpace = 'nowrap';

				var txtBox = document.createElement('input');
				txtBox.type = 'text';
				txtBox.id = 'regex-'+j;
				if( dtDefs.length > 0 ) {
					txtBox.disabled = true;
					txtBox.value = "DISABLED";
				} else {
					txtBox.value = regex;
				}
				col.appendChild(txtBox);

				var display = "";
				var disp = indices[j].getElementsByTagName('DISPLAY');
				if(disp.length > 0) {
					if(disp[0].firstChild) {
						display = disp[0].firstChild.nodeValue;	
					}
				}

				var col = row.insertCell(row.cells.length);
				col.style.fontSize = '8pt';
				col.style.whiteSpace = 'nowrap';

				var displayBox = document.createElement('input');
				displayBox.type = 'text';
				displayBox.id = 'display-'+j;
				if( dtDefs.length > 0 ) {
					displayBox.disabled = true;
					displayBox.value = "DISABLED";
				} else {
					displayBox.value = display;
				}
				col.appendChild(displayBox);

				var col = row.insertCell(row.cells.length);
				var txtBox = document.createElement('input');
				txtBox.type = 'text';
				txtBox.id = 'test-'+j;
				txtBox.className = "testing";
				txtBox.ct = j;
				if( dtDefs.length > 0 ) {
					txtBox.disabled = true;
					txtBox.value = "DISABLED";
				}
				col.appendChild(txtBox);
			}
		}

		$('inputDiv').style.display = 'block';
		$('actionDiv').style.display = 'block';
	}

	function check4ValidRegex() {
		var testList = document.getElementsByClassName('testing');
		if(testList.length > 0) {
			for(i=0;i<testList.length;i++) {
				el = testList[i];
				regex = $('regex-'+el.ct).value;
				if(regex && regex != "DISABLED") {
					if(isValid(regex,el.value)) {
						el.style.backgroundColor = 'LightGreen';
					} else {
						el.style.backgroundColor = 'OrangeRed';
					}
				}
			}
		}
	}

	function isValid(regex,val) {
		var regExpObj = new RegExp(regex);
		v = regExpObj.exec(val);
		if( regExpObj.test(val) ) {
			return true;
		} else {
			return false;
		}
	}

	function createFormatTable() {
		removeElementsChildren($('inputDiv'));
		removeElementsChildren($('errMsg'));
		var userDiv = document.createElement('div');
		userDiv.id = 'formatDiv';
		userDiv.style.overflow = 'auto';

		var formatTable = document.createElement('table');
		formatTable.id = 'formatTable';
		formatTable.style.whitespace = 'nowrap';
		formatTable.paddingLeft = '35px';
		formatTable.cellPadding = '5px';
		formatTable.style.textAlign = 'center';
		userDiv.appendChild(formatTable);

		var row = formatTable.insertRow(formatTable.rows.length);
		row.style.fontWeight = 'bold';
		for(var i = 0; i < headerArr.length; i++) {
			var col = row.insertCell(row.cells.length);
			col.style.whiteSpace = 'nowrap';
			var sp = document.createElement('span');
			sp.style.fontSize = '8pt';
			sp.appendChild(document.createTextNode(headerArr[i]));
			col.appendChild(sp);
		}

		$('inputDiv').appendChild(userDiv);
	}
</script>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<style type="text/css">
	.testing {

	}

	div.mainDiv {
		width		: 730px;
		text-align	: center;
		margin-left	: auto;
		margin-right: auto;
	}

	div.docDiv {
		padding-top : 10px;
		height		: 35px;
	}

	span.column {
		text-align		: right;
		padding-right	: 10px;
		padding-left	: 10px;
	}

	div.inputDiv {
		text-align	: center;
		padding-top	: 10px;
		display		: none;
	}

	div.actionDiv {
		text-align	: right;
		margin-right: auto;
		margin-left	: auto;
		padding-top	: 10px;
		padding-right : 10px;
		height		: 35px;
		display		: none;
	}
</style>
</head>
<body class="centered" onload="loadDocuments()">
	<div class="mainDiv">
		<div class="mainTitle">
			<span><?PHP echo $tableTitle ?></span>
		</div>
		<div id="docDiv" class="docDiv">
			<select id="docList"
				class="docList"
				name="docList"
				onchange="selectDocument()">
			</select>
		</div>
		<div id="inputDiv" class="inputDiv"></div>
		<div id="actionDiv" class="actionDiv">
			<span id="errMsg" class="error"></span>
			<input type="button" name="verify" value="Verify" onclick="check4ValidRegex()">
			<input type="button" name="submit" value="Save" onclick="saveValues()">
		</div>
	</div>
</body>
</html>
<?PHP
	setSessionUser($user);
} else {
	logUserOut();
}
?>
