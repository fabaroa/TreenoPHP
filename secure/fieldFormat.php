<?PHP
// $Id: fieldFormat.php 14213 2011-01-04 16:11:08Z acavedon $

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

	function selectCabinet() {
		var selBox = $('cabList');
		removeDefault(selBox);
		cab = selBox.options[selBox.selectedIndex].value;

		var xmlArr = {	"include"	: "secure/fieldFormatAction.php",
						"function"	: "xmlGetFieldFormats",
						"cabinet"	: cab };
		postXML(xmlArr);
	}

	function saveValues() {
		var xmlArr = new Object();
		var formatTable = $('formatTable');
		cabinet = $('cabList').value;

		xmlArr['include'] = 'secure/fieldFormatAction.php';	
		xmlArr['function'] = 'xmlAddFieldFormats';
		xmlArr['cabinet'] = cabinet;

		xmlArr['numIndex'] = formatTable.rows.length-1;		
		for(var k = 0; k < formatTable.rows.length-1; k++) { 
			xmlArr['index-'+k] = $('index-'+k).firstChild.nodeValue;
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

	function fillFieldFormats(XML) {
		$('inputDiv').style.display = 'none';
		createFormatTable();
		var formatTable = $('formatTable');
		var indices = XML.getElementsByTagName('INDEX');
		if(indices.length > 0) {
			for(var j = 0; j < indices.length; j++) {
				var indexingTypeDefs = indices[j].getAttribute('indexingTypeDefs');
				var row = formatTable.insertRow(formatTable.rows.length);
				for(var k = 0; k < tableArr.length; k++) {
					var element = tableArr[k];
					var attr = indices[j].getAttribute(element);
					var col = row.insertCell(row.cells.length);
					col.style.fontSize = '8pt';
					col.style.whiteSpace = 'nowrap';
					var sp = document.createElement('span');
					if( element == "index" ) {
						sp.id = 'index-'+j;
						sp.appendChild(document.createTextNode(attr));
						col.appendChild(sp);
					} else if( element == "required" ) {
						var checkBox = document.createElement('input');
						checkBox.type = 'checkbox';
						checkBox.id = 'required-'+j;
						col.appendChild(checkBox); 
						if( attr == 1 ) {
							checkBox.checked=true;
						} else {
							checkBox.checked=false;
						}
					} else if( element == "isdate" ) {
						var checkBox = document.createElement('input');
						checkBox.type = 'checkbox';
						checkBox.id = 'isdate-'+j;
						col.appendChild(checkBox);
						if( attr == 1 ) {
							checkBox.checked=true;
						} else {
							checkBox.checked=false;
						}
					}else if( element == "regex" ) {
						var txtBox = document.createElement('input');
						txtBox.type = 'text';
						txtBox.id = 'regex-'+j;
						if( indexingTypeDefs == "1" ) {
							txtBox.disabled = true;
							txtBox.value = "DISABLED";
						} else {
							txtBox.value = attr;
						}
						col.appendChild(txtBox);
					} else {
						var displayBox = document.createElement('input');
						displayBox.type = 'text';
						displayBox.id = 'display-'+j;
						if( indexingTypeDefs == "1" ) {
							displayBox.disabled = true;
							displayBox.value = "DISABLED";
						} else {
							displayBox.value = attr;
						}
						col.appendChild(displayBox);

						var col = row.insertCell(row.cells.length);
						var txtBox = document.createElement('input');
						txtBox.type = 'text';
						txtBox.id = 'test-'+j;
						txtBox.className = "testing";
						txtBox.ct = j;
						if( indexingTypeDefs == "1" ) {
							txtBox.disabled = true;
							txtBox.value = "DISABLED";
						}
						col.appendChild(txtBox);
					}
				}
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

	div.cabDiv {
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
<body class="centered">
	<div class="mainDiv">
		<div class="mainTitle">
			<span><?PHP echo $tableTitle ?></span>
		</div>
		<div id="cabDiv" class="cabDiv">
			<select id="cabList"
				class="cabList"
				name="cabList"
				onchange="selectCabinet()">
				<option value="__default">Choose a Cabinet</option>
				<?PHP foreach($user->cabArr AS $key => $value): ?>
				<option value="<?PHP echo $key; ?>"><?PHP echo $value; ?></option>
				<?PHP endforeach; ?>
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
