<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/movefileActions.php';

if( $logged_in == 1 && $user->username) {
	$cab		= $_GET['cab'];
	$doc_id		= $_GET['doc_id'];
	if (isset ($_GET['tab_id'])) {
		$tab_id = (int) $_GET['tab_id'];
	} else {
		$tab_id = 0;
	}
	if (isset ($_GET['temp_table'])) {
		$temp_table = $_GET['temp_table'];
	} else {
		$temp_table = '';
	}
	if (isset ($_GET['index'])) {
		$index = (int) $_GET['index'];
	} else {
		$index = 0; 
	}

	$dispCab = (isSet($_GET['dispCab'])) ? $_GET['dispCab'] : "";
	$dispDocID = (isSet($_GET['dispFolder'])) ? $_GET['dispFolder'] : "";

	$documentView = $user->checkSetting('documentView',$cab);
	if ($documentView and $tab_id > 0) {
		$tableArr = array ('document_field_defs_list', 'document_field_value_list', $cab.'_files');
		$selArr = array ('arb_field_name', 'document_field_value');
		$whereArr = array (
			'document_field_defs_list.id = document_field_value_list.document_field_defs_list_id',
			'document_field_defs_list.document_table_name = '.$cab.'_files.document_table_name',
			$cab.'_files.document_id = document_field_value_list.document_id',
			$cab.'_files.id = ' . $tab_id,
		);
		$currDocArr = getTableInfo ($db_object, $tableArr, $selArr, $whereArr, 'queryAll', 
			array ('document_field_defs_list.ordering' => 'ASC'));
		$tmpArr = array ();
		foreach ($currDocArr as $myDoc) {
			$tmpArr[] = $myDoc['arb_field_name'] . ': ' . $myDoc['document_field_value'];
		}
		$currDoc = implode (', ', $tmpArr);
	} else {
		$currDoc = ''; 
	}
echo<<<ENERGIE
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
 <head>
  <link rel="stylesheet" type="text/css" href="../lib/style.css"/>
  <script>
	var origDocumentView = ("$documentView") ? "$documentView" : "";
	var documentView = ("$documentView") ? "$documentView" : "";
	var moveCabinet = "$dispCab";
	var moveFolder = "$dispDocID";
	var isFolderSelected = "";
	var isTabSelected = "";
	var isDocumentSelected = "";
	var temp_table = "$temp_table";
	var cabinet = "$cab";
	var folderID = $doc_id;
	var tabID = $tab_id;
	var index = $index;
	var deleted = 'deleted=0';
	var divType = 'movefiles';
  </script>
  <script type="text/javascript" src="../lib/settings.js"></script>
  <script type="text/javascript" src="../lib/moveFiles.js"></script>
  <style>
	div#tableDiv {
		margin-right: auto;
		margin-left: auto;
		overflow: auto;
		width: 75%;
		height: 300px;
		border-style: solid;
		border-color: black;
		border-width: 1px;
	}

	.header {
		padding-top: 15px;
		padding-bottom: 2px;
		font-weight: bold;
		font-size: 12px;
	}

	#addDocOuterDiv {
		margin-top	: 20px;
		width		: 300px;
		border-style: double;
		border-width: 5px;
		border-color: #003B6F;
		display		: none;
	}

  </style>
 </head>
 <body onload="selectCabinet()" class="centered">
  <div class="mainDiv" style='width:80%'>
   <div class="mainTitle">
    <span>Move File Destination</span>
   </div>
   <div class="inputForm" style="padding:5px" id="movefiles">
	<div style="padding-bottom:5px;font-weight:bold;font-size:12px">Cabinet:</div>
	<div>
	 <select id="cabSelect" name="cab" onchange="selectCabinet()">\n
	  <option value="personal">Personal Inbox</option>
ENERGIE;
	if(!check_enable('lite',$user->db_name)) {
		echo "<option value='inbox'>Public Inbox</option>\n";
	}
	foreach($user->cabArr AS $real => $arb) {
		if($user->access[$real] == 'rw') {
			if( isSet($_GET['dispCab']) && $real == $_GET['dispCab'] ) {
				echo "<option selected=selected value=\"$real\">$arb</option>\n";
			} elseif( !isSet($_GET['dispCab']) && $real == $cab ) {
				echo "<option selected=selected value=\"$real\">$arb</option>\n";
			} else {
				echo "<option value=\"$real\">$arb</option>\n";
			}
		}
	}
echo<<<ENERGIE
	 </select>
	</div>
   </div>

   <div id='errMsg' class='error'></div>
   <div id='folderDisplay'>
	<div class='header'>Folder:</div>
	<div style="padding-top:2px;padding-bottom:2px">
	 <input type='text' id='folderSearch' name='folderSearchVal' onkeypress="return allowDigi(event)">
	</div>
	<div style="padding-top:2px;padding-bottom:10px">
	 <input style='margin-right:5px' type='button' name='search' value='GO' onclick="selectCabinet(document.getElementById('folderSearch'))"> 
	 <input type='button' name='add' value='Add Folder' onclick="addFolder()"> 
	</div>
	<div id='tableDiv' class="inputForm">
	 <table id='folderList' width="75%" cellpadding="0" cellspacing="1" style='white-space:nowrap'></table>
	</div> 
   </div>

   <div id='tabDisplay' style='display:none'>
	<div class='header'>Tab:</div>
	<div style='padding:5px'>
	 <select id='tabList' name='tab'></select>
	</div>
   </div>

   <div id='documentDisplay'>
	<div class='header'>Document:</div>
	<div id='documentDiv'></div> 
	<div style="margin-top:10px">
		<input type="button" name="" value="Add Document" onclick="toggleAddDocument()"/> 
	</div>
	<div id="addDocOuterDiv" style="margin-right:auto;margin-left:auto">
		<div class="mainTitle">
			<span>Add Document</span>
		</div>
		<div id="docSelDiv2" style="margin-right:auto;margin-left:auto"></div>
		<div id="addDocumentDiv" style="margin-top:10px;margin-bottom:10px;"></div>
		<div style="text-align:center">
			<input type="button" 
				id="addDocumentBtn"
				name="addDocumentBtn" 
				value="Add" 
				onclick="addNewDoc()"
			/>
			<input type="button" 
				id="addDocumentCnl"
				name="addDocumentCnl" 
				value="Cancel" 
				onclick="cancelAddDoc()"
			/>
		</div>
		<div id="addDocErr" class="error">&nbsp;</div>
	</div>
   </div>

   <div id='buttonsDisplay' style='display:none;padding:5px'>
    <input type='button' name='button1' value='Copy' onclick='moveFiles("copy")'>
    <input type='button' name='button2' value='Cut' onclick='moveFiles("cut")'>
   </div>
   
  </div>
 </body>
</html>
ENERGIE;
    setSessionUser($user);
} else {
	logUserOut();
}	
?>
