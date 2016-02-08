<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
 <xsl:output omit-xml-declaration="no" indent="yes"/>
  
  <xsl:key name="OrderId" match="Company/Row" use="ORDERID"/>
  
  <xsl:template match="/">
	<Company>
		<Invoices>
		  <xsl:for-each select="Company/Row[generate-id() = generate-id(key('OrderId', ORDERID)[1])]">
		    <xsl:call-template name="Invoice"/>
		  </xsl:for-each>
		</Invoices>
	</Company>
  </xsl:template>
  
  <xsl:template name="Invoice">
  
	<Invoice>
		<Id><xsl:value-of select="ORDERID"/></Id>
		<CustomerId><xsl:value-of select="ORDERID"/></CustomerId>
		<InvoiceNumber><xsl:value-of select="ORDERID"/></InvoiceNumber>
		<CustomerOrderNumber><xsl:value-of select="ORDERID"/></CustomerOrderNumber>
		<xsl:call-template name="InvoiceDate"/>
		<xsl:call-template name="AccountRef"/>
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
		<PaymentMethod><xsl:value-of select="PAYMENTMETHOD"/></PaymentMethod>
	</Invoice>
	
  </xsl:template>
  
  <xsl:template name="InvoiceAddress">
  
	<Title><xsl:value-of select="substring-before(CUSTOMERNAME, ' ')"/></Title>
	<xsl:call-template name="ForenameSurname"/>
	<Address1><xsl:value-of select="SHIPPINGADDRESS1"/></Address1>
	<Town><xsl:value-of select="SHIPPINGTOWNCITY"/></Town>
	<Country><xsl:value-of select="SHIPPINGCOUNTRY"/></Country>
	<Postcode><xsl:value-of select="SHIPPINGPOSTCODE"/></Postcode>
	<Email><xsl:value-of select="CUSTOMEREMAIL"/></Email>
	<Telephone><xsl:value-of select="CUSTOMERTELEPHONE"/></Telephone>
  
  </xsl:template>
  
  <xsl:template name="InvoiceDeliveryAddress">
  
	<xsl:call-template name="ForenameSurnameDeliveryAddress"/>
	<Address1><xsl:value-of select="BILLINGADDRESS1"/></Address1>
	<Town><xsl:value-of select="BILLINGTOWNCITY"/></Town>
	<Country><xsl:value-of select="BILLINGCOUNTRY"/></Country>
	<Postcode><xsl:value-of select="BILLINGPOSTCODE"/></Postcode>
	<Email><xsl:value-of select="CUSTOMEREMAIL"/></Email>
	<Telephone><xsl:value-of select="CUSTOMERTELEPHONE"/></Telephone>

   </xsl:template>
   
  <xsl:template name="ItemLine">
  
	<!-- There is no tax information provided in the XSLT document, if you leave it like this Sage will calculate tax the way it is currently configured to do so -->
  
	<!-- Makes each item unique to that sales order based on ORDERID -->
  
	  <xsl:variable name="id">
		<xsl:value-of select="ORDERID"/>
	  </xsl:variable>
	
	  <xsl:for-each select="../Row[ORDERID=$id]">	
		<Item> 
			<Sku><xsl:value-of select="SKU"/></Sku>
			<Name><xsl:value-of select="substring(ITEMDESCRIPTION, 1, 60)"/></Name>
			<Description><xsl:value-of select="substring(ITEMDESCRIPTION, 1, 60)"/></Description>
			<UnitPrice><xsl:value-of select="ITEMPRICE"/></UnitPrice>
			<QtyOrdered><xsl:value-of select="QUANTITY"/></QtyOrdered>
		</Item>
	  </xsl:for-each>
	
  </xsl:template>
  
  <xsl:template name="Carriage">
  
	<!-- May want to add tax information here if you apply VAT on Carriage -->
	
	<UnitPrice><xsl:value-of select="format-number(CARRIAGEAMOUNT, '0.00')"/></UnitPrice>
	<QtyOrdered>1</QtyOrdered>
  
  </xsl:template>
  
  <xsl:template name="ForenameSurname">
  
	<!-- Formats Customer Address name -->
	
	<xsl:variable name="Name" select="CUSTOMERNAME"/>
	<xsl:variable name="NoTitleName" select="substring-after($Name, ' ')"/>
	<xsl:variable name="Forename" select="substring-before($NoTitleName, ' ')"/>
	<xsl:variable name="Surname" select="substring-after($NoTitleName, ' ')"/>
	
	<Forename><xsl:value-of select="$Forename"/></Forename>
	<Surname><xsl:value-of select="$Surname"/></Surname>
	
  </xsl:template>
  
  <xsl:template name="ForenameSurnameDeliveryAddress">
  
	<!-- Formats Delivery Address name -->
  
	<xsl:variable name="Name" select="BILLINGNAME"/>
	<xsl:variable name="Forename" select="substring-before(BILLINGNAME, ' ')"/>
	<xsl:variable name="Surname" select="substring-after(BILLINGNAME, ' ')"/>
	
	<Forename><xsl:value-of select="$Forename"/></Forename>
	<Surname><xsl:value-of select="$Surname"/></Surname>
  
  </xsl:template>
  
  <xsl:template name="AccountRef">
	
	<xsl:variable name="Name" select="CUSTOMERNAME"/>
	<xsl:variable name="NoTitleName" select="substring-after($Name, ' ')"/>
	<xsl:variable name="Forename" select="substring-before($NoTitleName, ' ')"/>
	<xsl:variable name="Surname" select="substring-after($NoTitleName, ' ')"/>
	
	<AccountReference><xsl:value-of select="concat(substring($Forename, 1, 4), '001')"/></AccountReference>
	
  </xsl:template>
  
  <xsl:template name="InvoiceDate">
	
	<xsl:variable name="Date" select="ORDERDATE"/>
	<xsl:variable name="Month" select="substring-before($Date, '/')"/>
	<xsl:variable name="DayetYear" select="substring-after($Date, '/')"/>
	<xsl:variable name="Day" select="substring-before($DayetYear, '/')"/>
	<xsl:variable name="Year" select="substring-after($DayetYear, '/')"/>
	
	<InvoiceDate><xsl:value-of select="concat($Year,'-',$Month,'-',$Day,'T','00:00:00')"/></InvoiceDate>
  
  </xsl:template>
	
</xsl:stylesheet>