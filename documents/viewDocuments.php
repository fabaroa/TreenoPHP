<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/fileFuncs.php';
include_once '../lib/mime.php';
include_once '../modules/modules.php';
include_once '../settings/settings.php';
include_once 'documentDispFuncs.php';

if($logged_in == 1 && strcmp($user->username,"") != 0 && $user->checkSecurity($_GET['cab']) != 0) {
	$gblStt = new GblStt($user->db_name, $db_doc);
	$_SESSION['allThumbsURL'] = getRequestURI (); 
	$_SESSION['thumbnailArr'] = array();
	
	$fromDocuSignButton = isset($_SESSION['docuSignView'])? $_SESSION['docuSignView']: 0;
	
	$cab		= $_GET['cab'];//cabinet name
	$doc_id		= $_GET['doc_id'];//folder id
	$message	= "";
	
	if(isSet($_GET['tab_id'])) {
		$tab_id	= $_GET['tab_id'];//file id of the tab
	} else {
		$tab = $_GET['tab'];
		$sArr = array('id');
		$wArr = array(	'doc_id'	=> (int)$doc_id,
						'subfolder'	=> $tab,
						'filename'	=> 'IS NULL');
		$tab_id = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryOne');
	}
	if (isset ($_GET['table'])) {
		$temp_table = $_GET['table'];//temp table with the search results
	} else {
		$temp_table = '';
	}
	if (isset ($_GET['index'])) {
		$index		= $_GET['index'];//page of the search results
	} else {
		$index = '';
	}
	if (isset ($_GET['viewing'])) {
		$viewing = $_GET['viewing'];//scroll location of the div currentFiles
	} else {
		$viewing = '';
	}

	$file_id = 0;
	if(isSet($_GET['file_id'])) {
		$file_id = $_GET['file_id'];
	}

	$sArr = array('departmentid');
	$wArr = array('real_name' => $cab);
	$cabID = getTableInfo($db_object,'departments',$sArr,$wArr,'queryOne');

	$sArr = array('location');
	$wArr = array('doc_id' => (int)$doc_id);
	$loc = getTableInfo($db_object,$cab,$sArr,$wArr,'queryOne');
	$file_loc = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);

	$folderName = implode(" ",getCabIndexArr($doc_id,$cab,$db_object));

	$cabPath = $DEFS['DATA_DIR'].'/'.$user->db_name.'/'.$cab;
	$cabThumbPath = $DEFS['DATA_DIR'].'/'.$user->db_name.'/thumbs/'.$cab;
	$thumb_loc = str_replace($cabPath,$cabThumbPath,$file_loc); 
	
	//to display files in document view
	$selectArr = array(	'subfolder',
					'id',
					'filename',
					'parent_filename',
					'date_created',
					'who_indexed',
					'notes',
					'ca_hash',
					'file_size');
	if($tab_id != -1) {
		$sArr = array('document_table_name','subfolder','deleted');
		$wArr = array('id' => (int)$tab_id);
		$docInfo = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryRow');
		$docTable = $docInfo['document_table_name'];
		$tab = $docInfo['subfolder'];

		//if(($fromDocuSignButton == 1))// && (getTableInfo($db_object, 'pg_tables', array('COUNT(*)'), array('tablename'=>strtolower($cab).'_dsfiles'), 'queryOne') > 0)) 
		if(($fromDocuSignButton == 1) && (getTableInfo($db_object, 'information_schema.tables', array('COUNT(*)'), array('table_name'=>strtolower($cab).'_dsfiles'), 'queryOne') > 0)) 
		{
			error_log("fromDocuSignButton: ".$fromDocuSignButton);	
			$query = "SELECT t12.*, status, tmCreate FROM (SELECT t1.*, envID FROM ";
			$query .= "(SELECT ".implode(",",$selectArr)." FROM ".$cab."_files " ;
			$query .= "WHERE doc_id=$doc_id AND filename IS NOT NULL AND ";
			$query .= "display=1 AND deleted=0 AND subfolder='$tab' ORDER BY ordering ASC) AS t1 ";
			$query .= "LEFT OUTER JOIN {$cab}_dsfiles AS t2 ON t1.id=t2.origfileid) AS t12 ";
			$query .= "LEFT OUTER JOIN {$cab}_envelopes AS t3 ON t12.envid=t3.envid";	
	
		      // error_log("Query: ".$query);
		}
		else
		{
			$query = "SELECT ".implode(",",$selectArr)." FROM ".$cab."_files ";
			$query .= "WHERE doc_id=$doc_id AND filename IS NOT NULL AND ";
			$query .= "display=1 AND deleted=0 AND subfolder='$tab' ORDER BY ordering ASC";		
		}

		$fileArr = $db_object->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, true, true);
		
		/*$wArr = array(	'subfolder'	=> $tab,
						'filename'	=> 'IS NOT NULL',
						'doc_id'	=> (int)$doc_id,
						'display'	=> 1,
						'deleted'	=> 0 );*/		
		//$oArr = array('ordering' => 'ASC');
		//$fileArr = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryAll',$oArr);

		$sArr = array('document_type_name');
		$wArr = array('document_table_name' => $docTable);
		$subfolder = getTableInfo($db_object,'document_type_defs',$sArr,$wArr,'queryOne'); 
		if(!$subfolder) {
			$subfolder = $tab;
		}
		$tabArr = array($tab);
		$message = "Document contains no files";
	} else {
		$tabArr = array('');
		$tab = "";
		$subfolder = "main";
		$wArr = array(	'doc_id'	=> (int)$doc_id,
						'subfolder'	=> 'IS NULL',
						'display'	=> 1,
						'deleted'	=> 0 );	
		//$oArr = array('ordering' => 'ASC');
		$query = "SELECT ".implode(",",$selectArr)." FROM ".$cab."_files ";
		$query .= "WHERE doc_id=$doc_id AND subfolder IS NULL AND display=1 AND deleted=0 order by ordering ASC";
		$fileArr = $db_object->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT, true, true);
		dbErr ($fileArr);
		//$fileArr = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'getAssoc',$oArr);
		/*
		$sArr = array('DISTINCT(subfolder)');
		$wArr = array('doc_id' => (int)$doc_id);
		$oArr = array('subfolder' => 'ASC');
		$tabArr = getTableInfo($db_object,$cab.'_files',$sArr,$wArr,'queryCol',$oArr);
		*/
		$message = "Document contains no files";
	}

	$userStt = new Usrsettings($user->username,$user->db_name);
	$order = $userStt->get('order');
	if($order == "") {
		$order = $gblStt->get('order');
		if($order == "") {
			$order = 1;
			$userStt->set('order','1');
		}
	}
	$count = 0;

	$frameWidth = 250;
	if($gblStt->get('frame_width')) {
		$frameWidth = $gblStt->get('frame_width');
	}
	$turnOnZipPasswd = $gblStt->get('passwordZipFiles');
	if( $turnOnZipPasswd =="1" ){
		$turnOnZipPasswd = "true";
	}else{
		$turnOnZipPasswd = "false";
	}
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>View Document</title>
<script type="text/javascript" src="documents.js"></script>
<script type="text/javascript" src="viewDocuments.js"></script>
<script type="text/javascript" src="scriptaculous/lib/prototype.js"></script>
<script type="text/javascript" src="scriptaculous/src/scriptaculous.js"></script>
<script type="text/javascript" src="scriptaculous/src/slider.js"></script>
<script type="text/javascript" src="../lib/barcode.js"></script>
<script type="text/javascript" src="../lib/behaviour.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript" src="../lib/windowTitle.js"></script>
<script type="text/javascript">
	<?php 
		$folderName = str_replace("\n", "", $folderName);
	        $folderName = str_replace("\r", "", $folderName);
        	$folderName = str_replace(";", "", $folderName);
	        $folderName = str_replace("'", "", $folderName);
	?>

	parent.document.getElementById('mainFrameSet').setAttribute('cols', '*,'+<?php echo $frameWidth;?>);
	setTitle(3,'<?php echo $user->cabArr[$cab]; ?>','<?php echo h($folderName); ?>');
	var cab = "<?php echo $cab; ?>";
	var doc_id = "<?php echo $doc_id; ?>";
	var tab_id = "<?php echo $tab_id; ?>";
	var table = "<?php echo $temp_table; ?>";
	var index = "<?php echo $index; ?>";
	var scrollLoc = "<?php echo $viewing; ?>";
	var boolSelect = true;
	var selectedFile = "";
	var buttonPress = false;
	var startID = "";
	var modified = false;
	var fID = <?php echo $file_id; ?>;
	var createPDF = 0;
	var boolBC = false;
	var turnOnZipPassword = <?php echo $turnOnZipPasswd ?>
	

	Behaviour.register(myrules);
	Behaviour.addLoadEvent(function() {
							setTimeout('showThumbs(0)',1000);
							adjustHeight();
							window.onresize = adjustHeight;
							scrollLocation();
							openSearchFile();
							if(typeof adjustFilenames == 'function') {
								adjustFilenames();
							}
	});

	function openSearchFile() {
		if(fID) {
			viewFile(fID,getEl('id-'+fID));
		}
	}

	function scrollLocation() {
		document.getElementById('currentFiles').scrollTop = scrollLoc;
	}

	function adjustHeight() {
		var clienth = document.documentElement.clientHeight;
		var outh = getEl('outDiv').offsetHeight;

		var d = getEl('currentFiles');
		var inh = d.offsetHeight;

		var newh = 0;
		if(clienth > outh ) {
			newh = inh + (clienth - outh) - 25;
		} else {
			newh = inh - (outh - clienth) - 25;
		}

		if(newh < 0) {
			newh = 0;
		}
		d.style.height = newh+'px';
	}

	document.onkeydown = enableKeyPress;
	document.onkeyup = disableKeyPress;
</script>
<link rel="stylesheet" type="text/css" href="../lib/style.css" />
<style>
	ul {
		padding: 1px;
		margin: 0px;
		overflow-y: scroll;
		overflow-x: auto;
	}

	ul.regular li {
		text-align: left;
		list-style-type: none;
		padding-left: 2px;
		padding-bottom: 2px;
		cursor: pointer;
		vertical-align: middle;
	}

	ul.reorder li { 
		text-align: left;
		list-style-type: none;
		padding-left: 2px;
		cursor: move;
		vertical-align: middle;
	}

	li.mouseover {
		background-color: #6A78AF;
	}

	li.mouseout {
		background-color: #003B6F;
	}

	fieldset {
		padding: 2px;
	}

	legend {
		font-size: 14px;
		color: #FFFFFF;
	}

	body {
		height			: 100%;
		background-color: #003b6f;
		color			: #FFFFFF;
		margin-right	: 5px;
		margin-left		: 5px;
		margin-top		: 2px;
	}
</style>
</head>
<body>
	<div id="outDiv">
		<?php if($user->checkSetting('sliderBar',$cab)) printSliderBar($frameWidth); ?>
		<?php if(isValidLicense($db_doc)) printExportRedactInput($cab,$user); ?>
		<?php if(isValidLicense($db_doc)) displayToolBar(); ?>
		<?php if(isValidLicense($db_doc)) displayBarcode($subfolder,$cab,$user); ?> 
		<?php if(isValidLicense($db_doc)): ?>
		<?php if($tab): ?>
			<?php $mactBool = displayActions($cab,$doc_id,$tab_id,$tab,$order,$user,$db_doc, $db_object); ?> 
		<?php else: ?>
			<?php $mactBool = displayActions($cab,$doc_id,$tab_id,'main',$order,$user,$db_doc, $db_object); ?> 
		<?php endif; ?>
		<?php $sactBool = displaySingleFileActions($cab,$cabID,$user); ?> 
		<?php endif; ?>
		<?php
			if(isset ($fileArr[$tab]) && count($fileArr[$tab]) > 1) {
				displayPagingButtons(sizeof($fileArr[$tab]));
			}
		?>
		
		<div class="subDiv" id="subfolderContainer" style="display:block">
		<?php foreach($tabArr AS $sfold): ?>
			<div id="div-<?php echo $sfold; ?>">
				<fieldset id="fieldset-<?php echo $sfold; ?>">
					<legend>
						<input type="checkbox" 
							id="subfolder-<?php echo $sfold; ?>"
							style="vertical-align:50%"
							name="<?php echo $subfolder; ?>" 
							onclick="toggleSubfolder('<?php echo $sfold; ?>')" />
						<img src="../images/folder.gif" 
							title="Subfolder" 
							alt="Subfolder"
						/>
						<span style="vertical-align:75%"><?php echo " ".$subfolder; ?></span>
					</legend>
					<ul id="currentFiles" class="regular" style="height:0px;padding:1%;overflow-y:scroll">
					<?php if(isset ($fileArr[$sfold]) && sizeof($fileArr[$sfold]) > 0): ?>
						<?php foreach($fileArr[$sfold] AS $k => $fArr): ?>
							<?php $fArr['parent_filename'] = ($fArr['parent_filename']) 
									? $fArr['parent_filename'] 
									: $fArr['filename'] ?>
							<li id="id-<?php echo $fArr['id']; ?>"
								title="<?php echo $fArr['parent_filename']; ?>"
								style="width:85%">
								<table width="100%" cellpadding="0" cellspacing="0">
									<?php ($fromDocuSignButton) 
											? displayDSFileInfo($cab,$fArr)
											: (($order)?displayThumbnailView($cab,$fArr,$count,$file_loc,$thumb_loc,$sfold):displayFileInfo($cab,$fArr));
											$_SESSION['docuSignView'] = 0;  ?>
											
									<!-- ?php ($order) 
											? displayThumbnailView($cab,$fArr,$count,$file_loc,$thumb_loc,$sfold)
											: null ?-->
									<?php ($user->checkSetting('showFilename',$cab)) 
											? displayFilenameView($cab,$fArr,$k,$order) 
											: displayPagedView($cab,$fArr,$k,$order) ?> 
									<!-- ?php ($order) 
											? null 
											: displayDSFileInfo($cab,$fArr) ?-->
								</table>
							</li>
						<?php endforeach; ?>
					<?php else: ?>
						<li class="empty">
							<span style="font-size:12px"><?php echo $message; ?></span>
						</li>
					<?php endif; ?>
					</ul>
				</fieldset>
			</div>
			<?php endforeach; ?>
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
