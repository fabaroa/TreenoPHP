<?php
include_once '../check_login.php';
include_once '../settings/settings.php';
include_once '../lib/license.php';
include_once '../version.php';

if( $logged_in == 1 && strcmp( $user->username, "" )!=0) {
    
    //variables that may need to be translated
    $tableTitle_5        = $trans['User Status'];
    $spaceAllowed        = $trans['Space Allowed'];
    $spaceUsed           = $trans['Space Used'];
    $percentUsed         = $trans['Percent Used'];
    $usersAllowed        = $trans['Users Allowed'];
    $usersRegistered     = $trans['Users Registered'];
    $usersRemaining      = $trans['Users Remaining'];

	$whereArr = array('real_department'=>$user->db_name);
	$usr_max = getTableInfo($db_doc,'licenses',array('max'),$whereArr,'queryOne');
	if($usr_max == -1) {
		$usr_max = getTableInfo($db_doc,'global_licenses',array('max_licenses'),array(),'queryOne');
		$whereArr = array();
	} else {
		$whereArr = array('department'=>$user->db_name);
	}
	$usersOnline = getTableInfo($db_doc,'user_session',array('username'),$whereArr,'queryCol');
	$usr_cur = sizeof($usersOnline);
    $usr_rem = $usr_max - $usr_cur;

	$gblStt = new Gblstt('system',$db_doc);
	$lic = $gblStt->get('system_license');
	if($lic) {
		$licObj = new license(NULL,NULL,NULL,NULL,$lic); 
		$expDate = $licObj->expireDate;

		if($expDate == "1970-01-01") {
			$expDate = "Permanent";
		}
	} else {
		$expDate = "Expired";
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
	<title>License Information</title>
	<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
</head>
<body>
	<div class="mainDiv" style='width:350px'>
		<div class="mainTitle">
			<span>License Information</span>
		</div>
		<div style='padding:5px'>
			<fieldset>
				<legend>Status of Licenses</legend>
				<table width="100%">
					<tr>
						<td width="50%" style='text-align:right'><?php echo $usersAllowed.":"; ?></td>
						<td><?php echo $usr_max; ?></td>
					</tr>
					<tr>
						<td style='text-align:right'><?php echo $usersRegistered.":"; ?></td>
						<td><?php echo $usr_cur; ?></td>
					</tr>
					<tr>
						<td style='text-align:right'><?php echo $usersRemaining.":"; ?></td>
						<td><?php echo $usr_rem; ?></td>
					</tr>
			   </table>
			</fieldset>
		</div>
	   <div style='padding:5px;overflow:auto'>
			<fieldset>
				<legend>Users Online</legend>
				<table width="100%" style=''>
				<?php for($i=0;$i<count($usersOnline);$i+=2): ?>
					<tr>
						<?php if(isset($usersOnline[$i])): ?>
							<td style='width:45%'><?php echo $usersOnline[$i]; ?></td>
						<?php else: ?>
							<td style="width:45%">&nbsp;</td>
						<?php endif ?>
						<?php if(isset($usersOnline[$i+1])): ?>
							<td style='width:10%'>|</td>
							<td style='width:45%'><?php echo $usersOnline[$i+1]; ?></td>
						<?php else: ?>
							<td style='width:10%'></td>
							<td style='width:45%'>&nbsp;</td>
						<?php endif; ?>
					</tr>
				<?php endfor; ?>
				</table>
			</fieldset>
	   </div>
   </div>
   <div style="position:absolute;bottom:50px">
	<span>License Expiration: <?php echo $expDate?></span>
	<br/>
	<span>Version <?php echo $version ?></span>
	<br/>
	<span><?php echo $versionDate ?></span>
   </div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
