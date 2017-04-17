<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:param name="query_string" select="'default'" />
<xsl:template match="/">
   <xsl:for-each select="/department/cabinet/folder">
      <xsl:for-each select="index">
         <xsl:choose>  
            <xsl:when test="contains(.,'will')"> 
               <i>aaaaaa</i>
               <b><xsl:value-of select="."/></b><br/>
           </xsl:when>
           <xsl:otherwise>
               <b><xsl:value-of select="."/></b><br/>
            </xsl:otherwise> 
          </xsl:choose> 
      </xsl:for-each>
   </xsl:for-each>
</xsl:template>
</xsl:stylesheet>
      

