<?php

define('_ATTRIBUTE_OPTION_NAME_', 'Please Choose an Option');

class ZynkProducts extends Controller 
{
    var $categories;

    private function _startup()
    {
        include_once('zynk_categories.php');
        $this->Categories = new ZynkCategories($this->registry);

        include(DIR_APPLICATION.'../admin/model/localisation/weight_class.php');
        $this->model_localisation_weight_class = new ModelLocalisationWeightClass($this->registry);
    }

    public function UpdateProducts(&$products)
    {
        $this->_startup();

        foreach($products->Get() as $product)
        {
            // Should we upload the product?
            if($this->CheckIfShouldUpload($product))
            {            
                echo("********</br>");

                // Check if this is a product option
                $isOption = $this->CheckIfProductOption($product);

                if(!$isOption)
                {
                    $this->SetProduct($product);
					
                }
                else
                {
                    // Not dealing with these
                    //$this->CreateProductOption($product);
                }
            }
        }
    }
    public function UpsertQuantityPriceBreaks(&$products)
	{
		//get the valid customer groups for the site
		//at present assuming that quantity breaks
		//will be applied to all users of site
		//should really have an option in the zynk config for if the
		//qty breaks are to be site wide or based on qty break ref
		$groups = $this->db->query("SELECT customer_group_id FROM ".DB_PREFIX."customer_group;");
		if ($groups->num_rows == 0)
		{
			//if no groups set up on site, then can't really do much so just return.
			return;
		}
		
		foreach($products->Get() as $product)
        {
			//get the product id
			$p = $this->FindProductBySku($product->Sku);
			$productId = 0;
			if ($p->num_rows > 0)
			{
				if (isset($p->row['product_id']) && $p->row['product_id']!=null)
					$productId = $p->row['product_id'];
			}
				
			//if we've got this far and id is valid....
			if ($productId > 0)
			{
				$productPrice = $product->SalePrice;
				//delete any existing qty breaks for this product (we set priority to 99 on all our uploads so we don't delete user entered prices)
				$this->db->query("DELETE FROM ".DB_PREFIX."product_discount WHERE product_id='$productId' AND priority='99';");
				foreach ($groups->rows as $group)
				{
					$groupId = $group['customer_group_id'];
					foreach($product->ProductQtyBreaks->Get() as $qtyBreak)
					{
						$this->InsertQtyBreak($productId, $groupId, $qtyBreak, $productPrice);
					}
				}
			}
        }
	}
	public function UpsertSpecialOfferProducts(&$products)
	{
		//get the valid customer groups for the site
		//at present assuming that quantity breaks
		//will be applied to all users of site
		//should really have an option in the zynk config for if the
		//qty breaks are to be site wide or based on qty break ref
		$groups = $this->db->query("SELECT customer_group_id FROM ".DB_PREFIX."customer_group;");
		if ($groups->num_rows == 0)
		{
			//if no groups set up on site, then can't really do much so just return.
			return;
		}
		
		echo("</br>");
		foreach($products->Get() as $product)
        {
			//get the product id
			$p = $this->FindProductBySku($product->Sku);
			$productId = 0;
			if ($p->num_rows > 0)
			{
				if (isset($p->row['product_id']) && $p->row['product_id']!=null)
					$productId = $p->row['product_id'];
			}
				
			//if we've got this far and id is valid....
			if ($productId > 0)
			{
				//if product special flag is present, not null and equal to true, set to true, otherwise false
				$productSpecial = (ISSET($product->SpecialOffer) && ($product->SpecialOffer != null) && ($product->SpecialOffer=="true"))?true:false;
				//delete any existing special for this product (we set priority to 99 on all our uploads so we don't delete user entered prices)
				$this->db->query("DELETE FROM ".DB_PREFIX."product_special WHERE product_id='$productId' AND priority='99';");
				if ($productSpecial)
				{	
					echo("Adding special offer for product with Sku '$product->Sku'</br>");
					$productPrice = $product->SalePrice;
					foreach ($groups->rows as $group)
					{
						$groupId = $group['customer_group_id'];
						$this->InsertProductSpecial($productId, $groupId, $productPrice);
					}
				}
			}
        }		
		echo("</br>");
	}
    /**
     * Sanitize the title to make it url friendly
     * 
     * @param   string
     * @return  string
     */
    protected function _sanitizeTitle($title) 
    {
       $title = preg_replace('/[^A-Za-z0-9-]+/', '-', $title);
       
       return trim(strtolower($title));
    }
    
    /**
     * Add an SEO rewrite for this product..
     * 
     * @param   int
     * @param   string
     * @return  void
     */
    protected function _addUrlRewrite($productId, $productName)
    {
        $productName = $this->_sanitizeTitle($productName); // sanitize when we save it!

        $query = $this->db->query("SELECT url_alias_id FROM ".DB_PREFIX."url_alias WHERE query = 'product_id=" . (int)$productId . "';");

        if ($query->num_rows > 0)
        {
            $query_start = "UPDATE ";
            $query_end   = " WHERE query = 'product_id=" . (int)$productId . "';";
        }
        else
        {
            $query_start = "INSERT INTO ";
            $query_end   = " on duplicate key update query = 'product_id=" . (int)$productId . "'";
        }
        
        $query_middle = DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$productId . "', keyword = '" . $this->db->escape($productName) . "' ";
        
        $query = $query_start . $query_middle . $query_end;

        $this->db->query($query);
    }

    public function SetProduct($product)
    {
        $dbProd = $this->db->query("SELECT product_id, sku FROM ".DB_PREFIX."product WHERE LOWER(`sku`) = LOWER('$product->Sku');");

        if ($dbProd->num_rows > 0)
        {
            $query = "UPDATE ".DB_PREFIX."product SET ";
            $query .= "date_modified = NOW(), ";
            $type = "Updated";
        }
        else
        {
            $query = "INSERT INTO ".DB_PREFIX."product SET ";
            $query .= "date_added = NOW(), ";
            $type = "Inserted";
        }

        $status = $this->getStatus($product);

        if($this->config->get('zynk_upload_product_quantities'))
        {
            $free_stock = $product->QtyInStock - $product->QtyAllocated;
            $stock_status = 5;     // Set to 5 (lets opencart handle the logic; i.e. 0 or less quantity shows out of stock)
            $query .= "quantity         = '$free_stock ', ";
            $query .= "stock_status_id  = '$stock_status', ";
        }
        elseif ($dbProd->num_rows == 0)
        {
            // If we're adding a new product then just assume that the main product is in stock and set its quantity high as we
            // cannot order any of the options should the parent have no stock.
            $query .= "quantity         = '9999', ";
            $query .= "stock_status_id  = '7', ";
        }

        $date   = date('Y-m-d');

        $query .= "model            = '$product->Sku', ";
        $query .= "sku              = '$product->Sku', ";
        $query .= "price            = '$product->SalePrice', ";
        $query .= "weight           = '$product->UnitWeight', ";
        //$query .= "location         = '$product->Location', ";
        $query .= "upc              = '$product->SupplierPartNo', ";
        $query .= "date_available   = '$date', ";
        $tax_class_id = ($product->TaxCode == 0) ? $this->config->get('zynk_nonvatable_taxclass') : $this->config->get('zynk_vatable_taxclass'); // Client specific, default to UK Vatable
        $query .= "tax_class_id     = '" . $tax_class_id ."', ";    // Taxable
        //$weight_class = $this->model_localisation_weight_class->getWeightClass($this->config->get('config_weight_class_id'));
        //$query .= "weight_class_id  = '" . $weight_class['weight_class_id'] ."', "; 
        
        $query .= "weight_class_id  = '" . $this->config->get('config_weight_class_id') ."', "; // set to default weight

        $query .= "status           = '$status'";

        if($this->config->get('zynk_upload_product_images'))
        {
            $imageDirectory = $this->config->get('zynk_default_product_image_directory');

            if(!empty($product->Image) && !empty($product->ImageName))
            {
                // Write image to file
                $imageData  = base64_decode($product->Image);
                $handle     = fopen(DIR_APPLICATION.$imageDirectory.'/'.$product->ImageName, "w");
                //$handle     = fopen(DIR_APPLICATION.'../image/data/'.$product->ImageName, "w");
                fwrite($handle, $imageData, strlen($imageData));
                fclose($handle);

                $query .= ", image = '$imageDirectory/$product->ImageName' ";
            }
            elseif(!empty($product->ImageName))
            {
                // Images should already be on website (FTP?)
                if ($product->ImageName == "(none)") // Sage 50 descriptor for no image
                {
                    $query .= ", image = 'no_image.jpg' ";
                }
                else
                {
                    $query .= ", image = '$imageDirectory/$product->ImageName' ";
                }
            }
            else
            {
                if($this->DoesImageExist($product->ImageName))
                {
                    $query .= ", image = '$imageDirectory/$product->ImageName' ";
                }
                elseif($this->HasImages($product->Sku) || $this->HasMultipleImages($product->Sku))
                {
                    // Leave as images have most likely been manually assigned
                }
                else
                {
                    $query .= ", image = 'no_image.jpg' ";
                }
            }
        }
        else
        {
            // Only add the image if this is a new product as may have one uploaded manually
            if ($dbProd->num_rows == 0)
            {
                $query .= ", image = 'no_image.jpg' ";
            }
        }

        if ($dbProd->num_rows > 0)
        {
            $query .= "WHERE LOWER(`sku`) = LOWER('$product->Sku');";
        }

        $this->db->query($query);

        $product_id = $dbProd->num_rows > 0 ? $dbProd->row["product_id"] : $this->db->getLastId();
        
        /**
         * We added a new product, now insert an url rewrite
         */
        if ($dbProd->num_rows > 0)
        {
            $this->_addUrlRewrite($product_id, $product->Name);
        }

        echo($type ." Product ". $product->Name . " - SKU:[".$product->Sku."] - ID:[".$product_id."]. </br>");

        // A description is required for a new product by the cart...
        $this->CreateProductDescription($product, $product_id);
/*
        if($this->config->get('zynk_upload_product_images'))
        {
            $this->AssignMultipleProductImages($product->ImageName, $product_id);
        }
*/
        $store_id = ($this->config->get('config_store_id') != "") ? $this->config->get('config_store_id') : 0;
        $this->SetProductToStore($product_id, $store_id);
        
        if($this->config->get('zynk_upload_categories'))
        {
            $this->Categories->CreateAndAssignCategory($product, $product_id);
        }
        else
        {
            // Only assign default category for new products
            if ($dbProd->num_rows == 0)
            {
                $this->Categories->CreateAndAssignDefaultCategory($product, $product_id);
            }
        }

        echo("</br>");
    }
 
    public function DoesImageExist($product_image)
    {
        return file_exists("./image/".$this->config->get('zynk_default_product_image_directory')."/$product_image");
    }
    
    public function HasImages($product_sku)
    {
        $rtnVal = false;

        $p = $this->FindProductBySku($product_sku);
        
        if ($p->num_rows > 0)
        {
            $image = $p->row['image'];

            if ($image != 'no_image.jpg')
            {
                $rtnVal = true;
            }
        }

        return $rtnVal; 
    }
    
    public function HasMultipleImages($product_sku)
    {
        $rtnVal = false;

        $p = $this->FindProductBySku($product_sku);
        
        if ($p->num_rows > 0)
        {
            $id = $p->row['product_id'];
            
            $result = $this->db->query("SELECT * FROM product_image WHERE product_id = '".$id."';");
            if ($result->num_rows > 0)
            {
                $rtnVal = true;
            }
        }

        return $rtnVal;
    }

    public function AssignMultipleProductImages($product_image, $product_id)
    {
        $images = array();

        // Get all Product Images matching wildcard
        foreach ( glob("./image/".$this->config->get('zynk_default_product_image_directory')."/$product_image-*.*") as $filename )
        {
            // Remove ./image from name
            $images[]   = str_replace("./image", "", $filename);
        }

        // Remove all existing Images
        // This allows only current images to be associated
        /* 
            17/01/2011 - Removed so existing images would never
            be removed from a product  
            $this->RemoveMultipleProductImages($product_id);
        */

        foreach ($images as $image)
        {        
            // Add new Images
            $this->InsertMultipleProductImage($product_id, $image);
        }
    }
    
    public function RemoveMultipleProductImages($product_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id='".$product_id."';");
    }
    
    public function InsertMultipleProductImage($product_id, $image)
    {
        /*
            17/01/2011 - Updated so only new images
            are added to the product
            
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id='$product_id', image='$image';");
        */

        // build sql query
        $query = "SELECT product_image_id FROM " . DB_PREFIX . "product_image WHERE product_id='$product_id' AND image='$image';";
        $result = $this->db->query($query);
        // check if there any results back
        if ($result->num_rows > 0)
        {
            // this image is already associated with the product
            // don't do anything
            echo('Image "' . $image . '" already associated with product [' . $product_id) . ']<br />';
        }
        else
        {
            echo('Adding image "' . $image . '" to product [' . $product_id) . ']<br />';

            // add new image to product
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id='$product_id', image='$image';");
        }
    }

    public function CreateProductDescription($product, $product_id)
    {
        $rows = $this->db->query("SELECT * FROM product_description WHERE product_id = ".$product_id." AND language_id = 1;");
        $name = mysql_real_escape_string(htmlentities($product->Name, ENT_QUOTES));
        
        if($this->config->get('zynk_upload_product_descriptions'))
        {
            if(!empty($product->LongDescription))
            {
                $long = mysql_real_escape_string(htmlentities($product->LongDescription, ENT_QUOTES));
            }
            else
            {
                $long = mysql_real_escape_string(htmlentities($product->Description, ENT_QUOTES));
            }
        }
        else
        {
            // A description is required for a new product by the cart, so insert a blank one.
            $long = "";
        }

        if ($rows->num_rows > 0)
        {
            if($this->config->get('zynk_upload_product_descriptions'))
            {
                $query = sprintf("UPDATE product_description SET name = '%s',  description = '%s', language_id = 1 WHERE product_id = %d AND language_id = 1;", $name, $long, $product_id);
                $this->db->query($query);

                echo("Updated Product Description for ". $name . " - SKU:[".$product->Sku."]. </br>");
            }
            else // Only update name
            {
                $query = sprintf("UPDATE product_description SET name = '%s', language_id = 1 WHERE product_id = %d AND language_id = 1;", $name, $product_id);
                $this->db->query($query);

                echo("Updated Product Name for ". $name . " - SKU:[".$product->Sku."]. </br>");
            }
        }
        else
        {
            $query = sprintf("INSERT INTO product_description (product_id, language_id, name, description) VALUES (%d, %d, '%s','%s');", $product_id, 1, $name, $long);
            $this->db->query($query);

            echo("Inserted Product Description for ". $name . " - SKU:[".$product->Sku."]. </br>");
        }
    }

    public function FindProductBySku($sku)
    {
        $result = $this->db->query("SELECT * FROM product WHERE LOWER(`sku`) = LOWER('".$sku."');");
        return $result;
    }
    public function GetProductById($id)
    {
        $result = $this->db->query("SELECT * FROM product WHERE product_id = '".$id."';");
        return $result;
    }
    public function GetSpecialPrice($product_id, $customer_group_id)
    {
        $result = $this->db->query("SELECT * FROM product_special WHERE product_id = '".(int)$product_id."' AND customer_group_id = '".(int)$customer_group_id."';");
        return $result;
    }
 
    public function SetProductToStore($product_id, $store_id)
    {
        $rows = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$store_id . "'");
        
        if ($rows->num_rows == 0)
        {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");

            echo("Inserted Product to Store for Product ID:[".$product_id."]. </br>");
        }

        return $this->db->getLastId();
    }


    public function CheckIfShouldUpload($product)
    {
        // Always upload all products
        return true;
        
        // Only upload publish to web / status = true products
        try
        {
            if ($this->getStatus($product) != 1)
            {
                echo("This product should not be uploaded $product->Sku. </br>");
                return false;
            }
        }
        catch (Exception $e)
        {
            // Don't upload the product should there be an error e.g. Category 1 does not exist
            //echo("EXCEPTION: [CheckIfShouldUpload] $product->Sku. </br>");
            return false;
        }

        return true;
    }

    public function CheckIfProductOption($product)
    {
        try
        {
            $GroupCode_Name  = $product->Attributes->Get('Group Code')->Name;
            $GroupCode_Value = $product->Attributes->Get('Group Code')->Value;
            if ($GroupCode_Value != "<NONE>" && $GroupCode_Value != "")
            {
                echo("This product is an option item '$product->Sku' -  Parent SKU: '$GroupCode_Value'. </br>");
                return true;
            }
        }
        catch (Exception $e) {}

        return false;
    }

    public function CreateProductOption($attribute_product)
    {
        $attribute_parent_sku = $this->GetAttributeValue($attribute_product, "Group Code");

        if ($attribute_parent_sku != "" | $attribute_parent_sku != null)
        {
            //attribute_option_name      = "Please Choose an Option";
            $attribute_option_value     = $this->GetAttributeValue($attribute_product, _ATTRIBUTE_OPTION_NAME_);
            $parent_product             = $this->FindProductBySku($attribute_parent_sku);

            if ($parent_product->num_rows == 0)
            {
                // Should the parent not exist then create the parent based on the details of the current product.
                echo("Product Option '". $attribute_product->Sku ."' does not have an existing parent product, this will now be created.</br>");
                $parent_product             =  clone $attribute_product;

                $parent_product->Sku        = $attribute_parent_sku;
                $parent_product->Name       = $this->GetAttributeValue($attribute_product, "Group Name");
                $parent_product->SalePrice  = "0";
                $parent_product->Status     = "1";
                //$parent_product->ImageName  = str_replace(" ", "",$attribute_parent_sku).".jpg";
                $parent_product->ImageName  = $attribute_parent_sku.".jpg";

                $this->SetProduct($parent_product);

                $parent_product  = $this->FindProductBySku($attribute_parent_sku);
            }
            else
            {
             // Should the parent not exist then create the parent based on the details of the current product.
                echo("Product Option '". $attribute_product->Sku ."' has a parent product, this will now be edited.</br>");
                $parent_product             =  clone $attribute_product;
                $parent_product->Sku        = $attribute_parent_sku;
                $parent_product->Name       = $this->GetAttributeValue($attribute_product, "Group Name");
                //$parent_product->SalePrice  = "0";
                $parent_product->Status     = "1";
                //$parent_product->ImageName  = str_replace(" ", "",$attribute_parent_sku).".jpg";
                $parent_product->ImageName  = $attribute_parent_sku.".jpg";

                $this->SetProduct($parent_product);

                $parent_product  = $this->FindProductBySku($attribute_parent_sku);
            }
            //Update parent?

            if ($parent_product->num_rows > 0)
            {
                // Does our Option exist? (Presumed that there is a description for each Product Option)
                $product_option_description = $this->FindProductOptionDescription($attribute_product, $parent_product->row['product_id']);

                // Create the Product Option and the Description should it not exist
                if ($product_option_description->num_rows == 0 && $attribute_parent_sku != "" && $attribute_parent_sku != null)
                {
                    $data = array
                        (
                            'product_id'        => $parent_product->row['product_id'],
                            'name'              => $attribute_parent_sku,
                            'language_id'       => ($this->config->get('config_language_id') != "") ? $this->config->get('config_language_id') : 1,
                            'sort_order'        => 0
                        );
                    $product_option_id = $this->AddProductOption($data);
                    $data = array
                        (
                            'product_id'        => $parent_product->row['product_id'],
                            'product_option_id' => $product_option_id,
                            'name'              => _ATTRIBUTE_OPTION_NAME_,
                            'language_id'       => ($this->config->get('config_language_id') != "") ? $this->config->get('config_language_id') : 1,
                            'sort_order'        => 0
                        );
                    $this->AddProductOptionDescription($data);
                }
                else
                {
                    $product_option_id = $product_option_description->row['product_option_id'];
                }

                // Does the Option Value exist?
                $data = array
                        (
                            'product_id'            => $parent_product->row['product_id'],
                            'product_option_id'     => $product_option_id,
                            'attribute_product_sku' => $attribute_product->Sku
                        );
                $product_option_value = $this->FindProductOptionValue($data);
                
                if($this->config->get('zynk_upload_product_quantities'))
                {
                    $attribute_product_quantity = $product->QtyInStock;
                }
                else
                {
                    // If we're adding a new product then just assume that the main product is in stock and set its quantity high as we
                    // cannot order any of the options should the parent have no stock.
                    $attribute_product_quantity = "9999";
                }

                if($this->config->get('zynk_upload_product_images'))
                {
                    //$image = $attribute_product->Image;
                    // Different format for BoundTree
                    //$image = "data/Product Images/".$this->GetAttributeValue($attribute_product, "Category 1")."/".str_replace(" ", "",$attribute_product->Sku).".jpg";
                    $image = "data/Product Images/".str_replace("&", "-", $this->GetAttributeValue($attribute_product, "Category 1"))."/".$attribute_product->Sku.".jpg";
                }
                else
                {
                    $image = "no_image.jpg";
                }

                $data = array
                        (
                            'product_option_id' => $product_option_id,
                            'product_id'        => $parent_product->row['product_id'],
                            'quantity'          => $attribute_product_quantity,
                            'subtract'          => 0,
                            'price'             => $attribute_product->SalePrice,
                            'prefix'            => "+",
                            'sort_order'        => ($this->GetAttributeValue($attribute_product, "Sort Order") != "") ? $this->GetAttributeValue($attribute_product, "Sort Order") : 0,
                            'weight'            => $attribute_product->UnitWeight,
                            'sku'               => $attribute_product->Sku,
                            'info'              => $attribute_product->Name,
                            'image'             => $image,
                            'language_id'       => ($this->config->get('config_language_id') != "") ? $this->config->get('config_language_id') : 1,
                            'name'              => $attribute_option_value
                        );

                $status = $this->getStatus($attribute_product);
        
                if ($product_option_value->num_rows == 0 && $status == 1)
                {
                    // Add Option Value if not found & product is marked as Active
                    $product_option_value_id = $this->AddProductOptionValue($data);
                    $this->AddProductOptionValueDescription($product_option_value_id, $data);

                    // Set the parent Active incase it is marked as Inactive
                    $this->UpdateProductStatus($parent_product->row['product_id'], 1);
                }
                elseif ($product_option_value->num_rows > 0 && $status == 1)
                {
                    // Edit the Option Value if it exists & product is marked as Active
                    $product_option_value_id = $this->EditProductOptionValue($data);
                    $this->EditProductOptionValueDescription($product_option_value_id, $data);

                    // Set the parent Active incase it is marked as Inactive
                    $this->UpdateProductStatus($parent_product->row['product_id']);
                }
                elseif ($product_option_value->num_rows > 0 && $status == 0)
                {
                    echo("Attempting to remove Product Option '". $attribute_product->Sku ."'.</br>");
                    // Delete the product option based on its Product Option Value ID
                    $this->DeleteProductOption($data, $product_option_value->row['product_option_value_id']);

                    // Set the parent Inactive
                    $this->UpdateProductStatus($parent_product->row['product_id']);
                }
                else
                {
                    // Do Nothing
                    echo("Not doing anything with Product Option '". $attribute_product->Sku ."'.</br>");
                }

            }
            else
            {
                echo("Product Option '". $attribute_product->Sku ."' does not have an existing parent product.</br>");
            }
        }
        else
        {
            echo("Product Option '". $attribute_product->Sku ."' does not have a sku code for a parent product.</br>");
        }
    }

    public function AddProductOption($data)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$data['product_id']. "'";

        if ($data['sort_order'])
        {
            $sql .= " AND " . DB_PREFIX . "sort_order = '" . (int)$data['sort_order']. "'";
        }

        $result = $this->db->query($sql);

        $last_insert_id = $this->db->getLastId();

        echo("Inserted Product Option for ". $data['name'] ." ID:[".$data['product_id']."]. [".$last_insert_id."]</br>");

        return $last_insert_id;
    }

    public function DeleteProductOption($data, $product_option_value_id)
    {
        //$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$data['product_id']. "'");
        //$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_description WHERE product_id = '" . (int)$data['product_id'] . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . (int)$product_option_value_id  . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value_description WHERE product_option_value_id = '" . (int)$product_option_value_id  . "'");

        echo("Deleted Product Option Value for '". $data['sku'] ."' ID:[".(int)$product_option_value_id ."]. </br>");
    }

    public function DeleteAllProductOptions($data)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$data['product_id']. "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_description WHERE product_id = '" . (int)$data['product_id'] . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$data['product_id'] . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value_description WHERE product_id = '" . (int)$data['product_id'] . "'");

        echo("Deleted all Product Options for '". $data['sku'] ."' ID:[".(int)$data['product_id']."</br>");
    }

    public function AddProductOptionDescription($data)
    {

        $result = $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_description SET product_option_id = '" . $data['product_option_id']. "', language_id = '" . (int)$data['language_id']. "', product_id = '" . $data['product_id']. "', name = '" . $this->db->escape($data['name']). "'");

        $last_insert_id = $this->db->getLastId();

        echo("Inserted Product Option Description for ". $data['name'] ." ID:[".$data['product_id']."]. [".$last_insert_id."]</br>");
 
        return $result;
    }
    
    public function AddProductOptionValue($data)
    {
        $sql = ("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$data['product_option_id']. "', product_id = '" . (int)$data['product_id']. "', quantity = '" . (int)$data['quantity']. "', sage_price = '" . $data['price']. "', prefix = '" . $data['prefix']. "', sort_order = '" . $data['sort_order']. "', sku = '" . $this->db->escape($data['sku']). "', info = '" . $this->db->escape($data['info']). "'");

        if ($data['image'])
        {
            $sql .= ", image = '" . $this->db->escape($data['image']). "'";
        }
        
        $result = $this->db->query($sql);
        $last_insert_id = $this->db->getLastId();

        echo("Inserted Product Option Value for ". $data['name'] ." ID:[".$data['product_id']."]. [".$last_insert_id."]</br>");

        return $last_insert_id;
    }
    public function AddProductOptionValueDescription($product_option_value_id, $data)
    {
        $result = $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value_description SET product_option_value_id = '" . (int)$product_option_value_id. "', language_id = '" . (int)$data['language_id']. "', product_id = '" . (int)$data['product_id']. "', name = '" . $this->db->escape($data['name']). "'");

        $last_insert_id = $this->db->getLastId();
        
        echo("Inserted Product Option Value Description for ". $data['name'] ." ID:[".$data['product_id']."]. [".$last_insert_id."]</br>");

        return $last_insert_id;
    }

    public function EditProductOptionValue($data)
    {
        if($this->config->get('zynk_upload_product_quantities'))
        {
            if (isset($data['quantity']))
            {
                $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = '" . (int)$data['quantity']. "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
            }
        }
        if (isset($data['subtract']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET subtract = '" . $data['subtract']. "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
        }
        if (isset($data['price']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET sage_price = '" . $data['price']. "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
            echo("UPDATE " . DB_PREFIX . "product_option_value SET sage_price = '" . $data['price']. "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'</br>");
        }
        if (isset($data['prefix']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET prefix = '" . $data['prefix']. "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
        }
        if (isset($data['sort_order']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET sort_order = '" . (int)$data['sort_order']. "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
        }
        if (isset($data['weight']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET weight = '" . $data['weight']. "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
        }
        if (isset($data['info']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET info = '" . $this->db->escape($data['info']). "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
        }
        if (isset($data['image']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET image = '" . $this->db->escape($data['image']). "' WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
        }

        // Return the Product Option Value ID
        $last_insert_id = $this->db->query("SELECT " . DB_PREFIX . "product_option_value_id FROM " . DB_PREFIX . " product_option_value WHERE sku = '" . $this->db->escape($data['sku']). "' AND product_id = '" . (int)$data['product_id']. "' AND product_option_id = '" . (int)$data['product_option_id']. "'");
        $last_insert_id = $last_insert_id->row['product_option_value_id'];

        echo("Updated Product Option Value for '". $data['name'] ."' ID:[".$data['product_id']."]. [".$last_insert_id."]</br>");

        return $last_insert_id;
    }
    public function EditProductOptionValueDescription($product_option_value_id, $data)
    {
        $result = $this->db->query("UPDATE " . DB_PREFIX . "product_option_value_description SET name = '" . $this->db->escape($data['name']). "' WHERE product_option_value_id = '".$product_option_value_id."' AND product_id = '".(int)$data['product_id']."'");

        $last_insert_id = $this->db->getLastId();

        echo("Updated Product Option Value Description for '". $data['name'] ."' ID:[".$data['product_id']."]. [".$last_insert_id."]</br>");

        return $last_insert_id;
    }

    public function FindProductOption($product)
    {
        try
        {
            $Name  = $product->Attributes->Get(_ATTRIBUTE_OPTION_NAME_)->Name;
            if ($Name != "<NONE>" && $Name != "&lt;NONE&gt;" && $Name != "")
            {
                $result = $this->db->query("SELECT * FROM  " . DB_PREFIX . "product_option_description WHERE name = '".$Name."';");
                return $result;
            }
        }
        catch (Exception $e) {}

        return false;
    }
    
    public function FindProductOptionBySku($sku)
    {
        try
        {
            $result = $this->db->query("SELECT * FROM  " . DB_PREFIX . "product_option_value WHERE sku = '".$sku."';");
            return $result;
        }
        catch (Exception $e) {}

        return false;
    }
    
    public function GetProductOptionParentId($id)
    {
        try
        {
            $result = $this->db->query("SELECT * FROM  " . DB_PREFIX . "product_option_value WHERE product_option_id = '".$id."';");
            return $result;
        }
        catch (Exception $e) {}

        return false;
    }

    public function FindProductOptionDescription($product, $product_id)
    {
        try
        {
            $Name  = $product->Attributes->Get(_ATTRIBUTE_OPTION_NAME_)->Name;

            if ($Name != "<NONE>" && $Name != "&lt;NONE&gt;" && $Name != "")
            {
                $sql = "SELECT * FROM  " . DB_PREFIX . "product_option_description WHERE name = '".$Name."'";

                if ($product_id)
                {
                    $sql .= " AND " . DB_PREFIX . "product_id = '" . (int)$product_id. "'";
                }

                $result = $this->db->query($sql);
                
                return $result;
            }
        }
        catch (Exception $e) {}

        return false;
    }

    public function FindProductOptionValue($data)
    {
        $result = $this->db->query("SELECT * FROM  " . DB_PREFIX . "product_option_value WHERE product_option_id = '".(int)$data['product_option_id']."'" . " AND " . DB_PREFIX . "product_id = '".(int)$data['product_id']."'". " AND " . DB_PREFIX . "sku = '".$data['attribute_product_sku']."'");
        
        if ($result->num_rows > 0)
        {
            echo("Found Product Option Value '". $data['attribute_product_sku'] ."' for Product ID:[".$data['product_id']."].</br>");
        }
        else
        {
            echo("Could not find Product Option Value '". $data['attribute_product_sku'] ."' for Product ID:[".$data['product_id']."].</br>");
        }
        
        return $result;
    }

    public function GetAttributeValue($product, $attribute)
    {
        try
        {
            $Name  = $product->Attributes->Get($attribute)->Name;
            $Value = $product->Attributes->Get($attribute)->Value;
            if ($Value != "<NONE>" && $Name != "&lt;NONE&gt;" && $Value != "")
            {
                return (string)$Value;
            }
        }
        catch (Exception $e) {}

        return null;
    }

    public function GetProductOptionValue($product_option_value_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . (int)$product_option_value_id . "'");

        return $query;
    }

    public function SetOptionedProductPricing()
    {
        echo("</br>");
        echo("</br>");
        echo("<b>Reset Pricing.</b></br>");

        // First order by price to get the cheapest option, then groupby to give us the ID's of the parents.
        $derive_query = "SELECT * 
                            FROM
                                (SELECT `product_option_id`, `price`, `sage_price`, `sku`, `product_id`
                                FROM `product_option_value`
                                ORDER BY `product_option_id`, `sage_price`) As product_option_value_derived_table
                            GROUP BY `product_option_id`";
//echo("derive_query: $derive_query </br>");
        $result = $this->db->query($derive_query);

        if ($result->num_rows > 0)
        {
            // Now update the price of every 'Parent' product with that of the lowest priced option.
            foreach ($result->rows as $row)
            {
                echo("product_option_id: " . $row['product_option_id'] . " - child sku: " . $row['sku'] . " - price: " . $row['price'] . " - sage price: " . $row['sage_price'] . "</br>" );
                // On subsequent upload after a product option price is adjusted according to the base parent price the lowest one will have a price of 0.
                // We DON'T want to use this...
                if ($row['sage_price'] > 0)
                {
                    $this->UpdateProductPrice($row['product_id'], $row['sage_price']);
                }
            }

            $query = "SELECT `product_option_value`.*, `product`.`product_id` AS 'ParentID', `product`.`price` AS 'ParentPrice', `product`.`sku` AS 'ParentSku'
                        FROM `product_option_value`
                        INNER JOIN `product` on `product`.`product_id` = `product_option_value`.`product_id`";
            $result = $this->db->query($query);
//echo("query: $query </br>");
            // Now update the price of every 'Child' product by subtracting the price of it's parent.
            foreach ($result->rows as $row)
            {
                $parent_price   = $row['ParentPrice'];
                $price          = $row['sage_price'] - $parent_price;
                //echo("product_option_value_id: " . $row['product_option_value_id'] . " - sku: ".$row['price']." - origional price: " . $row['price'] . " - parent sku: " . $row['ParentSku'] . " - parent price: " . $parent_price . " - new price: " . $price . "</br>" );
                $this->UpdateProductOptionPrice($row['product_option_value_id'], $price);
            }
        }
    }

    public function UpdateProductPrice($product_id, $price)
    {
        $query  = "UPDATE ".DB_PREFIX."product SET `date_modified` = NOW(), `price` = '$price' WHERE `product_id` = '$product_id';";
        $result = $this->db->query($query);

        $p = $this->GetProductById($product_id);

        if ($p->num_rows > 0)
        {
            $sku = $p->row['sku'];
        }
        else
        {
            $sku = "NOT_FOUND";
        }

        echo("Updated price for Sku:[".$sku ."] to: " . $price . ". </br>");
    }
    public function UpdateProductStatus($product_id)
    {
        // Does product have any options assigned to it, if it does, set as ACTIVE, otherwise set as INACTIVE
        $query  = "SELECT COUNT(*) AS Count FROM `product` 
                        INNER JOIN `product_option` ON `product`.`product_id` = `product_option`.`product_id`
                        WHERE `product`.`product_id` = '$product_id'; ";

        $result = $this->db->query($query);

        if ($result->row['Count'] == 0)
        {
            $status     = 0;
            $statusText = "Inactive";
        }
        else
        {
            $status     = 1;
            $statusText = "Active";
        }
        
        $query  = "UPDATE ".DB_PREFIX."product SET `date_modified` = NOW(), `status` = '$status' WHERE `product_id` = '$product_id';";
        $result = $this->db->query($query);

        $p = $this->GetProductById($product_id);
        
        if ($p->num_rows > 0)
        {
            $sku = $p->row['sku'];
        }
        else
        {
            $sku = "NOT_FOUND";
        }

        echo("Updated status for Sku:[".$sku."] to: $status ($statusText) </br>");
    }

    public function UpdateProductOptionPrice($product_option_value_id, $price)
    {
        $query  = "UPDATE ".DB_PREFIX."product_option_value SET `price` = '$price' WHERE `product_option_value_id` = '$product_option_value_id';";
        $result = $this->db->query($query);
 
        $p = $this->GetProductOptionValue($product_option_value_id);

        if ($p->num_rows > 0)
        {
            $sku = $p->row['sku'];
        }
        else
        {
            $sku = "NOT_FOUND";
        }

        echo("Updated price for Option Sku:[".$sku."] to: " . $price . ". </br>");
    }
    public function HasProductOptions($product_id)
    {
        $product_options = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order");
        
        if ($product_options->num_rows == 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }
	
    public function CleanupProductOptions()
    {
        // foreach product option in the database, check to see if anything references it
        // within `product_option_value`
        //  `product_option_value_description`
        // if there are no matches then delete the   `product_option` and also `product_option_description` matched on product_option_id
        // then set the parent product to unpublished
        //SELECT * FROM product_option where product_option_id NOT IN ( select product_option_id from product_option_value);
        echo("</br>");
        echo("</br>");
        echo("<b>Cleanup Product Options.</b></br>");

        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option WHERE product_option_id NOT IN (SELECT product_option_id FROM " . DB_PREFIX . "product_option_value)");
        foreach ($result->rows as $row)
        {
            $product_option_id  = $row['product_option_id'];
            $product_id         = $row['product_id'];
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_option_id='".$product_option_id."';");
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_description WHERE product_option_id='".$product_option_id."';");
            $this->UpdateProductStatus($product_id);
            
            $p = $this->model_catalog_product->getProduct($product_id);

            echo("Removed all Product options relating to :[".$p['sku']."]. </br>");
        }
    }
    
    public function getStatus($product)
    {
        // Sage 200 doesn't export the publish flag
        if( isset($product->Status) && ($product->Status != "") )
        {
            $status = $product->Status;   
        }
        else
        {
            $status = ($product->Publish == "true") ? 1 : 0;  // Active (1) / Inactive (0)
        }
        
        return $status;
    }
	//set priority to 99 on all discounts uploaded by us (so when we are removing a product from specials we can identify from manually entered specials)
	public function InsertQtyBreak($productId, $customerGroupId, $productQtyBreak, $productSalePrice)
	{
		$discountedPrice = (double)$productSalePrice - ((double)$productQtyBreak->DiscountPercentage * (double)$productSalePrice / 100.00);
		$result = $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id =$productId, customer_group_id = $customerGroupId, quantity = $productQtyBreak->Quantity, priority = 99, price = $discountedPrice");
	}
	//set priority to 99 on all specials uploaded by us (so when we are removing a product from specials we can identify from manually entered specials)
	public function InsertProductSpecial($productId, $customerGroupId, $productSalePrice)
	{
		$result = $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id =$productId, customer_group_id = $customerGroupId, price = $productSalePrice, priority = 99");
		
	}
}