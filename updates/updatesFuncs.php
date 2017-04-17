<?php

//returns this machines mac address
function getMAC ($ipaddr, $DEFS) {
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$outputStr = shell_exec ($DEFS['IPCONFIG_EXE'] . ' /all');
		$outputArr = explode ("\n", $outputStr);

		$regexp = '/(?:[0-9a-f]{2}-){5}[0-9a-f]{2}/i';
		$matches = array ();

		for ($i = 0; $i < count ($outputArr); $i++) {
			if (strpos ($outputArr[$i], $ipaddr) !== false) {
				preg_match ($regexp, $outputArr[$i - 3], $matches);
				break;
			}
		}
		if( isset( $matches[0] ) )
			$matches[0] = str_replace('-', ':', $matches[0]);
		else
			$matches[0] = '';
	} else {
		$output = shell_exec('/sbin/ifconfig'); //stores the ifconfig output
		$lines = explode("\n", $output);
		$oldLine = $lines[0];
		$regexp = '/(?:[0-9a-f]{2}:){5}[0-9a-f]{2}/i';
		$matches = array();
		foreach($lines as $myLine) {
			if(strpos($myLine, $ipaddr) !== false) {
				preg_match($regexp, $oldLine, $matches);
				break;
			}
			$oldLine = $myLine;
		}
	}
	return $matches[0];
}

//converts 2.2.0 to the appropriate filename
function getVersionFilename ($version) {

	$pieces=explode(".",$version);
	return "html-$pieces[0]_$pieces[1]_$pieces[2].tar.gz";
}

function getVersionFilenameNoExt($version) {

	$pieces=explode(".",$version);
	return "html-$pieces[0]_$pieces[1]_$pieces[2]";
}

//returns filename without .gzip extension
function getUnzippedFilename ($file) {

	$pieces=explode(".",$file);

	return $pieces[0].".".$pieces[1];
}

//returns the filename without any extension
function getNoExtensionFilename ($file) {

	$pieces=explode(".",$file);
	return $pieces[0];
}

//return the md5sum of a file
function getmd5Sum ($file) {

	$sum= $DEFS['MD5SUM_EXE'] . " " . escapeshellarg ($file);
	$md5sum = shell_exec ($sum);
	$arr = explode (' ', $md5sum);
	return $arr[0];
}

//checks for software versions
function checkVersions ($department, $DEFS) {

	//return the mac address
	$mac_address=getMAC($_SERVER['SERVER_ADDR'], $DEFS);

	if($mac_address=="")
		die("Cannot obtain MAC address");

	$mac_address=getMAC($_SERVER['SERVER_ADDR'], $DEFS);	//gets this machine's MAC
	$encoded_mac=base64_encode($mac_address);
	$encoded_dep=base64_encode($department);
	
	//open server-side processing file
	$fp=@fopen("http://demo.docutronsystems.com/licensing/server/checkVersion.php?code=$encoded_mac&department=$encoded_dep","r");
	if ($fp) {
		$first_line=trim(fgets($fp));
		if(strcmp($first_line,"")==0) {
			echo"<script>window.location='checkForUpdates.php?message=Error Receiving Version Information'</script>";
			die();
		}
		if(strcmp($first_line,"no_register")==0) {
			echo"<script>window.location='checkForUpdates.php?message=Please Contact Docutron to Register For Updates'</script>";
			die();
		}
		if(strcmp($first_line, "customer_expired")==0) {
			echo "<script>window.location='checkForUpdates.php?message=Customer license expired'</script>";
			die();
		}
		
		if(strcmp($first_line, "no_upgrade")==0) {
			echo "<script>window.location='checkForUpdates.php?message=No later version currently exists'</script>";
			die();
		}

		if(strcmp($first_line,"not_compiled")==0) {

			echo "<script>window.location='checkForUpdates.php?message=Contact Docutron for the available update'</script>";
			die();
		}
		
		$second_line=trim(fgets($fp));
		$third_line=trim(fgets($fp));

		$key=base64_encode($third_line);

		echo "<script>window.location='confirm_update.php?from=$first_line&to=$second_line&key=$key'</script>";
	/*
		echo "<script>if (confirm('Do you wish to upgrade from $first_line to $second_line (All users will be logged out) ?'))
			startProcessing('$first_line','$second_line','$key');else
			window.location='checkForUpdates.php?message=$updateAborted';</script>";	
	*/
	} else {
		//echo"<script>window.location='checkForUpdates.php?message=Error Receiving Version Information'</script>";
		echo"<script>window.location='checkForUpdates.php?message=Successfully Received Version Information'</script>";
		die();
	}
}

//compares two versions -- return -1 if a<b, 0 if a=b, 1 if a>b
function compareVersions ($a,$b) {

	//split into arrays
	$version_a=explode(".",$a);
	$version_b=explode(".",$b);

	//if not a valid version argument, convert with 0's
	if(sizeof($version_a)<3) {
		$size=sizeof($version_a);

		for($i=$size;$i<3;$i++)
			$version_a[$i]=0;
	}

	if(sizeof($version_b)<3) {
		$size=sizeof($version_b);
		for($i=$size;$i<3;$i++)
			$version_b[$i]=0;
	}

	if(intval($version_a[0])<intval($version_b[0]))
		return -1;
	else if(intval($version_a[0])>intval($version_b[0]))
		return 1;
	else {	//equal
		if(intval($version_a[1])<intval($version_b[1]))
			return -1;
		else if(intval($version_a[1])>intval($version_b[1]))
			return 1;
		else {	//equal
			if(intval($version[2])<intval($version_b[2]))
				return -1;
			else if (intval($version[2])>intval($version_b[2]))
				return 1;
			else
				return 0;
		}
	}
}

//return a timestamp
function getTime()
{
	return date('Y')."-".date('m')."-".date('d')." ".date('G').":".date('i').":".date('s');
}

//function to updateLicenses
function updateLicenses ($DEFS) {
	$db_doc = getDbObject('docutron');

	$mac_address=getMAC($_SERVER['SERVER_ADDR'], $DEFS);	//gets this machine's MAC
		
	if( file_exists( "checkForUpdates.php" ) )
		$loc = "../updates/checkForUpdates.php?message";
	else
		$loc = "addDepartment.php?message";
		
	$encoded_mac=base64_encode($mac_address);
	//open server-side processing file
	$fp=@fopen("http://demo.docutronsystems.com/licensing/server/sendLicenses.php?code=$encoded_mac","r");
	if ($fp) {
		$remote_mac=trim(fgets($fp));
		if($remote_mac=="") {
				echo "<script>window.location='$loc=Error connecting to the updates server'</script>";
			die();
		}
		$remote_mac_decoded=base64_decode($remote_mac);

		if($mac_address!=$remote_mac_decoded) {
			echo "<script>window.location='$loc=Server does not match customer server on record'</script>";
			die();
		}
	/*
		$encoded_licenses=trim(fgets($fp));
		if(strcmp($encoded_licenses,"already_updated")==0) {
			echo "<script>window.location='$loc=This server has already updated licenses'</script>";
			die();
		}
		else if(strcmp($encoded_licenses,"customer_expired")==0) {
			echo "<script>window.location='$loc=Customer license expired'</script>";
			die();
		}
	*/
		$i=0;
		while (!feof($fp)) {
			$depLicenses = trim(fgets($fp));
			$dep = base64_decode($depLicenses);
			
			$depLicenses = trim(fgets($fp));
			$new_licenses=base64_decode($depLicenses);
				if( $new_licenses == "customer_expired" )
					$message = "$dep licenses expired<br>";
				elseif( $new_licenses == "already_updated")
					$message = "$dep has already updated licenses<br>";
				else
				{
					$updateArr = array('max'=>$new_licenses);
					$whereArr = array('real_department'=> $dep);
					updateTableInfo($db_doc,'licenses',$updateArr,$whereArr);
					$message = "$dep successfully updated to $new_licenses licenses<br>";
				}
			$i++;		
		}
	} else {
		//Fail Silently
		//echo "<script>window.location='$loc=Error connecting to the updates server'</script>";
		echo "<script>window.location='$loc=Successfully connected to the updates server'</script>";
		die();
	}
	//do database update
	if( file_exists("checkForUpdates.php") )
		echo "<script>window.location='$loc=$message'</script>";
}
function getNumUsersLoggedIn($dep) {
	$db_doc = getDbObject('docutron');
	return getTableInfo($db_doc,'user_security',array('COUNT(uid)'),array('department'=>$dep),'queryOne');
}
	
