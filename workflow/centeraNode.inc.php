<?php 
include_once 'signode.php';
include_once '../centera/centera.php';
include_once '../classuser.inc';
include_once '../modules/modules.php';

class centeraNode extends signatureNode {
	function centeraNode($db_object,$department,$uname,$wf_document_id,$state_wf_def_id,$cab,$cabDispName,$doc_id,$db_doc,$fileID = NULL) {
		signatureNode::signatureNode($db_object,$department,$uname,$wf_document_id,$state_wf_def_id,$cab,$cabDispName,$doc_id,$db_doc,$fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Document Signatures";
		$this->subject = "This document needs signing in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Document Link\n".$this->fileLink;
	}

	///////////////////////////////////////////
	//this function will sign document
	function accept() {
		if( $this->checkCurrentNode() == $this->state_wf_def_id ) {
			$this->deleteFromTodo();
			$db_doc = getDbObject('docutron');
			$whereArr = array('wf_document_id'=>(int)$this->wf_document_id,'department'=>$this->department);
			$ct = getTableInfo($db_doc,'wf_todo',array('COUNT(*)'),$whereArr,'queryOne');
			$this->lockWorkflow();

			$this->addToWFHistory('accepted');
			$histID = getTableInfo($this->db,'wf_history',array('MAX(id)'),array(),'queryOne');
			//add entry to signature table 
			$insertArr = array( "wf_history"	=> (int)$histID,
								"hash"			=> $this->uname );
			$this->db->extended->autoExecute('signatures',$insertArr);
			$nodeType = getWFNodeType( $this->db, $this->wf_document_id );

			$whereArr = array('wf_document_id'=>(int)$this->wf_document_id,'department'=>$this->department);
			if($ct == 0) {
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
			}
			$this->unlockWorkflow();

			if(check_enable('Centera',$this->department)) {
				$this->centeraFilePut();
			}
		}
	}

	function centeraFilePut() {
		global $DEFS;

		$user = new user();
		$user->username = $this->uname;
		$user->db_name = $this->department;
			
		$sArr = array('location');
		$wArr = array('doc_id' => (int)$this->doc_id);
		$loc = getTableInfo($this->db,$this->cab,$sArr,$wArr,'queryOne');
		if($this->fileID > 0) {
			$sArr = array('subfolder');
			$wArr = array('id' => (int)$this->fileID);
			$subfolder = getTableInfo($this->db,$this->cab.'_files',$sArr,$wArr,'queryOne');
			$sArr = array('id','filename','subfolder', 'ca_hash');
			$wArr = array(	'doc_id'	=> (int)$this->doc_id,
							'subfolder'	=> $subfolder,
							'deleted'	=> 0);
		} else {
			$sArr = array('id','filename','subfolder', 'ca_hash');
			$wArr = array(	'doc_id'	=> (int)$this->doc_id,
							'deleted'	=> 0);
		}
		$fileArr = getTableInfo($this->db,$this->cab.'_files',$sArr,$wArr,'queryAll');
		$path = $DEFS['DATA_DIR']."/".str_replace(" ","/",$loc);
		foreach($fileArr AS $info) {
			if (centerr ($info['ca_hash'])) {
					if($tab = $info['subfolder']) {
						$fullPath = $path."/".$tab."/".$info['filename'];
					} else {
						$fullPath = $path."/".$info['filename'];
					}
			
					$hash = centput($fullPath,$DEFS['CENT_HOST'],$user,$this->cab);
					$uArr = array('ca_hash' => $hash);
					$wArr = array('id' => (int)$info['id']);
					updateTableInfo($this->db,$this->cab.'_files',$uArr,$wArr);

			}
		}
	}
}
include_once 'centeraFidelityNode.inc.php';
?>
