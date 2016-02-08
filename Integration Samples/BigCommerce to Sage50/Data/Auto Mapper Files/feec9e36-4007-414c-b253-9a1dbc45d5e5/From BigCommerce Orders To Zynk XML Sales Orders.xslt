<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:msxsl="urn:schemas-microsoft-com:xslt" exclude-result-prefixes="msxsl"
>
    <xsl:output method="xml" indent="yes"/>

	<xsl:param name="WebSales">false</xsl:param> <!-- All sales to a single account { true, false } -->
	<xsl:param name="AccountReference">WEB</xsl:param> <!-- Web sales account reference -->
	<xsl:param name="BankAccount">1200</xsl:param> <!-- Bank account reference -->

    <xsl:template match="/">
		<Company>
			<SalesOrders>
				<xsl:for-each select="orders/order">
					<xsl:call-template name="SalesOrder" />	
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>
	
	<xsl:template name="SalesOrder">
		<SalesOrder>
		    <Id><xsl:value-of select="id"/></Id>
		    <CustomerId><xsl:value-of select="customer_id"/></CustomerId>
			<SalesOrderNumber><xsl:value-of select="id"/></SalesOrderNumber>
			<CustomerOrderNumber><xsl:value-of select="id"/></CustomerOrderNumber>
			<Notes1><xsl:value-of select="customer_message"/></Notes1>
			<Notes2><xsl:value-of select="staff_notes"/></Notes2>
			<Currency><xsl:value-of select="currency_code"/></Currency>
			
			<AccountReference>
				<xsl:if test="$WebSales = 'true' or customer_id = 0">
					<xsl:value-of select="$AccountReference"/>
				</xsl:if>
			</AccountReference>
			
			<xsl:choose>
  				<xsl:when test="currency_code = 'GBP'">
    				<CurrencyUsed>false</CurrencyUsed>
  				</xsl:when>
  				<xsl:otherwise>
    				<CurrencyUsed>true</CurrencyUsed>
  				</xsl:otherwise>
			</xsl:choose> 
			<SalesOrderDate>
				<xsl:call-template name="formatDate">
          			<xsl:with-param name="dateTimeStr" select="date_created" />
        		</xsl:call-template>
			</SalesOrderDate>
			
			<SalesOrderAddress>
				<Forename><xsl:value-of select="billing_address/first_name"/></Forename>
        		<Surname><xsl:value-of select="billing_address/last_name"/></Surname>

				<xsl:if test="billing_address/company != ''">
					<Company><xsl:value-of select="billing_address/company"/></Company>
				</xsl:if>

        		<Address1><xsl:value-of select="billing_address/street_1"/></Address1>
        		<Address2><xsl:value-of select="billing_address/street_2"/></Address2>
       			<Town><xsl:value-of select="billing_address/city"/></Town>
        		<Postcode><xsl:value-of select="billing_address/zip"/></Postcode>
        		<County><xsl:value-of select="billing_address/state"/></County>
        		<Country><xsl:value-of select="billing_address/country"/></Country>
        		<Telephone><xsl:value-of select="billing_address/phone"/></Telephone>
				<Email><xsl:value-of select="billing_address/email"/></Email>
			</SalesOrderAddress>
			
			<SalesOrderDeliveryAddress>
				<Forename><xsl:value-of select="shipping_addresses/address[1]/first_name"/></Forename>
        		<Surname><xsl:value-of select="shipping_addresses/address[1]/last_name"/></Surname>
				
				<xsl:if test="shipping_addresses/address[1]/company != ''">
					<Company><xsl:value-of select="shipping_addresses/address[1]/company"/></Company>
				</xsl:if>

        		<Address1><xsl:value-of select="shipping_addresses/address[1]/street_1"/></Address1>
        		<Address2><xsl:value-of select="shipping_addresses/address[1]/street_2"/></Address2>
       			<Town><xsl:value-of select="shipping_addresses/address[1]/city"/></Town>
        		<Postcode><xsl:value-of select="shipping_addresses/address[1]/zip"/></Postcode>
        		<County><xsl:value-of select="shipping_addresses/address[1]/state"/></County>
        		<Country><xsl:value-of select="shipping_addresses/address[1]/country"/></Country>
        		<Telephone><xsl:value-of select="shipping_addresses/address[1]/phone"/></Telephone>
				<Email><xsl:value-of select="shipping_addresses/address[1]/email"/></Email>
			</SalesOrderDeliveryAddress>
			
			<SalesOrderItems>
				<xsl:for-each select="products/product">
					<xsl:call-template name="OrderItem"/>
				</xsl:for-each>
			</SalesOrderItems>
			
			<Carriage>
				<Sku><xsl:value-of select="sku"/></Sku>
    	  		<Name>Shipping</Name>
    	  		<QtyOrdered>1</QtyOrdered>
    	  		<UnitPrice><xsl:value-of select="shipping_cost_ex_tax"/></UnitPrice>
    	  		<TotalNet><xsl:value-of select="shipping_cost_ex_tax"/></TotalNet>
				<TotalTax><xsl:value-of select="shipping_cost_tax"/></TotalTax>
				<TaxCode>
					<xsl:choose>
						<xsl:when test="shipping_cost_tax &gt; 0">1</xsl:when>
						<xsl:otherwise>0</xsl:otherwise>
					</xsl:choose>
				</TaxCode>
			</Carriage>
			
			<TakenBy>BigCommerce</TakenBy>
			<PaymentAmount><xsl:value-of select="total_inc_tax"/></PaymentAmount>
			<BankAccount><xsl:value-of select="$BankAccount"/></BankAccount>
			<PaymentReference><xsl:value-of select="payment_method"/></PaymentReference>
		</SalesOrder>
	</xsl:template>
	
	<!--Order items -->
	<xsl:template name="OrderItem">
   		<Item>
   	   		<Id><xsl:value-of select="id"/></Id>
			<Sku><xsl:value-of select="sku"/></Sku>
    	  	<Name><xsl:value-of select="name"/></Name>
    	  	<QtyOrdered><xsl:value-of select="quantity"/></QtyOrdered>
    	  	<UnitPrice><xsl:value-of select="price_ex_tax"/></UnitPrice>
			<UnitDiscountAmount><xsl:value-of select="sum(applied_discounts/discount/amount) div quantity"/></UnitDiscountAmount>
			<QtyDispatched><xsl:value-of select="quantity_shipped"/></QtyDispatched>
			<TaxCode>
				<xsl:choose>
					<xsl:when test="price_tax &gt; 0">1</xsl:when>
					<xsl:otherwise>0</xsl:otherwise>
				</xsl:choose>
			</TaxCode>
    	</Item>
  	</xsl:template>
	
	<!-- Converts a date to the correct format -->
	<xsl:template name="formatDate">
		<xsl:param name="dateTimeStr" />
    	<xsl:variable name="dateTime" select="substring-after(substring-before($dateTimeStr, ' +'), ', ')" />
		<xsl:variable name="date" select="substring($dateTime,0,12)"/>
		<xsl:variable name="time" select="substring($dateTime,13)"/>
		
		<xsl:variable name="day" select="substring-before($date, ' ')"/>
		<xsl:variable name="monthStr" select="substring-before(substring-after($date, ' '), ' ')"/>
		<xsl:variable name="year" select="substring-after(substring-after($date, ' '), ' ')"/>
		
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
