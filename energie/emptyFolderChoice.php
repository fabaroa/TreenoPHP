<?php
include_once('../classuser.inc');
include_once('../check_login.php');
include_once('../settings/settings.php');
include_once('../lib/fileFuncs.php');
include_once('../lib/tabFuncs.php');
echo <<<ENERGIE
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Empty Folder</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<style type="text/css">
form {
	padding-top: 5px;
}
</style>
</head>
<body class="centered">\n
ENERGIE;

if($logged_in and strcmp($user->username, "") != 0) {
	$cab		= $_GET['cab'];
	$temp_table	= $_GET['table'];
	$index		= $_GET['index'];
	$trigger	= $_GET['trigger'];
	$doc_id		= $_GET['doc_id'];
	$topTerms	= $_GET['topTerms'];

	if(isset($_POST['delete'])) {
		deleteTableInfo($db_object,$cab,array('doc_id'=>(int)$doc_id));
		deleteTableInfo($db_object,$cab."_files",array('doc_id'=>(int)$doc_id));
		deleteTableInfo($db_object,$temp_table,array('result_id'=>(int)$doc_id));

		echo "<script type=\"text/javascript\">\n";
		echo "document.onload = parent.mainFrame.window.location = ";
		echo "\"searchResults.php?cab=$cab&table=$temp_table&index=$index&";
		echo "trigger=1&topTerms=$topTerms&mess=Folder Entry Has Been Deleted\"\n";
		echo "</script>\n";
		die();
	} elseif(isset($_POST['create'])) { 
		$location = getTableInfo($db_object,$cab,array('location'),array('doc_id'=>(int)$doc_id),'queryOne');

		$pieces		= explode(" ", $location);
		$location	= str_replace(" ", "/", $location);
		//change cabinet write permissions
		mkdir($DEFS['DATA_DIR'].'/'.$location);
		deleteTableInfo($db_object,$cab."_files",array('doc_id'=>(int)$doc_id));
		$gblStt = new GblStt($user->db_name, $db_doc);
		addTabsToFolder($cab, $gblStt, $db_doc, $doc_id, $db_object, $user->db_name);
		echo "<script type=\"text/javascript\">\n";
		echo "document.onload = parent.mainFrame.window.location = ";
		echo "\"searchResults.php?cab=$cab&table=$temp_table&index=$index&";
		echo "trigger=1&topTerms=$topTerms&mess=Folder Directory Has Been Created\";\n";
		echo "</script>\n";
	} else {
		echo<<<ENERGIE
<div class="mainDiv">
<div class="mainTitle">
<span>Empty Folder</span>
</div>
<form name="empty" method="post" action="emptyFolderChoice.php?cab=$cab&amp;table=$temp_table&amp;index=$index&amp;trigger=$trigger&amp;doc_id=$doc_id&amp;topTerms=$topTerms">
<div>
<p style="display: inline;" class="myTitle">This Folder Path Does Not Exist.  What Action Do You Want To Take?</p>
<p>
<input type="submit" name="delete" value="Delete Entry"/>
</p>
<p>
<input type="submit" name="create" value="Create Empty Directory"/>
</p>
</div>
</form>
</div>
ENERGIE;
	}
	setSessionUser($user);
} else { //log them out
	logUserOut();
}
?>		
</body>
</html>
