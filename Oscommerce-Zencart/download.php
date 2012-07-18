<?php
    /**
    *    Script          :    download.php
    *
    *    Author          :    Internetware Limited
    *    Copyright       :    Internetware Limited, 2011
    *    Date            :    January 2011
    *    Description     :    Downloads data from eCommerce database and parses to XML format.
    *                         Customers, Invoices, SalesOrders.
    *    Dependancies    :    config.php, functions.php
    */

    include "config.php";
    include "./core/functions.php";

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    // MAIN PROGRAM

    // Get xml data from php input stream
    $xmlInput = file_get_contents("php://input");

    // Get rid of attributes in <Company> tag as xml2array doesn't like them for some reason?
    $xmlString = preg_replace('/<Company.*?>/', '<Company>', $xmlInput, 1);

    // Connect to database
    $cnx = dbConnect();

    // Check to see if xml has been posted to this script (Notification)
    if (!empty($xmlString))
    {
        die("Incorrect url...");
    }
    else
    {
        // Get all new orders
        $sql = "SELECT orders.orders_id                         AS OrderNumber,
                       orders.customers_id                      AS CustomerID,
                       orders.currency_value                    AS ForeignRate,
                       orders.currency                          AS Currency,
                       UNIX_TIMESTAMP(orders.date_purchased)    AS OrderDate,
                       orders.customers_telephone               AS Telephone,
                       orders.customers_email_address           AS Email,
                       orders.billing_name                      AS BillingName,
                       orders.billing_company                   AS BillingCompany,
                       orders.billing_street_address            AS BillingAddress1,
                       orders.billing_suburb                    AS BillingAddress2,
                       orders.billing_city                      AS BillingTown,
                       orders.billing_postcode                  AS BillingPostcode,
                       orders.billing_state                     AS BillingCounty,
                       billingCountry.countries_iso_code_2      AS BillingCountry,
                       orders.delivery_name                     AS DeliveryName,
                       orders.delivery_company                  AS DeliveryCompany,
                       orders.delivery_street_address           AS DeliveryAddress1,
                       orders.delivery_suburb                   AS DeliveryAddress2,
                       orders.delivery_city                     AS DeliveryTown,
                       orders.delivery_postcode                 AS DeliveryPostcode,
                       orders.delivery_state                    AS DeliveryCounty,
                       deliveryCountry.countries_iso_code_2     AS DeliveryCountry,
                       orders.payment_method                    AS PaymentMethod,
                       orders.orders_status                     AS OrderStatus,
                       customers.ref                            AS Ref
              FROM orders
                     INNER JOIN countries billingCountry ON orders.billing_country = billingCountry.countries_name
                     INNER JOIN countries deliveryCountry ON orders.delivery_country = deliveryCountry.countries_name
                     INNER JOIN orders_status ordersStatus ON orders.orders_status = ordersStatus.orders_status_id
                     LEFT OUTER JOIN postedorders ON postedorders.OrderID = orders.orders_id
                     INNER JOIN customers ON orders.customers_id = customers.customers_id";

        // Limit by Order ID
        if (isset($_GET['orderid']))
        {
            $sql .= " WHERE orders.orders_id = " . $_GET['orderid'];
        }
        else
        {
            $sql .= " WHERE postedorders.PostedDate is null";

            if (C_DOWNLOAD_ORDER_STATUS_FILTER == 'true')
            {
                $sql .= OrderStatusFilter();
            }

            if (C_DOWNLOAD_ORDER_DATE_FILTER == 'true')
            {
                $sql .= " AND orders.date_purchased > '" . C_DOWNLOAD_ORDER_DATE . "'";
            }

            $sql .= " ORDER BY orders.date_purchased ASC";

            if (C_QUERYLIMIT > 0)
            {
                $sql .= " LIMIT 0, " . C_QUERYLIMIT;
            }
        }

        if (C_DOWNLOAD_CUSTOMERS == 'true')
        {
            $customerIDs = "";
        }

        if (C_DOWNLOAD_ORDERS == 'true' || C_DOWNLOAD_INVOICES == 'true')
        {
            $r_newOrders = mysql_query($sql, $cnx) or die("Couldn't get new order data : " . mysql_error() . " SQL: [$sql]");
        }

        // Start XML output
        $xmlDoc = '<?xml version="1.0" encoding="' . C_ENCODING .'"?><Company xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //
        // CREATE SALESORDERS/INVOICES XML

        if (C_DOWNLOAD_ORDERS == 'true' || C_DOWNLOAD_INVOICES == 'true')
        {
            $orderType  = ((C_DOWNLOAD_ORDERS == 'true')) ? "SalesOrder" : "Invoice";
            $orders     = array();

            // Loop through new orders result set
            while($order = mysql_fetch_object($r_newOrders))
            {
                // For each order we get all of the order items and build that as a string
                // then assign it to $order->OrderItems

                // Set current OrderID
                $orderId = $order->OrderNumber;

// NOTES: divide string up into 60
// NOTES 1 = 0-59,
// NOTES 2 = 60-120,
// NOTES 3 = 120-180
// Rest is ignored.

// Notes field is in Table: orders_status_history
// Column: comments
// FK - Order_Id

                $sql = "SELECT orders_products.products_model                                           AS SKU,
                               orders_products.products_name                                            AS Name,
                               orders_products.products_quantity                                        AS QtyOrdered,
                               orders_products.final_price * (1 + (orders_products.products_tax / 100)) AS GrossPrice,
                               orders_products.final_price                                              AS UnitPrice,
                               orders_products.products_tax                                             AS TaxRate,
                               SUM(orders_products.products_quantity * orders_products.final_price)     AS TotalNet,
                               (((orders_products.products_quantity * orders_products.final_price)/100) * orders_products.products_tax) AS TotalTax
                        FROM orders_products
                        WHERE orders_products.orders_id = " . $orderId ."
                        GROUP BY orders_products.products_model,orders_products.products_name,orders_products .products_quantity,orders_products.final_price,orders_products.products_tax";

                // Get all current orders items
                $r_orderItems = mysql_query($sql, $cnx) or die("Couldn't extract order items for current order : " . mysql_error() . " SQL: [$sql]");

                // Set up defaults here

                // Loop through order items result set and add returned XML string to orderItems
                // Needs to be empty on each loop
                $orderItems = '';
                $itemTax    = '0.0';
                $itemNet    = '0.0';

                while($item = mysql_fetch_object($r_orderItems))
                {
                    // Get numeric values
                    $unitPrice  = (is_Numeric($item->TotalNet)) ? $item->TotalNet : '0';
                    $taxRate    = (is_Numeric($item->TaxRate)) ? $item->TaxRate : '0';
                    $taxCode    = ($taxRate > 0) ? C_ITEM_TAX : C_ITEM_TAX_ZERO;
                    $totalNet   = (is_Numeric($item->TotalNet)) ? $item->TotalNet : '0';
                    $totalTax   = (is_Numeric($item->TotalTax)) ? $item->TotalTax : '0';

                    $a_item = array
                    (
                        'Sku'           => $item->SKU,
                        'Name'          => $item->Name,
                        'QtyOrdered'    => $item->QtyOrdered,
                        'UnitPrice'     => saferound($unitPrice, 4),
                        'TaxRate'       => saferound($taxRate,   4),
                        'TotalNet'      => saferound($totalNet,  4),
                        'TotalTax'      => saferound($totalTax,  4),
                        'TaxCode'       => $taxCode
                    );
                    // Append XML to orderItems string
                    outputXMLString('Item', $a_item, $orderItems);

                    $itemTax = $itemTax + saferound($totalTax,  4);
                    $itemNet = $itemNet + saferound($totalNet,  4);
                }

                // Set up data that needs special attention as required.
                $order->BillingCompany  = (($order->BillingCompany != "")) ? $order->BillingCompany : $order->BillingName;
                $order->DeliveryCompany = (($order->DeliveryCompany != "")) ? $order->DeliveryCompany : $order->DeliveryName;

                //Split names as forename and surname in one field
                $billingNames   = splitName($order->BillingName);
                $deliveryNames  = splitName($order->DeliveryName);

                // Get Shipping Information
                $Carriage = GetCarriage($order->OrderNumber, $itemTax);

                // Get comments field and use it as the order notes.
                // Sage notes field is max 60 characters, so anything larger than 180 will
                // not be set.
                $Notes  = GetOrderNotes($order->OrderNumber);

                // Get coupon as NetValueDiscount
                //$coupon = GetCoupon($order->CouponCode);
                $coupon                     = GetCouponFromOrdersTable($orderId);
                $NetValueDiscount           = saferound(0, 2);
                $NetValueDiscountComment    = '';

                if ($coupon)
                {
                    //$NetValueDiscount           = saferound((($coupon['coupon_amount']/(100+$taxRate))*100), 2);
                    // $coupon['value'] is NET
                    $NetValueDiscount           = saferound($coupon['value'], 2);
                    $NetValueDiscountComment    = $coupon['title'];
                }

                // Create Order Node structure with associated result set data
                $a_order = array
                    (
                        'Id'                    => $orderId,
                        'CustomerId'            => $order->CustomerID,
                        $orderType.'Number'     => $orderId,
                        'CustomerOrderNumber'   => $orderId,
                        'AccountReference'      => (C_SINGLEACCOUNT == 'true') ? C_ACCOUNTREF : $order->Ref,
                        'Notes1'                => substr($Notes, 0, 60),
                        'Notes2'                => substr($Notes, 60, 60),
                        'Notes3'                => substr($Notes, 120, 60),
                        'ForeignRate'           => saferound($order->ForeignRate, 2),
                        'Currency'              => $order->Currency,
                        'CurrencyUsed'          => ($order->Currency == 'GBP') ? 'false' : 'true',  // Value needs to be lowercase boolean
                        $orderType.'Date'       => XSDDate($order->OrderDate),                      // Value needs to be XSDDate
                        //'DespatchDate'          => XSDDate(),                                       // Value needs to be XSDDate
                        'OrderNumber'           => $orderId,

                        $orderType.'Address'     => array
                            (
                                'Title'         => '',
                                'Forename'      => ucwords($billingNames['forename']),
                                'Surname'       => ucwords($billingNames['surname']),
                                'Company'       => ucwords($order->BillingCompany),
                                'Address1'      => ucwords($order->BillingAddress1),
                                'Address2'      => ucwords($order->BillingAddress2),
                                'Town'          => ucwords($order->BillingTown),
                                'Postcode'      => strtoupper($order->BillingPostcode),
                                'County'        => ucwords($order->BillingCounty),
                                'Country'       => strtoupper($order->BillingCountry),
                                'Telephone'     => $order->Telephone,
                                'Fax'           => '',
                                'Email'         => $order->Email
                            ),
                        $orderType.'DeliveryAddress' => array
                            (
                                'Title'         => '',
                                'Forename'      => ucwords($deliveryNames['forename']),
                                'Surname'       => ucwords($deliveryNames['surname']),
                                'Company'       => ucwords($order->DeliveryCompany),
                                'Address1'      => ucwords($order->DeliveryAddress1),
                                'Address2'      => ucwords($order->DeliveryAddress2),
                                'Town'          => ucwords($order->DeliveryTown),
                                'Postcode'      => strtoupper($order->DeliveryPostcode),
                                'County'        => ucwords($order->DeliveryCounty),
                                'Country'       => strtoupper($order->DeliveryCountry),
                                'Telephone'     => '',
                                'Fax'           => '',
                                'Email'         => ''
                            ),
                        $orderType.'Items'      => $orderItems,
                        'Carriage'              => $Carriage,
                        $orderType.'Type'       => 'ProductInvoice',
                        'TakenBy'               => C_TAKENBY,
                        'Stage'                 => StatusNameFromID($order->OrderStatus, $cnx)
                    );

                if (C_ALLOCATEPAYMENT == 'true')
                {
                    $a_order['BankAccount']     = C_BANKACCOUNT;
                    $a_order['PaymentRef']      = $order->PaymentMethod;
                    $a_order['PaymentAmount']   = saferound(($itemNet + $itemTax) + ($Carriage['TotalNet'] + $Carriage['TotalTax']), 2);
                    //$a_order['PaymentType']     = '';
                }

                if (C_GLOBAL_NOMINAL <> '')
                {
                    $a_order['GlobalNominalCode']   = C_GLOBAL_NOMINAL;
                }
                if (C_GLOBAL_DETAILS <> '')
                {
                    $a_order['GlobalDetails']       = C_GLOBAL_DETAILS;
                }
                if (C_GLOBAL_TAX <> '')
                {
                    $a_order['GlobalTaxCode']       = C_GLOBAL_TAX;
                }
                if (C_GLOBAL_DEPARTMENT <> '')
                {
                    $a_order['GlobalDepartment']    = C_GLOBAL_DEPARTMENT;
                }

                // Set up array for non-entity checking attributes.
                // This is used in the outputXMLString function below and is used because the OrderItems attribute above was a sub node
                // that has already been output to an XML string ($orderItems) and so does not need to be tested for HTML entity conversion.
                $dontCheck = array($orderType.'Items');

                array_push($orders, $a_order);
            }

            // Orders
            if (C_DOWNLOAD_ORDERS == 'true' || C_DOWNLOAD_INVOICES == 'true')
            {
                $dontCheck = array($orderType.'Items');
                $orderString = "<".$orderType."s>";
                for ( $counter = 0; $counter < count($orders); $counter ++ )
                {
                    // Rather than multiple connections to bring back the customer, execute a single query.
                    if (C_DOWNLOAD_CUSTOMERS == 'true')
                    {
                        $customerIDs .= $orders[$counter]['CustomerId'] . ", ";
                    }

                    $orderString .= outputXMLString($orderType, $orders[$counter], $orderString, $dontCheck);
                }
                $orderString .= "</".$orderType."s>";
            }

            // Customers
            if (C_DOWNLOAD_CUSTOMERS == 'true')
            {
                $customerString = "<Customers>";
                $customers = GetCustomer($customerIDs, true);
                for ( $counter = 0; $counter < count($customers); $counter ++ )
                {
                    $customerString .= outputXMLString("Customer", $customers[$counter], $customerString, $dontCheck);
                }
                $customerString .= "</Customers>";
            }

            // Append XML to xmlDoc string
            $xmlDoc .= $customerString;
            $xmlDoc .= $orderString;
        }
    }
    // Finish off XML output
    $xmlDoc .= "</Company>";


    // Write out to screen
    header("Content-type: application/xml; charset=".C_ENCODING);
    echo $xmlDoc;


    function GetCustomer($customer_id, $batch_get)
    {
        Global $cnx;
        $customers  = array();

        // Get customer details
        $sql = "SELECT DISTINCT customers.customers_id  AS CustomerNumber,
                    address_book.entry_company          AS CompanyName,
                    customers.customers_firstname       AS Forename,
                    customers.customers_lastname        AS Surname,
                    address_book.entry_street_address   AS Address1,
                    address_book.entry_suburb           AS Address2,
                    address_book.entry_City             AS Town,
                    address_book.entry_postcode         AS Postcode,
                    address_book.entry_state            AS County,
                    countries.countries_iso_code_2      AS Country,
                    customers.customers_email_address   AS Email,
                    customers.customers_telephone       AS Telephone,
                    customers.customers_fax             AS Fax,
                    customers.ref                       AS Ref
             FROM customers
                    INNER JOIN address_book ON customers.customers_default_address_id = address_book_id
                    INNER JOIN countries ON address_book.entry_country_id = countries.countries_id
                    INNER JOIN orders ON orders.customers_id = customers.customers_id
                    LEFT OUTER JOIN postedorders ON postedorders.OrderID = orders.orders_id ";

        if ($batch_get)
        {
            // remove trailing ', '
            $customer_id = substr_replace($customer_id,"",-2);
            $sql .= " WHERE customers.customers_id IN (" . $customer_id . ");";
        }
        else
        {
            $sql .= " WHERE customers.customers_id = " . $customer_id . ";";
        }

        if ($customer_id)
        {
            $r_customers = mysql_query($sql, $cnx) or die("Couldn't get customer data : " . mysql_error() . " SQL: [$sql]");

            // Loop through customers result set
            while($customer = mysql_fetch_object($r_customers))
            {
                $a_customer = CustomerData($customer);
                array_push($customers, $a_customer);
            }
        }

        return $customers;
    }

    function CustomerData($customer)
    {
        // Set up data that need special attention as required.
        $customer->CompanyName = (($customer->CompanyName != "")) ? $customer->CompanyName : $customer->Forename . ' ' . $customer->Surname;

        // Create Customer Node structure with associated result set data
        $a_customer = array
            (
                'Id'                => $customer->CustomerNumber,
                'CompanyName'       => ucwords($customer->CompanyName),
                'AccountReference'  => (C_SINGLEACCOUNT == 'false') ? $customer->Ref : C_ACCOUNTREF,
                'TermsAgreed'       => 'true',
                'CustomerInvoiceAddress' => array
                    (
                        'Title'     => '',
                        'Forename'  => ucwords($customer->Forename),
                        'Surname'   => ucwords($customer->Surname),
                        'Company'   => ucwords($customer->CompanyName),
                        'Address1'  => ucwords($customer->Address1),
                        'Address2'  => ucwords($customer->Address2),
                        'Town'      => ucwords($customer->Town),
                        'Postcode'  => strtoupper($customer->Postcode),
                        'County'    => ucwords($customer->County),
                        'Country'   => strtoupper($customer->Country),
                        'Telephone' => $customer->Telephone,
                        'Fax'       => $customer->Fax,
                        'Email'     => $customer->Email
                    ),
                'CustomerDeliveryAddress' => array
                    (
                        'Title'     => '',
                        'Forename'  => ucwords($customer->Forename),
                        'Surname'   => ucwords($customer->Surname),
                        'Company'   => ucwords($customer->CompanyName),
                        'Address1'  => ucwords($customer->Address1),
                        'Address2'  => ucwords($customer->Address2),
                        'Address3'  => '',
                        'Town'      => ucwords($customer->Town),
                        'Postcode'  => strtoupper($customer->Postcode),
                        'County'    => ucwords($customer->County),
                        'Country'   => strtoupper($customer->Country),
                        'Telephone' => $customer->Telephone,
                        'Fax'       => $customer->Fax,
                        'Email'     => $customer->Email,
                    )
            );

        return $a_customer;
    }

    function GetCarriage($orderID, $itemTax)
    {
        Global $cnx;
        $orderVATRate = '0.0';
        $carriage = array
            (
                'Sku'           => '',
                'Name'          => 'Shipping',
                'Description'   => 'Shipping for order ' . $orderID,
                'QtyOrdered'    => '1',
                'UnitPrice'     => '0.0',
                'TotalNet'      => '0.0',
                'TotalTax'      => '0.0',
                'TaxRate'       => '0.0',
                'TaxCode'       => '0'
            );

        // Get shipping totals
        $shipping = GetShippingFromOrdersTable($orderID);

        if ($shipping && $shipping['value'] > 0)
        {
            if ($shipping['value'] > 0)
            {
                //$carriage['totalNet'] = (($shipping['value']/(100+$taxRate))*100);
                $carriage['TotalNet'] = saferound($shipping['value'], 4);

                // Get order VAT
                $sql    = "SELECT value FROM orders_total WHERE class='ot_tax' AND orders_id = '" . $orderID . "'";
                $result = mysql_query($sql, $cnx) or die('Couldn\'t get order ot_tax class from ' . SHOPPING_CART . ' orders_total table: ' . mysql_error());
                if (mysql_num_rows($result) > 0)
                {
                    $row            = mysql_fetch_assoc($result);
                    $orderVATRate   = $row['value'];

                    // There are two tax lines that can be used?
                    $row            = mysql_fetch_assoc($result);
                    if ($row['value'] > $orderVATRate)
                    {
                        $orderVATRate = $row['value'];
                    }

                    $carriage['TotalTax'] = $orderVATRate - $itemTax;
                }

                $carriage['UnitPrice']  = $carriage['TotalNet'];
                //$carriage['UnitPrice']  = saferound(($carriage['TotalNet'] + $carriage['TotalTax']), 2);
                $carriage['TotalTax']   = ($carriage['TotalTax'] < 0) ? 0 : saferound($carriage['TotalTax'], 4);
                $carriage['TaxRate']    = saferound(($carriage['TotalTax'] / $carriage['TotalNet']) * 100, 1);

                // Due to possible rounding issues brought on by the above calculation...
                if($carriage['TaxRate'] > 15 && $carriage['TaxRate']  < 16)
                {
                    $carriage['TaxRate'] = 15;
                }
                elseif($carriage['TaxRate'] > 17 && $carriage['TaxRate']  < 18)
                {
                    $carriage['TaxRate'] = 17.5;
                }
                elseif($carriage['TaxRate'] > 20 && $carriage['TaxRate']  < 21)
                {
                    $carriage['TaxRate'] = 20;
                }

                $carriage['TaxCode']    = ($carriage['TotalTax'] > 0) ? C_CARRIAGE_TAX : C_CARRIAGE_TAX_ZERO;
            }
        }
        else
        {
            $carriage['Name'] = 'Free Shipping';
        }

        return $carriage;
    }

    function GetOrderNotes($orderID)
    {
        Global $cnx;
        $rtnVal = null;
        // Get comments field and use it as the order notes.
        // Sage notes field is max 60 characters, so anything larger than 180 will
        // not be set.
        $sql = "SELECT  orders_status_history.comments AS Notes,
                        orders_status_history.orders_id AS OID
                FROM    orders_status_history
                WHERE   orders_status_history.orders_id = " . $orderID;
        $result = mysql_query($sql, $cnx) or die('Couldn\'t get Notes for order: ' . $orderID . ' in ' . SHOPPING_CART . ' orders_status_history : ' . mysql_error());

        if (mysql_num_rows($result) > 0)
        {
            $row    = mysql_fetch_assoc($result);
            $rtnVal = $row['Notes'];
        }

        return $rtnVal;
    }

    function GetCouponFromCouponsTable($coupon_code)
    {
        Global $cnx;
        $coupons    = null;

        if (!empty($coupon_code))
        {
            $sql        = "SELECT * FROM coupons WHERE coupon_code='" . $coupon_code . "'";
            $result     = mysql_query($sql, $cnx) or die('Couldn\'t get coupon code details for the code: $coupon_code: ' . mysql_error());
            if (mysql_num_rows($result) > 0)
            {
                $coupons = mysql_fetch_assoc($result);
            }
        }

        return $coupons;
    }

    function GetCouponFromOrdersTable($orderID)
    {
        Global $cnx;
        $rtnVal = null;

        // Get order Coupon Code
        $sql    = "SELECT * FROM orders_total WHERE class='ot_coupon' AND orders_id = '" . $orderID . "'";
        $result = mysql_query($sql, $cnx) or die('Couldn\'t get order ot_coupon class from ' . SHOPPING_CART . ' orders_total table: ' . mysql_error());
        if (mysql_num_rows($result) > 0)
        {
            $rtnVal = mysql_fetch_assoc($result);
        }

        return $rtnVal;
    }


    function GetShippingFromOrdersTable($orderID)
    {
        Global $cnx;
        $rtnVal = null;

        // Get order Shipping
        $sql    = "SELECT value FROM orders_total WHERE class='ot_shipping' AND orders_id = '" . $orderID . "'";
        $result = mysql_query($sql, $cnx) or die('Couldn\'t get order ot_shipping class from ' . SHOPPING_CART . ' orders_total table: ' . mysql_error());
        if (mysql_num_rows($result) > 0)
        {
            $rtnVal = mysql_fetch_assoc($result);
        }

        return $rtnVal;
    }

    function GetVATFromOrdersTable($orderID)
    {
        Global $cnx;
        $rtnVal = null;

        // Get order VAT
        $sql    = "SELECT value FROM orders_total WHERE class='ot_tax' AND orders_id = '" . $orderID . "'";
        $result = mysql_query($sql, $cnx) or die('Couldn\'t get order ot_tax class from ' . SHOPPING_CART . ' orders_total table: ' . mysql_error());
        if (mysql_num_rows($result) > 0)
        {
            $rtnVal = mysql_fetch_assoc($result);
        }

        return $rtnVal;
    }
