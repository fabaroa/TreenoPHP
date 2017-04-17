<?php
// $Id: audit.php 14281 2011-03-18 19:57:09Z acavedon $

include_once '../check_login.php';

/*************************************************/
/*This Function prints out the fieldnames for Audit 
  using using a string translation array contained 
  in i18nStrings.php*/
function showFields($db_object, $user) {
	$info = getTableColumnInfo ($db_object, 'audit');
	foreach($info as $column) {
		echo "    <tr><td align='left'>";
		echo $column;//performs the string translation
		echo "  </td> ";
		if( $column == "username" AND !$user->isUserDepAdmin($user->username) ) {
			echo "<td><input type='textfield' name='{$column}' " .
				"value='".$user->username."' readonly='true' size='20'></td></tr>";
		} else {
			echo "<td><input type='textfield' name='{$column}' " .
				"value='' size='20'></td></tr>";
		}
	}
}
/*************************************************/
/* This Function inserts all the result ids that 
   met the search conditions submitted by the user.
   The ids are inserted into a temp table */
function searchQuery( $destTable , $whereArr, $db_object ) {
	insertFromSelect($db_object, $destTable, array('result_id'), 'audit',
		array('id'), $whereArr, array('datetime' => 'ASC'));
}
/*************************************************/
/*This Function gets the index of the page that the 
  user is viewing*/  
function getIndex( $db_object, $index, $table ) {
    //allows 100 values to be viewed a page
	$start = ($index * 100);//minimum value viewed
    $end = 100;//maximum value viewed
	$res = getIndex1($db_object, $table,  $start, $end);

return ( $res );//returns query results
}
/*************************************************/
/*This Function checks if the index value is out of
  bounds*/
function checkIndex( $count )
{
	if(isset($_POST['textfield'])) {
		if($_POST['textfield'] < 0 )//checks if entered value is lower than 0
			$index = 0;
		elseif( $_POST['textfield'] > $count )//checks if entered value is greater than last page
			$index = $count;
		elseif( $_POST['textfield'] )//entered value is in bounds and redirected to the entered value
			$index = $_POST['textfield'] - 1;
	} else//button has been submitted
		$index = $_GET['index'];

return ( $index );// returns index value
}
/*************************************************/
?>
