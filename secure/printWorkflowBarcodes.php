<?php
// $Id: printWorkflowBarcodes.php 14228 2011-01-04 16:32:49Z acavedon $

require_once '../check_login.php';

if ($logged_in and $user->username) {
	$db = $user->getDbObject ();
	$db_doc = getDbObject ('docutron');
	$DO_user = DataObject::factory ('users', $db_doc);

	$DO_user->get('username', $user->username);
	$allDepts = getTableInfo ($db_doc, 'licenses', array ('real_department',
				'arb_department'), array (), 'getAssoc', array ('arb_department'
					=> 'ASC'));
	$depts = array ();
	foreach($allDepts as $real => $fake) {
		if (isset ($DO_user->departments[$real])) {
			$depts[$real] = $fake;
		}
	}
	if (count($depts) == 1) {
		$dbName = $user->db_name;
	} else {
		$dbName = '';
	}
	$defsList =  getTableInfo ($db, 'wf_defs', array ('DISTINCT(defs_name)'),
			array (), 'queryCol', array ('defs_name'=>'ASC'));
	$numDefs = count ($defsList);
	$numCabs = count ($user->cabArr);

	//gets the usernames
	$usernames = getTableInfo($db,'access',array('username'),array(),'queryCol');
	foreach($usernames AS $uname) {
		$userArr[] = "username='$uname'";
	}

	$sArr = array('id','username');
	$wArr = array('('.implode(' OR ',$userArr).')');
	$userList = getTableInfo($db_doc,'users',$sArr,$wArr,'getAssoc');
	uasort($userList,"strnatcasecmp");

	$gblStt = new GblStt($user->db_name,$db_doc);
	$disableSecurity = $gblStt->get('wfSecurity');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Print Workflow Barcodes</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/barcode.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/behaviour.js"></script>
<script type="text/javascript">
var numDefs = '<?php echo $numDefs ?>';
var numCabs = '<?php echo $numCabs ?>';
var dbName = '<?php echo $dbName ?>';
</script>
<script type="text/javascript" src="../secure/printWorkflowBarcodes.js">
</script>
<style type="text/css">
td.input {
	width: 50%;
}
table.inputTable {
	margin: 1em 0 1em 0;
	width: 100%;
	border-collapse: separate;
}
</style>
</head>
<body class="centered">
<div class="mainDiv" style="display: none" id="mainDiv">
<div class="mainTitle">
<span>Print Workflow Barcodes</span>
</div>
<table class="inputTable">
<tr>
<td class="label">
<label>Department</label>
</td>
<td class="input">
<?php if (count ($depts) == 1): ?>
	<span id="depts"><?php echo $depts[$user->db_name] ?></span>
<?php else: ?>
	<select id="depts">
	<?php foreach ($depts as $real => $arb): ?>
		<?php if($real == $user->db_name): ?>
			<option selected="selected" value="<?php echo $real ?>">
				<?php echo $arb ?>
			</option>
		<?php else: ?>
			<option value="<?php echo $real ?>"><?php echo $arb ?></option>
		<?php endif; ?>
	<?php endforeach; ?>
	</select>
<?php endif; ?>
</td>
</tr>
<?php /* This is the section for the 'Workflow' selectBox */ ?>
<tr>
<td class="label">
<label>Workflow</label>
</td>
<td class="input">
<select id="wfDefs">
<option value="__default">Select a Workflow</option>
<?php foreach ($defsList as $myDef): ?>
	<option value="<?php echo $myDef ?>"><?php echo $myDef ?></option>
<?php endforeach; ?>
</select>
</td>
</tr>
<?php /* This is the section for the 'Cabinet' selectBox */ ?>
<tr>
<td class="label">
<label for="cabList">Cabinet</label>
</td>
<td class="input">
<select id="cabList">
<option value="__default">Select a Cabinet</option>
<?php foreach ($user->access as $myCab => $rights): ?>
	<?php if($disableSecurity): ?>
	<option value="<?php echo $myCab ?>"><?php echo $user->cabArr[$myCab] ?></option>
	<?php elseif($rights == "rw") : ?>
	<option value="<?php echo $myCab ?>"><?php echo $user->cabArr[$myCab] ?></option>
	<?php endif; ?>
<?php endforeach; ?>
</select>
</td>
</tr>
<?php /* This is the section for the 'Owner' selectBox */ ?>
<tr>
<td class="label">
<label for="userList">Owner</label>
</td>
<td class="input">
<select id="userList">
<option value="__default">Select an Owner</option>
<?php foreach ($userList as $id => $uname): ?>
	<option value="<?php echo $id ?>"><?php echo $uname ?></option>
<?php endforeach; ?>
</select>
</td>
</tr>
</table>
<div>
<button id="btnPrint">Print Barcode</button>
</div>
</div>
</body>
</html>
<?php
} else {
	logUserOut();
}
?>
