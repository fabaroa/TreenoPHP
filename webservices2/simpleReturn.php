<?php

class simpleReturn {
	var $success;
	var $message;
	
    function simpleReturn($success = NULL, $mess = NULL) {
        $this->success = $success;
        $this->message = $mess;
    }

    function &__to_soap($name = 'simpleReturn', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('success','boolean',$this->success);
        $inner[] = new SOAP_Value('message','string',$this->message);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}simpleReturn',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}simpleReturn',$inner);
    }
}
?>
