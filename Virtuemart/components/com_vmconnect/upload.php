<?php 
require_once(dirname(__FILE__) . "/common.php") ;

//~ if (!licensecheck($config->license_key)) 
	//~ bye ('Invalid License - Please contact Ibex Internet, support@ibexinternet.co.uk', SAGE_UPLOAD) ;

$xmlInput = file_get_contents('php://input') ;
$xmlString = preg_replace('/<Company.*?>/', '<Company>', $xmlInput, 1);
$report = "
VM-Connect Upload Report
---------------------------------------

Running in ".($config->testmode ? 'Test' : 'LIVE')." Mode
Site is $mosConfig_live_site\n" ;

if (!empty($xmlString)) {
	$xmlData = xml2array($xmlString);			   
	if (!$xmlData) bye("Couldn't read XML data", SAGE_UPLOAD) ;
	
	$prodGroups = build_categories(&$xmlData['Company'][0]['ProductGroups'][0]['ProductGroup'], $report) ;
	$taxRates = build_taxrates(&$xmlData['Company'][0]['TaxRates'][0]['TaxRate'], $report) ;
	$prodNodes =& $xmlData['Company'][0]['Products'][0]['Product'];
    if ($prodNodes)
    {
        foreach ($prodNodes as $node) {
            $prodSku				= htmlspecialchars($node['Sku'], ENT_QUOTES);	
            $prodGroupCode			= htmlspecialchars($node['GroupCode'],	ENT_QUOTES);
            if (isset($prodGroups[$prodGroupCode])) {
                $sql = "SELECT product_id FROM #__vm_product WHERE product_sku = '$prodSku'";
                $database->setQuery($sql) ;
                $prodId = $database->loadResult() ;
                if ($database->getErrorNum())
                    bye("Couldn't get products from database : " . mysql_error(), SAGE_UPLOAD);
                if ($prodId) {
                    if (!update_product($prodId, $node, $prodGroups, $taxRates, $report))
                        writelog("failed to update $prodSku", SAGE_UPLOAD) ;
                } else {
                    if (isset($config->insert_products) && !$config->insert_products)
                        continue ;
                    if (!insert_product($node, $prodGroups, $taxRates, $report))
                        writelog("failed to insert product $prodSku", SAGE_UPLOAD) ;
                }
            } else {
                writelog("failed to match category for product $prodSku", SAGE_UPLOAD) ;
            }
        }
    }
}

write_report($config, $report, SAGE_UPLOAD) ;

function build_categories(&$prodGroupNodes, &$report) {
	global $database, $config ;
	$prodGroups = array(); 
    if ($prodGroupNodes )
    {
        foreach ($prodGroupNodes as $node) {
            $groupName			= htmlspecialchars($node['Name'],  ENT_QUOTES);
            $groupReferenceId	= htmlspecialchars($node['Reference'],	ENT_QUOTES);
                    
            if($groupName != '') {
                $sql = "SELECT category_id FROM #__vm_category WHERE category_name = '$groupName'";
                $database->setQuery($sql) ;
                $result = $database->loadResult() ;
                
                //build lookup table matching sage category ids to virtuemart category ids
                if ($result) {
                    $prodGroups[$groupReferenceId] = $result;
                } elseif (isset($config->insert_categories) && !$config->insert_categories) {
                    continue ;
                } elseif ($config->testmode) {
                    if (!isset($lastCat)) {
                        $database->setQuery("SELECT MAX(category_id) FROM #__vm_category") ;
                        $lastCat = $database->loadResult() + 1;
                    }
                    $prodGroups[$groupReferenceId] = $lastCat++ ;
                    $report .= "\nTESTMODE: Create new Category: $groupName" ;
                } else {
                    $sql = "INSERT INTO #__vm_category (category_name, vendor_id, category_publish, cdate, mdate, category_flypage, list_order) "
                            ."VALUES ('$groupName', '".$config->vendor_id."', 'N', '".time()."', '".time()."', '', '0')";
                    $database->setQuery($sql) ;
                    $database->query() ;
                    if ($database->getErrorNum()) {
                        bye("Couldn't insert new category record into database : " . $database->getErrorMsg(), SAGE_UPLOAD);
                    } else {
                        $newid = $database->insertid() ;
                        $database->setQuery( "INSERT INTO #__vm_category_xref (category_parent_id, category_child_id) VALUES ('0', '$newid')" ) ;
                        $database->query();
                        if ($database->getErrorNum()) {
                            $database->setQuery("DELETE FROM #__vm_category WHERE category_id='$newid'") ;
                            $database->query() ;
                            bye ("Created category, $groupName but could not link to parent, deleted newly created category", SAGE_UPLOAD) ;
                        } else {
                            $prodGroups[$groupReferenceId] = $newid ;
                            if ($config->report) $report .= "\nCreated new Category: $groupName" ;
                        }
                    }
                }
            }
        }
    }
	
	return $prodGroups ;
}

function insert_product (&$node, &$prodGroups, &$taxRates, &$report) {
	global $database, $config ;
	$prodSku				= htmlspecialchars($node['Sku'], ENT_QUOTES);
	$prodName				= mysql_real_escape_string($node['Name']) ; 
	$prodDescription		= htmlspecialchars($node['Description'], ENT_QUOTES);
	$prodLongDescription	= nl2br($node['LongDescription']); 
	$prodSalePrice			= htmlspecialchars($node['SalePrice'], ENT_QUOTES);
	$prodUnitWeight			= htmlspecialchars($node['UnitWeight'], ENT_QUOTES); 
	$prodQtyInStock			= htmlspecialchars($node['QtyInStock'], ENT_QUOTES);
	$prodGroupCode			= htmlspecialchars($node['GroupCode'],	ENT_QUOTES);
	$prodTaxCode			= htmlspecialchars($node['TaxCode'], ENT_QUOTES);
	$prodTaxCode			= (isset($taxRates[$prodTaxCode]) ? $taxRates[$prodTaxCode] : $config->tax_rate_id) ;
	$attributes					= trim($node['Custom1']) ;
	$attributes 				= (!empty($attributes) && validate_attribute($attributes)) ? $attributes : '' ;
		
	if (isset($config->insert_products_unpublished) && $config->insert_products_unpublished)
		$prodPublish = 'N' ;
	else
		$prodPublish = (htmlspecialchars($node['Publish'], ENT_QUOTES) == 'false') ? 'N' : 'Y' ;
	
	if ($config->testmode) {
		$report .= "\nTESTMODE: Insert new product with sku: $prodSku" ;
		return true ;
	}
	
	$sql = "INSERT INTO #__vm_product (vendor_id, product_sku, product_s_desc, product_desc, product_thumb_image, product_full_image, product_publish, product_weight, product_url, product_in_stock, product_special, product_discount_id, cdate, mdate, product_name, attribute, product_tax_id, product_unit, product_packaging) "
			. "VALUES ('".$config->vendor_id."', '$prodSku', ".$database->Quote($prodDescription).", ".$database->Quote($prodLongDescription).", '', '', '$prodPublish', '$prodUnitWeight', '', '$prodQtyInStock', 'N', '0', '".time()."', '".time()."', '$prodName', '$attributes', '$prodTaxCode', '', '0')" ;

	$database->setQuery($sql) ;
	$database->query() ;
	if ($database->getErrorNum()) {
		return false ;
	} else {
		$newProdId = $database->insertid() ;
		
		$sql = "INSERT INTO #__vm_product_price (product_id, product_price, product_currency, cdate, mdate, shopper_group_id) "
				. "VALUES ('$newProdId', '$prodSalePrice', '".$config->currency_code."', '".time()."', '".time()."', '".$config->shopper_group_id."')" ;
		$database->setQuery($sql);
		$database->query();
		if ($database->getErrorNum()) {
			$database->setQuery("DELETE FROM #__vm_product WHERE product_id='$newProdId'") ;
			$database->query() ;
			return false ;
		} else {
			$sql = "INSERT INTO #__vm_product_category_xref (category_id, product_id) VALUES ('".$prodGroups[$prodGroupCode]."', '$newProdId')" ;
			$database->setQuery($sql) ;
			$database->query() ;
			if ($database->getErrorNum()) {
				$database->setQuery("DELETE FROM #__product_price WHERE product_id = '$newProdId'") ;
				$database->query() ;
				$database->setQuery("DELETE FROM #__vm_product WHERE product_id='$newProdId'") ;
				$database->query() ;
				return false ;
			}
		}
	}
	
	return true ;
}

function update_product ($product_id, &$node, &$prodGroups, &$taxRates, &$report) {
	global $database, $config ;
	$prodQtyInStock			= htmlspecialchars($node['QtyInStock'], ENT_QUOTES);
	$prodDescription		= htmlspecialchars($node['Description'], ENT_QUOTES);
	$prodLongDescription	= nl2br($node['LongDescription']);
	$prodName				= mysql_real_escape_string($node['Name']) ;
	$prodGroupCode			= htmlspecialchars($node['GroupCode'],	ENT_QUOTES);
	$prodUnitWeight			= htmlspecialchars($node['UnitWeight'], ENT_QUOTES);
	$prodSalePrice			= htmlspecialchars($node['SalePrice'], ENT_QUOTES);  
	$attributes				= trim($node['Custom1']) ;
	$attributes 			= (!empty($attributes) && validate_attribute($attributes)) ? $attributes : '' ;
	$prodPublish			= (htmlspecialchars($node['Publish'], ENT_QUOTES) == 'false') ? 'N' : 'Y' ;

	if ($config->testmode) {
		$report .= "\nTESTMODE: Update product with sku: ".$node['Sku'] ;
		return true ;
	}
	
	$sql = array() ;
	if ($config->update_stock) $sql[] = "product_in_stock='$prodQtyInStock'" ;
	if ($config->update_desc) $sql[] = "product_s_desc=".$database->Quote($prodDescription) ;
	if ($config->update_desc) $sql[] = "product_desc=".$database->Quote($prodLongDescription) ;
	if ($config->update_name) $sql[] = "product_name='$prodName'" ;
	if ($config->update_weight) $sql[] = "product_weight='$prodUnitWeight'" ;
	if ($config->update_attributes) $sql[] = "attribute='$attributes'" ;
	//if ($config->update_published) $sql[] = "product_publish='$prodPublish'" ;
	$sql[] = "product_publish='$prodPublish'" ;
 	if (!count($sql)) return true ;

	$database->setQuery("UPDATE #__vm_product SET " . implode(', ', $sql) . " WHERE product_id='$product_id'") ;
echo("UPDATE #__vm_product SET " . implode(', ', $sql) . " WHERE product_id='$product_id' .\n" );
	$database->query() ;
	if ($database->getErrorNum())
		return false ;

	if ($config->update_price) {
		$database->setQuery("UPDATE #__vm_product_price SET product_price='$prodSalePrice' WHERE product_id='$product_id' AND product_currency='$config->currency_code' AND shopper_group_id='$config->shopper_group_id' LIMIT 1") ;
		if (!$database->query())
			return false ;
	}
	
	if ($config->update_category) {
		$database->setQuery("SELECT category_id FROM #__vm_product_category_xref WHERE product_id = '$product_id'") ;
		$cats = $database->loadResultArray() ;
		if (!in_array($prodGroups[$prodGroupCode], $cats)) {
			$database->setQuery("DELETE FROM #__vm_product_category_xref WHERE product_id = '$product_id'") ;
			$database->query() ;
			$database->setQuery("INSERT INTO #__vm_product_category_xref SET category_id = '".$prodGroups[$prodGroupCode]."', product_id = '$product_id'") ;
			$database->query() ;
		}
	}

	return true ;
}

function build_taxrates(&$taxRateNodes, &$report) {
	global $database, $config ;
	$taxRates = array();
	return $taxRates ; 
	foreach ($taxRateNodes as $node) {
		$taxRate			= round(((float)htmlspecialchars($node['Name'],  ENT_QUOTES)) / 1000, 4) ;
		$sageTaxId	= htmlspecialchars($node['Reference'],	ENT_QUOTES);
				
		if($taxRate != '') {
			$database->setQuery("SELECT tax_rate_id FROM #__vm_tax_rate WHERE tax_rate = '$taxRate'") ;
			$result = $database->loadResult() ;
			
			//build lookup table matching sage category ids to virtuemart category ids
			if ($result) {
				$taxRates[$sageTaxId] = $result;
			} elseif ($config->testmode) {
				if (!isset($lastCat)) {
					$database->setQuery("SELECT MAX(tax_rate_id) FROM #__vm_tax_rate") ;
					$lastCat = $database->loadResult() + 1;
				}
				$prodGroups[$groupReferenceId] = $lastCat++ ;
				$report .= "\nTESTMODE: Create new Tax Rate: $taxRate" ;
			} else {
				$sql = "INSERT INTO #__vm_tax_rate (vendor_id, tax_state, tax_country, mdate, tax_rate) "
						."VALUES ('$config->vendor_id', '$config->default_tax_state', '$config->default_tax_country', '".time()."', '$taxRate')";
				$database->setQuery($sql) ;
				$database->query() ;
				if ($database->getErrorNum()) {
					bye("Couldn't insert new tax rate into database : " . $database->getErrorMsg(), SAGE_UPLOAD);
				} else {
					$newid = $database->insertid() ;
					$taxRates[$sageTaxId] = $newid ;
					if ($config->report) $report .= "\nCreated new Tax Rate: $taxRate" ;
				}
			}
		}
	}
	
	return $taxRates ;
}

function validate_attribute ($attribute) {
	return preg_match('/^\w+(,\w+(\[[\+-=]\d+(.\d+)?\])?)+(;\w+(,\w+(\[[\+-=]\d+(.\d+)?\])?)+)*$/', $attribute) ;
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