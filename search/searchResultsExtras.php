<?php
include_once '../modules/modules.php';
include_once '../lib/settings.php';
include_once '../lib/tabFuncs.php';

function printParentFolder($numberOfIndices, $topTerms, $cab, $user, $delFolders, $security_level ) {
	$db_doc = getDbObject('docutron');
   	//variables that may need to be translated
   	global $trans; 
   	$pFolder            = $trans['Parent Folder'];

	echo "<!-- This section is for table row entry for parent folder -->\n";
 	echo "  <TR id=\"parentFolder\" onmouseover=\"rowMouseover('parentFolder')\"";
  	if ($topTerms != "")
    	echo "onclick=\"window.location='topLevelSearch.php?case=2&amp;topTerms=$topTerms';top.searchPanel.removeCab()\"\n";
  	else
    	echo "onClick=\"window.location='home.php';top.searchPanel.removeCab()\"";

  	echo "onmouseout=\"rowMouseout('parentFolder')\" style=\"background-color:#ebebeb\">\n";
  	echo "   <td align=\"center\">\n";
	echo "    <img alt=\"Parent Folder\" title=\"\" src=\"../images/open_16.gif\" border=0>\n";
	echo "   </td>\n";

  	//extra column if delete folders is true
	if(check_enable("publishing",$user->db_name) && $user->checkSetting('publishFolder',$cab) && isValidLicense($db_doc)) {
		echo "   <TD></TD>\n";
	}

	if( $security_level == 2 && isValidLicense($db_doc)) {
		if ($user->checkSetting ('editFolder', $cab)) {
	    	echo "   <TD></TD>\n";
		}
		if ($delFolders) { 
			echo "   <TD></TD>\n";
		}
	}
	if($user->checkSetting('showBarcode', $cab) 
		and !$user->checkSetting('documentView', $cab)) {
        echo "   <TD></TD>\n";
	}

  	for ($x = 0; $x < $numberOfIndices; $x ++) { 
    	//prints row until no other indices
		if ($x == 0) { 
			echo "   <TD style=\"font-size:12px\"><span>$pFolder</span></TD>\n";
		} else { 
			echo "   <TD><span></span></TD>\n";
		}
  	} // end for
  	echo "  </tr>\n";
	echo "<!-- end of table row entry for parent folder -->\n\n";
}

function printCreateFolder($cab, $myfieldnames, $user, $temp_table, $delFolders, 
			$security_level, $passedTerms, $index ) {
  	global $trans;
  	$createNewFolder    = $trans['Create New Folder'];
  	$enterField         = $trans['Please enter at least one field'];

	$indiceStr = implode( ",", $myfieldnames );
	//$getStr = 'searchResults.php'.formGetStr($getArr);
	echo "<!-- This section is for table row entry for create folder -->\n";
    echo "  <TR id=\"createFolder\" onmouseover=\"rowMouseover('createFolder')\"";
    echo " onmouseout=\"rowMouseout('createFolder')\" style=\"background-color:#ebebeb\">\n";
    echo "   <td id=\"newFolder-folder\" noWrap=\"nowrap\" align=\"center\"";
    echo " onClick=\"openCreateNewFolder('$cab','$index','$passedTerms',$delFolders)\">";
	echo "    <img alt=\"Create Folder\" title=\"\" ";
	echo "src=\"../energie/images/new_folder.gif\" border=\"0\">\n";
	echo "   </td>\n";

	if(check_enable("publishing",$user->db_name) && $user->checkSetting('publishFolder',$cab)) {
		echo "   <TD id=\"newFolder-publishing\"";
    	echo " onClick=\"openCreateNewFolder('$cab','$index','$passedTerms',$delFolders)\">";
		echo "</TD>\n";
	}

    if ($security_level == 2 and $user->checkSetting('editFolder', $cab)) {
        echo "   <TD id=\"newFolder-edit\"";
    	echo " onClick=\"openCreateNewFolder('$cab','$index','$passedTerms',$delFolders)\">";
		echo "</TD>\n";
	}
    //extra column if delete folders is true
    if ($security_level == 2 && $delFolders) {
        echo "   <TD id=\"newFolder-delete\"";
    	echo " onClick=\"openCreateNewFolder('$cab','$index','$passedTerms',$delFolders)\">";
		echo "</TD>\n";
	}
	if($user->checkSetting('showBarcode', $cab) 
		and !$user->checkSetting('documentView', $cab)) {
        echo "   <TD id=\"newFolder-barcode\"";
    	echo " onClick=\"openCreateNewFolder('$cab','$index','$passedTerms',$delFolders)\">";
		echo "</TD>\n";
	}
   
	for ($x = 0; $x < sizeof($myfieldnames); $x ++) {
      	//prints row until no other indices
      	if ($x == 0) {
        	echo "   <td style=\"font-size:12px\" id=\"newFolder-$myfieldnames[$x]\" noWrap=\"nowrap\"";
    		echo " onClick=\"openCreateNewFolder('$cab','$index','$passedTerms',";
			echo "$delFolders)\"><span>$createNewFolder</span></td>\n";
		} else { 
        	echo "   <TD id=\"newFolder-$myfieldnames[$x]\"";
    		echo " onClick=\"openCreateNewFolder('$cab','$index','$passedTerms',";
			echo "$delFolders)\">";
			echo "<span></span></TD>\n";
		}
    } // end for
    echo "  </TR>\n";
	echo "<!-- end of table row entry for create folder -->\n\n";
} // end function

function createArrows($index,$rowCount,$getArr,$type) {
    global $trans;
    $of          = $trans['of'];
	$getArr['index'] = 0;
	$getStr = 'searchResults.php'.formGetStr($getArr);
	if( $type == "top" )
		$position = "top:-20px;";
	else
		$position = "top:10px;";

	if( $rowCount > 0 )
		$visible = "visibility:visible;height:30px;";
	else
		$visible = "visibility:hidden;";
	echo "\n<!--This section is for searchResults paging scheme -->";
	echo "\n<div id=\"table-$type\" style=\"position:relative;$position margin-left: auto; ";
	echo "margin-right: auto; text-align: center; width:33%; $visible\">\n"; 
    echo " <form style=\"margin-bottom: 0px;\" name=\"pageForm\" method=\"post\" action=\"$getStr\">\n";
    echo " <table style=\"margin-left: auto; margin-right:auto;\">\n";
    echo "  <tr>\n";
    echo "   <td class=\"arrows\" onclick=\"navArrowsBegin('$getStr')\">\n";
	echo "    <img style=\"vertical-align:middle\" src=\"images/begin_button.gif\">\n";
	echo "   </td>\n";
	if(($index - 1) < 0) 
		$getArr['index'] = 0;
	else
		$getArr['index'] = $index - 1;
	
	$getStr = 'searchResults.php'.formGetStr($getArr);

    echo "   <td class=\"arrows\" onclick=\"navArrowsDown('$getStr')\">\n";
	echo "    <img style=\"vertical-align:middle\" src=\"images/back_button.gif\">\n";
	echo "   </td>\n";
    echo "   <td style=\"white-space: nowrap\" class=\"lnk_black\">\n";
    echo "     <input name=\"indexID\" value=\"";
    echo $index +1;
    echo "\" type=\"text\" onkeypress=\"return allowDigi(event)\" size=\"3\">\n";
	echo "     <span style=\"text-align:center\" id=\"$type-pageCount\">".$of."\t \t".($rowCount+1)."</span>\n";
    echo "   </td>\n";
	if(($index + 1) > $rowCount) {
		$getArr['index'] = $rowCount;
	} else {
		$getArr['index'] = $index + 1;
	}
	$getStr = 'searchResults.php'.formGetStr($getArr);
    echo "   <td class=\"arrows\" onclick=\"navArrowsUp('$getStr')\">\n";
	echo "    <img style=\"vertical-align:middle\" src=\"images/next_button.gif\">\n";
	echo "   </td>\n";
	$getArr['index'] = $rowCount;
	$getStr = 'searchResults.php'.formGetStr($getArr);
    echo "   <td class=\"arrows\" onclick=\"navArrowsEnd('$getStr')\">\n";
	echo "    <img style=\"vertical-align:middle\" src=\"images/end_button.gif\">\n";
	echo "   </td>\n";
	echo "  </tr>\n";
	echo " </table>\n";
    echo " </form>\n";
	echo "</div>\n";
	echo "<!-- end of searchResults paging scheme -->\n\n"; 
}

function printButtons( $user, $glbSettings, $userSettings, $security_level, $cd_permission, $entries, $getArr ) {
	$csvRestrict_ro = $glbSettings->get('csvRestrict');
    if(!$csvRestrict_ro)
        $csvRestrict_ro = 'off';

	$csvRestrict_user = $userSettings->get('csvRestrict');
    if(!$csvRestrict_user)
        $csvRestrict_user = 'off';

	$isoRestrict_ro = $glbSettings->get('isoRestrict');
    if(!$isoRestrict_ro)
        $isoRestrict_ro = 'off';

	$isoRestrict_user = $userSettings->get('isoRestrict');
    if(!$isoRestrict_user)
        $isoRestrict_user = 'off';

	$bookmarkRestrict_user = $userSettings->get('bookmarkRestrict');
    if(!$bookmarkRestrict_user)
        $bookmarkRestrict_user = 'off';

	$bookmarkRestrict_ro = $glbSettings->get('bookmarkRestrict');
   if(!$bookmarkRestrict_ro)
        $bookmarkRestrict_ro = 'off';

	if( $entries == 0 ) {
		$hidden = "visibility:hidden";
	} else {
		$hidden = '';
	}

	$URL = '../search/searchResultsAction.php'.formGetStr($getArr);
	echo "<div id=\"searchButtons\" style=\"position:absolute;top:40px;right:1.5em;$hidden\">\n";
	
	if(!check_enable("lite",$user->db_name)) {
		if(!$user->restore) {
			if($bookmarkRestrict_user != "on") {
				if(($bookmarkRestrict_ro =="on" && $security_level == 2) || $bookmarkRestrict_ro =="off") {
					echo "<span id=\"booknamespot\">\n";
					echo "<img id=\"bookimg\" style=\"cursor:pointer\" onclick=\"bookmarkswitch('$URL&bookmarkSearch=1')\" ";
					echo "title=\"Bookmark This Search\" src=\"../images/paste_16.gif\">\n";
					echo "</span>&nbsp;&nbsp;\n";
				}
			} 
		}
	}
	if($csvRestrict_user != "on") {
		if(($csvRestrict_ro =="on" && $security_level == 2) || $csvRestrict_ro =="off") {
   			echo "<img style=\"cursor:pointer\" onclick=\"submitAction('$URL','export_csv')\" ";
			echo "title=\"Export Results\" src=\"../images/chart_16.gif\">\n";
		}
	}

	if(!check_enable("lite",$user->db_name)) {
		if($isoRestrict_user != "on") {
			if(($isoRestrict_ro =="on" && $security_level == 2) || $isoRestrict_ro =="off") {
						echo "<img style=\"cursor:pointer\" onclick=\"submitAction('$URL','burn')\" ";
				echo "title=\"Make CD of Results\" src=\"../images/cd_16.gif\">\n";
			}
		}
	}
	// cz
	if(check_enable("eSign",$user->db_name) && ($security_level == 2)) 
	{
		//cz 2011-03-02
		?>
	
		<script type="text/javascript">
			//<![CDATA[
		
			function QueryDocuSignStatus() {
				//alert("conditionally inFile QueryDocuSignStatus() called.");
			    parent.mainFrame.window.location = '../docuSign/docuSignStatus.php?cab=' + arguments[0];
				
				parent.document.getElementById('mainFrameSet').setAttribute('cols', '100%,*');
				parent.document.getElementById('folderViewSet').setAttribute('rows','100%,*');
				//parent.viewFileActions.window.location = '../energie/bottom_white.php';
				parent.sideFrame.window.location = '../energie/left_blue_search.php';
			}
			
			// ]]>
		</script>
		<?php
		
		$myCab = $getArr['cab'];
		if($user->checkSetting('docuSign', $myCab))
		{
			echo "<img style=\"cursor:pointer\" onclick=\"QueryDocuSignStatus('$myCab')\" ";
			echo "title=\"DocuSign Status\" src=\"../images/DocuSign.gif\">\n";
		}
	}
	echo "</div>\n";
}
//---------------------------------------------------
function getIndex($db_object, $index, $cabinet, $rowCount, $tempTable, $user, 
	$sortType, $sortDir, $getArr, $resultsPerPage ) {
	global $trans;

	$nowViewing 	= $trans['Now Viewing'];       
	$perPage 		= $trans['per page'];      
	$results 		= $trans['Results'];
	$resFound       = $trans['Results Found'];

	$start = ($index * $resultsPerPage);
	$end = ($index * $resultsPerPage) + $resultsPerPage + 1;
	$tmp = $rowCount - ($index * $resultsPerPage);

	$start1 = $start + 1;
	if($tmp < $resultsPerPage) 
		$end1 = $tmp + ($index * $resultsPerPage);
	else
		$end1 = $end - 1;

	if($rowCount > 0)
		$visible = "visibility:visible;";
	else
		$visible = "visibility:hidden;";
		
 	echo " <div class=\"lnk_black\" style=\"$visible position:absolute;left:5px\">\n";
	echo "  <b id=\"nowViewing\">$nowViewing: $start1 - $end1</b>\n";
	echo " </div>\n";
 	echo " <div class=\"lnk_black\" style=\"position:absolute;right:1.5em\">\n";
	echo "  <form name=\"results_form\" style=\"margin-bottom:0px\">\n";
	echo "   <select name=\"results\" onchange=\"changeResPerPage()\">\n";
	echo "    <option selected value=\"$resultsPerPage\">$resultsPerPage $results</option>\n";
	$allResPerPage = array(10, 25, 50, 75, 100);
	$resLoc = array_search($resultsPerPage, $allResPerPage);
	array_splice($allResPerPage, $resLoc, 1);
	$getStrOrig = 'searchResults.php';
	foreach($allResPerPage as $eachRes) {
		$getArr['res'] = $eachRes;
		$getStr = $getStrOrig.formGetStr($getArr);
		echo "    <option value=\"$getStr\">$eachRes $results</option>\n";
	}
	echo "   </select>\n";
	echo "  </form>\n";
	echo " </div>\n";
	echo "<div id=\"resultsFound\" class=\"lnk_black\" "; 
	echo "style=\"position:relative;top:25px;font-weight:bold;width:33%;\n";
    if( $rowCount == 0 ) {
        echo "color:#990000\">There were no results found.";
		if($user->checkSecurity($cabinet) == 2 && isset($_GET['hasACResult']) && $_GET['hasACResult'] == 1) {
			echo " A folder would have been created if you had " .
				"Write Permissions to this cabinet.";
		}
    } else {
        echo "\">".$rowCount.$resFound;
	}
    echo "</div>\n";
	
	if($sortType) {
		$orderArr = array("$sortType" => "$sortDir");
	} else {
		$orderArr = array('doc_id' => 'DESC');
	}
	$result = getTableInfo($db_object, array($cabinet, $tempTable), array(), 
		array("$cabinet.doc_id = $tempTable.result_id", 'deleted = 0'), 'query', 
		$orderArr, $start, $resultsPerPage);

	return $result;
}
//---------------------------------------
function printCabinetIndices( $indices, $fieldnames, $cab,$user,$sort,$getArr,$delFolders, $security_level) {
	//these may have  to be translated
    global $trans;
    $folderLabel      = $trans['Folder'];
    $editLabel        = $trans['Edit'];
    $deleteLabel      = $trans['Delete'];

	$db_doc = getDbObject('docutron');
	echo "<!-- this section is for table row entry of cabinet indices -->\n";
	echo "  <tr class=\"tableheads\">\n"; //c4cae4
    echo "   <td style=\"width:3%;text-align:center\">\n";
	echo "    <img title=\"$folderLabel\" src=\"images/File.gif\" border=\"0\">\n";
	echo "   </td>\n";
	if(check_enable("publishing",$user->db_name) && $user->checkSetting('publishFolder',$cab) && isValidLicense($db_doc)) {
?>
		<td style="width:3%;text-align:center">
			<img src="../images/new_16.gif" 
				border="0"  
				title="Add publishing"
				onclick="top.topMenuFrame.addItem('<?php echo $cab; ?>','')" />
		</td>
<?php
	}
    if ( $security_level == 2 && isValidLicense($db_doc)) {
		if($user->checkSetting('editFolder', $cab)) {
			echo "   <td style=\"width:3%;text-align:center\">\n";
			echo "    <img title=\"$editLabel\" src=\"images/file_edit_16.gif\" border=\"0\" width=\"14\">\n";
			echo "   </td>\n";
		}
      	if ($delFolders) {
        	echo "   <td style=\"width:3%;text-align:center\">\n";
			echo "    <img title=\"$deleteLabel\" src=\"images/trash.gif\" border=\"0\" width=\"14\">\n";
			echo "   </td>";
		}
	}
	if($user->checkSetting('showBarcode', $cab) 
		and !$user->checkSetting('documentView', $cab)) {
		echo "   <td style=\"width:3%;text-align:center\" id=\"barcodeHeader\">\n";
		echo "    <img title=\"Barcode\" src=\"../images/barcode.gif\" border=\"0\">\n";
		echo "   </td>\n";
	}

    for ($i = 0; $i < $indices; $i ++) {
        //Displays fieldnames
        $realName = $fieldnames[$i];
		$dispName = editNames($realName);
		$getArr['sortType'] = $realName;
		$getArr['sortDir'] = $sort[$realName];
		$getStr = 'searchResults.php'.formGetStr($getArr); 
		echo "   <td style=\"white-space:nowrap\" onmouseover=\"showSort('".$dispName."','{$sort[$realName]}')\" ";
		echo "onmouseout=\"removeSort()\" onclick=\"inOrder('$getStr');\">";
		echo "<span>".$dispName."</span></td>\n";
    } //end of for loop
	echo "  </tr>\n";
	echo "<!-- end of table row entry of cabinet indices -->\n\n";
}

//function that creates a csv file from a table of file search results
function createCSVFiles ($user, $cab, $temp_table) {
	global $DEFS;

	$db_object=$user->getDbObject();
	$uname=$user->username;

	$settings=new Usrsettings($user->username, $user->db_name );
	if($settings->get('context' )=="update")
		$settings->set('context','stop');	//stop the poll page
	
	if(!file_exists("{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup")) {
		mkdir("{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup",0777);
	} else {
		if (file_exists("{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchresults.xls"))
			unlink("{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchresults.xls");
	}

	$ft=$cab."_files";
	// db specific 
	if(getdbType() == 'mysql' or getdbType() == 'mysqli') {
		$query="SELECT $cab.*,$ft.id,$ft.parent_filename,$ft.subfolder,$ft.ordering,
				$ft.date_created,$ft.date_to_delete,$ft.who_indexed,$ft.access,$ft.notes 
				INTO OUTFILE '{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchData.xls' 
				FIELDS TERMINATED BY '"."\\t"."' ESCAPED BY '".addslashes("\\")."' 
				LINES TERMINATED BY '"."\\n"."' STARTING BY '' FROM $cab,$cab"."_files,$temp_table 
				WHERE $cab"."_files.id=$temp_table.result_id AND $ft.doc_id=$cab.doc_id";
		//echo "<P>DBG: mysql query ($query).</P>";
	} elseif(getdbType() == 'pgsql') {
		$query="COPY (SELECT $cab.*,$ft.id,$ft.parent_filename,$ft.subfolder,$ft.ordering,
				$ft.date_created,$ft.date_to_delete,$ft.who_indexed,$ft.access,$ft.notes 
				FROM $cab,$cab"."_files WHERE $ft.doc_id=$cab.doc_id) 
				TO '{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchData.xls' NULL ''";
		//echo "<P>DBG: pqsql query ($query).</P>";
	} elseif(getdbType() == 'mssql') {
//		OSQL is another choice, but both are CLI only, which doesn't help us here...
// 1
//		$query   = "BCP (SELECT $cab.*,$ft.id,$ft.parent_filename,$ft.subfolder,$ft.ordering,
//					$ft.date_created,$ft.date_to_delete,$ft.who_indexed,$ft.access,$ft.notes 
//					FROM $cab,$cab"."_files WHERE $ft.doc_id=$cab.doc_id) 
//					QUERYOUT '{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchData.xls' -c -T";
// 2
//		$query   = "osql -U $uid -P $pass -d $??? -q $select -o $outfile";
// 3
//		$uid     = $DEFS['DB_USER'];
//		$pass    = $DEFS['DB_PASS'];
//		$select  = "SELECT $cab.*,$ft.id,$ft.parent_filename,$ft.subfolder,$ft.ordering,
//					$ft.date_created,$ft.date_to_delete,$ft.who_indexed,$ft.access,$ft.notes 
//					FROM $cab,$cab"."_files WHERE $ft.doc_id=$cab.doc_id";
//		$outfile = "'{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchData.xls'";
		//echo "<P>DBG: pqsql query ($query).</P>";
		echo "<P>mssql not currently supported for this operation</P>";
		return -1;
	}
	
	$res=$db_object->query($query);
	//if there is an error, die
	dbErr($res);

	$info = getTableColumnInfo ($db_object, $cab);
	$head_str = "";
	foreach($info as $info_row) {
		$head_str.=$info_row."\t";
	}

	//add file entries to column headings
	$head_str.="id\tfilename\tsubfolder\tordering\tdate_created\tdate_to_delete\twho_indexed\taccess\tnotes\n";

	//create files with headers
	$fp=fopen("{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchHeadings.xls","w");
	fwrite($fp,$head_str);
	fclose($fp);

    //combine files
	$concatArr = array (
			escapeshellarg("{$DEFS['DATA_DIR']}/$user->db_name/$uname" .
				"_backup/searchHeadings.xls"),
			escapeshellarg("{$DEFS['DATA_DIR']}/$user->db_name/$uname" . 
				"_backup/searchData.xls")
			);
	$destFile = escapeshellarg("{$DEFS['DATA_DIR']}/$user->db_name/$uname" .
			"_backup/searchResults.xls");
	if (substr (PHP_OS, 0, 3) == 'WIN') {
		$myStr = str_replace ('/', '\\', implode ('+', $concatArr));
		$cmd = "copy /b " . $myStr . ' ' . $destFile;
	} else {
		$cmd = "cat " . implode (' ', $concatArr) . ' > ' . $destFile;
	}
	shell_exec($cmd);

	unlink("{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchHeadings.xls");
	unlink("{$DEFS['DATA_DIR']}/$user->db_name/$uname"."_backup/searchData.xls");

    $user->audit("Exported search results as file", "Cabinet: $cab");

	//use mime function to allow user to download this file from a hidden frame
	echo "<script type=\"text/javascript\">\n top.leftFrame1.window.location='/search/sendCSV.php';\n</script>\n";
}

function formGetStr($getArr) {
	$getStr = "";
	$tempArr = array();
	foreach($getArr as $key => $value) {
		$tempArr[] = "$key=$value";
	}
	$getStr = implode('&amp;', $tempArr);
	if($getStr) {
		$getStr = '?'.$getStr;
	}
	return $getStr;
}
?>
