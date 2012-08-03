<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:x="urn:schemas-microsoft-com:office:excel"
                xmlns:o="urn:schemas-microsoft-com:office:office"
                xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
                exclude-result-prefixes="x o ss">

  <xsl:output method="xml" indent="yes"/>
  
  <!-- Parameters -->
  <xsl:param name="AccountReference"/>
  <xsl:param name="CarriageUnitPrice"/> <!-- Specifies the delivery charge to be applied to invoices-->
  <xsl:param name="DiscountUnitPrice"/> <!-- Specifies the discount amount to be applied to invoices-->
  <xsl:param name="TaxRate"/> <!-- Specifies the tax rate (eg 0.2) to be applied to the invoice -->
  
  <!-- Global variables to find and store the index of each piece of data-->
  <xsl:variable name="invoiceNumber" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Invoice Number']/@ss:Index"/>
  <xsl:variable name="customerId" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'CRM ID']/@ss:Index"/>
  <xsl:variable name="invoiceDate" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Invoice Date']/@ss:Index"/>

  <xsl:variable name="customerName" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Name']/@ss:Index"/>
  <xsl:variable name="customerTitle" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Title']/@ss:Index"/>
  <xsl:variable name="customerEmail" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Email 1 (Primary)']/@ss:Index"/>
  <xsl:variable name="customerWorkPhone" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Work Phone']/@ss:Index"/>
  <xsl:variable name="customerWorkFax" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Work Fax']/@ss:Index"/>
  <xsl:variable name="customerHomePhone" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Home Phone']/@ss:Index"/>
  <xsl:variable name="customerHomeFax" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Home Fax']/@ss:Index"/>
  
  <xsl:variable name="invoiceAddress1" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Billing Address']/@ss:Index"/>
  <xsl:variable name="invoiceTown" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Billing Address City']/@ss:Index"/>
  <xsl:variable name="invoiceCounty" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Billing Address State']/@ss:Index"/>
  <xsl:variable name="invoicePostcode" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Billing Address Zipcode']/@ss:Index"/>
  <xsl:variable name="invoiceCountry" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Billing Address Country']/@ss:Index"/>
  
  <xsl:variable name="deliveryAddress1" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Shipping Address']/@ss:Index"/>
  <xsl:variable name="deliveryTown" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Shipping Address City']/@ss:Index"/>
  <xsl:variable name="deliveryCounty" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Shipping Address State']/@ss:Index"/>
  <xsl:variable name="deliveryPostcode" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Shipping Address Zipcode']/@ss:Index"/>
  <xsl:variable name="deliveryCountry" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Shipping Address Country']/@ss:Index"/>

  <xsl:variable name="itemName" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Product']/@ss:Index"/>
  <xsl:variable name="itemQuantity" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Quantity']/@ss:Index"/>
  <xsl:variable name="itemTotal" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Product Total']/@ss:Index"/>

  <xsl:variable name="carriageName" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Shipping Description']/@ss:Index"/>
  <xsl:variable name="orderTotal" select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Total Price']/@ss:Index"/>
  
  <!-- Keys to select unique invoices IDs / customer IDs-->
  <xsl:key name="InvoiceId" match="ss:Row/ss:Cell[@ss:Index = /ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'Invoice Number']/@ss:Index]/ss:Data" use="."/>
  <xsl:key name="CustomerId" match="ss:Row/ss:Cell[@ss:Index = /ss:Workbook/ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell[ss:Data = 'CRM ID']/@ss:Index]/ss:Data" use="."/>
  
  <xsl:template match="/">
    <Company>
      <Customers>
        <xsl:for-each select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[position() > 1]/ss:Cell/ss:Data[generate-id() = generate-id(key('CustomerId',.)[1])]">
          <xsl:call-template name="Customer">
            <xsl:with-param name="node" select="../.."/>
          </xsl:call-template>
        </xsl:for-each>
      </Customers>
      <Invoices>
        <xsl:for-each select="ss:Workbook/ss:Worksheet/ss:Table/ss:Row[position() > 1]/ss:Cell/ss:Data[generate-id() = generate-id(key('InvoiceId',.)[1])]">
          <xsl:call-template name="Invoice">
            <xsl:with-param name="node" select="../.."/>
          </xsl:call-template>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Customer details-->
  <xsl:template name="Customer">
    <xsl:param name="node"/>
    <!-- the current customer row node -->
    <Customer>
      <Id>
        <xsl:value-of select="$node/ss:Cell[@ss:Index = $customerId]/ss:Data"/>
      </Id>
      <CompanyName>
        <xsl:value-of select="substring-after($node/ss:Cell[@ss:Index = $customerName]/ss:Data, ', ')"/>
      </CompanyName>
      <CustomerInvoiceAddress>
        <xsl:call-template name="customerDetails"/>
        <Address1>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceAddress1]/ss:Data"/>
        </Address1>
        <Town>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceTown]/ss:Data"/>
        </Town>
        <Postcode>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoicePostcode]/ss:Data"/>
        </Postcode>
        <County>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceCounty]/ss:Data"/>
        </County>
        <Country>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceCountry]/ss:Data"/>
        </Country>
      </CustomerInvoiceAddress>
      <CustomerDeliveryAddress>
        <xsl:call-template name="customerDetails"/>
        <Address1>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryAddress1]/ss:Data"/>
        </Address1>
        <Town>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryTown]/ss:Data"/>
        </Town>
        <Postcode>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryPostcode]/ss:Data"/>
        </Postcode>
        <County>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryCounty]/ss:Data"/>
        </County>
        <Country>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryCountry]/ss:Data"/>
        </Country>
      </CustomerDeliveryAddress>
    </Customer>
  </xsl:template>
  
  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <xsl:param name="node"/> <!-- the current invoice row node -->
    <Invoice>
      <Id>
        <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceNumber]/ss:Data"/>
      </Id>
      <CustomerId>
        <xsl:value-of select="$node/ss:Cell[@ss:Index = $customerId]/ss:Data"/>
      </CustomerId>
      <InvoiceNumber>
        <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceNumber]/ss:Data"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceNumber]/ss:Data"/>
      </CustomerOrderNumber>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <InvoiceDate>
        <xsl:value-of select="substring-before($node/ss:Cell[@ss:Index = $invoiceDate]/ss:Data, '.')"/>
      </InvoiceDate>
      <TakenBy>BusinessCatalyst</TakenBy>
      
      <InvoiceAddress>
        <xsl:call-template name="customerDetails"/>
        <Address1>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceAddress1]/ss:Data"/>
        </Address1>
        <Town>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceTown]/ss:Data"/>
        </Town>
        <Postcode>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoicePostcode]/ss:Data"/>
        </Postcode>
        <County>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceCounty]/ss:Data"/>
        </County>
        <Country>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $invoiceCountry]/ss:Data"/>
        </Country>
      </InvoiceAddress>
      
      <InvoiceDeliveryAddress>
        <xsl:call-template name="customerDetails"/>
        <Address1>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryAddress1]/ss:Data"/>
        </Address1>
        <Town>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryTown]/ss:Data"/>
        </Town>
        <Postcode>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryPostcode]/ss:Data"/>
        </Postcode>
        <County>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryCounty]/ss:Data"/>
        </County>
        <Country>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $deliveryCountry]/ss:Data"/>
        </Country>
      </InvoiceDeliveryAddress>
      
      <InvoiceItems>
        <xsl:variable name="Id" select="$node/ss:Cell[@ss:Index = $invoiceNumber]/ss:Data"/>
        <!-- Select each row with matching ID number-->
        <xsl:for-each select="//ss:Row">
          <xsl:if test="./ss:Cell[@ss:Index = $invoiceNumber]/ss:Data = $Id">
            <xsl:call-template name="InvoiceItem"/>
          </xsl:if>
        </xsl:for-each>
        
        <xsl:if test="$DiscountUnitPrice">
          <xsl:call-template name="DiscountItem"/>
        </xsl:if>
      </InvoiceItems>

      <Carriage>
        <Name>
          <xsl:value-of select="$node/ss:Cell[@ss:Index = $carriageName]/ss:Data"/>
        </Name>
        <QtyOrdered>1</QtyOrdered>
        <xsl:if test="$CarriageUnitPrice">
          <UnitPrice>
            <xsl:value-of select="format-number($CarriageUnitPrice, '0.00')"/>
          </UnitPrice>
          <xsl:if test="$TaxRate">
            <TotalNet>
              <xsl:value-of select="format-number($CarriageUnitPrice, '0.00')"/>
            </TotalNet>
            <TotalTax>
              <xsl:value-of select="format-number($CarriageUnitPrice * $TaxRate, '0.00')"/>
            </TotalTax>
          </xsl:if>
        </xsl:if>
        
        <Type>NonStock</Type>
      </Carriage>

      <PaymentAmount>
        <xsl:value-of select="$node/ss:Cell[@ss:Index = $orderTotal]/ss:Data"/>
      </PaymentAmount>

    </Invoice>
  </xsl:template>
  
  <!-- Invoice items -->
  <xsl:template name="InvoiceItem">
    <Item>
      <Name>
        <xsl:value-of select="./ss:Cell[@ss:Index = $itemName]/ss:Data"/>
      </Name>
      <xsl:variable name="qty" select="./ss:Cell[@ss:Index = $itemQuantity]/ss:Data"/>
      <QtyOrdered>
        <xsl:value-of select="$qty"/>
      </QtyOrdered>

      <xsl:choose>
        <xsl:when test="$TaxRate">
          <xsl:variable name="totalNet" select="./ss:Cell[@ss:Index = $itemTotal]/ss:Data div (1 + $TaxRate)"/>
          <UnitPrice>
            <xsl:value-of select="format-number($totalNet div $qty,'0.00')"/>
          </UnitPrice>
          <TotalNet>
            <xsl:value-of select="format-number($totalNet,'0.00')"/>
          </TotalNet>
          <TotalTax>
            <xsl:value-of select="format-number(./ss:Cell[@ss:Index = $itemTotal]/ss:Data - $totalNet,'0.00')"/>
          </TotalTax>
        </xsl:when>
        <xsl:otherwise>
          <UnitPrice>
            <xsl:value-of select="format-number(./ss:Cell[@ss:Index = $itemTotal]/ss:Data div $qty,'0.00')"/>
          </UnitPrice>
        </xsl:otherwise>
      </xsl:choose>

      <Type>Stock</Type>
    </Item>
  </xsl:template>

  <!-- Discount Codes -->
  <xsl:template name="DiscountItem">
    <Item>
      <Name>Discount</Name>
      <QtyOrdered>1</QtyOrdered>
      <UnitPrice>
        <xsl:choose>
          <xsl:when test="$DiscountUnitPrice > 0">
            <xsl:value-of select="$DiscountUnitPrice * -1"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$DiscountUnitPrice"/>
          </xsl:otherwise>
        </xsl:choose>
      </UnitPrice>
      <Type>NonStock</Type>
    </Item>
  </xsl:template>
  
  <!-- Customer Details -->
  <xsl:template name="customerDetails">
    <Title>
      <xsl:value-of select="../../ss:Cell[@ss:Index = $customerTitle]/ss:Data"/>
    </Title>
    <Forename>
      <xsl:value-of select="substring-before(../../ss:Cell[@ss:Index = $customerName]/ss:Data, ' ')"/>
    </Forename>
    <xsl:choose>
      <xsl:when test="contains(../../ss:Cell[@ss:Index = $customerName]/ss:Data, ', ')">
        <Surname>
          <xsl:value-of select="substring-after(substring-before(../../ss:Cell[@ss:Index = $customerName]/ss:Data, ', '), ' ')"/>
        </Surname>
        <Company>
          <xsl:value-of select="substring-after(../../ss:Cell[@ss:Index = $customerName]/ss:Data, ', ')"/>
        </Company>
      </xsl:when>
      <xsl:otherwise>
        <Surname>
          <xsl:value-of select="substring-after(../../ss:Cell[@ss:Index = $customerName]/ss:Data, ' ')"/>
        </Surname>
      </xsl:otherwise>
    </xsl:choose>
    <Telephone>
      <xsl:choose>
        <xsl:when test="../../ss:Cell[@ss:Index = $customerWorkPhone]/ss:Data != ''">
          <xsl:value-of select="../../ss:Cell[@ss:Index = $customerWorkPhone]/ss:Data"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="../../ss:Cell[@ss:Index = $customerHomePhone]/ss:Data"/>
        </xsl:otherwise>
      </xsl:choose>
    </Telephone>
    <Fax>
      <xsl:choose>
        <xsl:when test="../../ss:Cell[@ss:Index = $customerWorkFax]/ss:Data != ''">
          <xsl:value-of select="../../ss:Cell[@ss:Index = $customerWorkFax]/ss:Data"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="../../ss:Cell[@ss:Index = $customerHomeFax]/ss:Data"/>
        </xsl:otherwise>
      </xsl:choose>
    </Fax>
    <Email>
      <xsl:value-of select="../../ss:Cell[@ss:Index = $customerEmail]/ss:Data"/>
    </Email>
  </xsl:template>
  
</xsl:stylesheet>