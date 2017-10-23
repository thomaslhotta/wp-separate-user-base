<?php
namespace WP_SUB;

use WP_SUB\WP_Separate_User_Base,
	WP_Network;

class Admin {

	public function register_hooks() {
		add_filter( 'manage_users-network_columns', array( $this, 'add_network_column' ) );
		add_action( 'manage_users_custom_column', array( $this, 'render_network_column' ), 10, 3 );
	}

	public function add_network_column( array $columns ) {
		$columns['network'] = __( 'Network' );
		return $columns;
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
