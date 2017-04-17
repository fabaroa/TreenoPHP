<?php 
// $Id: printAnyBarcode.php 15099 2014-05-29 19:35:59Z fabaroa $
require_once('../check_login.php');
require_once '../db/db_common.php';
require('../classuser.inc');
$db_name = $user->db_name;
$db_dept = getDbObject($db_name);
$selArr = array('document_table_name','document_type_name');
$docArr = getTableInfo($db_dept,'document_type_defs',$selArr,array(),'getAssoc');
$docTypeArr = array();
foreach ($docArr as $key=>$doctype)
{
	$select="select count(id) as cnt from document_field_defs_list where document_table_name='".$key."'";
	$results = $db_dept->queryOne($select);
	if ($results == 0)
	{
		$docTypeArr[]=$doctype;
	}
}


$printAnyBarcode = "Print Any Barcode";
$enterSomeText = "Select a DocType to Barcode";
$printBarcode = "Print Barcode";
?>
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title><?php echo $printAnyBarcode; ?></title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<style type="text/css">
form {
	padding-top: 5px;
}
</style>
<script type="text/javascript">
function submitBarcode()
{
	var e = document.getElementById("barcodeStr");
	var barcodeVal = e.options[e.selectedIndex].value;
	var locStr = "getBarcode.php?barcode=" + barcodeVal;
	parent.leftFrame1.window.location = locStr;
	return false;
}
</script>
</head>
<body class="centered">
<div class="mainDiv">
<div class="mainTitle">
<span><?php echo $printAnyBarcode; ?></span>
</div>
<form name="printAnyBarcode" action="" onsubmit="return submitBarcode()">
<div>
<table style="display: inline;" class="myTitle">
 <tr>
  <td><?php echo $enterSomeText; ?></td>
  <td><!--<input id="barcodeStr" type="text" />-->
  	<select id="barcodeStr">
  	<option value="" disabled="disabled" selected="selected">Please select a DocType</option>
    <option value="<?php if ($db_name=='client_files'){ echo '0'; } else { echo str_replace('client_files','',$db_name);} ?> 0">Public Inbox</option>
<?php
foreach ($docTypeArr as $docType)
{
	echo "<option value='TAB_".str_replace(" ","_",strtoupper($docType))."'>".$docType."</option>\n";
}
?>
  </td>
 </tr>
</table>
<input value="<?php echo $printBarcode ?>" type="button" onclick="submitBarcode()" />
</div>
</form>
</div>
</body>
</html>

<?php

	setSessionUser($user);
?>
