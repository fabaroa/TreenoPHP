#!/usr/bin/php -q
<?php
include_once '../db/db_engine.php';
include_once '../classuser.inc';
include_once '../settings/settings.php';
include_once '../search/fileSearch.php';

//This is a bot  to perform context searching
// calculate fuzzy logic criteria for a match  
$cab = $argv[1];
$contextList = strtolower($argv[2]);
$temp_table1 = $argv[3];
$user = unserialize(base64_decode($argv[4]));
$mythis = unserialize(base64_decode($argv[5]));
$temp_table2 = $argv[6];
$counthits = $argv[7] ; // bool telling to count hits or not
$db_name = $user->db_name;
$datasource = "$db_engine://$db_username:$db_password@$db_host/$db_name";
$db_object = MDB2::factory($datasource, TRUE);
$db_object->loadModule ('Extended');

$gbl = new Usrsettings($user->username, $user->db_name );

	    if ($search_type==0)
	        $criteria="400";
	    else
			$criteria="900";

	    // temporary hard coded search criteria
	    $criteria="800";
	    $results=getIDOCRfromCab($db_object,$cab,$temp_table1);

	    $index=0;
        $spot=0;
	    while ($row=$results->fetchRow()) 
	    {

	        $OCR=$row[1];
            $OCR=strtolower($OCR);
			// add it to the temp table if it has no OCR field entry
			if ($OCR == ""||$contextList=="")
 			{
			   // $match[$index++]=$row[0];
			}
			else		//do OCR context fuzzy logic search
			{	
			    $file_match=0;
			    $TEXTwords = preg_split("/[\s]+/", $OCR);
    		    $SEARCHwords = preg_split("/[\s\W]+/", $contextList);
	
		    	foreach ($TEXTwords as $hkey => $hayword)
			    {
    	    	    $hmp = metaphone ($hayword);
	        	    foreach ($SEARCHwords as $nkey => $needword) 
    	        	{              
	    	        // First or last letters of needle and haystack have to
    	    	    // match (case insensitive)
        	    	    $nfirst = strtolower(substr($needword, 0, 1));
	            	    $nlast = strtolower(substr($needword, -1));
    	            	$hfirst = strtolower(substr($hayword, 0, 1));
	    	            $hlast = strtolower(substr($hayword, -1));
    	    	        $nmp = metaphone ($needword);
	        	        $distance = levenshtein ($hayword, $needword);
    	        	    $n_len = strlen($nmp);
						if($n_len > 0)
        	        		$per = round(($distance/$n_len)*1000);
						else
							$per = 0;
		                if ($per <= $criteria) 
    		            {
					    	// Word is a match
            		    	$file_match++ ;

							if(!$counthits) // we know this is a match
								break 2 ; // so jump out and save time
                		}
		            }	
			    }
	
		    	if ($file_match) 
			    {
				    if($counthits){ // put right into result table
					$insertArr = array(
						"hits"		=> (int)$file_matchm, 
						"result_id"	=> (int)$row[0]
							  );
					$res = $db_object->extended->autoExecute($temp_table2,$insertArr);
					}
					else // set up array for the speed increase
						$match[$index]=$row[0];
		    	}
			}

			// this is building the temp_table2, only need for not counting hits
			if(!$counthits){ 
				if($index == 0)  //setup the temp table
				{
					$query3 = "insert into $temp_table2(result_id) select result_id from $temp_table1 where";
            		if($match[$index] != "")
        	    		$query3 .= " result_id = ".$match[$index]." or";
				}
	            else if(($index % 50) == 0 && $index != 0) //every 50 iterations update the temp_table
				{
					$pos = strrpos($query3, "o");
	                if(substr($query3, $pos) == "or")
						$query3 = substr_replace($query3, "", $pos, 2);
	    	    	$res = $db_object->query($query3);
			dbErr($res);
					$query3 = "insert into $temp_table2(result_id) select result_id from $temp_table1 where ";
        	        if($match[$index] != "")
    			    	$query3 .= " result_id = ".$match[$index]." or";
				}
				else
				{	
            		if($match[$index] != "")
        	    		$query3 .= " result_id = ".$match[$index]." or";
				}
			}

			$index++;
		} 

        if(($index % 50) != 0 && !$counthits){
			$pos = strrpos($query3, "o");
            if(substr($query3, $pos) == "or")
				$query3 = substr_replace($query3, "", $pos, 2);
			$res = $db_object->query($query3);
			dbErr($res);
//fwrite($thefp, "not weird - $query3\n\n") ; // ERROR
        }
$gbl->set('context','done');

//$ttt = explode(" ", microtime());//ERROR
//$endtime = (float)$ttt[0] + (float)$ttt[1] ;//ERROR
//fwrite($thefp, "\n\n".($endtime-$starttime)) ;//ERROR
//fclose($thefp) ; //ERROR
?>
