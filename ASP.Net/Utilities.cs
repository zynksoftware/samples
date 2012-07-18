using System;
using System.Data;
using System.Data.OleDb;
using System.IO;
using System.Web;
using System.Xml;

namespace ConnectSamples
{
	/// <summary>
	/// Summary description for Utilities.
	/// </summary>
	public class Utilities
	{
		#region Public Members

		public static string connectionString;
		public static OleDbConnection connection;

		#endregion
		
		#region Constructors

		static Utilities()
		{
			connectionString = "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=" + HttpContext.Current.Server.MapPath("Connect.mdb");
		}

		#endregion

		#region Methods

		/// <summary>
		/// Opens the default connection to the database
		/// </summary>
		public static void OpenConnection()
		{
			// Open Connection
			connection = new OleDbConnection(connectionString);
			connection.Open();
		}

		/// <summary>
		/// Opens a new connection to the database
		/// </summary>
		/// <returns></returns>
		public static OleDbConnection OpenNewConnection()
		{
			// Open Connection
			OleDbConnection connection = new OleDbConnection(connectionString);
			connection.Open();
			return connection;
		}

		/// <summary>
		/// Closes the default connection to the database
		/// </summary>
		public static void CloseConnection()
		{
			// Close connection if not closed
			if (connection != null && connection.State != ConnectionState.Closed)
			{
				connection.Close();
			}
		}

		public static void ExecuteQuery(string query)
		{
			// Open connection if currently closed
			if (connection == null || connection.State == ConnectionState.Closed)
			{
				OpenConnection();
			}

			// Execute query
			OleDbCommand command = new OleDbCommand(query, connection);
			command.ExecuteNonQuery();
		}

		public static OleDbDataReader ExecuteReader(string query)
		{
			// Open connection if currently closed
			if (connection == null || connection.State == ConnectionState.Closed)
			{
				OpenConnection();
			}

			// Execute query
			OleDbCommand command = new OleDbCommand(query, connection);
			return command.ExecuteReader();
		}

		public static OleDbDataReader ExecuteReader(OleDbConnection connection, string query)
		{
			// Execute query
			OleDbCommand command = new OleDbCommand(query, connection);
			return command.ExecuteReader();
		}

		/// <summary>
		/// Creates an element and appends it to the parent
		/// </summary>
		/// <param name="name">Name of the element</param>
		/// <param name="value">Value to place</param>
		/// <param name="parent">Node to append to</param>
		/// <param name="document">Document that will create the node</param>
		/// <returns>The node created</returns>
		public static XmlElement AppendElement(string name, string value, XmlNode parent, XmlDocument document)
		{
			XmlElement element = document.CreateElement(name);
			
			if (value != "")
			{
				XmlText textValue = document.CreateTextNode(value);
				element.AppendChild(textValue);
			}
			
			parent.AppendChild(element);
			return element;
		}

		/// <summary>
		/// Creates an element and appends it to the parent
		/// </summary>
		/// <param name="name">Name of the element</param>
		/// <param name="parent">Node to append to</param>
		/// <param name="document">Document that will create the node</param>
		/// <returns>The node created</returns>
		public static XmlElement AppendElement(string name, XmlNode parent, XmlDocument document)
		{
			return AppendElement(name, String.Empty, parent, document);
		}

		/// <summary>
		/// Converts a date to XSDDate format
		/// </summary>
		/// <param name="date"></param>
		/// <returns></returns>
		public static string GetXmlDate(DateTime date)
		{
			return date.Year + "-" + date.Month.ToString("00") + "-" + date.Day.ToString("00") + "T" + date.Hour.ToString("00") + ":" + date.Minute.ToString("00") + ":" + date.Second.ToString("00");
		}

		/// <summary>
		/// Removes dodgy characters from the input
		/// </summary>
		/// <param name="input"></param>
		/// <returns></returns>
		public static string CleanXml(string input)
		{
			input = input.Replace("'","''");
			return input;
		}

		/// <summary>
		/// Writes string data out to file
		/// </summary>
		/// <remarks>Used StreamWriter</remarks>
		/// <param name="filename">File to write to</param>
		/// <param name="data">Data to write to file</param>
		/// <param name="append">Whether to append. False will overwrite data</param>
		public static void StringToFile(string filename, string data, bool append)
		{
			try
			{
				using (StreamWriter sw = new StreamWriter(filename,append))
				{
					sw.Write(data,0,data.Length);
				}
			}
			catch(IOException ex)
			{
				string error = ex.Message;
			}
		}

		#endregion
	}
}
