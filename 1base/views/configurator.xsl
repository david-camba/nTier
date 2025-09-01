<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!-- Importamos el layout principal de la aplicación para tener el menú lateral, etc. -->
    <xsl:import href="[VIEW_PATH:layouts/app]"/>

    <!-- 
      PLANTILLA PRINCIPAL DE ESTA PÁGINA
      Define el contenido que se insertará en el layout 'app.xsl'.
    -->
    <xsl:template match="/data">
        <xsl:variable name="content">
            <xsl:apply-templates select="." mode="page-content"/>
        </xsl:variable>
        <xsl:call-template name="app-layout">
            <xsl:with-param name="page_content" select="$content"/>
        </xsl:call-template>
    </xsl:template>
    
    <!-- 
      DEFINICIÓN DEL CONTENIDO DE LA PÁGINA DEL CONFIGURADOR
    -->
    <xsl:template match="data" mode="page-content">
        <div class="configurator-container">
            
            <!--  Cabecera con título y plantillas  -->
            <div class="configurator-header">
                <h1><xsl:value-of select="configurator_title"/></h1>

                <!-- For the future 
                <div class="templates-section">
                    Comprobamos si hay plantillas para mostrar el dropdown 
                    <xsl:if test="count(templates/template) > 0">
                        <label for="template-select"><xsl:value-of select="configurator_templates_label"/></label>
                        <select id="template-select">
                            <option value=""><xsl:value-of select="configurator_templates_default_option"/></option>
                            <xsl:for-each select="templates/template">
                                <option value="{id_conf_session}">
                                    <xsl:value-of select="name"/> Supondremos que le añadimos un nombre a las plantillas 
                                </option>
                            </xsl:for-each>
                        </select>
                    </xsl:if>
                    Si no hay plantillas, podríamos mostrar un mensaje
                    <xsl:if test="count(templates/template) = 0">
                        <p class="no-templates-message"><xsl:value-of select="configurator_no_templates_message"/></p>
                    </xsl:if>
                </div>
                -->
            </div>

            <!--  Barra de Progreso de los Pasos -->
            <div class="step-indicator">
                <div class="step active" data-step="1">1. <xsl:value-of select="configurator_step1_title"/></div>
                <div class="step" data-step="2">2. <xsl:value-of select="configurator_step2_title"/></div>
                <div class="step" data-step="3">3. <xsl:value-of select="configurator_step3_title"/></div>
                <div class="step" data-step="4">4. <xsl:value-of select="configurator_step4_title"/></div>
            </div>

            <!-- EL CONTENEDOR DINÁMICO PARA LA SPA -->
            <!-- 
              Esta es la zona clave. El JavaScript borrará y "pintará" el contenido
              de cada paso aquí dentro.
            -->
            <div id="spa-content-area" class="spa-content">
                <!-- Se podría renderizar aquí el contenido del primer paso desde el servidor,
                     o dejarlo vacío para que el JS lo cargue. -->
                <p class="loading-message"><xsl:value-of select="configurator_loading_message"/></p>
            </div>
            
            <!-- Resumen y Botones de Acción -->
            <div class="configurator-summary">
                <div class="price-display">
                    <xsl:value-of select="configurator_price_label"/>: <span id="total-price">0.00</span> €
                </div>
                <div class="actions">
                    <button id="save-template-button" style="display: none;"><xsl:value-of select="configurator_save_template_button"/></button>
                    <!-- Este botón estará oculto hasta el último paso -->
                    <button id="assign-client-button" style="display: none;"><xsl:value-of select="configurator_assign_client_button"/></button>
                </div>
            </div>

        </div>
    </xsl:template>

</xsl:stylesheet>