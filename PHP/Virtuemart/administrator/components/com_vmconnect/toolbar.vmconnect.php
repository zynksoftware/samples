<?php

defined( '_VALID_MOS' ) or die( 'Restricted access' );

require_once( $mainframe->getPath( 'toolbar_html' ) );

switch ( $task ) {
	case 'reports' :
		TOOLBAR_contact::_REPORTS() ;
		break ;
	case 'config' :
	default:
		TOOLBAR_contact::_DEFAULT();
		break;
}
?>