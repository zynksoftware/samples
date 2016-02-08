<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" version="1.0" encoding="utf-8" indent="yes" xmlns="http://www.force.com/2009/06/asyncapi/dataload"/>

	<xsl:template match="/">
    	<sObjects xmlns="http://www.force.com/2009/06/asyncapi/dataload">
	        <xsl:for-each select="Company/Customers/Customer">
				<sObject>
					<xsl:if test="Id">
						<Id>
							<xsl:value-of select="Id"/>
						</Id>
					</xsl:if>
	
					<Name>
						<xsl:value-of select="CompanyName"/>
					</Name>
					
					<SageAccountReference__c>
						<xsl:value-of select="AccountReference"/>
					</SageAccountReference__c>
					
					<BillingStreet>
						<xsl:value-of select="CustomerInvoiceAddress/Address1"/>
					</BillingStreet>
					<BillingCity>
						<xsl:value-of select="CustomerInvoiceAddress/Town"/>
					</BillingCity>
					<BillingState>
						<xsl:value-of select="CustomerInvoiceAddress/County"/>
					</BillingState>
					<BillingCountry>
						<xsl:value-of select="CustomerInvoiceAddress/Country"/>
					</BillingCountry>
					<BillingPostalCode>
						<xsl:value-of select="CustomerInvoiceAddress/Postcode"/>
					</BillingPostalCode>
					<Phone>
						<xsl:value-of select="CustomerInvoiceAddress/Telephone"/>
					</Phone>
					<Fax>
						<xsl:value-of select="CustomerInvoiceAddress/Fax"/>
					</Fax>
				</sObject>
	        </xsl:for-each>
    	</sObjects>
    </xsl:template>
</xsl:stylesheet>