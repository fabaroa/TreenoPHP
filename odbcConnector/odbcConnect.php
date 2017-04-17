<?php
//odbcConnect.php
//Create a mapping between indices and fields in a remote ODBC database to be
//used for auto_complete_indexing

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';

echo <<<ENERGIE
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Full ODBC Setup</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<style type="text/css">
form {
	margin: 0;
	padding: 0;
}
p.iTitle {
	display: inline;
	padding: 1em;
}
div.selectDiv {
	padding: 1em;
}
div.notFirstDiv {
	border-top: 2px solid black;
}
table {
	width: 90%;
	border-collapse: collapse;
	margin: auto;
}
td {
	border-top: 1px solid gray;
	border-bottom: 1px solid gray;
	font-size: 9pt;
}
th {
	font-size: 9pt;
}
.fieldName {
	text-align: right;
}
.indexName {
	text-align: left;
}
p#submitBtn {
	display: none;
}
</style>
<script type="text/javascript">

//This function is called on the select boxes' onchange to automatically submit
//form
function submitForm(viaSelect)
{
	var i = 0;
	if(viaSelect == "cabinet") {
		//We came here after selecting a cabinet

		//disable select boxes before submitting form to prevent the values
		//selected in the select boxes from being posted - if a new cabinet
		//is selected, all other data in the form is erroneous and needs to be
		//reentered.
		if(document.getElementById('odbcSelect'))
			document.getElementById('odbcSelect').disabled = true;
		if(document.getElementById('tableSelect'))
			document.getElementById('tableSelect').disabled = true;
		while(document.getElementById('select-' + i)) {
			document.getElementById('select-' + i).disabled = true;
			i++;
		}
	} else if(viaSelect == "db") {
		//We came here after selected a connection
		
		//the list of tables will be different depending on the database, so
		//disable the select before posting.
		if(document.getElementById('tableSelect')) {
			document.getElementById('tableSelect').disabled = true;
		}
	}
	document.getElementById('odbcForm').submit();
}

//this function deletes option elements from the select elements so that a
//currently selected option can never be selected by another select
function indexSelect(fields)
{
	//passing an array from server-side to client-side, this array has all
	//fields in the odbc database
	var fieldArr = fields.split(',');
	var mySelect, i, j, notFound, selectedValue;
	var notSelectedFields = new Array();
	var notSelected = false;

	//loop through each select box in the fields portion of the form, and create
	//an array that only includes the values that are not currently selected
	//by the select boxes
	for(i = 0; i < fieldArr.length; i++) {
		notFound = true;
		j = 0;
		mySelect = document.getElementById('select-' + j);
		while(mySelect) {
			selectedValue = mySelect.options[mySelect.selectedIndex].value;
			if(selectedValue == fieldArr[i]) {
				notFound = false;
			} else if(selectedValue == "Select a Field") {
				//if this is still in the select box, nothing has been selected
				notSelected = true;
			}
			j++;
			mySelect = document.getElementById('select-' + j);
		}
		if(notFound) {
			//if it is not currently in a box selected, add it to the list of
			//unselected fields
			notSelectedFields.push(fieldArr[i]);
		}
	}
	i = 0;
	mySelect = document.getElementById('select-' + i);
	while(mySelect) {

		//loop through all select boxes, removing all values except those that
		//are selected - each box will have one value
		while(mySelect.length != 1) {
			if(mySelect.selectedIndex == 0) {
				mySelect.remove(1);
			} else {
				mySelect.remove(0);
			}
		}

		//add the options that are not selected anywhere to each select box.
		for(j = 0; j < notSelectedFields.length; j++) {
			var newOpt = document.createElement("option");
			newOpt.text = notSelectedFields[j];
			newOpt.value = notSelectedFields[j];
			if(document.all) {
				//IE only, just a little different syntax from DOM
				mySelect.add(newOpt);
			} else {
				//All others
				mySelect.add(newOpt, null);
			}
		}
		i++;
		mySelect = document.getElementById('select-' + i);
	}
	
	//if all fields have a fieldname in it, show the submit button
	if(notSelected == false) {
		document.getElementById('submitBtn').style.display = 'block';
	}
}

//This disables all select boxes in the indices/fields portion and submits form
//to reset the fields.
function resetFields()
{
	var i = 0;
	var mySelect = document.getElementById('select-' + i);
	while(mySelect) {
		mySelect.disabled = true;
		i++;
		mySelect = document.getElementById('select-' + i);
	}
	document.getElementById('odbcForm').submit();
}

</script>
</head>
<body class="centered">
ENERGIE;

if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin())
{
	$db_object = $user->getDbObject();
	$db_doc = getDbObject('docutron');
	
	//if set, this page has been posted by itself
	$cabinetName = $_POST['cabinetName'];
	$cabinetList = getTableInfo($db_object, 'departments', array(), array('deleted' => 0), 'queryCol');
	//if only 1 cabinet, select it automatically, do not wait for user to select
	//it manually.
	if(count($cabinetList) == 1) $cabinetName = $cabinetList[0];
	if($cabinetName)
		$indexNames = getCabinetInfo( $db_object, $cabinetName );
	if($_POST['inDBRemove']) {
		deleteTableInfo($db_object,'odbc_auto_complete',array('cabinetName'=>$cabinetName));
		if(PEAR::isError($result)) die($result->getMessage());
		echo <<<ENERGIE
<script type="text/javascript">
window.location.href = "{$_SERVER['PHP_SELF']}";
</script>

ENERGIE;
	} else if($_POST[$indexNames[0].'Trans']) {
		deleteTableInfo($db_object,'odbc_auto_complete',array('cabinetName'=>$cabinetName));

		if(PEAR::isError($result)) die($result->getMessage());
		
		foreach($indexNames as $index) {
			$transArray[] = "$index~".$_POST[$index.'Trans'];
		}
		$queryArr = array(
			'cabinetName'	=> $_POST['cabinetName'],
			'odbcDriver'	=> $_POST['odbcDriver'],
			'tableName'		=> $_POST['tableName'],
			'trans'			=> implode('@', $transArray)
		);
		$result = $db_object->extended->autoExecute('odbc_auto_complete', $queryArr,
										  MDB2_AUTOQUERY_INSERT);
		if(PEAR::isError($result)) die($result->getMessage());
		
		dropTable($db_object,"auto_complete_".$cabinetName);
		
		$gblStt = new GblStt($user->db_name, $db_doc);
		
		$gblStt->set('indexing_'.$cabinetName, 'odbc_auto_complete');
		echo <<<ENERGIE
<script type="text/javascript">
window.location.href = "{$_SERVER['PHP_SELF']}";
</script>

ENERGIE;
		
	} else {
		echo <<<ENERGIE
<div class="mainDiv">
<div class="mainTitle">
<span>Full ODBC Setup</span>
</div>
<form method="post" action="{$_SERVER['PHP_SELF']}" id="odbcForm">
<div class="selectDiv">
<p class="iTitle myTitle">Select a Cabinet:</p>
<select name="cabinetName" onchange="submitForm('cabinet')">

ENERGIE;
		if($cabinetName) {
			$result = getTableInfo($db_object,'odbc_auto_complete',array(),array('connectName'=>$cabinetName));
			if(PEAR::isError($result)) die($result->getMessage());
			if($row = $result->fetchRow()) {
				$odbcDriver = $row['odbcDriver'];
				$tableName = $row['tableName'];
				$transArray = explode("@", $row['trans']);
				foreach($transArray as $transStr) {
					$myTrans = explode("~", $transStr);
					$selectNames[$myTrans[0].'Trans'] = $myTrans[1];
					$inDB = true;
				}
			}	
			$dispName = $user->cabArr[$cabinetName];
			echo <<<ENERGIE
<option selected="selected" value="$cabinetName">$dispName</option>

ENERGIE;
		} else {
			echo <<<ENERGIE
<option selected="selected">Select a Cabinet</option>

ENERGIE;
		}
		foreach($cabinetList as $myCab) {
			$depName = $myCab;
			if($cabinetName !== $depName) {
				$dispName = $user->cabArr[$depName];
				echo <<<ENERGIE
<option value="$depName">$dispName</option>

ENERGIE;
		}
	}
		echo <<<ENERGIE
</select>
</div>

ENERGIE;
		if($cabinetName) {
			$odbcDriver or $odbcDriver = $_POST['odbcDriver'];
			$connList = getTableInfo($db_object,'odbc_connect',array('connect_name'),array().'queryCol');	

			//if only one, automatically select
			if(count($connList) == 1) $odbcDriver = $connList[0];
			echo <<<ENERGIE
<div class="selectDiv notFirstDiv">
<p class="iTitle myTitle">Select a Connection:</p>
<select name="odbcDriver" id="odbcSelect" onchange="submitForm('db')">

ENERGIE;
			if($odbcDriver) {
				$dispName = str_replace('_', ' ', $odbcDriver);
				echo <<<ENERGIE
<option selected="selected" value="$odbcDriver">$dispName</option>

ENERGIE;
			} else {
				echo <<<ENERGIE
<option selected="selected">Select a Connection</option>

ENERGIE;
			}
			if(PEAR::isError($connList)) die($result->getMessage());
			foreach($connList as $myConn) {
				if($myConn !== $odbcDriver) {
					$dispName = str_replace("_", " ", $myConn);
					echo <<<ENERGIE
<option value="$myConn">$dispName</option>

ENERGIE;
				}
			}
			echo <<<ENERGIE
</select>
</div>

ENERGIE;
			if($odbcDriver) {
				$tableName or $tableName = $_POST['tableName'];
				
			$result = getTableInfo($db_object,'odbc_connect',array(),array('connectName'=>$odbcDriver));
			if(PEAR::isError($result)) die($result->getMessage());

				$row = $result->fetchRow();
				$dsnHost = $row['host'];
				$dsnPass = $row['password'];
				$dsnUser = $row['userName'];
				$dsnDB = $row['dBaseName'];
				$odbcDBObj = getODBCDbObject($dsnHost,$dsnPass,$dsnDB,$dsnUser);
				$tables = getTableNames($odbcDBObj);

				//if only one, automatically select
				if(count($tables) == 1) $tableName = $tables[0];
				echo <<<ENERGIE
<div class="selectDiv notFirstDiv">
<p class="iTitle myTitle">Select a Table:</p>
<select name="tableName" id="tableSelect" onchange="submitForm('table')">

ENERGIE;
				if($tableName) {
					echo <<<ENERGIE
<option selected="selected" value="$tableName">$tableName</option>

ENERGIE;
				} else {
					echo <<<ENERGIE
<option selected="selected">Select a Table</option>

ENERGIE;
				}
				foreach($tables as $myTable) {
					if($myTable !== $tableName) {
						echo <<<ENERGIE
<option value="$myTable">$myTable</option>

ENERGIE;
					}
				}
				echo <<<ENERGIE
</select>
</div>

ENERGIE;
				if($tableName) {
					$colNames = queryColumnNames($odbcDBObj, $tableName);

					//only allow user to use the table if there are at least as
					//many fields in the table as there are indices in cabinet
					if(count($colNames) < count($indexNames)) {
						echo <<<ENERGIE
<div class="selectDiv notFirstDiv">
<p class="error">There are not enough columns in this table.</p>
</div>

ENERGIE;
					} else {
					
						//Used to pass array from server-side to client-side
						$colStr = implode(',', $colNames);

						//Used to figure out which fields have been selected and
						//which fields are to be displayed
						if(!$selectNames) {
							foreach($indexNames as $index) {
								$idx = $index.'Trans';
								$selectNames[$idx] = $_POST[$idx];
							}
						}
						echo <<<ENERGIE

<div class="selectDiv notFirstDiv">
<table>
<tr>
<th class="indexName">Indices in Cabinet</th>
<th class="fieldName">Fields in Table</th>
</tr>

ENERGIE;
						for($i = 0; $i < count($indexNames); $i++) {
							$selectName = $indexNames[$i].'Trans';
							
							//used to iterate through the select boxes on
							//client-side
							$selectID = "select-$i";
							echo <<<ENERGIE
<tr>
<td class="indexName">{$indexNames[$i]}</td>
<td class="fieldName">
<select name="$selectName" id="$selectID" onchange="indexSelect('$colStr')">

ENERGIE;
							if($selectNames[$selectName]) {
								//User posted this index translation
								$mySelectName = $selectNames[$selectName];
								echo <<<ENERGIE
<option selected="selected" value="$mySelectName">$mySelectName</option>

ENERGIE;
							} else {
								//First time through
								echo <<<ENERGIE
<option selected="selected">Select a Field</option>

ENERGIE;
								$notAllSelected = true;
							}
							foreach($colNames as $myCol) {
								if(!in_array($myCol, $selectNames)) {
									echo <<<ENERGIE
<option value="$myCol">$myCol</option>

ENERGIE;
								}
							}
							echo <<<ENERGIE
</select>
</td>
</tr>

ENERGIE;
						}
						echo <<<ENERGIE
</table>

ENERGIE;
						if($inDB) {
							echo <<<ENERGIE
<p>
<input type="submit" name="inDBRemove" value="Remove Connection"/>
</p>

ENERGIE;
						} else {
							//Reset fields so that they can be selected again
							echo <<<ENERGIE
<p>
<input type="button" value="Reset Field Selection" onclick="resetFields()"/>
</p>

ENERGIE;
						}
						//hide submit if there are some fields that are not
						//selected
						if($notAllSelected) {
							echo <<<ENERGIE
<p id="submitBtn">

ENERGIE;
						} else {
							echo <<<ENERGIE
<p id="submitBtn" style="display: block">

ENERGIE;
						}
						echo <<<ENERGIE
<input type="submit" value="Submit"/>
</p>
</div>

ENERGIE;
					}
				} 
			}
		}
		
		echo <<<ENERGIE
</form>
</div>

ENERGIE;
	}
} else {
	echo <<<ENERGIE
<script type="text/javascript">
top.window.location.href = "../logout.php";
</script>

ENERGIE;
}

echo <<<ENERGIE
</body>
</html>

ENERGIE;


	setSessionUser($user);

//returns a PEAR::DB object that  connects to the odbc database
function getODBCDbObject($host, $password, $dBaseName, $userName)
{
	PEAR::setErrorHandling(PEAR_ERROR_RETURN);
	$dsn = "odbc://$userName:$password@$host/$dBaseName";
	return getDatabaseConn('odbc',$userName,$password,$host,$dBaseName);
}

//Hack function that uses the native odbc functions to get a list of tables
//from the odbc database server
function getTableNames($dbObj)
{
	$tableRes = odbc_tables($dbObj->connection);
	while($tableArray = odbc_fetch_array($tableRes)) {
		$tables[] = $tableArray['Table_name'];
	}
	return $tables;
}

//Hack function that uses the native odbc functions to get a list of fields
//from the odbc database server in the table
function queryColumnNames($dbObj, $table)
{
	$colRes = odbc_columns($dbObj->connection, "", "", $table);
	while($colArray = odbc_fetch_array($colRes)) {
		$cols[] = $colArray['Column_name'];
	}
	return $cols;
}
// Modeline for vim, PLEASE LEAVE
// vi:ai:sw=4:ts=4:noet
?>
