<?php

define('__DIR__', DIR_APPLICATION.'controller/module');

class ControllerModuleZynk extends Controller
{
    var $devkit;

    private function _startup()
    {
        /*
        if ( ! version_compare(phpversion(), '5.3', ">="))
        {
            die('This integration requires PHP v5.3+.');
        }
        */

        // check that devkit is available
        if (file_exists(__DIR__.'/devkit.php'))
        {
            include(__DIR__.'/devkit.php');
            $this->devkit = new DevKit();
        }
        else
        {
            die('DevKit file not present.');
        }

        // check that the module is enabled
        if( ! $this->config->get('zynk_status'))
        {
            die('Module is not enabled.');
        }

        // check that the bank account is set
        if( $this->config->get('zynk_download_payments') & !$this->config->get('zynk_bank_account'))
        {
            die("You have chosen to download Payments, therfore you must specify a Bank Account.</br> Please login to the admin area and set the option 'Bank Account'.");
        }

        // check that the Account Reference is set
        if( !$this->config->get('zynk_download_customers') & !$this->config->get('zynk_account_reference'))
        {
            die("You have not chosen to download customers, therefore you must specify a default Account Reference.</br> Please login to the admin area and set the option 'Account Reference'.");
        }

        // include other files required
        include(DIR_APPLICATION.'../admin/model/sale/order.php');
        include(DIR_APPLICATION.'../admin/model/sale/customer.php');
        include(DIR_APPLICATION.'../admin/model/sale/coupon.php');
        include(DIR_APPLICATION.'../admin/model/sale/voucher.php');
        include(DIR_APPLICATION.'../admin/model/sale/shipping.php');
        include(DIR_APPLICATION.'../admin/model/sale/affiliate.php');
        include(DIR_APPLICATION.'../admin/model/catalog/product.php');
        include(DIR_APPLICATION.'../admin/model/localisation/tax_class.php');
        $this->model_sale_order         = new ModelSaleOrder($this->registry);
        $this->model_sale_customer      = new ModelSaleCustomer($this->registry);
        $this->model_sale_coupon        = new ModelSaleCoupon($this->registry);
        $this->model_sale_voucher       = new ModelSaleVoucher($this->registry);
        $this->model_sale_shipping      = new ModelSaleShipping($this->registry);
        $this->model_catalog_product    = new ModelCatalogProduct($this->registry);
        $this->model_sale_affiliate     = new ModelSaleAffiliate($this->registry);
        $this->model_tax_class          = new ModelLocalisationTaxClass($this->registry);

        // Include front end models
        include(DIR_APPLICATION.'model/account/order.php');
        $this->model_account_order = new ModelAccountOrder($this->registry);

        include_once(__DIR__.'/zynk_products.php');
        include_once(__DIR__.'/zynk_pricelists.php');
        include_once(__DIR__.'/zynk_customers.php');
        $this->products     = new ZynkProducts($this->registry);
       	$this->pricelists   = new ZynkPricelists($this->registry);
        $this->customers    = new ZynkCustomers($this->registry);
    }

    public function index()
    {
        $this->download();
    }

    public function download()
    {
        $this->_startup();

        // get raw http post
        if (isset($_GET['file']))
        {
            $filename = $_GET['file'];
            $post = file_get_contents("$filename");
        }
        else
        {
            $post = file_get_contents("php://input");
        }

        // check if we are dealing with a notify or download
        if (!empty($post))
        {
            $this->devkit->Upload($post);

            foreach($this->devkit->Invoices->Get() as $invoice)
            {
                $data = array
                (
                    'notify'            => false,
                    'order_status_id'   => $this->config->get('zynk_notify_stage'),
                    'comment'           => 'Order saved to Sage.'
                );
                $this->addOrderHistory($invoice->Id, $data);
            }

            foreach($this->devkit->SalesOrders->Get() as $sales_order)
            {
                $data = array
                (
                    'notify'            => false,
                    'order_status_id'   => $this->config->get('zynk_notify_stage'),
                    'comment'           => 'Order saved to Sage.'
                );
                $this->addOrderHistory($sales_order->Id, $data);
            }

            foreach($this->devkit->Customers->Get() as $customer)
            {
                $this->customers->UpdateCustomerAccountReference($customer->Id, $customer->AccountReference);
            }
        }
        elseif ($this->config->get('zynk_download_orders') OR $this->config->get('zynk_download_products'))
        {
            if ($this->config->get('zynk_download_orders')  & !isset($_GET['products']))
            {
                // Limit by OrderID
                if (isset($_GET['orderid']))
                {
                    $data = array
                    (
                        'filter_order_id'           => $_GET['orderid']
                    );
                }
                else
                {
                    $data = array
                    (
                        'filter_order_status_id'    => $this->config->get('zynk_download_stage'),
                        'limit'                     => $this->config->get('zynk_download_limit'),
                        'start'                     => 0
                    );
                }

                $results = $this->model_sale_order->getOrders($data);
                $type = ($this->config->get('zynk_download_type') == 'invoice') ? 'Invoice' : 'SalesOrder';
                foreach($results as $order_header)
                {
                    $this->download_order($order_header['order_id'], $type);
                }
            }

            if ($this->config->get('zynk_download_products') & isset($_GET['products']))
            {
                if (isset($_GET['sku']))
                {
                    $data = array
                    (
                        'sku'                       => $_GET['sku']
                    );
                }
                else if (isset($_GET['modified_date']))
                {
                    $data = array
                    (
                        'modified_date'             => $_GET['modified_date']
                    );
                }
                else
                {
                    $data = array
                    (
                        'limit'                     => $this->config->get('zynk_download_limit'),
                        'start'                     => 0
                    );
                }

                $this->download_products($data);
            }
            
            if (isset($_GET['customers']))
            {
                // Limit by account_reference
                if (isset($_GET['account_reference']))
                {
                    $customers = $this->customers->GetCustomerByAccountReference($_GET['account_reference']);

                    foreach ($customers->rows as $customer)
                    {
                        $this->download_customer($customer['customer_id'], null, null, null);
                    }
                
                }
            }

            $this->devkit->Download();
        }
        else
        {
            echo("Download not enabled");
        }
    }

    public function upload()
    {
        $this->_startup();

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

        // check if we are dealing with a notify or download
        if (!empty($post))
        {
            $this->devkit->Upload($post);

            // Create or Update Products
            if ($this->config->get('zynk_upload_products'))
            {
                $this->screenOutput("</br></br><b>Products</b></br>", 2);
                $this->products->UpdateProducts($this->devkit->Products);
                //$this->products->SetOptionedProductPricing();
                //$this->products->CleanupProductOptions();
            }
			
			// Create or Update Product Quantity Price Breaks
            if ($this->config->get('zynk_upload_product_price_breaks'))
            {
                $this->screenOutput("</br></br><b>Product Quantity Breaks</b></br>", 2);
                $this->products->UpsertQuantityPriceBreaks($this->devkit->Products);
            }

            // Create Pricelists
            if ($this->config->get('zynk_upload_pricelists'))
            {
                $this->screenOutput("</br></br><b>Pricelists</b></br>", 2);
                $this->pricelists->UploadPricelists($this->devkit->PriceLists);
            }

            // Create or Update Customers
            if ($this->config->get('zynk_upload_customers'))
            {
                $this->screenOutput("</br></br><b>Customers</b></br>", 2);
                $this->customers->UploadCustomers($this->devkit->Customers);
            }

            // Assign Customers to Pricelists & Pricelists to tax groups
            if ($this->config->get('zynk_upload_pricelists'))
            {
                $this->screenOutput("</br></br><b>Customers to Pricelists</b></br>", 2);
                $this->customers->AssignCustomersToPricelists($this->devkit->Customers);
                $this->screenOutput("</br></br><b>Pricelists to TaxClasses</b></br>", 2);
                $this->pricelists->AssignPricelistsToTaxClasses();
            }
			
			//Assign Products as Specials based on special offer flag in Sage
			if ($this->config->get('zynk_upload_product_special_offer'))
            {
                $this->screenOutput("</br></br><b>Setting up special offer products</b></br>", 2);
                $this->products->UpsertSpecialOfferProducts($this->devkit->Products);
            }
	
        }
        else
        {
            echo("No data sent.");
        }
    }

    private function download_order($order_id, $order_type = 'SalesOrder')
    {
        $order = $this->getOrder($order_id);
        $type = 'DK'.$order_type;

        // sales order header
        $sales_order                            = $this->devkit->{$order_type.'s'}->Add(new $type($order['order_id']));
        $sales_order->CustomerId                = $order['customer_id'];
        $sales_order->{$order_type.'Number'}    = $order['order_id'];
        $sales_order->CustomerOrderNumber       = $order['order_id'];
        $sales_order->{$order_type.'Date'}      = date('Y-m-d', strtotime($order['date_added'])) . "T00:00:00";
        //$sales_order->SalesOrderDate          = date('c', strtotime($order['date_added']));
        $sales_order->Notes1                    = substr($order['comment'], 0, 60);
        $sales_order->ForeignRate               = $order['currency_value'];
        $sales_order->Currency                  = $order['currency_code'];
        $sales_order->CurrencyUsed              = true;
        // sales order address
        $sales_order_address                    = $sales_order->{$order_type.'Address'} = new DKContact($order['customer_id']);
        $sales_order_address->Forename          = ucwords($order['payment_firstname']);
        $sales_order_address->Surname           = ucwords($order['payment_lastname']);
        $sales_order_address->Company           = ucwords($order['payment_company']);
        $sales_order_address->Address1          = ucwords($order['payment_address_1']);
        $sales_order_address->Address2          = ucwords($order['payment_address_2']);
        $sales_order_address->Town              = ucwords($order['payment_city']);
        $sales_order_address->County            = ucwords($order['payment_zone']);
        $sales_order_address->Country           = ucwords($order['payment_country']);
        $sales_order_address->Postcode          = strtoupper($order['payment_postcode']);
        $sales_order_address->Email             = $order['email'];
        $sales_order_address->Telephone         = $order['telephone'];
        $sales_order_address->Fax               = $order['fax'];

        // sales order delivery address
        $sales_delivery_address                 = $sales_order->{$order_type.'DeliveryAddress'} = new DKContact($order['customer_id']);
        $sales_delivery_address->Forename       = ucwords($order['shipping_firstname']);
        $sales_delivery_address->Surname        = ucwords($order['shipping_lastname']);
        $sales_delivery_address->Company        = ucwords($order['shipping_company']);
        $sales_delivery_address->Address1       = ucwords($order['shipping_address_1']);
        $sales_delivery_address->Address2       = ucwords($order['shipping_address_2']);
        $sales_delivery_address->Town           = ucwords($order['shipping_city']);
        $sales_delivery_address->County         = ucwords($order['shipping_zone']);
        $sales_delivery_address->Country        = ucwords($order['shipping_country']);
        $sales_delivery_address->Postcode       = strtoupper($order['shipping_postcode']);
        $sales_delivery_address->Email          = $order['email'];
        $sales_delivery_address->Telephone      = $order['telephone'];
        $sales_delivery_address->Fax            = $order['fax'];

        // payment details
        if ($this->config->get('zynk_download_payments'))
        {
            $sales_order->PaymentRef            = $order['payment_method'];
            $sales_order->PaymentAmount         = $order['total'];
            $sales_order->BankAccount           = $this->config->get('zynk_bank_account');
        }

        $sales_order->TakenBy                   = $this->config->get('zynk_taken_by');

        // products
        $product_collection                     = $sales_order->{$order_type.'Items'} = new Collection('DKItem');
        $products                               = $this->model_sale_order->getOrderProducts($order['order_id']);

        foreach ($products as $product)
        {
            $product_collection->Add($this->get_item($product));
        }

        // Is the shipping an item line?
        if ($this->config->get('zynk_shipping_as_item_line'))
        {
            $product_collection->Add($this->get_carriageItem($order));
        }
        else
        {
            $sales_order->Carriage = $this->get_carriage($order);
        }

		$coupons = $this->model_account_order->getOrderTotals($order_id);
		foreach ($coupons as $coupon)
		{
			if ($coupon['code'] == 'coupon')
			{
				$sales_order->NetValueDiscountDescription = $coupon['title'];
				$sales_order->NetValueDiscount = abs($coupon['value']);
				break;
			}
		}

        // and finally the customer
        if ($this->config->get('zynk_download_customers'))
        {
            $this->download_customer($order['customer_id'], $sales_order_address, $sales_delivery_address, $sales_order->{$order_type.'Date'});
        }
    }

    public function download_products($data)
    {
        $products                   = (!empty($data['sku'])) ? $this->products->FindProductBySku($data['sku']) : $this->model_catalog_product->getProducts($data);

        $add_product                = true;

        foreach ($products as $product)
        {
            if (isset($product['product_id']))
            {
                // Create a query with modified date integrated...
                if (!empty($data['modified_date']))
                {
                    $add_product        = ( ($product['date_added'] > $data['modified_date']) OR ($product['date_modified'] > $data['modified_date']) ) ? true : false;
                }

                if ($add_product)
                {
                    $product            = $this->model_catalog_product->getProduct($product['product_id']);

                    $p                  = new DKProduct(Guid());
                    $p->Id              = Guid();//$product['product_id'];
                    //$p->Sku             = ($product['sku'] == '') ? $product['model'] : $product['sku'];
                    $p->Sku             = $product['sku'];
                    $p->Name            = $product['name'];
                    $p->Description     = $product['name'];
                    $p->LongDescription = $product['description'];
                    $p->SupplierPartNo  = $product['upc'];
                    //$p->TaxRate       = $product['tax'];
                    $p->TaxCode         = $this->TaxClassToTaxCodeMap($product['tax_class_id']);
                    $p->SalePrice       = $product['price'];
                    $p->UnitWeight      = $product['weight'];
                    $p->Location        = $product['location'];
                    $p->ImageURL        = "http://".$_SERVER['HTTP_HOST'].'/image/'.$product['image'];
                    $p->ImageName       = str_replace("data/", "", $product['image']); //@TODO: This is the full image path, simply derive filename from this
                    $p->Publish         = $product['status'];
                    $p->DateCreated     = date("Y-m-d\TH:i:s", strtotime($product['date_added']));
                    $p->DateModified    = date("Y-m-d\TH:i:s", strtotime($product['date_modified']));

                    $this->devkit->Products->Add($p);
                }
            }

            $add_product            = true;
        }
    }
    private function get_item($product)
    {
        global $item_vat;
        $p = $this->model_catalog_product->getProduct($product['product_id']);

        $item               = new DKItem(Guid());
        $item->Id           = Guid();//($product['product_id'] == 0) ? $product['product_id'] : $p['product_id'];
        $item->Sku          = ($product['product_id'] == 0) ? $product['model'] : $p['sku'];
        $item->Name         = $product['name'];
        $item->QtyOrdered   = $product['quantity'];
        //$item->TaxCode      = $this->TaxClassToTaxCodeMap($p['tax_class_id']);
        $item->UnitPrice    = $product['price'];
        $item->TotalNet     = $product['total'];
        $item->TotalTax     = $product['tax'];
        $item->Total        = round($item->TotalNet + $item->TotalTax, 2);

        $options = $this->model_account_order->getOrderOptions($product['order_id'], $product['order_product_id']);
        if ($options)
        {
            $item->Name .= ': ';
            foreach ($options as $option)
            {
                $item->Name .= $option['name'] . ' - ' .$option['value'] . ', ';
            }
            $item->Name = substr($item->Name, 0, strlen($item->Name) - 2);

            //$productOption = $this->products->GetProductOptionValue($option['product_option_value_id']);

            //if ($productOption)
            //{
                //$item->Sku  = $productOption->row['ob_sku'];
            //}
        }

        $item_vat = $item_vat + $item->TotalTax;
        return $item;
    }

    private function get_carriage($order)
    {
        global $item_vat;

        $ShippingTaxCode    = $this->config->get('shipping_sort_order');
        $shippingNet        = $this->model_sale_shipping->getShippingNet($order['order_id']);
        $shippingVat        = $this->model_sale_shipping->getShippingVat($order['order_id'], $item_vat);

        $item               = new DKItem(Guid());
        $item->Id           = 0;
        $item->Sku          = substr($order['shipping_method'], 0, 20);
        $item->Name         = substr($order['shipping_method'], 0, 20);
        $item->QtyOrdered   = 1;
        $item->UnitPrice    = (isset($shippingNet['value'])) ? $shippingNet['value'] : 0;
        $item->TaxRate      = ($shippingVat > 0) ? $this->config->get('zynk_default_tax_rate') : 0;
        $item->TaxCode      = ($shippingVat > 0) ? $this->config->get('zynk_default_tax_code') : 0;
        $item->TotalNet     = $shippingNet['value'];
        $item->TotalTax     = round(($item->TotalNet / 100) * $item->TaxRate, 2);
        $item->Total        = round($item->TotalNet + $item->TotalTax, 2);

        return $item;
    }

    private function get_carriageItem($order)
    {
        global $item_vat;

        $ShippingTaxCode   = $this->config->get('shipping_sort_order');
        $shippingNet       = $this->model_sale_shipping->getShippingNet($order['order_id']);
        $shippingVat       = $this->model_sale_shipping->getShippingVat($order['order_id'], $item_vat);
        $item              = new DKItem(Guid());
        $item->Id          = 0;
        $item->Sku         = $order['shipping_method'];
        $item->Name        = $order['shipping_method'];
        $item->QtyOrdered  = 1;
        $item->UnitPrice   = $shippingNet['value'];
        $item->TaxRate     = ($ShippingTaxCode > 0) ? $this->config->get('zynk_default_tax_rate') : 0;
        $item->TaxCode     = ($ShippingTaxCode > 0) ? $this->config->get('zynk_default_tax_code') : 0;

        return $item;
    }

    private function download_customer($customer_id, $sales_order_address, $sales_delivery_address, $order_date)
    {
        $customer                       = $this->model_sale_customer->getCustomer($customer_id);

        if ($customer)
        {
            $cust                       = $this->devkit->Customers->Add(new DKCustomer($customer['customer_id']));
            $cust->AccountReference     = $customer['sage_account_ref'];
            $cust->CompanyName          = ucwords($customer['firstname'].' '.$customer['lastname']);
        }
        else
        {
            $cust                       = $this->devkit->Customers->Add(new DKCustomer($customer_id));
            $cust->AccountReference     = $this->config->get('zynk_account_reference');
            $cust->CompanyName          = "Guest Account: " . $customer_id;
        }
        $cust->Password                 = $customer['password'];
        $cust->CustomerInvoiceAddress   = $sales_order_address;
        $cust->CustomerDeliveryAddress  = $sales_delivery_address;
        $cust->TaxCode                  = isset($sales_order_address->Country) ? $this->GetTaxCode($this->customers->GetIsoCodeFromCountry($sales_order_address->Country)) : $this->config->get('zynk_default_tax_code');
        $cust->TermsAgreed              = 1;
    }

    // Return taxcode based on location
    public function GetTaxCode($country_code)
    {
        // Variables should really be defined in config...
        $TaxCode_UK     = $this->config->get('zynk_default_tax_code');
        $TaxCode_EU     = 1;
        $TaxCode_ROW    = 0;

        $taxCode        = $this->config->get('zynk_default_tax_code');

        $CountryCode_UK = "GB";
        $CountryCode_EU = "BE,BG,CZ,DK,DE,EE,IE,EL,ES,FR,IT,CY,LV,LT,LU,HU,MT,NL,AT,PL,PT,RO,SI,SK,FI,SE";

        if ($country_code == $CountryCode_UK)
        {
            $taxCode = $TaxCode_UK;
        }
        else
        {
            $countrycode_array = explode(",", $CountryCode_EU);

            if (in_array($country_code, $countrycode_array))
            {
                $taxCode = $TaxCode_EU;
            }
            else
            {
                $taxCode = $TaxCode_ROW;
            }
        }

        return $taxCode;
    }


    // Return TaxCode from a given Tax Class
    public function TaxClassToTaxCodeMap($tax_class_id)
    {
        $taxCode = $this->config->get('zynk_default_tax_code');

        switch ($tax_class_id)
        {
        case $this->config->get('zynk_vatable_taxclass'):
            $taxCode = $this->config->get('zynk_vatable_taxcode');
            break;
        case $this->config->get('zynk_nonvatable_taxclass'):
            $taxCode = $this->config->get('zynk_nonvatable_taxcode');
            break;
        case $this->config->get('zynk_exempt_taxclass'):
            $taxCode = $this->config->get('zynk_exempt_taxcode');
            break;
        default:
            $taxCode = $this->config->get('zynk_nonvatable_taxcode');
            break;
        }

        return $taxCode;
    }

    public function GetTaxClassData($taxClassId)
    {
        return $this->model_tax_class->getTaxClass($taxClassId);
    }

	public function addOrderHistory($order_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$data['order_status_id'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$data['order_status_id'] . "', notify = '" . (isset($data['notify']) ? (int)$data['notify'] : 0) . "', comment = '" . $this->db->escape(strip_tags($data['comment'])) . "', date_added = NOW()");

		$order_info = $this->getOrder($order_id);

        // @TODO: Should we implement this?
		// Send out any gift voucher mails
        /*
		if ($this->config->get('config_complete_status_id') == $data['order_status_id']) {
			$this->load->model('sale/voucher');

			$results = $this->model_sale_voucher->getVouchersByOrderId($order_id);

			foreach ($results as $result) {
				$this->model_sale_voucher->sendVoucher($result['voucher_id']);
			}
		}*/

      	if ($data['notify']) {
			$language = new Language($order_info['language_directory']);
			$language->load($order_info['language_filename']);
			$language->load('mail/order');

			$subject = sprintf($language->get('text_subject'), $order_info['store_name'], $order_id);

			$message  = $language->get('text_order') . ' ' . $order_id . "\n";
			$message .= $language->get('text_date_added') . ' ' . date($language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n\n";

			$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$data['order_status_id'] . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

			if ($order_status_query->num_rows) {
				$message .= $language->get('text_order_status') . "\n";
				$message .= $order_status_query->row['name'] . "\n\n";
			}

			if ($order_info['customer_id']) {
				$message .= $language->get('text_link') . "\n";
				$message .= html_entity_decode($order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id, ENT_QUOTES, 'UTF-8') . "\n\n";
			}

			if ($data['comment']) {
				$message .= $language->get('text_comment') . "\n\n";
				$message .= strip_tags(html_entity_decode($data['comment'], ENT_QUOTES, 'UTF-8')) . "\n\n";
			}

			$message .= $language->get('text_footer');

			$mail = new Mail();
			$mail->protocol = $this->config->get('config_mail_protocol');
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->hostname = $this->config->get('config_smtp_host');
			$mail->username = $this->config->get('config_smtp_username');
			$mail->password = $this->config->get('config_smtp_password');
			$mail->port = $this->config->get('config_smtp_port');
			$mail->timeout = $this->config->get('config_smtp_timeout');
			$mail->setTo($order_info['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($order_info['store_name']);
			$mail->setSubject($subject);
			$mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
		}
	}

	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND order_status_id > '0'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'telephone'               => $order_query->row['telephone'],
				'fax'                     => $order_query->row['fax'],
				'email'                   => $order_query->row['email'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_method'         => $order_query->row['shipping_method'],
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_method'          => $order_query->row['payment_method'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'language_id'             => $order_query->row['language_id'],
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'date_modified'           => $order_query->row['date_modified'],
				'date_added'              => $order_query->row['date_added'],
				'ip'                      => $order_query->row['ip']
			);
		} else {
			return false;
		}
	}

    public function screenOutput($input, $log_level)
    {
        $debugOutput = true;

        if ($debugOutput)
        {
            switch ($log_level)
            {
            case 0: //DEBUG
                echo($input);
                break;
            case 1: //ERROR
                echo("<FONT COLOR='RED'>");
                echo($input);
                echo("</FONT>");
                break;
            case 2: //INFO
                echo($input);
                break;
            case 3: //WARN
                echo($input);
                break;
            default:
                echo($input);
            }
        }
    }

}
