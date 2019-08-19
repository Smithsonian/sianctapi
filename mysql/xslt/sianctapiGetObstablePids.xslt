<?xml version="1.0" encoding="UTF-8"?>
	
<!-- 
# Sidora Taverna Usage - http://si-researchersdev.si.edu
# file: solr/conf/xslt/sianctapiGetObstablePids.xslt
# author: Gert Schmeltz Pedersen gertsp45@gmail.com
# generates pids of observation tables
-->
	
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="text" indent="yes" encoding="UTF-8" />
	
	<xsl:template match="/">
		<div>
			<xsl:attribute name="numFound"><xsl:value-of select="response/result/@numFound"/></xsl:attribute>
			<xsl:apply-templates select="response/result/doc" />
		</div>
	</xsl:template>

	<xsl:template match="doc">
		<xsl:variable name="OBSTABLEPID" select="str[@name='PID']"/>
		<xsl:if test="position()>1">,</xsl:if>
		<xsl:value-of select="$OBSTABLEPID"/>
	</xsl:template>

	<!-- disable all default text node output -->
	<xsl:template match="text()" />

</xsl:stylesheet>
