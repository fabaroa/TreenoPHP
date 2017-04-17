<?php
function checkTab( $path )
{
	$count = substr_count($path, "/");
	$tab = tabName( $count, $path );
return( $tab );
}
function tabName( $count , $path)
{
	if($count == 1)
		$tab = strtok($path,"/");
return( $tab );
}
function getInfo( $path, $count )
{
	$cab = strtok($path,"/");
	for($i=0;$i<$count;$i++)
		$cab = strtok("/");

return( $cab );
}
/*function removeFiles( $cab, $fold )
{
	global $db_object;	
	
}*/
?>
