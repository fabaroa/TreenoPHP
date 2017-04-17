<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="selected_id" select="'default'" />
  <xsl:param name="cab_id" select="'default'" />
  <xsl:template match="/">
    <xsl:for-each select="/department">
      <form>
        <input type="hidden" name="this_disk" value="{@this_disk}"></input>
        <xsl:for-each select="cabinet[@ID=$cab_id]">
          <xsl:for-each select="folder[@doc_id=$selected_id]">
            <input type="hidden" name="this_folder" value="{@disk}"></input>
            <xsl:for-each select="tab">
              <table border="0" width="100%">
                <tr><td>
                  <div class="tealbg" style="font-size:10pt" onmouseover="style.cursor='pointer'">
                    <div onclick="selectTab('{@name}')">
                      <img src="images/folder.gif"/><xsl:value-of select="@name"/>
                    </div>
                  </div>
                </td></tr>
              </table>
              <div class="gone" id="{@name}">
                <xsl:choose>
                  <xsl:when test="@num_files>0">
                    <xsl:for-each select="file">
                      <table id="file_{@doc_id}" border="0" cellspacing="1" cellpadding="1" width="100%">
                        <tr onclick="setSelected('file_{@doc_id}','{@doc_id}','{@location}')" onmouseover="style.cursor='pointer';rowMouseover('file_{@doc_id}')" onmouseout="rowMouseout('file_{@doc_id}')">
                          <td>
                            <img onclick="openFile('{@doc_id}','{@location}')">
                              <xsl:choose>
                                <xsl:when test="substring-after(.,'.')='TIF' or substring-after(.,'.')='tif'">
                                  <xsl:attribute name="src">images/tiff.gif</xsl:attribute>
                                </xsl:when>
                                <xsl:otherwise>
                                  <xsl:attribute name="src">images/ascii.gif</xsl:attribute>
                                </xsl:otherwise>
                              </xsl:choose>
                            </img>
                          </td>   
                        </tr>
                        <tr onmouseover="style.cursor='pointer';rowMouseover('file_{@doc_id}')" onmouseout="rowMouseout('file_{@doc_id}')" onclick="setSelected('file_{@doc_id}','{@doc_id}','{@location}')">
                          <td>
                            <xsl:value-of select="."/>
                              <img src="images/save.gif" border="0" onclick="downloadFile('../{@location}{@doc_id}')"/>
                          </td>
                        </tr>
                      </table>
                    </xsl:for-each>
                  </xsl:when>
                  <xsl:otherwise>
                    <font style="color:white"><i>Tab contains no files</i></font>
                  </xsl:otherwise>
                </xsl:choose>
                </div>
              </xsl:for-each>
            </xsl:for-each>
          </xsl:for-each>
        </form>
    </xsl:for-each>
  </xsl:template>
</xsl:stylesheet> 
