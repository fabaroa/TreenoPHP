<?php

include_once '../check_login.php';
include_once ( '../classuser.inc');
include_once 'XMLCabinetFuncs.php';
include_once '../settings/settings.php';

if($logged_in==1 && strcmp($user->username,"")!=0)
{
	$user->setSecurity();
	if (!empty ($_GET['default'])) {
		$default = $_GET['default'];
	} else {
		$default = '';
	}
	if (!empty ($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = '';
	}
	//$DepID = $_POST['departmentid'];
	$permission = 0;
	$admin_permissions = 0;
 
	$chooseCab      = $trans['Choose Cabinet']; 
	$backupACabinet = $trans['Backup A Cabinet'];
	$Backup         = $trans['Backup'];
	$process        = $trans['Process Started'];
  
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" type="text/css" href="../lib/style.css">
	<script>
	//function that opens processing window
	function startProcessing(cabs) {
		document.onload=window.location="cdStatus.php?DepID="+cabs;
	}
	</script>
 </head>
ENERGIE;
 if(isset($_POST['submit']))		//cabinet has been selected for backup
 {
	$allCabinets = getTableInfo($db_object, 'departments',
		array('departmentid'), array('deleted' => 0), 'queryCol');
	//die (print_r($res));
	$cabs="";
	foreach($allCabinets as $ID) {
		if (isset($_POST[$ID]))
			$cabs.="{".$ID."}";
	}

	echo"<script>startProcessing('$cabs');</script>";
 }
 else			 
	//no cabinet selected, display drop down
 {
   	if($mess)
   	{
		$user->audit("Cabinet CDBackup finished",$mess);
    	echo "<center>$mess</center>";
 	}

	$settings=new GblStt ($user->db_name, $db_doc);
	$admin_permissions=$settings->get("adminBackup" );	//admin related permissions
	$user_permissions=$settings->get("userBackup" );		//user related permissions
	if($user_permissions=='1') {
		$permission=2;
	} elseif($user_permissions=='2') {
		$permission=1;
	}
}
	
	//override this setting if the user is an admin and this setting is set --\
	//this will allow admins to backup any cabinet they have any access to 
	if($user->isAdmin() && $admin_permissions=='1')
		$permission=1;

    echo "<center><table class=\"settings\" width=\"315\">\n";
    echo " <tr class=\"tableheads\" bgcolor=\"#003b6f\">\n  <td colspan=\"2\">$backupACabinet</td>\n </tr>\n";
	echo " <form name=\"getDepartment\" method=\"POST\" action=\"backupCabinet.php?mess=$mess\">\n";
    echo " <tr>\n";
   	echo "<td align=\"center\">$chooseCab:</td>\n";
    echo "<td>&nbsp;</td>\n";

	$accessRights = $user->getAccess();
	$cabList = $user->cabArr;
	foreach($cabList AS $real_name => $departmentname) {
		$rights = $accessRights[$real_name];
		//allow display if user has permission or is the system administrator
		if ($permission!=0 || $user->isDepAdmin())
		{
       		$condition = $user->getRWorRO( $rights, $permission );
			if($condition)
			{
				//This function is located in lib/utility.php
				$row2 = getTableInfo($db_object, 'departments',
					array('departmentid'), array('real_name' => $real_name), 'queryRow');
				if( sizeof($cabList) )
					echo" <tr><td><input type='checkbox' name='".$row2['departmentid']."' value='yes'/></td><td>{$departmentname}</td></tr>\n";
			}
		}
	}

echo<<<ENERGIE
	</td></tr>
	<tr><td colspan="2" align="right"><input type="submit" name="submit" value="$Backup"></td></tr>
	</form></table>
	</center>

</body></html>
ENERGIE;

	setSessionUser($user);
}
else	//log them out
{
	 //redirect them to login
	logUserOut();
}
?>
