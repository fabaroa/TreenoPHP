<html>
<head><title>Backup A Cabinet</title></head>
<frameset rows="*">
<?php
	if (isset ($_GET['DepID'])) {
		$DepID = $_GET['DepID'];
	} else {
		$DepID = '';
	}
	if (isset ($_GET['temp_table'])) {
		$temp_table = $_GET['temp_table'];
	} else {
		$temp_table = '';
	}
	if (isset ($_GET['is_files'])) {
		$is_files = $_GET['is_files'];
	} else {
		$is_files = '';
	}
	echo<<<ENERGIE
	<frame name="process" border="0" src="processBackup.php?DepID=$DepID&temp_table=$temp_table&is_files=$is_files" noresize/>
ENERGIE;
?>
</frameset>
</html>
