<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:template match="/">
		<CustomerList>
			<xsl:for-each select="Company/Customers/Customer">
				<Customer>
					<xsl:attribute name="id">
						<xsl:value-of select="Id" />
					</xsl:attribute>
					<InternalNumber><xsl:value-of select="AccountReference" /></InternalNumber>
					<Address>
						<Email><xsl:value-of select="CustomerInvoiceAddress/Email" /></Email>
					</Address>
				</Customer>
			</xsl:for-each>
		</CustomerList>
	</xsl:template>
	
</xsl:stylesheet>