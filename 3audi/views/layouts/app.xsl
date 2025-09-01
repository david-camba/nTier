<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:import href="[PARENT_TEMPLATE_PATH]"/>

    <xsl:template match="data" mode="sidebar-content">
        <xsl:apply-imports/> <!-- Trae la plantilla del padre -->
        <p class="views-layer-message">
            <xsl:value-of select="views_layer_message" disable-output-escaping="yes"/>
        </p>
    </xsl:template>

</xsl:stylesheet>