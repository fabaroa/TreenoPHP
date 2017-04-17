<?php
chdir('/var/www/html/tools');
include_once('../db/db_common.php');
include_once('../lib/utility.php');
include_once('../lib/settings.php');

$db_doc = getDbObject('docutron');
$db_dept = getDbObject('client_files');
//create a ws object
$scharp = new SoapClient('http://asptest.schwabrt.com/SDDAv2/Scharp.asmx?WSDL');
//use the ws object to get the session id
$siteToken = "31";
$user = "wthibodeau";
$password = "treen0soft";
$sessionID = false;
$params = array('sitetoken'=>$siteToken, 'username'=>$user, 'password'=>$password);
try{ 
	$session = $scharp->GetSessionID($params);
	$sessionID = $session->GetSessionIDResult;
} catch (Exception $ex)
{
	die($ex->getMessage());
}
//$sessionID = "PAS-814545A8AEAA4C60926BAA47A96FE27A-31";
//this is the finished status, make sure nothing that you're querying is this status
//these have been completed and do not need to bogg down the bot
$finishedStatus = "hamburger"; //change this
$payroll_id_field = "srt_payroll_id";

//create a list of all the treeno payroll folders with an srt account number that are not yet
//completed.
//$payrollList = getTableInfo($db_dept, 'Payroll', array($payroll_id_field), array("$payroll_id_field > 0", "status != '$finishedStatus'"), 'queryCol');
$payrollList = array(21621, 21620, 21619);
if($sessionID !== false) 
{
	echo $sessionID,"\n";
	foreach($payrollList as $payroll_id)
	{
		//make the call for this id 
		try
		{
			$params = array('sessionID'=>$sessionID, 'eventActivityID'=>(int)$payroll_id, 'type'=>0);
			$details = $scharp->GetEventStatusById($params);
			die(print_r($details, true));
		}catch(Exception $ex) {
			die($ex->getMessage());
		}
	}
} else
{
	die("This is not funny anymore.");
}

