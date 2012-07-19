<?php
    //putenv("TZ=Europe/London");

    // Configure settings
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------
    define('DB_SERVER',                                     'localhost');
    define('DB_USERNAME',                                   'root');
    define('DB_PASSWORD',                                   '');
    define('DB_DATABASE',                                   'cscart');

    define('DownloadToSingleAccount',                       'false');               // All orders to be allocated to a single account
    define('AccountReference',                              'INT001');              // Account Reference to be used for the single account

    define('QueryLimit',                                    '10');                  // Limit maximum number of orders to be downloaded at any one time
    define('LimitByOrderStatus',                            'true');                // Only download orders that have a specified status
    define('OrderStatus',                                   'O');                   // Status of orders to be downloaded. (Can be CSV)
    define('LimitByOrderDate',                              'true');                // Only download orders that have been placed after a specified date
    define('OrderDate',                                     '2010-01-01');          // Download order on or after this date (Y-M-D format)
    define('TakenBy',                                       'Connect Web Order');   // Taken By field within Sage
    define('TermsAgreed',                                   'true');                // Set 'terms agreed' for a customer download
    define('DiscountDescription',                           'Website Discount');    // Description for the Net Value discount

    // Automatically change the Tax Rate after January 4th 2011
    if (date('Y-m-d') >= date('Y-m-d', strtotime('2011-01-04')))
    {
        define('DefaultTaxRate',                            '20');
    }
    else
    {
        define('DefaultTaxRate',                            '17.5');
    }

    // Leaving the following settings blank will allow Connect to retrieve the default settings from Sage should the item / carriage exist.
    // DefaultTaxCode, DefaultTaxCodeExempt, DefaultNominalCodeItem, DefaultNominalCodeCarriage.
    define('DefaultTaxCode',                                '1');                   // The Sage TaxCode to be used for items with a VAT Rate                 
    define('DefaultTaxCodeExempt',                          '0');                   // The Sage TaxCode to be used for items without a VAT Rate                 
    define('DefaultNominalCodeItem',                        '4000');                // Use the specified value for the Item Nominal Code, otherwise the Nominal Code will be retrieved from Sage
    define('DefaultNominalCodeCarriage',                    '4905');                // Use the specified value for the Carriage Nominal Code, otherwise the Nominal Code will be retrieved from Sage
    
    define('DefaultCurrency',                               'GBP');                 // Default currency denotion
    define('DefaultZeroRatedShipping',                      'Free Shipping');       // Description for shipping should there be no value

    define('UseDiscountAsUnitDiscount',                     'true');                // The discount on the item line will be set as a Unit Discount within Sage

    define('AllocatePayments',                              'true');                // Allocate payments to the Sales Order / Invoice within Sage
    define('PaymentType',                                   'SalesReceipt');        // Payment type of the Sales Order / Invoice within Sage
    define('BankAccount',                                   '1200');                // Default bank account to allocate the Sales Order / Invoice within Sage to

    define('GlobalNominalCode',                             '');                    // Global Nominal Code to allocate the Sales Order / Invoice within Sage to
    define('GlobalDetails',                                 '');                    // Global Details to allocate the Sales Order / Invoice within Sage to
    define('GlobalTaxCode',                                 '');                    // Global Tax Code to allocate the Sales Order / Invoice within Sage to
    define('GlobalDepartment',                              '');                    // Global Department to allocate the Sales Order / Invoice within Sage to

    define('DownloadCustomers',                             'true');                // Download Customers from website to Sage Sales Ledger Accounts (Only those with a Sales Order to be downloaded)
    define('DownloadInvoices',                              'true');               // Download order from website to Sage Invoice
    define('DownloadSalesOrders',                           'false');                // Download order from website to Sage Sales Order (Default)
    // ----------------------------------------------------------------------------------------------------------------------------------------------------------

    // The Settings below should not be modified by end users
    // DO NOT MODIFY - START //
    
    // Required to plug-into CSCart config.php
    define('AREA',                                          true);
    define('DIR_ROOT',                                      '..\\..\\');

    define('DownloadInvoiceTypeName',                       'Invoice');
    define('DownloadSalesOrderTypeName',                    'SalesOrder');

    define('ShoppingCart',                                  'CS-Cart');
    define('ShoppingCartVersion',                           '2.1.2');
    define('RequiredPHPVersionLB',                          '5.1.0');
    define('RequiredPHPVersionUB',                          '5.2.0');
    define('SageVersion',                                   'Sage Line 50');

    define('TablePrefix',                                   'cscart_');
    
    define('OrdersTable',                                   TablePrefix.'orders');
    define('OrdersTable_IdColumn',                          'order_id');
    define('OrdersTable_OrderIdColumn',                     'order_id');
    define('OrdersTable_DateColumn',                        'timestamp');
    define('OrdersTable_PostedColumn',                      'posted_to_sage');
    define('OrdersTable_StatusColumn',                      'status');

    define('OrderProductsTable',                            TablePrefix.'order_details');
    define('OrderProductsTable_IdColumn',                   'item_id');
    define('OrderProductsTable_OrderIdColumn',              'order_id');
    define('OrderProductsTable_productIdColumn',            'product_id');
    define('OrderProductsTable_SkuColumn',                  'product_code');
    define('OrderProductsTable_PriceColumn',                'price');
    define('OrderProductsTable_QuantityColumn',             'amount');
    define('OrderProductsTable_DetailsColumn',              'extra');
    
    define('OrderInformationTable',                         TablePrefix.'order_data');
    define('OrderInformation_IdColumn',                     'order_id');
    define('OrderInformation_TypeColumn',                   'type');
    define('OrderInformation_DataColumn',                   'data');
    define('OrderInformation_TaxType',                      'T');
    define('OrderInformation_LineType',                     'L');
    define('OrderInformation_CurrencyType',                 'R');

    define('ShippingTable',                                 TablePrefix.'shipping_descriptions');
    define('ShippingTable_IdColumn',                        'shipping_id');
    define('ShippingTable_NameColumn',                      'shipping');
    define('ShippingTable_DetailsColumn',                   'delivery_time');
    
    define('ShippingDetailsTable',                          TablePrefix.'shippings');
    define('ShippingDetailsTable_IdColumn',                 'shipping_id');
    define('ShippingDetailsTable_TaxIdColumn',              'tax_ids');

    define('PaymentsTable',                                 TablePrefix.'payment_descriptions');
    define('PaymentsTable_IdColumn',                        'payment_id');
    define('PaymentsTable_NameColumn',                      'payment');
    define('PaymentsTable_DescriptionColumn',               'description');
    
    define('CustomersTable',                                TablePrefix.'users');
    define('CustomersTable_IdColumn',                       'user_id');
    define('CustomersTable_AccountReferenceColumn',         'sage_account_reference');
    
    define('CustomersAddressTable',                         TablePrefix.'user_profiles');
    define('CustomersAddressTable_IdColumn',                'profile_id');
    define('CustomersAddressTable_UserIdColumn',            'user_id');
    define('CustomersAddressTable_ProfileNameColumn',       'profile_name');
    define('CustomersAddressTable_MainProfileText',         'Main');

    define('StatusesTable',                                 TablePrefix.'statuses');
    define('StatusesTable_statusColumn',                    'status');
    define('StatusesTable_typeColumn',                      'type');
    define('StatusesTable_is_defaultColumn',                'is_default');

    define('StatusDescriptionsTable',                       TablePrefix.'status_descriptions');
    define('StatusDescriptionsTable_statusColumn',          'status');
    define('StatusDescriptionsTable_typeColumn',            'type');
    define('StatusDescriptionsTable_descriptionColumn',     'description');
    define('StatusDescriptionsTable_email_subjColumn',      'email_subj');
    define('StatusDescriptionsTable_email_headerColumn',    'email_header');
    define('StatusDescriptionsTable_lang_codeColumn',       'lang_code');

    define('StatusDescriptionsTable_statusValue',           'S');
    define('StatusDescriptionsTable_typeValue',             'O');
    define('StatusDescriptionsTable_descriptionValue',      'Processing');
    define('StatusDescriptionsTable_email_subjValue',       'is being processed.');
    define('StatusDescriptionsTable_email_headerValue',     'Your order is currently being processed.');
    define('StatusDescriptionsTable_lang_codeValue',        'EN');
    define('StatusesTable_is_defaultValue',                 'N');
    // DO NOT MODIFY - END //

    $connection = Connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
?>