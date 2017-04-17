<?php
//NOT WINDOWS-SAFE
define("VOLUME_SIZE", 4294967296 );//actual volume sizes for dvds
//send backup to windows machine
if( !is_dir( "/root/backup" ))
	mkdir( "/root/backup", 777 );//make the directory to mount the windows share in
$remoteDrive = "//$argv[1]/docutronbackup";// this is the remote drive
$localDrive = "/root/backup";
$cmd = "smbmount " . escapeshellarg($remoteDrive) . ' ' .
	escapeshellarg($localDrive) . "-o username=guest,password=";
shell_exec($cmd);
/***************run sql backup***********************************/
$cmd = $DEFS['PHP_EXE'] . ' -q ' .
	escapeshellarg($DEFS['DOC_DIR'].'/bots/backupDB.php');
shell_exec($cmd);
/* Create Backup Directory for this backup */
$backupdir = date( "Y-m-d-H-i-s" );
$BACKUPS_NAME = "dms_backups";
/* Initialize Settings of backup bot */
if( file_exists( "/etc/docutron/.backupBot" ) ){
	//read in settings from last backupdd
	$fArr = file( "/etc/docutron/.backupBot");
	//read in the latest timestamp
	$latest = trim($fArr[0]);//in second from unix birth
	$vsize = trim($fArr[1]);//in bytes
}else{
	//backup everything
	$latest = 0;
	$vsize = 0;
}
/*traverse files backup things that are newer than the last newest timestamp*/
//eventually get multiple departments here.
$department = "client_files";
//set the backupdir_base place to make the incremental backups
$backupdir_base = "$localDrive/$BACKUPS_NAME";
//check if directory exists
if( !is_dir( $backupdir_base ) )
	//make directory for backups
	mkdir( $backupdir_base );
//set the base path for the backups
//$volume = $size % 4294967296 +1; //this is the one for 4 jiggabytes
$volume = floor($vsize / VOLUME_SIZE + 1);
echo "Backup directory path $BACKUPS_NAME/$volume/$backupdir\n";
$bpath = "$backupdir_base/$volume/$backupdir";
if( !is_dir( "$backupdir_base/$volume" ) )
	mkdir( "$backupdir_base/$volume" );
//make the directory for this backup
mkdir( $bpath );
//start the backups by calling the following function
$datadir = "/var/www/$department";
$latest=traverseDir($bpath,
					$datadir,
					"",
					$latest,
					$latest,
					$datadir,
					$vsize,
					$BACKUPS_NAME,
					$backupdir,
					$backupdir_base);
//open a file to keep track of the latest file modification timestamp
shell_exec("umount " . escapeshellarg ($localDrive));
$fe = fopen( "/etc/docutron/.backupBot", 'w+' );
//write the timestamp to the file
fwrite( $fe, $latest."\n" );
fwrite( $fe, $vsize );
//close the file
fclose( $fe );
/* backup given file ********************/
function backupFile( $path, $bpath, $dir, $datadir, &$size, $backupdir, $backupdir_base ){
	//split into an arry to create directories as needed
	$path = str_replace( " ", "\ ", $path );
	$arr = explode( "[,]", trim($path) );
	$tmp = implode( "/", $arr );
	$volume = floor($size / VOLUME_SIZE +1);
	$bpath = "$backupdir_base/$volume/$backupdir";
//echo "createPath file\n";
	createPathFile( "$bpath$tmp", $size );
	if(!is_dir("$datadir$tmp") && !copy( "$datadir$tmp", "$bpath$tmp" ))
	{
//		echo "not copying\n";
	}else{
		$size += filesize( "$datadir$tmp" );
echo "size = $size\n";
	}
}
function createPathFile( $path, &$size )
{
//echo "createPathFile path = $path\n";
	$dirArr = explode( "/", $path );
	array_pop( $dirArr );
	echo createPath( $dirArr, $size );
}
function createPath( $opath, &$size )
{
	$dirArr = $opath;
	foreach( $dirArr as $d )
	{
		$path .= "/$d";
		if( !is_dir( $path ) )
		{
			$size+=$path;
			$path = str_replace( " ", "\ ", $path );
			if( !mkdir( $path ) )
			{
				//die( $path );
				echo "$path\n";
				error_log( $path );
			}
		}
	}
}
/* recursively go through the directories and backup  new files */
/* TODO need to create empty directories based on timestamp *****/
function traverseDir($bpath,
					$dir,
					$path,
					$timestamp,
					$latest,
					$datadir,
					&$size,
					$BACKUPS_NAME,
					$backupdir,
					$backupdir_base){
	//open the directory to backup
	$dh = opendir( $dir );
	//pop off the directory pointers to self and out
	readdir( $dh );// .
	readdir( $dh );// ..
	$emptyDir = true;
	//go through each file, and test it for backing up
	//if directory recurse through it, and test each file.
	while($str = readdir($dh) )	{
		$emptyDir = false;
		//skip any strings named dms_backups
		if( $str!=$BACKUPS_NAME) {
			//if directory recurse through it, and check to back up the files.
			if( is_dir( "$dir/$str" ) )	{
				//recurse into directories...by adding $str to the path identifier
				$tmplatest=traverseDir($bpath,
										"$dir/$str",
										"$path,$str",
										$timestamp,
										$latest,
										$datadir,
										$size,
										$BACKUPS_NAME,
										$backupdir,
										$backupdir_base);
				//test for latest timestamp from previous recursion
				if( $tmplatest > $latest ) {$latest = $tmplatest;}
			}else {
				//get time stamp of file
				$tmplatest = filemtime( "$dir/$str" );
				//test for latest timestamp from previous recursion
				if( $tmplatest > $timestamp ) {
					if( $tmplatest > $latest ) {$latest = $tmplatest;}
					//backup file
					//do not write to the backupfiles file
					backupFile( "$path,$str",
								$bpath,
								$dir,
								$datadir,
								$size,
								$backupdir,
								$backupdir_base);
					//fwrite( $fd, "$path $str\n" );
				}
			}
		}
	}
	if( $emptyDir ){
		$tmplatest = filemtime( "$dir" );
		if( $tmplatest > $timestamp )
			backupFile($path,$bpath,$dir,$datadir,$size,$backupdir,$backupdir_base);
	}
	return $latest;
}
?>
