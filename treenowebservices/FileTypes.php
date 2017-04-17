<?php
// $Id: FileTypes.php 14414 2011-06-27 14:18:06Z fabaroa $
/*
 * This file contains the file level types
 */

// File List
class FileInfo {
	var $fileID;
	var $fileName;
	var $subfolder;
	// may want to add subfolder info in future
	
	function FileInfo($id = NULL, $name = NULL, $subfolder = NULL) {
		$this->fileID	= $id;
		$this->fileName	= $name;
		$this->subfolder	= $subfolder;
	}

    function &__to_soap($name = 'FileInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('fileID', 'int', $this->fileID);
        $inner[] = new SOAP_Value('fileName', 'string', $this->fileName);
        $inner[] = new SOAP_Value('subfolder', 'string', $this->subfolder);
        if ($header) {
            return new SOAP_Header($name, '{urn:TreenoWebServices}FileInfo', $inner, 
            					   $mustUnderstand, $actor);
        }
        return new SOAP_Value($name, '{urn:TreenoWebServices}FileInfo', $inner);
    }
}

?>