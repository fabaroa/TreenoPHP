<?php
//$Id: classuser.inc 14988 2013-04-30 15:23:33Z cz $

//include 'db/db_engine.php';
include_once 'lib/settings.php';
include_once 'settings/settings.php';
include_once 'lib/utility.php';
include_once 'DataObjects/DataObject.inc.php';
include_once 'groups/groups.php';
include_once 'lib/settingsList.inc.php';
include_once 'lib/fileFuncs.php';
include_once 'lib/random.php';
include_once 'lib/licenseFuncs.php';

if( in_array( "user", get_declared_classes() ) ) {
	//do nothing
} else {
/*
 * User defines access rigths based on priv table
 */
class user {
	// User name and password
	var $username;
	var $password;
	var $email;
	// Current department
	var $db_name;
	// Permissions
	var $access;
	//cabinet associative array
	var $cabArr;

	var $userSettings;
	
	// Flags if they are admins
	var $admin;
	var $depadmin;
	//list of groups the user belongs to
	var $groups;

	// Misc
	var $drive;
	var $hash_id;
	var $time_dif;
	var $language;
	var $cab;
	var $doc_id;
	var $file_id;
	var $userTempDir;
	var $todoID;
	var $restore;

	var $dbDept;
	var $dbDeptName;
	var $dbDoc;

	var $setSecurityTime;

	/* This function fills the user object with pertinant information from
		the database being: db_name, access, admin, depadmin, and drive */
	function fillUser($wf=NULL,$department=NULL) {
		$this->setSecurityTime = 0;
		// Set up db_name and admin flags
		if (!is_null($department)) {
			$department = strtolower ($department);
		}
		if( $wf ) {
			$this->admin = false ;
			$this->depadmin = false ;
			$this->setDepInfo ($department);
			$this->db_name = $department;
			$db_object =& $this->getDbObject();
	
			$this->setSecurity();
			$arbCab = getTableInfo($db_object,'departments',array('departmentname'),array('real_name'=>$_GET['cab']),'queryOne');
			$this->cabArr[$_GET['cab']] = $arbCab;
			if( $this->access[$_GET['cab']] != 'rw' ) {
				$this->restore = 1;
			}
			$this->access[$_GET['cab']] = 'rw';
			$this->cab = $_GET['cab'];
			$this->doc_id = $_GET['doc_id'];
			$user->file_id = 0;
		} else {
			$this->cab = '';
			$this->doc_id = '';
			$this->restore = 0;
			$this->setDepInfo($department);
			$this->setSecurity();
			$user->file_id = 0;
		}
		// Set up security and permissions (access) and harddrive
	}
	
	function createUserTempDir() {
		global $DEFS;
		$this->userTempDir = $DEFS['TMP_DIR'].'/docutron/'.$this->username;
		if(file_exists($this->userTempDir)) {
			if(is_file($this->userTempDir)) {
				unlink($this->userTempDir);
			} else {
				delDir($this->userTempDir);
			}
		}
		makeAllDir($this->userTempDir);
		allowWebWrite($this->userTempDir, $DEFS);
	}

	/*
 	 * return a dbObject as root!
	 */
	function &getDbObject() {
		global $DEFS;
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$this->dbDept =& getDbObject($this->db_name);
			$this->dbDeptName = $this->db_name;
		} else {
			if (!isset($this->dbDept) or !is_object($this->dbDept) or $this->dbDeptName != $this->db_name) {
				$this->dbDept =& getDbObject($this->db_name);
				$this->dbDeptName = $this->db_name;
			}
		}

		return $this->dbDept;
	}

	function &getDbDocObject() {
		if (substr (PHP_VERSION, 0, 1) == '4') {
			$this->dbDoc =& getDbObject('docutron');
		} else {
			if(!isset($this->dbDoc) or !is_object($this->dbDoc)) {
				$this->dbDoc =& getDbObject('docutron');
			}
		}
		return $this->dbDoc;
	}
	
	function setSettings() {
		$db_doc =& $this->getDbDocObject ();
		$db_dept =& $this->getDbObject ();
		$userSettings = new userSettings($db_doc, 
			$this->username, $this->groups, $this->db_name, $db_dept, $this->cabArr);
		$this->userSettings =& $userSettings->getUserSettings();
	}
	
	function checkSetting($setting, $cabinet) {
		if(!isset($this->userSettings[$cabinet])) {
			return false;
		}
		
		if(! strcmp($setting, "documentView")) {
			return TRUE;
		}
		
		if(in_array($setting, $this->userSettings[$cabinet])) {
			return true;
		}
		
		return false;
	}
	
	/*
	 * adds the needed jscripts for our action dropdowns
	 */
	function addCabinetJscript($formName)
	{
echo<<<ENERGIE
<script type="text/javascript">
function getIndex() {
	location=document.$formName.DepartmentID[document.$formName.DepartmentID.selectedIndex].value;
}
function getIndex2() {
	location=document.$formName.cab[document.$formName.cab.selectedIndex].value;
}
</script>
ENERGIE;
	}

	//function returns true if no cabinets exist
	function noCabinets() {
		$this->setSecurity();
		$noCab = true;
		foreach($this->access as $cab => $priv) {
			if($priv !== 'none' and isset($this->cabArr[$cab])) {
				$noCab = false;
				break;
			}
		}
		return $noCab;
	}

	//function returns true if user has only ro cabinets 
	function roCabinets() {
		if( is_array($this->access) && is_array($this->cabArr)) {
			foreach(array_keys($this->cabArr) AS $k) {
				if($this->access[$k] == 'rw') {
					return false;
				}
			}
		}
		return true;
	}	

	/*
	 * return RW access writes
	 */
    function getCurrentSelection( $DepID, $user, $optionValue ) {
	$db_object =& $this->getDbObject();
	$dispName = getTableInfo($db_object,'departments',array('real_name'),array('departmentid'=>(int)$DepID),'queryOne');
	$arbName = $this->cabArr[$dispName];
	if(strchr($optionValue,"?")) {
	  	echo "    <option selected=\"selected\" value=\"$optionValue&DepID=$DepID\">$arbName</option>\n";
        } else {
          	echo "    <option selected=\"selected\" value=\"$optionValue?DepID=$DepID\">$arbName</option>\n";
	}
      return( $dispName );
    }

    function getRWorRO( $access, $permission ) {
	$condition = 0;
 	if($permission == 1) {
 		if($access == "rw" || $access == "ro")
 		  	$condition = 1;
      	} else {
        	if($access == "rw")
        	  	$condition = 1;
      	}
      	return( $condition );
    }

    function getDropDown( $optionValue, $user, $permission ) {
        global $trans;        
        $selectOpt = $trans['Choose Cabinet'];          
	if (isset($_GET['DepID'])) {
	       	$DepID = $_GET['DepID'];//check to see if DepID is in the URL
	} else {
		$DepID = '';
	}
        $db_object =& $user->getDbObject();
		
		echo "   <select id=\"cabSel\" name=\"DepartmentID\" onchange=\"getIndex()\">\n";
		if($DepID) { //if DepID add current selection to the Select option
  	 	 	$name = $this->getCurrentSelection( $DepID, $user, $optionValue);
		} else {
			$name = '';
	  		echo "    <option selected=\"selected\" value=\"$optionValue\">$selectOpt</option>\n";
		}


		foreach($this->cabArr as $real => $arb ) {
			$condition = $this->getRWorRO( $this->access[$real], $permission );
			if($condition) {
				if (isset ($this->cabArr[$name]) && $arb == $this->cabArr[$name] ) {
					continue;
				}
				$cabID = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $real), 'queryOne');
				if(strchr($optionValue,"?") )
					echo " <option value=\"$optionValue&amp;DepID=$cabID\">{$arb}</option>\n";
				else	
					echo " <option value=\"$optionValue?DepID=$cabID\">{$arb}</option>\n";
			}
		}
		echo "</select>";
    }	

	/*Same function as getDropDown, but returns the cab name instead of DepID*/
	function getCab( $optionValue, $user, $permission ) {
        global $trans;        
        $selectOpt = $trans['Choose Cabinet'];
		echo "   <select id=\"cabSel\" name=\"cab\" onchange=\"getIndex2()\">\n";
	if (isset ($_GET['cab'])) {
		$cab = $_GET['cab'];//check to see if DepID is in the URL
		$name = $this->cabArr[$cab];
	} else {
		$cab = '';
		$name = '';
	}
		if($cab) {//if DepID add current selection to the Select option
			if(strchr($optionValue,"?"))
				echo" <option selected=\"selected\" value=\"$optionValue&amp;cab=$cab\">$name</option>\n";
			else
				echo" <option selected=\"selected\" value=\"$optionValue?cab=$cab\">$name</option>\n";	
		}   
		else
	  		echo "    <option selected=\"selected\" value=\"$optionValue\">$selectOpt</option>\n";

		foreach($this->cabArr as $real => $arb ) {
	        $condition = $this->getRWorRO( $this->access[$real], $permission );
			if(($condition) && $arb != $name && $arb ) {
				if(strchr($optionValue,"?"))
					echo" <option value=\"$optionValue&cab=$real\">{$arb}</option>\n";
				else
					echo" <option value=\"$optionValue?cab=$real\">{$arb}</option>\n";
			}
		}
		echo "</select>";
    }

	/* invalidNames checks the user names for cabinets and indices against a list of 
	 * words that can cause a DB Error.
	 * Pre: need $word is a char/string
	 * Post: Returns true if $word matches one of those on the invalid list
	 */
	function invalidNames($word) {
		$badwords = array("add", "all", "alter", "analyze", "and", "as", "asc", "asensitive", "auto_increment", "bdb", "before", "berkeleydb", "between", "bigint", "binary", "blob", "both", "btree", "by", "call", "cascade", "case", "change", "char", "character", "check", "collate", "column", "columns", "connection", "constraint", "create", "cross", "current_date", "current_time", "current_timestamp", "cursor", "database", "databases", "day_hour", "day_minute", "day_second", "dec", "decimal", "declare", "default", "delayed", "delete", "desc", "describe", "distinct", "distinctrow", "div", "doc_id", "double", "drop", "else", "elseif", "enclosed", "errors", "escaped", "exists", "explain", "false", "fields", "float", "for", "force", "foreign", "from", "fulltext", "grant", "group", "hash", "having", "high_priority", "hour_minute", "hour_second", "if", "ignore", "in", "index", "infile", "inner", "innodb", "inout", "insensitive", "insert", "int", "integer", "interval", "into", "is", "iterate", "join", "key", "keys", "kill", "leading", "leave", "left", "like", "limit", "lines", "load", "localtime", "localtimestamp", "location", "lock", "long", "longblob", "longtext", "loop", "low_priority", "master_server_id", "match", "mediumblob", "mediumint", "mediumtext", "middleint", "minute_second", "mod", "mrg_myisam", "natural", "not", "null", "numeric", "on", "optimize", "option", "optionally", "or", "order", "out", "outer", "outfile", "precision", "primary", "privileges", "procedure", "purge", "read", "real", "references", "regexp", "rename", "repeat", "replace", "require", "restrict", "return", "returns", "revoke", "right", "rlike", "rtree", "select", "sensitive", "separator", "set", "show", "smallint", "some", "soname", "spatial", "specific", "sql_big_result", "sql_calc_found_rows", "sql_small_result", "ssl", "starting", "straight_join", "striped", "table", "tables", "terminated", "then", "tinyblob", "tinyint", "tinytext", "to", "trailing", "true", "types", "union", "unique", "unlock", "unsigned", "until", "update", "usage", "use", "user", "user_resources", "using", "values", "varbinary", "varchar", "varcharacter", "varying", "warnings", "when", "where", "while", "with", "write", "xor", "year_month", "zerofill");
		if (in_array($word, $badwords))  {
			return true;
		} else {
       		return false;
   		}
	}

	/* invalidJscriptNames checks the tab name against a list of 
	 * words that can cause a javascript error.
	 * Pre: need $word is a char/string
	 * Post: Returns true if $word matches one of those on the invalid list
	 */
	function invalidJscriptNames($word) {
		$badwords = array("abstract", "boolean", "break", "byte", "case", "catch", "char", "class", "const", "continue", "debugger", "default", "delete", "do", "double", "else", "enum", "export", "extends", "false", "final", "finally", "float", "for", "function", "goto", "if", "implements", "import", "in", "Infinity", "instanceof", "int", "interface", "long", "NaN", "native", "new", "null", "package", "private", "protected", "public", "return", "short", "static", "super", "switch", "synchronized", "this", "throw", "throws", "transient", "true", "try", "typeof", "undefined", "var", "void", "volatile", "while", "with");
		if (in_array($word, $badwords)) {
			return true;
		} else {
       		return false;
   		}
	}

	/* invalidDocTypeIndexNames checks the tab name against a list of
 	* words that can cause a error.
 	* Pre: need $word is a char/string
 	* Post: Returns true if $word matches one of those on the invalid list
 	*/
	function invalidDocTypeIndexNames($word) {
		$badwords = array("doc_id", "file_id", "docTypeID", "document_type", "internal_name");
		if (in_array($word, $badwords)) {
			return true;
		} else {
			return false;
		}
	}

	/*
	 * getAccess returns list of access rights
	 */
	function getAccess() {
		return $this->access;
	}
	

	function isAdmin(){
		// OR together if the user has admin flag or is super user
		return ($this->admin || $this->isSuperUser()) ;
	}	

	/*
	 * return if user is administrator
	 */
	function isUserAdmin($username, $database = null){
		// Check right away if admin
		if($this->isSuperUserCheck($username))
			return true ;

		// If they dont send database, get the one in user object
		if(!$database)
			$database = $this->db_name ;

		if( $username=="" )
			$username=$this->username;

		$db_object =& $this->getDbDocObject(); 
		$DO_user = DataObject::factory('users', $db_object);

		if(!$this->checkUser($username)) {
			return 1;
		} else {
			$DO_user->get('username', $username);
			return $DO_user->departments[$database] == 'C' or $DO_user->departments[$database] == 'D';
		}
	}

    //function that returns true if the user is the top-notch "admin"	
    function isSuperUser($username = null) {
		if($username == null)
			$username = $this->username ;

		if($username == "admin" || substr(strtolower($username),0,strlen("TreenoSupport")) == "treenosupport") 
			return true ;
		else
			return false ;
    }

	function isSuperUserCheck($username) {
		if($username == "admin" || substr(strtolower($username),0,strlen("TreenoSupport")) == "treenosupport") 
			return true ;
		else
			return false ;
	}		

	// Return true if username is admin of given department
	function isUserDepAdmin($username, $department = null) {
		// Check right away if the user is admin
		if($this->isSuperUserCheck($username))
			return true ;

		// if dont supply department, use the users calling it
		if($department == null)
			$department = $this->db_name ;

		$db_object =& $this->getDbDocObject();
		$DO_user = DataObject::factory('users', $db_object);
		if(!$this->checkUser($username)) {
			return 1;
		} else {
			$DO_user->get('username', $username);
			return $DO_user->departments[$department] == 'D';
		}
	}

	function checkUser($username) {
		$sArr = array('COUNT(username)');
		$wArr = array('username' => $username);
		return getTableInfo($this->getDbDocObject(),'users',$sArr,$wArr,'queryOne');
	}
	
	// Return true if user is admin of given department
	function isDepAdmin() {
		// OR together if the user has depadmin flag or is super user
		return ($this->depadmin || $this->isSuperUser()) ;
	}

	// This function returns true if you are higher on the hierarchy of
	// user types
	function greaterThanUser($otherUser) {
		if( strcmp($this->username,$otherUser) == 0 ) {
			return true;
		}
	
		// If you are admin, then always true
		else if($this->isSuperUser()){
			return true ;
		}

		// If you are depadmin, then cant edit peers or admin
		else if($this->isDepAdmin() && !$this->isUserDepAdmin($otherUser)){
			return true ;
		}
		
		// If you are depadmin, then you can see yourself 		
		else if( $this->isDepAdmin() && strcmp( $this->username, $otherUser ) == 0 ) {
			return true;
		}

		// if you are a cabadmin, and the person is a loser user
		else if($this->isAdmin() && !$this->isUserAdmin($otherUser)){
			return true ;
		}

		// Otherwise return false
		return false ;
	}

	// reference fucntion for using usort 
	function getUserSortInfo( &$userArr, &$userIDs, $infoStr ) {
		$db_object =& $this->getDbObject();
		$users = getTableInfo($db_object,'access', array (), array (), 'query',
				array ('username' => 'ASC'));
		while($usernames = $users->fetchRow()) {
			if($this->greaterThanUser($usernames['username'])) {
			$userArr[] = $usernames['username'];
			$userIDs[$usernames['username']] = $usernames[$infoStr];
		}
	}
	}

	/*
	 * set password 
	 */	
	function setPassword( $pass ){
		$this->password = $pass;
	}
	
	/*
 	 * get username
	 */
	function getUsername() {
		return $this->username;
	}

	function setDBname( $DB ) {
		$this->db_name = $DB;
	}

	/*
	 * checkSecurity checks if user is allowed in directory( deparment check )
	 */
	function checkSecurity( $dirname ) {
		/* 
		 * return the level of security
		 * 0 - returns false
		 * 1 - returns read-only
		 * 2 - returns read-write
		 */
		$this->setSecurity();
		$retVal = 0;
		//Someone trying to hack the URL, or cabinet is deleted.
		if (!isset ($this->access[$dirname])) {
			return 0;
		}
		switch( $this->access[$dirname]) {
				case 'rw':
					$retVal = 2;
					break;
				case 'ro':
					$retVal = 1;
					break;
				default:
					$retVal = 0;
					break;
			}
			return $retVal;
		}

	/*
	 * Function returns '1' if user has rw access to at least one cabinet.
	 * Returns '0' if user has only read only access.
	 */
	function moreThanRO() {
		if( is_array($this->access) && is_array($this->cabArr)) {
			$this->setSecurity();
			foreach(array_keys ($this->cabArr) AS $k) {
				if($this->access[$k] == 'rw') {
					return true;
				}
			}
		}
		return false;
	}

	/*
	 * getTime() returns the appropriate datetime 
	 * for SQL standard type datetime
	 */
	function getrandstring ($length = 14) {
		return getrandstring ($length);
	}
/*
  	function getrandstring($length = 14) {
		$pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $pool .= "abcdefghijklmnopqrstuvwxyz";
		$sid = "";
        for($index = 0; $index < $length; $index++) {
			$sid .= substr($pool,(rand(0,60)%(strlen($pool))), 1);
		}
         return($sid);
    }
*/
	function getUniqueDirectory($path) {
		return getUniqueDirectory ($path);
    }

    //THIS FUNCTION APPEARS TO BE USELESS.
	function tableCreationDate($hminus = 0, $mplus = 0) {
			// These first two conditions are not known in their reasoning
			// whoever wrote them, please tell us why
          if(date('G')==23 && $hminus > 0) {
			$date = date("Y-m-d G:i:s") ;
          } else if(date('G')==23) {
			$date = date("Y-m-d 0:i:s") ;
          } else {
			// we want to move ahead an hour each time
            $hour = date('G') - $hminus + 1;
			if(strlen($hour) < 2) {
				$hour = str_pad($hour,2,"0",STR_PAD_LEFT);
			}
			$min = date('i') + $mplus ;
			if(strlen($min) < 2) {
				$min = str_pad($min,2,"0",STR_PAD_LEFT);
			}
			$date = date("Y-m-d $hour:$min:s") ;
          }
          return( $date );
	}

	function getTime() {
		return date("Y-m-d G:i:s") ;
	}
    function getNoteTime() {
		return date("g:ia M-d-Y") ;
	}

	/*
	 * audit inserts to the database.table audit access information
	 * actions are
	 */
	function audit( $action, $info, $db_obj=NULL) {
		$i = 0;
		if(!$db_obj )
			$db_obj =& $this->getDbObject();
		if(PEAR::isError($db_obj)) {
			die($db_obj->getMessage());
		}
		$insertArr = array(	"username"	=> $this->username,
							"datetime"	=> $this->getTime(),
							"info"		=> $info,
							"action"	=> $action );
		$res = $db_obj->extended->autoExecute('audit',$insertArr);
		dbErr($res);
	}
	
	// This function sets the user to be a cabinet admin.
	// The first variable is true or false, add or subtract the priviledge
	// respectively. The second (optional) variable is the department to be
	// edited for, if none is given, the default from the user object is used.
	// The third (also optional) variable is the username this will happen for,
	// if none sent, the user from the user object is used.
	function setAdmin($adminToggle, $department = null, $username = null) {
		$db_object =& $this->getDbDocObject ();
		
		// Get the info from user object if not passed in
		if($department === null)
			$department = $this->db_name;
		
		if($username === null)
			$username = $this->username;

		// Get current information
		$DO_user = DataObject::factory('users', $db_object);
		$DO_user->get('username', $username);

		if ($DO_user->departments[$department] != 'D') {
			// If toggle is true, add admin ability, it not, remove it
			if($adminToggle) {
				$DO_user->changeDepartmentAccess($department, 'C');
			} else { // Remove admin rights
				// Update db info
				$DO_user->changeDepartmentAccess($department, 'N');
			}
		}
	}
	// This function sets the user to be a department admin.
	// The first variable is true or false, add or subtract the priviledge
	// respectively. The second (optional) variable is the department to be
	// edited for, if none is given, the default from the user object is used.
	// The third (also optional) variable is the username this will happen for,
	// if none sent, the user from the user object is used.
	function setDepAdmin($adminToggle, $department = null, $username = null) {
		$db_object =& $this->getDbDocObject ();
		
		// Get the info from user object if not passed in
		if($department === null)
			$department = $this->db_name ;
		
		if($username === null)
			$username = $this->username ;

		// Get current information
		$DO_user = DataObject::factory('users', $db_object);
		$DO_user->get('username', $username);

		if($this->dbDeptName == $department) {
			$db_obj = $this->dbDept;
			$doDisconnect = false;
		} else {
			$db_obj =& getDbObject ($department);
			$doDisconnect = true;
		}
		$res = getTableInfo($db_obj,'access',array(),array('username'=>$username));
		$accessInfo = $res->fetchRow();
		$accessRights = unserialize(base64_decode($accessInfo['access']));
		$newRights = array();
		if(is_array($accessRights)) {
			foreach($accessRights as $cabinet => $rights) {
				$rights =  str_replace('ro', 'rw', $rights);
				$newRights[$cabinet] = str_replace('none', 'rw', $rights);
			}
		}
		$accessRights = $newRights;

		// If toggle is true, add admin ability, it not, remove it
		if($adminToggle) {
			// Update user object
			$this->admin = true;
			$this->depadmin = true;

			// Update db info
			$DO_user->changeDepartmentAccess($department, 'D');
				
		} else { // Remove admin rights
			// Update user object
			$this->admin = false;
			$this->depadmin = false;

			// Update db info
			$DO_user->changeDepartmentAccess($department, 'N');
		}

		// Insert new db info
		$updateArr = array('access'=>base64_encode(serialize($accessRights)));
		$whereArr = array('username'=>$username);
		updateTableInfo($db_obj,'access',$updateArr,$whereArr);
		if($doDisconnect) {
			$db_obj->disconnect ();
		}
	}

	// Sets up the default cabinet and the admin rights to it
	function setDepInfo( $currentDB=NULL ) {
		// Connect to docutron
		$db_object =& $this->getDbDocObject ();
		$DO_user = DataObject::factory('users', $db_object);
		$DO_user->get('username', $this->username);

		// Fill in what we need
        if( $currentDB == NULL ) {
			$this->db_name = $DO_user->defaultDept;
		} else {
			$this->db_name = $currentDB;
		}
		
		switch($DO_user->departments[$this->db_name]) {
			case 'D':
				// they are a department admin
				$this->admin = true;
				$this->depadmin = true;
				break;
			case 'C':
				// they are a cab level admin
				$this->admin = true;
				$this->depadmin = false;
				break;
			default:
				$this->admin = false;
				$this->depadmin = false;
				break;
		}
	}

	function maskPermissions() {
		$this->groups = array();
		$db_obj =& $this->getDbObject();
		$groupAccessList = queryAllGroupAccess($db_obj,$this->username);
		foreach( $groupAccessList AS $groupInfo ) {
			$cabinet = $groupInfo['real_name'];
			$rights = $groupInfo['access'];
			$this->groups[] = $groupInfo['real_groupname'];
			if($rights == 'rw' || ($rights == 'ro' && $this->access[$cabinet] != 'rw') ) {
				if (isset ($this->cabArr[$cabinet])) {
					$this->access[$cabinet] = $rights;
				}
			}
		}
		$this->groups = array_unique($this->groups);
	}

	function setSecurity($force = false) {
		if( $this->restore == 1 ) {
		} else {
			if ($force or (time () - $this->setSecurityTime) > 60) {
				$db_obj =& $this->getDbObject();
				
				// if you need to reset permission for admin, uncomment this block of code. login as admin, and then goto "Administration".
				// Comment-out this block after done.
				/* 
						if($this->username == "admin")
						{
							$adminAccess = array();
							$allCabinets = getTableInfo($db_obj, 'departments',
							array(), array('deleted' => 0), 'query', array('departmentname'=>'ASC'));
							
							while($myCab = $allCabinets->fetchRow()) {
								$adminAccess[$myCab['departmentname']] = 'rw';
							}
							error_log("adminAccess: ".print_r($adminAccess, true));
					
							$encAcess = base64_encode (serialize($adminAccess));
							error_log("encAcess: ".$encAcess);
							
							$updateArr = array('access'=>$encAcess);
							$whereArr = array('username'=>"admin");
							updateTableInfo($db_obj,'access',$updateArr,$whereArr);
						}
				*/
				
				$this->cabArr = array ();
				$allCabinets = getTableInfo($db_obj, 'departments',
					array(), array('deleted' => 0), 'query', array('departmentname'=>'ASC'));
				while($myCab = $allCabinets->fetchRow()) {
					$this->cabArr[$myCab['real_name']] = $myCab['departmentname'];
				}
				uasort($this->cabArr,'strnatcasecmp');
				$rightsInfo = getTableInfo($db_obj,'access',array(),array('username'=>$this->username));
				$access = $rightsInfo->fetchRow();
				$accessRights = $access['access'];
				$tmpAccess = unserialize(base64_decode($accessRights));
				$this->access = array();
				if (is_array ($tmpAccess)) {
					//Make sure only cabinets that are not
					//deleted are in the access array.
					foreach ($tmpAccess as $myCab => $myRights) {
						if (isset ($this->cabArr[$myCab])) {
							$this->access[$myCab] = $myRights;
						}
					}
				}
			
				$this->maskPermissions();
				if( array_key_exists("", $this->access ) || in_array( "", $this->access ) ) {
					$newAccess = array();
						foreach( $this->access as $key => $rights ) {
							if( $key != "" && $rights != "" )
								$newAccess[$key] = $rights;
					}
					$this->access = $newAccess;
					$updateArr = array('access'=>base64_encode(serialize($this->access)));
					$whereArr = array('username'=>$this->username);
					updateTableInfo($db_obj,'access',$updateArr,$whereArr);
				}
				$this->setSettings();

				$groupAccess = getTableInfo($db_obj,'group_tab',array(),array(),'queryAll');	
				$groups = new groups($db_obj);
				$noAccess = array();
				foreach($groupAccess as $myRule) {
					$inGrp = $groups->getMembers($myRule['authorized_group']);
					if( !in_array($this->username, $inGrp)) {
						$noAccess[] = $myRule;
					}
				}
				$_SESSION['groupAccess'] = $noAccess;
				$this->setSecurityTime = time ();
			}
		}

		$db_doc =& $this->getDbDocObject ();
		if(!isValidLicense($db_doc)) {
			$tmpAccess = array();
			foreach($this->access AS $cab => $rights) {
				if($rights == "rw") {
					$tmpAccess[$cab] = "ro";	
				} else {
					$tmpAccess[$cab] = $rights;	
				}
			}	
			$this->access = $tmpAccess;
		}
	}

	/*
	 * return root path
	 */
	function getRootPath() {
		global $DEFS;
		return $DEFS['DATA_DIR'].'/'.$this->db_name;
	}

	/*
	 *converts a folder name to a relative path where it is stored
	 */
	function getPath($cabinet) {
		return $this->db_name."/".$cabinet."/";
	}

	/*
	 * create sets up all the info for the Use object
	 * all the following cookies will have to be set in login.php
	 */
	function user()	{
		global $DEFS;
		$this->setSecurityTime = 0;
		$this->access = array();
	}

	/* 
	 * Takes a string $string that will be parsed for more than one space or 
	 * 	more than one underscore.
	 * Returns the new string.
	 */
	function parseStr($string) {
		$string = str_replace("_", " ",$string);
	
		$k = 0;
		$oldArray = split(" ", $string);
		$temp = '';
		while($k < sizeof($oldArray)) {
			if($oldArray{$k} != null) {
				if($temp)
					$temp = $temp." ".$oldArray{$k};
				else
					$temp = $oldArray{$k};
			}
			$k++;
		}
		
		$temp = str_replace(" ", "_", $temp);
		return($temp);
	}

	/* Takes a number as input:
			1 - characters, numbers, and special characters
			2 - for numbers only
			3 - for characters only
			4 - characters, numbers, special characters, and space
			...add more...
		Returns a pool of characters and numbers */
	function characters( $numChoice ) {
		$characters = "@.ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$characters .= "abcdefghijklmnopqrstuvwxyz";
		$numbers = "1234567890";
		$special = "_-";
		$space = " ";

		if( $numChoice == 1 )
			$pool = $characters.$numbers.$special;
		elseif( $numChoice == 2 )
			$pool = $numbers;
		elseif( $numChoice == 3 )
			$pool = $characters;
		elseif( $numChoice == 4 )
			$pool = $characters.$numbers.$space.$special;
		else 
			$pool = "";

		return $pool;
	}

	/*
	 * Takes a string $string that will be checked for invalid characters/number 
	 *	as 1st character.
	 * Returns true if character is invalid or first character is number.
	 */
	function invalidCharacter($string, $extrachars='') {
		$numbers = $this->characters(3); //returns a list of only characters
		$pool = $this->characters(1);	//returns a list of all characters	
		$pool .= $extrachars;	
		for($i=0;$i< strlen($string);$i++) {
			$status = strrpos($pool, $string{$i});
			if($status === false)
				return true;
		}
	
		if( !is_numeric(strrpos($numbers,$string{0})) )
			return $string{0};

		return false;
	}

	function replaceInvalidCharacters($string,$replace="_") {
		$pool = $this->characters(1);
		for($i=0;$i<strlen($string);$i++) {
			$status = strpos($pool, $string{$i});
			if($status === false) {
				$string = substr_replace($string,$replace,$i,1);
			}
		}
		return $string;
	}

	function disconnectDBs() {
		if(isset($this->dbDept) and is_object($this->dbDept)) {
//			echo "DISCONNECT\n";
			$this->dbDept->disconnect ();
			unset($this->dbDept);
			unset($this->dbDeptName);
		}
		if(isset($this->dbDoc) and is_object($this->dbDoc)) {
			$this->dbDoc->disconnect();
			unset($this->dbDoc);
		}
	}
}
}
?>
