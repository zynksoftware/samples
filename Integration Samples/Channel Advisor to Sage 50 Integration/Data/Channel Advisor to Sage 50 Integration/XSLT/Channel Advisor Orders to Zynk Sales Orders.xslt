<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:ca="http://api.channeladvisor.com/datacontracts/orders"
    exclude-result-prefixes="ca">

	<xsl:output method="xml" indent="yes"/>

	<xsl:template match="ArrayOfOrderResponseDetailComplete">
		<Company>
			<SalesOrders>
				<xsl:for-each select="OrderResponseDetailComplete">
					<xsl:call-template name="SalesOrder" />
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>

	<xsl:template name="SalesOrder">
		<SalesOrder>
			
			<Id><xsl:value-of select="ca:OrderID"/></Id>
			<CustomerOrderNumber><xsl:value-of select="ca:OrderID"/></CustomerOrderNumber>
			<SalesOrderDate><xsl:value-of select="concat(substring-before(ca:OrderTimeGMT, 'T'), 'T00:00:00')"/></SalesOrderDate>
			<Currency>GBP</Currency>
			<AccountReference>WEBSALES</AccountReference>

			<SalesOrderAddress>
				<Title><xsl:value-of select="ca:BillingInfo/ca:Title"/></Title>
				<Forename><xsl:value-of select="ca:BillingInfo/ca:FirstName"/></Forename>
				<Surname><xsl:value-of select="ca:BillingInfo/ca:LastName"/></Surname>

				<xsl:if test="ca:BillingInfo/ca:CompanyName != ''">
					<Company><xsl:value-of select="ca:BillingInfo/ca:CompanyName"/></Company>
				</xsl:if>
		
				<Address1><xsl:value-of select="ca:BillingInfo/ca:AddressLine1"/></Address1>
				<Address2><xsl:value-of select="ca:BillingInfo/ca:AddressLine2"/></Address2>
				<Town><xsl:value-of select="ca:BillingInfo/ca:City"/></Town>
				
				<xsl:if test="ca:BillingInfo/ca:RegionDescription != 'No Region Required'" >
					<County><xsl:value-of select="ca:BillingInfo/ca:RegionDescription"/></County>
				</xsl:if>
				
				<Postcode><xsl:value-of select="ca:BillingInfo/ca:PostalCode"/></Postcode>
				<Country><xsl:value-of select="ca:BillingInfo/ca:CountryCode"/></Country>
				<Telephone><xsl:value-of select="ca:BillingInfo/ca:PhoneNumberDay"/></Telephone>
				<Email><xsl:value-of select="ca:BuyerEmailAddress"/></Email>
			</SalesOrderAddress>

			<SalesOrderDeliveryAddress>
				<Title><xsl:value-of select="ca:ShippingInfo/ca:Title"/></Title>
				<Forename><xsl:value-of select="ca:ShippingInfo/ca:FirstName"/></Forename>
				<Surname><xsl:value-of select="ca:ShippingInfo/ca:LastName"/></Surname>
				
				<xsl:if test="ca:ShippingInfo/ca:CompanyName != ''">
					<Company><xsl:value-of select="ca:ShippingInfo/ca:CompanyName"/></Company>
				</xsl:if>

				<Address1><xsl:value-of select="ca:ShippingInfo/ca:AddressLine1"/></Address1>
				<Address2><xsl:value-of select="ca:ShippingInfo/ca:AddressLine2"/></Address2>
				<Town><xsl:value-of select="ca:ShippingInfo/ca:City"/></Town>
				
				<xsl:if test="ca:ShippingInfo/ca:RegionDescription != 'No Region Required'" >
					<County><xsl:value-of select="ca:ShippingInfo/ca:RegionDescription"/></County>
				</xsl:if>
        
				<Postcode><xsl:value-of select="ca:ShippingInfo/ca:PostalCode"/></Postcode>
				<Country><xsl:value-of select="ca:ShippingInfo/ca:CountryCode"/></Country>
				<Telephone><xsl:value-of select="ca:ShippingInfo/ca:PhoneNumberDay"/></Telephone>
				<Email><xsl:value-of select="ca:BuyerEmailAddress"/></Email>
			</SalesOrderDeliveryAddress>

			<SalesOrderItems>
				<xsl:for-each select="ca:ShoppingCart/ca:LineItemSKUList/ca:OrderLineItemItem">
					<xsl:call-template name="Item" />
				</xsl:for-each>
			</SalesOrderItems>

			<Carriage>
				<Sku><xsl:value-of select="ca:ShippingInfo/ca:ShipmentList/ca:Shipment/ca:ShippingClass" /></Sku>
				<Name><xsl:value-of select="concat(ca:ShippingInfo/ca:ShipmentList/ca:Shipment/ca:ShippingCarrier, ' ', ca:ShippingInfo/ca:ShipmentList/ca:Shipment/ca:ShippingClass)" /></Name>
				<QtyOrdered>1</QtyOrdered>
				<UnitPrice><xsl:value-of select="ca:ShoppingCart/ca:LineItemInvoiceList/ca:OrderLineItemInvoice[ca:LineItemType = 'Shipping']/ca:UnitPrice - ca:ShoppingCart/ca:LineItemInvoiceList/ca:OrderLineItemInvoice[ca:LineItemType = 'VATShipping']/ca:UnitPrice" /></UnitPrice>
				<TaxCode>1</TaxCode>
			</Carriage>

			<TakenBy>Channel Advisor</TakenBy>
			
		</SalesOrder>
	</xsl:template>

	<xsl:template name="Item">
		<Item>
			<Sku><xsl:value-of select="ca:SKU" /></Sku>
			<Name><xsl:value-of select="ca:Title" /></Name>
			<QtyOrdered><xsl:value-of select="ca:Quantity" /></QtyOrdered>
			<UnitPrice><xsl:value-of select="ca:UnitPrice - ca:TaxCost" /></UnitPrice>
    	</Item>
	</xsl:template>

</xsl:stylesheet>