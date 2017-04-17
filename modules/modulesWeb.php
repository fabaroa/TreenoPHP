<?php
// $Id: modulesWeb.php 14326 2011-04-11 20:31:25Z fabaroa $

include_once '../check_login.php';
include_once '../classuser.inc';
include_once 'modules.php';
include_once '../version.php';
include_once '../updates/updatesFuncs.php';

if( $logged_in == 1 && strcmp($user->username, "")!=0 && $user->isDepAdmin()) {
    $tableTitle_1        = $trans['Modules'];
    $ver                 = $trans['Version'];
	
	$db_doc = getDbObject('docutron');//connects to docutron database
	$sArr = array('arb_name','enabled');
	$wArr = array('department'=>$user->db_name);
	$modArr = getTableInfo($db_doc,'modules',$sArr,$wArr,'getAssoc');
	if (!isset ($modArr['Administration'])) {
		$modArr['Administration'] = 0;
	}
	$ct = 1;
	uksort($modArr,"strnatcasecmp");
?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>System Modules</title>
	<link REL="stylesheet" TYPE="text/css" HREF="../lib/style.css">
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<script type="text/javascript" src="../lib/settings.js"></script>
	<script>
		function toggleModules(type) {
			var ct = 1;
			while(el = getEl(type+'-'+ct)) {
				if(el.disabled == false) {
					el.checked = true;
				}
				ct++;
			}
		}

		function updateModules() {
			var xmlDoc = createDOMDoc();
			var root = xmlDoc.createElement('ROOT');
			xmlDoc.appendChild(root);
	
			var ct = 1;		
			while(el = getEl('enable-'+ct)) {
				if(el.checked == true && el.disabled == false) {
					var mod = xmlDoc.createElement('MODULE');	
					root.appendChild(mod);

					mod.appendChild(xmlDoc.createTextNode(el.name));
				}
				ct++;
			}

			var xmlStr = domToString(xmlDoc);
			var newAjax = new Ajax.Request( 'modulesActions.php?updateModules=1',
										{	method: 'post',
											postBody: xmlStr,
											onComplete: receiveXML,
											onFailure: reportError} );
		}

		function receiveXML(req) {
			var XML = req.responseXML;
			var mess = XML.getElementsByTagName('MESSAGE');
			if(mess.length > 0) {
				clearDiv(getEl('errMsg'));		
				m = mess[0].firstChild.nodeValue;
				getEl('errMsg').appendChild(document.createTextNode(m));
			}
		}

		function reportError(req) {
			
		}
	</script>
</head>
<body>
	<div class="mainDiv">
		<div class='mainTitle'>
			<span><?php echo $tableTitle_1 ?></span>
		</div>
		<div class="inputForm" style="margin:0px">
			<table>
				<tr>
					<th>
						<span>Module</span>
					</th>
					<th style="cursor:pointer"
						onmouseover="this.style.backgroundColor='#888888'"
						onmouseout="this.style.backgroundColor='#ffffff'"
						onclick="toggleModules('enable')"
					>
						<span>Enabled</span>
					</th>
					<th style="cursor:pointer"
						onmouseover="this.style.backgroundColor='#888888'"
						onmouseout="this.style.backgroundColor='#ffffff'"
						onclick="toggleModules('disable')"
					>
						<span>Disabled</span>
					</th>
				</tr>
				<?php foreach($modArr AS $name => $perm): ?>
				<tr style="cursor:pointer"
					onmouseover="this.style.backgroundColor='#ebebeb'"
					onmouseout="this.style.backgroundColor='#ffffff'"
				>
					<td>
						<span><?php echo $name ?></span>
					</td>
					<td>
						<input type="radio" 
							id="enable-<?php echo $ct ?>"
							name="<?php echo $name ?>" 
							value="1" 
							<?php if($perm == 1): ?>
							checked="checked"
							<?php endif ?>
							<?php if($name == "Administration" || !$user->isSuperUser() || $modArr['Administration'] == 0): ?>
							disabled="disabled"
							<?php endif ?>
						/>
					</td>
					<td>
						<input type="radio" 
							id="disable-<?php echo $ct++ ?>"
							name="<?php echo $name ?>" 
							value="0" 
							<?php if($perm == 0): ?>
							checked="checked"
							<?php endif ?>
							<?php if($name == "Administration" || !$user->isSuperUser() || $modArr['Administration'] == 0): ?>
							disabled="disabled"
							<?php endif ?>
						/>
					</td>
				</tr>
				<?php endforeach ?>
			</table>
			<?php if($user->isSuperUser() && $modArr['Administration'] == 1): ?>
			<div style="text-align:right;padding-top:5px;padding-left:15px">
				<span id="errMsg" class="error"></span>
				<input type="button" 
					name="B1" 
					value="Save" 
					onclick="updateModules()"	
				/>
			</div>
			<?php endif ?>
		</div>
	</div>
	<div style="position:absolute;bottom:10px">
		<span><?php echo $ver.": ".$version ?></span>
		<br/>
		<span><?php echo $versionDate ?></span>
		<br/>
		<span><?php echo "MAC: ".getMAC($_SERVER['SERVER_ADDR'], $DEFS) ?></span>
		<?php if($user->isDepAdmin()): ?>
		<input type="button" 
			name="B2"
			onclick="location.href ='support.php'" 
			value="Support" 
		/>
		<?php endif ?>
	</div>
</body>
</html>
<?php
	setSessionUser($user);
} else {
	logUserOut();
}
?>
