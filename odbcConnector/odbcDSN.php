<?php
class odbcDSN {
	var $dsnName;
	var $dsnDriver;
	var $dsnServer;
	var $dsnDB;
	var $dsnPort;
	var $dsnPasswd;
	function setName($name) {
		$this->dsnName = $name;
	}
	function getName() {
		return $this->dsnName;
	}
	function setDriver($driver) {
		$this->dsnDriver = $driver;
	}
	function getDriver() {
		return $this->dsnDriver;
	}
	function setServer($server) {
		$this->dsnServer = $server;
	}
	function getServer() {
		return $this->dsnServer;
	}
	function setDB($db) {
		$this->dsnDB = $db;
	}
	function getDB() {
		return $this->dsnDB;
	}
	function setPort($port) {
		$this->dsnPort = $port;
	}
	function getPort() {
		return $this->dsnPort;
	}
	function setPasswd($passwd) {
		$this->dsnPasswd = $passwd;
	}
	function getPasswd() {
		return $this->dsnPasswd;
	}
}
?>
