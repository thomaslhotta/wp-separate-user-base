<?php
namespace WP_SUB;

use WP_SUB\WP_Separate_User_Base;
use WP_Network;
use WP_User_Query;

/**
 * Adds WP-Admin functionality
 *
 * Class Admin
 * @package WP_SUB
 */
class Admin {

	public function register_hooks() {
		// Network admin user table integration
		add_filter( 'manage_users-network_columns', array( $this, 'add_network_column' ) );
		add_action( 'manage_users_custom_column', array( $this, 'render_network_column' ), 10, 3 );

		// Site admin user table integration
		add_action( 'load-users.php', [ $this, 'on_user_page_init' ] );
	}

	public function add_network_column( array $columns ) {
		add_filter( 'get_blogs_of_user', array( $this, 'get_blogs_of_user' ), 10, 3 );

		$columns['network'] = __( 'Network' );
		return $columns;
	}

	public function get_blogs_of_user( $sites, $user_id, $all ) {
		$user_site_ids = get_user_meta( $user_id, WP_Separate_User_Base::SITE_META_KEY, false );

		$sites_in_list = wp_list_pluck( $sites, 'userblog_id' );

		$sites_to_add = array_diff( $user_site_ids, $sites_in_list );
		if ( empty( $sites_to_add ) ) {
			return $sites;
		}

		$args = array(
			'number'   => '',
			'site__in' => array_diff( $user_site_ids, $sites_in_list ),
		);

		if ( ! $all ) {
			$args['archived'] = 0;
			$args['spam']     = 0;
			$args['deleted']  = 0;
		}


		foreach ( get_sites( $args ) as $site ) {
			$sites[ $site->id ] = (object) array(
				'userblog_id' => $site->id,
				'blogname'    => $site->blogname,
				'domain'      => $site->domain,
				'path'        => $site->path,
				'site_id'     => $site->network_id,
				'siteurl'     => $site->siteurl,
				'archived'    => $site->archived,
				'mature'      => $site->mature,
				'spam'        => $site->spam,
				'deleted'     => $site->deleted,
			);
		}

		return $sites;

	}

	public function render_network_column( $content, $column_name, $user_id ) {
		if ( 'network' !== $column_name ) {
			return $content;
		}

		$networks = get_user_meta( $user_id, WP_Separate_User_Base::NETWORK_META_KEY, false );
		if ( empty( $networks ) ) {
			return '';
		}

		$network_links = '';
		foreach ( $networks as $network_id ) {
			$network = get_network( $network_id );
			if ( $network instanceof WP_Network ) {
				$network_links .= sprintf(
					'<li><a href="%s">%s</a></li>',
					esc_url( set_url_scheme( 'http://' . $network->domain . $network->path ) ),
					esc_html( $network->site_name )
				);
			} else {
				$network_links .= sprintf(
					'<li>Network with ID %d does not exist</li>',
					$network_id
				);
			}
		}

		return sprintf( '<ul>%s</ul>', $network_links );
	}

	/**
	 * Makes user table show all users of the current site. Only takes effect if users are not automatically added to
	 * the current network.
	 */
	public function on_user_page_init() {
		// This is only needed if users are not added to the network automatically.
		if ( get_network_option( get_current_site()->id, 'wp_sub_add_users_to_network' ) ) {
			return;
		}

		add_action( 'users_list_table_query_args', [ $this, 'users_list_table_query_args' ] );
		add_filter( 'views_users', [ $this, 'views_users' ] );
	}

	/**
	 * Remove the blog_id limitation on the 'All' view.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function users_list_table_query_args( $args ) {
		if ( empty( $args['role'] ) ) {
			// Is is safe to remove the blog_id arg because users are already limited to the current site by the
			// injected meta query
			$args['blog_id'] = 0;
		}

		return $args;
	}

	/**
	 * Modifies the number in the 'All' tab to show all users of the current site.
	 *
	 * @param array $views
	 *
	 * @return array
	 */
	public function views_users( array $views ) {
		$user_query = new WP_User_Query(
			[
				'blog_id'     => 0,
				'number'      => 1,
				'count_total' => true,
				'fields'      => 'ids',
			]
		);

		$views['all'] = preg_replace(
			'/\(\d+\)/',
			sprintf( '(%d)', $user_query->get_total() ),
			$views['all']
		);

		return $views;
	}

}
