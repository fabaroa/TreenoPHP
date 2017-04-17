<?php
class PublishUserItem {
	var $id;
	var $username;
	
    function PublishUserItem($id = NULL, $username = NULL) {
        $this->id = $id;
        $this->username = $username;
    }

    function &__to_soap($name='PublishUserItem',$header=false,$mustUnderstand=0,$actor='http://schemas.xmlsoap.org/soap/actor/next'){
        $inner[] = new SOAP_Value('id','string',$this->id);
        $inner[] = new SOAP_Value('username','string',$this->username);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}PublishUserItem',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}PublishUserItem',$inner);
    }
}

?>
