<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>

  <xsl:key name="CustId" match="CustomerID" use="."/>

  <xsl:template match="/">
    <Company>
      <Customers>
        <xsl:for-each select="xmldata/Orders/CustomerID[generate-id() = generate-id(key('CustId',.)[1])]">
          <xsl:call-template name="Customer"/>
        </xsl:for-each>
      </Customers>
      <Invoices>
        <xsl:for-each select="xmldata/Orders">
          <xsl:call-template name="Invoice"/>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Customer details-->
  <xsl:template name="Customer">
    <Customer>
      <Id>
        <xsl:value-of select="."/>
      </Id>
      <CustomerInvoiceAddress>
        <Forename>
          <xsl:value-of select="../BillingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="../BillingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="../BillingCompanyName"/>
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
          <xsl:value-of select="../BillingPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="../BillingState"/>
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
          <xsl:value-of select="../ShipFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="../ShipLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="../ShipCompanyName"/>
        </Company>
        <Address1>
          <xsl:value-of select="../ShipAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="../ShipAddress2"/>
        </Address2>
        <Address3></Address3>
        <Town>
          <xsl:value-of select="../ShipCity"/>
        </Town>
        <Postcode>
          <xsl:value-of select="../ShipPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="../ShipState"/>
        </County>
        <Country>
          <xsl:value-of select="../ShipCountry"/>
        </Country>
        <Telephone>
          <xsl:value-of select="../ShipPhoneNumber"/>
        </Telephone>
      </CustomerDeliveryAddress>
    </Customer>
  </xsl:template>

  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="./OrderID"/>
      </Id>
      <CustomerId>
        <xsl:value-of select="./CustomerID"/>
      </CustomerId>
      <InvoiceNumber>
        <xsl:value-of select="./OrderID"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="./OrderID"/>
      </CustomerOrderNumber>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="./OrderDate"/>
        </xsl:call-template>
      </InvoiceDate>
      <TakenBy>Volusion</TakenBy>

      <InvoiceAddress>
        <Forename>
          <xsl:value-of select="./BillingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./BillingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="./BillingCompanyName"/>
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
          <xsl:value-of select="./BillingPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="./BillingState"/>
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
          <xsl:value-of select="./ShipFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./ShipLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="./ShipCompanyName"/>
        </Company>
        <Address1>
          <xsl:value-of select="./ShipAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./ShipAddress2"/>
        </Address2>
        <Address3></Address3>
        <Town>
          <xsl:value-of select="./ShipCity"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./ShipPostalCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="./ShipState"/>
        </County>
        <Country>
          <xsl:value-of select="./ShipCountry"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./ShipPhoneNumber"/>
        </Telephone>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:for-each select="OrderDetails">
          <xsl:call-template name="InvoiceItem"/>
        </xsl:for-each>
      </InvoiceItems>

      <Carriage>
        <Id>
          <xsl:value-of select="./ShippingMethodID"/>
        </Id>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="./TotalShippingCost"/>
        </UnitPrice>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentRef>
        <xsl:value-of select="./PaymentMethodID"/>
      </PaymentRef>
      <PaymentAmount>
        <xsl:value-of select="./PaymentAmount"/>
      </PaymentAmount>
      <xsl:choose>
        <xsl:when test="./Total_Payment_Received=./PaymentAmount">
          <PaidFlag>1</PaidFlag>
        </xsl:when>
        <xsl:otherwise >
          <PaidFlag>0</PaidFlag>
        </xsl:otherwise>
      </xsl:choose>
      <AmountPaid>
        <xsl:value-of select="./Total_Payment_Received"/>
      </AmountPaid>

    </Invoice>
  </xsl:template>

  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <Item>
      <Sku>
        <xsl:value-of select="./ProductID"/>
      </Sku>
      <Name>
        <xsl:value-of select="./ProductName"/>
      </Name>
      <QtyOrdered>
        <xsl:value-of select="./Quantity"/>
      </QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="./ProductPrice"/>
      </UnitPrice>
      <TotalNet>
        <xsl:value-of select="./TotalPrice"/>
      </TotalNet>
      <xsl:choose>
        <xsl:when test="./CouponCode">
          <Type>NonStock</Type>
        </xsl:when>
        <xsl:otherwise>
          <Type>Stock</Type>
        </xsl:otherwise>
      </xsl:choose>
      <QtyAllocated>
        <xsl:value-of select="./QtyOnHold"/>
      </QtyAllocated>
      <QtyDespatched>
        <xsl:value-of select="./QtyShipped"/>
      </QtyDespatched>
    </Item>
  </xsl:template>

  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime" />
    
    <xsl:variable name="date" select="substring-before($dateTime, ' ')" />
    <xsl:variable name="month" select="substring-before($date, '/')" />
    <xsl:variable name="day" select="substring-before(substring-after($date, '/'), '/')" />
    <xsl:variable name="year" select="substring-after(substring-after($date, '/'), '/')" />

    <xsl:variable name="time" select="substring-after($dateTime, ' ')" />
    <xsl:variable name="hour" select="substring-before($time, ':')"/>
    <xsl:variable name="min" select="substring-before(substring-after($time, ':'), ':')"/>
    <xsl:variable name="sec" select="substring-before(substring-after(substring-after($time, ':'), ':'), ' ')"/>

    <xsl:choose>
      <xsl:when test ="substring-after($time, ' ')='PM'">
        <xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', $hour+12, ':', $min, ':', $sec)" />
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', $hour, ':', $min, ':', $sec)" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
</xsl:stylesheet>