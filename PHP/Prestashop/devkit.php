<?php

	// Internetware Developer Kit

	// DevKit
	class DevKit 
	{
		public $Customers;
		public $SalesOrders;
		public $Invoices;
		public $Products;
		public $PriceLists;
		public $Inventories;
		public $Transactions;
		public $PurchaseOrders;
		public $AllocationSessions;
		
		public function __construct() 
		{
			$this->Customers = new Collection('Customer');
			$this->SalesOrders = new Collection('SalesOrder');
			$this->Invoices = new Collection('Invoice');
			$this->Products = new Collection('Product');
			$this->ProductGroups = new Collection('ProductGroup');
			$this->PriceLists = new Collection('PriceList');
			$this->Inventories = new Collection('Inventory');
			$this->Transactions = new Collection('Transaction');
			$this->PurchaseOrders = new Collection('PurchaseOrder');
			$this->AllocationSessions = new Collection('AllocationSession');
		}
		
		public function Download()
		{
			header("Content-type: application/xml");
			$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Company />');
			
			foreach ($this as $key => $value)
			{
				if (is_object($this->$key) AND get_class($this->$key) === 'Collection')
					$value->Output($xml->addChild($key));
			}
			
			exit($xml->asXml());
		}		
		
		public function Upload($data)
		{
			// Tidy data first off
			$data = str_replace(array('&#x0;'), '', $data);
			
			// Then carry on
			$xml = new SimpleXMLElement($data);
			
			foreach ($xml as $key => $value)
			{				
				foreach($value as $key2 => $value2)
				{
					$this->GetData($this->$key->Add(new $key2("$value2->Id")), $value2);
				}
			}
		}
		
		private function GetData(&$record, $fields)
		{
			foreach ($fields as $key => $value)
			{
				if ($value->children()->count() && $value->children()->children()->count())
				{					
					$name = $value->children()->getName();
					$record->$key = new Collection($name);
					
					foreach($value->children() as $child)
					{
						$this->GetData($record->$key->Add(new $name()), $child);
					}
				}
				else if ($value->count())
					$this->GetData($record->$key, $value);
				else
					$record->$key = "$value";
			}			
		}
	}
	// DevKit
	
	// Functions
	function Guid()
	{
		return md5(uniqid(mt_rand(), true));
	}
	
	function Strip($value)
	{
		$replace = array('&');
		$with = array('&amp;');
		
		$value = str_replace($replace, $with, $value);
		return $value;
	}
	// Functions
	
	abstract class DevObject
	{
		var $Id;
		
		public function __construct($id=null)
		{
			if ($id != null)
				$this->Id = $id;
		}
		
		function __invoke($field, $value=null)
		{
			if (is_array($field))
			{
				foreach ($field as $key => $value)
					$this->$key = $value;
			}
			else
				$this->$field = $value;
			
			return $this;
		}
		
		function set($field, $value=null)
		{
			if (is_array($field))
			{
				foreach ($field as $key => $value)
					$this->$key = $value;
			}
			else
				$this->$field = $value;
			
			return $this;
		}
		
		function __call($method, $arguments)
		{
			if (method_exists($this, $method))
				$this->$method($arguments);
			else
			{
				$this->$method = $arguments[0];
				return $this;
			}
		}
	}
	
	
	class Connection
	{
		private static $connection;
		private static $insert_id=0;
		private static $num_rows=0;
		
		private function __construct() {}
		
		function __destruct()
		{
			mysql_close(self::$connection);
		}
		
		private function Connect()
		{
			if (!self::$connection)
			{
				self::$connection = mysql_connect(	Settings::Get('DB_SERVER'), 
													Settings::Get('DB_USERNAME'), 
													Settings::Get('DB_PASSWORD')) or die('Unable to connect: ' . mysql_error() . ' - ' . $this->PrintSettings());
				mysql_select_db(Settings::Get('DB_DATABASE')) or die('Unable to select: ' . mysql_error());
			}
		}
		
		public static function Run($sql)
		{
			self::Connect();
			$message = 'Error running SQL.  Error: %s.  SQL: %s';
			$result = mysql_query($sql, self::$connection) or die(sprintf($message, mysql_error(), $sql));
			
			if (!$result) { die(sprintf($message, mysql_error(), $sql)); }
			self::$insert_id = mysql_insert_id(self::$connection);
			self::$num_rows = @mysql_num_rows($result);
			
			return $result;
		}
		
		public static function InsertId()
		{
			return self::$insert_id;
		}
		
		public static function NumRows()
		{
			return self::$num_rows;
		}
	}
	
	class Settings
	{
		private static $settings;
		
		private function __construct() {}
		
		private static function Create()
		{
			if (!self::$settings)
			{
				self::$settings = array();
				self::$settings['LOG_FILE'] = 'log.txt';
			}
		}
		
		public static function Get($key)
		{
			self::Create();
			if (isset(self::$settings[$key]))
    		{
    			return self::$settings[$key];
    		}

			return '';
		}
		
		public static function Set($key, $value)
		{
			self::Create();
			self::$settings[$key] = $value;
		}
	}
	
	class Collection implements Iterator
	{
		private $type;
		private $position = 0;
		private $array = array();
		
	    public function __construct($type) 
	    {
			$this->type = $type;
			$this->position = 0;
	    }

	    function rewind() 
		{
	        $this->position = 0;
	    }

	    function current() 
		{
	        return $this->array[$this->position];
	    }

	    function key() 
		{
	        return $this->position;
	    }

	    function next() 
		{
	        ++$this->position;
	    }

	    function valid() 
		{
	        return isset($this->array[$this->position]);
	    }
	    
	    public function Get($key = '')
	    {
	    	if (strlen($key) > 0)
	    	{
	    		if (isset($this->array[$key]))
	    		{
	    			return $this->array[$key];
	    		}
	    	
	    		return new $type();
	    	}
	    	else
	    	{
	    		return $this->array;
	    	}
	    }	
	       
		public function Add($object) 
		{		
			if (isset($this->type) && (get_class($object) == $this->type)) 
			{
				if ($object->Id == null or empty($object->Id))
					$this->array[] = $object;
				else
					$this->array[] = $object;
				
				return $object;
			}
			
			return new $type();
		}
		
	    public function AddNew($id) 
		{	
			return $this->array[] = new $this->type($id);
		}
		
		public function Output(SimpleXMLElement &$parent)
		{
			foreach ($this->Get() as $key => $value)
			{
				$child = $parent->addChild($this->type);
				
				foreach ($value as $key2 => $value2)
				{
					if (!is_object($value2))
						$child->addChild($key2, Strip($value2));
					elseif (get_class($value2) === 'Collection')
						$value2->Output($child->addChild($key2));
					else
					{
						$record = $child->addChild($key2);
						
						foreach ($value2 as $key3 => $value3)
							$record->addChild($key3, Strip($value3));
					}
				}
			}
		}
	}
	
	class Order extends DevObject
	{
		public function __construct($id, $type)
		{
			parent::__construct($id);
			$this->{$type.'Address'} = new Contact(Guid());
			$this->{$type.'DeliveryAddress'} = new Contact(Guid());
			$this->{$type.'Items'} = new Collection('Item');
		}
	}
	
	class SalesOrder extends Order
	{
		public function __construct($id)
		{
			parent::__construct($id, 'SalesOrder');
		}
	}
	
	class Invoice extends Order
	{
		public function __construct($id)
		{
			parent::__construct($id, 'Invoice');
		}
	}
	
	class PurchaseOrder extends Order
	{
		public function __construct($id)
		{
			parent::__construct($id, 'PurchaseOrder');
		}
	}
	
	class Account extends DevObject
	{
		public function __construct($id, $type)
		{
			parent::__construct($id);
			$this->{$type.'InvoiceAddress'} = new Contact(Guid());
			$this->{$type.'DeliveryAddress'} = new Contact(Guid());
		}	
	}
	
	class Customer extends DevObject 
	{
		public function __construct($id) 
		{
			parent::__construct($id, 'Customer');
		}
	}
	
	class Supplier extends DevObject 
	{
		public function __construct($id) 
		{
			parent::__construct($id, 'Supplier');
		}
	}
	
	class Contact extends DevObject {}	
	class Product extends DevObject {}
	class ProductGroup extends DevObject {}
	class PriceList extends DevObject {}
	class Price extends DevObject {}	
	class Inventory extends DevObject {}
	class Location extends DevObject {}	
	class Item extends DevObject {}	
	class Transaction extends DevObject {}
	class CustomField extends DevObject {}
	class AnalysisCode extends DevObject {}
	class AllocationSession extends DevObject {}
	class AllocationTransaction extends DevObject {}
	class Attribute extends DevObject {}
	class UnitOfMeasure extends DevObject {}
	class Bank extends DevObject {}
	
	class SalesOrderItem extends Item {}	
	class Carriage extends Item {}
	// Classes

?>