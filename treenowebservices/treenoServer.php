<?php
// $Id: treenoServer.php 14857 2012-06-15 15:23:17Z cz $
/*
 * This file contains all the treeno web services soap class/functions
 * 
 * Section markers: -dept-, -cab-, -folder-, -document-, -doc type-, -tab-, -file-,
 *		-note-, -versioning-, -search-, -workflow-, -publishing-, -reports-
 * 
 */
require_once '../lib/treenoServices.php';
require_once '../treenowebservices/BasicTypes.php';
require_once '../treenowebservices/DepartmentTypes.php';
require_once '../treenowebservices/CabinetTypes.php';
require_once '../treenowebservices/FolderTypes.php';
require_once '../treenowebservices/DocumentTypes.php';
require_once '../treenowebservices/FileTypes.php';
require_once 'Cache/Lite.php';
require_once '../lib/ldap.php';

// 
class treenoSoapServer {
	var $__dispatch_map = array();
	
	/**
	 * Database connection
	 *
	 * @var MDB2_Driver_Common
	 */
	var $db_doc;

	function treenoSoapServer($db_doc) {
				
		// docutron DB pointer 
		$this->db_doc = $db_doc;
		
		/*
		 * all of the basic class typedefs
		 */

		// simpleReturn type (in BasicTypes.php)
		$this->__typedef['{urn:TreenoWebServices}simpleReturn'] = array (
			'success'		=> 'boolean',
			'message'		=> 'string'
		);

		// array of strings
		$this->__typedef['StringList'] = array (
			array ( 'item'	=> 'string' )
		);
		
		// array of ints
		$this->__typedef['integerList'] = array (
			array ( 'item' 		=> 'int' )
		);
		
		// list of cabinets type (in BasicTypes.php)
		$this->__typedef['{urn:TreenoWebServices}IndexInfo'] = array (
				'key'		=> 'string',
				'value'		=> 'string'
		);
		
		
		/*
		 * -dept-
		 */
		
		// list departments type (in CabinetTypes.php)
		// $this->__typedef['DepartmentItem'] = array (
			// 'id'			=> 'int',
			// 'displayName'	=> 'string',
			// 'internalName'	=> 'string',
			// 'accessType'	=> 'string'
		// );
		// $this->__typedef['DepartmentList'] = array (
			// array (
				// 'item'		=> '{urn:TreenoWebServices}DepartmentItem'
			// ),
		// );
		
		/*
		 * -cab-
		 */	
		
		// list of cabinets type (in CabinetTypes.php)
		// $this->__typedef['CabinetList'] = array (
			// array ( 'displayName'	=> 'string' )
		// );
		
		//cabinet indecies (in CabinetTypes.php)
		// $this->__typedef['CabinetResultSet'] = array (
			// array (
				// 'folder'	=> '{urn:TreenoWebServices}CabinetFolder'
			// ),
		// );
		
		
		/*
		 * -folder-
		 */
		
		// list of cabinets type (in FolderTypes.php)
		$this->__typedef['FolderList'] = array (
			array (
				'item'		=> 'int'
			),
		);
		
		// list of cabinets type (in FolderTypes.php)
		$this->__typedef['FolderIndex'] = array (
			array (
				'indices'	=> '{urn:TreenoWebServices}IndexInfo'
			),
		);
		
		
		/*
		 * -document-
		 */
		
		// list of document definitions types (in DocumentTypes.php)
		$this->__typedef['DefinitionInfo'] = array (
			'index'	=> 'string',
			'value'	=> 'string'
		);
		$this->__typedef['DocIndiciesList'] = array (
			array (
				'item'		=> '{urn:TreenoWebServices}DefinitionInfo'
			),
		);
		
		$this->__typedef['DefinitionName'] = array (
			'index_name'		=> 'string',
			'definition_list'		=> '{urn:TreenoWebServices}StringList'
		);
		$this->__typedef['DefinitionNameList'] = array (
			array (
				'item'		=> '{urn:TreenoWebServices}DefinitionName'
			),
		);
/*		
		$this->__typedef['DocumentDefInfo'] = array (
			'docID'			=> 'int',
			'realName'		=> 'string',
			'arbName'		=> 'string',
			'indices'		=> '{urn:TreenoWebServices}DefinitionInfo',
			'definitions'	=> '{urn:TreenoWebServices}DefinitionName'
		);
*/		

		$this->__typedef['DocumentDefInfo'] = array (
			'documentTypeID'			=> 'int',
			'internalName'		=> 'string',
			'displayName'		=> 'string',
			'indicies'		=> '{urn:TreenoWebServices}DocIndiciesList',
			'docTypeIndexDefs'	=> '{urn:TreenoWebServices}DefinitionNameList'
		);
		$this->__typedef['DocumentDefList'] = array (
			array (
				'item'		=> '{urn:TreenoWebServices}DocumentDefInfo'
			),
		);
		
		/*
		 * -tab-
		 */
		
		$this->__typedef['TabInfo'] = array (
			'parent_filename'	=> 'string',
			'id'				=> 'string',
			'file_size'			=> 'int',
			'subfolder'			=> 'string'
		);
		$this->__typedef['TabList'] = array (
			array (
				'item'			=> '{urn:TreenoWebServices}TabInfo'
			),
		);

		$this->__typedef['AddTabInfo'] = array (
			'subfolderID'		=> 'int',
			'realTabName'		=> 'string'
		);
		$this->__typedef['AddTab'] = array (
			array (
				'item'			=> '{urn:TreenoWebServices}AddTabInfo'
			),
		);
		
		/*
		 * -file-
		 */
	
		$this->__typedef['FileInfo'] = array (
			'fileID'			=> 'int',
			'fileName'			=> 'string',
			'subfolder'			=> 'string'
		);
		$this->__typedef['FileList'] = array (
			array (
				'item'			=> '{urn:TreenoWebServices}FileInfo'
			),
		);
		
		$this->__typedef['CabinetItem'] = array (
			'index'	=> 'string',
			'value'	=> 'string'
		);
		
		$this->__typedef['autoCompleteList'] = array (
			array (
				'item' => '{urn:TreenoWebServices}CabinetItem'
			)			
		);

		
		/*
		 * -versioning-
		 */
		

		/*
		 * -search-
		 */
		
		
		/*
		 * -workflow-
		 */
		
		
		/*
		 * -publishing-
		 */
		
		
		/*
		 * -reports-
		 */
		

		ksort($this->__typedef);

		// set up the dispatch map (function mirrors)
		$arr = array (
			'md5String' => array (
				'in' => array (
					'str'			=> 'string',
				),
				'out' => array(
					'simpleOutput'	=> '{urn:TreenoWebServices}simpleReturn',
				),
			),

			'tripleDesEncrypt' => array (
				'in' => array (
					'str'			=> 'string',
				),
				'out' => array(
					'simpleOutput'	=> '{urn:TreenoWebServices}simpleReturn',
				),
			),
			
			'Login' => array (
				'in'	=> array (
					'userName'		=> 'string',
					'md5Pass'		=> 'string',
				),
				'out'	=> array (
					'simpleOutput'	=> '{urn:TreenoWebServices}simpleReturn',
				),
			),
			
			/*
			 * -dept-
			 */
			
			'GetDepartmentList' => array (
				'in' => array (
					'passKey'	=> 'string',
				),
				'out' => array (
					'return'	=> '{urn:TreenoWebServices}StringList',
				)			
			),		
			
			/*
			 * -cab-
			 */
			
			'GetCabinetList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
				),
				'out' => array (
					'return'		=> '{urn:TreenoWebServices}StringList',
				)
			),

			/*
			 * -folder-
			 */
			
			'GetFolderList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
				),
				'out' => array (
					'return'		=> '{urn:TreenoWebServices}FolderList',
				)
			),
			
			'AddFolder' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'indiceArr'		=> '{urn:TreenoWebServices}FolderIndex',
				),
				'out' => array (
					'return'		=> 'int',
				)
			),
			
			'EditFolder' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
					'indiceArr'		=> '{urn:TreenoWebServices}FolderIndex',
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),

			'DeleteFolder' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'force'			=> 'boolean',
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),
			
			/*
			 * -document-
			 */
			
			'GetDocumentList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
				),
				'out' => array (
					'return'		=> '{urn:TreenoWebServices}StringList',
				)
			),
					
			'GetDocumentDefinitionList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'filtered'		=> 'boolean',
				),
				'out' => array (
					'return'	=> '{urn:TreenoWebServices}DocumentDefList',
				)
			),
			
			'AddDocument' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'documentName'	=> 'string',
					'indexes'		=> '{urn:TreenoWebServices}DocIndiciesList',
				),
				'out' => array (
					'return'	=> 'int',
				)
			),

			'EditDocument' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'documentID'	=> 'int',
					'documentName'	=> 'string',
					'indexes'		=> '{urn:TreenoWebServices}DefinitionInfo',
				),
				'out' => array (
					'return'	=> 'boolean',
				)
			),
			
			'DeleteDocument' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderName'	=> 'string',
					'documentID'	=> 'int',
					'force'			=> 'boolean',
				),
				'out' => array (
					'return'	=> 'boolean',
				)
			),
			
			/*
			 * -doc type-
			 */
			
			'IsDocumentView' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
				),
				'out' => array (
					'return'	=> 'boolean',
				)
			),

			'GetDocumentTypeList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
				),
				'out' => array (
					'return'		=> '{urn:TreenoWebServices}DocumentDefList',
				)
			),

			'GetDocTypeDetails' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
				),
				'out' => array (
					'return'	=> '{urn:TreenoWebServices}DocumentDefList',
				)
			),

			/*
			 * -tab-
			 */
			
			'GetTabList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
				),
				'out' => array (
					'return'	=> '{urn:TreenoWebServices}StringList',
				)
			),

			'AddTab' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
					'tabname'		=> 'string',
					'saved'			=> 'boolean',
					'mkdir'			=> 'boolean',
				),
				'out' => array (
					'return'	=> 'int',
				)
			),
			
			'EditTab' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
					'OldTabName'	=> 'string',
					'NewTabName'	=> 'string',
					'saved'			=> 'boolean',
				),
				'out' => array (
					'return'	=> 'boolean',
				)
			),

			/*
			 * -file-
			 */
			
			'GetFileList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'includeEmptyTab'	=> 'boolean',
				),
				'out' => array (
					'return'	=> '{urn:TreenoWebServices}FileList',
				)
			),

			'AddFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'tabID'			=> 'int',
					'fileNameFull'	=> 'string',
				),
				'out' => array (
					'return'	=> 'int',
				)
			),			
			
			'EditFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'tabID'			=> 'int',
					'fileID'		=> 'int',
					'newFileName'	=> 'string',
			),
				'out' => array (
					'return'	=> 'boolean',
				)
			),	
			
			'DeleteFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'fileID'		=> 'int',
			),
				'out' => array (
					'return'	=> 'boolean',
				)
			),
			
			'DownloadFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'tab'			=> 'string',
					'fileNames'		=> '{urn:TreenoWebServices}StringList',
					'destPath'		=> 'string',
					'format'		=> 'string',
			),
				'out' => array (
					'simpleOutput'	=> '{urn:TreenoWebServices}simpleReturn',
				)
			),
			
			/*
			 * -note-
			 */
			
			'GetFileNotes' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'tabID'			=> 'int',
					'fileName'		=> 'string'
			),
				'out' => array (
					'return'	=> 'string',
				)
			),
			
			'AddFileNote' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'folderID'		=> 'int',
					'tabID'			=> 'int',
					'fileName'		=> 'string',
					'note'			=> 'string'
			),
				'out' => array (
					'return'	=> 'boolean',
				)
			),		

			/*
			 * -versioning-
			 */
			'CheckOutFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'fileID'		=> 'int'
			),
				'out' => array (
					'return'	=> 'string',
				)
			),
			
			'CancelCheckout' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'fileID'		=> 'int'
			),
				'out' => array (
					'return'	=> 'boolean',
				)
			),
			
			'CheckInFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'fileID'		=> 'int',
					'fileName'		=> 'string',
					'encFileData'	=> 'string'
					),
				'out' => array (
					'return'	=> 'string',
				)
			),
				
			'GetCurrentVersion' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
					'subFolder'		=> 'string',
					'fileName'		=> 'string'
					),
				'out' => array (
					'return'	=> 'string',
				)
			),		
	
			'GetVersionList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
					'subFolder'		=> 'string',
					'fileName'		=> 'string'
					),
				'out' => array (
					'return'	=> '{urn:TreenoWebServices}FileList',
				)
			),
			
			'changeFileVersion' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
					'subFolder'		=> 'string',
					'fileName'		=> 'string',
					'oldVersion'	=> 'string',
					'newVersion'	=> 'string'
					),
				'out' => array (
					'return'	=> 'boolean',
				)
			),
	
			/*
			 * -search-
			 */
			
			'searchTopLevel' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'search'		=> 'string'
					),
				'out' => array (
					'return'	=> '???',
				)
			),

			'SearchCabinetIndicies' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
 					'search'		=> 'string'
					),
				'out' => array (
					'return'	=> '???',
				)
			),

			'SearchDocumentTypes' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
 					'docType'		=> 'string',
 					'search'		=> 'string',
 					'tempTable'		=> 'string'
					),
				'out' => array (
					'return'	=> '???',
				)
			),
		
			'SearchDocumentInFolder' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
 					'docType'		=> 'string',
 					'search'		=> 'string',
 					'docID'			=> 'int'
					),
				'out' => array (
					'return'	=> '???',
				)
			),
	
		'StartWF' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
 					'wfDefsID'		=> 'int',
 					'wfOwner'		=> 'string',
 					'docID'			=> 'int',
 					'tabID'			=> 'int'
 					),
				'out' => array (
					'return'	=> 'boolean',
				)
			),
			
		/*
		 * -workflow-
		 */
					
					
		/*
		 * -publishing-
		 */
		
		
		/*
		 * -reports-
		 */
			
		'IsAutoComplete' => array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
				),
				'out'	=> array (
					'return'		=> 'boolean',
				)
			),
			
		'GetAutoComplete' => array (
				'in'	=> array (
					'passKey'			=> 'string',
					'department'		=> 'string',
					'cabinetID'			=> 'int',
					'autoCompleteTerm'	=> 'string',
				),
				'out'	=> array (
					'return'		=> '{urn:TreenoWebServices}autoCompleteList',
				)
			),
			
			
		);
		


		ksort($arr);
		$this->__dispatch_map = $arr;
	}

	function __dispatch($methodName) {
		if(isset($this->__dispatch_map[$methodName])) {
			return $this->__dispatch_map[$methodName];
		}
		return NULL;
	}
	
	function md5String($string)
	{
		$ret = new simpleReturn(true, md5($string));
		return new SOAP_Value('simpleOutput', '{urn:TreenoWebServices}simpleReturn', $ret);
	}

	function tripleDesEncrypt($string)
	{
		$ret = new simpleReturn(true, tdEncrypt($string));
		return new SOAP_Value('simpleOutput', '{urn:TreenoWebServices}simpleReturn', $ret);
	}
	
	/**
	*  Description: used to log into Treeno (services). The generated passcode is good for 24 hours or until this login object is destroyed
	* @param string $userName users login name
	* @param string $md5Pass users password
	* @return int|bool index key to web services class; failure = false
	* @example ../examples/login.php
	*/
	function Login($userName, $md5Pass) {
		global $DEFS;
		
		try {
			$userName = strtolower($userName);
			$usersInfo = getTableInfo($this->db_doc, 'users', array(), array('username' => $userName));
//			$key = decrypt($userName);
			
			if($row = $usersInfo->fetchRow()) {
				if ($row['ldap_id'] != 0) {
					// ldap user
					//error_log("Login() tdEncrpt passwoer: ".$md5Pass);
					$myPass = tdDecrypt($md5Pass);
					//error_log("Login() plaintext passwoer: ".$myPass);
					if(checkLDAPPassword($this->db_doc, $row['ldap_id'], $userName, $myPass)) {
						$key = $userName.','.(time() + 86400);
						$message = weakEncrypt($key);
						$retVal = true;
					} else {
						$message = 'Login Incorrect';
						$retVal = false;
					}
				} else {
					// non-ldap user
					if(strtoupper($row['password']) == strtoupper($md5Pass)) {
						$key = $userName.','.(time() + 86400);
						$message = weakEncrypt($key);
						$retVal = true;
					} else {
						$message = 'Login Incorrect';
						$retVal = false;
					}
				}
			} else {
				$message = 'Login Incorrect';
				$retVal = false;
			}
			$ret = new simpleReturn($retVal, $message);
			return new SOAP_Value('simpleOutput', '{urn:TreenoWebServices}simpleReturn', $ret);

		} catch(Exception $e) {
			return new SOAP_Value( 'return', 'boolean', 'false' );
		}
			
	}	// end login()
	
//	/**
//	 * Description: calls the class destructor
//	 * @param none
//	 * @return none
//	 */
//	function Logout() {
//		unset(???);  // calls the class object destructor
//	}

	/*
	 * -dept-
	 */
	
	/**
	*  Description: used to get a list of available departments. AccessType is regular user, dept admin, admin
	* @param int cached web services class object key
	* @return array|bool contains realName => arbName; failure = SOAP_FAULT
	* @example 
	*/
	public function GetDepartmentList($passKey) {
		// treenoServices call
		$retArr  = getDepartmentList($passKey);

		if($retArr['ret'] === true) {
			return new SOAP_Value('return', '{urn:TreenoWebServices}StringList', $retArr['data']);
		} else {
			// error
			return new SOAP_FAULT($retArr['msg']);
		}

	}	// end GetDepartmentList()
	
	/*
	 * -cab-
	 */
	
	/**
	 *  Description: looks up a list of cabinets and their access level (RW,RO or None) for that user
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @return array|bool string cabinetName; failure = SOAP_FAULT
	 * @example 
	 */
	public function GetCabinetList($passKey, $deptDisplayName) {
		// treenoServices call
		$cabListArr = getCabinetList($passKey, $deptDisplayName);
		
		if($cabListArr['ret'] == true) {
			$cabList = array ();
			foreach($cabListArr['data'] as $cab) {
				// only capture the displayed cabinet name
				$cabList[] = new CabinetInfo($cab['departmentname']);
			}
//			return new SOAP_Value('return', '{urn:TreenoWebServices}StringList', $cabList);
			return new SOAP_Value('return', '{urn:TreenoWebServices}StringList', $cabListArr['data']);
		} else {
			return new SOAP_FAULT($cabListArr['msg']);
		}
	}
	
	/*
	 * -folder-
	 */
	
	/**
	 *  Description: get a list of all folders in a specific cabinet
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @return array 'ret' = true, 'data' = array of folderIDs; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetFolderList($passKey, $deptDisplayName, $cabDisplayName) {
		// treenoServices call
		$folderListArr = getFolderList($passKey, $deptDisplayName, $cabDisplayName);
		
		if($folderListArr['ret'] == true) {
			$folderList = array ();
			foreach($folderListArr['data'] as $docID) {
				$folderList[] = new FolderID($docID['doc_id']);
			}
			return new SOAP_Value('return', '{urn:TreenoWebServices}FolderList', $folderList);
		} else {
			return new SOAP_FAULT($folderListArr['msg']);
		}
	}

	/**
	 *  Description: add a new folder to a specific cabinet
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param array $indiceArr associative array of field keys and values for the new folder
	 * @return array 'ret' = true, 'data' = int docID; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function AddFolder($passKey, $deptDisplayName, $cabDisplayName, $soapArr) {
		$indiceArr = $this->ConvertArray($soapArr);
		
		// treenoServices call
		$retArr = addFolder($passKey, $deptDisplayName, $cabDisplayName, $indiceArr);
		
		if($retArr['ret'] == true) {
			$docID = $retArr['data'];
			return new SOAP_Value('return', 'int', $docID);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}

	/**
	 *  Description: edit field values for a specific folder. 
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $docID doc_id of the specific folder we are editing
	 * @param array $indiceArr associative array of field keys and values for the folder
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function EditFolder($passKey, $deptDisplayName, $cabDisplayName, $docID, $soapArr) {
		$indiceArr = $this->ConvertArray($soapArr);
		
		// treenoServices call
		$retArr = editFolder($passKey, $deptDisplayName, $cabDisplayName, $docID, $indiceArr);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description markes a specific folder as removed. The force parameter must be used to remove a folder that contains files.
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the source cabinet
	 * @param int $folderID folder to be removed
	 * @param bool $force do we want to force the removal of a folder that contains files (default = false)
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function deleteFolder($passKey, $deptDisplayName, $cabDisplayName, $folderID, $force=false) {
		// treenoServices call
		$retArr = deleteFolder($passKey, $deptDisplayName, $cabDisplayName, $folderID, $force);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/*
	 * -document-
	 */
	
	/**
	 *  Description: get a list of all of the document types associated with a specific folder id.
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $docID folder id from which to get the documents
	 * @return array 'ret' = true, 'data' = array of document IDs; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetDocumentList($passKey, $deptDisplayName, $cabDisplayName, $docID) {
		// treenoServices call
		$retArr = getDocumentList($passKey, $deptDisplayName, $cabDisplayName, $docID);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', '{urn:TreenoWebServices}StringList', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: get the list of document type (pre-configured) index definition values. 
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param string $filtered document definiations are on a department level, but you can choose to filter on the login cabinet (default = false)
	 * @return array 'ret' = true, 'data' = array of index field names (realName, arbName, indicies, docTypeIndexDefs); 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetDocumentDefinitionList($passKey, $deptDisplayName, $cabDisplayName, $filtered=false) {
		// treenoServices call
		$retArr = getDocumentDefinitionList($passKey, $deptDisplayName, $cabDisplayName, $filtered);
		
		if($retArr['ret'] == true) {
			$docDefList = array();
			foreach($retArr['data'] as $docDef) {
				$docDefList[] = new DocumentDefsList($docDef['docID'],
													$docDef['realName'],
													$docDef['arbName'],
													$docDef['indices'],
													$docDef['definitions']
													);
			}
			return new SOAP_Value('return', '{urn:TreenoWebServices}DocumentDefList', $docDefList);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: adds a new document to a folder
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID doc id for the folder to add this document to
	 * @param string $documentName document of specific type to create (doc type must already exist)
	 * @param array $indexes associative array of indexes and values
	 * @return array 'ret' = true, 'data' = subFolderID; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function AddDocument($passKey, $deptDisplayName, $cabDisplayName, $folderID, $documentName, $indexes) {
		$indexArr = array();
		foreach($indexes as $soapElem) {
			$indexArr[$soapElem->index] = $soapElem->value;
		}
		// treenoServices call
		$retArr = addDocument($passKey, $deptDisplayName, $cabDisplayName, $folderID, $documentName, $indexArr);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'int', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: modify the index values of a specific document in a folder
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $documentID ID of the document to modify (document_id field from <cab>_files)
	 * @param array $indexes an associative array of index names and values
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	  * @example 
	 */
	public function EditDocument($passKey, $deptDisplayName, $cabDisplayName, $documentID, $documentName, $indexes) {
		// treenoServices call
		$indexArr=array();
		$indexArr[$indexes->index]=$indexes->value;
		$retArr = editDocument($passKey, $deptDisplayName, $cabDisplayName, $documentID, $documentName, $indexArr);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: delete a document in a folder. 
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param string $folderName 
	 * @param int $documentID ID of the document to delete
	 * @param bool $force set to true if you want to force the deletion of a document that contains attached files (default = false)
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function DeleteDocument($passKey, $deptDisplayName, $cabDisplayName, $folderName, $documentID, $force) {
		// treenoServices call
		$retArr = editDocument($passKey, $deptDisplayName, $cabDisplayName, $folderName, $documentID, $force);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/*
	 * -doc type-
	 */

	/**
	 *  Description determines whether a cabinet is in document type view or folder view
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function IsDocumentView($passKey, $deptDisplayName, $cabDisplayName=NULL) {
		// treenoServices call
		$retArr = isDocumentView($passKey, $deptDisplayName, $cabDisplayName);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: gets the document Types for a department must be >=dept admin
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @return array 'ret' = true, 'data' = array of documentTypeID, docTypeDisplayName, 
	 * 		document_type_name, docTypeInternalName, indices, docTypeIndexDefs for each doc type; 
	 * 		'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetDocumentTypeList($passKey, $deptDisplayName, $cabDisplayName=NULL) {
		// treenoServices call
		$retArr = getDocumentTypeList($passKey, $deptDisplayName, $cabDisplayName);
		
		if($retArr['ret'] == true) {
			$docTypeList = array();
			foreach($retArr['data'] as $docType) {
				$retIndArr = array();
				foreach($docType['indices'] AS $index => $value) {
					$retIndArr[] = new DefinitionInfo($index, $value);
				}
				$retIndArr = new SOAP_Value('indicies', '{urn:TreenoWebServices}DocIndiciesList', $retIndArr);
				$docDefArr = array ();
				foreach($docType['definitions'] as $realName => $listArr) {
					$docDefArr[] = new DefinitionName($realName, $listArr); 
				}
				$soapDefArr = new SOAP_Value('docTypeIndexDefs', '{urn:TreenoWebServices}DefinitionNameList', $docDefArr);

				$docTypeList[] = new DocumentDefsList($docType['documentTypeID'],
													  $docType['displayName'],
													  $docType['internalName'],
													  $retIndArr,
													  $soapDefArr
													  );
			}
    usort($docTypeList, create_function('$a,$b', "return strcmp(\$a->displayName,\$b->displayName);")); 
			
			return new SOAP_Value('return', '{urn:TreenoWebServices}DocumentDefList', $docTypeList);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}

	/**
	*  Description: details of a specific document type 
	* @param string $passKey login passKey
	* @param string $deptDisplayName name of the department
	* @param string $cabDisplayName name of the cabinet
	* @param int $folderID
	* @return array 'ret' = true, 'data' = array of doc type details: (int tabID, string documentName, 
	* 		string documentType, array(string indexName, string displayName, string value))
	* 		'ret' = false, 'msg' = error message
	* @example 
	*/
	public function GetDocTypeDetails($passKey, $deptDisplayName, $cabDisplayName, $folderID) {
		// treenoServices call
		$retArr = getDocTypeDetails($passKey, $deptDisplayName, $cabDisplayName, $folderID);
		
		if($retArr['ret'] == true) {
			$docTypeList = array();
			foreach($retArr['data'] as $docType) {
//new stuff
				$retIndArr = array();
				foreach($docType['indices'] AS $index => $value) {
					$retIndArr[] = new DefinitionInfo($index, $value);
				}
				$retIndArr = new SOAP_Value('indicies', '{urn:TreenoWebServices}DocIndiciesList', $retIndArr);
				$docDefArr = array ();
				foreach($docType['definitions'] as $realName => $listArr) {
					$docDefArr[] = new DefinitionName($realName, $listArr); 
				}
				$soapDefArr = new SOAP_Value('docTypeIndexDefs', '{urn:TreenoWebServices}DefinitionNameList', $docDefArr);

				$docTypeList[] = new DocumentDefsList($docType['documentTypeID'],
													  $docType['displayName'],
													  $docType['internalName'],
													  $retIndArr,
													  $soapDefArr
													  );
//****************************
		  }
			return new SOAP_Value('return', '{urn:TreenoWebServices}DocumentDefList', $docTypeList);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}

	/**
	 *  Description: gets the complete list of document types for a department - FUTURE
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param string $docType
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function AddDocumentType($passKey, $deptDisplayName, $cabDisplayName, $docType) {
		// treenoServices call
		$retArr = addDocumentType($passKey, $deptDisplayName, $cabDisplayName, $docType);
		
		if($retArr['ret'] == true) {
			$docTypeList = array();
			foreach($retArr['data'] as $docType) {
				$docTypeList[] = new DocumentDefsList($docType['tabID'],
													  $docType['documentName'],//realname
													  $docType['documentName'],//arb name
													  $docType['indices'],
													  $docType['documentType']
													  );
			}
			return new SOAP_Value('return', '{urn:TreenoWebServices}DocumentDefList', $docTypeList);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/*
	 * -tabs-
	 */
	
	/**
	 *  Description: list of 'saved' tabs for a cabinet that the User has access too.
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param bool $force do we want to force the removal of a folder that contains files (default = false)
	 * @return array 'ret' = true, 'data' = array of tabs found; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	function GetTabList($passKey, $deptDisplayName, $cabDisplayName) {
		// init
		$retArr = array('ret'=>false);
		
		// treenoServices call
		$retArr = getTabList($passKey, $deptDisplayName, $cabDisplayName);

		if($retArr['ret'] == true) {
			$tabList = array();
			foreach($retArr['data'] as $tabName) {
				$tabList[] = $tabName;
			}
			return new SOAP_Value('return', '{urn:TreenoWebServices}StringList', $tabList);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: creates a tab in folder within a classical view cabinet.
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $docID for the folder to create tab in
	 * @param string $tabName name of the tab to be added to the given folder
	 * @param bool $saved add as a saved tab (default = false)
	 * @param bool $mkdir should we create the directory if it doesn't exist (default = true)
	 * @return array 'ret' = true, 'data' = int subfolderID, string realTabName (physical tab name on the file system); 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function AddTab($passKey, $deptDisplayName, $cabDisplayName, $docID, $tabName, $saved=false, $mkdir=true) {
		//cz
		error_log("AddTab - dept: ".$deptDisplayName.", cab: ".$cabDisplayName.", docID: ".$docID.", tabName: ".$tabName.", saved: ".$saved.", madir: ".$mkdir );

		// treenoServices call
		$retArr = addTab($passKey, $deptDisplayName, $cabDisplayName, $docID, $tabName, $saved, $mkdir);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'int', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: allows you to change the name of a tab or to toggle its saved/unsaved state.
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $docID for the folder to create tab in
	 * @param string $oldTabName original or existing name of the tag
	 * @param string $newTabName new name of the tab (use the $oldTabName if you don't want to  change the name
	 * @param bool $saved set either the tab is supported to be saved or not (default is not saved)
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function EditTab($passKey, $deptDisplayName, $cabDisplayName, $docID, $oldTabName, $newTabName, $saved) {
		// treenoServices call
		$retArr = EditTab($passKey, $deptDisplayName, $cabDisplayName, $docID, $oldTabName, $newTabName, $saved);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/*
	 * -file-
	 */

	/**
	 *  Description: list of all of the files in a specified folder (all tabs/documents). 
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param string $folderID document ID of the folder to look up files
	 * @return array 'ret' = true, 'data' = array of file id and name pairs; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetFileList($passKey, $deptDisplayName, $cabDisplayName, $folderID, $includeEmptyTab = false) {
		// treenoServices call
		$retArr = getFileList($passKey, $deptDisplayName, $cabDisplayName, $folderID, $includeEmptyTab );
		
		if($retArr['ret'] == true) {
			$fileList = array();
			foreach($retArr['data'] as $fileInfo) {
				//error_log(print_r($fileInfo, true));
				if(is_null($fileInfo['filename']) == false || is_null($fileInfo['subfolder']) == false) {
				$fileList[] = new FileInfo($fileInfo['id'],
										   $fileInfo['filename'], $fileInfo['subfolder']
										   );
				}
			}
			return new SOAP_Value('return', '{urn:TreenoWebServices}FileList', $fileList);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
		
	/**
	 *  Description: add a file in a specified folder/tab. 
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID ID number of the folder to contains the tab that contains the file
	 * @param int $tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
	 * @param string $fileNameFull full path to the file to be uploaded 
	 * @return array 'ret' = true, 'data' = fileID added; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function AddFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileNameFull) {
		// treenoServices call
		$retArr = addFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileNameFull);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'int', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: rename a file in a specified folder/tab. 
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID name of the folder to contain file
	 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
 	 * @param string $fileId id of the file to be renamed
	 * @param string $newFileName new name (full path not required)(NULL = main tab in classical view)
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function EditFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileID, $newFileName) {
		// treenoServices call
		$retArr = editFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileID, $newFileName);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: marks a file from a specific folder/tab for deletion.
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID folder containing the file
	 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
	 * @param string $fileName file to be removed
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function DeleteFile($passKey, $deptDisplayName, $cabDisplayName, $fileID) {
		// treenoServices call
		$retArr = deleteFile($passKey, $deptDisplayName, $cabDisplayName, $fileID);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: downloads a file(s) from a specific folder/tab in a specified format (native, PDF, ZIP).
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID folder containing the file
	 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
	 * @param array $fileNamesArr file(s) to be downloaded
	 * @param string $destPath on disk location to place the file(s)
	 * @param string $format format of the file(s) (native, PDF, ZIP)
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function DownloadFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tab, $fileNamesArr, $destPath, $format) {
		// treenoServices call
		$retval = downloadFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tab, $fileNamesArr, $destPath, $format);
		$message = "File created";

		if($retval['ret'] == true) 
		{		
			$message = $retval['data'];
			$retVal = true;	
			
			$ret = new simpleReturn($retVal, $message);
			return new SOAP_Value('simpleOutput', '{urn:TreenoWebServices}simpleReturn', $ret);
			} 
		else 
		{
			return new SOAP_FAULT($retval['msg']);
		}
	}
	
	/*
	 * -note
	 */
	
	/**
	 *  Description: get note(s) from a specific file
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID folder containing the file
	 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
	 * @param string $fileName file to be removed
	 * @return array 'ret' = true, 'data' = $notes (notes for file string); 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetFileNotes($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileName) {
		// treenoServices call
		$retArr = getFileNotes($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileName);
		

		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'string', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: add note to a specific file
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID folder containing the file
	 * @param int tabID ID number of the tab that will contain the file (pass a '0' if main tab	within document view)
	 * @param string $fileName file to be removed
	 * @param string $note string message to be attached to the file
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function AddFileNote($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileName, $note) {
		// treenoServices call
		$retArr = addFileNote($passKey, $deptDisplayName, $cabDisplayName, $folderID, $tabID, $fileName, $note);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}

	/*
	 * -versioning-
	 */

	/**
	 *  Description: check out a specific file for versioning
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $fileID file to be checked out
	 * @return array 'ret' = true, 'data' = string containing file data; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function CheckOutFile($passKey, $deptDisplayName, $cabDisplayName, $fileID) {
		// treenoServices call
		$retArr = checkOutFile($passKey, $deptDisplayName, $cabDisplayName, $fileID);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'string', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: cancel the check out a specific file for versioning
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $fileID file to be checked out
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function CancelCheckout($passKey, $deptDisplayName, $cabDisplayName, $fileID) {
		// treenoServices call
		$retArr = cancelCheckout($passKey, $deptDisplayName, $cabDisplayName, $fileID);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}	
	
	/**
	 *  Description: check in a specific file for versioning
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $folderID folder containing the file
	 * @param string $fileName full path to the file to be checked in
	 * @param string $encFileData encoded file contents
	 * @return array 'ret' = true, 'data' version number; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function CheckInFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $fileName, $encFileData) {
		// treenoServices call
		$retArr = checkInFile($passKey, $deptDisplayName, $cabDisplayName, $folderID, $fileName, $encFileData);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'string', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: return the current version of a specific file
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $doc_id folder that we are working with
	 * @param string $subFolder tab within folder (exactly as in the DB)
	 * @param string $fileName file we are looking for
	 * @return array 'ret' = true, 'data' current version number; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetCurrentVersion($passKey, $deptDisplayName, $cabDisplayName, $doc_id, $subFolder, $fileName) {
		// treenoServices call
		$retArr = getCurrentVersion($passKey, $deptDisplayName, $cabDisplayName, $doc_id, $subFolder, $fileName);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'string', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: return a list of all version numbers for a specific file
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $docID document type id for file that we are working with
	 * @param string $subFolder tab within folder (exactly as in the DB)
	 * @param string $fileName file we are looking for
	 * @return array 'ret' = true, 'data' list all version numbers; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function GetVersionList($passKey, $deptDisplayName, $cabDisplayName, $docID, $subFolder, $fileName) {
		// treenoServices call
		$retArr = getVersionList($passKey, $deptDisplayName, $cabDisplayName, $docID, $subFolder, $fileName);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', '{urn:TreenoWebServices}StringList', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: change the version of a specific file
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the cabinet
	 * @param int $docID document type id for file that we are working with
	 * @param string $subFolder tab within folder
	 * @param string $fileName file we are looking for
	 * @param string $oldVersion the new version number for this file (format: <major>.<minor>)
	 * @param string $newVersion the new version number for this file (format: <major>.<minor>)
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function ChangeFileVersion($passKey, $deptDisplayName, $cabDisplayName, $docID, $subFolder, $fileName, $oldVersion, $newVersion) {
		// treenoServices call
		$retArr = changeFileVersion($passKey, $deptDisplayName, $cabDisplayName, $docID, $subFolder, $fileName, $oldVersion, $newVersion);
		
		if($retArr['ret'] == true) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}

	/*
	 * -search-
	 */

	/**
	 *  Description: searches for $search in every field and cabinet within a department.
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $search string to search on
	 * @return array 'ret' = true, 'data' = array containing an array of table rows and a count (string $tempTable, int $numOfResults); 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function searchTopLevel($passKey, $deptDisplayName, $searchStr) {
		// treenoServices call
		$retArr = searchTopLevel($passKey, $deptDisplayName, $searchStr);
		
		if($retArr['ret'] == true) {
			//ALS: exchange this return with a class call
			return new SOAP_Value('return', '{urn:TreenoWebServices}SearchTopInfo', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: search cabinet by index value
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the department
	 * @param array string $searchArr associative array to search by ($columnName => string $searchValue)
	 * @return array 'ret' = true, 'data' = array('tempTable'=>$tempTable( table_id | result_id (doc_id),'resultCount'=>$resultCount); 'ret' = false, 'msg' = error message
	 * @example array([tempTable]=>mpdiavmtcrigge, [resultCount]=>2)
	 */
	public function SearchCabinetIndicies($passKey, $deptDisplayName, $cabDisplayName, $searchArr) {
		// treenoServices call
		$retArr = searchCabinetIndicies($passKey, $deptDisplayName, $cabDisplayName, $searchArr);
		
		if($retArr['ret'] == true) {
			//ALS: exchange this return with a class call
			return new SOAP_Value('return', '{urn:TreenoWebServices}SearchTopInfo', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: this is the document search in the gui can include prior results
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the department
	 * @param $docTypeDisplayName 
	 * @param $searchArr 
	 * @param $tempTable 
	 * @return array 'ret' = true, 'data' = array(table_id | document_field_value_list_id | cabInternalName | doc_id | file_id ), $count; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function SearchDocumentTypes($passKey, $deptDisplayName, $cabDisplayName=NULL, $docTypeDisplayName, $searchArr, $tempTable=NULL) {
		// treenoServices call
		$retArr = searchDocumentTypes($passKey, $deptDisplayName, $cabDisplayName=NULL, $docTypeDisplayName, $searchArr, $tempTable=NULL);
		
		if($retArr['ret'] == true) {
			//ALS: exchange this return with a class call
			return new SOAP_Value('return', '{urn:TreenoWebServices}SearchTopInfo', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/**
	 *  Description: searchDocumentInFolder() in the gui equivelent search on doctype optional document type, cabID, FolderID
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the department
	 * @param string $docTypeDisplayName 
	 * @param array $searchArr  
	 * @param int $docID 
	 * @return array 'ret' = true, 'data' = array results from the searchDocumentTypes() function; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function SearchDocumentInFolder($passKey, $deptDisplayName, $cabDisplayName, $docTypeDisplayName, $searchArr, $docID) {
		// treenoServices call
		$retArr = searchDocumentInFolder($passKey, $deptDisplayName, $cabDisplayName, $docTypeDisplayName, $searchArr, $docID);
		
		if($retArr['ret'] == true) {
			//ALS: exchange this return with a class call
			return new SOAP_Value('return', '{urn:TreenoWebServices}SearchTopInfo', $retArr['data']);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/*
	 * -workflow-
	 */
	
	/**
	 *  Description: launches the workflow... code aquired from assignWorkflow(webservices.php)
	 * @param string $passKey login passKey
	 * @param string $deptDisplayName name of the department
	 * @param string $cabDisplayName name of the department
	 * @param int $wfDefsID workflow definitions ID from wf_defs table
	 * @param string $wfOwner user which started the workflow
	 * @param int $docID 
	 * @param int $tabID 
	 * @return array 'ret' = true; 'ret' = false, 'msg' = error message
	 * @example 
	 */
	public function StartWF($passKey, $deptDisplayName, $cabDisplayName, $wfDefsID, $wfOwner, $docID, $tabID) {
		// treenoServices call
		$retArr = startWF($passKey, $deptDisplayName, $cabDisplayName, $wfDefsID, $wfOwner, $docID, $tabID);
		
		if($retArr['ret'] == true) {
			//ALS: exchange this return with a class call
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_FAULT($retArr['msg']);
		}
	}
	
	/*
	 * -publishing-
	 */
	
	
	
	/*
	 * -reports-
	 */
	

	
	/**
	 *  Description: take the soap array of 'key' and 'value' values and convert into true 
	 *  	associative array for use in call to services - PRIVATE
	 * @param array $indiceArr associative array of field keys and values for the new folder
	 * @return associative array of real key=>value for services usage 
	 * @example 
	 * Input: Array (
	 *		[0] => IndexInfo Object (
	 *			[key] => ccan
	 *			[value] => 456
	 *		)
	 *		[1] => IndexInfo Object (
	 *			[key] => invoice_number
	 *			[value] => 55
	 *		)
	 *		[2] => IndexInfo Object (
	 *			[key] => date
	 *			[value] => 2011-11-30
	 *		)
	 *	)
	 * 
	 * Output: Array(
	 *		[ccan] => 456
	 *		[invoice_number] => 55
	 *		[date] => 2011-11-30
	 *	 )
	 * 
	 */
	function ConvertArray($soapArr) {
		// create a real associative array from $soapArr
		$assocArr = array();
		foreach($soapArr as $soapElem) {
			$assocArr[$soapElem->key] = $soapElem->value;
		}
		
		return $assocArr;
	}	// end ConvertArray()
	
	function IsAutoComplete( $passKey, $department, $cabinetID )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$isAutoComplete = isAutoComplete($userName, $department, $cabinetID, $this->db_doc);
		if($isAutoComplete === -1) {
			return new SOAP_Fault('Cabinet does not exist or cabinet permissions denied');
		} elseif( strcmp($isAutoComplete, "auto_complete") == 0 ) {
			return new SOAP_Value('return', 'boolean', true);
		} elseif( strcmp($isAutoComplete, "odbc_auto_complete") == 0 ) {
			return new SOAP_Value('return', 'boolean', true);
		} elseif($isAutoComplete == 'sagitta_ws_auto_complete') {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_Value('return', 'boolean', false);
		}
	}
	
	function GetAutoComplete( $passKey, $department, $cabinetID, $autoCompleteTerm )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$autoCompleteArr = getAutoComplete( $userName, $department, $cabinetID, $autoCompleteTerm, $this->db_doc );
		if($autoCompleteArr === false)
			return new SOAP_Fault('Cabinet does not exist or cabinet permissions denied');

		$autoCompleteList = array();
		foreach( $autoCompleteArr AS $indexKey => $value )
			$autoCompleteList[] = new CabinetItem (
				new SOAP_Value ('index', 'string', $indexKey),
				new SOAP_Value ('value', 'string', $value)
			);

		return new SOAP_Value('return', '{urn:TreenoWebServices}autoCompleteList', $autoCompleteList);	
	}
}	// end of class treenoSoapServer


	
?>