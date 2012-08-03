<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet 
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:b="urn:ebay:apis:eBLBaseComponents">
	
	<xsl:output method="xml" indent="yes"/>
	
	<xsl:param name="AccountReference" />
	<xsl:param name="BankReference"/>
	<xsl:param name="CreditNominalCode"/>
    
	<xsl:template match="/TransactionSearchResponseType">
		<Company>
			<Transactions>
				<xsl:for-each select="b:PaymentTransactions">
					<xsl:choose>
						<xsl:when test="b:Type='Payment'">
							<xsl:call-template name="SalesReceipt" />								
						</xsl:when>
						<xsl:when test="b:Type='Refund'">
							<xsl:call-template name="SalesCredit" />		
						</xsl:when>
					</xsl:choose>  
				</xsl:for-each>
			</Transactions>
		</Company>
	</xsl:template>
  
	<xsl:template name="SalesReceipt">
		<Transaction>
			<Id><xsl:value-of select="b:TransactionID"/></Id>
			<CustomerId><xsl:value-of select="b:Payer"/></CustomerId>
			<TransactionType>SalesReceipt</TransactionType>
			<AccountReference><xsl:value-of select="$AccountReference"/></AccountReference>
			<TransactionDate><xsl:value-of select="b:Timestamp"/></TransactionDate>
			<Reference><xsl:value-of select="b:InvoiceID"/></Reference>
			<SecondReference><xsl:value-of select="b:TransactionID"/></SecondReference>
			<PaymentReference><xsl:value-of select="b:TransactionID"/></PaymentReference>
			<Details><xsl:value-of select="b:PayerDisplayName" /></Details>
			<NetAmount><xsl:value-of select="b:GrossAmount" /></NetAmount>
			<TaxRate>9</TaxRate>
			<TaxCode>0</TaxCode>
			<TaxAmount>0</TaxAmount>
			<BankReference><xsl:value-of select="$BankReference"/></BankReference>
			<Discount><xsl:value-of select="b:FeeAmount" /></Discount>
		</Transaction>	
	</xsl:template>
	
	<xsl:template name="SalesCredit">
		<Transaction>
			<Id><xsl:value-of select="b:TransactionID"/></Id>
			<CustomerId><xsl:value-of select="b:Payer"/></CustomerId>
			<TransactionType>SalesCredit</TransactionType>
			<AccountReference><xsl:value-of select="$AccountReference"/></AccountReference>
			<TransactionDate><xsl:value-of select="b:Timestamp"/></TransactionDate>
			<Reference><xsl:value-of select="b:InvoiceID"/></Reference>
			<SecondReference><xsl:value-of select="b:TransactionID"/></SecondReference>
			<PaymentReference><xsl:value-of select="b:TransactionID"/></PaymentReference>
			<Details><xsl:value-of select="b:PayerDisplayName" /></Details>
			<NetAmount><xsl:value-of select="b:GrossAmount" /></NetAmount>
			<TaxRate>9</TaxRate>
			<TaxCode>0</TaxCode>
			<TaxAmount>0</TaxAmount>
			<BankReference><xsl:value-of select="$BankReference"/></BankReference>
			<NominalCode><xsl:value-of select="$CreditNominalCode"/></NominalCode>
			<Discount><xsl:value-of select="b:FeeAmount" /></Discount>
		</Transaction>
	</xsl:template>
</xsl:stylesheet>