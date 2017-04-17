<?php

class strAssocVal {
	var $key;
	var $value;
	
    function strAssocVal($key = NULL, $value = NULL) {
        $this->key = $key;
        $this->value = $value;
    }

    function &__to_soap($name = 'strAssocVal', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('key','string',$this->key);
        $inner[] = new SOAP_Value('value','string',$this->value);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}strAssocVal',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}strAssocVal',$inner);
    }
}
?>
