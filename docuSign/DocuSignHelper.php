<?php
include_once '../lib/crypt.php';
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once ('../classuser.inc');

include_once("commons.php");
include_once("Envelope.php");	

function NeedProcessEnvelopeStatusResult($EnvelopeStatusResult)
{
	//error_log("NeedProcessEnvelopeStatusResult"); 
	$result = false;
	if ( array_key_exists("Voided", $EnvelopeStatusResult) 		||
		 array_key_exists("Sent", $EnvelopeStatusResult) 		||
		 array_key_exists("Delivered", $EnvelopeStatusResult) 	||
		 array_key_exists("Signed", $EnvelopeStatusResult)		||
		 array_key_exists("Declined", $EnvelopeStatusResult)	||
		 array_key_exists("Completed", $EnvelopeStatusResult) )
	{
		$result = true;
	}
	return $result;
}

function ReadDocuSignConfig($docuSignConfigFile, &$admin_mail, &$admin_pass, &$userActKey)
{
	$bRes = false;
	try 
	{						
		$domDoc = DomDocument::load( $docuSignConfigFile );
		$domAccnts = $domDoc->getElementsByTagName( 'DocuSignAccount' );
	
		//$domApiAccountID =  $domAccnts->item(0)->getElementsByTagName('ApiAccountID');
		//$ApiAccountID = $domApiAccountID->item(0)->nodeValue;
								
		//$domApiUserName =  $domAccnts->item(0)->getElementsByTagName('ApiUserName');
		//$ApiUserName = $domApiUserName->item(0)->nodeValue;
		
		$domActKey =  $domAccnts->item(0)->getElementsByTagName('ActKey');
		$userActKey = $domActKey->item(0)->nodeValue;
		
		$domEmail =  $domAccnts->item(0)->getElementsByTagName('Email');//'ApiUser'
		$admin_mail = $domEmail->item(0)->nodeValue;
															
		$domPassword =  $domAccnts->item(0)->getElementsByTagName('Password');
		$encPassword = $domPassword->item(0)->nodeValue;
		$admin_pass = tdDecrypt($encPassword);
		$bRes = true;
	}
	catch (Exception $e)
	{		
		error_log("Exception thrown when reading docusign configuration file: ".$docuSignConfigFile);
		error_log("Exception message: ".$e->getMessage());
	    //echo("SoapClient exception");
	}	
	return $bRes;				
}

function LogDocuSignServer($admin_mail, $admin_pass)
{
	$credUrl = "https://www.docusign.net/API/3.0/Credential.asmx?WSDL";
	//xdebug_disable();//Xdebug messe up with fatal to exception error conversions with PHP SoapClient
	
	try
	{				
		//$client = new SOAP_WSDL("https://demo.docusign.net/API/3.0/Credential.asmx?WSDL");//SOAP_Client - pear, cmd
		$client = new SoapClient($credUrl);
	}
	catch (SoapFault $e)
	{		
		error_log("SoapFault exception occured in File:  ".$e->getFile().": ".$e->getLine().", ".$e->faultcode);
		error_log($e->getMessage());
	}
	//xdebug_enable();
			
	if(!isset($client))
	{
		return "Failed to initialize SoapClient from \"".$credUrl."\"";
	}
	
	//$admin_pass = "wrongpwd";
	$arr = array("Email" =>$admin_mail, "Password" => $admin_pass);
	$response = $client->Login($arr);
		
	return $response->LoginResult;
}

function GetEnvelopesStatus($config, $UserName, $email, &$dsApi, &$envelopesStatus)
{
	$apiUrl = "https://www.docusign.net/api/3.0/api.asmx?wsdl";
	//xdebug_disable();//Xdebug messe up with fatal to exception error conversions with PHP SoapClient
	
	try
	{	
		//$config["password"]	= "wrongpwd";		
		$dsApi = new Docusign_Envelope($apiUrl, $config);
	}			
	catch (SoapFault $e)
	{		
		error_log("SoapFault exception occured in File:  ".$e->getFile().": ".$e->getLine().", ".$e->faultcode);
		error_log($e->getMessage());
	}
	//xdebug_enable();
			
	if(!isset($dsApi))
	{
		return "Failed to initialize DocuSign API from \"".$apiUrl."\"";
	}
	
	//xdebug_disable();
	$errRequestStatuses = "";
	try
	{	
		//$config["password"]	= "wrongpwd";		
		//$dsApi = new Docusign_Envelope($apiUrl, $config);
						
		$RequestStatusesParams = array();				
		$RequestStatusesParams["EnvelopeStatusFilter"]["UserInfo"]["UserName"] = $UserName;	//$_SESSION["UserName"];
		$RequestStatusesParams["EnvelopeStatusFilter"]["UserInfo"]["Email"] = $email;		// $_SESSION["email"];
		$RequestStatusesParams["EnvelopeStatusFilter"]["AccountId"] = $config['accountId'];	//$ApiAccountID;		//$_SESSION["AccountID"];
		$RequestStatusesParams["EnvelopeStatusFilter"]["Statuses"]["Status"] =  "Any";//"Completed";//
		//$RequestStatusesParams["EnvelopeStatusFilter"]["BeginDateTime"] =  mktime(0, 0, 0, date("m"), date("d"),   date("Y"));
		//$myDateTime = date('Y-m-d G:i:s');
										
		//testing
		//$_SESSION["EnvelopeId"] = 'faf37b16-0985-4309-bb44-8a6174eb3dde';//0b23866e-0b92-4a58-a8eb-2089be3f2727';//ed81ee4b-48c0-4823-8714-1acd3ce497cc';					
		if(isset($_SESSION["EnvelopeId"]))
		{
			$RequestStatusesParams["EnvelopeStatusFilter"]["EnvelopeIds"]["EnvelopeId"] = $_SESSION["EnvelopeId"];
			unset($_SESSION["EnvelopeId"]);
		}
									
		$Envelopes = $dsApi->RequestStatuses($RequestStatusesParams);
	}
	/*catch( SoapFault $fault)
	{
		$err = $ex->getMessage();
		$errFile = $ex->getFile();
		$errCode = $ex->getCode();
		$errLine = $ex->getLine();
		error_log("SoapFault exception occured in GetEnvelopesStatus():  ".$err);	//error_log($ex->getTraceAsString());	
		return false;			
		//die(1);
	}*/
	catch (SoapFault $e)
	{		
		$errRequestStatuses = $e->getMessage();
		error_log("SoapFault exception occured in File:  ".$e->getFile().": ".$e->getLine().", ".$e->faultcode);
		error_log($e->getMessage());
	}
	//xdebug_enable();
	                    
	if(!isset($Envelopes))
	{
		return $errRequestStatuses;
	}
	
	$envelopesStatus = $Envelopes->RequestStatusesResult->EnvelopeStatuses->EnvelopeStatus;
	
	return (is_object($Envelopes) && is_object($Envelopes->RequestStatusesResult) && is_object($Envelopes->RequestStatusesResult->EnvelopeStatuses));
}

function UpdateTreenoDBPerEnvelope($dsApi, $EnvelopeStatusResult, $db_object, $eSign_cab)
{
	$status = $EnvelopeStatusResult->Status; 						
	if(NeedProcessEnvelopeStatusResult($EnvelopeStatusResult))
	{
		//error_log("NeedProcessEnvelopeStatusResult is true. Envelope status: ".$status.", cab: ".$eSign_cab); 
		$whereArr = array();
		$updateArr = array();
	    $whereArr['envid'] = $EnvelopeStatusResult->EnvelopeID;							
		$updateArr['status'] = $status;  
						
		if (array_key_exists("Voided", $EnvelopeStatusResult))
		{
			$updateArr['tmVoid']=$EnvelopeStatusResult->Voided;
		}
		if (array_key_exists("Sent", $EnvelopeStatusResult))
		{
			$updateArr['tmSend']=$EnvelopeStatusResult->Sent;
		}								
		if (array_key_exists("Delivered", $EnvelopeStatusResult))
		{
			$updateArr['tmDeliver']=$EnvelopeStatusResult->Delivered;
		}
		if (array_key_exists("Signed", $EnvelopeStatusResult))
		{
			$updateArr['tmSign']=$EnvelopeStatusResult->Signed;
		}
		if (array_key_exists("Declined", $EnvelopeStatusResult))
		{
			$updateArr['tmDecline']=$EnvelopeStatusResult->Declined;
		}
		if (array_key_exists("Completed", $EnvelopeStatusResult))
		{
			$updateArr['tmComplete']=$EnvelopeStatusResult->Completed;	
														
			try
			{
				$row_envelopes = getTableInfo($db_object, $eSign_cab.'_envelopes', array(), $whereArr, 'queryRow');	
				if(isset($row_envelopes['num_docs'])&& ($row_envelopes['num_docs'] == 1))
				{
					$row_dsfiles = getTableInfo($db_object, $eSign_cab.'_dsfiles', array(), $whereArr, 'queryRow');	
					//error_log('Query '.$eSign_cab.'_dsfiles'.' returned '.count($row_dsfiles).' result for envid='.$EnvelopeStatusResult->EnvelopeID);	
					//error_log(print_r($row_dsfiles, true));
										
					//$version = (int)$row_dsfiles['version'];																		
					if((count($row_dsfiles) > 0)&&((int)$row_dsfiles['version'] == 0))
					{		
						//$origfileid = $row_dsfiles['origfileid'];
						$origfilename = $row_dsfiles['origfilename'];	//C:/Treeno/data/client_files/DocuSign/iloitinrdowx/Signed_Forms1/1.pdf
											
						$loc = ExtractFilePathFromFullFilePathName($origfilename);	
						$filename = ExtractFilenameFromFullFilePathName($origfilename);	
						$newfilename = str_replace('.pdf', '_dsv1.pdf',  strtolower($filename));
																									
						error_log("Get signed document of ".$origfilename);									
						$RequestPDFParam = array();		//new RequestPDF();
						$RequestPDFParam["EnvelopeID"] = $EnvelopeStatusResult->EnvelopeID;		
																		
						$EnvelopePDF = $dsApi->RequestPDF($RequestPDFParam);
						$PDFBytes = $EnvelopePDF->RequestPDFResult->PDFBytes;
						file_put_contents($loc.'/'.$newfilename, $PDFBytes);									
						error_log("Signed document saved to ".$newfilename);	
						updateTableInfo($db_object, $eSign_cab.'_dsfiles', array('version' => 1, 'filename' => $newfilename), $whereArr);
					}	
				}
				else if(isset($row_envelopes['num_docs'])&& ($row_envelopes['num_docs'] > 1))
				{
					error_log("Evenlop ".$EnvelopeStatusResult->EnvelopeID." contains files: ".$row_envelopes['num_docs']);
					$query = "SELECT origfilename, version FROM {$eSign_cab}_dsfiles WHERE envid='$EnvelopeStatusResult->EnvelopeID'";
					//error_log("query: ".$query);
					$fileArr = $db_object->extended->getAssoc($query,null,array(),null,MDB2_FETCHMODE_DEFAULT);//, true, true);
					//error_log('Query '.$eSign_cab.'_dsfiles returned: '.print_r($fileArr, true));
												
					if(in_array(0, $fileArr))//, true))
					{
						//error_log('$bNeedRequestDocumentPDFs = true');
						$RequestDocumentPDFsParam = array();		//new RequestDocumentPDFs();
						$RequestDocumentPDFsParam["EnvelopeID"] = $EnvelopeStatusResult->EnvelopeID;
						$result = $dsApi->RequestDocumentPDFs($RequestDocumentPDFsParam);
						$docPDFs = $result->RequestDocumentPDFsResult;
						//error_log('RequestDocumentPDFs returned: '.count($docPDFs));
											
						$filesProcessed = 0;
						foreach($docPDFs->DocumentPDF as $doc)
						{
							foreach($fileArr as $origfilename => $version)
							{
								if (strpos($origfilename, $doc->Name) !== false) 
								{
									$loc = ExtractFilePathFromFullFilePathName($origfilename);	
									$filename = ExtractFilenameFromFullFilePathName($origfilename);	
									$newfilename = str_replace('.pdf', '_dsv1.pdf',  strtolower($filename));
																									
									file_put_contents($loc.'/'.$newfilename, $doc->PDFBytes);//, FILE_APPEND );
														
									//$whereArr['origfilename'] = $origfilename;
									$newWhereArr = $whereArr +  array('origfilename' => $origfilename);
									//error_log('$newWhereArr = '.print_r($newWhereArr, true));
									updateTableInfo($db_object, $eSign_cab.'_dsfiles', array('version' => 1, 'filename' => $newfilename), $newWhereArr);
									$filesProcessed += 1;
									break;
								}							
							}
						}
						error_log('Completed files processed = '.$filesProcessed);
					}
				}														
			}
			catch(Exception $ex)
			{
				error_log("docuSignStaus.php caught exception: ".$ex->getMessage());						
			}					
		}			
																	
		updateTableInfo($db_object, $eSign_cab.'_envelopes', $updateArr, $whereArr);
		//error_log(print_r($updateArr, true));
		unset($whereArr);
		unset($updateArr);
	}
	else
	{
		//error_log('Unprocessed status('.$status.') for envelope '.$EnvelopeStatusResult->EnvelopeID);
	}		
}
?>	
