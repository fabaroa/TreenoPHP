<?php
//$Id: soapServer.php 15058 2013-12-05 19:25:42Z cz $
require_once '../db/db_common.php';
require_once '../lib/crypt.php';
require_once '../lib/ldap.php';
require_once '../lib/fileFuncs.php';
require_once '../lib/utility.php';
require_once '../lib/mime.php';
require_once 'SOAP/Value.php';
require_once 'SOAP/Fault.php';
require_once '../webservices2/simpleReturn.php';
require_once '../webservices2/strAssocVal.php';
require_once '../webservices2/common.php';
require_once '../webservices2/CabinetItem.php';
require_once '../webservices2/CabinetFolder.php';
require_once '../webservices2/todoItem.php';
require_once '../webservices2/fileInfo.php';
require_once '../webservices2/fileIdInfo.php';
require_once '../webservices2/CabinetSearchInfo.php';
require_once '../webservices2/AuditSearchInfo.php';
require_once '../webservices2/InboxItem.php';
require_once '../webservices2/DocumentItem.php';
require_once '../webservices2/PublishUserItem.php';
require_once '../webservices2/PublishSearchItem.php';
require_once '../departments/depfuncs.php';
require_once '../lib/webServices.php';
require_once '../lib/mailparse.php';
require_once '../lib/cabinets.php';
require_once '../webservices2/getBarcode.php';
require_once '../webservices2/ZipInfo.php';
class docuSoapServer {
	var $__dispatch_map = array();
	
	/**
	 * Database connection
	 *
	 * @var MDB2_Driver_Common
	 */
	var $db_doc;

	function docuSoapServer($db_doc) {
		$this->__typedef['{urn:DocutronWebServices2}simpleReturn'] = array (
			'success' => 'boolean',
			'message' => 'string'
		);

		$this->__typedef['{urn:DocutronWebServices2}todoItem'] = array (
			'mailedTo'			=> 'string',
			'mailedFrom'		=> 'string',
			'subject'			=> 'string',
			'date'				=> 'dateTime',
			'bodyText'			=> 'string',
			'header'			=> 'string',
			'department'		=> 'string',
			'attachments'		=> '{urn:DocutronWebServices2}FileInfoList'
		);

		$this->__typedef['{urn:DocutronWebServices2}WorkflowDefs'] = array (
			'defsID'	=> 'int',
			'defsName'	=> 'string'
		);

		$this->__typedef['{urn:DocutronWebServices2}WorkflowDefsList'] = array (
			array( 'item' => '{urn:DocutronWebServices2}WorkflowDefs' )
		);


		$this->__typedef['{urn:DocutronWebServices2}todoItemHeader'] = array (
			'todoID'		=> 'int',
			'mailedFrom'	=> 'string',
			'mailedTo'		=> 'string',
			'subject'		=> 'string',
			'date'			=> 'dateTime',
			'arbDept'		=> 'string'
		);

		$this->__typedef['TodoHeaderList'] = array (
			array('item' => '{urn:DocutronWebServices2}todoItemHeader')
		);

		$this->__typedef['StringList'] = array(
			array( 'item' => 'string' )
		);

		$this->__typedef['DefinitionArray'] = array( 
			array('item' => '{urn:DocutronWebServices2}CabinetIndiceDefinitions')
		);

		$this->__typedef['WFDefinitionArray'] = array( 
			array('item' => '{urn:DocutronWebServices2}WorkflowDefinition')
		);

		$this->__typedef['{urn:DocutronWebServices2}CabinetIndiceDefinitions'] = array( 
			'name'		=> 'string',
			'required'	=> 'int',
			'regex'		=> 'string',
			'display'	=> 'string'
		);
		
		
		$this->__typedef['DocumentIndexDefinitionArray'] = array( 
			array('item' => '{urn:DocutronWebServices2}DocumentIndexDefinitions')
		);

		$this->__typedef['{urn:DocutronWebServices2}DocumentIndexDefinitions'] = array( 
			'name'		=> 'string',
			'required'	=> 'int',
			'regex'		=> 'string',
			'display'	=> 'string'
		);
		
		$this->__typedef['{urn:DocutronWebServices2}WorkflowDefinition'] = array( 
			'id'		=> 'int',
			'node_name'	=> 'string'
		);
//	
//		$this->__typedef['strAssocVal'] = array (
//			'key' => 'string',
//			'value' => 'string'
//		);
//		
//		$this->__typedef['strAssocList'] = array (
//			array(
//				'item' => '{urn:DocutronWebServices2}strAssocVal'
//			)
//		);
//
		$this->__typedef['integerList'] = array (
			array (
				'item' => 'int'
			)
		);

		$this->__typedef['fileInfo2'] = array (
			'fileName'		=> 'string',
			'fileID'		=> 'int',
			'docID'			=> 'int',
			'fileSize'		=> 'int',
			'department'	=> 'string',
			'cabinetID'		=> 'int',
			'ordering'		=> 'int'
		);

		$this->__typedef['fileInfo'] = array (
			'fileName'		=> 'string',
			'fileID'		=> 'int',
			'docID'			=> 'int',
			'fileSize'		=> 'int',
			'department'	=> 'string',
			'cabinetID'		=> 'int'
		);

		$this->__typedef['ADPfileInfo'] = array (
			'fileName'		=> 'string',
			'fileID'		=> 'int',
			'docID'			=> 'int',
			'fileSize'		=> 'int',
			'department'	=> 'string',
			'cabinetID'		=> 'int',
			'tab'		=> 'string'
		);

		$this->__typedef['fileIdInfo'] = array (
			'fileName'		=> 'string',
			'fileID'		=> 'int',
			'department'	=> 'string',
			'cabinetID'		=> 'int'
		);
		
		$this->__typedef['ExpFileInfo'] = array (
			'fileName'		=> 'string',
			'parentFileName'=> 'string',
			'fileID'		=> 'int',
			'docID'			=> 'int',
			'fileSize'		=> 'int',
			'department'	=> 'string',
			'cabinetID'		=> 'int'
		);

		$this->__typedef['CabinetSearchInfo'] = array (
			'department'	=> 'string',
			'cabinetID'		=> 'int',
			'resultID'		=> 'string',
			'numResults'	=> 'int'
		);

		$this->__typedef['PublishSearchItem'] = array (
			'id'	=> 'string',
			'name'	=> 'string',
			'cab'	=> 'string',
			'indexField'	=>	'string',
			'doc_id'	=> 'string',
			'file_id'	=> 'string',
			'searchTerm' => 'string'
		);
		$this->__typedef['PublishUserItem'] = array (
			'id'	=> 'string',
			'username'	=> 'string'
		);
		$this->__typedef['PublishSearchList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}PublishSearchItem'
			)			
		);
		$this->__typedef['PublishUserList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}PublishUserItem'
			)			
		);

		$this->__typedef['CabinetItem'] = array (
			'index'	=> 'string',
			'value'	=> 'string'
		);

		$this->__typedef['AuditSearchItem'] = array (
			'index'	=> 'string',
			'value'	=> 'string'
		);

		$this->__typedef['AuditDateTime'] = array (
			'operation'	=> 'string',
			'value'		=> 'string'
		);

		$this->__typedef['DepartmentItem'] = array (
			'realName'	=> 'string',
			'arbName'	=> 'string'
		);

		$this->__typedef['DepartmentList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DepartmentItem'
			)
		);
		
		$this->__typedef['CabinetInfo'] = array (
			'cabinetID'		=> 'int',
			'cabinetName'		=> 'string',
			'cabinetRealName'	=> 'string'
		);
		
		$this->__typedef['CabinetList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}CabinetInfo'
			)
		);
		
		$this->__typedef['DepartmentList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DepartmentItem'
			)
		);

		$this->__typedef['CabinetEntry'] = array (
			array(
				'item'	=> '{urn:DocutronWebServices2}CabinetItem'
			)
		);

		$this->__typedef['AuditSearchEntry'] = array (
			array(
				'item'	=> '{urn:DocutronWebServices2}AuditSearchItem'
			)
		);
		
		$this->__typedef['AuditDateTimeEntry'] = array (
			array(
				'item'	=> '{urn:DocutronWebServices2}AuditDateTime'
			)
		);
		
		$this->__typedef['CabinetFolder'] = array (
			'docID'				=> 'int',
			'cabinetIndices'	=> 'string'
		);
		
		$this->__typedef['CabinetResultSet'] = array (
			array (
				'folder'	=> '{urn:DocutronWebServices2}CabinetFolder'
			)
		);
		
		$this->__typedef['TopLevelSearchInfo'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}CabinetSearchInfo'
			)
		);
		
		$this->__typedef['FileIdInfoList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}fileIdInfo'
			)			
		);

		$this->__typedef['FileInfoList2'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}fileInfo2'
			)			
		);
		$this->__typedef['FileInfoList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}fileInfo'
			)			
		);
		
		$this->__typedef['ADPFileInfoList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}ADPfileInfo'
			)			
		);

		$this->__typedef['ExpFileInfoList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}ExpFileInfo'
			)			
		);

		$this->__typedef['indexDefList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}CabinetItem'
			)			
		);

		$this->__typedef['autoCompleteList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}CabinetItem'
			)			
		);

		$this->__typedef['InboxItem'] = array (
			'isFolder'			=> 'boolean',
			'filename'			=> 'string'
		);

		$this->__typedef['DocumentInfo'] = array (
			'tabID'				=> 'int',
			'documentIndices'	=> 'string'
		);

		$this->__typedef['AuditSearchInfo'] = array (
			'id'		=> 'int',
			'username'	=> 'string',
			'datetime'	=> 'string',
			'info'		=> 'string',
			'action'	=> 'string'
		);

		$this->__typedef['DetailedDocumentList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DetailedDocumentInfo'
			)
		);

		$this->__typedef['DetailedDocumentInfo'] = array (
			'tabID'			=> 'int',
			'documentName'		=> 'string',
			'documentType'		=> 'string',
			'documentIndices'	=> '{urn:DocutronWebServices2}DetailedDocumentIndices'
		);
		
		$this->__typedef['DetailedDocumentPartialList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DetailedDocumentInfo2'
			)
		);

		$this->__typedef['DetailedDocumentInfo2'] = array (
			'sequence'			=> 'int',
			'tabID'				=> 'int',
			'documentName'		=> 'string',
			'documentType'		=> 'string',
			'documentIndices'	=> '{urn:DocutronWebServices2}DetailedDocumentIndices'
		);

		$this->__typedef['DetailedDocumentIndices'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DocumentIndex'
			)
		);

		$this->__typedef['DocumentIndex'] = array (
			'indexName'	=> 'string',
			'displayName'	=> 'string',
			'value'		=> 'string'
		);

	
		$this->__typedef['inboxFileList'] = array (
 			array (
				'item' => '{urn:DocutronWebServices2}InboxItem'
			)
		);

		$this->__typedef['DocumentList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DocumentInfo'
			)
		);

		$this->__typedef['AuditList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}AuditSearchInfo'
			)
		);

		$this->__typedef['DocumentItem'] = array (
			'index'	=> 'string',
			'value'	=> 'string'
		);
	
		$this->__typedef['DocumentEntry'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DocumentItem'
			)
		);

		$this->__typedef['DocumentType'] = array (
			'documentID'	=> 'int',
			'realName'		=> 'string',
			'arbName'		=> 'string',
			'indices'		=> '{urn:DocutronWebServices2}DocumentEntry',
			'definitions'	=> '{urn:DocutronWebServices2}DocumentDefinitionList'
		);
	
		$this->__typedef['DocumentTypeList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}DocumentType'
			)
		);

		$this->__typedef['CheckedOutFileInfo'] = array (
			'fileName'		=> 'string',
			'encFileData'	=> 'base64Binary'
		);

		$this->__typedef['ZipFileInfo'] = array (
			'fileName'		=> 'string',
			'fileSize'		=> 'double'
		);

		$this->__typedef['ZipFileInfoList'] = array (
			array (
				'item'	=> '{urn:DocutronWebServices2}ZipFileInfo'
			)
		);

		$this->__typedef['GetTVInfo'] = array (
			'docID'		=> 'int',
			'barcodeStr'		=> 'base64Binary'
		);

		$this->__typedef['DocumentDefinition'] = array (
			'index_name' => 'string',
			'definition_list' => '{urn:DocutronWebServices2}StringList'
		);

		$this->__typedef['DocumentDefinitionList'] = array (
			array (
				'item' => '{urn:DocutronWebServices2}DocumentDefinition'
			)
		);

		ksort($this->__typedef);
		$this->db_doc = $db_doc;
		$arr= array (
			'GetOcrFiles'	=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'number_of_files_to_retrieve'	=> 'int',
				),
				'out'	=> array (
					'FileIdInfoList'	=> '{urn:DocutronWebServices2}FileIdInfoList',
				)
			),
			'GetAttachFiles'	=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'number_of_files_to_retrieve'	=> 'int',
				),
				'out'	=> array (
					'FileIdInfoList'	=> '{urn:DocutronWebServices2}FileIdInfoList',
				)
			),
			'UpdateOcrEntry'	=> array (
				'in'	=> array (
					'passKey'			=> 'string',
					'department'		=> 'string',
					'cabid'				=> 'int',
					'fileid'			=> 'int',
					'ocr_text'			=> 'string',
				),
				'out'	=> array ( 
					'return'		=> 'boolean',
				)
			),
			'UpdateAttachmentEntry'	=> array (
				'in'	=> array (
					'passKey'			=> 'string',
					'department'		=> 'string',
					'cabid'				=> 'int',
					'fileid'			=> 'int',
					'attachment'			=> 'int',
					'attachmentNames'			=> 'string',
				),
				'out'	=> array ( 
					'return'		=> 'string',
				)
			),
			'UploadToIndexingDirectory' => array (
				'in'	=> array (
					'passKey'			=> 'string',
					'encFileData'		=> 'base64Binary',
					'fileName'			=> 'string',
					'finalDirectory'	=> 'string',
				),
				'out'	=> array (
					'simpleOutput'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			'md5String' => array (
				'in' => array (
				'str' => 'string',
				),
				'out' => array(
					'simpleOutput'	=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'tripleDesEncrypt' => array (
				'in' => array (
				'str' => 'string',
				),
				'out' => array(
					'simpleOutput'	=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			
			'Login' => array (
				'in'	=> array (
					'userName'		=> 'string',
					'md5Pass'		=> 'string',
				),
				'out'	=> array (
					'simpleOutput'	=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'ExtPortalValidate' => array (
				'in'	=> array (
					'userName'		=> 'string',
					'md5Pass'		=> 'string',
				),
				'out'	=> array (
					'simpleOutput'	=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'IsPublishUser' => array (
				'in'	=>	array ( 
					'passkey'		=> 'string',
					'department'	=> 'string',
					'emailAddress'	=> 'string',
				),
				'out'	=> array (
					'return'		=> 'boolean',
				)
			), 
			
			'AddWorkflowHistoryAudit' => array (
				'in'	=>	array ( 
					'passkey'		=> 'string',
					'department'	=> 'string',
					'username'		=> 'string',
					'wf_doc_id'		=> 'int',
					'action'		=> 'string',
					'notes'			=> 'string', 
				),
				'out'	=> array (
					'return'		=> 'boolean',
				)
			),
			'AddPublishUser' => array (
				'in'	=>	array ( 
					'passkey'		=> 'string',
					'department'	=> 'string',
					'emailAddress'	=> 'string',
					'upload'		=> 'string',
					'publish'		=> 'string', 
				),
				'out'	=> array (
					'publishUserId'		=> 'int',
				)
			),
			
			'AddPublishItem' => array (
				'in'	=> array (
					'passkey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'expirationTime'	=> 'int',
					'enabled'		=> 'boolean',
					'searchName'	=> 'string',
					'wfDefId'		=> 'int',
					'type'			=> 'string',
				),
				'out'	=> array (
					'id'		=> 'int',
				)
			),
			'AddPublishSearch' => array (
				'in'	=> array (
					'passkey'		=> 'string',
					'department'	=> 'string',
					'cabinet'		=> 'string',
					'indexField'	=> 'string',
					'searchTerm'	=> 'string',
					'expirationTime'	=> 'int',
					'enabled'		=> 'boolean',
					'searchName'	=> 'string',
				),
				'out'	=> array (
					'id'		=> 'int',
				)
			),
			
			'GetPublishSearchList'	=> array (
				'in' => array (
					'passkey'		=> 'string',
					'department'	=> 'string',
				),
				'out' => array (
					'publishSearchList'	=> '{urn:DocutronWebServices2}PublishSearchList',
				)
			),

			'GetPublishUserList' => array (
				'in' => array( 
					'passkey'		=> 'string',	
					'department'	=> 'string',
				),
				'out' => array (
					'publishUserList'	=> '{urn:DocutronWebServices2}PublishUserList',
				)
			),

			'BindPublishSearchWithUser' => array (
				'in' => array (
					'passkey'		=> 'string',
					'department'	=> 'string',
					'userid'		=> 'int', 
					'publishid'		=> 'int',
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),
			
			'CreateCabinetFolder' => array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'indices'		=> '{urn:DocutronWebServices2}CabinetEntry', 
				),
				'out'	=> array (
					'docID'			=> 'int',
				)
			),
			
			'UpdateCabinetFolder' => array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'indices'		=> '{urn:DocutronWebServices2}CabinetEntry', 
				),
				'out'	=> array (
					'return'		=> 'boolean',
				)
			),

			'UploadFileToFolder' => array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string', //requires the real department name
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'tabID'			=> 'int',	//0 = main
					'filename'		=> 'string',
					'encFileData'	=> 'base64Binary',
				),
				'out'	=> array (
					'fileID'		=> 'int',
				)
			),

			'GetTabFileList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'tab'			=> 'string',
				),
				'out' => array (
					'tabFileList'	=> '{urn:DocutronWebServices2}FileInfoList',
				)
			),
			
			'GetTabFileListExp' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'tab'			=> 'string',
				),
				'out' => array (
					'tabFileList'	=> '{urn:DocutronWebServices2}ExpFileInfoList',
				)
			),
			
			'GetTabFileListByID2' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'tabID'			=> 'int',
				),
				'out' => array (
					'tabFileList'	=> '{urn:DocutronWebServices2}FileInfoList2',
				)
			),
			'GetTabFileListByID' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'tabID'			=> 'int',
				),
				'out' => array (
					'tabFileList'	=> '{urn:DocutronWebServices2}FileInfoList',
				)
			),
			
			'GetTabList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
				),
				'out' => array (
					'tabList'		=> '{urn:DocutronWebServices2}FileInfoList',
				)
			),
			
			'GetCabinetList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
				),
				'out' => array (
					'return'	=> '{urn:DocutronWebServices2}CabinetList',
				)
			),
				
			'GetROCabinetList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
				),
				'out' => array (
					'return'	=> '{urn:DocutronWebServices2}CabinetList',
				)
			),
				
			'GetDepartmentList' => array (
				'in' => array (
					'passKey'		=> 'string',
				),
				'out' => array (
					'return'	=> '{urn:DocutronWebServices2}DepartmentList',
				)
			),

			'GetTodoList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'nodeName'		=> 'string',
				),
				'out' => array (
					'integerList'	=> '{urn:DocutronWebServices2}integerList',
				)
			),

			'GetTodoHeaderList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'nodeName'		=> 'string',
				),
				'out' => array (
					'todoHeaderList' => '{urn:DocutronWebServices2}TodoHeaderList'
				)
			),

			'GetAttachment' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'fileID'		=> 'int',
				),
				'out' => array (
					'encFileData'	=> 'base64Binary',
				)
			),

			'GetTodoItem' => array (
				'in' => array (
					'passKey'		=> 'string',
					'wfTodoID'		=> 'int',
				),
				'out' => array (
					'todoItem'		=> '{urn:DocutronWebServices2}todoItem',
				)
			),

			'UpdateTodoItem' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'wfTodoID'		=> 'int',
					'priority'		=> 'int',
					'notes'			=> 'string',
					'dateDue'		=> 'string',
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),

			'GetWorkflowDefs'	=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'		=> 'string'
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}WorkflowDefsList'
				)
			),

			'GetDepartmentUserList'	=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'		=> 'string'
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}StringList'
				)
			),

			'CompleteWorkflowNode' => array (
				'in' => array (
					'passKey'		=> 'string',
					'wfTodoID'		=> 'int',
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),
			
			'SaveFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'userName'	=> 'string',
					'fileIDs'		=> '{urn:DocutronWebServices2}integerList',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'destCabinetID'	=> 'int',
					'destDocID'		=> 'int',
				),
				'out' => array (
					'simpleReturn'	=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			
			'SearchTopLevel' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'searchStr'		=> 'string'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}TopLevelSearchInfo',
				)
			), 

			'SearchCabinet' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'searchTerms'	=> '{urn:DocutronWebServices2}CabinetEntry'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}CabinetSearchInfo',
				)
			),

			'SearchAudit'	=> array( 
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'searchTerms'	=> '{urn:DocutronWebServices2}AuditSearchEntry',
					'dateTime'		=> '{urn:DocutronWebServices2}AuditDateTimeEntry'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}AuditList',
				)
			),

			'SearchAndReplace'	=> array( 
				'in' => array (
					'passKey'	=> 'string',
					'department'	=> 'string',
					'cabinetID'	=> 'int',
					'searchArr'	=> '{urn:DocutronWebServices2}CabinetEntry',
					'replaceArr'	=> '{urn:DocutronWebServices2}CabinetEntry'
				),
				'out' => array (
					'return'	=> 'int',
				)
			),

			'GetResultSet' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'resultID'		=> 'string',
					'startIndex'	=> 'int',
					'numberToFetch'	=> 'int',
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}CabinetResultSet',
				)
			),

			'CreateDocumentInfo' => array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'documentName'	=> 'string',
					'indices'		=> '{urn:DocutronWebServices2}DocumentEntry', 
				),
				'out'	=> array (
					'tabID'			=> 'int',
				)
			),

			'UpdateDocumentIndices'	=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'tabID'			=> 'int',
					'updatedIndices'	=> '{urn:DocutronWebServices2}DocumentEntry',
				),
				'out'	=> array (
					'return'		=> 'boolean',
				)
			),
			

			'GetDocumentList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DocumentList',
				)
			),
			
			'GetDetailedDocumentList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DetailedDocumentList',
				)
			),
			
			'GetDetailedDocumentPartialList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'start'			=> 'int',
					'count'			=> 'int',
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DetailedDocumentPartialList',
				)
			),
			
			'GetFilteredDocumentTypeList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinetID'		=> 'int',
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DocumentTypeList',
				)
			),

			'GetDocumentTypeList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'		=> 'string',
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DocumentTypeList',
				)
			),

			'GetCabinetIndices' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}StringList',
				)
			),

			'GetCabinetIndiceDefinitions' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DefinitionArray',
				)
			),

			
			'GetDocumentIndexDefinitions' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'documentType'		=> 'string'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DocumentIndexDefinitionArray',
				)
			),
			
			'GetDetailedWorkflowDefinitions' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'wfDefID'		=> 'int'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}WFDefinitionArray',
				)
			),

			'WorkflowAccept' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'wfDocID'		=> 'int',
					'wfNodeID'		=> 'int'
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),

			'CopyOnDocutron' => array (
				'in' => array (
					'passKey'		=> 'string',
					'sourceFileInfo'	=> '{urn:DocutronWebServices2}fileInfo',
					'destDepartment'	=> 'string',
					'destCabinetID'		=> 'int',
					'destDocID'		=> 'int',
					'destTabID'		=> 'int',
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),

			'DoDirExist' => array (
				'in' => array (
					'passKey'		=> 'string',
					'path'			=> 'string',
				),
				'out' => array (
					'return'		=> 'boolean',
				)
			),

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
/*
			'ListDirectory' => array (
				'in'	=> array (
					'passKey'		=> 'string',
					'path'			=> 'string',
				),
				'out'	=> array (
					'ArrayOfStrings' => '{urn:DocutronWebServices2}ArrayOfStrings',
				)
			),
			'CreateFolder' => array (
				'in' => array (
					'passKey'		=> 'string',
					'dep'			=> 'string',
					'cab'			=> 'string',
					'fields'		=> '{urn:DocutronWebServices2}ArrayOfStrings',
					'tabs'	=> '{urn:DocutronWebServices2}ArrayOfStrings',
				),
				'out' => array (
					'doc_id'		=> 'int',
				)
			),
*/
			'GetDataDir' => array (
				'in' => array (
					'passKey'		=> 'string',
				),
				'out' => array (
					'dataDir'		=> 'string',
				)
			),
			
			'GetFolderIndiceValues' => array (
				'in'	=> array (
					'passKey'			=> 'string',
					'department'		=> 'string',
					'cabinetID'			=> 'int',
					'docID'				=> 'int',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}indexDefList',
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
					'return'		=> '{urn:DocutronWebServices2}autoCompleteList',
				)
			),
		
			'GetDatatypeDefinitions' => array(
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}indexDefList',
				)
			),
			
			'AddDatatypeDefinitions' => array(
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',  
					'cabIndex'		=> 'string', //case-sensitive
					'dataDefList'	=> '{urn:DocutronWebServices2}StringList',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'ClearDatatypeDefinitions' => array(
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',  
					'cabIndex'		=> 'string', //case-sensitive
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'DeleteDatatypeDefinitions' => array(
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',  
					'cabIndex'		=> 'string', //case-sensitive
					'dataDefList'	=> '{urn:DocutronWebServices2}StringList',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'GetFolderBarcode'	=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'GetSubfolderBarcode'	=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',  
					'docID'			=> 'int',  
					'tabID'			=> 'int',  
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'GetInboxFileList'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'inboxUser'		=> 'string',
					'folder'		=> 'string',
				),
				'out'	=> array(
					'return'		=> '{urn:DocutronWebServices2}inboxFileList',
				)
			),	

			'UploadToInbox'			=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'inboxUser'		=> 'string',
					'folder'		=> 'string',
					'filename'		=> 'string',
					'encodedFile'	=> 'base64Binary',
				),
				'out'	=> array(
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			
			'DownloadFromInbox'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'inboxUser'		=> 'string',
					'folder'		=> 'string',
					'filename'		=> 'string',
				),
				'out'	=> array(
					'encodedFile'   => 'base64Binary', 
				)
			),

			'CreateInboxFolder'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'inboxUser'		=> 'string',
					'folder'		=> 'string',
				),
				'out'	=> array(
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'GetSavedTabs'			=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
				),
				'out'	=> array(
					'return'		=> '{urn:DocutronWebServices2}StringList',
				)
			),

			'GetUploadUsername'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'GetUploadPassword'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'GetCabinetInfo'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'IsDocumentView'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
				),
				'out'	=> array(
					'return'		=> 'boolean',
				)
			),

			'GetTVBarcode' => array (
				'in'    => array (
					'passKey'       => 'string',
					'application_number'    => 'string',
					'ccan_number'     => 'string',
					'Lessee_name'    => 'string',
					'sub_type_name'    => 'string',
				),
				'out'   => array (
					'return'		=> '{urn:DocutronWebServices2}GetTVInfo',
				)
			),

			'UploadFileFromTV' => array(
				'in'	=> array(
					'passKey'		=> 'string',
					'docID'			=> 'int',
					'sub_type_name'		=> 'string',
					'filename'		=> 'string',
					'encFileData'	=> 'base64Binary',
				),
				'out'	=> array (
					'return'		=> 'string',
				)
			),

			'UpdateTVCabinetFolder' => array(
				'in'	=> array(
					'passKey'		=> 'string',
					'docID'	=> 'int',
					'status'		=> 'string',
					'statusdate'		=> 'string',
					'contract_number'	=> 'string',
					'affiliateID'		=> 'int'
				),
				'out'	=> array (
					'return'		=> 'boolean',
				)
			),

			'GetBarcodeInfo'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'item'			=> 'string',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'GetBarcodeImage'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'item'			=> 'string',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'CreateSubfolder'		=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'subfolderName'	=> 'string',
				),
				'out'	=> array(
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),

			'GetUserBarcode'=> array(
				'in'	=> array(
					'passKey'		=> 'string',
					'userLookupName'=> 'string',
					'department'	=> 'string',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),

			'AssignWorkflow'	=> array (
				'in'	=> array (
					'passKey'	=> 'string',
					'department'	=> 'string',
					'cabinetID'	=> 'int',
					'docID'		=> 'int',
					'tabID'		=> 'int',
					'wfDefsID'	=> 'int',
					'wfOwner'	=> 'string',
				),
				'out'	=> array (
					'return'	=> 'boolean',
				)
			),
			
			'HasWorkflowInProgress' => array (
				'in'	=> array (
					'passKey'	=> 'string',
					'department'	=> 'string',
					'cabinetID'	=> 'int',
					'docID'		=> 'int',
					'tabID'		=> 'int',
				),
				'out'	=> array (
					'return'	=> 'boolean',
				)
			),
			
			'GetFileVersion'=> array(
				'in'	=> array(
					'passKey'	=> 'string',
					'department'	=> 'string',
					'cabinetID'	=> 'int',
					'fileID'	=> 'int',
				),
				'out'	=> array(
					'return'		=> 'string',
				)
			),
			
			'GetFolderID'=> array(
				'in'	=> array(
					'passKey'	=> 'string',
					'department'	=> 'string',
					'cabinetID'	=> 'int',
					'fileID'	=> 'int',
				),
				'out'	=> array(
					'return'		=> 'int',
				)
			),
			
			'CheckOutFile'	=> array (
				'in'	=> array (
					'passKey'	=> 'string',
					'department'	=> 'string',
					'cabinetID'	=> 'int',
					'fileID'	=> 'int',
				),
				'out'	=> array (
					'return'	=> '{urn:DocutronWebServices2}CheckedOutFileInfo',
				)
			),
			'CheckInFile'	=> array (
				'in'	=> array (
					'passKey'	=> 'string',
					'department'	=> 'string',
					'cabinetID'	=> 'int',
					'fileID'	=> 'int',
					'fileName'	=> 'string',
					'encFileData'	=> 'base64Binary',
				),
				'out'	=> array (
					'return'	=> 'boolean',
				)
			),
			'MoveFolder'	=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'destCabinetID'	=> 'int',
					'copy'			=> 'boolean',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			'MoveDocument'	=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'docID'			=> 'int',
					'subfolderID'	=> 'int',
					'destCabinetID'	=> 'int',
					'destDocID'		=> 'int',
					'copy'			=> 'boolean',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			'ZipSearchResults'	=> array (
				'in'	=> array (
					'passKey'			=> 'string',
					'department'		=> 'string',
					'cabinetID'			=> 'int',
					'searchResultsID'	=> 'string', //searchResults
					'fileName'			=> 'string',
				),
				'out'	=> array (
					'return'	=> 'string',
				)
			),
			'ZipFileIDArray'		=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'fileIDArray'	=> '{urn:DocutronWebServices2}integerList',
					'fileName'		=> 'string',
				),
				'out'	=> array (
					'return'		=> 'string',
				)
			),
			'ListZipFiles'		=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}ZipFileInfoList',
				)
			),
			'DeleteZipFile'		=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
					'cabinetID'		=> 'int',
					'zipFileName'	=> 'string',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			'ListImportDir'		=> array (
				'in'	=> array (
					'passKey'		=> 'string',
					'department'	=> 'string',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}StringList',
				)
			),
			'ImportDirectory'	=> array (
				'in'	=> array (
					'passKey'			=> 'string',
					'department'		=> 'string',
					'cabinetID'			=> 'int',
					'opCabinetID'		=> 'int', //operational cabinet
					'opDocID'			=> 'int',
					'maxFileSize'		=> 'double',
					'fileExtensions'	=> '{urn:DocutronWebServices2}StringList',
					'generateTemplate'	=> 'boolean',
					'postAction'		=> 'string',
				),
				'out'	=> array (
					'return'		=> '{urn:DocutronWebServices2}simpleReturn',
				)
			),
			'FilenameAdvSearch' => array (
				'in'    => array (
					'passKey'       => 'string',
					'department'    => 'string',
					'cabinetID'     => 'int',
					'searchTerm'    => 'string',
				),
				'out'   => array (
					'return'        => '{urn:DocutronWebServices2}CabinetSearchInfo',
				)
			),
			'GetFileResultSet'  => array (
				'in'    => array (
					'passKey'       => 'string',
					'department'    => 'string',
					'cabinetID'     => 'int',
					'resultID'      => 'string',
					'startIndex'    => 'int',
					'numberToFetch' => 'int',
				),
				'out'   => array (
					'return'        => '{urn:DocutronWebServices2}FileInfoList',
				)
			),		
			'GetADPFileList' => array (
				'in' => array (
					'passKey'		=> 'string',
					'contract_number'	=> 'string',
					'ccan_number'		=> 'string',
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}ADPFileInfoList',
				)
			),
			
			'GetADPInvoiceFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'invoice_number'	=> 'string',
				),
				'out' => array (
					'encFileData'	=> 'base64Binary',
				)
			),

			'GetDefaultDept' => array (
				'in' => array (
					'passKey'		=> 'string'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}DepartmentItem',
				)
			),
			'DownloadCabinetFile' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinetID'		=> 'string',
					'fileID'		=> 'string',
					'location'		=> 'string'
				),
				'out' => array (
					'return'		=> 'string',
				)
			),
			'GetBarcodeNumber' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinet'		=> 'string',
					'indices'		=> '{urn:DocutronWebServices2}CabinetEntry', 
					'primary'		=> '{urn:DocutronWebServices2}CabinetEntry', 
					'tab'		=> 'string'
				),
				'out' => array (
					'return'		=> 'string',
				)
			),
			'GetBarcodeData' => array (
				'in' => array (
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinet'		=> 'string',
					'indices'		=> '{urn:DocutronWebServices2}CabinetEntry', 
					'primary'		=> '{urn:DocutronWebServices2}CabinetEntry', 
					'tab'		=> 'string'
				),
				'out' => array (
					'return'		=> '{urn:DocutronWebServices2}StringList',
				)
			),
			'UploadFileFromBW' => array(
				'in'	=> array(
					'passKey'		=> 'string',
					'department'		=> 'string',
					'cabinet'		=> 'string',
					'docID'			=> 'int',
					'subfolderID'		=> 'string',
					'filename'		=> 'string',
					'encFileData'	=> 'base64Binary',
				),
				'out'	=> array (
					'return'		=> 'string',
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
	
	function md5String( $string )
	{
		$ret = new simpleReturn( true, md5( $string ) );
		return new SOAP_Value('simpleOutput', '{urn:DocutronWebServices2}simpleReturn', $ret);
	}

	function tripleDesEncrypt( $string )
	{
		$ret = new simpleReturn( true, tdEncrypt( $string ) );
		return new SOAP_Value('simpleOutput', '{urn:DocutronWebServices2}simpleReturn', $ret);
	}
	function IsPublishUser( $passKey,$department,$emailAddress ){
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$db = getDbObject( $department );
		if( !hasAccess( $db, $userName ) ){
			return new SOAP_Fault('Permission Denied');
		}
		if( isPublishUser( $department, $emailAddress )){
			return new SOAP_Value('return', 'boolean', true);
		}else{ 
			return new SOAP_Value('return', 'boolean', false);
		}
	}
	function AddWorkflowHistoryAudit( $passKey, $department, $username, $wf_doc_id, $action, $notes ){
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		try{
			$ret = addWorkflowHistoryAudit($department, $username, $wf_doc_id, $action, $notes );
			if( $ret )
				return new SOAP_Value( 'return', 'boolean', 'true' );
			return new SOAP_Value( 'return', 'boolean', 'false' );
		} catch(Exception $e){
			return new SOAP_Value( 'return', 'boolean', 'false' );
		}
	}

	function GetDetailedWorkflowDefinitions($passKey, $department, $wfDefID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		try {
			$wfInfo = array();
			$wfList = getDetailedWorkflowDefinitions($department,$wfDefID);
			foreach($wfList AS $n) {
				$wfInfo[] = new WorkflowDefinition($n['id'],$n['node_name']);
			}
			return new SOAP_Value('return', '{urn:DocutronWebServices2}WFDefinitionArray', $wfInfo);
		} catch(Exception $e) {

		}
	}

	function WorkflowAccept($passKey, $department, $wfDocID, $wfNodeID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		try {
			workflowAccept($department,$userName,$wfDocID,$wfNodeID);
			return new SOAP_Value('return', 'boolean', true);
		} catch(Exception $e) {
			return new SOAP_Value( 'return', 'boolean', false );
		}
	}

	function AddPublishUser( $passKey, $department, $emailAddress, $upload, $publish ){
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$db = getDbObject( $department );
		if( !hasAccess( $db, $userName ) ){
			return new SOAP_Fault('Permission Denied: '."$userName does not have access to this department");
		}
		$id = addPublishUser( $department, $emailAddress, $upload, $publish );
		if( $id===false )
			return new SOAP_Fault('Permissions denied ');
		return new SOAP_Value('publishUserId', 'int', $id);
	}
	function AddPublishItem($passKey,$dept,$cabinet,$expTime,$enabled,$searchName,$wfDefId,$type ){
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$db = getDbObject( $dept );
		if( !hasAccess( $db, $userName ) ){
			return new SOAP_Fault('Permission Denied: '."$userName does not have access to this department");
		}
		$id=addPublishSearch($dept,$searchName,$enabled,$expTime,$cabinet,$indexField,$searchTerm,$userName,$type,$wfDefId);
		if( $id===false ){
			return new SOAP_Fault('Search Could Not Be Created');
		}
		return new SOAP_Value( 'id', 'int', $id );
	}
	function AddPublishSearch($passKey,$dept,$cabinet,$indexField,$searchTerm,$expTime,$enabled,$searchName ){
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$db = getDbObject( $dept );
		if( !hasAccess( $db, $userName ) ){
			return new SOAP_Fault('Permission Denied: '."$userName does not have access to this department");
		}
		$id = addPublishSearch($dept,$searchName,$enabled,$expTime,$cabinet,$indexField,$searchTerm, $userName );
		if( $id===false ){
			return new SOAP_Fault('Search Could Not Be Created');
		}
		return new SOAP_Value( 'id', 'int', $id );
	}

	function GetPublishUserList( $passKey, $department )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$db = getDbObject( $department );
		if( !hasAccess( $db, $userName ) ){
			$db->disconnect();
			return new SOAP_Fault('Permission Denied: '."$userName does not have access to this department");
		}

		$userList = getPublishUserList( $department );
		if($userList === false)
			return new SOAP_Fault('Permissions denied');
		$soapList = array();
		foreach( $userList AS $indexKey => $value )
			$soapList[] = new PublishUserItem (
				new SOAP_Value ('id', 'string', $indexKey),
				new SOAP_Value ('username', 'string', $value)
			);
		return new SOAP_Value('publishUserList', '{urn:DocutronWebServices2}PublishUserList', $soapList);	
	}
	function GetPublishSearchList( $passKey, $department ){
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$db = getDbObject( $department );
		if( !hasAccess( $db, $userName ) ){
			$db->disconnect();
			return new SOAP_Fault('Permission Denied: '."$userName does not have access to this department");
		}

		$searchList = getPublishSearchList( $department );
		$soapList = array();
		foreach( $searchList AS $id => $search )
			$soapList[] = new PublishSearchItem (
				new SOAP_Value ('id', 'string', $id),
				new SOAP_Value ('name', 'string', $search['name']),
				new SOAP_Value ('cab', 'string', $search['cab']),
				new SOAP_Value ('indexField', 'string', $search['field']),
				new SOAP_Value ('doc_id', 'string', $search['doc_id']),
				new SOAP_Value ('file_id', 'string', $search['file_id']),
				new SOAP_Value ('searchTerm', 'string', $search['term'])
			);
		return new SOAP_Value('publishSearchList', '{urn:DocutronWebServices2}PublishSearchList', $soapList);	
		
	}
	function BindPublishSearchWithUser( $passKey, $department, $userid, $publishid ){
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		$db = getDbObject( $department );
		if( !hasAccess( $db, $userName ) ){
			$db->disconnect();
			return new SOAP_Fault('Permission Denied: '."$userName does not have access to this department");
		}
		$bool = bindPublishSearchWithUser( $userid, $publishid, $userName );	
		if( $bool ===false ){
			return new SOAP_Fault('could not bind user with search');
		}
		return new SOAP_Value( 'return', 'boolean', $bool );
	}
	
	function Login($userName, $md5Pass) {
		$userName = strtolower ($userName);
		$usersInfo = getTableInfo($this->db_doc, 'users', array(), array('username' => $userName));
		//$usersInfo = getUsersInfo($this->db_doc, $userName);
		if($row = $usersInfo->fetchRow()) {
			if ($row['ldap_id'] != 0) {
				$myPass = tdDecrypt ($md5Pass);
				if (checkLDAPPassword ($this->db_doc, $row['ldap_id'], $userName, $myPass)) {
					$key = $userName.','.(time() + 86400);
					$message = weakEncrypt($key);
					$retVal = true;
				} else {
					$message = 'Login Incorrect';
					$retVal = false;
				}
			} else {
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
		return new SOAP_Value('simpleOutput', '{urn:DocutronWebServices2}simpleReturn', $ret);
	}

	function ExtPortalValidate($userName, $md5Pass) {
		$userName = strtolower ($userName);
		$usersInfo = getTableInfo($this->db_doc, 'users', array(), array('username' => $userName));
		//$usersInfo = getUsersInfo($this->db_doc, $userName);
		if($row = $usersInfo->fetchRow()) {
			if ($row['ldap_id'] != 0) {
				$myPass = tdDecrypt ($md5Pass);
				if (checkLDAPPassword ($this->db_doc, $row['ldap_id'], $userName, $myPass)) {
					$key = $userName.','.(time() + 600);
					$message = weakEncrypt($key);
					$retVal = true;
				} else {
					$message = 'Login Incorrect';
					$retVal = false;
				}
			} else {
				if(strtoupper($row['password']) == strtoupper($md5Pass)) {
					$key = $userName.','.(time() + 600);
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
		return new SOAP_Value('simpleOutput', '{urn:DocutronWebServices2}simpleReturn', $ret);
	}

	function UploadToIndexingDirectory($key, $encFileData, $fileName, $directory) {
		list($retVal, $username) = checkKey($key);
		if(!$retVal) {
			$ret = new simpleReturn(false, 'Login Permission Denied');
			return new SOAP_Value('simpleOutput', '{urn:DocutronWebServices2}simpleReturn', $ret);
		}
		makeAllDir($directory);
		$fd = fopen($directory.'/'.$fileName, 'w+');
		fwrite($fd, $encFileData);
		fclose($fd);
		$ret = new simpleReturn(true, 'File Uploaded');
		return new SOAP_Value('simpleOutput', '{urn:DocutronWebServices2}simpleReturn', $ret);
	}
/*
	function GetCabinetIndices($passKey, $userName, $department) {
		list($retVal, $username) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		
		$cabList = getCabinetList($userName, $department);
		if( !testCabinetAccess($cabinet, $cabList) )

			return new SOAP_Fault('Cabinet does not exist or user does not have access rights');
	}
*/

	function CreateCabinetFolder($passKey, $department, $cabinetID, $indices) {
	//error_log('soapServer::CreateCabinetFolder()');
	//error_log('soapServer::CreateCabinetFolder() department='.$department.' -- indices: '.print_r($indices, true));
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$indiceArr = array ();
		foreach($indices as $myIndex) {
			if (isset($myIndex->index)) {
				$indiceArr[$myIndex->index] = $myIndex->value;
			} else {
				$indiceArr[$myIndex->key] = $myIndex->value;
			}
		}
		$db_dept = getDbObject ($department);
		$docID = createCabinetFolder($department, $cabinetID, $indiceArr, $userName, $this->db_doc, $db_dept);
		$db_dept->disconnect ();
		
		if($docID == -2) {
			return new SOAP_Fault("Folder exists and file_into_existing is true");
		}
		
		if($docID == -1) {
			return new SOAP_Fault("Folder Creation Permission Denied");
		}
		//error_log('soapServer::CreateCabinetFolder() docID = '.$docID);
		return $docID;
	}
	
	function UpdateTVCabinetFolder($passKey,$docID,$status,$statusDate,$contract_number,$affiliateID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$department="client_files168";
		if ($affiliateID==6){
			$cabinetID = 9;
			$cabinet = "CAF";
		} else {
			$cabinetID = 11;
			$cabinet = "ADPCL 20100525";
		}
//		$ret=array();
		$indiceArr = array ();
		$indiceArr['status'] = $status;
		error_log("********docID:".$docID.":statusDate:".$statusDate);
		$dateFields = explode("-",$statusDate);
		$formatedDateFields=sprintf("%04d-%02d-%02d", $dateFields[0], $dateFields[1], $dateFields[2]);
		$indiceArr['status_date'] = $formatedDateFields;
		$indiceArr['contract_number'] = $contract_number;
		$db_dept = getDbObject ($department);
		$ret = updateCabinetFolder($db_dept, $cabinetID, $docID, $indiceArr, $userName);
		$db_dept->disconnect ();
		return $ret;		
	}

	function UpdateCabinetFolder($passKey, $department, $cabinetID, $docID, $indices) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$indiceArr = array ();
		foreach($indices as $myIndex) {
			$indiceArr[$myIndex->index] = $myIndex->value;
		}
		$db_dept = getDbObject ($department);
		$ret = updateCabinetFolder($db_dept, $cabinetID, $docID, $indiceArr, $userName);
		$db_dept->disconnect ();
		if(!$ret) {
			return new SOAP_Fault("Folder Update Permission Denied");
		}
		return true;		
	}

	//	Returns the fileID in the db if file is uploaded
	function UploadFileToFolder($passKey, $department, $cabinetID, $docID, $tabID, $filename, $encodedFile)
	{
		list($retVal, $userName) = checkKey($passKey);
$fp = fopen( 'soapServer.log', 'a+' );
fwrite( $fp, $userName."\r\n");
		if(!$retVal) {
			fclose($fp);
			return new SOAP_Fault('Login Permission Denied');
		}
		
		$db_dept = getDbObject ($department);
	  if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $encodedFile)) {
fwrite( $fp, "needed to decode\r\n");
			$encodedFile=base64_decode($encodedFile);
	  } else {
	      $encoded=0;
	  }
//		$fileID = uploadFileToFolder($userName, $department, $cabinetID, $docID, $tabID, $filename, base64_decode($encodedFile), $this->db_doc, $db_dept);
		$fileID = uploadFileToFolder($userName, $department, $cabinetID, $docID, $tabID, $filename, $encodedFile, $this->db_doc, $db_dept);
		$db_dept->disconnect ();
fwrite( $fp, "department:".$department.", cabinetID:".$cabinetID.", docID:".$docID.", tabID:".$tabID.", filename:".$filename.", fileID:".$fileID."$this->db_doc=".$this->db_doc."\r\n");
fclose($fp);
		if(!$fileID)
			return new SOAP_Fault("Upload File to Folder Permission Denied");
		return new SOAP_Value('fileID', 'int', $fileID);
	}
	
	function UpdateOcrEntry( $passKey, $department, $cabid, $fileid, $ocr_text ){
		//must be the user admin!!!
		list($retVal, $userName) = checkKey($passKey);
		if( $userName != 'admin' ){
			return SOAP_Fault('Username MUST BE ADMIN');
		}
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		$ret = updateOcrContext( $db_dept, $userName, $cabid, $fileid, $ocr_text );
		$db_dept->disconnect();
		if( sizeof( $ret ) == 0 ){
			return new SOAP_Fault( 'No More Results - please stop requesting results' );
		}
		return $ret;	
		
	}
	function UpdateAttachmentEntry( $passKey, $department, $cabid, $fileid, $attachment, $attachmentNames ){
		//must be the user admin!!!
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		$ret = updateAttachmentEntry( $db_dept, $userName, $cabid, $fileid, $attachment, $attachmentNames );
		$db_dept->disconnect();
		return $ret;	
		
	}
	function GetOcrFiles($passKey, $numfiles) {
		//must be the user admin!!!
		list($retVal, $userName) = checkKey($passKey);
		if( $userName != 'admin' ){
			return SOAP_Fault('Username MUST BE ADMIN');
		}
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$fileIdList = getOcrFiles($numfiles);
		return new SOAP_Value('FileIdInfoList', '{urn:DocutronWebServices2}FileIdInfoList', $fileIdList);
	}
	function GetAttachFiles($passKey, $numfiles) {
		//must be the user admin!!!
		list($retVal, $userName) = checkKey($passKey);
		if( $userName != 'admin' ){
			return SOAP_Fault('Username MUST BE ADMIN');
		}
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$fileIdList = getAttachFiles($numfiles);
		return new SOAP_Value('FileIdInfoList', '{urn:DocutronWebServices2}FileIdInfoList', $fileIdList);
	}
	function GetTabFileList($passKey, $department, $cabinetID, $docID, $tab) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		$tabArr = GetTabFileList($db_dept, $cabinetID, $docID, $userName, $tab);
		$db_dept->disconnect ();
		if($tabArr === false) {
			return new SOAP_Fault("Cabinet Permission Denied");
		}
		$tabFileList = array ();
		foreach($tabArr as $myFile) {
			$tabFileList[] = new fileInfo($myFile['filename'], (int)$myFile['id'], (int)$docID, (int)$myFile['file_size'], $department, $cabinetID); 
		}
		return new SOAP_Value('tabFileList', '{urn:DocutronWebServices2}FileInfoList', $tabFileList);
	}
	
	function GetTabFileListExp($passKey, $department, $cabinetID, $docID, $tab) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		$tabArr = GetTabFileList($db_dept, $cabinetID, $docID, $userName, $tab);
		$db_dept->disconnect ();
		if($tabArr === false) {
			return new SOAP_Fault("Cabinet Permission Denied");
		}
		$tabFileList = array ();
		foreach($tabArr as $myFile) {
			$tabFileList[] = new ExpFileInfo($myFile['filename'], $myFile['parent_filename'], (int)$myFile['id'], 
								(int)$docID, (int)$myFile['file_size'], $department, $cabinetID); 
		}
		return new SOAP_Value('tabFileList', '{urn:DocutronWebServices2}ExpFileInfoList', $tabFileList);
	}

	function GetTabFileListByID2($passKey, $department, $cabinetID, $docID, $tabID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		$tabArr = getTabFileListByID2($db_dept, $cabinetID, $docID, $userName, $tabID);
		$db_dept->disconnect ();
		if($tabArr === false) {
			return new SOAP_Fault("Cabinet Permission Denied");
		}
		$tabFileList = array ();
		foreach($tabArr as $myFile) {
			$tabFileList[] = new fileInfo2($myFile['filename'], (int)$myFile['id'], (int)$docID, (int)$myFile['file_size'], $department, $cabinetID, $myFile['ordering']); 
		}
		return new SOAP_Value('tabFileList', '{urn:DocutronWebServices2}FileInfoList2', $tabFileList);
	}

	function GetTabFileListByID($passKey, $department, $cabinetID, $docID, $tabID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		$tabArr = getTabFileListByID($db_dept, $cabinetID, $docID, $userName, $tabID);
		$db_dept->disconnect ();
		if($tabArr === false) {
			return new SOAP_Fault("Cabinet Permission Denied");
		}
		$tabFileList = array ();
		foreach($tabArr as $myFile) {
			$tabFileList[] = new fileInfo($myFile['filename'], (int)$myFile['id'], (int)$docID, (int)$myFile['file_size'], $department, $cabinetID); 
		}
		return new SOAP_Value('tabFileList', '{urn:DocutronWebServices2}FileInfoList', $tabFileList);
	}
	
	function GetTabList($passKey, $department, $cabinetID, $docID) {
		list($retVal, $userName) = checkKey($passKey);
        if(!$retVal) {
           return new SOAP_Fault('Login Permission Denied');
		}

		$db_dept = getDbObject ($department);
		$res = GetTabList($db_dept, $cabinetID, $docID, $userName);
		$db_dept->disconnect();
		$fileInfoArr = array();
		foreach($res as $row) {
			$fileInfoArr[] = new fileInfo($row['subfolder'], (int)$row['id'], (int)$docID, 0, $department, $cabinetID);
		}
		return new SOAP_Value('tabList', '{urn:DocutronWebServices2}FileInfoList', $fileInfoArr);
	}

	//Returns a list of cabinets that the user has permission to access
	function GetCabinetList($passKey, $department) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}
		$cabListArr = getCabinetList($userName, $department);
		$cabList = array ();
		foreach($cabListArr as $k => $v) {
			$cabList[] = new CabinetInfo($k, $v);
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}CabinetList', $cabList);
	}
	
	//Returns a list of cabinets that the user has permission to access
	function GetROCabinetList($passKey, $department) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}
		$cabListArr = getROCabinetList($userName, $department);
		$cabList = array ();
		foreach($cabListArr as $k => $v) {
			$cabList[] = new CabinetInfo($k, $v);
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}CabinetList', $cabList);
	}
	
	function GetDepartmentList($passKey) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied 2');
		}
		$retArr = getDepartmentList($this->db_doc, $userName);
		$depList = array ();
		foreach($retArr as $realName => $arbName) {
			$depList[] = new DepartmentItem($realName, $arbName);
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}DepartmentList', $depList);
	}

	function GetTodoList($passKey, $nodeName) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}

		//Function in /lib/webServices.php
		$retArr = getUserWFIDs($this->db_doc, $userName, $nodeName);
		Return new SOAP_Value('integerList', '{urn:DocutronWebServices2}integerList', $retArr);
	}

	function GetTodoHeaderList($passKey, $nodeName) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}

		//Function in /lib/webServices.php
		$intArr = getUserWFIDs($this->db_doc, $userName, $nodeName);
		$retArr = array ();
		foreach($intArr as $wfTodoID) {
			//if( !is_resource($this->db_doc->connection) ){
			//	$this->db_doc = getDbObject('docutron');
			//}
			
			$res = getTodoId($this->db_doc, $wfTodoID);
			$dept = $res['department'];
			$sArr = array('arb_department');
			$wArr = array('real_department' => $dept);
			$arbDept = getTableInfo($this->db_doc, 'licenses', $sArr, $wArr, 'queryOne');
			$wf_doc_id = $res['wf_document_id'];

			if( $dept == NULL || $wf_doc_id == NULL ){
				return new SOAP_FAULT('Work flow id is invalid');
			}
			//Queries department.wf_documents table for matching wf_document_id
			$db_dept = getDbObject($dept);
			$res = getWf_doc($db_dept, $wf_doc_id);
			$cab = $res['cab'];
			$cabinetID = $res['departmentid'];
			$doc_id = $res['doc_id'];
			$file_id= $res['file_id'];
			//Gets location of body.txt and read in
			$fileLocation = $this->getFileLocation($db_dept, $cab, $doc_id);
			$db_dept->disconnect();

			//Gets header info
			$getHeader = parseEmailHeader($fileLocation."/header.txt");
			$mailedFrom = $getHeader['from'];
			$mailedTo = $getHeader['to'];
			$subject = $getHeader['subject'];
			if (!$subject) $subject = " file_id is ".$file_id;
			$date = $getHeader['date'];
			$dateStr = strftime("%Y-%m-%dT%H:%M:%S%z", strtotime(trim($date)));
			$dateLen = strlen($dateStr);
			$dateStr = substr($dateStr, 0, $dateLen - 2) . ':' . substr($dateStr, $dateLen - 2);
			$myDate = new SOAP_Value('date', 'dateTime', $dateStr);
			$retArr[] = new todoItemHeader((int)$wfTodoID, $mailedFrom, $mailedTo, $subject, $myDate, $arbDept);
		}
		return new SOAP_Value('TodoHeaderList', '{urn:DocutronWebServices2}TodoHeaderList', $retArr);
	}
	
	function GetAttachment($passKey, $department, $cabinetID, $fileID) {
		list($retVal, ) = checkKey($passKey);
        if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject($department);
		//$cabInfo = getCabinets($db_dept, '', $cabinetID);
		$cabInfo = getTableInfo($db_dept, 'departments', array(), array('departmentid' => (int)$cabinetID));
		$row = $cabInfo->fetchRow();
		$cab = $row['real_name'];
		$res = getFileQuery($db_dept, $cab, $fileID);
		$doc_id = $res['doc_id'];
		$fileName = $res['filename'];
		$tab = $res['subfolder'];
		
		$fileLocation = $this->GetFileLocation($db_dept, $cab, $doc_id);
		$fileLocation = $fileLocation."/".$tab."/".$fileName;
		$path_parts = pathinfo($fileLocation);
		$annotefile = $fileLocation.".ann.".$path_parts['extension'];
		//error_log("***get attachment:".$annotefile);
		if (file_exists($annotefile))
		{
			$file = file_get_contents($annotefile);
			//error_log("***got annotated:".$annotefile);
		} else
		{
			$file = file_get_contents($fileLocation);
		}
		

		$db_dept->disconnect();
		$encodedFile = base64_encode($file);
		return new SOAP_Value('encFileData', 'base64Binary', $encodedFile);
	}
	
	//Helper method for GetTodoItem
	// Returns array of attachObjs if email has attachments
	function getAttachList($db_dept, $department, $cab, $cabinetID, $doc_id, $fileLocation) {
		//Get all attachments if any
        $attachArr = array();
        $getAttachInfo = getFolderInfo($db_dept, $cab, $doc_id);
        while( $row = $getAttachInfo->fetchRow() ) {
            $fileName = $row['filename'];
			$filePath = $fileLocation."/".$row['subfolder']."/".$fileName;
			if(file_exists($filePath)) {
				$fileStat = stat($filePath);
				$fileSize = $fileStat['size'];

				$attachment = new fileInfo($row['filename'], (int)$row['id'], (int)$doc_id, $fileSize, $department, (int)$cabinetID);
				$attachArr[] = $attachment;
			}
		}
		return new SOAP_Value('attachments', '{urn:DocutronWebServices2}FileInfoList', $attachArr);
	}

	//Helper method for GetTodoItem
	// Returns file path for doc_id
	function GetFileLocation($db_dept, $cab, $doc_id) {
		global $DEFS;
		$fileLocation = getFolderLocation($db_dept, $cab, $doc_id);
		$fileLocation = str_replace(" ", "/", $fileLocation);
		$fileLocation = $DEFS['DATA_DIR'].'/'.$fileLocation;
		return $fileLocation;
	}

	function UpdateTodoItem($passKey, $department, $wfTodoID, $priority, $notes, $dateDue) {
		list($retVal, ) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}

		try { 
			updateTodoItem($department,$wfTodoID,$priority, $notes, $dateDue);
			return new SOAP_Value('return', 'boolean', true);
		} catch(Exception $e){
			return new SOAP_Value('return', 'boolean', false);
		}
	}
	
	function GetTodoItem($passKey, $wfTodoID) {
		list($retVal, ) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}
		//Queries wf_todo table in docutron for matching wf_todo_id
		$res = getTodoId($this->db_doc, $wfTodoID);
		$dept = $res['department'];
		$wf_doc_id = $res['wf_document_id'];

		if( $dept == NULL || $wf_doc_id == NULL ){
			return new SOAP_FAULT('Work flow id is invalid');
		}
		//Queries department.wf_documents table for matching wf_document_id
		$db_dept = getDbObject($dept);
		$res = getWf_doc($db_dept, $wf_doc_id);
		$cab = $res['cab'];
		$cabinetID = $res['departmentid'];
		$doc_id = $res['doc_id'];
		$file_id = $res['file_id'];
		//Gets location of body.txt and read in
		$fileLocation = $this->getFileLocation($db_dept, $cab, $doc_id);
		if(file_exists($fileLocation."/body.txt")) {
			$encodedBody = file_get_contents($fileLocation."/body.txt");
		} else {
			$encodedBody = "";
		}
		$encVal = new SOAP_Value('bodyText', 'string', $encodedBody);

		//Gets header info
		$getHeader = parseEmailHeader($fileLocation."/header.txt");
		if(file_exists($fileLocation.'/header.txt')) {
			$header = file_get_contents($fileLocation.'/header.txt');
		} else {
			$header = '';
		}
		$mailedTo = $getHeader['to'];
		$mailedFrom = $getHeader['from'];
		$subject = $getHeader['subject'];
		if (!$subject) $subject = " file_id is ".$file_id;
		$date = $getHeader['date'];
		$dateStr = strftime("%Y-%m-%dT%H:%M:%S%z", strtotime(trim($date)));
		$dateLen = strlen($dateStr);
		$dateStr = substr($dateStr, 0, $dateLen - 2) . ':' . substr($dateStr, $dateLen - 2);
		$myDate = new SOAP_Value('date', 'dateTime', $dateStr);
		
		//Gets array of attachments if any
		$attachArr = $this->getAttachList($db_dept, $dept, $cab, $cabinetID, $doc_id, $fileLocation);
		
		$db_dept->disconnect();
		return new todoItem($mailedTo, $mailedFrom, $subject, $myDate, $encVal, $header, $attachArr, $dept);
	}


	function CompleteWorkflowNode($passKey, $wfTodoID)
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}
		return finishWorkflow($this->db_doc, $wfTodoID, $userName);
	}
/*
	function SaveFile($passKey, $department, $userName, $fileIDs, $cabinetID, $docID, $destCabinetID, $destDocID) {
		list($retVal, ) = checkKey($passKey);
        if(!$retVal) {
			return new SOAP_FAULT('Login Permission Denied');
		}
		$db_dept = getDbObject($department);
                $success = copyDocuments($db_dept, $department, $userName, $fileIDs, $cabinet, $docID, $destCabinet, $destDocID);
                if( $success )
                        return new simpleReturn('true', 'File successfully moved');
                else
                        return new simpleReturn('false', 'Failed: File has not been saved');

	}
*/	
	function SearchTopLevel($passKey, $department, $searchStr) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		list($tlsArr, $ctArr) = searchTopLevel($db_dept, $searchStr, $userName);
		$db_dept->disconnect ();
		$retArr = array ();
		foreach($tlsArr as $cabinetID => $resultID) {
			$retArr[] = new CabinetSearchInfo($department, $cabinetID, $resultID, $ctArr[$cabinetID]);
		}
		return new SOAP_Value('return', 'TopLevelSearchInfo', $retArr);
	}
	
	function SearchCabinet($passKey, $department, $cabinetID, $searchArr) {
		//error_log('soapServer::SearchCabinet()');
	//error_log('soapServer::SearchCabinet() department='.$department.' -- searchArr: '.print_r($searchArr, true));
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$newSearchArr = array ();
		foreach($searchArr as $cabItem) {
			$newSearchArr[$cabItem->index] = $cabItem->value;
		}
		list($resultID, $numResults) = searchCabinet($department, (int)$cabinetID, $newSearchArr, $userName);
		if(!$resultID) {
			return new SOAP_Fault('Search Permission Denied');
		}
		return new CabinetSearchInfo($department, (int)$cabinetID, $resultID, $numResults);
	}

	function SearchAudit($passKey, $department, $searchTerms, $dateTime) { 
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}

		$newSearchArr = array ();
		if( is_array($searchTerms) ) {
			foreach($searchTerms as $auditItem) {
				$newSearchArr[$auditItem->index] = $auditItem->value;
			}
		}
		$dateTimeArr = array();
		if( is_array($dateTime) ) {
			foreach($dateTime AS $dateTimeItem) {
				$dateTimeArr[] = $dateTimeItem->operation." '".$dateTimeItem->value."'";
			}
		}
		$resSet = searchAudit($department,$newSearchArr, $dateTimeArr);
		if($resSet === false) {
			return new SOAP_Fault('Search Permission Denied');
		}
		$retArr = array ();
		foreach($resSet AS $info) {
			$retArr[] = new AuditSearchInfo((int)$info['id'],$info['username'],
						$info['datetime'],$info['info'],$info['action']);  
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}AuditList', $retArr);
	}

	function SearchAndReplace($passKey,$department,$cabinetID,$searchArr,$replaceArr) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$newSearchArr = array ();
		foreach($searchArr as $cabItem) {
			$newSearchArr[$cabItem->index] = $cabItem->value;
		}

		$newReplaceArr = array ();
		foreach($replaceArr as $cabItem) {
			$newReplaceArr[$cabItem->index] = $cabItem->value;
		}
		$result = searchAndReplace($department,$cabinetID,$newSearchArr,$newReplaceArr,$userName);
		if($result === false) {
			return new SOAP_Fault('Search Permission Denied');
		}
		return $result;
	}

	function CreateDocumentInfo($passKey, $department, $cabinetID, $docID, $documentName, $indices) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$indiceArr = array ();
		foreach($indices as $myIndex) {
			if (isset($myIndex->index)) {
				$indiceArr[$myIndex->index] = $myIndex->value;
			} else {
				$indiceArr[$myIndex->key] = $myIndex->value;
			}
		}
		$docTabID = createDocumentInfo($department, $cabinetID, $docID, $documentName, $indiceArr, $userName, $this->db_doc);
		if ($docTabID === false) {
			return new SOAP_Fault ('Error Creating Document');
		}
		return $docTabID;
	}
	
	function GetDocumentList($passKey,$department,$cabinetID,$docID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		
		$resSet = getDocumentList($department, $cabinetID, $docID, $userName);
		if($resSet === false) {
			return new SOAP_Fault('Search Permission Denied');
		}
		$retArr = array ();
		foreach($resSet AS $tabID => $myResult) {
			$retArr[] = new DocumentInfo($tabID, $myResult);  
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}DocumentList', $retArr);
	}

	function GetDetailedDocumentList ($passKey, $department, $cabinetID, $docID) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$resSet = getDetailedDocumentList ($department, $cabinetID, $docID, $userName);
		if ($resSet === false) {
			return new SOAP_Fault ('Search Permission Denied');
		}
		$retArr = array ();
		foreach ($resSet as $tabID => $documentInfo) {
			$myDocIndices = array ();
			foreach ($documentInfo['indices'] as $myIndex) {
				$myDocIndices[] = new DocumentIndex (
					$myIndex['indexName'],
					$myIndex['displayName'],
					$myIndex['value']
				);
			}
			$retArr[] = new DetailedDocumentInfo (
				(int)$tabID, $documentInfo['documentName'],
					$documentInfo['type'],
					new SOAP_Value ('documentIndices', 
					'{urn:DocutronWebServices2}DetailedDocumentIndices',
					$myDocIndices)
			);
		}
		return new SOAP_Value ('return', '{urn:DocutronWebServices2}DetailedDocumentList', $retArr);
	}
	
	function GetDetailedDocumentPartialList ($passKey, $department, $cabinetID, $docID, $start, $count) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$resSet = getDetailedDocumentPartialList ($department, $cabinetID, $docID, $userName, $start, $count);
		if ($resSet === false) {
			return new SOAP_Fault ('Search Permission Denied');
		}
		$retArr = array ();
		foreach ($resSet as $tabID => $documentInfo) {
			//error_log('soapServer: '.$tabID.' -- '.print_r($documentInfo, true));
			$myDocIndices = array ();
			foreach ($documentInfo['indices'] as $myIndex) {
				$myDocIndices[] = new DocumentIndex (
					$myIndex['indexName'],
					$myIndex['displayName'],
					$myIndex['value']
				);
			}
			$retArr[] = new DetailedDocumentInfo2 (
				(int)$documentInfo['sequence'],
				(int)$tabID,
				$documentInfo['documentName'],
				$documentInfo['type'],
				new SOAP_Value ('documentIndices', 
					'{urn:DocutronWebServices2}DetailedDocumentIndices',
					$myDocIndices)
			);
		}
		return new SOAP_Value ('return', '{urn:DocutronWebServices2}DetailedDocumentPartialList', $retArr);
	}
	
	function GetDocumentTypeList($passKey,$department) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		
		$resSet = getDocumentTypeList($department, NULL, $userName);
		$retArr = array ();
		foreach($resSet AS $documentID => $myResult) {
			$retIndArr = array();
			foreach($myResult['indices'] AS $index => $value) {
				$retIndArr[] = new DocumentItem($index, $value);
			}
			$retIndArr = new SOAP_Value('indices', '{urn:DocutronWebServices2}DocumentEntry', $retIndArr);
			$docDefArr = array ();
			foreach($myResult['definitions'] as $realName => $listArr) {
				$docDefArr[] = new DocumentDefinition($realName, $listArr); 
			}
			$soapDefArr = new SOAP_Value('definitions', '{urn:DocutronWebServices2}DocumentDefinitionList', $docDefArr);
			$retArr[] = new DocumentType($documentID, $myResult['realName'], $myResult['arbName'], $retIndArr, $soapDefArr);  
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}DocumentTypeList', $retArr);
	}

	function GetFilteredDocumentTypeList($passKey, $department, $cabinetID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$db_dept = getDbObject ($department);
		$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
		$db_dept->disconnect ();
		if($cabName !== false) {
			$resSet = getDocumentTypeList($department, $cabName, $userName);
			$retArr = array ();
			foreach($resSet AS $documentID => $myResult) {
				$retIndArr = array();
				foreach($myResult['indices'] AS $index => $value) {
					$retIndArr[] = new DocumentItem($index, $value);
				}
				$retIndArr = new SOAP_Value('indices', '{urn:DocutronWebServices2}DocumentEntry', $retIndArr);
				$docDefArr = array ();
				foreach($myResult['definitions'] as $realName => $listArr) {
					$docDefArr[] = new DocumentDefinition($realName, $listArr); 
				}
				$soapDefArr = new SOAP_Value('definitions', '{urn:DocutronWebServices2}DocumentDefinitionList', $docDefArr);
				$retArr[] = new DocumentType($documentID, $myResult['realName'], $myResult['arbName'], $retIndArr, $soapDefArr);  
			}
		} else {
			return new SOAP_Fault ('Cabinet Permission Denied');
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}DocumentTypeList', $retArr);
	}

	function GetResultSet($passKey, $department, $cabinetID, $resultID, $startIndex, $numberToFetch) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}

		$resSet = getResultSet($department, $cabinetID, $resultID, $startIndex, $numberToFetch, "admin");
		if($resSet === false) {
			return new SOAP_Fault('Search Permission Denied');
		}
		$retArr = array ();
		foreach($resSet as $docID => $myResult) {
			if (substr (PHP_VERSION, 0, 1) == '4') {
				$xmlDoc = domxml_new_doc('1.0');
				$root = $xmlDoc->create_element('folder');
				$xmlDoc->append_child($root);
				foreach($myResult as $myKey => $myVal) {
					$myEl = $xmlDoc->create_element('field');
					$myEl->set_attribute('index', $myKey);
					$myEl->append_child($xmlDoc->create_text_node($myVal));
					$root->append_child($myEl); 
				}
				$xmlStr = $xmlDoc->dump_mem(false);
			} else {
				$xmlDoc = new DOMDocument (); 
				$root = $xmlDoc->createElement('folder');
				$xmlDoc->appendChild($root);
				foreach($myResult as $myKey => $myVal) {
					//cz 2015-03-20 for "Coaches Choice" 
					$myVal = iconv(mb_detect_encoding($myVal, mb_detect_order(), true), "UTF-8", $myVal);
					$myEl = $xmlDoc->createElement('field');
					$myEl->setAttribute('index', $myKey);
					$myEl->appendChild($xmlDoc->createTextNode($myVal));
					$root->appendChild($myEl); 
				}
				$xmlStr = $xmlDoc->saveXML ();
			}
			$retArr[] = new CabinetFolder($docID, $xmlStr); 
		}

		return new SOAP_Value('return', 'CabinetResultSet', $retArr);
	}

	function GetCabinetIndices($passKey, $department, $cabinetID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$indices = getCabinetIndexFields($department, $cabinetID, $userName);
		if($indices !== false) {
			return new SOAP_Value('return', '{urn:DocutronWebServices2}StringList', $indices);
		} else {
			return new SOAP_Fault('Cabinet Permission Denied');
		}
	}
	
	function GetCabinetIndiceDefinitions($passKey, $department, $cabinetID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$indices = getCabinetIndexDefinitions($department, $cabinetID, $userName);
		if($indices !== false) {
			$fInfo = array(); 
			foreach($indices AS $col) {
				$fInfo[] = new CabinetIndiceDefinitions($col[0],$col[1],$col[2],$col[3]);
			}
			return new SOAP_Value('return', '{urn:DocutronWebServices2}DefinitionArray', $fInfo);
		} else {
			return new SOAP_Fault('Cabinet Permission Denied');
		}
	}
	
	function GetDocumentIndexDefinitions($passKey, $department, $documentType) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$indices = getDocumentIndexDefinitions($department, $documentType, $userName);
		if($indices !== false) {
			$fInfo = array(); 
			foreach($indices AS $col) {
				$fInfo[] = new DocumentIndexDefinitions($col[0],$col[1],$col[2],$col[3]);
			}
			return new SOAP_Value('return', '{urn:DocutronWebServices2}DocumentIndexDefinitionArray', $fInfo);
		} else {
			return new SOAP_Fault('Error');
		}
	}
	

	function GetWorkflowDefs ($passKey, $department) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$workflowDefs = getWorkflowDefs ($userName, $department);
		if ($workflowDefs !== false) {
			$defsArr = array ();
			foreach ($workflowDefs as $defsID => $defsName) {
				$defsArr[] =  new WorkflowDefs ((int)$defsID, $defsName);
			}
			return new SOAP_Value('return', 'WorkflowDefsList', $defsArr);
		} else {
			return new SOAP_Fault ('Permission Denied');
		}
	}

	function CopyOnDocutron($passKey, $sourceFileInfo, $destDepartment, $destCabinetID, $destDocID, $destTabID) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$department = $sourceFileInfo->department;
		$cabID = $sourceFileInfo->cabinetID;
		$fileID = $sourceFileInfo->fileID;
		$docID = $sourceFileInfo->docID;
		$srcArr = array("department" => $department, "cabinetID" => $cabID, "fileID" => $fileID, "docID" => $docID);

		$destArr = array("department" => $destDepartment, "cabinetID" => $destCabinetID, 
					"docID" => $destDocID, "tabID" => $destTabID);
		$db_curDept = getDbObject ($srcArr['department']);
		$db_destDept = getDbObject ($destArr['department']);
		$success = copyDocuments($userName, $srcArr, $destArr, $this->db_doc, $db_curDept, $db_destDept); 
		$db_destDept->disconnect ();
		$db_curDept->disconnect ();
		if($success) {
			return new SOAP_Value('return', 'boolean', true);
		} else {
			return new SOAP_Value('return', 'boolean', false);
		}
	}

	function DoDirExist($passKey, $path) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		
		if( file_exists( $path ))
			return new SOAP_VALUE('return', 'boolean', true);
		else
			return new SOAP_VALUE('return', 'boolean', false);
	}
/*
	function ListDirectory( $key, $path )
	{
		list($retVal, $username) = checkKey($key);
		if($retVal) {
			$dh = opendir( $path );
			while( $str = readdir($dh) )
			{
				if( $str != '.' and $str != '..' )
				{
					$fileArr[] = $str;
				}
			}
			return new SOAP_Value('ArrayOfStrings', '{urn:DocutronWebServices2}ArrayOfStrings', $fileArr ); 
		}
		else
		{
			return new SOAP_Fault( "didn't login" );
		}
	}

	 // Creates folder and returns doc_id of the folder
	function createFolder( $key, $dept, $cabinet, $fields, $tab )
	{
		list( $retVal, $username ) = checkKey( $key );
		if( !isCabWritable( $dept, $username ) )
		{
		
		}
		
	}
*/
	function GetDataDir( $passKey )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		global $DEFS;
		return new SOAP_Value('dataDir', 'string', $DEFS['DATA_DIR']);
	}

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
	
	function GetFolderIndiceValues( $passKey, $department, $cabinetID, $docID )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
			
		$folderValueList = array();

		$folderValues = getFolderValues($userName, $department, $cabinetID, $docID);
		
		foreach($folderValues as $key=>$value)
		{
			$folderValueList[] = new CabinetItem (
			new SOAP_Value ('index', 'string', $key),
			new SOAP_Value ('value', 'string', $value) );
		}
		
		return new SOAP_Value('return', '{urn:DocutronWebServices2}indexDefList', $folderValueList);
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

		return new SOAP_Value('return', '{urn:DocutronWebServices2}autoCompleteList', $autoCompleteList);	
	}

	function GetDatatypeDefinitions( $passKey, $department, $cabinetID )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$indexDefArr = getDatatypeDefinitions( $userName, $department, $cabinetID, $this->db_doc );
		if($indexDefArr === false)
			return new SOAP_Fault('Cabinet does not exist or cabinet permissions denied');

		$indexDefList = array();
		foreach( $indexDefArr AS $indexKey => $indexDef )
			$indexDefList[] = new CabinetItem($indexKey, $indexDef);
		return new SOAP_Value('return', '{urn:DocutronWebServices2}indexDefList', $indexDefList);
	}

	function AddDatatypeDefinitions( $passKey, $department, $cabinetID, $cabIndex, $dataDefList )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal)
			return new SOAP_Fault('Login Permission Denied');

		$message = addDatatypeDefinitions($userName, $department, $cabinetID, $cabIndex, $dataDefList, $this->db_doc);
		if( strcmp($message, "Datatypes added successfully") == 0 )
			$success = true;
		else
			$success = false;

		$return = new simpleReturn($success, $message);
		return new SOAP_Value('return', '{urn:DocutronWebServices2}simpleReturn', $return);
	}

	function ClearDatatypeDefinitions( $passKey, $department, $cabinetID, $cabIndex )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal)
			return new SOAP_Fault('Login Permission Denied');

		$message = clearDatatypeDefinitions($userName, $department, $cabinetID, $cabIndex, $this->db_doc);
		if( strcmp($message, "Datatypes cleared successfully") == 0 )
			$success = true;
		else
			$success = false;

		$return = new simpleReturn($success, $message);
		return new SOAP_Value('return', '{urn:DocutronWebServices2}simpleReturn', $return);
	}

	function DeleteDatatypeDefinitions( $passKey, $department, $cabinetID, $cabIndex, $dataDefList )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal)
			return new SOAP_Fault('Login Permission Denied');

		$message = deleteDatatypeDefinitions($userName, $department, $cabinetID, $cabIndex, $dataDefList, $this->db_doc);
		if( strcmp($message, "Datatypes removed successfully") == 0 )
			$success = true;
		else
			$success = false;

		$return = new simpleReturn($success, $message);
		return new SOAP_Value('return', '{urn:DocutronWebServices2}simpleReturn', $return);
	}

	function GetFolderBarcode( $passKey, $department, $cabinetID, $docID )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		if( !isSet($department) )
			return new SOAP_Fault('Required department not set');

		if( !isSet($cabinetID) )
			return new SOAP_Fault('Required cabinetID not set');

		if( !isSet($docID) )
			return new SOAP_Fault('Required docID not set');

		$barcode = getFolderBarcode($userName, $department, $cabinetID, $docID, 0, $this->db_doc);
		//barcode is a string for future use with wf barcodes
		return $barcode;
	}

	function GetSubfolderBarcode( $passKey, $department, $cabinetID, $docID, $tabID )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		if( !isSet($department) )
			return new SOAP_Fault('Required department not set');

		if( !isSet($cabinetID) )
			return new SOAP_Fault('Required cabinetID not set');

		if( !isSet($docID) )
			return new SOAP_Fault('Required docID not set');

		if( !isSet($tabID) )
			return new SOAP_Fault('Required tabID not set');

		$barcode = getFolderBarcode($userName, $department, $cabinetID, $docID, $tabID, $this->db_doc);
		//barcode is a string for future use with wf barcodes
		return $barcode;
	}
	
	function GetInboxFileList( $passKey, $department, $inboxUser, $folder )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		if( !isSet($department) )
			return new SOAP_Fault('Required department not set');

		$returnList = array();
		$inboxList = getInboxFileList($userName, $department, $inboxUser, $folder, $this->db_doc);
		if($inboxList === false)
			return new SOAP_Fault('Inbox Permission Denied');
			
		foreach( $inboxList AS $filename => $isFolder) {
			$returnList[] = new InboxItem( $isFolder , $filename);
		}
		
		return new SOAP_Value('return', '{urn:DocutronWebServices2}inboxFileList', $returnList);
	}

	function UploadToInbox( $passKey, $department, $inboxUser, $folder, $filename, $encodedFile )
	{
		list($retVal, $userName) = checkKey($passKey);

$fp = fopen( 'UploadToInbox.log', 'a+' );
fwrite( $fp, $userName."\r\n");


		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		if( !isSet($department) )
			return new SOAP_Fault('Required department not set');

	  if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $encodedFile)) {
fwrite( $fp, "*".$encoded."*needed to decode\r\n");
			$encodedFile=base64_decode($encodedFile);
	  } else {
	      $encoded=0;
	  }
fwrite( $fp, "department:".$department.", inboxUser:".$inboxUser.", filename:".$filename."\r\n");
fclose($fp);
		$newFile = uploadToInbox($userName, $department, $inboxUser, $folder, $filename, $encodedFile, $this->db_doc);
		if($newFile !== false)
			return new simpleReturn(true, $newFile);
		else
			return new simpleReturn(false, "File failed to upload");
	}

	function DownloadFromInbox( $passKey, $department, $inboxUser, $folder, $filename )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		if( !isSet($department) )
			return new SOAP_Fault('Required department not set');
		$encodedFile = downloadFromInbox($userName,$department,$inboxUser,$folder,$filename,$this->db_doc);
		if($encodedFile === false){
			error_log( 'Soap Fault: '.$filename );
			return new SOAP_Fault('Inbox Access Denied for File Download');
		}
		return base64_encode($encodedFile);
	}

	function CreateInboxFolder( $passKey, $department, $inboxUser, $folder )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');
		
		if( !isSet($department) )
			return new SOAP_Fault('Required department not set');

		if( !isSet($folder) )
			return new SOAP_Fault('Required folder not set');


		$newFolder = createInboxFolder($userName, $department, $inboxUser, $folder, $this->db_doc);
		if($newFolder !== false)
			return new simpleReturn(true, $newFolder);
		else
			return new simpleReturn(false, "Inbox Folder Not Created");
	}

	function GetSavedTabs( $passKey, $department, $cabinetID )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		//Returns an array		
		$savedTabs = getCabSavedTabs($userName, $department, $cabinetID, $this->db_doc);
		if($savedTabs === -1)
			return new SOAP_Fault('Cabinet does not exist or cabinet permissions denied');
		else
			return new SOAP_Value('return', '{urn:DocutronWebServices2}StringList', $savedTabs);
	}

	function GetUploadUsername( $passKey, $department )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$upload_username = getUploadUsername($userName, $department, $this->db_doc);
		if($upload_username === false)
			return new SOAP_Fault('Get upload username failed');
		else
			return $upload_username;
	}

	function GetUploadPassword( $passKey, $department )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$upload_password = getUploadPassword($userName, $department, $this->db_doc);
		if($upload_password === false)
			return new SOAP_Fault('Get upload password failed');
		else
			return $upload_password;
	}

	function GetCabinetInfo( $passKey, $department, $cabinetID )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$cabinetInfo = getCabinetInfoXML( $userName, $department, $cabinetID, $this->db_doc );
		if( $cabinetInfo === false )
			return new SOAP_Fault('Cabinet Access Denied');
		else
			return $cabinetInfo;
	}

	function IsDocumentView($passKey, $department, $cabinetID)
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
			return new SOAP_Fault('Login Permission Denied');
		}
		$isDoc = isDocumentView($userName, $department, $cabinetID);
		return new SOAP_Value('return', 'boolean', $isDoc);

	}

	function GetTVBarcode($passKey,$application_number,$ccan_number,$Lessee_name,$sub_type_name){
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		if (!$application_number){
			return new SOAP_Fault ('No ProposalNumber received');
		}
		$ADPTabs=array();
		$ADPTabs[18]="15_Percent_Provision_Letter-MELA";
		$ADPTabs[19]="ADP_Leasing_D_and_A";
		$ADPTabs[20]="ADP_Leasing_Master_Lease_Agreement";
		$ADPTabs[24]="Amendment_-_Change_Legal_Entity_and_CCAN";
		$ADPTabs[25]="Amendment_-_Name_Change";
		$ADPTabs[26]="Bill_of_Sale";
		$ADPTabs[27]="Canada_AutoDebit_-_English";
		$ADPTabs[28]="Canada_AutoDebit_-_French";
		$ADPTabs[29]="Canada_D_and_A_-_English";
		$ADPTabs[30]="Canada_D_and_A_-_French";
		$ADPTabs[32]="Canada_Fast_Lease_-_English";
		$ADPTabs[33]="Canada_Fast_Lease_-_English_with_EPO";
		$ADPTabs[34]="Canada_Fast_Lease_-_French";
		$ADPTabs[35]="Canada_Fast_Lease_-_French_with_EPO";
		$ADPTabs[36]="Canada_Fast_Lease_D_and_A_-_English";
		$ADPTabs[37]="Canada_Fast_Lease_D_and_A_-_French";
		$ADPTabs[38]="Canada_Invoice_-_English";
		$ADPTabs[39]="Canada_Invoice_-_French";
		$ADPTabs[40]="Canada_Master_Lease_Agreement_-_English";
		$ADPTabs[41]="Canada_Master_Lease_Agreement_-_French";
		$ADPTabs[42]="Canada_Schedule_-_English";
		$ADPTabs[43]="Canada_Schedule_-_French";
		$ADPTabs[44]="Canada_Schedule_with_EPO_-_English";
		$ADPTabs[45]="Canada_Schedule_with_EPO_-_French";
		$ADPTabs[46]="Canada_Supplemental_Agreement_-_English";
		$ADPTabs[47]="Canada_Supplemental_Agreement_-_French";
		$ADPTabs[48]="Co-Lessee_Addendum";
		$ADPTabs[49]="Cover_Letter";
		$ADPTabs[50]="D_and_A";
		$ADPTabs[51]="Drawdown_Authorization";
		$ADPTabs[52]="Equipment_Release_Form";
		$ADPTabs[54]="Fast_Lease";
		$ADPTabs[55]="Fast_Lease_D_and_A";
		$ADPTabs[56]="Funding_Sheet";
		$ADPTabs[57]="Insurance_Form";
		$ADPTabs[58]="Invoice";
		$ADPTabs[59]="Master_Lease_Agreement";
		$ADPTabs[60]="Master_Lease_Line_of_Credit_Agreement";
		$ADPTabs[61]="One_Way_Letter_-_Advance_to_Arrears";
		$ADPTabs[62]="Partial_Unwind_Letter";
		$ADPTabs[63]="Pay_Proceeds_Authorization";
		$ADPTabs[64]="Prom_Note";
		$ADPTabs[65]="Line_of_Credit_Permanent_Financing_Schedule";
		$ADPTabs[68]="Schedule";
		$ADPTabs[69]="Supplemental_Agreement";
		$ADPTabs[70]="Third_Party_Payoff_Agreement";
		$ADPTabs[71]="Unconditional_Guaranty_-_Lease";
		$ADPTabs[72]="Unconditional_Guaranty_-_PromNote";
		$ADPTabs[75]="US_AutoDebit";
		$ADPTabs[76]="ADP_Leasing_Schedule";
		$ADPTabs[78]="Canada_IDMS_Pay_Proceeds";
		$ADPTabs[79]="ADPCL_PromNote_Oct2007";
		$ADPTabs[80]="Escrow_Agreement";
		$ADPTabs[81]="IDMS_Pay_Proceeds";
		$ADPTabs[82]="Bill_of_Sale_Canada_IDMS";
		$ADPTabs[83]="SECURITY_AGREEMENT";
		$ADPTabs[106]="15_Percent_Provision_Letter-Fast_Lease";
		$ADPTabs[138]="FRANCHISE_ADDENDUM_TO_MELA_-_CANADA";
		$ADPTabs[139]="FRANCHISE_ADDENDUM_TO_ELA_-_CANADA";
		$ADPTabs[140]="FRANCHISE_ADDENDUM_TO_ELA_-_USA";
		$ADPTabs[141]="FRANCHISE_ADDENDUM_TO_FRENCH_ELA_-_CANADA";
		$ADPTabs[142]="FRANCHISE_ADDENDUM_TO_FRENCH_MELA_-_CANADA";
		$ADPTabs[143]="FRANCHISE_ADDENDUM_TO_MELA_-_USA";
		$ADPTabs[162]="Funding_Sheet_for_CAF";
		$ADPTabs[448]="CO-LESSEE_Addendum_Multi";
		$ADPTabs[449]="CO-LESSEE_Addendum_PR";
		$ADPTabs[450]="CO-LESSEE_Addendum_USA";
		$ADPTabs[451]="CO-LESSEE_Addendum_FrenchCanada";
		$ADPTabs[452]="CO-LESSEE_AddendumCanada";
		$ADPTabs[453]="Corporate_Guaranty";
		$ADPTabs[454]="Personal_Guaranty_ADPCL";
		$ADPTabs[455]="Personal_Guaranty_ADPL-Puerto_Rico";
		$ADPTabs[456]="Personal_Guaranty_PromNote";
//select * from settings where k='ADPCL_20100525_tabs' and department='client_files168';		
//update settings set value="15_Percent_Provision_Letter-MELA,ADP_Leasing_D_and_A,ADP_Leasing_Master_Lease_Agreement,Amendment_-_Change_Legal_Entity_and_CCAN,Amendment_-_Name_Change,Bill_of_Sale,Canada_AutoDebit_-_English,Canada_AutoDebit_-_French,Canada_D_and_A_-_English,Canada_D_and_A_-_French,Canada_Fast_Lease_-_English,Canada_Fast_Lease_-_English_with_EPO,Canada_Fast_Lease_-_French,Canada_Fast_Lease_-_French_with_EPO,Canada_Fast_Lease_D_and_A_-_English,Canada_Fast_Lease_D_and_A_-_French,Canada_Invoice_-_English,Canada_Invoice_-_French,Canada_Master_Lease_Agreement_-_English,Canada_Master_Lease_Agreement_-_French,Canada_Schedule_-_English,Canada_Schedule_-_French,Canada_Schedule_with_EPO_-_English,Canada_Schedule_with_EPO_-_French,Canada_Supplemental_Agreement_-_English,Canada_Supplemental_Agreement_-_French,Co-Lessee_Addendum,Cover_Letter,D_and_A,Drawdown_Authorization,Equipment_Release_Form,Fast_Lease,Fast_Lease_D_and_A,Funding_Sheet,Insurance_Form,Invoice,Master_Lease_Agreement,Master_Lease_Line_of_Credit_Agreement,One_Way_Letter_-_Advance_to_Arrears,Partial_Unwind_Letter,Pay_Proceeds_Authorization,Prom_Note,Line_of_Credit_Permanent_Financing_Schedule,Schedule,Supplemental_Agreement,Third_Party_Payoff_Agreement,Unconditional_Guaranty_-_Lease,Unconditional_Guaranty_-_PromNote,US_AutoDebit,ADP_Leasing_Schedule,Canada_IDMS_Pay_Proceeds,ADPCL_PromNote_Oct2007,Escrow_Agreement,IDMS_Pay_Proceeds,Bill_of_Sale_Canada_IDMS,SECURITY_AGREEMENT,15_Percent_Provision_Letter-Fast_Lease,FRANCHISE_ADDENDUM_TO_MELA_-_CANADA,FRANCHISE_ADDENDUM_TO_ELA_-_CANADA,FRANCHISE_ADDENDUM_TO_ELA_-_USA,FRANCHISE_ADDENDUM_TO_FRENCH_ELA_-_CANADA,FRANCHISE_ADDENDUM_TO_FRENCH_MELA_-_CANADA,FRANCHISE_ADDENDUM_TO_MELA_-_USA,Acknowledgement_Letter,UCC_Financing_Statement,Wire,Dealer_Services_Contracts_and_Post_Purchase_Adjustments,Authorization_for_CCAN_Change,Addendum,Advance_Rental_Check,Amortization_Schedule,Amendment,Acknowledgement_of_Assignment,Buyout_Quote,Credit_Application,Client_Account_Agreement,Check,Check_Request,Chax,Certificate_of_Acceptance,Certificate_of_Incumbency,Credit_Scoring,Disbursement_Form,Disposition_Report,Extension_and_Renewal,Equipment_Lease,Equipment_Release_Authorization,Equipment_Rental_and_Usage_Agreement,Financial_Statement,Insurance_Certificate,Loan_Agreement,Lightspeed,Letter,Third_Party_Payoff,Document_Review,Security_Agreement,Secretary_Certificate,Sales_Order,Transfer_Assumption,Tax_Certificate,Unwind,Letter_of_Credit,CO-LESSEE_Addendum_Multi,CO-LESSEE_Addendum_PR,CO-LESSEE_Addendum_USA,CO-LESSEE_Addendum_FrenchCanada,CO-LESSEE_AddendumCanada,Corporate_Guaranty,Personal_Guaranty_ADPCL,Personal_Guaranty_ADPL-Puerto_Rico,Personal_Guaranty_PromNote" where k = 'ADPCL_20100525_tabs' and department='client_files168';



		if ($ADPTabs[$sub_type_name]){
			$db = getDbObject('docutron');
			$department = 'client_files168';
			if ($sub_type_name=='162'){
				$cabinetID = 9;
				$cabinet = "CAF";
			} else {
				$cabinetID = 11;
				$cabinet = "ADPCL 20100525";
			}
			$xmlStr = '<barcode>';
			$xmlStr.= '<department value="'.$department.'" />';
			$xmlStr.= '<cabinetID value="'.$cabinetID.'" />';
			$xmlStr.= '<cabinet value="'.$cabinet.'" />';
			$xmlStr.= '<terms>';
			$xmlStr.= '<term index="application_number" value="'.$application_number.'" />';
			$xmlStr.= '</terms>';
			$xmlStr.= '<deletebc value="1" />';
			$xmlStr.= '<scanformat value="stif" />';
			$xmlStr.= '<sendimage value="'.$ADPTabs[$sub_type_name].'" />';
			$xmlStr.= '<user value="'.$userName.'" />';
			$xmlStr.= '<compress value="0" />';
			$xmlStr.= '<searchtype value="searchcreate" />';
			$xmlStr.= '<getTabsBC value="1" />';
			$xmlStr.= '</barcode>';	
			$xmlRet=getBarcodeInfo( $userName, $xmlStr );
			$domDoc = new DOMDocument();
			$domDoc->loadXML($xmlRet);
			$docIDs=$domDoc->getElementsByTagName('docID');
		  $xmldocID = $docIDs->item(0); 
			$retStr=array();
			$docID=$xmldocID->getAttribute('value');
		
			$tabIDs = $domDoc->getElementsByTagName('tab');
	    $barcodeStr= '';
			
			foreach( $tabIDs as $tabID ) 
			{ 
				
				if ($tabID->getAttribute('value')==$ADPTabs[$sub_type_name]){
			    $barcodeStr= $tabID->getAttribute('barcodeStr');
				} else if ($tabID->getAttribute('value')=='main'){
			    $barcodeStr1= $tabID->getAttribute('barcodeStr');
				} else {
					$query="delete from barcode_reconciliation where id=".$tabID->getAttribute('barcode');
					$res = $db->queryAll( $query );
				}
			}
			if ($barcodeStr=='') $barcodeStr=$barcodeStr1;
			// update the folder
			$indiceArr = array ();
			$indiceArr['ccan_number'] = $ccan_number;
			$indiceArr['Lessee_name'] = $Lessee_name;
			$db_dept = getDbObject ($department);
			$ret = updateCabinetFolder($db_dept, $cabinetID, $docID, $indiceArr, $userName);
			$db_dept->disconnect ();

			return new GetTVInfo (new SOAP_Value ('docID', 'int', $docID), new SOAP_Value('barcodeStr', 'base64Binary', $barcodeStr));
		} else {
			return new SOAP_Fault('sub_type_name not recognized');
		}
	}
	function UploadFileFromTV($passKey,$docID,$sub_type_name,$filename,$encodedFile){
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$ADPTabs=array();
		$ADPTabs[18]="15_Percent_Provision_Letter-MELA";
		$ADPTabs[19]="ADP_Leasing_D_and_A";
		$ADPTabs[20]="ADP_Leasing_Master_Lease_Agreement";
		$ADPTabs[24]="Amendment_-_Change_Legal_Entity_and_CCAN";
		$ADPTabs[25]="Amendment_-_Name_Change";
		$ADPTabs[26]="Bill_of_Sale";
		$ADPTabs[27]="Canada_AutoDebit_-_English";
		$ADPTabs[28]="Canada_AutoDebit_-_French";
		$ADPTabs[29]="Canada_D_and_A_-_English";
		$ADPTabs[30]="Canada_D_and_A_-_French";
		$ADPTabs[32]="Canada_Fast_Lease_-_English";
		$ADPTabs[33]="Canada_Fast_Lease_-_English_with_EPO";
		$ADPTabs[34]="Canada_Fast_Lease_-_French";
		$ADPTabs[35]="Canada_Fast_Lease_-_French_with_EPO";
		$ADPTabs[36]="Canada_Fast_Lease_D_and_A_-_English";
		$ADPTabs[37]="Canada_Fast_Lease_D_and_A_-_French";
		$ADPTabs[38]="Canada_Invoice_-_English";
		$ADPTabs[39]="Canada_Invoice_-_French";
		$ADPTabs[40]="Canada_Master_Lease_Agreement_-_English";
		$ADPTabs[41]="Canada_Master_Lease_Agreement_-_French";
		$ADPTabs[42]="Canada_Schedule_-_English";
		$ADPTabs[43]="Canada_Schedule_-_French";
		$ADPTabs[44]="Canada_Schedule_with_EPO_-_English";
		$ADPTabs[45]="Canada_Schedule_with_EPO_-_French";
		$ADPTabs[46]="Canada_Supplemental_Agreement_-_English";
		$ADPTabs[47]="Canada_Supplemental_Agreement_-_French";
		$ADPTabs[48]="Co-Lessee_Addendum";
		$ADPTabs[49]="Cover_Letter";
		$ADPTabs[50]="D_and_A";
		$ADPTabs[51]="Drawdown_Authorization";
		$ADPTabs[52]="Equipment_Release_Form";
		$ADPTabs[54]="Fast_Lease";
		$ADPTabs[55]="Fast_Lease_D_and_A";
		$ADPTabs[56]="Funding_Sheet";
		$ADPTabs[57]="Insurance_Form";
		$ADPTabs[58]="Invoice";
		$ADPTabs[59]="Master_Lease_Agreement";
		$ADPTabs[60]="Master_Lease_Line_of_Credit_Agreement";
		$ADPTabs[61]="One_Way_Letter_-_Advance_to_Arrears";
		$ADPTabs[62]="Partial_Unwind_Letter";
		$ADPTabs[63]="Pay_Proceeds_Authorization";
		$ADPTabs[64]="Prom_Note";
		$ADPTabs[65]="Line_of_Credit_Permanent_Financing_Schedule";
		$ADPTabs[68]="Schedule";
		$ADPTabs[69]="Supplemental_Agreement";
		$ADPTabs[70]="Third_Party_Payoff_Agreement";
		$ADPTabs[71]="Unconditional_Guaranty_-_Lease";
		$ADPTabs[72]="Unconditional_Guaranty_-_PromNote";
		$ADPTabs[75]="US_AutoDebit";
		$ADPTabs[76]="ADP_Leasing_Schedule";
		$ADPTabs[78]="Canada_IDMS_Pay_Proceeds";
		$ADPTabs[79]="ADPCL_PromNote_Oct2007";
		$ADPTabs[80]="Escrow_Agreement";
		$ADPTabs[81]="IDMS_Pay_Proceeds";
		$ADPTabs[82]="Bill_of_Sale_Canada_IDMS";
		$ADPTabs[83]="SECURITY_AGREEMENT";
		$ADPTabs[106]="15_Percent_Provision_Letter-Fast_Lease";
		$ADPTabs[138]="FRANCHISE_ADDENDUM_TO_MELA_-_CANADA";
		$ADPTabs[139]="FRANCHISE_ADDENDUM_TO_ELA_-_CANADA";
		$ADPTabs[140]="FRANCHISE_ADDENDUM_TO_ELA_-_USA";
		$ADPTabs[141]="FRANCHISE_ADDENDUM_TO_FRENCH_ELA_-_CANADA";
		$ADPTabs[142]="FRANCHISE_ADDENDUM_TO_FRENCH_MELA_-_CANADA";
		$ADPTabs[143]="FRANCHISE_ADDENDUM_TO_MELA_-_USA";
		$ADPTabs[162]="Funding_Sheet_for_CAF";
		$ADPTabs[448]="CO-LESSEE_Addendum_Multi";
		$ADPTabs[449]="CO-LESSEE_Addendum_PR";
		$ADPTabs[450]="CO-LESSEE_Addendum_USA";
		$ADPTabs[451]="CO-LESSEE_Addendum_FrenchCanada";
		$ADPTabs[452]="CO-LESSEE_AddendumCanada";
		$ADPTabs[453]="Corporate_Guaranty";
		$ADPTabs[454]="Personal_Guaranty_ADPCL";
		$ADPTabs[455]="Personal_Guaranty_ADPL-Puerto_Rico";
		$ADPTabs[456]="Personal_Guaranty_PromNote";
		if ($sub_type_name=='162'){
			$cabinetID = 9;
			$cabinet = "CAF";
		} else {
			$cabinetID = 11;
			$cabinet = "ADPCL 20100525";
		}
		$real_cabinet = str_replace(' ','_',$cabinet);
		$department = 'client_files168';
		$db = getDbObject($department);
		$subfolder=$ADPTabs[$sub_type_name];
		$filename=str_replace("C:\Temp\MaxTempDocument", "", $filename);
		
		$query="select * from ".$real_cabinet."_files where doc_id=".$docID." and subfolder='".$subfolder."' order by id DESC";
		if ($res=$db->queryAll( $query )) {
			$tabID=$res[0]['id'];
			if ($res[0]['filename']=='') {
				//upload file
				$fileID = uploadFileToFolder($userName, $department, $cabinetID, $docID, $tabID, basename($filename), base64_decode($encodedFile), $this->db_doc, $db);
			} else {
				$encFileData = '';
				$retVal = checkOutFile ($userName, $department, $cabinetID, $tabID, $encFileData);
				$retVal2 = checkInFile ($userName, $department, $cabinetID, $tabID, basename($filename), base64_decode($encodedFile), $this->db_doc);
			}
			$db->disconnect ();
			$retval='Success';
			return $retval;
		} else {
			$db->disconnect ();
			$retval='DocID or sub-type not recognized';
			return $retval;
		}
	}

	function GetBarcodeInfo( $passKey, $xmlStr )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$retXML = getBarcodeInfo( $userName, $xmlStr );
		return $retXML;
	}

	function GetBarcodeImage( $passKey, $barcode )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$retStr = getBarcodeImage( $userName, $barcode );
		return $retStr;

	}

	function CreateSubfolder( $passKey, $department, $cabinetID, $docID, $subfolderName )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$tabID = createSubfolder( $userName, $department, $cabinetID, $docID, $subfolderName );
		if( is_numeric($tabID) && $tabID > 0 )
			return new simpleReturn(true, $tabID);
		else
			return new simpleReturn(false, $tabID);
	}

	function GetUserBarcode( $passKey, $userLookupName, $department )
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) 
			return new SOAP_Fault('Login Permission Denied');

		$db_dept = getDbObject ($department);
		$userBarcode = getUserBarcode($userLookupName, $department, $db_dept, $this->db_doc);
		$db_dept->disconnect ();
		return $userBarcode;
	}

	function GetDepartmentUserList ($passKey, $department)
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal)  {
			return new SOAP_Fault('Login Permission Denied');
		}
		$userList = getDepartmentUserList ($userName, $department);
		if ($userList !== false) {
			return new SOAP_Value ('return', '{urn:DocutronWebServices2}StringList', $userList);
		} else {
			return new SOAP_Fault ('Permission Denied');
		}
	}
	
	function AssignWorkflow ($passKey, $department, $cabinetID, $docID, $tabID, $wfDefsID, $wfOwner)
	{
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal)  {
			return new SOAP_Fault('Login Permission Denied');
		}
		return assignWorkflow ($userName, $department, $cabinetID, $docID, $tabID, $wfDefsID, strtolower($wfOwner), $this->db_doc);
		
	}

	function HasWorkflowInProgress($passKey, $department, $cabinetID, $docID, $tabID ) {
			
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal)  {
			return new SOAP_Fault('Login Permission Denied');
		}
		return hasWorkflowInProgress($userName, $department, $cabinetID, $docID, $tabID);
	}
	
	function GetFileVersion ($passKey, $department, $cabinetID, $fileID) {
		list ($retVal, $userName) = checkKey($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$retVal = getFileVersion ($userName, $department, $cabinetID, $fileID);
		if($retVal == -1) {
			return new SOAP_Fault ("Cabinet access denied");
		}
		else {
			return $retVal;
		}
	}
		
	function GetFolderID ($passKey, $department, $cabinetID, $fileID) {
		list ($retVal, $userName) = checkKey($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$retVal = getFolderID ($userName, $department, $cabinetID, $fileID);
		if($retVal == -1) {
			return new SOAP_Fault ("Cabinet access denied");
		}
		else {
			return $retVal;
		}
	}
	
	function CheckOutFile ($passKey, $department, $cabinetID, $fileID) {
		list ($retVal, $userName) = checkKey($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$encFileData = '';
		$retVal = checkOutFile ($userName, $department, $cabinetID, $fileID, $encFileData);
		if ($retVal !== false) {
			return new CheckedOutFileInfo (new SOAP_Value ('fileName', 'string', $retVal), new SOAP_Value('encFileData', 'base64Binary', base64_encode($encFileData)));
		} else {
			return new SOAP_Fault ('Permission Denied');
		}
	}

	function CheckInFile ($passKey, $department, $cabinetID, $fileID, $fileName, $encFileData) {
		list ($retVal, $userName) = checkKey($passKey);
//$fp = fopen( 'CheckInFile.log', 'a+' );
//fwrite( $fp, $userName."\r\n");
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
	  if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $encFileData)) {
//fwrite( $fp, "*".$encoded."*needed to decode\r\n");
			$encFileData=base64_decode($encFileData);
	  } else {
	      $encoded=0;
	  }
//fwrite( $fp, "department:".$department.", cabinetID:".$cabinetID.", filename:".$fileName.", fileID:".$fileID."\r\n");
//fclose($fp);
		return checkInFile ($userName, $department, $cabinetID, $fileID, $fileName, $encFileData, $this->db_doc);
	}

	function UpdateDocumentIndices ($passKey, $department, $cabinetID, $docID, $tabID, $updatedIndices) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$indexArr = array ();
		foreach($updatedIndices as $myIndex) {
			$indexArr[$myIndex->index] = $myIndex->value;
		}
		return updateDocumentIndices ($userName, $department, $cabinetID, $docID, $tabID, $indexArr);
	}

	function MoveFolder($passKey, $department, $cabinetID, $docID, $destCabinetID, $copy) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$newDocID = moveFolderBetweenCabs($userName, $department, $cabinetID, $docID, $destCabinetID, $copy);
		if( is_numeric($newDocID) AND $newDocID > 0 ) {
			return new simpleReturn(true, (string)$newDocID);
		} else {
			//$newDocID contains error message if it is not a doc_id
			return new simpleReturn(false, $newDocID);
		}
	}

	function MoveDocument($passKey, $department, $cabinetID, $docID, $subfolderID, $destCabinetID, $destDocID, $copy) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}

		$message = moveDocumentBetweenCabs($userName, $department, $cabinetID, $docID, $subfolderID, $destCabinetID, $destDocID, $copy);
		if($message === true) {
			return new simpleReturn(true, "Document successfully moved");
		} else {
			//$message contains error message if it is not === true
			return new simpleReturn(false, $message);
		}
	}

	function ZipSearchResults($passKey, $department, $cabinetID, $searchResultsID, $fileName) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}

		//$searchResultsID is the temp_table
		$retFilename = zipSearchResults($userName, $department, $cabinetID, $searchResultsID, $fileName);
		if($retFilename === false) {
			return new SOAP_Fault ('Error zipping file');
		} else {
			return $retFilename;
		}
	}

	function ZipFileIDArray($passKey, $department, $cabinetID, $fileIDArray, $fileName) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}

		$retFilename = zipFileIDArray($userName, $department, $cabinetID, $fileIDArray, $fileName);
		if($retFilename === false) {
			return new SOAP_Fault ('Error zipping file');
		} else {
			return $retFilename;
		}
	}

	function ListZipFiles($passKey, $department, $cabinetID) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$retZipArr = array();
		$tempArr = listZipFiles($department, $cabinetID, $userName);
		if( !is_array($tempArr) ) {
			return new SOAP_Fault ('Permission Denied');
		}
		foreach($tempArr AS $zipInfo) {
			$fileSize = new SOAP_Value('fileSize', 'double', $zipInfo['fileSize']);
			$retZipArr[] = new ZipFileInfo($zipInfo['fileName'], $fileSize);
		}
		return $retZipArr;
	}

	function DeleteZipFile($passKey, $department, $cabinetID, $zipFileName) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		
		$success = deleteZipFile($department, $cabinetID, $zipFileName, $userName);
		if($success === true) {
			return new simpleReturn(true, "$zipFileName successfully deleted");
		} else {
			return new simpleReturn(false, "Zip file $zipFileName failed to be deleted");
		}
	}

	function ListImportDir($passKey, $department) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}

		$importDirList = getImportDirList ($userName, $department);
		if ($importDirList !== false) {
			return new SOAP_Value ('return', '{urn:DocutronWebServices2}StringList', $importDirList);
		} else {
			return new SOAP_Fault ('Permission Denied');
		}
	}

	function ImportDirectory($passKey, $department, $cabinetID, $opCabinetID, $opDocID, $maxFileSize, $fileExtensions, $generateTemplate, $postAction) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}

		list($success, $message) = importDirectory($userName, $department, $cabinetID, $opCabinetID, $opDocID, $maxFileSize, 
													$fileExtensions, $generateTemplate, $postAction);
		return new simpleReturn($success, "$message");
	}

	function FilenameAdvSearch($passKey, $department, $cabinetID, $filename) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		list($resultID, $numResults) = filenameAdvSearch($userName, $department, (int)$cabinetID, $filename);
		if(!$resultID) {
			return new SOAP_Fault('Search Permission Denied');
		}
		return new CabinetSearchInfo($department, (int)$cabinetID, $resultID, $numResults);
	}

	function GetFileResultSet($passKey, $department, $cabinetID, $resultID, $startIndex, $numberToFetch) {
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}

		$fileRes = getFileResultSet($department, $cabinetID, $resultID, $startIndex, $numberToFetch, $userName);
		$fileList = array();
		foreach($fileRes AS $fileID => $fileInfo) {
			$ref = new fileInfo($fileInfo['filename'], $fileID, $fileInfo['doc_id'], $fileInfo['file_size'], $department, $cabinetID);
			$fileList[] =& $ref;
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}FileInfoList', $fileList);
	}
	function GetADPFileList($passKey, $contract_number,$ccan_number) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$department='client_files168';
		$cabinetID=8;
		$results=getADPFileList($department,$cabinetID,$contract_number,$ccan_number);
		if($results === false) {
			return new SOAP_Fault("No Records Found");
		}
		$ADPFileList = array ();
		foreach($results as $myFile) {
			$ADPFileList[] = new ADPfileInfo($myFile['filename'], (int)$myFile['id'], (int)$myFile['docid'], (int)$myFile['file_size'], $department, $cabinetID, $myFile['subfolder']); 
		}
		return new SOAP_Value('return', '{urn:DocutronWebServices2}ADPFileInfoList', $ADPFileList);
	}
	
	function GetADPInvoiceFile($passKey, $invoice_number) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$department='client_files168';
		$cabinetID=6;
		$db_dept = getDbObject ($department);
		$query="select id, Invoice.doc_id as docid,file_size,parent_filename,subfolder,filename from Invoice,Invoice_files where display=1 and filename is not NULL and Invoice.doc_id=Invoice_files.doc_id and invoice_number='".$invoice_number."'";
		$results=$db_dept->queryAll($query);
		$db_dept->disconnect ();
		if (count($results)<1) {
      return new SOAP_Fault('No matches to that invoice');
		}
		foreach($results as $myFile) {
			$fileID = (int)$myFile['id']; 
		}
		$db_dept = getDbObject($department);
		//$cabInfo = getCabinets($db_dept, '', $cabinetID);
		$cabInfo = getTableInfo($db_dept, 'departments', array(), array('departmentid' => (int)$cabinetID));
		$row = $cabInfo->fetchRow();
		$cab = $row['real_name'];
		$res = getFileQuery($db_dept, $cab, $fileID);
		$doc_id = $res['doc_id'];
		$fileName = $res['filename'];
		$tab = $res['subfolder'];
		
		$fileLocation = $this->GetFileLocation($db_dept, $cab, $doc_id);
		$fileLocation = $fileLocation."/".$tab."/".$fileName;
		$file = file_get_contents($fileLocation);

		$db_dept->disconnect();
		$encodedFile = base64_encode($file);
		return new SOAP_Value('encFileData', 'base64Binary', $encodedFile);
	}
	
	function GetDefaultDept($passKey) {
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		$db_doc = getDbObject ('docutron');
		$query="select arb_department,real_department from users,db_list,licenses where username='".$userName."' and db_list_id=list_id and db_name=real_department and default_dept=1";
		$results=$db_doc->queryAll($query);
		$fInfo=new DepartmentItem($results[0]['real_department'],$results[0]['arb_department']);
		$db_doc->disconnect ();
		return new SOAP_Value('return', '{urn:DocutronWebServices2}DepartmentItem', $fInfo);
	}
	function DownloadCabinetFile($passKey, $department, $cabinetID, $fileID, $location) {
		//cron to clean it out? look on windows server for a tmp directory or download directory
		//look up file, copy to temp, send back location
		error_log("dept: ".$department." cab:".$cabinetID." fileID: ".$fileID." loc:".$location);
		list($retVal, $userName) = checkKey($passKey);
		if(!$retVal) {
            return new SOAP_Fault('Login Permission Denied');
		}
		global $DEFS;
		$db_dept = getDbObject ($department);
		$cabName = hasAccess($db_dept, $userName, $cabinetID, false);
		$results = getTableInfo($db_dept,
			array($cabName."_files", $cabName),  //table
			array('location', 'subfolder','filename'),  // SELECT columns
			array($cabName.'_files.doc_id='.$cabName.'.doc_id', $cabName.'_files.deleted=0', $cabName.'_files.id='.$fileID), //WHERE
			'queryAll' // type of query
		);
		$db_dept->disconnect ();
		$file=$DEFS['DATA_DIR']."/".str_replace(" ","/",$results[0]['location'])."/".$results[0]['subfolder']."/".$results[0]['filename'];
		if (is_file($file)){
			$newfile=$DEFS['DOC_DIR']."/".$location."/".$results[0]['filename'];
			copy($file, $newfile);
		}
		
		return "/".$location."/".$results[0]['filename'];
	}
	function GetBarcodeNumber($passKey,$department,$cabinet,$indices,$primary,$tab){
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$indiceArr = array ();
		foreach($indices as $myIndex) {
			$indiceArr[$myIndex->index] = $myIndex->value;
		}
		$primaryArr = array ();
		foreach($primary as $myPrimary) {
			$primaryArr[$myPrimary->index] = $myPrimary->value;
		}

		$db_dept = getDbObject($department);
		$query = "select departmentid from departments where real_name='".$cabinet."'";
		$res = $db_dept->queryAll($query);
		$cabinetID=$res[0]['departmentid'];
		$db_dept->disconnect ();
		//get $cabinetID
		$xmlStr = '<barcode>';
		$xmlStr.= '<department value="'.$department.'" />';
		$xmlStr.= '<cabinetID value="'.$cabinetID.'" />';
		$xmlStr.= '<cabinet value="'.$cabinet.'" />';
		$xmlStr.= '<terms>';
		foreach ($primaryArr as $key=>$value) {
			$xmlStr.= '<term index="'.$key.'" value="'.$value.'" />';
		}
		$xmlStr.= '</terms>';
		$xmlStr.= '<deletebc value="1" />';
		$xmlStr.= '<scanformat value="stif" />';
		$xmlStr.= '<sendimage value="'.$tab.'" />';
		$xmlStr.= '<user value="'.$userName.'" />';
		$xmlStr.= '<compress value="0" />';
		$xmlStr.= '<searchtype value="searchcreate" />';
		$xmlStr.= '<getTabsBC value="1" />';
		$xmlStr.= '</barcode>';	
		$xmlRet=getBarcodeInfo( $userName, $xmlStr );
		$domDoc = new DOMDocument();
		$domDoc->loadXML($xmlRet);
		$docIDs=$domDoc->getElementsByTagName('docID');
		$xmldocID = $docIDs->item(0); 
		$docID=$xmldocID->getAttribute('value');
		$tabIDs = $domDoc->getElementsByTagName('tab');

	    $barcode= '';
			
		foreach( $tabIDs as $tabID ) 
		{ 
			if ($tabID->getAttribute('value')==$tab){
				$barcode.=$tabID->getAttribute('barcode');
			} else if ($tabID->getAttribute('value')=='Main'){
				$barcode1=$tabID->getAttribute('barcode');
			} else {
				$query="delete from barcode_reconciliation where id=".$tabID->getAttribute('barcode');
				$res = $db->queryAll( $query );
			}
		}
		if ($barcode=='') $barcode=$barcode1;
		
		// update the folder
		$db_dept = getDbObject ($department);
		$ret = updateCabinetFolder($db_dept, $cabinetID, $docID, $indiceArr, $userName);
		$db_dept->disconnect ();

		//<barcodeInfo><department value="client_files8"/><cabinetID value="361"/><docID value="51"/><barcode value="1073830"/><barcodeStr value=""/></barcodeInfo>		
		if ($barcode=='') return new SOAP_Fault ('Not a valid tab');
		return $barcode;
	}

	function GetBarcodeData($passKey,$department,$cabinet,$indices,$primary,$tab){
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		$indiceArr = array ();
		foreach($indices as $myIndex) {
			$indiceArr[$myIndex->index] = $myIndex->value;
		}
		$primaryArr = array ();
		foreach($primary as $myPrimary) {
			$primaryArr[$myPrimary->index] = $myPrimary->value;
		}

		$db_dept = getDbObject($department);
		$query = "select departmentid from departments where real_name='".$cabinet."'";
		$res = $db_dept->queryAll($query);
		$cabinetID=$res[0]['departmentid'];
		$db_dept->disconnect ();
		//get $cabinetID
		$xmlStr = '<barcode>';
		$xmlStr.= '<department value="'.$department.'" />';
		$xmlStr.= '<cabinetID value="'.$cabinetID.'" />';
		$xmlStr.= '<cabinet value="'.$cabinet.'" />';
		$xmlStr.= '<terms>';
		foreach ($primaryArr as $key=>$value) {
			$xmlStr.= '<term index="'.$key.'" value="'.$value.'" />';
		}
		$xmlStr.= '</terms>';
		$xmlStr.= '<deletebc value="1" />';
		$xmlStr.= '<scanformat value="stif" />';
		$xmlStr.= '<sendimage value="'.$tab.'" />';
		$xmlStr.= '<user value="'.$userName.'" />';
		$xmlStr.= '<compress value="0" />';
		$xmlStr.= '<searchtype value="searchcreate" />';
		$xmlStr.= '<getTabsBC value="1" />';
		$xmlStr.= '</barcode>';	
		$xmlRet=getBarcodeInfo( $userName, $xmlStr );
		$domDoc = new DOMDocument();
		$domDoc->loadXML($xmlRet);
		$docIDs=$domDoc->getElementsByTagName('docID');
		$xmldocID = $docIDs->item(0); 
		$docID=$xmldocID->getAttribute('value');
		$tabIDs = $domDoc->getElementsByTagName('tab');

		$barcode= '';
		
		foreach( $tabIDs as $tabID ) 
		{ 
		
			if ($tabID->getAttribute('value')==$tab){
				$barcode=$tabID->getAttribute('barcode');
				$barcodeStr=$tabID->getAttribute('barcodeStr');
			} else if ($tabID->getAttribute('value')=='main'){
				$barcodeMain=$tabID->getAttribute('barcode');
				$barcodeStrMain=$tabID->getAttribute('barcodeStr');
			} else {
				$query="delete from barcode_reconciliation where id=".$tabID->getAttribute('barcode');
				$res = $db->queryAll( $query );
			}
		}
		if ($barcode=='' || $tab=="Main") {
			$barcode=$barcodeMain;
			$barcodeStr=$barcodeStrMain;
		}
		// update the folder
		$db_dept = getDbObject ($department);
		$ret = updateCabinetFolder($db_dept, $cabinetID, $docID, $indiceArr, $userName);
		$db_dept->disconnect ();
		
	//<barcodeInfo><department value="client_files8"/><cabinetID value="361"/><docID value="51"/><barcode value="1073830"/><barcodeStr value=""/></barcodeInfo>		
		if ($barcode=='') return new SOAP_Fault ('Not a valid tab'.$tab." barcodeMain:".$barcodeMain);
		$retVal=array($docID,$barcode,$barcodeStr);
		return $retVal;
	}
	
	function UploadFileFromBW($passKey,$department,$cabinet,$docID,$subfolderID,$filename,$encodedFile){
		list ($retVal, $userName) = checkKey ($passKey);
		if (!$retVal) {
			return new SOAP_Fault ('Login Permission Denied');
		}
		//get tabs and put here
		$BWTabs=array();
		$BWTabs[18]="";
		$subfolder=$BWTabs[$subfolderID];	
		$db = getDbObject($department);
		//get $cabinetID from cabinet
		$cabInfo = getTableInfo($db, 'departments', array(), array('real_name' => $cabinet));
		$row = $cabInfo->fetchRow();
		$cabinetID = $row['departmentid'];
		if ($subfolder=="") {
		$query="select * from ".$cabinet."_files where doc_id=".$docID." and subfolder is null order by id DESC";
		} else {
		$query="select * from ".$cabinet."_files where doc_id=".$docID." and subfolder='".$subfolder."' order by id DESC";
		}
		if ($res=$db->queryAll( $query )) {
			$tabID=$res[0]['id'];
			if ($res[0]['filename']=='') {
				//upload file
				$fileID = uploadFileToFolder($userName, $department, $cabinetID, $docID, $tabID, basename($filename), base64_decode($encodedFile), $this->db_doc, $db);
			} else {
				$encFileData = '';
				$retVal = checkOutFile ($userName, $department, $cabinetID, $tabID, $encFileData);
				$retVal2 = checkInFile ($userName, $department, $cabinetID, $tabID, basename($filename), base64_decode($encodedFile), $this->db_doc);
			}
			$db->disconnect ();
			$retval='Success';
			return $retval;
		} else {
			$db->disconnect ();
			$retval='DocID or sub-type not recognized';
			return $retval;
		}
		
	}	
	
}

class getTVInfo {
	var $docID;
	var $encFileData;
    function getTVInfo($docID = NULL, $barcodeStr = NULL) {
        $this->docID = $docID;
        $this->barcodeStr = $barcodeStr;
    }
}
?>
