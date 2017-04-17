<?php
chdir (dirname(__FILE__));
//bot to backup cabinet
include_once '../lib/filename.php';
include_once '../CDBackup/XMLCabinetFuncs.php';
include_once '../CDBackup/cdXML.inc';
include_once '../CDBackup/writeXML.inc';
include_once '../lib/settings.php';
include_once '../lib/fileFuncs.php';
include_once '../settings/settings.php';
include_once '../db/db_common.php';
//include_once '../db/db_engine.php';
include_once '../lib/utility.php';
print_r( $DEFS );
/*
 * cdbackup needs to be passed the following variables 

 *  * $db_name = name of the database to connect to
 *  * $DepID = list of cabinets {cab1}{cab2}{cab3}
 *  * $userDir = directory that the user is working on (enables 
 *	* concurrent backups)
 *  * $tempTable: null means take whole cab, otherwise the temp table name is 
 *	* passed
 *  * $is_files : null means that the ids in the temp table are folders, true 
 *	* means that they are file ids
 */

/* This file takes in requests to make ISO files, that are file systems to place
on a CD.  The file is passed the ID numbers of cabinets to be placed on this CD.
However, search results of folder IDs, and search results of file ids, can be 
passed to this bot to export to an ISO.  The bot determines its interpretation
of what type of result is being input based on its arguments (if a temp table
name is passed, and if the is_files argument is set).
*/

/*NOTE: The database calls that modify the 'cd_backup' entry in the
user_settings table are to communicate with the polling and display pages.  The
polling page controls what the status is on the user's screen, and will redirect
this location to a confirmation page or error page depending upon the outcome of
running this bot.  look at ../CDBackup/poll.php to see if any reprecutions will
occur from modifying this file.  If the bot is dying, try running the executed
command from the shell in order to view any error messages that may be reported.
*/

//cab from submitIndex.php
$DepID = $argv[2];
$DepID = substr($DepID, 1, strlen($DepID) - 2);
$cabinets = explode("}{", $DepID);
//db name from user object
$db_name = $argv[1];
$username = $argv[3];
if(isset($argv[4])) {
	$tempTable = $argv[4];
	$is_files = $argv[5];
} else {
	$tempTable = "";
	$is_files = "";
}
$dataDir = $DEFS['DATA_DIR'].'/';
$docDir = $DEFS['DOC_DIR'];
//get database object
//directory that holds the user's personal directory
$userDir = $DEFS['TMP_DIR'] . '/docutron/' . $argv[3] . '/cd_backup/';
if (file_exists ($userDir)) {
	error_log ('deleting directory 3');
	delDir ($userDir);
	error_log ('done deleting directory 3');
}
mkdir ($userDir);
$db_object = getDbObject($db_name);
$pid = getmypid();	//gets the process's PID
$usrSett = new Usrsettings($username, $db_name);
$usrSett->set('cd_backup', 'memory');
$db_doc=getDbObject( 'docutron' );
//now get the space allowed
$settings = new GblStt($db_name, $db_doc);
$hd = $settings->get("drive");
if ($hd) {
	$available = disk_free_space($hd);
	$usedSpace = disk_total_space($hd) - $available;
} else {
	$available = 1000000000000;
	$usedSpace = 0;

}
//if there is not double the space available from the space taken, this
//operation may take up too much storage. If this is the case, we must get
//Cabinet sizes and verify
if($usedSpace > $available) {
	$needed = 0;
	foreach($cabinets AS $cabname) { 
		$whereArr = array('departmentid'=>(int)$cabname);
		$realname = getTableInfo($db_doc,'departments',array('real_name'),$whereArr,'queryOne');
		$needed += duDir($dataDir."$db_name/".$realname);
	}
	//if not enough space, cleanup and die
	if ($available < $needed) {
		error_log ('deleting directory 4');
		delDir($userDir);
		error_log ('done deleting directory 4');
		$usrSett->set('cd_backup', 'out_of_mem');
		die();
	}
}
$usrSett->set('cd_backup', 'memory_done');

//create XML file of Cabinet -- root node is the Department, followed by 
//Cabinets, followed by folders and Indexes, then tabs and files
$usrSett->set('cd_backup', 'tree');
$tmpXML = $userDir."cabinet_tmp.xml";
//create XML tree of cabinet contents
error_log ('before');
$fileArr = cabinetXMLTree($db_object, $cabinets, $tempTable, $is_files, $tmpXML);
error_log ('after');
$fxml = fopen($tmpXML, 'r');
if(file_exists($userDir.'backup')) {
	error_log ('deleting directory 1');
	delDir($userDir.'backup');
	error_log ('done deleting directory 1');
}
mkdir($userDir.'backup');
$cdXML = new cdXML($userDir.'backup/', $dataDir, $fileArr, $DEFS);
$cdXML->parse($fxml);

$usrSett->set('cd_backup', 'tree_done');

$usrSett->set('cd_backup', 'files');

//analyze and tag for multi-disk backups
//get the setting for backup
$media = $settings->get('CDBackup');

if(!$media)
	$media = "CDR 700MB";

//set maximum allowable space for a cabinet
if(strcmp($media, "DVD+RW 4.7GB") == 0) {
	//1.8GB limit on DVD size (just to be safe)
	$cdSpace = 1.8 * 1024 * 1024 * 1024;
} else {
	//600MB limit on CD size (just to be safe)
	$cdSpace = 500 * 1024 * 1024;
}
//get size of "view_files/" in bytes
$viewFilesSize = duDir($docDir."/CDBackup/view_files");
//reserved size is view_files and cabinet.xml and autorun resources
$reservedSize = $viewFilesSize + filesize($userDir."cabinet_tmp.xml");
$reservedSize += filesize($docDir."/CDBackup/autodis.html");
$reservedSize += filesize($docDir."/CDBackup/autorun.exe");
$reservedSize += filesize($docDir."/CDBackup/autorun.inf");

//save room for the view_files and xml structure
$spaceRemaining = $cdSpace - $reservedSize;

//initialize disk number
$discNumber = 1;
mkdir($userDir."disk_$discNumber");

//go through each folder location from the XML tree and place files in it
$discInfo = array();

$cdDir = $userDir."disk_$discNumber/";
$backupDir = $userDir.'backup';
$dh = opendir($userDir.'backup/');
$locations = array();
while($dirEntry = readdir($dh)) {
	if(is_dir($backupDir.'/'.$dirEntry) && $dirEntry !=='.' && $dirEntry !== '..') {
		$dh2 = opendir($backupDir.'/'.$dirEntry);
		while($dirEntry2 = readdir($dh2)) {
			if(is_dir($backupDir.'/'.$dirEntry.'/'.$dirEntry2) && $dirEntry2 !== '.'
					&& $dirEntry2 !== '..') {
				$dh3 = opendir($backupDir.'/'.$dirEntry.'/'.$dirEntry2);
				while($dirEntry3 = readdir($dh3)) {
					if(is_dir($backupDir.'/'.$dirEntry.'/'.$dirEntry2."/$dirEntry3")
							&& $dirEntry3 !== '.' && $dirEntry3 !== '..') {
						$locStr = $dirEntry.'/'.$dirEntry2.'/'.$dirEntry3;
						$locations[] = $locStr; 
					}
				}
				closedir($dh3);
			}
		}
		closedir($dh2);
	}
}
closedir($dh);
for($i = 0; $i < sizeof($locations); $i++) {
	$folderSize = duSymDir($backupDir.'/'.$locations[$i]);
	$splitArr = explode('/', $locations[$i]);
	$cabDir = $splitArr[0].'/'.$splitArr[1];
	if($folderSize >= $spaceRemaining) {
		$spaceRemaining = changeCD($cdSpace,
					$reservedSize, 
					$docDir,
					$cdDir,
					$discNumber,
					$DEFS);
	}
	if (!file_exists ($cdDir.$cabDir)) {
		makeAllDir($cdDir.$cabDir);
	}
	$res = rename($backupDir.'/'.$locations[$i], $cdDir.$locations[$i]);
	$discInfo[$locations[$i]] = $discNumber;
	$spaceRemaining -= $folderSize;
}
//final disk hasn't been finished
if($spaceRemaining < ($cdSpace - $reservedSize))
	writeNoChange($docDir, $cdDir, $DEFS);
error_log ('deleting directory 2:' . $backupDir);
delDir($backupDir);
error_log ('done deleting directory 2');
$writeXML = new writeXML($fxml, $discInfo, $discNumber);
for($i = 1; $i <= $discNumber; $i++) {
	$discFD = fopen($userDir."disk_$i/cabinet.xml", 'w');
	$writeXML->writeDiscXML($i, $discFD);
	fclose($discFD);
}
fclose($fxml);

unlink($userDir.'cabinet_tmp.xml');
error_log('before iso');
makeTreeAndISO($discNumber, $docDir, $userDir, $DEFS);
error_log('after iso');
cleanUp($userDir,$discNumber, $DEFS);

$usrSett->set('cd_backup', 'files_done_disks='.$discNumber);
$db_object->disconnect();
$db_doc->disconnect ();

//change CD, prepare for next CD storage map
function changeCD($cdSpace, $reservedSize, $docDir, &$cdDir, &$discNumber, $DEFS)
{
	global $DEFS;
	global $trans;
	$disk = $trans['Disk'];

	//creates the view_files folder for file browsing
	copySymDir($docDir."/CDBackup/view_files", $cdDir, $DEFS);

	//increment disk number
   	$discNumber++;
	//make new disk directory
	$newDir = $cdDir."/../disk_$discNumber/";
	mkdir($newDir);
	$cdDir = realpath($newDir).'/';
	$spaceAvailable = $cdSpace - $reservedSize;

	return $spaceAvailable;
}

//writes information ready for burning
function writeNoChange($docDir, $cdDir, $DEFS) 
{
	global $DEFS;
	copySymDir($docDir."/CDBackup/view_files", $cdDir, $DEFS);
}	

//add a modified version of the XML tree containing disk number and create
//the ISO in the disk_# directory
function makeTreeAndISO($maxDiscNumber, $docDir, $userDir, $DEFS) {
	global $DEFS;
	//loop for each disk that was created
	for($i = 1; $i <= $maxDiscNumber; $i++) {
		$discDir = $userDir."disk_$i/";
		//auto-run instructions
		copy($docDir."/CDBackup/autorun.inf", $discDir.'autorun.inf');
		//first page displayed
		copy($docDir."/CDBackup/autodis.html", $discDir.'autodis.html');
		//executable that loads an html page
		copy($docDir."/CDBackup/autorun.exe", $discDir.'autorun.exe');
		//makes the ISO file
		if (substr (PHP_OS, 0, 3) == 'WIN') {
			$redirect = '2> NUL';
		} else {
			$redirect = '2> /tmp/cdbackup.dbg';//'2> /dev/null';
		}
		$cmd = $DEFS['MKISO_EXE'] . " -f -J -joliet-long -o " .
			escapeshellarg($userDir . "disk_$i.iso") . 
			" -r -V v1 ". escapeshellarg($discDir) . ' ' . $redirect;
//if the iso is not working in windows make sure the tasks user is administrator.			
//		$cmd = $DEFS['ZIP_EXE'] . ' -r disk_'.$i.'iso.zip disk_'.$i.' '.$redirect;
			error_log('before shell'.$cmd);
			$tmpDir=getcwd();
			chdir($userDir);
			$stuff = shell_exec($cmd);
			error_log('after shell '.$stuff);
			chdir($tmpDir);
	}
}

//function that removes all files besides the ISO
function cleanUp($userDir, $disks, $DEFS) {
	global $DEFS;
	global $username;
	global $db_name;
	$fileArr = array ();
	for($i = 1; $i <= $disks; $i++) {
		delSymDir($userDir."disk_$i", $DEFS);
		$fileArr[] = $userDir . 'disk_'.$i.'.iso';
//		$fileArr[] = $userDir . 'disk_'.$i.'iso.zip';
	}
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$redirect = '2> NUL';
	} else {
		$redirect = '2> /tmp/cdbackup.dbg';//'2> /dev/null';
	}
	//zip all of the files together
	$dateprefix = date("YmdHis");
	foreach( $fileArr as $cdiso ){
		$cdiso1 = $cdiso;
		$cdiso = basename( $cdiso );
		rename( $cdiso1, $DEFS['DATA_DIR'].'/'.$db_name.'/personalInbox/'.$username.'/'.$dateprefix.$cdiso );
	    allowWebWrite( $DEFS['DATA_DIR'].'/'.$db_name.'/personalInbox/'.$username.'/'.$dateprefix.$cdiso, $DEFS);
	}
}


function duSymDir($location) {
	global $DEFS;
	$total = 0;
	$all = opendir($location);
	while ($file = readdir($all)) {
		if (is_dir($location.'/'.$file) and $file <> ".." and $file <> ".") {
			$tempDH = @opendir ("$location/$file");
			if (!$tempDH) {
				if(substr(PHP_OS, 0, 3) == 'WIN') {
					$total += filesize($location.'/'.$file);
					unset($file);
				} else {
					$total += filesize(readlink($location.'/'.$file));
					unset($file);
				}
			} else {
				closedir ($tempDH);
				$total += duSymDir($location.'/'.$file);
				unset($file);
			}
		} else if (is_file($location.'/'.$file)) {
			$total += filesize($location.'/'.$file);
			unset($file);
		}
	}
	closedir($all);
	unset($all);
	return $total;
}

function copySymDir($srcdir, $destdir, &$DEFS)
{
	global $DEFS;
	$lastdir = substr($srcdir, strrpos($srcdir, '/'));
	$destdir .= $lastdir;
	if(!file_exists($destdir)) mkdir($destdir);
	$handle = opendir($srcdir);
	while (false !== ($folderOrFile = readdir($handle))) {
		if($folderOrFile != "." && $folderOrFile != ".." && $folderOrFile != '.svn') { 
			if(is_dir("$srcdir/$folderOrFile")) {
				$tempDH = @opendir ("$srcdir/$folderOrFile");
				if (!$tempDH) {
					if(!copy("$srcdir/$folderOrFile", "$destdir/$folderOrFile"))
						die ();
				} else {
					closedir ($tempDH);
					copySymDir("$srcdir/$folderOrFile", $destdir, $DEFS);
				}
			} else {
				if(!copy("$srcdir/$folderOrFile", "$destdir/$folderOrFile"))
					die();
			}
		} 
	}
	closedir($handle);
}

function delSymDir($dir, $DEFS)
{
	global $DEFS;
	$handle = opendir($dir);
	while (false!==($folderOrFile = readdir($handle))) {
		if($folderOrFile != "." && $folderOrFile != "..") { 
			if(is_dir("$dir/$folderOrFile")) { 
				$tempDH = @opendir ("$dir/$folderOrFile");
				if (!$tempDH) {
					unlink("$dir/$folderOrFile");
				} else {
					closedir ($tempDH);
					delSymDir("$dir/$folderOrFile", $DEFS);
				}
			} else { 
				unlink("$dir/$folderOrFile");
			}
		} 
	}
	closedir($handle);
	return rmdir($dir);
}
?>
