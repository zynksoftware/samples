<?php

    // Make sure we always set a default here...
    define('zynk_download_orders_default',                  '0');
    define('zynk_download_products_default',                '0');
    define('zynk_download_type_default',                    'sales_order');
    define('zynk_download_stage_default',                   '1');
    define('zynk_notify_stage_default',                     '2');
    define('zynk_status_default',                           '1');
    define('zynk_download_customers_default',               '0');
    define('zynk_upload_customers_default',                 '0');
    define('zynk_upload_products_default',                  '1');
    define('zynk_upload_product_images_default',            '1');
    define('zynk_upload_product_descriptions_default',      '1');
    define('zynk_upload_product_quantities_default',        '1');
	define('zynk_upload_product_price_breaks_default',		'0');
	define('zynk_upload_product_special_offer_default',		'0');
    define('zynk_upload_categories_default',                '0');
    define('zynk_upload_pricelists_default',                '0');
    define('zynk_download_payments_default',                '0');
    define('zynk_account_reference_default',                'WEBSALES');
    define('zynk_bank_account_default',                     '1200');
    define('zynk_taken_by_default',                         'ZYNK_WEB');
    define('zynk_default_category_default',                 '0');
    define('zynk_default_product_image_directory_default',  'data/products');
    define('zynk_default_tax_code_default',                 '1');
    define('zynk_default_tax_rate_default',                 '20');
    define('zynk_shipping_as_item_line_default',            '0');
    define('zynk_download_limit_default',                   '100');
    define('zynk_vatable_taxclass_default',                 '0');
    define('zynk_nonvatable_taxclass_default',              '0');
    define('zynk_exempt_taxclass_default',                  '0');
    define('zynk_vatable_taxcode_default',                  '1');
    define('zynk_nonvatable_taxcode_default',               '0');
    define('zynk_exempt_taxcode_default',                   '2');
	

class ControllerModuleZynk extends Controller
{
    private $error = array();

    // As we set form items to invalid to prevent access to these they are not pushed through in the form post
    // revalidate the form and set the missing data to the defaults
    public function validatePost()
    {
        if (!isset($this->request->post['zynk_download_orders'])) { $this->request->post['zynk_download_orders'] = zynk_download_orders_default; }
        if (!isset($this->request->post['zynk_download_products'])) { $this->request->post['zynk_download_products'] = zynk_download_products_default; }
        if (!isset($this->request->post['zynk_download_type'])) { $this->request->post['zynk_download_type'] = zynk_download_type_default; }
        if (!isset($this->request->post['zynk_download_stage'])) { $this->request->post['zynk_download_stage'] = zynk_download_stage_default; }
        if (!isset($this->request->post['zynk_notify_stage'])) { $this->request->post['zynk_notify_stage'] = zynk_notify_stage_default; }
        if (!isset($this->request->post['zynk_status'])) { $this->request->post['zynk_status'] = zynk_status_default; }
        if (!isset($this->request->post['zynk_download_customers'])) { $this->request->post['zynk_download_customers'] = zynk_download_customers_default; }
        if (!isset($this->request->post['zynk_upload_customers'])) { $this->request->post['zynk_upload_customers'] = zynk_upload_customers_default; }
        if (!isset($this->request->post['zynk_upload_products'])) { $this->request->post['zynk_upload_products'] = zynk_upload_products_default; }
        if (!isset($this->request->post['zynk_upload_product_images'])) { $this->request->post['zynk_upload_product_images'] = zynk_upload_product_images_default; }
        if (!isset($this->request->post['zynk_upload_product_descriptions'])) { $this->request->post['zynk_upload_product_descriptions'] = zynk_upload_product_descriptions_default; }
        if (!isset($this->request->post['zynk_upload_product_quantities'])) { $this->request->post['zynk_upload_product_quantities'] = zynk_upload_product_quantities_default; }
        if (!isset($this->request->post['zynk_upload_categories'])) { $this->request->post['zynk_upload_categories'] = zynk_upload_categories_default; }
        if (!isset($this->request->post['zynk_upload_pricelists'])) { $this->request->post['zynk_upload_pricelists'] = zynk_upload_pricelists_default; }
		if (!isset($this->request->post['zynk_upload_product_price_breaks'])) { $this->request->post['zynk_upload_product_price_breaks'] = zynk_upload_product_price_breaks_default; }
		if (!isset($this->request->post['zynk_upload_product_special_offer'])) { $this->request->post['zynk_upload_product_special_offer'] = zynk_upload_product_special_offer_default; }
        if (!isset($this->request->post['zynk_download_payments'])) { $this->request->post['zynk_download_payments'] = zynk_download_payments_default; }
        if (!isset($this->request->post['zynk_account_reference'])) { $this->request->post['zynk_account_reference'] = zynk_account_reference_default; }
        if (!isset($this->request->post['zynk_bank_account'])) { $this->request->post['zynk_bank_account'] = zynk_bank_account_default; }
        if (!isset($this->request->post['zynk_taken_by'])) { $this->request->post['zynk_taken_by'] = zynk_taken_by_default; }
        if (!isset($this->request->post['zynk_default_category'])) { $this->request->post['zynk_default_category'] = zynk_default_category_default; }
        if (!isset($this->request->post['zynk_default_product_image_directory'])) { $this->request->post['zynk_default_product_image_directory'] = zynk_default_product_image_directory_default; }
        if (!isset($this->request->post['zynk_default_tax_code'])) { $this->request->post['zynk_default_tax_code'] = zynk_default_tax_code_default; }
        if (!isset($this->request->post['zynk_default_tax_rate'])) { $this->request->post['zynk_default_tax_rate'] = zynk_default_tax_rate_default; }
        if (!isset($this->request->post['zynk_shipping_as_item_line'])) { $this->request->post['zynk_shipping_as_item_line'] = zynk_shipping_as_item_line_default; }
        if (!isset($this->request->post['zynk_download_limit'])) { $this->request->post['zynk_download_limit'] = zynk_download_limit_default; }
        if (!isset($this->request->post['zynk_vatable_taxclass'])) { $this->request->post['zynk_vatable_taxclass'] = zynk_vatable_taxclass_default; }
        if (!isset($this->request->post['zynk_exempt_taxclass'])) { $this->request->post['zynk_exempt_taxclass'] = zynk_exempt_taxclass_default; }
        if (!isset($this->request->post['zynk_nonvatable_taxclass'])) { $this->request->post['zynk_nonvatable_taxclass'] = zynk_nonvatable_taxclass_default; }
        if (!isset($this->request->post['zynk_vatable_taxcode'])) { $this->request->post['zynk_vatable_taxcode'] = zynk_vatable_taxcode_default; }
        if (!isset($this->request->post['zynk_nonvatable_taxcode'])) { $this->request->post['zynk_nonvatable_taxcode'] = zynk_nonvatable_taxcode_default; }
        if (!isset($this->request->post['zynk_exempt_taxcode'])) { $this->request->post['zynk_exempt_taxcode'] = zynk_exempt_taxcode_default; }
    }

    public function index() {

        $this->load->language('module/zynk');
        $this->load->model('setting/setting');

        // Save into DB
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
        
            $this->validatePost();
            $this->model_setting_setting->editSetting('zynk', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->data['heading_title']                        = $this->language->get('heading_title');

        $this->data['text_enabled']                         = $this->language->get('text_enabled');
        $this->data['text_disabled']                        = $this->language->get('text_disabled');
        $this->data['text_left']                            = $this->language->get('text_left');
        $this->data['text_right']                           = $this->language->get('text_right');
        $this->data['text_sales_order']                     = $this->language->get('text_sales_order');
        $this->data['text_invoice']                         = $this->language->get('text_invoice');
        $this->data['text_none']                            = $this->language->get('text_none');

        $this->data['entry_download_orders']                = $this->language->get('entry_download_orders');
        $this->data['entry_download_products']              = $this->language->get('entry_download_products');
        $this->data['entry_download_type']                  = $this->language->get('entry_download_type');
        $this->data['entry_download_stage']                 = $this->language->get('entry_download_stage');
        $this->data['entry_notify_stage']                   = $this->language->get('entry_notify_stage');
        $this->data['entry_status']                         = $this->language->get('entry_status');
        $this->data['entry_download_customers']             = $this->language->get('entry_download_customers');
        $this->data['entry_upload_customers']               = $this->language->get('entry_upload_customers');
        $this->data['entry_upload_products']                = $this->language->get('entry_upload_products');
        $this->data['entry_upload_product_images']          = $this->language->get('entry_upload_product_images');
        $this->data['entry_upload_product_descriptions']    = $this->language->get('entry_upload_product_descriptions');
        $this->data['entry_upload_product_quantities']      = $this->language->get('entry_upload_product_quantities');
		$this->data['entry_upload_product_price_breaks']    = $this->language->get('entry_upload_product_price_breaks');
		$this->data['entry_upload_product_special_offer']    = $this->language->get('entry_upload_product_special_offer');
        $this->data['entry_upload_categories']              = $this->language->get('entry_upload_categories');
        $this->data['entry_upload_pricelists']              = $this->language->get('entry_upload_pricelists');
        $this->data['entry_download_payments']              = $this->language->get('entry_download_payments');
        $this->data['entry_account_reference']              = $this->language->get('entry_account_reference');
        $this->data['entry_bank_account']                   = $this->language->get('entry_bank_account');
        $this->data['entry_taken_by']                       = $this->language->get('entry_taken_by');
        $this->data['entry_default_category']               = $this->language->get('entry_default_category');
        $this->data['entry_default_product_image_directory'] = $this->language->get('entry_default_product_image_directory');
        $this->data['entry_default_tax_code']               = $this->language->get('entry_default_tax_code');
        $this->data['entry_default_tax_rate']               = $this->language->get('entry_default_tax_rate');
        $this->data['entry_shipping_as_item_line']          = $this->language->get('entry_shipping_as_item_line');
        $this->data['entry_download_limit']                 = $this->language->get('entry_download_limit');
        $this->data['entry_vatable_taxclass']               = $this->language->get('entry_vatable_taxclass');
        $this->data['entry_nonvatable_taxclass']            = $this->language->get('entry_nonvatable_taxclass');
        $this->data['entry_exempt_taxclass']                = $this->language->get('entry_exempt_taxclass');
        $this->data['entry_vatable_taxcode']                = $this->language->get('entry_vatable_taxcode');
        $this->data['entry_nonvatable_taxcode']             = $this->language->get('entry_nonvatable_taxcode');
        $this->data['entry_exempt_taxcode']                 = $this->language->get('entry_exempt_taxcode');
        
        $this->data['button_save']                          = $this->language->get('button_save');
        $this->data['button_cancel']                        = $this->language->get('button_cancel');

        if (isset($this->error['warning']))
        {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->error['code']))
        {
            $this->data['error_code'] = $this->error['code'];
        } else {
            $this->data['error_code'] = '';
        }
        
        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        
        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('module/zynk', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->load->model('catalog/category');
        $categories = new ModelCatalogCategory($this->registry);
        $this->data['categories'] = $categories->getCategories();
        
        $this->load->model('localisation/tax_class');
        $tax_classes = new ModelLocalisationTaxClass($this->registry);
        $this->data['tax_classes'] = $tax_classes->getTaxClasses();

        $this->data['action'] = $this->url->link('module/zynk', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

        // are we downloading orders . . .
        if (isset($this->request->post['zynk_download_orders']))
        {
            $this->data['zynk_download_orders'] = $this->request->post['zynk_download_orders'];
        }
        else
        {
            $this->data['zynk_download_orders'] = $this->config->get('zynk_download_orders');
        }

        // are we downloading products . . .
        if (isset($this->request->post['zynk_download_products']))
        {
            $this->data['zynk_download_products'] = $this->request->post['zynk_download_products'];
        }
        else
        {
            $this->data['zynk_download_products'] = $this->config->get('zynk_download_products');
        }

        // download orders as . . .
        if (isset($this->request->post['zynk_download_type']))
        {
            $this->data['zynk_download_type'] = $this->request->post['zynk_download_type'];
        }
        else
        {
            $this->data['zynk_download_type'] = $this->config->get('zynk_download_type');
        }

        $this->load->model('localisation/order_status');
        $this->data['order_status']	= $this->model_localisation_order_status->getOrderStatuses();
        // download orders at stage . . .
        if (isset($this->request->post['zynk_download_stage']))
        {
            $this->data['zynk_download_stage'] = $this->request->post['zynk_download_stage'];
        }
        else
        {
            $this->data['zynk_download_stage'] = $this->config->get('zynk_download_stage');
        }

        // notify orders to stage . . .
        if (isset($this->request->post['zynk_notify_stage']))
        {
            $this->data['zynk_notify_stage'] = $this->request->post['zynk_notify_stage'];
        }
        else
        {
            $this->data['zynk_notify_stage'] = $this->config->get('zynk_notify_stage');
        }

        // are we downloading customers . . .
        if (isset($this->request->post['zynk_download_customers']))
        {
            $this->data['zynk_download_customers'] = $this->request->post['zynk_download_customers'];
        }
        else
        {
            $this->data['zynk_download_customers'] = $this->config->get('zynk_download_customers');
        }

        // are we uploading customers . . .
        if (isset($this->request->post['zynk_upload_customers']))
        {
            $this->data['zynk_upload_customers'] = $this->request->post['zynk_upload_customers'];
        }
        else
        {
            $this->data['zynk_upload_customers'] = $this->config->get('zynk_upload_customers');
        }

        // are we uploading products . . .
        if (isset($this->request->post['zynk_upload_products']))
        {
            $this->data['zynk_upload_products'] = $this->request->post['zynk_upload_products'];
        }
        else
        {
            $this->data['zynk_upload_products'] = $this->config->get('zynk_upload_products');
        }

        // are we uploading product images . . .
        if (isset($this->request->post['zynk_upload_product_images']))
        {
            $this->data['zynk_upload_product_images'] = $this->request->post['zynk_upload_product_images'];
        }
        else
        {
            $this->data['zynk_upload_product_images'] = $this->config->get('zynk_upload_product_images');
        }

        // are we uploading product descriptions . . .
        if (isset($this->request->post['zynk_upload_product_descriptions']))
        {
            $this->data['zynk_upload_product_descriptions'] = $this->request->post['zynk_upload_product_descriptions'];
        }
        else
        {
            $this->data['zynk_upload_product_descriptions'] = $this->config->get('zynk_upload_product_descriptions');
        }
        // are we uploading product quantities . . .
        if (isset($this->request->post['zynk_upload_product_quantities']))
        {
            $this->data['zynk_upload_product_quantities'] = $this->request->post['zynk_upload_product_quantities'];
        }
        else
        {
            $this->data['zynk_upload_product_quantities'] = $this->config->get('zynk_upload_product_quantities');
        }
		
		// are we uploading product quantity breaks . . .
        if (isset($this->request->post['zynk_upload_product_price_breaks']))
        {
            $this->data['zynk_upload_product_price_breaks'] = $this->request->post['zynk_upload_product_price_breaks'];
        }
        else
        {
            $this->data['zynk_upload_product_price_breaks'] = $this->config->get('zynk_upload_product_price_breaks');
        }
		
		// are we uploading product special offer (if a product has special offer checked in Sage, then will be added to Special in opencart; but with sale price)
        if (isset($this->request->post['zynk_upload_product_special_offer']))
        {
            $this->data['zynk_upload_product_special_offer'] = $this->request->post['zynk_upload_product_special_offer'];
        }
        else
        {
            $this->data['zynk_upload_product_special_offer'] = $this->config->get('zynk_upload_product_special_offer');
        }
        
        // are we uploading categories . . .
        if (isset($this->request->post['zynk_upload_categories']))
        {
            $this->data['zynk_upload_categories'] = $this->request->post['zynk_upload_categories'];
        }
        else
        {
            $this->data['zynk_upload_categories'] = $this->config->get('zynk_upload_categories');
        }

        // are we uploading pricelists . . .
        if (isset($this->request->post['zynk_upload_pricelists']))
        {
            $this->data['zynk_upload_pricelists'] = $this->request->post['zynk_upload_pricelists'];
        }
        else
        {
            $this->data['zynk_upload_pricelists'] = $this->config->get('zynk_upload_pricelists');
        }

        // are we downloading payments . . .
        if (isset($this->request->post['zynk_download_payments']))
        {
            $this->data['zynk_download_payments'] = $this->request->post['zynk_download_payments'];
        }
        else
        {
            $this->data['zynk_download_payments'] = $this->config->get('zynk_download_payments');
        }

        // using single account reference . . .
        $zynk_account_reference = $this->config->get('zynk_account_reference');
        if (isset($this->request->post['zynk_account_reference']))
        {
            $this->data['zynk_account_reference'] = $this->request->post['zynk_account_reference'];
        }
        elseif (!isset($zynk_account_reference))
        {
            $this->data['zynk_account_reference'] = zynk_account_reference_default;
        }
        else
        {
            $this->data['zynk_account_reference'] = $zynk_account_reference;
        }

        // bank account for payments . . .
        $zynk_bank_account = $this->config->get('zynk_bank_account');
        if (isset($this->request->post['zynk_bank_account']))
        {
            $this->data['zynk_bank_account'] = $this->request->post['zynk_bank_account'];
        }
        elseif (!isset($zynk_bank_account))
        {
            $this->data['zynk_bank_account'] = zynk_bank_account_default;
        }
        else
        {
            $this->data['zynk_bank_account'] = $zynk_bank_account;
        }

        // taken by field . . .
        $zynk_taken_by = $this->config->get('zynk_taken_by');
        if (isset($this->request->post['zynk_taken_by']))
        {
            $this->data['zynk_taken_by'] = $this->request->post['zynk_taken_by'];
        }
        elseif (!isset($zynk_taken_by))
        {
            $this->data['zynk_taken_by'] = zynk_taken_by_default;
        }
        else
        {
            $this->data['zynk_taken_by'] = $zynk_taken_by;
        }

        // default category field . . .
        $zynk_default_category = $this->config->get('zynk_default_category');
        if (isset($this->request->post['zynk_default_category']))
        {
            $this->data['zynk_default_category'] = $this->request->post['zynk_default_category'];
        }
        elseif (!isset($zynk_default_category))
        {
            $this->data['zynk_default_category'] = zynk_default_category_default;
        }
        else
        {
            $this->data['zynk_default_category'] = $zynk_default_category;
        }

        // default product image directory . . .
        $zynk_default_product_image_directory = $this->config->get('zynk_default_product_image_directory');
        if (isset($this->request->post['zynk_default_product_image_directory']))
        {
            $this->data['zynk_default_product_image_directory'] = $this->request->post['zynk_default_product_image_directory'];
        }
        elseif (!isset($zynk_default_product_image_directory))
        {
            $this->data['zynk_default_product_image_directory'] = zynk_default_product_image_directory_default;
        }
        else
        {
            $this->data['zynk_default_product_image_directory'] = $zynk_default_product_image_directory;
        }

        // default tax code
        $zynk_default_tax_code = $this->config->get('zynk_default_tax_code');
        if (isset($this->request->post['zynk_default_tax_code']))
        {
            $this->data['zynk_default_tax_code'] = $this->request->post['zynk_default_tax_code'];
        }
        elseif (!isset($zynk_default_tax_code))
        {
            $this->data['zynk_default_tax_code'] = zynk_default_tax_code_default;
        }
        else
        {
            $this->data['zynk_default_tax_code'] = $zynk_default_tax_code;
        }

        // default tax rate
        $zynk_default_tax_rate = $this->config->get('zynk_default_tax_rate');
        if (isset($this->request->post['zynk_default_tax_rate']))
        {
            $this->data['zynk_default_tax_rate'] = $this->request->post['zynk_default_tax_rate'];
        }
        elseif (!isset($zynk_default_tax_rate))
        {
            $this->data['zynk_default_tax_rate'] = zynk_default_tax_rate_default;
        }
        else
        {
            $this->data['zynk_default_tax_rate'] = $zynk_default_tax_rate;
        }

        // shipping as item line
        $zynk_shipping_as_item_line = $this->config->get('zynk_shipping_as_item_line');
        if (isset($this->request->post['zynk_shipping_as_item_line']))
        {
            $this->data['zynk_shipping_as_item_line'] = $this->request->post['zynk_shipping_as_item_line'];
        }
        elseif (!isset($zynk_shipping_as_item_line))
        {
            $this->data['zynk_shipping_as_item_line'] = zynk_shipping_as_item_line_default;
        }
        else
        {
            $this->data['zynk_shipping_as_item_line'] = $this->config->get('zynk_shipping_as_item_line');
        }

        // data download limit
        $zynk_download_limit = $this->config->get('zynk_download_limit');
        if (isset($this->request->post['zynk_download_limit']))
        {
            $this->data['zynk_download_limit'] = $this->request->post['zynk_download_limit'];
        }
        elseif (!isset($zynk_download_limit))
        {
            $this->data['zynk_download_limit'] = zynk_download_limit_default;
        }
        else
        {
            $this->data['zynk_download_limit'] = $zynk_download_limit;
        }
        
        // default vatable taxclass
        $zynk_vatable_taxclass = $this->config->get('zynk_vatable_taxclass');
        if (isset($this->request->post['zynk_vatable_taxclass']))
        {
            $this->data['zynk_vatable_taxclass'] = $this->request->post['zynk_vatable_taxclass'];
        }
        elseif (!isset($zynk_vatable_taxclass))
        {
            $this->data['zynk_vatable_taxclass'] = zynk_vatable_taxclass_default;
        }
        else
        {
            $this->data['zynk_vatable_taxclass'] = $zynk_vatable_taxclass;
        }
        
        // default nonvatable taxclass
        $zynk_nonvatable_taxclass = $this->config->get('zynk_nonvatable_taxclass');
        if (isset($this->request->post['zynk_nonvatable_taxclass']))
        {
            $this->data['zynk_nonvatable_taxclass'] = $this->request->post['zynk_nonvatable_taxclass'];
        }
        elseif (!isset($zynk_nonvatable_taxclass))
        {
            $this->data['zynk_nonvatable_taxclass'] = zynk_nonvatable_taxclass_default;
        }
        else
        {
            $this->data['zynk_nonvatable_taxclass'] = $zynk_nonvatable_taxclass;
        }

        // default exempt taxclass
        $zynk_exempt_taxclass = $this->config->get('zynk_exempt_taxclass');
        if (isset($this->request->post['zynk_exempt_taxclass']))
        {
            $this->data['zynk_exempt_taxclass'] = $this->request->post['zynk_exempt_taxclass'];
        }
        elseif (!isset($zynk_exempt_taxclass))
        {
            $this->data['zynk_exempt_taxclass'] = zynk_exempt_taxclass_default;
        }
        else
        {
            $this->data['zynk_exempt_taxclass'] = $zynk_exempt_taxclass;
        }
        
        // default vatable taxcode
        $zynk_vatable_taxcode = $this->config->get('zynk_vatable_taxcode');
        if (isset($this->request->post['zynk_vatable_taxcode']))
        {
            $this->data['zynk_vatable_taxcode'] = $this->request->post['zynk_vatable_taxcode'];
        }
        elseif (!isset($zynk_vatable_taxcode))
        {
            $this->data['zynk_vatable_taxcode'] = zynk_vatable_taxcode_default;
        }
        else
        {
            $this->data['zynk_vatable_taxcode'] = $zynk_vatable_taxcode;
        }

        // default nonvatable taxcode
        $zynk_nonvatable_taxcode = $this->config->get('zynk_nonvatable_taxcode');
        if (isset($this->request->post['zynk_nonvatable_taxcode']))
        {
            $this->data['zynk_nonvatable_taxcode'] = $this->request->post['zynk_nonvatable_taxcode'];
        }
        elseif (!isset($zynk_nonvatable_taxcode))
        {
            $this->data['zynk_nonvatable_taxcode'] = zynk_nonvatable_taxcode_default;
        }
        else
        {
            $this->data['zynk_nonvatable_taxcode'] = $zynk_nonvatable_taxcode;
        }

        // default exempt taxcode
        $zynk_exempt_taxcode = $this->config->get('zynk_exempt_taxcode');
        if (isset($this->request->post['zynk_exempt_taxcode']))
        {
            $this->data['zynk_exempt_taxcode'] = $this->request->post['zynk_exempt_taxcode'];
        }
        elseif (!isset($zynk_exempt_taxcode))
        {
            $this->data['zynk_exempt_taxcode'] = zynk_exempt_taxcode_default;
        }
        else
        {
            $this->data['zynk_exempt_taxcode'] = $zynk_exempt_taxcode;
        }

        // module status - enabled / disabled
        $zynk_status = $this->config->get('zynk_status');
        if (isset($this->request->post['zynk_status']))
        {
            $this->data['zynk_status'] = $this->request->post['zynk_status'];
        }
        elseif (!isset($zynk_status))
        {
            $this->data['zynk_status'] = zynk_status_default;
        }
        else
        {
            $this->data['zynk_status'] = $this->config->get('zynk_status');
        }

        $this->template = 'module/zynk.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/zynk'))
        {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
}
?>