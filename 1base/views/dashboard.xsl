<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
    <!-- 
      Importación del Layout.
      Usamos el marcador [VIEW_PATH:...] para que nuestra View en PHP
      lo resuelva y encuentre el layout.xsl más específico en la jerarquía.
    -->
    
    <xsl:import href="[VIEW_PATH:layouts/app]"/>

    <!-- 
      PLANTILLA PRINCIPAL DE ESTA PÁGINA
      Su única misión es renderizar su propio contenido y pasárselo al layout.
    -->
    <xsl:template match="/data">
        <!-- 1. Renderizamos nuestro bloque de contenido y lo guardamos en una variable -->
        <xsl:variable name="content">
            <xsl:apply-templates select="." mode="page-content"/>
        </xsl:variable>

        <!-- 2. Llamamos al layout maestro y le pasamos nuestro contenido -->
        <xsl:call-template name="app-layout">
            <xsl:with-param name="page_content" select="$content"/>
        </xsl:call-template>
    </xsl:template>
    
    <!-- 
      DEFINICIÓN DEL CONTENIDO ESPECÍFICO DEL DASHBOARD
      Este bloque será insertado en el "hueco" del layout.
    -->
    <xsl:template match="data" mode="page-content">
        <div>
            <h1><xsl:value-of select="dashboard_title"/></h1>
            <p style="color:yellow">
                <xsl:value-of select="dashboard_welcome_message"/>
            </p>
        </div>
        <div id="show-report"></div>
    </xsl:template>

</xsl:stylesheet>