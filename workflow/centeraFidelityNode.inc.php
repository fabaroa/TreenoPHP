<?php 
include_once 'signode.php';
include_once '../centera/centera.php';
include_once '../lib/settings.php';
include_once '../lib/mime.php';
include_once '../lib/xmlObj.php';
include_once '../lib/PDF.php';
include_once '../classuser.inc';

class fidelityNode extends centeraNode {
	var $transArr;
	var $returnCodes;
	var $codesArr;
	function fidelityNode($db_object,$department,$uname,$wf_document_id,$state_wf_def_id,$cab,$cabDisplayName,$doc_id,$db_doc,$fileID = NULL) {
		centeraNode::centeraNode($db_object,$department,$uname,$wf_document_id,$state_wf_def_id,$cab,$cabDisplayName,$doc_id,$db_doc,$fileID);
		$this->noActionMsg = "TODO item complete";
		$this->header = "Document Signatures";
		$this->subject = "This document needs signing in department: ".$this->depDisplayName." in cabinet: ".$this->cabDisplayName;
		$this->body = "Document Link\n".$this->fileLink;

		$this->setFormDescTrans();
		$this->setReturnCodes();
		$this->codesArr = array();
	}

	function notify() {
		global $DEFS;
		if( getWFStatus($this->db, $this->wf_document_id) != "PAUSED" ) {
			$uArr = array('status' => 'PROCESSING');
			$wArr = array('id' => (int)$this->wf_document_id);
			updateTableInfo($this->db,'wf_documents',$uArr,$wArr);

			$cmd = 'export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/Centera_SDK/lib/32/ && ';	
			$cmd .= "php -q ".$DEFS['DOC_DIR']."/bots/fidelityProcessBot.php";
			$parameters = array($this->department,
								$this->uname,
								$this->wf_document_id,
								$this->state_wf_def_id,
								$this->cab,
								$this->doc_id,
								$this->fileID );
			$cmd .= " ".implode(" ",$parameters);
			$insertArr = array(	'k'				=> 'docDaemon_execute',
								'value'			=> $cmd,
								'department'	=> $this->department);	
			$db_doc = getDbObject('docutron');
			$res = $db_doc->autoExecute('settings',$insertArr);
			dbErr($res);
		}
	}
	
	function processWorkflow() {
		global $DEFS;
		$status = getWFStatus($this->db, $this->wf_document_id); 
		$tmpPath = "";
		if($status == "PROCESSING") {
			$date = date("Y-m-d");
			$sArr = array('COUNT(id)');
			$wArr = array(	"date >= '$date 00:00:00'",
							"date < '$date 23:59:59'");
			$ct = getTableInfo($this->db,'nfs_list',$sArr,$wArr,'queryOne');
			if(!$ct) {
				$ct = 1;
			}

			/*
			$sArr = array('COUNT(id)');
			$wArr = array(	"cab" => $this->cab,
							"file_id" => $this->fileID);
			$attempts = getTableInfo($this->db,'nfs_list',$sArr,$wArr,'queryOne');
			if(!$attempts) {
				$attempts = 1;
			}
			*/

			$wArr = array('doc_id' => (int)$this->doc_id);
			$folderInfo = getTableInfo($this->db,$this->cab,array(),$wArr,'queryRow');
			$loc = $DEFS['DATA_DIR']."/".str_replace(" ","/",$folderInfo['location']); 

			if($this->fileID != -2) {
				$sArr = array('subfolder','document_table_name','document_id');
				$wArr = array('id' => $this->fileID);
				$sInfo = getTableInfo($this->db,$this->cab."_files",$sArr,$wArr,'getAssoc');
			} else {
				$sArr = array('subfolder','document_table_name','document_id');
				$wArr = array(	'doc_id' 	=> (int)$this->doc_id,
								'filename'	=> 'IS NULL',
								'deleted'	=> 0);
				$sInfo = getTableInfo($this->db,$this->cab."_files",$sArr,$wArr,'getAssoc');
			}

			$sArr = array('document_table_name','document_type_name','id');
			$formDesc = getTableInfo($this->db,'document_type_defs',$sArr,array(),'getAssoc');
		
			foreach($sInfo AS $sfold => $doc_info) {
				$doc_name = $doc_info['document_table_name'];
				$date = date("Ymd");
				$xmlObj = new xml('B2B');
				//B2B Header
				$parentEl = $xmlObj->createKeyAndValue("B2B_Req_Header");
				$xmlObj->createKeyAndValue("TRANSMISSION_NAME","B39_".$date."_".$ct,array(),$parentEl);
				//if($attempts) {
				//	$xmlObj->createKeyAndValue("RETRY_ATTEMPTS",$attempts,array(),$parentEl);
				//} else {
					$xmlObj->createKeyAndValue("RETRY_ATTEMPTS",NULL,array(),$parentEl);
				//}
				$xmlObj->createKeyAndValue("SOURCE","NFCORR",array(),$parentEl);
				$xmlObj->createKeyAndValue("LOCATION_ID","B39",array(),$parentEl);
				$xmlObj->createKeyAndValue("NO_OF_TRANSACTIONS",1,array(),$parentEl);
				
				//B2B Body
				$parentEl = $xmlObj->createKeyAndValue("B2B_Payload");
				$batchEl = $xmlObj->createKeyAndValue("BATCH",NULL,array(),$parentEl);

				$sArr = array('parent_filename','filename','ca_hash','file_size');
				$wArr = array(	'doc_id'	=> (int)$this->doc_id,
								'subfolder' => $sfold,
								'filename'	=> "IS NOT NULL",
								'deleted'	=> 0,
								'display'	=> 1);
				$oArr = array('ordering' => 'ASC');
				$filesArr = getTableInfo($this->db,$this->cab."_files",$sArr,$wArr,'queryAll',$oArr);
				$folderLoc = $loc."/".$sfold; 

				$fArr = array();
				foreach($filesArr AS $fileInfo) {
					$fpath = $folderLoc."/".$fileInfo['filename'];
					if($fileInfo['ca_hash']) {
						$ca_hash = $fileInfo['ca_hash'];
						$fsize = $fileInfo['file_size'];
						$user = new user();
						$user->username = $this->uname;
						$user->db_name = $this->department;
						if($DEFS['CENTERA_MODULE'] == 1){
							centget($DEFS['CENT_HOST'],$ca_hash,$fsize,$fpath,$user,$this->cab); 
						}
					}
					if(is_file($fpath)) {
						$mType = getMimeType($fpath,$DEFS);
						if($mType == "application/pdf") {
							$db_docInfo = new stdClass();
							$dbObjects = array();
							$tmpDir = '/tmp/nfs';
							if(!is_dir($tmpDir)) {
								mkdir($tmpDir);
							}
							$tmpFile = $tmpDir."/".$fileInfo['filename'];
							copy($fpath,$tmpFile);
							if(splitMultiPage($tmpFile, $db_docInfo, $dbObjects,false,false)) {
								unlink($tmpFile);
								$hd = opendir($tmpDir);
								while(false !== ($file = readdir($hd))) {
									if($file != "." && $file != "..") {
										if(is_dir($tmpDir."/".$file)) {
											$tmpPath = $tmpDir."/".$file;
											break;
										}
									}
								}
								closedir($hd);

								$hd = opendir($tmpPath);
								while(false !== ($file = readdir($hd))) {
									if($file == "INDEX.DAT" || $file == "ERROR.txt") {
										unlink($tmpPath."/".$file);
									} else {
										if(is_file($tmpPath."/".$file)) {
											$fArr[] = $tmpPath."/".$file;
										}
									}
									
								}
								closedir($hd);
							}
						} elseif($mType == "image/tiff") {
							$fArr[] = $fpath;
						}
					}
				}

				$systemID = "OTHER";
				$sArr = array('id');
				$wArr = array('document_table_name' => $doc_name,
							'arb_field_name' => 'Type Yes if Streetscape');
				$fnID = getTableInfo($this->db,'document_field_defs_list',$sArr,$wArr,'queryOne');
				if($fnID) {
					$sArr = array('document_field_value');
					$wArr = array('document_defs_list_id' => $formDesc[$doc_name]['id'],
									'document_id' => $doc_info['document_id'],
									'document_field_defs_list_id' => (int)$fnID);
					$sID = getTableInfo($this->db,'document_field_value_list',$sArr,$wArr,'queryOne');
					if(strtolower($sID) == "yes") {
						$systemID = "STREETSCAPE";
					}
				}

				$docParentEl = $xmlObj->createKeyAndValue("DOCUMENT",NULL,array(),$batchEl);
				$parentEl = $xmlObj->createKeyAndValue("DOC_DATA",NULL,array(),$docParentEl);
				$trans_type = $this->getFormDescTrans($formDesc[$doc_name]['document_type_name']);
				$xmlObj->createKeyAndValue("ACCT_NUMBER",str_replace("-","",$folderInfo['account_number']),array(),$parentEl);
				$xmlObj->createKeyAndValue("CUSTOMER_ID_TYPE",$folderInfo['ssn_or_tin']{0},array(),$parentEl);
				$xmlObj->createKeyAndValue("CUSTOMER_ID",$folderInfo['ssn'],array(),$parentEl);
				$xmlObj->createKeyAndValue("TRANSACTION_TYPE",$trans_type,array(),$parentEl);
				$xmlObj->createKeyAndValue("NO_OF_IMAGES",count($fArr),array(),$parentEl);
				$xmlObj->createKeyAndValue("ORIG_TRACKING_REF",$this->wf_document_id,array(),$parentEl);
				$xmlObj->createKeyAndValue("TRANSACTION_NUM",$ct,array(),$parentEl);
				$xmlObj->createKeyAndValue("ORIG_SYSTEM_ID",$systemID,array(),$parentEl);


				$imageNum = 1;
				foreach($fArr AS $fpath) {
					if(is_file($fpath)) {
						$fStr = file_get_contents($fpath);
						$mType = getMimeType($fpath,$DEFS);
						$parentEl = $xmlObj->createKeyAndValue("IMAGE",NULL,array(),$docParentEl);
						$xmlObj->createKeyAndValue("MIME_TYPE",$mType,array(),$parentEl);
						$xmlObj->createKeyAndValue("IMAGE_NUM",$imageNum,array(),$parentEl);
						$xmlObj->createKeyAndValue("IMAGE_DATA",base64_encode($fStr),array(),$parentEl);
						$imageNum++;
						//unlink($fpath);
					}
				}
				if(is_dir($tmpPath)) {
					rmdir($tmpPath);
				}
				$domStr = $xmlObj->createDOMString();

				$fp = fopen('nfs.xml','w+');
				fwrite($fp,$domStr);
				fclose($fp);

				$location = "https://epsigw.fmr.com:8500/invoke/SubmitBatchNFA/SubmitBatchNFA";
				$cmd = "curl --cacert /usr/share/ssl/certs/test.PEM ";
				$cmd .= "-E /etc/httpd/conf/doc1.crt ";
				$cmd .= "--pass xFactor9 ";
				$cmd .= "--header 'Content-Type: text/xml' ";
				$cmd .= "-d @./nfs.xml ";
				$cmd .= $location;

				$resXml = shell_exec($cmd);
				$this->parseXML($resXml);
				$ct++;
			}
			$this->processCodes();
		} elseif($status == "PAUSED") {
			$userList = array();
			$wfOwner = getWFOwner($this->db, $this->wf_document_id);
			$userList = $this->getUniqueUsers();
			//determine list of users to be notified
			$userList = $this->getWhichUser( $userList );	
			if( sizeof($userList) == 0 ) {
				$userList[] = $wfOwner;	
			}

			$docutron = getDbObject( 'docutron' );
			//add entry to the wf_todo table in docutron
			$todoArr = array();
			foreach( $userList AS $username ) {
				$todoArr[] = array(	"department" => $this->department,
									"username" => $username,
									"wf_document_id" => (int)$this->wf_document_id,
									"wf_def_id" => (int)$this->wf_def_id );
			}
			
			foreach( $todoArr AS $sqlArr ) {
				$docutron->autoExecute( "wf_todo", $sqlArr );
			}
			
			if( $this->email ) {
				$attachment = "";
				$serverName = "treenosoftware.com";
				$message = $this->message."\n".$this->body;
				//email userlist if applies
				$addressList = $this->generateEmailList($docutron, $wfOwner, $userList);
				sendMail( $addressList, $this->subject, $message, $attachment, $serverName );
			}
			$notes = "user notified that new workflow has entered their todo list";
			$this->addToWFHistory('notified',$notes,$userList);
		}
	}

	function parseXML($xml) {
		$domDoc = new DOMDocument();
		$domDoc->loadXML($xml);

		$ret = $domDoc->getElementsByTagName("B2B_Payload");
		$retCodes = $ret->item(0);
		$retCodes = $retCodes->childNodes;
		for($i=0;$i<$retCodes->length;$i++) {
			$node = $retCodes->item($i);
			$node = $node->nodeName;
			if($node == "RETURN_CODES") {
				$n = $retCodes->item($i);
				$n = $n->childNodes;
				for($j=0;$j<$n->length;$j++) {
					$myTmp = $n->item($j);
					if($myTmp->nodeName == "RETURN_CODES") {
						$code = $n->item($j);
						$code = $code->nodeValue;
						$this->codesArr[$code] = $this->returnCodes[$code];
						$rcode = "";
						if(isSet($this->returnCodes["$code"])) {
							$rcode = $this->returnCodes["$code"];
						}
						$this->addToWFHistory('Reply Code',$code.":".$rcode);
					}
				}
			} elseif($node == "GUID") {
				$guid = $retCodes->item($i);
				$guid = $guid->nodeValue;
				$this->addToWFHistory('GUID',$guid);
			}
		}

		$tname = $domDoc->getElementsByTagName("TransmissionName");
		$tname = $tname->item(0);
		$tname = $tname->nodeValue;
		$this->addToWFHistory('Transmission Name',$tname);

		$this->addNFSItem();
	} 

	function processCodes() {
		$uArr = array('status' => 'IN_PROGESS');
		$wArr = array('id' => (int)$this->wf_document_id);
		updateTableInfo($this->db,'wf_documents',$uArr,$wArr);

		//if(count($this->codesArr) == 1 && array_key_exists("0",$this->returnCodes)) {
			$this->accept();	
		//} else {
		//	foreach($this->codesArr AS $code => $desc) {
		//		$codes[] = "Code: ".$code." Description: ".$desc;
		//	}
		//	$this->reject(implode(",",$codes));	
		//}
	}

	function addNFSItem() {
		lockTables($this->db,array('nfs_list','unreconciled_nfs'));
		$insertArr = array(	'date'		=> date('Y-m-d G:i:s'),
							'cab'		=> $this->cab,
							'doc_id'	=> $this->doc_id,
							'file_id'	=> $this->fileID,
							'user'		=> $this->uname );
		$res = $this->db->autoExecute('nfs_list',$insertArr);
		dbErr($res);

		$sArr = array('MAX(id)');
		$nfs_id = getTableInfo($this->db,'nfs_list',$sArr,array(),'queryOne');

		$insertArr = array('nfs_id' => (int)$nfs_id);
		$res = $this->db->autoExecute('unreconciled_nfs',$insertArr);
		dbErr($res);
		unlockTables($this->db);
	}

	function setFormDescTrans() { 
		$transArr = array(
					'Beneficiary Designation'				=> 'BENE DESIGNATION',
					'Beneficiary Distribution'				=> 'BENE DISTRIBUTION',
					//'Brokerage Access'					=> '',
					'Brokerage Margin Accounts'				=> 'MARGIN ACCT',
					//'Brokerage Portfolio'					=> '',
					'Checkwriting (Retirement Account)'		=> 'RET CHECKING',
					'Conversion'							=> 'CONVERSION',
					'Divorce'								=> 'DIVORCE',
					'FAD Fee'								=> 'FAD FEES',
					'IRA-BDA Setup (Death Distributions)'	=> 'IRA-BDA SET-UP',
					'Letter of Instruction'					=> 'LOI',
					'One Time Retirement Distribution'		=> 'ONE TIME DIST',
					'Power of Attorney'						=> 'POA',
					'Recharacterization'					=> 'RECHARACTERIZATION',
					'Retirement Account Applications'		=> 'APPLICATION',
					'Return of Excess'						=> 'RETURN OF EXCESS',
					'SAM - Beneficiary Designation'			=> 'SAM - BENE DESIGNATION',
					'SAM - Other'							=> 'SAM - OTHER',
					'SAM - Rollover IRA'					=> 'SAM - ROLLOVER IRA',
					'SAM - Roth IRA'						=> 'SAM - ROTH IRA',
					'SAM - Traditional IRA'					=> 'SAM - TRADITIONAL IRA',
					'SIMPLE New Account'					=> 'SIMPLE',
					'Subcustodial Account Distributions'	=> 'SUBCUSTODIAL',
					'Supporting Documents'					=> 'SUPPORTING DOC',
					'SWP (Periodic Distribution)'			=> 'SWP',
					'Transfer of Assets Form (ACAT Only)'	=> 'STREETSCAPE ACAT',
					'ACAT Form'								=> 'STREETSCAPE ACAT',
					'Checkwriting (Core Account)'			=> 'CORE CHECKING',
					'Debit Card (Core Account)'				=> 'DEBIT CARD'
						);
		$this->transArr = $transArr;
	}

	function getFormDescTrans($k) {
		return $this->transArr[$k];
	}

	function setReturnCodes() {
		$returnCodes = array(
					'0'	=> 'Batch successfully created',
					'10'	=> 'Batch Alreday Exists',
					'99'	=> 'general problem',
					'20'	=> 'missing image',
					'21'	=> 'image parsing problem',
					'22'	=> 'unable to read image bytes',
					'23'	=> 'Mime type is unsupported',
					'24'	=> 'Image larger than the maximum size',
					'30'	=> 'Invalid Transaction Type',
					'31'	=> 'Invalid Processing Method',
					'32'	=> 'Unable to Handle Input',
					'33'	=> 'Transaction Count Mismatch',
					'34'	=> 'Image Count Mismatch',
					'35'	=> 'Request larger than the maximum request size',
					'36'	=> 'Invalid Orig System ID',
					'51'	=> 'Schema validation error related to the B2B_Req_Header element',
					'52'	=> 'Schema validation error related to the TRANSMISSION_NAME element',
					'53'	=> 'Schema validation error related to the RETRY_ATTEMPTS element',
					'54'	=> 'Schema validation error related to the SOURCE element',
					'55'	=> 'Schema validation error related to the NO_OF_TRANSACTIONS element',
					'56'	=> 'Schema validation error related to the B2B_Payload element',
					'57'	=> 'Schema validation error related to the BATCH element',
					'58'	=> 'Schema validation error related to the DOCUMENT element',
					'59'	=> 'Schema validation error related to the DOC_DATA element',
					'60'	=> 'Schema validation error related to the ACCT_NUMBER element',
					'61'	=> 'Schema validation error related to the CUSTOMER_ID element',
					'62'	=> 'Schema validation error related to the CUSTOMER_ID_TYPE element',
					'63'	=> 'Schema validation error related to the TRANSACTION_TYPE element',
					'64'	=> 'Schema validation error related to the PROCESSING_METHOD element',
					'65'	=> 'Schema validation error related to the NO_OF_IMAGES element',
					'66'	=> 'Schema validation error related to the ORIG_TRACKING_REF element',
					'67'	=> 'Schema validation error related to the TRANSACTION_NUM element',
					'68'	=> 'Schema validation error related to the DOUBLE_SIDED element',
					'69'	=> 'Schema validation error related to the IMAGE element',
					'70'	=> 'Schema validation error related to the MIME_TYPE element',
					'71'	=> 'Schema validation error related to the IMAGE_NUM element',
					'72'	=> 'Schema validation error related to the IMAGE_DATA element',
					'73'	=> 'Schema validation error related to the IMAGE_FILE element',
					'78'	=> 'Schema validation error related to the ORIG_SYSTEM_ID element',
					'90'	=> 'Schema Validation Error',
					'862'	=> 'Operator is Unauthorized to Transfer Batches of this work type',
					'1220'	=> 'middleware error exception',
					'1230'	=> 'middleware null exception',
					'1235'	=> 'Request larger than the maximum request size',
					'1240'	=> 'middleware timeout',
					'1299'	=> 'middleware general exception',
		);	
		$this->returnCodes = $returnCodes;
	}

	function getReturnCode($code) {
		return $this->returnCodes[$code];
	}
}

