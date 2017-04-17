<?php

if(file_exists('../settings/settings.php')) {
	require_once '../settings/settings.php';
	require_once '../lib/allSettings.php';
} else {
	require_once 'settings/settings.php';
	require_once 'lib/allSettings.php';
}

class userSettings extends settingsList {

	var $myList;

	function userSettings(&$db, $username, $groupArr, $db_name, &$db_dept, $allCabs) {
		$this->db =& $db;
		$this->dbName = $db_name;
		$this->username = '';
		$this->groupname = '';
		$this->setSettingsObject();
		$this->listID = $this->getListID();
		$settingsList = $this->getSettingsList();
		$retArr = array ();
		$baseArr = array ();
		if(isset($settingsList[0])) {
			foreach($settingsList[0] as $mySett => $enabled) {
				if($enabled == 1) {
					$baseArr[] = $mySett;
				}
			}
			unset($settingsList[0]);
		}
		$cabArr = array_keys ($allCabs);
		foreach ($allCabs as $real => $arb) {
			$retArr[$real] = $baseArr;
		}
		foreach($settingsList as $myCab => $myList) {
			foreach($myList as $mySett => $enabled) {
				if($enabled == 1) {
					if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
						$retArr[$myCab] = array ();
					}
					if(!in_array($mySett, $retArr[$myCab])) {
						$retArr[$myCab][] = $mySett;
					}
				} else {
					if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
						$retArr[$myCab] = array ();
					}
					if(in_array($mySett, $retArr[$myCab])) {
						$key = array_search($mySett, $retArr[$myCab]);
						unset($retArr[$myCab][$key]);
					}
				}
			}
		}
		foreach($groupArr as $myGroup) {
			$this->groupname = $myGroup;
			$this->setSettingsObject();
			$this->listID = $this->getListID();
			$settingsList = $this->getSettingsList();
			if(isset($settingsList[0])) {
				foreach($settingsList[0] as $mySett => $enabled) {
					foreach($cabArr as $myCab) {
						if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
							$retArr[$myCab] = array ();
						}
						if(!in_array($mySett, $retArr[$myCab])) {
							$retArr[$myCab][] = $mySett;
						}
					}
				}
				unset($settingsList[0]);
			}
			foreach($settingsList as $myCab => $myList) {
				foreach($myList as $mySett => $enabled) {
					if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
						$retArr[$myCab] = array ();
					}
					if(!in_array($mySett, $retArr[$myCab])) {
						$retArr[$myCab][] = $mySett;
					}
				}
			}
		}
		$this->groupname = '';
		$this->username = $username;
		$this->setSettingsObject();
		$this->listID = $this->getListID();
		$settingsList = $this->getSettingsList();
		if(isset($settingsList[0])) {
			foreach($settingsList[0] as $mySett => $enabled) {
				foreach($cabArr as $myCab) {
					if($enabled == 1) {
						if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
							$retArr[$myCab] = array ();
						}
						if(!in_array($mySett, $retArr[$myCab])) {
							$retArr[$myCab][] = $mySett;
						}
					} else {
						if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
							$retArr[$myCab] = array ();
						}
						if(in_array($mySett, $retArr[$myCab])) {	
							$key = array_search($mySett, $retArr[$myCab]);
							unset($retArr[$myCab][$key]);
						}
					}
				}
			}
			unset($settingsList[0]);
		}
		foreach($settingsList as $myCab => $myList) {
			foreach($myList as $mySett => $enabled) {
				if($enabled == 1) {
					if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
						$retArr[$myCab] = array ();
					}
					if(!in_array($mySett, $retArr[$myCab])) {
						$retArr[$myCab][] = $mySett;
					}
				} else {
					if(!isset($retArr[$myCab]) or !is_array($retArr[$myCab])) {
						$retArr[$myCab] = array (); 
					}
					if(in_array($mySett, $retArr[$myCab])) {
						$key = array_search($mySett, $retArr[$myCab]);
						unset($retArr[$myCab][$key]);
					}
				}
			}
		}
		$this->myList = $retArr;
	}
	
	function getUserSettings() {
		return $this->myList;
	}
}


class settingsList {
	var $listID;
	var $db;
	var $username;
	var $groupname;
	var $enableList;
	var $disableList;
	var $inheritList;
	var $deleteList;
	var $addList;
	var $dbName;
	var $settings;
	var $db_dept;
	
	function settingsList($db, $db_name, $db_dept, $type = '', $val = '') {
		$this->db =& $db;
		$this->db_dept =& $db_dept;
		$this->listID = 0;
		$this->dbName = $db_name;
		$this->enableList = array ();
		$this->disableList = array ();
		$this->inheritList = array ();
		$this->addList = array ();
		$this->deleteList = array ();
		if($type == 'user') {
			$this->username = $val;
		} else {
			$this->username = '';
		}
		
		if($type == 'group') {
			$this->groupname = $val;
		} else {
			$this->groupname = '';
		}
		if($this->dbName) {
			$this->setSettingsObject();
			$this->listID = $this->getListID();
		}
	}
	
	function getSettingsList() {
		if($this->listID) {
			$retArr = array ();
			$res = getTableInfo ($this->db, 'settings_list',
				array ('cabinet', 'setting', 'enabled'),
				array ('list_id' => (int)$this->listID),
				'queryAll');
			foreach($res as $eachSett) {
				if(!isset($retArr[$eachSett['cabinet']])) {
					$retArr[$eachSett['cabinet']] = array ();
				}
				$retArr[$eachSett['cabinet']][$eachSett['setting']] = $eachSett['enabled'];
			}
			return $retArr;
		}
		return array ();
	}
	
	function markEnabled($cabinet, $setting) {
		$this->enableList[$cabinet][] = $setting;
	}

	function markDisabled($cabinet, $setting) {
		$this->disableList[$cabinet][] = $setting;
	}
	
	function markInherited($cabinet, $setting) {
		$this->inheritList[$cabinet][] = $setting;
	}
	
	function commitChanges() {
		if($this->listID) {
			$myList = $this->getSettingsList();
			foreach($this->enableList as $cabinet => $cabSettings) {
				foreach($cabSettings as $mySetting) {
					if(!isset($myList[$cabinet]) or 
						!isset($myList[$cabinet][$mySetting])) {
						
						if(!isset($this->addList[$cabinet])) {
							$this->addList[$cabinet] = array ();
						}
						$this->addList[$cabinet][$mySetting] = 1;
					} elseif($myList[$cabinet][$mySetting] == 0) {
						
						if(!isset($this->addList[$cabinet])) {
							$this->addList[$cabinet] = array ();
						}
						if(!isset($this->deleteList[$cabinet])) {
							$this->deleteList[$cabinet] = array ();
						}
						$this->deleteList[$cabinet][] = $mySetting;
						$this->addList[$cabinet][$mySetting] = 1;
					}
				}
			}
			foreach($this->disableList as $cabinet => $cabSettings) {
				foreach($cabSettings as $mySetting) {
					if(isset($myList[$cabinet]) and 
						isset($myList[$cabinet][$mySetting]) and
							$myList[$cabinet][$mySetting] == 1) {
						
						if(!isset($this->deleteList[$cabinet])) {
							$this->deleteList[$cabinet] = array ();
						}
						
						$this->deleteList[$cabinet][] = $mySetting;
						$this->addList[$cabinet][$mySetting] = 0;
					} elseif(!isset($myList[$cabinet]) or
						!isset($myList[$cabinet][$mySetting])) {
						
						if(!isset($this->addList[$cabinet])) {
							$this->addList[$cabinet] = array ();
						}
						
						$this->addList[$cabinet][$mySetting] = 0;
					}
				}
			}
			foreach($this->inheritList as $cabinet => $cabSettings) {
				foreach($cabSettings as $mySetting) {
					if(isset($myList[$cabinet]) and
						isset($myList[$cabinet][$mySetting])) {
						
						if(!isset($this->deleteList[$cabinet])) {
							$this->deleteList[$cabinet] = array ();
						}
						$this->deleteList[$cabinet][] = $mySetting;
					}
				}
			}
			$this->lockTable();
			$this->deleteSettings();
			$numSettings = $this->addSettings();
			$this->unlockTable();
			if($numSettings == 0) {
				$this->removeListID();
			}
		} else {
			foreach($this->enableList as $cabinet => $cabSettings) {
				$this->addList[$cabinet] = array ();
				foreach($cabSettings as $mySetting) {
					$this->addList[$cabinet][$mySetting] = 1;
				}
			}
			foreach($this->disableList as $cabinet => $cabSettings) {
				if(!isset($this->addList[$cabinet])) {
					$this->addList[$cabinet] = array ();
				}
				foreach($cabSettings as $mySetting) {
					$this->addList[$cabinet][$mySetting] = 0;
				}
			}
			$this->lockTable();
			$this->listID = $this->getNextListID();
			$this->deleteSettings();
			$numSettings = $this->addSettings();
			$this->unlockTable();
			if($numSettings > 0) {
				$this->addListID();
			}
		}
	}
	
	function setSettingsObject() {
		if($this->username) {
			$settings = new Usrsettings($this->username, $this->dbName);
		} elseif($this->groupname) {
			$settings = new groupSettings($this->groupname, $this->dbName);
		} else {
			$settings = new GblStt($this->dbName, $this->db);
		}
		$this->settings =& $settings;
	}
	
	function addListID() {
		$this->settings->set('settings_list_id', $this->listID);
		
	}
	
	function getNextListID() {
		$listID = getTableInfo($this->db, 'settings_list',
			array('MAX(list_id) + 1'), array(), 'queryOne');
		if(!$listID) {
			$listID = 1;
		}
		return $listID;
	}
	
	function lockTable() {
		lockTables($this->db, array('settings_list'));
	}
	
	function addSettings() {
		foreach($this->addList as $cabinet => $cabSettings) {
			foreach($cabSettings as $mySetting => $enabled) {
				$queryArr = array (
					'list_id'		=> (int)$this->listID,
					'cabinet'		=> (string)$cabinet,
					'setting'		=> $mySetting,
					'enabled'		=> (int)$enabled,
					'department'	=> $this->dbName
				);
				$res = $this->db->extended->autoExecute('settings_list', $queryArr);
				dbErr($res);
			}
		}
		$count = getTableInfo($this->db, 'settings_list', array('COUNT(id)'), 
			array('list_id' => (int) $this->listID), 'queryOne');
		return $count; 
	}
	
	function deleteSettings() {
		$whereArr = array(
			'list_id'=>(int)$this->listID,
			'department'=>$this->dbName
				 );
		foreach($this->deleteList as $cabinet => $cabSettings) {
			$whereArr['cabinet'] = (string)$cabinet;
			foreach($cabSettings as $mySetting) {
				$whereArr['setting'] = $mySetting;
				deleteTableInfo($this->db,'settings_list',$whereArr);
			}
		}
	}
	
	function unlockTable() {
		unlockTables($this->db);
	} 
	
	function queryAllSettings($dbName) {
		$allSettings = queryAllSettings($dbName);
		uasort($allSettings, 'strnatcasecmp');
		return $allSettings;
	}
	
	function getCabinetSettings($cabinet) {
		$settings = $this->queryAllSettings($this->dbName);
		$settingsList = $this->getSettingsList();
		if(!isset($settingsList[$cabinet])) {
			$settingsList[$cabinet] = array ();
		}
		$settingsPerms = array ();
		foreach($settings as $name => $disp) {
			if(isset($settingsList[$cabinet][$name])) {
				$enabled = $settingsList[$cabinet][$name];
			} else {
				$enabled = 2;
			}
			$settingsPerms[] = array (
				'name'		=> $name,
				'disp_name'	=> $disp,
				'enabled'	=> (int)$enabled
			);
		}
		return $settingsPerms;
	}
	
	function getSettingCabinets($setting) {
		$settingsList = $this->getSettingsList();
		$cabInfo = getTableInfo($this->db_dept, 'departments', array(), array('deleted' => 0));
		$cabList = array ();
		while($row = $cabInfo->fetchRow()) {
			$cabinet = $row['real_name'];
			$enabled = 1;
			if(!isset($settingsList[$cabinet]) or !isset($settingsList[$cabinet][$setting])) {
				$enabled = 2;
			} else {
				$enabled = $settingsList[$cabinet][$setting];
			}
			$settArr = array (
				'real_name'	=> $cabinet,
				'arb_name'	=> $row['departmentname'],
				'enabled'	=> (int)$enabled
			);
			$cabList[] = $settArr;
		}
		return $cabList;
	}
	
	function getGlobalSettings() {
		$allSettings = $this->queryAllSettings($this->dbName);
		$settingsList = $this->getSettingsList();
		$baseArr = array ();
		if(isset($settingsList[0])) {
			foreach($settingsList[0] as $mySett => $enabled) {
				$baseArr[$mySett] = $enabled;
			}
		}
		$cabInfo = getTableInfo($this->db_dept, 'departments', array(), array('deleted' => 0));
		$cabList = array ();
		$gblList = array ();
		while($row = $cabInfo->fetchRow()) {
			$cabList[] = $row['real_name'];
		}
		foreach($allSettings as $mySetting => $myDisp) {
			$i = 0;
			foreach($cabList as $cabinet) {
				if(isset($settingsList[$cabinet]) and 
					isset($settingsList[$cabinet][$mySetting])) {
				
					$i++;
				}
			}
			$state = '';
			if(isset($baseArr[$mySetting])) {
				if($baseArr[$mySetting] == 1) {
					$state = 'enabled';
				} else {
					$state = 'disabled';
				}
			} else {
				$state = 'inherit';
			}
			
			if($i != 0) {			
				$mixed = 1;
			} else {
				$mixed = 0;
			}
			$gblList[] = array (
				'name'		=> $mySetting,
				'disp_name'	=> $myDisp,
				'state'		=> $state,
				'mixed'		=> (int)$mixed
			);
		}
		return $gblList;
	}
	
	function getListID() {
		return $this->settings->get('settings_list_id');
	}
	
	function removeListID() {
		$this->settings->removeKey('settings_list_id');
	}
}

?>
