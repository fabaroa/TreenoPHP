<?php
include_once '../version.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "https://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Treeno Software :: Support Ticket System</title>
    <link rel="stylesheet" href="../modules/main.css" media="screen">
    <link rel="stylesheet" href="../modules/colors.css" media="screen">
</head>
<body>
<div id="container">
    <div id="header">
        <img src="../images/logo_whitebg.gif" border=0 alt="Support Center">
        <p><span>TREENO SUPPORT TICKET</span> SYSTEM</p>
    </div>
    <div id="content">
<div>
    </div>
<div>Please fill in the form below to open a new ticket.</div><br>
<form name="myform" action="http://kb.treenosoftware.com/support/open.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="message" value="" />
<table align="left" cellpadding=2 cellspacing=1 width="90%">
    <tr>
        <th width="20%">Full Name:</th>
        <td>
                            <input type="text" name="name" size="25" value="">
	                    &nbsp;<font class="error">*&nbsp;</font>
        </td>
    </tr>
    <tr>
        <th nowrap >Email Address:</th>
        <td>
                         
                <input type="text" name="email" size="25" value="">
                        &nbsp;<font class="error">*&nbsp;</font>
        </td>
    </tr>
    <tr>
        <td>Telephone:</td>
        <td><input type="text" name="phone" size="25" value="">
             &nbsp;Ext&nbsp;<input type="text" name="phone_ext" size="6" value="">
            &nbsp;<font class="error">&nbsp;</font></td>
    </tr>
    <tr height=2px><td align="left" colspan=2 >&nbsp;</td</tr>
    <tr>
        <th>Help Topic:</th>
        <td>
            <select name="topicId">
                <option value="" selected >Select One</option>
                                    <option value="2">Billing</option>
                                    <option value="1">Support</option>
                                <option value="0" >General Inquiry</option>
            </select>
            &nbsp;<font class="error">*&nbsp;</font>
        </td>
    </tr>
    <tr>
        <th>Short Description:</th>
        <td>
            <input type="text" name="subject" size="35" value="">
            &nbsp;<font class="error">*&nbsp;</font>
        </td>
    </tr>
    <tr>
      <td style="width: 30%; min-width: 50px; max-width: 200px;">Treeno Version: </td>
      <td style="width: 70%; min-width: 70%;"> <input name="Version" size="10" maxlength="10" value="" type="text" onChange="javascript:click_on_select();"> (Settings/System Infomation/License Infomation)</td>
    </tr>
    <tr>
      <td style="width: 30%; min-width: 50px; max-width: 200px;">Treeno Module:</td>
      <td style="width: 70%; min-width: 70%;">
      <select name="Module" onChange="javascript:click_on_select();">
      <option value="0" selected> Please Select </option>
      <option value="File Monitor"> File Monitor </option>
      <option value="Legacy Integrator"> Legacy Integrator </option>
      <option value="Outlook"> Outlook </option>
      <option value="Cabinets"> Cabinets </option>
      <option value="Workflow"> Workflow </option>
      <option value="Portal/Publishing"> Portal/Publishing </option>
      <option value="User"> User </option>
      <option value="Documents"> Documents </option>
      <option value="Group Access"> Group Access </option>
      <option value="Indexing"> Indexing </option>
      <option value="AMS Integration"> AMS Integration </option>
      <option value="Sagitta Integration"> Sagitta Integration </option>
      <option value="Sales Force Integration"> Sales Force Integration </option>
      <option value="Request for New Feature"> Request for NewFeature </option>
      <option value="Other"> Other </option>
      </select>
      </td>
    </tr>
    <tr>
        <th valign="top">Please Include as much detail as possible:</th>
        <td>
                        <textarea name="message2" cols="35" rows="8" wrap="soft" style="width:85%" onChange="javascript:click_on_select();"></textarea></td>
    </tr>
    <tr>
        <td>Attachment:</td>
        <td>
            <input type="file" name="attachment"><font class="error">&nbsp;</font>
        </td>
    </tr>
          <tr>
        <td>Priority:</td>
        <td>
            <select name="pri">
                                  <option value="1"  >Low</option>
                                  <option value="2" selected >Normal</option>
                                  <option value="3"  >High</option>
                          </select>
        </td>
       </tr>
    
        <tr height=2px><td align="left" colspan=2 >&nbsp;</td</tr>
    <tr>
        <td></td>
        <td>
            <input class="button" type="submit" name="submit_x" value="Submit Ticket">
            <input class="button" type="reset" value="Reset">
            <input class="button" type="button" name="cancel" value="Cancel" onClick='window.location.href="../tutorial/tutorial.php"'>    
        </td>
    </tr>
</table>
</form>
 <div style="clear:both"></div> 
 </div>
 <div id="footer"><!--Copyright &copy; osTicket.com. All rights reserved--></div>
 <div align="center">
    <!-- As a show of support, we ask that you leave powered by osTicket link to help spread the word. Thank you! -->
     <!--<a id="powered_by" href="https://osticket.com"><img src="./images/poweredby.jpg" width="126" height="23" alt="Powered by osTicket"></a> --> </div>
</div>
</body>
<script type="text/javascript">
function click_on_select()
{
	document.myform.message.value=document.myform.message2.value + ": Using Version:" + document.myform.Version.value + ": In Module:" + document.myform.Module.options[document.myform.Module.selectedIndex].value;
}
document.myform.Version.value="<?php echo $version; ?>";
</script>
</html>
