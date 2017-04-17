<?php 
include_once '../check_login.php';
include_once '../classuser.inc';

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$mess = '';
	$full_filename = ''; 
	$cab = $_GET['cab'];
	$elID = '';
	if(isSet($_GET['fileID'])) {
		$file_id = $_GET['fileID'];

		$sArr = array('doc_id','subfolder','parent_filename','parent_id');
		$wArr = array('id' => (int)$file_id);
		$fInfo = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryRow');
		$doc_id 	= $fInfo['doc_id'];
		$tab 		= $fInfo['subfolder'];
		$filename 	= $fInfo['parent_filename'];
		$parent_id 	= $fInfo['parent_id'];

		$URL = "editName2.php?cab=$cab&fileID=$file_id";
	} else {
		$doc_id = $_GET['doc_id'];
		$tab = $_GET['tab'];
		$filename = $_GET['filename'];
		$sArr = array('id','parent_id');
		$wArr = array('doc_id' => (int)$doc_id,
					'parent_filename' => $filename);
		if($tab) {
			$wArr['subfolder'] = $tab; 
		} else {
			$wArr['subfolder'] = 'IS NULL'; 
		}
		$fInfo = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryRow');
		$parent_id = $fInfo['parent_id'];
		$file_id = $fInfo['id'];

		if($tab) {
			$elID = "s-$doc_id:$tab:".$_GET['count'];
		} else {
			$elID = "s-$doc_id:main:".$_GET['count'];
		}
		$URL = "editName2.php?cab=$cab&doc_id=$doc_id&tab=$tab&filename=$filename&count=".$_GET['count'];
	}

	$fileInfo = pathinfo($filename);
	$ext = $fileInfo['extension'];
	$fname = basename($filename,".".$ext);
	$flag = false;
	if(isset($_POST['editName'])) {
		$newFilename = $_POST['editName'];
		if($fname != $newFilename) {
			$sArr = array('DISTINCT(parent_filename)');
			$wArr = array(	'doc_id'	=> (int)$doc_id,
							'subfolder'	=> (($tab) ? $tab : 'IS NULL'));
			$filenameList = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryCol');
		
			$status = $user->invalidCharacter($newFilename, '.');
			if($status === true) {
				$mess = "Invalid characters typed";	
			} elseif(!in_array($newFilename.".".$ext,$filenameList)) {
				if ($parent_id) {
						$wArr = array('id='.(int)$file_id.' OR parent_id='.(int)$parent_id);
				} else {
						$wArr = array('id='.(int)$file_id);
				}
				$uArr = array('parent_filename' => $newFilename.".".$ext); 
				updateTableInfo($db_object,$cab.'_files',$uArr,$wArr);

				$fname = $newFilename;
				$full_filename = $newFilename.".".$ext;
				if(strlen($full_filename) > 20) {
					$filename = substr($full_filename,0,17)."...";
				} else {
					$filename = $full_filename;
				}
				$flag = true;
				$mess = "Filename successfully changed";
				$URL = "editName2.php?cab=$cab&doc_id=$doc_id&tab=$tab&filename=$filename";
				if(isSet($_GET['count'])) {
					$URL .= "&count=".$_GET['count'];
				}
			} else {
				$mess = "Filename already exists";
			}
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Edit Filename</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/settings.js"></script>
<script>
	function changeFilename() {
		var f = parent.sideFrame.getEl('filename-<?php echo $file_id; ?>');
		if(f) {
			clearDiv(f);
			var t = parent.sideFrame.document.createTextNode('<?php echo $filename; ?>');
			f.appendChild(t);

			var title = parent.sideFrame.getEl('id-<?php echo $file_id; ?>');
			title.title = '<?php echo $full_filename; ?>';
		} else if(parent.sideFrame.getEl('<?php echo $elID; ?>')) {
			parent.sideFrame.getEl('A:<?php echo $elID; ?>').title = '<?php echo $filename; ?>';

			clearDiv(parent.sideFrame.getEl('A:<?php echo $elID; ?>'));
			var sp = parent.sideFrame.document.createElement('span');
			sp = parent.sideFrame.document.createTextNode('<?php echo $filename; ?>');
			parent.sideFrame.getEl('A:<?php echo $elID; ?>').appendChild(sp);
			parent.sideFrame.newOnclick('<?php echo $elID; ?>','<?php echo $URL; ?>');
		}

	}

	function cancelEdit() {
		parent.topMenuFrame.getEl('up').click();	
	}
	<?php if($flag): ?>
	changeFilename();
	<?php endif; ?>
</script>
</head>
<body>
	<div class="mainDiv">
		<div class="mainTitle">
		 <span>Edit Filename</span>
		</div>
		<form name="editNameForm"
			style="padding:0px" 
			method="POST" 
			action="<?php echo $URL; ?>" >
			<div style='padding:3px'>
				<input type='text' name='editName' value='<?php echo $fname; ?>' /> 
			</div>
			<div style='padding:3px'>
				<input type='button' name='B1' value='Submit' onclick="document.editNameForm.submit()"/>
				<input type='button' name='B2' value='Cancel' onclick="cancelEdit()" />
			</div>
		</form>
		<?php if($mess): ?>
		<div class='error'><?php echo $mess; ?></div>
		<?php endif; ?>
	</div>
</body>
</html>
<?
} else {
	logUserOut();
}
?>
