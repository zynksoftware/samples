<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output
	method="xml"
	omit-xml-declaration="no"
	standalone="yes"
	indent="yes"/>

	<xsl:template match="/">
		<Company>
			<Customers>
				<xsl:for-each select="ArrayOfCustomer/Customer">
					<xsl:call-template name="Customer" />
				</xsl:for-each>
			</Customers>
		</Company>
	</xsl:template>

	<xsl:template name="Customer">
		<Customer>
			<Id><xsl:value-of select="customer_id"/></Id>
			<CompanyName>
			  <xsl:choose>
			    <xsl:when test="default_billing_address/company != ''">
				  <xsl:value-of select="default_billing_address/company"/>
			    </xsl:when>
			    <xsl:otherwise>
				  <xsl:value-of select="concat(default_billing_address/firstname, ' ', default_billing_address/lastname)"/>
			    </xsl:otherwise>
			  </xsl:choose>
			</CompanyName>
			<CustomerInvoiceAddress>
				<Title><xsl:value-of select="default_billing_address/prefix"/></Title>
				<Forename><xsl:value-of select="default_billing_address/firstname"/></Forename>
				<Middlename><xsl:value-of select="default_billing_address/middlename"/></Middlename>
				<Surname><xsl:value-of select="default_billing_address/lastname"/></Surname>
				<Address1><xsl:value-of select="default_billing_address/street"/></Address1>
				<Town><xsl:value-of select="default_billing_address/city"/></Town>
				<Postcode><xsl:value-of select="default_billing_address/postcode"/></Postcode>
				<County><xsl:value-of select="default_billing_address/region"/></County>
				<Country><xsl:value-of select="default_billing_address/country_id"/></Country>
				<Telephone><xsl:value-of select="default_billing_address/telephone"/></Telephone>
				<Fax><xsl:value-of select="default_billing_address/fax"/></Fax>
				<Email><xsl:value-of select="email"/></Email>
			</CustomerInvoiceAddress>
			<TermsAgreed>1</TermsAgreed>
			<AccountStatus>1</AccountStatus>
		</Customer>
	</xsl:template>

</xsl:stylesheet>