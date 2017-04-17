<?php
include_once '../check_login.php';
include_once '../lib/filter.php';

if($logged_in and $user->username) {
	$db_object = $user->getDbObject();
	
	$cab = $_GET['cab'];
	$doc_id = $_GET['doc_id'];

	$whereArr = array(	'cab'		=> $cab,
				'doc_id'	=> (int)$doc_id);

	if(isSet($_GET['file_id'])) {
		$file_id = $_GET['file_id'];
		$sArr = array('count(id)');
		$wArr = array(	"cab = '$cab'",
						'doc_id ='.(int)$doc_id,
						'file_id ='.(int)$file_id);
		$ct = getTableInfo($db_object,'wf_documents',$sArr,$wArr,'queryOne'); 
		if(!$ct) {
			$whereArr['file_id'] = -2;	
		} else {
			$whereArr['file_id'] = $_GET['file_id'];
		}
	}
	$idArr = getTableInfo($db_object,'wf_documents',array('id'),$whereArr,'queryCol');
	
	$tableArr = array('wf_history','wf_nodes');
	$selArr = array('username','date_time','notes','action','node_name','node_type');
	$whereArr = array(	'wf_document_id IN('.implode(",",$idArr).')',
						'wf_nodes.id = wf_node_id');
	$wfHistory = getTableInfo($db_object,$tableArr,$selArr,$whereArr,'queryAll',array('wf_history.id'=>'DESC'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta http-equiv="language" content="en-us" />
<title>Workflow History</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css">
</head>
<body>
	<?php if($wfHistory): ?>
	<div class="inputForm" style="padding:5px">
		<div style="overflow:auto;height:450px">
			<table>
				<tr class="tableheads">
					<th colspan="6">Document History</th>
				</tr>
				<tr class="tableheads">
					<th style="border:0px;">Username</th>
					<th style="border:0px;">Date</th>
					<th style="border:0px;">Action</th>
					<th style="border:0px;">Node Name</th>
					<th style="border:0px;">Node Type</th>
					<th style="border:0px;">Notes</th>
				</tr>
				<?php foreach($wfHistory AS $history): ?>
					<tr>
						<td><?php echo str_replace(","," ",h($history['username'])); ?></td>
						<td><?php echo $history['date_time']; ?></td>
						<td><?php echo h($history['action']); ?></td>
						<td><?php echo h($history['node_name']); ?></td>
						<td><?php echo h($history['node_type']); ?></td>
						<td><?php echo h($history['notes']); ?></td>
					</tr>
				<?php endforeach; ?>
		</table>	
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
