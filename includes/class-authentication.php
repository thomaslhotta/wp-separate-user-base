<?php

namespace WP_SUB;

use WP_User;
use ReflectionException;

/**
 * Handles user authentication
 *
 * Class User
 * @package WP_SUB
 */
class Authentication {
	/**
	 * @codeCoverageIgnore
	 * @throws ReflectionException
	 */
	public function register_hooks() {
		add_action( 'auth_cookie_bad_username', array( $this, 'auth_cookie_bad_username' ) );
	}

	public function auth_cookie_bad_username( $cookie_elements ) {
		if ( ! isset( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
			return;
		}

		$user = WP_User::get_data_by( 'login', $cookie_elements['username'] );

		if ( ! $user ) {
			return;
		}

		wp_clear_auth_cookie();
	}
}
