<?php
include_once '../departments/depfuncs.php';
include_once '../lib/utility.php';
include_once '../lib/settings.php';
include_once '../settings/settings.php';

function createDepartment($quota,$licenses = 1, $db_doc) {
	global $DEFS,$db_username,$db_password;
	$depName = getRealDepartment($db_doc);	
	$adjustedQuota = $quota * 1024 * 1024;
	createNewDepartment($db_doc,$depName,$depName,false,$adjustedQuota);

	$uArr = array('quota_used' => 0);
	$wArr = array('real_department' => $depName);
	updateTableInfo($db_doc,'licenses',$uArr,$wArr);

	$whereArr = array('department' => $depName, 'real_name' => 'versioning');
	updateTableInfo($db_doc,'modules',array('enabled'=>1),$whereArr);
	$sett = new GblStt($depName, $db_doc);
 	$cmd = $DEFS['PHP_EXE'] . " -q " .
 		escapeshellarg($DEFS['DOC_DIR']."/departments/samba.php") . ' ' .
 		escapeshellarg($depName);
	$sett->set('docDaemon_execute',$cmd);

	return $depName;
}

function assignDepartmentAdminAccess($username,$department, $db_doc) {
	$wArr = array('username' => $username);
	$db_list_id = getTableInfo($db_doc,'users',array('db_list_id'),$wArr,'queryOne');

	$insertArr = array(	'list_id'	=> (int)$db_list_id,
						'db_name'	=> $department,
						'priv'		=> 'D' ); 
	$res = $db_doc->extended->autoExecute('db_list',$insertArr);
	dbErr($res);
}

function assignDepartmentAccess($username,$password,$department,$expire_time, $db_doc) {
	$DO_user = DataObject::factory('users', $db_doc);
	$DO_user->username = $username;
	$DO_user->password = $password;
	$DO_user->regdate = date('Y-m-d G:i:s');
	$DO_user->last_login = 'Never';
	$DO_user->guest = 1;
	$DO_user->exp_time = date('Y-m-d G:i:s',mktime(date('G') + $expire_time,date('i'),date('s'),date('m'),date('d'),date('Y')));
	$DO_user->insertUser($department);
}

function createNewCabinet($db_dep,$newCab,$indiceName,$cabID) {
	$insertArr = array(	'real_name'			=> $newCab,
						'DepartmentName'	=> $newCab	);
	$res = $db_dep->extended->autoExecute('departments',$insertArr);
	dbErr($res);
    createCabinet($db_dep,$newCab,$indiceName);
    createCabinet_files($db_dep,$newCab,$cabID);
    createCabinet_Index_Files($db_dep,$newCab);
}

function createNewFolder($db_dep,$cabinet,$indiceArr) {
	$res = $db_dep->extended->autoExecute($cabinet,$indiceArr);
	dbErr($res);
}

function insertFile($db_dep,$cabinet) {
	$insertArr = array(	"filename"			=> "get_my_papers_instructions.doc",
						"parent_filename"	=> "get_my_papers_instructions.doc",
						"doc_id" 			=> 1,
						"ordering"			=> 1,
						"date_created"		=> date("Y-m-d G:i:s"),
						"who_indexed"		=> "admin" );
	$res = $db_dep->extended->autoExecute($cabinet."_files",$insertArr);
	dbErr($res);
	
}

function assignAdminCabinetAccess($db_dep,$username) {
	$uArr = array('access'	=> base64_encode( serialize(
								array('Documents' => 'rw',
									  'Instructions'=> 'rw'))));
	$wArr = array(	'username'	=> $username);
	updateTableInfo($db_dep,'access',$uArr,$wArr);
}

function assignCabinetAccess($db_dep,$username) {
	$insertArr = array(	'username'	=> $username,
						'access'	=> base64_encode( serialize(
								array('Documents' => 'rw',
									  'Instructions'=> 'rw'))));
	$res = $db_dep->extended->autoExecute('access',$insertArr);
	dbErr($res);
}
//NOT YET
function updateDepartmentInfo($department,$db_doc,$quota='') {
	if($quota) {
		$adjustedQuota = $quota * 1024 * 1024;
		updateTableInfo($db_doc,'licenses',array('quota_allowed'=>(int)$adjustedQuota),array('real_department'=>$department));
	}
}

function updateUserInfo($username,$expire_time='',$password='') {
	$setArr = array();
	if($expire_time) {
		$expire = date('Y:m:d G:i:s', mktime(date('G') + $expire_time, 
				date('i'), date('s'), date('m'), date('d'), date('Y')));
		$setArr['exp_time'] = $expire;
	}

	if($password) {
		$setArr['password'] = $password;
	}

	if(sizeof($setArr) > 0 ) {
		updateTableInfo($db_doc,'users',$setArr,array('username'=>$username));
	}
}

function updateAccessInfo($db_dep,$username,$permissions) {
	$access = base64_encode(serialize($permissions));
	updateTableInfo($db_dep,'users',array('access'=>$access),array('username'=>$username));
}

function createDepartmentTemplate($username,$department,$db_doc, $db_dept) {
	setHotLinks($username,$department);
	setFolderSettings($username,$department,$db_doc, $db_dept);
}

function setHotlinks($username,$department) {
	$settings = new Usrsettings($username,$department);	
	$enabled = array('search');
	$settings->set('enabledLinks',implode('!_DELIMITER_!',$enabled));
	$disabled = array('todo','inbox','settings');
	$settings->set('disabledLinks',implode('!_DELIMITER_!',$disabled));
}

function setFolderSettings($username,$department,$db_doc, $db_dept) {
	$settingsList = new settingsList($db_doc, $department, $db_dept, 'user', $username);
	$settingsList->markEnabled('Documents', 'editFolder');
	$settingsList->markEnabled(0, 'deleteFiles');
	$settingsList->markEnabled(0, 'moveFiles');
	$settingsList->markEnabled(0, 'uploadFiles');
	$settingsList->markEnabled(0, 'advSearchNotes');
	$settingsList->markEnabled(0, 'advSearchDateCreated');
	$settingsList->markEnabled(0, 'advSearchFilename');
	$settingsList->markEnabled(0, 'deleteButtonString');
	$settingsList->markEnabled(0, 'uploadButtonString');

	$settingsList->markDisabled('Instructions', 'deleteFiles');
	$settingsList->markDisabled('Instructions', 'uploadFiles');
	$settingsList->markDisabled('Instructions', 'moveFiles');
	$settingsList->markDisabled(0, 'addEditTabs');
	$settingsList->markDisabled(0, 'changeThumbnailView');
	$settingsList->markDisabled(0, 'getAsPDF');
	$settingsList->markDisabled(0, 'getAsZip');
	$settingsList->markDisabled(0, 'redactFiles');
	$settingsList->markDisabled(0, 'viewNonRedact');
	$settingsList->markDisabled(0, 'showBarcode');
	$settingsList->markDisabled(0, 'deleteFolders');
	$settingsList->markDisabled(0, 'versioning');

	$settingsList->markDisabled(0, 'advSearchContextSearch');
	$settingsList->markDisabled(0, 'advSearchWhoIndexed');
	$settingsList->markDisabled(0, 'advSearchSubfolder');
	$settingsList->commitChanges();

	$gblStt = new GblStt($department, $db_doc);
	$gblStt->set('redirectLogin','http://papers.getmypapers.com');

	$settings = new Usrsettings($username,$department);	
	$settings->set('csvRestrict','on');
	$settings->set('isoRestrict','on');
	$settings->set('bookmarkRestrict','on');
	$settings->set('order','0');
	$settings->set('deleteRecyclebin','0');
	$settings->set('displayQuota','1');
	$settings->set('displayDepartmentName','0');
}
?>
