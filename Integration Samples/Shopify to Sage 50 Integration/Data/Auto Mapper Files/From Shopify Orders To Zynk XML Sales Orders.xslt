<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet 
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes" />
	
	<xsl:param name="AccountReference" /> <!-- The account to add the orders to, leave blank if auto-creating customers -->
	<xsl:param name="BankAccount" /> <!-- The account to add payment to -->
	<xsl:param name="TaxCode">1</xsl:param> <!-- The tax code to use for order items, leave blank to pick up from Sage -->
	<xsl:param name="CarriageTaxCode">1</xsl:param>  <!-- The tax code to use for carriage, leave blank to pick up from Sage -->
  
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
			<AccountReference><xsl:value-of select="$AccountReference"/></AccountReference>
			<SalesOrderNumber><xsl:value-of select="name"/></SalesOrderNumber>
        	<CustomerOrderNumber><xsl:value-of select="name"/></CustomerOrderNumber>
        	<SalesOrderDate><xsl:value-of select="created-at"/></SalesOrderDate>

        	<SalesOrderAddress>
        		<Forename><xsl:value-of select="billing-address/first-name"/></Forename>
        		<Surname><xsl:value-of select="billing-address/last-name"/></Surname>
        		<Company><xsl:value-of select="billing-address/company"/></Company>
            	<Address1><xsl:value-of select="billing-address/address1"/></Address1>
            	<Address2><xsl:value-of select="billing-address/address2"/></Address2>
        		<Town><xsl:value-of select="billing-address/city"/></Town>
        		<Postcode><xsl:value-of select="billing-address/zip"/></Postcode>
        		<County><xsl:value-of select="billing-address/province"/></County>
        		<Country><xsl:value-of select="billing-address/country-code"/></Country>
        		<Telephone><xsl:value-of select="billing-address/phone"/></Telephone>
		    	<Email><xsl:value-of select="customer/email"/></Email>
			</SalesOrderAddress>

      		<SalesOrderDeliveryAddress>
        		<Forename><xsl:value-of select="shipping-address/first-name"/></Forename>
        		<Surname><xsl:value-of select="shipping-address/last-name"/></Surname>
        		<Company><xsl:value-of select="shipping-address/company"/></Company>
        		<Address1><xsl:value-of select="shipping-address/address1"/></Address1>
            	<Address2><xsl:value-of select="shipping-address/address2"/></Address2>
        		<Town><xsl:value-of select="shipping-address/city"/></Town>
        		<Postcode><xsl:value-of select="shipping-address/zip"/></Postcode>
        		<County><xsl:value-of select="shipping-address/province"/></County>
        		<Country><xsl:value-of select="shipping-address/country-code"/></Country>
        		<Telephone><xsl:value-of select="shipping-address/phone"/></Telephone>
		    	<Email><xsl:value-of select="customer/email"/></Email>
      		</SalesOrderDeliveryAddress>

      		<SalesOrderItems>
        		<xsl:for-each select="line-items/line-item">
         			<xsl:call-template name="Item" />
        		</xsl:for-each>
      		</SalesOrderItems>

      		<Carriage>
        		<Sku><xsl:value-of select="shipping-lines/shipping-line/code" /></Sku>
        		<Name><xsl:value-of select="shipping-lines/shipping-line/title" /></Name>
        		<QtyOrdered><xsl:value-of select="1" /></QtyOrdered>
        		<UnitPrice><xsl:value-of select="sum(shipping-lines/shipping-line/price)" /></UnitPrice>
				
				<xsl:if test="$CarriageTaxCode">
					<TaxCode><xsl:value-of select="$CarriageTaxCode"/></TaxCode>
				</xsl:if>
      		</Carriage>
			
			<VatInclusive><xsl:value-of select="taxes-included" /></VatInclusive>
			<TakenBy>Shopify</TakenBy>
			<PaymentRef><xsl:value-of select="gateway"/></PaymentRef>
			<PaymentAmount><xsl:value-of select="total-price"/></PaymentAmount>
			<BankAccount><xsl:value-of select="$BankAccount"/></BankAccount>

		</SalesOrder>
	</xsl:template>

	<xsl:template name="Item">
		<Item>
			<Sku><xsl:value-of select="sku" /></Sku>
			<Name><xsl:value-of select="name" /></Name>
			<QtyOrdered><xsl:value-of select="quantity" /></QtyOrdered>
			<UnitPrice><xsl:value-of select="price" /></UnitPrice>
			
			<xsl:if test="$TaxCode">
				<TaxCode><xsl:value-of select="$TaxCode"/></TaxCode>
			</xsl:if>
		</Item>
	</xsl:template>

</xsl:stylesheet>