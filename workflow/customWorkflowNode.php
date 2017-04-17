<?php
include_once 'node.inc.php';

class customNode extends node
{
	function customNode($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID = NULL)
	{
		node::node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Choose a Value";
		$this->subject = "Please select a value for this document in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Value must be selected\nDocument Link:\n".$this->fileLink;
	} 
	
	function accept($wf_node_id) {
		if( $this->checkCurrentNode() == $this->state_wf_def_id ) {
			$this->deleteFromTodo();

			$whereArr = array('wf_document_id'=>(int)$this->wf_document_id,'department'=>$this->department);
			$ct = getTableInfo($this->db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne');
			$this->lockWorkflow();
			$this->addToWFHistory('accepted');
			if($ct == 0) {
				$uArr = array('state_wf_def_id'=>(int)$wf_node_id);
				$wArr = array('id'=>(int)$this->wf_document_id);
				updateTableInfo($this->db,'wf_documents',$uArr,$wArr);
				$nodeType = getWFNodeType( $this->db, $this->wf_document_id );
				$nodeClass = $nodeType."Node";
				$nodeObj = new $nodeClass( $this->db, $this->department, $this->uname, 
										$this->wf_document_id, $wf_node_id, 
										$this->cab, $this->cabDisplayName, $this->doc_id, $this->db_doc, $this->fileID );
				$this->unlockWorkflow();
				$nodeObj->notify();
			} else {
				$this->unlockWorkflow();
			}
		}
	}	
	
	function notify($notifyUser = '', $needLock = true) {
		global $DEFS;

		node::notify($notifyUser,$needLock);
		if(isSet($DEFS['CNODE_LIB'])) {
			require_once $DEFS['CNODE_LIB'];
			$wfOwner = getWFOwner($this->db, $this->wf_document_id);
			customWorkflowNode($this->department,$this->cab,$this->doc_id,$this->fileID,$this->wf_document_id,$this->wf_def_id,$wfOwner);
		} 
	}
	
	function getExtraAction()
	{
		$valueNodes = array();
		$valueNodes = getWFValueNodes( $this->db, $this->wf_document_id );
		$checked = 0;
		foreach( $valueNodes AS $valueNodeArr )
		{
			echo "<tr>\n";
			echo "<td>\n";
			echo "<input type=\"radio\" ";
			if( $checked === 0 ) {
				echo " checked=\"checked\" ";
			}
			echo "name=\"valueNode\" value=\"".$valueNodeArr['id']."\">\n";
			echo "</td>\n";
			echo "<td>\n";
			echo $valueNodeArr['message'];
			echo "</td>\n";
			echo "</tr>\n";
			$checked++;
		}
	}
}
?>
