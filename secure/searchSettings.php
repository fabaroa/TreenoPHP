<?php

include_once '../check_login.php';
include_once '../classuser.inc' ;

// check for user
if($logged_in == 1 && strcmp($user->username, "") != 0)
{
	$searchSet = $trans['Search Settings'] ;

	$userset = new Usrsettings( $user->username, $user->db_name ) ;

	// Check for submission of the page
	if(isset($_GET['submit'])){
		$userset->set('context_type', $_GET['type']) ;
		$userset->set('context_hits', $_GET['count']) ;
		$mess = "Settings have been updated" ;
	}

	if(($searchtype = $userset->get('context_type')) == 1)
		$typecheck1 = "checked" ;
	else
		$typecheck0 = "checked" ;

	if(($contexthtis = $userset->get('context_hits')) == 1)
		$countcheck1 = "checked" ;
	else
		$countcheck0 = "checked" ;

echo<<<ENERGIE
<html>
 <head>
  <link rel="stylesheet" type="text/css" href="../lib/style.css">
  <title>$searchSet</title>
 </head>
 <body>
  <form action="searchSettings.php">
  <center>
   <table class="settings" width="400">
    <tr>
     <td colspan="2" class="tableheads">$searchSet</td>
    </tr>
    <tr>
     <td class="admin-tbl">Default Context Search Type:</td>
     <td>
      <input type="radio" name="type" value="1" $typecheck1> Exhaustive
      &nbsp;&nbsp;&nbsp;&nbsp;
      <input type="radio" name="type" value="0" $typecheck0> Quick
     </td>
    </tr>
    <tr>
     <td class="admin-tbl">Context Search Count Hits:</td>
     <td>
      <input type="radio" name="count" value="1" $countcheck1> Yes
      &nbsp;&nbsp;&nbsp;&nbsp;
      <input type="radio" name="count" value="0" $countcheck0> No
     </td>
    </tr>
    <tr>
     <td colspan="2" align="right">
      <span class="error">$mess</span>&nbsp;&nbsp;
      <input type="submit" name="submit" value="Submit">
     </td>
    </tr>
   </table>
   </center>
  </form>
 </body>
</html>

ENERGIE;

	setSessionUser($user);
                                                                                                                             

}
else{ // send to login
echo<<<LOGIN
<html>
 <body bgcolor="#FFFFFF">
  <script>
   document.onload = top.window.location = "../logout.php" ;
  </script>
 </body>
</html>
LOGIN;
}
?>
