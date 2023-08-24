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

	/**
	 * Logs the user out if an auth cookie exists for a different site that the user is not added to.
	 *
	 * This is necessary because we currently have to way to change the cookie path to be specific to a
	 * site only without completely overwriting the build in WP functionality.
	 *
	 * @param $cookie_elements
	 *
	 * @return void
	 */
	public function auth_cookie_bad_username( $cookie_elements ) {
		if ( ! isset( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
			return;
		}

		$user = WP_User::get_data_by( 'login', $cookie_elements['username'] );

		if ( ! $user ) {
			return;
		}

		// Set the system to an unauthenticated state. This prevents endless loops.
		wp_set_current_user( 0 );

		wp_clear_auth_cookie();
	}
}
