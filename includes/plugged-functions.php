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
	$userdata = WP_User::get_data_by( $field, $value );

	if ( ! $userdata ) {
		return false;
	}

	$user = new WP_User;
	$user->init( $userdata );

	if ( ! wp_sub_user_exists( $user->ID ) ) {
		return false;
	}

	return $user;
}
