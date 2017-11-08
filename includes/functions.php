<?php
/**
 * Contains global functions
 */

/**
 * Returns true if the user exists on the given network and site
 *
 * @param int $user_id
 * @param int $network
 * @param int $site
 *
 * @return bool
 */
function wp_sub_user_exists( int $user_id, int $network = 0, int $site = 0 ) : bool {
	$callers = wp_list_pluck( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 'function' );
	array_shift( $callers );

	// Prevent recursions
	if ( in_array( __FUNCTION__, $callers ) ) {
		return true;
	}

	// Fill defaults
	if ( empty( $network ) ) {
		$network = get_current_site()->id;
	}

	if ( empty( $site ) ) {
		$site = get_current_blog_id();
	}

	$allowed = false;

	if ( is_super_admin( $user_id ) ) {
		$allowed = true;
	} else if ( wp_sub_user_exists_on_network( $user_id, $network ) ) {
		// Check if user can access network
		$allowed = true;
	} else if ( wp_sub_user_exists_on_site( $user_id, $site ) ) {
		// Check if user can access site
		$allowed = true;
	}

	return apply_filters( 'wp_sub_user_exists', $allowed, $user_id, $network, $site );
}

/**
 * Returns true if the given user exists on the given network.
 *
 * @param int $user_id
 * @param int $network
 *
 * @return bool
 */
function wp_sub_user_exists_on_network( int $user_id, int $network ) : bool {
	$allowed = in_array( $network, get_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::NETWORK_META_KEY, false ) );
	return apply_filters( 'wp_sub_user_exists_on_network', $allowed, $user_id, $network );
}

/**
 * Adds the given user to the given site
 *
 * @param int $user_id
 * @param int $network_id
 *
 * @return bool
 */
function wp_sub_add_user_to_network( int $user_id, int $network_id ) : bool {
	if ( ! get_network( $network_id ) instanceof WP_Network ) {
		return false;
	}

	if ( wp_sub_user_exists_on_network( $user_id, $network_id ) ) {
		return false;
	}

	return boolval( add_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::NETWORK_META_KEY, $network_id ) );
}

/**
 * Removes the given user from the given network
 *
 * @param int $user_id
 * @param int $network_id
 *
 * @return bool
 */
function wp_sub_remove_user_from_network( int $user_id, int $network_id ) : bool {
	return delete_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::NETWORK_META_KEY, $network_id );
}

/**
 * Returns true if the user exists on the given site.
 *
 * @param int $user_id
 * @param int $site_id
 *
 * @return bool
 */
function wp_sub_user_exists_on_site( int $user_id, int $site_id ) : bool {
	$allowed = in_array( $site_id, get_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::SITE_META_KEY, false ) );
	return apply_filters( 'wp_sub_user_exists_on_network', $allowed, $user_id, $site_id );
}

/**
 * Adds the given user to the given site
 *
 * @param int $user_id
 * @param int $site_id
 *
 * @return bool
 */
function wp_sub_add_user_to_site( int $user_id, int $site_id ) : bool {
	if ( ! get_site( $site_id ) instanceof WP_Site ) {
		return false;
	}

	if ( wp_sub_user_exists_on_site( $user_id, $site_id ) ) {
		return false;
	}

	return boolval( add_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::SITE_META_KEY, $site_id ) );
}

/**
 * Removes the given user from the given site
 *
 * @param int $user_id
 * @param int $site_id
 *
 * @return bool
 */
function wp_sub_remove_user_from_site( int $user_id, int $site_id ) : bool {
	return delete_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::SITE_META_KEY, $site_id );
}
