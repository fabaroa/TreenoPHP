<?php

class AuditSearchInfo {
	var $id;
	var $username;
	var $datetime;
	var $info;
	var $action;
	
    function AuditSearchInfo($id = NULL, $username = NULL, $datetime = NULL, $info = NULL, $action = NULL) {
    	$this->id = $id;
    	$this->username = $username;
    	$this->datetime = $datetime;
    	$this->info = $info;
    	$this->action = $action;
    }

    function &__to_soap($name = 'AuditSearchInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('id','int',$this->id);
        $inner[] = new SOAP_Value('username','string',$this->username);
        $inner[] = new SOAP_Value('datetime','string',$this->datetime);
        $inner[] = new SOAP_Value('info','string',$this->info);
        $inner[] = new SOAP_Value('action','string',$this->action);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}AuditSearchInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}AuditSearchInfo',$inner);
    }
}

class AuditSearchItem {
	var $index;
	var $value;
	
    function AuditSearchItem($index = NULL, $value = NULL) {
        $this->index = $index;
        $this->value = $value;
    }

    function &__to_soap($name = 'AuditSearchItem', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('index','string',$this->index);
        $inner[] = new SOAP_Value('value','string',$this->value);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}AuditSearchItem',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}AuditSearchItem',$inner);
    }
}

class AuditDateTime {
	var $operation;
	var $value;
	
    function AuditDateTime($operation = NULL, $value = NULL) {
        $this->operation = $operation;
        $this->value = $value;
    }

    function &__to_soap($name = 'AuditDateTime', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('operation','string',$this->operation);
        $inner[] = new SOAP_Value('value','string',$this->value);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}AuditDateTime',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}AuditDateTime',$inner);
    }
}
?>
