<?php
require_once '../lib/webServices.php';
require_once '../lib/mime.php';
require_once 'XML/Util.php';

//function to create a specified cabinet's XML tree, the filename is returned. 
function cabinetXMLTree($db_object,$cabinets,$tempTable,$isFiles, $xmlFname, $db_name = NULL, $username = NULL)
{
	global $DEFS;
	$cabArr = array();
	foreach($cabinets AS $cabID) {
		$cabArr[] ="departmentid=".(int)$cabID; 
	}
	$whereArr = array(implode(' OR ',$cabArr));
	$cabinet = getTableInfo($db_object,'departments',array(),$whereArr);

	while($results = $cabinet->fetchRow()) {
		$cabinetName = $results['real_name'];
		$cabinetTabList[$cabinetName] = getTabs($cabinetName, $db_object);

		if($username != NULL) {
			$viewableTabs = getAccessibleTabsbyUser($db_name, $username, $cabinetName);
			$cabinetTabList = removeUnaccessibleTabs($cabinetTabList, $viewableTabs);
		}

		$numFoldersInCab[$cabinetName] = 0;
		$cab2 = getFoldersForBackup($db_object, $cabinetName, $tempTable, $isFiles);
		$files_res = getCabinetSearch($db_object, $cabinetName, $tempTable, $isFiles);
		//build an array to organize the results for easy lookup

		while($files_row = $files_res->fetchRow()) {
			if($username != NULL && isTabInAccessableList($viewableTabs, $files_row['subfolder'])){
				$all_files[$files_row['doc_id']][$files_row['id']] =
					$files_row['filename']."@@@".$files_row['ordering'].
					"@@@".$files_row['subfolder'];
			}
		}
		
		while($res = $cab2->fetchRow()) {
			$doc_id = $res['doc_id'];//doc_id of folder
			$numFilesInFolder[$cabinetName][$doc_id] = 0;
			if( isset( $cabinetTabList[$cabinetName][$doc_id] ) ) {
				$tabList = $cabinetTabList[$cabinetName][$doc_id];
				for($z=0;$z<sizeof($tabList);$z++) {
					$tname = $tabList[$z];//tabname

					$numFilesInTab[$cabinetName][$doc_id][$tname] = 0;
					if (isset ($all_files[$doc_id])) {
						$file_arr = $all_files[$doc_id];
						foreach($file_arr as $id => $f) {
							$pieces=explode("@@@",$f);
							$fname = $pieces[0];
							if((($tname=="main"&&$pieces[2]=="")||
									($pieces[2]==$tname))&&($fname!="")) {
								$numFilesInTab[$cabinetName][$doc_id][$tname]++;
							}
						}
					}
					//increment folder's number of files by tab's number
					$numFilesInFolder[$cabinetName][$doc_id] += 
						$numFilesInTab[$cabinetName][$doc_id][$tname];
				}
			}
			$numFoldersInCab[$cabinetName]++;
		}
	}
	$fd = fopen($xmlFname, 'w+');
	fwrite($fd, "<?xml version=\"1.0\"?>\n");
	//die($query);
	$xmlArr = array('name' => 'client_files');
	$xmlStr = XML_Util::createStartElement('department', $xmlArr);
	fwrite($fd, $xmlStr."\n");

	$cabinet = getTableInfo($db_object,'departments',array(),$whereArr);

	$fileArr = array();
	//cabinet XML information
	while($results = $cabinet->fetchRow())
	{
		$cabinetName = $results['real_name'];

                $cab2 =  getFoldersForBackup($db_object,$cabinetName,$tempTable,$isFiles);
		$info = getCabinetInfo($db_object, $cabinetName);
		$indices = count($info); 
		$DepID = $results['departmentid'];
		if(!$isFiles || $numFoldersInCab[$cabinetName]) {
			$xmlArr = array(
				'ID'			=> $DepID,
				'indices'		=> $indices,
				'name'			=> XML_Util::replaceEntities($cabinetName),
				'num_folders'	=> $numFoldersInCab[$cabinetName]
			);
			$xmlStr = XML_Util::createStartElement('cabinet', $xmlArr);
			fwrite($fd, $xmlStr."\n");
			$printedCab = true;
		}
		//indexing name information ---------------
		$j=0;//counter
		//Prints out a node for each indice
		for($i=0;$i<$indices;$i++)
		{
			while($info[$j] == 'doc_id' || 
					$info[$j] == 'location' || $info[$j] == 'deleted')
				$j++;

			$tmp = $info[$j]; //indice name
			$xmlStr = XML_Util::createStartElement('index_name');
			$xmlStr .= XML_Util::replaceEntities($tmp);
			$xmlStr .= XML_Util::createEndElement('index_name');
			fwrite($fd, $xmlStr."\n");
			$j++;
		}
                $files_res= getCabinetSearch($db_object,$cabinetName,$tempTable,$isFiles);

		//build an array to organize the results for easy lookup
		while($files_row=$files_res->fetchRow()) {
			$all_files[$files_row['doc_id']][$files_row['id']] = 
					$files_row['filename']."@@@".$files_row['ordering'].
						"@@@".$files_row['subfolder'];
		}

		//get all of the folders
		//changed from folder 
		while($res = $cab2->fetchRow())
		{
			$var = "";
			$j = 0;//counter
			$doc_id = $res['doc_id'];//doc_id of folder
			$location = str_replace(" ","/",$res['location']);
			if(!$isFiles || $numFilesInFolder[$cabinetName][$doc_id]) {
				$xmlArr = array(
					'doc_id' => $doc_id,
					'location' => $location,
					'num_files' => $numFilesInFolder[$cabinetName][$doc_id]
				);
				$xmlStr = XML_Util::createStartElement('folder', $xmlArr);
				$printedFolder = true;
				fwrite($fd, $xmlStr."\n");
			}
			$foldername="";
			for($i=0;$i<$indices;$i++)
			{
				while($info[$j] ==  'doc_id' || 
						$info[$j] == 'location' || $info[$j] == 'deleted' )
					$j++;

				$tmp = $info[$j];//indice name
				$var = $res[$tmp];//value of indice name in the array of results

				//folder indexing information
				$foldername .= $var;
				$xmlStr = XML_Util::createStartElement('index');
				$xmlStr .= XML_Util::replaceEntities(htmlspecialchars($var));
				$xmlStr .= XML_Util::createEndElement('index');
				fwrite($fd, $xmlStr."\n");
				$j++;
			}
			if( isset( $cabinetTabList[$cabinetName][$doc_id] ) )
			{
				$tabList = $cabinetTabList[$cabinetName][$doc_id];
				for($z=0;$z<sizeof($tabList);$z++)
				{
					$tname = $tabList[$z];//tabname
					if(!$isFiles || $numFilesInTab[$cabinetName][$doc_id][$tname]) {
						$xmlArr = array(
							'name'		=> XML_Util::replaceEntities($tname),
							'location'	=> $location,
							'num_files'	=> $numFilesInTab[$cabinetName][$doc_id][$tname]
						);
						$xmlStr = XML_Util::createStartElement('tab', $xmlArr);
						fwrite($fd, $xmlStr."\n");
						$printedTab = true;
					}
					//part of array for certain folder
					if (isset ($all_files[$doc_id])) {
						$file_arr=$all_files[$doc_id];
						while($f=current($file_arr)) {

							$pieces=explode("@@@",$f);
							$fname=$pieces[0];	//filename
							$id=key($file_arr);	//file id
							//tab name subfolder
							if($tname!="main")
								$subfolder=$tname."/";
							else
								$subfolder="";
							//add file if it belongs in this tab
							if((($tname=="main"&&$pieces[2]=="") ||
									($pieces[2]==$tname))&&($fname!="")) {
								$tmp = $location;
								$tmp = $tmp.'/'.$subfolder;
								$ext = getExtension( $fname );
								$xmlArr = array(
									'location' => $tmp,
									'doc_id' => $id.'.'.$ext
								);
								$fileArr[$DEFS['DATA_DIR']."/".$tmp.$fname] = $id; 
								$xmlStr =XML_Util::createStartElement('file', $xmlArr);
								$xmlStr .= htmlspecialchars($fname);
								$xmlStr .= XML_Util::createEndElement('file');
								fwrite($fd, $xmlStr."\n");
							}
							$f=next($file_arr);	//increment array index
						}
					}
					if(isset($printedTab) and $printedTab) {
						$xmlStr = XML_Util::createEndElement('tab');
						fwrite($fd, $xmlStr."\n");
						$printedTab = false;
					}
				}
			}
			if(isset($printedFolder) and $printedFolder) {
				$xmlStr = XML_Util::createEndElement('folder');
				fwrite($fd, $xmlStr."\n");
				$printedFolder = false;
			}
		}
		if(isset($printedCab) and $printedCab) {
			$xmlStr = XML_Util::createEndElement('cabinet');
			fwrite($fd, $xmlStr."\n");
			$printedCab = false;
		}
	}
	$xmlStr = XML_Util::createEndElement('department');
	fwrite($fd, $xmlStr."\n");
	fclose($fd);
	return $fileArr;
}

/**
 * @param $db_name
 * @param $username
 * @param $cabinetName
 * @return array
 */
function getAccessibleTabsbyUser($db_name, $username, $cabinetName)
{
	$typeList = getDocumentTypeList($db_name, $cabinetName, $username);
	$viewableTabs = array();
	foreach ($typeList as $type) {
		array_push($viewableTabs, $type['arbName']);
	}
	return $viewableTabs;
}

/**
 * @param $db_name
 * @param $username
 * @param $cabinetName
 * @param $cabinetTabList
 */
function removeUnaccessibleTabs($cabinetTabList, $viewableTabs)
{
	foreach ($cabinetTabList as $cabKey => $cabinetList) {
		foreach ($cabinetList as $docKey => $docids) {
			foreach ($docids as $key => $tab) {
				if ($tab !== 'main') {
					if (isTabInAccessableList($viewableTabs, $tab) == false) {
						unset($cabinetTabList[$cabKey][$docKey][$key]);
					}
				}
			}
		}
	}
	return $cabinetTabList;
}

/**
 * @param $viewableTabs
 * @param $tab
 * @return bool
 */
function isTabInAccessableList($viewableTabs, $tab)
{
	if($tab == NULL || $tab == '') return true;
	$found = false;
	foreach ($viewableTabs as $vTab) {
		if (preg_match('/^' . str_replace(' ', '_', $vTab) . '/', $tab) == 1) {
			$found = true;
			break;
		}
	}
	return $found;
}

function getTabs($cabinetName, $db_object)
{
	$whereArr = array('deleted'=>0,'display'=>1);
	$docIDs = getTableInfo($db_object,$cabinetName."_files",array('DISTINCT(doc_id)'),$whereArr,'queryCol',array('doc_id'=>'ASC'));

	$allTabs = array();
	foreach($docIDs as $docID) {
		$allTabs[$docID][] = "main";
	}
	unset($docIDs);
	$whereArr = array('filename'=>'IS NULL');
	$tabs = getTableInfo($db_object,$cabinetName."_files",array('DISTINCT(subfolder)','doc_id'),$whereArr,'query',array('subfolder'=>'ASC'));

	while($tabList = $tabs->fetchRow())
	{
		$allTabs[$tabList['doc_id']][] = $tabList['subfolder'];
	}
	return ($allTabs);
}

?>
