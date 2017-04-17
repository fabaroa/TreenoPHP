<?php
include_once '../lib/settings.php';
include_once '../settings/settings.php';
include_once '../lib/fileFuncs.php';

class filesToDelete {
	var $container;
	var $department;
	var $cabinet;
	var $doc_id;
	var $tab;
	var $filename;
	var $fileID;
	var $db;
	var $db_doc;
	
	var $cabArr;
	var $restore;
	var $delete;
	var $auditMess;
	/******************************************************************/
	//Constructor Function to initiliaze array
	function filesToDelete( $department, $cab, $doc_id, $tab, $filename,$fileID,$restore,$delete, $db, $db_doc ) {
		$this->container = array();
		$this->cabArr = array();
		$this->department = $department;
		$this->cabinet = $cab;
		$this->doc_id = $doc_id;
		$this->tab = $tab;
		$this->filename = $filename;
		$this->fileID = $fileID;
		$this->db = $db;
		$this->db_doc = $db_doc;
		$res = getTableInfo($this->db, 'departments',
			array('real_name', 'departmentname'));
		$this->cabArr = array ();
		while ($row = $res->fetchRow()) {
			$this->cabArr[$row['real_name']] = $row['departmentname'];
		}
		if( $restore ) {
			$this->restore = 1;
		} else {
			$this->restore = 0;
		}
		
		if( $delete ) {
			$this->delete = 1;
		} else {
			$this->delete = 0;
		}
		$this->auditMess = '';
	}
	/******************************************************************/
	function getList() {
		if( $this->filename ) {
			return( $this->getFiles() );
		} else if( $this->tab ) {
			return( $this->getFiles() );
		} else if( $this->doc_id ) {
			return( $this->getTabs() );
		} else if( $this->cabinet ) {
			return( $this->getFolders() );
		} else {
			return( $this->getCabinets() );
		}
	}
	/******************************************************************/
	function getCabinets() {
		$cabinetInfo = getTableInfo($this->db, 'departments');
		while( $results = $cabinetInfo->fetchRow() ) {
			//gets the cabinet name
			$cabinet = $results['real_name'];
			$del = $results['deleted'];
			if( $results['deleted'] == 0 ) {
				$count = getTableInfo($this->db,$cabinet,array('COUNT(doc_id)'),array('deleted'=>1),'queryOne');
				if( $count > 0 ) {
					$this->container[] = $del."~".$cabinet;
				} else {
					$count = getTableInfo($this->db,$cabinet."_files",array('COUNT(doc_id)'),array('deleted'=>1),'queryOne');
					if( $count > 0 )
						$this->container[] = $del."~".$cabinet;
				}
			} else
				$this->container[] = $del."~".$cabinet;
		}
		return ( $this->container );
	} 
	/******************************************************************/
	function getFolders() {
		//gets the last ID of the previous page
		$tablesArr = $this->db->manager->listTables();
		$tablesArr = explode(",",strtolower(implode(",",$tablesArr)));
		$iscabinet = false;
		if(in_array(strtolower($this->cabinet),$tablesArr)) {
			$iscabinet = true;
		}
		if( $iscabinet) {
			$indiceNames = getCabinetInfo( $this->db, $this->cabinet );
			//gets doc_id of all folders in cabinet
			$whereArr = array('display'=>0,'deleted'=>1);
			$folderIDs = getTableInfo($this->db,$this->cabinet."_files",array('DISTINCT(doc_id)'),$whereArr,'queryCol');
			$folderInfo = getTableInfo($this->db,$this->cabinet);
			while( $results = $folderInfo->fetchRow() ) {
				//gets the doc_id of folder
				$doc_id = $results['doc_id'];
				$deleted = $results['deleted'];
				if( $deleted == 1 || in_array( $doc_id , $folderIDs ) ) {
					$folderName = "";
					for($z=0;$z<sizeof($indiceNames);$z++)
						$folderName .= $results[$indiceNames[$z]]." ";

					$this->container[] = $deleted."~".trim($folderName)."~".$doc_id;
				}
			}
			return ( $this->container );
		} else
			return( NULL );
	}
	/******************************************************************/
	function getTabs() {
		//gets a list of tabs in folder
		$whereArr = array('doc_id'=>(int)$this->doc_id,'deleted'=>1);
		$tabInfo = getTableInfo($this->db,$this->cabinet."_files",array('DISTINCT(subfolder)'),$whereArr);
		while( $results = $tabInfo->fetchRow() ) {
			$tab = $results['subfolder'];
			if( $tab == NULL ) {
				$this->container[] = "0~Main";
			} else {
				$whereArr = array('doc_id'=>(int)$this->doc_id,'subfolder'=>$tab,'filename'=>'IS NULL');
				$del = getTableInfo($this->db,$this->cabinet."_files",array('deleted'),$whereArr,'queryOne');
				$this->container[] = $del."~".$tab;
			}
		}
		return ( $this->container );
	}
	/******************************************************************/
	function getFiles() {
		//gets a list of files in tab 
		$whereArr = array(
			'doc_id'	=> (int)$this->doc_id,
			'display'	=> 0,
			'deleted'	=> 1,
			'filename'	=> 'IS NOT NULL'
		);
		if(strtolower($this->tab) != "main") {	
			$whereArr['subfolder'] = $this->tab;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$fileInfo = getTableInfo($this->db,$this->cabinet."_files",array(),$whereArr,'query',array('subfolder'=>'ASC'));
		while( $results = $fileInfo->fetchRow() ) {
//			$del = $results['deleted'];
			if( $results['parent_filename'] == NULL )
				$filename = $results['filename'];
			else
				$filename = $results['parent_filename'];
		
			$this->container[] = $results['id']."~".$filename;
		}
		return ( $this->container );
	}
	/******************************************************************/
	function getFolderName() {
		$indiceNames = getCabinetInfo( $this->db, $this->cabinet );
		$whereArr = array('doc_id'=>(int)$this->doc_id);
		$folderInfo = getTableInfo($this->db,$this->cabinet,array(),$whereArr);
		$result = $folderInfo->fetchRow();
		$folderName = '';
		for($i=0;$i<sizeof($indiceNames);$i++)
			$folderName .= $result[$indiceNames[$i]]." ";

		return( $folderName );
	}
	/******************************************************************/
	function getDBName() {
		return getTableInfo($this->db_doc, 'licenses', array('arb_department'),
			array('real_department' => $this->department),
			'queryOne', array('arb_department' => 'ASC'));
	}
	/******************************************************************/
	function displayParent() {
		if( $this->tab ) {
			$this->displayParentTab();
		} else if( $this->doc_id ) {
			$this->displayParentFolder();
		} else if( $this->cabinet ) {
			$this->displayParentCabinet();
		}
	}
	/******************************************************************/
	function displayParentCabinet() {
		$result = getTableInfo($this->db,'departments',array(),array('real_name'=>$this->cabinet),'queryRow');
		$cur_cabinet = '';
		$cur_cabinet .= "<tr class='TDresults1' onmouseover=\"this.className='TDresults2'\"";
		$cur_cabinet .= "onmouseout=\"this.className='TDresults1'\">\n";
		$cur_cabinet .= "<td align='center' width='5%'>";
		if( $result['deleted'] == 1 ) {
			$cur_cabinet .= "<img onclick=\"restoreFile('$this->cabinet','','','');\" ";
			$cur_cabinet .= "alt='Restore' src='../energie/images/save.gif' border='0'>\n";
		}
		$cur_cabinet .= "</td>\n";
		$cur_cabinet .= "<td align='center' width='5%'>";
		if( $result['deleted'] == 1 ) {
			$cur_cabinet .= "<img onclick=\"removeFile('$this->cabinet','','','');\" ";
			$cur_cabinet .= "alt='Delete' src='../energie/images/trash.gif' border='0'>\n";
		}
		$cur_cabinet .= "</td>\n";
		$cur_cabinet .= "<td onclick=\"parentFolder('$this->cabinet','','')\">";
		$cur_cabinet .= "&nbsp;&nbsp;&nbsp;&nbsp;".$this->cabArr[$this->cabinet];
		$cur_cabinet .= "</td>\n";
		$cur_cabinet .= "</tr>\n";
		echo $cur_cabinet;
	}
	/******************************************************************/
	function displayParentFolder() {
		$folderName = $this->getFolderName();
		if( trim( $folderName ) != NULL ) {
			$whereArr = array('doc_id'=>(int)$this->doc_id,'deleted'=>1);
			$folderInfo = getTableInfo($this->db,$this->cabinet,array(),$whereArr);
			$result = $folderInfo->fetchRow();
		
			echo $this->displayParentCabinet();
			$cur_folder = '';
			$cur_folder .= "<tr class='TDresults1' onmouseover=\"this.className='TDresults2'\"";
			$cur_folder .= "onmouseout=\"this.className='TDresults1'\">\n";
			$cur_folder .= "<td align='center' width='5%'>";
			if( $result['deleted'] == 1 ) {
				$cur_folder .= "<img onclick=\"restoreFile('$this->cabinet','$this->doc_id','','');\" ";
				$cur_folder .= "alt='Restore' src='../energie/images/save.gif' border='0'>\n";
			}
			$cur_folder .= "</td>\n";
			$cur_folder .= "<td align='center' width='5%'>";
			if( $result['deleted'] == 1 ) {
				$cur_folder .= "<img onclick=\"removeFile('$this->cabinet','$this->doc_id','','');\" ";
				$cur_folder .= "alt='Delete' src='../energie/images/trash.gif' border='0'>\n";
			}
			$cur_folder .= "</td>\n";
			$cur_folder .= "<td onclick=\"parentFolder('$this->cabinet','$this->doc_id','')\">";
			$cur_folder .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".h($folderName);
			$cur_folder .= "</td>\n";
			$cur_folder .= "</tr>\n";

			echo $cur_folder;
		}
		else
			echo $this->displayParentCabinet();
	}
	/******************************************************************/
	function displayParentTab() {
		$whereArr = array(
			'doc_id'	=> (int)$this->doc_id,
			'filename'	=> 'IS NULL',
			'display'	=> 0,
			'deleted'	=> 1
				 );
		if(strtolower($this->tab) != "main") {	
			$whereArr['subfolder'] = $this->tab;
		} else {
			$whereArr['subfolder'] = 'IS NULL';
		}
		$tabInfo = getTableInfo($this->db,$this->cabinet."_files",array(),$whereArr);
		$result = $tabInfo->fetchRow();
		echo $this->displayParentFolder();
		$cur_tab = '';
		$cur_tab .= "<tr class='TDresults1' onmouseover=\"this.className='TDresults2'\"";
		$cur_tab .= "onmouseout=\"this.className='TDresults1'\">\n";
		$cur_tab .= "<td align='center' width='5%'>";
		if( $result['deleted'] == 1  && $this->tab != "Main" ) {
			$cur_tab .= "<img onclick=\"restoreFile('{$this->cabinet}','{$this->doc_id}','{$this->tab}','');\" ";
			$cur_tab .= "alt='Restore' src='../energie/images/save.gif' border='0'>\n";
		}
		$cur_tab .= "</td>\n";
		$cur_tab .= "<td align='center' width='5%'>"; 
		if( $result['deleted'] == 1 && $this->tab != "Main" ) {
			$cur_tab .= "<img onclick=\"removeFile('$this->cabinet','$this->doc_id','$this->tab','');\" ";
			$cur_tab .= "alt='Delete' src='../energie/images/trash.gif' border='0'>\n";
		}
		$cur_tab .= "</td>\n";
		$cur_tab .= "<td onclick=\"parentFolder('$this->cabinet','$this->doc_id','$this->tab')\">";
		$cur_tab .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$cur_tab .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$this->tab";
		$cur_tab .= "</td>\n";
		$cur_tab .= "</tr>\n";
		
		echo $cur_tab;
	}
	/******************************************************************/
	function createArrows($index, $rowCount ) {
		$next = $index + 1;
		$prev = $index - 1;
    	echo "<table>\n";
    	echo "<tr>\n";
    	
		echo "<td align=\"center\">\n";
		echo "<a href=\"#\" onClick=\"firstpage('$this->tab','$this->doc_id','$this->cabinet');\">\n";
		echo "<img src=\"../energie/images/begin_button.gif\" border=\"0\">\n";
		echo "</a>\n";
		echo "</td>\n";

	    echo "<td></td>\n";
		echo "<td align=\"center\">\n";
		echo "<a href=\"#\" onClick=\"prevpage('$this->tab','$this->doc_id','$this->cabinet','$prev');\">\n";
		echo "<img src=\"../energie/images/back_button.gif\" border=\"0\" >\n";
		echo "</a>\n";
		echo "</td>\n";
    	
    	echo "<form name=\"pageForm\" method=\"post\" ";
		echo "action=\"recycleBin.php?cabinet=$this->cabinet&doc_id=$this->doc_id&tab=$this->tab\">\n";
		echo "<td></td>\n";
		echo "<td noWrap=\"yes\" align=\"center\" class=\"lnk_black\">\n";
    	echo "<input name=\"index\" value=\"";
    	echo $index;
    	echo "\" type=\"text\" onkeypress=\"return allowDigi(event)\" size=\"3\">\n";
		echo "<span>of";
    	echo " ";
    	echo $rowCount;
		echo "</span>\n";
    	echo "</td>\n";
		echo "</form>\n";

    	echo "<td></td>\n";
		echo "<td align=\"center\">\n";
		echo "<a  href=\"#\" onClick=\"nextpage('$this->tab','$this->doc_id','$this->cabinet','$next');\">\n";
		echo "<img src=\"../energie/images/next_button.gif\" border=\"0\">\n";
		echo "</a>\n";
		echo "</td>\n";

    	echo "<td></td>\n";
		echo "<td align=\"center\">\n";
		echo "<a href=\"#\" onClick=\"lastpage('$this->tab','$this->doc_id','$this->cabinet','$rowCount');\">\n";
		echo "<img src=\"../energie/images/end_button.gif\" border=\"0\" >\n";
		echo "</a>\n";
		echo "</td>\n";

    	echo "</tr>\n";
		echo "</table>\n";
	}
	/******************************************************************/
	function printNoResults(){
		echo "<tr class='TDresults1'>\n";
	    echo "<td></td>\n";
		echo "<td></td>\n";
						
		if( $this->tab ) {
			echo "<td class='error'>There are no files marked for deletion</td>\n";	
		} else if( $this->doc_id ) {
			echo "<td class='error'>There are no tabs in this folder</td>\n";
		} else if( $this->cabinet ) {
			echo "<td class='error'>There are no folders in this cabinet</td>\n";
		} else {
			echo "<td class='error'>There are no cabinets in this department</td>\n";
		}
		echo "</tr>\n";
	}
	/******************************************************************/
	function restore() {
		$updateArr = array();
		$whereArr = array();
		$updateArr['deleted'] = 0;
		$updateArr['display'] = 1;

		$this->createAuditMessage();
		if( $this->filename != NULL ) {
			$whereArr['id'] = (int)$this->fileID;
			updateTableInfo($this->db,$this->cabinet."_files",$updateArr,$whereArr);
			$this->filename = NULL;
			$this->fileID = NULL;
		} elseif( $this->tab != NULL ) {
			$whereArr['doc_id'] = (int)$this->doc_id;
			if( strtolower( $this->tab ) != 'main' ) {
				$whereArr['subfolder'] = $this->tab;
			} else {
				$whereArr['subfolder'] = "IS NULL";
			}
			updateTableInfo($this->db,$this->cabinet."_files",$updateArr,$whereArr);
			$this->tab = NULL;
		} elseif( $this->doc_id != NULL ) {
			$whereArr['doc_id'] = (int)$this->doc_id;

			$updateArr3 = array();
			$updateArr3['deleted'] = 0;
			$whereArr3 = array();
			$whereArr3['doc_id'] = (int)$this->doc_id;
			updateTableInfo($this->db,$this->cabinet,$updateArr3,$whereArr3);
			updateTableInfo($this->db,$this->cabinet."_files",$updateArr,$whereArr);

			$this->doc_id = NULL;
			updateTableInfo($this->db,$this->cabinet."_files",$updateArr,$whereArr);
		} else {
			$whereArr['doc_id'] = (int)$this->doc_id;

			$updateArr2 = array();
			$updateArr2['deleted'] = 0;
			$whereArr2 = array();
			$whereArr2['real_name'] = $this->cabinet;
			updateTableInfo($this->db,'departments',$updateArr2,$whereArr2);

			$updateArr3 = array();
			$updateArr3['deleted'] = 0;
			updateTableInfo($this->db,$this->cabinet,$updateArr3,array());
			updateTableInfo($this->db,$this->cabinet."_files",$updateArr,$whereArr);
			$this->cabinet = NULL;
		}
	}
	/******************************************************************/
	function deleteTabs() {
		$gblStt = new GblStt($this->department, $this->db_doc);
		if($gblStt->get($this->cabinet.'_tabs')) {
			$gblStt->removeKey($this->cabinet.'_tabs');
		}
		$this->createAuditMessage();
	}
	/******************************************************************/
	function delete() {
		global $DEFS;
		$location = $DEFS['DATA_DIR']."/";
		if( $this->filename != NULL ) {
			$whereArr = array('doc_id'=>(int)$this->doc_id);
			$loc = getTableInfo($this->db,$this->cabinet,array('location'),$whereArr,'queryOne');
			$location .= str_replace( " ", "/", $loc);
			$whereArr = array('id'=>(int)$this->fileID);
			$fileInfo = getTableInfo($this->db,$this->cabinet."_files",array('filename'),$whereArr,'queryOne');
			if( strtolower( $this->tab ) != 'main' ) {
				$location .= "/".$this->tab."/".$fileInfo;
			} else {
				$location .= "/".$fileInfo;
			}
			if( file_exists( $location ) ) {
				$fileInfo = stat( $location );
				unlink ($location);
				$myDelRedactFile = $location . '.adminRedacted';
				if (file_exists ($myDelRedactFile)) {
					unlink ($myDelRedactFile);
				}
			}

			$this->editQuota();
			$whereArr = array('id'=>(int)$this->fileID);
			$this->createAuditMessage();
			deleteTableInfo($this->db,$this->cabinet."_files",$whereArr);
			$this->filename = NULL;
			$this->fileID = NULL;
		} elseif( $this->tab != NULL ) {
			$whereArr = array('doc_id'=>(int)$this->doc_id);
			$folderInfo = getTableInfo($this->db,$this->cabinet,array(),$whereArr);
			$result = $folderInfo->fetchRow();
			$location .= str_replace( " ", "/", $result['location']);
			if( strtolower( $this->tab ) != 'main' )
				delDir($location . '/' .$this->tab);
			else {
				$delFiles = array ();
				$dh = opendir ($location);
				$myEntry = readdir ($dh);
				while ($myEntry !== false) {
					if (is_file ($location . '/' . $myEntry)) {
						$delFiles[] = $location . '/' . $myEntry;
					}
					$myEntry = readdir ($dh);
				}
				closedir ($dh);
				foreach ($delFiles as $myFile) {
					unlink ($myFile);
					$myDelRedactFile = $myFile . '.adminRedacted';
					if (file_exists ($myDelRedactFile)) {
						unlink ($myDelRedactFile);
					}
				}
			}
			$this->editQuota();
			$whereArr = array( "doc_id"	=> (int)$this->doc_id );
			if( strtolower( $this->tab ) != 'main' ){
				$whereArr['subfolder'] = $this->tab; 
			} else {
				$whereArr['subfolder'] = 'IS NULL'; 
			}
			$this->createAuditMessage();
			deleteTableInfo($this->db,$this->cabinet."_files",$whereArr);
			$this->tab = NULL;
		} elseif( $this->doc_id != NULL ) {
			$whereArr = array('doc_id'=>(int)$this->doc_id);
			$folderInfo = getTableInfo($this->db,$this->cabinet,array(),$whereArr);
			$result = $folderInfo->fetchRow();
			if( $result['location'] ) {
				$location .= str_replace( " ", "/", $result['location']);
				delDir ($location);
				$this->editQuota();
				$whereArr = array( 'doc_id'	=> (int)$this->doc_id );
				$this->createAuditMessage();
				deleteTableInfo($this->db,$this->cabinet,$whereArr);
				deleteTableInfo($this->db,$this->cabinet."_files",$whereArr);
			}
			$this->doc_id = NULL;
		} elseif( $this->cabinet != NULL ) {	
			$location .= $this->department."/".$this->cabinet;	
			$this->editQuota();
			$this->editUsers();	
			
			$sArr = array('departmentid');
			$wArr = array('real_name' => $this->cabinet);
			$cabID = getTableInfo($this->db, 'departments', $sArr, $wArr, 'queryOne');
			$whereArr = array('real_name'=>$this->cabinet,'deleted'=>1);
			deleteTableInfo($this->db,'departments',$whereArr);
			dropTable($this->db,$this->cabinet);
			dropTable($this->db,$this->cabinet."_files");
			dropTable($this->db,$this->cabinet."_indexing_table");
			dropTable($this->db,"auto_complete_".$this->cabinet);

			$index_dir = $DEFS['DATA_DIR']."/".$this->department."/indexing/".$this->cabinet;
			$thumbs_dir = $DEFS['DATA_DIR']."/".$this->department."/thumbs/".$this->cabinet."/";
			$output = `du -bsc $index_dir $thumbs_dir`;
			$tmp = explode("\n",trim($output));
			$indexSize = substr( $tmp[sizeof($tmp)-1] , 0, strpos( $tmp[sizeof($tmp)-1], "\t" ) );
			$gblStt = new GblStt($this->department, $this->db_doc);
			$gblStt->removeKey('indexing_'.$this->cabinet);
		
			//Groups	
			$whereArr = array('department'=>$this->department,'cabinet'=>$this->cabinet);
			deleteTableInfo($this->db_doc,'settings_list',$whereArr);
			deleteTableInfo($this->db, 'group_access', array('cabid' => (int) $cabID));
			deleteTableInfo($this->db, 'group_tab', array('cabinet' => $this->cabinet));
			$whereArr = array('k'=>$this->cabinet."_tabs");
			deleteTableInfo($this->db_doc,'settings', $whereArr);

			$query = "DELETE FROM settings WHERE ";
			$query .= "k " . LIKE . " 'dt,{$this->department},$cabID%' AND ";
			$query .= "department='{$this->department}'";
			$res = $this->db_doc->query( $query );
			dbErr ($res);

			$updateArr = array('quota_used'=>'quota_used-'.(int)$indexSize);
			$whereArr = array('real_department'=> $this->department);
			updateTableInfo($this->db_doc,'licenses',$updateArr,$whereArr,1);
			delDir ($location);
			delDir ($index_dir);
			if(is_dir($thumbs_dir)) {
				delDir ($thumbs_dir);
			}
			$this->createAuditMessage();
			$this->cabinet = NULL;
			$this->deleteTabs();
		}
	}
	/******************************************************************/
	function editQuota() {

		$whereArr1 = array('deleted'=>1,'display'=>0);
		if( $this->filename != NULL ) {
			$whereArr1['id'] = (int)$this->fileID;
			$file_size = getTableInfo($this->db,$this->cabinet."_files",array('file_size'),$whereArr1,'queryOne');
			if(!$file_size) {
				$file_size = 0;
			}
		} elseif( $this->tab != NULL ) {
			$whereArr1['doc_id'] = (int)$this->doc_id;
			if(strtolower($this->tab) != 'main') {
				$whereArr1['subfolder'] = $this->tab;
			} else {
				$whereArr1['subfolder'] = 'IS NULL';
			}
			$file_size = getTableInfo($this->db,$this->cabinet."_files",array('SUM(file_size)'),$whereArr1,'queryOne');
			if(!$file_size) {
				$file_size = 0;
			}
			$file_size += 4096;
		} elseif( $this->doc_id != NULL ) {
			$whereArr1['doc_id'] = (int)$this->doc_id;
			$file_size = getTableInfo($this->db,$this->cabinet."_files",array('SUM(file_size)'),$whereArr1,'queryOne');
			if(!$file_size) {
				$file_size = 0;
			}
			$whereArr = array(
				"doc_id"	=> (int)$this->doc_id,
				"filename"	=> 'IS NULL',
				"display"	=> 0,
				"deleted"	=> 1
					 );
			$count = getTableInfo($this->db,$this->cabinet."_files",array(),$whereArr,'queryOne');
			$file_size += ( $count + 1 ) * 4096;
		} else {
			$tablesArr = $this->db->manager->listTables();
        		$tablesArr = explode(",",strtolower(implode(",",$tablesArr)));
			$iscabinet = false;
        		if(in_array(strtolower($this->cabinet),$tablesArr)) {
				$iscabinet = true;
			}
            if($iscabinet) {
			$whereArr = array(
				"filename"	=> 'IS NULL',
				"display"	=> 0,
				"deleted"	=> 1
					 );
			$count = getTableInfo($this->db,$this->cabinet."_files",array(),$whereArr,'queryOne');
				$count += getTableInfo($this->db,$this->cabinet,array('COUNT(doc_id)'),array('deleted'=>1),'queryOne');
				$file_size = getTableInfo($this->db,$this->cabinet."_files",array('SUM(file_size)'),$whereArr1,'queryOne');
				if(!$file_size) {
					$file_size = 0;
				}
				$file_size += $count * 4096 ;
			} else
				$file_size = 4096;
		}
		
		if( $file_size > 0 ) {
			$updateArr = array('quota_used'=>'quota_used-'.(int)$file_size);
			$whereArr = array('real_department'=> $this->department);
			updateTableInfo($this->db_doc,'licenses',$updateArr,$whereArr,1);
		}
	}
	/******************************************************************/
	function editUsers() {
		$accessInfo = getTableInfo($this->db,'access');
		while($result = $accessInfo->fetchRow()) {
			$username = $result['username'];
			$rights = unserialize(base64_decode($result['access']));
			unset($rights[$this->cabinet]);
			$updateArr = array('access'=>base64_encode(serialize($rights)));
			$whereArr = array('username'=>$username);
			updateTableInfo($this->db,'access',$updateArr,$whereArr);
		}
	}
	/******************************************************************/
	function createAuditMessage() {
		if( $this->cabinet ) {
			$this->auditMess .= "Cabinet: ".$this->cabinet." ";
		}

		if( $this->doc_id ) {
			$this->auditMess .= "Folder: ".$this->getFolderName()." ";
		} 
		
		if( $this->tab ) {
			$this->auditMess .= "Tab: ".$this->tab." ";
		}

		if( $this->filename ) {
			$this->auditMess .= "Filename: ".$this->filename." ";
		} 
	}
	/******************************************************************/
}
?>
