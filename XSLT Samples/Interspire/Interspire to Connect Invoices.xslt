<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" indent="yes"/>

  <xsl:param name="AccountReference"/>

  <xsl:key name="CustId" match="Customer_ID" use="."/>

  <xsl:template match="/">
    <Company>
      <Customers>
        <xsl:for-each select="orders/order/Customer_ID[generate-id() = generate-id(key('CustId',.)[1])]/..">
          <xsl:call-template name="Customer"/>
        </xsl:for-each>
      </Customers>
      <Invoices>
        <xsl:for-each select="orders/order">
          <xsl:call-template name="Invoice"/>
        </xsl:for-each>
      </Invoices>
    </Company>
  </xsl:template>

  <!-- Customer details-->
  <xsl:template name="Customer">
    <Customer>
      <Id>
        <xsl:value-of select="./Customer_ID"/>
      </Id>
      <CustomerInvoiceAddress>
        <Forename>
          <xsl:value-of select="./Billing_First_Name"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./Billing_Last_Name"/>
        </Surname>
        <Company>
          <xsl:value-of select="./Billing_Company"/>
        </Company>
        <Address1>
          <xsl:value-of select="./Billing_Street_1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./Billing_Street_2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./Billing_Suburb"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./Billing_Zip"/>
        </Postcode>
        <County>
          <xsl:value-of select="./Billing_State"/>
        </County>
        <Country>
          <xsl:value-of select="./Billing_Country"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./Billing_Phone"/>
        </Telephone>
        <Email>
          <xsl:value-of select="./Billing_Email"/>
        </Email>
      </CustomerInvoiceAddress>
      <CustomerDeliveryAddress>
        <Forename>
          <xsl:value-of select="./Shipping_First_Name"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./Shipping_Last_Name"/>
        </Surname>
        <Company>
          <xsl:value-of select="./Shipping_Company"/>
        </Company>
        <Address1>
          <xsl:value-of select="./Shipping_Street_1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./Shipping_Street_2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./Shipping_Suburb"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./Shipping_Zip"/>
        </Postcode>
        <County>
          <xsl:value-of select="./Shipping_State"/>
        </County>
        <Country>
          <xsl:value-of select="./Shipping_Country"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./Shipping_Phone"/>
        </Telephone>
        <Email>
          <xsl:value-of select="./Shipping_Email"/>
        </Email>
      </CustomerDeliveryAddress>
    </Customer>
  </xsl:template>

  <!-- Invoice details-->
  <xsl:template name="Invoice">
    <Invoice>
      <Id>
        <xsl:value-of select="./Order_ID"/>
      </Id>
      <CustomerId>
        <xsl:value-of select="./Customer_ID"/>
      </CustomerId>
      <InvoiceNumber>
        <xsl:value-of select="./Order_ID"/>
      </InvoiceNumber>
      <CustomerOrderNumber>
        <xsl:value-of select="./Order_ID"/>
      </CustomerOrderNumber>
      <Currency>
        <xsl:value-of select="./Order_Currency_Code"/>
      </Currency>
      <AccountReference>
        <xsl:value-of select="$AccountReference"/>
      </AccountReference>
      <InvoiceDate>
        <xsl:call-template name="formatDate">
          <xsl:with-param name="dateTime" select="./Order_Date"/>
        </xsl:call-template>
      </InvoiceDate>
      <TakenBy>Interspire</TakenBy>
      <xsl:choose>
        <xsl:when test="./Order_Currency_Code='GBP'">
          <CurrencyUsed>false</CurrencyUsed>
        </xsl:when>
        <xsl:otherwise>
          <CurrencyUsed>true</CurrencyUsed>
        </xsl:otherwise>
      </xsl:choose>

      <InvoiceAddress>
        <Forename>
          <xsl:value-of select="./Billing_First_Name"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./Billing_Last_Name"/>
        </Surname>
        <Company>
          <xsl:value-of select="./Billing_Company"/>
        </Company>
        <Address1>
          <xsl:value-of select="./Billing_Street_1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./Billing_Street_2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./Billing_Suburb"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./Billing_Zip"/>
        </Postcode>
        <County>
          <xsl:value-of select="./Billing_State"/>
        </County>
        <Country>
          <xsl:value-of select="./Billing_Country"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./Billing_Phone"/>
        </Telephone>
        <Email>
          <xsl:value-of select="./Billing_Email"/>
        </Email>
      </InvoiceAddress>

      <InvoiceDeliveryAddress>
        <Forename>
          <xsl:value-of select="./Shipping_First_Name"/>
        </Forename>
        <Surname>
          <xsl:value-of select="./Shipping_Last_Name"/>
        </Surname>
        <Company>
          <xsl:value-of select="./Shipping_Company"/>
        </Company>
        <Address1>
          <xsl:value-of select="./Shipping_Street_1"/>
        </Address1>
        <Address2>
          <xsl:value-of select="./Shipping_Street_2"/>
        </Address2>
        <Town>
          <xsl:value-of select="./Shipping_Suburb"/>
        </Town>
        <Postcode>
          <xsl:value-of select="./Shipping_Zip"/>
        </Postcode>
        <County>
          <xsl:value-of select="./Shipping_State"/>
        </County>
        <Country>
          <xsl:value-of select="./Shipping_Country"/>
        </Country>
        <Telephone>
          <xsl:value-of select="./Shipping_Phone"/>
        </Telephone>
        <Email>
          <xsl:value-of select="./Shipping_Email"/>
        </Email>
      </InvoiceDeliveryAddress>

      <InvoiceItems>
        <xsl:for-each select="Product_Details/item">
          <xsl:call-template name="InvoiceItem"/>
        </xsl:for-each>
        <xsl:if test="Handling_Cost_inc_tax > 0">
          <Item>
            <Name>Handling Fee</Name>
            <QtyOrdered>1</QtyOrdered>
            <UnitPrice>
              <xsl:value-of select="./Handling_Cost_inc_tax"/>
            </UnitPrice>
            <TotalNet>
              <xsl:value-of select="./Handling_Cost_ex_tax"/>
            </TotalNet>
            <TotalTax>
              <xsl:value-of select="./Handling_Cost_inc_tax - ./Handling_Cost_ex_tax"/>
            </TotalTax>
          </Item>
        </xsl:if>
      </InvoiceItems>

      <Carriage>
        <Name>
          <xsl:value-of select="./Ship_Method"/>
        </Name>
        <QtyOrdered>1</QtyOrdered>
        <UnitPrice>
          <xsl:value-of select="./Shipping_Cost"/>
        </UnitPrice>
        <Type>NonStock</Type>
      </Carriage>

      <PaymentRef>
        <xsl:value-of select="./Payment_Method"/>
      </PaymentRef>
      <PaymentAmount>
        <xsl:value-of select="./Order_Total"/>
      </PaymentAmount>
      <xsl:choose>
        <xsl:when test="./Order_Status='Completed' or ./Order_Status='Shipped' or ./Order_Status='Partially Shipped'">
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
      <Id>
        <xsl:value-of select="./Product_ID"/>
      </Id>
      <Sku>
        <xsl:value-of select="./Product_SKU"/>
      </Sku>
      <Name>
        <xsl:value-of select="./Product_Name"/>
      </Name>
      <QtyOrdered>
        <xsl:value-of select="./Product_Qty"/>
      </QtyOrdered>
      <xsl:variable name="taxRate" select="../../Tax_Rate div 100"/>
      <UnitPrice>
        <xsl:value-of select="format-number(./Product_Unit_Price div ($taxRate + 1), '0.00')"/>
      </UnitPrice>
      <TaxRate>
        <xsl:value-of select="$taxRate"/>
      </TaxRate>
      <xsl:variable name="totalNet" select="./Product_Total_Price div ($taxRate + 1)"/>
      <TotalNet>
        <xsl:value-of select="format-number($totalNet, '0.00')"/>
      </TotalNet>
      <TotalTax>
        <xsl:value-of select="format-number(./Product_Total_Price - $totalNet, '0.00')"/>
      </TotalTax>
      <xsl:choose>
        <xsl:when test="./CouponCode">
          <Type>NonStock</Type>
        </xsl:when>
        <xsl:otherwise>
          <Type>Stock</Type>
        </xsl:otherwise>
      </xsl:choose>
    </Item>
  </xsl:template>

  <!-- Date format transform -->
  <xsl:template name="formatDate">
    <xsl:param name="dateTime"/>

    <xsl:variable name="day" select="substring-before($dateTime, '/')" />
    <xsl:variable name="month" select="substring-before(substring-after($dateTime, '/'), '/')" />
    <xsl:variable name="year" select="substring-after(substring-after($dateTime, '/'), '/')" />

    <xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', '00:00:00')" />

  </xsl:template>

</xsl:stylesheet>