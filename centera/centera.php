<?php
#TODO
#Have the centput add the centera file to the unlinker
#NOT WINDOWS-SAFE
#
function centdel( $ca ){
	$str = `/usr/local/bin/dctdel $ca`;
	return $str;
}

function centput( $fullpath, $ipaddress, $user, $cab ){
	//return `/usr/local/bin/dctput $ipaddress $fullpath`;
	$ca =  `/usr/local/bin/dctput $ipaddress $fullpath`;
	if( centerr( $ca ) ){
		$user->audit( "Centera Error: $ca", "Centera Error" );
	}else{
		centeraunlink( $fullpath, $ca, $user->db_name, $cab );
		$user->audit( "Centera Put $fullpath, $ca", "Centera Put" );
	}
	return $ca;	
}

function centget( $ipaddress, $ca, $size, $path, $user, $cab='' ){
	$output = '';
	if( centerr( $ca ) ){
		$user->audit( "$path is not in Centera", "file not in Centera" );
		return "File is not in Centera";
	}
	$cmd = "/usr/local/bin/dctget $ipaddress $ca";
	$cadeletefile = '/tmp/docutron/centeraunlinker/'.$ca;
	if($size and $path and file_exists($cadeletefile) and file_exists($path)){
		clearstatcache();
		$fstat = stat($path);
		if( $fstat['size']==$size ){
			touch( $cadeletefile );
			$user->audit( "$path was already there", "Centera cache: $size");
		}else{//wait till the file is there
			//no growth for ten seconds get the file
			$nogrowth = false;
			$ngcount = 0;
			for($i=0;$i<60 and !$nogrowth;$i++){
				sleep(1);
				$oldgrowth = $fstat['size'];
				$fstat = stat($path);
				if( $fstat['size']==$size ){
					touch( $cadeletefile );
					return true;
				}
				if( $oldgrowth==$fstat['size'] ){
					$ngcount++;
				}
				if( $ngcount==10 ){
					$nogrowth=true;
				}
				clearstatcache();
			}
			if( $nogrowth and $i==59 ){
				centeraunlink( $ca, $path, $user->db_name, $cab );
				//$fd = fopen( $cadeletefile, 'w+' );
				//fwrite( $fd, $path );
				//fclose( $fd );
				$ca = `$cmd`;
				checkpaths($path,$ca,$size);
				if( centerr( $ca ) ){
					$user->audit( "Centera Get Retry Error: $ca", "Centera Error" );
				}else{
					centeraunlink( $path,$ca, $user->db_name, $cab );
					$user->audit( "Centera get $path, $ca", "Centera Get" );
				}
				return $ca;	
			}
		}
	}else{//get the file
		centeraunlink( $path,$ca, $user->db_name, $cab );
		$output = `$cmd`;
		checkpaths($path,$output,$size);
		if( centerr( $output ) )
			$user->audit( "Centera Error: $output", "Centera Error" );
	}
	return $output;
}

function multiCentGet ($ipAddr, $centInfos, $user, $cab=''){
	foreach($centInfos as $centInfo) {
		centget($ipAddr, $centInfo['hash'], $centInfo['size'], $centInfo['name'], $user, $cab);
	}
}

/* centerr 
 * checks for the content address to be 63 or 53 characters long and not ERROR is in the message
 */
function centerr( $ca ){
	//checks to see if has is empty
	if(!trim($ca)) {
		return true;
	}

	$pos = strpos( $ca, "ERROR" );
	$calen = strlen( $ca );
	if( ( $calen == 53 or $calen == 63 ) and $pos===false ){
		return false;
	}
	return true;
}

function centeraunlink( $path, $ca, $db_name='', $cab='' ){
	$fd = fopen( '/tmp/docutron/centeraunlinker/'.$ca, 'w+' );
	$dbInfo = array( 'db_name'=>$db_name, 'cab'=>$cab, 'called_from'=>$_SERVER['SCRIPT_FILENAME'] );
	$dbserial = serialize( $dbInfo );
	fwrite( $fd, $path." ".$dbserial );
	fclose( $fd ); 
}
function checkpaths($path,$result,$fsize){
	//error_log("checking path:".$path.":".$result.":".$fsize);
	$result = str_replace("The C-Clip has been stored into ", "", $result); 
	$result=trim($result);
	if (strcasecmp($result,$path)){
		$cmd="mv ".$result." ".$path;
		$fstat = stat($result);
		if( $fstat['size']==$fsize ){
			$mess=`$cmd`;
			$fp = fopen( '/tmp/docutron/getlog.log', 'a' );
			fwrite( $fp, "\nCMD:".$cmd."\n" );
			fclose( $fp ); 
			//write log file
		}
	} else {
	}
}
?>
