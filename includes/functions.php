<?php
/**
 * Contains global functions
 */

/**
 * Returns true if wp_sub is enabled
 *
 * @return bool
 */
function wp_sub_enabled() {
	if ( is_network_admin() ) {
		return false;
	}

	return apply_filters( 'wp_sub_enabled', true );
}

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
function wp_sub_user_exists_on_network( int $user_id, int $network_id ) : bool {
	$networks = (array) get_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::NETWORK_META_KEY, false );
	$networks = array_filter( $networks );

	$allowed = in_array( $network_id, $networks );
	return apply_filters( 'wp_sub_user_exists_on_network', $allowed, $user_id, $network_id );
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
 * Returns the site IDs the given user has explicitly been added to.
 *
 * @param int $user_id
 *
 * @return array
 */
function wp_sub_get_user_sites( int $user_id ) {
	return get_user_meta( $user_id, \WP_SUB\WP_Separate_User_Base::SITE_META_KEY, false );
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

/**
 * Returns query parameters for a query the fetches users the given user has access to.
 *
 * @param int $current_user
 *
 * @return array
 */
function wp_sub_get_accessible_users_query_args( int $current_user ) {
	$blogs = wp_list_pluck(
		get_blogs_of_user( $current_user, true ),
		'userblog_id'
	);

	$args = [
		'wp_sub_disable_query_integration' => true,
		'meta_query' => [
			'wp_sub' => [
				'relation' => 'OR',
				[
					'key' => \WP_SUB\WP_Separate_User_Base::SITE_META_KEY,
					'value' => $blogs,
					'compare' => 'IN',
				],
			],
		],
	];

	if ( wp_sub_user_exists_on_network( $current_user, get_current_site()->id ) ) {
		$args['meta_query']['wp_sub'][] = [
			'key' => \WP_SUB\WP_Separate_User_Base::NETWORK_META_KEY,
			'value' => get_current_site()->id,
		];
	}

	return $args;
}

/**
 * Returns users the are not assigned to any site or network
 *
 * @return array
 */
function wp_sub_get_orphaned_users() {
	global $wpdb;

	$all_user_site_keys = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID AS user_id, GROUP_CONCAT( m.meta_value SEPARATOR ',' ) AS site_ids, MAX(b.blog_id) AS found_id
				 FROM $wpdb->usermeta AS m
				 LEFT JOIN $wpdb->users AS u ON u.ID = m.user_id
				 LEFT JOIN $wpdb->blogs AS b ON b.blog_id = m.meta_value
				 WHERE m.meta_key = %s AND u.ID IS NOT NULL
				 GROUP BY u.ID HAVING found_id IS NULL
				",
			\WP_SUB\WP_Separate_User_Base::SITE_META_KEY
		),
		ARRAY_A
	);

	$orphaned = [];
	foreach ( $all_user_site_keys as $user_site ) {
		foreach ( wp_parse_id_list( $user_site['site_ids'] ) as $site_id ) {
			$orphaned[ $site_id ][] = intval( $user_site['user_id'] );
		}
	}

	return $orphaned;
}
