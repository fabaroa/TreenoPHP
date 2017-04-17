<?php
class document {
	var $table_name;//this is the document real name
	var $type_name;//this is the document arb name

	var $defs_id;//id of the document table in the document_type_defs table
	var $document_id;//id of the document table that points to a specific document
	
	var $field_names;//associative array of fields and corresponding values
	var $real_field_names;//associative array of fields and corresponding values
	var $field_values;//associative array of fields and corresponding values
	
	var $department;//department that the document is in
	var $cabinet;//cabinet that the document is in
	var $doc_id;//folder that the document is in
	var $file_id;//file id of the cabinet_files table for the subfolder
	
	var $db;//database connection

	function document($table_name,$cabinet,$doc_id,$file_id,$db_dept) {
		$this->table_name = $table_name;
		$this->cabinet = $cabinet;
		$this->doc_id = $doc_id;
		$this->file_id = $file_id;
		$this->db = $db_dept;
		$this->getDocumentTypeInfo();
		$this->getCabinetInfoForDocument();
		$this->getFieldsForDocument();
	}

	function getDocumentTypeInfo() {
		$sArr = array('id','document_type_name');	
		$whereArr = array('document_table_name' => $this->table_name);
		$docInfo = getTableInfo($this->db,'document_type_defs',$sArr,$whereArr,'queryRow');
		$this->type_name = $docInfo['document_type_name'];
		$this->defs_id = $docInfo['id'];
	}

	function getFieldsForDocument() {
		$sArr = array('document_field_defs_list_id','document_field_value');	
		$wArr = array(	"document_defs_list_id" => $this->defs_id,
						'document_id' => $this->document_id);
		$fieldInfo = getTableInfo($this->db,'document_field_value_list',$sArr,$wArr,'getAssoc');

		$sArr = array('id','real_field_name','arb_field_name');
		$wArr = array('document_table_name' => $this->table_name);
		$oArr = array('ordering' => 'ASC');
		$docInfo = getTableInfo($this->db,'document_field_defs_list',$sArr,$wArr,'getAssoc',$oArr);

		foreach($fieldInfo AS $id => $value) {
			$this->field_names[$id] = $docInfo[$id]['arb_field_name'];
			$this->real_field_names[$id] = $docInfo[$id]['real_field_name'];
			$this->field_values[$id] = $value;
		}
	}

	function getCabinetInfoForDocument() {
		$sArr = array('id');
		$whereArr = array(	'cab_name'	=> $this->cabinet,
							'doc_id'	=> (int)$this->doc_id,
							'file_id'	=> (int)$this->file_id );
		$this->document_id = getTableInfo($this->db,$this->table_name,$sArr,$whereArr,'queryOne');
	}

	function setFieldForDocument($field,$value, $useRealNames = false) {
		if ($useRealNames) {
			$id = array_search($field,$this->real_field_names);
		} else {
			$id = array_search($field,$this->field_names);
		}

		$updateArr['document_field_value'] = $value;
		$whereArr = array(	'document_id'					=> (int)$this->document_id,
							'document_defs_list_id'			=> (int)$this->defs_id,
							'document_field_defs_list_id'	=> (int)$id );
		updateTableInfo($this->db,'document_field_value_list',$updateArr,$whereArr);
	}

	function unsetDocumentForCabinetFiles() {
		$updateArr = array(	'document_id'			=> 0,
							'document_table_name'	=> '');
		$whereArr = array('id'	=> $this->file_id);
		updateTableInfo($this->db,$this->cabinet.'_files',$updateArr,$whereArr);
		$this->file_id = "";
	}

	function setDocumentForCabinetFiles($id) {
		$updateArr = array(	'document_id'			=> (int)$this->document_id,
							'document_table_name'	=> $this->table_name);
		$whereArr = array('id'	=> (int)$id);
		updateTableInfo($this->db,$this->cabinet.'_files',$updateArr,$whereArr);
		$this->file_id = $id;
	}

	function setSubfolderForDocument($file_id) {
		$updateArr = array('file_id' => (int)$file_id);
		$whereArr = array(	'cab_name'	=> $this->cabinet,
							'doc_id'	=> (int)$this->doc_id,
							'file_id'	=> (int)$this->file_id);
		updateTableInfo($this->db,$this->table_name,$updateArr,$whereArr);
	}

	function removeSubfolderFromDocument() {
		$whereArr = array(	'cab_name'	=> $this->cabinet,
							'doc_id'	=> (int)$this->doc_id,
							'file_id'	=> (int)$this->file_id);
		deleteTableInfo($this->db,$this->table_name,$whereArr);
	}

	function unsetDocumentFields() {
		$whereArr = array(	'document_id'			=> (int)$this->document_id,
							'document_defs_list_id'	=> (int)$this->defs_id);
		deleteTableInfo($this->db,'document_field_value_list',$whereArr);
	}

	function addSubfolderToDocument($username) {
		$date = date('Y-m-d G:i:s');
		$insertArr = array(	'cab_name'		=> $this->cabinet,
							'doc_id'		=> (int)$this->doc_id,
							'file_id'		=> (int)$this->file_id,
							'date_created'	=> $date,
							'date_modified'	=> $date,
							'created_by'	=> $username );
		$res = $this->db->extended->autoExecute($this->table_name,$insertArr);
		dbErr($res);
	}

	function addFieldForDocument($field,$value) {
		$id = array_search($field,$this->field_names);
		$insertArr = array(	'document_id'					=> (int)$this->document_id,
							'document_defs_list_id'			=> (int)$this->defs_id,
							'document_field_defs_list_id'	=> (int)$id,
							'document_field_value'			=> $value);
		$res = $this->db->extended->autoExecute('document_field_value_list',$insertArr);
		dbErr($res);

	}
}
?>
