<?php
// $Id: scrollIndex.php 14229 2011-01-04 16:33:29Z acavedon $

include_once '../modules/modules.php';
include_once '../check_login.php';
include_once '../classuser.inc';

if($logged_in == 1 && strcmp( $user->username, "" )!=0)
{
	$systemPref    = $trans['System Preferences'];
	$scrollSettsCh = $trans['indexScroll Changed'];
	$scrollSetts   = $trans['indexScroll'];
	$selectSetts   = $trans['Select Settings'];
	$enable        = $trans['Enable'];
	$disable       = $trans['Disable'];

	$db_doc = getDbObject ('docutron');
	$settings = new GblStt( $user->db_name, $db_doc );
	$setScroll = $settings->get( 'scroll' );
	if($setScroll == null) // for updating old db versions 
	{
		$settings->set( 'scroll', '1');
		$setScroll = $settings->get( 'scroll' );
	}

	if(isset($_POST['changeScroll']))
	{
		$enabled = $_POST['enable'];
		$settings->set( 'scroll', $enabled);
		$setScroll = $settings->get( 'scroll' );
		$message = $scrollSettsCh;
	} else {
		$message = '';
	}

echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>$systemPref</title>
 </head>
 <body>
<form name="movef" method="POST" action="scrollIndex.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="3" class="tableheads">$scrollSetts</td>
    </tr>
    <tr>
     <td class="admin-tbl">$selectSetts:</td>
ENERGIE;
	if($setScroll) {
		$status1 = "checked";
		$status2 = '';
	} else {
		$status2 = "checked";
		$status1 = '';
	}
echo<<<ENERGIE
     <td>
	$enable: <input type=radio value=1 $status1 name=enable>
     </td>
     <td>
	$disable: <input type=radio value=0 $status2 name=enable>
     </td>
    </tr>
    <tr>
     <td colspan="3">
ENERGIE;
	
		//display message if a change was successfully made
		if( $message)
   			echo "<div class=\"error\">$message\n";
		else
			echo "<div>\n";

echo<<<ENERGIE
		<input name="changeScroll" type="submit" value="Save"></div>  
    </tr>
   </table>
  </center>
</form>
 </body>
</html>

ENERGIE;

	setSessionUser($user);
                                                                                                                             

}
else 
{
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
