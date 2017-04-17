<?php
//session_start();
include("commons.php");
CheckSession('selectAcount.php');

$AcountNum = $_GET["num"];

$_SESSION["DS_LOGED"] = "1";
$selAccount = $_SESSION["docuSignAccts"][$AcountNum];
$_SESSION["AccountID"] = $selAccount->AccountID;
$_SESSION["AccountName"] = $selAccount->AccountName;
$_SESSION["UserName"] = $selAccount->UserName;
$_SESSION["email"] = $selAccount->Email;
$_SESSION["UserID"] = $selAccount->UserID;

unset($_SESSION["docuSignAccts"]);

if(isset($_GET['status']))
{
	header( 'Location: docuSignStatus.php' ) ;	
}
else if(isset($_GET['complete']))
{
	$id = isset($_GET["id"])? $_GET["id"]:0;
	header( 'Location: complete.php?id='.$id ) ;	
}
else
{
	header( 'Location: docuSignApply.php' ) ;	
}

?>