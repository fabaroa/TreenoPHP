<?php
if(file_exists('lib/licenseFuncs.php')) {
	include_once 'lib/licenseFuncs.php';
} else {
	include_once 'licenseFuncs.php';
}

class license {
	var $mac;
	var $expireDate;
	var $numLicenses;
	var $allModules;
	var $modules;
	var $license;
	var $validMAC;
	var $md5;
	var $padStart;

	function license($mac=NULL,$expireDate=NULL,$numLicenses=NULL,$modules=NULL,$license=NULL) {
		$this->setModulesArray();
		$this->validMAC = false;
		if(!$license) {
			$this->stripMacAddr($mac);
			$this->setExpireTime($expireDate);
			$this->numLicenses = $numLicenses;
			$this->modules = $modules;
		} else {
			$md5 = substr($license,0,4);
			$this->md5 = $md5;
			$shift = substr($license,4,3);
			$this->padStart = $this->convertBase($shift,36,2);
			$num = $this->convertBase($shift,36,10);
			$md5Dec = $this->convertBase($md5,16,10);
			$license = $this->convertBase(substr($license,7),36,2);

			$license = substr($license,6);
			$start = $num + $md5Dec;
			$kgbKey = $this->createKGB($start,$license);
			$this->license = bitXOR($license,$kgbKey);
			$this->getMACAddress();
			$this->getExpireTime();
			$this->getNumLicenses();
			$this->getModulesArray();
		}
	}

	function hasValidMAC() {
		return $this->validMAC;
	}

	function getMACAddress() {
		$this->mac = $this->convertBase(substr($this->license,48,48),2,16,12);	
		if($this->mac == getServerMAC()) {
			$this->validMAC = true;
		} else {
			$this->validMAC = false;
		}
	}

	function getExpireTime() {
		$expDate = $this->convertBase(substr($this->license,16,32),2,10);	
		$this->expireDate = gmdate("Y-m-d",$expDate);
	}

	function getNumLicenses() {
		$this->numLicenses = $this->convertBase(substr($this->license,0,16),2,10);	
	}

	function getModulesArray() {
		$modules = substr($this->license,96);	
		$modArr = array();
		for($i=0;$i<strlen($modules);$i++) {
			if($modules{$i} == "1") {
				$modArr[$i] = $this->allModules[$i];
			}
		}
		$this->modules = $modArr;
	}

	function convertBase($str, $from, $to, $padLength = false) {
		$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$tostring = substr($chars, 0, $to);

		$length = strlen($str);
		$result = '';
		for ($i = 0; $i < $length; $i++) {
		   $number[$i] = strpos($chars, $str{$i});
		}

		do {
		   $div = 0;
		   $newlen = 0;
		   for ($i = 0; $i < $length; $i++) {
			   $div = $div * $from + $number[$i];
			   if ($div >= $to) {
				   $number[$newlen++] = (int)($div / $to);
				   $div = $div % $to;
			   } elseif ($newlen > 0) {
				   $number[$newlen++] = 0;
			   }
		   }
		   $length = $newlen;
		   $result = $tostring{$div} . $result;
		}
		while ($newlen != 0);
		if($padLength) {
			$result = str_pad($result, $padLength, '0', STR_PAD_LEFT);
		}
		return $result;
	} 

	function setExpireTime($date) {
		if($date) {
			$dArr = explode("-",$date);
			$this->expireDate = gmmktime(0,0,0,$dArr[1],$dArr[2],$dArr[0]);
		} else {
			$this->expireDate = 0;
		}
	}

	function stripMacAddr($mac) {
		$mac = strtoupper($mac);
		$mac = str_replace(':','',$mac);
		$mac = str_replace('-','',$mac);
		$mac = trim(str_replace(' ','',$mac));
		$this->mac = $mac;
	}
	function checkMd5() {
		//md5 the original unencoded license with the padStart bits
		return $this->md5 == strtoupper(substr(md5($this->padStart.$this->license ),-4));
	}
	function generateLicense() {
		$licBits = $this->convertBase((string)$this->numLicenses,10,2,16);
		$tsBits = $this->convertBase((string)$this->expireDate,10,2,32);
		$macBits = $this->convertBase((string)$this->mac,16,2,48);
		$modBits = $this->getModuleBits();
		$padStart = mt_rand(1297,9500);
		$padBits = $this->convertBase((string)$padStart,10,2);
		$genBits = $licBits.$tsBits.$macBits.$modBits;
		$md5 = strtoupper(substr(md5($padBits.$genBits),-4));
		$newPadStart = $padStart + $this->convertBase((string)$md5,16,10);
		$genBits = '100011'.$this->licCrypt($genBits,$newPadStart);
		$key = $this->convertBase($genBits,2,36);
		$pad = $this->convertBase((string)$padStart,10,36);
		return $md5.$pad.$key;
	}

	function bitAnd($genBits,$padStart) {
		$padBit = getPad();
		$bitStr = "";
		for($i=0;$i<strlen($genBits);$i++) {
			if($padBit{$padStart + $i} == '1' && $genBits{$i} == '1') {
				$bitStr .= '1';
			} else {
				$bitStr .= '0';
			}
		}
		return $bitStr;
	}

	function licCrypt($genBits, $padStart) {
		$kgbKey = $this->createKGB($padStart,$genBits);

		return bitXOR($genBits, $kgbKey);
	}

	function createKGB($start,$str) {
		$pad = getPad();
		$loc = $start % strlen($pad);

		$kgbKey = '';
		$i = 0;
		while($i < strlen($str)) {
			if($loc == strlen($pad)) {
				$loc = 0;
			}
			$kgbKey .= $pad{$loc};

			$i++;
			$loc++;
		}
		return $kgbKey;
	}

	function getModuleBits() {
		$bitStr = ""; 
		foreach($this->allModules as $mod) {
			if(in_array($mod, $this->modules)) {
				$bitStr .= '1';
			} else {
				$bitStr .= '0';
			}
		}
		return str_pad ($bitStr, 64, '0', STR_PAD_RIGHT);
	}

	function setModulesArray() {
		$this->allModules = array (
			'0' => 'audit',
			'1' => 'auto_indexing',
			'2' => 'CD_Backup',
			'3' => 'context_search',
			'4' => 'ocr',
			'5' => 'Update_Info',
			'6' => 'Barcoding',
			'7' => 'versioning',
			'8' => 'departments',
			'9' => 'Groups',
			'10' => 'workflow',
			'11' => 'redaction',
			'12' => 'MAS500',
			'13' => 'searchResODBC',
			'14' => 'outlook',
			'15' => 'reports',
			'16' => 'publishing',
			'17' => 'centera',
			'18' => 'administration',
			'19' => 'lite',
			'20' => 'demo',
			'21' => 'global_search',
			'22' => 'eSign',
			'23' => 'compliance',
			'24' => 'dwf',
			'25' => '',
			'26' => '',
			'27' => '',
			'28' => '',
			'29' => '',
			'30' => '',
			'31' => '',
			'32' => '',
			'33' => '',
			'34' => '',
			'35' => '',
			'36' => '',
			'37' => '',
			'38' => '',
			'39' => '',
			'40' => '',
			'41' => '',
			'42' => '',
			'43' => '',
			'44' => '',
			'45' => '',
			'46' => '',
			'47' => '',
			'48' => '',
			'49' => '',
			'50' => '',
			'51' => '',
			'52' => '',
			'53' => '',
			'54' => '',
			'55' => '',
			'56' => '',
			'57' => '',
			'58' => '',
			'59' => '',
			'60' => '',
			'61' => '',
			'62' => '',
			'63' => '',
		);
/*		$mods = array();
		$mods[0] = array( 'audit'=>0 );//0
		$mods[1] = array( 'auto_indexing'=>0);//1
		$mods[2] = array( 'CD_Backup'=>0);//2
		$mods[3] = array( 'context_search'=>0);//3
		$mods[4] = array( 'ocr'=>0);//4
		$mods[5] = array( 'Update_Info'=>0);//5
		$mods[6] = array( 'Barcoding'=>0);//6
		$mods[7] = array( 'versioning'=>0);//7
		$mods[8] = array( 'departments'=>0);//8
		$mods[9] = array( 'Groups'=>0);//9
		$mods[10] = array( 'workflow'=>0);//10
		$mods[11] = array( 'redaction'=>0);//11
		$mods[12] = array( 'MAS500'=>0);//12
		$mods[13] = array( 'searchResODBC'=>0);//13
		$mods[14] = array( 'outlook'=>0);//14
		$mods[15] = array( 'reports'=>0);//15
		$mods[16] = array( 'publishing'=>0);//16
		$mods[17] = array( 'centera'=>0);//17
		$mods[18] = array( 'administration'=>0);//18
		$mods[19] = array( ''=>0);//19
		$mods[20] = array( ''=>0);//20
		$mods[21] = array( ''=>0);//21
		$mods[22] = array( ''=>0);//22
		$mods[23] = array( ''=>0);//23
		$mods[24] = array( ''=>0);//24
		$mods[25] = array( ''=>0);//25
		$mods[26] = array( ''=>0);//26
		$mods[27] = array( ''=>0);//27
		$mods[28] = array( ''=>0);//28
		$mods[29] = array( ''=>0);//29
		$mods[30] = array( ''=>0);//30
		$mods[31] = array( ''=>0);//31
		$mods[32] = array( ''=>0);//32
		$mods[33] = array( ''=>0);//33
		$mods[34] = array( ''=>0);//34
		$mods[35] = array( ''=>0);//35
		$mods[36] = array( ''=>0);//36
		$mods[37] = array( ''=>0);//37
		$mods[38] = array( ''=>0);//38
		$mods[39] = array( ''=>0);//39
		$mods[40] = array( ''=>0);//40
		$mods[41] = array( ''=>0);//41
		$mods[42] = array( ''=>0);//42
		$mods[43] = array( ''=>0);//43
		$mods[44] = array( ''=>0);//44
		$mods[45] = array( ''=>0);//45
		$mods[46] = array( ''=>0);//46
		$mods[47] = array( ''=>0);//47
		$mods[48] = array( ''=>0);//48
		$mods[49] = array( ''=>0);//49
		$mods[50] = array( ''=>0);//50
		$mods[51] = array( ''=>0);//51
		$mods[52] = array( ''=>0);//52
		$mods[53] = array( ''=>0);//53
		$mods[54] = array( ''=>0);//54
		$mods[55] = array( ''=>0);//55
		$mods[56] = array( ''=>0);//56
		$mods[57] = array( ''=>0);//57
		$mods[58] = array( ''=>0);//58
		$mods[59] = array( ''=>0);//59
		$mods[60] = array( ''=>0);//60
		$mods[61] = array( ''=>0);//61
		$mods[62] = array( ''=>0);//62
		$mods[63] = array( ''=>1);//63 //gives us a large number to obfuscate 
		$this->modulesArr = $mods;
		//return $mods;
*/
	}
}

?>
