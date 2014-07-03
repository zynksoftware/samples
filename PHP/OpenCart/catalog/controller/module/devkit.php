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
        public $Rewards;

        public function __construct() 
        {
            $this->Customers        = new Collection('DKCustomer');
            $this->SalesOrders      = new Collection('DKSalesOrder');
            $this->Invoices         = new Collection('DKInvoice');
            $this->Products         = new Collection('DKProduct');
            $this->PriceLists       = new Collection('DKPriceList');
            $this->Inventories      = new Collection('DKInventory');
            $this->Transactions     = new Collection('DKTransaction');
            $this->PurchaseOrders   = new Collection('DKPurchaseOrder');
            $this->Rewards          = new Collection('DKReward');
        }

        public function Download()
        {
            if ($this->HasErrors())
            {
                die($this->PrintErrors());
            }
            else
            {
                //$encoding = "ISO-8859-1";
                $encoding = "UTF-8";

                header("Content-type: application/xml; charset=".$encoding);
                
                $xml = '<?xml version="1.0" encoding="'.$encoding.'"?>';
                $xml .= '<Company xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';

                foreach ($this as $key => $value)
                {
                    if (is_object($this->$key) AND get_class($this->$key) === 'Collection')
                    {
                        $xml .= '<' . $key . '>' . $value->Output() . '</' . $key . '>';
                    }
                }

                $xml .= '</Company>';
                print($xml);
            }
        }

        public function Upload($data)
        {
            $xml = new SimpleXMLElement($data);

            // Customers
            if (count($xml->Customers->Customer) > 0)
            {
                foreach($xml->Customers->Customer as $customer)
                {
                    $c = $this->Customers->Add(new DKCustomer("$customer->AccountReference"));
                    foreach($customer as $key => $value)
                    {
                        if ($key == 'CustomerInvoiceAddress' || $key == 'CustomerDeliveryAddress')
                        {
                            $ca = $c->$key = new DKContact(Guid());
                            
                            foreach($value[0] as $key2 => $value2)
                                $ca->$key2 = $value2;
                        }
                        else
                            $c->$key = $value;
                    }
                }
            }

            // Salesorders	
            if (count($xml->SalesOrders->SalesOrder) > 0)
            {
                foreach($xml->SalesOrders->SalesOrder as $salesOrder)
                {
                    $s = $this->SalesOrders->Add(new DKSalesOrder("$salesOrder->Id"));
                    foreach($salesOrder as $key => $value)
                        $s->$key = $value;
                }
            }
            // Invoices	
            if (count($xml->Invoices->Invoice) > 0)
            {
                foreach($xml->Invoices->Invoice as $invoice)
                {
                    $s = $this->Invoices->Add(new DKInvoice("$invoice->InvoiceNumber"));
                    foreach($invoice as $key => $value)
                        $s->$key = $value;
                }
            }

            // Products
            #$counttemp = count($xml->Products->Product);
            #echo $counttemp;exit;
            if (count($xml->Products->Product) > 0)
            {
                foreach($xml->Products->Product as $product)
                {
                    $p = $this->Products->Add(new DKProduct("$product->Sku"));
                    foreach($product as $key => $value)
                    {
                        if ($key != 'Attributes' && $key != 'ProductQtyBreaks')
                        {
                            $p->$key = $value;
                        }
                    }

                    $attribs = $p->Attributes = new Collection('DKAttribute');
                    foreach ($product->Attributes->Attribute as $attrib)
                    {
                        $a = $attribs->Add(new DKAttribute((string)$attrib->Name));
                        foreach($attrib as $key => $value)
                        {                            
                            $a->$key = $value;
                        }
                    }
                    $qtyBreaks = $p->ProductQtyBreaks = new Collection('DKProductQtyBreak');
                    foreach ($product->ProductQtyBreaks->ProductQtyBreak as $qtyBreak)
                    {
						$pb = $qtyBreaks->Add(new DKProductQtyBreak(Guid()));
                        foreach($qtyBreak as $key => $value)
                        {              
                            $pb->$key = $value;
                        }
                    }
                }
            }

            // Price Lists
            if (count($xml->PriceLists->PriceList) > 0)
            {
                foreach ($xml->PriceLists->PriceList as $priceList)
                {
                    $pl = $this->PriceLists->Add(new DKPriceList("$priceList->Reference"));
                    
                    foreach($priceList as $key => $value)
                    {
                        if ($key != 'Prices')
                        {
                            $pl->$key = $value;
                        }
                    }

                    $prices =& $priceList->Prices;
                    $pc = $pl->Prices = new Collection('DKPrice');
                    foreach ($prices->Price as $price)
                    {
                        $p = $pc->Add(new DKPrice("$price->StockCode"));
                        foreach($price as $key => $value)
                        {
                            $p->$key = $value;
                        }
                    }

                    /*
                    $prices =& $priceList['Prices'][0][''];
                    $pc = $pl->Prices = new Collection('Price');
                    
                    foreach($prices as $price)
                    {
                        $p = $pc->Add(new Price($price['StockCode']));
                        
                        foreach($price as $key => $value)
                        {
                            $p->$key = $value;
                        }
                    }
                    */
                }
            }

            // Inventories
            if (count($xml->Inventories->Inventory) > 0)
            {
                foreach ($xml->Inventories->Inventory as $inventory)
                {
                    $i = $this->Inventories->Add(new DKInventory(Guid()));
                    
                    foreach($inventory as $key => $value)
                    {
                        if ($key != 'Locations')
                        {
                            $i->$key = $value;
                        }
                    }

                    $ls = $i->Locations = new Collection('Location');
                    if (count($inventory->Locations->Location) > 0)
                    {
                        foreach($inventory->Locations->Location as $location)
                        {
                            $l = $ls->Add(new DKLocation(Guid()));
                            foreach($location as $key => $value)
                            {
                                $l->$key = $value;
                            }
                        }
                    }
                }
            }

            // Transactions
            if (count($xml->Transactions->Transaction) > 0)
            {
                foreach ($xml->Transactions->Transaction as $transaction)
                {
                    $t = $this->Transactions->Add(new DKTransaction("$transaction->Id"));
                    
                    foreach($transaction as $key => $value)
                    {
                        $t->$key = $value;
                    }
                }
            }
            
            // Rewards
            if (count($xml->Rewards->Reward) > 0)
            {
                foreach($xml->Rewards->Reward as $reward)
                {
                    $r = $this->Rewards->Add(new DKReward("$reward->Id"));
                    foreach($reward as $key => $value)
                    {
                        $r->$key = $value;
                    }
                }
            }
        }

        public function RecordCount($type = '')
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

        public function HasErrors()
        {
            $this->Validate();

            foreach ($this as $key => $value)
            {
                if (is_object($this->$key) AND get_class($this->$key) === 'Collection')
                {
                    if ($value->HasErrors())
                    {
                        return true;
                    }
                }
            }

            return false;
        }

        public function PrintErrors()
        {
            foreach ($this as $key => $value)
            {
                if (is_object($this->$key) AND get_class($this->$key) === 'Collection')
                {
                    $value->PrintErrors();
                }
            }
        }

        public function Validate()
        {
            foreach ($this as $key => $value)
            {
                if (is_object($this->$key) AND get_class($this->$key) === 'Collection')
                {
                    $value->Validate();
                }
            }
        }
    }
    // DevKit

    // Functions
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

    function LogMessage($message)
    {
        if (strlen($message) > 0)
        {
            //echo('Attempting to log: ' . $message);
            //WriteFile(Settings::Get('LOG_FILE'), $message);
            file_put_contents(Settings::Get('LOG_FILE'), $message."\n", FILE_APPEND);
        }
    }

    function Strip($value)
    {
        $replace = array('&');
        $with = array('&amp;');
        
        $value = str_replace($replace, $with, $value);
        return $value;
    }
    // Functions

    // Interfaces
    interface iDevObject
    {
        public function Validate($log);
    }

    abstract class DevObject implements iDevObject
    {
        public $Id;
        
        public function __construct($id)
        {
            $this->Id = $id;
        }

        public function Validate($log)
        {
            if (!IsValid($this, 'Id'))
            {
                $log[] = 'Id field must be specified (and unique) for all objects. If no id available use Guid() function.';
            }
        }
    }
    // Interfaces

    // Classes
    class Connection
    {
        private static $connection;
        
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
                                                    Settings::Get('DB_PASSWORD')) or die('Unable to connect: ' . mysql_error());
                mysql_select_db(Settings::Get('DB_DATABASE')) or die('Unable to select: ' . mysql_error());
            }
        }

        public static function Run($sql)
        {
            self::Connect();
            $message = 'Error running SQL.  Error: %s.  SQL: %s';
            $result = mysql_query($sql, self::$connection) or die(sprintf($message, mysql_error(), $sql));

            if (!$result)
            {
                $message = 'Error running SQL.  Error: %s.  SQL: %s';
                die(sprintf($message, mysql_error(), $sql));
            }
            
            return $result;
        }
    }

    class Settings
    {
        private static $settings;
        
        private function __construct() {}
        
        private function Create()
        {
            if (!self::$settings)
            {
                self::$settings = array();
                self::$settings['LOG_FILE'] = 'log.txt';
            }
        }

        public static function Get($key)
        {
            return self::$settings[$key];
        }

        public static function Set($key, $value)
        {
            self::Create();
            self::$settings[$key] = $value;
        }
    }

    class Collection
    {
        private $type;
        private $array = array();
        private $errors = array();

        public function __construct($type) 
        {
                $this->type = str_replace('DK', '', $type);
        }

        public function Get($key = '')
        {
            if (strlen($key) > 0)
            {
                if (isset($this->array[$key]))
                {
                    return $this->array[$key];
                }

                throw new Exception('Key not found: ' . $key);
            }
            else
            {
                return $this->array;
            }
        }


        public function Add($object) 
        {
            if (isset($this->type) && (str_replace('DK', '', get_class($object)) == $this->type)) 
            {          
                $this->array[$object->Id] = $object;
                return $object;
            }

            return null;
        }

        public function Validate()
        {
            $this->errors = array();
            
            foreach	 ($this->Get() as $key => $value)
            {
                $value->Validate($this->errors);
            }
        }

        public function HasErrors()
        {
            if (count($this->errors) > 0)
            {
                return true;
            }
            
            return false;
        }

        public function PrintErrors()
        {
            foreach($this->errors as $error)
            {
                echo($this->type . ': ' . $error . '<br />');
            }
        }

        public function Output()
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
                    elseif (get_class($value2) === 'Collection')
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

    class DKSalesOrder extends DevObject
    {
        public function Validate($log)
        {
            parent::Validate($log);

            if (!IsValid($this, 'CustomerId')  AND !IsValid($this, 'AccountReference'))
            {
                $log[] = '[' . $this->Id . '] Either CustomerId or AccountReference must be specified.';
            }

            if (isset($this->SalesOrderAddress))
                $this->SalesOrderAddress->Validate($log);

            if (isset($this->SalesOrderDeliveryAddress))
                $this->SalesOrderDeliveryAddress->Validate($log);
        }
    }

    class DKInvoice extends DevObject
    {
        public function Validate($log)
        {
            parent::Validate($log);
            
            if (!IsValid($this, 'CustomerId')  AND !IsValid($this, 'AccountReference'))
            {
                $log[] = '[' . $this->Id . '] Either CustomerId or AccountReference must be specified.';
            }
            
            if (isset($this->InvoiceAddress))
                $this->InvoiceAddress->Validate($log);

            if (isset($this->InvoiceDeliveryAddress))
                $this->InvoiceDeliveryAddress->Validate($log);
        }
    }

    class DKContact extends DevObject
    {
        public function Validate($log)
        {
            parent::Validate($log);
            
            if (!IsValid($this, 'Forename') AND !IsValid($this, 'Surname') AND !IsValid($this, 'Company'))
            {
                $log[] = '[' . $this->Id . '] Either Forename, Surname or Company must be specified.';
            }
        }
    }

    class DKCustomer extends DevObject 
    {
        public function Validate($log)
        {
            parent::Validate($log);
                
            if (isset($this->CustomerInvoiceAddress))
                $this->CustomerInvoiceAddress->Validate($log);
            
            if (isset($this->CustomerDeliveryAddress))
                $this->CustomerDeliveryAddress->Validate($log);
        }
    }

    class DKProduct extends DevObject {}
    class DKAttributes extends DevObject {}
    class DKAttribute extends DevObject {}
    class DKPriceList extends DevObject {}
    class DKPrice extends DevObject {}
    class DKInventory extends DevObject {}
    class DKLocation extends DevObject {}
    class DKPurchaseOrder extends DevObject {}
    class DKItem extends DevObject {}
    class DKSalesOrderItem extends DKItem {}
    class DKCarriage extends DKItem {}
    class DKTransaction extends DevObject {}
    class DKCustomField extends DevObject {}
    class DKReward extends DevObject {}
	class DKProductQtyBreak extends DevObject {} 
    // Classes

?>