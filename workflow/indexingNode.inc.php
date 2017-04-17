<?php
include_once 'node.inc.php';

class indexingNode extends node
{
	function indexingNode($db_object, $department, $uname, $wf_document_id,
			$state_wf_def_id, $cab, $doc_id, $db_doc, $fileID = NULL) {
		
		node::node($db_object, $department, $uname, $wf_document_id,
				$state_wf_def_id, $cab, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Index Folder";
		$this->subject = "This document needs to be indexed in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = $this->fileLink;
		$this->actionURL = '../workflow/indexingAction.php';
	} 
}
?>
