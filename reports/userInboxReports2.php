<?php
require_once '../check_login.php';
require_once '../lib/settings.php';
require_once '../db/db_common.php';
require_once '../DataObjects/DataObject.inc.php';
error_reporting(E_ALL);
if( $logged_in == 1 && strcmp( $user->username, "" )!=0 ) {
	$data_dir = $DEFS['DATA_DIR'];

	$DO_users = DataObject::factory ('users', $db_doc);
	$DO_users->get('username',$user->username);

	$allUserArr = array ();
	$inboxArr = array ();
	foreach($DO_users->departments AS $dep => $priv) {
		if($priv == "D") {
			$query = "select arb_department from licenses where real_department='".$dep."'";
			$res = $db_doc->queryAll($query);
			$db_dept = getDbObject($dep);
			$sArr = array('username');
			$userList = getTableInfo($db_dept,'access',$sArr,array(),'queryCol');
			foreach($userList AS $u) {
				if(!in_array($u,$allUserArr)) {
					$inboxArr['username'] = $u;
					$fileArr = array();
					if(is_dir ($DEFS['DATA_DIR'].'/'.$dep)) {
						getFileList ($DEFS['DATA_DIR'].'/'.$dep.'/personalInbox/'.$u, $fileArr);
					}
					$inboxArr['data'] = $fileArr;
					$inboxArr['department'] = $res[0]['arb_department'];
					$allUserArr[] = $inboxArr;
				}
			}
			$db_dept->disconnect();
		}
	}
	
	
	
	$timeArr = array();
	foreach( $allUserArr as $userArr ){
//		print_r( $userArr );	
		$username = $userArr['username'];
		$dep = $userArr['department'];
		foreach( $userArr['data'] as $desc => $dataArr ){
			$count = 0;
//			$description = array_keys($dataArr);
//		print_r( $description );	
			$description = $desc;
		//	echo $description."<br>";
			$time = 0;
			foreach( $dataArr as $fname=>$timestamp){
				$count++;
				if($time == 0 or $time >$timestamp ){
					$time= $timestamp;
				}
			}
//echo "$username $timestamp $description $count<br>";
			$description = explode( '/', $description );
			$description = array_pop( $description );
			$timeArr[$timestamp][]=array('department'=>$dep,'username'=>$username,'date'=>date("Y-m-d",$timestamp),'desc'=>"**$description",'count'=>$count );
			if( $time==0){
				//do not add
			}
		}	
	}
//ksort( $timeArr );
ini_set('zlib.output_compression','Off');
header( 'Pragma:' );
header( 'Content-Type : application/vnd.ms-excel' );
header("Content-Disposition: attachment; filename=a.xls;");
echo "Department\tUsername\tOldest File Date\tFolder\tCount\n"; 
foreach($timeArr as $slot ){
	foreach( $slot as $row ){
		echo implode( "\t", $row )."\n";
	}
}
}else {
	logUserOut();
}
function getOldestTime ($fList) {
	$oldest = time ();
	foreach ($fList as $fName => $mTime) {
		if($mTime < $oldest) {
			$ret = array($fName, $mTime);
			$oldest = $mTime;
		}
	}
	return $ret;
}
function getFileList ($dir, &$array) {
	if(is_dir ($dir)) {
		$dh = opendir ($dir);
		while($str = readdir ($dh)) {
			if($str != '.' and $str !=  '..') {
				$fpath = $dir.'/'.$str;
				if (is_dir ($fpath)) {
					getFileList ($dir.'/'.$str, $array);
				} else {
					$st = stat ($dir.'/'.$str);
					$array[$dir][$fpath] = $st['mtime'];
					
				}
			}
		}
	}
}
?>
