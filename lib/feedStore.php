<?php
include_once '../db/db_common.php';


class feedStore
{
	//name of the feed to present to users
	var $feedName;
	//name of the feed file name
	var $feedFileName;
	//where the feed is being dropped on disk
	var $feedLocation;
	//descript of tabs in the feed
	var $translationArray;
	//which cabinet(s) it applies to
	var $cabs;
	//boolean append or create new auto_complete table
	var $append;

	var $department; 

	/*
	 * Constructor
	 */
	function feedStore( $name, 
						$feedFileName, 
						$feedLocation, 
						$translationArray,
						$cabs,
						$append,
						$department )
	{
		$this->feedName = $name;
		$this->feedFileName = $feedFileName;
		$this->feedLocation = $feedLocation;
		$this->translationArray = $translationArray;
		$this->cabs = $cabs;
		$this->append = $append;
		$this->department = $department;
	}

	function absorbFeed()
	{
		//checks if feed is available.
		if($this->feedExists())
		{
			$this->parseFeedIntoAutoComplete();
		}
		//if available parse line by line, and 
	}
	
	function feedExists()
	{
		return file_exists( $this->feedLocation."/".$this->feedFileName );
	}
	
	function deleteFeedFile()
	{
		//remove the feed file with unlink();
		if( file_exists( $this->feedLocation."/".$this->feedFileName)) {
			$feedLoc = $this->feedLocation."/".$this->feedFileName;
			copy ($feedLoc, '/tmp/feedList');
		}
//		unlink( $this->feedLocation."/".$this->feedFileName );
	}
	
	function parseFeedIntoAutoComplete() {
		//get cabInfos once
		$db_obj = getDbObject($this->department);
		//if append do not truncate the auto_complete<cab>s
		if( !$this->append ) {
			foreach( $this->cabs as $cab ) {
				$db_obj->query( "truncate table auto_complete_$cab" );
			}
		}
		$this->resetAutoCompleteFeed();
		foreach( $this->cabs as $cab ) {
			$arr = array();
			$autoCompleteInfo = $this->getAutoCompleteCabInfo($cab );
			foreach( $this->translationArray as $element ) {
				$myEl = strtolower ($element);
				if( in_array( $myEl, $autoCompleteInfo ) ) {
					//add column to array
					$arr[] = $myEl;
				}
			}
			insertFromSelect($db_obj, 'auto_complete_'.$cab, $arr, 'auto_complete_feeds', $arr);
		}
		$this->deleteFeedFile();
		$db_obj->disconnect ();
	}

	function resetAutoCompleteFeed()
	{
		//create table
		$create = "CREATE TABLE auto_complete_feeds ( ";
		//get size of array to create that number of fields
		$taSize = sizeof( $this->translationArray );
		for($i = 0; $i < sizeof($this->translationArray); $i++ )
		{
			if( $i+1 == $taSize )
			{
				$create .= $this->translationArray[$i]." VARCHAR(255) )";
			}
			else
			{
				$create .= $this->translationArray[$i]." VARCHAR(255), ";
			}
		}
		$load = "LOAD DATA LOCAL INFILE '".$this->feedLocation."/";
		$load .= $this->feedFileName."' INTO TABLE auto_complete_feeds ";
		$load .= "FIELDS TERMINATED BY '\\t' LINES TERMINATED BY '\\n'";

		$db_obj = getDbObject($this->department);
		dropTable($db_obj, "auto_complete_feeds");
		$db_obj->query( $create );
		$db_obj->query( $load );
		$db_obj->disconnect ();
	}

	function getCabInfos()
	{
		$db_obj = getDbObject ($this->department);
		foreach( $this->cabs as $cab )
		{
			$cabInfo["$cab"] = getCabinetInfo($db_obj, $cab);
		}
		$db_obj->disconnect ();
		return $cabInfo;
	}
	
	function getAutoCompleteCabInfo( $cab )
	{
		$db_obj = getDbObject ($this->department);
		$cabInfo = getCabinetInfo($db_obj, $cab); 
		$db_obj->disconnect ();
		return $cabInfo;
	}

	//TODO create auto_complete tables if they are not present
	//function create auto_complete tables
}


?>
