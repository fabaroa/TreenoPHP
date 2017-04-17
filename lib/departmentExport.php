<?php
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../groups/groups.php';
include_once '../documents/documents.php';
include_once '../lib/cabinets.php';
include_once '../settings/settings.php';
include_once '../lib/mime.php';
include_once '../lib/settings.php';

//$treenoObj = new treenoExport('client_files');
class treenoExport {
	var $groups;//groups array
	var $documents;//documents array
	var $cabinets;//cabinet array
	var $sysSettings;//folder view settings

	var $dep;//department
	var $db_dept;//department db object
	var $db_doc;//docutron db object
	function treenoExport($dep) {
		$this->dep = $dep;
		$this->db_doc = getDbObject('docutron');
		$this->db_dept = getDbObject($this->dep);
	}

	function export($userTempDir) {
		global $DEFS;

		$this->getGroups();
		$this->getDocuments();
		$this->getCabinets();
		$this->getSettings();
		$export['groups'] = $this->groups;
		$export['documents'] = $this->documents;
		$export['cabinets'] = $this->cabinets;
		$export['settings'] = $this->sysSettings;
		file_put_contents($userTempDir.'/dt_export.json',json_encode($export));
	}

	function getGroups() {
		$this->groups = array();
		$sArr = array('real_groupname','arb_groupname');
		$gArr = getTableInfo($this->db_dept,'groups',$sArr,array(),'getAssoc');	
		foreach($gArr AS $real => $arb) {
			$this->groups[$real] = $arb;
		}
	}

	function getDocuments() {
		$this->documents = array();	

		$sArr = array('id','document_table_name','document_type_name');
		$wArr = array('enable' => 1);
		$dArr = getTableInfo($this->db_dept,'document_type_defs',$sArr,$wArr,'getAssoc');	
		foreach($dArr AS $id => $d) {
			$sArr = array('real_field_name','arb_field_name');
			$wArr = array('document_table_name' => $d['document_table_name']);
			$oArr = array('ordering' => 'ASC');
			$indArr = getTableInfo($this->db_dept,'document_field_defs_list',$sArr,$wArr,'getAssoc',$oArr);
			foreach($indArr AS $real => $arb) {
				$this->documents[$d['document_type_name']]['indices'][$arb] = array(); 

				$sArr = array('definition');
				$wArr = array('document_type_id' => $id,
							'document_type_field' => $real);
				$defsList = getTableInfo($this->db_dept,'definition_types',$sArr,$wArr,'queryCol'); 
				if(count($defsList)) {
					$this->documents[$d['document_type_name']]['indices'][$arb] = $defsList; 
				}
			}

			$tArr = array('document_type_defs','document_permissions','group_list');
			$sArr = array('groupname');
			$wArr = array(	"document_table_name = '".$d['document_table_name']."'",
							'permissions_id != 0',
							'permissions_id=permission_id',
							'group_list_id=list_id');
			$groupArr = getTableInfo($this->db_dept,$tArr,$sArr,$wArr,'queryCol');
			foreach($groupArr AS $g) {
				$this->documents[$d['document_type_name']]['groupperm'][] = $this->groups[$g];
			}
		}
	}

	function getCabinets() {
		$this->cabinets = array();

		$sArr = array('departmentid','real_name','departmentname');
		$wArr = array('deleted' => 0);
		$cabArr = getTableInfo($this->db_dept,'departments',$sArr,$wArr,'getAssoc');
		foreach($cabArr AS $id => $cInfo) {
			$indArr = getCabinetInfo($this->db_dept,$cInfo['real_name']);
			foreach($indArr AS $ind) {
				$this->cabinets[$cInfo['departmentname']]['indices'][$ind] = array();

				$sArr = array('value');
				$wArr = array('k' => "dt,$this->dep,$id,$ind");
				$cabDefs = getTableInfo($this->db_doc,'settings',$sArr,$wArr,'queryOne');
				if($cabDefs) {
					$this->cabinets[$cInfo['departmentname']]['indices'][$ind] = explode(",,,",$cabDefs);
				}
			}

			$tArr = array('document_settings','document_settings_list');
			$sArr = array('document_id');
			$wArr = array('cab' => $cInfo['real_name']);
			$docFilters = getTableInfo($this->db_dept,$tArr,$sArr,$wArr,'queryCol');
			foreach($docFilters AS $doc) {
				$sArr = array('document_type_name');
				$wArr = array('id' => $doc);
				$docName = getTableInfo($this->db_dept,'document_type_defs',$sArr,$wArr,'queryOne');
				$this->cabinets[$cInfo['departmentname']]['filters'][] = $docName;
			}
		}
	}

	function getSettings() {
		foreach($this->groups AS $real => $arb) {
			$settingsList = new settingsList($this->db_doc,$this->dep,$this->db_dept,'group',$real);
			foreach($settingsList->getGlobalSettings() AS $sett) {
				$this->sysSettings['groups'][$real][$sett['name']] = $sett['state']; 
			}
		}

		$settingsList = new settingsList($this->db_doc,$this->dep,$this->db_dept);
		$sArr = array('real_name');
		$wArr = array('deleted' => 0);
		$cabArr = getTableInfo($this->db_dept,'departments',$sArr,$wArr,'queryCol');
		foreach($cabArr AS $cab) {
			foreach($settingsList->getCabinetSettings($cab) AS $sett) {
				$this->sysSettings['cabinets'][$cab][$sett['name']] = $sett['enabled']; 
			}
		}

		foreach($settingsList->getGlobalSettings() AS $sett) {
			$this->sysSettings['global'][$sett['name']] = $sett['state']; 
		}
	}
}

//$treenoObj = new treenoImport('client_files3','dt_export.json');
class treenoImport {
	var $structArr;
	var $dep;
	var $db_dept;
	var $db_doc;
	var $msgArr;
	var $user;

	function treenoImport($dep,$file,$user) {
		$this->dep = $dep;
		$this->db_dept = getDbObject($dep);
		$this->db_doc = getDbObject('docutron');
		$this->msgArr = array();
		$this->user = $user;
		$this->structArr = json_decode(file_get_contents($file));
	}

	function import() {
		global $DEFS;

		$this->setGroups($this->structArr->groups);
		$this->setDocuments($this->structArr->documents);
		$this->setCabinets($this->structArr->cabinets);
		$this->setSettings($this->structArr->settings);
	
		$this->msgArr[] = "Please logout for changes to take effect";
		if(count($this->msgArr)) {
			$msgFile = $DEFS['TMP_DIR'].'/docutron/admin/importError.txt';
			if(is_file($msgFile)) {
				unlink($msgFile);
			}
			foreach($this->msgArr AS $msg) {
				file_put_contents($msgFile,$msg."\n",FILE_APPEND);
			}
		}
	}

	function setGroups($groupArr) {
		$gObj = new groups($this->db_dept);
		foreach($groupArr AS $grp) {
			if(!$gObj->checkGroup($grp)) {
				$gObj->addGroup($grp,array());

				$sArr = array('id');
				$wArr = array('arb_groupname' => $grp);
				$gid = getTableInfo($this->db_dept,'groups',$sArr,$wArr,'queryOne');
				$gInfo = array('group_id' => $gid,
							'uid' => 1);
				$db = $this->db_dept->extended->autoExecute ('users_in_group', $gInfo);
				dbErr($db);
			} else {
				$this->msgArr[] = "group already exists: $grp";
			}
		}
	}

	function setDocuments($docArr) {
		foreach($docArr AS $docName => $docInfo) {
			$docTable = "";	
			$enArr = array('document_type_name' => $docName);
			addDocType($enArr,$docTable,$this->db_doc,$this->db_dept);
			if($docTable) {
				$sArr = array('id');
				$wArr = array('document_table_name' => $docTable);
				$docTypeID = getTableInfo($this->db_dept,'document_type_defs',$sArr,$wArr,'queryOne');
				$ct = 1;
				foreach($docInfo->indices AS $ind => $dTypes) {
					$fName = "";

					$enArr = array('document_table_name' => $docTable,'field_name' => $ind);
					addDocumentField($enArr,$fName,$this->db_doc,$this->db_dept);
					if(count($dTypes) > 0) {
						$id = 0;
						foreach($dTypes AS $t) {
							$enArr = array('document_type_id' => $docTypeID, 
										'document_type_field' => 'f'.$ct, 
										'definition' => $t);
							addDocDef($enArr,$id,$this->db_dept);	
						}
					}
					$ct++;
				}
			} else {
				$this->msgArr[] = "document already exists: $docName";
			}
		}
	}

	function setCabinets($cabArr) {
		$sArr = array('real_name','departmentname');
		$cArr = getTableInfo($this->db_dept,'departments',$sArr,array(),'getAssoc');
		foreach($cabArr AS $cab => $cInfo) {
			$realCab = str_replace("_"," ",$cab);
			$indArr = array();
			foreach($cInfo->indices AS $k => $dTypes) {
				$indArr[] = $k;
			}

			if(!in_array($cab,$cArr) && !array_key_exists($realCab,$cArr)) {
				$cab = str_replace("-","_",$cab);
				$cab = str_replace(" ","_",$cab);
				createFullCabinet($this->db_dept,$this->db_doc,$this->dep,$cab,$realCab,$indArr);

				$sArr = array('departmentid');
				$wArr = array('real_name' => $realCab);
				$cabID = getTableInfo($this->db_dept,'departments',$sArr,$wArr,'queryOne');
				$settObj = new Gblstt($this->dep,$this->db_doc);
				foreach($cInfo->indices AS $k => $dTypes) {
					if(count($dTypes) > 0) {
						$key = "dt,".$this->dep.",$cabID,$k";
						$settObj->set($key,implode(",,,",$dTypes));	
					}
				}
				//set the user access for the current logged in user as default.
				$this->setAccess($cab);

				if(isSet($cInfo->filters)) {
					$sArr = array('document_type_name','id');
					$wArr = array();
					$docList = getTableInfo($this->db_dept,'document_type_defs',$sArr,$wArr,'getAssoc');
					$fList = array();
					foreach($cInfo->filters AS $docType) {
					if(array_key_exists($docType,$docList)){
						$fList[] = $docList[$docType];
						}else{
							//error_log("document type does not exist:".$docType." in ".sArr['departmentname']." Department" );
						}
					}
					addDocumentFilter($cab,$fList,$this->db_dept);
				}
			} else {
				$this->msgArr[] = "cabinet already exists: $cab";
			}
		}
	}
	
	function setAccess($cab)
	{
		$username = $this->user->username;
		$access = getTableInfo($this->db_dept,'access', array('access'), array("username='$username'"), 'queryOne');
		
		$accessArr = unserialize(base64_decode($access));
		$accessArr[$cab] = 'rw';
		
		$updateArr = array('access'=>base64_encode(serialize($accessArr)));
		$whereArr = array('username'=>$username);
		updateTableInfo($this->db_dept,'access',$updateArr,$whereArr);
		
	}

	function setSettings($settArr) {
		if(count($settArr->groups)) {
			foreach($settArr->groups AS $grp => $sArr) {
				$settingsList = new settingsList($this->db_doc, $this->dep, $this->db_dept, 'group', $grp);
				foreach($sArr AS $k => $v) {
					if($v == "enabled") {
						$settingsList->markEnabled('0',$k);
					} elseif($v == "disabled") {
						$settingsList->markDisabled('0',$k);
					} else {
						$settingsList->markInherited('0',$k);
					}
				}
			}
			$settingsList->commitChanges();
		}

		if(count($settArr->cabinets)) {
			$settingsList = new settingsList($this->db_doc, $this->dep, $this->db_dept);
			foreach($settArr->cabinets AS $cab => $sArr) {
				foreach($sArr AS $k => $v) {
					if($v == 1) {
						$settingsList->markEnabled($cab,$k);
					} elseif($v == 2) {
						$settingsList->markInherited($cab,$k);
					} else {
						$settingsList->markDisabled($cab,$k);
					}
				}
			}
			$settingsList->commitChanges();
		}

		if(count($settArr->global)) {
			$settingsList = new settingsList($this->db_doc, $this->dep, $this->db_dept);
			foreach($settArr->global AS $k => $v) {
				if($v == "enabled") {
					$settingsList->markEnabled('0',$k);
				} elseif($v == "disabled") {
					$settingsList->markDisabled('0',$k);
				} else {
					$settingsList->markInherited('0',$k);
				}
			}
			$settingsList->commitChanges();
		}
	}
}
?>
