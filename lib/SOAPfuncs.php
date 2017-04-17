<?php
	// This function takes a "filename" and grabs the department, cabinetName,
	// fileid, and filename.
	function getfilenameinfo($fullfile, &$department, &$cabinet, &$fileid, &$filename)
	{
		// Get the information off the filename
		$fullinfo = explode("]", $fullfile) ;
		$fileinfo = explode("-", $fullinfo[0]) ;
		$department = str_replace("[", "client_files", $fileinfo[0]) ;
		$cabinet = $fileinfo[1] ;
		$fileid = $fileinfo[2] ;
		$filename = substr($fullfile, strpos($fullfile, ']') + 1) ;
		// skip over the version info to get filename
		//$filename = substr($fulltmp[1], strpos($fulltmp[1], " ")+1) ;
return "$department || $cabinet || $fileid || $filename" ;
	}

	// Very simple function to make a user object that can be used in
	// many other library calls that need a user object for which we
	// dont have
	function makeTempUser($username, $db_name)
	{
		$user = new user() ;
		$user->username = $username ;
		$user->db_name = $db_name ;
		return $user ;	
	}

	// -- XML Functions ----
	// This function returns XML for an error statement with a error number
	function XMLError($string, $number = null)
	{
		return "<ret><pass>false</pass><value><message>$string</message></value></ret>" ;
	}

	// This function rerturns XML for an successful call with the given string
	function XMLPass($string)
	{
		return "<ret><pass>true</pass><value>$string</value></ret>" ;
	}

	// Test for group access to cabinets from webservices
    function maskWebPermissions($db_obj, $username, $accessArr) {
        $groupAccessList = queryAllGroupAccess($db_obj,$username);
        foreach( $groupAccessList AS $groupInfo ) {
            $cabinet = $groupInfo['real_name'];
            $rights = $groupInfo['access'];
            if($rights == 'rw' || ($rights == 'ro' && $accessArr[$cabinet] != 'rw') ) {
                $accessArr[$cabinet] = $rights;
            }
        }
        return $accessArr;
    }
?>
