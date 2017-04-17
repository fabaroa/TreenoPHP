<?php

include_once '../check_login.php';
include_once ('../classuser.inc');

if ($logged_in == 1 && strcmp($user -> username, "") != 0) 
{
	//print_r($_GET);
	$nextPage=$_GET['next_page'];
	$fromDialog = $_GET['fromDialog']; //if it is for editName
	
	$settings=new Usrsettings($user->username, $user->db_name );
	//if($settings->get('context')=="stop")
//start the search result polling page to replace this page
	//	$settings->set('context','update');	
	//die($next_page);
	$settings->removeKey('context');	//remove any context entry
	$_SESSION['fsrArray']=$_POST;	//for submission 
	
	echo "<script>";
	if( isSet($fromDialog) )
		echo "parent.mainFrame.window.location=unescape('$nextPage');";
	else
		echo"document.onload=window.location=unescape('$nextPage')";
	echo "</script>";


	setSessionUser($user);
}
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
?>
