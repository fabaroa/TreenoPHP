<?php

// $Id: newCabinet.php 15033 2013-09-18 18:28:27Z fabaroa $

include_once '../check_login.php';
include_once '../lib/cabinets.php';
include_once '../lib/quota.php';

if ($logged_in == 1 && strcmp($user->username, "") != 0 && $user->isDepAdmin()) {
	$newCabLabel = $trans['New Cabinet'];
	//$submitButton = $trans['Submit'];
	$cabNameLabel = $trans['Cabinet Name'];
	$indexLabel = $trans['Index'];
	$missingCabName = $trans['Missing Cabinet Name'];
	$badChar = $trans['Invalid Characters'];
	$reserved = $trans['Reserved Word'];
	$taken = $trans['Cabinet Name Taken'];
	$duplError = $trans['Duplicate Index Error'];
	$badFstChar = $trans['First Character Invalid'];
	$minOneField = $trans['Minimum One Index Required'];
echo<<<ENERGIE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Create New Cabinet</title>
<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
<script type="text/javascript" src="../lib/prototype.js"></script>
<script type="text/javascript" src="../lib/settings.js"></script>
<script type="text/javascript">
	var emptyIndices = 0;

	function submitCabinet() {
		var err = document.getElementById('errorDiv');
		var fields = document.getElementsByTagName('input');
		if( fields[0].value != "" ) {
			domdoc = createDOMDoc();
			rootDoc = domdoc.createElement("ROOT");
			cabDoc = domdoc.createElement("CABINET");
			rootDoc.appendChild(cabDoc);
			domdoc.appendChild(rootDoc);
			cabDoc.appendChild(domdoc.createTextNode(fields[0].value));
		} else {
			while( err.childNodes[0] ) {
				err.removeChild(err.childNodes[0]);
			}
			err.appendChild(document.createTextNode('$missingCabName'));
			return false;
		}
		for(var i=1;i<fields.length;i++) {
			if( fields[i].type == 'text' && fields[i].value != "" ) {
				indice = domdoc.createElement("INDICE");
				rootDoc.appendChild(indice);
				indice.appendChild(domdoc.createTextNode(fields[i].value));
			} else {
				emptyIndices++;	
			}
		}

		if( emptyIndices == fields.length-1 ) {
			while( err.childNodes[0] ) {
				err.removeChild(err.childNodes[0]);
			}
			err.appendChild(document.createTextNode('$minOneField'));
			return false;
		} else {
			while( err.childNodes[0] ) {
				err.removeChild(err.childNodes[0]);
			}
			err.appendChild(document.createTextNode('Creating Cabinet...please wait'));
			var domString = domToString(domdoc);
			var xmlhttp = getXMLHTTP();
			xmlhttp.open('POST','cabinetActions.php?createCab=1',true);
			xmlhttp.setRequestHeader('Content-Type',
							 'application/x-www-form-urlencoded');
			xmlhttp.send( domString );
			xmlhttp.onreadystatechange = function() {
				if (xmlhttp.readyState == 4) {
					XML = xmlhttp.responseXML;
					if(XML) {
						var cabInfo = XML.getElementsByTagName("CABINET");
						var mess = XML.getElementsByTagName("MESSAGE");
						if( cabInfo.length > 0 ) {
							var DepID = cabInfo[0].firstChild.nodeValue;
							parent.searchPanel.window.location =
								'leftAdmin.php';
							parent.mainFrame.window.location = 'cabinetAccess.php?DepID='+DepID;
						} else {
							while( err.childNodes[0] ) {
								err.removeChild(err.childNodes[0]);
							}
							if(mess.length > 0) {
								$('cabCreate').focus();
								err.appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
								parent.searchPanel.window.location =
									'leftAdmin.php';
							}
						}
					}
				}
			};
		}
	}

	function allowKeys(evt) {
        evt = (evt) ? evt : event;
        var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);
        var pool = "1234567890 ";
        pool += "abcdefghijklmnopqrstuvwxyz";
        pool += "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        var character = String.fromCharCode(charCode);

        if( (pool.indexOf(character) != -1)
                || (charCode == 8) || (charCode == 9) || (charCode == 37) || (charCode == 39) )
            return true;
        else
            return false;
    }

	function focusFirst() {
		var toFocus = document.getElementById('cabinet');
		if(toFocus) {
			toFocus.focus();
		}
	}
</script>
</head>
<body class="centered" onload="focusFirst()">
<div class="mainDiv">
<div class="mainTitle">
<span>$newCabLabel</span>
</div>
ENERGIE;
		echo "<div style=\"padding:5px\">\n";
		echo "<table class=\"inputTable\">\n";
		echo " <tr>\n";
		echo "  <td class=\"label\"><label for=\"cabinet\">$cabNameLabel</label></td>\n";
		echo "  <td><input size=\"33\" onkeypress=\"return allowKeys(event)\" type=\"text\" id=\"cabCreate\" name=\"cabinet\" maxlength=\"70\" /></td>\n";
		echo " </tr>\n";
		for ($i = 1; $i < 11; $i ++) {
			echo "  <tr>\n";
			echo "	<td class=\"label\"><label for=\"field-$i\">$indexLabel ";
			echo $i;
			echo "</label></td>\n";
			echo "   <td>\n<input size=\"33\" onkeypress=\"return allowKeys(event)\" id=\"field-$i\" type=\"text\" name=\"indexfield";
			echo $i;
			echo "\"/>\n</td>\n";
			echo "  </tr>\n";
		}
		echo "</table>\n";
		echo "<div>\n";
		echo " <div style=\"float: right\">\n";
		echo "  <input type=\"button\" name=\"submit\" onclick=\"submitCabinet()\" value=\"Save\"/>\n";
		echo " </div>\n";
		echo "<div>&nbsp;</div>";
		if( isset($_GET['message'] )) {
			echo "<div id='errorDiv' class=\"error\">{$_GET['message']}</div>\n";
		} else {
			echo "<div id='errorDiv' class=\"error\">&nbsp;</div>\n";
		}
		echo "</div>\n";
		echo "</div>\n";
		echo "</div>\n";

	setSessionUser($user);
} else {
	logUserOut();
}
?>
</body>
</html>
