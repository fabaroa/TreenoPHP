<?php
include_once '../lib/licenseFuncs.php';

$modArr = getModulesArray();
?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Treeno Licensing</title>
	<script type="text/javascript" src="../lib/prototype.js"></script>
	<script type="text/javascript" src="../lib/behaviour.js"></script>
	<script type="text/javascript" src="../yui/yahoo.js"></script>
	<script type="text/javascript" src="../yui/event.js"></script>
	<script type="text/javascript" src="../yui/connection.js"></script>
	<script type="text/javascript" src="../yui/json.js"></script>
	<script type="text/javascript">
		function generateLicense() {
			var mac = $('mac').value;
			var exp = $('expire').value; 
			var	lic = $('lic').value; 
			var chkArr = document.getElementsByClassName('checkbox');	
			var modArr = new Array();
				for(i=0;i<chkArr.length;i++) {
					modArr[modArr.length] = {id: chkArr[i].num, name: chkArr[i].name, selected: (chkArr[i].checked) ? 1 : 0 };
				}
			var json = { macAddress: mac, expiration: exp, licenses: lic, modules: modArr };
			var jsonStr = YAHOO.lang.JSON.stringify(json,null);

			var callbacks = {
				success: suc,
				failure: fail
			};
			url = '../secure/testing.php';
			var transaction = YAHOO.util.Connect.asyncRequest('POST',url,callbacks,jsonStr);
		}	

		function suc(o) {
			alert(o.responseText);
		}

		function fail() {

		}
	</script>
	<style type="text/css">
		.container {
			width	: 500px;
			padding	: 5px;
		}

		.labelDiv {
			float			: left;
			width			: 100px;
			text-align		: right;
			padding-right	: 5px;
		}

		.textDiv {
			float		: left;
			width		: 300px;
			text-align	: left;
		}

		.macInput {
			width				: 150px;
			font-size			: 8pt;
			background-color	: lightyellow;
		}

		.expInput {
			width				: 75px;
			font-size			: 8pt;
			background-color	: lightyellow;
		}

		.licInput {
			width				: 30px;
			font-size			: 8pt;
			background-color	: lightyellow;
		}

		.mod1, .mod2 {
			float	: left;
			width	: 150px;
		}

		.checkbox {
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="labelDiv">
			<span>MAC Address</span>
		</div>
		<div class="textDiv">
			<input type="text" id="mac" name="mac" value="" class="macInput" />
		</div>
	</div>
	<div class="container">
		<div class="labelDiv">
			<span>Expiration</span>
		</div>
		<div class="textDiv">
			<input type="text" id="expire" name="expire" value="" class="expInput"/>
		</div>
	</div>
	<div class="container">
		<div class="labelDiv">
			<span>Licenses</span>
		</div>
		<div class="textDiv">
			<input type="text" id="lic" name="lic" value="" class="licInput" />
		</div>
	</div>
	<div class="container">
		<div class="labelDiv">Modules</div>
		<div class="textDiv">
			<?php for($i=0;$i<count($modArr);$i++): 
				$mArr = array_keys($modArr[$i]); 
			?>
			<div>
				<div>
					<input id="<?php echo $mArr[0] ?>" 
						type="checkbox" 
						num="<?php echo $i ?>"
						name="<?php echo $mArr[0] ?>" 
						value="" 
						class="checkbox"
						/>
					<span><?php echo str_replace("_"," ",$mArr[0]) ?></span>
				</div>
			</div>
			<?php endfor; ?>
		</div>
	</div>
	<div class="container">
		<div class="labelDiv"></div>
		<div class="textDiv">
			<input type="button" name="B1" value="Generate" onclick="generateLicense()" />
		</div>
</body>
</html>
