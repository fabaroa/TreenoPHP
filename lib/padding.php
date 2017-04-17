<?php
include_once '../lib/filename.php';
include_once '../lib/mime.php';

function pad( $working_dir, $user )
{
	$filesArr = array ();
	$dh = opendir ($working_dir);
	$myEntry = readdir ($dh);
	while ($myEntry !== false) {
		if (is_file ($working_dir . '/' . $myEntry)) {
			$filesArr[] = $myEntry;
		}
		$myEntry = readdir ($dh);
	}
	closedir ($dh);

	$max_name_size=0;

	foreach ($fileArr as $filename)
	{
		$filename=getFileName($filename);
		if(is_numeric($filename))
		{

		if(strlen($filename)>$max_name_size)
				$max_name_size=strlen($filename);
		}
	}
	
	foreach ($fileArr as $filename) {
	  $file_no_ext=getFileName($filename);

	  if(is_numeric($file_no_ext))
	  {   
		 $file_len=strlen($file_no_ext);
	  
		 //don't change if file name is the maximum size
		 if($file_len<$max_name_size)
		 {
			$new_filename=$filename;
			for($i=0;$i<$max_name_size-$file_len;$i++)
			{
					$new_filename="0".$new_filename;
				}
		//make sure new file name doesn't already exist, otherwise put a dash and the next number
		if(file_exists(trim($working_dir.$new_filename)))
		{
		$new_filename_no_ext=getFileName($new_filename);
		$ext=getExtension($new_filename);

		$new_filename_no_ext=$new_filename_no_ext."-";
		$j=1;
		while(file_exists(trim($working_dir.$new_filename_no_ext.$j.".".$ext)))
			$j++;
		$new_filename=$new_filename_no_ext.$j.".".$ext;
		}
				if(copy($working_dir.trim($filename),$working_dir.trim($new_filename)))
				unlink ($working_dir.trim($filename));
		 }
		}
   }
}

?>
