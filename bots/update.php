<?php
//NOT WINDOWS-SAFE - cannot overwrite files that are currently open.
//bot to get and install updates
include_once '../db/db_common.php';
include_once '../updates/updatesFuncs.php';
include_once '../settings/settings.php';
include_once '../lib/fileFuncs.php';

	$db_doc = getDbObject('docutron');
/* update.php needs to be passed the following variables:

	arg[1] current_version -- current version of software installed
	arg[2] latest_version -- version the user wants to get
	arg[3] num_patches -- number of different versions that must be patched
	arg[4] key -- server uses to identify this customer
*/
	$interface=$_SERVER['SERVER_ADDR'];

	$current_version=$argv[1];
	$latest_version=$argv[2];
	$num_patches=$argv[3];
	$key=$argv[4];
	$dep=$argv[5];
	$gblStt = new GblStt($dep, $db_doc);

	//insert user logout entry to mark that we have started
	$gblStt->set('version_update', 'user_logout');

	$whereArr = array('username'=>'<> "admin" ');
	deleteTableInfo($db_doc,'user_polls',$whereArr,1);

	$whereArr = array('username'=>'<> "admin" ');
	deleteTableInfo($db_doc,'user_polls',$whereArr,1);
	//completed user_logout
	$gblStt->set('version_update', 'lock_login');

	//retrieve entry from licenses, set to 1 for lock (set back after update is complete)
	$res = getLicensesInfo( $db_doc, $dep );
	$row=$res->fetchRow();
	$original_licenses=$row['max'];

	$updateArr = array('max'=>1);
	$whereArr = array('real_department'=> $dep);
	updateTableInfo($db_doc,'licenses',$updateArr,$whereArr);

	//completed login lock
	$gblStt->set('version_update', 'lock_login_complete');

	$localpath=$DEFS['DATA_DIR']."/$dep/";
	//do for each patch
	for($i=1;$i<=$num_patches;$i++) {

		//start downloading patch
		$gblStt->set('patch_'.$i, $latest_version);
		//convert version into actual filename
		$filename=getVersionFilename($latest_version);

		echo "arguments to get version: key=$key version=$latest_version\n";

		//retrieve the version file
		$d_key=base64_decode($key);
		$u="http://demo.docutronsystems.com/versions/$d_key/$filename";
		$s=$DEFS['DATA_DIR']."/$dep/$filename";
		shell_exec($DEFS['WGET_EXE'] . ' ' . escapeshellarg ($u) . ' -O ' .
				escapeshellarg($s));
		$md5=getmd5Sum($s, $DEFS);

		//retrieve the correct md5 encoding from the database

		$stored_md5 = file_get_contents("http://demo.docutronsystems.com/" . 
				"licensing/server/getmd5.php?key=$key&version=$latest_version");
	
		//check that file was existant
		if(strcmp($stored_md5,"no_file")==0) {
			$gblStt->set('version_fail', 'Error Retrieving Version File');
			unlink($localpath.$filename);
			$updateArr = array('max'=>$original_licenses);
			$whereArr = array('real_department'=> $dep);
			updateTableInfo($db_doc,'licenses',$updateArr,$whereArr);
			die ("file does not exist in md5<br>");
		}

		if(strcmp($stored_md5,$md5)!=0)	{//encodings not matched
			$gblStt->set('version_fail', 'Version file corrupted. Try Again');
			$fp=fopen("http://demo.docutronsystems.com/licensing/server/corruptVersion.php?key=$key&version=$current_version&md5=$md5","r");
			fclose($fp);
			unlink($localpath.$filename);
			$updateArr = array('max'=>$original_licenses);
			$whereArr = array('real_department'=> $dep);
			updateTableInfo($db_doc,'licenses',$updateArr,$whereArr);
			die ("file is corrupted: local=$md5 remote=$stored_md5");
		}
		//verify the sucess in the histories table
		$fp=fopen("http://demo.docutronsystems.com/licensing/server/getVersion.php?key=$key&version=$latest_version","r");
		fclose($fp);		//mark that download is complete
		$gblStt->set('patch_'.$i, 'complete');
		$gblStt->set('version_update', 'install');
		//install this patch
	
		//unzip file
 		
 		shell_exec ($DEFS['GUNZIP_EXE'] . ' -q9d ' . escapeshellarg ($localpath.$filename));

		//untar file (name has already had .gzip removed
		$unzipped_filename=getUnzippedFilename($filename);
		chdir($localpath);
 		$tar_cmd= $DEFS['TAR_EXE'] . ' xf ' . escapeshellarg ($localpath.$unzipped_filename);
 		shell_exec ($tar_cmd);
  
 		unlink($localpath.$unzipped_filename);	//remove tar file

		//actually install the version
		$dest_location=$DEFS['DOC_DIR'];
		
		$real_filename=getVersionFilenameNoExt($latest_version);

 		copyDir ($localpath.$real_filename, $dest_location, true);
 
 		delDir($localpath.$real_filename);
	
		//run the upgrade script if it exists

		$gblStt->set('version_update', 'install_complete');
	}
	$gblStt->set('version_update', 'remove_lock');
	$updateArr = array('max'=>$original_licenses);
	$whereArr = array('real_department'=> $dep);
	updateTableInfo($db_doc,'licenses',$updateArr,$whereArr);
	$gblStt->set('version_update', 'remove_lock_complete');

$db_doc->disconnect();
?>
