<?php 

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

class HTML_vmconnect {
	function edit (&$config, $vendors, $tax_rates, $currencies, $shopper_groups, $countries, $states) {
		global $option, $mosConfig_live_site, $mainframe ;
		
		define ('VM_TABLEPREFIX', 'vm') ;
		$vm_path = dirname(__FILE__).'/../com_virtuemart/classes' ;
		require($vm_path . '/ps_database.php') ;
		require($vm_path . '/ps_html.php') ;
		
		$ps_html =& new ps_html ; 
		
		$userref_options[] = mosHTML::makeOption('0', htmlentities('<vmid>')) ;
		$userref_options[] = mosHTML::makeOption('1', htmlentities('js<vmid>')) ;
		$userref_options[] = mosHTML::makeOption('2', htmlentities('jsmith<vmid>')) ;
		$userref_options[] = mosHTML::makeOption('3', htmlentities('johnsmith<vmid>')) ;
		$userref_options[] = mosHTML::makeOption('4', htmlentities('smith<vmid>')) ;
		?>
		<link href="<?php echo $mosConfig_live_site ; ?>/administrator/components/com_vmconnect/vmconnect.css" rel="stylesheet" type="text/css" />
		<?php if (!file_exists($mosConfig_live_site . '/media/system/js/mootools-uncompressed.js')) : ?>
			<script type="text/javascript" charset="utf-8" src="<?php echo $mosConfig_live_site ?>/administrator/components/com_vmconnect/mootools-release-1.11.js"></script>
		<?php endif; ?>
		<script type="text/javascript" charset="utf-8">
			function update_state_list(country) {
				var url = 'index2.php?option=com_vmconnect&task=statelist&country_code=' + country ;
				new Ajax(url, {method:'get', update: $('state_select')}).request() ;
			}
		</script>

		<h1>VM-Connect</h1>
		
		<form action="index2.php" name="adminForm" method="POST">

			<div id="upload" class="vmc_config">
				<h2>Upload</h2>
				<table class="vmc_config">
					<tr>
						<td align="right" width="50%"><label for="testmode">Test Mode:</label></td>
						<td width="50%"><?php echo mosHTML::yesnoRadioList('testmode', 'id="testmode" class="inputbox"', $config->testmode, 'Test', 'Live') ; ?></td>
					</tr>
					<tr>
						<td align="right"><label for="report">Generate Reports:</label></td>
						<td><?php echo mosHTML::yesnoRadioList('report', 'id="report" class="inputbox"', $config->report) ; ?></td>
					</tr>
					<tr>
						<td align="right"><label for="email_report">Email Reports to:</label></td>
						<td><input type="text" name="email_report" value="<?php echo isset($config->email_report) ? $config->email_report : '' ; ?>" /></td>
					</tr>
					<tr>
						<td align="right" width="50%"><label for="vendor_id">Vendor</label></td>
						<td width="50%">
							<?php echo mosHTML::selectList($vendors, 'vendor_id', 'id="vendor_id" class="inputbox" size="1"', 'vendor_id', 'vendor_name', 
								(isset($config->vendor_id) ? $config->vendor_id : ''))?><br />
						</td>
					</tr>
					<tr>
						<td align="right"><label for="tax_rate_id">Default Tax Code</label></td>
						<td>
							<?php echo mosHTML::selectList($tax_rates, 'tax_rate_id', 'id="tax_rate_id" class="inputbox" size="1"', 'tax_rate_id', 'tax_rate', 
								(isset($config->tax_rate_id) ? $config->tax_rate_id : ''))?><br />
						</td>
					</tr>
					<tr>
						<td align="right"><label for="default_tax_country">Default Tax Country</label></td>
						<td>
							<?php echo mosHTML::selectList ($countries, 'default_tax_country', 'id="default_tax_country" class="inputbox" size="1" onchange="update_state_list(this.value)"', 'country_3_code', 
							'country_name', (isset($config->default_tax_country) && !empty($config->default_tax_country) ? $config->default_tax_country : 'GBR')) ?>
						</td>
					</tr>
					<tr>
						<td align="right"><label for="default_tax_state">Default Tax State</label></td>
						<td>
							<div id="state_select">
							<?php echo mosHTML::selectList ($states, 'default_tax_state', 'id="default_tax_state" class="inputbox" size="1"', 'state_2_code',
							'state_name', (isset($config->default_tax_state) && !empty($config->default_tax_state) ? $config->default_tax_state : 'EN')) ?>
							</div>
						</td>
					</tr>
					<tr>
						<td align="right"><label for="default_shopper_group">Default Shopper Group</label></td>
						<td>
							<?php echo mosHTML::selectList($shopper_groups, 'shopper_group_id', 'id="default_shopper_group" class="inputbox" size="1"', 'shopper_group_id', 
								'shopper_group_name', isset($config->default_shopper_group) ? $config->default_shopper_group : '') ?>
						</td>
					</tr>
					<tr>
						<td align="right"><label for="currency_code">Currency</label></td>
						<td>
							<?php echo mosHTML::selectList($currencies, 'currency_code', 'id="currency_code" class="inputbox" size="1"', 'currency_code', 'currency_name', 
								(isset($config->currency_code) ? $config->currency_code : 'GBP'))?>
						</td>
					</tr>
					<tr>
						<td align="right"><label for="insert_products">Create New Products</label></td>
						<td><?php echo mosHTML::yesnoRadioList('insert_products', 'class="inputbox"', isset($config->insert_products) ? $config->insert_products : 1)?></td>
					</tr>
					<tr>
						<td align="right"><label for="insert_products_unpublished">Always create Unpublished</label></td>
						<td><?php echo mosHTML::yesnoRadioList('insert_products_unpublished', 'class="inputbox"', isset($config->insert_products_unpublished) ? $config->insert_products_unpublished : 1)?></td>
					</tr>
					<tr>
						<td align="right"><label for="insert_categories">Create New Categories</label></td>
						<td><?php echo mosHTML::yesnoRadioList('insert_categories', 'class="inputbox"', isset($config->insert_categories) ? $config->insert_categories : 1)?></td>
					</tr>
					<tr>
						<td align="right"><label for="update_name">Update Product Name</label></td>
						<td><?php echo mosHTML::yesnoRadioList('update_name', 'class="inputbox"', isset($config->update_name) ? $config->update_name : 1)?></td>
					</tr>
					<tr>
						<td align="right"><label for="update_desc">Update Product Descriptions</label></td>
						<td><?php echo mosHTML::yesnoRadioList('update_desc', 'class="inputbox"', isset($config->update_desc) ? $config->update_desc : 0)?></td>
					</tr>
					<tr>
						<td align="right"><label for="update_price">Update Product Price</label></td>
						<td><?php echo mosHTML::yesnoRadioList('update_price', 'class="inputbox"', isset($config->update_price) ? $config->update_price : 1)?></td>
					</tr>
					<tr>
						<td align="right"><label for="update_stock">Update Product Stock Levels</label></td>
						<td><?php echo mosHTML::yesnoRadioList('update_stock', 'class="inputbox"', isset($config->update_stock) ? $config->update_stock : 1)?></td>
					</tr>
					<tr>
						<td align="right"><label for="update_category">Update Product Category</label></td>
						<td><?php echo mosHTML::yesnoRadioList('update_category', 'class="inputbox"', isset($config->update_category) ? $config->update_category : 0)?></td>
					</tr>
					<tr>
						<td align="right"><label for="update_weight">Update Product Weight</label></td>
						<td><?php echo mosHTML::yesnoRadioList('update_weight', 'class="inputbox"', isset($config->update_weight) ? $config->update_weight : 1)?></td>
					</tr>
					<tr>
						<td align="right"><label for="update_attributes">Update Product Attributes</label></td>
						<td><?php echo mosHTML::yesnoRadioList('update_attributes', 'class="inputbox"', isset($config->update_attributes) ? $config->update_attributes : 1)?></td>
					</tr>
				</table>
			</div>
			
			<div id="download" class="vmc_config">
				<h2>Download</h2>
				<table class="vmc_config">
					<tr>
						<td align="right" width="50%"><label for="send_invoices">Download:</label></td>
						<td width="50%">
							<?php echo mosHTML::yesnoRadioList('send_invoices', 'class="inputbox" size="1"', isset($config->send_invoices) ? $config->send_invoices : 0, 'Invoices', 'Sales Orders') ; ?>
						</td>
					</tr>
					<tr>
						<td align="right"><label for="dl_single_customer">Download as Single customer</label></td>
						<td>
							<?php echo mosHTML::yesnoRadioList('dl_single_customer', 'class="inputbox" size="1"', isset($config->dl_single_customer) ? $config->dl_single_customer : 0) ; ?>
						</td>
					</tr>
					<tr>
						<td align="right"><label for="dl_customer_name">Single Customer Name</label></td>
						<td>
							<input type="text" name="dl_customer_name" value="<?php echo (isset($config->dl_customer_name) ? $config->dl_customer_name : 'INTERNET') ?>" />
						</td>
					</tr>
					<tr>
					 <td align="right"><label for="nominalcode_uk">Nominal Code (UK)</label></td>
					 <td>
					   <input type="text" name="nominalcode_uk" value="<?php echo $config->nominalcode_uk ?>" />
					 </td>
					</tr>
					<tr>
					 <td align="right"><label for="nominalcode_int">Nominal Code (International)</label></td>
					 <td>
					   <input type="text" name="nominalcode_int" value="<?php echo $config->nominalcode_int ?>" />
					 </td>
					</tr>
					<tr>
					 <td align="right"><label for="userref">User Reference Format</label></td>
					 <td>
             <?php echo mosHTML::selectList($userref_options, 'userref', 'class="inputbox"', 'value', 'text', $config->userref) ?>
					 </td>
					</tr>
				</table>
			</div>
			
			<br />
			
			<div id="security" class="vmc_config">
				<h2>Security</h2>
				<table class="vmc_config">
					<tr>
						<!-- <td align="right" width="50%"><label for="license_key">License Key</label></td> -->
						<td width="50%"><input type="hidden" name="license_key" value="<?php echo $config->license_key ?>" /></td>
					</tr>
					<tr>
						<td align="right" width="50%"><label for="check_passwd" valign="top">Enable Password Protection<br />(requires mod_php)</label></td>
						<td valign="top" width="50%">
							<?php echo mosHTML::yesnoRadioList('check_passwd', 'class="inputbox"', isset($config->check_passwd) ? $config->check_passwd : 0) ?>
						</td>
					</tr>
					<tr>
						<td align="right" width="50%"><label for="username">Username</label></td>
						<td width="50%"><input type="text" name="username" value="<?php echo isset($config->username) ? $config->username : '' ; ?>" /></td>
					</tr>
					<tr>
						<td align="right"><label for="password">Password</label></td>
						<td><input type="password" name="password" value="<?php echo isset($config->password) ? $config->password : '' ; ?>" /></td>
					</tr>
					<tr>
						<td colspan="2" align="center">Upload URL: &nbsp;&nbsp;<?php echo $mosConfig_live_site ; ?>/components/com_vmconnect/upload.php</td>
					</tr>
					<tr>
						<td colspan="2" align="center">Download URL: &nbsp;&nbsp;<?php echo $mosConfig_live_site ; ?>/components/com_vmconnect/download.php</td>
					</tr>
				</table>
			</div>
		<input type="hidden" name="option" value="<?php echo $option ; ?>" />
		<input type="hidden" name="task" value="save" />
		</form>
		<?php
	}
	
	function reports($reports, $currentreport) {
		global $option, $task, $mosConfig_live_site;
		?>
		<link href="<?php echo $mosConfig_live_site ; ?>/administrator/components/com_vmconnect/vmconnect.css" rel="stylesheet" type="text/css" />
		<form action="index2.php" method="POST" name="adminForm">
			<h1>Report for <?php echo $currentreport->reportdate ?></h1>
			<div id="reportdates">
				<?php foreach($reports as $report) :?>
					<a href="index2.php?option=<?php echo $option ?>&amp;task=reports&amp;report_id=<?php echo $report->id ?>"><?php echo $report->reportdate ?></a> <br />
				<?php endforeach ;?>
			</div>
			<div id="reportdiv">
				<?php echo nl2br($currentreport->report) ; ?>
			</div>
			<input type="hidden" name="option" value="<?php echo $option ; ?>">
			<input type="hidden" name="boxchecked" value="0">
			<input type="hidden" name="task" value="">
		</form>
		<?php
	}
}

?>