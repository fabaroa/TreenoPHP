<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../modules/modules.php';

$nodeTypes = array(
	'SIGNATURE',
	'VALUE',
	'CUSTOM',
	'ADD FILE',
	'INDEXING',
	'WORKING',
	'FINAL'
);

if( check_enable( "MAS500", $user->db_name ) )
	$nodeTypes[] = 'MAS500';

if( check_enable( "Outlook", $user->db_name ) )
	$nodeTypes[] = 'OUTLOOK';

if( check_enable( "centera", $user->db_name ) ) {
	$nodeTypes[] = 'CENTERA';
	$nodeTypes[] = 'FIDELITY';
}
?>
