<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:import href="[PARENT_TEMPLATE_PATH]"/>

    <xsl:template match="data" mode="header">
        <xsl:apply-imports/> <!-- Trae la plantilla del padre -->
        <p style="font-size: 1em; color: #ccc; margin-bottom: 20px;">
            <xsl:value-of select="login_3_header_subtitle" disable-output-escaping="yes"/>
        </p>
    </xsl:template>

</xsl:stylesheet>