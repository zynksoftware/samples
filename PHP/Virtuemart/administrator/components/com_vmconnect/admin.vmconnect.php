<?php 

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once( $mainframe->getPath( 'admin_html' ) );
require_once( $mainframe->getPath( 'class' ) );

$limit = $mainframe->getUserStateFromRequest("viewlistlimit", 'limit', 10);
$limitstart = $mainframe->getUserStateFromRequest("view{\$option}limitstart", 'limitstart', 0);

switch ($task) {
	case "save" :
		save() ;
		break ;
	case "reports" :
		reports($limitstart, $limit) ;
		break ;
	case "emptyreports" :
		emptyreports() ;
		break ;
	case 'statelist' :
		state_list() ;
		exit ;
	case "config" :
	default :
		edit() ;
		break ;
}

function edit() {
	global $database ;
	
	$config =& new mosVMCconf() ;
	$config->load() ;
	
	$database->setQuery("SELECT vendor_id, vendor_name FROM #__vm_vendor ORDER BY vendor_name ASC") ;
	$vendors = $database->loadObjectList() ;
	
	$database->setQuery("SELECT tax_rate_id, tax_rate FROM #__vm_tax_rate ORDER BY tax_rate ASC");
	$tax_rates = $database->loadObjectList() ;
	
	$database->setQuery("SELECT currency_code, currency_name FROM #__vm_currency ORDER BY currency_name ASC") ;
	$currencies = $database->loadObjectList() ;
	
	$database->setQuery("SELECT shopper_group_id, shopper_group_name FROM #__vm_shopper_group ORDER BY shopper_group_id ASC") ;
	$shopper_groups = $database->loadObjectList() ;
	
	$database->setQuery("SELECT country_3_code, country_name FROM #__vm_country ORDER BY country_name ASC") ;
	$countries = $database->loadObjectList() ;
	
	$country_code = isset($config->default_tax_country) && !empty($config->default_tax_country) ? $config->default_tax_country : 'GBR' ;	
	$database->setQuery("SELECT state_2_code, state_name FROM #__vm_state s LEFT JOIN #__vm_country c ON s.country_id = c.country_id WHERE country_3_code = '".
			$country_code."' ORDER BY state_NAME ASC") ;
	$states = $database->loadObjectList() ;
	
	HTML_vmconnect::edit($config, $vendors, $tax_rates, $currencies, $shopper_groups, $countries, $states) ;
}

function state_list() {
	global $database ;

	$config =& new mosVMCconf() ;
	$config->load() ;
	
	$country_code = mosGetParam($_REQUEST, 'country_code', '') ;
	$database->setQuery("SELECT state_2_code, state_name FROM #__vm_state s LEFT JOIN #__vm_country c ON s.country_id = c.country_id WHERE country_3_code = '".
			$country_code."' ORDER BY state_NAME ASC") ;
	$states = $database->loadObjectList() ;
	
	echo mosHTML::selectList ($states, 'default_tax_state', 'id="default_tax_state" class="inputbox" size="1"', 'state_2_code',
	'state_name', (isset($config->default_tax_state) && !empty($config->default_tax_state) ? $config->default_tax_state : 'EN')) ;
}

function save() {
	global $option, $database ;
	$ignore = array('task', 'option', 'Itemid') ;
		
	$config =& new mosVMCconf() ;
	$config->load() ;
		
	foreach ($_POST as $key=>$postvar)
		if (!in_array($key, $ignore)) $config->$key = mosGetParam($_POST, $key, '') ;
	
	$config->store() ;
	mosRedirect ("index2.php?option=$option", "Changes Saved!") ;
}

function reports($limitstart, $limit) {
	global $database, $mosConfig_absolute_path, $option ;

	$report_id = mosGetParam($_REQUEST, 'report_id', null) ;

	$sql = "SELECT id, DATE_FORMAT(reportdate, '%a %D %b %Y %H:%i') AS reportdate, mode FROM #__vmc_reports ORDER BY id DESC" ;
	$database->setQuery($sql) ;
	$reports = $database->loadObjectList() ;
	
	if (count($reports) < 1) mosRedirect('index2.php?option='.$option, 'No Reports Available') ;
	
	if (is_null($report_id))
		$database->setQuery("SELECT id, DATE_FORMAT(reportdate, '%a %D %b %Y %H:%i') AS reportdate, report FROM #__vmc_reports ORDER BY id DESC LIMIT 1") ;
	else
		$database->setQuery("SELECT id, DATE_FORMAT(reportdate, '%a %D %b %Y %H:%i') AS reportdate, report FROM #__vmc_reports WHERE id='$report_id' LIMIT 1") ;
	$currentreport = $database->loadObjectList() ;
	if (count($currentreport) < 1)	mosRedirect("index2.php?option=$option&amp;task=reports", 'Invalid Report') ;
		
	HTML_vmconnect::reports($reports, $currentreport[0]) ;
}

function emptyreports () {
	global $database, $option ;
	
	$database->setQuery("DELETE FROM #__vmc_reports") ;
	$database->query() ;
	if ($database->getErrorNum()) die($database->getErrorMsg()) ;
	
	mosRedirect('index2.php?option='.$option, 'Reports deleted') ;
}

?>

