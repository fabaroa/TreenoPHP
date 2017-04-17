<?php
//Don't use this class, extend it.
class DataObject {
	var $_table;
	var $_db;
	var $_columns;
	var $_uniqueKey;
	var $_res;
	var $_order;
	
	function factory($table, &$db, $useObj = null) {
		$class = 'DataObject_'.$table;
		$file = $class.'.inc.php';
		if(file_exists('../DataObjects/'.$file)) {
			require_once '../DataObjects/'.$file;
		} else {
			require_once 'DataObjects/'.$file;
		}
		return new $class($db, $useObj);
	}
			
	function DataObject(&$db, $useObj = null) {
		$this->_db =& $db;
		$this->_columns = $this->_tableColumns();
		$this->_table = $this->_tableName();
		$this->_uniqueKey = $this->_key();
		$this->_res = null;
		$this->_order = array();
		if(!is_null($useObj)) {
			if($this->_table == $useObj->_table) {
				foreach(array_keys($this->_columns) as $key) {
					$this->$key = $useObj->$key;
				}
			} else {
				die("Passed in wrong object type!!\n");
			}
		}
	}
	
	function _tableColumns() {
		return array ();
	}
	
	function _tableName() {
		return '';
	}
	
	function _key() {
		return '';
	}
			
	function get($myKey, $myValue = '') {
		if($this->_columns and $this->_table) {
			if($myValue === '' and $this->_uniqueKey) {
				$myValue = $myKey;
				$myKey = $this->_uniqueKey;
			}
			$this->$myKey = $myValue;
			$myValue = $this->_quote($myKey);
			$row = getTableInfo($this->_db, $this->_table, array(), array($myKey => $myValue), 'queryRow');
			foreach(array_keys($this->_columns) as $key) {
				if(isset($row[$key])) {
					$this->$key = $row[$key];
				} else {
					$this->$key = '';
				}
			}
		} else {
			$this->_notSetUp();
		}
	}
	
	function delete() {
		if($this->_columns and $this->_table) {
			$whereArr = array ();
			if($this->_uniqueKey and $this->_columnIsNotEmpty($this->_uniqueKey)) {
				$whereArr[$this->_uniqueKey] = $this->_quote($this->_uniqueKey);
			} else {
				foreach(array_keys($this->_columns) as $key) {
					if($this->_columnIsNotEmpty($key)) {
						$whereArr[$key] = $this->_quote($key);
					}
				}
			}
			deleteTableInfo($this->_db, $this->_table, $whereArr);
		} else {
			$this->_notSetUp();
		}
	}
	
	function _columnIsNotEmpty($column) {
		return ($this->$column !== null and $this->$column != '');
	}
	
	function update($origObj = null) {
		if($this->_columns and $this->_table) {
			$whereArr = array ();
			if($this->_uniqueKey and $this->_columnIsNotEmpty($this->_uniqueKey)) {
				$whereArr[$this->_uniqueKey] = $this->_quote($this->_uniqueKey);
			}
			$upArr = array ();
			if(!is_null($origObj)) {
				if($this->_table == $origObj->_table) {
					foreach(array_keys($this->_columns) as $key) {
						if($this->$key !== $origObj->$key) {
							$upArr[$key] = $this->_quote($key);
						}
					}
				} else {
					die("Passed in wrong object type!!\n");
				}
			} else {
				foreach(array_keys($this->_columns) as $key) {
					if($this->_columnIsNotEmpty($key)) {
						$upArr[$key] = $this->_quote($key);
					}
				}
			}

			if ($upArr) {
				if (count($whereArr)==0)
				{
					$this->_notSetUp();
				}
				else
				{
					updateTableInfo($this->_db, $this->_table, $upArr, $whereArr);
				}
			}
		} else {
			$this->_notSetUp();
		}
	}
	
	function insert() {
		if($this->_columns and $this->_table) {
			$insArr = array ();
			foreach($this->_columns as $key => $quote) {
				if($this->_columnIsNotEmpty($key)) {
					$insArr[$key] = $this->_quote($key);
				}
			}
			$res = $this->_db->extended->autoExecute($this->_table, $insArr);
			dbErr($res);
		} else {		
			$this->_notSetUp();
		}
	}
	
	function _notSetUp() {
		die("Class is not set up correctly!\n");
	}
	
	function _quote($key) {
		if($this->_columns[$key]) {
			return (string)$this->$key;
		} else {
			return (int)$this->$key;
		}
	}
	
	function orderBy($column, $dir) {
		$this->_order[$column] = $dir;
	}
	
	function find() {
		if($this->_columns and $this->_table) {
			$queryArr = array ();
			foreach(array_keys($this->_columns) as $key) {
				if($this->_columnIsNotEmpty($key)) {
					$queryArr[$key] = $this->_quote($key);
				}
			}
			$this->_res =& getTableInfo($this->_db, $this->_table, array(), $queryArr, 'query', $this->_order);
		} else {		
			$this->_notSetUp();
		}
	}
	
	function count() {
		if($this->_columns and $this->_table) {
			$queryArr = array ();
			foreach(array_keys($this->_columns) as $key) {
				if($this->_columnIsNotEmpty($key)) {
					$queryArr[$key] = $this->_quote($key);
				}
			}
			return getTableInfo($this->_db, $this->_table, array('COUNT(*)'), $queryArr, 'queryOne');
		} else {
			$this->_notSetUp();
			return false;
		}
	}
	
	function fetch() {
		if($this->_columns and $this->_table) {
			$row = $this->_res->fetchRow();
			if($row) {
				foreach(array_keys ($this->_columns) as $key) {
					$this->$key = $row[$key];
				}
				return true;
			} else {
				return false;
			}
		} else {		
			$this->_notSetUp();
			return false;
		}		
	}
}
