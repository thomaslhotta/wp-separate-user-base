<?php
// Set timezone to prevent warnings
if ( ! defined( 'BASE_DIR' ) ) {
	define( 'BASE_DIR', realpath( __DIR__ . '/../..' ) );
}

define( 'WP_TESTS_DIR', BASE_DIR . '/vendor/wordpress/phpunit/tests/phpunit/' );
define( 'ABSPATH', BASE_DIR . '/vendor/wordpress/phpunit/src/' );


$table_prefix = 'wptests_';

// Create the WP Test suite config
$config_file = "<?php 
		@define( ABSPATH, '" . ABSPATH . "'  );
		@define( 'WP_TESTS_MULTISITE', true );
		@define( 'WP_DEBUG', true );
		@define( 'DB_NAME', '" . DB_NAME . "' );
		@define( 'DB_USER', '" . DB_USER . "' );
		@define( 'DB_PASSWORD', '" . DB_PASSWORD . "' );
		@define( 'DB_HOST', '" . DB_HOST . "' );
		@define( 'DB_CHARSET', 'utf8' );
		@define( 'DB_COLLATE', '' );
		\$table_prefix  = 'wptests_';
		@define( 'WP_TESTS_DOMAIN', '" . WP_TESTS_DOMAIN . "' );
		@define( 'WP_TESTS_EMAIL', 'admin@test.dev' );
		@define( 'WP_TESTS_TITLE', 'Test page' );
		@define( 'WP_PHP_BINARY', 'php' );
";

if ( defined( 'WP_DEFAULT_THEME' ) ) {
	$config_file .= "\n  @define( 'WP_DEFAULT_THEME', '" . WP_DEFAULT_THEME . "' );";
}

file_put_contents( WP_TESTS_DIR . 'wp-tests-config.php', $config_file );


// Include the WP test suite functions
require_once WP_TESTS_DIR . 'includes/functions.php';

/**
 * Loads all plugins needed for running the tests
 */
function _manually_load_plugin() {
	// Gforms
	require_once __DIR__ . '/../../wp-separate-user-base.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Setup WordPress environment
require WP_TESTS_DIR . 'includes/bootstrap.php';


add_user_meta( 1, \WP_SUB\WP_Separate_User_Base::NETWORK_META_KEY, 1 );
update_network_option( 1, 'wp_sub_add_users_to_network', 1 );


// Limit the compute time required for passwords. This speeds up unit test that create users.
// DO NOT USE THIS IN PRODUCTION!
tests_add_filter(
	'wp_hash_password_options',
	function () {
		return array(
			'cost' => 4,
		);
	}
);
