<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" version="1.0" encoding="utf-8" indent="yes"/>

	<xsl:param name="TaxCode"/>

	<xsl:template match="/">
		<Company>
			<Customers>
				<xsl:for-each select="QueryResult/records">
					<xsl:call-template name="Customer" />
				</xsl:for-each>
			</Customers>

			<SalesOrders>
				<xsl:for-each select="QueryResult/records">
					<xsl:call-template name="SalesOrder" />
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>

	<xsl:template name="Customer">
		<Customer>
			<Id>
				<xsl:value-of select="Account/Id"/>
			</Id>
			<CompanyName>
				<xsl:value-of select="Account/Name"/>
			</CompanyName>
			<AccountReference>
				<xsl:value-of select="Account/AccountNumber"/>
			</AccountReference>

			<CustomerInvoiceAddress>
				<Company>
					<xsl:value-of select="Account/Name"/>
				</Company>
				<Address1>
					<xsl:value-of select="Account/BillingStreet"/>
				</Address1>
				<Town>
					<xsl:value-of select="Account/BillingCity"/>
				</Town>
				<County>
					<xsl:value-of select="Account/BillingState"/>
				</County>
				<Country>
					<xsl:value-of select="Account/BillingCountry"/>
				</Country>
				<Postcode>
					<xsl:value-of select="Account/BillingPostalCode"/>
				</Postcode>
				<Telephone>
					<xsl:value-of select="Account/Phone"/>
				</Telephone>
				<Fax>
					<xsl:value-of select="Account/Fax"/>
				</Fax>
			</CustomerInvoiceAddress>

			<CustomerDeliveryAddress>
				<Company>
					<xsl:value-of select="Account/Name"/>
				</Company>
				<Address1>
					<xsl:value-of select="Account/BillingStreet"/>
				</Address1>
				<Town>
					<xsl:value-of select="Account/BillingCity"/>
				</Town>
				<County>
					<xsl:value-of select="Account/BillingState"/>
				</County>
				<Country>
					<xsl:value-of select="Account/BillingCountry"/>
				</Country>
				<Postcode>
					<xsl:value-of select="Account/BillingPostalCode"/>
				</Postcode>
				<Telephone>
					<xsl:value-of select="Account/Phone"/>
				</Telephone>
				<Fax>
					<xsl:value-of select="Account/Fax"/>
				</Fax>
			</CustomerDeliveryAddress>
		</Customer>
	</xsl:template>

	<xsl:template name="SalesOrder">
		<SalesOrder>
			<Id>
				<xsl:value-of select="Id"/>
			</Id>
			<CustomerId>
				<xsl:value-of select="Account/Id"/>
			</CustomerId>
			<AccountReference>
				<xsl:value-of select="Account/AccountNumber"/>
			</AccountReference>
			<SalesOrderDate>
				<xsl:value-of select="concat(CloseDate, 'T00:00:00')"/>
			</SalesOrderDate>

			<SalesOrderAddress>
				<Company>
					<xsl:value-of select="Account/Name"/>
				</Company>
				<Address1>
					<xsl:value-of select="Account/BillingStreet"/>
				</Address1>
				<Town>
					<xsl:value-of select="Account/BillingCity"/>
				</Town>
				<County>
					<xsl:value-of select="Account/BillingState"/>
				</County>
				<Country>
					<xsl:value-of select="Account/BillingCountry"/>
				</Country>
				<Postcode>
					<xsl:value-of select="Account/BillingPostalCode"/>
				</Postcode>
				<Telephone>
					<xsl:value-of select="Account/Phone"/>
				</Telephone>
				<Fax>
					<xsl:value-of select="Account/Fax"/>
				</Fax>
			</SalesOrderAddress>

			<SalesOrderDeliveryAddress>
				<Company>
					<xsl:value-of select="Account/Name"/>
				</Company>
				<Address1>
					<xsl:value-of select="Account/BillingStreet"/>
				</Address1>
				<Town>
					<xsl:value-of select="Account/BillingCity"/>
				</Town>
				<County>
					<xsl:value-of select="Account/BillingState"/>
				</County>
				<Country>
					<xsl:value-of select="Account/BillingCountry"/>
				</Country>
				<Postcode>
					<xsl:value-of select="Account/BillingPostalCode"/>
				</Postcode>
				<Telephone>
					<xsl:value-of select="Account/Phone"/>
				</Telephone>
				<Fax>
					<xsl:value-of select="Account/Fax"/>
				</Fax>
			</SalesOrderDeliveryAddress>

			<SalesOrderItems>
				<xsl:for-each select="OpportunityLineItems/records">
					<xsl:call-template name="SalesOrderItem" />
				</xsl:for-each>
			</SalesOrderItems>
		</SalesOrder>
	</xsl:template>

	<xsl:template name="SalesOrderItem">
		<Item>
			<Sku>
				<xsl:value-of select="PricebookEntry/Product2/ProductCode"/>
			</Sku>
			<Name>
				<xsl:value-of select="PricebookEntry/Product2/Name"/>
			</Name>
			<QtyOrdered>
				<xsl:value-of select="Quantity"/>
			</QtyOrdered>
			<UnitPrice>
				<xsl:value-of select="UnitPrice"/>
			</UnitPrice>
			<TaxCode>
				<xsl:value-of select="$TaxCode"/>
			</TaxCode>
		</Item>
	</xsl:template>

</xsl:stylesheet>