<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/">
	<center>
	Disk <xsl:value-of select="department/@this_disk"/> of <xsl:value-of select="department/@total_disks"/>
	<table width="750" border="1" bgcolor="ffffff" class="border" cellpadding="5" align="center">
		<tr>
			<td align="middle" class="tableHeader" width="50"></td>
			<td align="center" class="tableHeader">Cabinet Name</td>
			<td align="center" class="tableHeader">Number of Folders</td>
		</tr>
		<xsl:for-each select="department/cabinet">
			<tr onmouseover="style.cursor='pointer';rowMouseover('{@ID}')" onmouseout="rowMouseout('{@ID}')" onclick="setSelected('{@ID}')" id="{@ID}" bgcolor="#ebebeb">
				<td class="document" align="middle"><img src="images/cabinet.gif"/></td>
				<td class="document" width="250"><xsl:value-of select="@name"/></td>
				<td class="document"><xsl:value-of select="@num_folders"/></td>
			</tr>
		</xsl:for-each>
	</table>
	</center>
</xsl:template>
</xsl:stylesheet>
			
