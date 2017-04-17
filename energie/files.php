<?php
include_once '../check_login.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$doc_id = $_GET['doc_id']; //folder ID 
	$cab = $_GET['cab']; //cabinet name 
	$tab = $_GET['tab']; //tab of file being viewed
	if (isset($_GET['type'])) {
		$type = $_GET['type'];
	} else {
		$type = '';
	}
	$temp_table = $_GET['table'];
	$index = $_GET['index'];
	if (isset($_GET['fileID'])) {
		$fileID = $_GET['fileID'];
	} else {
		$fileID = '';
	}
	if ($user->checkSecurity($cab) > 0) {
		echo<<<ENERGIE
<html>
 <head>
  <link rel="stylesheet" type="text/css" href="../lib/style.css">
 </head>
 <body class="tealbg">
ENERGIE;
		$whereArr = array(
			"doc_id"		=> (int)$doc_id,
			"display"		=> 1,
			"deleted"		=> 0
				 );
		if ( ($tab == "main") || !$tab )
			$whereArr['subfolder'] = 'IS NULL';
		else
			$whereArr['subfolder'] = $tab;
		$listOfFiles = getTableInfo($db_object,$cab."_files",array(),$whereArr,'query',array('ordering'=>'ASC'));
		$i = 0;
		$fileOrdering = array ();
		while ($fileInfo = $listOfFiles->fetchRow()) {
			if ($fileInfo['filename'] != NULL) {
				$fileOrdering[$i] = $fileInfo['ordering']; //Ordering ID of each file in Tab
				$i ++;
			}
		}

		$num_files = sizeof($fileOrdering);
		if (isset($_POST['textfield']) and $_POST['textfield'] != NULL) {
			if ($_POST['textfield'] > $num_files)
				$count = $num_files -1;
			else {
				if ($_POST['textfield'] < 1)
					$count = 0;
				else
					$count = $_POST['textfield'] - 1;
			}
			$tmp = $fileOrdering[$count];

			if ($tab)
				$ttab = $tab;
			else
				$ttab = "main";

			echo "<script>";
			if ($count != "") {
				echo "parent.sideFrame.addBackButton();";
				echo "parent.sideFrame.tmp_count = $count;";
			}
			echo "parent.sideFrame.setSelectedRow('s-$doc_id:$ttab:$tmp');";
			echo "parent.mainFrame.location.href = \"";
			echo "display.php?cab=$cab&doc_id=$doc_id&tab=$tab&ID=$tmp\"";
			echo "</script>";
		}
		elseif (isSet ($_GET['count'])){
			$count = $_GET['count']; //ordering ID of File being viewed
		}
		else
			$count = 0;

		if ($num_files > 0) {
			if ($type == NULL) {
				if (!$fileID) {
					$ID = $fileOrdering[$count];
					$whereArr = array(
						"doc_id"		=> (int)$doc_id,
						"ordering"		=> (int)$ID,
						"display"		=> 1,
						"deleted"		=> 0
							 );
					if($tab && strtolower($tab) != "main") {
						$whereArr['subfolder'] = $tab;
					} else {
						$whereArr['subfolder'] = 'IS NULL';
					}
					$fileID = getTableInfo($db_object,$cab."_files",array('id'),$whereArr,'queryOne');
				}
				echo "<script type=\"text/javascript\">\n";
				echo "  parent.searchPanel.showNotes('$fileID');\n";
				echo "</script>\n";
			}
			echo "<center><table class=\"tealbg\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
			echo "<tr>\n";
			echo "<td width=\"10%\" align=\"right\">";
			echo "</td>\n";
			echo "    <td width=\"15%\" onclick=\"parent.sideFrame.addBackButton()\" align=\"right\"><a href=\"#\" onClick=\"navArrowsBegin()\">";
			echo "<img src=\"images/begin_button.gif\" border=\"0\" ></a></td>\n";
			echo "    <td width=\"2%\" onclick=\"parent.sideFrame.addBackButton()\" align=\"center\"><a href=\"#\"  onClick=\"navArrowsDown()\">";
			echo "<img src=\"images/back_button.gif\" border=\"0\" ></a></td>\n";
			echo "<td noWrap=\"yes\" align=\"center\">\n";
			echo "<form name=\"pageForm\" method=\"post\"";
			echo " action=\"files.php?cab=$cab&doc_id=$doc_id&tab=$tab\">";
			echo "&nbsp;<input name=\"textfield\" id=\"newPage\" value=\"".($count+1)."\"";
			echo " type=\"text\" size=\"3\">&nbsp;";
			echo "</td><td noWrap=\"yes\" width=\"15%\" align=\"center\" class=\"lnk\">"; //lnk_black
			echo " of ".$num_files."&nbsp;";
			echo "</td></form>\n";
			echo "    <td width=\"2%\" onclick=\"parent.sideFrame.addBackButton()\" align=\"center\"><a href=\"#\"  onClick=\"navArrowsUp()\">";
			echo "<img src=\"images/next_button.gif\" border=\"0\" ></a></td>\n";
			echo "    <td width=\"5%\" onclick=\"parent.sideFrame.addBackButton()\" align=\"left\"><a href=\"#\"  onClick=\"navArrowsEnd()\">";
			echo "<img src=\"images/end_button.gif\" border=\"0\" ></a></td>\n";
			echo "	</tr>\n</table>\n";
			//////////BUTTONS SET//////////
			if ($count == 0) //Previous File in Tab
				$prev = 0;
			else
				$prev = $count -1;

			if (($count +1) < $num_files) //Next File in Tab
				$next = $count +1;
			else
				$next = $num_files -1;

			$end = $num_files -1; //End of Tab
			///////////////////////////////
		} else {
			$next = 0;
			$prev = 0;
			$end = 0;
		}
		if ($tab == "")
			$tmptab = "main";
		else
			$tmptab = $tab;
		echo<<<ENERGIE
 </body>
</html>
<script>
function navArrowsUp()
{
ENERGIE;
		if ($num_files > 0) {
			$tmp1 = $fileOrdering[$next];
			echo "parent.sideFrame.setSelectedRow('s-$doc_id:$tmptab:$tmp1');";
			if ($next != "")
				echo "parent.sideFrame.tmp_count = $next;";
			echo "parent.mainFrame.window.location=\"";
			echo "display.php?cab=$cab&doc_id=$doc_id&ID=$tmp1&tab=$tab\";\n";
			echo "parent.bottomFrame.window.location=\"";
			echo "files.php?cab=$cab&doc_id=$doc_id&tab=$tab&table=$temp_table&index=$index&count=$next\";";
		}
		echo<<<ENERGIE
}
function navArrowsBegin()
{
ENERGIE;
		if ($num_files > 0) {
			$tmp2 = $fileOrdering[0];
			echo "parent.sideFrame.setSelectedRow('s-$doc_id:$tmptab:$tmp2');";
			echo "parent.sideFrame.tmp_count = 0;";
			echo "parent.mainFrame.window.location=\"";
			echo "display.php?cab=$cab&doc_id=$doc_id&ID=$tmp2&tab=$tab\";\n";
			echo "parent.bottomFrame.window.location=\"";
			echo "files.php?cab=$cab&doc_id=$doc_id&tab=$tab&table=$temp_table&index=$index&count=0\";";
		}
		echo<<<ENERGIE
}
function navArrowsEnd()
{
ENERGIE;
		if ($num_files > 0) {
			$tmp3 = $fileOrdering[$end];
			echo "parent.sideFrame.setSelectedRow('s-$doc_id:$tmptab:$tmp3');";
			if ($end != "")
				echo "parent.sideFrame.tmp_count = $end;";
			echo "parent.mainFrame.window.location=\"";
			echo "display.php?cab=$cab&doc_id=$doc_id&ID=$tmp3&tab=$tab\";\n";
			echo "parent.bottomFrame.window.location=\"";
			echo "files.php?cab=$cab&doc_id=$doc_id&tab=$tab&table=$temp_table&index=$index&count=$end\";";
		}
		echo<<<ENERGIE
}
function navArrowsDown()
{
ENERGIE;
		if ($num_files > 0) {
			$tmp4 = $fileOrdering[$prev];
			echo "parent.sideFrame.setSelectedRow('s-$doc_id:$tmptab:$tmp4');";
			if ($prev != "")
				echo "parent.sideFrame.tmp_count = $prev;";
			echo "parent.mainFrame.window.location=\"";
			echo "display.php?cab=$cab&doc_id=$doc_id&ID=$tmp4&tab=$tab\";\n";
			echo "parent.bottomFrame.window.location=\"";
			echo "files.php?cab=$cab&doc_id=$doc_id&tab=$tab&table=$temp_table&index=$index&count=$prev\";";
		}
		echo<<<ENERGIE
}	
</script>\n
ENERGIE;

		setSessionUser($user);
	} //end of if that checks security
	else {
		//redirect them to login
		echo<<<ENERGIE
<html>
 <body bgcolor="#FFFFFF">
  <script>
    document.onload = top.window.location = "../logout.php";
  </script>
  <br>no security access</br>
 </body>
</html>
ENERGIE;
	}
} //end of if that checks if you are still logged in
else {
	echo<<<ENERGIE
<html>
 <body bgcolor="#FFFFFF">
  <script>
    document.onload = top.window.location = "../logout.php";
  </script>
 </body>
</html>
ENERGIE;
}
?>

