<?php
require_once '../db/db_common.php';
require_once '../departments/depfuncs.php';
require_once '../DataObjects/DataObject.inc.php';
require_once '../lib/tables.php';
include_once '../lib/settings.php';

function installDB($initialize = true, $DEFS, $doOtherInstall = true) {
	if($doOtherInstall) {
		echo "\nInstalling/Upgrading PEAR::MDB2...\n";
		shell_exec($DEFS['PEAR_EXE'] . ' install -sa MDB2');
		if (getDbType () == 'pgsql') {
			if (!extension_loaded ('pgsql')) {
				die ("Please install php-pgsql RPM\n");
			}
			echo "loading pgsql\n";
			shell_exec($DEFS['PEAR_EXE'] . ' install -sa MDB2_Driver_pgsql');
		} elseif (getDbType () == 'mysqli') {
			if (!extension_loaded ('mysqli')) {
				die ("Please install php-mysqli RPM\n");
			}
			shell_exec($DEFS['PEAR_EXE'] . ' install -sa MDB2_Driver_mysqli');
		} elseif (getDbType () == 'mssql') {
			if (!extension_loaded ('mssql')) {
				die ("Please install php-mssql RPM, or compile the extension\n");
			}
			echo "Remember to add the new_link hack into the MDB2 MSSQL driver (mssql.php, line 330):\n";
			echo 'if (!empty($this->dsn[\'new_link\']) && ($this->dsn[\'new_link\'] == \'true\' || $this->dsn[\'new_link\'] === true))\n{\n$params[] = true;\n}' . "\n";
			shell_exec($DEFS['PEAR_EXE'] . ' install -sa MDB2_Driver_mssql');
		}
		echo "\nInstalling/Upgrading PEAR::XML_Util...\n";
		shell_exec($DEFS['PEAR_EXE'] . ' install -sa XML_Util');
		echo "\nInstalling/Upgrading PEAR::Image_Graph...\n";
		shell_exec($DEFS['PEAR_EXE'] . ' config-set preferred_state alpha');
		shell_exec($DEFS['PEAR_EXE'] . ' install -sa Image_Graph');
		shell_exec($DEFS['PEAR_EXE'] . ' config-set preferred_state stable');
		shell_exec($DEFS['PEAR_EXE'] . ' install -sa HTTP_Client');
	}

	include '../db/db_engine.php';
	if ($initialize) {
		initializeDB($DEFS);
	}
	$allDBs = getListOfDatabases();
	if(!in_array(getDatabase('docutron'), $allDBs)) {
		echo "Adding docutron database.\n";
		createDB(getDatabase('docutron'));
		$db_doc = getDbObject('docutron');
		createDocutronTables($db_doc);
		importLanguage($db_doc);
		loadHelp($db_doc);
		$DO_user = DataObject::factory('users', $db_doc);
		$DO_user->username = 'admin';
		$DO_user->password = '21232f297a57a5a743894a0e4a801fc3';
		$DO_user->insertUser('client_files', 'D');
 		if (substr (PHP_OS, 0, 3) == 'WIN') {
 			$mapped_to = 'c:';
 		} else {
 			$output = shell_exec('mount');
 			$str = explode("\n",trim($output));
			$drive = '';
 			foreach($str as $strPart) {
 				list($location, , $directory) = explode(' ', $strPart);
 				if(substr($location, '/dev/') !== false and $directory == '/var/www') {
 					$mapped_to = $location;
 					break;
 				} elseif(substr($location, '/dev/') !== false and $directory == '/var'){
 					$drive = $directory;
 					$mapped_to = $location;
 				} elseif(substr($location, '/dev/') !== false and $directory == '/' and $drive != '/var') {
 					$drive = $directory;
 					$mapped_to = $location;
 				}
  			}
  		}
		global $DEFS;
		$space = disk_free_space($DEFS['DATA_DIR']);
		$space = round(($space *.95), -4);
		$queryArr = array (
			'drive'		=> $mapped_to,
			'max_size'	=> (double)$space,
			'size_used'	=> (double)$space,
		);
		$res = $db_doc->extended->autoExecute('quota', $queryArr);
		dbErr($res);

		$queryArr = array( 'max_licenses' => (int) 4);
		$res = $db_doc->extended->autoExecute('global_licenses', $queryArr);
		dbErr($res);
	} else {
		echo "\nWarning: docutron database already exists.\n";
		$db_doc = getDbObject('docutron');
	}
	if(!in_array(getDatabase('client_files'), $allDBs)) {
		echo "\nAdding client_files database.\n";
		createNewDepartment($db_doc, 'client_files', 'Main Department');

	} else {
		echo "\nWarning: client_files database already exists.\n";
	}
	$db_doc->disconnect();

	echo "\nFinished installing Databases\n\n";
}

function initializeDB($DEFS) {
	if(getdbType() == 'mysql' or getdbType() == 'mysqli') {
		include '../db/db_engine.php';
		shell_exec('mysql_install_db');
		#shell_exec('/etc/init.d/mysqld restart');
		echo shell_exec($DEFS['MYSQLADMIN_EXE'] .
			" -u $db_username password $db_password");
	}
}

function getListOfDatabases() {
	$allDBs = array ();
	if(in_array(getdbType(), array('mysql', 'mysqli', 'pgsql'))) {
		$db = connectToDefaultDB();
		$allDBs = $db->manager->listDatabases();
		dbErr($allDBs);
		$db->disconnect();
	} elseif(getdbType() == 'db2') {
		$outputArr = explode("\n", shell_exec("db2 LIST DATABASE DIRECTORY"));
		foreach($outputArr as $myLine) {
			if(strpos($myLine, 'Database alias') !== false) {
				$arr = explode('=', $myLine);
				$allDBs[] = strtolower(trim($arr[1]));
			}
		}
	} elseif(getdbType() == 'mssql') {
		$db = connectToDefaultDB();
		dbErr($db);
		$res = $db->queryAll ('EXEC sp_databases');
		foreach ($res as $myRes) {
			$allDBs[] = $myRes[0];
		}
		dbErr($res);
		$db->disconnect ();
	}
	return $allDBs;
}


function importLanguage($db) {
	$langFile = 'language.xml';
	$allQueries = array ();
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_file($langFile);
		$transArr = $xmlDoc->get_elements_by_tagname('trans');
		foreach ($transArr as $myTrans) {
			$queryArr = array ();
			$tmp = $myTrans->get_elements_by_tagname('k');
			$queryArr['k'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('english');
			$queryArr['english'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('spanish');
			$queryArr['spanish'] = $tmp[0]->get_content();
			$allQueries[] = $queryArr;
		}
	} else {
		$xml = simplexml_load_file ($langFile);
		foreach ($xml->trans as $myTrans) {
			$allQueries[] = array (
					'k'			=> $myTrans->k[0],
					'english'	=> $myTrans->english[0],
					'spanish'	=> $myTrans->spanish[0]
					);
		}
	}
		
	foreach($allQueries as $queryArr) {
		$res = $db->extended->autoExecute('language', $queryArr);
		dbErr($res);
	}
}

function loadHelp($db_doc) {
	$helpFile = 'help.xml';
	$allQueries = array ();
	if (substr (PHP_VERSION, 0, 1) == '4') {
		$xmlDoc = domxml_open_file($helpFile);
		$transArr = $xmlDoc->get_elements_by_tagname('info');
	    foreach($transArr as $myTrans) {
			$queryArr = array ();
			$tmp = $myTrans->get_elements_by_tagname('k');
			$queryArr['k'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('language');
			$queryArr['language'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('section');
			$queryArr['section'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('title');
			$queryArr['title'] = $tmp[0]->get_content();
			$tmp = $myTrans->get_elements_by_tagname('description');
			$queryArr['description'] = $tmp[0]->get_content();
			$allQueries[] = $queryArr;
		}
	} else {
		$xml = simplexml_load_file ($helpFile);
		foreach ($xml->info as $myTrans) {
			$allQueries[] = array (
					'k'				=> (string) $myTrans->k[0],
					'language'		=> (string) $myTrans->language[0],
					'section'		=> (string) $myTrans->section[0],
					'title'			=> (string) $myTrans->title[0],
					'description'	=> (string) $myTrans->description[0]
					);
		}
	}
	foreach ($allQueries as $queryArr) {
		$res = $db_doc->extended->autoExecute ('help', $queryArr);
		dbErr ($res);
	}
}
?>
