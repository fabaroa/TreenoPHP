<?php
include_once("../lib/crypt.php");
include_once("commons.php");
//include("Envelope.php");
require_once("DocuSignHelper.php");	

CheckSession('docuSignLogin.php');
						
if(isset($_GET['signFile']) && !isset($_POST["password"]))
{
	$_SESSION["eSign_cab"] = (isset($_GET['cab']))? $_GET['cab']:'';
	$_SESSION["eSign_docid"] = (isset($_GET['doc_id']))? $_GET['doc_id']:'';
	$_SESSION["eSign_tabid"] = (isset ($_GET['tab_id']))? $_GET['tab_id']:'';
	$_SESSION["eSign_checkedfiles"] = (isset($_GET['checked_files'])) ? $_GET['checked_files']:'';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>SyndicIT</title>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
		<link rel="stylesheet" type="text/css" href="css/style.css">
	</head>
	<body>
 		<div class="header">
			<div class="floatLeft" >
				<img src="images/logo.png" alt="Treenosoftware - Your global document management solution">
			</div>
		</div>
		<div class="gutter"></div>	
		<div class="col1">
			<form id="applicationForm" method="post">
				<fieldset>
					<legend class="heading">Sign Document</legend>					
					<span style="color: red;"></span>
					<?php
					//$testing = 1;
					if(!isset($_SESSION["DS_LOGED"]) || ($_SESSION["DS_LOGED"] != "1") )
					{		
						if (!isset($_POST["email"]))
						{
							$docuSignConfigFile = $DEFS['MAPPING_DIR'] . '/' . $user->db_name . '/docuSignSettings.xml';								
							if(!ReadDocuSignConfig($docuSignConfigFile, $admin_mail, $admin_pass, $userActKey))
							{
								return;
							}
							//$admin_mail = "czhang@treenosoftware.com";
							//$admin_pass = "Doc-4tron";	
							//$encPassword = tdEncrypt("testpassword");

							$_SESSION["email"] = $admin_mail;
							$_SESSION["password"] = $admin_pass;
							$_SESSION["actKey"] = $userActKey;
						}
						else
						//if (isset($_POST["email"]))
						{
							$admin_mail = $_POST["email"];
							$admin_pass = $_POST["password"];
							
							$encPassword = tdEncrypt($admin_pass);
							$domDoc = DomDocument::load( 'docuSignSettings.xml' );
														
							$domAccnts = $domDoc->getElementsByTagName( 'DocuSignAccount' );
							$domPasswords =  $domAccnts->item(0)->getElementsByTagName('Password');
							$domPasswords->item(0)->nodeValue = $encPassword;
							$domDoc->savexml();
							$domDoc->save( 'docuSignSettings.xml');
							//$decPassword = tdDecrypt($encPassword);
						}
																	
						$LoginRes = LogDocuSignServer($admin_mail, $admin_pass);
						$logSuccess = is_string($LoginRes)? false : $LoginRes->Success;						
						error_log("Logging to DocuSign: ".(string)$logSuccess);
						if ($logSuccess) 
						{						
							$resAccount = $LoginRes->Accounts->Account;
							if(!is_array($resAccount))
							{
								$_SESSION["DS_LOGED"] = "1";
								$_SESSION["AccountID"] = $resAccount->AccountID;		//40bedf24-70fd-4234-bf57-4263fa036605 = Api accountId
								$_SESSION["AccountName"] = $resAccount->AccountName;	//Treeno Software, Inc
								$_SESSION["UserName"] = $resAccount->UserName;		//Chris Zhang
								$_SESSION["email"] = $resAccount->Email;				//czhang@treenosoftware.com
								$_SESSION["UserID"] = $resAccount->UserID;			//397fae8a-af31-45e2-bc03-3db45039f255 = api userName
								//$_SESSION["UserID"] = $response->LoginResult->Accounts->Account[$AcountNum]->UserID;		
							}
							else
							{
								//$ApiAccountID = $resAccount[0]->AccountID;		//40bedf24-70fd-4234-bf57-4263fa036605 = Api accountId		
								$caller = 'whichfile';
								if(isset($_GET['status']))
								{
									$caller = 'status';
								}
								else if(isset($_GET['complete']))
								{
									$caller = 'complete';
								}
		
								echo("<span style='color:black;'>Please select one of the follwing account(s): </span>");
								echo("<ul>");
								$i=0;
								foreach($resAccount as $Account) 
								//foreach($response->LoginResult->Accounts as $Account) 
								{
									$_SESSION["docuSignAccts"] = array();
									$_SESSION["docuSignAccts"][$i] = $Account;
									$query = ($caller == 'whichfile')? 'num='.$i : (string)$caller.'&num='.$i;
									echo("<li><a style='text-decoration:underline;color:green;' href='selectAcount.php?$query'>Account Name: ".$Account->AccountName."</a></li>");
									echo("<ul><li>User Name: ".$Account->UserName."</li><li>Email: ".$Account->Email."</li></ul>");
									$i++;
								}
								echo("</ul>");
							}
						} 
						else if(!is_string($LoginRes))
						{
							echo("<span style='color:red;'>$LoginRes->ErrorCode</span>");
							
							echo('<p>Please enter the email address and password for your Docusign account. You
							will be prompted to select the account you wish to work with if your
							email address and password match multiple accounts.</p>');
							
							echo("<label for=\"email\">
								<input name=\"email\" tabindex=\"1\" id=\"email\" size=\"60\" type=\"text\" value=$admin_mail>
								Email:				</label>");
							echo('<label for="password">
							 	<input name="password" tabindex="2" id="password" size="60" value="" type="password">
							 	Password:					</label>
								<br/>
								<input name="Submit" id="submit" tabindex="4" value="Connect to DocuSign" type="submit" align="left"><br/>');
						}
						else
						{
							echo ("<span style='color:red;'>Error occured - $LoginRes</span>");
							echo('<p>Please contact Treeno server administrator to correct this issue.</p>');
						}
					}
					
					if(isset($_SESSION["DS_LOGED"]) && ($_SESSION["DS_LOGED"] == "1") )
					{
						if(isset($_GET['status']))
						{
							header( 'Location: docuSignStatus.php?' ) ;
							//header( 'Location: docuSignStatus.php?cab='.$_SESSION["eSign_cab"] ) ;	
						}
						else if(isset($_GET['complete']))
						{
							$id = isset($_GET["id"])? $_GET["id"]:0;
							header( 'Location: complete.php?id='.$id ) ;	
						}
						else
						{
							header( 'Location: docuSignApply.php' ) ;	
						}			
					}			
					?>                   
				</fieldset>
			</form>
		</div>
	
		<div class="footer">
			Treeno - Docusign		</div>
	</body></html>