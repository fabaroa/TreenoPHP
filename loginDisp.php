<?php

class loginDisp {
	var $trans;
	var $availLangs;
	var $language;
	var $settings;
	function setTrans($trans, $settings) {
		$this->trans = $trans;
		$this->settings = $settings;
	}
	function setAvailLangs($availLangs, $language) {
		$this->availLangs = $availLangs;
		$this->language = $language;
	}
	function printLangLogin() {
		echo <<<ENERGIE
<tr>
	<td>
		<div class="label">
			{$this->trans['Language']}:
		</div>
	</td>
	<td class="inputTD">
		<select class="inputBox" tabindex="4" name="lang" onchange="submit()">

ENERGIE;
		foreach($this->availLangs as $myLang):
			if($myLang == $this->language):
				echo <<<ENERGIE
			<option selected="selected" value="$myLang">
				{$this->trans[strtolower($myLang)]}
			</option>

ENERGIE;
			else:
				echo <<<ENERGIE
			<option value="$myLang">
				{$this->trans[strtolower($myLang)]}
			</option>

ENERGIE;
			endif;
		endforeach;
		echo <<<ENERGIE
		</select>
	</td> 
</tr>

ENERGIE;
	}
	
	function printLoginBox() {
		echo <<<ENERGIE
<table id="loginbox">
	<tr>
		<td colspan="2">
		</td>
	</tr>
	<tr>
		<td>
			<div class="label">
				{$this->trans['Username']}:
			</div>
		</td>
		<td class="inputTD">

ENERGIE;
		echo <<<ENERGIE
		<input class="inputBox" tabindex="1" type="text" name="uname" maxlength="50" />
ENERGIE;
		echo <<<ENERGIE
		</td>
	</tr>
	<tr>
		<td>
			<div class="label">
				{$this->trans['Password']}:
			</div>
		</td>
		<td class="inputTD">
			<input class="inputBox" tabindex="2" type="password" name="passwd" maxlength="50" />
		</td>
	</tr>
ENERGIE;
		if($this->settings->get('langlogin') == "on"):
			$this->printLangLogin();
		endif;
		echo <<<ENERGIE
	<tr>
		<td colspan="2">
			<div class="label">
				<input tabindex="3" type="submit" name="submitted" value="{$this->trans['Login']}" />
			</div>
		</td>
	</tr>
</table>
ENERGIE;
	}
	
	
	function printResetPasswordBox($uname) {
		$username = base64_decode($uname);
		echo <<<ENERGIE
<table id="loginbox">
	<tr>
		<td colspan="2">
		</td>
	</tr>
	<tr>
		<td colspan="2">
			$username, you are required to change your password before logging in. 
		</td>
	</tr>
	<tr>
		<td>
			<div class="label">
				Old Password:
			</div>
		</td>
		<td class="inputTD">

ENERGIE;
		echo <<<ENERGIE
		<input class="inputBox" tabindex="1" type="password" name="oldpass" maxlength="50" />
ENERGIE;
		echo <<<ENERGIE
		</td>
	</tr>
	<tr>
		<td>
			<div class="label">
				New Password:
			</div>
		</td>
		<td class="inputTD">
			<input class="inputBox" tabindex="2" type="password" name="newpass" maxlength="50" />
		</td>
	</tr>
	<tr>
		<td>
			<div class="label">
				Confirm Password:
			</div>
		</td>
		<td class="inputTD">
			<input class="inputBox" tabindex="2" type="password" name="nconfpass" maxlength="50" />
		</td>
	</tr>	
ENERGIE;

		echo <<<ENERGIE
	<tr>
		<td colspan="2">
			<div class="label">
				<input tabindex="3" type="submit" name="submitted" value="Save Changes" />
			</div>
		</td>
	</tr>
</table>
ENERGIE;
	}

	function printSecurity() {
		echo <<<ENERGIE
<div class="centered login">
	{$this->trans['Security Level']}:
ENERGIE;
	if(isset($_SERVER['HTTPS'])):
		echo <<<ENERGIE
		<span style="cursor: pointer" onclick="toggleURL()">
			<img id="lock" src="energie/images/lock.gif" alt="Insecure" title="" />
			{$this->trans['Secure SSL']}
		</span>
ENERGIE;
	else:
		echo <<<ENERGIE
		<span style="cursor: pointer" onclick="toggleURL()">
			<img id="lock" src="energie/images/unlock.gif" alt="Secure" title="" />
			{$this->trans['Standard']}
		</span>
ENERGIE;
	endif;
	echo <<<ENERGIE
</div>
ENERGIE;
	}
}

?>
