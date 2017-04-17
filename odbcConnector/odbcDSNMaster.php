<?php
include_once '../lib/settings.php';

require_once('odbcDSN.php');
class odbcDSNMaster {
	var $dsnArray;
	function popDSNArray() {
		$newArray = array();
		$lines = file("{$DEFS['DATA_DIR']}/.odbc.ini");
		foreach($lines as $line) {
			if($this->isNameLine($line)) {
				if(isset($newDSN)) {
					$newArray = $newDSN;
				}
				$newDSN = new odbcDSN();
				$newName = $this->extractName($line);
				$newDSN->setName($newName);
			} else if($this->whichLine($line, 'Driver')) {
				$newDSN->setDriver($this->extractValue($line));
			} else if($this->whichLine($line, 'Server')) {
				$newDSN->setServer($this->extractValue($line));
			} else if($this->whichLine($line, 'Database')) {
				$newDSN->setDB($this->extractValue($line));
			} else if($this->whichLine($line, 'Port')) {
				$newDSN->setPort($this->extractValue($line));
			} else if($this->whichLine($line, 'Password')) {
				$newDSN->setPasswd($this->extractValue($line));
			}
		}
		$newArray[] = $newDSN;
		$this->dsnArray = $newArray;
	}

	function appendDSN($newDSN) {
		$this->dsnArray[] = $newDSN;
	}

	function whichLine($line, $key) {
		if(strpos($line, $key) !== false) {
			return true;
		} else {
			return false;
		}
	}

	function extractValue($line) {
		$start = strpos($line, '=') + 1;
		return trim(substr($line, $start));
	}

	function isNameLine($line) {
		if(strpos($line, '[') !== false) {
			return true;
		} else {
			return false;
		}
	}

	function extractName($line) {
		$start = strpos($line, '[') + 1;
		$end = strpos($line, ']');
		$length = $end - $start;
		return substr($line, $start, $length);
	}
	function writeINI() {
		foreach($this->dsnArray as $DSN) {
			$name = $DSN->getName();
			$driver = $DSN->getDriver();
			$server = $DSN->getServer();
			$db = $DSN->getDB();
			$port = $DSN->getPort();
			$passwd = $DSN->getPasswd();
			$out .= "[$name]\n";
			$out .= "Driver = $driver\n";
			$out .= "Server = $server\n";
			$out .= "Database = $db\n";
			$out .= "Port = $port\n";
			if($passwd)
				$out .= "Password = $passwd\n";
		}
			$out .= "\n";
		$fd = fopen("{$DEFS['DATA_DIR']}/.odbc.ini", 'w+');
		fwrite($fd, $out);
		fclose($fd);
	}
}

?>
