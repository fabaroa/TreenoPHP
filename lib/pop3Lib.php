<?php
include_once '../db/db_common.php';
include_once '../lib/cabinets.php';
include_once '../lib/indexing2.php';
include_once '../lib/mime.php';
include_once '../lib/PDF.php';
include_once '../lib/translate.php';
include_once '../lib/utility.php';
include_once '../settings/settings.php';
include_once '../workflow/stateNode.php';

class pop3Email {
	var $uri;
	var $username;
	var $password;
	var $department;
	var $cabinet;
	var $workflow;
	var $prefix;
	var $keep_header;
	var $keep_body;
	var $keep_attachment;
	var $max_depth;
	var $route_path;
	var $mbox;
	var $structArr;
	var $headerArr;
	var $db_dept;
	var $db_doc;

	function pop3Email($ini) {
		extract($ini);
		$this->uri				= $uri;
		$this->username			= $username;
		$this->password			= $password;
		$this->department		= $department;
		$this->cabinet			= $cabinet;
		$this->workflow			= $workflow;
		$this->prefix			= $prefix;
		$this->keep_header		= $keep_header;
		$this->keep_body		= $keep_body;
		$this->keep_attachment	= $keep_attachment;
		$this->max_depth		= $max_depth;
		$this->route_path		= $route_path;
		$this->mbox			= $this->connectToImap();
		$this->structArr		= array();
		$this->headerArr		= array();
		$this->db_dept			= getDbObject($department);
		$this->db_doc			= getDbObject('docutron');
	}

	function processParts($messno,$struct,$header,$path) {
		if($this->keep_header) {
			//create dir for processing this message
			$fd = fopen( "$path/header.txt", "w+" );
			//offset for messages
			fwrite( $fd, $header );
			fclose($fd);
		}
		//If there are no parts, it is a special case. Process the
		//structure as a part.
		if($struct->parts) {
			$numParts = sizeof( $struct->parts );
			for($k=0;$k<$numParts or $k==0;$k++) {
				$this->writeFile( $struct->parts[$k], $k+1, $messno, $path, $struct );
			}
		} else {
			$this->writeFile(null, 1, $messno, $path, $struct);
		}

		if($this->keep_body) {
			if( !file_exists("$path/body.txt") AND !file_exists("$path/body.html") ) {
					$fd = fopen("$path/body.txt","a+");
					fwrite($fd," ");
					fclose($fd);
			} elseif( !file_exists("$path/body.txt") ) {
					$fd = fopen("$path/body.txt","a+");
					fwrite($fd,imap_body($this->mbox,$messno));
					fclose($fd);
			}
		}
	}

	function writeFile( $part, $partno, $messno, $path, $struct ) {
		//get the primary type of the body of the part( 0-7 )
		//see the define statements at the tope of this file for
		//possible values
		//get primary body type
		//write a specific part to a given director
		$filename = "";
		//If part is null, it is because there are no parts to this
		//message. Treat the structure as the part.
		if(is_null($part)) {
			$part = $struct;
			$ignorePartNo = true;
		} else {
			$ignorePartNo = false;
		}
		$type = $part->type;
		//get transfer encoding( 0-5 ) 
		//see define statements at top of file for these
		$encoding = $part->encoding;
		if($encoding == null)
			$encoding = $struct->encoding;

		//lets you know if this is the attachment
		//and it's file name if there is one
		$dparameters = array();
		if($part->ifdparameters) {
			$dparameters = $part->dparameters;
		}
		if( count($dparameters) > 0 and ($partno!=1 || $ignorePartNo) ) {
			if($this->keep_attachment) {
				if($this->max_depth) { 
					if( !is_dir( "$path/attachments" ) ) {
						if(!mkdir( "$path/attachments", 0777 )){
							error_log( "problem creating $path/attachments");
						}
					}
					$path .= "/attachments";
				}
				//get the filename
				foreach( $dparameters as $p ) {
					if(strtoupper($p->attribute)=='NAME'or strtoupper($p->attribute)=='FILENAME') {
						$filename = $path."/".$p->value;
					}
				}
			}
		} else {
			if($this->keep_body) {
				$full_path = $path;
				if($this->max_depth) { 
					if( !is_dir( "$path/attachments" ) ) {
						if(!mkdir( "$path/attachments", 0777 )){
							error_log("problem creating $path/attachments");
						}
						$full_path = "$path/attachments";
					}
				}
				//check if name should be html
				if( strtolower( $part->subtype )=='html' ) {
					$filename = "$path/body.html";
					$i = 0;
					while(file_exists($filename)) {
						$filename = $full_path."/body-{$i}.html";
						$i++;
					}
				} else {
					$mimeType = $this->createMimeType($messno, $partno, $encoding);
					$ext = $this->getExtFromMime($mimeType);
					$filename = "$path/body".$ext;
					$i = 0;
					while(file_exists($filename)) {
						$filename = $full_path."/body-{$i}".$ext;
						$i++;
					}
				}
			}
		}
		if($filename) {
			//DECODE the message parts
			//each piece is has an encoding type the write function
			//writes it to a temporary directory
			$bpart = imap_fetchbody( $this->mbox, $messno, $partno );
			$func = $this->getFunc($encoding);
			$this->write( $func, $bpart, $filename );
			
			$fparts = pathInfo($filename);
			$newFilename = str_replace("?","",$fparts['basename']);
			rename($filename,$fparts['dirname']."/".$newFilename);
		}
	}

	//Returns the extension from the lib/translate.php array
	function getExtFromMime($mimeType) {
		global $mArr;
		$extension = null;
		if(array_key_exists($mimeType,$mArr)) {
			$extension = trim($mArr[$mimeType]);
		}
		if($extension == null)
			return ".txt";
		return $extension;
	}

	//Create the file from the email to get the mime type
	function createMimeType($messno, $partno, $encoding) {
		global $DEFS;
		//This is all to get the mime type
		$bpart = imap_fetchbody( $this->mbox, $messno, $partno );
		$func = $this->getFunc($encoding);
		$tmpfname = tempnam("{$DEFS['TMP_DIR']}", "FOO");
		$handle = fopen($tmpfname, "w");
		$this->writeTempFile($handle, $func, $bpart);
		fclose($handle);
		$mimeType = getMimeType($tmpfname,$DEFS);
		unlink($tmpfname);

		return $mimeType;
	}

	//Get the function to pass to write() based on the encoding
	function getFunc($encoding) {
		$func = '';
		switch( (int)$encoding ) {
			case 0: //7BIT
				$func = '';
				break;
			case 1: //8BIT
				$func = 'imap_8bit';
				break;
			case 3: //BASE64:
				$func = 'imap_base64';
				break;
			case 4: //QUOTEDPRINTABLE:
				$func = 'imap_qprint';
				break;
			case 5: //EOTHER:
				$func = '';
				break;
			default:
				break;
		};
		return $func;
	}

	//write a specific part to a given directory
	function write( $func, $bpart, $filename ) {
		//check the func
		if( $func ) {
			//call the right function to decode
			$message = $func( $bpart );
		} else {
			//doesn't need to be decoded
			$message = $bpart;
		}
		//only write if you have a filename
		if( $filename ){
			$fd = fopen( $filename, 'w+' );
		  if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $message)) {
		  	error_log("*********pop3 needed decoding*******");
				$message=base64_decode($message);
		  }
	  	fwrite( $fd, $message );
			fclose( $fd );
		}
	}

	function writeTempFile($fd, $func, $bpart) {
		//check the func
		if( $func ){
			//call the right function to decode
			$message = $func( $bpart );
		} else {    //doesn't need to be decoded
			$message = $bpart;
		}
		fwrite($fd, $message);
	}

	function createTempPath($ct) {
		global $DEFS;
		//create tmp pat to save the emails
		$path = "{$DEFS['TMP_DIR']}/pop3Bot/$this->username$ct";
		if(!is_dir("{$DEFS['TMP_DIR']}/pop3Bot")) {
			mkdir( "{$DEFS['TMP_DIR']}/pop3Bot", 0777 );
		}
		if(!is_dir($path)){
			mkdir($path,0777);
		}else{
			error_log($path." already exists!\n");
		}
		return $path;
	}

	function processEmailToCabinet($path,$header,$structure,$ct) {
		global $DEFS;
		$doc_id = $this->createDocutronFolder($header);
		$location = $this->getFolderLocation($doc_id);
		$settObj = new GblStt($this->department, $this->db_doc);
		//process parts to a temporary place to be indexed away to the cab/doc_id
		$this->processParts($ct+1,$structure,$header,$path);
		//index the files away
		copyFiles( 		$path, 
						$DEFS['DATA_DIR']."/".str_replace( " ", "/", $location ), 
						$doc_id, 
						$this->db_dept, 
						$this->username, 
						$this->cabinet, 
						$this->department, 
						'Off',
						$settObj,
						$DEFS );	
		//get the doc_id from creating the folder
		//delete the files from /tmp
		$a = "/*";
		shell_exec("rm -rf {$DEFS['TMP_DIR']}/pop3Bot$a");
		//assign workflow if there was one for this line
		if($this->workflow) {
			$this->assignWorkflow($doc_id);
		}
	}

	function processEmailToPath($path,$header,$structure,$ct) {
		$this->processParts($ct+1,$structure,$header,$path);
		$toPath = $this->route_path."/".$this->prefix."-".date('YmdGis').mt_rand(1000,9999);
		while(is_dir($toPath)){
			$toPath = $this->route_path."/".$this->prefix."-".date('YmdGis').mt_rand(1000,9999);
		}
		$this->processFiles($path);
		$this->moveFiles($path,$toPath);
		rmdir($path);
	}

	function processFiles($path) {
		global $DEFS;
		$hd = opendir($path);
		while(false !== ($file = readdir($hd))) {
			if(is_file($path."/".$file)) {
				$mType = getMimeType($path."/".$file,$DEFS);
				if($mType == "application/pdf" || $mType == "image/tiff") {
					$db_docInfo = new stdClass();
					$db_docInfo->time = 0;
					$db_docInfo->db = null;
					$dbObjects = array();
					if(splitMultiPage($path."/".$file,$db_docInfo,$dbObjects)) {
						unlink($path."/".$file);
					}
				}
			} else {
				if($file != "." && $file != "..") {
					$this->processFiles($path."/".$file);	
				}
			}			
		}
	}

	function moveFiles($path,$toPath) {
		if(!is_dir($toPath)) {
			if(!mkdir($toPath,0777)) {
				error_log( $toPath );
			}
		}

		$hd = opendir($path);
		while(false !== ($file = readdir($hd))) {
			if(is_file($path."/".$file)) {
				if($file != "ERROR.txt" && $file != "INDEX.DAT") {
					$fpath = $this->checkFile($toPath,$file);
					rename($path."/".$file,$fpath);
				} else {
					unlink($path."/".$file);
				}
			} else {
				if($file != "." && $file != "..") {
					$this->moveFiles($path."/".$file,$toPath);	
					rmdir($path."/".$file);
					
					if(file_exists("$toPath/$file")) {
						//uncomment this line if attachments include barcodes.
						//copy("$toPath/$file", "{$DEFS['DATA_DIR']}/Scan");
					}
				}
			}			
		}
	}

	function checkFile($toPath,$file) {
		$fpath = $toPath."/".$file;
		if((is_file($fpath))) {
			$ct = 1;
			$p = $fpath."-".$ct;
			while(is_file($p)) {
				$ct++;
				$p = $fpath."-".$ct;
			}
			$fpath = $p;
		}
		return $fpath;
	}

	function processEmails() {
		$this->getEmailStructure();
		for($i=0;$i<count($this->structArr);$i++) {
			$path = $this->createTempPath($i);
			if($this->cabinet != "") {
				$this->processEmailToCabinet($path,$this->headerArr[$i],$this->structArr[$i],$i);
			} elseif($this->route_path != "") {
				$this->processEmailToPath($path,$this->headerArr[$i],$this->structArr[$i],$i);
			}
			//delete message number i+1
			imap_delete( $this->mbox, $i+1 );
		}
		imap_close( $this->mbox, CL_EXPUNGE );
	}

	function connectToImap() {
		$mbox = imap_open( $this->uri, $this->username, $this->password );
		if(!$mbox) {
			error_log(print_r(imap_errors(), true));
			error_log("imap/pop error msg: ".imap_last_error());
			error_log("cant pop {$this->uri} {$this->username} {$this->password}");
			return false;
		}
		return $mbox;
	}

	function getEmailStructure() {
		$msgNum = imap_num_msg($this->mbox);
		for($i=1;$i<=$msgNum;$i++) {
			//get the structure of the email
			//this helps decode attachements and parse the header
			$this->structArr[] = imap_fetchstructure($this->mbox,$i);
			//get the header information and save it
			//will allow for array parsing of lines of the header
			$this->headerArr[] = imap_fetchheader($this->mbox,$i);
		}
	}

	//getHeaderInfo parses the header and breaks out the
	//To, From, Subject fields into usable pieces
	function getHeaderInfo($header) {
		//the header is just one big string
		//split it by line
		$lines = explode( "\n", $header );
		foreach( $lines as $line ) {
			//check for To starting a line to get to
			if( strtolower($line{0})=='t' and strtolower($line{1})=='o' ) {
				$tokens = explode( ':', $line );
				//The first is the To field
				unset($tokens[0]);
				$indiceArr[0] = trim(implode(':', $tokens));
			}
			if( strtolower($line{0})=='f' and strtolower($line{1})=='r' and 
				strtolower( $line{2})=='o' and strtolower($line{3})=='m' )
			{
				$tokens = explode( ':', $line );
				//This is the form field
				unset($tokens[0]);
				$indiceArr[1] = trim(implode(':', $tokens));
			}
			if( strtolower($line{0})=='s' and strtolower($line{1})=='u' and 
				strtolower( $line{2})=='b' and strtolower($line{3})=='j' )
			{
				$tokens = explode( ':', $line );
				unset($tokens[0]);
				$indiceArr[2] = trim(implode(':', $tokens));
			}
		}
		ksort( $indiceArr );
		//add the date time field
		$indiceArr[3] = date( "Y-m-d H:i:s" );
		$indiceArr = $this->removeBadCharacters($indiceArr);
		return $indiceArr;
	}

	function createDocutronFolder($header) {
		$indiceArr = $this->getHeaderInfo($header);
		//get cabinet indices
		$fieldNames = getCabinetInfo($this->db_dept,$this->cabinet);
		//create folder per email
		$temp_table = "";
		$gblStt = new GblStt($this->department,$this->db_doc);
		$doc_id = createFolderInCabinet( 
						$this->db_dept, 
						$gblStt,
						$this->db_doc,
						$this->username, 
						$this->department, 
						$this->cabinet, 
						$indiceArr,
						$fieldNames,
						$temp_table,
						false );
		return $doc_id;
	}

	function getFolderLocation($doc_id) {
		$sArr = array('location');
		$wArr = array('doc_id' => (int) $doc_id);
		$location = getTableInfo($this->db_dept,$this->cabinet,$sArr,$wArr,'queryOne');
		return $location;
	}

	function assignWorkflow($doc_id) {
		$sArr = array('departmentname');
		$wArr = array('real_name' => $this->cabinet);
		$arbCab = getTableInfo($this->db_dept,'departments',$sArr,$wArr,'queryOne');

		$arr 			= getWFDefsInfo( $this->db_dept, $this->workflow );
		$wf_def_id		= $arr[1];
		$wf_document_id	= addToWorkflow($this->db_dept,$wf_def_id,$doc_id,0,$this->cabinet,$this->username);
		$stateNodeObj   = new stateNode($this->db_dept,$this->department,$this->username,$wf_document_id,$wf_def_id,$this->cabinet,$arbCab,$doc_id,$this->db_doc);
		$stateNodeObj->notify();
		$action = $stateNodeObj->message;
		if($action == "" OR $action == NULL) {
			$action = "Workflow assigned";
		}
		
		$insArr = array (	'username' => $this->username,
							'datetime' => date('Y-m-d G:i:s'),
							'info' => 'workflow',
							'action' => $action );
		$res = $this->db_dept->autoExecute('audit', $insArr);
		dbErr($res);
	}

	function removeBadCharacters($indiceArr) {
		for( $i = 0; $i < sizeof( $indiceArr ); $i++ ) {
			//strip out bad characters
			$indiceArr[$i] = str_replace( "'", "", $indiceArr[$i] );
			$indiceArr[$i] = str_replace( "<", "", $indiceArr[$i] );
			$indiceArr[$i] = str_replace( ">", "", $indiceArr[$i] );
			$indiceArr[$i] = str_replace( "\"", "", $indiceArr[$i] );
			$indiceArr[$i] = str_replace( "`", "", $indiceArr[$i] );
			$indiceArr[$i] = str_replace( "~", "", $indiceArr[$i] );
			$indiceArr[$i] = str_replace( "&", "", $indiceArr[$i] );
			$indiceArr[$i] = str_replace( "\\", "", $indiceArr[$i] );
		}
		return $indiceArr;
	}
}
?>
