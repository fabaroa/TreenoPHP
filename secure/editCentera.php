<?php
require_once '../check_login.php';
require_once '../settings/settings.php';

if ($logged_in and $user->username and $user->isSuperUser ()) {
	$db_doc = getDbObject ('docutron');
	$settings = new GblStt ($user->db_name, $db_doc);
	$centeraSettings = array ();
	foreach ($user->cabArr as $cabinet => $dispName) {
		$settingValue = $settings->get ('centera_' . $cabinet);
		if ($settingValue !== false) {
			$centeraSettings[$cabinet] = $settingValue;
		} else {
			$centeraSettings[$cabinet] = 2;
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Centera Settings</title>
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script type="text/javascript" src="../lib/behaviour.js"></script>
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<link rel="stylesheet" type="text/css" href="../lib/style.css"/>
	<script type="text/javascript">
	var behaviors = {
		'#submitBtn' : function (element) {
			element.onclick = function () {
				var xmlDoc = createDOMDoc ();
				var root = xmlDoc.createElement ('root');
				xmlDoc.appendChild (root);
				var myInputs = document.getElementsByTagName ('input');
				for (var i = 0; i < myInputs.length; i++) {
					if (myInputs[i].type == 'radio' && myInputs[i].checked) {
						var settEl = xmlDoc.createElement ('setting');
						settEl.setAttribute ('name', myInputs[i].name);
						settEl.appendChild (xmlDoc.createTextNode (myInputs[i].value));
						root.appendChild (settEl);
					}
				}
				var myAjax = new Ajax.Request('centeraPostRequest.php',
								{	method: 'post',
									postBody: domToString(xmlDoc),
									onComplete: receiveXML,
									onFailure: reportError} );
			}
		}
	};

	function receiveXML(req) {
		var eMsg = getEl('errorMsg');	
		clearDiv(eMsg);
		if(req.responseXML) {
			var XML = req.responseXML;
			var mess = XML.getElementsByTagName('MESSAGE');
			if(mess.length > 0) {
				eMsg.appendChild(document.createTextNode(mess[0].firstChild.nodeValue));
			}
		} else {
			eMsg.appendChild(document.createTextNode('An Error Occured Loading the XML'));
		}
	}

	function reportError(req) {
		getEl('errorMsg').appendChild(document.createTextNode('An Error Occured Loading the XML'));
	}
	
	Behaviour.register(behaviors);
	</script>
</head>
<body class="centered">
<div class="mainDiv">
<div class="mainTitle">
<span>Centera Settings</span>
</div>
<div class="inputForm">
<div style="text-align:center;height:25px;font-weight:bold"><?php echo "Centera Host: ".$DEFS['CENT_HOST']; ?></div>
<table>
<tr>
<th>Cabinet</th>
<th>Always On</th>
<th>Optional</th>
</tr>
<?php foreach ($centeraSettings as $cabinet => $centSett): ?>
	<tr>
	<td>
		<?= $user->cabArr[$cabinet] ?>
	</td>
	<td>
		<?php if ($centSett == 1): ?>
			<input 
				type="radio"
				name="centera_<?= $cabinet ?>"
				value="1" 
				checked="checked"
			/>
		<?php else: ?>
			<input 
				type="radio"
				name="centera_<?= $cabinet ?>"
				value="1" 
			/>
		<?php endif ?>
	</td>
	<td>
		<?php if ($centSett == 2): ?>
			<input 
				type="radio"
				name="centera_<?= $cabinet ?>"
				value="2" 
				checked="checked"
			/>
		<?php else: ?>
			<input 
				type="radio"
				name="centera_<?= $cabinet ?>"
				value="2" 
			/>
		<?php endif ?>
	</td>
	</tr>
<?php endforeach ?>
</table>
</div>
<div style="text-align:right">
	<span id="errorMsg" class="error" style="padding-right:25px"></span>
	<button id="submitBtn">Submit</button>
</div>
</div>
</body>
</html>
<?php
}

?>
