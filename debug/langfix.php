<?php


//include '../db/db_engine.php';
include_once '../db/db_common.php';
// run this file from the browser to add a new entry or edit an existing entry in the language table.

echo<<<HATEBEMIS
<html>
<head>
 <title>Edit Language Entries</title>
 <link rel="stylesheet" type="text/css" href="../lib/style.css">
 <style>
	span.small{
		font-size: 8pt ;
	}
	
	a:link{
		text-decoration: none ;
		color: #000000 ;
	}
	a:visited{
		text-decoration: none ;
		color: #000077 ;
	}
	a:hover{
		text-decoration: none ;
		color: blue ;
	}
 </style>
</head>
<body  bgcolor="#99CCFF">
HATEBEMIS;
//include '../db/db_engine.php';
include_once '../db/db_common.php';

//$datasource = "$db_engine://$db_username:$db_password@localhost/docutron" ;
//$db_object  = getDocutronConn();

/*
$query      = "SELECT * FROM language ORDER BY k" ;
$result     = $db_object->query($query) ;
*/
// Ram
$result = $db_object->query('SELECT * FROM LANGUAGE ORDER BY k');
$info       = $result->reverse->tableInfo() ;  //info has the information about the table in an array form

$dump = false;

$mode = $_POST['mode'];
$lang = $_POST['lang'];
//echo "<pre>";
//print_r ($result->reverse->tableInfo());

echo<<<FORM
 <form method="POST" action="langfix.php">
<hr>
<table>
 <tr>
  <td width="450">
Add new language: <input type="text" name="newlangname" value="">
                    <input type="submit" name="addNewLang" value="Add New Language">
  </td>
  <td style="font-size:8pt;"> NOTE: All entries in the new language will be initially English, so you must edit them appropriately before using the application with the new language. </td> 
</table>
FORM;

// figure out which thing should be selected
if ($mode == "add")
	$addselected = "selected";
else
	if ($mode == "delete") {
		$delselected = "selected";
	} elseif ($mode == "modifykey") {
		$modselected = "selected";
	} else {
		$editselected = "selected";
	}

echo<<<FORM
<hr>
<table>
   <tr>
    <td>
     <select name="mode">
      <option value="add" $addselected>Add to
      <option value="edit" $editselected>Edit 
      <option value="delete" $delselected>Delete from
      <option value="modifykey" $modselected>Modify Key
     </select>
    </td>
    <td>
     <select name="lang">
FORM;
$whereclause = "(";
$whereconnect = "";

for ($i = 2; $i < sizeof($info); $i ++) { //starts from 2 as field 0 is id and field 1 is k,
	// thus language is from field 2 onwards.
	$langname = $info[$i]['name'];
	echo "     <option value=\"$langname\">$langname";

	// also, while here, make where clasuse for the select incomplete
	$whereclause .= "$whereconnect $langname like \"*need%\"";
	$whereconnect = " OR";
}

$whereclause .= ")";

echo<<<FORM
     </select>
    </td>
    <td>
     <input type="submit" name="submit" value="Choose Action">
    </td>
    <td>
&nbsp; &nbsp; || &nbsp; &nbsp;
     <input type="submit" name="view" value="View All">
     <input type="submit" name="view" value="View Incomplete">
     <input type="text" name="viewsearch" value="">
    </td>
   </tr>
  </table>

 </form>
FORM;

//check to see if new language was added:
if (isset ($_POST['addNewLang'])) {
	alterTable($db_object,'language','ADD',$_POST['newlangname'],'VARCHAR(255)');

	 $db_object->query("UPDATE language SET {$_POST['newlangname']} = english");

	echo "<script>document.onload = location = \"langfix.php\"</script>";
}

// Check to see if first form was filled
//print_r($_POST) ; //ERROR
//echo isset($_POST['view']) ; //ERROR
//echo isset($_POST['viewsearch']); //ERROR

if (isset ($_POST['view']) || $_POST['viewsearch'] != "") {

	// Clear our whereclause if not needed
	if ($_POST['view'] != "View Incomplete") {
		$whereclause = "";
	}

	// Check if search field was filled with anything
	if ($_POST['viewsearch'] != "") {
		// get tokens
		$toklist = explode(" ", $_POST['viewsearch']);

		// prepare to add on to whereclause
		if ($whereclause != "")
			$whereconnect = " AND";
		else
			$whereconnect = "";

		for ($i = 0; $i < sizeof($toklist); $i ++) {
			$whereclause .= $whereconnect." ( k like \"%".$toklist[$i]."%\"";
			$whereclause .= " OR english like \"%".$toklist[$i]."%\")";

			$whereconnect = " AND";
		}
	}

	// get all or just incomplete
	if ($whereclause != "")
		$whereclause = "where ".$whereclause;

	$query = "select * from language $whereclause ORDER BY k";

	// do the query
	echo "<hr><span class=\"small\">$query</span><br>\n";
	$viewresults = $db_object->query($query);
	$viewinfo = $viewresults->reverse->tableInfo();

	echo " <table border=\"1\" cellpadding=\"3\">\n"."  <tr>\n";

	// header names
	for ($i = 0; $i < sizeof($viewinfo); $i ++)
		echo "   <td>".$viewinfo[$i]['name']."</td>\n";
	echo "  </tr>\n";

	// put out all the results
	$color = "bgcolor=\"#88c0ee\"";
	while ($viewrow = $viewresults->fetchRow()) {

		// alternating color
		if (!empty ($printcolor))
			$printcolor = "";
		else
			$printcolor = $color;

		echo "  <tr $printcolor>\n";
		for ($i = 0; $i < sizeof($viewrow); $i ++) {
			echo "   <td>"."     <a href=\"langfix.php?keyval=$viewrow[0]\">"."     ".$viewrow[$i]."</a>"."   </td>\n";
		}
		echo "  </tr>\n";
	}

	echo " </table>\n";
} else
	if (isset ($_POST['submit'])) {
		echo<<<FORM
 <hr>
 <form method="POST" action="langfix.php">
FORM;

		// This is where we add new words
		if ($mode == "add") {
			echo "New Key Name:<br><input type=\"text\" name=\"key\" value=\"New Key Name\" size=\"50\">";
			for ($i = 2; $i < sizeof($info); $i ++) {
				$langname = $info[$i]['name'];
				echo "<br><br>$langname <input type=\"text\" name=\"$langname\" value=\"*Need $langname*\" size=\"50\">";
			}
			echo "  <br><br><input type=\"submit\" name = \"addPhrase\" value=\"Add Phrase\">"." </form>";
		}

		// This is where we select values to edit from the drop-down
		else
			if ($mode == "edit") {
				echo "  Select a key value to edit:<br>"."  <select name=\"keyval\">";
		
		$count = getTableInfo($db_object, 'language', array('COUNT(*)'), array(), 'queryOne');
		for ($i = 0; $i < $count; $i ++) {
					$row = $result->fetchRow(MDB2_FETCHMODE_ORDERED);
					echo "   <option value=\"$row[0]\">". ($i +1).". ".$row[1]."\n";
				}
				echo "  </select><br>\n"."  <br><input type=\"submit\" name = \"editPhrase\" value=\"Edit Key\">\n"." </form>";
			}

		elseif ($mode == "delete") {
			echo "  Select a key value to delete:<br>"."  <select name=\"delkey\">";
			for ($i = 0; $i < $count; $i ++) {
				$row = $result->fetchRow(MDB2_FETCHMODE_ORDERED);
				echo "   <option value=\"$row[1]\">". ($i +1).".".$row[1]."\n";
			}

			echo "  </select><br>\n"."<br><input type=\"submit\" name = \"deletePhrase\" value=\"View Key\">\n";
			echo " </form>";

		}
		elseif ($mode == "modifykey") {
			echo "  Select a key to modify:<br>"."  <select name=\"modkey\">";
			for ($i = 0; $i < $count; $i ++) {
				$row = $result->fetchRow(MDB2_FETCHMODE_ORDERED);
				echo "   <option value=\"$row[1]\">". ($i +1).". ".$row[1]."\n";
			}
			echo "  </select><br>\n"."  <br><input type=\"submit\" name = \"modifyKey\" value=\"Modify Key\">\n"." </form>";
		}

	}

// This where we edit values
if (isset ($_POST['editPhrase']) || isset ($_GET['keyval'])) {
	$keyval = $_POST['keyval'].$_GET['keyval']; // Get it from either

	$result = $db_object->query("SELECT * FROM language WHERE id = $keyval");
	$row = $result->fetchRow();

	echo<<<FORM

 <hr>
 <form method="POST" action="langfix.php">
 <br>Editing Key:<br><br> 
FORM;
	echo "<i>".$row[1]."</i><br><br>";

	for ($i = 2; $i < sizeof($row); $i ++) {
		$tmplang = $info[$i]['name'];
		echo "$tmplang:<br><input type=\"text\" size =\"150\" name=\"$tmplang\" value=\"".$row[$i]."\"><br><br>";
	}

	echo "<input type=\"submit\" name=\"submitEdit\" value=\"Submit Edit\">";
	echo "<input type=\"hidden\" name=\"keyval\" value=\"$row[1]\"></form>";
}

//add the new key with translations to the database
if ($_POST['addPhrase']) {

	//check if there are duplicate keys:
	$res = getTableInfo($db_object, 'language', array('COUNT(*)'), array('k' => $_POST['key']), 'queryOne');
	if ($res > 0) {
		echo "<font color=red><b>THIS KEY ALREADY EXISTS: "."<i>".$_POST['key']."</i>"."</b><font>";
		echo "<br><br><font color=red><b>PLEASE CHOOSE A DIFFERENT KEY!";
		die();
	}
	$query = "INSERT INTO language SET k='{$_POST['key']}'";
	$comma = ",";
	for ($i = 2; $i < sizeof($info); $i ++) {
		$tmplang = $info[$i]['name'];
		$query .= "$comma $tmplang=\"".$_POST[$tmplang]."\"";
	}
	$db_object->query($query);
	$dump = true;
}

//if we have selected to delete a key:
if ($_POST['deletePhrase']) {
	$delkey = $_POST['delkey'];
	//select from the DB the key that you want to edit:
		$result = $db_object->query("SELECT * FROM language WHERE k = '$delkey'");
	echo<<<FORM
 <hr>
 <form method="POST" action="langfix.php">
 Selected Key for Deletion:<br>
FORM;
	echo "<font color = red><i>".$delkey."</i></font><br>";
	echo "<br>The entries for this key are:<br>";
	$count = getTableInfo($db_object, 'language', array('COUNT(*)'), array('k' => $delkey), 'queryOne');
	for ($i = 0; $i < $count; $i ++) {
		$row = $result->fetchRow();
		for ($i = 2; $i < sizeof($row); $i ++) {
			$tmplang = $info[$i]['name'];
			echo "$tmplang:<br><input type=\"text\" size =\"150\" name=\"$tmplang\" value=\"".$row[$i]."\"><br>";
		}
	}
	echo "<br><input type=\"submit\" name=\"submitDeletion\" value=\"Submit Deletion\">";
	echo "<input type=\"hidden\" name=\"delkey\" value=\"$row[0]\"></form>";
}

if ($_POST['modifyKey']) {
	$modkey = $_POST['modkey'];
	$result = $db_object->query("SELECT * FROM language WHERE k = '$modkey'");

	echo<<<FORM
 <hr>
 <form method="POST" action="langfix.php">
 Selected Key for Modification:<br>
FORM;
	echo "<font color = red><i>".$modkey."</i></font><br>";
	echo "<br>The entries for this key are:<br>";
	$count = getTableInfo($db_object, 'language', array('COUNT(*)'), array('k' => $modkey), 'queryOne');

	for ($i = 0; $i < $count; $i ++) {
		$row = $result->fetchRow();
		for ($i = 2; $i < sizeof($row); $i ++) {
			$tmplang = $info[$i]['name'];
			echo "$tmplang:<br><input type=\"text\" size =\"150\" name=\"$tmplang\" value=\"".$row[$i]."\"><br>";
		}
	}
	echo "<br><i>Type in your new key in the text box:</i>";
	echo "<br><input type = \"text\" name = \"newKey\" value = \"$modkey\" size=\"100\" >";
	echo "<br><br><input type=\"submit\" name=\"submitKeyMod\" value=\"Submit Key Modification\">";
	echo "<input type=\"hidden\" name=\"modkey\" value=\"$row[0]\"></form>";
}

//put the edited entry in the database
if ($_POST['submitEdit']) {

	$keyval = $_POST['keyval'];
        $query = "UPDATE language SET";
        $end = " WHERE k='$keyval'";
        $comma = ""; // only want a comma after first one
        for ($i = 2; $i < sizeof($info); $i ++) {
                $tmplang = $info[$i]['name'];
                $query .= "$comma $tmplang=".$_POST[$tmplang]."";
                $comma = ",";
        }
        $query .= $end;
        $res = $db_object->query($query);
        dbErr($res);

	$dump = true;
}

if ($_POST['submitDeletion']) {
	$query = "DELETE FROM language where id = {$_POST['delkey']}";
	$db_object->query($query);
	$dump = true;
}

if ($_POST['submitKeyMod']) {
	//Ramesh:
	//$query  = "UPDATE language SET k='$_POST[newKey]' where id = $_POST[modkey]";
	//$db_object->query($query);

	dateLanguage3($db_object);

	$dump = true;
}

if ($dump) {
	if (file_exists("../install/sql/langdump.sql")) {
		`rm -rf ../install/sql/langdump.sql`;
	}

	$cmd = "touch ../install/sql/langdump.sql";
	`$cmd`;
	$cmd = "chmod 777 ../install/sql/langdump.sql";
	`$cmd`;

	$handle = fopen("../install/sql/langdump.sql", "a");

	$res = $db_object->query("SELECT * FROM language");

	while ($row = $res->fetchRow()) {
		$line = "\nINSERT INTO language VALUES ('' ";

		for ($i = 1; $i < sizeof($info); $i ++) { //dynamically build the string to insert in the dump file
			$col = $row[$i];
			$line .= ",'".$col."'";
		}
		$line .= ");";
		fwrite($handle, $line);
	}

	fclose($handle);

	echo "<font color=red><b><i>NOW PLEASE CHECK IN langdump.sql (html/install/sql/langdump.sql) INTO CVS !!!</i></b></font>";

}

$db_object->disconnect ();
echo<<<BOTTOM
</body>
</html>
BOTTOM;
?>
