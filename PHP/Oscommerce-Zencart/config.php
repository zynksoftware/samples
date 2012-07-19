<?php

define('SHOPPING_CART',                         'OSCommerce');
define('SHOPPING_CART_VERSION',                 '2.2');

// Database Information
define('C_DBSERVER',                            'localhost');           // Server Address
define('C_DBUSERNAME',                          'root');                // DataBase Username
define('C_DBPASSWORD',                          '');                    // DataBase Password
define('C_DBDATABASE',                          'oscommerce');          // DataBase Name


// Download Tasks
define('C_DOWNLOAD_CUSTOMERS',                  'true');                // Download Customers from website to Sage
define('C_DOWNLOAD_ORDERS',                     'false');               // Download Sales Orders from website to Sage
define('C_DOWNLOAD_INVOICES',                   'true');                // Download Invoices from website to Sage

// Upload Tasks
define('C_UPLOAD_CUSTOMERS',                    'true');                // Upload Customers from Sageto website
define('C_UPLOAD_PRODUCTS',                     'true');                // Upload Product details, stock leves and prices from Sage to website
define('C_UPLOAD_PRODUCT_STOCK',                'false');               // Upload Product stock levels only from Sage to website
define('C_UPLOAD_PRODUCT_PRICE',                'false');               // Upload Product prices only from Sage to website


// Download Options

define('C_ENCODING',                            'ISO-8859-1');          // Should be UTF-8... However this ensures maximum compatiblity
define('C_QUERYLIMIT',                          '100');                 // Limit returned records to this number.
                                                                        // NOTE: 0 will return all results

define('C_DOWNLOAD_ORDER_DATE_FILTER',          'true');                // Only orders placed after the date below will be downloaded
define('C_DOWNLOAD_ORDER_DATE',                 '2011-01-01 00:00:00'); // Only orders placed after the following date will be downloaded, (YYYY-MM-DD HH:MM:SS)
define('C_DOWNLOAD_ORDER_STATUS_FILTER',        'true');                // Only orders with the statuses below will be downloaded
define('C_DOWNLOAD_ORDER_STATUS',               'Pending');             // Only orders with these statuses will be downloaded, Seperate using ',' (comma)
define('C_DOWNLOAD_ORDER_NOTIFY_STATUS_FILTER', 'true');                // Change the order status when successfully saved into Sage
define('C_DOWNLOAD_ORDER_NOTIFY_STATUS',        'Processing');          // The order status to update the website order to once it has been successfully saved into Sage

define('C_SINGLEACCOUNT',                       'false');               // Allocate all orders to a single account in Sage
define('C_ACCOUNTREF',                          'WEBSALES');            // Account Reference to allocate all order to in Sage
define('C_ALLOCATEPAYMENT',                     'false');               // Allocate payments against the order in Sage
define('C_TAKENBY',                             'CONNECT');             // Default TakenBy to set against the order in Sage
define('C_BANKACCOUNT',                         '1200');                // Default BankAccount to set against the order in Sage
define('C_NOMINAL',                             '4000');                // Default Item Nominal Code to set against the order in Sage (Leave blank for Connect to retrieve from Item in Sage)
define('C_CARRIAGE_NOMINAL',                    '4905');                // Default Carriage Nominal Code to set against the order in Sage
define('C_DEPARTMENT',                          '0');                   // Default Department to set against the order in Sage
define('C_ITEM_TAX',                            '1');                   // Default Tax Code to set against the item in Sage
define('C_ITEM_TAX_ZERO',                       '0');                   // Default Zero Rated Tax Code to set against the item in Sage
define('C_CARRIAGE_TAX',                        '1');                   // Default Tax Code to set against the carriage charge in Sage
define('C_CARRIAGE_TAX_ZERO',                   '0');                    // Default Zero Rated Tax Code to set against the carriage charge in Sage

define('C_GLOBAL_NOMINAL',                      '');                    // Default Global Nominal Code to set against the order in Sage
define('C_GLOBAL_DETAILS',                      '');                    // Default Global Details to set against the order in Sage
define('C_GLOBAL_TAX',                          '');                    // Default Global Tax Code to set against the order in Sage
define('C_GLOBAL_DEPARTMENT',                   '');                    // Default Global Department to set against the order in Sage


// Upload Options

define('C_IMAGEPATH',                           '..\\images\\');                            // Directory for Images
define('C_NON_GROUPED_CAT',                     'UNCATEGORIZED');                           // Default category to give to items with no group set.
define('C_DEFAULT_CUSTOMER_GENDER',             'm');                                       // Default gender for a customer.
define('C_DEFAULT_CUSTOMER_PASSWORD',           'e6b0ffce8401dd6a2658541d0f7cbb22:1c');     // Default password that new customers should have (password1).
define('C_UPLOAD_PUBLISHED_PRODUCTS',           'false');                                   // Only upload products marked as 'Publish to web' within Sage.

