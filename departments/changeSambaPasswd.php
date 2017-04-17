<?php                                                         

include_once '../check_login.php';
include_once '../classuser.inc';                                   
                                                             
if($logged_in==1 && strcmp($user->username,"")!=0 && $user->isSuperUser()) {       
  	$default = $_GET['default'];
	$mess = $_GET['mess'];
    if( isset( $_POST['B1'] ) ) {
		$newPass = $_POST['newPass'];
		$confirmPass = $_POST['confirmPass'];
		if( $newPass != $confirmPass) {
			$mess = "Passwords Do Not Match";
			echo "<script>";
			echo "document.onload = parent.mainFrame.window.location='changeSambaPasswd.php?default=$default&mess=$mess'";
			echo "</script>";
			die();
		}
		if( $newPass == NULL || $confirmPass == NULL ) {
			$mess = "A field was blank";
			echo "<script>";
			echo "document.onload = parent.mainFrame.window.location='changeSambaPasswd.php?default=$default&mess=$mess'";
			echo "</script>";
			die();
		}
		shell_exec("(echo $newPass;echo $confirmPass) | smbpasswd -a " .
				"$user->db_name -s");
		$mess = "Password Successfully Changed";
    }
  	echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
 </head>
 <body>
  <center>
ENERGIE;
	if( $mess )
   		echo "<div class='error'>$mess</div>\n";
echo<<<ENERGIE
   <form name="samba" method="POST" target="mainFrame" action="changeSambaPasswd.php?default=$default">
    <table class="settings" width="315">
	 <tr class="tableheads">
      <td colspan="2">Enter new Samba Password</td>
     </tr>
  	 <tr>
	  <td>New Password</td>
	  <td><input type="password" name="newPass" ></td>
     </tr> 
  	 <tr>
	  <td>Confirm Password</td>
	  <td><input type="password" name="confirmPass"></td>
     </tr>
  	 <tr>
	  <td colspan="2" align="right">
       <input type="submit" name="B1" value="Submit">
      </td>
     </tr>
    </table>
   </form>
  </center>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?> 
