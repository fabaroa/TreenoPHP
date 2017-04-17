<?php
include_once '../check_login.php'; 
include_once '../settings/settings.php';

$db_doc = getDbObject ('docutron');
$settings = new GblStt($user->db_name, $db_doc);
$tutorial = "Tutorial";

$usrSett = new Usrsettings($user->username,$user->db_name);

$seeReport = false;
if(false !== ($sett = $usrSett->get('versioningReportAccess'))) {
	$seeReport = ($sett) ? true : false;	
} else {
	$seeReport = ($settings->get('versioningReportAccess')) ? true : false;
}

if($logged_in == 1 && strcmp($user->username,"") != 0 && ($user->isDepAdmin() || $seeReport)) {
$db_dept = $user->getDbObject();

$sArr = array('real_name');
$wArr = array('deleted = 0',
				"LOWER(real_name) != 'publishing'");
$cabList = getTableInfo($db_dept,'departments',$sArr,$wArr,'queryCol');

$folderInfo = array();
$verArr = array();
foreach($cabList AS $cab) {
	//error_log("****************** Cabinet: ".$cab." *******************************");
	//if($cab != "Legacy_Integrator") continue;
	$indexArr = getCabinetInfo($db_dept,$cab);
	$sArr = array('doc_id',implode(",",$indexArr));
	$wArr = array('deleted = 0');
	$folderList = getTableInfo($db_dept,$cab,$sArr,$wArr,'getAssoc');
	$folderInfo[$cab] = $folderList;
	//error_log("folderList: ".print_r($folderList, true));
	$docIDs = implode(",",array_keys($folderList));
	//error_log("docIDs: ".$docIDs);
	if(count($folderList)){
		$sArr = array('doc_id','parent_filename','who_indexed','date_created','filename');
		$wArr = array(	'deleted = 0',
						'display = 1',
						'doc_id IN('.implode(",",array_keys($folderList)).')',
						"(subfolder ='' or subfolder is null)");
		//$files = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'queryAll');
		$gArr = array('doc_id','parent_filename','who_indexed','date_created','filename');
		$verArr[$cab] = getTableInfo($db_dept,$cab."_files",$sArr,$wArr,'getAssoc',array(),0,0,$gArr,true);
	}
	//error_log("verArr[$cab]: ".print_r($verArr[$cab], true));
}
//error_log("folderInfo: ".print_r($folderInfo, true));
/*
$folderInfo = array();
foreach($verArr AS $cab => $info) {
	if(count($info)) {
		$indexArr = getCabinetInfo($db_dept,$cab);
		$sArr = array('doc_id',implode(",",$indexArr));
		$wArr = array('doc_id IN('.implode(",",array_keys($info)).')');
		$folderList = getTableInfo($db_dept,$cab,$sArr,$wArr,'getAssoc');
		$folderInfo[$cab] = $folderList;
	}
}*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Files In Main Tabs</title>
	<link rel="stylesheet" type="text/css" href="../lib/style.css" />
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<script type="text/javascript" src="../lib/sorttable.js"></script>
	<script type="text/javascript">
		function adjustWidth() {
			var clientw = document.documentElement.clientWidth;
			$('mainDiv').style.width = (clientw *.95)+'px';
		}

		function initFunc () {
			ts_makeSortable ($('myTable'));
		}
		Event.observe (window, 'load', initFunc);
	</script>
	<style type="text/css">
		td {
			text-align: left;
		}

		a {
			text-decoration: none;
			color: black;
		}
	</style>
</head>
<body onload="adjustWidth()">
<div id="mainDiv" class="mainDiv">
<div class="mainTitle">
<span>Files In Main Tabs</span>
</div>
<div class="inputForm" style="width:100%">
<table id="myTable">
<tr>
<th>Creator</th>
<th>Creation Date</th>
<th>Cabinet</th>
<th>Folder</th>
<th>doc_id</th>
<th>Filename</th>
</tr>
<?php foreach($verArr AS $cab => $info) :?>
	<?php if(count($info)) : ?>
		<?php foreach($info AS $doc_id => $fInfo) : ?>
			<?php foreach($fInfo AS $f) : ?>
	<tr>
		<td><?php echo $f['who_indexed']; ?></td>
		<td style="white-space:nowrap"><?php echo $f['date_created']; ?></td>
		<td><?php echo $user->cabArr[$cab]; ?></td>
		<td><?php echo h(implode(" ",$folderInfo[$cab][$doc_id])); ?></td>
		<td><?php echo $doc_id; ?></td>
		<td><?php echo $f['parent_filename']; ?></td>
	</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
	<?php endif; ?>
<?php endforeach; ?>
</table>
</div>
</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
