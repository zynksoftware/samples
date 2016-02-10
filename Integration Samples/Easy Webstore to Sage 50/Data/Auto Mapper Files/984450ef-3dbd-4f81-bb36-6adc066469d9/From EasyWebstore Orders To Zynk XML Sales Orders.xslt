<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:msxsl="urn:schemas-microsoft-com:xslt" 
	exclude-result-prefixes="msxsl"
>
    <xsl:output method="xml" indent="yes" />

	<xsl:param name="AccountReference" /> <!-- The account to add the orders to. Leave blank if auto generating account refs -->
	<xsl:param name="CarriageSku" /> <!-- The SKU code to use for the carriage charge -->
	<xsl:param name="Currency" >GBP</xsl:param>  <!-- The currency code -->
    <xsl:param name="BankAccount" /> <!-- The bank account to add payment to. Leave blank to not include payment -->
	<xsl:param name="PaymentRef" /> <!-- The reference to use for paid orders -->
	<xsl:param name="TaxCode">1</xsl:param> <!-- The tax code for taxable items -->
	<xsl:param name="NonTaxableTaxCode">2</xsl:param> <!-- The tax code for non taxable items -->
	<xsl:param name="TakenBy">Easy Webstore</xsl:param> <!-- The value for the 'Taken by' field -->

    <xsl:template match="/">
		<Company>
			<SalesOrders>
				<xsl:for-each select="ArrayOfOrder/Order">
					<xsl:call-template name="SalesOrder" />	
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>
	
	<xsl:template name="SalesOrder">
		<SalesOrder>
		    <Id><xsl:value-of select="ID" /></Id>
		    <CustomerId><xsl:value-of select="CustomerID" /></CustomerId>
			<CustomerOrderNumber><xsl:value-of select="ID" /></CustomerOrderNumber>
			<Currency><xsl:value-of select="$Currency" /></Currency>
    		<CurrencyUsed>false</CurrencyUsed>
			<SalesOrderDate><xsl:value-of select="PendingTimeStamp" /></SalesOrderDate>
			
			<xsl:if test="$AccountReference != ''">
				<AccountReference><xsl:value-of select="$AccountReference" /></AccountReference>
			</xsl:if>

			<SalesOrderAddress>
				<Title><xsl:value-of select="CustomerTitle" /></Title>
				<Forename><xsl:value-of select="CustomerFirstName" /></Forename>
        		<Surname><xsl:value-of select="CustomerLastName" /></Surname>
				<Company><xsl:value-of select="CustomerCompanyName" /></Company>
        		<Address1><xsl:value-of select="BillingAddress1" /></Address1>
        		<Address2><xsl:value-of select="BillingAddress2" /></Address2>
				<Address3><xsl:value-of select="BillingAddress3" /></Address3>
       			<Town><xsl:value-of select="BillingTown" /></Town>
        		<Postcode><xsl:value-of select="BillingPostcode" /></Postcode>
        		<County><xsl:value-of select="BillingCounty" /></County>
        		<Country><xsl:value-of select="BillingCountry" /></Country>
				<Email><xsl:value-of select="CustomerEmailAddress" /></Email>
				<Telephone><xsl:value-of select="CustomerTelephone" /></Telephone>
				<Fax><xsl:value-of select="CustomerFax" /></Fax>
			</SalesOrderAddress>
			
			<xsl:choose>
				<xsl:when test="DeliveryAddress1 = ''">
					<SalesOrderDeliveryAddress>
						<Title><xsl:value-of select="CustomerTitle" /></Title>
						<Forename><xsl:value-of select="CustomerFirstName" /></Forename>
        				<Surname><xsl:value-of select="CustomerLastName" /></Surname>
						<Address1><xsl:value-of select="BillingAddress1" /></Address1>
        				<Address2><xsl:value-of select="BillingAddress2" /></Address2>
						<Address3><xsl:value-of select="BillingAddress3" /></Address3>
       					<Town><xsl:value-of select="BillingTown" /></Town>
        				<Postcode><xsl:value-of select="BillingPostcode" /></Postcode>
        				<County><xsl:value-of select="BillingCounty" /></County>
        				<Country><xsl:value-of select="BillingCountry" /></Country>
						<Email><xsl:value-of select="CustomerEmailAddress" /></Email>
						<Telephone><xsl:value-of select="CustomerTelephone" /></Telephone>
						<Fax><xsl:value-of select="CustomerFax" /></Fax>
					</SalesOrderDeliveryAddress>
				</xsl:when>
				<xsl:otherwise>
					<SalesOrderDeliveryAddress>
						<Title><xsl:value-of select="CustomerTitle" /></Title>
						<Forename><xsl:value-of select="CustomerFirstName" /></Forename>
        				<Surname><xsl:value-of select="CustomerLastName" /></Surname>
						<Address1><xsl:value-of select="DeliveryAddress1" /></Address1>
        				<Address2><xsl:value-of select="DeliveryAddress2" /></Address2>
						<Address3><xsl:value-of select="DeliveryAddress3" /></Address3>
       					<Town><xsl:value-of select="DeliveryTown" /></Town>
        				<Postcode><xsl:value-of select="DeliveryPostcode" /></Postcode>
        				<County><xsl:value-of select="DeliveryCounty" /></County>
        				<Country><xsl:value-of select="DeliveryCountry" /></Country>
						<Email><xsl:value-of select="CustomerEmailAddress" /></Email>
						<Telephone><xsl:value-of select="CustomerTelephone" /></Telephone>
						<Fax><xsl:value-of select="CustomerFax" /></Fax>
					</SalesOrderDeliveryAddress>
				</xsl:otherwise>
			</xsl:choose>

			<SalesOrderItems>
				<xsl:for-each select="OrderItems/OrderItem">
					<xsl:call-template name="OrderItem" />
				</xsl:for-each>
				<xsl:call-template name="Carriage" />
			</SalesOrderItems>
			
			<TakenBy><xsl:value-of select="$TakenBy" /></TakenBy>
			<Status><xsl:value-of select="Status" /></Status>
			<VatInclusive>true</VatInclusive>

			<xsl:if test="CouponDiscount > 0">
				<NetValueDiscountDescription>Coupon</NetValueDiscountDescription>
     			<NetValueDiscount><xsl:value-of select="format-number(CouponDiscount div 100, '0.00')" /></NetValueDiscount>
			</xsl:if>

			<xsl:if test="$BankAccount != ''">
				<PaymentAmount>
					<xsl:choose>
						<xsl:when test="Status = 'Payment_Pending'">
							<xsl:text>0</xsl:text>
						</xsl:when>
						<xsl:when test="Status = 'Payment_Collected'">
							<xsl:value-of select="format-number((TotalPayable + TotalTaxPayable) div 100, '0.00')" />
						</xsl:when>
					</xsl:choose>
				</PaymentAmount>
				<BankAccount><xsl:value-of select="$BankAccount" /></BankAccount>
				<PaymentRef><xsl:value-of select="$PaymentRef" /></PaymentRef>
			</xsl:if>
		</SalesOrder>
	</xsl:template>
	
	<!--Order items -->
	<xsl:template name="OrderItem">
   		<Item>
			<Name>
				<xsl:choose>
					<xsl:when test="Option != ''">
						<xsl:value-of select="ProductTitle" /> - <xsl:value-of select="Option" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="ProductTitle" />
					</xsl:otherwise>
				</xsl:choose>
			</Name>

			<Sku>
				<xsl:choose>
					<xsl:when test="CompositeProductReference != ''">
						<xsl:value-of select="CompositeProductReference" />
					</xsl:when>
					<xsl:when test="OptionCode != ''">
						<xsl:value-of select="OptionCode" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="ProductCode" />
					</xsl:otherwise>
				</xsl:choose>
			</Sku>

    	  	<QtyOrdered><xsl:value-of select="Quantity" /></QtyOrdered>
			<UnitPrice><xsl:value-of select="format-number(((ItemPriceAtTimeOfOrder + ItemTaxAtTimeOfOrder) div Quantity) div 100, '0.00')" /></UnitPrice>
			
			<TaxCode>
				<xsl:choose>
					<xsl:when test="ItemTaxAtTimeOfOrder = 0">
						<xsl:value-of select="$NonTaxableTaxCode" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$TaxCode" />
					</xsl:otherwise>
				</xsl:choose>
			</TaxCode>
		</Item>
  	</xsl:template>

	<!-- Carriage -->
	<xsl:template name="Carriage">
		<Item>
			<Sku><xsl:value-of select="$CarriageSku" /></Sku>
			<Comments><xsl:value-of select="ShippingMethod/Name" /></Comments>
    	  	<QtyOrdered>1</QtyOrdered>
				
			<UnitPrice><xsl:value-of select="format-number((ShippingCost + ShippingTax) div 100, '0.00')" /></UnitPrice>
			
			<TaxCode>
				<xsl:choose>
					<xsl:when test="ShippingTax = 0 and ShippingCost > 0">
						<xsl:value-of select="$NonTaxableTaxCode"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$TaxCode" />
					</xsl:otherwise>
				</xsl:choose>
			</TaxCode>
		</Item>
	</xsl:template>
</xsl:stylesheet>