<?php 
	$uname = $_GET['username'];
	$name = $_GET['name'];
	$folder = $_GET['foldname'];
	$page = $_GET['page'];
	$type = $_GET['user'];
	$delID = $_GET['delegateID'];

	$ibxUrl = "?inbox=1&name=$name&user=$type&foldname=$folder".
			"&username=$uname&delegateID=$delID&tmp=0/$name";
	$backUrl = $ibxUrl."&page=$page";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Starting Indexing</title>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
</head>
<frameset rows="*,25" cols="*">
<frame framespacing="0" frameborder="0" src="displayInbox.php<?php echo $ibxUrl; ?>" name="tFrame" />
<frame framespacing="0" frameborder="0" src="back.php<?php echo $backUrl; ?>" name="bFrame" noresize="noresize" />
</frameset>
</html>
