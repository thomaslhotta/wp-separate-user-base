<?php
/**
 * Contains WordPress functions that are replaced by this plugin.
 */


/**
 * @param $field
 * @param $value
 *
 * @return bool|WP_User
 */
function get_user_by( $field, $value ) {
	global $current_user;

	$userdata = WP_User::get_data_by( $field, $value );

	if ( ! $userdata ) {
		return false;
	}

	$user = new WP_User;
	$user->init( $userdata );

	$bypass_check_functions = array( 'username_exists' );
	$bypass_check = ! wp_sub_enabled();

	$backtrace = debug_backtrace();

	if ( isset( $backtrace[1] ) ) {
		$last_call = $backtrace[1];

		if ( ! isset( $last_call['class'] ) && in_array( $last_call['function'], $bypass_check_functions ) ) {
			$bypass_check = true;
		}
	}

	if ( ! $bypass_check && ! wp_sub_user_exists( $user->ID ) ) {
		return false;
	}

	if ( $current_user instanceof WP_User && $current_user->ID === (int) $userdata->ID ) {
		return $current_user;
	}

	return $user;
}
