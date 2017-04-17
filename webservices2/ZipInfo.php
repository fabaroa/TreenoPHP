<?php

class ZipFileInfo {
	var $fileName;
	var $fileSize;
	
    function ZipFileInfo($fileName = NULL, $fileSize = NULL) {
        $this->fileName = $fileName;
        $this->fileSize = $fileSize;
    }

    function &__to_soap($name = 'ZipFileInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('fileName','string',$this->fileName);
        $inner[] = new SOAP_Value('fileSize','double',$this->fileSize);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}ZipFileInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}ZipFileInfo',$inner);
    }
}

?>
