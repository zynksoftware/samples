<?php

    /**
        * Script          :   setup.php
        * Author          :   Internetware
        * Date            :   January 2011
        * Updated         :   January 2011
        * Description     :   Check whether the correct version of Cart is installed.
        * Dependancies    :   config.php, functions.php
        */

    include_once('../config.php');
    include_once('../core/functions.php');

    $cnx = dbConnect();

    // Set up an array that will hold checkpoints
    $checkPoints = array('staus' => array(), 'msg' => array());

    // Test 1 check if the user has new enough version of PHP
    if (version_compare(phpversion(), "4.3.0", ">="))
    {
        // you're on 4.3.0 or later
        $checkPoints['status'][] = "correct";
        $checkPoints['msg'][] = "You have a suitable version of PHP installed. (You need at least 4.3.0, you have ". phpversion() . ")";
    }
    else
    {
        // you're on earlier than 4.3.0
        $checkPoints['status'][] = "error";
        $checkPoints['msg'][] = "You do not have a suitable version of PHP installed. - <strong>You must install PHP 4.3.0 or later for the system to work</strong>";
    }

    // Test 2, check if postedorders table is setup and correct

    $result = @mysql_query("SHOW TABLE STATUS LIKE 'postedorders'");
    $tblExists = (mysql_num_rows($result) == 1) ? true : false;
    if(!$tblExists)
    {
        //Create table if not present
        $sql = "CREATE TABLE `postedorders`
                (
                    `Id` int(11) NOT NULL auto_increment,
                    `OrderID` int(11) NOT NULL default '0',
                    `PostedDate` datetime default NULL,
                    PRIMARY KEY  (`Id`),
                    KEY `OrderID` (`OrderID`)
                ) DEFAULT CHARSET=latin1";
        $result = @mysql_query($sql, $cnx);

        if(!$result)
        {
            // Table could not be created
            $checkPoints['status'][] = "error";
            $checkPoints['msg'][] = "The 'postedorders' tables does not exist and creation failed. Please make sure that proper permissions are available to user: " . C_DBUSERNAME;
        }
        else
        {
            // Table created
            $checkPoints['status'][] = "correct";
            $checkPoints['msg'][] = "The 'postedorders' table has been created.";
        }
    }
    else
    {
        // Table already exists
        $checkPoints['status'][] = "correct";
        $checkPoints['msg'][] = "The 'postedorders' table is already setup in the database.";
    }

    // Test 3, check if column 'ref' in Customers table is present
    $result = @mysql_query("SHOW COLUMNS FROM `customers` LIKE 'ref'");
    $clmExists = (mysql_num_rows($result) == 1) ? true : false;

    if(!$clmExists || $clmExists="")
    {
        //Create Column if not present
        $sql = "ALTER TABLE `customers` ADD `ref` VARCHAR( 8 ) NOT NULL" ;
        $result = @mysql_query($sql, $cnx);

        if(!$result)
        {
            // Column could not be created
            $checkPoints['status'][] = "error";
            $checkPoints['msg'][] = "The column 'ref' does not exist and creation failed. Please make sure that proper permissions are available to user: " . C_DBUSERNAME;
        }
        else
        {
            // Column created
            $checkPoints['status'][] = "correct";
            $checkPoints['msg'][] = "The column 'ref' has been created.";
        }
    }
    else
    {
        // Column already exists
        $checkPoints['status'][] = "correct";
        $checkPoints['msg'][] = "The column 'ref' is already setup in the database.";
    }


    // Test 4, check if all table columns are available for the upload/download scripts

    // Set up an array of the tables and their fields to check
    $tables = array
        (
            'customers'					=> array('customers_id','customers_firstname','customers_lastname','customers_email_address','customers_telephone','customers_fax','customers_default_address_id'),
            'address_book'				=> array('address_book_id','entry_company','entry_street_address','entry_suburb','entry_city','entry_postcode','entry_state','entry_country_id'),
            'countries'					=> array('countries_id','countries_name','countries_iso_code_2'),
            'orders'					=> array('orders_id','customers_id','currency_value','currency','date_purchased','customers_telephone','customers_email_address','billing_name','billing_company','billing_street_address','billing_suburb','billing_city','billing_postcode','billing_state','delivery_name','delivery_company','delivery_street_address','delivery_suburb','delivery_city','delivery_postcode','delivery_state','billing_country','delivery_country'),
            'orders_products' 			=> array('orders_id','products_model','products_name','products_quantity','final_price','products_tax'),
            'orders_total'				=> array('value','class','orders_id'),
            'products' 					=> array('products_id', 'products_model', 'products_quantity', 'products_price', 'products_date_added', 'products_weight'),
            'products_description'		=> array('products_id', 'products_name', 'products_description', 'language_id'),
            'products_to_categories'	=> array('products_id', 'categories_id'),
            'categories'				=> array('sort_order', 'date_added'),
            'categories_description'	=> array('categories_id', 'language_id', 'categories_name')
        );

    // For each needed table check if it exists and if correct fields are available

    // Flag to determine if any table errors present
    $tableErrorsFlag = false;

    foreach ($tables as $tableName => $fields)
    {
        //Check it exists
        $result = @mysql_query("SHOW TABLE STATUS LIKE '$tableName'");
        $exists = (mysql_num_rows($result) == 1) ? true : false;

        if ($exists)
        {
            $msg = "Some field/s in the table '$tableName' are missing.";
            $str_fields = '';

            //Check if right columns available
            $result = mysql_query("SHOW COLUMNS FROM $tableName") or die('Could not run query: ' . mysql_error());
            if (mysql_num_rows($result) > 0)
            {
                $allFields = array();
                while ($row = mysql_fetch_assoc($result))
                {
                    $allFields[] = $row['Field'];
                }

                $missingFlag = false;

                foreach ($fields as $field)
                {
                    // Check if each required field exists in tbale fields array (allFields)
                    if (!in_array($field, $allFields))
                    {
                        $str_fields .= "'$field', ";
                        $missingFlag = true;
                    }
                }

                // If there are missing fields
                if ($missingFlag)
                {
                    // Get rid of last comma
                    $str_fields = substr($str_fields, 0, -2);
                    // Finish off error msg
                    $msg .= "<br /><br />The following fields are required: $str_fields";
                    // Set checkPoint as an error and set message
                    $checkPoints['status'][] = "error";
                    $checkPoints['msg'][] = $msg;

                    // Set the flag to say at least one table error has occured
                    $tableErrorsFlag = true;
                }
            }
            else
            {
                // No fields present
                // Set checkPoint as error and set message
                $checkPoints['status'][] = "error";
                $checkPoints['msg'][] = "The '$tableName' table appears to have no fields/columns set up";
            }
        }
        else
        {
            // table doesn't exist

            // Get fields for this table
            $str_fields = '';
            foreach($fields as $field)
            {
                $str_fields .= "'$field', ";
            }
            // Get rid of last comma
            $str_fields = substr($str_fields, 0, -2);

            $checkPoints['status'][] = "error";
            $checkPoints['msg'][] = "The required table '$tableName' is not setup in the database.<br /><br />The following fields are required: $str_fields";
        }
    }

    if (!$tableErrorsFlag)
    {
        $checkPoints['status'][] = "correct";
        $checkPoints['msg'][] = "You have a suitable version of " . SHOPPING_CART . " installed.";
    }

    // Build list of points in html
    $numErrors = false;
    $numPoints = count($checkPoints['status']);
    $checkListPoints = '';
    for($i = 0; $i < $numPoints; $i++)
    {
        if ($checkPoints['status'][$i] == "error")
        {
            $numErrors = true;
        }
        $checkListPoints .= '        <li class="' . $checkPoints['status'][$i] . '">' . $checkPoints['msg'][$i] .'</li>' . "\n";
    }

    $checkListSummary = '';
    if ($numErrors)
    {
        $checkListSummary = "It appears that your " . SHOPPING_CART . " setup is not compatible with this plugin. This can be corrected by taking one of the options below.<br/><br/>";
        $checkListSummary .= "1) Upgrade your version of " . SHOPPING_CART . " (This plugin was designed to work with version  " . SHOPPING_CART_VERSION . " )<br/>";
        $checkListSummary .= "2) Ask your web developer to customise the download and upload scripts to work with your " . SHOPPING_CART . " version.";
    }

?>
<html>
    <head>
        <title><?php echo SHOPPING_CART ?> Setup</title>
        <link rel="stylesheet" type="text/css" href="setup.css" /> 
    </head>
    <body>
        <div id="header"><image src="pix/banner.gif" width="750" height="50" alt="http://www.internetware.co.uk/" /></div>
            <div id="pageBody">
                <p id="title"><?php echo  SHOPPING_CART ?> v<?php echo  SHOPPING_CART_VERSION ?> Integration Setup</p>
                <p>Currently checking the setup of this system...</p>
                    <ul>
                        <?php echo  $checkListPoints ?>
                    </ul>
                    <br/>
                <p><?php echo  $checkListSummary ?></p>
            </div>
        <div id="footer">&copy; Internetware 2011</div>
    </body>
</html>