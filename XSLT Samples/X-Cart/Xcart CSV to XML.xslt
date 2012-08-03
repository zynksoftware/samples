<xsl:stylesheet
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:fn="fn"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  version="2.0" exclude-result-prefixes="xs fn">

  <xsl:output indent="yes" encoding="US-ASCII" />

  <xsl:param name="pathToCSV" select="'file:///c:/csv.csv'" />

  <!-- Tokenises a single row of CSV -->
  <xsl:function name="fn:getTokens" as="xs:string+">
    <xsl:param name="str" as="xs:string"/>
    <xsl:analyze-string select="concat($str, ',')" regex='(("[^"]*")+|[^,]*),'>
      <xsl:matching-substring>
        <xsl:sequence select='replace(regex-group(1), "^""|""$|("")""", "$1")'/>
      </xsl:matching-substring>
    </xsl:analyze-string>
  </xsl:function>

  <xsl:template match="/" name="main">
    <xsl:choose>
      <xsl:when test="unparsed-text-available($pathToCSV)">
        
        <!-- Split up the input file into Orders and Order Items -->
        <xsl:variable name="csv" select="unparsed-text($pathToCSV)"/>
        <xsl:variable name="orders" select="substring-before($csv, '&#13;&#10;,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,&#13;&#10;[ORDER_ITEMS]')"/>
        <xsl:variable name="orderItems" select="substring-after($csv, '[ORDER_ITEMS],,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,&#13;&#10;')"/>
        
        <!-- Process Orders -->
        <xsl:variable name="orderlines" select="tokenize($orders, '&#13;&#10;')" as="xs:string+"/>
        <xsl:variable name="orderNames" select="fn:getTokens($orderlines[2])" as="xs:string+"/>
        <Orders>
          <xsl:for-each select="$orderlines[position() &gt; 2]">
            <Order>
              <xsl:variable name="lineItems" select="fn:getTokens(.)" as="xs:string+"/>
              <xsl:for-each select="$orderNames">
                <xsl:variable name="pos" select="position()"/>
                <xsl:element name="{substring-after(., '!')}">
                  <xsl:value-of select="$lineItems[$pos]"/>
                </xsl:element>
              </xsl:for-each>
            </Order>
          </xsl:for-each>

          <!-- Process Order Items -->
          <xsl:variable name="orderItemLines" select="tokenize($orderItems, '&#13;&#10;')" as="xs:string+"/>
          <xsl:variable name="orderItemNames" select="fn:getTokens($orderItemLines[1])" as="xs:string+"/>
          <xsl:for-each select="$orderItemLines[position() &gt; 1]">
            <OrderItem>
              <xsl:variable name="lineItems" select="fn:getTokens(.)" as="xs:string+"/>
              <xsl:for-each select="$orderItemNames">
                <xsl:if test=". != ''">
                  <xsl:variable name="pos" select="position()"/>
                  <xsl:element name="{substring-after(., '!')}">
                    <xsl:value-of select="$lineItems[$pos]"/>
                  </xsl:element>
                </xsl:if>
              </xsl:for-each>
            </OrderItem>
          </xsl:for-each>
        </Orders>
        
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>Cannot locate : </xsl:text>
        <xsl:value-of select="$pathToCSV" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>

