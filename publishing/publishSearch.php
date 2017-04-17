<?php
include_once 'publishUser.php';

class publishSearch {
	var $name;
	var $id;
	var $userList;
	var $itemID;
	var $itemList;
	var $filterID;
	var $filterList;
	var $owner;
	var $message;
	var $expireTime;
	var $dateAdded;
	var $dependArr;
	var $db;

	function publishSearch($owner,$id=NULL) {
		$this->message	= "";
		$this->db = getDbObject('docutron');
		if(!$id) {
			$this->name			= "";
			$this->id			= "";
			$this->userList		= array();
			$this->itemID		= "";
			$this->itemList		= array();
			$this->filterID		= "";
			$this->filterList	= array();
			$this->owner		= $owner;
			$this->expireTime	= "";
			$this->dateAdded	= "";
			$this->dependArr	= array();
		} else {
			$this->id		= $id;
			$this->setPublishSearch();
			$this->setUserList();
			$this->setItemList();
			//$this->getFilterList();
		}
	}

	function setPublishSearch() {
		$wArr = array('id' => (int)$this->id);
		$pubInfo = getTableInfo($this->db,'publish_search',array(),$wArr,'queryRow');

		$this->name			= $pubInfo['name'];
		$this->owner		= $pubInfo['owner'];
		$this->itemID		= $pubInfo['ps_list_id'];
		$this->filterID		= $pubInfo['pf_list_id'];
		$this->dateAdded	= $pubInfo['date_added'];
		$this->expireTime	= $pubInfo['expiration'];
	}

	function getPubName() {
		return $this->name;
	}

	function getExpireTime() {
		return $this->expireTime;
	}

	function setExpireTime($hours) {
		$uArr = array('expiration' => (int)$hours);
		$wArr = array('id' => (int)$this->id);
		updateTableInfo($this->db,'publish_search',$uArr,$wArr);

		$this->message = "Expiration time successfully updated";
	}

	function getUserList() {
		return $this->userList;
	}

	function setUserList() {
		$tArr = array('publish_user_list','publish_user');
		$sArr = array('publish_user.id','publish_user.email');
		$wArr = array(	'ps_id = '.(int)$this->id,
						'p_id = publish_user.id');
		$this->userList = getTableInfo($this->db,$tArr,$sArr,$wArr,'getAssoc');
	}

	function getItemList() {
		return $this->itemList;
	}

	function getItemID() {
		if(!$this->itemID) {
			$this->setItemID();
		}
		return $this->itemID;
	}

	function getItem($dep,$cab,$doc_id,$file_id=NULL) {
		if($file_id) {
			return $this->itemList[$dep][$cab][$doc_id][$file_id];
		} else {
			return $this->itemList[$dep][$cab][$doc_id]['folder'];
		}
	}

	function setItemID() {
		$sArr = array('MAX(ps_list_id)+1');
		$itemID = getTableInfo($this->db,'publish_search',$sArr,array(),'queryOne');
		$this->itemID = ($itemID) ? $itemID : 1;

		$uArr = array('ps_list_id' => (int)$this->itemID);
		$wArr = array('id' => (int)$this->id);
		updateTableInfo($this->db,'publish_search',$uArr,$wArr);
	}

	function setItemList() {
		$this->itemList = array();
		$this->dependArr = array();

		$sArr = array('id','department','cab','doc_id','file_id');
		$wArr = array('ps_list_id' => (int)$this->itemID);
		$oArr = array('department' => 'ASC','cab' => 'ASC', 'doc_id' => 'ASC');
		$itemArr = getTableInfo($this->db,'publish_search_list',$sArr,$wArr,'queryAll');

		foreach($itemArr AS $info) {
			extract($info);	
			if($doc_id) {
				$this->addFolderToList($department,$cab,$doc_id);
				if($file_id) {
					$db_dept = getDbObject($department);	
					$sArr = array('document_id','document_table_name','parent_filename');
					$wArr = array('id' => (int)$file_id);
					$docInfo = getTableInfo($db_dept,$cab.'_files',$sArr,$wArr,'queryRow');
					if($docInfo['document_id']) {
						$doc_name = $docInfo['document_table_name'];
						$document_id = $docInfo['document_id'];
						$this->addDocumentToList($department,$cab,$doc_id,$file_id,$doc_name,$document_id);
					}
				} else {
					$this->addDependency($department,$cab,$doc_id);
				}
			}
		}
	}

	function getDependArr() {
		return $this->dependArr;
	}

	function addDependency($dep,$cab,$doc_id) {
		$str = "$dep-$cab-$doc_id";
		$this->dependArr[] = $str;  
	}

	function addFolderToList($dep,$cab,$doc_id) {
		if(!array_key_exists($dep,$this->itemList)) {
			$this->itemList[$dep] = array();
		}

		if(!array_key_exists($cab,$this->itemList[$dep])) {
			$this->itemList[$dep][$cab] = array();
		}
		
		if(!array_key_exists($doc_id,$this->itemList[$dep][$cab])) {
			$this->itemList[$dep][$cab][$doc_id] = array();
			$db_dept = getDbObject($dep);
			$folderInfo = getCabIndexArr($doc_id,$cab,$db_dept);
			$this->itemList[$dep][$cab][$doc_id]['folder'] = implode(" ",$folderInfo);	
		}
	}

	function addDocumentToList($dep,$cab,$doc_id,$file_id,$doc_name,$document_id) {
		$db_dept = getDbObject($dep);	
		$sArr = array('id','document_type_name');
		$wArr = array('document_table_name' => $doc_name);
		$docInfo = getTableInfo($db_dept,'document_type_defs',$sArr,$wArr,'queryRow');

		$sArr = array('document_field_value');
		$wArr = array(	'document_defs_list_id' => (int)$docInfo['id'],
						'document_id' => (int)$document_id);
		$docValues = getTableInfo($db_dept,'document_field_value_list',$sArr,$wArr,'queryCol');
		$docDesc = $docInfo['document_type_name']." ".implode(" ",$docValues);
		$this->itemList[$dep][$cab][$doc_id][$file_id] = $docDesc; 
	}

	function getFilterList() {
		$tArr = array('publish_search','publish_filter','publish_filter_list');
		$sArr = array('publish_filter_list.id');
		$wArr = array('publish_search.id = '.(int)$this->id,
					  'pf_list_id = psf_list_id',
					  'publish_filter.id = pf_id' );
		$this->filterList = getTableInfo($this->db,$tArr,$sArr,$wArr,'queryCol');
	}

	function setPublishName($name,$enabled=1) {
		$this->name = $name;

		$sArr = array('COUNT(id)');
		$wArr = array('name' => $name); 
		$ct = getTableInfo($this->db,'publish_search',$sArr,$wArr,'queryOne');
		if(!$this->id) {
			if(!$ct) {
				$this->dateAdded = date('Y-m-d G:i:s');
				lockTables($this->db,array('publish_search'));	
				$insertArr = array(	'name'			=> $name,
									'owner'			=> $this->owner,
									'enabled'		=> (int)$enabled,
									'date_added'	=> $this->dateAdded);
				$res = $this->db->extended->autoExecute('publish_search',$insertArr);

				$sArr = array('MAX(id)');
				$this->id = getTableInfo($this->db,'publish_search',$sArr,array(),'queryOne');
				unlockTables($this->db);	
				$this->message = "Publish name successfully created";
				return true;
			} else {
				$this->message = "Publish name already exists";
			}
		} else {
			if(!$ct) {
				$uArr = array('name' => $name);
				$wArr = array('id' => (int)$this->id);
				updateTableInfo($this->db,'publish_search',$uArr,$wArr);
				$this->message = "Publish name successfully updated";
				return true;
			}
		}
		return false;
	}

	function addToUserList($uid,$username) {
		$insertArr = array(	'ps_id' => (int)$this->id,
							'p_id'	=> (int)$uid);
		$this->db->extended->autoExecute('publish_user_list',$insertArr);
		$this->userList[$uid] = $username; 
		$this->message = "Publish User has been added to search";
	}

	function deleteFromUserList($uid) {
		$wArr = array(	'ps_id' => (int)$this->id,
						'p_id'	=> (int)$uid);
		deleteTableInfo($this->db,'publish_user_list',$wArr);
		unset($this->userList[$uid]); 
		$this->message = "Publish User has been removed from search";
	}

	function addItemToList($dep,$cab,$doc_id=0,$file_id=0,$field='',$term='',$wf_def_id=0,$type='folder_search') {
		$dispArr = array();
		
		lockTables($this->db,array('publish_search_list','publish_search'));
		$id = 0;
		if($doc_id) {
			$sArr = array('id');	
			$wArr = array(	'ps_list_id'	=> (int)$this->id,
							'department'	=> $dep,
							'cab'			=> $cab,
							'doc_id'		=> (int)$doc_id,
							'file_id'		=> (int)$file_id );
			$id = getTableInfo($this->db,'publish_search_list',$sArr,$wArr,'queryOne');
		}
		
		if(!$id) {
			$list_id = $this->getItemID();
			$insertArr = array(	'ps_list_id' => (int)$list_id,
						'department' => $dep,
						'cab' => $cab,
						'doc_id' => (int)$doc_id,
						'file_id' => (int)$file_id,
						'field' => ($field) ? $field : NULL,
						'term' => ($term) ? $term : NULL,
						'wf_def_id' => (int)$wf_def_id,
						'type' => $type);
			$res = $this->db->extended->autoExecute('publish_search_list',$insertArr);
			unlockTables($this->db);
			dbErr($res);

			if($doc_id) {
				$this->addFolderToList($dep,$cab,$doc_id);
				$dispArr = array(	'dep'	=> $dep,
								'cab'	=> $cab,
								'doc_id'=> $doc_id,
								'folder'=> $this->getItem($dep,$cab,$doc_id));
				if($file_id) {
					$db_dept = getDbObject($dep);	
					$sArr = array('document_id','document_table_name','parent_filename');
					$wArr = array('id' => (int)$file_id);
					$docInfo = getTableInfo($db_dept,$cab.'_files',$sArr,$wArr,'queryRow');
					if($docInfo['document_id']) {
						$doc_name = $docInfo['document_table_name'];
						$document_id = $docInfo['document_id'];
						$this->addDocumentToList($dep,$cab,$doc_id,$file_id,$doc_name,$document_id);
						$dispArr['file_id'] = $file_id; 
						$dispArr['document'] = $this->getItem($dep,$cab,$doc_id,$file_id); 
					} else {
						$this->itemList[$doc_id]['file'] = $docInfo['parent_filename'];
					}
				} else {
					$this->addDependency($dep,$cab,$doc_id);
				}
			}
			$this->message = "Item successfully added to ".$this->getPubName();
		} else {
			unlockTables($this->db);
			$this->message = "Item already exists in ".$this->getPubName();
		}
		return $dispArr;
	}

	function deleteItemFromList($dep,$cab,$doc_id=0,$file_id=0) {
		$wArr = array(	'ps_list_id'=> (int)$this->itemID,
						'department'=> $dep,
						'cab'		=> $cab);
		if($doc_id) {
			$wArr['doc_id'] = $doc_id;
		}

		if($file_id) {
			$wArr['file_id'] = $file_id;
		}
		deleteTableInfo($this->db,'publish_search_list',$wArr);
		$this->message = "Published Item(s) have been deleted successfully";
	}
}
?>
