using System;
using System.Data.OleDb;
using System.IO;
using System.Xml;

namespace ConnectSamples
{
	/// <summary>
	/// Summary description for Download.
	/// </summary>
	public class Download : System.Web.UI.Page
	{
		#region Private Members

		#endregion

		#region Methods

		private void UpdateRecords(string input)
		{
			XmlDocument document = new XmlDocument();
			try
			{
				// Load in xml data
				document.LoadXml(input);

				//TODO: Validate xml against XSD

				// Retrieve posted Customers
				XmlNodeList customers = document.SelectNodes("Company/Customers/Customer");
				foreach (XmlNode customer in customers)
				{
					string customerId = customer.SelectSingleNode("Id").InnerText;
					string customerRef = customer.SelectSingleNode("AccountReference").InnerText;
					
					Utilities.ExecuteQuery("UPDATE Customers SET Ref = '" + customerRef + "' WHERE ID=" + customerId);
				}

				// Retrieve posted Sales Orders
				XmlNodeList salesOrders = document.SelectNodes("Company/SalesOrders/SalesOrder");
				string salesOrderNos = String.Empty;
				foreach (XmlNode salesOrder in salesOrders)
				{
					salesOrderNos += salesOrder.SelectSingleNode("Id").InnerText + ",";
				}

				// Update posted Sales Orders
				if (salesOrderNos.Length > 0)
				{
					// Remove extra comma from end of string
					salesOrderNos = salesOrderNos.Substring(0,salesOrderNos.Length-1);
					Utilities.ExecuteQuery("UPDATE Orders SET PostedDate = '" + DateTime.Now + "' WHERE ID IN (" + salesOrderNos + ")");
				}


				// Retrieve posted Transactions
				XmlNodeList transactions = document.SelectNodes("Company/Transactions/Transaction");
				string transactionNos = String.Empty;
				foreach (XmlNode transaction in transactions)
				{
					transactionNos += transaction.SelectSingleNode("Id").InnerText + ",";
				}

				// Update posted Transactions
				if (transactionNos.Length > 0)
				{
					// Remove extra comma from end of string
					transactionNos = transactionNos.Substring(0,transactionNos.Length-1);
					Utilities.ExecuteQuery("UPDATE Transactions SET PostedDate = '" + DateTime.Now + "' WHERE ID IN (" + transactionNos + ")");
				}

				// Close database connection
				Utilities.CloseConnection();
				
			}
			catch(Exception)
			{
				// XML could not be loaded
			}
		}

		private void RetrieveData()
		{
			// Create XML document
			XmlDocument document = new XmlDocument();
			document.AppendChild(document.CreateXmlDeclaration("1.0", "UTF-8", "no"));
			XmlNode root = document.CreateElement("Company");
			document.AppendChild(root);

			// Append data
			AppendCustomers(document, root);
			AppendSalesOrders(document, root);
			AppendInvoices(document, root);
			AppendTransactions(document, root);

			// Write data out to response stream
			Response.ContentType = "text/xml";
			document.Save(Response.OutputStream);
			Response.End();
		}

		private void AppendCustomers(XmlDocument document, XmlNode parent)
		{
			// Append customers node
			XmlNode customers = document.CreateElement("Customers");
			parent.AppendChild(customers);

			string query = "SELECT * FROM Customers INNER JOIN Orders ON Orders.CustomerID=Customers.ID WHERE PostedDate IS NULL";
			OleDbDataReader customerReader = Utilities.ExecuteReader(query);
			while (customerReader.Read())
			{
				// Make sure that Company Name has a value
				string companyName = customerReader["BillingCompany"].ToString();
				string BillingForename = customerReader["BillingForename"].ToString();
				string BillingSurname = customerReader["BillingSurname"].ToString();
				if (companyName == null || companyName.Length == 0)
				{
					companyName = BillingForename + " " + BillingSurname;
				}
				
				// Append Customer node and properties
				XmlNode customer = document.CreateElement("Customer");
				customers.AppendChild(customer);
				Utilities.AppendElement("Id", customerReader["ID"].ToString(), customer, document);
				Utilities.AppendElement("CompanyName", companyName, customer, document);
				Utilities.AppendElement("AccountReference", customerReader["Ref"].ToString(), customer, document);
				Utilities.AppendElement("VatNumber", customerReader["VatNumber"].ToString(), customer, document);

				// Append CustomerInvoiceAddress node and properties
				XmlNode billing = document.CreateElement("CustomerInvoiceAddress");
				customer.AppendChild(billing);
				Utilities.AppendElement("Title", customerReader["BillingTitle"].ToString(), billing, document);
				Utilities.AppendElement("Forename", customerReader["BillingForename"].ToString(), billing, document);
				Utilities.AppendElement("Surname", customerReader["BillingSurname"].ToString(), billing, document);
				Utilities.AppendElement("Company", companyName, billing, document);
				Utilities.AppendElement("Address1", customerReader["BillingAddress1"].ToString(), billing, document);
				Utilities.AppendElement("Address2", customerReader["BillingAddress2"].ToString(), billing, document);
				Utilities.AppendElement("Address3", customerReader["BillingAddress3"].ToString(), billing, document);
				Utilities.AppendElement("Town", customerReader["BillingTown"].ToString(), billing, document);
				Utilities.AppendElement("Postcode", customerReader["BillingPostcode"].ToString(), billing, document);
				Utilities.AppendElement("County", customerReader["BillingCounty"].ToString(), billing, document);
				Utilities.AppendElement("Country", customerReader["BillingCountry"].ToString(), billing, document);
				Utilities.AppendElement("Telephone", customerReader["BillingTelephone"].ToString(), billing, document);
				Utilities.AppendElement("Fax", customerReader["BillingFax"].ToString(), billing, document);
				Utilities.AppendElement("Mobile", customerReader["BillingMobile"].ToString(), billing, document);
				Utilities.AppendElement("Email", customerReader["BillingEmail"].ToString(), billing, document);

				// Append CustomerDeliveryAddress node and properties
				XmlNode delivery = document.CreateElement("CustomerDeliveryAddress");
				customer.AppendChild(delivery);
				Utilities.AppendElement("Title", customerReader["DeliveryTitle"].ToString(), delivery, document);
				Utilities.AppendElement("Forename", customerReader["DeliveryForename"].ToString(), delivery, document);
				Utilities.AppendElement("Surname", customerReader["DeliverySurname"].ToString(), delivery, document);
				Utilities.AppendElement("Company", companyName, delivery, document);
				Utilities.AppendElement("Address1", customerReader["DeliveryAddress1"].ToString(), delivery, document);
				Utilities.AppendElement("Address2", customerReader["DeliveryAddress2"].ToString(), delivery, document);
				Utilities.AppendElement("Address3", customerReader["DeliveryAddress3"].ToString(), delivery, document);
				Utilities.AppendElement("Town", customerReader["DeliveryTown"].ToString(), delivery, document);
				Utilities.AppendElement("Postcode", customerReader["DeliveryPostcode"].ToString(), delivery, document);
				Utilities.AppendElement("County", customerReader["DeliveryCounty"].ToString(), delivery, document);
				Utilities.AppendElement("Country", customerReader["DeliveryCountry"].ToString(), delivery, document);
				Utilities.AppendElement("Telephone", customerReader["DeliveryTelephone"].ToString(), delivery, document);
				Utilities.AppendElement("Fax", customerReader["DeliveryFax"].ToString(), delivery, document);
				Utilities.AppendElement("Mobile", customerReader["DeliveryMobile"].ToString(), delivery, document);
				Utilities.AppendElement("Email", customerReader["DeliveryEmail"].ToString(), delivery, document);
			}

			// Close reader
			customerReader.Close();
		}

		private void AppendSalesOrders(XmlDocument document, XmlNode parent)
		{
			// Append sales orders node
			XmlNode salesOrders = document.CreateElement("SalesOrders");
			parent.AppendChild(salesOrders);

			string query = "SELECT *,Orders.ID AS OrderID FROM Orders INNER JOIN Customers ON Orders.CustomerID=Customers.ID WHERE PostedDate IS NULL";
			OleDbDataReader salesOrderReader = Utilities.ExecuteReader(query);
			while (salesOrderReader.Read())
			{	
				// Get current Order ID
				int orderID = Convert.ToInt32(salesOrderReader["OrderID"]);

				// Append SalesOrder node and properties
				XmlNode salesOrder = document.CreateElement("SalesOrder");
				salesOrders.AppendChild(salesOrder);
				Utilities.AppendElement("Id", orderID.ToString(), salesOrder, document);
				Utilities.AppendElement("CustomerId", salesOrderReader["CustomerID"].ToString(), salesOrder, document);
				Utilities.AppendElement("CustomerOrderNumber", salesOrderReader["CustomerID"].ToString(), salesOrder, document);
				Utilities.AppendElement("Notes1", salesOrderReader["Notes"].ToString(), salesOrder, document);
				Utilities.AppendElement("AccountReference", salesOrderReader["Ref"].ToString(), salesOrder, document);
				Utilities.AppendElement("SalesOrderDate", Utilities.GetXmlDate(Convert.ToDateTime(salesOrderReader["OrderDate"])).ToString(), salesOrder, document);
				Utilities.AppendElement("DespatchDate", Utilities.GetXmlDate(Convert.ToDateTime(salesOrderReader["DespatchDate"])).ToString(), salesOrder, document);


				// Make sure that Company Name has a value
				string companyName = salesOrderReader["BillingCompany"].ToString();
				string BillingForename = salesOrderReader["BillingForename"].ToString();
				string BillingSurname = salesOrderReader["BillingSurname"].ToString();
				if (companyName == null || companyName.Length == 0)
				{
					companyName = BillingForename + " " + BillingSurname;
				}

				// Append SalesOrderAddress node and properties
				XmlNode billing = document.CreateElement("SalesOrderAddress");
				salesOrder.AppendChild(billing);
				Utilities.AppendElement("Title", salesOrderReader["BillingTitle"].ToString(), billing, document);
				Utilities.AppendElement("Forename", salesOrderReader["BillingForename"].ToString(), billing, document);
				Utilities.AppendElement("Surname", salesOrderReader["BillingSurname"].ToString(), billing, document);
				Utilities.AppendElement("Company", companyName, billing, document);
				Utilities.AppendElement("Address1", salesOrderReader["BillingAddress1"].ToString(), billing, document);
				Utilities.AppendElement("Address2", salesOrderReader["BillingAddress2"].ToString(), billing, document);
				Utilities.AppendElement("Address3", salesOrderReader["BillingAddress3"].ToString(), billing, document);
				Utilities.AppendElement("Town", salesOrderReader["BillingTown"].ToString(), billing, document);
				Utilities.AppendElement("Postcode", salesOrderReader["BillingPostcode"].ToString(), billing, document);
				Utilities.AppendElement("County", salesOrderReader["BillingCounty"].ToString(), billing, document);
				Utilities.AppendElement("Country", salesOrderReader["BillingCountry"].ToString(), billing, document);
				Utilities.AppendElement("Telephone", salesOrderReader["BillingTelephone"].ToString(), billing, document);
				Utilities.AppendElement("Fax", salesOrderReader["BillingFax"].ToString(), billing, document);
				Utilities.AppendElement("Mobile", salesOrderReader["BillingMobile"].ToString(), billing, document);
				Utilities.AppendElement("Email", salesOrderReader["BillingEmail"].ToString(), billing, document);

				// Append SalesOrderDeliveryAddress node and properties
				XmlNode delivery = document.CreateElement("SalesOrderDeliveryAddress");
				salesOrder.AppendChild(delivery);
				Utilities.AppendElement("Title", salesOrderReader["DeliveryTitle"].ToString(), delivery, document);
				Utilities.AppendElement("Forename", salesOrderReader["DeliveryForename"].ToString(), delivery, document);
				Utilities.AppendElement("Surname", salesOrderReader["DeliverySurname"].ToString(), delivery, document);
				Utilities.AppendElement("Company", companyName, delivery, document);
				Utilities.AppendElement("Address1", salesOrderReader["DeliveryAddress1"].ToString(), delivery, document);
				Utilities.AppendElement("Address2", salesOrderReader["DeliveryAddress2"].ToString(), delivery, document);
				Utilities.AppendElement("Address3", salesOrderReader["DeliveryAddress3"].ToString(), delivery, document);
				Utilities.AppendElement("Town", salesOrderReader["DeliveryTown"].ToString(), delivery, document);
				Utilities.AppendElement("Postcode", salesOrderReader["DeliveryPostcode"].ToString(), delivery, document);
				Utilities.AppendElement("County", salesOrderReader["DeliveryCounty"].ToString(), delivery, document);
				Utilities.AppendElement("Country", salesOrderReader["DeliveryCountry"].ToString(), delivery, document);
				Utilities.AppendElement("Telephone", salesOrderReader["DeliveryTelephone"].ToString(), delivery, document);
				Utilities.AppendElement("Fax", salesOrderReader["DeliveryFax"].ToString(), delivery, document);
				Utilities.AppendElement("Mobile", salesOrderReader["DeliveryMobile"].ToString(), delivery, document);
				Utilities.AppendElement("Email", salesOrderReader["DeliveryEmail"].ToString(), delivery, document);

				// Append SalesOrderItems node and properties
				XmlNode salesOrderItems = document.CreateElement("SalesOrderItems");
				salesOrder.AppendChild(salesOrderItems);

				query = "SELECT OrderItems.* FROM OrderItems LEFT OUTER JOIN Products ON OrderItems.ProductID = Products.ID WHERE OrderID=" + orderID.ToString();
				// Create a new connection
				OleDbConnection connection = Utilities.OpenNewConnection();
				OleDbDataReader salesOrderItemReader = Utilities.ExecuteReader(connection,query);
				while (salesOrderItemReader.Read())
				{
					// Append Item nodes and properties
					XmlNode item = document.CreateElement("Item");
					salesOrderItems.AppendChild(item);
					Utilities.AppendElement("Sku", salesOrderItemReader["Sku"].ToString(), item, document);
					Utilities.AppendElement("Name", salesOrderItemReader["Name"].ToString(), item, document);
					Utilities.AppendElement("Description", salesOrderItemReader["Description"].ToString(), item, document);
					Utilities.AppendElement("QtyOrdered", salesOrderItemReader["QtyOrdered"].ToString(), item, document);
					Utilities.AppendElement("UnitPrice", salesOrderItemReader["UnitPrice"].ToString(), item, document);
					Utilities.AppendElement("Reference", item, document);
					Utilities.AppendElement("TotalNet", salesOrderItemReader["TotalNet"].ToString(), item, document);
					Utilities.AppendElement("TotalTax", salesOrderItemReader["TotalTax"].ToString(), item, document);
				}

				// Close reader
				salesOrderItemReader.Close();

				// Append Carriage node and properties
				XmlNode carriage = document.CreateElement("Carriage");
				salesOrder.AppendChild(carriage);
				Utilities.AppendElement("QtyOrdered", "0", carriage, document);
				Utilities.AppendElement("UnitPrice", "0", carriage, document);
				Utilities.AppendElement("TotalNet", "0", carriage, document);
				Utilities.AppendElement("TotalTax", "0", carriage, document);
				Utilities.AppendElement("TaxCode", "9", carriage, document);
			}

			// Close reader
			salesOrderReader.Close();
		}

		private void AppendInvoices(XmlDocument document, XmlNode parent)
		{
			//TODO		
		}

		private void AppendTransactions(XmlDocument document, XmlNode parent)
		{
			// Append transactions node
			XmlNode transactions = document.CreateElement("Transactions");
			parent.AppendChild(transactions);

			string query = "SELECT *,Transactions.ID AS TransactionID FROM Transactions INNER JOIN Customers ON Transactions.CustomerID=Customers.ID WHERE PostedDate IS NULL";
			OleDbDataReader transactionsReader = Utilities.ExecuteReader(query);
			while (transactionsReader.Read())
			{		
				// Append Transaction node and properties
				XmlNode transaction = document.CreateElement("Transaction");
				transactions.AppendChild(transaction);
				Utilities.AppendElement("Id", transactionsReader["TransactionID"].ToString(), transaction, document);
				Utilities.AppendElement("TransactionType", "SalesReceiptOnAccount", transaction, document);
				Utilities.AppendElement("AccountReference", transactionsReader["Ref"].ToString(), transaction, document);
				Utilities.AppendElement("TransactionDate", Utilities.GetXmlDate(Convert.ToDateTime(transactionsReader["TransactionDate"])).ToString(), transaction, document);
				Utilities.AppendElement("Reference", transactionsReader["Reference"].ToString(), transaction, document);
				Utilities.AppendElement("PaymentReference", transaction, document);
				Utilities.AppendElement("Details", transactionsReader["Details"].ToString(), transaction, document);
				Utilities.AppendElement("NetAmount", transactionsReader["TotalNet"].ToString(), transaction, document);
				Utilities.AppendElement("TaxAmount", transactionsReader["TotalTax"].ToString(), transaction, document);
			}

			// Close reader
			transactionsReader.Close();			
		}

		#endregion

		#region Event Methods

		private void Page_Load(object sender, System.EventArgs e)
		{
			// Read XML posted via HTTP
			string data = String.Empty;
			Page.Response.ContentType = "text/xml";
			using (StreamReader reader = new StreamReader(Page.Request.InputStream))
			{
				data = reader.ReadToEnd();
			}

			// Check if it is a postback
			if (data.Length > 0)
			{
				UpdateRecords(data);
			}
			else
			{
				RetrieveData();
			}
		}

		#endregion

		#region Web Form Designer generated code
		override protected void OnInit(EventArgs e)
		{
			//
			// CODEGEN: This call is required by the ASP.NET Web Form Designer.
			//
			InitializeComponent();
			base.OnInit(e);
		}
		
		/// <summary>
		/// Required method for Designer support - do not modify
		/// the contents of this method with the code editor.
		/// </summary>
		private void InitializeComponent()
		{    
			this.Load += new System.EventHandler(this.Page_Load);
		}
		#endregion
	}
}
