<?php

	include_once("Envelope.php");
	include_once("commons.php");
	
	include_once '../db/db_common.php';
	include_once '../check_login.php';
	include_once ('../classuser.inc');

	CheckSession('docuSignApply.php');

	if(!isset($_SESSION["DS_LOGED"]) || ($_SESSION["DS_LOGED"] != "1"))
	{
		header( 'Location: docuSignLogin.php' ) ; 
	}
			
	$arrFullFilePathName = array();
	$filenames = array();
	if(!isset($_POST["Submit"]) )
	{
		if ($logged_in == 1 && strcmp($user -> username, "") != 0) 
		{	
			$myCab	= $_SESSION["eSign_cab"];	
			$MyDoc_id = $_SESSION["eSign_docid"];
			$MyTab_id = $_SESSION["eSign_tabid"];
		
			// find location of $MyDoc_id
			$whereArr = array('doc_id'=>(int)$MyDoc_id);
			$res = getTableInfo($db_object,$myCab,array(),$whereArr);
		
			$row2 = $res->fetchRow();
			$folderLoc = $DEFS['DATA_DIR'].'/'.str_replace(' ', '/', $row2['location']).'/';
			
			$MyFiles = $_SESSION["eSign_checkedfiles"];
			$myFileIDs = explode("_next-file-id_", $MyFiles);
			
			error_log("Number of file(s) selected to be signed: ".count($myFileIDs));
			$index = 0;
			// find subfolder and filename
			foreach($myFileIDs as $fileID)
			{
				$row = getTableInfo($db_object, $myCab.'_files', array(), array('id' => (int) $fileID), 'queryRow');				
				$filename = $row['filename'];
				
				$filenames[$index] = $filename;
				
				if($row['subfolder']) {
					$loc = $folderLoc.$row['subfolder'].'/';
				}
				else
				{
					$loc = $folderLoc;
				}
				
				$arrFullFilePathName[$index] = $loc.$filename.'_fileid_'.$fileID;
				$index += 1;		
				
				error_log("File ".$index.": ".$loc.$filename);
			}		
		}
		
		$_SESSION["arrFiles"]= $arrFullFilePathName;
	}
	
	$submitComplete = isset($_POST["Submit"]) && isset($_POST["lastName"]) && ($_POST["lastName"]!="") && isset($_POST["firstName"]) 
				&& ($_POST["firstName"]!="")&& isset($_POST["emailDestination"]) && validateEmail($_POST["emailDestination"]);
					
	if($submitComplete)
	{
		if(isset($_SESSION["arrFiles"]))
		{
			$arrFullFilePathName = $_SESSION["arrFiles"];
		}
		//error_log('Api username: '.'['.$_SESSION["actKey"].']'.$_SESSION["UserID"]);
		$config = array("userId" => '['.$_SESSION["actKey"].']'.$_SESSION["UserID"],			//$_SESSION["UserID"], 
						"password" => $_SESSION["password"], 
						"accountId" => $_SESSION["AccountID"], 
						"subject" => $_POST["emailSubject"], 
						"blurb" => $_POST["emailBlurb"]);
		
		$envelope = new Docusign_Envelope("https://www.docusign.net/api/3.0/api.asmx?wsdl", $config);

		$URL = curPagePath()."docuSignStatus.php";
		
		//$fullfilename = "";	
		//$fileid = 0;						
		if (isset($_POST["embedded"]))
		{		
			if($_POST["embedded"] == "embeddedSending") 
			{	
				error_log("docuSignApply.php: signing method - embeddedSending");				
				$RequestEvenlopeParams = array();
				$RequestEvenlopeParams["Envelope"]["AccountId"] = $_SESSION["AccountID"];
				$RequestEvenlopeParams["Envelope"]["Subject"] = $_POST["emailSubject"];		
				$RequestEvenlopeParams["Envelope"]["EmailBlurb"] = $_POST["emailBlurb"];
				
				for($i = 1; $i <= count($arrFullFilePathName); $i++)
				{
					$docParas = array();
					
					$arrFileNameID = explode('_fileid_',$arrFullFilePathName[$i - 1]);
					$fullfilename = $arrFileNameID[0];	
					$fileid = $arrFileNameID[1];			
					if(file_exists($fullfilename))
					{								
						$docParas["ID"] = $i;	//"1";
						$docParas["FileExtension"] = "pdf";
						$docParas["Name"] = ExtractFilenameFromFullFilePathName($fullfilename);
		
						$pdfContent = file_get_contents($fullfilename);					
						$docParas["PDFBytes"] = $pdfContent;
						
						$RequestEvenlopeParams["Envelope"]["Documents"]["Document"][$i-1] = $docParas;
					}
				}
				
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["ID"] = "1";
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["Email"] = $_POST["emailDestination"];;
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["UserName"] = $_POST["firstName"]." ".$_POST["lastName"];
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["RequireIDLookup"] = "false";
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["Type"] =  $_POST["role"];//"Signer";
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["SignatureInfo"]["SignatureInitials"] = substr($_POST["firstName"],0,1)." ".substr($_POST["lastName"],0,1);//YG";
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["SignatureInfo"]["FontStyle"] = "BradleyHandITC";
				$RequestEvenlopeParams["Envelope"]["Recipients"]["Recipient"]["SignatureInfo"]["SignatureName"] = $_POST["firstName"]." ".$_POST["lastName"];

				//xdebug_disable();
				try
				{
					$Envelopes = $envelope->CreateEnvelope($RequestEvenlopeParams);
					$envelopeId = $Envelopes->CreateEnvelopeResult->EnvelopeID;
					error_log("Envelop created with ID: ".$envelopeId);
					
					$Status = $Envelopes->CreateEnvelopeResult->Status;
					$createTime = $Envelopes->CreateEnvelopeResult->Created;
					
					$myDateTime = date('Y-m-d G:i:s');
					$insertArr_cabName_envelopes = array(	'envID'		=> $envelopeId,
								'status'		=> $Status,
								'tmCreate'		=> $createTime,
								'num_docs' 		=> count($arrFullFilePathName));
					$res = $db_object->extended->autoExecute($_SESSION["eSign_cab"].'_envelopes',$insertArr_cabName_envelopes);
			
					/*	$insertArr_cabName_dsfiles = array(	'origfileID'		=> $fileid,
									'origfileName'		=> $fullfilename,
									'envID '		=> $envelopeId,
									'docID_InEnv'		=> 0,
									'version'		=> 0,
						 );
						$res = $db_object->extended->autoExecute('docuSign_dsfiles ',$insertArr_cabName_dsfiles);
					*/
	
					for($i = 1; $i <= count($arrFullFilePathName); $i++)
					{
						$arrFileNameID = explode('_fileid_',$arrFullFilePathName[$i - 1]);
						$fullfilename = $arrFileNameID[0];	
						$fileid = $arrFileNameID[1];			
						if(file_exists($fullfilename))
						{								
							$insertArr_cabName_dsfiles = array(	'origfileID'		=> $fileid,
									'origfileName'		=> $fullfilename,
									'envID '		=> $envelopeId,
									'docID_InEnv'		=> $i,
									'version'		=> 0,
							 );
							$res = $db_object->extended->autoExecute($_SESSION["eSign_cab"].'_dsfiles ',$insertArr_cabName_dsfiles);
						}
					}
								
					$RequestSenderTokenParam = array();
					$RequestSenderTokenParam["EnvelopeID"] = $envelopeId;
					$RequestSenderTokenParam["AccountID"] = $config['accountId'];	//"2fd91fb6-58f0-4cfe-83ce-ac977dd4d338";
					$RequestSenderTokenParam["ReturnURL"] = curPagePath()."docuSignStatus.php";	//curPageURL()."docuSignStatus.php";
					
					$_SESSION["EnvelopeId"] = $envelopeId;
					
					$Env = $envelope->RequestSenderToken($RequestSenderTokenParam);		
					$URL = $Env->RequestSenderTokenResult;
				}
				catch (SoapFault $e)
				{		
					error_log("SoapFault exception occured in File:  ".$e->getFile().": ".$e->getLine().", ".$e->faultcode);
					error_log($e->getMessage());
					
					$URL = 'docuSignStatus.php?error='.$e->getMessage();
				}
				//xdebug_enable();
			}	
			//if (isset($_POST["embeddedSigning"])) 
	/*		else if($_POST["embedded"] == "embeddedSigning")
			{
				$TemplateID = "";
				$TmplParam = array();
				$TmplParam["AccountID"] = $_SESSION["AccountID"];
				$TmplParam["IncludeAdvancedTemplates"] = false;
				$Tmlp = $envelope->RequestTemplates($TmplParam);
				foreach ($Tmlp->RequestTemplatesResult->EnvelopeTemplateDefinition as $Template)
				{
					if ($Template->Name == "Auto Insurance Application") {
						$TemplateID = $Template->TemplateID;
						$_SESSION["TEMPLATE_ID"] = $TemplateID;
					}
					error_log("Retuned template Name: ".$Template->Name.", templateID: ".$Template->TemplateID);
				}	
			
				//$RequestTemplateParams = array();
				
				$RequestTemplateParams = array();
				$RequestTemplateParams["TemplateID"] = $_SESSION["TEMPLATE_ID"];
				$RequestTemplateParams["IncludeDocumentBytes"] = "false";
				try
				{
					$Envelopes = $envelope->RequestTemplate($RequestTemplateParams);
				}
				catch(SoapFault $ex)
				{
					$err = $ex->getMessage();
					$errFile = $ex->getFile();
					$errCode = $ex->getCode();
					$errLine = $ex->getLine();
					error_log("SoapFault exception occured when calling UploadTemplate(). ".$err);
				}
				$arrTemplEnvelopes = object_to_array($Envelopes);
			
				$arrEnvelope["Envelope"] = $arrTemplEnvelopes["RequestTemplateResult"]["Envelope"];
				
				$arrEnvelope["Envelope"]["Recipients"]["Recipient"]["UserName"] = $_POST["firstName"]." ".$_POST["lastName"];
				$arrEnvelope["Envelope"]["Recipients"]["Recipient"]["Email"] = $_POST["emailDestination"];
				//if (isset($_POST["embeddedSigning"])) {
					$arrEnvelope["Envelope"]["Recipients"]["Recipient"]["CaptiveInfo"]['ClientUserId'] = session_id();
				//}
				$arrEnvelope["Envelope"]["Subject"] = $_POST["emailSubject"];
				$arrEnvelope["Envelope"]["EmailBlurb"] = $_POST["emailBlurb"];
				$arrEnvelope["Envelope"]["SigningLocation"] = "Online"; 
				
				//$arrEnvelope["Envelope"]["Tabs"]["Tab"][0]["Value"] = $_POST["carMake"];
				//$arrEnvelope["Envelope"]["Tabs"]["Tab"][1]["Value"] = $_POST["carModel"];
				//$arrEnvelope["Envelope"]["Tabs"]["Tab"][2]["Value"] = $_POST["carVIN"];
				
				$Envelopes = $envelope->CreateAndSendEnvelope($arrEnvelope);
				$envelopeId = $Envelopes->CreateAndSendEnvelopeResult->EnvelopeID;
				error_log("Envelop created with ID: ".$envelopeId);
			
			
				function current_millis() 
				{
				  list($usec, $sec) = explode(" ", microtime());
				  return round(((float)$usec + (float)$sec) * 1000);
			  	}		  		
				$RequestRecipientTokenparam = array();
				$RequestRecipientTokenparam["EnvelopeID"] = $envelopeId;
				$RequestRecipientTokenparam["ClientUserID"] = session_id();
				$RequestRecipientTokenparam["Username"] = $_POST["firstName"]." ".$_POST["lastName"];
				$RequestRecipientTokenparam["Email"] = $_POST["emailDestination"];
				$RequestRecipientTokenparam["AuthenticationAssertion"]["AssertionID"] = current_millis();
				//$d = date("Y")."-".$m."-".date("d")."T00:00:00.00";
				$m = date("m")+1;
				if($m<10){
					$d = date("Y")."-0".$m."-".date("d")."T00:00:00.00";
				}
				else
				{
					$d = date("Y")."-".$m."-".date("d")."T00:00:00.00";
				}
				$RequestRecipientTokenparam["AuthenticationAssertion"]["AuthenticationInstant"] = $d;
				$RequestRecipientTokenparam["AuthenticationAssertion"]["AuthenticationMethod"] = "Password";
				$RequestRecipientTokenparam["AuthenticationAssertion"]["SecurityDomain"] = "dsx.test";
				
				$RequestRecipientTokenparam["ClientURLs"]["OnViewingComplete"] = curPagePath()."complete.php?id=2";	
				$RequestRecipientTokenparam["ClientURLs"]["OnCancel"] = curPagePath()."complete.php?id=3";			
				$RequestRecipientTokenparam["ClientURLs"]["OnDecline"] = curPagePath()."complete.php?id=4";			
				$RequestRecipientTokenparam["ClientURLs"]["OnSessionTimeout"] = curPagePath()."complete.php?id=5";	
				$RequestRecipientTokenparam["ClientURLs"]["OnTTLExpired"] = curPagePath()."complete.php?id=6";		
				$RequestRecipientTokenparam["ClientURLs"]["OnException"] = curPagePath()."complete.php?id=7";		
				$RequestRecipientTokenparam["ClientURLs"]["OnAccessCodeFailed"] = curPagePath()."complete.php?id=8";	
				$RequestRecipientTokenparam["ClientURLs"]["OnSigningComplete"] = curPagePath()."complete.php?id=9";	
				$RequestRecipientTokenparam["ClientURLs"]["OnIdCheckFailed"] = curPagePath()."complete.php?id=1";	
				
				$Env = $envelope->RequestRecipientToken($RequestRecipientTokenparam);
				$URL = $Env->RequestRecipientTokenResult;		
			}
			*/
		}
	
		error_log("return to URL: ".$URL);
		header( 'Location: '.$URL ) ;
	//}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>SyndicIT</title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<link rel="stylesheet" type="text/css" href="css/style.css">
		
	
		
		<script type="text/javascript">				
		function OpenNewWindow()
		{
			var radioEmbeddedSending = document.getElementById('embeddedSending');
	        if(radioEmbeddedSending.checked)
			{ 
				newWindow = window.open('complete.php?id=1', 'newWin','scrollbars=no,menubar=no,height=600,width=800,resizable=yes,toolbar=no,location=no,status=no');	
			}
		}
		</script>

	</head>
	<body>
 		<div class="header">
			<div class="floatLeft" >
				<img src="images/logo.png" alt="Treenosoftware - Your global document management solution">
			</div>
			<div class="userBox">
				<span style="font-weight: bold;">DocuSign Username:</span>
				<span style="align:right"><?php echo($_SESSION["UserName"]) ?></span>
				<br/>
				<span style="font-weight: bold;">DocuSign Account:</span>
				<span><?php echo($_SESSION["AccountName"]) ?></span>
			</div>
		</div>
	
		<div class="gutter"></div>
		<div>
			 <span class="col1">				
				<span class="error">&nbsp;&nbsp;</span>
				<?php
					
					$useName = explode(" ", $_SESSION["UserName"]);
					
					$firstName = ($_SESSION["DS_LOGED"] == "1")? $useName[0]: "";
					$lastName = ($_SESSION["DS_LOGED"] == "1")? $useName[1]: "";;
					$emailDestination = ($_SESSION["DS_LOGED"] == "1")? $_SESSION["email"]:"";
					
					$emailSubject =  "Document(s) to be signed: ".implode(",", $filenames);
					
					$emailBlurb = "Please review and complete this application";
					
                    if ( isset($_POST["firstName"]) &&
                     (($_POST["firstName"]=="") || ($_POST["lastName"]=="") || ($_POST["emailDestination"]=="")) )  
                    {
						$firstName = $_POST["firstName"];
						$lastName = $_POST["lastName"];
						$emailDestination = $_POST["emailDestination"];		

						//echo '<script type="text/javascript">
						//if ( 1 ) { window.open(\'complete.php?id=1\', \'_WELCOME\', \'HEIGHT=450, resizable=yes, WIDTH=600\');	}</script>'		
						?>
		                <p><strong style="color:#F00">Please fill in the missing fields</strong></p>
		                <?php 
                    } 
                    else if(isset($_POST["emailDestination"]) && !validateEmail($_POST["emailDestination"]))
                    {
                    	?>
		                <p><strong style="color:#F00">Please correct Email address</strong></p>
		                <?php
                    }
                ?>
                
				<form id="applicationForm" method="post" action="docuSignApply.php"> <!-- onsubmit="OpenNewWindow()">-->
				
					<fieldset>													
					<legend class="heading">Recipient Information</legend>					
					
					<fieldset>													
					<legend class="heading">Name</legend>	
					<label for="firstName">
						<input name="firstName" value="<?php echo($firstName); ?>" tabindex="1" id="firstName" size="60" value="" type="text">
						First Name:					</label>
					<label for="firstName">
						<input name="lastName" value="<?php echo($lastName); ?>" tabindex="2" id="lastName" size="60" value="" type="text">
						Last Name:					</label>

					</fieldset>
					
					<fieldset tabindex="3">													
					<legend class="heading">Role</legend>		
						<label for="role1">
							<input name="role" id="role1" value="Signer" type="radio" checked >
						</input>Signer</label>
						<label for="role2">
							<input name="role" id="role2" value="CarbonCopy" type="radio" >
						</input>Carbon Copy</label>
						<label for="role3">
							<input name="role" id="role3" value="CertifiedDelivery" type="radio" >
						</input>Certified Delivery</label>
						<label for="role4">
							<input name="role" id="role4" value="InPersonSigner" type="radio" >
						</input>In-Person Signer</label>				
					</fieldset>	
					
					<fieldset>													
					<legend class="heading">Email</legend>	
					<label for="emailDestination">
						<input name="emailDestination" value="<?php echo($emailDestination); ?>" tabindex="4" id="emailDestination" size="60" type="text">
						Address:				</label>
					<label for="emailSubject">
						<input name="emailSubject" value="<?php echo($emailSubject); ?>" tabindex="4" id="emailSubject" size="60" type="text">
						Subject:				</label>
					<label for="emailBlurb">
						<textarea name="emailBlurb"  tabindex="5" id="emailBlurb" rows="1" cols="42"><?php echo($emailBlurb); ?></TEXTAREA>
						Blurb:				</label>
					</fieldset>


					<br />
					<fieldset >
						<legend class="heading">Compeletion Details</legend>
						
						<!-- label for="embeddedSigning">
							<input name="embedded" value="embeddedSigning" id="embeddedSigning" type="radio"/>
							<span>Complete Application now? (Embedded Signing)</span>
						</label-->
					
						<label for="embeddedSending">
							<input name="embedded" value="embeddedSending" tabindex="6" id="embeddedSending" type="radio" checked/>
							<span>Customize Signature Format? (Embedded Sending)</span>
						</label>
						
						<!-- input type="hidden" name="arrFiles" value="<?php echo($arrFullFilePathName); ?>" /-->
						
						<br/>
						<input name="Submit" id="submit" tabindex="7" value="Proceed" type="submit" align="left"/><br/>
						<br/>							
											
						<!--<p><a href='complete.php?id=1' target='_blank'>click to view</a></p>-->
						
						
					</fieldset>
				</form>
			</span>
		</div>
	
		<div class="footer">
			SyndicIT - Treenosoftware - Docusign		
		</div>
	</body>
</html>
