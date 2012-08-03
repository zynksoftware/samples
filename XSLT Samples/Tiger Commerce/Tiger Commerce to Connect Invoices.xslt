<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>
  <xsl:param name="ItemTaxRate" select="0.0"/>
  <xsl:param name="ShippingTaxRate" select="0.0"/>

  <xsl:template match="/">
    <Company>
      <Invoices>
        <xsl:for-each select="/Orders/Order">
          <xsl:call-template name="Invoice"/>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="./OrderNumber"/>
      </Id>
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
      <TakenBy>TigerCommerce</TakenBy>

      <InvoiceDeliveryAddress>
        <xsl:call-template name="DeliveryAddress"/>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:for-each select="./LineItems/Item">
          <xsl:call-template name="InvoiceItem"/>
        </xsl:for-each>

        <xsl:variable name="discount" select="./OrderValue + ./OrderDelPrice - ./OrderTotalValue"/>
        <xsl:if test="$discount > 0">
          <xsl:call-template name="Coupon">
            <xsl:with-param name="amount" select="$discount"/>
          </xsl:call-template>
        </xsl:if>
      </InvoiceItems>

      <Carriage>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="format-number(./OrderDelPrice, '0.00')"/>
        </UnitPrice>
        <TaxRate>
          <xsl:value-of select="$ShippingTaxRate"/>
        </TaxRate>
        <xsl:variable name="totalNet" select="./OrderDelPrice div ($ShippingTaxRate + 1)"/>
        <TotalNet>
          <xsl:value-of select="format-number($totalNet, '0.00')"/>
        </TotalNet>
        <TotalTax>
          <xsl:value-of select="format-number(./OrderDelPrice - $totalNet, '0.00')"/>
        </TotalTax>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentAmount>
        <xsl:value-of select="./OrderTotalValue"/>
      </PaymentAmount>

    </Invoice>
  </xsl:template>

  <!-- Delivery Address -->
  <xsl:template name="DeliveryAddress">
    <Forename>
      <xsl:value-of select="./DelFirstName"/>
    </Forename>
    <Surname>
      <xsl:value-of select="./DelLastName"/>
    </Surname>
    <Company>
      <xsl:value-of select="./DelCompanyName"/>
    </Company>
    <Address1>
      <xsl:value-of select="./DelAddress1"/>
    </Address1>
    <Address2>
      <xsl:value-of select="./DelAddress2"/>
    </Address2>
    <Town>
      <xsl:value-of select="./DelCity"/>
    </Town>
    <Postcode>
      <xsl:value-of select="./DelPostcode"/>
    </Postcode>
    <County>
      <xsl:value-of select="./DelCounty"/>
    </County>
    <Telephone>
      <xsl:value-of select="./DelPhoneNumber"/>
    </Telephone>
  </xsl:template>

  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <Item>
      <Sku>
        <xsl:value-of select="./ItemCode"/>
      </Sku>
      <Name>
        <xsl:value-of select="./ItemName"/>
      </Name>
      <QtyOrdered>
        <xsl:value-of select="./ItemQuantity"/>
      </QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="format-number(./ItemNetValue, '0.00')"/>
      </UnitPrice>
      <TaxRate>
        <xsl:value-of select="$ItemTaxRate"/>
      </TaxRate>
      <TotalNet>
        <xsl:value-of select="format-number(./Total, '0.00')"/>
      </TotalNet>
      <TotalTax>
        <xsl:value-of select="format-number(ceiling(./Total * $ItemTaxRate * 100) div 100, '0.00')"/>
      </TotalTax>
      <Type>Stock</Type>
    </Item>
  </xsl:template>

  <!-- Coupon codes -->
  <xsl:template name="Coupon">
    <xsl:param name="amount"/>
    <Item>
      <Name>Coupon Code</Name>
      <QtyOrdered>1</QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="format-number($amount * -1, '0.00')"/>
      </UnitPrice>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>

  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime"/>

    <xsl:variable name="date" select="substring-before($dateTime, ' at ')"/>
    <xsl:variable name="day" select="substring-before($date, '/')"/>
    <xsl:variable name="month" select="substring-before(substring-after($date, '/'), '/')"/>
    <xsl:variable name="year" select="substring-after(substring-after($date, '/'), '/')"/>
    
    <xsl:variable name="time" select="substring-after($dateTime, ' at ')"/>

    <xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', $time)"/>
  </xsl:template>

</xsl:stylesheet>
