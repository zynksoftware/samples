<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" indent="yes"/>

	<xsl:param name="AccountReference" /> <!-- A/C to use for all guest checkouts -->
	<xsl:param name="TaxCode"/> <!-- Tax code to use for all sales and carriage -->
	<xsl:param name="CarriageNominalCode"/> <!-- Nominal code to use for the carriage -->
	<xsl:param name="TakenBy"/> <!-- Order taken by -->

	<xsl:variable name="br"><xsl:text>&#10;</xsl:text></xsl:variable>
	<xsl:variable name="comma"><xsl:text>, </xsl:text></xsl:variable>

	<xsl:template match="ArrayOfOrderInfo">
		<Company>
			<SalesOrders>
				<xsl:for-each select="OrderInfo">
					<xsl:call-template name="SalesOrder" />
				</xsl:for-each>
			</SalesOrders>
		</Company>
	</xsl:template>

	<xsl:template name="SalesOrder">
		<SalesOrder>
			<Id><xsl:value-of select="order_id"/></Id>
			<xsl:choose>
			  <xsl:when test="customer_id != '0'"> <!-- when not a guest checkout -->
			    <CustomerId><xsl:value-of select="customer_id"/></CustomerId>
			  </xsl:when>
			  <xsl:otherwise>
			    <AccountReference><xsl:value-of select="$AccountReference"/></AccountReference>
			  </xsl:otherwise>
			</xsl:choose>
			<CustomerOrderNumber><xsl:value-of select="increment_id"/></CustomerOrderNumber>
			<SalesOrderDate><xsl:value-of select="substring-before(created_at, ' ')"/>T<xsl:value-of select="substring-after(created_at, ' ')"/></SalesOrderDate>
			<SalesOrderAddress>
				<Forename><xsl:value-of select="billing_address/firstname"/></Forename>
				<Surname><xsl:value-of select="billing_address/lastname"/></Surname>
				<Company><xsl:value-of select="billing_address/company"/></Company>

				<xsl:choose>
					<xsl:when test="contains(billing_address/street, $br)">
						<Address1>
							<xsl:value-of select="substring-before(billing_address/street, $br)"/>
						</Address1>
						<Address2>
							<xsl:call-template name="string-replace-all">
								<xsl:with-param name="text" select="substring-after(billing_address/street, $br)"/>
								<xsl:with-param name="replace" select="$br"/>
								<xsl:with-param name="by" select="$comma"/>
							</xsl:call-template>
						</Address2>
					</xsl:when>
					<xsl:otherwise>
						<Address1>
							<xsl:value-of select="billing_address/street"/>
						</Address1>
					</xsl:otherwise>
				</xsl:choose>

				<Town><xsl:value-of select="billing_address/city"/></Town>
				<Postcode><xsl:value-of select="billing_address/postcode"/></Postcode>
				<County><xsl:value-of select="billing_address/region"/></County>
				<Country><xsl:value-of select="billing_address/country_id"/></Country>
				<Telephone><xsl:value-of select="billing_address/telephone"/></Telephone>
				<Fax><xsl:value-of select="billing_address/fax"/></Fax>
				<Email><xsl:value-of select="customer_email"/></Email>
			</SalesOrderAddress>

			<SalesOrderDeliveryAddress>
				<Forename><xsl:value-of select="shipping_address/firstname"/></Forename>
				<Surname><xsl:value-of select="shipping_address/lastname"/></Surname>
				<Company><xsl:value-of select="shipping_address/company"/></Company>

				<xsl:choose>
					<xsl:when test="contains(shipping_address/street, $br)">
						<Address1>
							<xsl:value-of select="substring-before(shipping_address/street, $br)"/>
						</Address1>
						<Address2>
							<xsl:call-template name="string-replace-all">
								<xsl:with-param name="text" select="substring-after(shipping_address/street, $br)"/>
								<xsl:with-param name="replace" select="$br"/>
								<xsl:with-param name="by" select="$comma"/>
							</xsl:call-template>
						</Address2>
					</xsl:when>
					<xsl:otherwise>
						<Address1>
							<xsl:value-of select="shipping_address/street"/>
						</Address1>
					</xsl:otherwise>
				</xsl:choose>

				<Town><xsl:value-of select="shipping_address/city"/></Town>
				<Postcode><xsl:value-of select="shipping_address/postcode"/></Postcode>
				<County><xsl:value-of select="shipping_address/region"/></County>
				<Country><xsl:value-of select="shipping_address/country_id"/></Country>
			</SalesOrderDeliveryAddress>

			<SalesOrderItems>
				<xsl:for-each select="items/OrderProduct[product_type != 'configurable']">
					<xsl:call-template name="Item" />
				</xsl:for-each>
			</SalesOrderItems>

			<Carriage>
				<Name><xsl:value-of select="shipping_method" /></Name>
				<QtyOrdered><xsl:value-of select="1" /></QtyOrdered>
				<UnitPrice><xsl:value-of select="shipping_amount" /></UnitPrice>
				<TaxCode><xsl:value-of select="$TaxCode"/></TaxCode>
				<NominalCode><xsl:value-of select="$CarriageNominalCode"/></NominalCode>
			</Carriage>

			<TakenBy><xsl:value-of select="$TakenBy"/></TakenBy>

		</SalesOrder>
	</xsl:template>

	<xsl:template name="Item">
		<Item>
			<Sku><xsl:value-of select="sku"/></Sku>
			<Name><xsl:value-of select="name" /></Name>
			<QtyOrdered><xsl:value-of select="qty_ordered" /></QtyOrdered>
			<UnitPrice><xsl:value-of select="price" /></UnitPrice>
			<TaxCode><xsl:value-of select="$TaxCode"/></TaxCode>
		</Item>
	</xsl:template>

	<xsl:template name="string-replace-all">
		<xsl:param name="text"/>
		<xsl:param name="replace"/>
		<xsl:param name="by"/>
		<xsl:choose>
			<xsl:when test="contains($text,$replace)">
				<xsl:value-of select="substring-before($text,$replace)"/>
				<xsl:value-of select="$by"/>
				<xsl:call-template name="string-replace-all">
					<xsl:with-param name="text" select="substring-after($text,$replace)"/>
					<xsl:with-param name="replace" select="$replace"/>
					<xsl:with-param name="by" select="$by"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>