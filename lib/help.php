<?php
include_once '../check_login.php';
include_once '../lib/utility.php';

function getHelp ($k, $lang, $db_doc) {
	$selArr = array('section','title','description');
	$whereArr = array('k'=>$k,'language'=>$lang);
	$helpInfo = getTableInfo($db_doc,'help',$selArr,$whereArr,'queryRow');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<script type="text/javascript">
</script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/help.js"></script>
<link rel="stylesheet" type="text/css" href="../lib/style.css">
<style>
	div.mDiv {
		border-style: double;
        border-color: #003b6f;
        background-color: #ffff99;
        padding: 0.25em;
        overflow: auto;
        width: 250px;
        height: 150px;
        visibility: visible;
	}
	
	div.innerDiv {
		padding: 0px;
	}

	div.exitDiv {
		padding: 0px;
        color: #003b6f;
        cursor: pointer;
        text-align: right;
        text-decoration: underline;
	}

	div.descDiv {
		color: black;
	}
</style>
</head>
<body>
<div class="mDiv">
	<div class="innerDiv">
		<div class="exitDiv" onclick="exitHelp()">close</div>
		<div class="descDiv"><?php echo $helpInfo['description']; ?></div>
	</div>
</div>
</body>
</html>
<?php
}
if($logged_in==1 && strcmp($user->username,"")!=0) {
	$k = (isSet($_GET['k']) ? $_GET['k'] : '');
	$lang = (isSet($_GET['lang']) ? $_GET['lang'] : '');
	getHelp($k,$lang, $db_doc);

    setSessionUser($user);
} else {
    logUserOut();	
}
?>
