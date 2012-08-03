<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet 
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:param name="AccountReference" />

  <xsl:key name="CustId" match="CustomerNumber" use="."/>
  
	<xsl:template match="/">
		<Company>
      <Customers>
        <xsl:for-each select="Orders/Order/CustomerNumber[generate-id() = generate-id(key('CustId',.)[1])]">
          <xsl:call-template name="Customer"/>          
        </xsl:for-each>
      </Customers>
      <Invoices>
				<xsl:for-each select="Orders/Order">
					<xsl:call-template name="Invoice" />	
				</xsl:for-each>
			</Invoices>
		</Company>
	</xsl:template>

  <!-- Customer details-->
  <xsl:template name="Customer">
    <Customer>
      <Id><xsl:value-of select="."/></Id>
      <CustomerInvoiceAddress>
        <Title><xsl:value-of select="../Addresses/BillingAddress/Title"/></Title>
        <Forename><xsl:value-of select="../Addresses/BillingAddress/FirstName"/></Forename>
        <Surname><xsl:value-of select="../Addresses/BillingAddress/LastName"/></Surname>
        <Company><xsl:value-of select="../Addresses/BillingAddress/Company"/></Company>
        <Address1><xsl:value-of select="../Addresses/BillingAddress/Street"/></Address1>
        <Address2><xsl:value-of select="../Addresses/BillingAddress/Street2"/></Address2>
        <Town><xsl:value-of select="../Addresses/BillingAddress/City"/></Town>
        <Postcode><xsl:value-of select="../Addresses/BillingAddress/Zipcode"/></Postcode>
        <County><xsl:value-of select="../Addresses/BillingAddress/State"/></County>
        <Country><xsl:value-of select="../Addresses/BillingAddress/Country"/></Country>
      </CustomerInvoiceAddress>
      <CustomerDeliveryAddress>
        <Title><xsl:value-of select="../Addresses/ShippingAddress/Title"/></Title>
        <Forename><xsl:value-of select="../Addresses/ShippingAddress/FirstName"/></Forename>
        <Surname><xsl:value-of select="../Addresses/ShippingAddress/LastName"/></Surname>
        <Company><xsl:value-of select="../Addresses/ShippingAddress/Company"/></Company>
        <Address1><xsl:value-of select="../Addresses/ShippingAddress/Street"/></Address1>
        <Address2><xsl:value-of select="../Addresses/ShippingAddress/Street2"/></Address2>
        <Town><xsl:value-of select="../Addresses/ShippingAddress/City"/></Town>
        <Postcode><xsl:value-of select="../Addresses/ShippingAddress/Zipcode"/></Postcode>
        <County><xsl:value-of select="../Addresses/ShippingAddress/State"/></County>
        <Country><xsl:value-of select="../Addresses/ShippingAddress/Country"/></Country>
      </CustomerDeliveryAddress>
    </Customer>
  </xsl:template>

  <!-- Invoice details-->
	<xsl:template name="Invoice">
		<Invoice>
		  <Id><xsl:value-of select="OrderNumber"/></Id>
		  <CustomerId><xsl:value-of select="CustomerNumber"/></CustomerId>
		  <InvoiceNumber><xsl:value-of select="OrderNumber"/></InvoiceNumber>
      <Currency><xsl:value-of select="Currency"/></Currency>
      <AccountReference><xsl:value-of select="$AccountReference"/></AccountReference>
      <InvoiceDate><xsl:value-of select="CreationDate"/></InvoiceDate>
      <TakenBy>ePages</TakenBy>
      
      <xsl:choose>
        <xsl:when test="Currency='GBP'">
          <CurrencyUsed>false</CurrencyUsed>
        </xsl:when>
        <xsl:otherwise>
          <CurrencyUsed>true</CurrencyUsed>
        </xsl:otherwise>
      </xsl:choose>

      <InvoiceAddress>
        <Title><xsl:value-of select="Addresses/BillingAddress/Title"/></Title>
        <Forename><xsl:value-of select="Addresses/BillingAddress/FirstName"/></Forename>
        <Surname><xsl:value-of select="Addresses/BillingAddress/LastName"/></Surname>
        <Company><xsl:value-of select="Addresses/BillingAddress/Company"/></Company>
        <Address1><xsl:value-of select="Addresses/BillingAddress/Street"/></Address1>
        <Address2><xsl:value-of select="Addresses/BillingAddress/Street2"/></Address2>
        <Town><xsl:value-of select="Addresses/BillingAddress/City"/></Town>
        <Postcode><xsl:value-of select="Addresses/BillingAddress/Zipcode"/></Postcode>
        <County><xsl:value-of select="Addresses/BillingAddress/State"/></County>
        <Country><xsl:value-of select="Addresses/BillingAddress/Country"/></Country>
      </InvoiceAddress>

      <InvoiceDeliveryAddress>
        <Title><xsl:value-of select="Addresses/ShippingAddress/Title"/></Title>
        <Forename><xsl:value-of select="Addresses/ShippingAddress/FirstName"/></Forename>
        <Surname><xsl:value-of select="Addresses/ShippingAddress/LastName"/></Surname>
        <Company><xsl:value-of select="Addresses/ShippingAddress/Company"/></Company>
        <Address1><xsl:value-of select="Addresses/ShippingAddress/Street"/></Address1>
        <Address2><xsl:value-of select="Addresses/ShippingAddress/Street2"/></Address2>
        <Town><xsl:value-of select="Addresses/ShippingAddress/City"/></Town>
        <Postcode><xsl:value-of select="Addresses/ShippingAddress/Zipcode"/></Postcode>
        <County><xsl:value-of select="Addresses/ShippingAddress/State"/></County>
        <Country><xsl:value-of select="Addresses/ShippingAddress/Country"/></Country>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:for-each select="LineItems/LineItem">
          <xsl:call-template name="InvoiceItem" />
        </xsl:for-each>
        <xsl:for-each select="LineItems/LineItemDiscount">
          <xsl:call-template name="DiscountItem" />
        </xsl:for-each>
        <xsl:for-each select="LineItems/LineItemSalesDiscount">
          <xsl:call-template name="CouponDiscountItem" />
        </xsl:for-each>
        <xsl:for-each select="LineItems/LineItemPayment">
          <xsl:call-template name="PaymentFeeItem" />
        </xsl:for-each>
        <xsl:for-each select="LineItems/LineItemPaymentDiscount">
          <xsl:call-template name="PaymentDiscountItem" />
        </xsl:for-each>
      </InvoiceItems>
      
      <Carriage>
        <Id><xsl:value-of select="./LineItems/LineItemShipping/Id"/></Id>
        <Name><xsl:value-of select="./LineItems/LineItemShipping/Name"/></Name>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice><xsl:value-of select="./LineItems/LineItemShipping/TotalPrice"/></UnitPrice>
        <xsl:choose>
          <xsl:when test="./LineItems/LineItemShipping/TaxRate">
            <TaxRate><xsl:value-of select="./LineItems/LineItemShipping/TaxRate"/></TaxRate>
            <xsl:variable name="totNet" select="format-number(LineItems/LineItemShipping/TotalPrice div (LineItems/LineItemShipping/TaxRate + 1),'0.00')"/>
            <TotalNet><xsl:value-of select="$totNet"/></TotalNet>
            <TotalTax><xsl:value-of select="format-number(LineItems/LineItemShipping/TotalPrice - $totNet,'0.00')"/></TotalTax>
          </xsl:when>
          <xsl:otherwise>
            <TotalNet><xsl:value-of select="./LineItems/LineItemShipping/TotalPrice"/></TotalNet>
            <TotalTax><xsl:value-of select="0.00"/></TotalTax>
          </xsl:otherwise>
        </xsl:choose>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentAmount><xsl:value-of select="./GrandTotal"/></PaymentAmount>
      <PaymentType><xsl:value-of select="./LineItems/LineItemPayment/Id"/></PaymentType>
      
    </Invoice>
	</xsl:template>

  <!-- Invoice items-->
  <xsl:template name="InvoiceItem">
    <Item>
      <Sku><xsl:value-of select="./Id"/></Sku>
      <Name><xsl:value-of select="./Name"/></Name>
      <UnitOfSale><xsl:value-of select="./OrderUnit"/></UnitOfSale>
      <QtyOrdered><xsl:value-of select="./Quantity"/></QtyOrdered>
      <UnitPrice><xsl:value-of select="./UnitPrice"/></UnitPrice>
      <UnitDiscountAmount><xsl:value-of select="./Discount"/></UnitDiscountAmount>
      <TaxRate><xsl:value-of select="./TaxRate"/></TaxRate>
      <xsl:variable name="net" select="format-number(TotalPrice div (TaxRate + 1),'0.00')"/>
      <!-- Totals with discount applied-->
      <TotalNet><xsl:value-of select="$net"/></TotalNet>
      <TotalTax><xsl:value-of select="format-number(TotalPrice - $net,'0.00')"/></TotalTax>
      <Type>Stock</Type>
    </Item>
  </xsl:template>

  <!-- Include any shopping basket as an item-->
  <xsl:template name="DiscountItem">
    <Item>
      <Name>ShoppingBasketDiscount</Name>
      <Comments>Discount</Comments>
      <UnitPrice><xsl:value-of select="./TotalPrice"/></UnitPrice>
      <QtyOrdered>1</QtyOrdered>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>
  
  <!-- Include any coupons as an item-->
  <xsl:template name="CouponDiscountItem">
    <Item>
      <Sku><xsl:value-of select="./Id"/></Sku>
      <Name><xsl:value-of select="./Name"/></Name>
      <Comments>Coupon code</Comments>
      <UnitPrice><xsl:value-of select="./TotalPrice"/></UnitPrice>
      <QtyOrdered>1</QtyOrdered>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>

  <!-- Include the payment method fee as an item-->
  <xsl:template name="PaymentFeeItem">
    <Item>
      <Name>PaymentFee</Name>
      <Comments>Payment method fee for <xsl:value-of select="./Id"/></Comments>
      <xsl:choose>
        <xsl:when test="./TotalPrice">
          <UnitPrice><xsl:value-of select="./TotalPrice"/></UnitPrice>
        </xsl:when>
        <xsl:otherwise>
          <UnitPrice>0.00</UnitPrice>
        </xsl:otherwise>
      </xsl:choose>
      <QtyOrdered>1</QtyOrdered>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>

  <!-- Include the discount payment method as an item-->
  <xsl:template name="PaymentDiscountItem">
    <Item>
      <Name>PaymentFeeWithDiscount</Name>
      <Comments>Payment method fee with discount applied</Comments>
      <UnitPrice><xsl:value-of select="./TotalPrice"/></UnitPrice>
      <QtyOrdered>1</QtyOrdered>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>
  
</xsl:stylesheet>