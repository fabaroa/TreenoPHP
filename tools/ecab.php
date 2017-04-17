<?php 
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/synchronizeBots.php';
include_once '../lib/settings.php';

if($logged_in and $user->isSuperUser()) {
	if(isSet($_GET['start']) and $_GET['start'] == 1) {
		$startBot = true;
		$pidFile = "eCabConversion.pid";
		if(file_exists($DEFS['TMP_DIR'].'/'.$pidFile)) {
			$pid = file_get_contents($DEFS['TMP_DIR'].'/'.$pidFile);
			if (!isRunning ($pid, $DEFS)) {
				unlink ($DEFS['TMP_DIR'].'/'.$pidFile);
			} else {
				$startBot = false;
			}
		}

		if($startBot) {
			shell_exec (escapeshellarg ($DEFS['PHP_EXE']) . ' -q ' .
					escapeshellarg ($DEFS['DOC_DIR'].'/tools/ecabConversion.php ecabinet') . 
					' > /dev/null 2>&1 &');
			$mess = "conversion started";	
		} else {
			$mess = "conversion is already running";
		}

	}
?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>ECabinet Conversion</title>
<script>
	function startConversion() {
		window.location = "../tools/ecab.php?start=1";
	}
</script>
<style>
	.outerDiv {
		width			: 250px;
		margin-right	: auto;
		margin-left		: auto;
		text-align		: center;
	}
</style>
</head>
<body>
	<div class="outerDiv">
		<div>
			<span>Start ECabinet Conversion</span>
		</div>
		<div>
			<input type="button" name="Start" value="Start" onclick="startConversion()" />
		</div>
		<?php if(isSet($mess)): ?>
		<div><?php echo $mess; ?></div>
		<?php endif; ?>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
