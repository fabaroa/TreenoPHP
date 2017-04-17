<?php

class DataObject_users extends DataObject {
	
	//These are the public variables that you can set to query the table
	var $id;
	var $username;
	var $password;
	var $regdate;
	var $last_login;
	var $guest;
	var $db_list_id;
	var $exp_time;
	var $email;
	var $ldap_id;
	var $departments;
	var $defaultDept;
	
	function DataObject_users(&$db, $useObj = null) {
		DataObject::DataObject($db, $useObj);
	}
	
	function _tableColumns() {
		return array (
			'id'			=>	false,
			'username'		=>	true,
			'password'		=>	true,
			'regdate'		=>	true,
			'last_login'	=>	true,
			'guest'			=>	false,
			'db_list_id'	=>	false,
			'exp_time'		=>	true,
			'email'			=>	true,
			'ldap_id'		=>	false,
		);
	}
	
	function _tableName() {
		return 'users';
	}
	
	function _key() {
		return 'id';
	}
	
	function get($key, $value = '') {
		DataObject::get($key, $value);
		$res =& getTableInfo($this->_db, 'db_list', array('db_name', 'priv', 'default_dept'), array('list_id' => (int) $this->db_list_id));
		$this->departments = array();
		while($row = $res->fetchRow()) {
			if($row['default_dept'] == 1) {
				$this->defaultDept = $row['db_name'];
			}
			$this->departments[$row['db_name']] = $row['priv'];
		}
	}
	
	function fetch() {
		$row = $this->_res->fetchRow();
		if($row) {
			foreach(array_keys ($this->_columns) as $key) {
				if (isset ($row[$key])) {
					$this->$key = $row[$key];
				}
			}
			$res = getTableInfo($this->_db, 'db_list', array('db_name', 'priv', 'default_dept'), array('list_id' => (int) $this->db_list_id));
			$this->departments = array();
			while($row = $res->fetchRow()) {
				if($row['default_dept'] == 1) {
					$this->defaultDept = $row['db_name'];
				}
				$this->departments[$row['db_name']] = $row['priv'];
			}
			return true;
		} else {
			return false;
		}
	}
	
	function getUsersByDepartments($deptArr) {
		$orArr = array ();
		foreach($deptArr as $department => $priv) {
			if (is_numeric($department)) {
				$orArr[] = "db_list.db_name " . LIKE . " '%$priv%'"; 
			} else {
				$orArr[] = "db_list.db_name " . LIKE . " '%$department%'"; 
			}
		}
		$whereArr = array (
			'('.implode(' OR ', $orArr).')',
			"list_id = db_list_id"
		);
		$res = getTableInfo($this->_db, array('users', 'db_list'), array('DISTINCT(username)'), $whereArr, 'queryCol', array('username' => 'ASC'));
		return $res; 
	}
	
	//Do not use ->insert(), use this -- when you insert a user, you have to
	//insert into the db_list table as well, and this takes care of that.
	function insertUser($defaultDept, $adminCode = 'N') {
		lockTables($this->_db, array('db_list', 'users'));
		$listID = getTableInfo($this->_db, 'db_list', array('MAX(list_id) + 1'), array(), 'queryOne');
		if(!$listID) {
			$listID = 1;
		}
		$this->db_list_id = $listID;
		$this->changeDepartmentAccess($defaultDept, $adminCode, 1);
		unlockTables($this->_db);
		$this->insert();
	}

	function changeDepartmentAccess($dept, $adminCode, $default = false) {
		if( (int)$this->db_list_id === 0 ) { //checks for ZERO previous department access
			$listID = getTableInfo($this->_db, 'db_list', array('MAX(list_id) + 1'), array(), 'queryOne');
			if(!$listID) {
				$listID = 1;
			}
			$this->db_list_id = $listID;
			updateTableInfo($this->_db, 'users', array('db_list_id' => $this->db_list_id), array('id' => $this->id)); 
		}

		$myList = getTableInfo($this->_db, 'db_list', array(), array('list_id' => (int) $this->db_list_id), 'queryAll');
		$foundDB = false;
		foreach($myList as $eachDept) {
			if($eachDept['db_name'] == $dept) {
				$foundDB = true;
				$queryArr = array ();
				if($eachDept['priv'] != $adminCode) {
					$queryArr['priv'] = $adminCode;
				}
				if($eachDept['default_dept'] != $default and $default !== false) {
					$queryArr['default_dept'] = (int) $default;
				}
				if($queryArr) {
					updateTableInfo($this->_db, 'db_list', $queryArr, array('id' => (int) $eachDept['id']));
				}
			}
		}
		if(!$foundDB) {
			$insArr = array (
				'list_id'		=> (int) $this->db_list_id,
				'db_name'		=> $dept,
				'priv'			=> $adminCode,
				'default_dept'	=> (int) $default,
				
			);
			$res = $this->_db->extended->autoExecute('db_list', $insArr);
			dbErr($res);
		}
	}
	
	function changeDefaultDepartment($dept) {
		foreach($this->departments as $myDept => $priv) {
			if($dept == $myDept) {
				$this->changeDepartmentAccess($myDept, $priv, 1);
			} else {
				$this->changeDepartmentAccess($myDept, $priv, 0);
			}
		}
	}
	
	function deleteDeptFromUser($dept) {
		$res = getTableInfo($this->_db, 'db_list', array('id', 'db_name'), array('list_id' => (int) $this->db_list_id));
		$myID = -1;
		while($row = $res->fetchRow()) {
			if($row['db_name'] == $dept) {
				$myID = $row['id'];
			}
		}
		if($myID != -1) {
			deleteTableInfo($this->_db, 'db_list', array('id' => (int) $myID));
			if(count($this->departments) == 1) {
				updateTableInfo($this->_db, 'users', array('db_list_id' => 0), array('id' => $this->id)); 
			}
		}
	}
	
	function delete() {
		DataObject::delete();
		deleteTableInfo($this->_db, 'db_list', array('list_id' => (int) $this->db_list_id));
	}
}

?>
