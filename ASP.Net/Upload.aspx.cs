using System;
using System.Data.OleDb;
using System.IO;
using System.Xml;

namespace ConnectSamples
{
	/// <summary>
	/// Summary description for Upload.
	/// </summary>
	public class Upload : System.Web.UI.Page
	{
		#region Methods

		private void UpdateRecords(string input)
		{
			XmlDocument document = new XmlDocument();
			try
			{
				// Load in xml data
				document.LoadXml(input);

				//TODO: Validate xml against XSD

				UpdateProductGroups(document);
				UpdateProducts(document);

				// Close database connection
				Utilities.CloseConnection();
			}
			catch(Exception ex)
			{
				Utilities.StringToFile(Server.MapPath("Upload.txt"),ex.Message + Environment.NewLine,true);
			}
		}

		private void UpdateProductGroups(XmlDocument document)
		{
			try
			{
				// Retrieve Product Groups
				XmlNodeList productGroups = document.SelectNodes("Company/ProductGroups/ProductGroup");
				foreach (XmlNode productGroup in productGroups)
				{
					string productCategory = productGroup.SelectSingleNode("Name").InnerText;
					string reference = productGroup.SelectSingleNode("Reference").InnerText;

					// Don't import empty product categories
					if (productCategory != null && productCategory.Length > 0)
					{
						// Check if product category already exists
						int productCategoryID = 0;
						string query = "SELECT ID FROM ProductCategories WHERE Ref = '" + reference + "'";
						Utilities.StringToFile(Server.MapPath("upload.text"),query + Environment.NewLine,true);
						OleDbDataReader productGroupReader = Utilities.ExecuteReader(query);
						
						// Read the first row
						if (productGroupReader.HasRows)
						{
							productGroupReader.Read();
							productCategoryID = Convert.ToInt32(productGroupReader["ID"]);
						}

						// Close reader
						productGroupReader.Close();

						// Create new or update product category
						if (productCategoryID > 0)
						{
							query = "UPDATE ProductCategories SET Name='" + productCategory + "',Ref=" + reference + " WHERE ID = " + productCategoryID.ToString();
						}
						else
						{
							query = "INSERT INTO ProductCategories (Name,Ref) VALUES ('" + productCategory + "','" + reference + "')";
						}

						// Execute query
						Utilities.StringToFile(Server.MapPath("upload.text"),query + Environment.NewLine,true);
						Utilities.ExecuteQuery(query);
					}
				}
			}
			catch (Exception ex)
			{
				Utilities.StringToFile(Server.MapPath("Upload.txt"),ex.Message + Environment.NewLine,true);
			}
		}

		private void UpdateProducts(XmlDocument document)
		{
			try
			{
				// Retrieve Products
				XmlNodeList products = document.SelectNodes("Company/Products/Product");
				foreach (XmlNode product in products)
				{
					string sku = Utilities.CleanXml(product.SelectSingleNode("Sku").InnerText);
					string productName = Utilities.CleanXml(product.SelectSingleNode("Name").InnerText);
					string productDescription = Utilities.CleanXml(product.SelectSingleNode("Description").InnerText);
					string details = Utilities.CleanXml(product.SelectSingleNode("LongDescription").InnerText);
					string salePrice = product.SelectSingleNode("SalePrice").InnerText;
					string unitWeight = product.SelectSingleNode("UnitWeight").InnerText;
					string qtyInStock = product.SelectSingleNode("QtyInStock").InnerText;
					string categoryRef = product.SelectSingleNode("GroupCode").InnerText;
					string publish = product.SelectSingleNode("Publish").InnerText;
					publish = (publish == "true") ? "1" : "0";
					string specialOffer = product.SelectSingleNode("SpecialOffer").InnerText;
					specialOffer = (specialOffer == "true") ? "1" : "0";

					// Check if product already exists
					int productID = 0;
					string query = "SELECT ID FROM Products WHERE Sku = '" + sku + "'";
					OleDbDataReader productReader = Utilities.ExecuteReader(query);
							
					// Read the first row
					if (productReader.HasRows)
					{
						productReader.Read();
						productID = Convert.ToInt32(productReader["ID"]);
					}

					// Close reader
					productReader.Close();

					// Create new or update product category
					if (productID > 0)
					{
						query = "UPDATE Products SET Name='" + productName + "',Description='" + productDescription + "',Details='" + details + "',";
						query += "UnitPrice=" + salePrice + ",UnitWeight=" + unitWeight + ",QtyInStock=" + qtyInStock + ",Publish=" + publish + ",";
						query += "SpecialOffer=" + specialOffer + ",ProductCategoryRef='" + categoryRef + "' WHERE ID = " + productID;
					}
					else
					{
						query = "INSERT INTO Products (Sku,Name,Description,Details,UnitPrice,UnitWeight,QtyInStock,ProductCategoryRef,Publish,SpecialOffer) ";
						query += "VALUES ('" + sku + "','" + productName + "','" + productDescription + "','" + details + "'," + salePrice + "," + unitWeight + ",";
						query += qtyInStock + "," + categoryRef + "," + publish + "," + specialOffer + ")";
					}

					// Execute query
					Utilities.ExecuteQuery(query);
				}
			}
			catch (Exception ex)
			{
				Utilities.StringToFile(Server.MapPath("Upload.txt"),ex.Message + Environment.NewLine,true);
			}	
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
