<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!-- 1. Importamos la plantilla base. Sus reglas tienen menor prioridad. -->
    <xsl:import href="[PARENT_TEMPLATE_PATH]"/>

    <!-- 
      2. SOBREESCRIBIMOS EL BLOQUE "HEADER"
      Definimos una nueva plantilla para 'data' en modo 'header'.
      Como este archivo tiene mayor prioridad, esta implementación se usará
      en lugar de la de la capa base.
    -->
    <xsl:template match="data" mode="header">
        <!-- 
          3. ¡LLAMAMOS AL PADRE!
          <xsl:apply-imports/> le dice al procesador: "Ahora, ejecuta la
          implementación de este mismo bloque ('data' en modo 'header')
          que se encuentra en el archivo importado".
          Esto renderizará el <h1> original de la capa base.
          Es
        -->
        <xsl:apply-imports/> <!-- Trae la plantilla del padre -->

        <!-- 4. AÑADIMOS NUESTRO CONTENIDO PERSONALIZADO -->
        <p style="font-size: 1em; color: #ccc; margin-top: -20px; margin-bottom: 20px;">
            <xsl:value-of select="login_2_header_subtitle" disable-output-escaping="yes"/>
        </p>
    </xsl:template>

    <!-- 
      ¡No definimos una plantilla para el modo "form"!
      Por lo tanto, cuando el layout principal llame a <apply-templates mode="form"/>,
      el procesador no encontrará una implementación en este archivo y usará
      la implementación por defecto del archivo importado (la de la capa base).
    -->

</xsl:stylesheet>