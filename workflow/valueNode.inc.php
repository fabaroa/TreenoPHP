<?php
include_once 'node.inc.php';

class valueNode extends node
{
	function valueNode($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID = NULL)
	{
		node::node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Choose a Value";
		$this->subject = "Please select a value for this document in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Value must be selected\nDocument Link:\n".$this->fileLink;
	} 
	
	function accept() {
		if( $this->checkCurrentNode() == $this->state_wf_def_id ) {
			$valueID = 0;
			if(isSet($_POST['valueNode'])) {
				$valueID = $_POST['valueNode'];
			}
			$this->deleteFromTodo();

			$whereArr = array('wf_document_id'=>(int)$this->wf_document_id,'department'=>$this->department);
			$ct = getTableInfo($this->db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne');
			$this->lockWorkflow();
			$this->addToWFHistory('accepted');
			$nextValueNode = getTableInfo($this->db,'wf_value_list',array('next_node'),array('id'=>(int)$valueID),'queryOne');
			//$next == 0 when accepting to previous node
			if( $nextValueNode == 0 ) {
				$nextValueNode = getNodeFromHistory($this->db, $this->wf_document_id, $this->state_wf_def_id);
			}

			if($ct == 0) {
				$next = getTableInfo($this->db,'wf_defs',array('id'),array('node_id'=>(int)$nextValueNode), 'queryOne');
				$updateArr = array('state_wf_def_id'=>(int)$next);
				$whereArr = array('id'=>(int)$this->wf_document_id);
				updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
				$nodeType = getWFNodeType( $this->db, $this->wf_document_id );
				$nodeClass = $nodeType."Node";
				$nodeObj = new $nodeClass( $this->db, $this->department, $this->uname, 
										$this->wf_document_id, $next, 
										$this->cab, $this->cabDisplayName, $this->doc_id, $this->db_doc, $this->fileID );
				$this->unlockWorkflow();
				$nodeObj->notify();
			} else {
				$this->unlockWorkflow();
			}
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
