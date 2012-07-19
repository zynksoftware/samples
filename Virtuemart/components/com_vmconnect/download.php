<?php
require_once(dirname(__FILE__) . "/common.php") ;

//~ if (!licensecheck($config->license_key)) 
	//~ bye ('Invalid License - Please contact Ibex Internet, support@ibexinternet.co.uk', SAGE_DOWNLOAD) ;


$xmlInput = file_get_contents("php://input");
$xmlString = preg_replace('/<Company.*?>/', '<Company>', $xmlInput, 1);	  

if (!empty($xmlString)) {
	markDownloadedSalesOrders($xmlString) ;
	markDownloadedInvoices($xmlString) ;
} else
	downloadData() ;

function markDownloadedSalesOrders(&$xmlString) {
	global $database ;
	$xmlData = xml2array($xmlString);			
	if (!$xmlData) bye("Couldn't read XML data", SAGE_DOWNLOAD);
	
	$salesOrderNodes =& $xmlData['Company'][0]['SalesOrders'][0]['SalesOrder'];
    if ($salesOrderNodes)
    {
        foreach($salesOrderNodes as $node) {
            $orderID = $node['Id'];
            
            $sql = "SELECT id FROM #__vmc_downloaded WHERE order_id=" . $orderID;
            $database->setQuery($sql) ;
            if ($database->getErrorNum()) 
                bye("Couldn't check downloaded orders table for existing records : " . $database->getErrorMsg(), SAGE_DOWNLOAD);
            
            if ($database->getNumRows() > 0) {
                $sql = "UPDATE #__vmc_downloaded SET downloaded_date = NOW() WHERE order_id=" . $orderID;
                $database->setQuery($sql);
                $database->query() ;
                if ($database->getErrorNum()) 
                    bye('There was a problem updating the downloaded orders information in to the database.', SAGE_DOWNLOAD);
            }	else {
                $sql = "INSERT INTO #__vmc_downloaded (order_id,downloaded_date) VALUES(" . $orderID . ",NOW())";
                $database->setQuery($sql) ;
                $database->query() ;
                if ($database->getErrorNum()) 
                    bye('There was a problem inserting the downloaded orders information into the database.', SAGE_DOWNLOAD);
            }	
        }
    }
}

function markDownloadedInvoices(&$xmlString) {
	global $database ;
	$xmlData = xml2array($xmlString);			
	if (!$xmlData) bye("Couldn't read XML data", SAGE_DOWNLOAD);
	
	$invoiceNodes =& $xmlData['Company'][0]['Invoices'][0]['Invoice'];
    if ($invoiceNodes)
    {
        foreach($invoiceNodes as $node) {
            $orderID = $node['Id'];
            
            $sql = "SELECT id FROM #__vmc_downloaded WHERE order_id=" . $orderID;
            $database->setQuery($sql) ;
            if ($database->getErrorNum()) 
                bye("Couldn't check downloaded orders table for existing records : " . $database->getErrorMsg(), SAGE_DOWNLOAD);
            
            if ($database->getNumRows() > 0) {
                $sql = "UPDATE #__vmc_downloaded SET downloaded_date = NOW() WHERE order_id=" . $orderID;
                $database->setQuery($sql);
                $database->query() ;
                if ($database->getErrorNum()) 
                    bye('There was a problem updating the downloaded orders information in to the database.', SAGE_DOWNLOAD);
            }	else {
                $sql = "INSERT INTO #__vmc_downloaded (order_id,downloaded_date) VALUES(" . $orderID . ",NOW())";
                $database->setQuery($sql) ;
                $database->query() ;
                if ($database->getErrorNum()) 
                    bye('There was a problem inserting the downloaded orders information into the database.', SAGE_DOWNLOAD);
            }	
        }
    }
}

function downloadData () {
	global $config ;
	$customers = getNewCustomers() ;
	$orders = getNewOrders() ;
	
	$xmlDoc = '<?xml version="1.0" encoding="utf-8"?><Company xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Products /><Customers>';
	if (isset($config->dl_single_customer) && $config->dl_single_customer)
		$xmlDoc .= singleCustomerXML($config->dl_customer_name) ;
	else
		foreach ($customers as $customer)
			$xmlDoc .= buildCustomerXML($customer) ;
	$xmlDoc .= "</Customers>";
	if ($config->send_invoices) {
		$xmlDoc .= "<Invoices>" ;
		foreach ($orders as $order)
			$xmlDoc .= buildInvoiceXML($order) ;
		$xmlDoc .= "</Invoices>" ;
	} else {
		$xmlDoc .= "<SalesOrders>" ;
		foreach ($orders as $order)
			$xmlDoc .= buildOrderXML($order) ;
		$xmlDoc .= "</SalesOrders>" ;
	}
	$xmlDoc .= "</Company>";
	
	die( $xmlDoc) ;
}

function singleCustomerXML($customer) {
	$invoiceaddress = array
		(
			'Title'		=> '',
			'Forename'	=> '',
			'Surname'	=> $customer,
			'Company'	=> '',
			'Address1'	=> '',
			'Address2'	=> '',
			'Town'		=> '',
			'Postcode'	=> '',
			'County'	=> '',
			'Country'	=> '',
			'Telephone' => '',
			'Fax'		=> '',
			'Email'		=> ''
		) ;
		
	$a_customer = array
		(
			'Id'				=> $customer,
			'CompanyName'		=> '',
			'AccountReference'	=> $customer,
			'VatNumber'			=> '',
			'CreditLimit'		=> '0',
			'Balance'			=> '0',
			'CustomerInvoiceAddress' => $invoiceaddress,
			'CustomerDeliveryAddress' => $invoiceaddress
		) ;	
	
	return outputXMLString('Customer', $a_customer) ;
}

function buildCustomerXML($customer) {
	$company = (empty($customer['bt_company']) ? $customer['bt_first_name'] . " " . $customer['bt_last_name'] : $customer['bt_company']) ;
	
	$invoiceaddress = array
		(
			'Title'		=> $customer['bt_title'],
			'Forename'	=> $customer['bt_first_name'],
			'Surname'	=> $customer['bt_last_name'],
			'Company'	=> $customer['bt_company'],
			'Address1'	=> $customer['bt_address_1'],
			'Address2'	=> $customer['bt_address_2'],
			'Town'		=> $customer['bt_city'],
			'Postcode'	=> $customer['bt_zip'],
			'County'	=> $customer['bt_state'],
			'Country'	=> $customer['bt_country'],
			'Telephone' => $customer['bt_phone_1'],
			'Fax'		=> $customer['bt_fax'],
			'Email'		=> $customer['bt_email']
		) ;

	$a_customer = array
		(
			'Id'				=> makeUserRef($customer['user_id'], $customer['bt_first_name'], $customer['bt_last_name']),
			'CompanyName'		=> $company,
			'AccountReference'	=> makeUserRef($customer['user_id'], $customer['bt_first_name'], $customer['bt_last_name']),
			'VatNumber'			=> '',
			'CreditLimit'		=> '0',
			'Balance'			=> '0',
			'CustomerInvoiceAddress' => $invoiceaddress
		) ;

	if (empty($customer['st_email']))
		$a_customer['CustomerDeliveryAddress'] = $invoiceaddress ;
	else
		$a_customer['CustomerDeliveryAddress'] = array
			(
				'Title'		=> $customer['st_title'],
				'Forename'	=> $customer['st_first_name'],
				'Surname'	=> $customer['st_last_name'],
				'Company'	=> $customer['st_company'],
				'Address1'	=> $customer['st_address_1'],
				'Address2'	=> $customer['st_address_2'],
				'Town'		=> $customer['st_city'],
				'Postcode'	=> $customer['st_zip'],
				'County'	=> $customer['st_state'],
				'Country'	=> $customer['st_country'],
				'Telephone' => $customer['st_phone_1'],
				'Fax'		=> $customer['st_fax'],
				'Email'		=> $customer['st_email']
			) ;
	
	return outputXMLString('Customer', $a_customer) ;
} 

function getNewCustomers () {
	global $database ;
	
	$sql = "SELECT o.user_id, 
	bt.title AS bt_title, bt.first_name AS bt_first_name, bt.middle_name AS bt_middle_name, bt.last_name AS bt_last_name, bt.company AS bt_company, bt.address_1 AS bt_address_1, bt.address_2 AS bt_address_2, 
	bt.city AS bt_city, bt.zip AS bt_zip, bts.state_name AS bt_state, btc.country_2_code AS bt_country, bt.phone_1 AS bt_phone_1, bt.fax AS bt_fax, bt.user_email as bt_email,
	st.title AS st_title, st.first_name AS st_first_name, st.middle_name AS st_middle_name, st.last_name AS st_last_name, st.company AS st_company, st.address_1 AS st_address_1, st.address_2 AS st_address_2, 
	st.city AS st_city, st.zip AS st_zip, sts.state_name AS st_state, stc.country_2_code AS st_country, st.phone_1 AS st_phone_1, st.fax AS st_fax, st.user_email as st_email
 	FROM #__vm_orders AS o
	LEFT OUTER JOIN #__vmc_downloaded AS dl ON dl.order_id = o.order_id
	LEFT OUTER JOIN #__vm_user_info AS bt ON (o.user_id = bt.user_id AND bt.address_type = 'BT')
	LEFT OUTER JOIN #__vm_user_info AS st ON (o.user_id = st.user_id AND st.address_type = 'ST')
	LEFT OUTER JOIN #__vm_country AS btc ON bt.country = btc.country_3_code
	LEFT OUTER JOIN #__vm_country AS stc ON st.country = stc.country_3_code
	LEFT OUTER JOIN #__vm_state AS bts ON bt.state = bts.state_3_code
	LEFT OUTER JOIN #__vm_state AS sts ON st.state = sts.state_3_code
	WHERE dl.downloaded_date IS NULL GROUP BY o.user_id" ;
	$database->setQuery($sql) ;
	$newcustomers = $database->loadAssocList() ;
	if ($database->getErrorNum()) bye ("Could not get new customers from database: ".$database->getErrorMsg(), SAGE_DOWNLOAD) ;
	
	return $newcustomers ;
}

function buildOrderXML($order) {
	global $config ;	
	$orderItems = getOrderItems($order['order_id']) ;
	if ($order['st_country'] == 'GB' || (empty($order['st_country']) && $order['bt_country'] == 'GB'))
	  $nominalCode = (!empty($config->nominalcode_uk) ? $config->nominalcode_uk : DEFAULT_NOMINAL_CODE) ;
	else
	  $nominalCode = (!empty($config->nominalcode_int) ? $config->nominalcode_int : DEFAULT_NOMINAL_CODE) ;
		
	$salesOrder['Id'] = $order['order_id'];
	$salesOrder['CustomerId'] = (isset($config->dl_single_customer) && $config->dl_single_customer) ? $config->single_customer_name : makeUserRef($order['user_id'], $order['bt_first_name'], $order['bt_last_name']) ;
	$salesOrder['SalesOrderNumber'] = '0' ;
	$salesOrder['CustomerOrderNumber'] = $order['order_id'] ;
	$salesOrder['Notes1'] = 'Virtuemart Order Number: ' . $order['order_id'] ;
	$salesOrder['ForeignRate'] = '1' ;
	$salesOrder['Currency'] = $order['order_currency'] ;
	$salesOrder['AccountReference'] = (isset($config->dl_single_customer) && $config->dl_single_customer) ? $config->single_customer_name : makeUserRef($order['user_id'], $order['bt_first_name'], $order['bt_last_name']) ;
	$salesOrder['CurrencyUsed'] = ($order->Currency == 'GBP') ? 'false' : 'true' ;
	$salesOrder['SalesOrderDate'] = XSDDate($order['mdate']) ;
	$salesOrder['DespatchDate'] = XSDDate() ;
	$salesOrder['SalesOrderAddress'] = array
		(
			'Title'		=> $order['bt_title'],
			'Forename'	=> $order['bt_first_name'],
			'Surname'	=> $order['bt_last_name'],
			'Company'	=> $order['bt_company'],
			'Address1'	=> $order['bt_address_1'],
			'Address2'	=> $order['bt_address_2'],
			'Town'		=> $order['bt_city'],
			'Postcode'	=> $order['bt_zip'],
			'County'	=> $order['bt_state'],
			'Country'	=> $order['bt_country'],
			'Telephone' => $order['bt_phone_1'],
			'Fax'		=> $order['bt_fax'],
			'Email'		=> $order['bt_email']
		) ;
	if (empty($order['st_address_1']))
		$salesOrder['SalesOrderDeliveryAddress'] = $salesOrder['SalesOrderAddress'] ;
	else
		$salesOrder['SalesOrderDeliveryAddress'] = array
			(
				'Title'		=> $order['st_title'],
				'Forename'	=> $order['st_first_name'],
				'Surname'	=> $order['st_last_name'],
				'Company'	=> $order['st_company'],
				'Address1'	=> $order['st_address_1'],
				'Address2'	=> $order['st_address_2'],
				'Town'		=> $order['st_city'],
				'Postcode'	=> $order['st_zip'],
				'County'	=> $order['st_state'],
				'Country'	=> $order['st_country'],
				'Telephone' => $order['st_phone_1'],
				'Fax'		=> $order['st_fax'],
				'Email'		=> $order['st_email']
			) ;
	$salesOrder['SalesOrderItems'] = "" ;
	foreach ($orderItems as $item) :
		$salesOrder['SalesOrderItems'] .= outputXMLString('Item', array
		(
			'Sku' => $item['order_item_sku'],
			'Name' => $item['order_item_name'],
			'Comments' => $item['product_attribute'],
			'QtyOrdered' => $item['product_quantity'],
			'UnitPrice' => round($item['product_item_price'], 2),
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate' => round($item['item_tax_rate'], 1),
			'TotalNet' => round($item['product_item_price'] * $item['product_quantity'], 2),
			'TotalTax' => round(($item['product_final_price'] - $item['product_item_price']) * $item['product_quantity'], 2),
			'TaxCode' => '1',
			'NominalCode' => $nominalCode
			)) ;
	endforeach ;
	if ($order['order_discount'] != 0.00)
		$salesOrder['SalesOrderItems'] .= outputXMLString('Item', array
		(
			'Sku' => 'S1',
			'Name' => 'Order Discount',
			'QtyOrdered' => 1,
			'UnitPrice' => round(($order['order_discount'] * -1), 2),
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate' => '0.00',
			'TotalNet' => round(($order['order_discount'] * -1), 2),
			'TotalTax' => '0.00',
			'TaxCode' => '0',
			'NominalCode' => $nominalCode
			)) ;
	if ($order['coupon_discount'] != 0.00)
		$invoice['InvoiceItems'] .= outputXMLString('Item', array
		(
			'Sku' => 'S1',
			'Name' => 'Coupon Discount',
			'QtyOrdered' => 1,
			'UnitPrice' => round(($order['coupon_discount'] * -1), 2),
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate' => '0.00',
			'TotalNet' => round(($order['coupon_discount'] * -1), 2),
			'TotalTax' => '0.00',
			'TaxCode' => '0',
			'NominalCode' => $nominalCode
			)) ;
	$salesOrder['Carriage'] = array (
			'Sku'			=> '',
			'Name'			=> 'Carriage',
			'Description'	=> '',
			'QtyOrdered'	=> '1',
			'UnitPrice'		=> '0',
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate'		=> round($order['order_shipping_tax_rate'], 1),
			'TotalNet'		=> $order['order_shipping'],
			'TotalTax'		=> $order['order_shipping_tax'],
			'TaxCode'		=> '1'
		) ;
	$salesOrder['SalesOrderType'] = "ProductInvoice" ;
	$salesOrder['Courier'] = '0' ;
	$salesOrder['SettlementDays'] = '30' ;
	$salesOrder['SettlementDiscount'] = '0' ;
	$salesOrder['GlobalTaxCode'] = '1' ;
	$salesOrder['GlobalDepartment'] = '0' ;
	$salesOrder['PaymentAmount'] = '0' ;
	
	return outputXMLString('SalesOrder', $salesOrder, array('SalesOrderItems')) ;
}

function buildInvoiceXML($order) {
  global $config ;
	$orderItems = getOrderItems($order['order_id']) ;
	if ($order['st_country'] == 'GB' || (empty($order['st_country']) && $order['bt_country'] == 'GB'))
	  $nominalCode = (!empty($config->nominalcode_uk) ? $config->nominalcode_uk : DEFAULT_NOMINAL_CODE) ;
	else
	  $nominalCode = (!empty($config->nominalcode_int) ? $config->nominalcode_int : DEFAULT_NOMINAL_CODE) ;
	
	$invoice['Id'] = $order['order_id'];
	$invoice['CustomerId'] = (isset($config->dl_single_customer) && $config->dl_single_customer) ? $config->single_customer_name : makeUserRef($order['user_id'], $order['bt_first_name'], $order['bt_last_name']) ;
	$invoice['InvoiceNumber'] = '0' ;
	$invoice['CustomerOrderNumber'] = $order['order_id'] ;
	$invoice['AccountReference'] = (isset($config->dl_single_customer) && $config->dl_single_customer) ? $config->single_customer_name : makeUserRef($order['user_id'], $order['bt_first_name'], $order['bt_last_name']) ;
	$invoice['OrderNumber'] = $order['order_id'] ;
	$invoice['ForeignRate'] = '1' ;
	$invoice['Currency'] = $order['order_currency'] ;
	$invoice['Notes1'] = 'Virtuemart Order Number: ' . $order['order_id'] ;
	$invoice['CurrencyUsed'] = ($order->Currency == 'GBP') ? 'false' : 'true' ;
	$invoice['InvoiceDate'] = XSDDate($order['mdate']) ;
	$invoice['InvoiceAddress'] = array
		(
			'Title'		=> $order['bt_title'],
			'Forename'	=> $order['bt_first_name'],
			'Surname'	=> $order['bt_last_name'],
			'Company'	=> $order['bt_company'],
			'Address1'	=> $order['bt_address_1'],
			'Address2'	=> $order['bt_address_2'],
			'Town'		=> $order['bt_city'],
			'Postcode'	=> $order['bt_zip'],
			'County'	=> $order['bt_state'],
			'Country'	=> $order['bt_country'],
			'Telephone' => $order['bt_phone_1'],
			'Fax'		=> $order['bt_fax'],
			'Email'		=> $order['bt_email']
		) ;
	if (empty($order['st_address_1']))
		$invoice['InvoiceDeliveryAddress'] = $invoice['InvoiceAddress'] ;
	else
		$invoice['InvoiceDeliveryAddress'] = array
			(
				'Title'		=> $order['st_title'],
				'Forename'	=> $order['st_first_name'],
				'Surname'	=> $order['st_last_name'],
				'Company'	=> $order['st_company'],
				'Address1'	=> $order['st_address_1'],
				'Address2'	=> $order['st_address_2'],
				'Town'		=> $order['st_city'],
				'Postcode'	=> $order['st_zip'],
				'County'	=> $order['st_state'],
				'Country'	=> $order['st_country'],
				'Telephone' => $order['st_phone_1'],
				'Fax'		=> $order['st_fax'],
				'Email'		=> $order['st_email']
			) ;
	$invoice['InvoiceItems'] = "" ;
	foreach ($orderItems as $item) {
		$invoice['InvoiceItems'] .= outputXMLString('Item', array(
			'Sku' => $item['order_item_sku'],
			'Name' => $item['order_item_name'],
			'Comments' => $item['product_attribute'],
			'QtyOrdered' => $item['product_quantity'],
			'UnitPrice' => $item['product_item_price'],
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate' => round($item['item_tax_rate'], 1),
			'TotalNet' => $item['product_item_price'] * $item['product_quantity'],
			'TotalTax' => ($item['product_final_price'] - $item['product_item_price']) * $item['product_quantity'],
			'TaxCode' => '1',
			'NominalCode' => $nominalCode
			)) ;
	}
	if ($order['order_discount'] != 0.00)
		$invoice['InvoiceItems'] .= outputXMLString('Item', array
		(
			'Sku' => 'S1',
			'Name' => 'Order Discount',
			'QtyOrdered' => 1,
			'UnitPrice' => round(($order['order_discount'] * -1), 2),
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate' => '0.00',
			'TotalNet' => round(($order['order_discount'] * -1), 2),
			'TotalTax' => '0.00',
			'TaxCode' => '0',
			'NominalCode' => $nominalCode
			)) ;
	if ($order['coupon_discount'] != 0.00)
		$invoice['InvoiceItems'] .= outputXMLString('Item', array
		(
			'Sku' => 'S1',
			'Name' => 'Coupon Discount',
			'QtyOrdered' => 1,
			'UnitPrice' => round(($order['coupon_discount'] * -1), 2),
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate' => '0.00',
			'TotalNet' => round(($order['coupon_discount'] * -1), 2),
			'TotalTax' => '0.00',
			'TaxCode' => '0',
			'NominalCode' => $nominalCode
			)) ;
	$invoice['Carriage'] = array (
			'Sku'			=> '',
			'Name'			=> 'Carriage',
			'Description'	=> '',
			'QtyOrdered'	=> '1',
			'UnitPrice'		=> '0',
			'UnitDiscountAmount' => '0',
			'UnitDiscountPercentage' => '0',
			'TaxRate'		=> round($order['order_shipping_tax_rate'], 1),
			'TotalNet'		=> $order['order_shipping'],
			'TotalTax'		=> $order['order_shipping_tax'],
			'TaxCode'		=> '1'
		) ;
	$invoice['InvoiceType'] = "ProductInvoice" ;
	$invoice['Courier'] = '0' ;
	$invoice['SettlementDays'] = '30' ;
	$invoice['SettlementDiscount'] = '0' ;
	$invoice['GlobalTaxCode'] = '1' ;
	$invoice['GlobalDepartment'] = '0' ;
	$invoice['PaymentAmount'] = '0' ;
	
	return outputXMLString('Invoice', $invoice, array('InvoiceItems')) ;
}

function getNewOrders() {
	global $database ;
	
	$sql = "SELECT o.order_id, o.user_id, o.order_currency, o.mdate, o.order_shipping, o.coupon_discount, o.order_discount, o.order_shipping_tax, ((order_shipping_tax / order_shipping) / 100) AS order_shipping_tax_rate,
	bt.title AS bt_title, bt.first_name AS bt_first_name, bt.middle_name AS bt_middle_name, bt.last_name AS bt_last_name, bt.company AS bt_company, bt.address_1 AS bt_address_1, bt.address_2 AS bt_address_2, 
	bt.city AS bt_city, bt.zip AS bt_zip, bts.state_name AS bt_state, btc.country_2_code AS bt_country, bt.phone_1 AS bt_phone_1, bt.fax AS bt_fax, bt.user_email as bt_email,
	st.title AS st_title, st.first_name AS st_first_name, st.middle_name AS st_middle_name, st.last_name AS st_last_name, st.company AS st_company, st.address_1 AS st_address_1, st.address_2 AS st_address_2, 
	st.city AS st_city, st.zip AS st_zip, sts.state_name AS st_state, stc.country_2_code AS st_country, st.phone_1 AS st_phone_1, st.fax AS st_fax, st.user_email as st_email
 	FROM #__vm_orders AS o
	LEFT OUTER JOIN #__vmc_downloaded AS dl ON dl.order_id = o.order_id
	LEFT OUTER JOIN #__vm_order_user_info AS bt ON (o.order_id = bt.order_id AND o.user_id = bt.user_id AND bt.address_type = 'BT')
	LEFT OUTER JOIN #__vm_order_user_info AS st ON (o.order_id = st.order_id AND o.user_id = st.user_id AND st.address_type = 'ST')
	LEFT OUTER JOIN #__vm_country AS btc ON bt.country = btc.country_3_code
	LEFT OUTER JOIN #__vm_country AS stc ON st.country = stc.country_3_code
	LEFT OUTER JOIN #__vm_state AS bts ON (bt.state = bts.state_2_code AND btc.country_id = bts.country_id)
	LEFT OUTER JOIN #__vm_state AS sts ON (st.state = sts.state_3_code AND stc.country_id = stc.country_id)
	WHERE dl.downloaded_date IS NULL AND o.order_status != 'X'" ;
	$database->setQuery($sql) ;
	$neworders = $database->loadAssocList() ;
	if ($database->getErrorNum()) bye ("Could not get new orders from database: ".$database->getErrorMsg(), SAGE_DOWNLOAD) ;

	return $neworders ;
}

function getOrderItems($order_id) {
	global $database ;
	
	$sql = "SELECT *, ((product_final_price / product_item_price) * 100 - 100) AS item_tax_rate FROM #__vm_order_item WHERE order_id = '$order_id' ORDER BY order_item_id ASC" ;
	$database->setQuery($sql) ;
	$orderitems = $database->loadAssocList() ;
	if ($database->getErrorNum()) bye ("Could not get order items from database for order $order_id: ". $database->getErrorMsg(), SAGE_DOWNLOAD) ;

	return $orderitems ;
}

function outputXMLString($nodeName, $nodeStructure, $noCheckEnts = NULL) {
	// Output node opening tag
	$outStr .= "<$nodeName>";
	//loop through node attributes
	foreach($nodeStructure as $attr => $value) {
		 // If attribute is a child node then call recursion
		if ( is_array($value) ) {
			 $outStr .= outputXMLString($attr, $value, $noCheckEnts);	  
		} else {	
			 // If attr is not in $noCheckEntities array then check for quotes and other things in values inbetween start and end tags
			 // All special characters (& ' " < >) are transformed into their HTML entity. i.e '&amp;' for & 
			if(is_array($noCheckEnts) && in_array($attr, $noCheckEnts)) {
				// Assume attribute has already been checked
				$outStr .= "<$attr" . (($value != '' || $value == 0) ? ">" . $value . "</$attr>" : " />");
			} else {
				$outStr .= "<$attr" . (($value != '' || $value == 0) ? ">" . htmlspecialchars($value, ENT_QUOTES) . "</$attr>" : " />");
			}
		}
	}
	 // Output node closing tag
	$outStr .= "</$nodeName>";
	
	return $outStr ;
}

function XSDDate($timestamp = NULL)	{ 
	if (!empty($timestamp))	{
		return date("Y-m-d\TH:i:s", $timestamp);
	} else {
		return "0001-01-01T00:00:00";
	}
}

function splitName($name)	{
	$wholeName = explode(' ', $name);
	$numNames = count($wholeName);
	$names = array('forename' => '', 'surname' => '');
	for ($i = 0; $i < $numNames - 1; $i++) {
		$names['forename'] .= $wholeName[$i];
	}
	$names['surname'] = $wholeName[$numNames - 1];
	return $names;
}

function makeUserRef($user_id, $firstname, $lastname) {
  global $config ;
  
  switch ($config->userref) {
    case 1 :
      $userref = substr($firstname, 0, 1) . substr($lastname, 0, 1) . $user_id ;
      break ;
    case 2 :
      $userref = substr($firstname, 0, 1) . substr($lastname, 0, 7 - strlen($user_id)) . $user_id ;
      break ;
    case 3 :
      $userref = substr($firstname . $lastname, 0, 8 - strlen($user_id)) . $user_id ;
      break ;
    case 4 :
      $userref = substr($lastname, 0, 8 - strlen($user_id)) . $user_id ;
      break ;
    case 0 :
    default :
      $userref = $user_id ;
      break ;
  }
  
  return $userref ;
}

function decrypt ($encrypted, $key) {
	$encrypted = base64_decode($encrypted);
	$keylist = array();
  for($i = 0; $i < strlen($key); $i++)
    $keylist[$i] = ord(substr($key, $i, 1));

  $output = "";
  for($i = 0; $i < strlen($encrypted); $i++)
    $output.= chr(ord(substr($encrypted, $i, 1)) ^ ($keylist[$i % strlen($key)]));

  return $output;
}

function licensecheck ($licensekey) {
	if (empty($licensekey))
		return false ;
	
	list($licensed_host, $timeout) = explode('|', decrypt($licensekey, 'ibexproperty')) ;
	
	if (!empty($timeout))
		if (time() > $timeout)
			return false ;
	
	if (!empty($licensed_host))
		if ($licensed_host != $_SERVER['HTTP_HOST'])
			return false ;
	
	return true ;
}

?>