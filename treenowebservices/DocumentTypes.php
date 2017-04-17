<?php
// $Id: DocumentTypes.php 14389 2011-06-01 12:19:59Z fabaroa $
/*
 * This files contains the document level types
 */

// definitions list
class DocumentDefsList {
	var $documentTypeID;
	var $displayName;
	var $internalName;
	var $indicies;
	var $docTypeIndexDefs;
	
	function DocumentDefsList($id=NULL, $realName = NULL, $arbName = NULL, $indicies = NULL, $docTypeIndexDefs = NULL) {
		$this->documentTypeID = $id;
		$this->displayName  = $realName;
		$this->internalName = $arbName;
		$this->indicies     = $indicies;
		$this->docTypeIndexDefs  = $docTypeIndexDefs;
	}
	
    function &__to_soap($name = 'DocumentDefsList', $header = false, $mustUnderstand = 0, 
    					$actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('documentTypeID', 'string', $this->documentTypeID);
        $inner[] = new SOAP_Value('displayname', 'string', $this->displayname);
        $inner[] = new SOAP_Value('internalName', 'string', $this->internalName);
        $inner[] = new SOAP_Value('indicies', '{urn:TreenoWebServices}DocIndiciesList', $this->indicies);
        $inner[] = new SOAP_Value('docTypeIndexDefs', '{urn:TreenoWebServices}DefinitionNameList', $this->docTypeIndexDefs);
        if ($header) {
            return new SOAP_Header($name, '{urn:TreenoWebServices}DocumentDefInfo', $inner, 
            					   $mustUnderstand, $actor);
        }
        return new SOAP_Value($name, '{urn:TreenoWebServices}DocumentDefInfo', $inner);
    }
}
class DefinitionName {
	var $index_name;
	var $definition_list;

	function DefinitionName($index_name = NULL, $definition_list = NULL) {
		$this->index_name = $index_name;
		$this->definition_list = $definition_list;
	}

    function &__to_soap($name = 'DefinitionName', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
//        $inner[] =& $ref;
        $inner[] = new SOAP_Value('index_name','string',$this->index_name);
				$inner[] = new SOAP_Value('definition_list', '{urn:TreenoWebServices}StringList', $this->definition_list);
        if ($header) {
            return new SOAP_Header($name,'{urn:TreenoWebServices}DefinitionName',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:TreenoWebServices}DefinitionName',$inner);
    }
}
class DefinitionInfo {
	var $index;
	var $value;
	
    function DefinitionInfo($index = NULL, $value = NULL) {
        $this->index = $index;
        $this->value = $value;
    }

    function &__to_soap($name = 'DefinitionInfo', $header = false, $mustUnderstand = 0, $actor = 'http://schemas.xmlsoap.org/soap/actor/next') {
        $inner[] = new SOAP_Value('index','string',$this->index);
        $inner[] = new SOAP_Value('value','string',$this->value);
        if ($header) {
            return new SOAP_Header($name,'{urn:TreenoWebServices}DefinitionInfo',$inner,$mustUnderstand,$actor);
        }
        return new SOAP_Value($name,'{urn:TreenoWebServices}DefinitionInfo',$inner);
    }
}


?>