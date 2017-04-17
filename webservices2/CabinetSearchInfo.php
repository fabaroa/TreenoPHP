<?php

class CabinetSearchInfo {
	var $department;
	var $cabinetID;
	var $resultID;
	var $numResults;
	
    function CabinetSearchInfo($department = NULL, $cabinetID = NULL, $resultID = NULL, $numResults = NULL) {
    	$this->department = $department;
        $this->cabinetID = $cabinetID;
        $this->resultID = $resultID;
        $this->numResults = $numResults;
    }

    function &__to_soap($name = 'CabinetSearchInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('department','string',$this->department);
        $inner[] = new SOAP_Value('cabinetID','int',$this->cabinetID);
        $inner[] = new SOAP_Value('resultID','string',$this->resultID);
        $inner[] = new SOAP_Value('numResults','int',$this->numResults);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}CabinetSearchInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}CabinetSearchInfo',$inner);
    }
}

?>
