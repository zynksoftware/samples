<?php

	// Internetware Developer Kit

	// DevKit
	class DevKit 
	{
		var $Customers;
		var $SalesOrders;
		var $Invoices;
		var $Products;
		var $PriceLists;
		var $Inventories;
		var $Transactions;
		var $PurchaseOrders;

		function DevKit() 
		{
			$this->Customers        = new Collection('Customer');
			$this->SalesOrders      = new Collection('SalesOrder');
			$this->Invoices         = new Collection('Invoice');
			$this->Products         = new Collection('Product');
			$this->PriceLists       = new Collection('PriceList');
			$this->Inventories      = new Collection('Inventory');
			$this->Transactions     = new Collection('Transaction');
			$this->PurchaseOrders   = new Collection('PurchaseOrder');
		}

		function Download()
		{
			header("Content-type: application/xml; charset=ISO-8859-1");
			
			$xml = '<?xml version="1.0" encoding="utf-8"?>';
			$xml .= '<Company xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';

			foreach ($this as $key => $value)
			{
				if (is_object($value))
				{
					$xml .= '<' . $key . '>' . $value->Output() . '</' . $key . '>';
				}
			}

			$xml .= '</Company>';
			print($xml);
		}

		function Upload($data)
		{
			$xml = new SimpleXMLElement($data);

			// Customers
			if (count($xml->Customers->Customer) > 0)
			{
				foreach($xml->Customers->Customer as $customer)
				{
					$c = new Customer("$customer->AccountReference");
					foreach($customer as $key => $value)
					{
						if ($key == 'CustomerInvoiceAddress' || $key == 'CustomerDeliveryAddress')
						{
							$ca = new Contact(Guid());
							foreach($value[0] as $key2 => $value2)
							{
								$ca->$key2 = $value2;
							}
							$c->$key = $ca;
						}
						else
						{
							$c->$key = $value;
						}
					}
					$this->Customers->Add($c);
				}
			}

			// Salesorders	
			if (count($xml->SalesOrders->SalesOrder) > 0)
			{
				foreach($xml->SalesOrders->SalesOrder as $salesOrder)
				{
					$s = new SalesOrder("$salesOrder->Id");
					foreach($salesOrder as $key => $value)
					{
						$s->$key = $value;
					}
					$this->SalesOrders->Add($s);
				}
			}

			// Invoices
			if (count($xml->Invoices->Invoice) > 0)
			{		
				foreach($xml->Invoices->Invoice as $invoice)
				{
					$s = new Invoice("$invoice->InvoiceNumber");
					foreach($invoice as $key => $value)
					{
						$s->$key = $value;
					}
					$this->Invoices->Add($s);
				}
			}

			// Products	
			if (count($xml->Products->Product) > 0)
			{
				foreach($xml->Products->Product as $product)
				{
					$p = new Product("$product->Sku");
					foreach($product as $key => $value)
					{
						$p->$key = $value;
					}
					$this->Products->Add($p);
				}
			}

			// Price Lists
			if (count($xml->PriceLists->PriceList) > 0)
			{
				foreach ($xml->PriceLists->PriceList as $priceList)
				{
					$pl = new PriceList("$priceList->Reference");
					foreach($priceList as $key => $value)
					{
						if ($key != 'Prices')
						{
							$pl->$key = $value;
						}
					}

					// NEED TO ADD IN SUPPORT FOR PRICES AS WELL
					// SEE BELOW FOR USAGE
					
					$this->PriceLists->Add($pl);
				}
			}

			// Inventories
			if (count($xml->Inventories->Inventory) > 0)
			{
				foreach ($xml->Inventories->Inventory as $inventory)
				{
					$i = new Inventory(Guid());
					
					foreach($inventory as $key => $value)
					{
						if ($key != 'Locations')
						{
							$i->$key = $value;
						}
					}

					$ls = new Collection('Location');
					if (count($inventory->Locations->Location) > 0)
					{
						foreach($inventory->Locations->Location as $location)
						{
							$l = new Location(Guid());
							foreach($location as $key => $value)
							{
								$l->$key = $value;
							}
							$ls->Add($l);
						}
					}
					$i->Locations = $ls;
					$this->Inventories->Add($i);
				}
			}

			// Transactions
			if (count($xml->Transactions->Transaction) > 0)
			{
				foreach ($xml->Transactions->Transaction as $transaction)
				{
					$t = new Transaction("$transaction->Id");
					foreach($transaction as $key => $value)
					{
						$t->$key = $value;
					}
					$this->Transactions->Add($t);
				}
			}
		}

		function RecordCount($type = '')
		{
			if (strlen($type) == 0)
			{
				$rtnVal = 0;

				foreach ($this as $key => $value)
				{
					if (is_object($this->$key) AND get_class($this->$key) === 'Collection')
					{
						$rtnVal += count($value->Data);
					}
				}

				return $rtnVal;
			}
			else
			{
				return count($this->$type->Data);
			}
		}
	}
	// DevKit

	// Functions
	function Connect($server, $username, $password, $database)
	{
		$connection = mysql_connect($server, $username, $password) or die('Unable to connect: ' . mysql_error());
		mysql_select_db($database) or die('Unable to select: ' . mysql_error());
		return $connection;
	}

	function Disconnect($connection)
	{
		mysql_close($connection);
	}

	function Guid()
	{
		return md5(uniqid(mt_rand(), true));
	}

	function IsValid($object, $field)
	{
		if (isset($object->$field) AND strlen($object->$field) > 0)
		{
			return true;
		}

		return false;
	}

	function SplitName($name)
	{
		$wholeName = explode(' ', $name);
		$numNames = count($wholeName);
		$names = array('forename' => '', 'surname' => '');
		for ($i = 0; $i < $numNames - 1; $i++)
		{
			$names['forename'] .= ' ' . $wholeName[$i];
		}
		$names['surname'] = $wholeName[$numNames - 1];
		return $names;
	}

	function Strip($value)
	{
		$replace = array('&');
		$with = array('&amp;');

		$value = str_replace($replace, $with, $value);
		return $value;
	}
	// Functions

	// Classes
	class Collection
	{
		var $type;
		var $array = array();
		var $errors = array();

	    function Collection($type) 
	    {
			$this->type = $type;
	    }

	    function Get($key = '')
	    {
	    	if (strlen($key) > 0)
	    	{
	    		if (isset($this->array[$key]))
	    		{
	    			return $this->array[$key];
	    		}

	    		die('Key not found: ' . $key);
	    	}
	    	else
	    	{
	    		return $this->array;
	    	}
	    }


	    function Add($object) 
		{
			if (isset($this->type) && (strtolower(get_class($object)) == strtolower($this->type))) 
			{
				$this->array[$object->Id] = $object;
				return $object;
			}
			
			return null;
		}

		function Output()
		{
			// Rewrite this to use XML document rather than string
			$rtnVal = '';
			foreach ($this->Get() as $key => $value)
			{
				$rtnVal .= '<' . $this->type . '>';

				foreach ($value as $key2 => $value2)
				{
					if (!is_object($value2))
					{
						$rtnVal .= '<'. $key2 .'>' . Strip($value2) . '</' . $key2 . '>';
					}
					elseif (strtolower(get_class($value2)) === strtolower('Collection'))
					{
						$rtnVal .= '<'. $key2 .'>';
						
						$rtnVal .= $value2->Output();
						
						$rtnVal .= '</' . $key2 . '>';
					}
					else
					{
						$rtnVal .= '<'. $key2 .'>';
						
						foreach ($value2 as $key3 => $value3)
						{
							$rtnVal .= '<'. $key3 .'>' . Strip($value3) . '</' . $key3 . '>';
						}
						
						$rtnVal .= '</' . $key2 . '>';
					}
				}

				$rtnVal .= '</' . $this->type . '>';
			}

			return $rtnVal;
		}
	}

	class SalesOrder {}
	class Invoice {}
	class Contact {}
	class Customer {}
	class Product {}
	class PriceList {}
	class Price {}	
	class Inventory {}
	class Location {}
	class PurchaseOrder {}
	class Item {}
	class SalesOrderItem {}	
	class Carriage {}
	class Transaction {}
	class CustomField {}
	// Classes

?>