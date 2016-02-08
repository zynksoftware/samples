<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
 <xsl:output omit-xml-declaration="no" indent="yes"/>
  
  <xsl:key name="OrderId" match="DocumentElement/Sheet1" use="ORDER_x0020_ID"/>
  
  <xsl:template match="/">
	<Company>
		<Invoices>
		  <xsl:for-each select="DocumentElement/Sheet1[generate-id() = generate-id(key('OrderId', ORDER_x0020_ID)[1])]">
		    <xsl:call-template name="Invoice"/>
		  </xsl:for-each>
		</Invoices>
	</Company>
  </xsl:template>
  
  <xsl:template name="Invoice">
	<Invoice>
		<Id><xsl:value-of select="ORDER_x0020_ID"/></Id>
		<CustomerId><xsl:value-of select="ORDER_x0020_ID"/></CustomerId>
		<InvoiceNumber><xsl:value-of select="ORDER_x0020_ID"/></InvoiceNumber>
		<CustomerOrderNumber><xsl:value-of select="ORDER_x0020_ID"/></CustomerOrderNumber>
		<InvoiceDate><xsl:value-of select="substring-before(ORDER_x0020_DATE, '+')"/></InvoiceDate>
		<InvoiceAddress>
			<xsl:call-template name="InvoiceAddress"/>
		</InvoiceAddress>
		<InvoiceDeliveryAddress>
			<xsl:call-template name="InvoiceDeliveryAddress"/>
		</InvoiceDeliveryAddress>
		<InvoiceItems>
			<xsl:call-template name="ItemLine"/>
		</InvoiceItems>
		<Carriage>
			<xsl:call-template name="Carriage"/>
		</Carriage>
		<PaymentMethod><xsl:value-of select="PAYMENT_x0020_METHOD"/></PaymentMethod>
	</Invoice>
	
  </xsl:template>
  
  <xsl:template name="InvoiceAddress">
  
	<Title><xsl:value-of select="substring-before(CUSTOMER_x0020_NAME, ' ')"/></Title>
	<xsl:call-template name="ForenameSurname"/>
	<Address1><xsl:value-of select="SHIPPING_x0020_ADDRESS_x0020_1"/></Address1>
	<Town><xsl:value-of select="SHIPPING_x0020_TOWN_x0020_CITY"/></Town>
	<Country><xsl:value-of select="SHIPPING_x0020_COUNTRY"/></Country>
	<Postcode><xsl:value-of select="SHIPPING_x0020_POSTCODE"/></Postcode>
	<Email><xsl:value-of select="CUSTOMER_x0020_EMAIL"/></Email>
	<Telephone><xsl:value-of select="CUSTOMER_x0020_TELEPHONE"/></Telephone>
  
  </xsl:template>
  
  <xsl:template name="InvoiceDeliveryAddress">
  
	<xsl:call-template name="ForenameSurnameDeliveryAddress"/>
	<Address1><xsl:value-of select="BILLING_x0020_ADDRESS_x0020_1"/></Address1>
	<Town><xsl:value-of select="BILLING_x0020_TOWN_x0020_CITY"/></Town>
	<Country><xsl:value-of select="BILLING_x0020_COUNTRY"/></Country>
	<Postcode><xsl:value-of select="BILLING_x0020_POSTCODE"/></Postcode>
	<Email><xsl:value-of select="CUSTOMER_x0020_EMAIL"/></Email>
	<Telephone><xsl:value-of select="CUSTOMER_x0020_TELEPHONE"/></Telephone>

   </xsl:template>
   
  <xsl:template name="ItemLine">
  
	<!-- There is no tax information provided in the XSLT document, if you leave it like this Sage will calculate tax the way it is currently configured to do so -->
  
	<!-- Makes each item unique to that sales order based on ORDER_x0020_ID -->
  
	  <xsl:variable name="id">
		<xsl:value-of select="ORDER_x0020_ID"/>
	  </xsl:variable>
	
	  <xsl:for-each select="../Sheet1[ORDER_x0020_ID=$id]">	
		<Item> 
			<Sku><xsl:value-of select="SKU"/></Sku>
			<Name><xsl:value-of select="substring(ITEM_x0020_DESCRIPTION, 1, 60)"/></Name>
			<Description><xsl:value-of select="substring(ITEM_x0020_DESCRIPTION, 1, 60)"/></Description>
			<UnitPrice><xsl:value-of select="ITEM_x0020_PRICE"/></UnitPrice>
			<QtyOrdered><xsl:value-of select="QUANTITY"/></QtyOrdered>
		</Item>
	  </xsl:for-each>
	
  </xsl:template>
  
  <xsl:template name="Carriage">
  
	<!-- May want to add tax information here if you apply VAT on Carriage -->
	
	<UnitPrice><xsl:value-of select="format-number(CARRIAGE_x0020_AMOUNT, '0.00')"/></UnitPrice>
	<QtyOrdered>1</QtyOrdered>
  
  </xsl:template>
  
  <xsl:template name="ForenameSurname">
  
	<!-- Formats Customer Address name -->
	
	<xsl:variable name="Name" select="CUSTOMER_x0020_NAME"/>
	<xsl:variable name="NoTitleName" select="substring-after($Name, ' ')"/>
	<xsl:variable name="Forename" select="substring-before($NoTitleName, ' ')"/>
	<xsl:variable name="Surname" select="substring-after($NoTitleName, ' ')"/>
	
	<Forename><xsl:value-of select="$Forename"/></Forename>
	<Surname><xsl:value-of select="$Surname"/></Surname>
	
  </xsl:template>
  
  <xsl:template name="ForenameSurnameDeliveryAddress">
  
	<!-- Formats Delivery Address name -->
  
	<xsl:variable name="Name" select="BILLING_x0020_NAME"/>
	<xsl:variable name="Forename" select="substring-before(BILLING_x0020_NAME, ' ')"/>
	<xsl:variable name="Surname" select="substring-after(BILLING_x0020_NAME, ' ')"/>
	
	<Forename><xsl:value-of select="$Forename"/></Forename>
	<Surname><xsl:value-of select="$Surname"/></Surname>
  
  </xsl:template>
  
</xsl:stylesheet>