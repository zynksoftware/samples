<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" indent="yes"/>
	<xsl:param name="AttributeSet">4</xsl:param>

	<xsl:template match="/">
		<ArrayOfProduct>
			<xsl:for-each select="Company/Products/Product">
				<xsl:call-template name="Product" />
			</xsl:for-each>
		</ArrayOfProduct>
	</xsl:template>

	<xsl:template name="Product">
		<Product>
			<sku><xsl:value-of select="Sku" /></sku>
			<name><xsl:value-of select="Name" /></name>
			<short_description><xsl:value-of select="Description" /></short_description>
			<description><xsl:value-of select="Description" /></description>
			<weight><xsl:value-of select="UnitWeight" /></weight>
			<qty><xsl:value-of select="QtyInStock - QtyAllocated" /></qty>
			<type>simple</type>
			<set><xsl:value-of select="$AttributeSet" /></set>
			<websites>1</websites>
			<price><xsl:value-of select="SalePrice" /></price>

			<is_in_stock>
				<xsl:choose>
					<xsl:when test="(QtyInStock - QtyAllocated) &gt; 0">1</xsl:when>
					<xsl:otherwise>0</xsl:otherwise>
				</xsl:choose>
			</is_in_stock>

			<tax_class_id>
				<xsl:choose>
					<xsl:when test="TaxCode = '1'">2</xsl:when>
					<xsl:otherwise>0</xsl:otherwise>
				</xsl:choose>
			</tax_class_id>

			<Fields>
				<Field>
					<xsl:attribute name="Name">manufacturer</xsl:attribute>
					<xsl:attribute name="Value">
						<xsl:value-of select="@Manufacturer"/>
					</xsl:attribute>
				</Field>
			</Fields>
		</Product>
	</xsl:template>
</xsl:stylesheet>