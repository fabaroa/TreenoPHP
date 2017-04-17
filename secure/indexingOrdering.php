<?php
// $Id: indexingOrdering.php 14218 2011-01-04 16:19:53Z acavedon $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';

if($logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin()) {
	$cabnames = array_keys( $user->access );	
	$systemPref	= $trans['System Preferences'];

	// Set all the settings in the database if the form was submitted
	$db_doc = getDbObject ('docutron');
	$sett = new GblStt( $user->db_name, $db_doc );
	if(isset($_POST['changeOrdering'])) {
		// This is the default
		$sett->set( "indexing_ordering", $_POST['ordering']);
		$enabled = $_POST['ordering'];
		// This loops through each individual cabinet
		for($i = 0 ; $i < sizeof($cabnames) ; $i++){
			$cabname = $cabnames[$i] ;
			// if it was set, put the setting, else delete it and use default
			if($_POST[$cabname] >= 0){
				$sett->set( "indexing_ordering_$cabname", $_POST[$cabname]) ;
				// This sets up checking off the appropriate options
				if($_POST[$cabname] == 0) {
					$status0[$cabname] = "checked" ;
					$status1[$cabname] = '';
					$statusD[$cabname] = '';
				} else {
					$status0[$cabname] = '';
					$status1[$cabname] = "checked" ;
					$statusD[$cabname] = '';
				}
			}
			else{
				$sett->removeKey( "indexing_ordering_$cabname");
				$statusD[$cabname] = "checked" ;
				$status0[$cabname] = '';
				$status1[$cabname] = '';
			}
		}
		$message = "Indexing Ordering Has Been Changed";
	} else { // If there was no form submitted
		$message = '';
		// Get the default value, set it if doesnt exist
		$enabled = $sett->get( "indexing_ordering" );
		if( $enabled == "" ) {
			$sett->set( "indexing_ordering", "1");
			$enabled = "1";
		}

		// Get the other defaults and store the checked values
		for($i = 0 ; $i < sizeof($cabnames) ; $i++){
			$cabname = $cabnames[$i] ;
			$tmp = $sett->get( "indexing_ordering_$cabname" ) ;
			if($tmp === "0") {
				$status0[$cabname] = "checked";
				$status1[$cabname] = '';
				$statusD[$cabname] = '';
			} else if($tmp === "1") {
				$status1[$cabname] = "checked";
				$status0[$cabname] = '';
				$statusD[$cabname] = '';
			} else {
				$statusD[$cabname] = "checked";
				$status1[$cabname] = '';
				$status0[$cabname] = '';
			}
		}
	}
	
	// Get the value for the default
	if($enabled=="0") {
		$statusen0 = "checked";
		$statusen1 = '';
	} else {
		$statusen1 = "checked";
		$statusen0 = '';
	}
echo<<<ENERGIE
<html>
 <head>
  <link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css"><title>$systemPref</title>
 </head>
 <body>
  <form name="movef" method="POST" action="indexingOrdering.php">
  <center>
   <table class="settings" width="566">
    <tr>
     <td colspan="4" class="tableheads">
      File Order
     </td>
    </tr>
    <tr>
     <td class="admin-tbl" align="left">
      Default Ordering
     </td>
     <td>
      Prepend
      <input type=radio value="0" $statusen0 name=ordering>
     </td>
     <td>
      Append
      <input type=radio value="1" $statusen1 name=ordering>
     </td>
     <td>&nbsp;</td>
    </tr>
    <tr><td colspan="4">&nbsp;</td></tr>
    <tr>
     <td>&nbsp;</td>
     <td>Prepend</td>
     <td>Append</td>
     <td>Default</td>
    </tr>
ENERGIE;
	for($i = 0 ; $i < sizeof($cabnames) ; $i++) {
		if (isset ($user->cabArr[$cabnames[$i]])) {
			$bgcolor = "" ; // clear it out 
			//if(($i % 2) == 1) $bgcolor="style=\"background-color: #eeeeee\"" ;
			//else $bgcolor="style=\"background-color: #dddddd\"" ;
			$cabname = $cabnames[$i] ;
			$st0 = $status0[$cabname] ;
			$stD = $statusD[$cabname] ;
			$st1 = $status1[$cabname] ;
			echo<<<ENERGIE
    <tr $bgcolor>
     <td>{$user->cabArr[$cabname]}</td>
     <td><input type="radio" value="0" name="$cabname" $st0>
     <td><input type="radio" value="1" name="$cabname" $st1>
     <td><input type="radio" value="-1" name="$cabname" $stD>
    </tr>
ENERGIE;
		}
	}
echo<<<ENERGIE
    </tr>
     <td colspan="4" align="right">
      <span class="error">$message</span>&nbsp;
      <input name="changeOrdering" type="submit" value="Save">&nbsp;&nbsp;
     </td>
    </tr>
   </table>
  </center>
  </form>
 </body>
</html>
ENERGIE;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
