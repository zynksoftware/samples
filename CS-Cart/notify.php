<?php
    // Include DevKit file
    include_once('./core/devkit.php');
    include_once('config.php');
    include_once('./core/functions.php');

    // Create new instance of DevKit
    $d = new DevKit();

    // Read from post or file
    if (isset($_GET['file']))
    {
        $filename = $_GET['file'];
        $post = file_get_contents("$filename");
    }
    else
    {
        $post = file_get_contents("php://input");
    }

    // Check for HTTP Post data
    if (!empty($post)) // Run notify
    {
        echo("Running Notify...</br>\n");

        // Convert xml string to array
        $xmlData = xml2array($post);

        // Extract Customer Nodes
        $CustomerNodes =& $xmlData['Company'][0]['Customers'][0]['Customer'];

        if ($CustomerNodes)
        {
            UpdateCustomers($CustomerNodes);
        }

        // Extract Invoice Nodes
        $InvoiceNodes =& $xmlData['Company'][0]['Invoices'][0]['Invoice'];

        if ($InvoiceNodes)
        {
            UpdateInvoices($InvoiceNodes);
        }

        // Extract Sales Order Nodes
        $SalesOrderNodes =& $xmlData['Company'][0]['SalesOrders'][0]['SalesOrder'];

        if ($SalesOrderNodes)
        {
            UpdateSalesOrders($SalesOrderNodes);
        }

        Disconnect($connection);
    }
    else
    {
        echo("No data posted.</br>\n");
    }

    function UpdateCustomers(&$CustomerNodes)
    {
        Global $connection;

        // Loop through sales order nodes
        foreach($CustomerNodes as $c)
        {
            $customerID                 = $c['Id'];
            $customerAccountReference   = $c['AccountReference'];
            $sql        = "UPDATE %s SET %s = '%s' WHERE %s = '%s';";
            $sql        = sprintf($sql, CustomersTable, CustomersTable_AccountReferenceColumn, $customerAccountReference, CustomersTable_IdColumn, $customerID);
            $result     = mysql_query($sql, $connection) or die("Couldn't update Customer: " . $customerID . " - " . $customerAccountReference . " </br>\n$sql</br>\n " . mysql_error() . "</br></br>\n\n");
            echo("Updated Customers Table - Customer [$customerID] $customerAccountReference - Account Reference updated.</br>\n");
            //echo($sql."</br>\n");
        }
    }

    function UpdateInvoices(&$InvoiceNodes)
    {
        Global $connection;

        // Loop through Invoice nodes
        foreach($InvoiceNodes as $i)
        {
            $orderID    = $i['Id'];
            $sql        = "UPDATE %s SET %s = '1', %s = '%s' WHERE %s = '%s';";
            $sql        = sprintf($sql, OrdersTable, OrdersTable_PostedColumn, OrdersTable_StatusColumn, StatusDescriptionsTable_statusValue, OrdersTable_IdColumn, $orderID);
            $result     = mysql_query($sql, $connection) or die("Couldn't update Order: " . $orderID . " </br>\n$sql</br>\n " . mysql_error() . "</br></br>\n\n");
            if(!$result)
            {
                echo("Couldn't update Order: $orderID </br>\n$sql</br>\n ");
            }
            else
            {
                echo("Updated Orders Table - Order [$orderID] marked as posted.</br>\n");
            }
            //echo($sql."</br>\n");
        }
    }
    
    function UpdateSalesOrders(&$SalesOrderNodes)
    {
        Global $connection;

        // Loop through Sales Order nodes
        foreach($SalesOrderNodes as $so)
        {
            $orderID    = $so['Id'];
            $sql        = "UPDATE %s SET %s = '1', %s = '%s' WHERE %s = '%s';";
            $sql        = sprintf($sql, OrdersTable, OrdersTable_PostedColumn, OrdersTable_StatusColumn, StatusDescriptionsTable_statusValue, OrdersTable_IdColumn, $orderID);
            $result     = mysql_query($sql, $connection) or die("Couldn't update Order: " . $orderID . " </br>\n$sql</br>\n " . mysql_error() . "</br></br>\n\n");
            if(!$result)
            {
                echo("Couldn't update Order: $orderID </br>\n$sql</br>\n ");
            }
            else
            {
                echo("Updated Orders Table - Order [$orderID] marked as posted.</br>\n");
            }
            echo($sql."</br>\n");
        }
    }
?>