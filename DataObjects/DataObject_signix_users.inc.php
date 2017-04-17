<?php

class DataObject_signix_users extends DataObject {
	//These are the public variables that you can set to query the table
	var $id;
	var $signix_userid;
	var $signix_password;
	var $signix_sponsor;
	var $signix_client;
	
	function DataObject_signix_users(&$db, $useObj = null) {
		DataObject::DataObject($db, $useObj);
	}
	
	function _tableColumns() {
		return array (
			'id'				=>	true,
			'signix_userid'		=>	true,
			'signix_password'	=>	true,
			'signix_sponsor'	=>	true,
			'signix_client'		=>	true,
		);
	}
	
	function _tableName() {
		return 'signix_users';
	}
	
	function _key() {
		return 'id';
	}
	
	function get($key, $value = '') {
		DataObject::get($key, $value);
	}
	
	function delete() {
		DataObject::delete();
	}
}

?>
