<?PHP
include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in ==1 && strcmp($user->username,"")!=0) {
echo<<<ENERGIE
<html>
<head>
<title>Inbox Delegation</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript">
	function hideAddFolder() {
        if(top.mainFrame.document.getElementById('addNewActionDiv')) {
            top.mainFrame.document.getElementById('addNewActionDiv').style.display = 'none';
        }
	}
	
	function checkForSelected (checkBoxType) {
		var i = 1;
		var el;
		var anySelected = false;
		while (el = top.mainFrame.document.getElementById (checkBoxType + i)) {
			if (el.checked) {
				anySelected = true;
				break;
			}
			i++;
		}
		return anySelected;
	}

	function printError(message)
	{
		var myDoc = top.mainFrame.window.document;
		var errMsg = document.getElementById ('errMsg');
		while (errMsg.hasChildNodes ()) {
			errMsg.removeChild (errMsg.firstChild);
		}
		var txtStr = message;
		var myTxt = document.createTextNode (txtStr);
		errMsg.appendChild (myTxt);
	}

	function submitDelegate()
	{
		var submit = true;
		if( !checkForSelected('fileCheck:') ) {
			printError('Please Select Files or Folders to Delegate');
			submit = false;
		}

		if( checkForSelected('delFileCheck:') ) {
			printError('Cannot select delegated files');
			submit = false;
		}

		if(submit)
			delegate();
	}

	function delegate() {
		var myDoc = top.mainFrame.window.document;
		myDoc.getElementById('comments').value = document.getElementById('comments').value;
		myDoc.getElementById('delegated_user').value = document.getElementById('delegated_user').value;
		myDoc.getElementById('status').value = document.getElementById('status').value;
		myDoc.filename.action += '&delegate=1';
		myDoc.filename.submit ();
	}
</script>
</head>
<body class="centered">
<div class="mainDiv" id="mainDiv">
	<div class="mainTitle">
		<span>Inbox Delegation</span>
	</div>
	<form name="getDelegation" action="{$_SERVER['PHP_SELF']}">
		<table class="inputTable">
ENERGIE;
	if( sizeof($userList) > 1 ) {

		echo "<tr><td>Select User:</td>\n";
		echo "<td><select style='margin:0;padding:0' id='delegated_user' name='delegated_user'>\n";
		foreach($userList AS $uname) {
			if($uname == $_GET['username']) {
                echo "<option selected value='$uname'>$uname</option>\n";
            } else if($uname == $user->username && !$_GET['username']) {
                echo "<option selected value='$uname'>$uname</option>\n";
            } else {
                echo "<option value='$uname'>$uname</option>\n";
            }
		}
		echo "</select>\n";
		echo "</td></tr>\n";

		echo "<tr><td>Status:</td>\n";
		echo "<td><select style='margin:0;padding:0' id='status' name='status'>\n";
		foreach($statusArr AS $status) {
			if( strcmp($status, $selectedStatus) == 0  )
				echo "<option selected value='$status'>$status</option>";
			else
				echo "<option value='$status'>$status</option>";
		}
		echo "</td></tr>\n";

		echo "<tr><td>Comments:</td>\n";
		echo "<td><textarea rows='4' cols='20' id='comments' name='comments'></textarea>\n";
		echo "</td></tr>\n";		
	}
echo<<<ENERGIE
		</table>
		<div style='float: right'>
			<input type='button' value='Cancel' onclick='hideAddFolder()' name='btnCnl'/>
			<input type='button' value='Delegate' 
				onclick='submitDelegate()' name='btnDelegate'>
		</div>
		<div id='errMsg' class='error'>
ENERGIE;

	if( $mess != null )
		echo str_replace("_", " ", $mess);
	else
		echo "&nbsp;";

echo<<<ENERGIE
		</div>
	</form>
</div>
</body>
</html>

ENERGIE;
	setSessionUser($user);
} else {
    logUserOut();
}
?>
