<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  
  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>

  <xsl:key name="CustId" match="CustomerId" use="."/>

  <xsl:template match="/">
    <Company>
      <Customers>
        <xsl:for-each select="Orders/Order/CustomerId[generate-id() = generate-id(key('CustId',.)[1])]">
          <xsl:call-template name="Customer"/>
        </xsl:for-each>
      </Customers>
      <Invoices>
        <xsl:for-each select="Orders/Order">
          <xsl:call-template name="Invoice"/>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Customer details-->
  <xsl:template name="Customer">
    <Customer>
      <Id><xsl:value-of select="."/></Id>
      <CustomerInvoiceAddress>
        <Forename>
          <xsl:value-of select="../BillingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="../BillingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="../BillingCompany"/>
        </Company>
        <Address1>
          <xsl:value-of select="../BillingAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="../BillingAddress2"/>
        </Address2>
        <Address3></Address3>
        <Town>
          <xsl:value-of select="../BillingCity"/>
        </Town>
        <Postcode>
          <xsl:value-of select="../BillingZipPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="../BillingStateProvince"/>
        </County>
        <Country>
          <xsl:value-of select="../BillingCountry"/>
        </Country>
        <Telephone>
          <xsl:value-of select="../BillingPhoneNumber"/>
        </Telephone>
      </CustomerInvoiceAddress>
      <CustomerDeliveryAddress>
        <Forename>
          <xsl:value-of select="../ShippingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="../ShippingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="../ShippingCompany"/>
        </Company>
        <Address1>
          <xsl:value-of select="../ShippingAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="../ShippingAddress2"/>
        </Address2>
        <Address3></Address3>
        <Town>
          <xsl:value-of select="../ShippingCity"/>
        </Town>
        <Postcode>
          <xsl:value-of select="../ShippingZipPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="../ShippingStateProvince"/>
        </County>
        <Country>
          <xsl:value-of select="../ShippingCountry"/>
        </Country>
        <Telephone>
          <xsl:value-of select="../ShippingPhoneNumber"/>
        </Telephone>
      </CustomerDeliveryAddress>
    </Customer>
  </xsl:template>

  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="./OrderId"/>
      </Id>
      <CustomerId>
        <xsl:value-of select="./CustomerId"/>
      </CustomerId>
      <InvoiceNumber>
        <xsl:value-of select="./OrderId"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="./OrderId"/>
      </CustomerOrderNumber>
      <Currency>
        <xsl:value-of select="./CustomerCurrencyCode"/>
      </Currency>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="./CreatedOn"/>
        </xsl:call-template>
      </InvoiceDate>
      <TakenBy>Mr Site</TakenBy>

      <xsl:choose>
        <xsl:when test="./CustomerCurrencyCode='GBP'">
          <CurrencyUsed>false</CurrencyUsed>
        </xsl:when>
        <xsl:otherwise>
          <CurrencyUsed>true</CurrencyUsed>
        </xsl:otherwise>
      </xsl:choose>

      <InvoiceAddress>
        <Forename>
          <xsl:value-of select="./BillingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./BillingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="./BillingCompany"/>
        </Company>
        <Address1>
          <xsl:value-of select="./BillingAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./BillingAddress2"/>
        </Address2>
        <Address3></Address3>
        <Town>
          <xsl:value-of select="./BillingCity"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./BillingZipPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="./BillingStateProvince"/>
        </County>
        <Country>
          <xsl:value-of select="./BillingCountry"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./BillingPhoneNumber"/>
        </Telephone>
      </InvoiceAddress>

      <InvoiceDeliveryAddress>
        <Forename>
          <xsl:value-of select="./ShippingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./ShippingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="./ShippingCompany"/>
        </Company>
        <Address1>
          <xsl:value-of select="./ShippingAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./ShippingAddress2"/>
        </Address2>
        <Address3></Address3>
        <Town>
          <xsl:value-of select="./ShippingCity"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./ShippingZipPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="./ShippingStateProvince"/>
        </County>
        <Country>
          <xsl:value-of select="./ShippingCountry"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./ShippingPhoneNumber"/>
        </Telephone>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:for-each select="OrderProductVariants/OrderProductVariant">
          <xsl:call-template name="InvoiceItem"/>
        </xsl:for-each>
      </InvoiceItems>

      <Carriage>
        <Name>
          <xsl:value-of select="./ShippingMethod"/>
        </Name>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="./OrderShippingExclTax"/>
        </UnitPrice>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentRef>
        <xsl:value-of select="./PaymentMethodName"/>
      </PaymentRef>
      <PaymentAmount>
        <xsl:value-of select="./OrderTotal"/>
      </PaymentAmount>
      <xsl:choose>
        <xsl:when test="./PaidDate!=''">
          <PaidFlag>1</PaidFlag>
        </xsl:when>
        <xsl:otherwise >
          <PaidFlag>0</PaidFlag>
        </xsl:otherwise>
      </xsl:choose>
      
    </Invoice>
  </xsl:template>

  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <Item>
      <Sku>
        <xsl:value-of select="./ProductVariantId"/>
      </Sku>
      <Name>
        <xsl:value-of select="./ProductVariantName"/>
      </Name>
      <Description>
        <xsl:value-of select="./AttributeDescription"/>
      </Description>
      <QtyOrdered>
        <xsl:value-of select="./Quantity"/>
      </QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="./UnitPriceExclTax"/>
      </UnitPrice>
      <UnitDiscountAmount>
        <xsl:value-of select="./DiscountAmountInclTax"/>
      </UnitDiscountAmount>
      <TotalNet>
        <xsl:value-of select="./PriceExclTax"/>
      </TotalNet>
      <TotalTax>
        <xsl:value-of select="./PriceInclTax - ./PriceExclTax"/>
      </TotalTax>
      <Type>Stock</Type>
    </Item>
  </xsl:template>

  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime" />
    <xsl:variable name="time" select="substring-after($dateTime, ' ')" />
    <xsl:variable name="date" select="substring-before($dateTime, ' ')" />
    <xsl:variable name="day" select="substring-before($date, '/')" />
    <xsl:variable name="month" select="substring-before(substring-after($date, '/'), '/')" />
    <xsl:variable name="year" select="substring-after(substring-after($date, '/'), '/')" />
    <xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', $time)" />
  </xsl:template>
</xsl:stylesheet>