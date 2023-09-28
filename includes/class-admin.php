<?php
namespace WP_SUB;

use WP_SUB\WP_Separate_User_Base;
use WP_Network;
use WP_User_Query;

/**
 * Adds WP-Admin functionality
 *
 * Class Admin
 *
 * @package WP_SUB
 */
class Admin {

	/**
	 * @return void
	 */
	public function register_hooks() {
		// Network admin user table integration
		add_filter( 'manage_users-network_columns', array( $this, 'add_network_column' ) );
		add_action( 'manage_users_custom_column', array( $this, 'render_network_column' ), 10, 3 );

		// Site admin user table integration
		add_action( 'load-users.php', array( $this, 'on_user_page_init' ) );

		// Site admin user add table manage site
		add_action( 'edit_user_profile', array( $this, 'manage_user_section' ), 1 );
		add_action( 'show_user_profile', array( $this, 'manage_user_section' ), 1 );
		add_action( 'personal_options_update', array( $this, 'manage_user_section_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'manage_user_section_update' ) );

		// add js file
		add_action( 'admin_enqueue_scripts', array( $this, 'table_enqueue_admin_script' ) );
	}

	public function table_enqueue_admin_script( $hook ) {
		wp_enqueue_script( 'script_table', plugin_dir_url( __FILE__ ) . 'js/table.js', array(), '1.0' );
	}

	public function manage_user_section( $user ) {
		if ( user_can( $user->ID, 'manage_network_users' ) ) {
			$this->manage_site_options( $user );
			$this->manage_network_options( $user );
		}
	}

	/**
	 * @return void
	 */
	protected function manage_site_options( $user ) {
		$all_sites         = get_sites();
		$current_user_site = wp_sub_get_user_sites( $user->ID );
		?>
		<h2><?php esc_html_e( 'Manage Sites', 'wp-separate-user-base' ); ?></h2>
		<?php
		foreach ( $all_sites as $site ) {
			$site->site_name = $site->blogname;
			$site->id        = $site->blog_id;
		}
		$this->create_table( 'site_id', $all_sites, $current_user_site );
	}

	/**
	 * @return void
	 */
	protected function manage_network_options( $user ) {
		$all_networks         = get_networks();
		$current_user_network = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );
		?>
		<h2><?php esc_html_e( 'Manage Networks', 'wp-separate-user-base' ); ?></h2>
		<?php
		$this->create_table( 'network_id', $all_networks, $current_user_network );
	}

	protected function create_table( $name, $all_data, $current_data ) {
		$data_format = array();
		?>
		<div class="wrap">
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select type="<?php echo esc_attr( $name ); ?>" style="min-width: 200px;">
						<?php if ( count( $all_data ) > 0 ) : ?>
							<?php foreach ( $all_data as $data ) : ?>
								<?php $data_format[ $data->id ] = $data; ?>
								<?php if ( ! in_array( (string) $data->id, $current_data, true ) ) : ?>
									<option value="<?php echo esc_attr( $data->id ); ?>">
										<?php echo esc_html( $data->site_name ); ?> <b>(<?php esc_html_e( 'ID', 'wp-separate-user-base' ); ?>: <?php echo esc_html( $data->id ); ?>)</b>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<button type="button" class="add_row button action"><?php esc_html_e( 'Add', 'wp-separate-user-base' ); ?></button>
				</div>
			</div>
			<table class="wp-list-table widefat fixed striped table-view-list posts" style="width:500px;">
				<tbody>
				<?php if ( count( $current_data ) > 0 ) : ?>
					<?php foreach ( $current_data as $data_id ) : ?>
						<tr id="site-<?php echo $data_id; ?>" >
							<td class="title column-title has-row-actions column-primary page-title" data-colname="Site">
								<?php if ( isset( $data_format[ $data_id ] ) ) : ?>
									<?php echo esc_html( $data_format[ $data_id ]->site_name ); ?> (<?php esc_html_e( 'ID', 'wp-separate-user-base' ); ?>: <?php echo esc_html( $data_format[ $data_id ]->id ); ?>)
								<?php else : ?>
									Site <?php echo esc_html( $data_id ); ?>
								<?php endif; ?>
							</td>
							<td class="action-remove-site" style="width: 80px;" >
								<input class="input-text" type="hidden" value="<?php echo esc_attr( $data_id ); ?>" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $data_id ); ?>]" />
								<button type="button" class="button action">
									<span class="btn-remove-site"><?php esc_html_e( 'Remove', 'wp-separate-user-base' ); ?></span>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}


	/**
	 * @param $user_id
	 * @return void
	 */
	public function manage_user_section_update( $user_id ) {
		if ( user_can( $user_id, 'manage_network_users' ) ) {

			$this->manage_site_update( $user_id );
			$this->manage_network_update( $user_id );
		}
	}

	protected function manage_site_update( $user_id ) {
		$current_user_site = wp_sub_get_user_sites( (int) $user_id );

		$site_ids = array();
		if ( isset( $_POST['site_id'] ) ) {
			$site_ids = $_POST['site_id'];
		}
		if ( ! empty( $current_user_site ) ) {
			foreach ( $current_user_site as $site_id ) {
				if ( ! in_array( $site_id, $site_ids, true ) ) {
					wp_sub_remove_user_from_site( $user_id, (int) $site_id );
				}
			}
		}
		if ( ! empty( $site_ids ) ) {
			foreach ( $site_ids as $site_id ) {
				if ( ! in_array( $site_id, $current_user_site, true ) ) {
					wp_sub_add_user_to_site( $user_id, (int) $site_id );
				}
			}
		}
	}

	protected function manage_network_update( $user_id ) {
		$current_user_network = get_user_meta( $user_id, WP_Separate_User_Base::NETWORK_META_KEY, false );

		$network_ids = array();
		if ( isset( $_POST['network_id'] ) ) {
			$network_ids = $_POST['network_id'];
		}
		if ( ! empty( $current_user_network ) ) {
			foreach ( $current_user_network as $network_id ) {
				if ( ! in_array( $network_id, $network_ids, true ) ) {
					wp_sub_remove_user_from_network( $user_id, (int) $network_id );
				}
			}
		}
		if ( ! empty( $network_ids ) ) {
			foreach ( $network_ids as $network_id ) {
				if ( ! in_array( $network_id, $current_user_network, true ) ) {
					wp_sub_add_user_to_network( $user_id, (int) $network_id );
				}
			}
		}
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

		add_action( 'users_list_table_query_args', array( $this, 'users_list_table_query_args' ) );
		add_filter( 'views_users', array( $this, 'views_users' ) );
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
			array(
				'blog_id'     => 0,
				'number'      => 1,
				'count_total' => true,
				'fields'      => 'ids',
			)
		);

		$views['all'] = preg_replace(
			'/\(\d+\)/',
			sprintf( '(%d)', $user_query->get_total() ),
			$views['all']
		);

		return $views;
	}

}
