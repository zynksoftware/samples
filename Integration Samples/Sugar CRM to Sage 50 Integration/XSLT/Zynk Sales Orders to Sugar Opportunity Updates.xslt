<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" indent="yes"/>

	<xsl:template match="/">
		<Opportunities>
			<xsl:for-each select="Company/SalesOrders/SalesOrder">
				<xsl:call-template name="Opportunity" />
			</xsl:for-each>
		</Opportunities>
	</xsl:template>

	<xsl:template name="Opportunity">
		<Opportunity>
			<xsl:attribute name="id"><xsl:value-of select="Id"/></xsl:attribute>
			<xsl:attribute name="sales_order_no_c"><xsl:value-of select="SalesOrderNumber"/></xsl:attribute>
    	</Opportunity>
	</xsl:template>

</xsl:stylesheet>