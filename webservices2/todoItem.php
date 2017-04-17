<?php

class WorkflowDefs {
	var $defsID;
	var $defsName;

	function WorkflowDefs($defsID = NULL, $defsName = NULL) {
		$this->defsID = $defsID;
		$this->defsName = $defsName;
	}
}

class WorkflowDefinition {
	var $id;
	var $nodeName;
	
    function WorkflowDefinition($id = 0, $nodeName = NULL) {
        $this->id = (int)$id;
        $this->nodeName = $nodeName;
    }

    function &__to_soap($name = 'WorkflowDefinition', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('id','int',$this->id);
        $inner[] = new SOAP_Value('nodeName','string',$this->nodeName);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}WorkflowDefinition',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}WorkflowDefinition',$inner);
    }

}

class todoItem {
	var $mailedTo;
	var $mailedFrom;
	var $subject;
	var $date;
	var $bodyText;
	var $header;
	var $attachments;
	var $department;
	
    function todoItem($mailedTo = NULL, $mailedFrom = NULL, $subject = NULL, 
		$date = NULL, $bodyText = NULL,	$header = NULL, $attachments = NULL,
		$department = NULL) {
       
		$this->mailedTo = $mailedTo;
		$this->mailedFrom = $mailedFrom;
		$this->subject = $subject;
		$this->date = $date;
		$this->bodyText = $bodyText;
		$this->header = $header;
		$this->attachments = $attachments;
		$this->department = $department;
    }

    function &__to_soap($name = 'todoItem', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
		$inner[] = new SOAP_Value('mailedTo','string',$this->mailedTo);
		$inner[] = new SOAP_Value('mailedFrom','string',$this->mailedFrom);
		$inner[] = new SOAP_Value('subject','string',$this->subject);
		$inner[] = new SOAP_Value('date','date',$this->date);
        $inner[] = new SOAP_Value('bodyText','string',$this->bodyText);
        $inner[] = new SOAP_Value('header','string',$this->header);
		$inner[] = new SOAP_Value('attachments', '{urn:DocutronWebServices2}attachments', $this->attachments);
        $inner[] = new SOAP_Value('department','string',$this->department);
		
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}todoItem',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}todoItem',$inner);
    }
}

class todoItemHeader {
	var $todoID;
	var $mailedFrom;
	var $mailedTo;
	var $subject;
	var $date;
	var $arbDept;
    
    function todoItemHeader($todoID = NULL, $mailedFrom = NULL, $mailedTo = NULL, $subject = NULL, 
		$date = NULL, $arbDept = NULL) {
       
		$this->todoID = $todoID;
		$this->mailedFrom = $mailedFrom;
		$this->mailedTo = $mailedTo;
		$this->subject = $subject;
		$this->date = $date;
		$this->arbDept = $arbDept;
    }

    function &__to_soap($name = 'todoItemHeader', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
		$inner[] = new SOAP_Value('todoID','int',$this->todoID);
		$inner[] = new SOAP_Value('mailedFrom','string',$this->mailedFrom);
		$inner[] = new SOAP_Value('mailedTo','string',$this->mailedTo);
		$inner[] = new SOAP_Value('subject','string',$this->subject);
		$inner[] = new SOAP_Value('date','date',$this->date);
		$inner[] = new SOAP_Value('arbDept', 'string', $this->arbDept);
		
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}todoItemHeader',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}todoItemHeader',$inner);
    }
}
?>
