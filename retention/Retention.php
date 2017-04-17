<?php
/*
include_once '../db/db_common.php';
include_once '../lib/utility.php';
include_once '../groups/groups.php';
include_once '../documents/documents.php';
include_once '../lib/cabinets.php';
include_once '../settings/settings.php';
include_once '../lib/mime.php';
include_once '../lib/settings.php';

class Retention
{
	var $separator; //defaulted to "::"
	var $file; //name of ini file and handler
	var $filepath; //file path for ini
	var $cabinet; //cabinet to be run on
	var $destination; //action(delete, recycle, or workflow name)
	var $condition; //matching this criteria.

	var $dep;//department
	var $db_dept;//department db object
	var $db_doc;//docutron db object

	var $error_message;

	public function Retention(){ $this->Retention(NULL, NULL, NULL, NULL); }
	public function Retention($dep, $cabinet, $destination, $condition)
	{
		//set condtiion to an array
		$this->condition = array();
		//intialize a blank error message
		$this->error_message = "";
		//default filepath//
		$this->filepath = '/var/www/tools/retention.ini';
		//set data objects
		$this->dep = $dep;
		$this->db_doc = getDbObject('docutron');
		$this->db_dept = getDbObject($this->dep);

		//pull values and check for valid input
		$this->separator = "::";
		$this->cabinet = checkIsCabinet($cabinet);
		$this->destination = checkIsDestination($destination);
		$this->condition = checkIsCondition($condition);
	}

	public function postToFile()
	{
		if(is_null($this->cabinet) || is_null($this->destination) || is_null($this->condition))
		{
			$this->error_message = "Retention criteria is incomplete and cannot be created at this time.  Please try again.";
			return false;
		}
		$this->file = file_exists($this->filepath) ? fopen($this->filepath, 'w') : fopen($this->filepath, 'a');

		$tmpArr = array($this->cabinet, $this->destination, $this->$condition);
		$retention = implode($this->separator, $tmpArr);

		fwrite($this->file, $retention);
		fclose($this->file);
	}

	//check for valid cabinet
	private function checkIsCabinet($cabinet)
	{
		//if null argument return null
		if(is_null($cabinet))
		return NULL;
		//otherwise, check the department for this cabinet;
		if($cabID = getTableInfo($this->db_dept, 'departments', array('departmentid'), array('departmentname' => $this->cabinet), 'queryOne'))
		return $cabinet;
		else
		{
			$this->error_message .= "<br />Invalid cabinet name.  Please try again.";
			return NULL;
		}
	}

	private function checkIsDestination($destination)
	{
		//check destination is delete, recycle or wf name
		if(is_null($destination))
		return NULL;
		//otherwise, recycle, delete, or wf name;

		if(strtolower($destination) == "recycle" || strtolower($destination) == "delete")
		return strtolower($destination);
		//first we find out if this is a valid workflow
		else
		{
			if($workFlowID = getTableInfo($this->db_dept, 'wf_defs', array('id'), array('defs_name'=>$destination), 'queryOne'))
			return $destination;
			else
			{
				$this->error_message .= "<br />Invalid action or Workflow name.  Please try again.";
				return NULL;
			}
		}
	}

	//checks the condition for an array of both an index, value pair
	//and has an operator.
	private function checkIsCondition($condition)
	{
		//an array of valid operators//
		$operatorsArr = array("=", ">", "<");
		//split the string on '&&'
		$conditionArr = explode("&&", $condition);
		//has a valid operator in the string//
		$hasOperator = false;

		//loop through valid operators and check for at least one.
		//per condition
		foreach($operatorArr as $oper)
		{
			if(count($conditionArr) > 1)
			{
				foreach($conditionArr as $cond)
				{
					if(stristr($cond, $oper))
					{
						//this string contains at least one valid operator
						$hasOperator = true;
						continue;
					}
					$hasOperator = false;
				}
					
			}
			else
			{
				if(stristr($condition, $oper))
				{
					//this string contains at least one valid operator
					$hasOperator = true;
					break;
				}
			}
		}
		//we either has a valid condition or we don't
		//without a valid operator no action can take place.
		if(!$hasOperator)
		{
			$this->error_message .= "<br />One or more criteria conditions is invalid.  Please try again.";
			return NULL;
		}
		//if we have a valid condition and have made it this far, return.
		return trim($condition);
	}
}

}
*/
