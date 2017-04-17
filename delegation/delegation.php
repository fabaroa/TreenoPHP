<?php
include_once '../lib/delegate.php';
include_once '../lib/utility.php';
include_once '../lib/xmlObj.php';

function displayInboxDelegationRow($info,&$count,$fname=NULL) {
?>
	<tr id="delFile:<?php echo $info['file_id']; ?>"> 
		<?php if($fname == ""): ?>
		<td style="width:3%" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<img id="file-del<?php echo $count; ?>" 
				src="../images/docs_16.gif"
				alt="File"
				title="File"
				border="0"
			/>
		</td>
		<td style="width:3%">
			<img id="edit-del<?php echo $count; ?>" 
				src="../energie/images/file_edit_16.gif"
				alt="Edit Folder"
				title="Edit Folder"
				height="16"
				border="0"
				onclick="editDelegatedFile('<?php echo $count; ?>')"
			/>
		</td>
		<?php else: ?>
		<td colspan="2" style="width:6%;border-bottom:1px;border-top:1px">&nbsp;</td>
		<?php endif; ?>
		<td style="width:3%;border-bottom:1px;border-top:1px">
			<input type="checkbox" 
				id="fileCheck:<?php echo $count; ?>" 
				value="<?php echo $info['list_id']; ?>" 
				name="delFolder[]" 
				<?php if($fname != "" && (isSet($info['type']) && $info['type'] == 'file')): ?>
				disabled	
				<?php endif; ?>
			/>
		</td>
		<?php if($fname != ""): ?>
		<td style="width:81%;text-align:left;text-indent:3px;border-bottom:1px;border-top:1px" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<span id="name-del<?php echo $count; ?>"><?php echo $info['name']; ?></span>
		</td>
		<td style="width:10%;border-bottom:1px;border-top:1px">
			<span id="info-del<?php echo $count++; ?>"><?php echo isSet($info['size']) ? $info['size'] : ''; ?></span>
		</td>
		<?php else: ?>
		<td style="width:15%;text-align:left;text-indent:5px" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<span id="name-del<?php echo $count; ?>"><?php echo $info['name']; ?></span>
		</td>
		<td style="width:10%" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<span id="delegatedBy-del<?php echo $count; ?>"><?php echo $info['delegate_owner']; ?></span>
		</td>
		<td style="width:10%" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<span id="delegatedTo-del<?php echo $count; ?>"><?php echo $info['delegate_username']; ?></span>
		</td>
		<td style="width:20%" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<span id="date-del<?php echo $count; ?>"><?php echo str_replace(".000000","",$info['dtime']); ?></span>
		</td>
		<td style="width:6%" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<span id="status-del<?php echo $count; ?>"><?php echo $info['status']; ?></span>
		</td>
		<td style="width:20%" onclick="viewDelegatedFile('<?php echo $info['file_id'] ?>')">
			<span id="comments-del<?php echo $count; ?>"><?php echo $info['comments']; ?></span>
		</td>
		<td style="width:10%">
			<span id="info-del<?php echo $count++; ?>"><?php echo isSet($info['size']) ? $info['size'] : ''; ?></span>
		</td>
		<?php endif; ?>
	</tr>
<?
}

function updateDelegatedFile($en,$user) {
	global $DEFS;
	$newFilename = "";
	$newFoldername = "";
	
	$restore = 1;

	$db_dept = $user->getDbObject();
	$path = $DEFS['DATA_DIR']."/".$user->db_name."/personalInbox/"; 
	$delegateObj = new delegate($path,$user->username,$db_dept);

	$sArr = array('folder','filename','delegate_username','status','comments','delegate_owner');
	$wArr = array('inbox_delegation.list_id='.(int)$en['delegateID'],
				'inbox_delegation.list_id=inbox_delegation_file_list.list_id');
	$tArr = array('inbox_delegation','inbox_delegation_file_list');
	$fileInfo = getTableInfo($db_dept,$tArr,$sArr,$wArr,'queryRow');

	$updateArr = array();
	$wArr = array("list_id" => (int)$en['delegateID']);
	if($fileInfo['folder'] == "") {
		if($fileInfo['filename'] != $en['name']) {
			$path .= $fileInfo['delegate_owner'];
			if(!is_file($path."/".$en['name'])) {
				$updateArr['filename'] = $en['name'];
				$newFilename = $en['name'];
				$delegateObj->updateDelegateItem('inbox_delegation_file_list',$en['delegateID'],$updateArr,$wArr);
			} else {
				$text = 'File already exists';
				$restore = 0;
			}
		}
	} else {
		if($fileInfo['folder'] != $en['name']) {
			$path .= $fileInfo['delegate_owner'];
			if(!is_dir($path."/".$en['name'])) {
				$updateArr['folder'] = $en['name'];
				$newFoldername = $en['name'];
				$delegateObj->updateDelegateItem('inbox_delegation_file_list',$en['delegateID'],$updateArr,$wArr);
			} else {
				$text = 'Folder already exists';
				$restore = 0;
			}
		}
	}

	$updateArr = array();
	if($fileInfo['delegate_username'] != $en['delegatedTo']) {
		$updateArr['delegate_username'] = $en['delegatedTo'];
	}

	if($fileInfo['status'] != $en['status']) {
		$updateArr['status'] = $en['status'];
	}

	if($fileInfo['comments'] != $en['comments']) {
		$updateArr['comments'] = $en['comments'];
	}

	if($newFilename) {
		rename($path."/".$fileInfo['file'], $path."/".$en['name']);
		$text = 'Delegated file successfully updated';
	} elseif($newFoldername) {
		rename($path."/".$fileInfo['folder'], $path."/".$en['name']);
		$text = 'Delegated file successfully updated';
	}

	if($updateArr) {
		$delegateObj->updateDelegateItem('inbox_delegation',$en['delegateID'],$updateArr,$wArr);
		$text = 'Delegated file successfully updated';
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("MESSAGE",$text,array('restore' => $restore));
	$xmlObj->setHeader();
} 
?>
