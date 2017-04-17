<?php
include_once 'node.inc.php';

class mas500Node extends node
{
	function mas500Node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID = NULL)
	{
		node::node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "MAS500 Integration";
		$this->subject = "This document is waiting for MAS500 in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Document Link:\n".$this->fileLink;
		if( getWFStatus($this->db, $this->wf_document_id) != "PAUSED" ) {
		$updateArr = array('status'=>'MAS500');
		$whereArr = array('id'=>(int)$this->wf_document_id);
		updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
		}
	} 
}
?>
