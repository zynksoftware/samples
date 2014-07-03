<?php echo $header; ?>
<div id="content">
<div class="breadcrumb">
  <?php foreach ($breadcrumbs as $breadcrumb) { ?>
  <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
  <?php } ?>
</div>
<?php if ($error_warning) { ?>
<div class="warning"><?php echo $error_warning; ?></div>
<?php } ?>

<div class="box">
  <div class="heading">
    <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
    <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><?php echo $button_cancel; ?></a></div>
  </div>
  <div class="content">
    <!-- <form action="<?php echo $action;?>&amp;token=<?php echo $_GET['token']; ?>" method="post" enctype="multipart/form-data" id="form"> -->
    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
      <table class="form">

        <!-- do you want to enable the module? -->
        <tr>
          <td><b><?php echo $entry_status; ?></b></td>
          <td><select name="zynk_status">
              <?php if ($zynk_status) { ?>
              <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
              <option value="0"><?php echo $text_disabled; ?></option>
              <?php } else { ?>
              <option value="1"><?php echo $text_enabled; ?></option>
              <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Enable/Disable the Zynk Integration module</i></td>
        </tr>

        <!-- are we downloading orders . . . -->
        <tr>
          <td><b><?php echo $entry_download_orders; ?></b></td>
          <td>
            <select name="zynk_download_orders">
              <?php if ($zynk_download_orders) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Download orders into Sage as the type defined below.</i>
          </td>
        </tr>

        <!-- what type of information to download -->
        <tr>
            <td><b><?php echo $entry_download_type; ?></b></td>
            <td>
                <select name="zynk_download_type">
                      <?php if ($zynk_download_type == 'sales_order') { ?>
                      <option value="sales_order" selected="selected"><?php echo $text_sales_order; ?></option>
                      <?php } else { ?>
                      <option value="sales_order"><?php echo $text_sales_order; ?></option>
                      <?php } ?>
                      <?php if ($zynk_download_type == 'invoice') { ?>
                      <option value="invoice" selected="selected"><?php echo $text_invoice; ?></option>
                      <?php } else { ?>
                      <option value="invoice"><?php echo $text_invoice; ?></option>
                      <?php } ?>
                </select><i>&nbsp;&nbsp;&nbsp;Bring orders down into Sage in this format.</i>
          </td>
        </tr>

        <!-- when should we download the orders -->
        <tr>
            <td><b><?php echo $entry_download_stage; ?></b></td>
            <td>
                <select name="zynk_download_stage">
                <?php
                    foreach ($order_status as $status)
                    {
                        $selected = '';
                        if ($zynk_download_stage == $status['order_status_id'])
                        {
                            $selected = 'selected="selected"';
                        }

                        echo('<option value="'.$status['order_status_id'].'" '.$selected.'>'.$status['name'].'</option>');
                    }
                ?>
                </select><i>&nbsp;&nbsp;&nbsp;Bring only those orders down into Sage that are at the specified Stage.</i>
          </td>
        </tr>

        <!-- and what are we moving it to -->
        <tr>
            <td><b><?php echo $entry_notify_stage; ?></b></td>
            <td>
                <select name="zynk_notify_stage">
                <?php
                    foreach ($order_status as $status)
                    {
                        $selected = '';
                        if ($zynk_notify_stage == $status['order_status_id'])
                        {
                            $selected = 'selected="selected"';
                        }

                        echo('<option value="'.$status['order_status_id'].'" '.$selected.'>'.$status['name'].'</option>');
                    }
                ?>
                </select><i>&nbsp;&nbsp;&nbsp;The stage that an order is set to once it has been successfully posted into Sage.</i>
          </td>
        </tr>
        
        <!-- are we downloading products . . . -->
        <tr>
          <td><b><?php echo $entry_download_products; ?></b></td>
          <td>
            <select name="zynk_download_products">
              <?php if ($zynk_download_products) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Download products into Sage from the website.</i>
          </td>
        </tr>

        <!-- are we downloading customers too . . . -->
        <tr>
          <td><b><?php echo $entry_download_customers; ?></b></td>
          <td><select name="zynk_download_customers">
              <?php if ($zynk_download_customers) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Download customers to as individual accounts into Sage, otherwise they will all be allocated a single Account Reference, defined below.</i>
        </tr>

        <!-- are we uploading customers too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_customers; ?></b></td>
          <td><select name="zynk_upload_customers">
              <?php if ($zynk_upload_customers) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Customers from Sage to the website.</i>
        </tr>

        <!-- are we uploading products too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_products; ?></b></td>
          <td><select name="zynk_upload_products">
              <?php if ($zynk_upload_products) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1" >Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Products from Sage to the website</i>.</td>
        </tr>

        <!-- are we uploading product images too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_product_images; ?></b></td>
          <td><select name="zynk_upload_product_images">
              <?php if ($zynk_upload_product_images) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload a single image per product from Sage to the website</i>.</td>
        </tr>

        <!-- are we uploading product descriptions too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_product_descriptions; ?></b></td>
          <td><select name="zynk_upload_product_descriptions">
              <?php if ($zynk_upload_product_descriptions) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Product Descriptions from Sage to the website.&nbsp;&nbsp;&nbsp;</td>
        </tr>

        <!-- are we uploading product quantities too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_product_quantities; ?></b></td>
          <td><select name="zynk_upload_product_quantities">
              <?php if ($zynk_upload_product_quantities) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Stock Levels from Sage to the website.</td>
        </tr>
		
		<!-- are we uploading product price breaks too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_product_price_breaks; ?></b></td>
          <td><select name="zynk_upload_product_price_breaks">
              <?php if ($zynk_upload_product_price_breaks) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Stock Quantity Breaks from Sage to the website.</td>
        </tr>
		
		<!-- are we uploading setting products as special offers based on flag in Sage too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_product_special_offer; ?></b></td>
          <td><select name="zynk_upload_product_special_offer">
              <?php if ($zynk_upload_product_special_offer) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Special Offer flag from Sage to the website (set the product up as a special offer with standard sale price).</td>
        </tr>

        <!-- are we uploading categories too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_categories; ?></b></td>
          <td><select name="zynk_upload_categories">
              <?php if ($zynk_upload_categories) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Categories from Sage to the website.</i>
        </tr>

        <!-- are we uploading pricelists too . . . -->
        <tr>
          <td><b><?php echo $entry_upload_pricelists; ?></b></td>
          <td><select name="zynk_upload_pricelists">
              <?php if ($zynk_upload_pricelists) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Upload Pricelists from Sage to the website.</i>
        </tr>

        <!-- and what about payments . . . -->
        <tr>
          <td><b><?php echo $entry_download_payments; ?></b></td>
          <td><select name="zynk_download_payments">
              <?php if ($zynk_download_payments) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Download payments from the website to Sage.</i>
        </tr>

        <!-- single account reference . . . -->
        <tr>
          <td><b><?php echo $entry_account_reference; ?></b></td>
          <td><input type="text" id="zynk_account_reference" name="zynk_account_reference" value="<?php echo($zynk_account_reference); ?>" /><i>&nbsp;&nbsp;&nbsp;If 'Download Customers' is NOT selected or a customer cannot be found when downloading the order then the specified Account Reference for Sage will be used.</i>
        </tr>

        <!-- bank account . . . -->
        <tr>
          <td><b><?php echo $entry_bank_account; ?></b></td>
          <td><input type="text" id="zynk_bank_account" name="zynk_bank_account" value="<?php echo($zynk_bank_account); ?>" /><i>&nbsp;&nbsp;&nbsp;Default Bank account for payments to be allocated to.</i>
        </tr>

        <!-- taken by . . . -->
        <tr>
          <td><b><?php echo $entry_taken_by; ?></b></td>
          <td><input type="text" id="zynk_taken_by" name="zynk_taken_by" value="<?php echo($zynk_taken_by); ?>" /><i>&nbsp;&nbsp;&nbsp;The value for the order taken by field within Sage.</i>
        </tr>

        <!-- default category . . . -->
        <tr>
          <td><b><?php echo $entry_default_category; ?></b></td>
          <td>
                <select name="zynk_default_category">
                    <option value="" <?php if ($zynk_default_category == '0') { echo('selected="selected"'); } ?> > -- None -- </option>
                    <?php
                        foreach ($categories as $category)
                        {
                            echo('<option value="' . $category['category_id'] . '"');
                            if ($zynk_default_category == $category['category_id']) { echo('selected="selected"'); }
                            echo('>'.$category['name'].'</option>');
                        }
                    ?>
                </select>
                <i>&nbsp;&nbsp;&nbsp;The default category to assign to products when NOT uploading categories from Sage. This will ONLY be visible within the admin area.</i>
          </td>
        </tr>

        <!-- default product image location . . . -->
        <tr>
          <td><b><?php echo $entry_default_product_image_directory; ?></b></td>
          <td>
              <input <?php if (!$zynk_upload_product_images) { ?> <?php } ?> type="text" id="zynk_default_product_image_directory" name="zynk_default_product_image_directory" value="<?php echo($zynk_default_product_image_directory); ?>" />
              <i>&nbsp;&nbsp;&nbsp;The default location for images to assign to products.</i>
          </td>
        </tr>

        <!-- default tax code . . . -->
        <tr>
          <td><b><?php echo $entry_default_tax_code; ?></b></td>
          <td><input type="text" id="zynk_default_tax_code" name="zynk_default_tax_code" value="<?php echo($zynk_default_tax_code); ?>" /><i>&nbsp;&nbsp;&nbsp;The default tax code to use.</i>
        </tr>

        <!-- default tax rate . . . -->
        <tr>
          <td><b><?php echo $entry_default_tax_rate; ?></b></td>
          <td><input type="text" id="zynk_default_tax_rate" name="zynk_default_tax_rate" value="<?php echo($zynk_default_tax_rate); ?>" /><i>&nbsp;&nbsp;&nbsp;The default tax rate to use.</i>
        </tr>

        <!-- shipping as item line . . . -->
        <tr>
          <td><b><?php echo $entry_shipping_as_item_line; ?></b></td>
          <td><select name="zynk_shipping_as_item_line">
              <?php if ($zynk_shipping_as_item_line) { ?>
              <option value="1" selected="selected">Yes</option>
              <option value="0">No</option>
              <?php } else { ?>
              <option value="1">Yes</option>
              <option value="0" selected="selected">No</option>
              <?php } ?>
            </select><i>&nbsp;&nbsp;&nbsp;Bring the shipping down as an individual line item, or as carriage on the order.</i>
        </tr>
        <!-- download limit . . . -->
        <tr>
          <td><b><?php echo $entry_download_limit; ?></b></td>
          <td><input type="text" id="zynk_download_limit" name="zynk_download_limit" value="<?php echo($zynk_download_limit); ?>" /><i>&nbsp;&nbsp;&nbsp;The number of orders to bring down at any one time.</i>
        </tr>

        <!-- taxable product tax class . . . -->
        <tr>
          <td><b><?php echo $entry_vatable_taxclass; ?></b></td>
          <td>
                <select name="zynk_vatable_taxclass">
                    <option value="" <?php if ($zynk_vatable_taxclass == '0') { echo('selected="selected"'); } echo('>'.$text_none.'</option>');?>
                    <?php
                        foreach ($tax_classes as $tax_class)
                        {
                            echo('<option value="' . $tax_class['tax_class_id'] . '"');
                            if ($zynk_vatable_taxclass == $tax_class['tax_class_id']) { echo('selected="selected"'); }
                            echo('>'.$tax_class['title'].'</option>');
                        }
                    ?>>
                </select>
                <i>&nbsp;&nbsp;&nbsp;The tax class denoting taxable products upon the website.</i>
          </td>
        </tr>

        <!-- non taxable product tax class . . . -->
        <tr>
          <td><b><?php echo $entry_nonvatable_taxclass; ?></b></td>
          <td>
                <select name="zynk_nonvatable_taxclass">
                    <option value="" <?php if ($zynk_nonvatable_taxclass == '0') { echo('selected="selected"'); } echo('>'.$text_none.'</option>');?>
                    <?php
                        foreach ($tax_classes as $tax_class)
                        {
                            echo('<option value="' . $tax_class['tax_class_id'] . '"');
                            if ($zynk_nonvatable_taxclass == $tax_class['tax_class_id']) { echo('selected="selected"'); }
                            echo('>'.$tax_class['title'].'</option>');
                        }
                    ?>>
                </select>
                <i>&nbsp;&nbsp;&nbsp;The tax class denoting non taxable products upon the website.</i>
          </td>
        </tr>

        <!-- exempt product tax class . . . -->
        <tr>
          <td><b><?php echo $entry_exempt_taxclass; ?></b></td>
          <td>
                <select name="zynk_exempt_taxclass">
                    <option value="" <?php if ($zynk_exempt_taxclass == '0') { echo('selected="selected"'); } echo('>'.$text_none.'</option>');?>
                    <?php
                        foreach ($tax_classes as $tax_class)
                        {
                            echo('<option value="' . $tax_class['tax_class_id'] . '"');
                            if ($zynk_exempt_taxclass == $tax_class['tax_class_id']) { echo('selected="selected"'); }
                            echo('>'.$tax_class['title'].'</option>');
                        }
                    ?>>
                </select>
                <i>&nbsp;&nbsp;&nbsp;The tax class denoting tax exempt products upon the website.</i>
          </td>
        </tr>

        <!-- taxable product tax code in Sage . . . -->
        <tr>
          <td><b><?php echo $entry_vatable_taxcode; ?></b></td>
          <td><input type="text" id="zynk_vatable_taxcode" name="zynk_vatable_taxcode" value="<?php echo($zynk_vatable_taxcode); ?>" /><i>&nbsp;&nbsp;&nbsp;The tax code to use for taxable products within Sage.</i></td>
        </tr>
        
        <!-- non taxable product tax code in Sage . . . -->
        <tr>
          <td><b><?php echo $entry_nonvatable_taxcode; ?></b></td>
          <td><input type="text" id="zynk_nonvatable_taxcode" name="zynk_nonvatable_taxcode" value="<?php echo($zynk_nonvatable_taxcode); ?>" /><i>&nbsp;&nbsp;&nbsp;The tax code to use for non taxable products within Sage.</i></td>
        </tr>
        
        <!-- exempt product tax code in Sage . . . -->
        <tr>
          <td><b><?php echo $entry_exempt_taxcode; ?></b></td>
          <td><input type="text" id="zynk_exempt_taxcode" name="zynk_exempt_taxcode" value="<?php echo($zynk_exempt_taxcode); ?>" /><i>&nbsp;&nbsp;&nbsp;The tax code to use for tax exempt products within Sage.</i></td>
        </tr>

      </table>
    </form>
  </div>
</div>

<?php echo $footer; ?>