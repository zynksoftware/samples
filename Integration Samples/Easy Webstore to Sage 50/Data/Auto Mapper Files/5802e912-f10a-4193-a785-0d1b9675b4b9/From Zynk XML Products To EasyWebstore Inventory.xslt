<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:msxsl="urn:schemas-microsoft-com:xslt" 
	exclude-result-prefixes="msxsl"
>
    <xsl:output method="xml" indent="yes" />

    <xsl:template match="/">
		<ArrayOfProduct>
			<xsl:for-each select="Company/Products/Product">
				<xsl:call-template name="Inventory" />	
			</xsl:for-each>
		</ArrayOfProduct>
	</xsl:template>
	
	<xsl:template name="Inventory">
		<Product>
			<ProductCode><xsl:value-of select="Sku" /></ProductCode>
			<StockLevel><xsl:value-of select="QtyInStock" /></StockLevel>
		</Product>
	</xsl:template>

</xsl:stylesheet>