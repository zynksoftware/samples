<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:template match="/">
		<ProductList>
			<xsl:for-each select="Company/Products/Product">
				<Product>
					<Sku><xsl:value-of select="Sku" /></Sku>
					<Price>
						<Amount><xsl:value-of select="SalePrice" /></Amount>
					</Price>
					<InventoryLevel><xsl:value-of select="QtyInStock - QtyAllocated" /></InventoryLevel>
				</Product>
			</xsl:for-each>
		</ProductList>
	</xsl:template>
	
</xsl:stylesheet>