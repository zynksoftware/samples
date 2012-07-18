<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet 
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:b="urn:ebay:apis:eBLBaseComponents">
	
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:param name="AccountReference" />
	<xsl:param name="TaxCode"/>
	<xsl:param name="NominalCode"/>
	<xsl:param name="CarriageCode"/>
	<xsl:param name="ProductCode"/>
	<xsl:param name="TakenBy"/>
    
	<xsl:template match="/">
		<Company>
			<Invoices>
				<xsl:for-each select="Orders/Order">
					<xsl:call-template name="Invoice" />	
				</xsl:for-each>
			</Invoices>
		</Company>
	</xsl:template>
  
	<xsl:template name="Invoice">
		<Invoice>
		  <Id><xsl:value-of select="./@AmazonOrderId"/></Id>
		  <CustomerId><xsl:value-of select="./@AmazonOrderId"/></CustomerId>
		  <InvoiceNumber><xsl:value-of select="./@AmazonOrderId"/></InvoiceNumber>
		  <CustomerOrderNumber>EB<xsl:value-of select="./@AmazonOrderId"/></CustomerOrderNumber>
      <Currency><xsl:value-of select="OrderTotal/@CurrencyCode"/></Currency>
      <AccountReference><xsl:value-of select="$AccountReference"/></AccountReference>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="./@PurchaseDate" />
        </xsl:call-template>
        <!--<xsl:value-of select="./@PurchaseDate"/>-->
      </InvoiceDate>
      <TakenBy><xsl:value-of select="$TakenBy"/></TakenBy>
      
      <xsl:choose>
        <xsl:when test="OrderTotal/@CurrencyCode='GBP'">
          <CurrencyUsed>false</CurrencyUsed>
        </xsl:when>
        <xsl:otherwise>
          <CurrencyUsed>true</CurrencyUsed>
        </xsl:otherwise>
      </xsl:choose>

      <InvoiceAddress>
        <Forename><xsl:value-of select="ShippingAddress/@Name"/></Forename>
        <Company><xsl:value-of select="ShippingAddress/@Name"/></Company>
        <Address1><xsl:value-of select="ShippingAddress/@AddressLine1"/></Address1>
        <Address2><xsl:value-of select="ShippingAddress/@AddressLine2"/></Address2>
        <Address3><xsl:value-of select="ShippingAddress/@AddressLine3"/></Address3>
        <Town><xsl:value-of select="ShippingAddress/@City"/></Town>
        <Postcode><xsl:value-of select="ShippingAddress/@PostalCode"/></Postcode>
        <County><xsl:value-of select="ShippingAddress/@StateOrRegion"/></County>
        <Country><xsl:value-of select="ShippingAddress/@CountryCode"/></Country>
        <Telephone><xsl:value-of select="ShippingAddress/@Phone"/></Telephone>
      </InvoiceAddress>

      <InvoiceDeliveryAddress>
        <Forename><xsl:value-of select="ShippingAddress/@Name"/></Forename>
        <Company><xsl:value-of select="ShippingAddress/@Name"/></Company>
        <Address1><xsl:value-of select="ShippingAddress/@AddressLine1"/></Address1>
        <Address2><xsl:value-of select="ShippingAddress/@AddressLine2"/></Address2>
        <Address3><xsl:value-of select="ShippingAddress/@AddressLine3"/></Address3>
        <Town><xsl:value-of select="ShippingAddress/@City"/></Town>
        <Postcode><xsl:value-of select="ShippingAddress/@PostalCode"/></Postcode>
        <County><xsl:value-of select="ShippingAddress/@StateOrRegion"/></County>
        <Country><xsl:value-of select="ShippingAddress/@CountryCode"/></Country>
        <Telephone><xsl:value-of select="ShippingAddress/@Phone"/></Telephone>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:for-each select="OrderItems/OrderItem">
          <xsl:call-template name="InvoiceItem" />
        </xsl:for-each>
      </InvoiceItems>

      <!--<Carriage>
        <Name><xsl:value-of select="b:ShippingServiceSelected/b:ShippingService"/></Name>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice><xsl:value-of select="b:ShippingServiceSelected/b:ShippingServiceCost"/></UnitPrice>
        <TaxCode><xsl:value-of select="$TaxCode"/></TaxCode>
        <NominalCode><xsl:value-of select="$CarriageCode"/></NominalCode>
      </Carriage>-->
      
    </Invoice>
	</xsl:template>

  <xsl:template name="InvoiceItem">
    <Item>
      <Sku><xsl:value-of select="./@SellerSKU"/></Sku>
      <Name><xsl:value-of select="./@Title"/></Name>
      <QtyOrdered><xsl:value-of select="./@QuantityOrdered"/></QtyOrdered>
      <UnitPrice><xsl:value-of select="ItemPrice/@Amount"/></UnitPrice>
      <TaxCode><xsl:value-of select="$TaxCode"/></TaxCode>
      <NominalCode><xsl:value-of select="$NominalCode"/></NominalCode>
    </Item>
  </xsl:template>

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