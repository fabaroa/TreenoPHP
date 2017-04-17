<?php

include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once '../settings/settings.php';
include_once 'energiefuncs.php';
    
$cab = $_GET['cab'];
if($logged_in ==1 && strcmp($user->username,"")!=0 && $user->checkSecurity($cab) == 2)
{
  
  //variables that may have to be translated
  $docTitle         = $trans['Add/Edit Tabs'];
  $createTab        = $trans['Create Tab'];
  $tabName          = $trans['Tab Name'];
  $submit           = $trans['Submit'];
  $editTab          = $trans['Edit Tab'];
  $editButton       = $trans['Edit'];
  $deleteButton     = $trans['Delete'];
  $warning          = $trans['Delete Warning'];
  $tabExists        = $trans['Tab Already Exists'];
  $Tab              = $trans['Tab'];

	$doc_id = $_GET['doc_id'];
	if (isset ($_GET['ID'])) {
		$ID = $_GET['ID'];
	} else {
		$ID = '';
	}

	if (isset ($_GET['tab'])) {
		$tab = $_GET['tab'];
	} else {
		$ID = '';
	}

	if (isset ($_GET['error'])) {
		$error = $_GET['error'];
	} else {
		$error = '';
	}

	if (isset($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = '';
	}

	if (isset ($_GET['table'])) {
		$temp_table = $_GET['table'];
	} else {
		$temp_table = '';
	}

	if (isset ($_GET['index'])) {
		$index = $_GET['index'];
	} else {
		$index = '';
	}

echo<<<ENERGIE
<html>
  <head>
    <link rel='stylesheet' type='text/css' href='../lib/style.css'>
	<script>
	function selectAll(field)
	{
		if(field.selectTab.checked == true )	
		{
			if(field.tabcheckID.length > 1)
			{
				for (i = 0; i < field.tabcheckID.length; i++)
					field.tabcheckID[i].checked = true ;
			}
			else
			{
				field.tabcheckID.checked = true;
			}
		}
		else
		{
			if(field.tabcheckID.length > 1)
			{
				for (i = 0; i < field.tabcheckID.length; i++)
				{
					field.tabcheckID[i].checked = false ;
				}
			}
			else
				field.tabcheckID.checked = false;
		}
	}
	</script>
    <title>$docTitle</title>
  </head>
  <body onload="document.tabName.tab.focus()">
		<form name="tabName" method="POST">
			<table class="admin-tbl" cellpadding="1" cellspacing="2" border='2' width='275' align='center'>
				<tr class='tableheads'>
					<td colspan='2'>$createTab</td>
				</tr>
				<tr>
					<td align='left' colspan='2' >$tabName</td>
  				</tr>
				<tr>
			 		<td><input type="text" name="tab" onKeyPress="checkEnter(event);" size="25" maxlength="255"></td>
					<td><input type="button" onclick="verifyTab();" name="B1" value="$submit"></td>
				</tr>
ENERGIE;
			$mess = str_replace("_"," ",$mess);
			if( $mess != NULL )
			{
				echo "<tr>\n";
				echo "<td colspan=\"4\"><div class=\"error\">$mess</div></td>\n";
				echo "</tr>\n";
			}
echo<<<ENERGIE
			</table>
			<table width='275' border='0' cellpadding='0' cellspacing='0'>
			</table>
		</form>
ENERGIE;
	$gblStt = new GblStt($user->db_name, $db_doc);
	$tabname = queryAllTabs($db_object, $cab, $doc_id, $gblStt, $user->db_name);
	$count = sizeof($tabname);
	$j = 1;
  if($count > 1)
  {
	echo "\n<form id=\"editTab\" name=\"editTab\" method=\"post\"";
	echo "		action=\"editTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&table=$temp_table&index=$index\">\n";
	echo "	<table class='admin-tbl' cellpadding='1' cellspacing='2' border='2' width='275' align='center'>\n";
	echo "		<tr class='tableheads'>\n";
	echo "			<td colspan='2'>$editTab</td>\n";
	echo "			<td><input type=\"checkbox\" id=\"selectTab\" name=\"selectTab\" value=\"selectTab\" ";
	echo "				onclick=\"selectAll(document.editTab)\"></td>";
	echo "		</tr>\n";
	for($i=0;$i<sizeof( $tabname );$i++)
	{
    $realName = $tabname[$i];
    $dispName = str_replace("_", " ", $realName);
		if(strcmp($tabname[$i], 'main') != 0) {
			echo<<<ENERGIE
		<tr>
		 <td>$Tab $j</td>
		 <td><input type="textfield" name="$realName" value="$dispName" size="20" maxlength="255"></td>
		 <td align="center"><input type="checkbox" id="tabcheckID" name="tab[]" value="$realName"></td>
		</tr>
ENERGIE;
		$j++;
		}
	}
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='2' align='right'><input type=\"submit\" name=\"edit\" value=$editButton></td>\n";
	echo "	<td><input type='button' onclick=\"confirmDel()\" name='delete' value=$deleteButton></td>\n";
	echo "</tr>\n";
	if( $error != NULL )
	{
		echo "<tr>\n";
		echo "	<td colspan='4'><div class=\"error\">$error</div></td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "</form>\n";
  }
echo<<<ENERGIE
  <script>
  function confirmDel() {
    message = '$warning';
    answer = window.confirm(message);
    if(answer == true) {
	document.editTab.submit();
    }
  }
  function verifyTab()
  {
    tname = document.tabName.tab.value;
    tabArr = new Array();
    ct = false;

    if(tname == null)
    {
	alert("Missing field for create tab");
	ct = true;	
    } 
ENERGIE;
    echo "\n";
    for($i=0;$i<$count;$i++)
    {
	$tempName = str_replace("_"," ",$tabname[$i]);
echo<<<ENERGIE
      tabArr[$i] = "$tempName";
ENERGIE;
    }  
echo<<<ENERGIE
    for(i=0;i<tabArr.length;i++)
    {
      if(tname.toLowerCase() == tabArr[i].toLowerCase())
      {
        alert("$tabExists");
        ct = true;
      }
    }
    if(ct == false)
    {
      document.tabName.action = "createTab.php?cab=$cab&doc_id=$doc_id&ID=$ID&tab=$tab&table=$temp_table&index=$index";
      document.tabName.submit(); 
    }
  }
  function checkEnter(event)
  {
    var code = 0;
    if(document.all)
      code = event.keyCode;
    else
      code = event.which;

    if(code == 13)
      verifyTab(); 
  }
  </script>
	</body>
	</html>
ENERGIE;

	setSessionUser($user);
}
else
{  //redirect them to login
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
?>
