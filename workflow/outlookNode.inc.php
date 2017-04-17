<?php
include_once 'node.inc.php';

class outlookNode extends node
{
	function outlookNode($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID = NULL)
	{
		global $fd;
		node::node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Outlook Integration";
		$this->subject = "This document is waiting for Outlook in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Document Link:\n".$this->fileLink;
		if( getWFStatus($this->db, $this->wf_document_id) != "PAUSED" ) {
        	$this->updateWFStatus($this->db, $this->wf_document_id, 'OUTLOOK');
		}
	}

	/*
 	*  This function will update the wf_document status
 	*/
	function updateWFStatus($db_object, $wf_document_id, $status) {
		$result = updateTableInfo($db_object, 'wf_documents', 
			array('status' => $status),
			array('id' => $wf_document_id));

		if(PEAR::isError($result))
			dbErr($result);
	} 
}
?>
