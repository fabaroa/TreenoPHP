<?php
include_once 'node.inc.php';

class workingNode extends node
{
	function workingNode($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID = NULL)
	{
		node::node($db_object, $department, $uname, $wf_document_id, $state_wf_def_id, $cab, $cabDisplayName, $doc_id, $db_doc, $fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Document Signatures";
		$this->subject = "This document needs signing in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Please sign this document\n".$this->fileLink;
	} 
	///////////////////////////////////////////
	//this will send node to prev node and remove all signatures 
	function reject($notes=NULL) {
		if( $this->checkCurrentNode() == $this->state_wf_def_id ) {
			$notifyUser = '';
			$this->deleteFromTodo(1);
			$this->lockWorkflow();
			if($notes) {
				$this->addToWFHistory('rejected',$notes);
			} else {
				$this->addToWFHistory('rejected');
			}
			
			//delete all entries from the signature table that was
			//previously signed before rejection for that document
			list($wf_node_id, $state) = array_values(getCurrentWFNodeInfo($this->db, $this->wf_document_id));
			$whereArr = array('wf_document_id'=>$this->wf_document_id,'wf_node_id'=>$wf_node_id);
			$wfHistoryInfo = getTableInfo($this->db,'wf_history',array('id'),$whereArr);
			while( $result = $wfHistoryInfo->fetchRow() ) {
				$histID = $result['id'];
				//this is the function that actually deletes the signature entries
				deleteTableInfo($this->db,'signatures',array('wf_history_id'=>(int)$histID));
			}
			
			$prev = getTableInfo($this->db,'wf_defs',array('prev'),array('id'=>(int)$this->state_wf_def_id),'queryOne');
			//$prev == 0 when rejecting to previous node, not selected reject
			//node
			if ($prev == 0) {
				$prev = getNodeFromHistory($this->db, $this->wf_document_id,
						$this->state_wf_def_id);
			}

			//sets the workflow state to the prev node
			$nodeType = getWFPrevNodeType( $this->db, $this->wf_document_id );
			$updateArr = array('state_wf_def_id'=>(int)$prev);
			$whereArr = array('id'=>(int)$this->wf_document_id);
			if( $nodeType == "STATE" ) {
				$updateArr['status'] = 'PAUSED';
				updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
			} else {
				updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
				$oldRows = getTableInfo($this->db, 'wf_history',array(), 
					array('wf_document_id' => (int) $this->wf_document_id),
					'queryAll', array('id' => 'DESC'), 2);
				if($oldRows and count($oldRows) == 2) {
					$nodeID = getTableInfo($this->db,'wf_documents',array('state_wf_def_id'),array('id' =>(int)$this->wf_document_id),'queryOne');
					if($nodeID == $oldRows[1]['wf_node_id']) {
						$notifyUser = $oldRows[1]['username'];
					}
				}
			}
			$nodeClass = $nodeType."Node";
			$nodeObj = new $nodeClass( $this->db, $this->department, $this->uname,
										$this->wf_document_id, $prev,
										$this->cab, $this->cabDisplayName, $this->doc_id, $this->db_doc, $this->fileID );
			$this->unlockWorkflow();
			$nodeObj->notify($notifyUser);
		}
	}
	///////////////////////////////////////////
	//this function will sign document
	function accept() {
		if( $this->checkCurrentNode() == $this->state_wf_def_id ) {
			$this->deleteFromTodo();
			$whereArr = array('wf_document_id'=>(int)$this->wf_document_id,'department'=>$this->department);
			$ct = getTableInfo($this->db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne');
			$this->lockWorkflow();

			//lockTables($this->db, array('wf_history'));
			$this->addToWFHistory('accepted');
			$histID = getTableInfo($this->db,'wf_history',array('MAX(id)'),array(),'queryOne');
			//unlocks table                                                            
			//unlockTables($this->db);
			//add entry to signature table 
			$insertArr = array( "wf_history_id"	=> (int)$histID,
								"hash"			=> $this->uname );
			$res = $this->db->extended->autoExecute('signatures',$insertArr);
			dbErr($res);
			$nodeType = getWFNodeType( $this->db, $this->wf_document_id );

			$whereArr = array('wf_document_id'=>(int)$this->wf_document_id,'department'=>$this->department);
			if( $ct == 0) {
				//gets the next node for the workflow
				$next = getTableInfo($this->db,'wf_defs',array('next'),array('id'=>(int)$this->state_wf_def_id),'queryOne');
				//$next == 0 when accepting to previous node
				if( $next == 0 ) {
					$next = getNodeFromHistory($this->db, $this->wf_document_id, $this->state_wf_def_id);
				}
				//updates the workflow node to next
				$updateArr = array('state_wf_def_id'=>(int)$next);
				$whereArr = array('id'=>(int)$this->wf_document_id);
				updateTableInfo($this->db,'wf_documents',$updateArr,$whereArr);
				//checks to see if the next node = current..if so current node is final
				//else go to the next node
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
}
include_once 'centeraNode.inc.php';
?>
