<?php

include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../groups/groups.php';
include_once '../documents/documents.php';
include_once '../lib/cabinets.php';
include_once '../settings/settings.php';
include_once '../lib/mime.php';
include_once '../lib/settings.php';

class RetentionParser
{
	var $cabinet;
	var $destination;
	var $condition;
	var $fileArr;

	var $dep;//department
	var $db_dept;//department db object
	var $db_doc;//docutron db object
	//constructor creates the retention to parse
	public function RetentionParser($dep, $string)
	{
		$elemArr = explode("::", $string);
		$this->cabinet = $this->destination = $this->condition = NULL;
		$this->fileArr = array();
		
		$this->dep = $dep;
		$this->db_doc = getDbObject('docutron');
		$this->db_dept = getDbObject($this->dep);
		
		if(count($elemArr) > 0)
		{
			$this->cabinet = $this->getCabinetName(trim($elemArr[0]));
			$this->destination = trim($elemArr[1]);
			$this->condition = $elemArr[2];
		}
	}
	
	//this function finds the real cabinet name, gets the files that match, 
	//and performs whatever operation is listed within the condition(s)
	function parse()
	{
		if(is_null($this->cabinet) || is_null($this->destination) || is_null($this->condition))
		{
			return false;
		}	
		
		$this->getTaggedFiles(); //get all matching files to the cabinet and criteria. 
		$this->performRetention();
	}
	
	function getCabinetName($cab)
	{
		if($cabinetName = getTableInfo($this->db_dept, 'departments', array('real_name'), array('departmentname'=> $cab), 'queryOne'))
			return $cabinetName;	
	}
	
	private function getTaggedFiles()
	{
		$table = $this->cabinet;
		$wArr = array();
	
		$conditions = explode("&&", $this->condition);
		$wArr[] = "deleted = 0";
		
		if(is_array($conditions) && count($conditions) > 0)
		{
			for($i = 0; $i < count($conditions); $i++)
			{
				if(!stristr($conditions[i], "date_created"))
					$wArr[] = $conditions[$i];
			}
		}elseif(!stristr($this->condition, "date_created")){
			$wArr[] = $this->condition;
		}
		$sArr = array('doc_id', 'location');
		
		$docs = getTableInfo($this->db_dept, $this->cabinet, $sArr, $wArr, 'queryAll');
		foreach($docs as $document)
		{
			$sArr = $wArr = array();			
			$location = str_replace(' ', '/', $document['location']);
			$docID = $document['doc_id'];	
			//set what to select
			$sArr = array('id', 'filename', 'date_created');
			//set the where 
			$wArr[] = 'deleted = 0';
			$wArr[] = 'doc_id = "'.$docID.'"';
			//if the condition includes the date created
			if(stristr($this->condition, "date_created"))
			{
				if(is_array($conditions) && count($conditions) > 0)
				{
					for($i = 0; $i < count($conditions); $i++)
					{
						if(stristr($conditions[i], "date_created"))
							$wArr[] = $conditions[$i];
					}
				}
				else
				{
					$wArr[] = $this->condition;
				}
			}			
			$filesInFolder = getTableInfo($this->db_dept, $this->cabinet."_files", $sArr, $wArr, 'queryAll');
			foreach($filesInFolder as $folderFile)
			{	
					$this->fileArr[] = array(
							'file_id' => $folderFile['id'],
							'doc_id' => $docID,
							'filename' => '/'.$location.'/'.$folderFile['filename'],
							'date_created' => $folderFile['date_created']
					);
			}	
		} 
	}
	//this function will evaluate the destination
	//(delete, recycle or workflow)
	private function performRetention()
	{
		$func = strtolower($this->destination);
		foreach($this->fileArr as $file)
		{
			if($func == 'delete' || $func == 'recycle')
				$this->$func($file);
			else die('Invalid Function Call');
		}	
	}
	//update the file deleted field record from zero to one 
	private function recycle($file)
	{
		if(is_array($file))
		{
			if(updateTableInfo($this->db_dept, $this->cabinet."_files", array('deleted'=>'1', 'display'=>'0'), array('id'=>$file['file_id'], 'doc_id'=>$file['doc_id'])))
			print('Recycled File: '.$file['filename']);
		}
		else die('Invalid Array');
	}
	//delete the file
	private function delete($file)
	{
		if(is_array($file))
		{
			//check that the current file exists
			if(file_exists($file['filename']))
			{
				//delete the record in the table and delte the file from the system
				deleteTableInfo($this->db_dept, $this->cabinet.'_files', array('id'=>(int)$file['file_id'], 'doc_id'=>(int)$file['doc_id']));
				unlink($file['filename']);
				//ok
				return true;
			}
		}
				
		
		return false;
	}
	
	
}