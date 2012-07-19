<?php
    // Include DevKit file
    include_once('./core/devkit.php');
    include_once('config.php');
    include_once('./core/functions.php');

    // Create new instance of DevKit
    $d      = new DevKit();

    // Get HTTP Post data
    $post   = file_get_contents("php://input");

    // Check for HTTP Post data
    if (!empty($post)) // Run notify
    {
        echo("Call Notify...</br>\n");
    }
    else // Run Download
    {
        // Limit by OrderID
        if (isset($_GET['orderid']))
        {
            DownloadSingle($d, $_GET['orderid']);
        }
        else
        {
            DownloadMultiple($d);
        }

        $d->Download();
    }
    
    Disconnect($connection);

    function DownloadSingle(&$d, $id)
    {
        DownloadOrder($d, $id);
    }

    function DownloadMultiple(&$d)
    {
        DownloadOrders($d);
    }

    function DownloadOrder(&$d, $id)
    {
        Global $connection;

        // Download Order
        $sql    = "SELECT * FROM %s WHERE %s = '%s';";
        $sql    = sprintf($sql, OrdersTable, OrdersTable_OrderIdColumn, $id);
        $orders = mysql_query($sql, $connection) or die("Couldn't download Order: " . $id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");

        while ($order = mysql_fetch_object($orders))
        {
            if (DownloadInvoices == 'true')
            {
                $d->Invoices->Add(GetOrder($d, $order));
            }
            else
            {
                $d->SalesOrders->Add(GetOrder($d, $order));
            }
        }
    }

    function DownloadOrders(&$d)
    {
        Global $connection;

        // Download Orders
        $sql    = "SELECT * FROM %s WHERE %s = 0 ";

        if (LimitByOrderStatus == 'true')
        {
            $sql .= sqlExplode(OrderStatus, OrdersTable_StatusColumn);
        }

        if (LimitByOrderDate == 'true')
        {
            $sql .= " AND " . OrdersTable_DateColumn . " > '" . strtotime(OrderDate) . "' ";
        }

        $sql .= " ORDER BY %s ASC LIMIT 0, %d;";

        $sql  = sprintf($sql, OrdersTable, OrdersTable_PostedColumn, OrdersTable_IdColumn, QueryLimit);

        $orders = mysql_query($sql, $connection) or die("Couldn't download Invoice from table: " . OrdersTable . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");

        while ($order = mysql_fetch_object($orders))
        {
            if (DownloadInvoices == 'true')
            {
                $i = $d->Invoices->Add(GetOrder($d, $order));
            }
            else
            {
                $i = $d->SalesOrders->Add(GetOrder($d, $order));
            }
            //sendEmail($order->oNum, $currentDomain, $accountType);
        }
    }

    function GetOrder(&$d, $order)
    {
        Global $connection;

        $type = (DownloadInvoices == 'true') ? DownloadInvoiceTypeName : DownloadSalesOrderTypeName;

        $o                      = new $type($order->order_id);
        $o->Id                  = $order->order_id;
        $o->CustomerId          = $order->user_id;
        $NumberField            = $type."Number";
        $o->$NumberField        = $order->order_id;
        $o->CustomerOrderNumber = $order->order_id;

        // NOTES: divide string up into 60
        // NOTES 1 = 0-59,
        // NOTES 2 = 60-120,
        // NOTES 3 = 120-180
        // Rest is ignored.
        if (isset($order->notes))
        {
            $o->Notes1          = substr($order->notes, 0,   60);
            $o->Notes2          = substr($order->notes, 60,  60);
            $o->Notes3          = substr($order->notes, 120, 60);
        }
        
        $o->Currency            = GetCurrency($o->$NumberField);

        if (DownloadToSingleAccount == 'true' || $order->user_id == '0' || DownloadCustomers == 'false')
        {
            $AccountReference = AccountReference;
        }
        else
        {
            $c = DownloadCustomer($d, $order->user_id);
            $AccountReference   = $c->AccountReference;
        }
        $o->AccountReference    = $AccountReference;

        $DateField              = $type."Date";
        $o->$DateField          = date("Y-m-d\TH:i:s", $order->timestamp);
        $o->TakenBy             = TakenBy;

        // Globals
        if(GlobalNominalCode    != "")  $o->GlobalNominalCode   = GlobalNominalCode;
        if(GlobalDetails        != "")  $o->GlobalDetails       = GlobalDetails;
        if(GlobalTaxCode        != "")  $o->GlobalTaxCode       = GlobalTaxCode;
        if(GlobalDepartment     != "")  $o->GlobalDepartment    = GlobalDepartment;

        // Payments
        if (AllocatePayments == 'true')
        {
            $PaymentInformation             = GetPaymentDetails($order->payment_id, $o->$NumberField);

            $o->PaymentRef                  = $PaymentInformation['payment'];
            $o->PaymentAmount               = $order->total;
            $o->BankAccount                 = BankAccount;
            $o->PaymentType                 = PaymentType;
        }

        // Customer Billing Contact
        $DeliveryField                      = $type."Address";
        $o->$DeliveryField                  = GetAddress($order, "Billing");

        // Customer Delivery Contact
        $DeliveryField                      = $type."DeliveryAddress";
        $o->$DeliveryField                  = GetAddress($order, "Shipping");

        // Tax
        $taxData                            = GetTaxData($o->$NumberField);

        // Items
        $itemField                          = $type."Items";
        $o->$itemField                      = GetOrderItems($o->$NumberField, $taxData);

        // Carriage
        $o->Carriage                        = GetOrderShipping($o->$NumberField, $taxData, $order->shipping_ids);

        //if(isset($o->Carriage->Courier))        $o->Courier = $o->Carriage->Courier;
        if(isset($o->Carriage->ConsignmentNo))  $o->ConsignmentNo = $o->Carriage->ConsignmentNo;

        // Discount (TODO)
        /*
        if (UseDiscountAsNetValue == 'true' && $order->discount > 0)
        {
            $o->NetValueDiscount            = $order->discount / (1 + ($taxData['taxRate'] / 100));
            $o->NetValueDiscountDescription = "Order Discount";
            $o->NetValueDiscountComment1    = "Discount of " . $order->discount . " " . $o->Currency;
        }
        */

        return $o;
    }
    
    function GetOrderItems($order_id, $taxData)
    {
        Global $connection;

        $ic     = new Collection('Item');

        // Retrieve Items
        $sql    = "SELECT * FROM %s WHERE %s = %s;";
        $sql    = sprintf($sql, OrderProductsTable, OrderProductsTable_OrderIdColumn, $order_id);
        $items  = mysql_query($sql, $connection) or die("Couldn't download Order Items for Order: " . $order_id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");

        while ($item = mysql_fetch_object($items))
        {
            $information            = unserialize($item->extra);

            $unitPrice              = $item->price / (1 + ($taxData['taxRate'] / 100));
            if (isset($information['base_price']))
            {
                $unitPrice          = $information['base_price'] / (1 + ($taxData['taxRate'] / 100));
            }

            $quantity               = $item->amount;
            $totalNet               = $unitPrice * $quantity;
            $totalTax               = ($totalNet / 100) * $taxData['taxRate'];

            $i_item                 = new Item($item->item_id);
            $i_item->Id             = $item->item_id;
            $i_item->Sku            = $item->product_code;
            $i_item->Name           = $information['product'];
            
            if (isset($information['product_options_value']))
            {
                $description            = "";
                $productOptionsArray    = $information['product_options_value'];

                foreach($productOptionsArray as $productOption)
                {
                    if (!empty($description))
                    {
                        $description .= " - ";
                    }
                    $description .= $productOption['variant_name']; 
                }
                
                $i_item->Description = $description;
            }

            $i_item->QtyOrdered     = $quantity;
            $i_item->UnitPrice      = safeRound($unitPrice, 2);
            $i_item->TaxRate        = $taxData['taxRate'];
            if($taxData['taxCode'] != "")  $i_item->TaxCode = $taxData['taxCode'];
            if (isset($information['discount']) && UseDiscountAsUnitDiscount == 'true')
            {
                $i_item->UnitDiscountAmount = $information['discount'] / (1 + ($taxData['taxRate'] / 100));
            }
            if(DefaultNominalCodeItem != "")  $i_item->NominalCode = DefaultNominalCodeItem;

            $ic->Add($i_item);
        }

        return $ic;
    }

    function GetOrderShipping($order_id, $taxData, $shipping_id)
    {
        Global $connection;

        // Retrieve Carriage
        $sql        = "SELECT * FROM %s WHERE %s = %s AND %s = '%s'";
        $sql        = sprintf($sql, OrderInformationTable, OrderInformation_IdColumn, $order_id, OrderInformation_TypeColumn, OrderInformation_LineType);
        $carriage   = mysql_query($sql, $connection) or die("Couldn't download Order Carriage for Order: " . $order_id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        $carriage   = mysql_fetch_object($carriage);

        foreach(unserialize($carriage->data) as $carriageArray)
        {
            $vatAble        = GetShippingTaxInformation($order_id, $carriageArray['shipping'], $shipping_id);
            
            // Calculate shipping costs 
            if($vatAble && $carriageArray['rates'][0] > 0)
            {
                $unitPrice  = $carriageArray['rates'][0] / (1 + ($taxData['taxRate'] / 100));
                $totalTax   = ($unitPrice * $taxData['taxRate']) / 100;
                $taxCode    = $taxData['taxCode'];
                $taxRate    = $taxData['taxRate'];
            }
            else
            {
                $unitPrice  = $carriageArray['rates'][0];
                $totalTax   = 0;
                $taxCode    = 0;
                $taxRate    = 0;
            }

            // Set values for Carriage
            $c                      = new Carriage('0');
            $Name                   = ($carriageArray['rates'][0] > 0) ? $carriageArray['shipping'] : DefaultZeroRatedShipping;
            $c->Name                = $Name;
            $c->QtyOrdered          = 1;
            $c->UnitPrice           = $unitPrice;
            $c->TaxRate             = $taxRate;
            //$c->TotalNet            = safeRound($unitPrice, 2);
            //$c->TotalTax            = safeRound($totalTax,  2);
            if($taxCode != "")  $c->TaxCode = $taxCode;
            if(DefaultNominalCodeCarriage != "")            $c->NominalCode     = DefaultNominalCodeCarriage;
            if(isset($carriageArray['carrier']))            $c->Courier         = $carriageArray['carrier'];
            if(isset($carriageArray['tracking_number']))    $c->ConsignmentNo   = $carriageArray['tracking_number'];

            return $c;
        } 
    }
    
    function GetAddress($data, $type)
    {
        $prefix = ($type == "Shipping") ? "s_" : "b_";

        $c              = new Contact(Guid());
        
        $Title          = $prefix."title";
        if(isset($data->$Title))
            $c->Title       = $data->$Title ;
        
        $Forename       = $prefix."firstname";
        if(isset($data->$Forename))
            $c->Forename    = $data->$Forename;
        
        $Surname        = $prefix."lastname";
        if(isset($data->$Surname))
            $c->Surname     = $data->$Surname;
        
        if(isset($data->company))
            $c->Company     = $data->company;
        
        $Address1       = $prefix."address";
        if(isset($data->$Address1))
            $c->Address1    = $data->$Address1;
        
        $Address2       = $prefix."address_2";
        if(isset($data->$Address2))
            $c->Address2    = $data->$Address2;
        
        $Town           = $prefix."city";
        if(isset($data->$Town))
            $c->Town        = $data->$Town;
        
        $Postcode       = $prefix."zipcode";
        if(isset($data->$Postcode))
            $c->Postcode    = $data->$Postcode;
        
        $County         = $prefix."state";
        if(isset($data->$County))
            $c->County      = $data->$County;
        
        $Country        = $prefix."country";
        if(isset($data->$Country))
            $c->Country     = $data->$Country;
        
        $Telephone      = $prefix."phone";
        if(isset($data->$Telephone))
            $c->Telephone   = $data->$Telephone;
        
        if(isset($data->email))
            $c->Email       = $data->email;

        return $c;
    }

    function GetTaxData($order_id)
    {
        Global $connection;

        $sql        = "SELECT * FROM %s WHERE %s = %s AND %s = '%s'";
        $sql        = sprintf($sql, OrderInformationTable, OrderInformation_IdColumn, $order_id, OrderInformation_TypeColumn, OrderInformation_TaxType);
        $tax        = mysql_query($sql, $connection) or die("Couldn't download Order Tax for Order: " . order_id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        $tax        = mysql_fetch_object($tax);

        foreach(unserialize($tax->data) as $taxArray)
        {
            $data       = array
                (
                    'taxRate'           => $taxArray['rate_value'],
                    'taxCode'           => ($taxArray['rate_value'] > 0) ? DefaultTaxCode : DefaultTaxCodeExempt,
                    'comment'           => $taxArray['description'],
                    'item_tax'          => $taxArray['applies']
                );
        }

        return $data;
    }
    
    function GetCurrency($order_id)
    {
        Global $connection;
        $rtnVal     = DefaultCurrency;
        $sql        = "SELECT * FROM %s WHERE %s = %s AND %s = '%s'";
        $sql        = sprintf($sql, OrderInformationTable, OrderInformation_IdColumn, $order_id, OrderInformation_TypeColumn, OrderInformation_CurrencyType);
        $currency   = mysql_query($sql, $connection) or die("Couldn't download Order Currency for Order: " . $order_id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        $currency   = mysql_fetch_object($currency);
        $currency   = unserialize($currency->data);

        $rtnVal     = isset($currency) ? $currency : $rtnVal;

        return $rtnVal;
    }
    
    // Does the shipping have VAT applied to it
    function GetShippingTaxInformation($order_id, $shipping, $shipping_id)
    {
        Global $connection;
        
        $rtnVal = false;

        // Retrieve Carriage
        $sql        = "SELECT * FROM %s WHERE %s = %s";
        $sql        = sprintf($sql, ShippingDetailsTable, ShippingDetailsTable_IdColumn, $shipping_id);
        $carriage   = mysql_query($sql, $connection) or die("Couldn't download Order Carriage Shipping Information for Order: " . $order_id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        $carriage   = mysql_fetch_object($carriage);
        
        $rtnVal     = ($carriage->tax_ids == "") ? false : true;
        
        return $rtnVal;
    }

    function GetPaymentDetails($payment_id, $order_id)
    {
        Global $connection;

        $sql            = "SELECT * FROM %s WHERE %s = '%s'";
        $sql            = sprintf($sql, PaymentsTable, PaymentsTable_IdColumn, $payment_id);
        $payment_data   = mysql_query($sql, $connection) or die("Couldn't download Payment Information for Order Id: " . $order_id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        $payment_data   = mysql_fetch_object($payment_data);

        $data = array
            (
                'payment'       => $payment_data->payment,
                'description'   => $payment_data->description,
                'instructions'  => $payment_data->instructions
            );

        return $data;
    }

    function DownloadCustomer(&$d, $customer_id)
    {
        Global $connection;
        // Download Customer
        $sql        = "SELECT * FROM %s WHERE %s = '%s';";
        $sql        = sprintf($sql, CustomersTable, CustomersTable_IdColumn, $customer_id);
        $customers  = mysql_query($sql, $connection) or die("Couldn't download Customer from table: " . CustomersTable . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        $customer   = mysql_fetch_object($customers);

        return $d->Customers->Add(GetCustomer($customer));
    }

    function GetCustomer($customer)
    {
        $c                          = new Customer($customer->user_id);
        $c->Id                      = $customer->user_id;
        $c->CompanyName             = $customer->company;

        $AccountReferenceColumn     = CustomersTable_AccountReferenceColumn;
        $c->AccountReference        = $customer->$AccountReferenceColumn;

        $cb                         = new Contact('0');
        $cb->Title                  = $customer->title;
        $cb->Forename               = $customer->firstname;
        $cb->Surname                = $customer->lastname;
        $cb->Company                = $customer->company;
        $cb->Telephone              = $customer->phone;
        $cb->Fax                    = $customer->fax;
        $cb->Email                  = $customer->email;
        $cb->Website                = $customer->url;

        $address                    = GetCustomerAddress($customer->user_id);
        $c->CustomerInvoiceAddress  = $address['billing'];
        $c->CustomerDeliveryAddress = $address['shipping'];

        //$c->AccountStatus           = $customer->cStatus;
        $c->TermsAgreed             = TermsAgreed;

        return $c;
    }
    
    function GetCustomerAddress($user_id)
    {
        Global $connection;

        $sql        = "SELECT * FROM %s WHERE %s = '%s' AND %s = '%s'";
        $sql        = sprintf($sql, CustomersAddressTable, CustomersAddressTable_UserIdColumn, $user_id, CustomersAddressTable_ProfileNameColumn, CustomersAddressTable_MainProfileText);
        $address    = mysql_query($sql, $connection) or die("Couldn't download Address for Customer: " . $user_id . " \n</br>$sql</br>\n " . mysql_error() . "</br></br>\n\n");
        $address    = mysql_fetch_object($address);

        $billing    = GetAddress($address, "Billing");
        $shipping   = GetAddress($address, "Shipping");
        $address = array
            (
                'billing'   => $billing,
                'shipping'  => $shipping
            );
        return $address;
    }
    
?>