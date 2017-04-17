<?php
ini_set("log_errors", 1);
ini_set("error_log", "C:/Treeno/Logs/IP Restrictions.log");
include_once '../check_login.php';
include_once '../modules/modules.php';
include_once '../settings/settings.php';
require_once '../db/db_common.php';
require_once '../lib/webServices.php';
	$publicIP=GetPublicIP ();
	echo '<!DOCTYPE html><html><head>
			<style>
			* {font-family: Tahoma,Verdana,sans-serif;}
			h3 {color: #517CA3; line-height: 90%;}
			h2 {font-size: 16px; color: #006A5A; line-height: 90%;}
			h1 {font-size: 26px; color:#97A1AA;line-height: 90%;}
			table {border-collapse: collapse;width: 50%;}
			table.center {margin-left:auto;margin-right:auto;}
			td, th {border: 1px solid #517CA3;text-align: center;padding: 8px;}
			tr:nth-child(even) 
			{background-color: #CFDBE6;}</style></head>';

	if($_POST['UserReport_button']){
		$dept=$user->db_name;
		$db_doc = getDbObject ('docutron');
		$time=date('y_m_d_h_i_s');
		echo '<center><br><a href="https://tr2.treenosoftware.com:4443/rest/IPRestrictionsReports/'.$time.'_IP_Restrictions_'.$dept.'_Report.csv">Download Report</a></center><br>';	
			
		$select="SELECT * FROM [user_settings] where k ='ip_restriction' 
		and department='".$dept."' order by username";
		echo'<table class="center" ><tr><th>Username</th><th>Current IP or Range</th></tr>';
		$IPRestrictions = $db_doc->queryAll($select);
		$csvData="Username,IP Address \n";
		foreach($IPRestrictions as $IPRestriction){
			
			$IPRestrictionsArray=explode(",",$IPRestriction['value']);
			$x=0;
			foreach($IPRestrictionsArray as $IPAddress){
				$x++;
				echo"<tr><td>".$IPRestriction['username']."</td>
				<td>".$IPAddress."</td></tr>";	
				$csvData=$csvData.$IPRestriction['username'].",".$IPAddress."\n";		
			}		
		}
		$fp = fopen("C:/Treeno/treeno/rest/IPRestrictionsReports/".$time."_IP_Restrictions_".$dept."_Report.csv","a+");
			fwrite($fp,$csvData);
			fclose($fp);
		
	}elseif($_POST['submit_button']||isset($_SESSION['IPCount'])){
		$dept=$user->db_name;
		$db_doc = getDbObject ('docutron');
		//$message=$_SESSION['IPCount'];
		if(array_key_exists("Insert_New_button",$_POST)){
			$NewIP=$_POST['NewIP'];
			if(isvalidIP($NewIP)){
				if($_SESSION['IPRestrictions']==''){
					$query="Insert into [user_settings] values('".$_POST['deptusers']."','ip_restriction','".$NewIP."','".$dept."')";
					$db_doc->query($query);
					$_SESSION['IPCount']=1;
					$_SESSION['IPRestrictions']=$NewIP;
					$message="<h2>NEW IP or RANGE ADDED:".$NewIP."</h2>";
				}elseif(!strstr(",".$_SESSION['IPRestrictions'].",",",".$NewIP.",")){
					$currentIPRest=$NewIP.",".$_SESSION['IPRestrictions'];
					$update="update [user_settings] set value='".$currentIPRest."' where k ='ip_restriction' 
					and username='".$_POST['deptusers']."' and department='".$dept."'";	
					$db_doc->queryAll($update);
					$_SESSION['IPCount']++;
					$_SESSION['IPRestrictions']=$currentIPRest;
					$message="<h2>NEW IP or RANGE ADDED: ".$NewIP."</h2>";		
				}else{
					$message="<h2>IP or RANGE ALREADY EXISTS: ".$NewIP."</h2>";
				}	
			}else{
				$message="<h2>'".$NewIP."' IS NOT A VALID IP OR RANGE</h2>";	
			}
		}elseif(array_key_exists("Insert_New_For_All_button",$_POST)){
			$NewIP=$_POST['NewIP'];
			if(isvalidIP($NewIP)){
				//get userlist
				$deptusers =getDeptUsers($dept,$db_doc,$user);
				//loop through restrictions
				$message="<h2>New IP: ".$NewIP." added<br>FOR: ";
				foreach($deptusers as $deptuser){
					//not for current user, treenosupport users, or admin
					if($deptuser['username'] !='admin' && $deptuser['username'] !=$user->username && !strstr($deptuser['username'],'TreenoSupport') ){
						$select="SELECT value FROM [user_settings] where k ='ip_restriction' 
						and username='".$deptuser['username']."' and department='".$dept."'";
						$IPRestrictions = $db_doc->queryOne($select);
						//need to update users with restrictions
						if($IPRestrictions!=''){
							//only add the restriction to users who do not already have the restriction
							if(!strstr(",".$IPRestrictions.",",",".$NewIP.",")){
								$currentIPRest=$NewIP.",".$IPRestrictions;
								$currentIPRest=trim($currentIPRest,",");
								$update="update [user_settings] set value='".$currentIPRest."' where k ='ip_restriction' 
								and username='".$deptuser['username']."' and department='".$dept."'";
								$db_doc->queryAll($update);
								//$message="<h2>UPDATED:".$ipToUpdate."<br> TO:".$NewIP."</h2><br>";
								$message=$message.$deptuser['username']." ";
							}
						}else{
							$query="Insert into [user_settings] values('".$deptuser['username']."','ip_restriction','".$NewIP."','".$dept."')";
							$db_doc->query($query);
							$message=$message.$deptuser['username']."<br>";
						}
					}
				}
			}else{
				$message="<h2>'".$NewIP."' IS NOT A VALID IP OR RANGE</h2>";	
			}
			
		}elseif($_SESSION['IPCount']>0){
			//we need to add an insert statement if needed
			// and a delete statement if there are no longer any ip restrictions
			for ($x = 1; $x <= $_SESSION['IPCount']; $x++) {
				if(array_key_exists("Delete_button".$x,$_POST)){
					// need to delete the entry
					$ipToDelete=$_POST['IP'.$x];
					$currentIPRest=str_replace(",".$ipToDelete.",",",",",".$_SESSION['IPRestrictions'].",");
					$currentIPRest=trim($currentIPRest,",");
					if($currentIPRest==''){
						$delete="delete from [user_settings] where k ='ip_restriction' 
						and username='".$_POST['deptusers']."' and department='".$dept."'";
						$db_doc->query($delete);
						$_SESSION['IPCount']=0;
					}else{
						$update="update [user_settings] set value='".$currentIPRest."' where k ='ip_restriction' 
						and username='".$_POST['deptusers']."' and department='".$dept."'";
						$db_doc->queryAll($update);
						$_SESSION['IPCount']--;
					}
					$message="<h2>DELETED: ".$ipToDelete."</h2><br>";
				}elseif(array_key_exists("Update_button".$x,$_POST)){
					//need to update old IP to new IP
					$ipToUpdate=$_POST['IP'.$x];
					$NewIP=$_POST['UpdateValue'.$x];
					if(isvalidIP($NewIP)){
						$currentIPRest=str_replace(",".$ipToUpdate.",",",".$NewIP.",",",".$_SESSION['IPRestrictions'].",");
						$currentIPRest=trim($currentIPRest,",");
						$update="update [user_settings] set value='".$currentIPRest."' where k ='ip_restriction' 
						and username='".$_POST['deptusers']."' and department='".$dept."'";
						$db_doc->queryAll($update);
						$message="<h2>UPDATED:".$ipToUpdate."<br> TO:".$NewIP."</h2>";
					}else{
						$message="<h2>'".$NewIP."' IS NOT A VALID IP OR RANGE</h2>";
					}
				}elseif(array_key_exists("UpdateAll_button".$x,$_POST)){
					$ipToUpdate=$_POST['IP'.$x];
					$NewIP=$_POST['UpdateValue'.$x];
					
					if(isvalidIP($NewIP)){
						//need to get all users
						$deptusers =getDeptUsers($dept,$db_doc,$user);
						//need to loop through all IP_restrictions
						$message="<h2>UPDATED:".$ipToUpdate."<br> TO:".$NewIP."<br> FOR:";
						foreach($deptusers as $deptuser){
							$select="SELECT value FROM [user_settings] where k ='ip_restriction' 
							and username='".$deptuser['username']."' and department='".$dept."'";
							$IPRestrictions = $db_doc->queryOne($select);
							//need to update users with restrictions
							if($IPRestrictions!=''&& strstr(",".$IPRestrictions.",",",".$ipToUpdate.",")){
								
								$currentIPRest=str_replace(",".$ipToUpdate.",",",".$NewIP.",",",".$IPRestrictions.",");
								$currentIPRest=trim($currentIPRest,",");
								$update="update [user_settings] set value='".$currentIPRest."' where k ='ip_restriction' 
								and username='".$deptuser['username']."' and department='".$dept."'";
								$db_doc->queryAll($update);
								//$message="<h2>UPDATED:".$ipToUpdate."<br> TO:".$NewIP."</h2><br>";
								$message=$message.$deptuser['username']." ";
							}
						}
					}else{
						$message="<h2>'".$NewIP."' IS NOT A VALID IP OR RANGE</h2>";
					}
				}
			} 
		}else{
			$message="No Restrictions setup yet";
		}
		
		//echo'Submit button selected for user '.$_POST['deptusers'];
		

		echo "<body>
		
			 <form action='".$_SERVER['PHP_SELF']."' method = 'POST'><center><input name='UserReport_button' type='submit' value='Run IP User Permission Report'>
			 <br><br><h3>CURRENT IP: ".$publicIP."</h3>
			 <h1>WHITE LIST IP ACCESS RESTRICTIONS FOR: ".$_POST['deptusers']."</h1>
			 USER:
			  <select name='deptusers'>";
		$deptusers =getDeptUsers($dept,$db_doc,$user);
		echo '<option selected="'.$_POST['deptusers'].'">'.$_POST['deptusers'].'</option>';
		foreach($deptusers as $deptuser){
			if($deptuser['username'] !='admin' && $deptuser['username'] !=$user->username && !strstr($deptuser['username'],'TreenoSupport') ){
				echo '<option value="'.$deptuser['username'].'">'.$deptuser['username'].'</option>';
			}
		} 
		echo'</select><input name="submit_button" type="submit" value="Submit"><br><br>';
		//table here
		$select2="SELECT value FROM [user_settings] where k ='ip_restriction' 
		and username='".$_POST['deptusers']."' and department='".$dept."'";
		echo'<table class="center" ><tr><th>Current IP or Range</th><th>New IP or Range</th><th colspan="3">(Sample Range Format: 255.255.255.0-255.255.255.255)</th></tr>';
		$IPRestrictions = $db_doc->queryOne($select2);
		if($IPRestrictions!=''){
			
			$IPRestrictionsArray=explode(",",$IPRestrictions);
			$x=0;
			foreach($IPRestrictionsArray as $IPRestriction){
				$x++;
				echo"<tr><td><input name='IP".$x."' maxlength ='31' size='24' type='text' value='".$IPRestriction."' readonly='readonly'/></td>
				<td><input name='UpdateValue".$x."'  maxlength ='31' size='24' type='text'onkeypress='javascript:return (event.keyCode != 13)'/></td>
				<td><input name='Delete_button".$x."' type='submit' value='Delete'></td>
				<td><input name='Update_button".$x."' type='submit' value='Update'></td>
				<td><input name='UpdateAll_button".$x."' type='submit' value='Update All Users'></td></tr>";	
			}
				
		}else{
			$message=$message."<h2>No IP Restrictions currently defined</h2>";
		}
		$_SESSION['IPRestrictions']=$IPRestrictions;
		$_SESSION['IPCount']=$x;
		echo"<tr style= 'background-color:#517CA3;'><td><font color=#FFFFFF><b>NEW IP OR RANGE:</b></td><td><input name ='NewIP'  maxlength ='31' size='24' type='text' onkeypress='javascript:return (event.keyCode != 13)'/></td>		
				<td colspan='1.5'><input name='Insert_New_button' type='submit' value='Insert New' ></td><td><input name='Insert_New_For_All_button' type='submit' value='Insert New For All' ></td><td></td></tr>";
		echo '</table>';
		
		echo'</form></body>';
	}elseif($logged_in && $user->username) {//this is the initial user select screen
		$dept=$user->db_name;
		$db_doc = getDbObject ('docutron');
		$_SESSION['IPCount']=1;
		echo "<body>
			 <form action='".$_SERVER['PHP_SELF']."' method = 'POST'><center>CURRENT IP: <h2>".$publicIP."</h2><br>
			 <h1>WHITE LIST IP Access Restrictions</h1><br>
			  USER:
			  <select name='deptusers'>";
		$deptusers =getDeptUsers($dept,$db_doc,$user);
		echo '<option value="">Select User...</option>';
		foreach($deptusers as $deptuser){
			if($deptuser['username'] !='admin' && $deptuser['username'] !=$user->username && !strstr($deptuser['username'],'TreenoSupport') ){
			echo '<option value="'.$deptuser['username'].'">'.$deptuser['username'].'</option>';	
			}
			
		} 
		echo'</select><input name="submit_button" type="submit" value="Submit"><center></form></body>';
	}else{
		 echo'LOGIN ERROR PLEASE LOG OUT AND BACK IN AND TRY AGAIN ';
	}
	//print_r($_POST);
	//echo "IP Count:".$_SESSION['IPCount'];
	//print_r($_SESSION);
	//echo $update;
	 echo$message.'</center></html>';
	 function isvalidIP($IP){
		 $valid=true;
		 if(strstr($IP,"-")){
			$IPs=explode("-",$IP);
		 }else{
			$IPs[]=$IP;
		 }
		 foreach($IPs as $address){
			if(filter_var($address,FILTER_VALIDATE_IP)===false){
				$valid=false;
			}			
		 }
		 return $valid;
	 }
	 function getDeptUsers($dept,$db_doc,$user){
		 $select="select a.username,a.db_list_id,b.id,b.db_name from [docutron].[dbo].[users] a inner join
		[docutron].[dbo].[db_list] b  on a.db_list_id = b.list_id where b.db_name='".$dept."'";
		if(!$user->isSuperUser()){
			$select=$select." and db_list_id !=1";
		}
		$select=$select." order by username";
		$deptusers = $db_doc->queryAll($select);
		return $deptusers;
	 }
	 function GetPublicIP (){
		 if(!empty($_SERVER['HTTP_CLIENT_IP']))
		{
				//check ip from share internet
				$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
				//to check ip is pass from proxy
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else{
				$ip = $_SERVER['REMOTE_ADDR'];
			}

		return  $ip;
	}
?>
