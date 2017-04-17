<?php

include_once '../check_login.php';
include_once '../classuser.inc';


if( $logged_in == 1 && strcmp( $user->username, "" )!=0 )
{
	
	$doc_id = $_GET['doc_id'];
	$cab = $_GET['cab'];
	$tab = $_GET['tab'];
	$ID = $_GET['ID'];
	$temp_table1 = $_GET['table1'];
	$temp_table2 = $_GET['table2']; 


	setSessionUser($user);
echo<<<ENERGIE
<script language="javascript"> 

var Frameset="<frameset cols='85%,15%'>" + 
       "<frame src='display.php?DepartmentName=
ENERGIE;
echo $cab."&doc_id=".$doc_id."&ID=".$ID."&tab=".$tab."&table1=".$temp_table1."&table2=".$temp_table2;
echo<<<ENERGIE
' name='mainFrame' marginwidth='1'"+
       "marginheight='1' noresize>" +
       "<frame src='allthumbs.php?cab=
ENERGIE;
echo $cab."&doc_id=".$doc_id."&ID=".$ID."&tab=".$tab."&table1=".$temp_table1."&table2=".$temp_table2;
echo<<<ENERGIE

' name='sideFrame' marginwidth='1'" +
       "marginheight='1'>" +
       "</frameset><noframes>";

document.write(Frameset) 
</script> 

<frameset cols='85%,15%'>
  <frame src='display.php?DepartmentName=
ENERGIE;
echo $cab."&doc_id=".$doc_id."&ID=".$ID."&tab=".$tab."&table1=".$temp_table1."&table2=".$temp_table2;
echo<<<ENERGIE
' name='mainFrame' marginwidth='1' marginheight='1' noresize>
  <frame src='allthumbs.php?cab=
ENERGIE;
echo $cab."&doc_id=".$doc_id."&ID=".$ID."&tab=".$tab."&table1=".$temp_table1."&table2=".$temp_table2;
echo<<<ENERGIE

' name='sideFrame' marginwidth='1' marginheight='1'>
</frameset></noframes>
ENERGIE;

}else{
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
