<?php
//$Id: indexingAction.php 14657 2012-02-06 13:48:38Z acavedon $

echo <<<HTML
<style type="text/css">
#indexingDiv {
	width: 100%;
	height: 30%;
}
#viewDiv {
	width: 100%;
	height: 70%;
}
#errDiv {
	position: absolute;
	width: 100%;
	left: 0;
	bottom: 0;
}
.navBtn {
	cursor: pointer;
}
body, html {
	padding: 0;
	margin: 0;
	height: 100%;
	font-size: 11px;
}
#navDiv table {
	margin: auto;
}
#rowTable {
	width: 100%;
}
#rowTable td {
	white-space: nowrap;
}
#rowDiv {
	width: 100%;
	overflow-x: scroll;
	overflow-y: hidden;
} 
/*this caused a problem in IE, you couldn't enter notes
#notes, #currPageInput {
	position: relative;
	z-index: -100;
}
*/
</style>
<link rel="stylesheet" type="text/css" href="../lib/calendar.css"/>
<script src="../lib/calendar.js"></script>		
<script type="text/javascript">
var fileIDs = new Array ();
var currPage = 0;
var cabinet = '$cab';
var docID = $doc_id;
var file_id = $file_id;
var needInsert = false;
</script>
HTML;
if($actionNeeded) {
	$db_doc = getDbObject ('docutron');
$gblStt = new GblStt($user->db_name, $db_doc);
$dateFuncs = $gblStt->get('date_functions');
if (!$dateFuncs) {
	$dateFuncs = 'false';
}
$indices = getCabinetInfo($db_object, $cab);
$cabinetID = getTableInfo($db_object, 'departments', array('departmentid'),
		array('real_name' => $cab), 'queryOne');
$whereArr = array('doc_id'=>(int)$doc_id);
$folderRes = getTableInfo($db_object,$cab,array(),$whereArr);
$folder = $folderRes->fetchRow();
$newArr = array ();
foreach($indices as $myIndex) {
	$newArr[] = "'$myIndex'";
}
$newArrStr = implode(',', $newArr);
$autoCompleteTable = $gblStt->get('indexing_'.$cab);
$indexTypeDefStr = 'dt,'.$user->db_name.','.$cabinetID.',';
if($autoCompleteTable) {
	$onKeyPressCmd = 'acKeys(event)';
} else {
	$onKeyPressCmd = 'acKeysSubmit(event)';
}
$onKeyPressCmd2 = 'acKeysSubmit(event)';
//echo<<<HTML
?>
<script type="text/javascript" src="../lib/prototype.js"></script>	
<script type="text/javascript">
var dateFunctions = '$dateFuncs';
//alert(window.name);	//mainFrame
//alert(window.location); //https://localhost/workflow/getSignature.php?cab=Sales_Orders&doc_id=3
function extIndexMove(cab,fieldNames,fieldValues, tabName) 
{
	if(cabinet == cab) 
	{
		var getTextFields = document.getElementsByTagName('input');
		for(var i=0;i<getTextFields.length;i++) 
		{
			if(getTextFields[i].type == 'text') 
			{
				var txtname = getTextFields[i].name;
				for(var j=0;j<fieldNames.length;j++) 
				{
					if(txtname == fieldNames[j]) 
					{
						getTextFields[i].value = fieldValues[j];
						break;
					}
				}
			}
		}

		xmlDoc = createDOMDoc ();
		var rootDoc = xmlDoc.createElement ('LEGACY');
		xmlDoc.appendChild (rootDoc);
		var cabDoc = xmlDoc.createElement ('CABINET');
		cabDoc.appendChild (xmlDoc.createTextNode (cab));
		rootDoc.appendChild (cabDoc);
		var tabDoc = xmlDoc.createElement ('TAB');
		tabDoc.appendChild (xmlDoc.createTextNode (tabName));
		rootDoc.appendChild (tabDoc);
		for (var i = 0;i < fieldNames.length; i++) 
		{
			var index = xmlDoc.createElement ('INDEX');
			index.appendChild (xmlDoc.createTextNode( fieldValues[i]));
			index.setAttribute ('name',fieldNames[i]);
			rootDoc.appendChild (index);
		}
	
		var xmlStr = domToString(xmlDoc);
		//alert("ajax request: " + xmlStr);
		
		var newAjax = new Ajax.Request( 'ajaxIndexingAction.php?updateIndexes=1&doc_id=<?php echo $doc_id; ?>',
									{	method: 'post',
										postBody: xmlStr,
										onComplete: receiveXML,
										onFailure: reportError} );
											
	} 
	else 
	{
		alert('you are currently viewing the wrong cabinet');
	}
}

function receiveXML(req) 
{
	var XML = req.responseXML;
	var msg = XML.getElementsByTagName('ERROR');
	if(msg.length > 0) 
	{	
		m = msg[0].firstChild.nodeValue;
		alert(m);
	}
}

function reportError(req)
{
}
		
</script>
<?php

echo<<<HTML
</head>
<body onload="loadInitialImg()">
<div id="viewDiv" style="overflow:hidden">
<iframe id="myEmbed" style="height: 100%; width: 100%"
src="../lib/IEByPass.htm"></iframe>
</div>
<div id="indexingDiv">
<div id="rowDiv">
<table id="rowTable">
<tr>
HTML;
	for ($i = 0; $i < count($indices); $i++) {
		echo '<td>'.str_replace('_', ' ', $indices[$i]).'</td>';
}
echo '</tr><tr>';
for ($i = 0; $i < count($indices); $i++) {
	echo '<td>';
	$indexTypeDefs = $gblStt->get($indexTypeDefStr.$indices[$i]);
	if($indexTypeDefs) {
		$defArr = explode(',,,', $indexTypeDefs);
		echo '<select tabindex="'.($i + 1).'" id="field-'.$i.'" name="'.$indices[$i].'">';
		echo '<option value=""></option>';
		foreach($defArr as $myDef) {
			if($folder[$indices[$i]] == $myDef) {
				echo '<option value="'.$myDef.'" selected="selected">'.$myDef.'</option>';
			} else {
				echo '<option value="'.$myDef.'">'.$myDef.'</option>';
			}
		}
		echo '</select>';
	} else {
	
		if($i == 0) {
			echo '<input type="text" ' .
					'onkeypress="'.$onKeyPressCmd.'" ' .
					'id="field-'.$i.'" ' .
					'name="'.$indices[$i].'" ' .
					'tabindex="'.($i + 1).'" ' .
					'value="'.$folder[$indices[$i]].'"/>';
		} else {
			echo '<input type="text" ' .
					'onkeypress="'.$onKeyPressCmd2.'" ' .
					'id="field-'.$i.'" ' .
					'name="'.$indices[$i].'" ' .
					'tabindex="'.($i + 1).'" ' .
					'value="'.$folder[$indices[$i]].'"/>';
		}
	}
	echo '</td>';
}
echo '</tr>';
echo '</table>';
echo '</div>';
echo '<div id="btnSearchDiv">';
if($autoCompleteTable) {
	echo "<button id=\"searchBtn\" onclick=\"searchAutoComplete('field-0', '$indices[0]', '$autoCompleteTable')\">Search</button>";
}
echo "<button onclick=\"acceptIndexingNode()\">Accept</button>";
echo "<button onclick=\"rejectIndexingNode()\">Reject</button>";
echo '</div>';
$newTabIndex = $i + 1;
echo<<<HTML
<div id="notesDiv">
<textarea id="notes" onfocus="clearArea(this)"tabindex="$newTabIndex" rows="3" cols="25">Enter notes here</textarea>
</div>
<div style="text-align: center" id="navDiv">
<table>
<tr>
<td><img src="../energie/images/begin_button.gif" alt="First" title="" class="navBtn" onclick="goToFirstPage()" /></td>
<td><img src="../energie/images/back_button.gif" alt="Previous" title="" class="navBtn" onclick="goToPrevPage()" /></td>
<td><input id="currPageInput" type="text" size="1" onkeypress="checkKeyForPage(event)" /></td>
<td><img src="../energie/images/next_button.gif" alt="Next" title="" class="navBtn" onclick="goToNextPage()" /></td>
<td><img src="../energie/images/end_button.gif" alt="Last" title="" class="navBtn" onclick="goToLastPage()" /></td>
</tr>
</table>
</div>
<form method="post" id="indexingForm" action="../workflow/getSignature.php?cab=$cab&amp;doc_id=$doc_id&amp;file_id=$file_id">
<input type="hidden" id="submitField" name="Submit" value="" />
<input type="hidden" id="notesField" name="notes" value="" />
HTML;

	$sArr = array('id');
	$wArr = array("cab='$cab'",
				'doc_id='.$doc_id,
				"(file_id=$file_id OR file_id=-2)",
				"status='IN PROGRESS'");
	$wfIds = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryCol');
	$selArr = array('username','date_time','notes','action');
	$wfHistory =
		getTableInfo ($db_object, 'wf_history', $selArr,
				array('wf_document_id'=>(int)$documentInfo['id']), 'queryAll', array('id' =>
					'DESC'));
		if( $wfHistory ) {
			echo "<div class=\"inputForm\" style=\"padding:5px\" id=\"movefiles\"><div style=\"overflow:auto;height:250px\">\n";
		echo "<table>\n";
		echo "<tr class=\"tableheads\">\n";
		echo "<td colspan=\"4\">Document History</td>\n";
		echo "</tr>\n";
		echo "<tr class=\"tableheads\">\n";
		echo "<td style=\"border:0px;\">Username</td>\n";
		echo "<td style=\"border:0px;\">Date</td>\n";
		echo "<td style=\"border:0px;\">Action</td>\n";
		echo "<td style=\"border:0px;\">Notes</td>\n";
		echo "</tr>\n";
		foreach( $wfHistory AS $history ) {
			echo "<tr>\n";
			echo "<td>".str_replace(","," ",$history['username'])."</td>\n";
			echo "<td>".$history['date_time']."</td>\n";
			echo "<td>".$history['action']."</td>\n";
			echo "<td>".h($history['notes'])."</td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n";	
	echo "</div></div>\n";
		}

} else {
echo <<<HTML
	<div class="error" align="center">{$nodeObj->noActionMsg}</div>
HTML;
}
echo '</div>
</body></html>';
?>
