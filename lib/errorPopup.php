<?php

include_once '../check_login.php';
include_once ('../classuser.inc');
//variables passed from energiefuncs.php    
$cab = $_GET['cab'];
$path = $_GET['path'];

//translated variables
$docTitle = $trans['Error: Indexing File Not Found'];
$InvalidPath = $trans['Invalid File Path'];
$cabinet = $trans['Cabinet'];
$skip = $trans['Skip to next page'];

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	echo<<<ENERGIE
	<html>
	 <head>
	  <link rel='stylesheet' type='text/css' href='../lib/style.css'>
	  <title>$docTitle</title>
	 </head>
	 <body>
	  <center>
	  <img width='300' src='../images/addfoldertop.jpg'>
	  <table class="admin-tbl" cellpadding="1" cellspacing="2" border='2' width='300'>
		 <tr class='tableheads'>
			<td colspan='2'>$docTitle</td>
		 </tr>
		 <tr>
			<td>$cabinet:</td>
			<td>$cab</td>
		 </tr>
		 <tr>
			<td>$InvalidPath:</td>
			<td>$path</td>
		 </tr>
		 <tr>
			<td colspan='2'>
				<form name="tabName" method="POST" action="../secure/indexing.php?cab=$cab">
				<input type="submit" value="$skip">
			 </form>
			</td>
		  </tr>
	  </table>
	  <img src='../images/addfolderbottom.jpg' width='300'>
	  </center>
	 </body>
	</html>
ENERGIE;

	setSessionUser($user);
} else { //redirect them to login
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