<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:b="urn:ebay:apis:eBLBaseComponents"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

	<xsl:import href="Zynk.xslt"/>
	<xsl:output method="xml" indent="yes" />
	
	<xsl:param name="AccountReference" /> <!-- The account the orders should go to -->
	<xsl:param name="TaxCode" /> <!-- The tax code to use for taxable order items -->
	<xsl:param name="NonTaxableTaxCode" /> <!-- The tax code to use for order items with no tax -->
	<xsl:param name="NominalCode" /> <!-- Optional. The nominal code to use on the order items -->
	<xsl:param name="CarriageCode" /> <!-- Optional. The nominal code to use on the carriage charge -->
	<xsl:param name="TakenBy" /> <!-- Optional. The value to set in the 'Taken By' field -->
	<xsl:param name="VATInclusiveItem" /> <!-- Set to 'true' if the order item price is VAT inclusive -->
	<xsl:param name="VATInclusiveCarriage" /> <!-- Set to 'true' if the carriage charge is VAT inclusive -->
	<xsl:param name="AddPaymentDetails" /> <!-- Set to 'true' to include payment information on the orders -->
	<xsl:param name="BankAccount" /> <!-- Optional. The bank account to use for the payments -->
	
	<xsl:template match="GetOrdersResponseType">
		<Company>
			<SalesOrders>
				<xsl:for-each select="b:OrderArray/b:Order">
					<xsl:call-template name="OutputSalesOrder" />
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>

	<xsl:template name="OutputSalesOrder">
		<SalesOrder>
			<Id><xsl:value-of select="b:OrderID" /></Id>
			<CustomerId><xsl:value-of select="b:BuyerUserID" /></CustomerId>
			<SalesOrderNumber><xsl:value-of select="b:TransactionArray/b:Transaction/b:ShippingDetails/b:SellingManagerSalesRecordNumber" /></SalesOrderNumber>
			<CustomerOrderNumber><xsl:value-of select="b:TransactionArray/b:Transaction/b:ShippingDetails/b:SellingManagerSalesRecordNumber" /></CustomerOrderNumber>
			<Currency><xsl:value-of select="b:AmountPaid/@currencyID" /></Currency>
			<AccountReference><xsl:value-of select="$AccountReference" /></AccountReference>
			<SalesOrderDate><xsl:value-of select="b:CreatedTime" /></SalesOrderDate>
			<TakenBy><xsl:value-of select="$TakenBy" /></TakenBy>

			<SalesOrderAddress>
				<xsl:call-template name="OutputAddress" />
			</SalesOrderAddress>
			
			<SalesOrderDeliveryAddress>
				<xsl:call-template name="OutputAddress" /> 
			</SalesOrderDeliveryAddress>

			<!-- Items -->
			<xsl:call-template name="OutputItems" />

			<!-- Carriage -->
			<xsl:call-template name="OutputCarriage" />

			<!-- Payment Details -->
			<xsl:call-template name="OutputPaymentDetails" />

		</SalesOrder>
	</xsl:template>

	<xsl:template name="OutputItems">
		<SalesOrderItems>
			<xsl:for-each select="b:TransactionArray/b:Transaction">
				<xsl:call-template name="OutputItem" />
			</xsl:for-each>
		</SalesOrderItems>
	</xsl:template>
	
	<xsl:template name="OutputItem">
		<Item>
      <xsl:choose>
        <xsl:when test="b:Variation/b:VariationTitle">
          <Sku>
            <xsl:value-of select="b:Variation/b:SKU"/>
          </Sku>
          <Name>
            <xsl:value-of select="b:Variation/b:VariationTitle"/>
          </Name>
        </xsl:when>
        <xsl:otherwise>
          <Sku>
            <xsl:value-of select="b:Item/b:SKU"/>
          </Sku>
          <Name>
            <xsl:value-of select="b:Item/b:Title"/>
          </Name>
        </xsl:otherwise>
      </xsl:choose>
			<QtyOrdered><xsl:value-of select="b:QuantityPurchased" /></QtyOrdered>
			<UnitPrice>
				<xsl:call-template name="calculateNetPrice">
					<xsl:with-param name="Price" select="b:TransactionPrice" />
					<xsl:with-param name="TaxRate" select="b:Item/b:VATDetails/b:VATPercent" />
					<xsl:with-param name="CalculateAsVATInclusive" select="$VATInclusiveItem" />
				</xsl:call-template>
			</UnitPrice>
      
			<TaxCode>
				<xsl:choose>
					<xsl:when test="b:Item/b:VATDetails/b:VATPercent">
						<xsl:value-of select="$TaxCode" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$NonTaxableTaxCode" />
					</xsl:otherwise>
				</xsl:choose>
			</TaxCode>
			<TaxRate>
				<xsl:choose>
					<xsl:when test="b:Item/b:VATDetails/b:VATPercent">
						<xsl:value-of select="b:Item/b:VATDetails/b:VATPercent" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>0</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</TaxRate>
      
			<!-- NominalCode -->
			<xsl:call-template name="filterOutEmptyNode">
				<xsl:with-param name="NodeName">NominalCode</xsl:with-param>
				<xsl:with-param name="NodeValue" select="$NominalCode" />
			</xsl:call-template>
		</Item>
	</xsl:template>
	
	<xsl:template name="OutputAddress">
		<Forename><xsl:value-of select="b:ShippingAddress/b:Name" /></Forename>
		<Company><xsl:value-of select="b:ShippingAddress/b:Name" /></Company>
		<Address1><xsl:value-of select="b:ShippingAddress/b:Street1" /></Address1>
		<Address2><xsl:value-of select="b:ShippingAddress/b:Street2" /></Address2>
		<Town><xsl:value-of select="b:ShippingAddress/b:CityName" /></Town>
		<Postcode><xsl:value-of select="b:ShippingAddress/b:PostalCode" /></Postcode>
		<County><xsl:value-of select="b:ShippingAddress/b:StateOrProvince" /></County>
		<Country><xsl:value-of select="b:ShippingAddress/b:CountryName" /></Country>
		<Telephone><xsl:value-of select="b:ShippingAddress/b:Phone" /></Telephone>
	</xsl:template>

	<xsl:template name="OutputPaymentDetails">
		<xsl:choose>
			<xsl:when test="$AddPaymentDetails='true'">
				<BankAccount><xsl:value-of select="$BankAccount" /></BankAccount>
				<PaymentRef><xsl:value-of select="b:CheckoutStatus/b:PaymentMethod" /></PaymentRef>
				<PaymentAmount><xsl:value-of select="b:AmountPaid" /></PaymentAmount>
				<PaymentStatus><xsl:value-of select="b:CheckoutStatus/b:Status" /></PaymentStatus>
			</xsl:when>
			<xsl:otherwise><!-- Don't Output --></xsl:otherwise>
		</xsl:choose>
	</xsl:template>

  <xsl:template name="OutputCarriage">
    <Carriage>
		<Name><xsl:value-of select="b:ShippingServiceSelected/b:ShippingService" /></Name>
		<QtyOrdered>1</QtyOrdered>
		<UnitPrice>
			<xsl:call-template name="calculateNetPrice">
				<xsl:with-param name="Price" select="b:ShippingServiceSelected/b:ShippingServiceCost" />
				<xsl:with-param name="TaxRate" select="b:ShippingDetails/b:SalesTax/b:SalesTaxPercent" />
				<xsl:with-param name="CalculateAsVATInclusive" select="$VATInclusiveCarriage" />
			</xsl:call-template>
		</UnitPrice>

		<TaxCode>
			<xsl:choose>
				<xsl:when test="b:ShippingDetails/b:SalesTax/b:SalesTaxPercent">
					<xsl:value-of select="$TaxCode" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$NonTaxableTaxCode" />
				</xsl:otherwise>
			</xsl:choose>
		</TaxCode>
		<TaxRate>
			<xsl:choose>
				<xsl:when test="b:ShippingDetails/b:SalesTax/b:SalesTaxPercent">
					<xsl:value-of select="b:ShippingDetails/b:SalesTax/b:SalesTaxPercent" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>0</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</TaxRate>
      
		<NominalCode><xsl:value-of select="$CarriageCode" /></NominalCode>
	</Carriage>
  </xsl:template>

</xsl:stylesheet>