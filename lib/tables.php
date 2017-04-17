<?php
// $Id: tables.php 14847 2012-06-06 15:22:49Z rweeks $

if (file_exists('../lib/random.php')) {
	include_once '../lib/random.php';
} elseif (file_exists('lib/random.php')) {
	include_once 'lib/random.php';
} else {
	include_once '../../lib/random.php';
}

function &getDocutronTableDefs() {
	$indexArr = array();
	$queryArr = array();
	$allQueries = array();

	$queryArr['ocr_queue'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"location VARCHAR(255) NOT NULL DEFAULT ''",
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		"cabinet VARCHAR(255) NOT NULL DEFAULT ''",
		"file_id INT NOT NULL DEFAULT 0",
	);

	$queryArr['pub_wf_messages'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"wf_id INT NOT NULL DEFAULT 0",
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		"message ".TEXT16M." NULL"		
	);

	$queryArr['wf_filter_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT NOT NULL DEFAULT 0",
		"name VARCHAR(255) NOT NULL DEFAULT ''",
		"value VARCHAR(255) NOT NULL DEFAULT ''",
		'exact SMALLINT DEFAULT 0',
	);
	$indexArr[] = 'wflid_idx ON wf_filter_list(list_id)';

	$queryArr['publish_user'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"email VARCHAR(255) NOT NULL DEFAULT ''",
		"password VARCHAR(255) NOT NULL DEFAULT ''",
		'expiration '.DATETIME,
		'date_added '.DATETIME,
		"reset_password INT NOT NULL DEFAULT 0", 
		"upload INT NOT NULL DEFAULT 0",
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		"status VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'pub_email_idx ON publish_user(email)';

	$queryArr['publish_user_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"p_id INT NOT NULL DEFAULT 0",
		"ps_id INT NOT NULL DEFAULT 0"
	);
	$indexArr[] = 'pid_idx ON publish_user_list(p_id)';
	$indexArr[] = 'psid_idx ON publish_user_list(ps_id)';
	
	$queryArr['publish_search'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"name VARCHAR(255) NOT NULL DEFAULT ''",
		'date_added '.DATETIME,
		"expiration INT NOT NULL DEFAULT 0",
		"owner VARCHAR(255) NOT NULL DEFAULT ''",
		"enabled INT NOT NULL DEFAULT 0",
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		"ps_list_id INT NOT NULL DEFAULT 0",
		"pf_list_id INT NOT NULL DEFAULT 0",
	);

	$queryArr['publish_search_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"ps_list_id INT NOT NULL DEFAULT 0", 
		"type VARCHAR(255) NOT NULL DEFAULT ''",
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		"doc_id INT NOT NULL DEFAULT 0", 
		"file_id INT NOT NULL DEFAULT 0", 
		"field VARCHAR(255) NULL DEFAULT ''",
		"term VARCHAR(255) NULL DEFAULT ''",
		"wf_def_id INT NOT NULL DEFAULT 0",
	);
	$indexArr[] = 'pslid_idx ON publish_search_list(ps_list_id)';

	$queryArr['publish_filter'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"name VARCHAR(255) NOT NULL DEFAULT ''",
		"psf_list_id INT NOT NULL DEFAULT 0", 
	);
	$indexArr[] = 'psflid_idx ON publish_filter(psf_list_id)';

	$queryArr['publish_filter_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"pf_id INT NOT NULL DEFAULT 0", 
		"department VARCHAR(255) NOT NULL DEFAULT ''",
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		"document_name VARCHAR(255) NOT NULL DEFAULT ''",
		"filter VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'pflid_idx ON publish_filter_list(pf_id)';

	$queryArr['inbox_recyclebin'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		"folder VARCHAR(255) NOT NULL DEFAULT ''",
		"filename VARCHAR(255) NOT NULL DEFAULT ''",
		"path VARCHAR(255) NOT NULL DEFAULT ''",
		'date_deleted '.DATETIME,
		"type VARCHAR(255) NOT NULL DEFAULT ''",
		"department VARCHAR(255) NOT NULL DEFAULT ''",
	);
	
	$queryArr['help'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"k VARCHAR(255) NOT NULL DEFAULT ''",
		"language VARCHAR(255) NOT NULL DEFAULT ''",
		"section VARCHAR(255) NOT NULL DEFAULT ''",
		"title VARCHAR(255) NOT NULL DEFAULT ''",
		'description '.TEXT64K." NOT NULL",
	);
	
	$queryArr['global_licenses'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"max_licenses INT NOT NULL DEFAULT 0" 
	);
	
	$queryArr['barcode_reconciliation'] = array (
		'id '.AUTOINC2,
		'PRIMARY KEY (id)',
		"barcode_info VARCHAR(255) NOT NULL DEFAULT ''",
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		"barcode_field VARCHAR(255) NULL",
		'delete_barcode SMALLINT DEFAULT 1',
		"split_type VARCHAR(10) DEFAULT ''",
		'compress SMALLINT DEFAULT 1',
		'date_printed '.DATETIME,
		"department VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'br_username_idx ON barcode_reconciliation(username)';
	$indexArr[] = 'br_department_idx ON barcode_reconciliation(department)';

	$queryArr['barcode_lookup'] = array (
		'id INT NOT NULL',
		"department VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'bl_id_idx ON barcode_lookup(id)';
	
	$queryArr['files_to_redact'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"department VARCHAR(100) NOT NULL DEFAULT ''",
		"cabinet VARCHAR(100) NOT NULL DEFAULT ''",
		'file_id INT NOT NULL DEFAULT 0',
		'locked SMALLINT NOT NULL DEFAULT 0',
	);
	$queryArr['settings_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'list_id INT NOT NULL DEFAULT 0',
		"cabinet VARCHAR(100) NOT NULL DEFAULT ''",
		"setting VARCHAR(255) NOT NULL DEFAULT ''",
		'enabled SMALLINT DEFAULT 0',
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'sl_list_id_idx ON settings_list(list_id)';

	$queryArr['wf_todo'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"department VARCHAR(100) NOT NULL DEFAULT ''",
		"username VARCHAR(100) NOT NULL DEFAULT ''",
		'wf_def_id INT NOT NULL DEFAULT 0',
		'wf_document_id INT NOT NULL DEFAULT 0',
		'priority SMALLINT NULL DEFAULT 0',
		'notes '.TEXT16M . ' NULL',
		'date ' . DATETIME,
	);
	$queryArr['quota'] = array (
		"drive VARCHAR(100) NOT NULL DEFAULT ''",
		'PRIMARY KEY (drive)',
		'max_size BIGINT NOT NULL DEFAULT 0',
		'size_used BIGINT NOT NULL DEFAULT 0',
	);
	$queryArr['licenses'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"real_department VARCHAR(100) NOT NULL DEFAULT ''",
		"arb_department VARCHAR(100) NOT NULL DEFAULT ''",
		'dept_licenses INT NOT NULL DEFAULT 0',
		'max INT NOT NULL DEFAULT 0',
		'quota_allowed BIGINT NOT NULL DEFAULT 0',
		'quota_used BIGINT NOT NULL DEFAULT 0',
		'restricted_list INT NOT NULL DEFAULT 0'
	);
	$queryArr['user_polls'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"username VARCHAR(100) NOT NULL DEFAULT ''",
		'message '.TEXT64K." NULL",
		'ptime BIGINT NOT NULL DEFAULT 0',
		"department VARCHAR(100) NOT NULL DEFAULT ''",
		'strikes SMALLINT NOT NULL DEFAULT 0',
		'shared SMALLINT NOT NULL DEFAULT 0',
		"current_department VARCHAR(100) NOT NULL DEFAULT ''"
	);
	$queryArr['user_security'] = array (
		'uid '.AUTOINC,
		'PRIMARY KEY (uid)',
		"hash_code VARCHAR(128) NOT NULL DEFAULT ''",
		"username VARCHAR(80) NOT NULL DEFAULT ''",
		'status SMALLINT DEFAULT 0',
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$queryArr['modules'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"arb_name VARCHAR(100) NOT NULL DEFAULT ''",
		"real_name VARCHAR(100) NOT NULL DEFAULT ''",
		"dir VARCHAR(100) NOT NULL DEFAULT ''",
		'enabled SMALLINT NOT NULL DEFAULT 0',
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$queryArr['settings'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"k VARCHAR(255) NOT NULL DEFAULT ''",
		'value '.TEXT64K." NOT NULL",
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 's_k_idx ON settings(k)';

	$queryArr['user_settings'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"username VARCHAR(100) NOT NULL DEFAULT ''",
		"k VARCHAR(255) NOT NULL DEFAULT ''",
		'value '.TEXT64K." NOT NULL",
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'us_k_idx ON user_settings(k)';
	$queryArr['users'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"username VARCHAR(100) NOT NULL DEFAULT ''",
		"password VARCHAR(100) NOT NULL DEFAULT ''",
		"regdate VARCHAR(255) DEFAULT ''",
		"last_login VARCHAR(100) DEFAULT ''",
		'guest SMALLINT DEFAULT 0',
		'exp_time '.DATETIME,
		"email VARCHAR(255) DEFAULT ''",
		'db_list_id SMALLINT DEFAULT 0',
		'ldap_id SMALLINT DEFAULT 0',
		'ldap_user VARCHAR(100) NULL',
	);
	$queryArr['ldap'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'name VARCHAR(255) NOT NULL',
		'connect_string VARCHAR(255) NULL',
		'host VARCHAR(255) NOT NULL',
		'query_user VARCHAR(255) NULL',
		'query_password VARCHAR(255) NULL',
		'active_directory SMALLINT DEFAULT 0',
		'suffix VARCHAR(255) NULL',
		'department VARCHAR(255) NOT NULL',
	);
	$queryArr['db_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY(id)',
		'list_id SMALLINT NOT NULL',
		'db_name VARCHAR(100) NOT NULL',
		'priv CHAR(1) NOT NULL',
		'default_dept SMALLINT DEFAULT 0'		
	);
	$indexArr[] = 'dl_list_id_idx ON db_list(list_id)';
	$queryArr['language'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY(id)',
		"k VARCHAR (255) NOT NULL DEFAULT ''",
		"english VARCHAR (255) NOT NULL DEFAULT ''",
		"spanish VARCHAR (255) NOT NULL DEFAULT ''",
	);
	$queryArr['license_util'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY(id)',
		'num_used INT NOT NULL DEFAULT 0',
		'currtime INT NOT NULL DEFAULT 0',
		"department VARCHAR (100) NOT NULL DEFAULT ''",
	);
	$queryArr['monitor'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY(id)',
		"department VARCHAR (100) NOT NULL DEFAULT ''",
		"cabinet VARCHAR (100) NOT NULL DEFAULT ''",
		"path VARCHAR (255) NOT NULL DEFAULT ''",
		'file_size INT NOT NULL DEFAULT 0',
		'date_indexed INT NOT NULL DEFAULT 0',
	);
	$queryArr['odbc_connect'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY(id)',
		"connect_name VARCHAR (255) NOT NULL DEFAULT ''",
		"host VARCHAR (255) NOT NULL DEFAULT ''",
		"dsn VARCHAR (255) NOT NULL DEFAULT ''",
		"username VARCHAR (255) NOT NULL DEFAULT ''",
		"password VARCHAR (255) NOT NULL DEFAULT ''",
		"syntax VARCHAR (255) NOT NULL DEFAULT ''",
		"type VARCHAR (255) NOT NULL DEFAULT ''",
		"department VARCHAR (255) NOT NULL DEFAULT ''",
	);
	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(', ', $tableDef).')';
	}

	foreach($indexArr as $subQuery) {
		$allQueries[] = 'CREATE INDEX '.$subQuery;
	}
	
	return $allQueries;
}

function createDocutronTables($db) {
	$allQueries =& getDocutronTableDefs();
	foreach($allQueries as $query) {
		$res = $db->query($query);
		dbErr($res);
	}
	if (getDbType() == 'mysql' or getDbType() == 'mysqli') {
		$query = 'ALTER TABLE barcode_reconciliation AUTO_INCREMENT = 20000';
		$res = $db->query ($query);
		dbErr($res); 
	} elseif (getDbType() == 'pgsql') {
		$query = 'CREATE SEQUENCE bc_rec_seq INCREMENT BY 1 ' .
			'MINVALUE 1 NO MAXVALUE START WITH 20000 CACHE 1 ' .
			'NO CYCLE';
		$res = $db->query ($query);
		dbErr($res);
		$query = 'ALTER TABLE barcode_reconciliation ALTER COLUMN id ' .
			'SET DEFAULT NEXTVAL(\'bc_rec_seq\');';
		$res = $db->query ($query);
		dbErr($res);
	}
//	$res = $db->extended->autoExecute('barcode_reconciliation', array('id' => 20000));
//	dbErr($res);
}

function createDepartmentTables($db) {
	$allQueries =& getDepartmentTableDefs();
	foreach($allQueries as $query) {
		$res = $db->query($query);
		dbErr($res);
	}
}

function &getDepartmentTableDefs() {
	$allQueries = array ();
	$indexArr = array ();
	$queryArr = array ();
	
	$queryArr ['definition_types'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_type_id INT NOT NULL DEFAULT 0",
		"document_type_field VARCHAR(255) NOT NULL DEFAULT ''",
		"definition VARCHAR(255) NOT NULL DEFAULT ''"
	);
	$indexArr[] = 'def_docid_idx ON definition_types(document_type_id)';

	$queryArr['unreconciled_nfs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"nfs_id INT DEFAULT 0",
	);

	$queryArr['reconciled_nfs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"nfs_id INT DEFAULT 0",
	);

	$queryArr['nfs_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"date ".DATETIME,
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		"doc_id INT DEFAULT 0",
		"file_id INT DEFAULT 0",
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		"rec_date ".DATETIME,
	);

	$queryArr['document_settings'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT DEFAULT 0",
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		"k VARCHAR(255) NOT NULL DEFAULT ''",
		"value VARCHAR(255) NOT NULL DEFAULT ''"
	);

	$queryArr['document_settings_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT DEFAULT 0",
		"document_id INT DEFAULT 0",
	);
	$indexArr[] = 'dsl_list_id_idx ON document_settings_list(list_id)';

	$queryArr['inbox_delegation_history'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"delegate_id INT DEFAULT 0",
		"delegate_username VARCHAR(100) NOT NULL DEFAULT ''",
		"delegate_owner VARCHAR(100) NOT NULL DEFAULT ''",
		"folder VARCHAR(255) NULL",
		"filename VARCHAR(255) NOT NULL DEFAULT ''",
		'date_delegated '.DATETIME,
		'date_completed '.DATETIME,
		"status VARCHAR(100) NOT NULL DEFAULT ''",
		"comments VARCHAR(255) NULL",
		"action VARCHAR(255) NOT NULL DEFAULT ''"
	);
	$indexArr[] = 'inbdel_delegate_id_idx ON inbox_delegation_history(delegate_id)';
	$indexArr[] = 'inbdel_delegate_username ON inbox_delegation_history(delegate_username)';
	$indexArr[] = 'inbdel_delegate_owner ON inbox_delegation_history(delegate_owner)';

	$queryArr['inbox_delegation_file_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"list_id INT DEFAULT 0",
		"folder VARCHAR(255) NULL",
		"filename VARCHAR(255) NOT NULL DEFAULT ''"
	);
	$indexArr[] = 'inbdel_list_id_idx ON inbox_delegation_file_list(list_id)';
	$queryArr['inbox_delegation'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"delegate_username VARCHAR(100) NOT NULL DEFAULT ''",
		"delegate_owner VARCHAR(100) NOT NULL DEFAULT ''",
		"list_id INT DEFAULT 0",
		"STATUS vARCHAR(100) NOT NULL DEFAULT ''",
		"comments VARCHAR(255) NULL DEFAULT ''",
		'dtime '.DATETIME
	);

	$queryArr['document_type_defs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_type_name VARCHAR(255) NOT NULL DEFAULT ''",
		"document_table_name VARCHAR(255) NOT NULL DEFAULT ''",
		'enable INT default 0',
		'permissions_id INT default 0',
	);
	$indexArr[] = 'dtd_doc_tname_idx ON document_type_defs(document_table_name)';
	$indexArr[] = 'dtd_perm_id_idx ON document_type_defs(permissions_id)';

	$queryArr['document_field_defs_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_table_name VARCHAR(255) NOT NULL DEFAULT ''",
		"real_field_name VARCHAR(255) NOT NULL DEFAULT ''",
		"arb_field_name VARCHAR(255) NOT NULL DEFAULT ''",
		"ordering INT NOT NULL DEFAULT 0"
	);

	$queryArr['document_permission_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'user_permissions_id INT default 0',
		'group_permissions_id INT default 0',
	);
	$indexArr[] = 'dpl_u_perm_id_idx ON document_permission_list(user_permissions_id)';
	$indexArr[] = 'dpl_g_perm_id_idx ON document_permission_list(group_permissions_id)';

	$queryArr['document_permissions'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"permission_id INT DEFAULT 0",
		"group_list_id INT DEFAULT 0",
		"user_list_id INT DEFAULT 0",
	);
	$indexArr[] = 'dp_perm_id_idx ON document_permissions(permission_id)';

	$queryArr['document_field_value_list'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"document_defs_list_id INT NOT NULL DEFAULT 0", 
		"document_id INT NOT NULL DEFAULT 0", 
		"document_field_defs_list_id INT NOT NULL DEFAULT 0",
		"document_field_value VARCHAR(255) NULL",
	  "TimeStamp timestamp NULL"
	);
	$indexArr[] = 'dfvl_defs_id_idx ON document_field_value_list(document_defs_list_id)';
	$indexArr[] = 'dfvl_doc_id_idx ON document_field_value_list(document_id)';
	$indexArr[] = 'dfvl_fdefs_id_idx ON document_field_value_list(document_field_defs_list_id)';
	$indexArr[] = 'dfvl_doc_fval_idx ON document_field_value_list(document_field_value)';
	
	$queryArr['access'] = array (
		'uid '.AUTOINC,
		'PRIMARY KEY (uid)',
		"username VARCHAR(100) NOT NULL",
		'access '.TEXT64K . ' NULL',
	);
	$queryArr['audit'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"username VARCHAR(100) NOT NULL DEFAULT ''",
		'datetime '.DATETIME,
		'info '.TEXT64K." NOT NULL",
		"action VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$queryArr['departments'] = array (
		'departmentid '.AUTOINC,
		'PRIMARY KEY (departmentid)',
		"real_name VARCHAR(128) NOT NULL default ''",
		"departmentname VARCHAR(128) NOT NULL default ''",
		'deleted SMALLINT DEFAULT 0',
	);
	$queryArr['redactions'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"cabinet VARCHAR(100) NOT NULL DEFAULT ''",
		'doc_id INT default 0',
		"subfolder VARCHAR(100) NULL",
		"filename VARCHAR(100) NOT NULL DEFAULT ''",
		'xml_data '.TEXT16M." NOT NULL",
	);
	$queryArr['signatures'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'wf_history_id INT DEFAULT 0',
		'hash '.TEXT64K." NULL",
	);
	$queryArr['temp_tables'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"table_name VARCHAR(255) NOT NULL default ''",
		'expire_time '.DATETIME,
	);

	$queryArr['dwf_taskTypes'] = array (
		'taskTypeID '.AUTOINC,
		'PRIMARY KEY (taskTypeID)',
		'task_type varchar (255) NULL', 
		'task_enabled int NOT NULL' ,
	);
	$queryArr['dwf_node_defs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'wf_node_id int NOT NULL', 
		'if_this text NULL', 
		'then_what text NULL', 
		'task_id int NOT NULL', 
		'reject_task text NULL', 
	);


	$queryArr['wf_defs'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'state INT NOT NULL DEFAULT 0',
		"defs_name VARCHAR(255) NOT NULL DEFAULT ''",
		'prev INT NOT NULL DEFAULT 0',
		'next INT NOT NULL DEFAULT 0',
		'parent_id INT NOT NULL DEFAULT 0',
		'node_id INT NOT NULL DEFAULT 0',
		"owner VARCHAR(255) NOT NULL DEFAULT ''",
		"isDWF int NOT NULL DEFAULT 0",
		"isDraft int NOT NULL DEFAULT 0",
	);
	$queryArr['wf_documents'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'wf_def_id INT NOT NULL DEFAULT 0',
		"cab VARCHAR(255) NOT NULL DEFAULT ''",
		'doc_id INT NOT NULL DEFAULT 0',
		'file_id INT NOT NULL DEFAULT 0',
		'state_wf_def_id INT NOT NULL DEFAULT 0',
		"owner VARCHAR(255) NOT NULL DEFAULT ''",
		"status VARCHAR(255) NOT NULL DEFAULT ''",
		"prev_node_id int NULL ",
	);
	$indexArr[] = 'wf_doc_def_idx ON wf_documents(wf_def_id)';
	$indexArr[] = 'wf_doc_cab_idx ON wf_documents(cab)';
	$indexArr[] = 'wf_doc_doc_idx ON wf_documents(doc_id)';
	$indexArr[] = 'wf_doc_file_idx ON wf_documents(file_id)';
	$queryArr['wf_history'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'wf_document_id INT NOT NULL DEFAULT 0',
		'wf_node_id INT NOT NULL DEFAULT 0',
		'file_id INT NOT NULL DEFAULT 0',
		"action VARCHAR(50) NOT NULL DEFAULT ''",
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		'date_time '.DATETIME,
		'state INT NOT NULL DEFAULT 0',
		'notes '.TEXT64K." NULL",
	);
	$indexArr[] = 'wf_hist_doc_idx ON wf_history(wf_document_id)';
	$indexArr[] = 'wf_hist_node_idx ON wf_history(wf_node_id)';
	$indexArr[] = 'wf_hist_file_idx ON wf_history(file_id)';
	$queryArr['wf_nodes'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"node_type VARCHAR(255) NOT NULL DEFAULT ''",
		"node_name VARCHAR(255) NOT NULL DEFAULT ''",
		'user_list_id INT NOT NULL DEFAULT 0',
		'group_list_id INT NOT NULL DEFAULT 0',
		'message '.TEXT64K." NULL",
		'which_user SMALLINT NOT NULL DEFAULT 1',
		'value_list_id INT NOT NULL DEFAULT 0',
		'email INT NOT NULL DEFAULT 0',
		'message_alert INT NOT NULL DEFAULT 0'
	);
	$queryArr['wf_value_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'value_list_id INT NOT NULL DEFAULT 0',
		'next_node INT NOT NULL DEFAULT 0',
		"message VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$queryArr['group_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'list_id INT NOT NULL DEFAULT 0',
		"groupname VARCHAR(100) NOT NULL DEFAULT ''",
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$queryArr['group_settings'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"groupname VARCHAR(100) NOT NULL DEFAULT ''",
		"k VARCHAR(255) NOT NULL DEFAULT ''",
		'value '.TEXT64K." NOT NULL",
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'gs_k_idx ON group_settings(k)';
	
	$queryArr['groups'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"real_groupname VARCHAR(100) NOT NULL DEFAULT ''",
		"arb_groupname VARCHAR(100) NOT NULL DEFAULT ''",
		'users '.TEXT64K." NULL",
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$queryArr['group_tab'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"cabinet VARCHAR(100) NOT NULL DEFAULT ''",
		'doc_id INT NOT NULL DEFAULT 0',
		"subfolder VARCHAR(100) NOT NULL DEFAULT ''",
		"authorized_group VARCHAR(100) NOT NULL DEFAULT ''",
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$queryArr['user_list'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'list_id INT NOT NULL DEFAULT 0',
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		"department VARCHAR(100) NOT NULL DEFAULT ''",
	);
	$queryArr['barcode_history'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'barcode_rec_id INT NOT NULL DEFAULT 0',
		"barcode_info VARCHAR(255) NOT NULL DEFAULT ''",
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		"cab VARCHAR(255) NULL",
		"barcode_field VARCHAR(255) NULL",
		'delete_barcode SMALLINT DEFAULT 0',
		"split_type VARCHAR(10) DEFAULT ''",
		'compress SMALLINT DEFAULT 0',
		'date_printed '.DATETIME,
		'date_processed '.DATETIME,
		"description VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$indexArr[] = 'bh_username_idx ON barcode_history(username)';
	
	$queryArr['wf_triggers'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'wf_document_id INT NOT NULL DEFAULT 0',
		'entry_date '.DATETIME,
		'times_notified INT NOT NULL DEFAULT 0',
		'notify_list_id INT NOT NULL DEFAULT 0',
		'notify_group_id INT NOT NULL DEFAULT 0',
		'take_action_number INT NOT NULL DEFAULT 0',
		"action VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$queryArr['users_in_group'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'group_id INT NOT NULL DEFAULT 0',
		'uid INT NOT NULL DEFAULT 0',
	);
	$queryArr['group_access'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'group_id INT NOT NULL DEFAULT 0',
		'cabid INT NOT NULL DEFAULT 0',
		"access VARCHAR(100) NOT NULL DEFAULT 'none'",
		
	);
	$queryArr['odbc_auto_complete'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"cabinet_name VARCHAR(255) NOT NULL DEFAULT ''",
		'connect_id INT NOT NULL DEFAULT 0',
		"table_name VARCHAR(255) NOT NULL DEFAULT ''",
		"lookup_field VARCHAR(255) NOT NULL DEFAULT ''",
		"location VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$queryArr['odbc_mapping'] = array (
		'id '.AUTOINC,
		'PRIMARY KEY(id)',
		"cabinet_name VARCHAR (255) NOT NULL DEFAULT ''",
		'level INT NOT NULL DEFAULT 0',
		"odbc_name VARCHAR (255) NOT NULL DEFAULT ''",
		"docutron_name VARCHAR (255) NULL",
		"table_name VARCHAR (255) NOT NULL DEFAULT ''",
		'previous_value SMALLINT NOT NULL DEFAULT 0',
		"odbc_trans VARCHAR (255) NULL",
		"odbc_trans_level INT NOT NULL DEFAULT 0",
		"where_op VARCHAR (255) NULL",
		'odbc_auto_complete_id INT NOT NULL DEFAULT 0',
		"logical_op VARCHAR (255) NULL",
		"grouping VARCHAR (10) NULL",
		"quoted INT NOT NULL DEFAULT 0",
	);

	$queryArr['field_format'] = array (
		"id ".AUTOINC,
		"PRIMARY KEY (id)",
		"cabinet_id INT NOT NULL DEFAULT 0",
		"document_table_name VARCHAR(255) NULL",
		"field_name VARCHAR(255) NOT NULL",
		"required INT DEFAULT 0",
		"regex VARCHAR(255) NULL DEFAULT ''",
		"display VARCHAR(255) NULL DEFAULT ''",
		"is_date INT DEFAULT 0",
	);
	$indexArr[] = 'ff_cab_id_idx ON field_format(cabinet_id)';
	$indexArr[] = 'ff_doc_name_idx ON field_format(document_table_name)';
	$indexArr[] = 'ff_field_name_idx ON field_format(field_name)';

	$queryArr['cabinet_filters'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"username VARCHAR(255) NOT NULL DEFAULT ''",
		"cabinet VARCHAR(255) NOT NULL DEFAULT ''",
		"index1 VARCHAR(255) NOT NULL DEFAULT ''",
		"search VARCHAR(255) NOT NULL DEFAULT ''",
	);
	$queryArr['compliance'] = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"cabinet VARCHAR(255) NOT NULL DEFAULT ''", 
		"document_types VARCHAR(255)", 
	);
	foreach($queryArr as $table => $tableDef) {
		$allQueries[] = "CREATE TABLE $table (".implode(', ', $tableDef).')';
	}

	foreach($indexArr as $subQuery) {
		$allQueries[] = 'CREATE INDEX '.$subQuery;
	}
	$allQueries[] = "ALTER TABLE [dbo].[dwf_taskTypes] ADD  CONSTRAINT [DF_dwf_taskTypes_task_enabled]  DEFAULT ((1)) FOR [task_enabled]";
	$allQueries[] = "SET IDENTITY_INSERT [dbo].[dwf_taskTypes] ON ";
	$allQueries[] = "INSERT [dbo].[dwf_taskTypes] ([taskTypeID], [task_type], [task_enabled]) VALUES (1, N'SIGNATURE', 1)";
	$allQueries[] = "INSERT [dbo].[dwf_taskTypes] ([taskTypeID], [task_type], [task_enabled]) VALUES (2, N'VALUE', 1)";
	$allQueries[] = "INSERT [dbo].[dwf_taskTypes] ([taskTypeID], [task_type], [task_enabled]) VALUES (3, N'ADD FILE', 1)";
	$allQueries[] = "INSERT [dbo].[dwf_taskTypes] ([taskTypeID], [task_type], [task_enabled]) VALUES (4, N'INDEXING', 1)";
	//$allQueries[] = "INSERT [dbo].[dwf_taskTypes] ([taskTypeID], [task_type], [task_enabled]) VALUES (5, N'WORKING', 1)";
	//$allQueries[] = "INSERT [dbo].[dwf_taskTypes] ([taskTypeID], [task_type], [task_enabled]) VALUES (6, N'CUSTOM', 1)";
	$allQueries[] = "SET IDENTITY_INSERT [dbo].[dwf_taskTypes] OFF";
	
	return $allQueries;
}

function dropTable($db_object,$table) {
	$tablesArr = $db_object->manager->listTables();
	$tablesArr = explode(",",strtolower(implode(",",$tablesArr)));
	if(in_array(strtolower($table),$tablesArr)) {
		$query = "DROP TABLE $table";
		$res = $db_object->query($query);
		dbErr($res);
	}
}

function alterTable($db_object,$table,$action,$colName = '',$colDef=NULL) {
	$query = "ALTER TABLE $table $action $colName $colDef"; 
	$res = $db_object->query($query);
	dbErr($res);
}

function dropConstraints ($db, $table, $column) {
	if (getDbType () == 'mssql') {
		$query = <<<QUERY
SELECT  OBJECT_NAME(constid) AS Expr1
FROM     sysconstraints
WHERE  (id = OBJECT_ID('dbo.$table')) AND (COL_NAME(id, colid) = '$column') AND
	(OBJECTPROPERTY(constid, 'IsDefaultCnst') = 1)
QUERY;
		$constraints = $db->queryCol ($query);
		dbErr ($constraints);
		foreach ($constraints as $myConstraint) {
			$query = 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $myConstraint;
			$res = $db->query ($query);
			dbErr($res);
		}
	}
}

function createDocument($db, $docTable) {
	$queryArr = array(
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		"cab_name VARCHAR(255) NOT NULL DEFAULT ''",
		'doc_id INT NOT NULL DEFAULT 0',
		'file_id INT NOT NULL DEFAULT 0',
		'date_created '.DATETIME,
		'date_modified '.DATETIME,
		"created_by VARCHAR(255) NOT NULL DEFAULT ''",
		'deleted SMALLINT NOT NULL DEFAULT 0'
	);
	//foreach($indexArr as $index) {
	//	$queryArr[] = $index.' VARCHAR(255)';
	//}
	$query = "CREATE TABLE ".$docTable." (".implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);
}

function createCabinet($db, $cabinet, $indexArr) {
	$queryArr = array(
		'doc_id '.AUTOINC,
		'PRIMARY KEY (doc_id)',
		'location VARCHAR(100)',
		'deleted SMALLINT DEFAULT 0'
	);
	foreach($indexArr as $index) {
		$queryArr[] = $index.' VARCHAR(255) NULL';
	}
	$queryArr[] = 'TimeStamp timestamp NULL';
	$query = "CREATE TABLE $cabinet (".implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);
}

function createCabinet_files($db, $cabinet,$cabID) {
	$queryArr = array(
		'id '.AUTOINC,
		'PRIMARY KEY(id)',
		"filename VARCHAR(255) NULL",
		'doc_id INT',
		"subfolder VARCHAR(255) NULL",
		'ordering INT DEFAULT 0',
		'date_created '.DATETIME,
		'date_to_delete '.DATETIME,
		"who_indexed VARCHAR(255) NULL",
		"access VARCHAR(255) NULL",
		'ocr_context '.TEXT16M . " NULL",
		'notes '.TEXT16M . " NULL",
		'deleted SMALLINT default 0',
		'parent_id INT default 0',
		'v_major INT default 1',
		'v_minor INT default 0',
		"parent_filename VARCHAR(255) NULL",
		"who_locked VARCHAR(255) NULL",
		'date_locked '.DATETIME,
		'display SMALLINT DEFAULT 1',
		'file_size BIGINT DEFAULT 0',
		"redaction VARCHAR(100) NULL",
		'redaction_id INT DEFAULT 0',
		'document_id INT DEFAULT 0',
		"ca_hash VARCHAR(65) NULL",
		"document_table_name VARCHAR(255) NULL",
	  "TimeStamp timestamp NULL"
	);
	if (getDbType () == 'mysql' or getDbType () == 'mysqli') {
		$queryArr[] = 'FULLTEXT(ocr_context)';
		$queryArr[] = 'FULLTEXT(notes)';
	}
	$query = "CREATE TABLE {$cabinet}_files (".implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);

	$query = "CREATE INDEX cab_{$cabID}_did_idx ON {$cabinet}_files(doc_id)";
	$res = $db->query($query);
	dbErr($res);
	
	$query = "CREATE INDEX cab_{$cabID}_doc_idx ON {$cabinet}_files(document_id)";
	$res = $db->query($query);
	dbErr($res);

	$query = "CREATE INDEX cab_{$cabID}_name_idx ON {$cabinet}_files(document_table_name)";
	$res = $db->query($query);
	dbErr($res);
}

function createCabinet_Files_Sprocs($db_doc, $db, $cabinet, $cabID) {

	$db_doc->loadModule('Function');
	error_log('in sproc func' .$db_doc.$db.$cabinet.$cabID);
	$params = array($db, $cabinet, $cabID);
	$res = $db_doc->function->executeStoredProc('sp_CreateTrigForFileAdded', $params);
	dbErr($res);
	$res = $db_doc->function->executeStoredProc('sp_CreateTrigForFileDelete', $params);
	dbErr($res);
}

function createCabinet_Index_Files($db, $cabinet) {
	$queryArr = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'path VARCHAR(255)',
		'final_path VARCHAR(255)',
		'folder VARCHAR(255)',
		'finished SMALLINT DEFAULT 0',
		'total SMALLINT DEFAULT 0',
		'date '.DATETIME,
		'flag SMALLINT',
		'upforindexing SMALLINT DEFAULT 0'
	);
	$query = 'CREATE TABLE '.$cabinet.'_indexing_table ('.implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);
}

function getTableColumnInfo ($db_object, $table) {
	$indiceArr = array();
	if ($db_object->phptype == 'mssql') {
		$tableInfo = $db_object->queryAll("EXEC SP_COLUMNS[$table]");
		dbErr($tableInfo);
		foreach($tableInfo as $column) {
			$indiceArr[] = strtolower($column['column_name']);
		}
	} elseif ($db_object->phptype == 'mysqli' or $db_object->phptype == 'mysql') {
		$tableInfo = $db_object->queryCol ('DESC ' . $table);
		dbErr($tableInfo);
		foreach($tableInfo as $column) {
			$indiceArr[] = strtolower($column);
		}
	} else {
		if ($db_object->phptype == 'pgsql') {
			$table = strtolower ($table);
		}
		$tableInfo = $db_object->reverse->tableInfo($table);
		dbErr($tableInfo);
		foreach($tableInfo as $column) {
			$indiceArr[] = $column['name'];
		}
	}
	return $indiceArr;
}

function createACTable($db, $cabinet, $indices, $tableName = false) {
	$queryArr = array ();
	if($tableName) {
		$table = $cabinet;
	} else {
		$table = 'auto_complete_'.$cabinet;
	}
	foreach($indices as $index) {
		$queryArr[] = $index.' VARCHAR(255) NULL';
	}
	$query = 'CREATE TABLE '.$table.'('.implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);
}

function createTemporaryTable($db) {
	$tempTable = "zzzTempTable".getrandstring();
	$queryArr = array (
		'table_id '.AUTOINC,
		'PRIMARY KEY (table_id)',
		'result_id INT DEFAULT 0',
	);
	$query = 'CREATE TABLE '.$tempTable.'('.implode(', ', $queryArr).')';
	error_log("createTemporaryTable(): ".$query);
	$res = $db->query($query);
	dbErr($res);

	$query = "CREATE INDEX {$tempTable}_idx ON $tempTable(result_id)";
	$res = $db->query($query);
	dbErr($res);
	
	insertTempTable($db, $tempTable);
	return $tempTable;
}

function createTemporaryFileSearchTable($db) {
	$tempTable = getrandstring();
	$queryArr = array (
		'table_id '.AUTOINC,
		'PRIMARY KEY (table_id)',
		'result_id INT DEFAULT 0',
		'doc_id INT DEFAULT 0',
	);
	if (getDbType () == 'mssql') {
		$queryArr[] = 'rownumber INT DEFAULT 0';
	}
	$query = 'CREATE TABLE '.$tempTable.'('.implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);

	$query = "CREATE INDEX {$tempTable}_id1 ON $tempTable(result_id)";
	$res = $db->query($query);
	dbErr($res);
	
	$query = "CREATE INDEX {$tempTable}_id2 ON $tempTable(doc_id)";
	$res = $db->query($query);
	dbErr($res);
	
	insertTempTable($db, $tempTable);
	return $tempTable;
}

function createDynamicTempTable($db,$queryArr) {
    $tempTable = "zzTempTable".getrandstring();
    $query = 'CREATE TABLE '.$tempTable.'('.implode(', ', $queryArr).')';
    $res = $db->query($query);
    dbErr($res);

	insertTempTable($db,$tempTable);
    return $tempTable;
}

//DO NOT CALL THIS FUNCTION DIRECTLY
function insertTempTable($db, $tempTable) {
	$newDate = mktime(
		date('G') + 1,
		date('i'),
		date('s'),
		date('m'),
		date('d'),
		date('Y')
	);
	$date = date('Y-m-d G:i:s', $newDate);
	$insertArr = array('table_name'=>$tempTable,'expire_time'=>$date);
	$res = $db->extended->autoExecute('temp_tables',$insertArr);
	dbErr($res);
}

function createFileSearchTempTable($db) {
	$tempTable = getrandstring();
	$queryArr = array (
		'table_id '.AUTOINC,
		'PRIMARY KEY(table_id)',
		'result_id INT DEFAULT 0',
		'hits INT DEFAULT 0'
	);
	$query = "CREATE TABLE $tempTable (".implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);
	insertTempTable($db, $tempTable);
	return $tempTable;
}

function createMoveFilesTempTable($db) {
	$tempTable = getrandstring();
	$queryArr = array (
		'id '.AUTOINC,
		'PRIMARY KEY (id)',
		'filename VARCHAR(255)',
		'ext VARCHAR(255)',
		'location VARCHAR(255)',
		'path VARCHAR(255)',
		'doc_id INT'
	);
	$query = "CREATE TABLE $tempTable (".implode(', ', $queryArr).')';
	$res = $db->query($query);
	dbErr($res);
	insertTempTable($db, $tempTable);
	return $tempTable;
}

function alterUploadTable($db, $table, $changeIndices, $indexArr) {
	renameTable ($db, $table, $table.'_temp');
	createACTable($db, $table, $indexArr, true);
	insertFromSelect($db, $table, $changeIndices, $table.'_temp', $indexArr);
	dropTable($db, $table.'_temp');
}

function renameTable($db, $oldTable, $newTable) {
	if (getDbType () == 'pgsql') {
		$query = 'ALTER TABLE ' . $oldTable . ' RENAME TO ' . $newTable;
	} elseif (getDbType () == 'mysql' or getDbType () == 'mysqli' 
		or getDbType () == 'db2') {
		$query = "RENAME TABLE $oldTable TO $newTable";
	} elseif (getDbType () == 'mssql') {
		$query = "EXEC sp_rename '$oldTable', '$newTable'";
	}
	if ($query) {
		$res = $db->query ($query);
		dbErr($res);
	}
}

function createDB($dbName, $dbConn = false) {
	global $DEFS;
	//NOT WINDOWS-SAFE
	if(in_array(getdbType(), array('mysql', 'mysqli', 'pgsql', 'mssql'))) {
		$db = connectToDefaultDB();
		$res = $db->query("CREATE DATABASE $dbName");
		dbErr($res);
		$res = $db->query("CREATE DATABASE ".$dbName."_search");
		dbErr($res);
		$res = $db->query("CREATE DATABASE ".$dbName."_file_audit");
		dbErr($res);
	} elseif(getdbType() == 'db2') {
		include '../db/db_engine.php';
		if($dbConn) {
			global $DEFS;
 			shell_exec('expect ' .
 					escapeshellarg($DEFS['DOC_DIR'] .
 						"/departments/db2createDB.exp") . ' ' .
 					escapeshellarg($db_instance) . ' ' .
 					escapeshellarg($db_password) .  ' ' .
 					escapeshellarg($dbName));
		} else {
 			shell_exec('su - ' . escapeshellarg($db_instance) . ' -c "db2 ' .
 					'CREATE DATABASE ' . escapeshellarg($dbName) . '; ' .
 					'db2 UPDATE CLI CFG FOR SECTION ' . 
 					escapeshellarg($dbName) . ' USING PATCH2 6; ' .
 					'db2 UPDATE CLI CFG FOR SECTION ' .
 					escapeshellarg($dbName) . ' USING LONGDATACOMPAT 1"'
			);
		}
	}
}

function autoCompLoad($dbName,$db_dep,$acCab,$fields,$file,$windows=false) {
	$fileArr = file ($file);
	foreach ($fileArr as $line) {
		if (trim ($line)) {
			$lineArr = explode ("\t", trim ($line));
			for ($i = 0; $i < count ($lineArr); $i++) {
				$field = trim ($lineArr[$i]);
				if ($field) {
					if ($field{0} == '"' and $field{strlen($field) - 1} == '"') {
						$field = substr ($field, 1, strlen ($field) - 2);
					}
				}
				$lineArr[$i] = $field;
			}
			$queryArr = array ();
			for ($i = 0; $i < count ($fields); $i++) {
				if (isset ($lineArr[$i])) {
					$queryArr[$fields[$i]] = $lineArr[$i];
				}
			}
			$res = $db_dep->extended->autoExecute ($acCab, $queryArr);
			dbErr($res);
		}
	}
/*
	if(in_array(getdbType(), array('mysql', 'mysqli'))) {
		$query = "LOAD DATA LOCAL INFILE \"$file\" INTO TABLE ";
		$query .= "$acCab FIELDS OPTIONALLY ENCLOSED BY \"\"";
		if($windows) {
			$query .= " LINES TERMINATED BY '\r\n'";
		}
		$res = $db_dep->query($query);
		if(PEAR::isError($res)) {
			$query = "LOAD DATA INFILE \"$file\" INTO TABLE ";
			$query .= "$acCab FIELDS OPTIONALLY ENCLOSED BY \"\"";
			if( $windows )
				$query .= " LINES TERMINATED BY '\r\n'";
			$res = $db_dep->query($query);
			if(PEAR::isError($res)) {
				$mess = "Error loading file";
				return $mess;
			}
		}
	} elseif(getdbType() == 'db2') {
		include '../db/db_engine.php';
		global $DEFS;
		shell_exec('expect ' .
				escapeshellarg($DEFS['DOC_DIR']."/lib/db2AutoComp.exp") . ' ' .
				escapeshellarg($db_instance) . ' ' .
				escapeshellarg($db_password) . ' ' .
				escapeshellarg($dbName) . ' ' . escapeshellarg($acCab) . ' ' .
				escapeshellarg($file));
	} else if(getdbType() == 'pgsql') {
		$query = "COPY $acCab FROM '$file' USING DELIMITERS '\t' WITH NULL AS ''";	
		$res = $db_dep->query($query);
		if(PEAR::isError($res)) {
			$mess = "Error loading file";
			return $mess;
		}
	} elseif (getdbType() == 'mssql') {
		$query = "BULK INSERT $acCab FROM '$file' WITH (FIELDTERMINATOR = '\t')";
		$res = $db_dep->query ($query);
		dbErr($res);
	}
*/
}

function backupDB($dbName,$path) {
	if(getdbType() == 'db2') {
		global $DEFS;
		include '../db/db_engine.php';
		$answer = shell_exec('expect ' .
				escapeshellarg($DEFS['DOC_DIR']."/lib/db2Backup.exp") . ' ' .
				escapeshellarg($db_instance) . ' ' .
				escapeshellarg($db_password) . ' ' .
				escapeshellarg($dbName) . ' ' . escapeshellarg($path));
	}
}

function connectToDefaultDB() {
	global $DEFS;
	$db = '';
	if(getdbType() == 'mysql' or getdbType() == 'mysqli') {
		$db_engine = $db_username = $db_password = $db_host = '';
		include '../db/db_engine.php';
		$db_dsn = array (
			'phptype'	=> $db_engine,
			'username'	=> $db_username,
			'password'	=> $db_password,
			'hostspec'	=> $db_host,
			'database'	=> 'mysql',
		);
		$db = MDB2::factory($db_dsn, array('portability' => MDB2_PORTABILITY_ALL));
		$db->loadModule('Extended');
		$db->loadModule('Manager');
		dbErr($db);
	} elseif(getdbType() == 'mssql') {
		$db_engine = $db_username = $db_password = $db_host = '';
		include '../db/db_engine.php';
		$db_dsn = array (
			'phptype'	=> $db_engine,
			'username'	=> $db_username,
			'password'	=> $db_password,
			'hostspec'	=> $db_host,
			'database'	=> 'master',
		);
		$db = MDB2::factory($db_dsn, array('portability' => MDB2_PORTABILITY_ALL));
		dbErr($db);
		$db->loadModule('Extended');
		$db->loadModule('Manager');
		$db->loadModule('Function');
	} elseif(getdbType() == 'pgsql') {
		$db_engine = $db_username = $db_password = $db_host = '';
		include '../db/db_engine.php';
		$db_dsn = array (
			'phptype'	=> $db_engine,
			'username'	=> $db_username,
			'hostspec'	=> $db_host,
			'database'	=> 'template1',
		);
		$db = MDB2::factory($db_dsn, array('portability' => MDB2_PORTABILITY_ALL));
		$db->loadModule('Extended');
		$db->loadModule('Manager');
		dbErr($db);
	}
	return $db;
}

 ?>
