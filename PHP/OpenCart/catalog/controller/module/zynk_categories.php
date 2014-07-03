<?php 

class ZynkCategories extends Controller 
{
    public function CreateAndAssignCategory($sage_product, $product_id)
    {
    
        $Custom1 = mysql_real_escape_string(htmlentities($sage_product->Custom1, ENT_QUOTES));
        $Custom2 = mysql_real_escape_string(htmlentities($sage_product->Custom2, ENT_QUOTES));
        $Custom3 = mysql_real_escape_string(htmlentities($sage_product->Custom3, ENT_QUOTES));
    
        if (isset($Custom1) && $Custom1 != "")
        {
            $c1Id = $c2Id = $c3Id = 0;
            $cq = "SELECT c.category_id FROM category_description cd INNER JOIN category c ON cd.category_id = c.category_id ";

            // Cat 1 (Root: No parent)
            $cat1 = $this->db->query($cq . "WHERE name = '".$Custom1."' AND c.parent_id = 0;");

            echo("| Category 1 Start |");
            if ($cat1->num_rows>0)
            {
                echo ("| Found Category 1 |");
                $c1Id = $cat1->row["category_id"];
            }
            else
            {
                echo ("| Creating Category 1 |");
                $c1Id = $this->CreateCategory($Custom1, 0);
            }

            if ($c1Id > 0 && isset($Custom2) && $Custom2 != "")
            {
                echo("| Category 2 Start |");

                $cat2 = $this->db->query($cq . "WHERE name = '".$Custom2."' AND c.parent_id = '".$c1Id."';");

                if ($cat2->num_rows>0)
                {
                    echo ("Found Category 2 |");
                    $c2Id = $cat2->row["category_id"];
                }
                else
                {
                    echo ("| Creating Category 2 |");
                    $c2Id = $this->CreateCategory($Custom2, $c1Id);
                }
            }

            if ($c1Id > 0 && $c2Id > 0 && isset($Custom3) && $Custom3 != "")
            {
                echo("| Category 3 Start |");

                $cat3 = $this->db->query($cq . "WHERE name = '".$Custom3."' AND c.parent_id = '".$c2Id."';");

                if ($cat3->num_rows>0)
                {
                    echo ("Found Category 3 |");
                    $c3Id = $cat3->row["category_id"];
                }
                else
                {
                    echo ("| Creating Category 3 |");
                    $c3Id = $this->CreateCategory($Custom3, $c2Id);
                }
            }

            // Only assign to final category
            $categoryId = $c3Id > 0 ? $c3Id : $c2Id; // c3 then c2
            $categoryId = $categoryId == 0 ? $c1Id : $categoryId; // then c1

            //$this->SetCategory($product_id, $categoryId);

            // Just assign the products to each category
            /*
            if ($c1Id > 0)
            {
                $this->SetCategory($product_id, $c1Id);
            }
            if ($c2Id > 0)
            {
                $this->SetCategory($product_id, $c2Id);
            }
            if ($c3Id > 0)
            {
                $this->SetCategory($product_id, $c3Id);
            }
            */
        }
    }

    public function CreateAndAssignDefaultCategory($sage_product, $product_id)
    {
        try
        {
            echo("Beginning Category Assigning - ");
            $category = $this->config->get('zynk_default_category');
		    if ($category > 0)
		    {
		        $this->SetCategory($product_id, $category);
		    }

            //$Category_Name = $this->config->get('zynk_default_category');
            
            // // Category
            // if ($Category_Name != "")
            // {
            //     $cId = 0;
            //     $sql = "SELECT c.category_id FROM category_description cd INNER JOIN category c ON cd.category_id = c.category_id ";
            // 
            //     $Category = $this->db->query($sql . "WHERE name = '".$Category_Name."' AND c.parent_id = 0;");
            // 
            //     if ($Category->num_rows > 0)
            //     {
            //         echo ("<i>Found Category '$Category_Name'</i> - ");
            //         $cId = $Category->row["category_id"];
            //     }
            //     else
            //     {
            //         echo ("<i>Creating Category '$Category_Name'</i> - ");
            //         $cId = $this->CreateCategory($Category_Name, 0);
            //     }
            // 
            //     // Assign the product to category
            //     if ($cId > 0)
            //     {
            //         $this->SetCategory($product_id, $cId);
            //     }
            // }
        }
        catch (Exception $e) {}
    }

    public function CreateAndAssignCategorySearchValues($sage_product, $product_id)
    {
        try
        {
            echo("Beginning Category Assigning: ");
            $Category1_Name  = $sage_product->Attributes->Get('Category 1')->Name;
            $Category1_Value = $sage_product->Attributes->Get('Category 1')->Value;
            $Category1_Value = mysql_real_escape_string(htmlentities($Category1_Value, ENT_QUOTES));
            // Category 1
            if ($Category1_Value != "<NONE>" && $Category1_Value != "&lt;NONE&gt;" && $Category1_Value != "")
            {
                //echo $Category1_Name.":".$Category1_Value."</br>";
                $c1Id = $c2Id = $c3Id = 0;
                $cq = "SELECT c.category_id FROM category_description cd INNER JOIN category c ON cd.category_id = c.category_id ";

                // Cat 1 (Root: No parent)
                $cat1 = $this->db->query($cq . "WHERE name = '".$Category1_Value."' AND c.parent_id = 0;");

                echo("Category 1 Start > ");

                if ($cat1->num_rows > 0)
                {
                    echo ("Found Category 1 ");
                    $c1Id = $cat1->row["category_id"];
                }
                else
                {
                    echo ("Creating Category 1 ");
                    $c1Id = $this->CreateCategory($Category1_Value, 0);
                }

                // Category 2
                try
                {
                    $Category2_Name  = $sage_product->Attributes->Get('Category 2')->Name;
                    $Category2_Value = $sage_product->Attributes->Get('Category 2')->Value;
                    $Category2_Value = mysql_real_escape_string(htmlentities($Category2_Value, ENT_QUOTES));
                    if ($c1Id > 0 && $Category2_Value != "<NONE>" && $Category2_Value != "&lt;NONE&gt;" && $Category2_Value != "")
                    {
                        echo("| Category 2 Start > ");

                        $cat2 = $this->db->query($cq . "WHERE name = '".$Category2_Value."' AND c.parent_id = '".$c1Id."';");

                        if ($cat2->num_rows > 0)
                        {
                            echo ("Found Category 2 ");
                            $c2Id = $cat2->row["category_id"];
                        }
                        else
                        {
                            echo ("Creating Category 2 ");
                            $c2Id = $this->CreateCategory($Category2_Value, $c1Id);
                        }
                    }
                }
                catch (Exception $e) {}

                // Category 3
                try
                {
                    $Category3_Name  = $sage_product->Attributes->Get('Category 3')->Name;
                    $Category3_Value = $sage_product->Attributes->Get('Category 3')->Value;
                    $Category3_Value = mysql_real_escape_string(htmlentities($Category3_Value, ENT_QUOTES));
                    if ($c1Id > 0 && $c2Id > 0 && $Category3_Value != "<NONE>" && $Category3_Value != "&lt;NONE&gt;" && $Category3_Value != "")
                    {
                        echo("| Category 3 Start > ");

                        $cat3 = $this->db->query($cq . "WHERE name = '".$Category3_Value."' AND c.parent_id = '".$c2Id."';");

                        if ($cat3->num_rows > 0)
                        {
                            echo ("Found Category 3 ");
                            $c3Id = $cat3->row["category_id"];
                        }
                        else
                        {
                            echo ("Creating Category 3 ");
                            $c3Id = $this->CreateCategory($Category3_Value, $c2Id);
                        }
                    }
                }
                catch (Exception $e) {}

                // Only assign to final category
                //$categoryId = $c3Id > 0 ? $c3Id : $c2Id; // c3 then c2
                //$categoryId = $categoryId == 0 ? $c1Id : $categoryId; // then c1

                //$this->SetCategory($product_id, $categoryId);

                // Just assign the products to each category (client request)
                if ($c1Id > 0)
                {
                    $this->SetCategory($product_id, $c1Id);
                }
                if ($c2Id > 0)
                {
                    $this->SetCategory($product_id, $c2Id);
                }
                if ($c3Id > 0)
                {
                    $this->SetCategory($product_id, $c3Id);
                }
            }
        }
        catch (Exception $e) {}
    }
    
    public function SetCategory($product_id, $category_id)
    {
		$sql = "SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id. "'";
		// if we are not setting categories via the scripts then all we have to do is check that the 
		// product record is assigned to some category
        if($this->config->get('zynk_upload_categories'))
        {
			$sql .= " AND category_id = " . (int)$category_id . ";";
		}
		$found = $this->db->query($sql);
        if ($found->num_rows == 0)
        {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category (category_id, product_id) VALUES ('".(int)$category_id."','" . (int)$product_id . "');");
            echo("Inserted Product into Category. </br>");
        }
        else
        {
            //$this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET category_id = '" . (int)$category_id . "' WHERE product_id = '" . (int)$product_id . "';");
            //echo("Updated Category for ProductID:[".$product_id."]. </br>");
            //echo("Product already assigned to category. </br>");
        }

        return $this->db->getLastId();
    }

    public function CreateCategory($name, $parentid)
    {
        $data['sort_order']         = ($this->config->get('zynk_upload_categories')) ? 0 : -1; //-1: Hide from listing...
        $data['image']              = '';
        //$data['category_store']     = array(7);
        $data['category_store']     = ($this->config->get('config_store_id') != "") ? $this->config->get('config_store_id') : 0;
        $data['keyword']            = $name;
        $data['meta_description']   = $name;
        $data['language_id']        = ($this->config->get('config_language_id') != "") ? $this->config->get('config_language_id') : 1;
        $data['parent_id']          = $parentid;
        $data['status']             = 1;//($this->config->get('zynk_upload_categories')) ? 1 : 0;

        $id = $this->db->query("INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW(), date_added = NOW(), status = '" . (int)$data['status'] . "'");

        $category_id = $this->db->getLastId();
        
        if (isset($data['image']))
        {
            $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape($data['image']) . "' WHERE category_id = '" . (int)$category_id . "'");
        }
        
        $data['category_description'] = array(1, array('name' => $name, 'meta_description'  => $name, 'description' => $name));

        $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$data['language_id'] . "', name = '" . $this->db->escape($name) . "', meta_description = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($name) . "'");

        if (isset($data['category_store']))
        {
            //foreach ($data['category_store'] as $store_id)
            //{
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$data['category_store'] . "'");
            //}
        }
        
        if ($data['keyword'])
        {
            $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
        }
        
        return $category_id;
    }
}
