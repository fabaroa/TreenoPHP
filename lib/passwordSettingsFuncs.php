<?php
// $Id$

include_once '../lib/settingsFuncs.php';
include_once '../lib/utility.php';
include_once '../db/db_common.php';

function displayPasswordSettingsArray($dept)
{
	$settings = createPasswordSettingsArray($dept);
	//$keys = array("passwordRestriction", "requireChange", "minLength", "alpha_character", "numeric_character", "special_character", "forcePassword");
	$restrictions = array();
	//if there is no settings enabled or included, return false;
	if(count($settings) <= 1) return;
	
	foreach($settings as $key=>$value)
	{
		switch($key)
		{
			case 'passwordRestriction':
				//if password restrictions are not enabled print that there are none and break the foreach
				if($value != '1') 
				{
					$restrictions[] = "No password restrictions are currently enabled";
					return $restrictions;
				}
				break;
			case 'requireChange':
				//display that users are required to change their passwords at first login
				$restrictions[]= "All users are required to change their password at first login";
				continue;
			case 'minLength':
				//echo the minimum safe password length
				$restrictions[] = "Passwords must be at least $value characters in length";
				continue;
			case 'alpha_character':
				//echo passwords must include at least one letter
				if($value > 0)
					$restrictions[] = "Passwords must include at least one alpha character ( aA-zZ )";
				continue;
			case 'numeric_character':
				//echo passwords must have at least one number
				if($value > 0)
					$restrictions[] = "Passwords must include at least one numeric character ( 0-9 )";
				continue;
			case 'special_character':
				//echo passwords need a special character
				if($value > 0)
					$restrictions[] = "Passwords must include at least one special character ( &,%,_,-,!,@,# )";
				continue;
			case 'forcePassword':
				//echo that passwords must be changed every x days
				if($value > 0)
					$restrictions[] = "Users will be required to change their existing password every $value days";
				continue;							
		}
	}
	return $restrictions;
}

//get the xml string from the settings file functions
function getPasswordSettingsXmlString($dept)
{
	$db_doc = getDbObject('docutron');
	
	$xmlStr = xmlGetPasswordSettingsList($db_doc, $dept);
	
	return $xmlStr;
}

//create an array with the xmlstring that can be easily accessed within the gui
function createPasswordSettingsArray($dept, $xmlStr = NULL)
{
	$xmlDoc = new DOMDocument ();
	//if an xml string has been passed, use that to create the array
	//otherwise, get the array from the settings table.
	$xmlString = $xmlStr != NULL ? $xmlStr : getPasswordSettingsXmlString($dept);
	$xmlDoc->loadXML($xmlString);
	$xmlArray = $xmlDoc->getElementsByTagName('Setting');
	$Settings = array();
	foreach($xmlArray as $setting)
	{
		$Settings[$setting->getAttribute('id')] = $setting->getAttribute('value');
	}
	return $Settings;
}

//update the settings table
function updatePasswordSettings($xmlStr, $dept)
{
	$forcePasswordchg=true;
	//create the array of new settings
	$passwordSettings    = createPasswordSettingsArray($dept, $xmlStr);
	
	$db_doc = getdbObject('docutron');
	//check for old pasword settings
	$oldPasswordSettings = createPasswordSettingsArray($dept);
	$message="";
	$confirmation = false;
	foreach($passwordSettings as $k=>$value)
	{
		// are we turning on change password for all users?
		if(! strcmp($k, "passwordRestriction") ) {
			$restrict = $value;
		}
		if( !strcmp($k, "requireChange") ) {
			$pwChange = $value;
		}
		//if this is an update..
		if(count($oldPasswordSettings) >= 1)
		{
			$message     = "Password Settings Updated Successfully!";
			$updateArray = array('value'=>$value);
			$wArr        = array('k'=>$k,'department'=>$dept);
			if(updateTableInfo($db_doc, 'settings', $updateArray, $wArr))
				$confirmation = true;
			else
			{
				$confirmation = false;
				break;
			}
			if ($k == "forcePassword" && isset($oldPasswordSettings[$k]) &&  $oldPasswordSettings[$k] == $value )
			{
				$forcePasswordchg=false;
			}
		}
		//if there are no password settings, add them as new..
		else
		{
			$message = "Password Settings Inserted Correctly!";
			$tmp = array();
			$tmp['k'] = $k;
			$tmp['value'] = $value;
			$tmp['department'] = $dept;
		
			if($res = $db_doc->extended->autoExecute('settings', $tmp))
				$confirmation = true;
			else
			{
				$confirmation = false;
				break;	
			}
		} 

	}   // end foreach(pw setting)
	if (!$restrict)
	{
		$delete = "delete from user_settings WHERE username='".$user['username']."' AND k='next_password_update' AND department='".$dept."'";
		$res    = $db_doc->query($delete);
		dbErr($res);
	}
	
	if( $restrict && $confirmation ) {
		$users = getTableInfo($db_doc, 'user_settings', array('DISTINCT(username)'),
                              array("department='$dept'"), 'queryAll');
		// loop through users
		foreach( $users as $user ) {
			// force all users to change pw on next login
			if ($pwChange)
			{
				// check for 'change_password_on_login' for this user
				$set = getTableInfo($db_doc, 'user_settings', array(),
					array('username'=>$user['username'], 'department'=>$dept, 'k'=>"change_password_on_login"),
					'queryOne');
				if(count($set) >= 1) {
					// update user change pw
					$update = "UPDATE user_settings SET value='true' WHERE username='".
						$user['username'].
						"' AND k='change_password_on_login' AND department='$dept'";
					$res    = $db_doc->query($update);
					dbErr($res);
				} else {
					// insert user change pw
					$insert['username']   = $user['username'];
					$insert['k']          = "change_password_on_login";
					$insert['value']      = "true";
					$insert['department'] = $dept;
					if($res = $db_doc->extended->autoExecute('user_settings', $insert))
						$confirmation = true;
					else {
						$confirmation = false;
						break;
					}
				}
			}
			if ($forcePasswordchg)
			{
				//check for this user 
				$set = getTableInfo($db_doc, 'user_settings', array(),
					array('username'=>$user['username'], 'department'=>$dept, 'k'=>"next_password_update"),
					'queryOne');
				$date = new DateTime('NOW');
				$interval = 'P'.$passwordSettings["forcePassword"].'D';
				$date->add(new DateInterval($interval));
				$next_update = $date->format('Y-m-d');
				if(count($set) >= 1) {
					// update user change pw
					if (trim($passwordSettings["forcePassword"])==0)
					{
						$delete = "delete from user_settings WHERE username='".$user['username']."' AND k='next_password_update' AND department='".$dept."'";
						$res    = $db_doc->query($delete);
						dbErr($res);
					}
					else
					{
						$update = "UPDATE user_settings SET value='".$next_update."' WHERE username='".
							$user['username'].
							"' AND k='next_password_update' AND department='$dept'";
						$res    = $db_doc->query($update);
						dbErr($res);
					}
				} else if ($passwordSettings["forcePassword"]!="0"){
					// insert user change pw
					$insert['username']   = $user['username'];
					$insert['k']          = "next_password_update";
					$insert['value']      = $next_update;
					$insert['department'] = $dept;
					if($res = $db_doc->extended->autoExecute('user_settings', $insert))
						$confirmation = true;
					else {
						$confirmation = false;
						break;
					}
				}
			}
		}   // end foreach(user)
	}   // if(add pwChange restriction)

	// check that change was completed
	if($confirmation  == false)//if there was any db errors
		$message = "Could not change password settings.  Please try again.";
	
	return $message;
	
}	// end of updatePasswordSettings()

?>
