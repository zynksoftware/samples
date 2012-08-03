<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>
  <xsl:param name="ShippingTaxRate" select="0.0"/> <!-- Default to 0 -->

  <xsl:template match="/">
    <Company>
      <Invoices>
        <xsl:for-each select="/root/row">
          <!-- Check if the node is the start of an invoice -->
          <xsl:if test="./increment_id != ''">
            <xsl:call-template name="Invoice"/>
          </xsl:if>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="./increment_id"/>
      </Id>
      <InvoiceNumber>
        <xsl:value-of select="./increment_id"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="./increment_id"/>
      </CustomerOrderNumber>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="./created_at"/>
        </xsl:call-template>
      </InvoiceDate>
      <TakenBy>Magento Go</TakenBy>

      <xsl:call-template name="Address"/>

      <InvoiceItems>
        <xsl:call-template name="InvoiceItem">
          <xsl:with-param name="rowNode" select="."/>
          <xsl:with-param name="nodePosition" select="count(./preceding-sibling::*) + 1"/>
        </xsl:call-template>
        
        <xsl:if test="./discount_amount &lt; 0">
          <xsl:call-template name="CouponItem"/>
        </xsl:if>

        <xsl:call-template name="GiftCardItem">
            <xsl:with-param name="giftCards" select="./gift_cards"/>
        </xsl:call-template>
      </InvoiceItems>

      <Carriage>
        <Name>
          <xsl:value-of select="./shipping_description"/>
        </Name>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="format-number(./shipping_amount, '0.00')"/>
        </UnitPrice>
        <TaxRate>
          <xsl:value-of select="$ShippingTaxRate"/>
        </TaxRate>
        <TotalNet>
          <xsl:value-of select="format-number(./shipping_amount, '0.00')"/>
        </TotalNet>
        <TotalTax>
          <xsl:value-of select="format-number(./shipping_amount * $ShippingTaxRate, '0.00')"/>
        </TotalTax>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentAmount>
        <xsl:value-of select="./grand_total"/>
      </PaymentAmount>

    </Invoice>
  </xsl:template>

  <!-- Addresses -->
  <xsl:template name="Address">
    <!-- Check which address is at this node, then look for the other address at the next node -->
    <xsl:choose>
      <xsl:when test="./_order_address_address_type = 'billing'">
        <InvoiceAddress>
          <xsl:call-template name="AddressDetails">
            <xsl:with-param name="rowNode" select="."/>
          </xsl:call-template>
        </InvoiceAddress>
        <InvoiceDeliveryAddress>
          <xsl:variable name="nextNodeNumber" select="count(./preceding-sibling::*) + 2"/>
          <xsl:call-template name="AddressDetails">
            <xsl:with-param name="rowNode" select="/root/row[$nextNodeNumber]"/>
          </xsl:call-template>
        </InvoiceDeliveryAddress>
      </xsl:when>
      <xsl:when test="./_order_address_address_type = 'shipping'">
        <InvoiceAddress>
          <xsl:variable name="nextNodeNumber" select="count(./preceding-sibling::*) + 2"/>
          <xsl:call-template name="AddressDetails">
            <xsl:with-param name="rowNode" select="/root/row[$nextNodeNumber]"/>
          </xsl:call-template>
        </InvoiceAddress>
        <InvoiceDeliveryAddress>
          <xsl:call-template name="AddressDetails">
            <xsl:with-param name="rowNode" select="."/>
          </xsl:call-template>
        </InvoiceDeliveryAddress>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
  
  <!-- Address Details -->
  <xsl:template name="AddressDetails">
    <xsl:param name="rowNode"/>
    <Title>
      <xsl:value-of select="$rowNode/_order_address_prefix"/>
    </Title>
    <Forename>
      <xsl:choose>
        <xsl:when test="$rowNode/_order_address_middlename != ''">
          <xsl:value-of select="concat($rowNode/_order_address_firstname, ' ' , $rowNode/_order_address_middlename)"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$rowNode/_order_address_firstname"/>
        </xsl:otherwise>
      </xsl:choose>
    </Forename>
    <Surname>
      <xsl:value-of select="$rowNode/_order_address_lastname"/>
    </Surname>
    <Company>
      <xsl:value-of select="$rowNode/_order_address_company"/>
    </Company>
    <xsl:call-template name="addressLines">
      <xsl:with-param name="street" select="$rowNode/_order_address_street"/>
    </xsl:call-template>
    <Town>
      <xsl:value-of select="$rowNode/_order_address_city"/>
    </Town>
    <Postcode>
      <xsl:value-of select="$rowNode/_order_address_postcode"/>
    </Postcode>
    <County>
      <xsl:value-of select="$rowNode/_order_address_region"/>
    </County>
    <Telephone>
      <xsl:value-of select="$rowNode/_order_address_telephone"/>
    </Telephone>
    <Fax>
      <xsl:value-of select="$rowNode/_order_address_fax"/>
    </Fax>
    <Email>
      <xsl:value-of select="$rowNode/_order_address_email"/>
    </Email>
  </xsl:template>

  <!-- Address Lines transform -->
  <xsl:template name="addressLines">
    <xsl:param name="street" />
    <Address1>
      <xsl:value-of select="substring-before($street, '&#10;')"/>
    </Address1>
    <Address2>
      <xsl:value-of select="substring-after($street, '&#10;')"/>
    </Address2>
  </xsl:template>

  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <xsl:param name="rowNode"/>
    <xsl:param name="nodePosition"/>
    <Item>
      <Sku>
        <xsl:value-of select="$rowNode/_order_item_sku"/>
      </Sku>
      <Name>
        <xsl:value-of select="$rowNode/_order_item_name"/>
      </Name>
      <QtyOrdered>
        <xsl:value-of select="$rowNode/_order_item_qty_ordered"/>
      </QtyOrdered>
      <QtyDespatched>
        <xsl:value-of select="$rowNode/_order_item_qty_shipped"/>
      </QtyDespatched>
      <UnitPrice>
        <xsl:value-of select="format-number($rowNode/_order_item_price, '0.00')"/>
      </UnitPrice>
      <UnitDiscountAmount>
        <xsl:value-of select="format-number($rowNode/_order_item_discount_amount div $rowNode/_order_item_qty_ordered, '0.00')"/>
      </UnitDiscountAmount>
      <TaxRate>
        <xsl:value-of select="format-number($rowNode/_order_item_tax_percent div 100, '0.0000')"/>
      </TaxRate>
      <TotalNet>
        <xsl:value-of select="format-number($rowNode/_order_item_row_total, '0.00')"/>
      </TotalNet>
      <TotalTax>
        <xsl:value-of select="format-number($rowNode/_order_item_tax_amount, '0.00')"/>
      </TotalTax>
      <Type>Stock</Type>
    </Item>
    
    <!-- Test if there are more items for this invoice-->
    <xsl:variable name="nextNodePosition" select="$nodePosition + 1"/>
    <xsl:if test="/root/row[$nextNodePosition]/increment_id = '' and /root/row[$nextNodePosition]/_order_item_price != ''">
      <xsl:call-template name="InvoiceItem">
        <xsl:with-param name="rowNode" select="/root/row[$nextNodePosition]"/>
        <xsl:with-param name="nodePosition" select="$nodePosition + 1"/>
      </xsl:call-template>
    </xsl:if>
    
  </xsl:template>

  <!-- Discount Codes -->
  <xsl:template name="CouponItem">
    <Item>
      <Name>Coupon Code</Name>
      <QtyOrdered>1</QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="format-number(./discount_amount, '0.00')"/>
      </UnitPrice>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>

  <!-- Gift Cards -->
  <xsl:template name="GiftCardItem">
    <xsl:param name="giftCards"/>
    
    <xsl:if test="$giftCards != ''">
      <Item>
        <Id>
          <xsl:value-of select="substring-before($giftCards, ', ')"/>
        </Id>
        <Name>Gift Card</Name>
        <Sku>
          <xsl:value-of select="substring-before(substring-after($giftCards, ', '), ', ')"/>
        </Sku>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:text>-</xsl:text><xsl:value-of select="substring-before(substring-after(substring-after($giftCards, ', '), ', '), ', ')"/>
        </UnitPrice>
        <Type>NonStock</Type>
      </Item>

      <!-- Check for more gift cards -->
      <xsl:if test="contains($giftCards, '&#10;')">
        <xsl:call-template name="GiftCardItem">
          <xsl:with-param name="giftCards" select="substring-after($giftCards, '&#10;')"/>
        </xsl:call-template>
      </xsl:if>
    </xsl:if>
    
  </xsl:template>

  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime"/>

    <xsl:variable name="date" select="substring-before($dateTime, ' ')"/>
    <xsl:variable name="day" select="substring-before($date, '/')"/>
    <xsl:variable name="month" select="substring-before(substring-after($date, '/'), '/')"/>
    <xsl:variable name="year" select="substring-after(substring-after($date, '/'), '/')"/>
    
    <xsl:variable name="time" select="concat(substring-after($dateTime, ' '), ':00')"/>

    <xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', $time)"/>
  </xsl:template>

</xsl:stylesheet>