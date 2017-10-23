<?php
namespace WP_SUB;

use WP_User_Query;

/**
 * Handles user modifications
 *
 * Class User
 * @package WP_SUB
 */
class User {
	public function register_hooks() {
		add_action( 'pre_get_users', array( $this, 'add_user_meta_query' ) );
		add_action( 'user_register', array( $this, 'add_user_access_meta' ) );
	}

	/**
	 * Adds the necessary query args to WP_User_Query
	 *
	 * @param WP_User_Query $query
	 */
	function add_user_meta_query( WP_User_Query $query ) {

		// Do not modify queries when on the network admin
		if ( is_network_admin() ) {
			return;
		}

		// Allows the modifications to be disabled by adding the 'wp_sub_disable_query_integration' query arg
		if ( apply_filters( 'wp_sub_disable_query_integration', $query->get( 'wp_sub_disable_query_integration' ), $query ) ) {
			return;
		}

		$meta = $query->get( 'meta_query' );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		// Add meta query meta query array
		$meta['user_separation'] = array(
			'relation' => 'OR',
			array(
				'key'   => WP_Separate_User_Base::NETWORK_META_KEY,
				'value' => get_current_site()->id,
			),
			array(
				'key'   => WP_Separate_User_Base::SITE_META_KEY,
				'value' => get_current_blog_id(),
			),
		);

		$query->set( 'meta_query', $meta );
	}

	/**
	 * Automatically adds users to the current network or site based on the network configuration
	 *
	 * @param $user_id
	 */
	function add_user_access_meta( $user_id ) {
		if ( ! apply_filters( 'wp_sub_add_user_access_meta', true, $user_id ) ) {
			return;
		}

		if ( get_network_option( get_current_site()->id, 'wp_sub_add_users_to_network' ) ) {
			update_user_meta( $user_id, WP_Separate_User_Base::NETWORK_META_KEY, get_current_site()->id );
		} else {
			update_user_meta( $user_id, WP_Separate_User_Base::SITE_META_KEY, get_current_blog_id() );
		}
	}
}
