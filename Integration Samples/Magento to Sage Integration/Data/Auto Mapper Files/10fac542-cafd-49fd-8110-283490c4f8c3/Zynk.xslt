<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.1"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output indent="yes" method="xml" encoding="utf-8" />

  <!-- Format a date to XSD format -->
  <xsl:template name="formatDate">
		<xsl:param name="dateTime" />

		<xsl:variable name="time" select="substring-after($dateTime, ' ')" />
		<xsl:variable name="date" select="substring-before($dateTime, ' ')" />
		<xsl:variable name="day" select="substring-before($date, '/')" />
		<xsl:variable name="month" select="substring-before(substring-after($date, '/'), '/')" />
		<xsl:variable name="year" select="substring-after(substring-after($date, '/'), '/')" />
		<xsl:value-of select="concat($year, '-', $month, '-', $day, 'T', $time)" />
  </xsl:template>
  
  <!-- Don't write out the node should the value be empty -->
  <xsl:template name="filterOutEmptyNode">
    <xsl:param name="NodeName" />
    <xsl:param name="NodeValue" />

    <xsl:choose>
      <xsl:when test="$NodeValue!=''">
        <xsl:element name="{$NodeName}"><xsl:value-of select="$NodeValue" /></xsl:element>
      </xsl:when>
      <xsl:otherwise><!-- Don't Output --></xsl:otherwise>
    </xsl:choose>

  </xsl:template>
  
  <!-- Set value to default should it not exist -->
  <xsl:template name="safeSetValue">
    <xsl:param name="Value" />
    <xsl:param name="Default" />

    <xsl:choose>
      <xsl:when test="$Value!=''"><xsl:value-of select="$Value" /></xsl:when>
      <xsl:otherwise><xsl:value-of select="$Default" /></xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- Work out the Net for a given value -->
  <xsl:template name="calculateNetPrice">
    <xsl:param name="CalculateAsVATInclusive" />
    <xsl:param name="Price" />
    <xsl:param name="TaxRate" />
    <xsl:param name="Quantity" />
    <xsl:param name="NumberFormat" /><!-- 0.0000 (4dp), 0.00 (2dp) -->

    <xsl:param name="WorkingQuantity">
      <xsl:call-template name="safeSetValue">
        <xsl:with-param name="Value" select="$Quantity" />
        <xsl:with-param name="Default" select="1" />
      </xsl:call-template>
    </xsl:param>
    
    <xsl:param name="WorkingNumberFormat">
      <xsl:call-template name="safeSetValue">
        <xsl:with-param name="Value" select="$NumberFormat" />
        <xsl:with-param name="Default" select="'0.00'" />
      </xsl:call-template>
    </xsl:param>
    
    <xsl:choose>
      <xsl:when test="$CalculateAsVATInclusive='true'">
        <xsl:value-of select="format-number(((($Price div (100 + $TaxRate)) * 100) div $WorkingQuantity), $WorkingNumberFormat)" />
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="format-number(($Price div $WorkingQuantity), $WorkingNumberFormat)" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- Output zero if empty -->
  <xsl:template name="isEmpty">
    <xsl:param name="Input" />
    <xsl:choose>
      <xsl:when test="$Input &gt; 0">
        <xsl:value-of select="$Input" />
      </xsl:when>
      <xsl:otherwise>0</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

</xsl:stylesheet>
