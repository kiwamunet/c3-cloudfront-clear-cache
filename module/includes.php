<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( 'base.php' );
require_once( 'model/client-base.php' );

// Model
require_once( 'model/auth.php' );
require_once( 'model/invalidation.php' );
if ( c3_is_later_than_php_55() ) {
	require_once( 'model/client-v3.php' );
} else {
	require_once( 'model/client-v2.php' );
}


// View
require_once( 'view/components.php' );
require_once( 'view/root.php' );
require_once( 'view/menus.php' );