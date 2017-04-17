<?php
//print_r($argv);
//return;
error_log('CheckEnvStatus.php starts.');

//$incPath = ini_get('include_path');
ini_set('include_path', 'C:\Treeno\php\pear');

require_once '../db/db_common.php';
require_once '../lib/settings.php';
require_once("DocuSignHelper.php");	

function CheckEnvStatusPerDeptCab($dept, $configPath)
{	
	error_log('function CheckEnvStatusPerDeptCab('.$dept.', '.$configPath.') called.');
	$docuSignConfigFile = $configPath. '/'. $dept.'/docuSignSettings.xml';
	
	if(!ReadDocuSignConfig($docuSignConfigFile, $admin_mail, $admin_pass, $userActKey))
	{
		return;
	}
		
	$LoginRes = LogDocuSignServer($admin_mail, $admin_pass);
	//$logSuccess = $LoginRes->Success;
	$logSuccess = is_string($LoginRes)? false : $LoginRes->Success;	
		
	error_log("Logging to DocuSign: ".(string)$logSuccess);
	if (!$logSuccess) 
	{
		return;
	}
	
	$resAccount = $LoginRes->Accounts->Account;
	if(!is_array($resAccount))
	{
		$ApiAccountID = $resAccount->AccountID;		//40bedf24-70fd-4234-bf57-4263fa036605 = Api accountId
		//$AccountName = $response->LoginResult->Accounts->Account->AccountName;	//Treeno Software, Inc
		$UserName = $resAccount->UserName;			//Chris Zhang
		$email = $resAccount->Email;				//czhang@treenosoftware.com
		$ApiUserName = $resAccount->UserID;			//397fae8a-af31-45e2-bc03-3db45039f255 = api userName					
	}
	else
	{
		$ApiAccountID = $resAccount[0]->AccountID;	
		$UserName = $resAccount[0]->UserName;		
		$email = $resAccount[0]->Email;		
		$ApiUserName = $resAccount[0]->UserID;			
	}
		
	$config = array("userId" => '['.$userActKey.']'.$ApiUserName,	// '[TREE-b88ac21a-a6be-4bde-b342-c38933721a1e]397fae8a-af31-45e2-bc03-3db45039f255'
					"password" =>  $admin_pass,						//'Doc-4tron',	
					"accountId" => $ApiAccountID,					//'40bedf24-70fd-4234-bf57-4263fa036605',
					"subject" => '', 
					"blurb" => '');
						
	
	//if(!GetEnvelopesStatus($config, $UserName, $email, $docusignApi, $envelopesStatus))
	$resGetEnvelopesStatus = GetEnvelopesStatus($config, $UserName, $email, $docusignApi, $envelopesStatus);
	$success = is_string($resGetEnvelopesStatus)? false : $resGetEnvelopesStatus;	
	if(!$success)
	{
		return;
	}
	
	if(!is_array($envelopesStatus))
	{               	
		$arrEnvelopeStatus[] = $envelopesStatus;                
	}            
	else
	{                
		$arrEnvelopeStatus = $envelopesStatus;
	}
	                    	
	error_log("Request status returned ".count($arrEnvelopeStatus)." results. Will update database. ")	;		
	$db_object = getDbObject($dept);
	foreach ($arrEnvelopeStatus as $EnvelopeStatusResult)
	{       
		UpdateTreenoDBPerEnvelope($docusignApi, $EnvelopeStatusResult, $db_object, 'docusign');
		UpdateTreenoDBPerEnvelope($docusignApi, $EnvelopeStatusResult, $db_object, 'Lease');
	}				
}

$myPId = getmypid ();
error_log('Process ID ('.$myPId.') is written to: '.$DEFS['TMP_DIR'].'/checkEnvStatus.pid');

$fd = fopen ($DEFS['TMP_DIR'].'/checkEnvStatus.pid', 'w+');
fwrite ($fd, $myPId);
fclose ($fd);

$department = (isset($_GET['dept']))? $_GET['dept'] : 'client_files';
$eSign_cab = (isset($_GET['cab']))? $_GET['cab'] : 'docusign';
	
CheckEnvStatusPerDeptCab($department, $DEFS['MAPPING_DIR'] );

//add more cabinets here

?>													
