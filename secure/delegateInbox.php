<?PHP
include_once '../check_login.php';
include_once '../db/db_common.php';
include_once '../lib/delegate.php';

//Only allow someone to get through if they are logged in.
if($logged_in == 1 && strcmp($user->username, "") != 0) {
global $DEFS;
$path = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/";
$username = $user->username;
$db_obj = getDBObject($user->db_name);
$delegateObj = new delegate($path, $username, $db_obj);
$delegateList = $delegateObj->getDelegateList("file", "ASC");
echo "<pre>";
print_r($delegateList);

echo<<<ENERGIE
<html>
<head>
<title>Inbox Delegation</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css">
<style type="text/css">
</style>
<script>
	function folderClicked(delegateID)
	{
		var rowElement;
		var j = 0;
		while( el = document.getElementById(delegateID + ':' + j)) {
			if(el.style.display == 'none')
				el.style.display = 'table-row';
			else
				el.style.display = 'none';
			j++;
		}
	}
</script>
</head>
<body>

<div id="outerDiv">

	<div id="delegateTable">
		<table border="2">
			<thead>
				Delegate Inbox
			</thead>
ENERGIE;
		foreach($delegateList AS $arrayID => $folderArr) {
			if( strcmp($arrayID, "") == 0 ) { //show delegate file
				foreach($folderArr AS $folderName => $delegateItem) {
					$delegateID = $delegateItem['id'];
					echo "<tr id=$delegateID>\n";
					echo "	<td>";
					echo "<img id='file:$delegateID' style=\"height: 24px; width: 24px; border: 0\"
                        src='../energie/images/file_thumb.gif'  alt=\"File\" title=\"\">\n";
					echo "</td>\n";//iconr
					echo "	<td id=checkBox:$delegateID>\n";
					echo "		<input type=\"checkbox\" id=\"check:$delegateID\" name=\"delCheck1[]\" value=\"$delegateID\">\n";
					echo "	</td>\n"; //checkbox
					echo "	<td id=edit:$delegateID>\n";
					echo "		<img style=\"height: 16px; border: 0\"\n";
					echo "			 src='../energie/images/file_edit_16.gif' alt=\"Edit File\" title=\"\" ";
					echo "			onclick='editFolder()'>\n";
					echo "	</td>\n"; //edit icon
					echo "	<td>".$delegateItem['file']."</td>\n";  //filename
					echo "	<td>".$delegateItem['delegate_owner']."</td>\n";  //delegated by
					echo "	<td>".$delegateItem['delegate_username']."</td>\n";  //delegated to
					echo "	<td>".$delegateItem['dtime']."</td>\n";  //time created
					echo "</tr>\n";
				}
			} else { //show a folder line item
				$delegateID = $delegateItem['id'];
				echo "<tr id=$delegateID>\n";
				echo "	<td onclick='folderClicked($delegateID);'>";
				echo "   <img id='file:$delegateID' style=\"height: 24px; width: 24px; border: 0\" ";
                echo "		src='../images/folder.png' alt=\"Folder\" title=\"\">\n";
				echo "	</td>\n";//iconr
				echo "	<td id=checkBox:$delegateID>\n";
				echo "		<input type=\"checkbox\" id=\"check:$delegateID\" name=\"delCheck1[]\" value=\"$delegateID\">\n";
				echo "	</td>\n"; //checkbox
				echo "	<td id=edit:$delegateID>\n";
				echo "		<img style=\"height: 16px; border: 0\"\n";
				echo "			 src='../energie/images/file_edit_16.gif' alt=\"Edit Folder\" title=\"\" ";
				echo "			onclick='editFolder()'>\n";
				echo "	</td>\n"; //edit icon
				echo "	<td onclick='folderClicked($delegateID);'>$arrayID</td>\n";  //blank filename
				echo "	<td onclick='folderClicked($delegateID);'>&nbsp;</td>\n";  //delegated by
				echo "	<td onclick='folderClicked($delegateID);'>&nbsp;</td>\n";  //delegated to
				echo "	<td onclick='folderClicked($delegateID);'>&nbsp;</td>\n";  //time created
				echo "</tr>\n";
				$j = 0;
				foreach($folderArr AS $folderName => $delegateItem) { // foreach file in the folder
					echo "<tr id=$delegateID:$j style=\"display:none\">\n";
					echo "	<td>&nbsp;</td>\n"; 
					echo "	<td>";
					echo "		<img id='file:$delegateID:$j' style=\"height: 24px; width: 24px; border: 0\"
			                        src='../energie/images/file_thumb.gif'  alt=\"File\" title=\"\">\n";
					echo "	</td>\n";//icon
					echo "	<td id=edit:$delegateID:$j>\n";
					echo "		<img style=\"height: 16px; border: 0\"\n";
					echo "			 src='../energie/images/file_edit_16.gif' alt=\"Edit Folder\" title=\"\" ";
					echo "			onclick='editFolder()'>\n";
					echo "	</td>\n"; //edit icon
					echo "	<td>".$delegateItem['file']."</td>\n";  //filename
					echo "	<td>".$delegateItem['delegate_owner']."</td>\n";  //delegated by
					echo "	<td>".$delegateItem['delegate_username']."</td>\n";  //delegated to
					echo "	<td>".$delegateItem['dtime']."</td>\n";  //time created
					echo "</tr>\n";
					$j++;
				}
			}
		}
echo<<<ENERGIE
		</table>
	</div>
</div>
</body>
</html>
ENERGIE;

$db_obj->disconnect();
}
?>
