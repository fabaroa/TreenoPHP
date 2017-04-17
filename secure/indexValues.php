<?php
include_once '../check_login.php';
include_once ('../classuser.inc');
include_once '../settings/settings.php';
include_once '../lib/filename.php'; //need for function countFiles
include_once '../lib/cabinets.php'; //need for function readyToIndex
include_once '../lib/wfIndexing.php';
include_once '../modules/modules.php'; //need for check_enable function

if ($logged_in == 1 && strcmp($user->username, "") != 0) {
	$db_doc = getDbObject ('docutron');
	$settings = new GblStt($user->db_name, $db_doc);
	$dateFuncs = $settings->get('date_functions');
	if (!$dateFuncs) {
		$dateFuncs = 'false';
	}
	$db_object = $user->getDBObject();
	$cab = $_GET['cab'];
	$ID = $_GET['ID'];
	//get workflow if it is there
	//$workflow = $_GET['workflow'];
	//$_GET/$_POST['page'] below
	$foldersLeft = $trans['foldersLeft'];
	$of = $trans['of'];
	$Submit = $trans['Submit'];
	$Skip = $trans['Skip'];
	$from = $trans['from'];
	$Delete = $trans['Delete'];
	$workflowstring = "Workflow";
	$filesArray = $_SESSION['indexFileArray'];
	if ($cab != NULL) {
		//This function is located in lib/utility.php
		$id = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cab), 'queryOne');
		if ($user->checkSecurity($cab) != 0) {
			$fields = getCabinetInfo($db_object, $cab);
			$num_indices = count($fields);
			echo "<html>\n";
			// used for scrolling and readyToIndex
			$countPath = $DEFS['DATA_DIR']."/".$countPath;
			//returns an array of sorted filenames
			//corresponding page-1 == element in array 
			//Test to see if scrolling is allowed
			$setScroll = $settings->get('scroll');
			if ($setScroll == null) // for updating old db versions
				{
				$settings->set('scroll', '1');
				$setScroll = $settings->get('scroll');
			}
			//returns a pool of characters, numbers, and special characters
			$pool = $user->characters(4);
			echo "<head>\n";
			if ($setScroll) {
				//beginning of scroll PART1
				$num_files = count ($filesArray); 
				echo<<<ENERGIE
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>				
<link rel="stylesheet" type="text/css" href="../lib/calendar.css"/>
<script type="text/javascript" src="../energie/func.js"></script>
<script type="text/javascript" src="../lib/prototype.js"></script> 
<script src="../lib/windowTitle.js"></script>
<script src="../lib/calendar.js"></script>		
 	  <script language="JavaScript">
	  var currentCab = '$cab';
 	  var dateFunctions = $dateFuncs;
 	  var formToSubmit = 'submitBtn';
	  setTitle( 4, "$cab", "" );
	  function passIndex(cab,ID,page,oldPage) 
	  {
		var sumVar;
		var passString = "&"; //string that is added to onload statement
		var verPage;
		if(page < 1)
			verPage = 1;
		else if(page > $num_files)
			verPage = $num_files;
		else if( (page > 0) && (page <= $num_files) )
			verPage = page;
		else 
			verPage = oldPage;\n

ENERGIE;
				for ($k = 0; $k < $num_indices; $k ++) {
					$var = $fields[$k]; //forms[0]
					//sumVar = document.indexing.$var.value;
					echo<<<ENERGIE
			if(document.getElementById("field-$k")) {
			sumVar = document.getElementById("field-$k").value;
			} else {
				sumVar = '';
			}
		
			if( $k == ($num_indices-1) )
				passString = passString+"$k="+sumVar;
			else
				passString = passString+"$k="+sumVar+"&";\n
ENERGIE;
				}

				echo<<<ENERGIE
		document.onload = parent.IndexMainFrame.window.location='getImage.php?cab='+cab+'&ID='+ID+'&page='+verPage+passString+"&numIndex=$num_indices";
	  }

	  </script>
ENERGIE;
				//end of scroll PART 1
			} //end of test for $setScroll
			echo<<<ENERGIE
<script>
	function registerFuncs() {
		var numIndices = '$num_indices';
		if(!numIndices) {
			numIndices = 0;
		}
		for (var i = 0; i < numIndices; i++) {
			var inpField = document.getElementById('field-' + i);
			if(inpField) {
				if(dateFunctions && (inpField.name.search(/date/i) != -1 || inpField.name.search(/DOB/i) != -1)) {
					inpField.validate = validateDate;
					newImg = document.createElement('img');
					newImg.src = '../images/edit_16.gif';
					newImg.style.cursor = 'pointer';
					newImg.style.verticalAlign = 'middle';
					newImg.input = inpField;
					newImg.onclick = dispCurrMonthIndex;
					inpField.parentNode.insertBefore(newImg, inpField.nextSibling);
				} else {
					inpField.validate = function(){return true;};
				}
			}
		}
	}
	function validateForm() {
		var numIndices = '$num_indices';
		var inpField;
		for(var i = 0; i < numIndices; i++) {
			inpField = document.getElementById('field-' + i);
			if(inpField) {
				if(!inpField.validate()) {
					var errDiv;
					if (!document.getElementById("valDiv")) {
						errDiv = document.createElement('div');
						errDiv.id = "valDiv";
						errDiv.style.position = 'absolute';
						errDiv.style.bottom = '1em';
						errDiv.className = 'error';
						errDiv.style.left = '2em';
						document.body.appendChild(errDiv);
					} else {
						errDiv = document.getElementById('valDiv');
						while(errDiv.hasChildNodes()) {
							errDiv.removeChild(errDiv.firstChild);
						}
					}	
					errDiv.appendChild(document.createTextNode(inpField.msg));
					return false;
				}
			}
		}
		return true;
	}

function dispCurrMonthIndex() {
	var inputBox = this.input;
	if(currShowing[inputBox.id]) {
		if (currShowing[inputBox.id].shim) {
			document.body.removeChild (currShowing[inputBox.id].shim);
		}
		document.body.removeChild(currShowing[inputBox.id]);
		currShowing[inputBox.id] = null;
	} else {
		var currDate = new Date();
		var newDiv = document.createElement('div');
		newDiv.style.visibility = 'hidden';
		new Calendar(currDate.getMonth(), currDate.getFullYear(), newDiv, inputBox);
		document.body.appendChild(newDiv);
		newDiv.style.position = 'absolute';
		newDiv.style.zIndex = 100;
		var tmpVal = 0;
		var el = inputBox;
		while (el) {
			tmpVal += el.offsetLeft;
			el = el.offsetParent;
		}
		tmpVal += inputBox.offsetWidth + 30;
		newDiv.style.left = tmpVal + 'px';
		if(newDiv.offsetLeft < 0) {
			newDiv.style.left = '0px';
		}
		newDiv.style.top = '0px';
		var iframe = document.createElement ('iframe');
		iframe.style.display = 'none';
		iframe.style.left = '0px';
		iframe.style.position = 'absolute';
		iframe.style.top = '0px';
		iframe.src = 'javascript:false;';
		iframe.frameborder = '0';
		iframe.style.border = '0px';
		iframe.scrolling = 'no';
		document.body.appendChild(iframe);
		iframe.style.top = newDiv.style.top;
		iframe.style.left = newDiv.style.left;
		iframe.style.width = newDiv.offsetWidth + 'px';
		iframe.style.height = newDiv.offsetHeight + 'px';
		iframe.style.zIndex = newDiv.style.zIndex - 1;
		newDiv.style.visibility = 'visible';
		iframe.style.display = 'block';
		newDiv.shim = iframe;
		currShowing[inputBox.id] = newDiv;
	}
}
function extIndexMove(cab,fieldNames,fieldValues, myTab) {
	if(currentCab == cab) {
		var getTextFields = document.getElementsByTagName('input');
		for(var i=0;i<getTextFields.length;i++) {
			if(getTextFields[i].type == 'text') {
				var txtname = getTextFields[i].name;
				for(var j=0;j<fieldNames.length;j++) {
					if(txtname == fieldNames[j]) {
						getTextFields[i].value = fieldValues[j];
						break;
					}
				}
			}
		}
		document.getElementById ('extIndexTab').value = myTab;
		document.indexing.submit();	
	} else {
		alert('you are currently viewing the wrong cabinet');
	}
}

</script>
ENERGIE;
			echo "</head>";
			echo " <body onload=\"registerFuncs()\" style=\"overflow-y: scroll\">\n";
			echo "  <form name=\"indexing\" method=\"post\" onsubmit=\"return validateForm()\" target=\"IndexMainFrame\" action=\"submitIndex.php?cab=$cab&ID=$ID\">\n";
			echo "   <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
			for ($i = 0; $i < $num_indices; $i ++) {
				//Displays the fieldname
				$var = $fields[$i];
				$tmp = str_replace("_", " ", $var);
				$fieldnames .= "<td>".$tmp."</td>";

				//check for settings for data type definitions
				$setstr = "dt,".$user->db_name.",$id,$var";
				$setRet = $settings->get($setstr);
				//special case for workflow
				//if( $workflow==1 )
				//{
				//die("in workflow");
				/* workflow has four fields
				 * 0 workflow
				 * 1 Cabinet
				 * 2 Date Scanned
				 * 3 Date Indexed
				 * pass them to wfIndexing($i)
				 */
				//	$func = "wfIndexing$i";
				//		$fieldvalues .= $func( $db_object, $var, $path."/".$displayPath);
				//	}else 
				if ((!$setRet) || $_GET[$i]) //does something exist in the settings table?  
					{ //or is there a value entered by user?
					$passInValue = $_GET[$i]; //$_GET[$i] comes from previously entered values saved after changing pages
					//put date in date_indexed automatically
					if (strtolower($var) == "date_indexed")
						$passInValue = date('Y')."-".date('m')."-".date('d');
					$fieldvalues .= "<td style=\"white-space: nowrap\"><input type=\"textfield\" id=\"field-$i\" name=\"$var\"";
					$fieldvalues .= " onkeypress=\"return inputFilter(event);\" ";
					$fieldvalues .= "value=\"$passInValue\" size=\"15\"></td>\n";
				} else {
					$items = explode(",,,", $setRet);
					$count = count($items);
					if ($count == 1) {
						//put date in Date_indexed automatically
						if (strtolower($var) == "date_indexed")
							$passInValue = date('Y')."-".date('m')."-".date('d');

						$fieldvalues .= "<td style=\"white-space: nowrap\"><input type=\"textfield\" onkeypress=\"return inputFilter(event);\" ";
						$fieldvalues .= " id=\"$i\" name=\"$var\" value=\"$setRet\" size=\"15\"></td>\n";
					} else {
						$fieldvalues .= "<td><select id=\"$i\" name=\"$var\">";
						$fieldvalues .= "<option value=\"\"></option>";
						for ($o = 0; $o < $count; $o ++) {
							$fieldvalues .= "<option value=\"$items[$o]\">$items[$o]</option>";
						}
					}
					$fieldvalues .= "</td>";

				}
			} //end of for loop

			if($setScroll) {
				$num_files = count($filesArray);

				if ($_GET['page'])
					$page = $_GET['page'];
				else
					$page = 1;

				//this is for $previous which passes the previous page
				if ($page == 1)
					$previous = 1;
				else
					$previous = $page -1;

				//this checks if $next has reached the last page
				if ($page == $num_files)
					$next = $num_files;
				else
					$next = $page +1;

				$fileName = $filesArray[$page-1]; //returns the fileName at the page number
				$fileName = substr($fileName, (strpos($fileName, $cab) + strlen($cab) + 1));
			}

			//tack on workflow here
			if (check_enable('workflow', $user->db_name)) {
				$fieldnames .= "<td>$workflowstring</td>";
				$fieldvalues .= "<td>".wfIndexing0($db_object, 'Workflow')."</td>";
			}
			echo "<tr>".$fieldnames."</tr>";
			echo "<tr>".$fieldvalues."</tr>";
			echo "</table><table>";
			echo "<tr>";
			echo "<td><input type=\"submit\" name=\"Submit\" id=\"submitBtn\" value=\"$Submit\"></td>";
			echo "<td><input type=\"submit\" name=\"Delete\" value=\"$Delete\"></td>";
			echo "<td><input type=\"button\" value=\"$Skip\" onclick=\"javascript:parent.IndexMainFrame.window.location='../secure/getImage.php?cab=$cab&quota=$ID';\"></td>\n";
			$whereArr = array('finished<total','flag=0','upforindexing=0');
			$count = getTableInfo($db_object,$cab."_indexing_table",array('COUNT(id)'),$whereArr,'queryOne');
			echo "<td>\n$count $foldersLeft<br>\n";
			// echo "<td>&nbsp;<BR>";
			echo "Now viewing $fileName</td>\n";
//			echo "<td>&nbsp</td>\n";

//			$whereArr = array('finished<total','flag=0','upforindexing=0');
//			$count = getTableInfo($db_object,$cab."_indexing_table",array('COUNT(id)'),$whereArr,'queryOne');
//			echo "<td>$count $foldersLeft $from $displayPath</td>\n";
			echo "<td>&nbsp;</td>\n";
			echo "</tr>";
			echo "</table><input type=\"hidden\" name=\"extIndexTab\"
			id=\"extIndexTab\"></form>";

			//beginning of scrolling PART 2 
			if ($setScroll) {
				//PART 1 used to be here
				//for arrows to flip through tif images
				echo "\n\n";
				echo "<table width=\"25%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
				echo "<tr>\n";
				//button to go to beginning, $id passes cab ID, $ID passes row id, third param passes page number
				echo "    <td width=\"15%\" align=\"right\">";
				echo "<a href=\"#\" onClick=\"javascript:passIndex('$cab','$ID','1','$page');\">";
				echo "<img src=\"../energie/images/begin_button.gif\" border=\"0\" ></a></td>\n";
				//button for previous image
				echo "    <td width=\"2%\" align=\"center\">";
				echo "<a href=\"#\"  onClick=\"javascript:passIndex('$cab','$ID','$previous','$page');\">";
				echo "<img src=\"../energie/images/back_button.gif\" border=\"0\" ></a></td>\n";
				echo "<td style=\"padding-top: 15px\"  noWrap=\"yes\" align=\"center\">\n";
				echo "<form name=\"pageForm\" method=\"post\">";
				echo "&nbsp;<input name=\"textfield\" value=\"";
				echo $page;
				echo "\" type=\"text\" size=\"3\" onkeypress=\"if(event.keyCode == 13){return passIndex('$cab','$ID',this.value,'$page');}\">&nbsp;";
				echo "</td><td noWrap=\"yes\" width=\"15%\" align=\"center\" class=\"lnk_black\">";
				echo " $of ".$num_files."&nbsp;";
				echo "</td></form>\n";
				//button for next page
				echo "    <td width=\"2%\" align=\"center\">";
				echo "<a href=\"#\"  onClick=\"javascript:passIndex('$cab','$ID','$next','$page');\">";
				echo "<img src=\"../energie/images/next_button.gif\" border=\"0\" ></a></td>\n";
				//button for last image
				echo "    <td width=\"5%\" align=\"left\"><a href=\"#\"";
				echo "  onClick=\"javascript:passIndex('$cab','$ID','$num_files','$page');\">";
				echo "<img src=\"../energie/images/end_button.gif\" border=\"0\" ></a></td>\n";
				echo "	</tr>\n</table>\n";
			} //end of scrolling PART 2
			echo "</body></html>";
			setSessionUser($user);
		} else { //end of security check
			logUserOut();
		}
	} else //DepID has not been set yet so display Select Cabinet
		echo "you need to choose a Cabinet";
} else {
	logUserOut();
}
?>
