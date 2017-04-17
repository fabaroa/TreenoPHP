<?php

class fileIdInfo {
	var $fileName;
	var $fileID;
	var $department;
	var $cabinetID;
	
    function fileIdInfo($fileName = NULL, $fileID = NULL, $department = NULL, $cabinetID = NULL) {
        $this->fileName = $fileName;
        $this->fileID = $fileID;
		$this->department = $department;
		$this->cabinetID = $cabinetID;
    }

    function &__to_soap($name = 'fileInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('fileName','string',$this->fileName);
        $inner[] = new SOAP_Value('fileID','int',$this->fileID);
		$inner[] = new SOAP_Value('department', 'string', $this->department);
		$inner[] = new SOAP_Value('cabinetID', 'int', $this->cabinetID);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}fileInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}fileInfo',$inner);
    }
}
?>
