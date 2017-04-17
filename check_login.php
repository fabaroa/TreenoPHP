<?php
// $Id: check_login.php 15034 2013-09-18 20:08:44Z fabaroa $

require_once 'classuser.inc';
require_once 'lib/security.php';
require_once 'lib/filter.php';
require_once 'modules/modules.php';
require_once 'settings/settings.php';
require_once 'languageFuncs.php';
require_once 'DataObjects/DataObject.inc.php';

//set_error_handler('docutronErrorHandler');

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$logged_in = 0;

if(!session_id()) {
	session_start();
}

require_once 'lib/settings.php';
if(isset($_SESSION['user'])) {
	if(!isset($_SESSION['st_time'])) {
		$_SESSION['st_time'] = time();
	} else {
		if((time() - $_SESSION['st_time']) < ini_get('session.gc_maxlifetime')) {
			$_SESSION['st_time'] = time();
		} else {
			$user =& $_SESSION['user'];
			$user->audit('logged out','Logged out due to inactivity.  Last user activity: '.date('Y-m-d G:i:s',$_SESSION['st_time']));

			session_destroy();

			$logged_in = 0;

			return;
		}
	}

	$user =& $_SESSION['user'];

	$db_doc =& $user->getDbDocObject ();
	if(!checkUser($user->hash_id, $db_doc)) {
		return;
	}

	$uArr = array('ptime' => time());
	$wArr = array('username' => $user->username);
	updateTableInfo($db_doc,'user_polls',$uArr,$wArr);
	
	$logged_in = 1;
	$trans = $_SESSION['lang'];
	$db_object =& $user->getDbObject ();

	$content_frame_pages = array(
		'/energie/home.php',
		'/secure/admin.php',
		'/modules/userInfo.php',
		'/secure/inbox1.php',
		'/energie/topLevelSearch.php',
		'/energie/searchResults.php',
		'/energie/file_search_results.php',
		'/workflow/viewWFTodo.php'
	);
	
	$left_sidebar_pages = array(
		'/secure/leftAdmin.php',
		'/secure/inboxSelect1.php'
	);
	
	
	// Check for the user being forced to change their password
	$user_settings = new Usrsettings($user->username, $user->db_name);

	$firstLogin  = getTableInfo($db_doc, 'users', array('last_login'), 
                                array("username='".$user->username."'"), 'queryOne');

/***** We don't need this anymore... all password checking at login happens on TreenoV4 side.
	if( ( $user_settings->get('change_password_on_login')
          || !strcmp($firstLogin, "Never") )
        && !$user->isSuperUser() )
	{
		// If it's a content frame page, redirect to the change password page
		if (in_array($_SERVER['PHP_SELF'], $content_frame_pages))
		{
			header('Location: /secure/changePassword.php?message=Please change your password');
			exit();
		}
		// If it's a left sidebar page, redirect to the search panel
		elseif (in_array($_SERVER['PHP_SELF'], $left_sidebar_pages))
		{
			header('Location: /energie/searchPanel.php');
			exit();
		}
	}
*/
	//find out when the next password update is due for this user
	$nextPasswordDate = $user_settings->get('next_password_update');

	if (getdbType() == 'mssql') {
		// mssql
		$sArr = array('min(cast(value as varchar(30)))');
	} else {
		// mysql & pgsql
		$sArr = array('min(value)');
	}
	$forcePassword = getTableInfo($db_doc, 'settings', $sArr, 
								  array("k='forcePassword' AND department='".$user->db_name."'"), 
								  'queryOne');
	//if there is a password update date on record and the user is not admin.
	if ( !empty($forcePassword) && $forcePassword > 0 && !$user->isSuperUser() )
	{
		$isValidPassword;
		//password update is not empty and is a valid format
		if(!empty($nextPasswordDate) && stristr($nextPasswordDate, '-')) {
			//if the next password date is less than or equal to today, make them change the pwd
			$isValidPassword = $db_doc->queryOne("SELECT IF(CURDATE() < '$nextPasswordDate', 1, 0) AS isValid");
		}
		else{ 
			$isValidPassword = 0;
		}
		
		// If it's a content frame page and the password must be changed, redirect to the change password page
		if($isValidPassword === 0)
		{
			if (in_array($_SERVER['PHP_SELF'], $content_frame_pages))
			{
				$message = 'Users passwords must be changed every '.$forcePassword.' days';
				error_log($message);
				header("Location: /secure/changePassword.php?message=$message");
				exit();
			}
			// If it's a left sidebar page, redirect to the search panel
			elseif (in_array($_SERVER['PHP_SELF'], $left_sidebar_pages))
			{
				header('Location: /energie/searchPanel.php');
				exit();
			}
		}
	}
//	*/	
} else {
	$db_doc =& getDbObject ('docutron');
}

function setSessionUser($user, $disconnect = true) {
	if($disconnect and $user) {
		$user->disconnectDBs();
	}
	if($user) {
		$_SESSION['user'] = $user;
		unset($_SESSION['user']->dbDept);
		unset($_SESSION['user']->dbDeptName);
		unset($_SESSION['user']->dbDoc);
	} else {
		unset($_SESSION['user']);
	}
}

function logUserOut() {
	echo "<html>\n";
	echo " <body bgcolor=\"#FFFFFF\">\n";
	echo "  <script>\n";
	echo "   document.onload = top.window.location = '../logout.php';\n";
	echo "  </script>\n";
	echo " </body>\n";
	echo "</html>\n";
}

function GetHostUrl() 
{
	$hostUrl = 'http';
 	if (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) {$hostUrl .= "s";}
 	$hostUrl .= "://";

 	if ($_SERVER["SERVER_PORT"] != "80")
	{
		$hostUrl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
	}
	else
	{
	  $hostUrl .= $_SERVER["SERVER_NAME"];
	}
	
	return $hostUrl;
}

?>
