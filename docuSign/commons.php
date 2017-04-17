<?php
	
	function array2object($array)
	 {
	    if (is_array($array)) 
	    {
	        $obj = new StdClass();
	 
	        foreach ($array as $key => $val)
	        {
	            $obj->$key = $val;
	        }    
	    }
	    else { $obj = $array; }
	    return $obj;
	}
 
	function object2array($object) 
	{
	    if (is_object($object))
	     {
	        foreach ($object as $key => $value)
	        {
	            $array[$key] = $value;
	        }
	    }
	    else 
	    {
	        $array = $object;
	    }
	    return $array;
	}

	function object_to_array($object)
	{
	  if(is_array($object) || is_object($object))
	  {
		$array = array();
		foreach($object as $key => $value)
		{
		  $array[$key] = object_to_array($value);
		}
		return $array;
	  }
	  return $object;
	}
	
	
	/*	function curPageURL() 
	{
		 $pageURL = 'http';
		 //if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		 $pageURL .= "://";
		 if ($_SERVER["SERVER_PORT"] != "80") 
		 {
		  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		 } 
		 else 
		 {
		  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		 }
		 
		 $pageURL = str_replace("docuSignApply.php", "",$pageURL); 
		 
		 return $pageURL;  
	}*/

	
	function curPageURL() 
	{
		$pageURL = 'http';
	 	if (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) {$pageURL .= "s";}
	 	$pageURL .= "://";

	 	if ($_SERVER["SERVER_PORT"] != "80")
		{
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		}
		else
		{
		  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		
		return $pageURL;
	}
	
	function curPageName() {
 		return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
	}
	

	function curPagePath()
	{
		return str_replace(curPageName(), "", curPageURL() ); 
	}

	function validateEmail($email)
	{
		return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
	}
	
	function CheckSession($fromWhere)
	{
		$sessionID = session_id();	
		if($sessionID == '' ) //!session_id()
		{
			$res = session_start();
			error_log($fromWhere.": start a new session; Success: ".(string)$res);
			if($res)
			{
				$sessionID = session_id();
			}
		}
		error_log($fromWhere.": sessionID = ".$sessionID);
	}
	
	function ExtractFilenameFromFullFilePathName($fullFilePathName)
	{
		return substr($fullFilePathName, strrpos($fullFilePathName, '/') + 1, strlen($fullFilePathName) - strrpos($fullFilePathName, '/') - 1);
	}
	
	function ExtractFilePathFromFullFilePathName($fullFilePathName)
	{
		return substr($fullFilePathName, 0, strrpos($fullFilePathName, '/') );
	}
	
	
?>