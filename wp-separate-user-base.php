<?php
namespace WP_SUB;

use WP_CLI,
	WP_SUB;

/**
 * Handles the plugin initialization
 *
 * @package WP_SUB
 */
class WP_Separate_User_Base {

	/**
	 * User meta key used for allowed networks
	 */
	const NETWORK_META_KEY = 'wp_sub_n';

	/**
	 * User meta key used for allowed sites
	 */
	const SITE_META_KEY = 'wp_sub_s';

	/**
	 * @var WP_Separate_User_Base
	 */
	protected static $instance;

	/**
	 * @return WP_Separate_User_Base
	 */
	public static function get_instance() {
		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {
		require __DIR__ . '/includes/functions.php';
		require __DIR__ . '/includes/plugged-functions.php';
		require __DIR__ . '/includes/class-user.php';
		require __DIR__ . '/includes/class-admin.php';

		$user = new User();
		$user->register_hooks();

		$admin = new Admin();
		$admin->register_hooks();

		if ( class_exists( 'WP_CLI_Command', false ) ) {
			require_once __DIR__ .'/includes/class-cli.php';
			WP_CLI::add_command( 'separate-user-base', 'WP_SUB\CLI' );
		}
	}
}

WP_Separate_User_Base::get_instance();
