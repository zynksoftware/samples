<?php

	// Include DevKit file
	include_once('devkit.php');
	
	// Include PrestaShop config file
	include_once('../../prestashop/config/settings.inc.php');
	
	// Create new instance of DevKit
	$d = new DevKit();
	
	// Configure settings
	Settings::Set('DB_SERVER', _DB_SERVER_);
	Settings::Set('DB_USERNAME', _DB_USER_);
	Settings::Set('DB_PASSWORD', _DB_PASSWD_);
	Settings::Set('DB_DATABASE', _DB_NAME_);
	
	Settings::Set('ORDER_STATUS', '2');
	Settings::Set('POSTED_ORDER_STATUS', '4');
	Settings::Set('EMPLOYEE', '1');
	
	
	Settings::Set('ORDER_TYPE', 'Invoice');
	Settings::Set('PAYMENTS', 'true');
	Settings::Set('BANK_ACCOUNT', '1260');
	
	// Get HTTP Post data
	$post = file_get_contents("php://input");
	
	// Check for HTTP Post data
	if (!empty($post)) // Run notify
	{
		$d->Upload($post);
					
		foreach($d->Invoices->Get() as $i)
		{
			$sql = 'INSERT INTO %sorder_history (id_employee, id_order, id_order_state, date_add) VALUES (%s, %s, %s, "%s")';
			$sql = sprintf($sql, _DB_PREFIX_, Settings::Get('EMPLOYEE'), $i->Id, Settings::Get('POSTED_ORDER_STATUS'), date('c'));
			Connection::Run($sql);
		}
	}
	else // Run Download
	{
		// Check to see if there is an order id passed to the script
		// If so, download the single order regardsless of status
		if (isset($_REQUEST['id']))
		{
			download_order($d, $_REQUEST['id']);
		}
		// Otherwise continue as standard
		else
		{
			download_orders($d);
		}
		
		$d->Download();
	}
	
	// download all orders at the status from the configuration
	function download_orders($d)
	{
		$sql = 'SELECT id_order FROM %1$sorders WHERE (SELECT id_order_state FROM %1$sorder_history WHERE %1$sorder_history.id_order = %1$sorders.id_order ORDER BY id_order_history DESC LIMIT 1) = %2$s';
		$sql = sprintf($sql, _DB_PREFIX_, Settings::Get('ORDER_STATUS'));
		$orders = Connection::Run($sql);
		
		while ($order = mysql_fetch_object($orders))
		{
			download_order($d, $order->id_order);
		}
	}
	
	function download_order($d, $id)
	{
		// Grab order details
		$type = Settings::Get('ORDER_TYPE');
		$sql = 'SELECT * FROM %1$sorders INNER JOIN %1$scurrency ON %1$scurrency.id_currency = %1$sorders.id_currency WHERE id_order = ' . $id;
		$sql = sprintf($sql, _DB_PREFIX_, $id);
		$order = mysql_fetch_object(Connection::Run($sql));
		
		// Create order and populate header details
		$o = $d->{$type.'s'}->Add(new $type($order->id_order));
		$o->CustomerId = $order->id_customer;
		$o->{$type.'Number'} = $o->CustomerOrderNumber = $o->Id;
		$o->Currency = $order->iso_code;
		$o->ForeignRate = $order->conversion_rate;
		$o->{$type.'Date'} = str_replace(' ', 'T', $order->date_add);
		
		// Invoice address
		get_address($o, 'Address', $order->id_address_invoice);
		
		// Delivery address
		get_address($o, 'DeliveryAddress', $order->id_address_delivery);
		
		// Add items
		get_items($o);
		
		// Add carriage
		$c = $o->Carriage = new Item(Guid());
		$c->Name = 'Carriage';
		$c->TotalNet = $c->UnitPrice = ($order->total_shipping / (100 + $order->carrier_tax_rate)) * 100;
		$c->QtyOrdered = 1;
		$c->TaxRate = $order->carrier_tax_rate;
		$c->TaxCode = ($c->TaxRate > 0) ? 1 : 0;

		// Populate footer details
		$o->TakenBy = 'WEB';
		$o->NetValueDiscount = ($order->total_discounts / (100 + $order->carrier_tax_rate)) * 100;
		
		if (Settings::Get('PAYMENTS') == 'true')
		{
			$o->PaymentRef = $order->payment;
			$o->PaymentAmount = $order->total_paid;
			$o->BankAccount = Settings::Get('BANK_ACCOUNT');
		}
		
		get_customer($d, $o);
	}
	
	function get_address($o, $type, $id)
	{
		$sql = 'SELECT %1$saddress.*, %1$scountry.iso_code, %1$sstate.name, %1$scustomer.email FROM %1$saddress INNER JOIN %1$scountry ON %1$scountry.id_country = %1$saddress.id_country LEFT OUTER JOIN %1$sstate ON %1$sstate.id_state = %1$saddress.id_state INNER JOIN %1$scustomer ON %1$scustomer.id_customer = %1$saddress.id_customer WHERE id_address = ' . $id;
		$sql = sprintf($sql, _DB_PREFIX_, $id);
		$address = mysql_fetch_object(Connection::Run($sql));
		
		$c = $o->{Settings::Get('ORDER_TYPE').$type}; // = new Contact($id);
		$c->Forename = $address->firstname;
		$c->Surname = $address->lastname;
		$c->Company = $address->company;
		$c->Address1 = $address->address1;
		$c->Address2 = $address->address2;
		$c->Town = $address->city;
		$c->Postcode = $address->postcode;
		$c->County = $address->name;
		$c->Country = $address->iso_code;
		$c->Telephone = $address->phone;
		$c->Mobile = $address->phone_mobile;
		$c->Email = $address->email;
		
		if ($type == 'DeliveryAddress')
		{
			$o->Notes1 = $address->other;
		}
	}
	
	function get_items($o)
	{
		$ii = $o->{Settings::Get('ORDER_TYPE').'Items'}; // = new Collection('Item');
		$sql = 'SELECT * FROM %1$sorder_detail WHERE id_order = %2$s';
		$sql = sprintf($sql, _DB_PREFIX_, $o->Id);
		$items = Connection::Run($sql);
		
		while ($item = mysql_fetch_object($items))
		{
			$i = $ii->Add(new Item($item->id_order_detail));
			$i->Sku = $item->product_reference;
			$i->Name = $item->product_name;
			$i->QtyOrdered = $item->product_quantity;
			$i->UnitPrice = $item->product_price;
			$i->TaxRate = $item->tax_rate;
			$i->TaxCode = ($i->TaxRate > 0) ? 1 : 0;
			$i->Type = 'Stock';
			
			if ($item->reduction_percent > 0)
			{
				$i->UnitDiscountPercentage = $item->reduction_percent;
			}
			else if ($item->reduction_amount > 0)
			{
				$i->UnitDiscountAmount = $item->reduction_amount;
			}
		}
	}
	
	function get_customer($d, $o)
	{
		$type = Settings::Get('ORDER_TYPE');
		$sql = 'SELECT * FROM %1$scustomer WHERE id_customer = %2$s';
		$sql = sprintf($sql, _DB_PREFIX_, $o->CustomerId);
		$customer = mysql_fetch_object(Connection::Run($sql));
		
		$c = $d->Customers->Add(new Customer($customer->id_customer));
		$c->CompanyName = $o->{$type.'Address'}->Company;
		$c->CustomerInvoiceAddress = $o->{$type.'Address'};
		$c->CustomerDeliveryAddress = $o->{$type.'DeliveryAddress'};
	}

?>