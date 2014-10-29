<?php

// Script needs more memory
ini_set("memory_limit","800M");

    /**
    *    Script          :    upload.php
    *
    *    Author          :    Internetware Limited
    *    Copyright       :    Internetware Limited, 2011
    *    Date            :    January 2011
    *    Description     :    Upload data from Sage to eCommerce database.
    *                         Export Customers, Export Products, Export Stock Levels, Export Product Prices.
    *    Dependancies    :    config.php, functions.php
    */

    include_once "config.php";
    include_once "./core/functions.php";

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

    // Check to see if xml has been sent via a HTTP POST
    if (!empty($xmlString))
    {
        // Convert xml string to array
        $xmlData = xml2array($xmlString);

        if (!$xmlData)
        {
            die("Couldn't read XML data.");
        }

        // Connect to database
        $cnx = dbConnect();

        $languageId = GetLanguageId($cnx);

        // Upload the whole product information, or only specific
        if (C_UPLOAD_PRODUCTS == 'true')
        {
            // Make a category for products that don't have a group
            CreateNonGroupedCategory($cnx, $languageId);

            ExportProducts($cnx, $languageId, $xmlData);
        }
        else
        {
            // Update stock levels for existing products
            if (C_UPLOAD_PRODUCT_STOCK == 'true')
            {
                ExportProductStock($cnx, $languageId, $xmlData);
            }

            // Update prices for existing products
            if (C_UPLOAD_PRODUCT_PRICE == 'true')
            {
                ExportProductPrice($cnx, $languageId, $xmlData);
            }
        }

        if (C_UPLOAD_CUSTOMERS == 'true')
        {
            ExportCustomers($cnx, $languageId, $xmlData);
        }

        mysql_close();
    }

    function GetLanguageId($cnx)
    {
        // Get Language Id
        $sql = "SELECT languages_id FROM languages WHERE code='en'";
        $result = mysql_query($sql, $cnx) or die("Couldn't get language Id from database : " . mysql_error());

        if (mysql_num_rows($result) > 0)
        {
            $row = mysql_fetch_assoc($result);
            $languageId = $row['languages_id'];
        }
        else
        {
            $languageId = 1;
        }

        return $languageId;
    }

    function ExportCustomers($cnx, $languageId, $xmlData)
    {
        // Retrieve Customers from XML
        $custNodes =& $xmlData['Company'][0]['Customers'][0]['Customer'];

        if ($custNodes)
        {
            foreach ($custNodes as $node)
            {
                $custAccRef     = htmlspecialchars($node['AccountReference'],  ENT_QUOTES);
                $custTitle      = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Title'],  ENT_QUOTES));
                $custForename   = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Forename'],  ENT_QUOTES));
                $custSurname    = htmlspecialchars($node['CustomerInvoiceAddress'][0]['Surname'],  ENT_QUOTES);
                $custCompany    = htmlspecialchars($node['CustomerInvoiceAddress'][0]['Company'],  ENT_QUOTES);
                $custAdd1       = htmlspecialchars($node['CustomerInvoiceAddress'][0]['Address1'],  ENT_QUOTES);
                $custAdd2       = htmlspecialchars($node['CustomerInvoiceAddress'][0]['Address2'],  ENT_QUOTES);
                $custAdd3       = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Address3'],  ENT_QUOTES));
                $custTown       = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Town'],  ENT_QUOTES));
                $custPostcode   = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Postcode'],  ENT_QUOTES));
                $custCounty     = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['County'],  ENT_QUOTES));
                $custCountry    = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Country'],  ENT_QUOTES));
                $custTelephone  = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Telephone'],  ENT_QUOTES));
                $custFax        = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Fax'],  ENT_QUOTES));
                $custMobile     = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Mobile'],  ENT_QUOTES));
                $custEmail      = nl2br(htmlspecialchars($node['CustomerInvoiceAddress'][0]['Email'],  ENT_QUOTES));
                $custGender     = C_DEFAULT_CUSTOMER_GENDER;
                $custPassword   = C_DEFAULT_CUSTOMER_PASSWORD;

                // Check if each Customer from XML has an associated record in the customer table
                $sql    = "SELECT customers_id
                            FROM customers
                            WHERE ref = '$custAccRef'";
                $result = mysql_query($sql, $cnx) or die("Couldn't get customers from database : " . mysql_error());
                $num_rows = mysql_num_rows($result);

                if ($num_rows == 0)
                {
                    if(!$custAccRef){$custAccRef = " ";}
                    if(!$custForename){$custForename = " ";}
                    if(!$custSurname){$custSurname = " ";}
                    if(!$custEmail){$custEmail = " ";}
                    if(!$custTelephone){$custTelephone = " ";}
                    if(!$custFax){$custFax = " ";}

                    // Create a new customer record for current customer node
                   $sql     = "INSERT INTO customers (customers_firstname, customers_lastname, customers_gender, customers_email_address, customers_telephone, customers_fax, customers_password, ref)
                                VALUES ('$custForename', '$custSurname', '$custGender', '$custEmail', '$custTelephone', '$custFax', '$custPassword', '$custAccRef')";
                   $result  = mysql_query($sql, $cnx) or die("Couldn't insert new customer record into customers table : " . mysql_error() . " SQL: [$sql]");

                    // Get new customer record Id from last insert
                    $newCustId = mysql_insert_id();

                    // Create record for customer info table
                    $sql    = "INSERT INTO customers_info(customers_info_id,customers_info_number_of_logons)
                                    VALUES('$newCustId', '0')";
                    $result = mysql_query($sql,$cnx) or die("Couldn't insert new customer info record into customer info table : " . mysqlerror());

                    // Get country id
                    $sql        = "SELECT countries_id FROM countries WHERE countries_iso_code_2 = '$custCountry'";
                    $result     = mysql_query($sql, $cnx) or die("Couldn't get countries from database : " . mysql_error() . " SQL: [$sql]");
                    $row        = mysql_fetch_assoc($result);
                    $countryId  = $row['countries_id'];

                    if(!$custCompany){$custCompany = " ";}
                    if(!$custForename){$custForename = " ";}
                    if(!$custSurname){$custSurname = " ";}
                    if(!$custAdd1){$custAdd1 = " ";}
                    if(!$custAdd2){$custAdd2 = " ";}
                    if(!$custPostcode){$custPostcode = " ";}
                    if(!$custTown){$custTown = " ";}
                    if(!$custCounty){$custCounty = " ";}

                    $sql    = "INSERT INTO address_book (customers_id, entry_company, entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_state, entry_country_id, entry_gender)
                                VALUES ('$newCustId', '$custCompany','$custForename', '$custSurname', '$custAdd1', '$custAdd2', '$custPostcode', '$custTown', '$custCounty', '$countryId', '$custGender')";
//echo "SQL: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("Couldn't insert new address record into address book table : " . mysql_error() . " SQL: [$sql]");

                    // Get new address record Id from last insert
                    $newAddId = mysql_insert_id();

                    //Update the customer record with address id
                    $sql    = "UPDATE customers
                                SET customers_default_address_id = $newAddId
                                WHERE customers_id = $newCustId";
//echo "SQL: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("Couldn't update customer in customers table : " . mysql_error() . " SQL: [$sql]");

                }
                elseif ($num_rows > 0)
                {
                    // Customer record found so extract id from resultset
                    $row    = mysql_fetch_assoc($result);
                    $custId = $row['customers_id'];

                    if(!$custAccRef){$custAccRef = " ";}
                    if(!$custForename){$custForename = " ";}
                    if(!$custSurname){$custSurname = " ";}
                    if(!$custEmail){$custEmail = " ";}
                    if(!$custTelephone){$custTelephone = " ";}
                    if(!$custFax){$custFax = " ";}

                    // Update customer details in customer table
                    $sql    = "UPDATE customers
                                SET customers_firstname = '$custForename', customers_lastname = '$custSurname', customers_email_address = '$custEmail', customers_telephone = '$custTelephone', customers_fax = '$custFax', ref = '$custAccRef'
                                WHERE customers_id = $custId";
//echo "SQL: " . $sql . "\n";
                    $result     = mysql_query($sql, $cnx) or die("Couldn't update customer in customer table : " . mysql_error() . " SQL: [$sql]");

                    // Get default address id
                    $sql        = "SELECT customers_default_address_id FROM customers WHERE customers_id = '$custId'";
                    $result     = mysql_query($sql, $cnx) or die("Couldn't get address book id from database : " . mysql_error() . " SQL: [$sql]");
                    $row        = mysql_fetch_assoc($result);
                    $addressId  = $row['customers_default_address_id'];

                    // Get country id
                    $sql        = "SELECT countries_id FROM countries WHERE countries_iso_code_2 = '$custCountry'";
                    $result     = mysql_query($sql, $cnx) or die("\n\nCouldn't get countries from database : " . mysql_error() . " SQL: [$sql]");
                    $row        = mysql_fetch_assoc($result);
                    $countryId  = $row['countries_id'];

                    // Check if customer has an associated record in the address_book table
                    $sql = "SELECT address_book_id FROM address_book WHERE customers_id = '$custId'";
//echo "SQL: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("\n\nCouldn't get address_book_id from address_book table : " . mysql_error() . " SQL: [$sql]");

                    $num_rows = mysql_num_rows($result);

                    // If no matching Address is found then create a new one, otherwise update existing.
                    if ($num_rows == 0)
                    {
                        $sql    = "INSERT INTO address_book (customers_id, entry_company, entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_state, entry_country_id, entry_gender)
                                    VALUES ('$custId', '$custCompany','$custForename', '$custSurname', '$custAdd1', '$custAdd2', '$custPostcode', '$custTown', '$custCounty', '$countryId', '$custGender')";
//echo ($sql . "\n");
                        $result = mysql_query($sql, $cnx) or die("\n\nCouldn't insert new address record into " . SHOPPING_CART . " address book table : " . mysql_error() . " SQL: [$sql]");

                        // Get new address record Id from last insert
                        $newAddId = mysql_insert_id();

                        // Update the customer record with address id
                        $sql    = "UPDATE customers
                                    SET customers_default_address_id = $newAddId
                                    WHERE customers_id = $custId";
                        $result = mysql_query($sql, $cnx) or die("\n\nCouldn't update customer in " . SHOPPING_CART . " customers table : " . mysql_error() . " SQL: [$sql]");
                    }
                    else
                    {
                        // Update customer address details in address book table
                        $sql = "UPDATE address_book
                                    SET entry_company = '$custCompany', entry_firstname = '$custForename', entry_lastname = '$custSurname', entry_suburb = '$custAdd2', entry_postcode = '$custPostcode', entry_city = '$custTown', entry_state = '$custCounty', entry_country_id = '$countryId'
                                    WHERE customers_id = $custId AND address_book_id = $addressId";
//echo ($sql . "\n");
                        $result = mysql_query($sql, $cnx) or die("\n\nCouldn't update customer address in " . SHOPPING_CART . " address book table : " . mysql_error() . " SQL: [$sql]");
                    }
                }
            }
        }
    }

    // Make a category for products that don't have a group
    function CreateNonGroupedCategory($cnx, $languageId)
    {
        // Check if non grouped category exists in categories table
        $sql = "SELECT categories_id FROM categories_description WHERE language_id =".$languageId." AND categories_name = '".C_NON_GROUPED_CAT."'";
        $result = mysql_query($sql, $cnx) or die("Couldn't get groupnames from database : " . mysql_error() . " SQL: [$sql]");

        // If no matching category record is found, create a new one with ProductGroup info (NONGROUPED).
        if (mysql_num_rows($result) == 0)
        {
            // Create new category record
            $sql = "INSERT INTO categories (sort_order, date_added) VALUES(0, NOW())";
            mysql_query($sql, $cnx) or die("Couldn't insert new category record into database : " . mysql_error() . " SQL: [$sql]");

            // Get new category record Id from last insert
            $newCatId = mysql_insert_id();

            // Create a new corresponding category description record
            $sql = "INSERT INTO categories_description (categories_id, categories_name, language_id) VALUES ($newCatId, '".C_NON_GROUPED_CAT."', $languageId)";
            mysql_query($sql, $cnx) or die("Couldn't insert new category description record into database : " . mysql_error() . " SQL: [$sql]");
        }
    }

    function ExportProducts($cnx, $languageId, $xmlData)
    {
        // Retrieve Products from XML
        $prodNodes =& $xmlData['Company'][0]['Products'][0]['Product'];

        if ($prodNodes)
        {
            foreach ($prodNodes as $node)
            {
                $prodSku                = htmlspecialchars($node['Sku'],                    ENT_QUOTES);
                $prodName               = htmlspecialchars($node['Name'],                   ENT_QUOTES);
                $prodDescription        = htmlspecialchars($node['Description'],            ENT_QUOTES);
                $prodLongDescription    = nl2br(htmlspecialchars($node['LongDescription'],  ENT_QUOTES));
                $prodSalePrice          = htmlspecialchars($node['SalePrice'],              ENT_QUOTES);
                $prodUnitWeight         = htmlspecialchars($node['UnitWeight'],             ENT_QUOTES);
                $prodQtyInStock         = htmlspecialchars($node['QtyInStock'],             ENT_QUOTES);
                $prodGroupCode          = htmlspecialchars($node['GroupCode'],              ENT_QUOTES);
                $prodPublish            = htmlspecialchars($node['Publish'],                ENT_QUOTES);
                $prodTaxClass           = $node['TaxCode'];
                if ($prodTaxClass == null){$prodTaxClass = 1;}
                $prodImage              = htmlspecialchars($node['Image'],                  ENT_QUOTES);
                $prodImageName          = htmlspecialchars($node['ImageName'],              ENT_QUOTES);
                // Not Allowed in file names: \ / : * " < > |
                $notAllowed             = array("\\", "/", "*", "<", ">", "\"", "|");
                $prodImageName          = str_replace($notAllowed, "", $prodImageName);
                $categoryId             = 0;

                // Don't upload the product if option set to true, and product is not set as published
                if (C_UPLOAD_PUBLISHED_PRODUCTS == 'true' && $prodPublish == "false")
                    break;

                // Put into uncategorized group
                // Get categoryId using category title from XML
                $sql        = "SELECT categories_id FROM categories_description WHERE language_id = $languageId AND categories_name = '".C_NON_GROUPED_CAT."'";
//echo "SQL categories_id: " . $sql . "\n";
                $result     = mysql_query($sql, $cnx) or die("Couldn't get a category id from categories_description table : " . mysql_error() . " SQL: [$sql]");
                $row        = mysql_fetch_assoc($result);
                $categoryId = $row['categories_id'];

                // Check if each Product from XML has an associated record in the products table
                $productID  = GetProductIDFromSku($cnx, $prodSku);

                // If no matching product record is found, create a new one with ProductGroup info (groupName).
                if ($productID == null)
                {
                    $prodPublish = ($prodPublish == "true") ? 1 : 0;

                    // Create a new product record for current product node
                   $sql = "INSERT INTO products (products_model, products_quantity, products_price, products_date_added, products_weight, products_status, products_image, products_tax_class_id)
                           VALUES ('$prodSku', $prodQtyInStock, $prodSalePrice, NOW(), $prodUnitWeight, $prodPublish, '$prodImageName', $prodTaxClass)";
//echo "SQL INSERT products: " . $sql . "\n";
                   $result = mysql_query($sql, $cnx) or die("Couldn't insert new product record into " . SHOPPING_CART . " products table : " . mysql_error() . " SQL: [$sql]");
                   if($prodImageName != "" && $prodImage != "")
                   {
                        saveImage( $prodImage, C_IMAGEPATH . $prodImageName );
                   }
                    // Get new product record Id from last insert
                    $newProdId = mysql_insert_id();

                    $sql = "INSERT INTO products_description (products_id, products_name, products_description, language_id)
                            VALUES ($newProdId, '$prodName','$prodLongDescription', $languageId)";
//echo "SQL INSERT products_description: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("Couldn't insert new product description record into products_description table : " . mysql_error() . " SQL: [$sql]");

                    // Insert Product / Category relation
                    $sql = "INSERT INTO products_to_categories (products_id, categories_id)
                            VALUES($newProdId, $categoryId)";
//echo "SQL INSERT products_to_categories: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("Couldn't insert new products_to_categories relation : " . mysql_error() . " SQL: [$sql]");
                }
                elseif ($productID)
                {
                    $prodPublish = ($prodPublish == "true") ? 1 : 0;

                    // Update product details in products table
                    $sql = "UPDATE products
                            SET products_quantity = $prodQtyInStock, products_price = $prodSalePrice, products_weight = $prodUnitWeight, products_tax_class_id = $prodTaxClass, products_status = $prodPublish, products_last_modified = NOW()
                            WHERE products_id = $productID ";
//echo "SQL UPDATE products: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("Couldn't update product in " . SHOPPING_CART . " products table : " . mysql_error() . " SQL: [$sql]");

                    if($prodImageName != "" && $prodImage != "")
                    {
                        saveImage( $prodImage, C_IMAGEPATH . $prodImageName );
                    }
                }
            }
        }
    }

    function ExportProductStock($cnx, $languageId, $xmlData)
    {
        // Retrieve Products from XML
        $prodNodes =& $xmlData['Company'][0]['Products'][0]['Product'];

        if ($prodNodes)
        {
            foreach ($prodNodes as $node)
            {
                $prodSku            = htmlspecialchars($node['Sku'],        ENT_QUOTES);
                $prodQtyInStock     = htmlspecialchars($node['QtyInStock'], ENT_QUOTES);
                $prodPublish        = htmlspecialchars($node['Publish'],    ENT_QUOTES);
                $prodSalePrice      = htmlspecialchars($node['SalePrice'],  ENT_QUOTES);

                // Don't update the product if option set to true, and product is not set as published
                if (C_UPLOAD_PUBLISHED_PRODUCTS == 'true' && $prodPublish == "false")
                    break;

                // Check if each Product from XML has an associated record in the products table
                $productID  = GetProductIDFromSku($cnx, $prodSku);

                if ($productID)
                {
                    $prodPublish = ($prodPublish == "true") ? 1 : 0;

                    // Update product details in products table
                    $sql = "UPDATE products
                            SET products_quantity = $prodQtyInStock, products_last_modified = NOW()
                            WHERE products_id = $productID ";
//echo "SQL UPDATE Product Stock: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("Couldn't update product stock in " . SHOPPING_CART . " products table : " . mysql_error() . " SQL: [$sql]");
                }
            }
        }
    }

    function ExportProductPrice($cnx, $languageId, $xmlData)
    {
        // Retrieve Products from XML
        $prodNodes =& $xmlData['Company'][0]['Products'][0]['Product'];

        if ($prodNodes)
        {
            foreach ($prodNodes as $node)
            {
                $prodSku            = htmlspecialchars($node['Sku'],        ENT_QUOTES);
                $prodPublish        = htmlspecialchars($node['Publish'],    ENT_QUOTES);
                $prodSalePrice      = htmlspecialchars($node['SalePrice'],  ENT_QUOTES);

                // Don't update the product if option set to true, and product is not set as published
                if (C_UPLOAD_PUBLISHED_PRODUCTS == 'true' && $prodPublish == "false")
                    break;

                // Check if each Product from XML has an associated record in the products table
                $productID  = GetProductIDFromSku($cnx, $prodSku);

                if ($productID)
                {
                    $prodPublish = ($prodPublish == "true") ? 1 : 0;

                    // Update product details in products table
                    $sql = "UPDATE products
                            SET products_price = $prodSalePrice, products_last_modified = NOW()
                            WHERE products_id = $productID ";
//echo "SQL UPDATE Product Stock: " . $sql . "\n";
                    $result = mysql_query($sql, $cnx) or die("Couldn't update product price in " . SHOPPING_CART . " products table : " . mysql_error() . " SQL: [$sql]");
                }
            }
        }
    }

    function GetProductIDFromSku($cnx, $prodSku)
    {
        $rtnVal = null;

        // Check if each Product from XML has an associated record in the products table
        $sql    = "SELECT products_id FROM products WHERE products_model = '$prodSku'";
        $result = mysql_query($sql, $cnx) or die("Couldn't get products from " . SHOPPING_CART . " products table: " . mysql_error() . " SQL: [$sql]");
        if (mysql_num_rows($result) > 0)
        {
            $row = mysql_fetch_assoc($result);
            $rtnVal = $row['products_id'];
        }

        return $rtnVal;
    }
