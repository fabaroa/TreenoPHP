<?php
// $Id: settings.php 14985 2013-04-24 12:14:59Z cz $

if(file_exists('../db/db_common.php')) {
include_once '../db/db_common.php';
} else {
include_once 'db/db_common.php';
}

class sett {
	var $settings;
	var $department;

	/*
	 * returns a string based upon a key
	 */
	function get($str) {
		if (isset ($this->settings[$str])) {
			//return array if user wants file extensions setting
			if (strcmp($str, "indexing_file_filter") == 0)
				return explode(",", $this->settings[$str]);
			else
				return $this->settings[$str];
		}
		return false;
	}

}

class Gblstt extends sett {

	/*
	 * function GblStt
	 * constructor creates new instance of global settings
	 */
	function GblStt($department, &$db_doc) {
		$db_doc->query("SET TEXTSIZE 1024000");
		$this->department = $department;
		if ($this->department != NULL) {
			$this->settings = array ();
			if (!empty ($_SESSION[$this->department . '-GblStt'])) {
				$sessGbl = $_SESSION[$this->department . '-GblStt'];
				if ((time () - $sessGbl['time']) < 60) {
					$this->settings = $sessGbl['settings'];
				}
			}
			if (!$this->settings) {
				$whereArr = array('department'=>$this->department);
				$results = getTableInfo($db_doc,'settings',array(),$whereArr);
				while ($row = $results->fetchRow()) {
					$this->settings[$row['k']] = $row['value'];
				}
				$sessGbl = array ();
				$sessGbl['time'] = time ();
				$sessGbl['settings'] = $this->settings;
				$_SESSION[$this->department . '-GblStt'] = $sessGbl;
			}
		}
	}

	/*
	 * function set sets options for Settings table
	 */
	function set($key, $value) {
		$db_doc =& getDbObject('docutron');
		global $user;
		if (isset ($this->settings[$key])) {
			$updateArr = array('value' => (string)$value);
			$whereArr = array();
			$whereArr['k'] = $key;
			$whereArr['department'] = $this->department;
			updateTableInfo($db_doc,'settings',$updateArr,$whereArr);	
		} else {
			$insertArr = array (
				'k'		=> $key,
				'value'		=> (string)$value,
				'department'	=> $this->department
			);
			$res = $db_doc->extended->autoExecute('settings',$insertArr);
			dbErr($res);
		}
		if(isset($this->settings[$key])) {
			$oldValue = $this->settings[$key];
		} else {
			$oldValue = '';
		}
		$this->settings[$key] = $value;
		$_SESSION[$this->department . '-GblStt'] = array ();
		if (strcmp($oldValue, $value) != 0) {//If the strings are equal, the settings have not changed
			if ($user) {
				$db_dept = getDbObject($this->department);
				$user->audit("changed global settings", "$key=$value", $db_dept);
			}
		}
//		$db_doc->disconnect ();
	}

	// add a file extension to the settings table
	function addExtension($ext) {
		$key = "indexing_file_filter";
		$extension_array = $this->get($key); //gets the array of file extensions
		if (!in_array($ext, $extension_array)) //add only if the extension is not in an array
			{
			array_push($extension_array, $ext); //place new extension at the end of the array
			$extension_array = array_unique($extension_array); //guard against duplicate entries
			sort($extension_array); //alphabetically sort the array

			$value = implode(",", $extension_array); //turn array into a comma seperated

			$this->set($key, $value); //write to table
		}
	}

	//remove a file extension from the settings table
	function removeExtension($ext) {
		$key = "indexing_file_filter";
		$extension_array = $this->get($key);

		if (in_array($ext, $extension_array)) //remove only if the extension is in the array
			{
			$i = 0;
			while (array_key_exists($i, $extension_array)) {
				if (strcmp($ext, $extension_array[$i]) == 0) //value is the one that was selected to be removed
					$extension_array[$i] = "";
				$i = $i +1;
			}

			$extension_array = ridEmpty($extension_array); //remove the empty field
			if (is_array($extension_array))
				$value = implode(",", $extension_array); //make the array into a comma seperated string
			else
				$value = $extension_array;
			$this->set($key, $value); //write to table
		}
	}

	//function to remove a key from the db
	function removeKey($key) {
		$db_doc =& getDbObject('docutron');
		$whereArr = array('k'=>$key,'department'=>$this->department);
		deleteTableInfo($db_doc,'settings',$whereArr);
		unset ($this->settings[$key]);

		$_SESSION[$this->department . '-GblStt'] = array ();
//		$db_doc->disconnect ();
	}
		
	function get($str) {
		$result = parent::get($str);
		if( $result === false && $str == "pcDocCart") {
			return '1';
		}
		else {
			return $result;
		}	
	}
}

class Usrsettings extends sett {
	var $username;
	var $db_doc;
	/*
	 * function UsrStt
	 * constructor creates new instance of user settings
	 */
	function Usrsettings($u, $department,$db_doc=NULL) {
		$this->department = $department;
		$this->username = $u;
		$this->settings = array ();
		if( $db_doc == NULL ) {
			$this->db_doc = getDbObject('docutron');
		} else {
			$this->db_doc = $db_doc;
		}
		if (!empty ($_SESSION[$this->department . '-' . $this->username . '-Usrsettings'])) {
			$sessUsr = $_SESSION[$this->department . '-' . $this->username . '-Usrsettings'];
			if ((time () - $sessUsr['time']) < 60) {
				$this->settings = $sessUsr['settings'];
			}
		}
		if (!$this->settings) {
			$whereArr = array('department'=>$this->department,'username'=>$u);
			$results = getTableInfo($this->db_doc,'user_settings',array(),$whereArr);
			while ($row = $results->fetchRow()) {
				$this->settings[$row['k']] = $row['value'];
			}
//			$db_doc->disconnect ();
			$sessUsr = array ();
			$sessUsr['time'] = time ();
			$sessUsr['settings'] = $this->settings;
			$_SESSION[$this->department . '-' . $this->username . '-Usrsettings'] = $sessUsr;
		}
	}

	/*
	 * function set sets options for Settings table
	 */
	function set($key, $value) {
		if (array_key_exists($key, $this->settings)) {
			$updateArr = array("k"=>(string)$key, 'value' => (string)$value);
			$whereArr = array();
			$whereArr['k'] = $key;
			$whereArr['department'] = $this->department;
			$whereArr['username'] = $this->username;
			updateTableInfo($this->db_doc,'user_settings',$updateArr,$whereArr);	
		} else {
			$insertArr = array(
				"username"		=> $this->username,
				"k"				=> $key,
				"value"			=> (string)$value,
				"department"	=> $this->department
					  );
			$res =& $this->db_doc->extended->autoExecute('user_settings',$insertArr);
			dbErr($res);
		}
//		$db_doc->disconnect ();

		$this->settings[$key] = $value;
		if(isset($_SESSION[$this->department . '-' . $this->username.'-Usrsettings'])) {
			$_SESSION[$this->department . '-' . $this->username . '-Usrsettings'] = array();
		}
	}
	
	//function to remove a key from the db
	function removeKey($key) {
		$whereArr = array(	'department'=>$this->department,
							'username'=>$this->username);
		if($key != $this->username) {
			$whereArr['k'] = $key;
		}
		deleteTableInfo($this->db_doc,'user_settings',$whereArr);
//		$db_doc->disconnect ();
	}
	function get($str) {
		$result = parent::get($str);
		if( $result === false && $str == "pcDocCart") {
			return '2';
		}
		else {
			return $result;
		}	
	}
}

class groupSettings extends sett {
	var $groupname;
	var $db;
	/*
	 * constructor creates new instance of settings
	 */
	function groupSettings($g, $department) {
		$this->department = $department;
		$this->db =& getDbObject($this->department);
		$this->groupname = $g;
		$this->settings = array ();
		if (!empty ($_SESSION[$this->department . '-' . $this->groupname . '-groupSettings'])) {
			$sessGrp = $_SESSION[$this->department  . '-' . $this->groupname . '-groupSettings'];
			if ((time () - $sessGrp['time']) < 60) {
				$this->settings = $sessGrp['settings'];
			}
		}
		if (!$this->settings) {
			$whereArr = array('department'=>$this->department,'groupname'=>$g);
			$results =& getTableInfo($this->db,'group_settings',array(),$whereArr);
			while ($row = $results->fetchRow()) {
				$this->settings[$row['k']] = $row['value'];
			}
			$sessGrp = array ();
			$sessGrp['time'] = time ();
			$sessGrp['settings'] = $this->settings;
			$_SESSION[$this->department . '-' . $this->groupname . '-groupSettings'] = $sessGrp;
		}
		$this->db->disconnect();
	}

	/*
	 * function set sets options for Settings table
	 */
	function set($key, $value) {
		if (array_key_exists($key, $this->settings)) {
			$updateArr = array("$key"=>(string)$value);
			$whereArr = array();
			$whereArr['k'] = $key;
			$whereArr['department'] = $this->department;
			$whereArr['groupname'] = $this->groupname;
			updateTableInfo($this->db,'group_settings',$updateArr,$whereArr);	
		} else {
			$insertArr = array(
				"groupname"		=> $this->groupname,
				"k"				=> $key,
				"value"			=> (string)$value,
				"department"	=> $this->department
					  );
			$res =& $this->db->extended->autoExecute('group_settings',$insertArr);
			dbErr($res);
		}

		$this->settings[$key] = $value;
		$_SESSION[$this->department . '-' . $this->groupname . '-groupSettings'] = array ();
	}
	//function to remove a key from the db
	function removeKey($key) {
		$whereArr = array(	'k'=>$key,
							'department'=>$this->department,
							'groupname'=>$this->groupname );
		deleteTableInfo($this->db,'group_settings',$whereArr);
	}
}
?>
