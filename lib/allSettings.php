<?php
// $Id: allSettings.php 14948 2012-11-29 21:13:59Z rweeks $

function queryAllSettings($db_name) {
	$allSettings = array (
		'deleteFiles'				=> 'Delete Files',
		'deleteFolders'				=> 'Delete Folders',
		'deleteDocuments'			=> 'Delete Documents',
		'getAsZip'					=> 'Get as ZIP File',
		'moveFiles'					=> 'Move Files',
		//'addEditTabs'				=> 'Add/Edit Tabs',
		'editFolder'				=> 'Add/Edit Folders',
		'addDocument'				=> 'Add Documents',
		'editDocument'				=> 'Edit Documents',
		'editFilename'				=> 'Edit Filenames',
		'hideSearch'				=> 'Hide Search on Page Load',
	);
//		'documentView'				=> 'Document View',

	if(!check_enable('lite', $db_name)) {
//		$allSettings['advSearchSubfolder']     = 'Advanced Search Subfolder';
//		$allSettings['advSearchFilename']      = 'Advanced Search Filename';
//		$allSettings['advSearchContextSearch'] = 'Advanced Search Context Search';
//		$allSettings['advSearchDateCreated']   = 'Advanced Search Date Created';
//		$allSettings['advSearchWhoIndexed']    = 'Advanced Search Who Indexed';
//		$allSettings['advSearchNotes']         = 'Advanced Search Notes';
//		$allSettings['changeThumbnailView']    = 'Change Thumbnail View';
//		$allSettings['deleteButtonString']     = 'Display Delete String Next to Button';
//		$allSettings['uploadButtonString']     = 'Display Upload String Next to Button';
//		$allSettings['viewMode']               = 'Full Screen Mode';
//		$allSettings['globalEditFolder']       = 'Global Search and Replace';
//		$allSettings['modifyImage']            = 'Image Modification';
		$allSettings['publishFolder']          = 'Publish Folder';
		$allSettings['publishDocument']        = 'Publish Document';
		$allSettings['reorderFiles']           = 'Reorder Files';
		$allSettings['saveFiles']              = 'Save Files';
//		$allSettings['showFilename']           = 'Show Filenames';
//		$allSettings['sliderBar']              = 'Slider Bar';
		$allSettings['uploadFiles']            = 'Upload Files';
		$allSettings['prefixCheckOut']         = 'Versioning Prefix for Plug-in Integration';
		$allSettings['getAsPDF']			   = 'Get as PDF File';
//		$allSettings['showDocumentCreation']   = 'Show Document Creation Date';
	}
	
	if(check_enable('workflow', $db_name)) {
		$allSettings['wfIcons']	= 'Show Workflow Icons';
	}

	$allSettings['showBarcode']	= 'Show Barcode Link';
	
	if(check_enable('versioning', $db_name)) {
		$allSettings['versioning']	= 'Allow Versioning Access';
	}
	
	if(check_enable('redaction', $db_name)) {
		$allSettings['redactFiles']		= 'Redact Files';
		$allSettings['viewNonRedact']	= 'View Original of Redacted Files';
	}

	if(check_enable('eSign', $db_name)) {
		$allSettings['docuSign']	= 'Sign File';
	}
	$allSettings['allowFileView']			= 'View File View Icon';
	$allSettings['allowFileVirtualView']	= 'View File Virtual View Icon';
	return $allSettings;
}

function getDefaultPageInfo () {
	return array (
		'cabinetInfo'	=> array (
				'dispStr'	=> 'Cabinet Information',
				'url'		=> '../modules/fileInfo.php' 
		),
		'licenseInfo'	=> array (
				'dispStr'	=> 'License Information',
				'url'		=> '../modules/userInfo.php' 
		),
		'printUserBarcodes'	=> array (
				'dispStr'	=> 'Print User Barcodes',
				'url'		=> '../secure/printUserBarcodes.php' 
		),
		'unRecBarcodes'		=> array (
				'dispStr'	=> 'Unprocessed Barcodes',
				'url'		=> '../barcode/getBarcodeReconciliation.php'
		),
	);
}

?>
