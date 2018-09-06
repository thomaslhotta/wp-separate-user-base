<?php
namespace WP_SUB;

use WP_SUB\WP_Separate_User_Base,
	WP_Network;

/**
 * Adds WP-Admin functionality
 *
 * Class Admin
 * @package WP_SUB
 */
class Admin {

	public function register_hooks() {
		add_filter( 'manage_users-network_columns', array( $this, 'add_network_column' ) );
		add_action( 'manage_users_custom_column', array( $this, 'render_network_column' ), 10, 3 );
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

}
