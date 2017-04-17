<?php
// $Id: BasicTypes.php 14326 2011-04-11 20:31:25Z fabaroa $
/*
 * This file contains all of the most basic soap types
 */


class simpleReturn {
	var $success;
	var $message;
	
    function simpleReturn($success = NULL, $mess = NULL) {
        $this->success = $success;
        $this->message = $mess;
    }

    function &__to_soap($name = 'simpleReturn', $header = false, $mustUnderstand = 0, 
    					$actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('success', 'boolean', $this->success);
        $inner[] = new SOAP_Value('message', 'string', $this->message);
        if ($header) {
            return new SOAP_Header($name,'{urn:TreenoWebServices}simpleReturn', $inner,
            					   $mustUnderstand, $actor);
        }
        return new SOAP_Value($name, '{urn:TreenoWebServices}simpleReturn', $inner);
    }
}

?>
