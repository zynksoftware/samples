<?php
    /**
    *    Script          :    notify.php
    * 
    *    Author          :    Internetware Limited
    *    Copyright       :    Internetware Limited, 2011
    *    Date            :    January 2011
    *    Description     :    Handles notifications from Connect for invoices/customers/sales orders posted to Sage.
    *    Dependancies    :    config.php, functions.php
    */

    include "./core/functions.php";
    include "config.php";

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // MAIN PROGRAM

    // Read from post or file
    if (isset($_GET['file']))
    {
        $filename = $_GET['file'];
        $xmlInput = file_get_contents("$filename");
    }
    else
    {
        $xmlInput = file_get_contents("php://input");
    }

    // Get rid of attributes in <Company> tag as xml2array doesn't like them for some reason?
    $xmlString = preg_replace('/<Company.*?>/', '<Company>', $xmlInput, 1);   

    // Connect to database
    $cnx = dbConnect();

    // Check to see if xml has been posted to this script    
    if (!empty($xmlString))
    {
        // Convert xml string to array
        $xmlData = xml2array($xmlString);           
        
        if (!$xmlData)
        {
            die("Couldn't read XML data");
        }
        UpdateCustomers($xmlData, $cnx);
        UpdateInvoices($xmlData, $cnx);
        UpdateSalesOrders($xmlData, $cnx);
    }

    function UpdateSalesOrders($xmlData, $cnx)
    {
        // Extract SalesOrder Nodes
        $salesOrderNodes =& $xmlData['Company'][0]['SalesOrders'][0]['SalesOrder'];        

        if ($salesOrderNodes)
        {            
            // Loop through Sales Order nodes
            foreach($salesOrderNodes as $node)
            {
                $orderID = $node['Id'];

                $sql = "SELECT Id FROM postedorders WHERE OrderID=" . $orderID;
                $result = mysql_query($sql, $cnx) or die("Couldn't check posted orders table for existing records : " . mysql_error());

                if (mysql_num_rows($result) > 0)
                {
                    // Update the existing record with new posted date
                    $sql = "UPDATE postedorders SET PostedDate = NOW() WHERE OrderId=" . $orderID;
                    $result = mysql_query($sql, $cnx) or die("There was a problem updating the postedorders information in to the database: " . mysql_error());
                }
                else
                {
                    // Insert a new record
                    $sql = "INSERT INTO postedorders (OrderID,PostedDate) VALUES(" . $orderID . ",NOW())";
                    $result = mysql_query($sql, $cnx) or die("There was a problem inserting the postedorders information into the database: " . mysql_error());
                }
                
                // Set order status
                if(C_DOWNLOAD_ORDER_NOTIFY_STATUS_FILTER == 'true')
                {
                    $statusID = StatusIDFromName(C_DOWNLOAD_ORDER_NOTIFY_STATUS, $cnx);
                    if($statusID)
                    {
                        UpdateOrderStatus($orderID, $statusID, $cnx);
                    }
                }
                
            }    
        }
    }

    function UpdateInvoices($xmlData, $cnx)
    {
        // Extract Invoice Nodes
        $invoiceNodes =& $xmlData['Company'][0]['Invoices'][0]['Invoice'];        
    
        if ($invoiceNodes)
        {            
            // Loop through sales order nodes
            foreach($invoiceNodes as $node)
            {
                $orderID = $node['Id'];

                $sql = "SELECT Id FROM postedorders WHERE OrderID=" . $orderID;
                $result = mysql_query($sql, $cnx) or die("Couldn't check posted orders table for existing records : " . mysql_error());

                if (mysql_num_rows($result) > 0)
                {
                    // Update the existing record with new posted date
                    $sql = "UPDATE postedorders SET PostedDate = NOW() WHERE OrderId=" . $orderID;
                    $result = mysql_query($sql, $cnx) or die("There was a problem updating the postedorders information in to the database: " . mysql_error());
                }
                else
                {
                    // Insert a new record
                    $sql = "INSERT INTO postedorders (OrderID,PostedDate) VALUES(" . $orderID . ",NOW())";
                    $result = mysql_query($sql, $cnx) or die("There was a problem inserting the postedorders information into the database: " . mysql_error());
                }
                
                // Set order status
                if(C_DOWNLOAD_ORDER_NOTIFY_STATUS_FILTER == 'true')
                {
                    $statusID = StatusIDFromName(C_DOWNLOAD_ORDER_NOTIFY_STATUS, $cnx);
                    if($statusID)
                    {
                        UpdateOrderStatus($orderID, $statusID, $cnx);
                    }
                }
            }
        }
    }

    function UpdateCustomers($xmlData, $cnx)
    {
        // Extract Customer Nodes
        $customerNodes =& $xmlData['Company'][0]['Customers'][0]['Customer'];
        
        if ($customerNodes)
        {
            // Loop through customers
            foreach($customerNodes as $node)
            {
                $customerID     = $node['Id'];
                $customerRef    = $node['AccountReference'];
                
                // Update the existing record with new ref
                $sql = "UPDATE customers SET ref = '$customerRef' WHERE customers_id = $customerID";
                $result = mysql_query($sql, $cnx) or die("There was a problem updating the customer information in to the database for customer: $customerID : " . mysql_error());      
            }
        }
    }
    
    function UpdateOrderStatus($orderID, $statusID, $cnx)
    {
        // Update the existing record with new posted date
        $sql = "UPDATE orders SET orders_status = $statusID WHERE orders_id = $orderID";
        $result = mysql_query($sql, $cnx) or die("There was a problem updating the orders status in to the database for order: $orderID : " . mysql_error());
    }