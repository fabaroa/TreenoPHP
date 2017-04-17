<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'audit.php';
include_once '../search/search.php';
include_once '../lib/filter.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 ) {
	if(isset($_GET['table'])) {
		$temp_table = $_GET['table'];
	}
	$index = $_GET['index'];
/*
echo<<<ENERGIE
<script>
	parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
	parent.sideFrame.window.location = '../energie/left_blue_search.php';
</script>
ENERGIE;
*/
	if($index == 0 && $_GET['trigger'] == 0) {
		$search = new search();
		$searchID = $_POST['id'];
		$searchUser = $_POST['username'];
		$searchDate = $_POST['datetime'];
		$searchInfo = $_POST['info'];
		$searchAction = $_POST['action'];

		//security check
		$exactSearchArr = array();
		if( !$user->isUserDepAdmin($user->username) ) {
			$searchUser = $user->username;
			$exactSearchArr[] = "username";
		}

		if(!$searchID) $searchID = 'ALL';
		if(!$searchUser) $searchUser = 'ALL';
		if(!$searchDate) $searchDate = 'ALL';
		if(!$searchInfo) $searchInfo = 'ALL';
		if(!$searchAction) $searchAction = 'ALL';
		$auditStr = 'user searched the audit table: ';
		$auditStr .= "id=$searchID, username=$searchUser, date=$searchDate,";
		$auditStr .= " info=$searchInfo, action=$searchAction";
		$user->audit('audit search', $auditStr);
		$whereArr = $search->getAudit($db_object, $exactSearchArr);
		
		if(!isset($temp_table)) {//check for null, because we need to create it if it doesn't exist
			$temp_table = createTemporaryTable($db_object);
		}
		searchQuery( $temp_table, $whereArr, $db_object );
	}
	$rowCount = getTableInfo($db_object, $temp_table, array('COUNT(*)'), array(), 'queryOne');
	$rowCount1 = ($rowCount/100) - floor($rowCount/100);
 	
	if($rowCount1 == 0)
        $rowCount =  ($rowCount/100) - 1;

	$rowCount = floor($rowCount/100);

	$index = checkIndex( $rowCount );
	$res = getIndex($db_object, $index, $temp_table);
	$columnInfo = getTableColumnInfo ($db_object, 'audit');

//Prints out Buttons on Top
echo "<html>\n";
echo "	<head>\n";
echo "		<link rel='stylesheet' type='text/css' href='../lib/style.css'>\n";
echo "	</head>\n";
echo "	<body>\n";
echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
echo " <tr>\n";
echo "  <td width=\"41%\" align=\"right\"><a onClick=\"navArrowsBegin()\"><img src=\"../energie/images/begin_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo "  <td width=\"2%\" align=\"center\"><a onClick=\"navArrowsDown()\"><img src=\"../energie/images/back_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo "  <td nowrap=\"yes\" width=\"7%\" align=\"center\">\n   <form name=\"pageForm\" method=\"post\" action=\"";
echo "showAudit.php?index=$index&trigger=1&table=$temp_table\">\n";
echo "   <input name=\"textfield\" value=\"";
echo $index+1;
echo "\" type=\"text\" size=\"3\">\n";
echo "</td><td nowrap=\"yes\" width=\"7%\" align=\"center\">of ";
echo $rowCount+1;
echo "   </td></form>\n";
echo "  <td width=\"2%\" align=\"center\"><a onClick=\"navArrowsUp()\"><img src=\"../energie/images/next_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo "  <td width=\"41%\" align=\"left\"><a onClick=\"navArrowsEnd()\"><img src=\"../energie/images/end_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo " </tr>\n</table>\n";

//Prints out Results
echo  "<table class='admin-tbl' width='700' border='2' cellpadding='1' cellspacing='2' align='center'>\n<tr class='tableheads'>\n";

foreach($columnInfo as $column) {
	echo "  <td >";
	echo "<b>".$column."<b>";
	echo "  </td>\n";
}
  echo " </tr>\n";
  $res1 = array ();
while($res1 = $res->fetchRow()) {
	echo " <tr>\n";
	$i = 0;
	foreach($columnInfo as $column) {
		echo "  <td>";
		echo "<font size=\"2\">";
		$tmp =  $res1[$column];
		if( $tmp=="" )
			echo "&nbsp";
		else {
			echo h($tmp);
		}

		echo "</font></td>\n";
	}
	echo " </tr>\n";
	$i++;
}
echo "</table>\n";

//Prints out Buttons on Bottom

echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
echo " <tr>\n";
echo "  <td width=\"41%\" align=\"right\"><a onClick=\"navArrowsBegin()\"><img src=\"../energie/images/begin_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo "  <td width=\"2%\" align=\"center\"><a onClick=\"navArrowsDown()\"><img src=\"../energie/images/back_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo "  <td nowrap=\"yes\" width=\"7%\" align=\"center\">\n   <form name=\"pageForm\" method=\"post\" action=\"";
echo "showAudit.php?index=$index&trigger=1&table=$temp_table\">\n";
echo "   <input name=\"textfield\" value=\"";
echo $index+1;
echo "\" type=\"text\" size=\"3\">\n";
echo "</td><td nowrap=\"yes\" width=\"7%\" align=\"center\">of ";
echo $rowCount+1;
echo "   </td></form>\n";
echo "  <td width=\"2%\" align=\"center\"><a onClick=\"navArrowsUp()\"><img src=\"../energie/images/next_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo "  <td width=\"41%\" align=\"left\"><a onClick=\"navArrowsEnd()\"><img src=\"../energie/images/end_button.gif\" border=\"0\" onmouseover=\"javascript:style.cursor='pointer';\"></a></td>\n";
echo " </tr>\n</table>\n";

echo<<<ENERGIE
<script>

function navArrowsUp(){

        parent.mainFrame.window.location="
ENERGIE;
    if(( $index + 1 ) <= $rowCount ) {
		$index++;
		$end = NULL;
	} else {
		$end = 1;
	}
	echo "showAudit.php?index=$index&trigger=1&table=$temp_table";
	

echo<<<ENERGIE
";
}

function navArrowsDown(){

        parent.mainFrame.window.location="
ENERGIE;
		if( $end ) {
			$index--;
		} else {
			if(( $index - 2 )>= 0 && $index != $rowCount)
				$index = $index - 2;
			elseif(($index - 2) < 0) 
				$index--;
			else
				$index = $index - 2;
		}
	echo "showAudit.php?index=$index&trigger=1&table=$temp_table";
	
echo<<<ENERGIE
";
    }

function navArrowsBegin(){

        parent.mainFrame.window.location="
ENERGIE;
    $index = 0;
        echo "showAudit.php?index=$index&trigger=1&table=$temp_table";
echo<<<ENERGIE
";
    }

function navArrowsEnd(){

        parent.mainFrame.window.location="
ENERGIE;
    $index = $rowCount;
		echo "showAudit.php?index=$index&trigger=1&table=$temp_table";
echo<<<ENERGIE
";
    }
</script>

ENERGIE;
	print " </body>\n</html>";
	

	setSessionUser($user);
} else {
	logUserOut();
}
?>
