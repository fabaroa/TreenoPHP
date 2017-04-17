<?php 
// $Id: odbcWizard.php 14225 2011-01-04 16:30:45Z acavedon $

include_once '../db/db_common.php';
include_once '../check_login.php';

if($logged_in and $user->username) {
	echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="language" content="en-us" />
<title>ODBC Mapping Wizard</title>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/help.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/odbcWizard.js"></script>
<script type="text/javascript">
var selectedArr = new Array();
var selectedNum = 0;
var cabIndices = new Array();
var odbc_level = 1;
var odbc_auto_id = 0;
var trans = false;
var previousTrans = '';
var prev_bool = false;
var test_connection = 0;
var pg = 1;
</script>
<link rel="stylesheet" type="text/css" href="../lib/style.css">
<link rel="stylesheet" type="text/css" href="../lib/odbcWizard.css">
</head>
<body class="centered">
<div id="outerDiv" class="mainDiv">

<div class="mainTitle">
 <span>ODBC Mapping Wizard</span>
</div>

<div id="wizard" style="height:230px; width:750px; padding-top:35px">
<div id="cabinetDiv" style="position:absolute;top:60px;text-align:left;padding-left:5px">
	<span id="selectedCabinet" style="font-style:italic"></span>
</div>

<div id="page1" style="height:200px">
<span>Welcome to the ODBC Mapping Wizard</span>
</div>

<div id="page2" style="padding-left:10%;height:200px" class="hideDiv">
<div id="whichConnDiv">
 <table class="systemSelectMenu">
  <tr>
   <td class="systemLabel">
    <label style="cursor:help" onclick="requestHelp(this,'odbcConnector','english')" for="whichConn">Connector</label>
   </td>
  </tr>
  <tr>
   <td class="systemSelect">
    <select id="whichConn" onchange="connSelected(this)">
     <option value="__default">Choose One</option>
HTML;
	$db_doc = getDbObject('docutron');
	$odbcList = getTableInfo($db_doc,'odbc_connect',array('id','connect_name'),array(),'getAssoc',array('connect_name'=>'ASC'));
	foreach($odbcList AS $id => $name) {
		echo "<option value='$id'>$name</option>";
	}
echo<<<HTML
    </select>
   </td>
  </tr>
 </table>
 </div>

 <div id="whichCabDiv" style="padding-top: 25px">
 <table class="systemSelectMenu">
  <tr>
   <td class="systemLabel">
    <label style="cursor:help" onclick="requestHelp(this,'odbcCabinet','english')" for="cabinet_name">Cabinet</label>
   </td>
  </tr>
  <tr>
   <td class="systemSelect">
    <select id="cabinet_name" disabled="disabled" onchange="cabSelected(this)">
     <option value="__default">Choose One</option>
HTML;
	foreach($user->cabArr AS $real => $arb) {
		if($user->access[$real] != 'none') {
			echo "<option value='$real'>{$arb}</option>\n";
		}
	}
echo<<<HTML
    </select>
   </td>
  </tr>
 </table>
 </div>
</div>

<div id="page3" style="padding-left:10%;height:200px" class="hideDiv">
 <table class="systemSelectMenu">
  <tr>
   <td class="systemLabel">
    <input type="radio" checked="checked" name="mappingType" value="1">
	<label style="cursor:help" onclick="requestHelp(this,'odbcAddMapping','english')" for="mappingType">Add / Edit</label>
   </td>
  </tr>
  <tr>
   <td class="systemLabel" style="padding-top:55px">
    <input type="radio" name="mappingType" value="0">
	<label style="cursor:help" onclick="requestHelp(this,'odbcRemoveMapping','english')" for="mappingType">Remove</label>
   </td>
  </tr>
 </table>
</div>

<div id="page4" style="padding-left: 10%;height:200px" class="hideDiv">
 <div id="whichConnDiv" style="float: left; width: 35%">
 <table class="systemSelectMenu">
  <tr>
   <td class="systemLabel">
	<label style="cursor:help" onclick="requestHelp(this,'odbcTable','english')" for="odbcTableSel">ODBC Table</label>
   </td>
  </tr>
  <tr>
   <td class="systemSelect">
    <select id="odbcTableSel" name="odbcTableSel" onchange="retrieveMappingInfo(4)">
     <option value="__default">Choose One</option>
    </select>
   </td>
  </tr>
 </table>
 </div>
 
 <div id="odbcColumnsDiv" class="odbcColumnsDiv">
  <table border='0' cellpadding='0' cellspacing='0' width="100%">
   <thead class="odbcColumnsTHead">
    <tr>
	 <th width="320px">
	  <label style="cursor:help" onclick="requestHelp(this,'odbcTableFieldName','english')">Name</label>
	 </th>
	 <th width="30px">
	  <label style="cursor:help" onclick="requestHelp(this,'odbcFK','english')">FK</label>
	 </th>
	 <th width="30px">
	  <label style="cursor:help" onclick="requestHelp(this,'odbcPK','english')">PK</label>
	 </th>
	 <th width="46px">
	  <label style="cursor:help" onclick="requestHelp(this,'odbcQuoted','english')">Quoted</label>
	 </th>
	</tr>
   </thead>
   <tbody id="odbcColumnsTBody" class="odbcColumnsTBody">
   </tbody>
  </table>
 </div>

 <div id="odbc_trans" style="position: relative; top: 50px; float: left" class="hideDiv">
  <div style="font-weight:bold">
   <span>
	<label style="cursor:help" onclick="requestHelp(this,'odbcTrans','english')" for="odbc_trans_sel">ODBC Trans</label>
   </span>
  </div>
  <table>
   <tr>
    <td id="odbc_trans_name"></td>
    <td>
     <select id="odbc_trans_sel" name="odbc_trans_sel" onchange="disableODBCColumn(this)">
      <option value="__default">Choose One</option>
     </select>
    </td>
   </tr>
  </table>
 </div>
 
</div>

<div id="page5" class="hideDiv" style="height:200px">
 <div id="cabinetMappingDiv" class="cabinetMappingDiv" style="float: left">
  <table border="0" cellpadding="0" cellspacing="0" width="100%">
   <thead class="cabinetMappingTHead">
	<tr>
	 <th style="width:115px">
	  <label style="cursor:help" onclick="requestHelp(this,'odbcDocutronFieldName','english')">Cabinet</label>
	 </th>
	 <th style="width:285px">
	  <label style="cursor:help" onclick="requestHelp(this,'odbcTableFieldName','english')">ODBC Mapping</label>
	 </th>
	</tr>
   </thead>
   <tbody id="cabinetMappingTBody" class="cabinetMappingTBody">
   </tbody>
  </table>
 </div>
 <div id="mappingTestDiv" class="mappingTestDiv">
  <table id="mappingTestTable" border="0" cellpadding="0" cellspacing="1" width="100%">
   <thead class="mappingTestTHead">
	<tr>
	 <th colspan="2"><input type="button" name="test" value="Test Mapping" onclick="testMapping()"></th>
	</tr>
    <tr>
     <td style="width:125px" onclick="requestHelp(this,'odbcTestMapping','english')">Search Value</td>
     <td style="width:191px"><input type="text" size="25" id="searchValue" name="searchValue"></td>
    </tr>
   </thead>
   <tbody id="mappingTestTBody" class="mappingTestTBody">
   </tbody>
  </table>
 </div>
</div>

<div id="page6" style="padding-left: 10%;height:200px" class="hideDiv">
 <table class="systemSelectMenu">
  <tr>
   <td class="systemLabel">
    <input type="radio" name="enableMapping" checked="checked" value="1">
	<label style="cursor:help" onclick="requestHelp(this,'odbcEnableMapping','english')" for="whichConn">Enable ODBC Mapping</label>
   </td>
  </tr>
  <tr>
   <td class="systemLabel" style="padding-top:25px;">
    <input type="radio" name="enableMapping" value="0">
	<label style="cursor:help" onclick="requestHelp(this,'odbcDisableMapping','english')" for="whichConn">Disable ODBC Mapping</label>
   </td>
  </tr>
 </table>
</div>

<div id="page7" style="padding-left: 10%;height:200px" class="hideDiv">
 <table class="systemSelectMenu">
  <tr>
   <td class="systemDescription">You have successfully created an ODBC Mapping for the selected cabinet</td>
   </td>
  </tr>
 </table>
</div>

<div id="page8" style="padding-left: 10%;height:200px" class="hideDiv">
 <table class="systemSelectMenu">
  <tr>
   <td class="systemDescription">You have successfully removed the ODBC Mapping for the selected cabinet</td>
   </td>
  </tr>
 </table>
</div>

<div id="buttonControl">
  <div style="float:left;width:400px;text-align:right">
   <span id="errorMsg" class="error"></span>
  </div>
  <div style="float:right;width:200px">
	<input id="prevPage" type="button" name="back" value="Back" disabled="disabled">
	<input id="nextPage" type="button" name="next" value="Next" onclick="nextPage(2)">
	<input id="cancelWizard" type="button" name="cancel" value="Cancel" onclick="cancelWizard(0)">
  </div>
</div>

</div>
</div>
</body>
</html>
HTML;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
