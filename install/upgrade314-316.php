#!/usr/bin/php -q
<?php 
include_once '../lib/utility.php';
include_once '../lib/settingsList.inc.php';

$db_doc = getDbObject('docutron');
createGroupAccess($db_doc);
updateFilesTables($db_doc);
createFilesToRedact($db_doc);
createSettingsList($db_doc);
createSharedLicenses($db_doc);
upgradeSystemSettings($db_doc);
$db_doc->disconnect();

function createFilesToRedact($db_doc) {
	$query = "CREATE TABLE files_to_redact (" .
			"id INT(11) NOT NULL AUTO_INCREMENT, " .
			"department VARCHAR(100) NOT NULL DEFAULT '', " .
			"cabinet VARCHAR(100) NOT NULL DEFAULT '', " .
			"file_id INT(11) NOT NULL DEFAULT 0, " .
			"locked INT(1) NOT NULL DEFAULT 0, " .
			"PRIMARY KEY (id)) TYPE=MYISAM";
	$db_doc->query($query);
}

function updateFilesTables($db_doc) {
	$licenseInfo = getLicensesInfo($db_doc);
	while($row = $licenseInfo->fetchRow()) {
		$db_dept = getDbObject($row['real_department']);
		$cabinetInfo = getTableInfo($db_dept, 'departments');
		while($myCab = $cabinetInfo->fetchRow()) {
			$cabinet = $myCab['real_name'];
			$indiceNames = $db_dept->reverse->tableInfo($cabinet.'_files');
			if(!in_array('redaction', $indiceNames)) {
				$query = "ALTER TABLE ".$cabinet."_files ADD redaction VARCHAR(100) DEFAULT ''";
				$res = $db_dept->query($query);
				dbErr($res);
			}
			if(!in_array('redaction_id', $indiceNames)) {
				$query = "ALTER TABLE ".$cabinet."_files ADD redaction_id INT(11) DEFAULT '0'";
				$res = $db_dept->query($query);
				dbErr($res);
			}			
			//$indiceArr = getCabinetInfo($db_dept,$cabinet);
			//createCabinet($db_dept,$cabinet."__deleted",$indiceArr);
			//createCabinet_files($db_dept,$cabinet."__deleted");
			alterTable($db_dept, $cabinet.'_indexing_table', 'DROP COLUMN', 'user');
		}
		$query = "CREATE TABLE barcode_history (" .
			"id INT(11) NOT NULL AUTO_INCREMENT, " .
			"barcode_rec_id INT(11) NOT NULL DEFAULT 0, " .
			"barcode_info VARCHAR(255) NOT NULL DEFAULT '', ".
			"username VARCHAR(255) NOT NULL DEFAULT '', " .
			"cab VARCHAR(255) NOT NULL DEFAULT '', " .
			"barcode_field VARCHAR(255) NOT NULL DEFAULT '', ".
			"date_printed DATETIME DEFAULT NULL, " .
			"date_processed DATETIME DEFAULT NULL, " .
			"description VARCHAR(255) NOT NULL DEFAULT '', " .
			"PRIMARY KEY (id), KEY (username)) TYPE=MYISAM";
		$db_dept->query($query);

		$query = "ALTER TABLE odbc_mapping ADD odbc_trans_level INT NOT NULL DEFAULT 0";
		$res = $db_dept->query($query);
		dbErr($res);

		$db_dept->disconnect();
	}
	$query = "CREATE TABLE barcode_reconciliation (" .
		"id INT(11) NOT NULL AUTO_INCREMENT, " .
		"barcode_info VARCHAR(255) NOT NULL DEFAULT '', ".
		"username VARCHAR(255) NOT NULL DEFAULT '', " .
		"cab VARCHAR(255) NOT NULL DEFAULT '', " .
		"barcode_field VARCHAR(255) NOT NULL DEFAULT '', ".
		"date_printed DATETIME DEFAULT NULL, " .
		"department VARCHAR(255) NOT NULL DEFAULT '', " .
		"PRIMARY KEY (id), KEY (department), KEY (username)) TYPE=MYISAM";
	$db_doc->query($query);

	$query = "CREATE TABLE barcode_lookup (" .
		"id INT(11) NOT NULL, " .
		"department VARCHAR(255) NOT NULL DEFAULT '', " .
		"PRIMARY KEY (id)) TYPE=MYISAM";
	$db_doc->query($query);
}

function createGroupAccess($db_doc) {
	$licenseObject = getLicensesInfo( $db_doc );
	while( $row = $licenseObject->fetchRow() )
	{
		$client_files = $row['real_department'];
		$db_object = getDbObject( $client_files );
		$query = "RENAME TABLE group_access TO group_tab";
		$check = $db_object->query( $query );
		if( PEAR::isError( $check ) ) {
			echo "couldn't rename cabinet group access";
		} else {
			$query = "CREATE TABLE group_access (
				id int NOT NULL auto_increment,
				group_id varchar(100) NOT NULL default '',
				cabID int (11) NOT NULL default 0,
				access varchar(100) NOT NULL default 'none',
				PRIMARY KEY  (id)
				) TYPE=MyISAM COMMENT='For storing data for group access';";
			$db_object->query($query);

			$query = "ALTER TABLE groups DROP users, DROP department";
			$db_object->query($query);

			$query = "CREATE TABLE users_in_group (
				id int NOT NULL auto_increment,
				group_id varchar(100) NOT NULL default 0,
				uid int (11) NOT NULL default 0,
				PRIMARY KEY  (id)
				) TYPE=MyISAM COMMENT='For storing data for group list';";
			$db_object->query($query);
		}

		$query = "CREATE TABLE wf_triggers (
			id INT(11) NOT NULL auto_increment,
			wf_document_id INT(11) NOT NULL default 0,
			entry_date DATETIME default NULL,
			times_notified INT(11) NOT NULL default 0,
			notify_list_id INT(11) NOT NULL default 0,
			notify_group_id INT(11) NOT NULL default 0,
			take_action_number INT(11) NOT NULL default 0,
			action VARCHAR(255) NOT NULL default '',
			PRIMARY KEY (id)
			) TYPE=MyISAM COMMENT='For storing workflow trigger information';";
		$db_object->query($query);

		$query = "SELECT username,uid FROM access";
		$uidList = $db_object->extended->getAssoc($query); 
		$query = "SELECT id,users FROM groups";
		$list = $db_object->extended->getAssoc($query);
		foreach($list AS $id => $userArr) {
			$tmp = unserialize(base64_decode($userArr));
			foreach($tmp AS $username) {
				$insertArr[] = array(
								"group_id"	=> $id,
								"uid"		=> $uidList[$username]
									);
			}
		}
	
		foreach( $insertArr AS $groupInfo ) {
			$db_object->extended->autoExecute("users_in_group", $groupInfo);
		}
	}
}

function createSettingsList($db_doc) {
	$query = "CREATE TABLE `settings_list` ( " .
			"`id` int(11) NOT NULL auto_increment, " .
			"`list_id` int(11) NOT NULL default '0', " .
			"`cabinet` varchar(100) NOT NULL default '', " .
			"`setting` varchar(255) NOT NULL default '', " .
			"`enabled` int(1) default '0', " .
			"`department` varchar(100) NOT NULL default '', " .
			"PRIMARY KEY  (`id`), " .
			"KEY `list_id` (`list_id`))";
	$db_doc->query($query);
}

function createSharedLicenses($db_doc) {
	$query = "CREATE TABLE `global_licenses` ( " .
			"`id` int(11) NOT NULL auto_increment, " .
			"`max_licenses` int(11) NOT NULL default '0', " .
			"PRIMARY KEY (`id`))";
	$db_doc->query($query);
	
	$query = "ALTER TABLE user_polls ADD shared SMALLINT NOT NULL DEFAULT 0";
	$db_doc->query($query);
	$query = "ALTER TABLE user_polls ADD current_department VARCHAR(100) NOT NULL DEFAULT ''";
	$db_doc->query($query);		
}

function upgradeSystemSettings($db_doc) {
	$settingsGrid = array (
		'getAsPDF',
		'getAsZip',
		'deleteFiles',
		'moveFiles',
		'addEditTabs',
		'uploadFiles',
		'changeThumbnailView',
	);

	//Don't bother switching this to settings object.
	$userATIcons = getTableInfo($db_doc,'user_settings',array(),array('k'=>'userATIcons'));
	$deleteCabs = array ();
	while($row = $userATIcons->fetchRow()) {
		$db_dept = getDbObject ($row['department']);
		$settingsList = new settingsList($db_doc, $row['department'], $db_dept, 'user', $row['username']);
		$valArr = explode(',', $row['value']);
		for($i = 0; $i < count($settingsGrid); $i++) {
			if($valArr[$i] == 'show') {
				$settingsList->markEnabled(0, $settingsGrid[$i]);
			} elseif($valArr[$i] == 'noshow') {
				$settingsList->markDisabled(0, $settingsGrid[$i]);
			} else {
				$settingsList->markInherited(0, $settingsGrid[$i]);
			}
		}
		if($valArr[7] == 'show') {
			$deleteCabs[$row['username']] = $row['department'];
		}
		$settingsList->commitChanges();
		$whereArr = array(
			'username'=>$row['username'],
			'k'=>'userATIcons',
			'department'=>$row['department']
				 );
		deleteTableInfo($db_doc,'user_settings',$whereArr);
		$db_dept->disconnect ();
	}
	
	foreach($deleteCabs as $username => $db_name) {
		$settings = new Usrsettings($username, $db_name);
		$settings->set('deleteCabinets', 1);
	}
	
	//Don't bother switching this to settings object.
	$sysATIcons = getTableInfo($db_doc,'settings',array(),array('k'=>'sysATIcons'));
	$deleteCabs = array ();
	while($row = $sysATIcons->fetchRow()) {
		$db_dept = getDbObject ($row['department']);
		$adminSettingsList = new settingsList($db_doc, $row['department'], $db_dept, 'user', 'admin');
		$settingsList = new settingsList($db_doc, $row['department'], $db_dept);
		$valArr = explode(',', $row['value']);
		for($i = 0; $i < count($settingsGrid); $i++) {
			if($valArr[$i] == 'all') {
				$settingsList->markEnabled(0, $settingsGrid[$i]);
			} elseif($valArr[$i] == 'none') {
				$settingsList->markDisabled(0, $settingsGrid[$i]);
			} elseif($valArr[$i] == 'default') {
				$settingsList->markInherited(0, $settingsGrid[$i]);
			} else {
				$adminSettingsList->markEnabled(0, $settingsGrid[$i]);
			}
			if($valArr[7] == 'all') {
				$settings = new GblStt($row['department'], $db_doc);
				$settings->set('deleteCabinets', 1);
			} elseif($valArr[7] == 'admin') {
				$settings = new Usrsettings('admin', $row['department']);
				$settings->set('deleteCabinets', 1);
			}
		}
		$settingsList->commitChanges();
		$adminSettingsList->commitChanges();
		$whereArr = array(
			'k'=>'userATIcons',
			'department'=>$row['department']
				 );
		deleteTableInfo($db_doc,'settings',$whereArr);
		$db_dept->disconnect ();
	}
}
?>
