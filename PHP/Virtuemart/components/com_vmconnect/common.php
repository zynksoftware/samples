<?php
define( '_VALID_MOS', '1') ;
define( '_JEXEC', '1') ;

define( 'SAGE_COMMON', 0) ;
define( 'SAGE_UPLOAD', 1) ;
define( 'SAGE_DOWNLOAD', 2) ;
define( 'DEFAULT_NOMINAL_CODE', 4000) ;
define( 'BASEPATH', dirname(__FILE__)."/../..") ;

global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_lang, $database, $config;

require_once(BASEPATH . "/configuration.php") ;

if (class_exists('JConfig', false)) {
	$jconfig =& new JConfig();
	define('JPATH_BASE', BASEPATH);
	define('DS', '/') ;
	require_once(BASEPATH . "/libraries/joomla/base/object.php") ;
	require_once(BASEPATH . "/includes/database.php") ;
	$dboptions = array ( 'driver' => $jconfig->dbtype, 'host' => $jconfig->host, 'user' => $jconfig->user, 'password' => $jconfig->password, 'database' => $jconfig->db, 'prefix' => $jconfig->dbprefix );
	$database = JDatabase::getInstance( $dboptions) ;
} else {
	require_once(BASEPATH . "/includes/database.php") ;
	$database = new database( $mosConfig_host, $mosConfig_user, $mosConfig_password, $mosConfig_db, $mosConfig_dbprefix );
}

$database->setQuery("SET SQL_BIG_SELECTS = 1");
$database->query();
require_once(BASEPATH . "/components/com_vmconnect/vmconnect.class.php") ;

$config = new mosVMCconf() ;
$config->load() ;
if (!$config->is_configured()) bye("This connector has not been configured yet - please go to the Admin interface and choose Components->VM-Connect", SAGE_COMMON) ;
checkauth() ;

function xml2array ($xml_data)
{
	// parse the XML datastring
	$xml_parser = xml_parser_create ();
	xml_parser_set_option ($xml_parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option ($xml_parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct ($xml_parser, $xml_data, $vals, $index);
	xml_parser_free ($xml_parser);

	// convert the parsed data into a PHP datatype
	$params = array();
	$ptrs[0] = & $params; 
	foreach ($vals as $xml_elem) {
	   $level = $xml_elem['level'] - 1;
	   switch ($xml_elem['type']) {
	   case 'open':
  			$tag_or_id = (array_key_exists ('attributes', $xml_elem)) ? $xml_elem['attributes']['ID'] : $xml_elem['tag'];
   			$ptrs[$level][$tag_or_id][] = array ();
			$ptrs[$level+1] = & $ptrs[$level][$tag_or_id][count($ptrs[$level][$tag_or_id])-1];
			break;
	   case 'complete':
   			$ptrs[$level][$xml_elem['tag']] = (isset ($xml_elem['value'])) ? $xml_elem['value'] : '';
   			break;
	   }
	}
	return ($params);
}

function writelog($message, $action) {
	global $database ;
	$database->setQuery("INSERT INTO #__vmc_log (action, message, at) VALUES ('$action', '$message', NOW())") ;
	$database->query() ;
}

function bye($message, $action) {
	global $database, $config, $mosConfig_mailfrom ;
	if (isset($config->email_report) && !empty($config->email_report))
		mail($config->email_report, "VM-Connect failure", $message, 'From: '.$mosConfig_mailfrom) ;
	else {
		$database->setQuery("SELECT email FROM jos_users ORDER BY id ASC LIMIT 1") ;
		$adminemail = $database->loadResult() ;
		if (!empty($adminemail))
			mail ($adminemail, "VM-Connect failure", $message, 'From: '.$mosConfig_mailfrom) ;
	}
	writelog($message, $action) ;
	die($action.": ".$message) ;
}

function checkauth() {
	global $config ;
	
	if (isset($config->check_passwd) && !$config->check_passwd)
		return ;
	
	if (!isset($_SERVER['PHP_AUTH_USER'])) {
	  header('WWW-Authenticate: Basic');
	  header('HTTP/1.0 401 Unauthorized');
		bye("Access Denied", SAGE_COMMON);
	} elseif ($_SERVER['PHP_AUTH_USER'] != $config->username || $_SERVER['PHP_AUTH_PW'] != $config->password) {
	 	header('WWW-Authenticate: Basic');
	  header('HTTP/1.0 401 Unauthorized');
		bye("Access Denied", SAGE_COMMON) ;
	}
}

function write_report(&$config, &$report, $mode) {
	global $database, $mosConfig_mailfrom ;
	
	if (!empty($config->email_report))
		@mail($config->email_report, 'VM-Connect '.($mode == SAGE_UPLOAD ? 'Upload' : 'Download').' Report', $report, 'From: '.$mosConfig_mailfrom) ;
	
	$database->setQuery("INSERT INTO jos_vmc_reports SET reportdate=NOW(),report='$report',mode='$mode'") ;
	$database->query() ;
}

?>