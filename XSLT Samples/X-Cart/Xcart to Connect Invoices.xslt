<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>
  <xsl:param name="ShippingTaxRate" select="0.0"/> <!-- Default to no tax -->

  <xsl:template match="/">
    <Company>
      <Invoices>
        <xsl:for-each select="Orders/Order">
          <xsl:call-template name="Invoice"/>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Invoice details -->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="./ORDERID"/>
      </Id>
      <InvoiceNumber>
        <xsl:value-of select="./ORDERID"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="./ORDERID"/>
      </CustomerOrderNumber>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <Notes1>
        <xsl:choose>
          <xsl:when test="./CUSTOMER_NOTES != ''">
            <xsl:value-of select="concat('Customer Notes: ', ./CUSTOMER_NOTES)"/>
          </xsl:when>
          <xsl:when test="contains(./DETAILS, 'Customer notes: ')">
            <xsl:value-of select="concat('Customer Notes:', substring-after(./DETAILS ,'Customer notes:'))"/>
          </xsl:when>
        </xsl:choose>
      </Notes1>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="./DATE"/>
        </xsl:call-template>
      </InvoiceDate>
      <TakenBy>X-Cart</TakenBy>

      <InvoiceAddress>
        <Title>
          <xsl:value-of select="./B_TITLE"/>
        </Title>
        <Forename>
          <xsl:value-of select="./B_FIRSTNAME"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./B_LASTNAME"/>
        </Surname>
        <Address1>
          <xsl:value-of select="./B_ADDRESS"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./B_ADDRESS_2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./B_CITY"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./B_ZIPCODE"/>
        </Postcode>
        <County>
          <xsl:value-of select="./B_STATE"/>
        </County>
        <Country>
          <xsl:value-of select="./B_COUNTRY"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./B_PHONE"/>
        </Telephone>
        <Fax>
          <xsl:value-of select="./B_FAX"/>
        </Fax>
        <Email>
          <xsl:value-of select="./EMAIL"/>
        </Email>
      </InvoiceAddress>

      <InvoiceDeliveryAddress>
        <Title>
          <xsl:value-of select="./S_TITLE"/>
        </Title>
        <Forename>
          <xsl:value-of select="./S_FIRSTNAME"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./S_LASTNAME"/>
        </Surname>
        <Address1>
          <xsl:value-of select="./S_ADDRESS"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./S_ADDRESS_2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./S_CITY"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./S_ZIPCODE"/>
        </Postcode>
        <County>
          <xsl:value-of select="./S_STATE"/>
        </County>
        <Country>
          <xsl:value-of select="./S_COUNTRY"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./S_PHONE"/>
        </Telephone>
        <Fax>
          <xsl:value-of select="./S_FAX"/>
        </Fax>
        <Email>
          <xsl:value-of select="./EMAIL"/>
        </Email>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:variable name="orderId" select="./ORDERID"/>
        <xsl:for-each select="/Orders/OrderItem[ORDERID = $orderId]">
          <xsl:call-template name="InvoiceItem"/>
        </xsl:for-each>

        <!-- Include discount as an item -->
        <xsl:if test="./DISCOUNT > 0">
          <Item>
            <Name>Discounts</Name>
            <QtyOrdered>1</QtyOrdered>
            <UnitPrice>
              <xsl:value-of select="./DISCOUNT * -1"/>
            </UnitPrice>
            <Type>NonStock</Type>
          </Item>
        </xsl:if>
        
        <!-- Include coupons as an item -->
        <xsl:if test="./COUPON_DISCOUNT > 0">
          <Item>
            <Name>
              <xsl:value-of select="concat('Coupon: ', ./COUPON)"/>
            </Name>
            <QtyOrdered>1</QtyOrdered>
            <UnitPrice>
              <xsl:value-of select="./COUPON_DISCOUNT * -1"/>
            </UnitPrice>
            <Type>NonStock</Type>
          </Item>
        </xsl:if>
      </InvoiceItems>

      <Carriage>
        <Id>
          <xsl:value-of select="./SHIPPINGID"/>
        </Id>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="format-number(./SHIPPING_COST, '0.00')"/>
        </UnitPrice>
        <TaxRate>
          <xsl:value-of select="$ShippingTaxRate"/>
        </TaxRate>
        <TotalNet>
          <xsl:value-of select="format-number(./SHIPPING_COST, '0.00')"/>
        </TotalNet>
        <TotalTax>
          <xsl:value-of select="format-number(./SHIPPING_COST * $ShippingTaxRate, '0.00')"/>
        </TotalTax>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentRef>
        <xsl:value-of select="./PAYMENT_METHOD"/>
      </PaymentRef>
      <PaymentAmount>
        <xsl:value-of select="./TOTAL"/>
      </PaymentAmount>
    </Invoice>
  </xsl:template>
  
  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <Item>
      <Id>
        <xsl:value-of select="./PRODUCTID"/>
      </Id>
      <Sku>
        <xsl:value-of select="./PRODUCTCODE"/>
      </Sku>
      <Name>
        <xsl:value-of select="./PRODUCT"/>
      </Name>
      <Comments>
        <!-- Include the product details if any -->
        <xsl:if test="./PRODUCT_CLASS != ''">
          <xsl:value-of select="concat(./PRODUCT_CLASS, ': ', ./PRODUCT_CLASS_OPTION)"/>
        </xsl:if>
        <xsl:variable name="nextNode" select="./following-sibling::OrderItem[1]"/>
        <xsl:if test="$nextNode/ORDERID = '' and $nextNode/PRODUCT_CLASS != ''">
          <xsl:value-of select="concat(', ', $nextNode/PRODUCT_CLASS, ': ', $nextNode/PRODUCT_CLASS_OPTION)"/>
        </xsl:if>
      </Comments>
      <QtyOrdered>
        <xsl:value-of select="./AMOUNT"/>
      </QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="format-number(./PRICE, '0.00')"/>
      </UnitPrice>
      <xsl:variable name="orderId" select="./ORDERID"/>
      <xsl:variable name="ItemTaxRate" select="substring-before(substring-after(/Orders/Order[ORDERID = $orderId]/TAXES_APPLIED, '&quot;rate_value&quot;;d:'), ';') div 100"/>
      <TaxRate>
        <xsl:value-of select="$ItemTaxRate"/>
      </TaxRate>
      <TotalNet>
        <xsl:value-of select="format-number(./PRICE * ./AMOUNT, '0.00')"/>
      </TotalNet>
      <TotalTax>
        <xsl:value-of select="format-number(./PRICE * ./AMOUNT * $ItemTaxRate, '0.00')"/>
      </TotalTax>
      <Type>Stock</Type>
    </Item>
  </xsl:template>
  
  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime"/>

    <xsl:variable name="time" select="substring-after(substring-after(substring-after(substring-after($dateTime, ' '), ' '), ' '), ' ')"/>
    <xsl:variable name="date" select="substring-before($dateTime, concat(' ',$time))"/>
    
    <xsl:variable name="day" select="substring-before(substring-after($date, ' '), ' ')" />
    <xsl:variable name="monthStr" select="substring-before(substring-after(substring-after($date, ' '), ' '), ' ')" />
    <xsl:variable name="year" select="substring-after(substring-after(substring-after($date, ' '), ' '), ' ')" />

    <!-- Convert the month to a number -->
    <xsl:choose>
      <xsl:when test="$monthStr = 'January'">
        <xsl:value-of select="concat($year, '-01-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'February'">
        <xsl:value-of select="concat($year, '-02-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'March'">
        <xsl:value-of select="concat($year, '-03-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'April'">
        <xsl:value-of select="concat($year, '-04-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'May'">
        <xsl:value-of select="concat($year, '-05-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'June'">
        <xsl:value-of select="concat($year, '-06-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'July'">
        <xsl:value-of select="concat($year, '-07-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'August'">
        <xsl:value-of select="concat($year, '-08-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'September'">
        <xsl:value-of select="concat($year, '-09-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'October'">
        <xsl:value-of select="concat($year, '-10-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'November'">
        <xsl:value-of select="concat($year, '-11-', $day, 'T', $time)"/>
      </xsl:when>
      <xsl:when test="$monthStr = 'December'">
        <xsl:value-of select="concat($year, '-12-', $day, 'T', $time)"/>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>