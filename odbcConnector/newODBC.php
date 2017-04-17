<?php

include_once '../check_login.php';
include_once '../classuser.inc';

include_once '../lib/cabinets.php';
include_once 'odbcDSNMaster.php';
include_once 'odbcDSN.php';

echo <<<ENERGIE
<?xml version="1.0" encoder="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>New ODBC Connection</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<style type="text/css">
div#formDiv {
	width: 85%;
	margin: auto;
	text-align: right;
}
div#goodTestDiv {
	width: 80%;
	margin: auto;
	text-align: left;
}
form {
	margin: 0;
	padding: 0;
}
input {
	margin: 0.25em;
}
body.centered {
	font-size: 13px;
}
div.notFirstDiv {
    border-top: 2px solid black;
}
div.selectDiv {
    padding: 1em;
}
</style>
<script type="text/javascript">
function submitForm()
{
	document.getElementById('odbcForm').submit();
}
</script>
</head>
<body class="centered">

ENERGIE;
if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isDepAdmin())
{
	$db_object = $user->getDbObject();
	if($_POST['connectName']) {
		$odbcInfo = array(
			'connectName'	=> $_POST['connectName'],
			'host'			=> $_POST['host'],
			'password'		=> $_POST['password'],
			'dBaseName'		=> $_POST['dBaseName'],
			'userName'		=> $_POST['userName'],
			'id'			=> $_POST['id']
		);
	}
	if($_POST['testConnect']) {
		$_SESSION['odbcInfo'] = $odbcInfo;
		$testOK = testODBCConnect($odbcInfo);
		if(!$testOK) $errMsg = "Testing the connection failed";
	} else if($_POST['addConnect']) {
		$odbcInfo = $_SESSION['odbcInfo'];
		unset($_SESSION['odbcInfo']);
		
		//Insert new connection into the database
		$queryArr = $odbcInfo; 
		$result = $db_object->extended->autoExecute('odbc_connect', $queryArr);
		if(PEAR::isError($result)) die($result->getMessage());
		
	} else if($_POST['cancelConnect']) {
		$odbcInfo = $_SESSION['odbcInfo'];
		unset($_SESSION['odbcInfo']);
		$dispName = str_replace("_", " ", $odbcInfo['connectName']);
	} else if($_POST['changeConnect']) {
		$odbcInfo = $_SESSION['odbcInfo'];
		unset($_SESSION['odbcInfo']);
		$queryArr = $odbcInfo;
		unset($queryArr['id']);
		$where = "id = ".$db_object->quote($odbcInfo['id']);
		$result = $db_object->extended->autoExecute('odbc_connect', $queryArr,
										  MDB2_AUTOQUERY_UPDATE, $where);
		if(PEAR::isError($result)) die($result->getMessage());
		unset($odbcInfo);
		unset($_POST);
	}
	echo <<<ENERGIE
<div class="mainDiv">
<div class="mainTitle">
<span>ODBC Connections</span>
</div>

ENERGIE;
	$availActions = array(
		'addConnection'		=> 'Add a Connection',
		'editConnection'	=> 'Edit a Connection',
		'delConnection'		=> 'Delete a Connection'
	);
	$action = $_POST['odbcAction'];
	echo <<<ENERGIE
<form id="odbcForm" method="post" action="{$_SERVER['PHP_SELF']}">
<div class="selectDiv">
<span>Select an Action:</span>
<select name="odbcAction" onchange="submitForm('selectAct')">

ENERGIE;
	if($action) {
		echo <<<ENERGIE
<option value="$action">{$availActions[$action]}</option>

ENERGIE;
		unset($availActions[$action]);
	} else {
		echo <<<ENERGIE
<option selected="selected">Select an Action</option>

ENERGIE;
	}

	foreach($availActions as $myAction => $myString) {
		echo <<<ENERGIE
<option value="$myAction">$myString</option>

ENERGIE;
	}
	echo <<<ENERGIE
</select>
</div>
ENERGIE;

	if($testOK) {
		$myConnectName = str_replace("_", " ", $odbcInfo['connectName']);
		if($action === "editConnection")
			$cancelStr = "Cancel Edit";
		else
			$cancelStr = "Cancel Creation";
		echo <<<ENERGIE
<div class="notFirstDiv">
<div id="goodTestDiv">
<div>Connection Name: $myConnectName</div>
<div>Host: {$odbcInfo['host']}</div>
<div>Database Name: {$odbcInfo['dBaseName']}</div>
<div>Username: {$odbcInfo['userName']}</div>
<div>Password: &lt;hidden&gt;</div>
</div>
</div>
<form method="post" action="{$_SERVER['PHP_SELF']}">
<div>

ENERGIE;
		if($action === "editConnection") {
			echo <<<ENERGIE
<input type="submit" name="changeConnect" value="Change ODBC Connection"/>

ENERGIE;
		} else {
			echo <<<ENERGIE
<input type="submit" name="addConnect" value="Create ODBC Connection"/>

ENERGIE;
		}
		echo <<<ENERGIE
<input type="submit" name="cancelConnect" value="$cancelStr"/>
</div>
</form>

ENERGIE;
		unset($action);
	}
	if($action === 'editConnection') {
		$odbcConnect = $_POST['odbcConnect'];
		$table = "odbc_connec";
		$connArray = getTableInfo($db_object,$table);

		if(PEAR::isError($connArray)) die($connArray->getMessage());
		if(count($connArray) > 0) {
			echo <<<ENERGIE
<div class="selectDiv notFirstDiv">
<span>Select a Connection:</span>
<select name="odbcConnect" id="selectEdit" onchange="submitForm('selectEdit')">

ENERGIE;
			if($odbcConnect) {
				$dispName = str_replace('_', ' ', $odbcConnect);
				echo <<<ENERGIE
<option value="$odbcConnect">$dispName</option>

ENERGIE;
			} else {
				echo <<<ENERGIE
<option>Select A Connection</option>

ENERGIE;
			}
			foreach($connArray as $myConnection) {
				$realName = $myConnection['connectName'];
				if($realName !== $odbcConnect) {
					$dispName = str_replace('_', ' ', $realName);
					echo <<<ENERGIE
<option value="$realName">$dispName</option>

ENERGIE;
				} else {
					$odbcInfo = $myConnection;
				}
			}
			echo <<<ENERGIE
</select>
</div>

ENERGIE;
		} else {
			echo <<<ENERGIE
<div class="notFirstDiv">
<p class="error">There are no saved connections.</p>
</div>

ENERGIE;
			$noConn = true;
		}
	}
	if($action === 'addConnection' or ($action === 'editConnection' and 
			$odbcConnect)) {
		$dispName = str_replace('_', ' ', $odbcInfo['connectName']);
		$connTxt = " value=\"$dispName\"";
		$hostTxt = " value=\"{$odbcInfo['host']}\"";
		$dBaseTxt = " value=\"{$odbcInfo['dBaseName']}\"";
		$unameTxt = " value=\"{$odbcInfo['userName']}\"";
		if($action === 'editConnection') {
			echo <<<ENERGIE
<div>
<input type="hidden" name="id" value="{$odbcInfo['id']}"/>
</div>

ENERGIE;
		}
		echo <<<ENERGIE
<div class="selectDiv notFirstDiv">
<div id="formDiv">
<div>
<span>Connection Name:</span>
<input name="connectName" size="20"$connTxt/>
</div>
<div>
<span>Host:</span>
<input name="host" size="20"$hostTxt/>
</div>
<div>
<span>Database Name:</span>
<input name="dBaseName" size="20"$dBaseTxt/>
</div>
<div>
<span>Username:</span>
<input name="userName" size="20"$unameTxt/>
</div>
<div>
<span>Password:</span>
<input name="password" type="password" size="20"/>
</div>
</div>
<div>
<input type="submit" name="testConnect" value="Test ODBC Connection"/>
</div>

ENERGIE;
		if($errMsg) {
			echo <<<ENERGIE
<p class="error">$errMsg</p>

ENERGIE;
		}
	}
echo <<<ENERGIE
</div>
</div>

ENERGIE;
}
echo <<<ENERGIE
</body>
</html>

ENERGIE;

function testODBCConnect($myArray)
{
	$username = $password = $host = $dBaseName = $userName = '';
	extract($myArray);
	PEAR::setErrorHandling(PEAR_ERROR_RETURN);
	$dsn = "odbc://$userName:$password@$host/$dBaseName";
	$db = getDatabaseConn('odbc',$userName,$password,$host,$dBaseName);
	if(PEAR::isError($db)) {
		return false;
	} else {
		$db->disconnect();
		return true;
	}
}
// modeline for vim, PLEASE LEAVE
// vi:ai:sw=4:ts=4:noet
?>
