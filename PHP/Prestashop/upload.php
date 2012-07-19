<?php

	// Include DevKit file
	include_once('devkit.php');
	
	// Include PrestaShop config file
	include_once('../../prestashop/config/settings.inc.php');;

	// Create new instance of DevKit
	$d = new DevKit();
	
	// Configure settings
	Settings::Set('DB_SERVER', _DB_SERVER_);
	Settings::Set('DB_USERNAME', _DB_USER_);
	Settings::Set('DB_PASSWORD', _DB_PASSWD_);
	Settings::Set('DB_DATABASE', _DB_NAME_);

	Settings::Set('EMPLOYEE', '1');
	Settings::Set('STOCK_REASON_INCREASE', '5');
	Settings::Set('STOCK_REASON_DECREASE', '3');

	// Get HTTP Post data
	$post = file_get_contents("php://input");

	if (isset($_REQUEST['xml_file']))
	{
		$file = fopen(str_replace(' ', '%20', $_REQUEST['xml_file']), 'r');
		while (!feof ($file)) { $line = fgets ($file, 1024); $post .= $line; }
		fclose($file);
	}
	
	// Check for HTTP Post data
	if (!empty($post)) // Run notify
	{
		// Load data from post
		$d->Upload($post);

		// Product Information
		update_products($d);
	}
	
	function update_products($d)
	{
		foreach ($d->Products->Get() as $p)
		{
			// Check main product table for a match
			$sql = 'SELECT id_product, quantity FROM %sproduct WHERE reference = "%s"';
			$sql = sprintf($sql, _DB_PREFIX_, $p->Sku);
			echo($sql.'<br />');
			$product = Connection::Run($sql);
			
			// Details for stock movements
			$id_product = 0;
			$id_product_attribute = 0;
			$current_quantity = 0;
			$new_quantity = $p->QtyInStock;
			
			// Only need to update information for existing products
			if (mysql_num_rows($product) > 0)
			{
				$product = mysql_fetch_object($product);
				$id_product = $product->id_product;
				$current_quantity = $product->quantity;
				
				// Set the quantity and price on the product
				$sql = 'UPDATE %sproduct SET quantity = %s, price = %s WHERE id_product = %s';
				$sql = sprintf($sql, _DB_PREFIX_, $new_quantity, $p->SalePrice, $id_product);
				echo($sql.'<br />');
				Connection::Run($sql);
			}
			else
			{
				// Check the attribute table for a match (AKA Combination in admin)
				$sql = 'SELECT id_product_attribute, id_product, quantity FROM %sproduct_attribute WHERE reference = "%s"';
				$sql = sprintf($sql, _DB_PREFIX_, $p->Sku);
				echo($sql.'<br />');
				$product = Connection::Run($sql);
				
				if (mysql_num_rows($product) > 0)
				{
					$product = mysql_fetch_object($product);
					$id_product_attribute = $product->id_product_attribute;
					$id_product = $product->id_product;
					$current_quantity = $product->quantity;
					
					// Update child quantity and price
					$sql = 'UPDATE %sproduct_attribute SET quantity = %s, price = %s WHERE id_product_attribute = %s';
					$sql = sprintf($sql, _DB_PREFIX_, $new_quantity, $p->SalePrice, $id_product_attribute);
					echo($sql.'<br />');
					Connection::Run($sql);
					
					// Select the total quantity of all child products
					$sql = 'SELECT SUM(quantity) AS total FROM %sproduct_attribute WHERE id_product = %s';
					$sql = sprintf($sql, _DB_PREFIX_, $product->id_product);
					echo($sql.'<br />');
					$total = mysql_fetch_object(Connection::Run($sql))->total;
					
					// And now update the parent product quantity
					$sql = 'UPDATE %sproduct SET quantity = %s WHERE id_product = %s';
					$sql = sprintf($sql, _DB_PREFIX_, $total, $id_product);
					echo($sql.'<br />');
					Connection::Run($sql);
				}
			}
			
			// If we have a valid product id, create a stock movement
			if ($id_product > 0 && $new_quantity != $current_quantity)
			{
				$reason = ($new_quantity > $current_quantity) ? Settings::Get('STOCK_REASON_INCREASE') : Settings::Get('STOCK_REASON_DECREASE');
				$quantity = $new_quantity - $current_quantity;
				
				$sql = 'INSERT INTO %sstock_mvt (id_product, id_product_attribute, id_order, id_stock_mvt_reason, id_employee, quantity, date_add, date_upd) VALUES (%s, %s, %s, %s, %s, %s, "%s", "%s")';
				$sql = sprintf($sql, _DB_PREFIX_, $id_product, $id_product_attribute, 0, $reason, Settings::Get('EMPLOYEE'), $quantity, date('c'), date('c'));
				echo($sql.'<br />');
				Connection::Run($sql);
			}
		}
	}
	
?>