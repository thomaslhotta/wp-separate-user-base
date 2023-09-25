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
		add_action( 'edit_user_profile', array( $this, 'manage_site_options' ), 1 );
		add_action( 'show_user_profile', array( $this, 'manage_site_options' ), 1 );
		add_action( 'personal_options_update', array( $this, 'manage_site_options_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'manage_site_options_update' ) );

		// add js file
		add_action( 'admin_enqueue_scripts', array( $this, 'table_enqueue_admin_script' ) );
	}

	public function table_enqueue_admin_script( $hook ) {
		wp_enqueue_script( 'script_table', plugin_dir_url( __FILE__ ) . 'js/table.js', array(), '1.0' );
	}

	/**
	 * @return void
	 */
	public function manage_site_options( $user ) {
		if ( ! user_can( $user->ID, 'manage_network_users' ) ) {
			return;
		}
		$all_sites         = get_sites();
		$current_user_site = wp_sub_get_user_sites( $user->ID );
		$data_site_format  = array();
		?>
		<h2><?php _e( 'Manage Sites', 'wp-separate-user-base' ); ?></h2>
		<div class="wrap">
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="add_site" id="action-selector-site" style="min-width: 200px;">
						<?php if ( count( $all_sites ) > 0 ) : ?>
							<?php foreach ( $all_sites as $site ) : ?>
								<?php $data_site_format[ $site->blog_id ] = $site; ?>
								<?php if ( ! in_array( $site->blog_id, $current_user_site, true ) ) : ?>
									<option value="<?php echo esc_html( $site->blog_id ); ?>">
										<?php echo esc_html( $site->blogname ); ?> <b>(ID: <?php echo esc_html( $site->blog_id ); ?>)</b>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php else : ?>
							<option ><?php _e( 'Empty site', 'wp-separate-user-base' ); ?></option>
						<?php endif; ?>
					</select>
					<button type="button" id="add_site" class="button action"><?php _e( 'Add', 'wp-separate-user-base' ); ?></button>
				</div>
			</div>
			<table id="list_site" class="wp-list-table widefat fixed striped table-view-list posts" style="width:500px;">
				<tbody id="the-list">
				<?php if ( count( $current_user_site ) > 0 ) : ?>
					<?php foreach ( $current_user_site as $site_id ) : ?>
						<tr id="site-<?php echo $site_id; ?>" >
							<td class="title column-title has-row-actions column-primary page-title" data-colname="Site">
								<?php if ( isset( $data_site_format[ $site_id ] ) ) : ?>
									<?php echo esc_html( $data_site_format[ $site_id ]->blogname ); ?> (ID: <?php echo esc_html( $data_site_format[ $site_id ]->blog_id ); ?>)
								<?php else : ?>
									Site <?php echo esc_html( $site_id ); ?>
								<?php endif; ?>
							</td>
							<td class="action-remove-site" style="width: 80px;" >
								<input class="input-text" type="hidden" value="<?php echo esc_html( $site_id ); ?>" name="site_id[<?php echo esc_html( $site_id ); ?>]" />
								<button type="button" class="button action">
									<span class="btn-remove-site"><?php _e( 'Remove', 'wp-separate-user-base' ); ?></span>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="2"><?php _e( 'Empty site', 'wp-separate-user-base' ); ?></td>
					</tr>
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
	public function manage_site_options_update( $user_id ) {
		if ( user_can( $user_id, 'manage_network_users' ) ) {

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
