<?php
    // Include DevKit file
    include_once('..\core\devkit.php');
    include_once('..\config.php');
    include_once('..\core\functions.php');
    include_once('..\..\config.php');

    // Set up an array that will hold checkpoints
    $checkPoints = array('staus' => array(), 'msg' => array());

    // Test 1 check if the user has compatible version of PHP
    if (version_compare(phpversion(), RequiredPHPVersionLB, ">=") && version_compare(phpversion(), RequiredPHPVersionUB, "<=")) 
    {
        $checkPoints['status'][]    = "correct";
        $checkPoints['msg'][]       = "You have a suitable version of PHP installed. (You need " . RequiredPHPVersionLB . " - " . RequiredPHPVersionUB . ", you have ". phpversion() . ")";
    }
    else
    {
        $checkPoints['status'][]    = "error";
        $checkPoints['msg'][]       = "You do not have a suitable version of PHP installed, you have ". phpversion() .". - <strong>This integration has been tested with PHP versions " . RequiredPHPVersionLB . " through to " . RequiredPHPVersionUB . "</strong>";
    }

    // Test 2, check if orders table is setup and correct with the posted column
    $sql    = "SHOW COLUMNS FROM `%s` LIKE '%s'";
    $sql    = sprintf($sql, OrdersTable, OrdersTable_PostedColumn);
    $result = mysql_query($sql, $connection) or die("Couldn't select PostedOrders Column: " . OrdersTable_PostedColumn . " \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

    $tblExists = (mysql_num_rows($result) == 1) ? true : false;
    if(!$tblExists)
    {
        //Create column if not present
        $sql    = "ALTER TABLE `%s` ADD `%s` BINARY(1) NOT NULL DEFAULT '0' COMMENT 'Orders imported by Connect into Sage Line 50';";
        $sql    = sprintf($sql, OrdersTable, OrdersTable_PostedColumn);
        $result = mysql_query($sql, $connection) or die("Couldn't Alter table " . OrdersTable . " to add the Posted Orders column: " . OrdersTable_PostedColumn ." \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

        if(!$result)
        {
            // Table could not be created
            $checkPoints['status'][]    = "error";
            $checkPoints['msg'][]       = "The '" . OrdersTable_PostedColumn . "' column does not exist and creation failed. Please make sure that proper permissions are available to user: " . C_DBUSERNAME;
        }
        else
        {
            // Table created
            $checkPoints['status'][]    = "correct";
            $checkPoints['msg'][]       = "The '" . OrdersTable_PostedColumn . "' column has been created.";
        }
    }
    else
    {
        // Table already exists
        $checkPoints['status'][]    = "correct";
        $checkPoints['msg'][]       = "The '" . OrdersTable_PostedColumn . "' column is already setup in the database.";
    }

    // Test 3, check if customers table is setup and correct with the account reference column
    $sql    = "SHOW COLUMNS FROM `%s` LIKE '%s'";
    $sql    = sprintf($sql, CustomersTable, CustomersTable_AccountReferenceColumn);
    $result = mysql_query($sql, $connection) or die("Couldn't select Account Reference Column: " . CustomersTable . " \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

    $tblExists = (mysql_num_rows($result) == 1) ? true : false;
    if(!$tblExists)
    {
        //Create column if not present
        $sql    = "ALTER TABLE `%s` ADD `%s` VARCHAR(8) NOT NULL COMMENT 'Sage Line Account Reference';";
        $sql    = sprintf($sql, CustomersTable, CustomersTable_AccountReferenceColumn);
        $result = mysql_query($sql, $connection) or die("Couldn't Alter table " . CustomersTable . " to add the Account Reference column: " . CustomersTable_AccountReferenceColumn ." \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

        if(!$result)
        {
            // Table could not be created
            $checkPoints['status'][]    = "error";
            $checkPoints['msg'][]       = "The '" . CustomersTable_AccountReferenceColumn . "' column does not exist and creation failed. Please make sure that proper permissions are available to user: " . C_DBUSERNAME;
        }
        else
        {
            // Table created
            $checkPoints['status'][]    = "correct";
            $checkPoints['msg'][]       = "The '" . CustomersTable_AccountReferenceColumn . "' column has been created.";
        }
    }
    else
    {
        // Table already exists
        $checkPoints['status'][]    = "correct";
        $checkPoints['msg'][]       = "The '" . CustomersTable_AccountReferenceColumn . "' column is already setup in the database.";
    }

    // Test 4, check if status description table is setup and correct with the relevant data
    $sql    = "SELECT * FROM `%s` WHERE %s = '%s' AND %s = '%s' AND %s = '%s' AND %s = '%s' AND %s = '%s' AND %s = '%s';";
    $sql    = sprintf($sql, StatusDescriptionsTable, StatusDescriptionsTable_statusColumn, StatusDescriptionsTable_statusValue, StatusDescriptionsTable_typeColumn, StatusDescriptionsTable_typeValue, StatusDescriptionsTable_descriptionColumn, StatusDescriptionsTable_descriptionValue, StatusDescriptionsTable_email_subjColumn, StatusDescriptionsTable_email_subjValue, StatusDescriptionsTable_email_headerColumn, StatusDescriptionsTable_email_headerValue, StatusDescriptionsTable_lang_codeColumn, StatusDescriptionsTable_lang_codeValue);
    $result = mysql_query($sql, $connection) or die("Couldn't select " . StatusDescriptionsTable . " table. \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

    $tblExists = (mysql_num_rows($result) == 1) ? true : false;
    if(!$tblExists)
    {
        // Create data if not present 
        $sql    = "INSERT INTO `%s`(%s, %s, %s, %s, %s, %s) VALUES ('%s', '%s', '%s', '%s', '%s', '%s');";
        $sql    = sprintf($sql, StatusDescriptionsTable, StatusDescriptionsTable_statusColumn, StatusDescriptionsTable_typeColumn, StatusDescriptionsTable_descriptionColumn, StatusDescriptionsTable_email_subjColumn, StatusDescriptionsTable_email_headerColumn, StatusDescriptionsTable_lang_codeColumn, StatusDescriptionsTable_statusValue, StatusDescriptionsTable_typeValue, StatusDescriptionsTable_descriptionValue, StatusDescriptionsTable_email_subjValue, StatusDescriptionsTable_email_headerValue, StatusDescriptionsTable_lang_codeValue);
        $result = mysql_query($sql, $connection) or die("Couldn't Alter table " . StatusDescriptionsTable . " to add the " . type . " data. \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

        if(!$result)
        {
            // Data could not be inserted
            $checkPoints['status'][]    = "error";
            $checkPoints['msg'][]       = "The '" . StatusDescriptionsTable . "' " . type . " data does not exist and creation failed. Please make sure that proper permissions are available to user: " . C_DBUSERNAME;
        }
        else
        {
            // Table created
            $checkPoints['status'][]    = "correct";
            $checkPoints['msg'][]       = "The '" . StatusDescriptionsTable . "' " . type . " data has been inserted.";
        }
    }
    else
    {
        // Table already exists
        $checkPoints['status'][]    = "correct";
        $checkPoints['msg'][]       = "The '" . StatusDescriptionsTable . "' " . type . " data is already setup in the database.";
    }

    // Test 5, check if statuses table is setup and correct with the relevant data
    $sql    = "SELECT * FROM `%s` WHERE %s = '%s' AND %s = '%s' AND %s = '%s';";
    $sql    = sprintf($sql, StatusesTable, StatusesTable_statusColumn, StatusDescriptionsTable_statusValue, StatusesTable_typeColumn, StatusDescriptionsTable_typeValue, StatusesTable_is_defaultColumn, StatusesTable_is_defaultValue);
    $result = mysql_query($sql, $connection) or die("Couldn't select " . StatusDescriptionsTable . " table. \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

    $tblExists = (mysql_num_rows($result) == 1) ? true : false;
    if(!$tblExists)
    {
        // Create data if not present 
        $sql    = "INSERT INTO `%s`(%s, %s, %s) VALUES ('%s', '%s', '%s');";
        $sql    = sprintf($sql, StatusesTable, StatusesTable_statusColumn, StatusesTable_typeColumn, StatusesTable_is_defaultColumn, StatusDescriptionsTable_statusValue, StatusDescriptionsTable_typeValue, StatusesTable_is_defaultValue);
        $result = mysql_query($sql, $connection) or die("Couldn't Alter table " . StatusesTable . " to add the " . type . " data. \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

        if(!$result)
        {
            // Data could not be inserted
            $checkPoints['status'][]    = "error";
            $checkPoints['msg'][]       = "The '" . StatusesTable . "' " . type . " data does not exist and creation failed. Please make sure that proper permissions are available to user: " . C_DBUSERNAME;
        }
        else
        {
            // Table created
            $checkPoints['status'][]    = "correct";
            $checkPoints['msg'][]       = "The '" . StatusesTable . "' " . type . " data has been inserted.";
        }
    }
    else
    {
        // Table already exists
        $checkPoints['status'][]    = "correct";
        $checkPoints['msg'][]       = "The '" . StatusesTable . "' " . type . " data is already setup in the database.";
    }

    // Test 6, check if all table columns are available for the upload/download scripts
    // Set up an array of the tables and their fields to check
    $tables = array
        (
            OrdersTable             => array(OrdersTable_PostedColumn),
            CustomersTable          => array(CustomersTable_AccountReferenceColumn),
            StatusDescriptionsTable => array(StatusDescriptionsTable_statusColumn, StatusDescriptionsTable_typeColumn, StatusDescriptionsTable_descriptionColumn, StatusDescriptionsTable_email_subjColumn, StatusDescriptionsTable_email_headerColumn, StatusDescriptionsTable_lang_codeColumn),
        );

    // For each needed table check if it exists and if correct fields are available

    // Flag to determine if any table errors present
    $tableErrorsFlag = false;

    foreach ($tables as $tableName => $fields)
    {
        //Check it exists
        $sql    = "SHOW TABLE STATUS LIKE '%s'";
        $sql    = sprintf($sql, $tableName);
        $result = mysql_query($sql, $connection) or die("Couldn't Select table: " . $tableName . " \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

        $exists = (mysql_num_rows($result) == 1) ? true : false;

        if ($exists)
        {
            $msg        = "Some field(s) in the table '$tableName' are missing.";
            $str_fields = '';
            
            //Check if right columns available
            $sql    = "SHOW COLUMNS FROM `%s`";
            $sql    = sprintf($sql, $tableName);
            $result = mysql_query($sql, $connection) or die("Couldn't Select columns from table: " . $tableName . " \n</br>$sql\n</br> " . mysql_error() . "\n\n</br></br>");

            if (mysql_num_rows($result) > 0) 
            {
                $allFields  = array();
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
                    
            $checkPoints['status'][]    = "error";
            $checkPoints['msg'][]       = "The required table '$tableName' is not setup in the database.<br /><br />The following fields are required: $str_fields";
        }
    }

    if (!$tableErrorsFlag)
    {
        $checkPoints['status'][] = "correct";
        $checkPoints['msg'][] = "You have a suitable version of " . ShoppingCart . " installed.";
    }


    // Build list of points in html
    $numErrors          = false;
    $numPoints          = count($checkPoints['status']);
    $checkListSummary   = '';
    $checkListPoints    = '';

    for($i = 0; $i < $numPoints; $i++)
    {
        if ($checkPoints['status'][$i] == "error")
        {
            $numErrors = true;
        }
        $checkListPoints .= '        <li class="' . $checkPoints['status'][$i] . '">' . $checkPoints['msg'][$i] .'</li>' . "\n";
    }

    if ($numErrors)
    {
        $checkListSummary .= "<br/>";
        $checkListSummary = "It appears that your " . ShoppingCart . " setup is not compatible with this plugin. This can be corrected by taking one of the options below.<br/><br/>";
        $checkListSummary .= "# Ensure you are using the correct version of " . ShoppingCart . " (This plugin was designed to work with version " . ShoppingCartVersion . ")<br/>";
        $checkListSummary .= "# Ask your web developer to customise the download and upload scripts to work with your " . ShoppingCart . " version.";
    }
    else
    {
        $checkListSummary .= "<br/>";
        $checkListSummary = "It appears that everything is setup for the integration.<br/><br/>";
    }
    
    echo("<html>");
    echo("<head>");
            echo("<title>".ShoppingCart."Setup</title>");
            echo("<link rel='stylesheet' type='text/css' href='setup.css' /> ");
        echo("</head>");
        echo("<body>");
            echo("<div id='header'><image src='pix/banner.gif' width='750' height='50' alt='http://www.internetware.co.uk/' /></div>");
                echo("<div id='pageBody'>");
                    echo("<p id='title'>".ShoppingCart." v".PRODUCT_VERSION." ".SageVersion." Integration Setup</p>");
                    echo("<p>Currently checking the setup of this system...</p>");
                        echo("<ul>");
                            echo($checkListPoints);
                        echo("</ul>");
                        echo("<br/>");
                    echo("<p>".$checkListSummary."</p>");
                echo("</div>");
            echo("<div id='footer'>&copy; Internetware 2010</div>");
        echo("</body>");
    echo("</html>");

?>