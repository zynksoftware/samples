<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" indent="yes"/>

	<xsl:template match="/">
		<Company>
			<SalesOrders>
				<xsl:for-each select="Opportunities/Opportunity">
					<xsl:call-template name="SalesOrder" />
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>

	<xsl:template name="SalesOrder">
		<SalesOrder>
			<Id><xsl:value-of select="@id"/></Id>
			<CustomerId><xsl:value-of select="Accounts/Account/@id"/></CustomerId>
			<AccountReference><xsl:value-of select="Accounts/Account/@sage_account_ref_c"/></AccountReference>
			<SalesOrderDate><xsl:value-of select="@date_closed"/></SalesOrderDate>

			<SalesOrderAddress>
				<Company><xsl:value-of select="Accounts/Account/@name"/></Company>
        		<Address1><xsl:value-of select="Accounts/Account/@billing_address_street"/></Address1>
				<Address2><xsl:value-of select="Accounts/Account/@billing_address_street_2"/></Address2>
        		<Town><xsl:value-of select="Accounts/Account/@billing_address_city"/></Town>
        		<County><xsl:value-of select="Accounts/Account/@billing_address_state"/></County>
				<Country><xsl:value-of select="Accounts/Account/@billing_address_country"/></Country>
				<Postcode><xsl:value-of select="Accounts/Account/@billing_address_postalcode"/></Postcode>
				<Telephone><xsl:value-of select="Accounts/Account/@phone_office"/></Telephone>
				<Fax><xsl:value-of select="Accounts/Account/@phone_fax"/></Fax>
			</SalesOrderAddress>

			<SalesOrderDeliveryAddress>
				<Company><xsl:value-of select="Accounts/Account/@name"/></Company>
				<Address1><xsl:value-of select="Accounts/Account/@shipping_address_street"/></Address1>
				<Town><xsl:value-of select="Accounts/Account/@shipping_address_city"/></Town>
				<County><xsl:value-of select="Accounts/Account/@shipping_address_state"/></County>
				<Country><xsl:value-of select="Accounts/Account/@shipping_address_country"/></Country>
				<Postcode><xsl:value-of select="Accounts/Account/@shipping_address_postalcode"/></Postcode>
				<Telephone><xsl:value-of select="Accounts/Account/@phone_office"/></Telephone>
				<Fax><xsl:value-of select="Accounts/Account/@phone_fax"/></Fax>
      		</SalesOrderDeliveryAddress>

			<SalesOrderItems>
				<Item>
					<Sku>S1</Sku>
					<Name><xsl:value-of select="@name"/></Name>
					<UnitPrice><xsl:value-of select="@amount_usdollar"/></UnitPrice>
					<QtyOrdered>1</QtyOrdered>
				</Item>
			</SalesOrderItems>
    	</SalesOrder>
	</xsl:template>

</xsl:stylesheet>