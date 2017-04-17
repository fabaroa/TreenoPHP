<?PHP
include_once '../db/db_common.php';
include_once '../lib/utility.php';

class documentObj {
	var $department;
	var $cabinet;
	var $doc_id;
	var $db_dept;

	var $isDocument; //boolean denoting whether document is a document
	var $subfolderID;
	var $subfolderName;
	var $filesArr;
	var $documentID;
	var $documentTableName;
	var $documentType;
	var $documentTypeID;
	var $documentFieldValues;

	//$subfolderDBRow is the fetch row from the database
	function documentObj($department, $cabinet, $doc_id, $subfolderID, $db_dept=null)
	{
		if($db_dept == null) {
			$db_dept = getDbObject($department);
		}
		$this->db_dept = $db_dept;
		$this->department = $department;
		$this->cabinet = $cabinet;
		$this->doc_id = $doc_id;
		$this->subfolderID = $subfolderID;

		$this->buildDocObj();

		if( ($this->documentID > 0) AND is_numeric($this->documentID) ) {
			$this->isDocument = true;
			$this->populateDocInfo();
		} else {
			$this->isDocument = false;
		}
	}

	function buildDocObj() 
	{
		$subfolderInfo = getTableInfo($this->db_dept, $this->cabinet."_files",
			array(), array('id' => $this->subfolderID),
			'queryRow'
		);
		$this->subfolderName = $subfolderInfo['subfolder'];
		$this->documentID = $subfolderInfo['document_id'];
		$this->documentTableName = $subfolderInfo['document_table_name'];
		$cabinetFiles = getTableInfo($this->db_dept, $this->cabinet."_files",
			array(), 
			array("doc_id=".$this->doc_id, "subfolder='".$this->subfolderName."'", "filename IS NOT NULL")
		);

		$filesArr = array();
		while( $row = $cabinetFiles->fetchRow() ) {
			$filesArr[] = $row;
		}
		$this->filesArr = $filesArr;
	}

	function populateDocInfo()
	{
		$docInfo = getTableInfo($this->db_dept, 'document_type_defs',
			array('document_type_name','id'),
			array('document_table_name' => $this->documentTableName),
			'queryRow'
		);
		$this->documentType = $docInfo['document_type_name'];
		$this->documentTypeID = $docInfo['id'];

		$fieldArr = array();
		$wArr = array( 'document_defs_list_id' => $this->documentTypeID,
					'document_id' => $this->documentID);
		$fieldInfo = getTableInfo($this->db_dept, 'document_field_value_list', array(), $wArr);
		while($row = $fieldInfo->fetchRow()) {
			$fieldArr[] = $row;
		}
		$this->documentFieldValues = $fieldArr;
	}
}
?>
