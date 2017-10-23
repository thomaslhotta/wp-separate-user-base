<?php
namespace WP_SUB;

use WP_SUB\WP_Separate_User_Base,
	WP_CLI,
	WP_CLI_Command,
	WP_Network,
	WP_Site,
	WP_User;

/**
 * Handles user separation between networks and sites
 *
 * @package WP_SUB
 */
class WP_SUP_CLI extends WP_CLI_Command {

	/**
	 * Adds a user to a network
	 *
	 * ## OPTIONS
	 *
	 * <network_id>
	 * : The network ID
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * ## Examples
	 *
	 *     # Add user 5 to network 1
	 *     $ wp separate-user-base add-user-to-network 1 5
	 *
	 * @subcommand add-user-to-network
	 * @param $args
	 * @param $assoc_args
	 */
	public function add_user_to_network( $args, $assoc_args ) {
		$network = $this->get_network( $args[0] );
		$user = $this->get_user( $args[1] );

		$networks = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );
		if ( in_array( $network->id, $networks ) ) {
			$this->error( 'User %d already exists in network %s', $user->ID, $this->format_network_name( $network ) );
		}

		if ( add_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, $network->id ) ) {
			$this->error( 'Adding user %d to network %s failed', $user->ID, $this->format_network_name( $network ) );
		} else {
			$this->error( 'Adding user %d to network %s failed', $user->ID, $this->format_network_name( $network ) );
		}
	}

	/**
	 * Removes a user form a network
	 *
	 * ## OPTIONS
	 *
	 * <network_id>
	 * : The network ID
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * ## Examples
	 *
	 *     # Removes user 5 from network 1
	 *     $ wp separate-user-base remove-user-from-network 1 5
	 *
	 * @subcommand remove-user-from-network
	 * @param $args
	 * @param $assoc_args
	 */
	public function remove_user_from_network( $args, $assoc_args ) {
		$network = $this->get_network( $args[0] );
		$user = $this->get_user( $args[1] );

		$networks = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );
		if ( ! in_array( $network->id, $networks ) ) {
			$this->error( 'User %d does not exist in network %s', $user->ID, $this->format_network_name( $network ) );
		}

		if ( delete_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, $network->id ) ) {
			$this->success( 'Deleted user %d from network %s', $user->ID, $this->format_network_name( $network ) );
		} else {
			$this->error( 'Deleting user %d from network %s failed', $user->ID, $this->format_network_name( $network ) );
		}
	}

	/**
	 * Adds a user to a network
	 *
	 * ## OPTIONS
	 *
	 * <site_id>
	 * : The site ID
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * ## Examples
	 *
	 *     # Add user 5 to site 1
	 *     $ wp separate-user-base add-user-to-site 1 5
	 *
	 * @subcommand add-user-to-site
	 * @param $args
	 * @param $assoc_args
	 */
	public function add_user_to_site( $args, $assoc_args ) {
		$site = $this->get_site( $args[0] );
		$user = $this->get_user( $args[1] );

		$sites = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );
		if ( in_array( $site->id, $sites ) ) {
			$this->error( 'User %d already exists in site %s', $user->ID, $this->format_site_name( $site ) );
		}

		if ( add_user_meta( $user->ID, WP_Separate_User_Base::SITE_META_KEY, $site->id ) ) {
			$this->success( 'Added user %d to site %s', $user->ID, $this->format_site_name( $site ) );
		} else {
			$this->error( 'Adding user %d to site %s failed', $user->ID, $this->format_site_name( $site ) );
		}

	}

	/**
	 * Removes a user form a site
	 *
	 * ## OPTIONS
	 *
	 * <site_id>
	 * : The site ID
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * ## Examples
	 *
	 *     # Removes user 5 from site 1
	 *     $ wp separate-user-base remove-user-from-site 1 5
	 *
	 * @subcommand remove-user-from-site
	 * @param $args
	 * @param $assoc_args
	 */
	public function remove_user_from_site( $args, $assoc_args ) {
		$site = $this->get_site( $args[0] );
		$user = $this->get_user( $args[1] );

		$sites = get_user_meta( $user->ID, WP_Separate_User_Base::SITE_META_KEY, false );
		if ( ! in_array( $site->id, $sites ) ) {
			$this->error( 'User %d does not exist on site %s', $user->ID, $this->format_site_name( $site ) );
		}

		if ( delete_user_meta( $user->ID, WP_Separate_User_Base::SITE_META_KEY, $site->id ) ) {
			$this->success( 'Removed user %d from site %s', $user->ID, $this->format_site_name( $site ) );
		} else {
			$this->error( 'Removing user %d from site %s failed', $user->ID, $this->format_site_name( $site ) );
		}
	}

	/**
	 * Lists the networks a user exists on.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * ## Examples
	 *
	 *     # Lists the networks of user 5.
	 *     $ wp separate-user-base list-user-networks 5
	 *
	 * @subcommand list-user-networks
	 * @param $args
	 * @param $assoc_args
	 */
	public function list_user_networks( $args, $assoc_args ) {
		$user = $this->get_user( $args[0] );
		$networks = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );

		$table = $table = new \cli\Table();
		$table->setHeaders( array( 'ID', 'Name', 'URL' ) );
		foreach ( $networks as $n_id ) {
			$n = get_network( $n_id );
			if ( $n instanceof WP_Network ) {
				$table->addRow( array( $n->id, $n->site_name, $n->domain . $n->path ) );
			} else {
				$table->addRow( array( $n_id, 'Network not found', '' ) );
			}
		}

		$table->display();
	}

	/**
	 * Lists the sites a user exists on.
	 *
	 * ## OPTIONS
	 *
	 * <user_id>
	 * : The user ID
	 *
	 * ## Examples
	 *
	 *     # Lists the sites of user 5.
	 *     $ wp separate-user-base list-user-sites 5
	 *
	 * @subcommand list-user-sites
	 * @param $args
	 * @param $assoc_args
	 */
	public function list_user_sites( $args, $assoc_args ) {
		$user = $this->get_user( $args[0] );
		$sites = get_user_meta( $user->ID, WP_Separate_User_Base::SITE_META_KEY, false );

		$table = $table = new \cli\Table();
		$table->setHeaders( array( 'ID', 'Name', 'URL', 'User role' ) );
		foreach ( $sites as $id ) {
			$s = get_site( $id );
			if ( $s instanceof WP_Site ) {
				$user->for_blog( $s->id );
				$table->addRow( array( $s->id, $s->blogname, get_home_url( $s->id ), join( ', ', $user->roles ) ) );
			} else {
				$table->addRow( array( $id, 'Site not found', '' ) );
			}
		}

		$table->display();
	}

	/**
	 * Enables network registration for the given network.
	 *
	 * ## OPTIONS
	 *
	 * <network_id>
	 * : The network ID
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand enable-network-registration
	 */
	public function enable_network_registration( $args, $assoc_args ) {
		$network = $this->get_network( $args[0] );

		if ( update_network_option( $network->id, 'wp_sub_add_users_to_network', 1 ) ) {
			$this->success( 'Enabled network registration for %s', $this->format_network_name( $network ) );
		} else {
			$this->error( 'Enabling network registration for network %s failed', $this->format_network_name( $network ) );
		}
	}

	/**
	 * Disables network registration for the given network.
	 *
	 * ## OPTIONS
	 *
	 * <network_id>
	 * : The network ID
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand disable-network-registration
	 */
	public function disable_network_registration( $args, $assoc_args ) {
		$network = $this->get_network( $args[0] );

		if ( delete_network_option( $network->id, 'wp_sub_add_users_to_network' ) ) {
			$this->success( 'Disabled network registration for network %s', $this->format_network_name( $network ) );
		} else {
			$this->error( 'Disabling network registration for network %s failed', $this->format_network_name( $network ) );
		}
	}

	/**
	 * Created a CLI success message
	 */
	protected function success() {
		WP_CLI::success( call_user_func_array( 'sprintf', func_get_args() ) );
	}

	/**
	 * Created a CLI error
	 */
	protected function error() {
		WP_CLI::error( call_user_func_array( 'sprintf', func_get_args() ) );
	}

	/**
	 * Returns the network for the given ID or triggers a CLI error
	 *
	 * @param array $args
	 *
	 * @return WP_Network
	 */
	protected function get_network( int $id ) {
		$network = get_network( $id );
		if ( ! $network instanceof WP_Network ) {
			$this->error( 'Network with ID %d not found', $id );
		}

		return $network;
	}

	/**
	 * Returns the formatted network name
	 *
	 * @param WP_Network $network
	 *
	 * @return string
	 */
	protected function format_network_name( WP_Network $network ) {
		return sprintf( '"%s" (%d)', $network->site_name, $network->id );
	}

	/**
	 * @param int $id
	 *
	 * @return null|WP_Site
	 */
	protected function get_site( int $id ) {
		$site = get_site( $id );
		if ( ! $site instanceof WP_Site ) {
			$this->error( 'Site with ID %d not found', $id );
		}

		return $site;
	}

	/**
	 * Returns the formatted site name
	 *
	 * @param WP_Site $site
	 *
	 * @return string
	 */
	protected function format_site_name( WP_Site $site ) {
		return sprintf( '"%s" (%d)' , $site->blogname, $site->id );
	}

	/**
	 * @param int $id
	 *
	 * @return WP_User|null
	 */
	protected function get_user( int $id ) {
		add_filter( 'wp_sub_check_user_access', '__return_true' );

		$user = get_user_by( 'id', $id );
		if ( ! $user instanceof WP_User ) {
			$this->error( 'User %d not found', $id );
		}

		remove_filter( 'wp_sub_check_user_access', '__return_true' );

		return $user;
	}
}
