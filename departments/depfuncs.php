<?php
require_once '../lib/fileFuncs.php';
require_once '../lib/settings.php';
require_once '../lib/settingsList.inc.php';
require_once '../DataObjects/DataObject.inc.php';
/**
  This function will return all the databases that exist on the system
 */
function getDatabases( $db_object ) {
  $depInfo = getLicensesInfo( $db_object );
	while( $results = $depInfo->fetchRow() ) {
		$depList[] = $results['real_department'];
  }
return( $depList );
}
/**
  This function will return all the usernames on the system 
 */
function getUsernames( $db_object ) {
	$DO_users = DataObject::factory('users', $db_object);
	$DO_users->orderBy('username', 'ASC');
	$DO_users->find();
	$names = array ();
	while( $DO_users->fetch() ) {
		if($DO_users->username != "admin") {
			$names[] = $DO_users->username;
		}
	}
return( $names );
}
/**
  This function will return the rights of each database for each
  individual user 
 */
function getUserDepartmentInfo( $db_object, $depList ) {
	$DO_users = DataObject::factory('users', $db_object);
	$DO_users->orderBy('username', 'ASC');
	$DO_users->find();

	while($DO_users->fetch()) {
		$uName = $DO_users->username;
		//determines the rights of each user and databases
		for($j=0;$j<sizeof($depList);$j++) {
			$tmp = strtolower($depList[$j]);
			if(in_array($tmp,array_keys($DO_users->departments))) {
			    $names[$tmp][$uName] = "yes";
			} else {
				$names[$tmp][$uName] = "no";
			}
		}
  }
return( $names );
}
/**
  This function will return the default department for the user 
 */
function getDefaultDB( $db_object, $uname ) {
	$DO_user = DataObject::factory('users', $db_object);
	$DO_user->get('username', $uname);
	return $DO_user->defaultDept;
}
/**
  This function will return the real department name for the new department added
*/
function getRealDepartment( $db_object ) {
	$depList = getTableInfo($db_object,'licenses',array('real_department'),array(),'queryCol');
	usort($depList,"strnatcasecmp");

	$dep = $depList[count($depList)-1];
	$num = (int)str_replace("client_files","",$dep);
	$newDepName = "client_files".($num+1);
	return $newDepName;
}

function addDirectories( $real_dep) {
	global $DEFS;
	if(!file_exists("{$DEFS['DATA_DIR']}/$real_dep")) {
		if(!@mkdir("{$DEFS['DATA_DIR']}/$real_dep")) return false;
		if(!@mkdir("{$DEFS['DATA_DIR']}/$real_dep/indexing")) return false;
		if(!@mkdir("{$DEFS['DATA_DIR']}/$real_dep/inbox")) return false;
		if(!@mkdir("{$DEFS['DATA_DIR']}/$real_dep/personalInbox")) return false;
		if(!@mkdir("{$DEFS['DATA_DIR']}/$real_dep/personalInbox/admin")) return false;
		if(!@mkdir("{$DEFS['DATA_DIR']}/$real_dep/thumbs")) return false;
		if(!@mkdir("{$DEFS['DATA_DIR']}/$real_dep/stamps")) return false;
		copyStamps("{$DEFS['DATA_DIR']}/$real_dep/stamps", $DEFS);	
		allowWebWrite ($DEFS['DATA_DIR'].'/'.$real_dep, $DEFS);
		return true;
	} else {
		return false;
	}
}

function copyStamps($path, $DEFS) {
	$stampDir = $DEFS['DOC_DIR'].'/stamps';
	$dh = opendir($stampDir);
	while( false !== ($file = readdir($dh)) ) {
		if($file != "." AND $file != "..") {
			copy($stampDir."/$file", $path."/$file");
		}
	}
}

function createNewDepartment($db_doc, $real_dep, $newDep, $dbConn = false, $quota='') {
	if(!$quota) {
		$totalQuotaUsed = getTableInfo($db_doc,'licenses',array('SUM(quota_allowed)'),array(),'queryOne');
		if($totalQuotaUsed) {
			$total = $totalQuotaUsed;
		} else {
			$total = 0;
		}
		$quota_allowed = getTableInfo($db_doc,'quota',array('size_used'),array(),'queryOne');
		$quota = $quota_allowed - $total;
	} 
	if (addDirectories($real_dep)) {
		$newDep = str_replace('_', ' ', $newDep);
		$max = getTableInfo($db_doc, 'licenses', array('min(max)'), array(), 'queryOne');
		if($max == "" or $max == NULL) {
			$max = -1;
		}
		//Add department to licenses table
		$insertArr = array(	"real_department"	=> $real_dep,
					"arb_department"	=> $newDep,
					"max"				=> $max,
					"quota_allowed"		=> (double)$quota,
					"quota_used"		=> 40000 );
		$res = $db_doc->extended->autoExecute('licenses',$insertArr);
		dbErr($res);
		insertDefaultDeptModules($db_doc, $real_dep);
		enableModules($db_doc, $real_dep);
		insertDefaultDeptSettings($db_doc, $real_dep);
		createDB(getDatabase($real_dep), $dbConn);
		$db_search = getDbObject($real_dep."_search");
		$query="CREATE TABLE LastModifiedKey(TableName varchar(max) NULL,	LastUpdated varchar(50) NULL,	UpdateDate datetime NULL)";
		$res = $db_search->query($query);
		dbErr($res);
		$query="CREATE TABLE search(	fsearch_id int IDENTITY(1,1) NOT NULL,	type varchar(50) NULL,	cabinet_id int NULL,	doc_id int NULL,	document_id int NULL,	file_id int NULL,	lastupdated datetime NULL,	cabinet varchar(128) NULL, info varchar(50) NULL,	fulltext_content varchar(MAX) NULL,	timestamp timestamp NULL, CONSTRAINT PK_search PRIMARY KEY CLUSTERED (	fsearch_id ASC)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]";
		$res = $db_search->query($query);
		dbErr($res);
		$query="CREATE FULLTEXT CATALOG searchFTC";
		$res = $db_search->query($query);
		dbErr($res);
		$query="CREATE FULLTEXT INDEX ON [dbo].[search] (fulltext_content) KEY INDEX PK_search ON searchFTC";
		$res = $db_search->query($query);
		dbErr($res);
		$db_dept = getDbObject($real_dep);
		$db_doc = getDbObject('docutron');
		createDepartmentTables($db_dept);
		addDefaultSettingsList($db_doc, $real_dep, $db_dept);
		$queryArr = array('username' => 'admin', 
				'access' => base64_encode (serialize(array())));
		$res = $db_dept->extended->autoExecute('access', $queryArr);
		dbErr($res);
		$db_dept->disconnect ();
		return true;
	} else {
		return false;
	}
}

function insertDefaultDeptSettings($db, $dbName) {
	$arr = array();
	$arr[] = array('k' => 'allowReassignTodo', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'allowSelfAudit', 'value' => '1', 'department' => $dbName);
	$arr[] = array('k' => 'backup_dir', 'value' => '/opt/docutron_backups', 'department' => $dbName);
	$arr[] = array('k' => 'bookmarkRestrict', 'value' => 'on', 'department' => $dbName);
	$arr[] = array('k' => 'compareCols', 'value' => '-1', 'department' => $dbName);
	$arr[] = array('k' => 'date_functions', 'value' => '1', 'department' => $dbName);
	$arr[] = array('k' => 'deletePublicInbox', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'deleteRecyclebin', 'value' => '1', 'department' => $dbName);
	$arr[] = array('k' => 'displayDepartmentName', 'value' => '1', 'department' => $dbName);
//	$arr[] = array('k' => 'displayDepartmentName', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'displayQuota', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'inboxAccess', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'inboxGroupAccess', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'inboxWorkflow', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'indexingWait', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'indexing_file_filter', 'value' => 'TIF,TIFF,PDF', 'department' => $dbName);
	$arr[] = array('k' => 'indexing_ordering', 'value' => '1', 'department' => $dbName);
	$arr[] = array('k' => 'isoRestrict', 'value' => 'on', 'department' => $dbName);
	$arr[] = array('k' => 'i18n', 'value' => 'english', 'department' => $dbName);
	$arr[] = array('k' => 'langlogin', 'value' => 'off', 'department' => $dbName);
	$arr[] = array('k' => 'last_backup', 'value' => '2003-September-04 11:44:56', 'department' => $dbName);
	$arr[] = array('k' => 'moveAllthumbs', 'value' => '1', 'department' => $dbName);
	$arr[] = array('k' => 'publishingExpire', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'publishingDefaultExp', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'salt', 'value' => 'willbemis', 'department' => $dbName);
	$arr[] = array('k' => 'scroll', 'value' => '1', 'department' => $dbName);
	$arr[] = array('k' => 'tab_ordering', 'value' => 'ASC', 'department' => $dbName);
	$arr[] = array('k' => 'versioningReportAccess', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'wfGroupAccess', 'value' => '0', 'department' => $dbName);
	$arr[] = array('k' => 'pcDocCart', 'value' => '1', 'department' => $dbName);
	foreach($arr as $setting) {
		$res = $db->extended->autoExecute('settings', $setting);
		dbErr($res);
	}

}

function &defaultDeptModules($dbName) {
	$arr = array();
	$arr[] = array('arb_name' => 'CD Backup', 'real_name' => 'CD_Backup', 'dir' => 'CDBackup', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Context Search', 'real_name' => 'context_search', 'dir' => 'context_search', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'OCR', 'real_name' => 'ocr', 'dir' => 'bots', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Version Control', 'real_name' => 'versioning', 'dir' => 'versioning', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Multiple Departments', 'real_name' => 'departments', 'dir' => 'departments', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Workflow', 'real_name' => 'workflow', 'dir' => 'workflow', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Redaction', 'real_name' => 'redaction', 'dir' => 'redaction', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'MAS500', 'real_name' => 'MAS500', 'dir' => 'MAS500', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Search Results ODBC', 'real_name' => 'searchResODBC', 'dir' => 'searchResODBC', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'outlook', 'real_name' => 'outlook', 'dir' => 'outlook', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Reports', 'real_name' => 'reports', 'dir' => 'reports', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Publishing', 'real_name' => 'publishing', 'dir' => 'publishing', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Centera', 'real_name' => 'centera', 'dir' => 'centera', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Administration', 'real_name' => 'administration', 'dir' => 'administration', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Treeno Express', 'real_name' => 'lite', 'dir' => 'lite', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Advanced Inbox Indexing', 'real_name' => 'advancedInbox', 'dir' => 'advancedInbox', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Global Search', 'real_name' => 'global_search', 'dir' => 'globalsearch', 'enabled' => 0, 'department' => $dbName);
	$arr[] = array('arb_name' => 'Demo', 'real_name' => 'demo', 'dir' => 'demo', 'enabled' => 0, 'department' => $dbName );
	$arr[] = array('arb_name' => 'Compliance', 'real_name' => 'compliance', 'dir' => 'compliance', 'enabled' => 1, 'department' => $dbName );
	$arr[] = array('arb_name' => 'Dynamic Workflow', 'real_name' => 'dwf', 'dir' => 'dwf', 'enabled' => 0, 'department' => $dbName );
	return $arr;
}

//Enables the same modules as client_files or the optional third parameter
//Does NOT create the modules if it does exist in client_filesN
function enableModules($db_object, $currentDep, $baseDep=NULL) {
	if( $baseDep == NULL ) {
		$client_files = getTableInfo($db_object, 'modules', array('department'), array(), 'queryOne', array('id' => 'ASC'), 1);
	} else {
		$client_files = $baseDep;
	}
	$modules = getTableInfo($db_object, 'modules', array('arb_name', 'enabled'), array('department' => $client_files), 'getAssoc');

	foreach( $modules AS $arb_name => $enabled ) {
		if ($arb_name!="dwf")
		{
			updateTableInfo($db_object, 'modules', array('enabled' => $enabled), array('arb_name' => $arb_name, 'department' => $currentDep));
		}
	}
	
}

function insertDefaultDeptModules($db, $dbName) {
	$arr =& defaultDeptModules($dbName);
	foreach($arr as $module) {
		$res = $db->extended->autoExecute('modules', $module);
		dbErr($res);
	}
}

function addDefaultSettingsList($db_doc, $real_dep, $db_dept) {
	$settingsList = new settingsList($db_doc, $real_dep, $db_dept);
	$settingsList->markEnabled(0, 'changeThumbnailView');
	$settingsList->markEnabled(0, 'saveFiles');
	$settingsList->markEnabled(0, 'addDocument');
	$settingsList->markEnabled(0, 'editDocument');
	$settingsList->markEnabled(0, 'showFilename');
	$settingsList->markEnabled(0, 'deleteFiles');
	$settingsList->markEnabled(0, 'getAsZip');
	$settingsList->markEnabled(0, 'moveFiles');
	$settingsList->markEnabled(0, 'uploadFiles');
	$settingsList->markEnabled(0, 'addEditTabs');
	$settingsList->markEnabled(0, 'editFolder');
	$settingsList->markEnabled(0, 'showBarcode');
	$settingsList->markEnabled(0, 'advSearchNotes');
	$settingsList->markEnabled(0, 'advSearchDateCreated');
	$settingsList->markEnabled(0, 'advSearchFilename');
	$settingsList->markEnabled(0, 'advSearchContextSearch');
	$settingsList->markEnabled(0, 'advSearchWhoIndexed');
	$settingsList->markEnabled(0, 'advSearchSubfolder');
	$settingsList->markEnabled(0, 'editFolder');
	$settingsList->markEnabled(0, 'wfIcons');
	$settingsList->markEnabled(0, 'viewMode');
	$settingsList->markEnabled(0, 'reorderFiles');
	$settingsList->markEnabled(0, 'documentView');

	$settingsList->markDisabled(0, 'getAsPDF');
	$settingsList->markDisabled(0, 'showDocumentCreation');
	$settingsList->markDisabled(0, 'editFilename');
	$settingsList->markDisabled(0, 'redactFiles');
	$settingsList->markDisabled(0, 'deleteFolders');
	$settingsList->markDisabled(0, 'viewNonRedact');
	$settingsList->markDisabled(0, 'versioning');
	$settingsList->markDisabled(0, 'showBarcode');
	$settingsList->markDisabled(0, 'deleteButtonString');
	$settingsList->markDisabled(0, 'uploadButtonString');
	$settingsList->markDisabled(0, 'modifyImage');
	$settingsList->markDisabled(0, 'globalEditFolder');
	$settingsList->markDisabled(0, 'publishFolder');
	$settingsList->markDisabled(0, 'publishDocument');
	$settingsList->markDisabled(0, 'sliderBar');
	$settingsList->markDisabled(0, 'prefixCheckOut');
	$settingsList->commitChanges();
}

function switchDepartments($newDep,&$user,$db_object, $isAjax = false) {
	$oldDep = getTableInfo ($db_object, 'user_polls',
		array('current_department'), array ('username' => $user->username),
		'queryOne');
	//changes department but doesn't change the default
	$user->audit('left department', 'user left department, went to ' . $newDep); 
	$updateArr = array('current_department'=>$newDep);
	$whereArr = array('username'=>$user->username);
	updateTableInfo($db_object,'user_polls',$updateArr,$whereArr);
	//switches departments inside the user object
	$user->fillUser (null, $newDep);
//	$user->setDBname( $newDep );
	//resets depadmin and admin for the user in the new department
//	$user->setDepInfo( $newDep );
//	$user->doc_id = 0;
//	$user->cab = '';
//	$user->restore = 0;
	//retrieves new access table for department selected
//	$user->setSecurity();
//	$user->setSettings();

	//finds the drive that belongs to this department
	$stt = new Gblstt( $newDep, $db_object );
	$drive = $stt->get( "drive" );
	$user->drive = $drive;

	//finds the language default for this department	
	$language = $stt->get( "i18n" );
	$user->language = $language;
	$user->audit('entered department', 'user entered department, came from ' .
		$oldDep); 
	setSessionUser($user);
	if ($isAjax) {
		$defaultPage = $stt->get ('defaultPage');
		if (!$defaultPage) {
			$defaultPage = 'cabinetInfo';
		}
		$defInfo = getDefaultPageInfo ();
		$url = $defInfo[$defaultPage]['url'];
		header ('Content-type: text/xml');
		echo '<root><default url="'.h($url).'" /></root>';
	}
}

?>
