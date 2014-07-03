<?php

class ZynkPricelists extends Controller
{
    var $products;

    public function _startup()
    {
        include_once('zynk_products.php');
        $this->Products = new ZynkProducts($this->registry);

        include_once(DIR_APPLICATION.'../admin/model/setting/setting.php');
        $this->Setting = new ModelSettingSetting($this->registry);

        include_once(DIR_APPLICATION.'../admin/model/sale/customer_group.php');
        $this->model_sale_customer_group = new ModelSaleCustomerGroup($this->registry);
        
        
        include_once(DIR_APPLICATION.'../admin/model/localisation/tax_rate.php');
        $this->model_localisation_tax_rate = new ModelLocalisationTaxRate($this->registry);
    }

    public function UploadPricelists(&$pricelists)
    {
        $this->_startup();

        $this->CreateDefaultPricelist("DEFAULT");

        foreach($pricelists->Get() as $pricelist)
        {
            // We don't want to create the standard prices as a pricelist...
            if (strtolower($pricelist->Name) == "standard")
            {
                continue;
            }

            $customer_group     = $this->GetPricelistByRef($pricelist->Reference);
            $customer_group_id  = 0;

            // Create
            if ($customer_group->num_rows == 0)
            {
                $customer_group_id = $this->CreatePricelist($pricelist->Reference);
            }
            else
            {
                $customer_group_id = $customer_group->row['customer_group_id'];
            }

//echo("customer_group_id $customer_group_id </br>");

            foreach ($pricelist->Prices->Get() as $price)
            {
                $result     = $this->Products->FindProductBySku($price->StockCode);
                $stock_code = $price->StockCode;
                $group_name = $pricelist->Name;
                $type       = $pricelist->Type;
                switch ($type)
                {
                    case "Fixed":
                        $price      = ($price->StoredPrice != "") ? $price->StoredPrice : 0.00;
                        break;
                    case "DiscountGroup":
                        $price      = ($price->DiscountAmountValue != "") ? $price->DiscountAmountValue : 0.00;
                        break;
                    default:
                        $price      = ($price->StoredPrice != "") ? $price->StoredPrice : 0.00;
                }
                $date_start = date("Y-m-d");
                $date_end   = date("Y-m-d", strtotime(date("Y-m-d", strtotime(date("Y-m-d"))) . " +12 month"));

                // Does the product exist?
                if ($result->num_rows > 0)
                {
                    $product_id = $result->row['product_id'];
                    $result     = $this->Products->GetSpecialPrice($product_id, $customer_group_id);
                    // Is there already a special price for this product and this pricelist?
                    if ($result->num_rows > 0)
                    {
                        $this->db->query("UPDATE " . DB_PREFIX . "product_special SET price = '" . $this->db->escape($price) . "', date_start = '" . $this->db->escape($date_start) . "', date_end = '" . $this->db->escape($date_end) . "' WHERE product_id = '" . $this->db->escape($product_id) . "' AND customer_group_id = '" . $this->db->escape($customer_group_id) . "';");

                        echo("Updated Special Product Price for Product [".$stock_code ."] ID:[".$this->db->escape($product_id)."] on the Pricelist[".$group_name."]. </br>");
                    }
                    else
                    {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . $this->db->escape($product_id) . "', customer_group_id = '" . $this->db->escape($customer_group_id) . "', priority = '2', price = '" . $this->db->escape($price) . "', date_start = '" . $this->db->escape($date_start) . "', date_end = '" . $this->db->escape($date_end) . "';");

                        echo("Inserted Special Product Price for Product [".$stock_code."] ID:[".$this->db->escape($product_id)."] on the Pricelist[".$group_name."]. </br>");
                    }
                }

                // Check to see if it's a product option
                // @TODO: The SetOptionedProductPricing doesn't work correctly if called from here 
                /*
                $result     = $this->Products->FindProductOptionBySku($stock_code);
                if ($result->num_rows > 0)
                {
                    $product_option_id  = $result->row['product_option_id'];
                    
                    $result = $this->Products->GetProductOptionParentId($product_option_id);
                    
                    $data = array
                        (
                            'product_id'        => $result->row['product_id'],
                            'product_option_id' => $product_option_id,
                            'name'              => $stock_code,
                            'sku'               => $stock_code,
                            'price'             => $price,
                        );
                    $result = $this->Products->EditProductOptionValue($data);

                    if ($result->num_rows > 0)
                    {
                        echo("Updated Special Product Price for Product [".$stock_code ."] ID:[".$this->db->escape($product_id)."] on the Pricelist[".$group_name."]. </br>");
                    }
                    else
                    {
                        echo("Inserted Special Product Price for Product [".$stock_code."] ID:[".$this->db->escape($product_id)."] on the Pricelist[".$group_name."]. </br>");
                    }

                }
                */
            }
        }
        //$this->Products->SetOptionedProductPricing();
    }

    public function GetPricelistByRef($ref)
    {
        $result = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer_group WHERE name = '" . $this->db->escape($ref). "'");
        return $result;
    }

    public function CreatePricelist($ref)
    {
        $result = $this->db->query("INSERT INTO " . DB_PREFIX . "customer_group SET name = '" . $this->db->escape($ref) . "';");

        echo("Inserted Price List:[".$ref."]. </br>");

        return $this->db->getLastId();
    }
    
    // OpenCart requires a default pricelist for customers.
    // Create this and ensure no products are assigned to it.
    public function CreateDefaultPricelist($ref)
    {
        $customer_group = $this->GetPricelistByRef($ref);
        
        // Create the data object for the config settings.
        $data = $this->Setting->getSetting('config');

        if ($customer_group->num_rows == 0)
        {
            $customer_group_id = $this->CreatePricelist($ref);

            $data["config_customer_group_id"] = $customer_group_id;
            $this->Setting->editSetting('config', $data);

            echo("Updated the default Price List $ref ID:[$customer_group_id]. </br>");
        }
        else
        {
            $customer_group_id = $customer_group->row['customer_group_id'];
            echo("The Price List '$ref' ID:[$customer_group_id] already exists. </br>");
        }
    }


    public function AssignPricelistsToTaxClasses()
    {
        $customer_groups = $this->model_sale_customer_group->getCustomerGroups();
        $tax_rates       = $this->model_localisation_tax_rate->getTaxRates();

        if ($customer_groups && $tax_rates)
        {
            foreach ($customer_groups as $c)
            {
                foreach ($tax_rates as $t)
                {
                    $data = $this->CheckTaxRateToCustomerGroup($t['tax_rate_id'], $c['customer_group_id']);
                    if ($data->num_rows == 0)
                    {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "tax_rate_to_customer_group SET tax_rate_id = '" . (int)$t['tax_rate_id'] . "', customer_group_id = '" . (int)$c['customer_group_id'] . "'");
                        echo("Setting the Tax Class for Customer Group " . $c['name'] . " to " . $t['name'] . ".</br>");
			        }
                    else
                    {
                        echo("Customer Group " . $c['name'] . " is already assigned to Tax Class '" . $t['name'] . "'.</br>");
                    }
                }
            }
        }
    }

    public function CheckTaxRateToCustomerGroup($taxrate_id, $customer_group_id)
    { 
        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "tax_rate_to_customer_group WHERE tax_rate_id = " . $taxrate_id . " AND customer_group_id = " . $customer_group_id);

        return $result;
    }

}
