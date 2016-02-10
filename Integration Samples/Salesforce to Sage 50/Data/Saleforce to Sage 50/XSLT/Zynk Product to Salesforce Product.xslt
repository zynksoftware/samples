<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" version="1.0" encoding="utf-8" indent="yes" xmlns="http://www.force.com/2009/06/asyncapi/dataload"/>

	<xsl:template match="/">
    	<sObjects xmlns="http://www.force.com/2009/06/asyncapi/dataload">
	        <xsl:for-each select="Company/Products/Product">
				<sObject>
					<xsl:if test="Id">
						<Id>
							<xsl:value-of select="Id"/>
						</Id>
					</xsl:if>
					<ProductCode>
						<xsl:value-of select="Sku"/>
					</ProductCode>
					<Name>
						<xsl:value-of select="Name"/>
					</Name>
					<Description>
						<xsl:value-of select="Description"/>
					</Description>
					<IsActive>
						<xsl:value-of select="Publish"/>
					</IsActive>
				</sObject>
	        </xsl:for-each>
    	</sObjects>
    </xsl:template>
</xsl:stylesheet>