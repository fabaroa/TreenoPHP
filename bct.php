<?php

$cabinetArr = array(
	'clientid'		=> 'Clients',
	'policyid'		=> 'Policies',
	'lossid'		=> 'Claims',
	'memoid'		=> 'Activity_Log',
	'marketingid'	=> 'Marketing'
);

$cabinet = $cabinetArr[key($_GET)];
if(!$cabinet) die("No matching cabinet found!\n");
$searchStr = current($_GET);

$newURL = "login.php?autosearch=$searchStr&cabinet=$cabinet";
//echo $newURL;
header("Location: $newURL");

?>
