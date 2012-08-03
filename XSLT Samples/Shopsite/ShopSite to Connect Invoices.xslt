<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>

  <xsl:key name="CustId" match="CustomerID" use="."/>
  
  <xsl:template match="/">
    <Company>
      <Customers>
        <xsl:for-each select="/ShopSiteOrders/Order/Other/CustomerID[generate-id() = generate-id(key('CustId',.)[1])]/../..">
          <xsl:if test="./Other/CustomerID != ''">
            <xsl:call-template name="Customer"/>
          </xsl:if>
        </xsl:for-each>
      </Customers>
      <Invoices>
        <xsl:for-each select="/ShopSiteOrders/Order">
          <xsl:call-template name="Invoice"/>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Customer details -->
  <xsl:template name="Customer">
    <Customer>
      <Id>
        <xsl:value-of select="./Other/CustomerID"/>
      </Id>
      <CompanyName>
        <xsl:value-of select="./Billing/Company"/>
      </CompanyName>
      <CustomerInvoiceAddress>
        <xsl:call-template name="InvoiceAddress"/>
      </CustomerInvoiceAddress>
      <CustomerDeliveryAddress>
        <xsl:call-template name="DeliveryAddress"/>
      </CustomerDeliveryAddress>
    </Customer>
  </xsl:template>
  
  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="./ShopSiteTransactionID"/>
      </Id>
      <CustomerId>
        <xsl:value-of select="./Other/CustomerID"/>
      </CustomerId>
      <InvoiceNumber>
        <xsl:value-of select="./OrderNumber"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="./OrderNumber"/>
      </CustomerOrderNumber>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="./OrderDate"/>
        </xsl:call-template>
      </InvoiceDate>
      <TakenBy>ShopSite</TakenBy>

      <InvoiceAddress>
        <xsl:call-template name="InvoiceAddress"/>
      </InvoiceAddress>
      <InvoiceDeliveryAddress>
        <xsl:call-template name="DeliveryAddress"/>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:for-each select="./Shipping/Products/Product">
          <xsl:call-template name="InvoiceItem"/>
        </xsl:for-each>

        <xsl:for-each select="./Coupon">
          <xsl:if test="./Total">
            <xsl:call-template name="Coupon"/>
          </xsl:if>
        </xsl:for-each>
      </InvoiceItems>
      
      <Carriage>
        <Name>
          <xsl:value-of select="./Totals/ShippingTotal/Description"/>
        </Name>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="./Totals/ShippingTotal/Total"/>
        </UnitPrice>
        <xsl:choose>
          <xsl:when test="./Totals/Tax/TaxShipping = 'Yes'">
            <xsl:variable name ="taxRate" select="../../../Totals/Tax/TaxRate div 100"/>
            <TaxRate>
              <xsl:value-of select="$taxRate"/>
            </TaxRate>
            <TotalNet>
              <xsl:value-of select="./Totals/ShippingTotal/Total"/>
            </TotalNet>
            <TotalTax>
              <xsl:value-of select="./Totals/ShippingTotal/Total * $taxRate"/>
            </TotalTax>
          </xsl:when>
          <xsl:otherwise>
            <TaxRate>
              <xsl:text>0</xsl:text>
            </TaxRate>
            <TotalNet>
              <xsl:value-of select="./Totals/ShippingTotal/Total"/>
            </TotalNet>
            <TotalTax>
              <xsl:text>0.00</xsl:text>
            </TotalTax>
          </xsl:otherwise>
        </xsl:choose>
        
        <Type>NonStock</Type>
      </Carriage>

      <PaymentRef>
        <xsl:value-of select="./OrderGateway"/>
      </PaymentRef>
      <PaymentAmount>
        <xsl:value-of select="./Totals/GrandTotal"/>
      </PaymentAmount>

    </Invoice>
  </xsl:template>

  <!-- Invoice Address -->
  <xsl:template name="InvoiceAddress">
    <Title>
      <xsl:value-of select="./Billing/NameParts/Title"/>
    </Title>
    <Forename>
      <xsl:value-of select="./Billing/NameParts/FirstName"/>
    </Forename>
    <Surname>
      <xsl:value-of select="./Billing/NameParts/LastName"/>
    </Surname>
    <Company>
      <xsl:value-of select="./Billing/Company"/>
    </Company>
    <Address1>
      <xsl:value-of select="./Billing/Address/Street1"/>
    </Address1>
    <Address2>
      <xsl:value-of select="./Billing/Address/Street2"/>
    </Address2>
    <Town>
      <xsl:value-of select="./Billing/Address/City"/>
    </Town>
    <Postcode>
      <xsl:value-of select="./Billing/Address/Code"/>
    </Postcode>
    <County>
      <xsl:value-of select="./Billing/Address/State"/>
    </County>
    <Country>
      <xsl:value-of select="./Billing/Address/Country"/>
    </Country>
    <Telephone>
      <xsl:value-of select="./Billing/Phone"/>
    </Telephone>
    <Email>
      <xsl:value-of select="./Billing/Email"/>
    </Email>
  </xsl:template>

  <!-- Delivery Address -->
  <xsl:template name="DeliveryAddress">
    <Title>
      <xsl:value-of select="./Shipping/NameParts/Title"/>
    </Title>
    <Forename>
      <xsl:value-of select="./Shipping/NameParts/FirstName"/>
    </Forename>
    <Surname>
      <xsl:value-of select="./Shipping/NameParts/LastName"/>
    </Surname>
    <Company>
      <xsl:value-of select="./Shipping/Company"/>
    </Company>
    <Address1>
      <xsl:value-of select="./Shipping/Address/Street1"/>
    </Address1>
    <Address2>
      <xsl:value-of select="./Shipping/Address/Street2"/>
    </Address2>
    <Town>
      <xsl:value-of select="./Shipping/Address/City"/>
    </Town>
    <Postcode>
      <xsl:value-of select="./Shipping/Address/Code"/>
    </Postcode>
    <County>
      <xsl:value-of select="./Shipping/Address/State"/>
    </County>
    <Country>
      <xsl:value-of select="./Shipping/Address/Country"/>
    </Country>
    <Telephone>
      <xsl:value-of select="./Shipping/Phone"/>
    </Telephone>
  </xsl:template>

  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <Item>
      <Sku>
        <xsl:value-of select="./SKU"/>
      </Sku>
      <Name>
        <xsl:value-of select="./Name"/>
      </Name>
      <xsl:if test="./OrderOptions != ''">
        <Comments>
          <xsl:value-of select="./OrderOptions"/>
        </Comments>
      </xsl:if>
      <QtyOrdered>
        <xsl:value-of select="./Quantity"/>
      </QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="./ItemPrice"/>
      </UnitPrice>
      <xsl:choose>
        <xsl:when test="./Taxable = 'Yes'">
          <xsl:variable name ="taxRate" select="../../../Totals/Tax/TaxRate div 100"/>
          <TaxRate>
            <xsl:value-of select="$taxRate"/>
          </TaxRate>
          <TotalNet>
            <xsl:value-of select="./Total"/>
          </TotalNet>
          <TotalTax>
            <xsl:value-of select="format-number(./Total * $taxRate, '0.00')"/>
          </TotalTax>
        </xsl:when>
        <xsl:otherwise>
          <TaxRate>
            <xsl:text>0</xsl:text>
          </TaxRate>
          <TotalNet>
            <xsl:value-of select="./Total"/>
          </TotalNet>
          <TotalTax>0.00</TotalTax>
        </xsl:otherwise>
      </xsl:choose>
      <Type>Stock</Type>
    </Item>
  </xsl:template>

  <!-- Coupon codes -->
  <xsl:template name="Coupon">
    <Item>
      <Name>
        <xsl:value-of select="./Name"/>
      </Name>
      <QtyOrdered>1</QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="./Total"/>
      </UnitPrice>
      <xsl:choose>
        <xsl:when test="./ApplyCoupon = 'Pre'">
          <xsl:variable name ="taxRate" select="../Totals/Tax/TaxRate div 100"/>
          <TaxRate>
            <xsl:value-of select="$taxRate"/>
          </TaxRate>
          <TotalNet>
            <xsl:value-of select="./Total"/>
          </TotalNet>
          <TotalTax>
            <xsl:value-of select="format-number(./Total * $taxRate, '0.00')"/>
          </TotalTax>
        </xsl:when>
        <xsl:otherwise>
          <TaxRate>0</TaxRate>
          <TotalNet>
            <xsl:value-of select="./Total"/>
          </TotalNet>
          <TotalTax>0.00</TotalTax>
        </xsl:otherwise>
      </xsl:choose>
    </Item>
  </xsl:template>
  
  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime"/>

    <xsl:variable name="date" select="substring-before($dateTime, ' ')"/>
    <xsl:variable name="time" select="substring-after($dateTime, ' ')"/>

    <xsl:value-of select="concat($date, 'T', $time)"/>
  </xsl:template>

</xsl:stylesheet>