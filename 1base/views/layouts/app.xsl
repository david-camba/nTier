<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" indent="yes"/>

    <!-- 
      PLANTILLA "LAYOUT" PRINCIPAL (app-layout)
      Define la estructura común: cabecera, menú lateral, área de contenido.
    -->
    <xsl:template name="app-layout">
        <!-- Parámetro que recibirá el contenido específico de cada página -->
        <xsl:param name="page_content"/>

        <html lang="{page_lang}">
            <head>
                <meta charset="UTF-8"/>
                <title><xsl:value-of select="pageTitle"/> - <xsl:value-of select="brandName"/></title>
                
                <!-- Hueco para las etiquetas <link> de CSS -->
                <xsl:for-each select="styles/style">
                    <link rel="stylesheet">
                        <xsl:attribute name="href"><xsl:value-of select="."/></xsl:attribute>
                    </link>
                </xsl:for-each>
                <style>
                    .main-content {background-image: url('<xsl:value-of select="dashboard_image_url"/>'); }
                </style>
            </head>
            <body>
                <div class="app-container">
                    <!-- Columna del Menú (Sidebar) -->
                    <aside class="sidebar">
                        <!-- Hueco para el contenido del menú -->
                        <xsl:apply-templates select="." mode="sidebar-content"/>
                    </aside>

                    <!-- Área de Contenido Principal -->
                    <main class="main-content">
                        <!-- Inyectamos el contenido de la página específica -->
                        <xsl:copy-of select="$page_content"/>
                    </main>
                </div>
                
                <!-- Hueco para las etiquetas <script> de JS -->
                <xsl:apply-templates select="." mode="load_scripts"/>
            </body>
        </html>
    </xsl:template>

    <!-- 
      DEFINICIÓN DEL BLOQUE DEL MENÚ
      Este bloque puede ser sobreescrito por las capas superiores.
    -->
    <xsl:template match="data" mode="sidebar-content">
        <div class="sidebar-header">
            <a href="/app" style="text-decoration: none; color: inherit; font-size:32px"><xsl:value-of select="brandName"/></a>
        </div>
        <nav class="main-menu">
            <ul>
               <xsl:for-each select="menuItems/menuItem">
                    <li>
                        <a>
                            <!-- El href se toma del nodo hijo 'url' -->
                            <xsl:attribute name="href">
                                <xsl:value-of select="url"/>
                            </xsl:attribute>
                            <!-- El texto del enlace se toma del nodo hijo 'text' -->
                            <xsl:value-of select="text"/>
                        </a>
                    </li>
                </xsl:for-each>
                <xsl:apply-templates select="." mode="language-selector"/>
            </ul>
        </nav>
        <div class="user-info">
            <p><xsl:value-of select="user_greeting"/>, <xsl:value-of select="user/name"/></p>
            <a href="/logout"><xsl:value-of select="menu_logout"/></a>
        </div>
    </xsl:template>

        <xsl:template match="data" mode="sidebar-menu">
        <div class="sidebar-header">
            <h2><xsl:value-of select="brandName"/></h2>
        </div>
        <nav class="main-menu">
            <ul>
                <!-- 
                  Iteramos sobre cada nodo 'menuItem' dentro del nodo 'menuItems'.
                  Nuestro buildXmlNodes creará esta estructura a partir del array.
                -->
                <xsl:for-each select="menuItems/menuItem">
                    <li>
                        <a>
                            <!-- El href se toma del nodo hijo 'url' -->
                            <xsl:attribute name="href">
                                <xsl:value-of select="url"/>
                            </xsl:attribute>
                            <!-- El texto del enlace se toma del nodo hijo 'text' -->
                            <xsl:value-of select="text"/>
                        </a>
                    </li>
                </xsl:for-each>
            </ul>
        </nav>
        <div class="user-info">
        </div>
    </xsl:template>

    <xsl:template match="data" mode="language-selector">
        <div class="language-selector">
            <!-- El 'span' y los enlaces 'a' son idénticos a los del login -->
            <span><xsl:value-of select="login_translation_message"/></span>
            
            <a href="?lang=es" title="Español" style="margin-left: 10px;">
                <img src="/1base/icons/es.svg" alt="Bandera de España" style="width: 24px; vertical-align: middle;"/>
            </a>
            <a href="?lang=en" title="English" style="margin-left: 5px;">
                <img src="/1base/icons/gb.svg" alt="Flag of United Kingdom" style="width: 24px; vertical-align: middle;"/>
            </a>
        </div>
    </xsl:template>

    <xsl:template match="data" mode="load_scripts">
        <xsl:value-of select="json_data_blocks" disable-output-escaping="yes"/>
        <!-- 
          Iteramos sobre cada nodo 'script' dentro del nodo 'scripts'.
          Asumimos que el controlador nos ha pasado una estructura de datos así.
        -->
        <xsl:for-each select="scripts/script">
            <script>
                <!-- El atributo 'src' se establece con el valor del nodo actual -->
                <xsl:attribute name="src">
                    <xsl:value-of select="."/>
                </xsl:attribute>
            </script>
        </xsl:for-each>
    </xsl:template>
</xsl:stylesheet>