<?php
include_once '../check_login.php';
include_once '../classuser.inc';
include_once '../lib/quota.php';

if($logged_in ==1 && strcmp($user->username,"")!=0) {  
  global $trans;

  $dieMessage          = $trans['dieMessage'];
  $selectCabLabel      = $trans['Choose Cabinet'];
  $createNewFo         = $trans['Create New Folder'];
  $addFolderButton     = $trans['Add Folder']; 

	if (isset($_GET['mess'])) {
		$mess = $_GET['mess'];
	} else {
		$mess = '';
	}

	if (isset($_GET['table'])) {
		$table = $_GET['table'];
	} else {
		$table = '';
	}

	if (isset($_GET['doc_id'])) {
		$curDoc_id = $_GET['doc_id'];
	} else {
		$curDoc_id = '';
	}

	if (isset($_GET['tab_id'])) {
		$tab_id = $_GET['tab_id'];
	} else {
		$tab_id = '';
	}
	
	//gets a value "inbox" if sent from inbox
	if (isset($_GET['parent'])) {
		$parent = $_GET['parent'];
	} else {
		$parent = '';
	}

	if (isset($_GET['cab'])) {
		$cab = $_GET['cab'];
	} else {
		$cab = '';
	}
	$existing = 0;
	$dateFuncs = '';
	$fieldStr = '';
	$numIndices = 0;
	if($cab) {
		$department = $user->db_name;
		$fieldnames = getCabinetInfo( $db_object, $cab );
		$numIndices = sizeof( $fieldnames );
		$fieldStr = implode(',,,', $fieldnames);
		$DepID = getTableInfo($db_object, 'departments', array('departmentid'), array('real_name' => $cab), 'queryOne');
		$settings = new GblStt( $department, $db_doc );
		$existing = $settings->get( 'file_into_existing' );
		$dateFuncs = $settings->get('date_functions');
		$department = $user->db_name;
		$whereArr = array("department='$department'","k " . LIKE . " 'dt,$department,$DepID,%'");
		$dataTypeInfo = getTableInfo($db_doc,'settings',array('k','value'),$whereArr);
		while( $result = $dataTypeInfo->fetchRow() ) {
			$key = str_replace( "dt,$department,$DepID,", "", $result['k'] );
			$value = $result['value'];
			$valArr = array();
			$valArr = explode( ",,,", $value );
			$indiceArr[$key] = $valArr;
		}

		$autoComp = $settings->get('indexing_'.$cab);
		if(!$autoComp) {
			$autoComp = "";
		}

		$sArr = array('field_name','required','regex','display');
		$wArr = array('cabinet_id' => (int)$DepID);
		$ffArr = getTableInfo($db_object,'field_format',$sArr,$wArr,'getAssoc');	
	}
	if( !$existing ) {
		$existing = 0;
	}

	if (!$dateFuncs) {
		$dateFuncs = 'false';
	}
echo <<<HTML
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>New Cabinet</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<link rel="stylesheet" type="text/css" href="../lib/calendar.css"/>
<script type="text/javascript" src="../lib/calendar.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript">
var existing = $existing;
var dateFunctions = $dateFuncs;
var divType = '$parent';
var autoComp = "$autoComp";
var cab = "$cab";
	function focusField() {
		var firstField = getEl('field-0');
		if(firstField) {
			firstField.focus();
		}
	}

	function hideAddFolder() {
		if(top.mainFrame.document.getElementById('addNewFolderDiv')) {
			top.mainFrame.document.getElementById('addNewFolderDiv').style.display = 'none';
		} else if(divType == 'movefiles') {
			window.location = '../movefiles/departmentContents.php?cab=$cab&doc_id=$curDoc_id&tab_id=$tab_id&table=$table';	
		} else {
			window.location = '../energie/addFolder.php';
		}
	}

	function submitFolder( cab, numIndices ) {
        var postStr = "";
        var inpField;
        var domDoc = createDOMDoc();
        var root = domDoc.createElement('ROOT');
        domDoc.appendChild(root);
        for(var i=0;i<numIndices;i++) {
            inpField = getEl('field-' + i);
            if(!inpField.validate()) {
                getEl('errMsg').firstChild.nodeValue = inpField.msg;
                return;
            }

			if(inpField.required == "1") {
				if(!inpField.value) {
					inpField.focus();
					getEl('errMsg').firstChild.nodeValue = 'Please fill in all required fids';
					return;
				}
			}

			if(inpField.regex) {
				check4ValidRegex(inpField);
				if(!inpField.ifValidRegex) {
					inpField.select();
					getEl('errMsg').firstChild.nodeValue = 'Please fill in the proper format';
					return;
				}
			}

            var folder = domDoc.createElement('FOLDER');
            root.appendChild(folder);

            k = domDoc.createElement('KEY');
            k.appendChild(domDoc.createTextNode(inpField.name));
            folder.appendChild(k);

            postValue = inpField.value;
            v = domDoc.createElement('VALUE');
            v.appendChild(domDoc.createTextNode(postValue));
            folder.appendChild(v);
        }
        postStr = domToString(domDoc);

        if (window.XMLHttpRequest)
            xmlhttp = new XMLHttpRequest();
        else if (window.ActiveXObject)
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

        URL = "../search/searchResultsAction.php?cab="+cab+"&checkFolder=1";
        xmlhttp.open('POST', URL, true);
        xmlhttp.setRequestHeader('Content-Type',
                                'application/x-www-form-urlencoded');
        xmlhttp.send(postStr);
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
                if( xmlhttp.responseText == 0 || existing == 0 ) {
                    document.addFolder.submit();
                } else {
                    answer = window.confirm( "This Folder Already Exists. Do you wish to add duplicate?")
                    if( answer == true )
                        document.addFolder.submit();
                }
            }
        };
    }
	
	function registerFuncs() {
		var indexStr = '$fieldStr';
		var indices = indexStr.split(",,,");
		var inpField, newImg;
		for(i = 0; i < indices.length; i++) {
			inpField = getEl('field-' + i);
			if(inpField) {
				if(dateFunctions && (indices[i].search(/date/i) != -1 || indices[i].search(/DOB/i) != -1)) {
					inpField.validate = validateDate;
					newImg = document.createElement('img');
					newImg.src = '../images/edit_16.gif';
					newImg.style.cursor = 'pointer';
					newImg.style.verticalAlign = 'middle';
					newImg.input = inpField;
					newImg.onclick = dispCurrMonth;
					inpField.parentNode.insertBefore(newImg, inpField.nextSibling);
				} else {
					inpField.validate = function(){return true;};
				}

				if(autoComp && i == 0) {
					inpField.autoComp = true;
				}
			}
		}
		
	}

	function allowDigi(evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		if( charCode == 13) {
			submitFolder("$cab","$numIndices");
		}
		return true;
	}

	function submitAutoComplete(evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
		if( charCode == 13 || charCode == 9 ) {
			searchAutoComplete();	
		}
		return true;
	}

	function searchAutoComplete() {
		var postStr = "";
		inpField = getEl('field-0');

        if (window.XMLHttpRequest)
            xmlhttp = new XMLHttpRequest();
        else if (window.ActiveXObject)
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

        URL = "../search/searchResultsAction.php?cab="+cab+"&search="+inpField.value+"&autoComp=1";
        xmlhttp.open('POST', URL, true);
        xmlhttp.setRequestHeader('Content-Type',
                                'application/x-www-form-urlencoded');
        xmlhttp.send(null);
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
				var xml = xmlhttp.responseXML;
				indArr = xml.getElementsByTagName('INDICE');
				if(indArr.length > 0) {
					for(i=0;i<indArr.length;i++) {
						var val = "";
						if(indArr[i].firstChild) {
							val = indArr[i].firstChild.nodeValue;
						}
						getEl('field-'+i).value = val;
					}
				} else {
					i = 1;
					while(el = getEl('field-'+i)) {
						el.value = "";
						i++;
					}
				}
				if(el = getEl('field-1')) {
					el.select();
				}
            }
        };
	}
	function check4ValidRegex(el) {
		if(el.value) {
			var regExpObj = new RegExp(el.regex);
			v = regExpObj.exec(el.value);
			if( regExpObj.test(el.value) ) {
				getEl(el.displayDiv).style.color = 'Lime';	
				el.ifValidRegex = 1;
			} else {
				getEl(el.displayDiv).style.color = 'red';	
				el.ifValidRegex = 0;
				el.select();
				getEl('errMsg').firstChild.nodeValue = 'Invalid '+el.name;
				return false;
			}
		} else {
			getEl(el.displayDiv).style.color = 'Lime';	
			el.ifValidRegex = 1;
		}
		getEl('errMsg').firstChild.nodeValue = '';
		return true;
	}
</script>
</head>
<body class="centered" onload="focusField();registerFuncs()">
HTML;

if($user->noCabinets()){
  die("<div class=\"error\">$dieMessage</div></body></html>");
}
  echo <<<HTML
<div class="mainDiv">
<div class="mainTitle">
<span>$createNewFo</span>
</div>
<form name="getDepartment" action="{$_SERVER['PHP_SELF']}">
<table class="inputTable">
<tr>
<td class="label">
<label for="cabSel">$selectCabLabel</label>
</td>
<td>
HTML;
  if(strcmp($parent,'movefiles') ==0) {
	if(isSet($_GET['original'])) {
		$originalCab = $_GET['original'];
	} else {
		$originalCab = $cab;
	}
  } else {
  	$originalCab = '';
  }
  $user->addCabinetJscript("getDepartment");
  $user->getCab( "addFolder.php?parent=$parent&doc_id=$curDoc_id&tab_id=$tab_id&original=$originalCab&table=$table",$user,2 );
  echo "</td>\n</tr>\n</table></form>";
  
  if( $cab ) {
	if( strcmp($parent, "inbox") == 0 ) {
		if (isset ($_GET['search'])) {
			$value = $_GET['search'];
		} else {
			$value = '';
		}
		echo "<form name=\"addFolder\" method=\"post\" ";
		echo "action=\"updateFolder.php?cab=$cab&parent=$parent&table=$table&search=$value\">";
	} elseif( strcmp($parent,"movefiles") == 0 ) {
		echo "<form name=\"addFolder\" method=\"post\" ";
		echo "action=\"updateFolder.php?cab=$cab&doc_id=$curDoc_id&tab_id=$tab_id&parent=$parent&table=$table&original=$originalCab\">";
	} else
		echo "<form name=\"addFolder\" method=\"post\" action=\"updateFolder.php?cab=$cab\">";

    if($user->checkSecurity($cab) > 1 ) {
		echo "<table class=\"inputTable\">\n";
		for($i=0; $i<sizeof($fieldnames); $i++) {
			//Displays the fieldname
			echo "<tr>\n";
			echo "<td class=\"label\"><label for=\"field-$i\">";
			echo str_replace("_"," ",$fieldnames[$i]);
			echo "</label></td>\n";
			//Displays textfield
			echo "<td>\n";
			$bg = "white";
			$required = 0;
			if(isSet($ffArr[$fieldnames[$i]]) && $ffArr[$fieldnames[$i]]['required']) {
				$bg = "gold";
				$required = 1;
			}
			if( !isSet( $indiceArr[$fieldnames[$i]] ) ) {	

				echo "<input type=\"text\" 
						id=\"field-$i\" 
						name=\"{$fieldnames[$i]}\" 
						value=\"\"
						style=\"background-color:$bg\"
						required=$required
						ifValidRegex=0
						displayDiv=\"disp$i\"
						size=\"25\"";
				if(isSet($ffArr[$fieldnames[$i]]) && $ffArr[$fieldnames[$i]]['regex']) {
					echo "regex=\"{$ffArr[$fieldnames[$i]]['regex']}\"";
					echo "onblur=\"check4ValidRegex(this)\"";
				}
				if($autoComp && $i == 0) {
					echo "onkeydown=\"submitAutoComplete(event)\" />"; 
				} else {
					echo "onkeypress=\"allowDigi(event)\" />";
				}

				if(isSet($ffArr[$fieldnames[$i]]) && $ffArr[$fieldnames[$i]]['display']) {
					echo "<span id=\"disp$i\" style=\"color:red\">{$ffArr[$fieldnames[$i]]['display']}</span>";
				}
			} else {
				echo "<select id=\"field-$i\" 
					name=\"$fieldnames[$i]\" 
					style=\"background-color:$bg\"
					required=$required >\n";
				//echo "<option value=\"\"></option>\n";
				foreach( $indiceArr[$fieldnames[$i]] AS $defs )
					echo "<option value=\"$defs\">$defs</option>\n"; 
			}

			echo "</td>\n";
			echo "</tr>\n";
		}
	echo "</table>\n";
    echo "<div>\n";
	echo "<div style=\"float: right\">\n";
	echo "<input type='button' value='Cancel' onclick='hideAddFolder()' name='btnCnl'/>";
	echo "<input type=\"button\" value=\"$addFolderButton\" ";
	echo "onclick=\"submitFolder('$cab',$numIndices)\" name=\"btnAdd\"/></div>\n";
	echo "<div id=\"errMsg\" class=\"error\">\n";
	if( $mess != null )
		echo str_replace("_"," ",$mess);
	else
		echo "&nbsp;";
	
	echo "</div>\n";
	echo "</div>\n";
    echo "</form>\n";
	echo "</div>\n";
	echo "</body>\n</html>\n";
    } else {
		logUserOut();
    }
  } else
	echo "</div></body>\n</html>";
	setSessionUser($user);
} else {
	logUserOut();
}
?>
