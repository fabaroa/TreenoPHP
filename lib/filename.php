<?php
function getFileName( $filename )
{
	$a = explode( ".", $filename );
	return $a[0];
}

/*
 *	Function takes a path and returns the number of files
 *		in directory and subdirectories.
 *	Function does not count the pointers (".", "..") to the above
 *		directories as files.
 *	Returns false if directory does not exist
 */
function countFiles($path)
{
	$dh = opendir($path);
	$i = 0;
	while($file = readdir($dh)) {
		if(is_file($path.'/'.$file)) { 
			if($file != "INDEX.DAT") {
				$i++;
			}
		}
		if(is_dir($path.'/'.$file) and $file != '.' and $file != '..') {
			$subDh = opendir($path.'/'.$file);
			while($subFile = readdir($subDh)) {
				if(is_file($path.'/'.$file.'/'.$subFile)) {
					if($subFile != "INDEX.DAT") {
						$i++;
					}
				}
			}
			closedir($subDh);
		}
	}
	closedir($dh);
	return $i;
}

function getFilesFromIndexingFolder($path)
{
	$dh = opendir($path);
	$filesArray = array();
	while($file = readdir($dh)) {
		if(is_file($path.'/'.$file)) {
			if($file != "INDEX.DAT") {
				$filesArray[] = $path.'/'.$file;
			}
		}
		if(is_dir($path.'/'.$file) and $file != '.' and $file != '..') {
			$subDh = opendir($path.'/'.$file);
			while($subFile = readdir($subDh)) {
				if(is_file($path.'/'.$file.'/'.$subFile)) {
					if($subFile != "INDEX.DAT") {
						$filesArray[] = $path.'/'.$file.'/'.$subFile;
					}
				}
			}
			closedir($subDh);
		}
	}
	closedir($dh);
	usort($filesArray, 'strnatcasecmp');
	return $filesArray;
}
?>
