<?php
include_once '../lib/utility.php';
/*
 * output the possible workflow defs
 * the first inidex is the workflow def
 */
function wfIndexing0( $db_object, $name )
{
	$wf_defs =  getTableInfo($db_object,'wf_defs',array('DISTINCT(defs_name)'),array(),'queryCol',array('defs_name'=>'ASC'));
	$fieldvalues = "<select name=\"$name\" value=\"\">\n";
	$fieldvalues .= "<option value=\"\" selected></option>\n" ;
	for($o = 0; $o < sizeof( $wf_defs ); $o++)
	{
		$fieldvalues .= "<option value=\"$wf_defs[$o]\">$wf_defs[$o]</option>\n";
	}
	$fieldvalues .= "</select>\n";
	return $fieldvalues;
}
/*
 * output the possible cabinets to send the document to
 * the second index is a list of cabinets
 */
function wfIndexing1( $db_object, $name, $path)
{
	$cabs = getTableInfo($db_object, 'departments', array(), array('deleted' => 0));
	$fieldvalues = "<select name=\"$name\">\n";
	while($row = $cabs->fetchRow())
	{
		$name = $row['real_name'];
		$displayName = $row['departmentname'];
		$fieldvalues .= "<option value=\"$name\"";
		$fieldvalues .= ">$displayName</option>\n";
	}
	$fieldvalues .= "</select>\n";
	return $fieldvalues;
}
/*
 * output the datetime of the directory
 * the third index is date scanned is the time at which the 
 * document got onto the server
 */
function wfIndexing2( $db_object, $name, $path )
{
	$arr = stat( $path );
	$timestamp = $arr[9];
	$d = date( "Y-m-d", $timestamp );
	$fieldvalues .= "<input readonly type=\"textfield\" name=\"$name\" ";
	$fieldvalues .= "onkeypress=\"return allowDigi(event);\" ";
	$fieldvalues .= "value=\"$d\" size=\"15\">";
	return $fieldvalues;	
}
/*
 * output the current time
 * the 4th index is date indexed which is the time at which the 
 * file was assigned to workflow
 */
function wfIndexing3( $db_object, $name, $path )
{
	$passInValue=date('Y')."-".date('m')."-".date('d');
	$fieldvalues .= "<input readonly type=\"textfield\" name=\"$name\" ";
	$fieldvalues .= "onkeypress=\"return allowDigi(event);\" ";
	$fieldvalues .= "value=\"$passInValue\" size=\"15\">";
	return $fieldvalues;	
}
?>
