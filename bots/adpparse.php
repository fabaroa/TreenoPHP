<?php
//adpparse.php - Parsing the ADP printouts to insert into the auto_complete
//table. Supply a filename to adpparse.php as its only argument.



//Verify that file exists before trying to open it.
if(file_exists($argv[1]))
	$fileArr = file($argv[1]);
else die("error reading file $argv[1]\n");

$cab = "Work_Orders";
$db_object = getDbObject("client_files");

//needed for loop, collects entries for insert query
$queryArr = array();

//Loop through every line of the file supplied, only parsing for the variables
//that are not yet set
for($i = 0; $i < count($fileArr); $i++) {
	if(!isset($queryArr['Phone_Numbers'])) {
		$searchBefore = 'HOME:';
		$homeLoc = strpos($fileArr[$i], $searchBefore);
		if($homeLoc === 0) {
			$phoneNum = trim($fileArr[$i]);
			if($phoneNum) {
				$queryArr['Phone_Numbers'] = $phoneNum;
			}
			$j = $i;
			//The address is the lines that are not blank, and are not CUSTOMER
			//#.
			$foundAddr = false;
			while(!$foundAddr) {
					if($fileArr[$j][0] !== ' ' and 
					   strpos($fileArr[$j], 'CUSTOMER #') === false) $j--;
					else if(!trim($fileArr[$j]) and trim($fileArr[$j - 1]) and
							strpos($fileArr[$j], 'CUSTOMER #') === false) $j--;
					else $foundAddr = true;
			}
			$addr = "";
			for($k = $j + 1; $k < $i; $k++) {
				$endPos = strpos($fileArr[$k], '  ');
				$strPrint = trim(substr($fileArr[$k], 0, $endPos));
				if($strPrint)
					$addr .=  " ".$strPrint;
			}
			if(isset($addr) and $addr) 
				$queryArr['Name_or_Address'] = trim($addr);
		}
	}
	if(!isset($queryArr['RO_Number'])) {
		$searchBefore = "\x1b&k18.72H";
		$beforeRO = strpos($fileArr[$i], $searchBefore);
		if($beforeRO !== false) {
			$RONum = findString($fileArr[$i], $searchBefore, $beforeRO, "\x1b");
			if(is_numeric($RONum)) 
				$queryArr['RO_Number'] = $RONum;
		}
	}
	if(!isset($queryArr['Customer_Number'])) {
		//17 spaces
		$beforeBlankStr = '                 ';
		$beforeCN = strpos($fileArr[$i], $beforeBlankStr);
		if($beforeCN === 0) {
			$CNum = findString($fileArr[$i],
								$beforeBlankStr,
								$beforeCN,
								' ');
			if(is_numeric($CNum))
				$queryArr['Customer_Number'] = $CNum;
		}
	}
	if(!isset($queryArr['Customer_Number'])) {
		$custNum = "CUSTOMER #:";
		$beforeCN = strpos($fileArr[$i], $custNum);
		if($beforeCN !== false) {
			$CNum = findString($fileArr[$i], $custNum, $beforeCN, " ");
			if(is_numeric($CNum)) 
				$queryArr['Customer_Number'] = $CNum;
		}
	}
	if(!isset($queryArr['Del_Date'])) {
		$regex = "/^[0-9][0-9][A-Z][A-Z][A-Z][0-9][0-9][0-9][0-9]/";
		$results = preg_match($regex, $fileArr[$i]);
		if($results) {
			if($tmp = trim(substr($fileArr[$i], 0, 10)))
				$queryArr['Del_Date'] = $tmp;
			if($tmp = trim(substr($fileArr[$i], 18, 9)))
				$queryArr['Warr_Exp'] = $tmp;
			if($tmp = trim(substr($fileArr[$i], 72, 13)))
				$queryArr['Inv_Date'] = $tmp;
			$j = $i - 1;
			while(!trim($fileArr[$j])) 
				$j--;
			
			if($tmp = trim(substr($fileArr[$j], 11, 2)))
				$queryArr['Year'] = $tmp;
			if($tmp = trim(substr($fileArr[$j], 14, 22)))
				$queryArr['Make_Model'] = $tmp;
			if($tmp = trim(substr($fileArr[$j], 36, 19)) and strlen($tmp) == 17)
				$queryArr['VIN'] = $tmp;
			if($tmp = trim(substr($fileArr[$j], 55, 9)))
				$queryArr['License'] = $tmp;
		}
	}
	if(!isset($queryArr['Options'])) {
		if(preg_match('/^.{15}[0-9]{2}\S[0-9]{2}\s\S{7}/', $fileArr[$i])) {
			$options = trim($fileArr[$i - 2]);
			$options .= " ".trim($fileArr[$i - 1]);
			$options .= trim(substr($fileArr[$i], 30, 53));
			if($tmp = trim($options))
				$queryArr['Options'] = $tmp;
		}
	}
}
if(isset($queryArr['ro_number'])) {
		$result = getTableInfo($db_object, 'auto_complete_'.$cab,
			array('COUNT(*)'),
			array('ro_number' => $queryArr['ro_number']),
			'queryOne');
		if($result) {
			$numNotNullOld = 0;
			foreach($result as $col) {
				if($col) $numNotNullOld++;
			}
			$numNotNullNew = 0;
			foreach($queryArr as $col) {
				if($col) $numNotNullNew++;
			}
			if($numNotNullNew > $numNotNullOld) {
				deleteTableInfo($db_obect,$table,array('ro_number'=>$queryArr['ro_number']));

			} else {
				exit(0);
			}
		}
		$result = $db_object->extended->autoExecute("auto_complete_$cab", $queryArr);
		if(PEAR::isError($result)) {
			echo $result->getMessage()."\n";
			$db_object->disconnect ();
			die();
		}
}
$db_object->disconnect ();

//Looks for and grabs a substring given the two neighboring strings
function findString($string, $searchStr, $beforeLoc, $afterStr)
{
	$start = $beforeLoc + strlen($searchStr);
	$end = strpos($string, $afterStr, $start);
	$length = $end - $start;
	return trim(substr($string, $start, $length));
}

?>
