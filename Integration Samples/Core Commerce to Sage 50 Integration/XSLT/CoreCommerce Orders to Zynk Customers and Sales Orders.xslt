<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:param name="CustomerFile" />
	<xsl:variable name="customers" select="document($CustomerFile)/Response/List" />
	
	<xsl:param name="CountryFile" />
	<xsl:variable name="countries" select="document($CountryFile)/Rows" />
	
	<xsl:key name="UniqueCustomer" match="/Response/List/Order" use="CustomerId" />
	
	<xsl:template match="/">
		<Company>
			<Customers>
				<xsl:for-each select="Response/List/Order[generate-id() = generate-id(key('UniqueCustomer', CustomerId)[1])]">
					<xsl:call-template name="Customer" />
				</xsl:for-each>
			</Customers>
			<SalesOrders>
				<xsl:for-each select="Response/List/Order">
					<xsl:call-template name="SalesOrder" />
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>
	
	<xsl:template name="Customer">
		
		<xsl:variable name="customer-id" select="CustomerId" />
		<xsl:variable name="account-ref" select="$customers/Customer[CustomerString = $customer-id]/InternalNumber" />
		
		<!-- Only output new customers -->
		<xsl:if test="$account-ref = ''">
			<Customer>
				<xsl:variable name="country" select="ShippingAddress/Country" />
				<xsl:variable name="country-is-gb" select="$country = 'GB'" />
				<xsl:variable name="country-is-eu" select="not($country-is-gb) and $countries/Row[@CODE = $country]/@EU_MEMBER = '1'" />
				
				<Id><xsl:value-of select="$customer-id" /></Id>
				<AccountReference><xsl:value-of select="$account-ref" /></AccountReference>
				
				<CompanyName>
					<xsl:choose>
						<xsl:when test="BillingAddress/Company != ''">
							<xsl:value-of select="BillingAddress/Company"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="concat(BillingAddress/FirstName, ' ', BillingAddress/LastName)"/>
						</xsl:otherwise>
					</xsl:choose>
				</CompanyName>

				<CustomerInvoiceAddress>
					<xsl:call-template name="address">
						<xsl:with-param name="address" select="BillingAddress" />
					</xsl:call-template>
				</CustomerInvoiceAddress>
				
				<CustomerDeliveryAddress>
					<xsl:call-template name="address">
						<xsl:with-param name="address" select="ShippingAddress" />
					</xsl:call-template>
				</CustomerDeliveryAddress>
				
				<xsl:call-template name="tax-code">
					<xsl:with-param name="country" select="ShippingAddress/Country" />
				</xsl:call-template>
			</Customer>
		</xsl:if>
	</xsl:template>
	
	<xsl:template name="SalesOrder">
		<SalesOrder>
			<Id><xsl:value-of select="Number"/></Id>

			<xsl:variable name="customer-id" select="CustomerId" />
			<CustomerId><xsl:value-of select="$customer-id" /></CustomerId>
			<AccountReference><xsl:value-of select="$customers/Customer[CustomerString = $customer-id]/InternalNumber" /></AccountReference>

			<CustomerOrderNumber><xsl:value-of select="Number" /></CustomerOrderNumber>
			<SalesOrderDate><xsl:value-of select="concat(OrderDate/Year, '-', OrderDate/Month, '-', OrderDate/Day)" /></SalesOrderDate>
			
			<VatInclusive>
				<xsl:choose>
					<xsl:when test="VatInclusive = 'TRUE_VALUE'">
						<xsl:text>true</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>false</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</VatInclusive>

			<SalesOrderAddress>
				<xsl:call-template name="address">
					<xsl:with-param name="address" select="BillingAddress" />
				</xsl:call-template>
			</SalesOrderAddress>
			
			<SalesOrderDeliveryAddress>
				<xsl:call-template name="address">
					<xsl:with-param name="address" select="ShippingAddress" />
				</xsl:call-template>
			</SalesOrderDeliveryAddress>
			
			
			
			<SalesOrderItems>
				<xsl:for-each select="Items/Item">
					<xsl:call-template name="Item" />
				</xsl:for-each>
			</SalesOrderItems>
			
			<TakenBy>Website</TakenBy>
		</SalesOrder>
	</xsl:template>
	
	<xsl:template name="address">
		<xsl:param name="address" />
		
		<xsl:if test="$address/Company != ''">
			<Company><xsl:value-of select="BillingAddress/Company"/></Company>
		</xsl:if>
		
		<Forename><xsl:value-of select="$address/FirstName" /></Forename>
		<Surname><xsl:value-of select="$address/LastName" /></Surname>
		<Address1><xsl:value-of select="$address/Address1" /></Address1>
		<Address2><xsl:value-of select="$address/Address2" /></Address2>
		<Town><xsl:value-of select="$address/City"/></Town>
		<County><xsl:value-of select="$address/State"/></County>
		<Postcode><xsl:value-of select="$address/Zip"/></Postcode>
		<Country><xsl:value-of select="$address/Country" /></Country>
		<Email><xsl:value-of select="$address/Email" /></Email>
		<Telephone><xsl:value-of select="Phone" /></Telephone>
		<Fax><xsl:value-of select="Fax" /></Fax>
	</xsl:template>

	<xsl:template name="Item">
		<Item>
			<Sku><xsl:value-of select="Sku" /></Sku>
			<Name><xsl:value-of select="Name" /></Name>
			<UnitPrice><xsl:value-of select="Price/Amount" /></UnitPrice>
			<QtyOrdered><xsl:value-of select="Quantity" /></QtyOrdered>
			
			<xsl:call-template name="tax-code">
				<xsl:with-param name="country" select="../../ShippingAddress/Country" />
			</xsl:call-template>
		</Item>
	</xsl:template>
	
	<xsl:template name="tax-code">
		<xsl:param name="country" />

		<xsl:variable name="country-is-gb" select="$country = 'GB'" />
		<xsl:variable name="country-is-eu" select="not($country-is-gb) and $countries/Row[@CODE = $country]/@EU_MEMBER = '1'" />
		
		<TaxCode>
			<xsl:choose>
				<xsl:when test="$country-is-gb">1</xsl:when>
				<xsl:when test="$country-is-eu">4</xsl:when>
				<xsl:otherwise>0</xsl:otherwise>
			</xsl:choose>
		</TaxCode>
	</xsl:template>
	
</xsl:stylesheet>