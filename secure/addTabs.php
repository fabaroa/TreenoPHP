<?php
include_once '../check_login.php';
include_once '../lib/tabFuncs.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isAdmin()) {

	$selectCabLabel = $trans['Choose Cabinet'];
	$tableTitle = $trans['Cabinet Access'];
	$dieMessage = $trans['dieMessage'];
	$tabname = $trans['Tab Name'];
	$submit = $trans['Submit'];
	$addTab = $trans['Add Tab'];
	$save = "Save";

	$db_object = $user->getDBObject();
	$db_doc = getDbObject ('docutron');
	if (isset ($_GET['cab'])) {
		$cab = $_GET['cab'];
	} else {
		$cab = ''; 
	}
	if (isset ($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = ''; 
	}
	$mess = str_replace("_", " ", $mess);
	$cabList = array_keys($user->access,'rw');

	echo<<<ENERGIE
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<title>Add Tabs</title>
</head>
<body class="centered">
ENERGIE;
	if ($user->noCabinets()) {
		die("<div class='error'>$dieMessage</div></body></html>");
	}
	echo<<<HTML
<div class="mainDiv">
<div class="mainTitle">
<span>$addTab</span>
</div>
<form name="getDepartment" method="post" action="addTabs.php">
<table class="inputTable">
 <tr>
  <td class="label"><label for="cabSel">$selectCabLabel</label></td>
  <td>
HTML;
	$user->getCab("addTabs.php",$user,1,1);
echo<<<HTML
  </td>
 </tr>
</table>
</form>
HTML;
	if ($mess != NULL) {
		echo "<div class=\"error\">$mess</div>\n";
	}

	$user->addCabinetJscript("getDepartment");
	if ($cab) {
		echo "<form method=\"post\" action=\"createTabs.php?cab=$cab\">\n";
		echo "<table class=\"inputTable2\"><tr>\n";
		echo "<th>$save</th>";
		echo "<th>$tabname</th>\n";
		echo "</tr>\n";
		$tabs = getSavedTabs($cab, $user->db_name, $db_doc);
		$i = 0;
		foreach ($tabs as $myTab) {
			$myTabName = str_replace("_", " ", $myTab);
			echo<<<ENERGIE
<tr>
<td><input style="vertical-align: middle" type="checkbox" name="save$i" checked="checked"/></td>
<td>
<span>$myTabName</span>
<input type="hidden" name="tab$i" value="$myTabName">
</td>
</tr>
ENERGIE;
			$i++;
		}
		$end = $i + 15;
		while($i < $end) {
			echo "<tr>\n";
			echo "<td><input style=\"vertical-align: middle\"";
			echo " type=\"checkbox\" name=\"save$i\"/></td>\n";
			echo "<td><input type=\"text\" name=\"tab$i\" size=\"20\" maxlength=\"255\"/></td>\n";
			echo "</tr>\n";
			$i++;
		}
		echo<<<ENERGIE
	</table>
	<div style="margin-left: auto; margin-right: 0; text-align: right">
	<input type="submit" name="Submit" value="$save" />
	</div>
	</form>
ENERGIE;
	}
	echo<<<ENERGIE
	</div>
	</body>
	</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
