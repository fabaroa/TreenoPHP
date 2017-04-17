<?php
include_once 'modules.php';

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/cabinets.php';
include_once '../lib/quota.php';
                                                                                                                             
if( $logged_in == 1 && strcmp( $user->username, "")!=0 )
{
 	$tableTitle_4        = $trans['Storage Status'];
	$tableTitle_6        = $trans['Cabinet Status'];
    $spaceAllowed        = $trans['Space Allowed'];
    $spaceUsed           = $trans['Space Used'];
    $percentUsed         = $trans['Percent Used'];
    $cabinet             = $trans['Cabinet'];
    $folders             = $trans['Folders'];
    $files               = $trans['Files'];

	$max = getLicensesInfo( $db_doc, $user->db_name );
    $max = $max->fetchRow();
    $usr_max = $max['max'];
    $quota_allowed = $max['quota_allowed'];
    $quota_used = $max['quota_used'];

	$allowed = adjustQuota( $quota_allowed );
    $used = adjustQuota( $quota_used );
    if( $quota_used != 0 && $quota_allowed != 0)
    	$pUsed = round(($quota_used / $quota_allowed) * 100);
    else
        $pUsed = 0;
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
 </head>
 <body>
  <center>
   <table class="settings" width="278">
    <tr>
     <td colspan="2" class="tableheads">$tableTitle_4</td>
    </tr>
    <tr>
     <td class="admin-tbl" width="75%" align="center">$spaceAllowed:</td>
     <td>$allowed</td>
    </tr>
    <tr>
     <td class="admin-tbl" align="left">$spaceUsed:</td>
	 <td>$used</td>
    </tr>
    <tr>
     <td class="admin-tbl" align="left">$percentUsed:</td>
	 <td>$pUsed%</td>
    </tr>
ENERGIE;
    if( $pUsed > 95 )
        echo "<tr><td colspan='2' class='error'>Contact Administrator For More Space</td></tr>";
echo<<<ENERGIE
   </table>
  <br>
ENERGIE;
  if( $user->isDepAdmin() )
  {
echo<<<ENERGIE
   <table class="settings" width="278">
     <tr>
      <td colspan="3" class="tableheads">$tableTitle_6</td>
     </tr>
     <tr class="admin-tbl">
      <td>$cabinet</td>
      <td>$folders</td>
      <td>$files</td>
     </tr>
ENERGIE;
    //$uname = $user->username;
    $user->setSecurity();
	if( $user->access ) {
		$cablist = array_merge(array_keys( $user->access,'rw'), array_keys( $user->access,'ro'));
	} else {
		$cablist = array ();
	}
    //$cablist = cabinetList($uname,$user);
	//if( is_array( $cablist ) )
	//	usort( $cablist, "strnatcasecmp" );
    
	$totalFolderCount = 0;
	$totalFileCount = 0;
	//for each cabinet
    for($i=0;$i<sizeof( $cablist );$i++)
    {
		if( !empty($user->cabArr[$cablist[$i]]) )
		{
			echo "     <tr>\n";
			//get cabinet name
			//$tmp = str_replace("_"," ",$cablist[$i]);
			$tmp = $user->cabArr[$cablist[$i]];
			echo "      <td>$tmp</td>\n";
			//get # of folders
			$count = getTableInfo($db_object,$cablist[$i],array('COUNT(doc_id)'),array('deleted'=>0),'queryOne');
			$totalFolderCount += $count;
			echo "      <td>$count</td>\n";
			//get # of files
			$count = getNumFilesInCab($cablist[$i], $db_object);
			$totalFileCount += $count;
			echo "      <td>$count</td>\n";
			echo "     </tr>\n";
		}
    }

echo<<<ENERGIE
     <tr class="admin-tbl">
      <td class="tableheads">Total</td>
	  <td>$totalFolderCount</td>
	  <td>$totalFileCount</td>
	 </tr>
   </table>
ENERGIE;
	
  }
echo<<<ENERGIE
  </center>
 </body>
</html>\n             
ENERGIE;

	setSessionUser($user);
} else {
	logUserOut();
}
?>
