<?php
function getNotes($enArr,$user,$db_doc,$db_dept) {
	$wArr = array(	"id" => (int)$enArr['fileID'] );
	$noteInfo = getTableInfo($db_dept,$enArr['cabinet']."_files",array(),$wArr,'queryRow');
	$allnotes = $noteInfo['notes'];
	
	$doc_id = $noteInfo['doc_id'];
	$subfolder = $noteInfo['subfolder'];
	$ordering = $noteInfo['ordering'];
	
	// format: ,{name,date,comment}
	$htmlID = "";
	$noteStr = "";
	if($allnotes) {
		if($user->checkSetting('documentView',$enArr['cabinet'])) {
			$htmlID = "note-".$enArr['fileID'];
		} else {
			$htmlID = "s-$doc_id:";
			if($subfolder) {
				$htmlID .= "$subfolder:$ordering:notes";
			} else {
				$htmlID .= "main:$ordering:notes";
			}
		}

		$tmp = strtok( $allnotes, "{" );
		$username = str_replace( " ", "", $tmp ); 
		while($tmp == ",") {
			$noteStr .= "-------------------\n";
			$username = strtok( "," ); 
			$time = strtok( "," );
			$note = strtok( "}" );
		
			$note = str_replace("<br>","\n",$note); 
			$noteStr .= "$username\n$time\n$note\n";
			
			$tmp = "" ;
			$tmp = strtok( "{" );
		}
		$noteStr .= "-------------------";
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","setNotes(XML)");
	if($noteStr) {
		$xmlObj->createKeyAndValue("HTML_ID",$htmlID);
		$xmlObj->createKeyAndValue("NOTE",$noteStr);
	}
	$xmlObj->setHeader();
}

function addFileNote($enArr,$user,$db_doc,$db_dept) {
	extract($enArr);

	$time = $user->getNoteTime();
	$uname = $user->username; 
	
	$wArr = array(	"display"	=> 1,
			"deleted"	=> 0 );
	$wArr["id"]	= (int)$fileID;
	$sArr = array('notes');
	$oldnotes = getTableInfo($db_dept,$cabinet."_files",$sArr,$wArr,'queryOne');
	$note = str_replace("}"," ",$newNote);
	$note = str_replace("{"," ",$note);
    	$temp = ereg_replace("(\r\n|\n|\r)", "<br>", $note); 
	if($temp != NULL){
		$newNotes = ",{".$uname.",".$time.",".$temp."}".$oldnotes;

		$uArr = array();
		$uArr['notes'] = $newNotes;
		$wArr = array('id' => (int)$fileID);
		updateTableInfo($db_dept,$cabinet."_files",$uArr,$wArr);
		$tabNfileID = "FileID: $fileID, ";
		$user->audit("added note", "Cabinet: $cabinet, $tabNfileID Note: $temp");
	}

	$xmlObj = new xml();
	$xmlObj->createKeyAndValue("FUNCTION","showNotes($fileID)");
	$xmlObj->setHeader();
}
function addNote( $doc_id, $ID, $tab, $cab, $user, $fileID, $addedNote, $db_object )
{
	$time = $user->getNoteTime();
	$uname = $user->username; 
	
	$whereArr = array(
		"display"		=> 1,
		"deleted"		=> 0
			 );
	if($fileID) { 
		$whereArr["id"]	= (int)$fileID;
	} else {
		$whereArr["doc_id"] = (int)$doc_id;
		$whereArr["ordering"]	= (int)$ID;
		if( $tab ) 
			$whereArr["subfolder"] = $tab;
		else	
			$whereArr["subfolder"] = 'IS NULL';
	}
	$check = getTableInfo($db_object,$cab."_files",array(),$whereArr);
	$Notes = $check->fetchRow();
	$oldnotes = $Notes['notes'];
	$nwNote = str_replace("}"," ",$addedNote);
	$nwNote = str_replace("{"," ",$nwNote);
    $temp = ereg_replace("(\r\n|\n|\r)", "<br>", $nwNote); 
	if($temp != NULL){
		$newNotes = ",{".$uname.",".$time.",".$temp."}".$oldnotes;

		$updateArr = array();
		$updateArr['notes'] = $newNotes;
		if($fileID) {
			$whereArr = array();
			$whereArr['id'] = (int)$fileID;
			updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr);
			$tabNfileID = "FileID: $fileID, ";
		} else {
			$whereArr = array();
			$whereArr['doc_id'] = (int)$doc_id;
 			if($tab) {
				$whereArr['subfolder'] = $tab;
			} else {
				$whereArr['subfolder'] = 'IS NULL';
			}
			$whereArr['ordering'] = (int)$ID;
			updateTableInfo($db_object,$cab."_files",$updateArr,$whereArr);
		}		
		if ($tab)
			$tabNfileID .= "Tab: $tab, ";
		$user->audit("added note", "Cabinet: $cab, $tabNfileID Note: $temp");

	}
}
?>
