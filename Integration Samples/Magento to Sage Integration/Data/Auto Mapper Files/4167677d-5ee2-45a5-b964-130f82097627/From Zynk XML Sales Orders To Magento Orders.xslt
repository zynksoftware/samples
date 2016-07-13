<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet 
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes"/>
  
	<xsl:template match="Company">
		<ArrayOfOrderInfo>
			<xsl:for-each select="SalesOrders/SalesOrder">
				<xsl:call-template name="OrderInfo" />
			</xsl:for-each>
		</ArrayOfOrderInfo>
	</xsl:template>

	<xsl:template name="OrderInfo">
		<OrderInfo>
			<increment_id><xsl:value-of select="CustomerOrderNumber"/></increment_id>
		</OrderInfo>
	</xsl:template>
  
</xsl:stylesheet>