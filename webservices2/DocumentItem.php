<?php 

class DetailedDocumentInfo {
	var $tabID;
	var $documentName;
	var $documentType;
	var $documentIndices;

	function DetailedDocumentInfo($tabID = NULL, $documentName = NULL, $documentType = NULL, $documentIndices = NULL) {
		$this->tabID = $tabID;
		$this->documentName = $documentName;
		$this->documentType = $documentType;
		$this->documentIndices = $documentIndices;
	}
}

class DetailedDocumentInfo2 {
	var $sequence;
	var $tabID;
	var $documentName;
	var $documentType;
	var $documentIndices;

	function DetailedDocumentInfo2($sequence = 0, $tabID = NULL, $documentName = NULL, $documentType = NULL, $documentIndices = NULL) {
		$this->sequence = $sequence;
		$this->tabID = $tabID;
		$this->documentName = $documentName;
		$this->documentType = $documentType;
		$this->documentIndices = $documentIndices;
	}
}


class DocumentIndex {
	var $indexName;
	var $displayName;
	var $value;

	function DocumentIndex ($indexName = NULL, $displayName = NULL, $value = NULL) {
		$this->indexName = $indexName;
		$this->displayName = $displayName;
		$this->value = $value;
	}
}

class DocumentIndexDefinitions {
	var $name;
	var $required;
	var $regex;
	var $display;
	
    function DocumentIndexDefinitions($name = NULL, $required = 0, $regex = NULL, $display = NULL) {
        $this->name = $name;
        $this->required = (int)$required;
        $this->regex = $regex;
        $this->display = $display;
    }

    function &__to_soap($name = 'DocumentIndexDefinitions', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('name','string',$this->name);
        $inner[] = new SOAP_Value('required','int',$this->required);
        $inner[] = new SOAP_Value('regex','string',$this->regex);
        $inner[] = new SOAP_Value('display','string',$this->display);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}DocumentIndexDefinitions',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}DocumentIndexDefinitions',$inner);
    }

}

class DocumentInfo {
	var $tabID;
	var $documentIndices;
	
    function DocumentInfo($tabID = NULL, $documentIndices = NULL) {
        $this->tabID = $tabID;
        $this->documentIndices = $documentIndices;
    }

    function &__to_soap($name = 'DocumentInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('tabID','int',$this->tabID);
        $inner[] = new SOAP_Value('documentIndices','string',$this->documentIndices);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}DocumentInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}DocumentInfo',$inner);
    }
}

class DocumentItem {
	var $index;
	var $value;
	
    function DocumentItem($index = NULL, $value = NULL) {
        $this->index = $index;
        $this->value = $value;
    }

    function &__to_soap($name = 'DocumentItem', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('index','string',$this->index);
        $inner[] = new SOAP_Value('value','string',$this->value);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}DocumentItem',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}DocumentItem',$inner);
    }
}

class DocumentType {
	var $documentID;
	var $realName;
	var $arbName;
	var $indices;
	var $definitions;

	function DocumentType($documentID = NULL, $realName = NULL, $arbName = NULL, $indices = NULL, $definitions = NULL) {
		$this->documentID = $documentID;
		$this->realName = $realName;
		$this->arbName = $arbName;
		$this->indices = $indices;
		$this->definitions = $definitions;
	}

    function &__to_soap($name = 'DocumentType', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('documentID','int',$this->documentID);
        $inner[] = new SOAP_Value('realName','string',$this->realName);
        $inner[] = new SOAP_Value('arbName','string',$this->arbName);
        $inner[] = new SOAP_Value('indices','{urn:DocutronWebServices2}DocumentEntry',$this->indices);
        $inner[] = new SOAP_Value('definitions','{urn:DocutronWebServices2}DocumentDefinitionsList',$this->definitions);
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}DocumentType',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}DocumentType',$inner);
    }
}

class DetailedDocumentType {
	var $documentID;
	var $realName;
	var $arbName;
	var $indices;

	function DetailedDocumentType($documentID = NULL, $realName = NULL, $arbName = NULL, $indices = NULL) {
		$this->documentID = $documentID;
		$this->realName = $realName;
		$this->arbName = $arbName;
		$this->indices = $indices;
	}

    function &__to_soap($name = 'DetailedDocumentType', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $ref = new SOAP_Value('documentID','int',$this->documentID);
        $inner[] =& $ref;
        $ref = new SOAP_Value('realName','string',$this->realName);
        $inner[] =& $ref;
        $ref = new SOAP_Value('arbName','string',$this->arbName);
        $inner[] =& $ref;
        $ref = new SOAP_Value('indices','{urn:DocutronWebServices2}DetailedDocumentEntry',$this->indices);
        $inner[] =& $ref;
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}DetailedDocumentType',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}DetailedDocumentType',$inner);
    }
}

class DocumentDefinition {
	var $index_name;
	var $definition_list;

	function DocumentDefinition($index_name = NULL, $definition_list = NULL) {
		$this->index_name = $index_name;
		$this->definition_list = $definition_list;
	}

    function &__to_soap($name = 'DocumentDefinition', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $ref = new SOAP_Value('index_name','string',$this->index_name);
        $inner[] =& $ref;
				$ref = new SOAP_Value('definition_list', '{urn:DocutronWebServices2}StringList', $this->definition_list);
				$inner[] =& $ref;
        if ($header) {
            return new SOAP_Header($name,'{urn:DocutronWebServices2}DocumentDefinition',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:DocutronWebServices2}DocumentDefinition',$inner);
    }
}
?>
