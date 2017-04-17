<?PHP
/* This page is used to wrap the jpeg images inside HTML tags for sizing purposes */
if( isset( $_GET['inbox'] ) ){
	$inbox = $_GET['inbox'];
}else{
	$inbox = 0;
}

//if coming from the inbox
if($inbox == 1) {
	$name = $_GET['name'];
	$user = $_GET['user'];
	$foldname = $_GET['foldname'];
	$username = $_GET['username'];
	$delegateID = $_GET['delegateID'];
	$URL = "../secure/displayInbox.php?name=$name&user=$user&foldname=$foldname&username=$username&delegateID=$delegateID&tmp=0/$name";
} else {
	$cab = (isSet($_GET['cab'])) ? $_GET['cab']: ""; 
	$doc_id = (isSet($_GET['doc_id'])) ? $_GET['doc_id']: ""; 
	$tab = (isSet($_GET['tab'])) ? $_GET['tab']: ""; 
	$fileID = (isSet($_GET['fileID'])) ? $_GET['fileID']: ""; 
	$ID = (isSet($_GET['ID'])) ? $_GET['ID']: ""; 
	$download = (isSet($_GET['download'])) ? $_GET['download']: ""; 
	$delete = (isSet($_GET['delete'])) ? $_GET['delete']: ""; 

	$URL = "readfile.php?doc_id=$doc_id&fileID=$fileID&tab=$tab&cab=$cab&ID=$ID&download=$download&delete=$delete";
}

echo "<HTML>";
echo "<BODY>";
echo "		<IMG SRC='$URL' width=100%>";
echo "</BODY>";
echo "</HTML>";
?>
