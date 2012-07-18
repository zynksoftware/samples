<%
	' COPYRIGHT (C) 2005, Internetware Ltd.
	' For Internetware Connect support, please see our contact details 
	' at http://www.internetware.co.uk
	
	'****** System Variables *******	
	
	Dim conn,connString
	
	'connString = "provider=SQLOLEDB;network=DBMSSOCN;uid=USERNAME;pwd=PASSWORD;server=SERVER;database=DATABASE" 'SQL Server
	connString = "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=" & Server.MapPath("Connect.mdb") 'Access
	
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
		
	'****** Main Code *******
	' Check that there is some data
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
		
		' Retrieve Product Groups
		Set nodeList = objDoc.SelectNodes("Company/ProductGroups/ProductGroup")
		for each Node in nodeList
			ProductCategory = CleanXML(Node.SelectSingleNode("Name").Text)
			Reference = Node.SelectSingleNode("Reference").Text
			
			' Not import empty product categories
			if (LEN(ProductCategory) > 0) then			
				' Check if record exists
				ProductCategoryID = 0
				SQL = "SELECT ID FROM ProductCategories WHERE Ref = '" & Reference & "'"
				set rsCount = conn.execute(SQL)
				if not (rsCount.EOF) then
					ProductCategoryID = rsCount("ID")
				end if
				
				' Insert or Update product groups
				if (ProductCategoryID > 0) then
					SQL = "UPDATE ProductCategories SET Name='" & ProductCategory & "',Ref=" & Reference & " WHERE ID = " & ProductCategoryID
				else
					SQL = "INSERT INTO ProductCategories (Name,Ref) VALUES ('" & ProductCategory & "','" & Reference & "')"
				end if
				
				' Execute query
				conn.execute(SQL)
			end if		
		next
				
				
		' Retrieve Products
		Set nodeList = objDoc.SelectNodes("Company/Products/Product")
		for each Node in nodeList
			Sku = CleanXML(Node.SelectSingleNode("Sku").Text)
			ProductName = CleanXML(Node.SelectSingleNode("Name").Text)
			ProductDescription = CleanXML(Node.SelectSingleNode("Description").Text)
			Details = CleanXML(Node.SelectSingleNode("LongDescription").Text)
			SalePrice = Node.SelectSingleNode("SalePrice").Text
			UnitWeight = Node.SelectSingleNode("UnitWeight").Text
			QtyInStock = Node.SelectSingleNode("QtyInStock").Text
			CategoryRef = Node.SelectSingleNode("GroupCode").Text
			Publish = Node.SelectSingleNode("Publish").Text
			if (Publish = "true") then
				Publish = 1
			else
				Publish = 0
			end if
			SpecialOffer = Node.SelectSingleNode("SpecialOffer").Text
			if (SpecialOffer = "true") then
				SpecialOffer = 1
			else
				SpecialOffer = 0
			end if
			
			' Check if record exists
			ProductID = 0
			SQL = "SELECT ID FROM Products WHERE Sku = '" & Sku & "'"
			set rsCount = conn.execute(SQL)
			if not (rsCount.EOF) then
				ProductID = rsCount("ID")
			end if
			
			' Insert or Update product groups
			if (ProductID > 0) then
				SQL = "UPDATE Products SET Name='" & ProductName & "',Description='" & ProductDescription & "',Details='" & Details & "',"
				SQL = SQL & "UnitPrice=" & SalePrice & ",UnitWeight=" & UnitWeight & ",QtyInStock=" & QtyInStock & ",Publish=" & Publish & ","
				SQL = SQL & "SpecialOffer=" & SpecialOffer & ",ProductCategoryRef='" & CategoryRef & "' WHERE ID = " & ProductID
			else
				SQL = "INSERT INTO Products (Sku,Name,Description,Details,UnitPrice,UnitWeight,QtyInStock,ProductCategoryRef,Publish,SpecialOffer) "
				SQL = SQL & "VALUES ('" & Sku & "','" & ProductName & "','" & ProductDescription & "','" & Details & "'," & SalePrice & "," & UnitWeight & ","
				SQL = SQL & QtyInStock & "," & CategoryRef & "," & Publish & "," & SpecialOffer & ")"
			end if
			
			' Execute query
			conn.execute(SQL)
		next
		
		' Close Connection to database
		CloseConnection()
	end if
%>