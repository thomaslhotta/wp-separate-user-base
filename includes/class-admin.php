<?php
namespace WP_SUB;

use WP_Network;
use WP_User_Query;
use WP_Site;
use WP_User;

/**
 * Adds WP-Admin functionality
 *
 * Class Admin
 *
 * @package WP_SUB
 */
class Admin {

	/**
	 * The name used for the POST request variable containing the site ids.
	 */
	const POST_SITE_IDS = 'wp_sub_site_ids';

	/**
	 * The name used for the POST request variable containing the network ids.
	 */
	const POST_NETWORK_IDS = 'wp_sub_network_ids';

	/**
	 * Stores the last user id passed from ms_user_list_site_class filter for reuse in subsequent filters.
	 *
	 * @var null
	 */
	protected $last_user_id = null;

	/**
	 * Register the hooks for the admin functionality.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Adds functionality to edit user sites and networks.
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'wp_ajax_wp_sub_render_table_sites', array( $this, 'ajax_render_table_sites' ) );
		add_action( 'wp_ajax_wp_sub_render_table_network', array( $this, 'ajax_render_table_networks' ) );

		// Network admin user table integration.
		add_filter( 'manage_users-network_columns', array( $this, 'add_network_column' ) );
		add_action( 'manage_users_custom_column', array( $this, 'render_network_column' ), 10, 3 );

		// Site admin user table integration.
		add_action( 'load-users.php', array( $this, 'on_user_page_init' ) );

		add_action( 'user_new_form', array( $this, 'user_new_form' ) );

		add_action( 'check_admin_referer', array( $this, 'check_admin_referer' ), 10, 2 );
	}

	public function admin_init() {
		add_action( 'edit_user_profile', array( $this, 'manage_user_section' ), 1 );
		add_action( 'show_user_profile', array( $this, 'manage_user_section' ), 1 );
		add_action( 'personal_options_update', array( $this, 'manage_user_section_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'manage_user_section_update' ) );
	}

	/**
	 * Register the admin scripts.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts(): void {
		wp_register_script( 'htmx', plugin_dir_url( __FILE__ ) . 'js/htmx.min.js', array(), '1.9.10' );
		wp_register_style( 'wp-sub-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), '1.0.0' );
		wp_enqueue_style( 'wp-sub-admin' );
	}

	/**
	 * Renders the user sites and networks section in the user profile.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return void
	 */
	public function manage_user_section( WP_User $user ): void {
		if ( current_user_can( 'manage_network_users' ) ) {
			wp_enqueue_script( 'htmx' );
			?>
			<h2><?php esc_html_e( 'Manage Userbase', 'wp-separate-user-base' ); ?></h2>
			<table class="form-table">
			<?php
			$this->manage_site_options( $user );
			$this->manage_network_options( $user );
			?>
			</table>
			<?php
		}
	}

	/**
	 * Renders the user sites section in the user profile.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return void
	 */
	protected function manage_site_options( WP_User $user ): void {
		?>
		<tr>
			<th scope="row">
				<label >
					<?php esc_html_e( 'Sites' ); ?>
				</label>
			</th>
			<td>
				<?php
				$this->create_sites_table( wp_sub_get_user_sites( $user->ID ) ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the user networks section in the user profile.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return void
	 */
	protected function manage_network_options( WP_User $user ): void {

		?>
		<tr>
			<th scope="row">
				<label >
					<?php esc_html_e( 'Networks', 'wp-separate-user-base' ); ?>
				</label>
			</th>
			<td>
				<?php
				$this->create_networks_table( wp_sub_get_user_networks( $user->ID ) ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the site list for use with admin-ajax.php.
	 *
	 * @return void
	 */
	public function ajax_render_table_sites(): void {
		if ( ! current_user_can( 'manage_network_users' ) ) {
			wp_die();
		}

		$user_site_ids = $this->calculate_id_list( self::POST_SITE_IDS );

		$this->create_sites_table( $user_site_ids );

		wp_die();
	}


	/**
	 * Renders the network list for use with admin-ajax.php.
	 *
	 * @return void
	 */
	public function ajax_render_table_networks(): void {
		if ( ! current_user_can( 'manage_network_users' ) ) {
			wp_die();
		}

		$user_network_ids = $this->calculate_id_list( self::POST_NETWORK_IDS );

		$this->create_networks_table( $user_network_ids );

		wp_die();
	}

	/**
	 * Calculates a list of ids based on IDS in the POST request.
	 *
	 * The list is calculated by taking the list of ids from the POST request variable with the given name, adding the
	 * value from the POST request variable with the given name and the suffix '-add', and removing the value from the
	 * POST request variable with the given name and the suffix '-delete'.
	 *
	 * @param string $name The name of the POST request variable.
	 *
	 * @return int[] The list of ids.
	 */
	protected function calculate_id_list( string $name ): array {
		$user_site_ids   = wp_parse_id_list(
			filter_input( INPUT_POST, $name, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY )
		);
		$user_site_ids[] = filter_input( INPUT_POST, $name . '-add', FILTER_VALIDATE_INT );
		$user_site_ids   = array_filter( $user_site_ids );

		$user_site_ids = array_diff(
			$user_site_ids,
			array(
				filter_input( INPUT_POST, $name . '-delete', FILTER_VALIDATE_INT ),
			)
		);

		return wp_parse_id_list( $user_site_ids );
	}

	/**
	 * Creates the users sites table.
	 *
	 * @param array $user_site_ids The ids of the sites the user is a member of.
	 *
	 * @return void
	 */
	protected function create_sites_table( array $user_site_ids ): void {
		$all_sites = get_sites(
			array(
				'number'       => 0,
				'site__not_in' => $user_site_ids,
			)
		);

		$user_sites = [];
		if ( ! empty( $user_site_ids ) ) {
			$user_sites = get_sites(
				[
					'number'   => 0,
					'site__in' => $user_site_ids,
				]
			);
		}

		$all_sites  = array_map( [ $this, 'site_to_array' ], $all_sites );
		$user_sites = array_map( [ $this, 'site_to_array' ], $user_sites );
		$this->create_table( 'wp_sub_render_table_sites', self::POST_SITE_IDS, $all_sites, $user_sites );
	}

	/**
	 * Creates the users networks table.
	 *
	 * @param array $user_network_ids The ids of the networks the user is a member of.
	 *
	 * @return void
	 */
	protected function create_networks_table( array $user_network_ids ): void {
		$all_networks = get_networks(
			array(
				'number'          => 0,
				'network__not_in' => $user_network_ids,
			)
		);

		$user_networks = [];
		if ( ! empty( $user_network_ids ) ) {
			$user_networks = get_networks(
				[
					'number'      => 0,
					'network__in' => $user_network_ids,
				]
			);
		}

		$all_networks  = array_map( [ $this, 'network_to_array' ], $all_networks );
		$user_networks = array_map( [ $this, 'network_to_array' ], $user_networks );
		$this->create_table( 'wp_sub_render_table_network', self::POST_NETWORK_IDS, $all_networks, $user_networks );
	}

	/**
	 * Renders a table for adding and removing sites or networks.
	 *
	 * @param string $action       The ajax action name.
	 * @param string $name         The variable name for the ids.
	 * @param array  $all_data     The data for all sites or networks.
	 * @param array  $current_data The data for the sites or networks the user is a member of.
	 *
	 * @return void
	 */
	protected function create_table( string $action, string $name, array $all_data, array $current_data ): void {
		$ajax_url = add_query_arg(
			array(
				'action' => $action,
			),
			admin_url( 'admin-ajax.php' )
		);
		$ajax_url = wp_nonce_url( $ajax_url, $action );

		$hx_params_add = sprintf(
			'%1$s[],%1$s-add',
			$name
		);

		$hx_params_delete = sprintf(
			'%1$s[],%1$s-delete',
			$name
		);

		?>
		<div class="wp-sub-table" hx-target="this" hx-swap="outerHTML">
			<?php if ( $all_data ) : ?>
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="<?php echo esc_attr( $name ); ?>-add">
						<?php foreach ( $all_data as $site ) : ?>
							<option value="<?php echo esc_attr( $site['id'] ); ?>">
								<?php echo esc_html( sprintf( '%d - %s', $site['id'], $site['name'] ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button hx-post="<?php echo esc_attr( $ajax_url ); ?>"
							hx-params="<?php echo esc_attr( $hx_params_add ); ?>"
							type="button" class="add_row button action">
						<?php esc_html_e( 'Add'/*, 'wp-separate-user-base'*/ ); ?>
					</button>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $current_data ) : ?>
			<table class="wp-list-table widefat striped ">
				<thead>
					<tr>
						<th>ID</th>
						<th><?php esc_html_e( 'Name' ); ?></th>
						<th><?php esc_html_e( 'Action' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $current_data as $site ) : ?>
						<tr >
							<td class="title column-title has-row-actions column-primary page-title" data-colname="Site">
								<?php echo esc_html( $site['id'] ); ?>
							</td>
							<td>
								<?php echo esc_html( $site['name'] ); ?>
							</td>
							<td class="action-remove-site" style="width: 80px;" >
								<input type="hidden" value="<?php echo esc_attr( $site['id'] ); ?>" name="<?php echo esc_attr( $name ); ?>[]" />
								<button hx-post="<?php echo esc_attr( $ajax_url ); ?>"
										hx-params="<?php echo esc_attr( $hx_params_delete ); ?>"
										type="button"
										value="<?php echo esc_attr( $site['id'] ); ?>"
										name="<?php echo esc_attr( $name ); ?>-delete"
										class="button button-link">
									<span ><?php esc_html_e( 'Remove'/*, 'wp-separate-user-base'*/ ); ?></span>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Converts a site to an array.
	 *
	 * @param WP_Site $site The site to convert.
	 *
	 * @return array
	 */
	protected function site_to_array( WP_Site $site ): array {
		return array(
			'id'   => $site->id,
			'name' => $site->blogname,
		);
	}

	/**
	 * Converts a network to an array.
	 *
	 * @param WP_Network $network The network to convert.
	 *
	 * @return array
	 */
	protected function network_to_array( WP_Network $network ): array {
		return array(
			'id'   => $network->id,
			'name' => $network->site_name,
		);
	}


	/**
	 * Handles updating the user's sites and networks.
	 *
	 * @param int|string $user_id The user ID to update.
	 *
	 * @return void
	 */
	public function manage_user_section_update( int|string $user_id ): void {
		if ( current_user_can( 'manage_network_users' ) ) {
			$user_id = intval( $user_id );
			$this->update_site_ids( $user_id );
			$this->manage_network_update( $user_id );
		}
	}

	/**
	 * Handles updating the user's sites.
	 *
	 * @param int $user_id The user ID to update.
	 *
	 * @return void
	 */
	protected function update_site_ids( int $user_id ): void {
		$current_user_sites = wp_sub_get_user_sites( $user_id );

		$site_ids = $this->get_id_array_from_post( self::POST_SITE_IDS );

		foreach ( array_diff( $current_user_sites, $site_ids ) as $site_id_to_remove ) {
			wp_sub_remove_user_from_site( $user_id, $site_id_to_remove );
		}

		foreach ( array_diff( $site_ids, $current_user_sites ) as $site_id_to_add ) {
			wp_sub_add_user_to_site( $user_id, $site_id_to_add );
		}
	}

	/**
	 * Handles updating the user's networks.
	 *
	 * @param int $user_id The user ID to update.
	 *
	 * @return void
	 */
	protected function manage_network_update( int $user_id ): void {
		$current_user_networks = wp_sub_get_user_networks( $user_id );

		$network_ids = $this->get_id_array_from_post( self::POST_NETWORK_IDS );

		foreach ( array_diff( $current_user_networks, $network_ids ) as $id_to_remove ) {
			wp_sub_remove_user_from_network( $user_id, $id_to_remove );
		}

		foreach ( array_diff( $network_ids, $current_user_networks ) as $id_to_add ) {
			wp_sub_add_user_to_network( $user_id, $id_to_add );
		}
	}

	/**
	 * Returns the ids from the POST request.
	 *
	 * @param string $name The name of the POST request variable.
	 *
	 * @return array The ids.
	 */
	public function get_id_array_from_post( string $name ): array	{
		$ids = wp_parse_id_list(
			filter_input(
				INPUT_POST,
				$name,
				FILTER_DEFAULT,
				FILTER_REQUIRE_ARRAY
			)
		);

		return array_filter( $ids );
	}

	/**
	 * Adds a network column to the user table in the network admin.
	 *
	 * @param array $columns The columns of the user table.
	 *
	 * @return array The modified columns.
	 */
	public function add_network_column( array $columns ): array {
		add_filter( 'get_blogs_of_user', array( $this, 'get_blogs_of_user' ), 10, 3 );

		$columns['network'] = __( 'Network' );
		return $columns;
	}

	public function get_blogs_of_user( $sites, $user_id, $all ) {
		$user_site_ids = wp_sub_get_user_networks( (int) $user_id );

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

	/**
	 * Renders the networks column in the user table.
	 *
	 * @param string $content     Any existing content for the column.
	 * @param string $column_name The name of the column.
	 * @param int    $user_id     The ID of the user.
	 *
	 * @return string The content of the column.
	 */
	public function render_network_column( string $content, string $column_name, int $user_id ): string {
		if ( 'network' !== $column_name ) {
			return $content;
		}

		$networks = wp_sub_get_user_networks( $user_id );
		if ( empty( $networks ) ) {
			return '';
		}

		$network_links = '';
		foreach ( $networks as $network_id ) {
			$network = get_network( $network_id );
			if ( $network instanceof WP_Network ) {
				$network_links .= sprintf(
					'<li><a href="%s">%s</a></li>',
					esc_url( set_url_scheme( 'https://' . $network->domain . $network->path ) ),
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
	 *
	 * @return void
	 */
	public function on_user_page_init(): void {
		// This is only needed if users are not added to the network automatically.
		if ( get_network_option( get_current_site()->id, 'wp_sub_add_users_to_network' ) ) {
			return;
		}

		add_action( 'users_list_table_query_args', array( $this, 'users_list_table_query_args' ) );
		add_filter( 'views_users', array( $this, 'views_users' ) );
		add_filter( 'ms_user_list_site_class', array( $this, 'ms_user_list_site_class' ), 10, 4 );
		add_filter( 'ms_user_list_site_actions', array( $this, 'ms_user_list_site_actions' ),10, 2 );
	}

	/**
	 * Remove the blog_id limitation on the 'All' view.
	 *
	 * @param array $args Arguments for the user query.
	 *
	 * @return array
	 */
	public function users_list_table_query_args( array $args ): array {
		if ( empty( $args['role'] ) ) {
			// It is safe to remove the blog_id arg because users are already limited to the current site by the
			// injected meta query.
			$args['blog_id'] = 0;
		}

		return $args;
	}

	/**
	 * Modifies the number in the 'All' tab to show all users of the current site.
	 *
	 * @param array $views The views of the user table.
	 *
	 * @return array
	 */
	public function views_users( array $views ): array {
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


	/**
	 * Stores the user id of the current row.
	 *
	 * @param array   $classes    The classes for the blog. Passed through from the filter.
	 * @param int     $site_id    Not used
	 * @param int     $network_id Not used
	 * @param WP_User $user       The current user.
	 *
	 * @return array
	 */
	public function ms_user_list_site_class( array $classes, int $site_id, int $network_id, WP_User $user ): array {
		unset( $site_id, $network_id );

		$this->last_user_id = $user->ID;
		return $classes;
	}

	/**
	 * Adds a link to edit the user on the site.
	 *
	 * @param array $actions All row actions.
	 * @param int   $site_id The site id.
	 *
	 * @return array
	 */
	public function ms_user_list_site_actions( array $actions, int $site_id ): array {
		if ( $this->last_user_id ) {
			$actions['edit_user'] = sprintf(
				'<a href="%s">%s</a>',
				get_admin_url( $site_id, 'user-edit.php?user_id=' . $this->last_user_id ),
				esc_html__( 'Edit User' )
			);
		}

		return $actions;
	}

	/**
	 * Adds a checkbox to the new user for to add the user to the site automatically.
	 *
	 * @param string $form The form name.
	 *
	 * @return void
	 */
	public function user_new_form( string $form ) {
		if ( 'add-existing-user' !== $form ) {
			return;
		}
		?>
		<table class="form-table">
		<tr>
			<th scope="row">
				<label>
					<?php esc_html_e( 'Add to site\'s user base', 'wp-separate-user-base' ); ?>
				</label>
			</th>
			<td>
				<input type="checkbox" name="wp-sub-add-user" id="wp-sub-add-user" value="1" />
				<label for="wp-sub-add-user">
					<?php esc_html_e( 'Add user to this site\'s user base', 'wp-separate-user-base' ); ?>
				</label>
				<p class="description">
					<?php
					esc_html_e(
						'To add a user to this site, you must hold the necessary privileges on any site where the user has previously been added.',
						'wp-separate-user-base'
					)
					?>
				</p>
			</td>
		</tr>
		</table>
		<?php
	}

	/**
	 * Allows adding a user to the current site's user base when adding a user in the admin.
	 *
	 * @param string $action The current action being checked. We only process the 'add-user' action.
	 * @param bool   $result The result of the nonce check. We only process the case where the nonce check was successful.
	 *
	 * @return void
	 */
	public function check_admin_referer( string $action, bool $result ): void {
		// Only run on the user-new.php or network-admin/site-users.php page.
		if ( 'add-user' !== $action || ! $result ) {
			return;
		}

		// Only run if the user is being added to the current site.
		if ( empty( $_REQUEST['_wpnonce_add-user'] ) ) {
			return;
		}

		// Only run if the admin has selected to correct option. For network admins, the option is always selected as
		// we have no way of injecting markup into the form.
		if ( empty( $_POST['wp-sub-add-user'] && ! is_network_admin() ) ) {
			return;
		}

		// Only run if the admin has the necessary privileges.
		if ( ! current_user_can( 'manage_network_users' ) ) {
			return;
		}

		add_action( 'wp_sub_user_exists_on_site', '__return_true' );

		if ( is_network_admin() ) {
			$user = get_user_by( 'login', $_POST['user_login'] );
		} else {
			$user = get_user_by( 'login', $_POST['email'] );
		}

		remove_action( 'wp_sub_user_exists_on_site', '__return_true' );

		if ( $user instanceof WP_User && ! wp_sub_user_exists_on_network( $user->ID, get_current_network_id() ) ) {
			wp_sub_add_user_to_site( $user->ID, get_current_blog_id() );
		}
	}
}

