<?php
defined( '_VALID_MOS' ) or die( 'Restricted access' );

class mosVMCconf {
	var $vendor_id = null ;
	var $tax_rate_id = null ;	
	var $currency_code = 'GBP' ;
	var $username = null ;
	var $password = null ;
	var $testmode = true ;
	var $report = 1 ;
	var $send_invoices = 1 ;
	var $email_report = "" ;
	
	function mosVMCconf () {
		global $database ;
		$this->_db =& $database ;
		$this->_table = '#__vmc_conf' ;
	}
	
	function load() {
		$this->_db->setQuery("SELECT conf, value FROM $this->_table ORDER BY conf ASC") ;
		$results = $this->_db->loadAssocList() ;
		foreach ($results as $result)
			$this->$result['conf'] = $result['value'] ;
	}
	
	function store( $updateNulls=false ) {
		$vars = get_object_vars($this) ;
		$this->_db->setQuery("SELECT conf FROM $this->_table ORDER BY conf ASC") ;
		$dbvars = $this->_db->loadResultArray() ;
		foreach ($vars as $key=>$var) {
			if (substr($key, 0, 1) != "_") {
				if (!is_null($var) || $updateNulls) {
					echo "In array with $key=$var<br>" ;
					if (in_array($key, $dbvars))
						$this->_db->setQuery("UPDATE $this->_table SET value='$var' WHERE conf='$key'") ;
					else
						$this->_db->setQuery("INSERT $this->_table SET conf='$key',value='$var'") ;
					$this->_db->query() ;
				}
			}
		}
	}
	
	function is_configured() {
		$this->_db->setQuery("SELECT COUNT(*) FROM ".$this->_table) ;
		return ($this->_db->loadResult() > 0) ;
	}
}

?>