<?php
include_once '../lib/email.php';
include_once '../lib/settings.php';

class publishUser {
	var $id;
	var $email;
	var $password;
	var $status;
	var $expire;
	var $department;
	var $upload;
	var $reset_password;
	var $tempTableList;
	var $searchListIDs;
	var $headerArr;

	function publishUser($email=NULL,$passwd=NULL) {
		$this->tempTableList	= array();
		$this->searchListIDs	= array();
		$this->headerArr		= array();
		if($passwd) {
			$this->email		= $email;
			$this->password 	= $passwd;

			$db_doc = getDbObject('docutron');
			$wArr = array(	'email'		=> $this->email,
							'password'	=> $this->password );
			$pubUserInfo = getTableInfo($db_doc,'publish_user',array(),$wArr,'queryRow');
			$this->id				= $pubUserInfo['id'];
			$this->status			= $pubUserInfo['status'];
			$this->expire			= $pubUserInfo['expiration'];
			$this->department		= $pubUserInfo['department'];
			$this->upload			= $pubUserInfo['upload'];
			$this->reset_password	= $pubUserInfo['reset_password'];
			$this->setPublishSearchListIDs();
			$this->setPublishHeaders();
		} else {
			$this->id			= "";
			$this->email		= "";
			$this->password		= "";
			$this->status		= "";
			$this->expire		= "";
			$this->department	= "";
			$this->upload		= "";
		}
	}

	function setPublishUser($name,$department,$upload) {
		$this->email = $name;
		$db_doc = getDbObject('docutron');
		lockTables($db_doc,array('publish_user'));
		$this->generatePassword();
		
		$sArr = array('COUNT(id)');
		$wArr = array('email' => $name);
		$ct = getTableInfo($db_doc,'publish_user',$sArr,$wArr,'queryOne');
		if(!$ct) {
			$insertArr = array(	'email'			=> $name,
								'password'		=> $this->password,
								'reset_password'=> 1,
								'department'	=> $department,
								'status'		=> "active",
								'upload'		=> (int)$upload,
								'date_added'	=> date('Y-m-d G:i:s'));	
			$db_doc->extended->autoExecute('publish_user',$insertArr);

			$sArr = array('MAX(id)');
			$this->id = getTableInfo($db_doc,'publish_user',$sArr,array(),'queryOne');
		} else {
			unlockTables($db_doc);
			return false;
		}
		unlockTables($db_doc);
		return true;
	}

	function generatePassword() {
		$totalChar = 8; // number of chars in the password
		$salt = "abcdefghijklmnpqrstuvwxyz";
		$salt .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$salt .= "123456789"; 
		srand((double)microtime()*1000000); // start the random generator
		$password=""; // set the inital variable
		for ($i=0;$i<$totalChar;$i++) { // loop and create password
			$password .= substr($salt, rand() % strlen($salt), 1);
		}
		$this->password = md5($password);
		$this->sendMailToPublishUser($password);
	}

	function sendMailToPublishUser($pass) {
		global $DEFS;
		$subject = "Publishing Account Information";
		if( isset( $DEFS[$this->department.'CREDENTIAL_EMAIL_SUBJECT'] ) )
            {$subject = $DEFS[$this->department.'CREDENTIAL_EMAIL_SUBJECT'];
        } else {$subject = "Publishing Account Information";
        }
        if( isset( $DEFS[$this->department.'CREDENTIAL_EMAIL_BODY1'] ) )
            {$body = $DEFS[$this->department.'CREDENTIAL_EMAIL_BODY1']."<br>---------------------------------------------<br>";
        } else {
			$body = "";
        }
		$body .= "User Name: ".$this->email."<br>";
		$body .= "Password: $pass<br>";
		$body .= "---------------------------------------------<br><br>";
		$body .= "Document Link:<br>";
		$body .= portalLink();
        if( isset( $DEFS[$this->department.'CREDENTIAL_EMAIL_BODY2'] ) )
            $body .= "<br><br><br>". $DEFS[$this->department.'CREDENTIAL_EMAIL_BODY2'];
        if( isset( $DEFS[$this->department.'CREDENTIAL_EMAIL_FOOTER'] ) )
            $body .= "<br><br><br>". $DEFS[$this->department.'CREDENTIAL_EMAIL_FOOTER'];
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		mail($this->email,$subject,$body,$headers."From: publishing@{$DEFS['HOST']}");	
	}

	function getDbObject() {
		return getDbObject('docutron');
	}

	function updatePassword($newPassword,$reset=0) {
		$uArr = array(	'password' 			=> $newPassword,
						'reset_password'	=> (int)$reset);
		$wArr = array('id' => (int)$this->id);
		updateTableInfo($this->getDbObject(),'publish_user',$uArr,$wArr);
	}

	function audit($action,$info,$db_obj=NULL) {
		if(!$db_obj) {
			$db_obj = $this->getDbObject();
		}

		if(PEAR::isError($db_obj)) {
			die($db_obj->getMessage());
		}
		$insertArr = array(	"username"	=> $this->email,
							"datetime"	=> date('Y-m-d G:i:s'),
							"info"		=> $info,
							"action"	=> $action );
		$res = $db_obj->extended->autoExecute('audit',$insertArr);
		dbErr($res);
	}

	function checkSetting() {
		return 0;
	}

	function checkSecurity() {
		return 1;
	}
	function getPublishSearch($dep=NULL,$cab=NULL) {
		if(!$dep && !$cab) {
			$dep = key($this->headerArr);
			$cab = key(current($this->headerArr));
		}

		$sArr = array('department','cab','doc_id','file_id','field','term');
		$wArr = array(	"ps_list_id IN(".implode(",",$this->searchListIDs).")",
		//				"department='$dep'",
		//				"cab='$cab'",
						"type='folder_search'");
		$db_doc = $this->getDbObject();
		$searchList = getTableInfo($db_doc,'publish_search_list',$sArr,$wArr,'queryAll');
		$db_doc->disconnect();

		if(!$searchList) {
			return array();
		}

		$searchArr = array();
		foreach($searchList AS $info) {
			extract($info);
			if(!array_key_exists($department,$searchArr)) {
				$searchArr[$department] = array();
			}

			if(!array_key_exists($cab,$searchArr[$department])) {
				$searchArr[$department][$cab] = array();
			}
		
			if($field) {
				$searchArr[$department][$cab][$field] = "'".$term."'";
			} else {
				if(!in_array('doc_id = '.$doc_id,$searchArr[$department][$cab])) {
					$searchArr[$department][$cab][] = 'doc_id = '.(int)$doc_id;
				}
			}
		}
		return $searchArr;
	}

	function setPublishSearchListIDs() {
		$db_doc = $this->getDbObject();
		
		$tArr = array('publish_user_list','publish_search');
		$sArr = array('DISTINCT(ps_list_id)');
		$wArr = array(	"p_id=".(int)$this->id,
						"ps_id=publish_search.id");
		$this->searchListIDs = getTableInfo($db_doc,$tArr,$sArr,$wArr,'queryCol');
		$db_doc->disconnect();
		
	}

	function setPublishHeaders() {
		$sArr = array('department','cab');
		$wArr = array( 	"ps_list_id IN(".implode(",",$this->searchListIDs).")",
						"type='folder_search'");
		$oArr = array("department" => "ASC",'cab' => 'ASC');

		$db_doc = $this->getDbObject();
		$headerList = getTableInfo($db_doc,'publish_search_list',$sArr,$wArr,'queryAll');
		$db_doc->disconnect();
		if(!$headerList) {
			return array();
		}

		foreach($headerList AS $info) {
			if(!array_key_exists($info['department'],$this->headerArr)) {
				$this->headerArr[$info['department']] = array();
			}
		
			if(!array_key_exists($info['cab'],$this->headerArr[$info['department']])) {
				$this->headerArr[$info['department']][$info['cab']] = array();

				$db_dept = getDbObject($info['department']);
				$sArr = array('departmentname');
				$wArr = array('real_name' => $info['cab']);
				$real_name = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryOne');
				$this->headerArr[$info['department']][$info['cab']] = $real_name; 
			}
		}
	}

	function getPublishFolderResults($cab = NULL,$searchVal = NULL) {
		$searchArr = $this->getPublishSearch();

		$completeSearchList = array();
		$tempTable = "";
		$selCab = "";
		foreach($searchArr AS $department => $info) {
			//if(!array_key_exists($department,$this->tempTableList)) {
			//	$this->tempTableList[$department] = array();
			//}
			$completeSearchList[$department] = array();
			foreach($info AS $cabinet => $fields) {
				//if(!array_key_exists($cabinet,$this->tempTableList[$department])) {
				//	$this->tempTableList[$department][$cabinet] = "";
				//}
				//$tempTable = $this->tempTableList[$department][$cabinet];
				$completeSearchList[$department][$cabinet] = array();
				//if(!$tempTable) {
					$db_dept = getDbObject($department);
					$tempTable = createTemporaryTable($db_dept);
					$indiceNames = getCabinetInfo($db_dept,$cabinet);
					if($searchVal) {
						$indArr = array();
						foreach($indiceNames AS $name) {
							$indArr[] = "$name " . LIKE . " '%$searchVal%'"; 
						}
					}
					$destCol = array('result_id');
					$sArr = array('DISTINCT(doc_id)');
					$wArr = array();
					foreach($fields AS $k => $v) {
						if($searchVal) {
							$wArr[] = "($k=$v AND (".implode(" OR ",$indArr)."))";
						} elseif(is_numeric($k)) {
							$wArr[] = $v;
						} else {
							$wArr[] = "($k=$v)";
						}
					}
					$wArr  = array(	implode(" OR ",$wArr),
									"deleted=0");
					insertFromSelect($db_dept,$tempTable,$destCol,$cabinet,$sArr,$wArr);
					$selCab = $cabinet;
					//$this->tempTableList[$department][$cabinet] = $tempTable;

					$tArr = array($tempTable,$selCab);
					$sArr = array('doc_id',implode(",",$indiceNames));
					$wArr = array('result_id=doc_id');
					$searchRes = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryAll');
					$completeSearchList[$department][$selCab] = $searchRes;
				//}
			}
		}
		$db_dept->disconnect();
		return $completeSearchList;
	}

	function getPublishProcessList() {
		$db_doc = $this->getDbObject();
		$tArr = array('publish_user_list','publish_search','publish_search_list');
		$sArr = array('type','publish_search.id','name');
		$wArr = array(	'p_id = '.(int)$this->id,
						'publish_user_list.ps_id = publish_search.id',
						'publish_search.ps_list_id = publish_search_list.ps_list_id',
						"type != 'folder_search'"); 
		$oArr = array('name' => 'ASC');
		$pubSearchInfo = getTableInfo($db_doc,$tArr,$sArr,$wArr,'getAssoc',$oArr,0,0,array(),true);
		if(!$pubSearchInfo) {
			$pubSearchInfo = array();
		}
		return $pubSearchInfo;
	}

	function getPublishedFiles($dep,$cab,$doc_id) {
		$docList = array();
		if(!$dep && !$cab) {
			$dep = key($this->headerArr);
			$cab = key(current($this->headerArr));
		}
		$sArr = array('file_id');
		$wArr = array(	"ps_list_id IN(".implode(",",$this->searchListIDs).")",
						"department='$dep'",
						"cab='$cab'",
						"doc_id=".(int)$doc_id,
						"file_id != 0",
						"type='folder_search'");
		$db_doc = $this->getDbObject();
		$docList = getTableInfo($db_doc,'publish_search_list',$sArr,$wArr,'queryCol');

		$db_dept = getDbObject($dep);
		$sArr = array('document_table_name','id','document_type_name');
		$docTypeInfo = getTableInfo($db_dept,'document_type_defs',$sArr,array(),'getAssoc');
		
		$sArr = array('document_id','document_table_name','subfolder');
		if(!count($docList)) {
			$wArr = array(	'doc_id'	=> (int)$doc_id,
							'filename'	=> 'IS NULL',
							'deleted'	=> 0);
		} else {
			$wArr = array(	'id IN('.implode(",",$docList).')');
		}
		$docInfo = getTableInfo($db_dept,$cab.'_files',$sArr,$wArr,'getAssoc');

		$fileArr = array();
		$sFoldArr = array();
		foreach($docInfo AS $id => $info) {
			$docTypeID = $docTypeInfo[$info['document_table_name']]['id'];
			$sArr = array('document_field_value');
			$wArr = array(	'document_defs_list_id'	=> (int)$docTypeID,
							'document_id'			=> (int)$id);
			$docValues = getTableInfo($db_dept,'document_field_value_list',$sArr,$wArr,'queryCol');

			$docTypeName = $docTypeInfo[$info['document_table_name']]['document_type_name'];
			$fileArr[$info['subfolder']]['name'] = $docTypeName;
			$fileArr[$info['subfolder']]['desc'] = implode(" ",$docValues);
			$sFoldArr[] = "'".$info['subfolder']."'";
		}
		
		if(count($sFoldArr)) {
			$sArr = array('subfolder','parent_filename','id');
			$wArr = array(	'doc_id = '.(int)$doc_id,
							'deleted = 0',
							'filename IS NOT NULL',
							"subfolder IN(".implode(",",$sFoldArr).")");
			$oArr = array('subfolder','ordering');
			$fArr = getTableInfo($db_dept,$cab.'_files',$sArr,$wArr,'getAssoc',array(),0,0,$oArr,true);
		}

		if($fArr) {
			foreach($fArr AS $sfold => $fList) {
				$fileArr[$sfold]['filesArr'] = array();
				foreach($fList AS $fileInfo) {
					$fileArr[$sfold]['filesArr'][$fileInfo['id']] = $fileInfo['parent_filename'];
				}
			}
		}
		return $fileArr;
	}

}
?>
