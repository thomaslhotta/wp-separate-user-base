<?php

namespace WP_SUB;

use WP_User_Query,
	ReflectionClass;

/**
 * Handles user modifications
 *
 * Class User
 * @package WP_SUB
 */
class User {

	/**
	 * @var string[]
	 */
	protected $query_regex;

	/**
	 * @codeCoverageIgnore
	 * @throws \ReflectionException
	 */
	public function register_hooks() {
		// User query integration
		add_filter( 'query', array( $this, 'query' ) );
		add_action( 'pre_get_users', array( $this, 'add_user_meta_query' ), 9999 );

		// User creation
		add_action( 'user_register', array( $this, 'add_user_access_meta' ) );

		// Caching behavior changes
		$this->modify_cache_groups();
		add_action( 'switch_blog', array( $this, 'modify_cache_groups' ) );
	}

	/**
	 * Modifies persistent cache groups
	 *
	 * @throws \ReflectionException
	 */
	public function modify_cache_groups() {
		$cache = $GLOBALS['wp_object_cache'];

		$reflection = new ReflectionClass( $cache );
		if ( ! $reflection->hasProperty( 'global_groups' ) ) {
			wp_die( 'Your object cache plugin is not compatible with WP_Separate_User_Base' );
		}

		$global_groups = $reflection->getProperty( 'global_groups' );
		$global_groups->setAccessible( true );
		$groups = $global_groups->getValue( $cache );

		unset( $groups['useremail'] );

		$global_groups->setValue( $cache, $groups );
	}

	/**
	 * Hooks into all queries to modify the query created in WP_User::get_data_by
	 *
	 * @param $sql
	 *
	 * @return string
	 */
	public function query( $sql ) {
		global $wpdb;

		// Prevent more expensive regex from running if not a user query
		if ( 0 !== strpos( $sql, 'SELECT' ) || false === 'FROM ' . $wpdb->users ) {
			return $sql;
		}

		foreach ( $this->get_query_regex() as $pattern ) {
			if ( preg_match( $pattern, $sql ) && wp_sub_enabled() ) {
				$sql = $this->add_meta_sql( $sql );
				break;
			}
		}

		return $sql;
	}

	/**
	 * Returns the regex pattern to match user queries.
	 *
	 * @return string[]
	 */
	protected function get_query_regex() {
		global $wpdb;

		if ( ! $this->query_regex ) {
			$this->query_regex = array(
				sprintf(
					'/^SELECT \* FROM %s WHERE (user_email) = \'.*\'$/',
					preg_quote( $wpdb->users, '/' )
				),
			);
		}

		return $this->query_regex;
	}

	/**
	 * Wraps the existing user query to check for the existence of the separation meta keys
	 *
	 * @param string $sql
	 *
	 * @return string
	 */
	protected function add_meta_sql( string $sql ) {
		global $wpdb;

		$meta = new \WP_Meta_Query(
			array(
				'relation' => 'OR',
				array(
					'key'   => WP_Separate_User_Base::SITE_META_KEY,
					'value' => get_current_blog_id(),
				),
				array(
					'key'   => WP_Separate_User_Base::NETWORK_META_KEY,
					'value' => get_current_network_id(),
				),
			)
		);

		$meta_sql = $meta->get_sql( 'user', 'u', 'ID' );

		return sprintf(
			'SELECT * FROM %s WHERE ID IN (
				SELECT u.ID FROM ( %s ) AS u %s %s
			)',
			$wpdb->users,
			$sql,
			$meta_sql['join'],
			$meta_sql['where']
		);
	}

	/**
	 * Adds the necessary query args to WP_User_Query
	 *
	 * @param WP_User_Query $query
	 */
	public function add_user_meta_query( WP_User_Query $query ) {

		// Do not modify queries when on the network admin
		if ( is_network_admin() ) {
			return;
		}

		// Allows the modifications to be disabled by adding the 'wp_sub_disable_query_integration' query arg
		if ( apply_filters( 'wp_sub_disable_query_integration', $query->get( 'wp_sub_disable_query_integration' ), $query ) ) {
			return;
		}

		if ( ! wp_sub_enabled() ) {
			return;
		}

		$user_sep_query = array(
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


		$meta = $query->get( 'meta_query' );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		// Prevent nesting of our own query modifications
		if ( array_key_exists( 'user_separation', $meta ) ) {
			unset( $meta['user_separation'] );
		}

		// Wrap the original query
		if ( ! empty( $meta ) ) {
			$meta = array( 'wp_sub_org_query' => $meta );
		}

		$meta['user_separation'] = $user_sep_query;

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
