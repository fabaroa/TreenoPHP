<html>
	<head>
		<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
		<style type="text/css">
			th {
				padding-left: 5px;
				padding-right: 5px;
			}
			td {
				padding-left: 5px;
				padding-right: 5px;
			}
		</style>
		<script type="text/javascript" src="../lib/prototype.js"></script>
		<script type="text/javascript" src="../lib/windowTitle.js"></script>
		<script type="text/javascript" src="../lib/settings2.js"></script>
		<script>
			var folder = '';

			function generateTemplate() {
				folder = $('searchPath').value;
				var xmlArr = { "include" : "canada/readRepo.php",
								"function" : "generateTemplate",
								"folder"	: folder };
				postXML(xmlArr);
			}

			function printMessage(XML) {
				var message = XML.getElementsByTagName('MESSAGE');
				message = message[0].firstChild.nodeValue;
				$('errMsg').appendChild(document.createTextNode(message));
				$('processButton').style.display = 'none';
			}

			function loadTemplate(XML) {
				var inputDiv = $('inputDiv');
				removeElementsChildren(inputDiv);

				var userTable = document.createElement('table');
				userTable.id = 'folderTable';
				userTable.style.width = "100%";
				userTable.style.whiteSpace = 'nowrap';
				userTable.paddingLeft = '35px';
				inputDiv.appendChild(userTable);

				var row = userTable.insertRow(userTable.rows.length);
				var XMLindicesArr = XML.getElementsByTagName('INDICES');
				var indices = XMLindicesArr[0].getElementsByTagName('INDEX');
				var indicesArr = new Array();

				//Sets up the table headers
				row.style.fontWeight = 'bold';
				for( var k = 0; k < indices.length; k++ ) {
					indicesArr[k] = indices[k].firstChild.nodeValue;
					var col = row.insertCell(row.cells.length);
					col.appendChild(document.createTextNode(indices[k].firstChild.nodeValue));
				}

				var entriesArr = XML.getElementsByTagName('ENTRY');
				//Sets up the folders
				for( var k = 0; k < entriesArr.length; k++ ) {
					row = userTable.insertRow(userTable.rows.length);
					row.style.backgroundColor = '#D3D3D3';
					var index = entriesArr[k].getElementsByTagName('INDEX');
					for( var j = 0; j < indicesArr.length; j++) {
						var attr = index[0].getAttribute(indicesArr[j]);
						if( !attr) {
							attr = "";
						}
						var col = row.insertCell(row.cells.length);
						col.appendChild(document.createTextNode(attr));
					}

					var filesArr = entriesArr[k].getElementsByTagName('FILE');
					//Sets up the files in each folder
					for( var j = 0; j < filesArr.length; j++ ) {
						var attr = filesArr[j].getAttribute('filename');
						row = userTable.insertRow(userTable.rows.length);
						col = row.insertCell(row.cells.length);
						col.appendChild(document.createTextNode(" "));
						col = row.insertCell(row.cells.length);
						col.style.textAlign = 'center';
						col.appendChild(document.createTextNode(attr));
						col = row.insertCell(row.cells.length);
						col.appendChild(document.createTextNode(" "));
						col = row.insertCell(row.cells.length);
						col.appendChild(document.createTextNode(" "));
					}
				}

				if(entriesArr.length > 0) {
					$('processButton').style.display = 'inline';
				}
			}

			function processTemplate() {
				var xmlArr = { "include"	: "canada/readRepo.php",
								"function"	: "processTemplate",
								"folder"	: folder };
				postXML(xmlArr);
			}
		</script>
	</head>

	<body class="centered">
		<div>
			<input id="searchPath" type="text" name="searchPath" value="wellid" onkeypress="allowDigi(event)">
			<input id="generateButton" type="button" onclick="generateTemplate()" name="Generate" value="Generate">
		</div>
		<div id='inputDiv' class="inputForm" style="margin-bottom: 0px;">
		</div>
		<div>
			<input id="processButton" type="button" onclick="processTemplate()" name="Process" value="Process" style="display:none">
		</div>
		<div id="errMsg" class='error'></div>
	</body>
</html>
