<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:param name="selected_id" select="'default'" />
<xsl:template match="/department">
<xsl:for-each select="cabinet[@ID=$selected_id]">
<form name="form_all" method="POST">
<tr>
   <td valign="top" class="sideMenu"><div class="sideMenu">Search All Fields</div></td>
</tr>
<tr>
   <td valign="top" class="sideMenu"><input type="textfield" name="search_text" size="16"/></td>
</tr>
<tr>
   <td valign="top" class="sideMenu" align="right"><input type="button" name="submit" value="GO" onclick="submission_check('all')"/></td>
</tr>
</form>
      <xsl:for-each select="index_name">
      <form name="form_{.}" method="POST">
      <tr>
         <td valign="top" class="sideMenu"><div class="sideMenu"><xsl:value-of select="."/></div></td>
      </tr>
      <tr>
         <td valign="top" class="sideMenu">
           <input type="textfield" name="search_text" size="16">
	   </input>
         </td>
      </tr>
      <tr>
         <td valign="top" class="sideMenu" align="right">
           <input type="button" name="submit" value="GO">
             <xsl:attribute name="onclick">submission_check('<xsl:value-of select="."/>');</xsl:attribute>
           </input>
         </td>
      </tr>
     </form>
     </xsl:for-each>
</xsl:for-each>
</xsl:template>
</xsl:stylesheet> 
