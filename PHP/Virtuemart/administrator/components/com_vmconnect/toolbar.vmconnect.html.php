<?php

defined( '_VALID_MOS' ) or die( 'Restricted access' );

class TOOLBAR_contact {
	function _REPORTS() {
		mosMenuBar::startTable() ;
		mosMenuBar::custom('config', '', 'config.png', 'Settings', false) ;
		mosMenuBar::spacer() ;
		mosMenuBar::trash('emptyreports', 'Empty', false) ;
		mosMenuBar::endTable() ;
	}
	
	function _DEFAULT() {
		mosMenuBar::startTable();
		mosMenuBar::custom('reports', '', 'copy_f2.png', 'Reports', false) ;
		mosMenuBar::spacer() ;
		mosMenuBar::save();
		mosMenuBar::spacer();
		mosMenuBar::cancel();
		mosMenuBar::endTable();
	}
}
?>