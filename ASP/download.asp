<%
	' COPYRIGHT (C) 2005, Internetware Ltd.
	' For Internetware Connect support, please see our contact details 
	' at http://www.internetware.co.uk
	
	'****** Reference *******
	
    '1. Country - Must be ISO 3166-1 2 digit country codes eg.. GB
    '2. Currency - Must be ISO 4217 3 character currency code eg.. GBP
	
	
	'****** Functions *******
	
	Function OpenConnection()
		set conn = Server.CreateObject("ADODB.Connection")
		conn.Open connString 		
	End Function

	Function CloseConnection()
		if ucase(TypeName(conn)) = "CONNECTION" then
			conn.Close()
			set conn = nothing
		end if
	End Function
	
	Function CleanXML(xml)
		xml = Replace(xml,"'","''")
		xml = Replace(xml,"""","""""")
		CleanXML = xml
	End Function
		
	Function AppendField (parent,objname,objvalue,xmlDoc)
		Dim child
		set child = xmlDoc.createElement(objname)
		if (IsNull(objvalue)) then
			child.text = ""
		elseif(objvalue = "False" OR objvalue = "True") then
			child.text = LCase(objvalue)
		else
			child.text = CStr(objvalue)
		end if
		parent.appendChild(child)
	End Function
	
	Function XSDDate(dt) 
		if (IsDate(dt)) then
	        XSDDate = year(dt) & "-" &  Right("0" & month(dt),2) & "-" & Right("0" & day(dt),2) & "T" & Right("0" & hour(dt),2) & ":" & Right("0" & minute(dt),2) & ":" & Right("0" & second(dt),2)  
		else
			XSDDate = "0001-01-01T00:00:00"
		end if
	End Function 
	
	
	'****** System Variables *******	
	
	Dim conn,connString
	
	'connString = "provider=SQLOLEDB;network=DBMSSOCN;uid=USERNAME;pwd=PASSWORD;server=SERVER;database=DATABASE" 'SQL Server
	connString = "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=" & Server.MapPath("Connect.mdb") 'Access	

	
	'****** Main Code *******	
		
	if (Request.TotalBytes > 0) then
	
		' Create Xml Document and load in Xml
		Dim objDoc
		Set objDoc = Server.CreateObject("Microsoft.XMLDOM")
		objDoc.async = False
		objDoc.Load(Request)
			
		' Check the document for errors
		If objDoc.parseError.errorCode <> 0 Then
			Dim ErrorText
			errorText = "OrderID: " & OrderID & vbcrlf
			errorText = errorText & "Code: " &  objDoc.parseError.errorCode & vbcrlf
			errorText = errorText & "Position: " &  objDoc.parseError.filepos & vbcrlf
			errorText = errorText & "Line: " &  objDoc.parseError.Line & vbcrlf
			errorText = errorText & "Line Position: " &  objDoc.parseError.linepos & vbcrlf
			errorText = errorText & "Reason: " &  objDoc.parseError.reason & vbcrlf
			errorText = errorText & "Source Text: " &  objDoc.parseError.srcText & vbcrlf			
			' Possibly send email if errors?
		End If
					
		' Open Connection to database
		OpenConnection()
		
		' Update Customers
		Set nodeList = objDoc.SelectNodes("Company/Customers/Customer")
		for each Node in nodeList
			CustomerId = CleanXML(Node.SelectSingleNode("Id").Text)
			CustomerRef = CleanXML(Node.SelectSingleNode("AccountReference").Text)
			
			' Update account references
			SQL = "UPDATE Customers SET Ref = '" & CustomerRef & "' WHERE ID=" & CustomerId
			set rsCount = conn.execute(SQL)	
		next
				
		' Update Sales Orders
		Set nodeList = objDoc.SelectNodes("Company/SalesOrders/SalesOrder")
		for each Node in nodeList
			SalesOrderId = CleanXML(Node.SelectSingleNode("Id").Text)
			SalesOrderIds = SalesOrderIds & SalesOrderId & ","	
		next
		
		if (SalesOrderIds <> "") then		
			' Remove trailing comma
			SalesOrderIds = LEFT(SalesOrderIds,LEN(SalesOrderIds) - 1)				
			' Update posted dates
			SQL = "UPDATE Orders SET PostedDate = '" & Now() & "' WHERE ID IN (" & SalesOrderIds & ")"
			set rsCount = conn.execute(SQL)
		end if
		
		' Update Transactions
		Set nodeList = objDoc.SelectNodes("Company/Transactions/Transaction")
		for each Node in nodeList
			TransactionId = CleanXML(Node.SelectSingleNode("Id").Text)
			TransactionIds = TransactionIds & TransactionId & ","	
		next
		
		if (TransactionIds <> "") then		
			' Remove trailing comma
			TransactionIds = LEFT(TransactionIds,LEN(TransactionIds) - 1)				
			' Update posted dates
			SQL = "UPDATE Transactions SET PostedDate = '" & Now() & "' WHERE ID IN (" & TransactionIds & ")"
			set rsCount = conn.execute(SQL)
		end if
	else	
	
		' Variables
		Dim CustomerSQL,SalesOrderSQL,InvoiceSQL,TransactionSQL,SalesOrderItemSQL,InvoiceItemSQL
		Dim orderID,invoiceID,transactionID
		Dim CustomerFilter,SalesOrderFilter,InvoiceFilter,TransactionFilter
		
		'Setup Database Queries
		CustomerSQL = "SELECT DISTINCT Customers.* FROM Customers INNER JOIN Orders ON Orders.CustomerID=Customers.ID WHERE Orders.PostedDate IS NULL"		
		SalesOrderSQL = "SELECT *,Orders.ID AS OrderID FROM Orders INNER JOIN Customers ON Orders.CustomerID=Customers.ID"		
		InvoiceSQL = "SELECT * FROM Orders WHERE 1=0"
		TransactionSQL = "SELECT *,Transactions.ID AS TransactionID FROM Transactions INNER JOIN Customers ON Transactions.CustomerID=Customers.ID"	
		
		'Filters (Comment out to allow downloading of duplicate orders/transactions)
		SalesOrderSQL = SalesOrderSQL & " WHERE PostedDate IS NULL"
		TransactionSQL = TransactionSQL & " WHERE PostedDate IS NULL"		
						
		'Document Header
		set xmlDoc = Server.CreateObject("MSXML2.DOMDocument")
		set objRoot = xmlDoc.createElement("Company")
		xmlDoc.documentElement = objRoot
		objRoot.setAttribute "xmlns:xsd","http://www.w3.org/2001/XMLSchema"
		objRoot.setAttribute "xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance"
		
		' Open Connection to database
		OpenConnection()
		
		' Create Products Node
		set Products = xmlDoc.createElement("Products")
		objRoot.appendChild Products
		
		' Create Customers Node
		set Customers = xmlDoc.createElement("Customers")
		objRoot.appendChild Customers
		set CustomersRS = Server.CreateObject("ADODB.Recordset")
		set CustomersRS = conn.execute(CustomerSQL)
		
		' Loop through Customer records
		while not (CustomersRS.EOF)
		
			' Make sure that Company Name has a value
			CompanyName = CustomersRS("BillingCompany")
			if (CompanyName = "") then
				CompanyName = CustomersRS("BillingForename") & " " & CustomersRS("BillingSurname")
			end if
			
			set Customer = xmlDoc.createElement("Customer")
			AppendField Customer,"Id",CustomersRS("ID"),xmlDoc
			AppendField Customer,"CompanyName",CompanyName,xmlDoc
			AppendField Customer,"AccountReference",CustomersRS("Ref"),xmlDoc
			AppendField Customer,"VatNumber",CustomersRS("VatNumber"),xmlDoc
					
			set CustomerBilling = xmlDoc.createElement("CustomerInvoiceAddress")
			AppendField CustomerBilling,"Title",CustomersRS("BillingTitle"),xmlDoc
			AppendField CustomerBilling,"Forename",CustomersRS("BillingForename"),xmlDoc
			AppendField CustomerBilling,"Surname",CustomersRS("BillingSurname"),xmlDoc
			AppendField CustomerBilling,"Company",CompanyName,xmlDoc
			AppendField CustomerBilling,"Address1",CustomersRS("BillingAddress1"),xmlDoc
			AppendField CustomerBilling,"Address2",CustomersRS("BillingAddress2"),xmlDoc
			AppendField CustomerBilling,"Address3",CustomersRS("BillingAddress3"),xmlDoc
			AppendField CustomerBilling,"Town",CustomersRS("BillingTown"),xmlDoc
			AppendField CustomerBilling,"Postcode",CustomersRS("BillingPostcode"),xmlDoc
			AppendField CustomerBilling,"County",CustomersRS("BillingCounty"),xmlDoc
			AppendField CustomerBilling,"Country",CustomersRS("BillingCountry"),xmlDoc
			AppendField CustomerBilling,"Telephone",CustomersRS("BillingTelephone"),xmlDoc
			AppendField CustomerBilling,"Fax",CustomersRS("BillingFax"),xmlDoc
			AppendField CustomerBilling,"Mobile",CustomersRS("BillingMobile"),xmlDoc
			AppendField CustomerBilling,"Email",CustomersRS("BillingEmail"),xmlDoc		
			Customer.appendChild CustomerBilling
			
			' Make sure that Company Name has a value
			CompanyName = CustomersRS("DeliveryCompany")
			if (CompanyName = "") then
				CompanyName = CustomersRS("DeliveryForename") & " " & CustomersRS("DeliverySurname")
			end if
			
			set CustomerDelivery = xmlDoc.createElement("CustomerDeliveryAddress")
			AppendField CustomerDelivery,"Title",CustomersRS("DeliveryTitle"),xmlDoc
			AppendField CustomerDelivery,"Forename",CustomersRS("DeliveryForename"),xmlDoc
			AppendField CustomerDelivery,"Surname",CustomersRS("DeliverySurname"),xmlDoc
			AppendField CustomerDelivery,"Company",CompanyName,xmlDoc
			AppendField CustomerDelivery,"Address1",CustomersRS("DeliveryAddress1"),xmlDoc
			AppendField CustomerDelivery,"Address2",CustomersRS("DeliveryAddress2"),xmlDoc
			AppendField CustomerDelivery,"Address3",CustomersRS("DeliveryAddress3"),xmlDoc
			AppendField CustomerDelivery,"Town",CustomersRS("DeliveryTown"),xmlDoc
			AppendField CustomerDelivery,"Postcode",CustomersRS("DeliveryPostcode"),xmlDoc
			AppendField CustomerDelivery,"County",CustomersRS("DeliveryCounty"),xmlDoc
			AppendField CustomerDelivery,"Country",CustomersRS("DeliveryCountry"),xmlDoc
			AppendField CustomerDelivery,"Telephone",CustomersRS("DeliveryTelephone"),xmlDoc
			AppendField CustomerDelivery,"Fax",CustomersRS("DeliveryFax"),xmlDoc
			AppendField CustomerDelivery,"Mobile",CustomersRS("DeliveryMobile"),xmlDoc
			AppendField CustomerDelivery,"Email",CustomersRS("DeliveryEmail"),xmlDoc
			Customer.appendChild CustomerDelivery
			
			Customers.appendChild Customer		
			CustomersRS.movenext
		wend	
		 
			
		' Create Sales Orders Node
		set SalesOrders = xmlDoc.createElement("SalesOrders")
		objRoot.appendChild SalesOrders
		set SalesOrdersRS = Server.CreateObject("ADODB.Recordset")
		set SalesOrdersRS = conn.execute(SalesOrderSQL)
		
		' Loop through Sales Order records
		while not (SalesOrdersRS.EOF)
			
			' Set current OrderID
			orderID = SalesOrdersRS("OrderID")
		
			'Create SalesOrder and attach subnodes
			set SalesOrder = xmlDoc.createElement("SalesOrder")
			AppendField SalesOrder,"Id",orderID,xmlDoc
			AppendField SalesOrder,"CustomerId",SalesOrdersRS("CustomerID"),xmlDoc
			AppendField SalesOrder,"CustomerOrderNumber",SalesOrdersRS("CustomerID"),xmlDoc
			AppendField SalesOrder,"Notes1",SalesOrdersRS("Notes"),xmlDoc
			AppendField SalesOrder,"Notes2",null,xmlDoc
			AppendField SalesOrder,"Notes3",null,xmlDoc
			AppendField SalesOrder,"AccountReference",SalesOrdersRS("Ref"),xmlDoc
			AppendField SalesOrder,"SalesOrderDate",XSDDate(SalesOrdersRS("OrderDate")),xmlDoc '***** Value needs to be XSDDate ******
			AppendField SalesOrder,"DespatchDate",XSDDate(SalesOrdersRS("DespatchDate")),xmlDoc '***** Value needs to be XSDDate ******
			
			' Create BillingAddress node and attach subnodes
			
			' Make sure that Company Name has a value
			CompanyName = SalesOrdersRS("BillingCompany")
			if (CompanyName = "") then
				CompanyName = SalesOrdersRS("BillingForename") & " " & SalesOrdersRS("BillingSurname")
			end if
			
			set SalesBilling = xmlDoc.createElement("SalesOrderAddress")
			AppendField SalesBilling,"Title",SalesOrdersRS("BillingTitle"),xmlDoc
			AppendField SalesBilling,"Forename",SalesOrdersRS("BillingForename"),xmlDoc
			AppendField SalesBilling,"Surname",SalesOrdersRS("BillingSurname"),xmlDoc
			AppendField SalesBilling,"Company",CompanyName,xmlDoc
			AppendField SalesBilling,"Address1",SalesOrdersRS("BillingAddress1"),xmlDoc
			AppendField SalesBilling,"Address2",SalesOrdersRS("BillingAddress2"),xmlDoc
			AppendField SalesBilling,"Address3",SalesOrdersRS("BillingAddress3"),xmlDoc
			AppendField SalesBilling,"Town",SalesOrdersRS("BillingTown"),xmlDoc
			AppendField SalesBilling,"Postcode",SalesOrdersRS("BillingPostcode"),xmlDoc
			AppendField SalesBilling,"County",SalesOrdersRS("BillingCounty"),xmlDoc
			AppendField SalesBilling,"Country",SalesOrdersRS("BillingCountry"),xmlDoc
			AppendField SalesBilling,"Telephone",SalesOrdersRS("BillingTelephone"),xmlDoc
			AppendField SalesBilling,"Fax",SalesOrdersRS("BillingFax"),xmlDoc
			AppendField SalesBilling,"Mobile",SalesOrdersRS("BillingMobile"),xmlDoc
			AppendField SalesBilling,"Email",SalesOrdersRS("BillingEmail"),xmlDoc		
			SalesOrder.appendChild SalesBilling
			
			' Create DeliveryAddress node and attach subnodes
			
			' Make sure that Company Name has a value
			CompanyName = SalesOrdersRS("DeliveryCompany")
			if (CompanyName = "") then
				CompanyName = SalesOrdersRS("DeliveryForename") & " " & SalesOrdersRS("DeliverySurname")
			end if
			
			set SalesDelivery = xmlDoc.createElement("SalesOrderDeliveryAddress")
			AppendField SalesDelivery,"Title",SalesOrdersRS("DeliveryTitle"),xmlDoc
			AppendField SalesDelivery,"Forename",SalesOrdersRS("DeliveryForename"),xmlDoc
			AppendField SalesDelivery,"Surname",SalesOrdersRS("DeliverySurname"),xmlDoc
			AppendField SalesDelivery,"Company",CompanyName,xmlDoc
			AppendField SalesDelivery,"Address1",SalesOrdersRS("DeliveryAddress1"),xmlDoc
			AppendField SalesDelivery,"Address2",SalesOrdersRS("DeliveryAddress2"),xmlDoc
			AppendField SalesDelivery,"Address3",SalesOrdersRS("DeliveryAddress3"),xmlDoc
			AppendField SalesDelivery,"Town",SalesOrdersRS("DeliveryTown"),xmlDoc
			AppendField SalesDelivery,"Postcode",SalesOrdersRS("DeliveryPostcode"),xmlDoc
			AppendField SalesDelivery,"County",SalesOrdersRS("DeliveryCounty"),xmlDoc
			AppendField SalesDelivery,"Country",SalesOrdersRS("DeliveryCountry"),xmlDoc
			AppendField SalesDelivery,"Telephone",SalesOrdersRS("DeliveryTelephone"),xmlDoc
			AppendField SalesDelivery,"Fax",SalesOrdersRS("DeliveryFax"),xmlDoc
			AppendField SalesDelivery,"Mobile",SalesOrdersRS("DeliveryMobile"),xmlDoc
			AppendField SalesDelivery,"Email",SalesOrdersRS("DeliveryEmail"),xmlDoc
			SalesOrder.appendChild SalesDelivery
			
			' Create OrderItems node and attach subnodes
			set SalesOrderItems = xmlDoc.createElement("SalesOrderItems")
			
			' Create OrderItem SQL
			SalesOrderItemSQL = "SELECT OrderItems.* FROM OrderItems LEFT OUTER JOIN Products ON "
			SalesOrderItemSQL = SalesOrderItemSQL & "OrderItems.ProductID = Products.ID WHERE OrderID = " & orderID
			
			set SalesOrderItemsRS = Server.CreateObject("ADODB.Recordset")
			set SalesOrderItemsRS = conn.execute(SalesOrderItemSQL)
			
			' Loop through Order Items
			while not (SalesOrderItemsRS.EOF)
				set Item = xmlDoc.createElement("Item")
				AppendField Item,"Sku",SalesOrderItemsRS("Sku"),xmlDoc
				AppendField Item,"Name",SalesOrderItemsRS("Name"),xmlDoc
				AppendField Item,"Description",SalesOrderItemsRS("Description"),xmlDoc
				AppendField Item,"QtyOrdered",SalesOrderItemsRS("QtyOrdered"),xmlDoc
				AppendField Item,"UnitPrice",SalesOrderItemsRS("UnitPrice"),xmlDoc
				AppendField Item,"TotalNet",SalesOrderItemsRS("TotalNet"),xmlDoc
				AppendField Item,"TotalTax",SalesOrderItemsRS("TotalTax"),xmlDoc
				SalesOrderItems.appendChild Item
			
				SalesOrderItemsRS.movenext
			wend
			
			SalesOrder.appendChild SalesOrderItems
			
			' Create Carriage node and attach subnodes
			set SalesOrderCarriage = xmlDoc.createElement("Carriage")
			AppendField SalesOrderCarriage,"QtyOrdered",0,xmlDoc
			AppendField SalesOrderCarriage,"UnitPrice",0,xmlDoc
			AppendField SalesOrderCarriage,"TotalNet",0,xmlDoc
			AppendField SalesOrderCarriage,"TotalTax",0,xmlDoc
			AppendField SalesOrderCarriage,"TaxCode",9,xmlDoc
		
			SalesOrder.appendChild SalesOrderCarriage
					
			SalesOrders.appendChild SalesOrder		
			SalesOrdersRS.movenext
		wend
		
		
		' Create Invoices Node
		set Invoices = xmlDoc.createElement("Invoices")
		objRoot.appendChild Invoices
		set InvoicesRS = Server.CreateObject("ADODB.Recordset")
		set InvoicesRS = conn.execute(InvoiceSQL)
		
		' Loop through Invoice records
		while not (InvoicesRS.EOF)
			
			' Assign variables
			invoiceID = InvoicesRS("InvoiceNumber")
		
			'Create Invoice and attach subnodes
			set Invoice = xmlDoc.createElement("Invoice")
			AppendField Invoice,"Id",InvoicesRS("InvoiceNumber"),xmlDoc
			AppendField Invoice,"CustomerId",InvoicesRS("CustomerId"),xmlDoc
			AppendField Invoice,"CustomerOrderNumber",InvoicesRS("CustomerOrderNumber"),xmlDoc
			AppendField Invoice,"AccountReference",InvoicesRS("Ref"),xmlDoc
			AppendField Invoice,"Notes1",InvoicesRS("Notes1"),xmlDoc
			AppendField Invoice,"InvoiceDate",XSDDate(InvoicesRS("InvoiceDate")),xmlDoc  '***** Value needs to be XSDDate ******
			
			' Create BillingAddress node and attach subnodes
			
			' Make sure that Company Name has a value
			CompanyName = InvoicesRS("BillingCompany")
			if (CompanyName = "") then
				CompanyName = InvoicesRS("BillingForename") & " " & InvoicesRS("BillingSurname")
			end if
			
			set InvoiceBilling = xmlDoc.createElement("InvoiceAddress")
			AppendField InvoiceBilling,"Title",InvoicesRS("BillingTitle"),xmlDoc
			AppendField InvoiceBilling,"Forename",InvoicesRS("BillingForename"),xmlDoc
			AppendField InvoiceBilling,"Surname",InvoicesRS("BillingSurname"),xmlDoc
			AppendField InvoiceBilling,"Company",CompanyName,xmlDoc
			AppendField InvoiceBilling,"Address1",InvoicesRS("BillingAddress1"),xmlDoc
			AppendField InvoiceBilling,"Address2",InvoicesRS("BillingAddress2"),xmlDoc
			AppendField InvoiceBilling,"Address3",null,xmlDoc
			AppendField InvoiceBilling,"Town",InvoicesRS("BillingTown"),xmlDoc
			AppendField InvoiceBilling,"Postcode",InvoicesRS("BillingPostcode"),xmlDoc
			AppendField InvoiceBilling,"County",InvoicesRS("BillingCounty"),xmlDoc
			AppendField InvoiceBilling,"Country",InvoicesRS("BillingCountry"),xmlDoc
			AppendField InvoiceBilling,"Telephone",InvoicesRS("BillingTelephone"),xmlDoc
			AppendField InvoiceBilling,"Fax",null,xmlDoc
			AppendField InvoiceBilling,"Mobile",InvoicesRS("BillingMobile"),xmlDoc
			AppendField InvoiceBilling,"Email",InvoicesRS("BillingEmail"),xmlDoc		
			Invoice.appendChild InvoiceBilling
			
			' Create DeliveryAddress node and attach subnodes
			
			' Make sure that Company Name has a value
			CompanyName = InvoicesRS("DeliveryCompany")
			if (CompanyName = "") then
				CompanyName = InvoicesRS("DeliveryForename") & " " & InvoicesRS("DeliverySurname")
			end if
			
			set InvoiceDelivery = xmlDoc.createElement("InvoiceDeliveryAddress")
			AppendField InvoiceDelivery,"Title",InvoicesRS("DeliveryTitle"),xmlDoc
			AppendField InvoiceDelivery,"Forename",InvoicesRS("DeliveryForename"),xmlDoc
			AppendField InvoiceDelivery,"Surname",InvoicesRS("DeliverySurname"),xmlDoc
			AppendField InvoiceDelivery,"Company",CompanyName,xmlDoc
			AppendField InvoiceDelivery,"Address1",InvoicesRS("DeliveryAddress1"),xmlDoc
			AppendField InvoiceDelivery,"Address2",InvoicesRS("DeliveryAddress2"),xmlDoc
			AppendField InvoiceDelivery,"Address3",null,xmlDoc
			AppendField InvoiceDelivery,"Town",InvoicesRS("DeliveryTown"),xmlDoc
			AppendField InvoiceDelivery,"Postcode",InvoicesRS("DeliveryPostcode"),xmlDoc
			AppendField InvoiceDelivery,"County",InvoicesRS("DeliveryCounty"),xmlDoc
			AppendField InvoiceDelivery,"Country",InvoicesRS("DeliveryCountry"),xmlDoc
			AppendField InvoiceDelivery,"Telephone",InvoicesRS("DeliveryTelephone"),xmlDoc
			AppendField InvoiceDelivery,"Fax",null,xmlDoc
			AppendField InvoiceDelivery,"Mobile",null,xmlDoc
			AppendField InvoiceDelivery,"Email",InvoicesRS("DeliveryEmail"),xmlDoc
			Invoice.appendChild InvoiceDelivery
			
			' Create OrderItems node and attach subnodes
			set InvoiceItems = xmlDoc.createElement("InvoiceItems")
			
			' Create OrderItem SQL
			InvoiceItemSQL = "SELECT OrderItems.*,Notes AS Comments,Products.StockCode AS Sku FROM OrderItems LEFT OUTER JOIN Products ON "
			InvoiceItemSQL = InvoiceItemSQL & "OrderItems.ProductID = Products.ID WHERE OrderID = " & invoiceID
	
			set InvoiceItemsRS = Server.CreateObject("ADODB.Recordset")
			set InvoiceItemsRS = conn.execute(InvoiceItemSQL)
			
			' Loop though Invoice Items
			while not (InvoiceItemsRS.EOF)
				set Item = xmlDoc.createElement("Item")
				AppendField Item,"Sku",InvoiceItemsRS("Sku"),xmlDoc
				AppendField Item,"Name",InvoiceItemsRS("Name"),xmlDoc
				AppendField Item,"Description",InvoiceItemsRS("Description"),xmlDoc
				AppendField Item,"Comments",InvoiceItemsRS("Comments"),xmlDoc
				AppendField Item,"QtyOrdered",InvoiceItemsRS("QtyOrdered"),xmlDoc
				AppendField Item,"UnitPrice",InvoiceItemsRS("UnitPrice"),xmlDoc
				AppendField Item,"TotalNet",InvoiceItemsRS("TotalNet"),xmlDoc
				AppendField Item,"TotalTax",InvoiceItemsRS("TotalTax"),xmlDoc
				InvoiceItems.appendChild Item
			
				InvoiceItemsRS.movenext
			wend
			
			Invoice.appendChild InvoiceItems
			
			' Create Carriage node and attach subnodes
			set InvoiceCarriage = xmlDoc.createElement("Carriage")
			AppendField InvoiceCarriage,"QtyOrdered",0,xmlDoc
			AppendField InvoiceCarriage,"UnitPrice",0,xmlDoc
			AppendField InvoiceCarriage,"TotalNet",0,xmlDoc
			AppendField InvoiceCarriage,"TotalTax",0,xmlDoc
			AppendField InvoiceCarriage,"TaxCode",9,xmlDoc
		
			Invoice.appendChild InvoiceCarriage
					
			Invoices.appendChild Invoice		
			InvoicesRS.movenext
		wend
		
		' Create Product Groups Node
		set ProductGroups = xmlDoc.createElement("ProductGroups")
		objRoot.appendChild ProductGroups
		
		' Create Transactions Node
		set Transactions = xmlDoc.createElement("Transactions")
		objRoot.appendChild Transactions
		set TransactionRS = Server.CreateObject("ADODB.Recordset")
		set TransactionRS = conn.execute(TransactionSQL)
		
		' Loop through Transaction records
		while not (TransactionRS.EOF)	
			set Transaction = xmlDoc.createElement("Transaction")
			AppendField Transaction,"Id",TransactionRS("TransactionID"),xmlDoc
			AppendField Transaction,"AccountReference",TransactionRS("Ref"),xmlDoc
			AppendField Transaction,"TransactionDate",XSDDate(TransactionRS("TransactionDate")),xmlDoc
			AppendField Transaction,"Reference",TransactionRS("Reference"),xmlDoc
			AppendField Transaction,"Details",TransactionRS("Details"),xmlDoc
			AppendField Transaction,"NetAmount",TransactionRS("TotalNet"),xmlDoc
			AppendField Transaction,"TaxAmount",TransactionRS("TotalTax"),xmlDoc
			
			Transactions.appendChild Transaction		
			TransactionRS.movenext
		wend
		
		' Close Connection to database
		CloseConnection()
		
		' Write document
		xmlText = xmlText & "<?xml version=""1.0"" encoding=""UTF-8""?>"
		xmlText = xmlText & xmlDoc.xml
		set xml = Server.CreateObject("Microsoft.XMLDOM")
		xml.async = false
		xml.loadXML(xmlText)
		Response.ContentType = "text/xml"
		Response.Charset = "utf-8"
		xml.save Response
	end if
%>