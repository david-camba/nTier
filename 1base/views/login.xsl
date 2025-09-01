<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" indent="yes"/>

    <!-- 
      PLANTILLA "LAYOUT" PRINCIPAL
      Construye la estructura de la página y define los "huecos" donde irá el contenido.
    -->
    <xsl:template match="/data">
        <html lang="es">
            <head>
                <meta charset="UTF-8"/>
                <title><xsl:value-of select="login_page_title"/></title>
                <xsl:for-each select="styles/style">
                    <link rel="stylesheet">
                        <xsl:attribute name="href">
                            <xsl:value-of select="."/>
                        </xsl:attribute>
                    </link>
                </xsl:for-each>
                <style>             
                    .background-container {background-image: url('<xsl:value-of select="login_background_image_url"/>'); }
                </style>
            </head>
            <body>
                <!-- Aplicamos la plantilla del selector de idioma -->
                <xsl:apply-templates select="." mode="language-selector"/>

                <div class="background-container">
                    <div class="layout-container">
                        <div class="left-box">
                        Usar el traductor...
                            <p>Aqui tengo que meter la explicación rápida del sistema (empieza en layer 3)</p>
                            <p style="background-color:rgba(60, 179, 113, 0.6)"><xsl:value-of select="login_userlevel_test"/></p>
                        </div>
                        <div class="login-box">                           
                            <!-- "HUECO" PARA EL ENCABEZADO -->
                            <!-- Le decimos: "busca una plantilla para el nodo actual (data)
                                que tenga el modo 'header' y ejecútala". -->
                            <xsl:apply-templates select="." mode="header"/>
                            
                            <!-- "HUECO" PARA EL FORMULARIO -->
                            <xsl:apply-templates select="." mode="form"/>
                        </div>
                    </div>
                </div>
                        <!-- CARGAMOS SCRIPTS -->
                        <xsl:apply-templates select="." mode="load_scripts"/>
            </body>
        </html>
    </xsl:template>

    <!-- BLOQUE "HEADER" (ahora usa una clave de traducción) -->
    <xsl:template match="data" mode="header">
        <h1><xsl:value-of select="login_header_brand"/></h1>
        <p><xsl:value-of select="login_header_brand"/></p>
    </xsl:template>

<!-- BLOQUE "FORM" (ahora usa claves de traducción para los placeholders y el botón) -->
    <xsl:template match="data" mode="form">
        <form id="login-form">
            <!-- Usamos <xsl:attribute> para establecer los placeholders dinámicamente -->
            <input type="text" id="username" required="true">
                <xsl:attribute name="placeholder">
                    <xsl:value-of select="login_form_username_placeholder"/>
                </xsl:attribute>
            </input>
            <input type="password" id="password" required="true">
                <xsl:attribute name="placeholder">
                    <xsl:value-of select="login_form_password_placeholder"/>
                </xsl:attribute>
            </input>
            
            <button type="submit">
                <xsl:value-of select="login_form_submit_button"/>
            </button>
            
            <div id="login-message" style="margin-top: 15px;"></div>
        </form>
    </xsl:template>

    <!-- DEFINICIÓN POR DEFECTO DEL BLOQUE "SCRIPTS" -->
    <xsl:template match="data" mode="load_scripts">
        <!-- 
          Iteramos sobre cada nodo 'script' dentro del nodo 'scripts'.
          Asumimos que el controlador nos ha pasado una estructura de datos así.
        -->
        <xsl:value-of select="json_data_blocks" disable-output-escaping="yes"/>
        <xsl:for-each select="scripts/script">
            <script>
                <!-- El atributo 'src' se establece con el valor del nodo actual -->
                <xsl:attribute name="src">
                    <xsl:value-of select="."/>
                </xsl:attribute>
            </script>
        </xsl:for-each>
    </xsl:template>

    <!-- DEFINICIÓN POR DEFECTO DEL BLOQUE DEL SELECTOR DE IDIOMA -->
    <xsl:template match="data" mode="language-selector">
        <div class="language-selector">
            <span style="color:red!important"><xsl:value-of select="login_translation_message"/></span>
            <a href="?lang=es" title="Español">
                <img src="/1base/icons/es.svg" alt="Bandera de España"/>
            </a>
            <a href="?lang=en" title="English">
                <img src="/1base/icons/gb.svg" alt="Flag of United Kingdom"/>
            </a>
        </div>
    </xsl:template>

</xsl:stylesheet>