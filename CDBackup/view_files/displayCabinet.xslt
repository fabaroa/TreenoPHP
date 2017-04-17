<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:param name="selected_id" select="'default'" />
<xsl:template match="/">
	<center>
	  <xsl:for-each select="department/cabinet[@ID=$selected_id]">
            <p><img src="images/cabinet.gif" border="0"/><b><xsl:value-of select="@name"/></b></p>
	    Disk <xsl:value-of select="../@this_disk"/> of <xsl:value-of select="../@total_disks"/>
	    <table width="750" border="1" bgcolor="ffffff" class="border" cellpadding="5" align="center">
		<tr>
			<td align="center" class="tableHeader" width="50">Folder</td>
			<td align="center" class="tableHeader" width="50">Disk</td>
			<xsl:for-each select="index_name">
				<td align="center" class="tableHeader"><xsl:value-of select="."/></td>
			</xsl:for-each>
			</tr>
			<tr bgcolor="#ebebeb" onmouseover="style.cursor='pointer';rowMouseover('parent')" id="parent" onclick="parentFolder()">
				<xsl:attribute name="onmouseout">rowMouseout("parent")</xsl:attribute>
				<td class="document" align="center"><img src="images/open_16.gif"/></td>
				<td class="document" align="center"></td>
				<td class="document">
				<xsl:attribute name="colspan"><xsl:value-of select="@indices"/></xsl:attribute>
				Back to Cabinets
				</td>
			</tr>
			<xsl:for-each select="folder">
			<tr bgcolor="#ebebeb">
				<xsl:attribute name="id"><xsl:value-of select="@doc_id"/></xsl:attribute>
				<xsl:attribute name="onclick">setSelected('<xsl:value-of select="@doc_id"/>')</xsl:attribute>
				<xsl:attribute name="onmouseover">style.cursor='pointer';rowMouseover('<xsl:value-of select="@doc_id"/>')</xsl:attribute>
				<xsl:attribute name="onmouseout">rowMouseout('<xsl:value-of select="@doc_id"/>')</xsl:attribute>

				<td class="document" align="center"><img src="images/File.gif" width="14" height="14" border="0"/></td>
				<td class="document" align="center"><xsl:value-of select="@disk"/></td>
				<xsl:for-each select="index">
					<td class="document"><xsl:value-of select="."/></td>
				</xsl:for-each>
			</tr>
                   	</xsl:for-each>
		</table>
		</xsl:for-each>
	</center>
</xsl:template>
</xsl:stylesheet>
