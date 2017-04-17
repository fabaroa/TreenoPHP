<?php
include_once ( '../classuser.inc');
#include_once '../check_login.php';


/*
 * Expects a string $tab, and user object $user to be passed 
 *	from createTab.php, editTab.php, and createTabs.php.
 * Function checks for errors in user entered tab name.
 * Returns error messages if tab name is invalid.
 * Returns false if tab name is valid.
 */
function tabCheck($tab, $user)
{
     global $trans;

     $badFstChar      = $trans['First Character Invalid'];
     $tabNameReserved = $trans['Tab Reserved'];
     $badCharacters   = $trans['Invalid Characters'];
     $missingField    = $trans['Missing Field'];
	
	//check for empty field
	if($tab == null)
	{
		$mess = $missingField;
		return $mess;
	}

	//function in classuser.inc
	$status = $user->invalidCharacter($tab);  
	
	//check for invalid characters in the tab
	if($status === true)
	{
		$mess = $badCharacters;
	}
	//check for invalid tab names "main" and Javascript reserved words
	elseif( ($tab == "main") || ($user->invalidJscriptNames($tab)) )
	{
		$mess = $tabNameReserved.$tab;
	}
	//check for invalid number as first character
/*	elseif($status !== false)
	{
		$mess = $badFstChar." ".$status;
	}*/
	//return false if tabs are valid (not invalid == false)
	else
		return false; 

	//return $mess if there is an error
	return($mess); 
}

?>
