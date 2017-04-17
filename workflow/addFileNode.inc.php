<?php

include_once 'node.inc.php';

class addFileNode extends node
{
	function addFileNode($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDispName, $doc_id, $db_doc, $fileID = NULL)
	{
		node::node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDispName, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Upload/Scan In a Document";
		$this->subject = "Files need to be uploaded in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Please upload the necessary files\n".$this->fileLink;
	} 
}

?>
