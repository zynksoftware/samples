<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>
  <xsl:param name="ItemTaxRate" select="0.0"/> <!-- Default to 0 -->
  <xsl:param name="ShippingTaxRate" select="0.0"/> <!-- Default to 0 -->
  
  <xsl:key name="CustId" match="CustomerID" use="."/>

  <xsl:template match="/">
    <Company>
      <Customers>
        <xsl:for-each select="/ArrayOfOrderInformation/OrderInformation/CustomerID[generate-id() = generate-id(key('CustId',.)[1])]/..">
          <xsl:call-template name="Customer"/>
        </xsl:for-each>
      </Customers>
      <Invoices>
        <xsl:for-each select="/ArrayOfOrderInformation/OrderInformation">
          <!-- Check if the node is the start of an invoice -->
          <xsl:if test="./CustomerID != ''">
            <xsl:call-template name="Invoice"/>
          </xsl:if>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Customer details -->
  <xsl:template name="Customer">
    <Customer>
      <Id>
        <xsl:value-of select="./CustomerID"/>
      </Id>
      <CompanyName>
        <xsl:value-of select="./BillingCompanyName"/>
      </CompanyName>
      <CustomerInvoiceAddress>
        <xsl:call-template name="InvoiceAddress"/>
      </CustomerInvoiceAddress>
      <CustomerDeliveryAddress>
        <xsl:call-template name="DeliveryAddress"/>
      </CustomerDeliveryAddress>
    </Customer>
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
      <TakenBy>ekmPowerShop</TakenBy>

      <InvoiceAddress>
        <xsl:call-template name="InvoiceAddress"/>
      </InvoiceAddress>
      <InvoiceDeliveryAddress>
        <xsl:call-template name="DeliveryAddress"/>
      </InvoiceDeliveryAddress>
        
      <InvoiceItems>
        <xsl:variable name="nextNodeNumber" select="count(./preceding-sibling::*) + 2"/>
        <xsl:call-template name="InvoiceItem">
          <xsl:with-param name="rowNode" select="../OrderInformation[$nextNodeNumber]"/>
          <xsl:with-param name="nodePosition" select="$nextNodeNumber"/>
        </xsl:call-template>
      </InvoiceItems>

      <Carriage>
        <Name>
          <xsl:value-of select="./DeliveryMethod"/>
        </Name>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="./TotalDelivery"/>
        </UnitPrice>
        <TaxRate>
          <xsl:value-of select="$ShippingTaxRate"/>
        </TaxRate>
        <TotalNet>
          <xsl:value-of select="./TotalDelivery"/>
        </TotalNet>
        <TotalTax>
          <xsl:value-of select="format-number(./TotalDelivery * $ShippingTaxRate, '0.00')"/>
        </TotalTax>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentRef>
        <xsl:value-of select="./OrderGateway"/>
      </PaymentRef>
      <PaymentAmount>
        <xsl:value-of select="format-number(./TotalCost, '0.00')"/>
      </PaymentAmount>

    </Invoice>
  </xsl:template>

  <!-- Invoice Address -->
  <xsl:template name="InvoiceAddress">
    <Forename>
      <xsl:value-of select="./BillingFirstName"/>
    </Forename>
    <Surname>
      <xsl:value-of select="./BillingLastName"/>
    </Surname>
    <Company>
      <xsl:value-of select="./BillingCompanyName"/>
    </Company>
    <Address1>
      <xsl:value-of select="./BillingAddress1"/>
    </Address1>
    <Address2>
      <xsl:value-of select="./BillingAddress2"/>
    </Address2>
    <Town>
      <xsl:value-of select="./BillingTown"/>
    </Town>
    <Postcode>
      <xsl:value-of select="./BillingPostCode"/>
    </Postcode>
    <County>
      <xsl:value-of select="./BillingCounty"/>
    </County>
    <Country>
      <xsl:value-of select="./BillingCountry"/>
    </Country>
    <Telephone>
      <xsl:value-of select="./BillingTelephone"/>
    </Telephone>
    <Email>
      <xsl:value-of select="./BillingEmailAddress"/>
    </Email>
  </xsl:template>

  <!-- Delivery Address -->
  <xsl:template name="DeliveryAddress">
    <xsl:choose>
      <!-- If the shipping address is blank, the customer must have selected the 'same as billing address' option -->
      <xsl:when test="./ShippingFirstName = '' and ./ShippingLastName = ''">
        <Forename>
          <xsl:value-of select="./BillingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./BillingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="./BillingCompanyName"/>
        </Company>
        <Address1>
          <xsl:value-of select="./BillingAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./BillingAddress2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./BillingTown"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./BillingPostCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="./BillingCounty"/>
        </County>
        <Country>
          <xsl:value-of select="./BillingCountry"/>
        </Country>
      </xsl:when>
      <xsl:otherwise>
        <Forename>
          <xsl:value-of select="./ShippingFirstName"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./ShippingLastName"/>
        </Surname>
        <Company>
          <xsl:value-of select="./ShippingCompany"/>
        </Company>
        <Address1>
          <xsl:value-of select="./ShippingAddress1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./ShippingAddress2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./ShippingTown"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./ShippingPostCode"/>
        </Postcode>
        <County>
          <xsl:value-of select="./ShippingCounty"/>
        </County>
        <Country>
          <xsl:value-of select="./ShippingCountry"/>
        </Country>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <xsl:param name="rowNode"/>
    <xsl:param name="nodePosition"/>
    <Item>
      <Id>
        <xsl:value-of select="$rowNode/ProductID"/>
      </Id>
      <Name>
        <xsl:value-of select="$rowNode/ProductName"/>
      </Name>
      <xsl:if test="$rowNode/ProductOptions != '[No Options]'">
        <Comments>
          <xsl:value-of select="$rowNode/ProductOptions"/>
        </Comments>
      </xsl:if>
      <QtyOrdered>
        <xsl:value-of select="$rowNode/ProductQuantity"/>
      </QtyOrdered>
      <UnitPrice>
        <xsl:value-of select="$rowNode/ProductPrice"/>
      </UnitPrice>
      <UnitDiscountAmount>
        <xsl:value-of select="$rowNode/ProductDiscount"/>
      </UnitDiscountAmount>
      <TaxRate>
        <xsl:value-of select="$ItemTaxRate"/>
      </TaxRate>
      <xsl:variable name="totalNet" select="$rowNode/ProductPrice * $rowNode/ProductQuantity - $rowNode/ProductDiscount" />
      <TotalNet>
        <xsl:value-of select="format-number($totalNet, '0.00')"/>
      </TotalNet>
      <TotalTax>
        <xsl:value-of select="format-number($totalNet * $ItemTaxRate, '0.00')"/>
      </TotalTax>
      <Type>Stock</Type>
    </Item>

    <!-- Test if there are more items for this invoice-->
    <xsl:variable name="nextNodePosition" select="$nodePosition + 1"/>
    <xsl:if test="/ArrayOfOrderInformation/OrderInformation[$nextNodePosition]/ProductID != ''">
      <xsl:call-template name="InvoiceItem">
        <xsl:with-param name="rowNode" select="/ArrayOfOrderInformation/OrderInformation[$nextNodePosition]"/>
        <xsl:with-param name="nodePosition" select="$nodePosition + 1"/>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>

  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime"/>

    <xsl:variable name="date" select="substring-before($dateTime, ' ')"/>
    <xsl:variable name="day" select="substring-before($date, '/')"/>
    <xsl:variable name="month" select="substring-before(substring-after($date, '/'), '/')"/>
    <xsl:variable name="year" select="substring-after(substring-after($date, '/'), '/')"/>

    <xsl:variable name="time" select="substring-after($dateTime, ' ')"/>

    <xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', $time)"/>
  </xsl:template>

</xsl:stylesheet>