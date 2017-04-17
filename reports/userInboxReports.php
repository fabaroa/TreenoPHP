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
					$allUserArr[] = $inboxArr;
				}
			}
			$db_dept->disconnect();
		}
	}
echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Inbox Metrics</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/behaviour.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/sorttable.js"></script>
<script type="text/javascript">
function initFunc () {
	ts_makeSortable ($('myTable'));
}
Event.observe (window, 'load', initFunc);
</script>
<style type="text/css">
div.mainDiv {
	width: 600px;
}

td {
	text-align: left;
}

a {
	text-decoration: none;
	color: black;
}
</style>
</head>
<body class="centered">
<div class="mainDiv">
<div class="mainTitle">
<span>Personal Inbox Report</span>
</div>
<div class="inputForm">
<table id="myTable">
<tr>
<th>User</th>
<th>Number of Pages</th>
<th>Oldest Page</th>
<th>Page Date</th>
</tr>
HTML;
foreach ($allUserArr as $dataset) {
	$number = sizeof ($dataset['data']);
	$mod_time = '-';
	if ($number) {
		$mod_time = getOldestTime ($dataset['data']);
	}
	if ($number != 0) {
		echo<<<HTML
<tr>
<td>{$dataset['username']}</td>
<td>$number</td>
HTML;
		if ($mod_time != '-') {
			echo '<td>'.basename($mod_time[0]).'</td>';
			echo '<td>'.date ('F j, Y, g:i a ', $mod_time[1]).'</td>';
		} else {
			echo '<td>'.$mod_time.'</td>';
		}
		echo '</tr>';
	}
}
echo<<<HTML
</table>
</div>
</div>
</body>
</html>
HTML;
	setSessionUser($user);
} else {
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
					$array[$fpath] = $st['mtime'];	
				}
			}
		}
	}
}
?>
