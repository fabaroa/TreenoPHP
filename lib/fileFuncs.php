<?php
function getCaller()
{
    $trace = debug_backtrace();
    $previousCall = $trace[1]; // 0 is this call, 1 is call in previous function, 2 is caller of that function?
	return "File: ".$previousCall["file"].", Line: ".$previousCall["line"];
}

function delDir($dir)
{
	//error_log("delDir(): ".$dir);
	//error_log("delDir() caller: ".getCaller());

	if(file_exists($dir) && is_dir($dir)){
		if($handle = opendir($dir)){
			clearstatcache ();
			while (false!==($folderOrFile = readdir($handle))) {
				if($folderOrFile != "." && $folderOrFile != "..") { 
					if(is_dir("$dir/$folderOrFile")) { 
						delDir("$dir/$folderOrFile");
					} else { 
						unlink("$dir/$folderOrFile");
					}
				} 
			}
			closedir($handle);
			clearstatcache ();
			if(!@rmdir($dir)) {
				$dirArr = array ();
				$noExistArr = array ();
				$dh = opendir ($dir);
				while (($myEntry = readdir ($dh)) !== false) {
					if ($myEntry != '.' and $myEntry != '..') {
						clearstatcache ();
						if (file_exists ($dir . '/' . $myEntry)) {
							$dirArr[] = $myEntry;
						} else {
							$noExistArr[] = $myEntry;
						}
					}
				}
				closedir ($dh);
				$weird = false;
				if (count ($dirArr)) {
					$weird = true;
					error_log ('ERROR: Files still exist in directory : ' . $dir .
							' : ' . implode (', ', $dirArr));
				}

				if (count ($noExistArr)) {
					$weird = true;
					error_log ('ERROR: readdir sees these but they do not exist : ' . $dir .
							' : ' . implode (', ', $noExistArr));
				}
				if (!$weird) {
					error_log ('ERROR: Directory could not be deleted : ' . $dir);
				}
				return false;
			} elseif (file_exists ($dir)) {
				error_log ('rmdir returned true, but directory exists: ' . $dir);
			} else {
		//		error_log ('deleted directory: ' . $dir . ' successfully');
			}
			return true;
		}
		else
		{
			error_log("delDir() opendir failed to open: ".$dir);
			error_log("delDir() caller: ".getCaller());
			return true;
		}
	}
	else
	{
		error_log("delDir() this dir does not exist: ".$dir);
		error_log("delDir() caller: ".getCaller());
		return true;
	}
}

function isDirClosed( $path )
{	
	$size1 = duDir($path);
	sleep(3);
	$size2 = duDir($path);
	if( $size1 == $size2 )
	{
		return true;
	}
	return false;
}

function copyDir($dir, $destDir, $force = false)
{
	if(!file_exists($destDir)) {
		if(!@mkdir($destDir)) {
			die("failed to create directory $destDir!\n");
		}
	}
	$handle = opendir($dir);
	while (false!==($folderOrFile = readdir($handle))) {
		if($folderOrFile != "." && $folderOrFile != "..") { 
			if(is_dir("$dir/$folderOrFile")) { 
				copyDir("$dir/$folderOrFile", "$destDir/$folderOrFile");
			} else {
				if($force and file_exists ($destDir.'/'.$folderOrFile)) {
					unlink ($destDir.'/'.$folderOrFile);
				}
				if(!@copy("$dir/$folderOrFile", "$destDir/$folderOrFile")) {
					die("failed file copy!\n");
				}
			}
		} 
	}
	closedir($handle);
	return true;
}

function duDir($location) {
	$total = 0;
	$all = opendir($location);
	while ($file = readdir($all)) {
		if (is_dir($location.'/'.$file) and $file <> ".." and $file <> ".") {
			$total += duDir($location.'/'.$file);
			unset($file);
		} else if (!is_dir($location.'/'.$file)) {
			$total += filesize($location.'/'.$file);
			unset($file);
		}
	}
	closedir($all);
	unset($all);
	return $total;
}

function chmodDir($dir, $mode) {
	$handle = opendir($dir);
	if(!@chmod($dir, $mode)) {
		error_log("failed to chmod $dir\n");
	}
	while (false!==($folderOrFile = readdir($handle))) {
		if($folderOrFile != "." && $folderOrFile != "..") { 
			if(is_dir("$dir/$folderOrFile")) {
				chmodDir("$dir/$folderOrFile", $mode);
			} else {
				if(!@chmod("$dir/$folderOrFile", $mode)) {
					error_log("failed to chmod $dir/$folderOrFile!\n");
				}
			}
		} 
	}
	closedir($handle);
}

function chownDir($dir, $user, $group)
{
	$handle = opendir($dir);
	chown($dir, $user);
	chgrp($dir, $group);
	while (false!==($folderOrFile = readdir($handle))) {
		if($folderOrFile != "." && $folderOrFile != "..") { 
			if(is_dir("$dir/$folderOrFile")) {
				chownDir("$dir/$folderOrFile", $user, $group);
			} else {
				chown("$dir/$folderOrFile", $user);
				chgrp("$dir/$folderOrFile", $group);
			}
		} 
	}
	closedir($handle);
}

function makeAllDir($dirName) {
	if (file_exists ($dirName)) {
		error_log ('Directory Exists: ' . $dirName);
		return false;
	}
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$parts = explode('/', $dirName);
		$dir = $parts[0];
		
		for($i = 1; $i < count($parts); $i++) {
			$dir .= '/'.$parts[$i];
			if(!file_exists($dir)) {
//				error_log ($dir);
				mkdir($dir);
			}
		}
	} else {
		if (substr (PHP_OS, 0, 3) == 'WIN') {
			$dirName = str_replace ('/', '\\', $dirName);
		}
//		error_log ($dirName);
		mkdir ($dirName, 0777, true);
	}
	return true;
}

function canThumbnail($ext) {
	if(!$ext) {
		return false;
	}
	$myExt = strtolower($ext);
	if($myExt == 'tif' or $myExt == 'tiff' or $myExt == 'jpg' or $myExt == 'jpeg' or $myExt == 'pdf') {
		return true;
	} else {
		return false;
	}
}

function safeCheckDir ($dir) {
	global $DEFS;
	if (!file_exists ($dir)) {
		if (is_writable (dirname ($dir))) {
			if (!mkdir ($dir)) {
				$errStr = "Error: Could not  create directory: $dir. " .
					"The parent directory is writable. Is the volume full?";

				error_log ($errStr);
				die ($errStr."\n");
			} else {
				allowWebWrite ($dir, $DEFS);
			}
		} else {
			$errStr = "Error: ".dirname ($dir)." is not writable. ".
				"Directory $dir could not be created.";

			error_log ($errStr);
			die ($errStr."\n");
		}
	} elseif (is_file ($dir)) {
		$errStr = "Error: $dir could not be created because it is a ".
			"file. Please move it out of the way.";

		error_log ($errStr);
		die ($errStr."\n");
	} elseif (!is_writable ($dir)) {
		$errStr = "Error: $dir is not writable.";
		error_log ($errStr);
		die($errStr."\n");
	}
}
function listDir( $dir ){
	clearstatcache();
	$dh = safeOpenDir( $dir );
	$farr = array();
	while( $str = readdir( $dh )) {
		if( $str != '.' and $str != '..' )
			$farr[] = $str;
	}
	closedir($dh);
	usort( $farr, 'strnatcasecmp' );
	return $farr;
}
/* function safeOpenDir ($dir) {
	$dh = opendir ($dir);
	if(!$dh) {
		$errStr = "Cannot open directory: " . $dir;
		error_log ($errStr);
		die ($errStr."\n");
	}
	return $dh;
} */
function safeOpenDir ($dir) {
	if(is_dir($dir)){
	$dh = opendir ($dir);	
	}else{
		mkdir($dir);
		if(is_dir($dir)){
			$dh = opendir ($dir);	
		}else{
			$errStr = "Cannot open directory: " . $dir;
			error_log($errStr);
			die ($errStr."\n");
		}
	}
	
	
	return $dh;
}

function allowWebWrite ($path, $DEFS, $mode = 0775) {
	global $DEFS;
	if (file_exists ($path)) {
		if (substr (PHP_OS, 0, 3) == 'WIN') {
			if(isset($DEFS['XCACLS_EXE'])) {
				$cmd = $DEFS['XCACLS_EXE'] . ' ' . escapeshellarg ($path) 
					. " /E /G " . "{$DEFS['WWW_USER']}:F /T /Y";
			} else {
				$cmd = 'echo y| ' . $DEFS['CACLS_EXE'] . ' ' . 
					escapeshellarg ($path) . " /E /G " . 
					"{$DEFS['WWW_USER']}:F /T";
			}
			//error_log("execute shell command: ".$cmd);
			shell_exec ($cmd);
			return true;
		} else {
			if (is_dir($path)) {
				chownDir ($path, $DEFS['WWW_USER'], $DEFS['WWW_GROUP']);
				chmodDir ($path, $mode);
			} else {
				chown ($path, $DEFS['WWW_USER']);
				chgrp ($path, $DEFS['WWW_GROUP']);
				chmod ($path, $mode);
			}
			return true;
		}
	} 
	return false;
}

?>
