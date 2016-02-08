<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:template match="/">
		<OrderList>
			<xsl:for-each select="Company/SalesOrders/SalesOrder">
				<Order>
					<xsl:attribute name="id">
						<xsl:value-of select="Id" />
					</xsl:attribute>
				</Order>
			</xsl:for-each>
		</OrderList>
	</xsl:template>
	
</xsl:stylesheet>