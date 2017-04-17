<?php
// $Id: editDepInfo.php 14862 2012-06-27 19:08:24Z cz $

include_once '../check_login.php';
include_once '../lib/quota.php';

if($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isSuperUser()) {
	if(isset($_GET['depLicenses'])) {
		$infoType = 1;
		$depLabel = 'Manage Department Licenses';
		$headerArr = array('Department', 'Limit');
		$selArr = array('SUM(dept_licenses)');
		$total = getTableInfo($db_doc,array('licenses'),$selArr,array(),'queryOne'); 

		$licenseType = getTableInfo($db_doc,array('licenses'),array('max'),array(),'queryOne'); 
		if($licenseType == -1) {
			$licenseType = 'disable';
		} else {
			$licenseType = 'enable';
		}
		
		$selArr = array('SUM(max_licenses)');
		$global_total = getTableInfo($db_doc,array('global_licenses'),$selArr,array(),'queryOne'); 
		//$total += $global_total;
	} else {
		$infoType = 0;
		$depLabel = 'Edit Department Quota';
		$headerArr = array('Department','Space Allowed','Space Used','New Allowed Size');
		$selArr = array('size_used');
		$total = getTableInfo($db_doc,'quota',$selArr,array(),'queryOne'); 
		$licenseType = 'enable';
	}
	$depList = getTableInfo($db_doc,'licenses',array(),array(),'query',array('arb_department'=>'ASC'));
echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>New Cabinet</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/settings.js"></script>
<script>
	var depInfoType = $infoType;
	var licenseType = "enable";
	function adjustInfo() {
		var xmlhttp = getXMLHTTP();
		var URL = 'departmentActions.php?editDepInfo=1';
		xmlhttp.open('POST',URL,true);	
		xmlhttp.setRequestHeader('Content-Type',
			'application/x-www-form-urlencoded');
		xmlhttp.send(getDepartmentInfo());
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
				if(xmlhttp.responseXML) {
					var errDiv = document.getElementById('errorMsg');
					clearDiv(errDiv);
					var XML = xmlhttp.responseXML;
					var err = XML.getElementsByTagName('MESSAGE');
					if(err.length > 0) {
						if(err[0].getAttribute('error') == '1') {
							errDiv.appendChild(document.createTextNode(err[0].getAttribute('message')));
						} else {
							var txtFields = document.getElementsByTagName('input');
							for(var i=0;i<txtFields.length;i++) {
								if(txtFields[i].type == 'text') {
									var name = txtFields[i].name;
									var val = txtFields[i].value;
									if(val != '') {
										if(getEl('quotaScalar-'+name)) {
											var scalar = getEl('quotaScalar-'+name).value;
											clearDiv(getEl('allowed-'+name));
											getEl('allowed-'+name).appendChild(document.createTextNode(val+scalar));
										}
									}
								}
							}
							errDiv.appendChild(document.createTextNode(err[0].getAttribute('message')));
						}
					}	
				}
			}
		};
	}
	
	function getDepartmentInfo() {
		var xmlDoc = createDOMDoc();
		var rootDoc = xmlDoc.createElement('ROOT');
		if(depInfoType == 1) {
			rootDoc.setAttribute('func','editLicenses');
			rootDoc.setAttribute('licenseType',licenseType);
		} else {
			rootDoc.setAttribute('func','editQuota');
		}
		xmlDoc.appendChild(rootDoc);
		
		var txtFields = document.getElementsByTagName('input');
					
		//cz 2012-06-27
		if(depInfoType == 1)
		{
			for(var i=0;i<txtFields.length;i++) {
				if(txtFields[i].type == 'text' && txtFields[i].name == "limit") {
					var id = txtFields[i].id;
					var name = id.substring(6);
					var n = getEl(id);
					//alert("n: " + n.value);
					if(n.value != '') {
						var dep = xmlDoc.createElement('DEPARTMENT');
						//dep.setAttribute('dept_licenses',val);
						dep.setAttribute('limit',n.value);	
						dep.setAttribute('name',name);
						rootDoc.appendChild(dep);					
					} 
			
				}
			}		
		}
		else
		{
			for(var i=0;i<txtFields.length;i++) {
				if(txtFields[i].type == 'text' && txtFields[i].name != "limit") {
					var name = txtFields[i].name;
					var val = txtFields[i].value;
					var n = getEl('limit-'+name);
					if(val != '') {
						var dep = xmlDoc.createElement('DEPARTMENT');
						if(document.getElementById('quotaScalar-'+name)) {
							var scalar = document.getElementById('quotaScalar-'+name).value;
							dep.setAttribute('scalar',scalar);
							dep.setAttribute('quota',val);
						} 
						dep.setAttribute('name',name);
						rootDoc.appendChild(dep);
					}
				}
			}
		}
		var szDeptInfo = domToString(xmlDoc);
		//alert("getDepartmentInfo(): " +  szDeptInfo);
		return szDeptInfo;
	}

	function numericCheck(e) {
	        if (window.event)
        	        code = window.event.keyCode;
        	else if (e.which)
                	code = e.which;

		var pool = '1234567890';
		var num = String.fromCharCode(code);
		if((pool.indexOf(num) != -1) || code == 8 || code == 13 || (charCode == 37) || (charCode == 39)) {
                        return true;
		}
		return false;
	}

	function toggleSelectBoxes(toggle) {
		var selectBoxes = document.getElementsByTagName('select');
		for(var i=0;i<selectBoxes.length;i++) {
			if(toggle == 'enable') {
				selectBoxes[i].disabled = false;
			} else {
				selectBoxes[i].disabled = true;
			}
		}
		licenseType = toggle;
	}
</script>
<style>
td,th { 
     margin: auto;
     padding: 5px;
     text-align: center;
     white-space: nowrap;
   } 
</style>
</head>
<body class="centered" onload='toggleSelectBoxes("$licenseType")'>
<div class="mainDiv" style="width:550px">
<div class="mainTitle"><span>$depLabel</span></div>
HTML;
if(isSet($_GET['depLicenses'])) {
 echo "<div>\n";
 if($licenseType == "enable") {
	echo "<input type='radio' name='licenseType' value='1' checked=\"checked\" onclick='toggleSelectBoxes(\"enable\")'>Limit\n";
	echo "<input type='radio' name='licenseType' value='0' onclick='toggleSelectBoxes(\"disable\")'>Inherit\n";
 } else {
	echo "<input type='radio' name='licenseType' value='1' onclick='toggleSelectBoxes(\"enable\")'>Limit\n";
	echo "<input type='radio' name='licenseType' value='0' checked=\"checked\" onclick='toggleSelectBoxes(\"disable\")'>Inherit\n";
 }
 echo "</div>\n";
	}
echo<<<HTML
<table class="inputTable" style="width:550px">
HTML;
	echo "<tr>\n";
	foreach($headerArr AS $header) {
		echo "<th>$header</th>\n";
	}
	echo "</tr>\n";
	while($result = $depList->fetchRow()) {
		echo "<tr>\n";
		echo "<td>\n";
		echo "<label for='{$result['real_department']}'>{$result['arb_department']}::{$result['real_department']}</label>\n";
		echo "</td>\n";
		if(isset($_GET['depLicenses'])) {
			echo "<td>\n";
			echo "<input type='text' id='limit-{$result['real_department']}' name='limit' value='{$result['max']}' size='3'/>";
			echo "</td>\n";
		} else {
			echo "<td id='allowed-{$result['real_department']}'>".adjustQuota($result['quota_allowed'])."</td>\n";
			echo "<td>".adjustQuota($result['quota_used'])."</td>\n";
			echo "<td>\n";
			echo "<input id='new-{$result['real_department']}' type='text' name='{$result['real_department']}'";
			echo " value='' size='5' onkeypress='return numericCheck(event)'>\n";
			echo "<select id='quotaScalar-{$result['real_department']}' name='quotaScalar'>\n";
			echo "<option value='KB'>KB</option>\n";
			echo "<option value='MB'>MB</option>\n";
			echo "<option value='GB'>GB</option>\n";
			echo "<option value='TB'>TB</option>\n";
			echo "</select>\n";
			echo "</td>\n";
		}
		echo "</tr>\n";
	}
	echo "<tr>\n";
	if(isset($_GET['depLicenses'])) {
		echo "<td>Total allocated licenses</td>\n";
		echo "<td id='licenseTotal'>$total</td>\n";
	} else {
		echo "<td>Total</td>\n";
		echo "<td id='quotaTotal'>".adjustQuota($total)."</td>\n";
	}
	echo "</tr>\n";
	echo "</table>\n";
	echo "<div style='height: 25px'>\n";
	echo "<div id='errorMsg' class='error' style='float: left; width: 65%'></div>\n";
	echo "<div style='float: right; text-align:right; width: 30%'>\n";
	echo "<input type='button' name='update' onclick='adjustInfo()' value='Save'>\n";
	echo "</div>\n";
	echo "</div>\n";
echo<<<HTML
</div>
</body>
</html>
HTML;
	setSessionUser($user);
} else {
	logUserOut();
}
?>
