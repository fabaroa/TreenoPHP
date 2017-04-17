<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/filename.php';
include_once '../lib/mime.php';
if($logged_in and $user->username !== '') {
	$currentPage = $_GET['page'];
	$filesArray = $_SESSION['indexFileArray'];
	$nextFileLoc = $filesArray[$currentPage - 1];
	if(file_exists($nextFileLoc)) { 
		$fileDirectory = dirname ($nextFileLoc);
		$fileName = basename ($nextFileLoc);
		if( isset( $_SESSION[$user->db_name.'-'.$user->username.'-Usrsettings']) ){
			$arr = $_SESSION[$user->db_name.'-'.$user->username.'-Usrsettings'];
			if( isset($arr['settings']['indexingQuickView'])){
				if( $arr['settings']['indexingQuickView']==1 ){
					downloadFile($fileDirectory, $fileName, false, false, '', true );
				}else{
					downloadFile($fileDirectory, $fileName, false, false, '', false );
				}	
			}else{
				downloadFile($fileDirectory, $fileName, false, false, '', true );
			}	
		}else{
			downloadFile($fileDirectory, $fileName, false, false, '', true );
		}
	}
	setSessionUser($user);
} else {
	logUserOut();
}
?>
