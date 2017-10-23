<?php
if ( ! defined( 'WP_SEPARATE_USER_BASE_ENABLED' ) || ! WP_SEPARATE_USER_BASE_ENABLED ) {
	return;
}

if ( ! file_exists( __DIR__ . '/wp-separate-user-base/wp-separate-user-base.php' ) ) {
	return;
}


require_once __DIR__ . '/wp-separate-user-base/wp-separate-user-base.php';
