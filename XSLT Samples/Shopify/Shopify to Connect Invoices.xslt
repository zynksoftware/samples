<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:msxsl="urn:schemas-microsoft-com:xslt"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:shopify="http://shopify.com/schema/order"
                exclude-result-prefixes="msxsl xsd xsi shopify">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>

  <xsl:template match="/">
    <Company>
      <Invoices>
        <xsl:for-each select="./rss/channel/item">
          <xsl:call-template name="Invoice"/>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="shopify:order_id"/>
      </Id>
      <InvoiceNumber>
        <xsl:value-of select="substring-after(./title, '#')"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="substring-after(./title, '#')"/>
      </CustomerOrderNumber>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <Notes1>
        <xsl:value-of select="./shopify:note"/>
      </Notes1>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="substring-before(substring-after(./shopify:created_at, ', '), ' -')"/>
        </xsl:call-template>
      </InvoiceDate>
      <TakenBy>Shopify</TakenBy>

      <InvoiceAddress>
        <Forename>
          <xsl:value-of select="./shopify:billing_address/shopify:first_name"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./shopify:billing_address/shopify:last_name"/>
        </Surname>
        <Company>
          <xsl:value-of select="./shopify:billing_address/shopify:company"/>
        </Company>
        <Address1>
          <xsl:value-of select="./shopify:billing_address/shopify:address1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./shopify:billing_address/shopify:address2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./shopify:billing_address/shopify:city"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./shopify:billing_address/shopify:zip"/>
        </Postcode>
        <County>
          <xsl:value-of select="./shopify:billing_address/shopify:province"/>
        </County>
        <Country>
          <xsl:value-of select="./shopify:billing_address/shopify:country"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./shopify:billing_address/shopify:phone"/>
        </Telephone>
        <Email>
          <xsl:value-of select="./shopify:email"/>
        </Email>
      </InvoiceAddress>

      <InvoiceDeliveryAddress>
        <Forename>
          <xsl:value-of select="./shopify:shipping_address/shopify:first_name"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./shopify:shipping_address/shopify:last_name"/>
        </Surname>
        <Company>
          <xsl:value-of select="./shopify:shipping_address/shopify:company"/>
        </Company>
        <Address1>
          <xsl:value-of select="./shopify:shipping_address/shopify:address1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./shopify:shipping_address/shopify:address2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./shopify:shipping_address/shopify:city"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./shopify:shipping_address/shopify:zip"/>
        </Postcode>
        <County>
          <xsl:value-of select="./shopify:shipping_address/shopify:province"/>
        </County>
        <Country>
          <xsl:value-of select="./shopify:shipping_address/shopify:country"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./shopify:shipping_address/shopify:phone"/>
        </Telephone>
        <Email>
          <xsl:value-of select="./shopify:email"/>
        </Email>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <!-- List each item -->
        <xsl:for-each select="shopify:line_items/shopify:line_item">
          <xsl:call-template name="InvoiceItem"/>
        </xsl:for-each>

        <!-- Get the total for each item -->
        <xsl:variable name="itemTotals">
          <totals>
            <xsl:for-each select="./shopify:line_items/shopify:line_item">
              <total>
                <xsl:value-of select="./shopify:price * shopify:quantity"/>
              </total>
            </xsl:for-each>
          </totals>
        </xsl:variable>
        <xsl:variable name="itemTotalNodes" select="msxsl:node-set($itemTotals)"/>
                
        <!-- Check if there has been a discount-->
        <xsl:if test="(sum($itemTotalNodes/totals/total) + ./shopify:shipping_price) > ./shopify:total_price">
          <xsl:call-template name="DiscountItem">
            <xsl:with-param name="discount" select="./shopify:total_price - sum($itemTotalNodes/totals/total) - ./shopify:shipping_price"/>
          </xsl:call-template>
        </xsl:if>
      </InvoiceItems>

      <Carriage>
        <Name>
          <xsl:value-of select="./shopify:shipping_lines/shopify:shipping_line/shopify:shipping_title"/>
        </Name>
        <QtyOrdered>1</QtyOrdered>
        <xsl:variable name="totalNet" select="./shippingAmount div (./shippingTaxRate + 1)"/>
        <UnitPrice>
          <xsl:value-of select="./shopify:shipping_price"/>
        </UnitPrice>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentAmount>
        <xsl:value-of select="./shopify:total_price"/>
      </PaymentAmount>
      <xsl:choose>
        <xsl:when test="./shopify:financial_status = 'paid'">
          <PaidFlag>1</PaidFlag>
        </xsl:when>
        <xsl:otherwise>
          <PaidFlag>0</PaidFlag>
        </xsl:otherwise>
      </xsl:choose>

    </Invoice>
  </xsl:template>

  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <Item>
      <Sku>
        <xsl:value-of select="./shopify:sku"/>
      </Sku>
      <Name>
        <xsl:value-of select="./shopify:line_title"/>
      </Name>
      <Description>
        <xsl:value-of select="./shopify:variant_title"/>
      </Description>
      <QtyOrdered>
        <xsl:value-of select="./shopify:quantity"/>
      </QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="./shopify:price"/>
      </UnitPrice>
      <Type>Stock</Type>
    </Item>
  </xsl:template>

  <!-- Discount Codes -->
  <xsl:template name="DiscountItem">
    <xsl:param name="discount" />
    <Item>
      <Name>Discount Code(s)</Name>
      <QtyOrdered>1</QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="format-number($discount,'0.00')"/>
      </UnitPrice>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>
  
  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime" />

    <xsl:variable name ="day" select="substring-before($dateTime, ' ')"/>
    <xsl:variable name ="monthStr" select="substring-before(substring-after($dateTime, ' '), ' ')"/>
    <xsl:variable name ="year" select="substring-before(substring-after(substring-after($dateTime, ' '), ' '), ' ')"/>

    <xsl:variable name ="time" select="substring-after(substring-after(substring-after($dateTime, ' '), ' '), ' ')"/>
    
    <xsl:choose>
      <xsl:when test="$monthStr = 'Jan'">
        <xsl:value-of select="concat($year, '-01-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Feb'">
        <xsl:value-of select="concat($year, '-02-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Mar'">
        <xsl:value-of select="concat($year, '-03-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Apr'">
        <xsl:value-of select="concat($year, '-04-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'May'">
        <xsl:value-of select="concat($year, '-05-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Jun'">
        <xsl:value-of select="concat($year, '-06-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Jul'">
        <xsl:value-of select="concat($year, '-07-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Aug'">
        <xsl:value-of select="concat($year, '-08-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Sep'">
        <xsl:value-of select="concat($year, '-09-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Oct'">
        <xsl:value-of select="concat($year, '-10-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Nov'">
        <xsl:value-of select="concat($year, '-11-', $day, 'T', $time)" />
      </xsl:when>
      <xsl:when test="$monthStr = 'Dec'">
        <xsl:value-of select="concat($year, '-12-', $day, 'T', $time)" />
      </xsl:when>
    </xsl:choose>
    
  </xsl:template>

</xsl:stylesheet>
