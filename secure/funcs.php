<?php

/*
 * recursively change the permissions of a directory structure
 * uses recursion because I was bored.
 */
function chgrpownR( $this_path, $grp, $own, $mod )
{
	
    if (is_dir($this_path)) 
    {
         $handle=opendir('.');
        while (($file = readdir($handle))!==false) 
         {
             if (($file != ".") && ($file != "..")) 
            {
                if (is_dir($file)) 
                {
                    chown( $file, $own ); 
                    chgrp( $file, $grp );
                    chmod( $file, $mod );
				    chdir( $file );
					chgrpownR( $file, $grp, $own, $mod );
                    chdir( ".." );
                }
                else if (is_file($file))
                {
                    chown( $file, $own ); 
                    chgrp( $file, $grp );
                    chmod( $file, $mod );
                }
            }
        }
        closedir($handle);
    }
}

chgrpownR( "/home/bemis/demo/", "apache", "apache", 0700 );

?>
