<?php
include_once '../db/db_common.php';
include_once '../check_login.php';
include_once ('../classuser.inc');

include_once("commons.php");
include_once("Envelope.php");	

include_once("DocuSignHelper.php");	


CheckSession('docuSignStatus.php');

if(isset($_GET['cab']))
{
	$_SESSION["eSign_cab"] =  $_GET['cab'];
}

//error_log('$_GET["cab"]'.$_GET['cab']);

if(!isset($_SESSION["DS_LOGED"]) || ($_SESSION["DS_LOGED"] != "1"))
{
	header( 'Location: docuSignLogin.php?status' ) ; 
	return;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<title>SyndicIT</title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<link rel="stylesheet" type="text/css" href="css/style.css">
		<script type="text/javascript">
		function Display()
		{
			if(self.location  != parent.mainFrame.window.location)
				parent.mainFrame.window.location = "docuSignStatus.php";//"complete.php";
		}
		</script>
		
	</head>
	<body onLoad="Display()">
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
			<h1><?php echo(date('Y-m-d')) ?></h1>
			<?php		
				if(isset($_GET['error']))
				{
					$errorMsg = $_GET['error'];
					echo ("<p style='color:red;'>$errorMsg</p>");
					echo('<p>Please contact Treeno server administrator to correct this issue.</p>');
					return;
				}
			?>
			<h1>DocuSign Envelope Status for Account: <?php echo($_SESSION["AccountName"]) ?></h1>
			<?php		
				$config = array("userId" => '['.$_SESSION["actKey"].']'.$_SESSION["UserID"],	// '[TREE-b88ac21a-a6be-4bde-b342-c38933721a1e]397fae8a-af31-45e2-bc03-3db45039f255',//$_SESSION["UserID"],
							    "password" =>  $_SESSION["password"],							//'Doc-4tron',	
							    "accountId" => $_SESSION["AccountID"],							//'40bedf24-70fd-4234-bf57-4263fa036605',
							    "subject" => '', 
							    "blurb" => '');
				
				$resGetEnvelopesStatus = GetEnvelopesStatus($config, $_SESSION["UserName"], $_SESSION["email"], $docusignApi, $envelopesStatus);
				$success = is_string($resGetEnvelopesStatus)? false : $resGetEnvelopesStatus;	
				if(!$success)
				{	
					echo ("<p style='color:red;'>Failed to request status from DocuSign</p>");
					echo ("<p style='color:red;'>$resGetEnvelopesStatus</p>");
					echo('<p>Please contact Treeno server administrator to correct this issue.</p>');
					return;
				}			
			?>
			<table class="dataTable">
				<thead>
					<tr>
						<th align="left">Subject</th>
						<th align="left">Status</th>
						<th align="left">Envelope Id</th>
						<th align="left">Time Generated</th>
					</tr>
				</thead>
				<tbody>
                    <?php
					$eSign_cab = $_SESSION["eSign_cab"];//'docusign';
					                    
					if(!is_array($envelopesStatus))
					{               	
						$arrEnvelopeStatus[] = $envelopesStatus;                
					}            
					else
					{                
						rsort($envelopesStatus);
						$arrEnvelopeStatus = $envelopesStatus;
					}

                    //$table = "docusign_envelopes";//$_SESSION["eSign_cab"].'_envelopes';		
					error_log("Request status returned ".count($arrEnvelopeStatus)." results. Will update table: ".$eSign_cab.'_envelopes')	;	
					//foreach ($Envelopes->RequestStatusesResult->EnvelopeStatuses as $tmpEnvelopeStatusResult)	
                    foreach ($arrEnvelopeStatus as $EnvelopeStatusResult)          
                    {       
						UpdateTreenoDBPerEnvelope($docusignApi, $EnvelopeStatusResult, $db_object, $eSign_cab);			
					?>
					<tr>			
						<td align="left"><?php echo($EnvelopeStatusResult->Subject);?></td>
						<td align="left"><?php echo($EnvelopeStatusResult->Status);?></td>
						<td align="left"><?php echo($EnvelopeStatusResult->EnvelopeID);?></td>
						<td align="left"><?php echo($EnvelopeStatusResult->Created);?></td>
					</tr>
						<?php
					}							
						?>													
				</tbody>
			</table>
		</div>
	
		<div class="footer">
			SyndicIT - Treenosoftware - Docusign		</div>
	</body></html>