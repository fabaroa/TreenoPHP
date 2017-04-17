<?php
include_once '../db/db_common.php';
include_once '../lib/settings.php';
function checkInboxSelected( $path, $filesSelected )
{
	$total = 0;
    for($i=0;$i<sizeof($filesSelected);$i++)
    {
        $filename = urldecode( $filesSelected[$i] );
        if( file_exists( $path."/".$filename ) )
        {
			if( is_file( $path."/".$filename ) )
                $total += filesize($path."/".$filename);
            else
            	$total += duDir($path.'/'.$filename);
        }
    }
    return( $total );
}
/**
  This function just formats the quota size by translating it to KB,MB,GB
*/
function adjustQuota( $size )
{
	$adjSize = $size;
	if( $size >= 1099511627776 )
		$adjSize = round( ( $size / 1099511627776 ), 2 )."TB";
	elseif( $size >= 1073741824 )
	 	$adjSize = round( ( $size / 1073741824 ), 2 )."GB";
	elseif( $size >= 1048576 )
		$adjSize = round( ( $size / 1048576 ), 2 )."MB";
	elseif( $size >= 1024 )
		$adjSize = round( ( $size / 1024), 2 )."KB";

return $adjSize;
}
/**
  This function will retrieve the file size and check to see if by adding this
  file the quota limit is not exceeded
*/
function checkQuota($db, $fsize, $dbname) {
	$quotaInfo = getLicensesInfo ($db, $dbname);
	$quotaResults = $quotaInfo->fetchRow ();
	$quota_allowed = $quotaResults['quota_allowed'];
    $quota_used = $quotaResults['quota_used'];
	if(($quota_used + $fsize) < $quota_allowed) {
		return true;
	} else {
		error_log('CHECK QUOTA FAILED!!!!!'." - $dbname");
		return true;
	}
}
function calcSpaceForUpload()
{
	$fileSize = 0;
	for($i = 0; $i < count($_SESSION['upFile']); $i++) 
	{
		$finfo = stat($_SESSION['upFile'][$i]['tmpLoc']);
		$fileSize += $finfo[7];
	}
return $fileSize;		
}
?>
