<?php
// $Id: energiefuncs.php 14704 2012-02-21 16:45:33Z acavedon $
include_once '../lib/filename.php';
include_once '../modules/modules.php';
include_once '../lib/versioning.php';
include_once '../lib/tabFuncs.php';
include_once '../lib/quota.php';
include_once '../groups/groups.php';
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../lib/mime.php';
include_once '../lib/licenseFuncs.php';
/***************************************************************************/
function listFiles( $tab, $path )
{
	$listfiles = array();
  if(strcmp($tab, "main") != 0)
    $path .= $tab."/";
  $handle = opendir( $path );
	while(false !== ($file = readdir($handle))) {
    if(is_file($path."//".$file))
			$listfiles[] = $tab."@@~~".$file;
  }
return( $listfiles );
}
/***************************************************************************/
function checkFiles( $tab, $path )
{
  $count = 0;
  if(strcmp($tab, "main") != 0)
    $path .= $tab;
	
  $handle = opendir( $path );
  while(false !== ($file = readdir($handle))) {
    if($file != "." && $file != "..")
      $count++;
  }
return( $count );
}
/***************************************************************************/
function queryAllTabs($db_object, $cabinet, $doc_id, $settings, $db_name, $all = true)
{
	$notShow = array();
	if(!$all) {
		//This is to show tabs based on the access list in group_tab in the
		//department database.
		//$groups = new groups($db_object);
		$notShow = getNoShowTabs($cabinet, $doc_id, $db_name);
	}
	$whereArr = array('filename'=>'IS NULL','doc_id'=>(int)$doc_id,'display'=>1,'deleted'=>0);
	$ordering = $settings->get('tab_ordering');
	$orderArr = array ();
	if($ordering) {
		$orderArr['subfolder'] = $ordering;
	} else {
		$orderArr['id'] = 'ASC';
	}
	//'id' must be here for ordering by id. - Tristan
	$tabs = getTableInfo($db_object,$cabinet."_files",array('id, subfolder'),$whereArr,'query',$orderArr);
	$allTabs = array('main');
	//gets all unique tabs that have files in them
	while($tabList = $tabs->fetchRow()) {
		$tmp = $tabList['subfolder'];	
		if($tmp and !in_array($tmp, $notShow)) {
			$allTabs[] = $tmp;
		}
	}
	return ($allTabs);
}
/***************************************************************************/
function printSelect( $db_object, $folderName, $doc_id, $allTabs, $settings, $db_name) {
		echo<<<HTML
<div style="padding: 0.25em">
	<select onchange="printTabBarcodes(this)">
		<option value="__default" selected="selected">Print Barcodes</option>
HTML;
	foreach($allTabs as $myTab) {
		$tabName = $myTab;
		$dispName = str_replace('_', ' ', $tabName);
		echo '<option value="'.$tabName.'">Print '.$dispName.' Barcode</option>';
  }
		echo '<option value="__all">Print All Barcodes</option>';
  echo<<<HTML
  	</select>
</div>
HTML;
}
/***************************************************************************/
function findJpeg($user, $starting_directory, $numberOfFiles, $cab, $doc_id, 
	$allTabs, $order, $temp_table, $index,$security,&$vArr,&$enabledArr,$cabID)
{
}
/***************************************************************************/
function displayJpeg($cab, $doc_id, $orderNum, $tab, $fileName, 
	$count, $numOfJpegs,$maxJpegs, $fileID, $all_notes, $ext, &$thumbCt)
{
	if($tab) {
		$str = "s-$doc_id:$tab:$orderNum";
		$currTab = $tab;
		echo "<tr id=\"$str\" onclick=\"setSelectedRow('$str'); ";
		echo "loadFiles('$cab',$doc_id,$orderNum,'$tab','$fileID', $count); ";
		echo "parent.mainFrame.window.location='display.php?pop=1&amp;cab=$cab";
		echo "&amp;doc_id=$doc_id&amp;ID=$orderNum&amp;tab=$tab'\" ";
		echo "onmouseover=\"javascript:style.cursor='pointer'; ";
		echo "changeColor('$str','');\" ";
		echo "onmouseout=\"resetColor('$str','')\">\n";
	} else {
		$str = "s-$doc_id:main:$orderNum";
		$currTab = 'main';
		echo "<tr id=\"$str\" onclick=\"setSelectedRow('$str'); ";
		echo "loadFiles('$cab',$doc_id,$orderNum,'$tab','$fileID', $count);parent.topMenuFrame.removeVersButton(); ";
		echo "parent.mainFrame.window.location='display.php?pop=1&amp;cab=$cab";
		echo "&amp;doc_id=$doc_id&amp;ID=$orderNum&amp;tab=$tab'\" ";
		echo "onmouseover=\"javascript:style.cursor='pointer'; ";
		echo "changeColor('$str','');\" ";
		echo "onmouseout=\"resetColor('$str','')\">\n";
	}
	echo "<td></td>\n";
	echo "<td>\n";
	if(canThumbnail($ext)) {
		if($numOfJpegs > $maxJpegs) {
			//displays this icon if there are more than 2000 tifs
			echo "<img class=\"ATimg\" src=\"../images/generic.gif\">\n";
		} 
		else {
			echo "<img class=\"ATimg\" id=\"img:$thumbCt\" alt=\"Thumbnail\" title=\"\" src=\"../images/thumb.jpg\"";
			echo "width=\"65\" height=\"80\">\n";
			echo "<div style=\"display:none\" id=\"imgInfo:$thumbCt\">";
			echo "$cab,$fileID\n";
			echo "</div>\n";
			$thumbCt++;
		}
	} else {
		putIcon( $tab, $fileName );
	}
	echo "</td>\n";
	echo "<td>\n";
	/* NO DB CALLS */
	if( $tab && $tab != "main" ) {
		$note = checknotes($doc_id, $orderNum, $tab, $all_notes);
	} else {
		$note = checknotes($doc_id, $orderNum, NULL, $all_notes);
	}
	if($note) {
		echo "<img id=\"$str:notes\" alt=\"note\" src=\"../images/note.gif\" border=\"0\">";
	} else {
		echo "<img id=\"$str:notes\" alt=\"note\" style=\"visibility: hidden\" src=\"../images/note.gif\" border=\"0\">";
	}
	echo "&nbsp;</td>\n";
			    
	echo "</tr>\n";
}

/***************************************************************************/
function displayFilename($cab, $doc_id, $e, $tab, $file, $count,$fileID, $order, $temp_table, $index, $security, $db_object,$user, $vArr, $enabledArr, $cabID, $thumbCt )
{
	global $trans;
	$fileEdit =		$trans['Edit File Name'];
	$versInfo = "Versioning";
	$save = "Save File";
	$signFile = "Sign File";
	
	$db_doc = getDbObject('docutron');
	if($tab) {
		$str = "s-$doc_id:$tab:$e";
	} else {
		$str = "s-$doc_id:main:$e";
	}
	echo "<tr id=\"filerow:$str\" onmouseover=\"changeColor('$str','');\" onmouseout=\"resetColor('$str','');\">\n";
	echo "<td style=\"width:80px\" nowrap=\"nowrap\">\n";
	//allow user to edit filename only if he has RW access
	if($user->checkSetting('editFilename',$cab) && $security && isValidLicense($db_doc)) { //check if user has rw for this cabinet, $security==2
		echo "<img id=\"editRow:$str\" class=\"smallBtn\" alt=\"$fileEdit\" title=\"$fileEdit\" ";
		echo "onclick=\"parent.topMenuFrame.removeVersButton(true);";
		$mainFrameLoc = addSlashes("editName2.php?cab=$cab&amp;doc_id=$doc_id&amp;tab=$tab&amp;filename=$file&amp;count=$e");
		echo "parent.mainFrame.window.location='$mainFrameLoc'\" ";
		echo "src=\"images/file_edit_16.gif\">\n";
	}
	
	if($user->checkSetting('versioning', $cab) and isSet($enabledArr['versioning']) &&$enabledArr['versioning'] && isValidLicense($db_doc)) {
		echo "<img alt=\"$versInfo\" title=\"$versInfo\" class=\"smallBtn\" ";
		echo "onclick=\"loadFiles('$cab', '$doc_id', '$e', '$tab',";
		echo "'$fileID', '$count');parent.topMenuFrame.removeVersButton();parent.mainFrame.location=";
		echo "'../versioning/vFHFrame.php?fileID=$fileID&amp;cabinetID=$cabID'\" ";
		echo "src=\"../images/version.gif\">\n";
	}
	
  // show up next to the file in right-side pane, classic view
	/*if($user->checkSetting('docuSign', $cab) and isSet($enabledArr['eSign']) &&$enabledArr['eSign'] && isValidLicense($db_doc)) {
		//error_log("DocuSign function is available in 'DocumentView' only.");
		echo "<img alt=\"$signFile\" title=\"$signFile\" class=\"smallBtn\" ";
		echo "onclick=\"loadFiles('$cab', '$doc_id', '$e', '$tab',";
		echo "'$fileID', '$count');parent.topMenuFrame.removeVersButton();parent.mainFrame.location=";
		echo "'../versioning/vFHFrame.php?fileID=$fileID&amp;cabinetID=$cabID'\" ";
		echo "src=\"../images/docuSign.gif\">\n";
	}*/
	
	if($user->checkSetting('saveFiles',$cab) && isValidLicense($db_doc)) {
		echo "<img alt=\"save file\" title=\"$save\" class=\"smallBtn\" ";
		echo "src=\"images/save.gif\" ";
		echo "onclick=\"parent.topFrame.window.location='display.php?pop=1&amp;cab=$cab&amp;doc_id=$doc_id&amp;";
		echo "ID=$e&amp;tab=$tab&amp;download=1'\">\n";
	}
	if(isset($enabledArr['redaction']) and $enabledArr['redaction'] and $user->checkSetting('redactFiles', $cab) && isValidLicense($db_doc)) {
		$ext = strtolower(getExtension($file));
		if($ext == 'tif' or $ext == 'tiff' or $ext == 'jpeg' or $ext == 'jpg') {
		echo <<<ENERGIE
<img 
	alt="Edit Redaction"
	title="Edit Redaction"
	class="smallBtn"
	onclick="addBackButton();setSelectedRow('$str');loadRedaction('$cab', $doc_id, $fileID, $thumbCt, '$str')"
	src="../images/generic.gif"
>

ENERGIE;
	}
	}
	echo "</td>\n";
	echo "<td style='white-space:nowrap;cursor:pointer;font-size:7pt' onclick=\"setSelectedRow('$str'); ";
	echo "loadFiles('".$cab."',".$doc_id.",".$e.",'".$tab."','".$fileID."', ".$count.");";
	echo "parent.mainFrame.window.location='display.php?pop=1&amp;cab=";
	echo "$cab&amp;doc_id=$doc_id&amp;ID=$e&amp;tab=$tab';\" ";
	echo "id=\"A:$str\" title='$file' class='lnk'>\n ";
	echo "<span id='' class='atfilename'>\n";
	if( strlen($file) < 20) {
		echo $file;
	} else {
		$tmp = substr($file,0,17)."...";
		echo $tmp;
	}
	echo "</span>\n";
	echo "</td>\n";
}
/***************************************************************************/
function createCheckbox($this_entry, $fileID, $number) {
	if(!$this_entry) {
	    $tabinfo = "main";
	} else {
	  	$tabinfo = $this_entry;
	}
	$chkID = "$tabinfo-$number";
?>
	<td style="width:25px">
		<input type="checkbox" 
			id="tab:<?php echo $chkID; ?>" 
			name="check[]" 
			value="<?php echo $fileID;?>" 
			onclick='selectCheck(this,"<?php echo $tabinfo; ?>")' />
	</td>
	</tr>
<?php
}

/****************************************************************************
 * Displays details of TIFs when images are not loaded						*
 ****************************************************************************/
function displayDetails($cab, $doc_id, $orderNum, $tab, $file,$count,$fileID,$temp_table,$index,$security,$date_created,$who_indexed, $ext, $numFiles, $all_notes )
{

	$fileType = "File Type";
	$currentPos = $count + 1; //normalize count to display to user
	//beginning of first tr
	if($tab)
		$str = "s-$doc_id:$tab:$orderNum";
	else
		$str = "s-$doc_id:main:$orderNum";

	echo "<tr style=\"color: white\" id=\"$str\" ";
	echo "onmouseover=\"javascript:style.cursor='pointer'; changeColor('$str','');\" ";
	echo "onmouseout=\"resetColor('$str','')\" >\n";
	//beginning of first td
	echo "<td>\n";
	$dispTabName = $tab;
	if(!$dispTabName) {
		$dispTabName = 'main';
	}
	if( $security && $numFiles > 1 ) //check to see if user has rw access to cabinet, $security == 2
	{
		echo<<<HTML
<input
	style="width: 20px"
	id="reorder-$dispTabName-$currentPos"
	name="reorder-$dispTabName-$fileID"
	type="text"
	value="$currentPos"
	onkeypress="return submitReorder(event)"
>
HTML;
	}
	else {
	echo "&nbsp;\n";
	}
	echo "</td>\n";
	//end of first td

	//beginning of second td
// This is the old line that matches the change just above
	echo "<td align=\"left\" ";
	echo "onclick=\"setSelectedRow('$str'); ";
	echo "loadFiles('".$cab."',".$doc_id.",".$orderNum;
	echo ",'".$tab."','".$fileID."',".$count."); ";
	echo "parent.mainFrame.window.location='display.php?pop=1";
	echo "&amp;cab=$cab&amp;doc_id=$doc_id&amp;ID=$orderNum&amp;tab=$tab'\" >";
	echo "<div style=\"font-size: 9px\">\n";
	echo "<span style=\"white-space: nowrap\">$date_created</span>\n<BR>\n";
	echo $who_indexed;
	if($ext)
		echo "<div>$fileType: $ext</div>\n";
	echo "</div>\n";
	echo "</td>\n";
	//end of second td

	$note = checknotes($doc_id, $orderNum, $tab, $all_notes);
	echo "<td>\n";
	if($note) {
		echo "<img id=\"$str:notes\" alt=\"note\" src=\"../images/note.gif\" border=\"0\">";			
	} else {
		echo "<img id=\"$str:notes\" alt=\"note\" style=\"visibility: hidden\" src=\"../images/note.gif\" border=\"0\">";
	}
	echo "&nbsp;</td>\n";
	echo "</tr>\n";
	//end of first tr
}
/***************************************************************************/
function displaybuttons($cab, $doc_id, $tab,$temp_table,$user,$security, $order, $enabledArr, $settings, $db_object, $db_doc )
{
	global $trans;
	$getPDF      = $trans['Get As PDF File'];
	$getZIP      = $trans['Get As ZIP File'];
	$delFile     = $trans['Delete File']; 
	$mvFile      = $trans['Move File'];
	$snFile		 = isset($trans['Sign File'])? $trans['Sign File'] : 'Sign File';	//cz 
	$crNewTab    = $trans['Add/Edit Tabs'];
	$_upload     = $trans['Upload'];
	$details     = "Detailed View";
	$thumbnails  = "Thumbnail View";
	$getWorkflow = "Sign Folder/Files";
	$wfHist		 = "Workflow History";

	$db_doc = getDbObject('docutron');
	$userSettings = new Usrsettings( $user->username, $user->db_name );
	//get user settings for whether or not to display the icons

	//decide if user has the priviledge to make ZIP or PDF
	$restrict = $userSettings->get('viewRestrict');
	if( !$restrict ) 
		$restrict_buttons_user=false;
	else {
		if($restrict>=1024)
			$restrict-=1024;

		if($restrict>=8)
			$restrict_buttons_user=true;
		else
			$restrict_buttons_user=false;
	}

	$settings = new GblStt( $user->db_name, $db_doc );

	//this is a check for RW users to be able to order thumbnails
	//$security true if checkSecurity($cab) == 2
	if( $user->checkSetting('changeThumbnailView', $cab)) 
		$allowOrdering = true;
	else
		$allowOrdering = false;

	$restrict = $settings->get('roViewRestrict');
	if( !$restrict )
		$restrict_buttons_ro=false;
	else {
		if($restrict>=1024)
			$restrict -=1024;
		if($user->checkSecurity($cab)==1&&$restrict>=8)
			$restrict_buttons_ro=true;
		else
			$restrict_buttons_ro=false;
	}

	if( $order )
		$type = $details;
	else
		$type = $thumbnails;

	echo "<table cellpadding=\"5\" id=\"btnTbl\" style=\"margin-top:5px;margin-bottom:5px\" ";
	echo "cellspacing=\"0\" border=\"0\">\n";
	//display if the user is not restricted to not saving
	if( !$restrict_buttons_ro && (!$restrict_buttons_user || $allowOrdering ) )
	{
		echo "<tr>\n";
		if( !$restrict_buttons_user ) {
			if($user->checkSetting('getAsPDF', $cab) && isValidLicense($db_doc)) {
				echo "<td>\n";
				echo "<img class=\"buttons\" alt=\"$getPDF\" title=\"$getPDF\" ";
				echo "src=\"../images/pdf.gif\" onclick=\"addAction('$cab','$doc_id','$tab','PDF');\">\n";
				echo "</td>\n";
			}	

			if($user->checkSetting('getAsZip', $cab) && isValidLicense($db_doc)) {
					echo "<td>\n";
					echo "<img class=\"buttons\" alt=\"$getZIP\" title=\"$getZIP\" ";
					echo "src=\"../images/zip.gif\" onclick=\"addAction('$cab','$doc_id','$tab','ZIP');\">\n";
					echo "</td>\n";
			}

			//BARCODING FOR SAGITTA
			if(isset ($enabledArr['barcoding']) and $enabledArr['barcoding'] and $user->checkSetting('showBarcode', $cab)) {
				echo "<td onclick=\"printDocutronBarcode('$cab', '$doc_id')\">\n";
				echo "<img class=\"buttons\" alt=\"print barcode\" ";
				echo "title=\"Print Barcode\" src=\"../images/barcode.gif\">";
				echo "</td>\n";
			}
		}

		if( $allowOrdering  && isValidLicense($db_doc))
		{
			echo "<td align=\"center\">\n";
			echo "<img class=\"buttons\" alt=\"show details\" title=\"$type\" ";
			echo "onclick=\"allowOrdering($order);\" ";
			echo "src=\"../images/generic.gif\" height=\"24\" width=\"24\">\n";
			echo "</td>\n";
		}

		if(isset($enabledArr['viewHistory']) && $enabledArr['viewHistory'] && ($user->checkSetting('wfIcons', $cab)) ) {
			echo "<td id=\"wfHistory\">\n";
			echo "<img class=\"buttons\" id=\"workflowHistory\" alt=\"$wfHist\" ";
			echo "title=\"$wfHist\" src=\"../images/addbk_16.gif\" ";
			echo "onclick=\"viewWorkflowHistory()\">\n";
			echo "</td>\n";
		}
			
		if( (isset($enabledArr['workflow']) && $enabledArr['workflow'] == 1) && ($user->checkSetting('wfIcons', $cab))  && isValidLicense($db_doc)) {
			echo "<td id=\"workflow1\">\n";
			echo "<img class=\"buttons\" id=\"workflow2\" alt=\"$getWorkflow\" ";
			echo "title=\"$getWorkflow\" src=\"../images/edit_24.gif\" ";
			echo "onclick=\"enterWorkflow()\">\n";
			echo "</td>\n";
		} elseif( (isset ($enabledArr['workflow']) && $enabledArr['workflow'] == 2) && ($user->checkSetting('wfIcons', $cab))  && isValidLicense($db_doc)) {
			echo "<td id=\"workflow1\">\n";
			echo "<img class=\"buttons\" id=\"workflow2\" alt=\"$getWorkflow\" ";
			echo "title=\"$getWorkflow\" src=\"../images/edit_24.gif\" ";
			echo "onclick=\"editWorkflow('$cab',$doc_id)\">\n";
			echo "</td>\n";
		} elseif( (isset ($enabledArr['workflow']) && $enabledArr['workflow'] == 3) && ($user->checkSetting('wfIcons', $cab))  && isValidLicense($db_doc)) {
			echo "<td id=\"workflow1\">\n";
			echo "<img class=\"buttons\" id=\"workflow2\" alt=\"workflow\" ";
			echo "title=\"Assign Workflow\" src=\"../images/email.gif\" ";
			echo "onclick=\"assignWorkflow('$cab',$doc_id)\">\n";
			echo "</td>\n";
		}	

		echo "</tr>\n";
	}
//$security == true if checkSecurity==2
	if($security) {
		echo "<tr>\n";
		//check whether we should be deleting or not
		if( $user->checkSetting('deleteFiles', $cab) && isValidLicense($db_doc)) {
			echo "<td style='white-space:nowrap'>\n";
			echo "<img class=\"buttons\" alt=\"$delFile\" title=\"$delFile\" ";
			echo "src=\"../images/trash.gif\" ";
			echo "onclick=\"addAction('$cab','$doc_id','$tab','DELETE');\">\n";
//ALS: no longer settable - DISABLED
//			if($user->checkSetting('deleteButtonString',$cab)){
//                echo "<span style='vertical-align:middle;cursor:pointer;font-size:12px' ".
//                    "onclick=\"addAction('$cab','$doc_id','$tab','DELETE');\"> Delete</span>";
//            }
			echo "</td>\n";
		}

		if($user->checkSetting('uploadFiles', $cab) && isValidLicense($db_doc)) {
			echo "<td align=\"center\" style='white-space:nowrap'>\n";
			echo "<img class=\"buttons\" alt=\"$_upload\" title=\"$_upload\" ";
			echo "src=\"../images/upload1.jpg\" width=\"20\" ";
			echo "onclick=\"enterUploadFile($doc_id,'$tab','$cab','$temp_table',currTab)\">\n";
//ALS: no longer settable - DISABLED
//			if($user->checkSetting('deleteButtonString',$cab)){
//                echo "<span style='vertical-align:middle;cursor:pointer;font-size:12px' ".
//                    "onclick=\"enterUploadFile($doc_id,'$tab','$cab','$temp_table',currTab)\"> Upload</span>";
//            }
			echo "</td>\n";
		}

		if($user->checkSetting('moveFiles', $cab) && isValidLicense($db_doc)) {
			echo "<td>\n";
			echo "<img class=\"buttons\" alt=\"$mvFile\" title=\"$mvFile\" ";
			echo "src=\"../images/movefiles.gif\" ";
			echo "onclick=\"enterMoveFiles2('$cab',$doc_id,'$temp_table')\">\n";
			echo "</td>\n";
		}

		//cz
		$signFiles = $user->checkSetting('docuSign', $cab);
		//error_log("Check docuSign setting for ".$cab.": ".$signFiles);	
			
    	// show up in the top of right-side pane, classic view
		if($user->checkSetting('docuSign', $cab) && isValidLicense($db_doc)) {
			error_log("DocuSign function is available in 'DocumentView' only.");
			/*echo "<td>\n";
			echo "<img class=\"buttons\" alt=\"$snFile\" title=\"$snFile\" ";
			echo "src=\"../images/DocuSign.gif\" ";
			echo "onclick=\"enterSignFiles2('$cab',$doc_id,'$temp_table')\">\n";
			echo "</td>\n";*/
		}
	

//ALS: no longer settable - DISABLED
//		if($user->checkSetting('addEditTabs', $cab) && isValidLicense($db_doc)) {
//			echo "<td>\n";
//			echo "<img class=\"buttons\" alt=\"$crNewTab\" title=\"$crNewTab\" ";
//			echo "src=\"../images/new_tab.gif\" ";
//			echo "onClick=\"enterCreateTabs($doc_id,'$tab','$cab','$temp_table')\">\n";
//			echo "</td>\n";
//		}
	}
		if($user->checkSetting('viewMode',$cab)) {
			echo "<td>\n";
			echo "<img class=\"buttons\" alt=\"Full Screen Mode\" title=\"Full Screen Mode\" ";
			echo "src=\"../images/opnbr_24.gif\" ";
			echo "onClick=\"fullScreenMode()\">\n";
			echo "</td>\n";
		}
	echo "</table>\n";
}


function printExportRedactInput() {
	echo '<input type="checkbox" id="exportNonRedact">';
	echo '<label class="lnk" for="exportNonRedact">Export Non-Redacted Files</label>';
}
/***************************************************************************/
function arrowPointer($cab,$doc_id, $ID, $tab, $file )
{
  $doc_id1 = $_GET['doc_id'];
  $cab1 = $_GET['cab'];
  $tab1 = $_GET['tab']; 
  $ID1 = $_GET['ID'];
  $current = "$doc_id1,$cab1,$tab1,$ID1";

  $new = "$doc_id,$cab,$tab,$ID";
  
  if(strcmp($current, $new) == 0)
    $count = 1;  
  else
    $count = 0;
return($count);
}
/***************************************************************************/
function checknotes( $doc_id, $ID, $tab, $all_notes)
{
	$key_str="$doc_id@@@$tab@@@$ID";
	return $all_notes[$key_str];
}
/***************************************************************************/
function putIcon( $tab, $file )
{
  $temp = pathinfo( $file );
  $ext = $temp["extension"];
  $ext = strtolower($ext);

  switch($ext){
	case 'txt':
	  //for text files
	  echo "<img class=\"ATimg\" alt=\"ascii\" src=\"../images/ascii.gif\">\n";
	  break;

	case 'doc':
	  //for word documents
	  echo "<img class=\"ATimg\" alt=\"doc\" src=\"../images/worddoc.gif\">\n";
	  break;
	
	case 'xls':
	  //for excel files
	  echo "<img class=\"ATimg\" alt=\"xls\" src=\"../images/xls.gif\">\n";
	  break;

	case 'ppt':
	  //for powerpoint files
	  echo "<img class=\"ATimg\" alt=\"ppt\" src=\"../images/ppt.gif\">\n";
	  break;

	case 'zip':
	  //for zip files
	  echo "<img class=\"ATimg\" alt=\"zip\" src=\"../images/zip_32.gif\">\n";
	  break;

	case 'gz':
	  //for gz files
	  echo "<img class=\"ATimg\" alt=\"gz\" src=\"../images/zip_32.gif\">\n";
	  break;

	case 'tar':
	  //for tar files
	  echo "<img class=\"ATimg\" alt=\"tar\" src=\"../images/zip_32.gif\">\n";
	  break;

	case 'ogg':
	  //for audio files
	  echo "<img class=\"ATimg\" alt=\"ogg\" src=\"../images/audio.gif\">\n";
	  break;

	case 'mp3':
	  //for audio files
	  echo "<img class=\"ATimg\" alt=\"mp3\" src=\"../images/audio.gif\">\n";
	  break;

	case 'wav':
	  //for audio files
	  echo "<img class=\"ATimg\" alt=\"wav\" src=\"../images/audio.gif\">\n";
	  break;

	case 'avi':
	  //for video files
	  echo "<img class=\"ATimg\" alt=\"avi\" src=\"../images/video.gif\">\n";
	  break;

	case 'mov':
	  //for video files
	  echo "<img class=\"ATimg\" alt=\"mov\" src=\"../images/video.gif\">\n";
	  break;

	case 'mpeg':
	  //for video files
	  echo "<img class=\"ATimg\" alt=\"mpeg\" src=\"../images/video.gif\">\n";
	  break;

	case 'docx':
	  //for word documents
	  echo "<img class=\"ATimg\" alt=\"doc\" src=\"../images/worddoc.gif\">\n";
	  break;
	
	case 'xlsx':
	  //for excel files
	  echo "<img class=\"ATimg\" alt=\"xls\" src=\"../images/xls.gif\">\n";
	  break;

	case 'pptx':
	  //for powerpoint files
	  echo "<img class=\"ATimg\" alt=\"ppt\" src=\"../images/ppt.gif\">\n";
	  break;

	default:
	  //for everything else
	  echo "<img class=\"ATimg\" alt=\"file\" src=\"../images/generic.gif\">\n";
	  break;
}
/*
	if($tab) {
		$currTab = $tab;
	} else {
		$currTab = 'main';
	}
*/

/*
  if($tab)
    $str = "s-$doc_id:$tab:$e:notes";
  else
    $str = "s-$doc_id:main:$e:notes";
  $note = checknotes($cab, $doc_id, $e, $tab, $all_notes);
	if($note) {
		echo "<img id=\"$str\" alt=\"note\" src=\"../images/note.gif\" border=\"0\">\n";
	} else {
		echo "<img id=\"$str\" alt=\"note\" style=\"visibility: hidden\" src=\"../images/note.gif\" border=\"0\">";		
	}
*/
}

?>
