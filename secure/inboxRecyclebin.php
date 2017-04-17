<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/searchLib.php';
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../lib/licenseFuncs.php';

function recyclebinController($type,$filesSelected,$user) {
	global $DEFS;
	$db_doc = getDbObject('docutron');
	foreach($filesSelected AS $id) {
		$auditStr = '';
		$whereArr = array('id' => (int)$id);
		$row = getTableInfo($db_doc,'inbox_recyclebin',array(),$whereArr,'queryRow');	
		$path = $DEFS['DATA_DIR']."/".$user->db_name."/recyclebin/";
		$dateArr = preg_split('/[^(0-9)*]/',$row['date_deleted']);	
		$path .= date('Y-m-d',mktime(0,0,0,$dateArr[1],$dateArr[2],$dateArr[0]));
		$path .= "/".$row['username'];
		
		$time = date('G:i:s',mktime($dateArr[3],$dateArr[4],$dateArr[5]));
		if(!is_dir($path."/".$time)) {
			$time = date('G-i-s',mktime($dateArr[3],$dateArr[4],$dateArr[5]));
		}
		$path .= "/".$time;
		$auditStr = "";
		//$path .= "/".date('G:i:s',mktime($dateArr[3],$dateArr[4],$dateArr[5]));
		if(trim($row['folder'])) {
			$path .= "/".$row['folder'];
			$auditStr .= 'Folder: '.$row['folder'].' ';
		}
		$path .= "/".$row['filename'];

		if($type == "delete") {
			if(file_exists($path)) {
				unlink($path);
				$auditStr .= 'Filename: '.$row['filename'];
			} else {
				$p = "";
				if(trim($row['folder'])) {
					$p = $row['folder'];
				}
				$p = $row['filename'];
				$auditStr = "File doesn't exist: $p";
			}
			$user->audit($row['type'].' inbox file removed',$auditStr);
		} else {
			$restorePath = $row['path'];
			if($row['type'] == 'personal') {
				$auditStr = 'Personal Inbox: '.$auditStr;
			} else { 
				$auditStr = 'Public Inbox: '.$auditStr;
			}

			if(!is_dir($restorePath)) {
				mkdir($restorePath,0755);
			}

			if(trim($row['folder'])) {
				$pathArr = explode("/",$restorePath);
				$f = $pathArr[count($pathArr)-2];
				if($f != $row['folder']) {
					$restorePath .= $row['folder'];
					if(!is_dir($restorePath)) {
						mkdir($restorePath,0755);
					}
				}
			}

			if(file_exists($path)) {
				$filename = $row['filename'];
				$ct = 1;
				while(file_exists($restorePath."/".$filename)) {
					$filename = $ct."-".$row['filename'];	
					$ct++;
				}
				rename($path,$restorePath."/".$filename);
				$auditStr .= 'Filename '.$filename;
				$user->audit('file restored',$auditStr);
			}
		}
		deleteTableInfo($db_doc,'inbox_recyclebin',$whereArr);
	}
}

if( $logged_in == 1 && strcmp( $user->username, "" )!=0 && $user->isDepAdmin()) {
	if(!isValidLicense($db_doc)) {
?>
<html>
	<head>
		<title>Inbox Recycle Bin</title>
	</head>
	<body style="color:red">
		<div>Invalid License</div>
		<div>Recycle Bin Operations Are Not Permitted</div>
	</body>
</html>
<?php
	die();
	}
	$db_doc = getDbObject('docutron');
	$headerArr = array(	'id'			=> 'ID',
						'username'		=> 'Username',
						'folder'		=> 'Folder',
						'filename'		=> 'Filename',
						'date_deleted'	=> 'Date Deleted',
						'type'			=> 'Type' );	

	if(isSet($_POST['submit']) || isSet($_GET['page']) || isSet($_POST['page'])) {
		$queryArr = array("department = '".$user->db_name."'");
		if(isset($_POST['id']) && $_POST['id'] !== '') {
			if( is_numeric( $_POST['id'] )){
				$idArr = explode(" ",trim($_POST['id']));
				for ($i = 0; $i < count($idArr); $i++) {
					$idArr[$i] = $db_doc->quote ((int)$idArr[$i], true);
				}
				$queryArr[] = "(id = ".implode(" OR id = ",$idArr).")";
			}
		}
		if(isset($_POST['username']) && $_POST['username'] !== '') {
			$usernameArr = splitOnQuote($db_doc, trim($_POST['username']),true);
			if ($usernameArr) {
				$queryArr[] = "(username " . LIKE . " ".implode(" OR username " . LIKE . " ",$usernameArr).")";
			}
		}
		if(isset($_POST['folder']) && $_POST['folder'] !== '') {
			$folderArr = splitOnQuote($db_doc, trim($_POST['folder']),true);
			if ($folderArr) {
				$queryArr[] = "(folder " . LIKE . " ".implode(" OR folder " . LIKE . " ",$folderArr).")";
			}
		}
		if(isset($_POST['filename']) && $_POST['filename'] !== '') {
			$filenameArr = splitOnQuote($db_doc, trim($_POST['filename']),true);
			if ($filenameArr) {
				$queryArr[] = "(filename " . LIKE . " ".implode(" OR filename " . LIKE . " ",$filenameArr).")";
			}
		}
		if(!empty($_POST['date_deleted']) and
			isISODate ($_POST['date_deleted'])) {

			$queryArr[] = "(date_deleted >= " . $db_doc->quote ($_POST['date_deleted']." 00:00:00") . " AND date_deleted <= " . $db_doc->quote ($_POST['date_deleted']." 23:59:59") . ")";
		}
		if(isset($_POST['type']) && $_POST['type'] !== '') {
			$typeArr = splitOnQuote($db_doc, trim($_POST['type']),true);
			if ($typeArr) {
				$queryArr[] = "(type " . LIKE . " ".implode(" OR type " . LIKE . " ",$typeArr).")";
			}
		}

		if(isSet($_POST['filelist'])) {
			$filelist = $_POST['filelist'];
			recyclebinController($_GET['action'],$filelist,$user);
		}

		$sett = new Usrsettings($user->username,$user->db_name);	
		$per_page = $sett->get('results_per_page');
		if(!$per_page) {
			$per_page = 25;
			$sett->set('results_per_page',$per_page);
		}
		
		if(isSet($_GET['page']) || isSet($_POST['page'])) {
			$queryArr = $_SESSION['inboxRecyclebin'];
			$page = (($_GET['page']) ? $_GET['page'] : $_POST['page']);
		} else {
			$_SESSION['inboxRecyclebin'] = $queryArr;
			$page = 1;
		}
		$ct = getTableInfo($db_doc,'inbox_recyclebin',array('COUNT(id)'),$queryArr,'queryOne');
		$last = ($ct) ? ceil($ct / $per_page) : 1;
		if($page <= 0) {
			$page = 1;
		} elseif($page > $last) {
			$page = $last;
		}
		$start = ($page - 1) * $per_page;
		$recbinInfo = getTableInfo($db_doc,'inbox_recyclebin',array(),$queryArr,'queryAll',array('id'=>'ASC'),$start,$per_page); 
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title>Inbox Recycle Bin</title>
    <link rel="stylesheet" type="text/css" href="../lib/style.css"/>
    <script type="text/javascript" src="../lib/settings.js"></script>
	<script>
		var sType = true;
		function viewFile(id) {
			parent.document.getElementById('rightFrame').setAttribute('rows', '*,20');
			parent.document.getElementById('mainFrameSet').setAttribute('cols', '40%,60%');

			parent.bottomFrame.location='back.php';
			parent.sideFrame.location='displayInboxRecbin.php?id=' + id;
		}

		function mOver(t) {
			t.style.backgroundColor = '#888888';
		}

		function mOut(t) {
			t.style.backgroundColor = '#ebebeb';
		}

		function changePage(p) {
			window.location = 'inboxRecyclebin.php?page='+p;
		}

		function selectAll() {
			var chk = document.getElementsByTagName('input');	
			for(var i=0;i<chk.length;i++) {
				if(chk[i].type == 'checkbox') {
					chk[i].checked = sType;
				}
			}

			if(sType) {
				sType = false;	
			} else {
				sType = true;	
			}
		}

		function submitForm() {
			var selBox = getEl('action');
			var selectValue = selBox.options[selBox.selectedIndex].value;
			document.results.action += '&action='+selectValue;
			document.results.submit();
		}
	</script>
    <style type="text/css">
		tr.inboxRes {
			background-color:#ebebeb;
			text-align:center;
		}
		tr.paging {
			text-align:center;
		}
    </style>
</head>
<body>
	<?php if(isSet($_POST['submit']) || isSet($_GET['page']) || isSet($_POST['page'])): ?>
		<div style='margin-left:auto;margin-right:auto;width:80%'>
			<form name='pageForm' style='padding:0px;margin:0px' method='post' action='inboxRecyclebin.php'>
				<table style='margin-left:auto;margin-right:auto'>
					<tr class='paging'>
						<td><img src="../energie/images/begin_button.gif" border="0" onclick='changePage(1)'></td>
						<td><img src="../energie/images/back_button.gif" border="0" onclick="changePage('<?php echo $page - 1; ?>')"></td>
						<td><input type='text' name='page' value='<?php echo $page; ?>' size='3'> of <?php echo $last; ?></td>
						<td><img src="../energie/images/next_button.gif" border="0" onclick="changePage('<?php echo $page + 1; ?>')"></td>
						<td><img src="../energie/images/end_button.gif" border="0" onclick="changePage('<?php echo $last; ?>')"></td>
					</tr>
				</table>
			</form>
		</div>
		<div style='margin-left:auto;margin-right:auto;width:80%'>
			<div>
			</div>
			<div style='text-align:right'>
				<select id='action' name='action'>
					<option value='restore'>Restore</option>
					<option value='delete'>Delete</option>
				</select>
				<input type='button' name='submit' value='Submit' onclick='submitForm()'/>
			</div>
		</div>
		<div style='margin-left:auto;margin-right:auto;width:80%'>
			<form name='results' method='POST' action='inboxRecyclebin.php?page=<?php echo $page; ?>'>
			<table width="100%"  cellpadding='0' border='0' cellspacing='1' class='results'>
				<tr class='tableheads'>
					<th><input type='checkbox' name='all' onclick='selectAll()'>All</th>
					<?php foreach($headerArr AS $h): ?> 
						<th><?php echo $h; ?></th>
					<?php endforeach; ?>
				</tr>
				<?php if(is_array($recbinInfo)): ?>
					<?php foreach($recbinInfo AS $row): ?> 
						<tr class='inboxRes' 
							onmouseover="mOver(this)"
							onmouseout="mOut(this)">
						<td><input type='checkbox' name='filelist[]' value='<?php echo $row['id']; ?>'></td>
						<?php foreach($row AS $col => $val): ?>
							<?php if($col != 'department' && $col != 'rownumber' && $col != 'path'): ?>
								<td onclick="viewFile('<?php echo $row['id']; ?>')"><?php echo $val; ?></td>
							<?php endif; ?>
						<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr class='inboxRes'>
					<td align='left' colspan='7'>Inbox Recycle Bin is empty</td>
					</tr>
				<?php endif; ?>
			</table>
			</form>
		</div>
	<?php else: ?>
		<div class="mainDiv">
			<div class="mainTitle"><span>Inbox Recycle Bin</span></div>
			<form name='recyclebin' style='padding:0px;margin:0px' method='POST' action='inboxRecyclebin.php'>
				<div style='margin-right:auto;margin-left:auto'>
					<table width='100%'>
						<?php foreach($headerArr AS $k => $h): ?>
							<tr>
							<?php if($k == 'date_deleted'): ?>
								<td style='text-align:right'><?php echo $h."(yyyy-mm-dd)"; ?></td>
							<?php else: ?>
								<td style='text-align:right'><?php echo $h; ?></td>
							<?php endif; ?>
								<td><input type='text' name='<?php echo $k; ?>' size='25'></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
				<div style='text-align:right'>
					<span><input type='submit' name='submit' value='Search'></span>
				</div>
			</form>
		</div>
	<?php endif; ?>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
