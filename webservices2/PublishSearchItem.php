<?php
class PublishSearchItem {
	var $id;
	var $name;
	var $cab;
	var $indexField;
	var $doc_id;
	var $file_id;
	var $searchTerm;
	
    function PublishSearchItem($id=NULL,$name=NULL,$cab=NULL,$indexField=NULL,$doc_id=NULL,$file_id=NULL,$searchTerm = NULL) {
        $this->id = $id;
        $this->name = $name;
		$this->cab = $cab;
		$this->indexField = $indexField;
		$this->doc_id = $doc_id;
		$this->file_id = $file_id;
		$this->searchTerm = $searchTerm;
    }

    function &__to_soap($name='PublishSearchItem',$header=false,$mustUnderstand=0,$actor='http://schemas.xmlsoap.org/soap/actor/next'){
        $inner[] = new SOAP_Value('id','string',$this->id);
        $inner[] = new SOAP_Value('name','string',$this->name);
        $inner[] = new SOAP_Value('cab','string',$this->cab);
        $inner[] = new SOAP_Value('indexField','string',$this->indexField);
        $inner[] = new SOAP_Value('doc_id','string',$this->doc_id);
        $inner[] = new SOAP_Value('file_id','string',$this->file_id);
        $inner[] = new SOAP_Value('searchTerm','string',$this->searchTerm);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}PublishSearchItem',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}PublishSearchItem',$inner);
    }
}

?>
