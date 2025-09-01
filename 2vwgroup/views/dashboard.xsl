<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
    
    <xsl:import href="[PARENT_TEMPLATE_PATH]"/>

    <xsl:template match="data" mode="page-content">
      <xsl:apply-imports/>
        <div class="dashboard-vw-message">
          <xsl:value-of select="dashboard_vw_message" disable-output-escaping="yes"/>
        </div>
    </xsl:template>

    

</xsl:stylesheet>