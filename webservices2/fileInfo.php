<?php
class fileInfo2 extends fileInfo {
	var $ordering;
    function fileInfo2($fileName=NULL,$fileID=NULL,$docID=NULL,$fileSize=NULL,$department=NULL,$cabinetID=NULL,$ordering=NULL) {
		fileinfo::fileinfo($fileName,$fileID,$docID,$fileSize,$department,$cabinetID);
		$this->ordering = $ordering; 
	}
}
class ADPfileInfo extends fileInfo {
//	var $tab;
    function ADPfileInfo($fileName=NULL,$fileID=NULL,$docID=NULL,$fileSize=NULL,$department=NULL,$cabinetID=NULL,$tab=NULL) {
		fileinfo::fileinfo($fileName,$fileID,$docID,$fileSize,$department,$cabinetID);
		$this->tab = $tab; 
	}
}
class fileInfo {
	var $fileName;
	var $fileID;
	var $docID;
	var $fileSize;
	var $department;
	var $cabinetID;
	
    function fileInfo($fileName = NULL, $fileID = NULL, $docID = NULL, $fileSize = NULL, $department = NULL, $cabinetID = NULL) {
        $this->fileName = $fileName;
        $this->fileID = $fileID;
        $this->docID = $docID;
		$this->fileSize = $fileSize;
		$this->department = $department;
		$this->cabinetID = $cabinetID;
    }

    function &__to_soap($name = 'fileInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('fileName','string',$this->fileName);
        $inner[] = new SOAP_Value('fileID','int',$this->fileID);
        $inner[] = new SOAP_Value('docID','int',$this->docID);
		$inner[] = new SOAP_Value('fileSize', 'int', $this->fileSize);
		$inner[] = new SOAP_Value('department', 'string', $this->department);
		$inner[] = new SOAP_Value('cabinetID', 'int', $this->cabinetID);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}fileInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}fileInfo',$inner);
    }
}

class ExpFileInfo {
	var $fileName;
	var $parentFileName;
	var $fileID;
	var $docID;
	var $fileSize;
	var $department;
	var $cabinetID;
	
    function ExpFileInfo($fileName = NULL, $parentFileName = NULL, $fileID = NULL, $docID = NULL, $fileSize = NULL, $department = NULL, $cabinetID = NULL) {
        $this->fileName = $fileName;
		$this->parentFileName = $parentFileName;
        $this->fileID = $fileID;
        $this->docID = $docID;
		$this->fileSize = $fileSize;
		$this->department = $department;
		$this->cabinetID = $cabinetID;
    }

    function &__to_soap($name = 'ExpFileInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('fileName','string',$this->fileName);
        $inner[] = new SOAP_Value('parentFileName','string',$this->parentFileName);
        $inner[] = new SOAP_Value('fileID','int',$this->fileID);
        $inner[] = new SOAP_Value('docID','int',$this->docID);
		$inner[] = new SOAP_Value('fileSize', 'int', $this->fileSize);
		$inner[] = new SOAP_Value('department', 'string', $this->department);
		$inner[] = new SOAP_Value('cabinetID', 'int', $this->cabinetID);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}ExpFileInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}ExpFileInfo',$inner);
    }
}
?>
