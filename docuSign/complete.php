<?php
include("commons.php");
CheckSession('complete.php');

$id = isset($_GET["id"])? $_GET["id"]:0;
if(!isset($_SESSION["DS_LOGED"]) || ($_SESSION["DS_LOGED"] != "1"))
{
	header( 'Location: docuSignLogin.php?complete&id='.$id ) ; 
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">	
	<head>
		<title>SyndicIT</title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<link rel="stylesheet" type="text/css" href="css/style.css">
				
	</head>
	<body >
 		<div class="header">
			<div class="floatLeft" >
				<img src="images/logo.png" alt="Treenosoftware - Your global document management solution">
			</div>
			<div class="userBox">
				<span style="font-weight: bold;">Username:</span>
				<span><?php echo($_SESSION["UserName"]) ?></span>
				<br>
				<span style="font-weight: bold;">Account:</span>
				<span><?php echo($_SESSION["AccountName"]) ?></span>
			</div>
		</div>
		
		<div class="gutter"></div>

		<div>
			<span class="col1">
				<!--  <h1>Automobile Insurance Application</h1>-->
				<h1>Rental Application</h1>
                <?php
                	//$id = isset($_GET["id"])? $_GET["id"]:0;				
					if ($id==10) echo("Signing Status: 	Application was sent successfully.");
					if ($id==9) echo("Signing Status: 	Application was signed successfully. You are now insured!");
					if ($id==3) echo("Signing Status:   Application was not signed successfully because it has been cancelled. ");
					if ($id==4) echo("Signing Status:   Application was not signed successfully because it was declined. ");
					if ($id==5) echo("Signing Status:   Application was not signed successfully due to a session timeout. ");
					if ($id==6) echo("Signing Status:   Application was not signed successfully due to an expired Time-To-Live. ");
					if ($id==7) echo("Signing Status:   Application was not signed successfully due to an exception that occured. ");
					if ($id==8) echo("Signing Status:   Application was not signed successfully due to an Access Code Failure. ");
					if ($id==2) echo("Signing Status:   Application has been viewed, but has not yet been completed. ");
					if ($id==1) echo("Signing Status:   Application was not signed successfully due to an Id Check Failure. ");
					
					if ($id==0) echo("Signing Status:  Unknown ");
                ?>                				
			</span>
		</div>
	
		<div class="footer">
			Treeno - Docusign		</div>
	</body></html>