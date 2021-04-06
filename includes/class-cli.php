<?php
declare(strict_types = 1);

namespace WP_SUB;

use WP_SUB\WP_Separate_User_Base;
use WP_CLI;
use WP_CLI_Command;
use WP_Network;
use WP_Site;
use WP_Site_Query;
use WP_User;
use WP_User_Query;
use Countable;

/**
 * Handles user separation between networks and sites
 *
 * @package WP_SUB
 */
class CLI extends WP_CLI_Command {

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
		$network = $this->get_network( (int) $args[0] );
		$user    = $this->get_user( $args[1] );

		$user_id = (int) $user->ID;

		$networks = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );

		if ( in_array( $network->id, $networks ) ) {
			$this->error( 'User %d already exists in network %s', $user_id, $this->format_network_name( $network ) );
		}

		if ( wp_sub_add_user_to_network( $user_id, $network->id ) ) {
			$this->success( 'Adding user %d to network %s', $user_id, $this->format_network_name( $network ) );
		} else {
			$this->error( 'Adding user %d to network %s failed', $user_id, $this->format_network_name( $network ) );
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
		$user    = $this->get_user( $args[1] );

		$networks = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );
		if ( ! in_array( $network->id, $networks ) ) {
			$this->error( 'User %d does not exist in network %s', $user->ID, $this->format_network_name( $network ) );
		}

		if ( wp_sub_remove_user_from_network( $user->ID, $network->id ) ) {
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
		$site = $this->get_site( (int) $args[0] );
		$user = $this->get_user( $args[1] );

		$sites = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );
		if ( in_array( $site->id, $sites ) ) {
			$this->error( 'User %d already exists in site %s', $user->ID, $this->format_site_name( $site ) );
		}

		if ( wp_sub_add_user_to_site( $user->ID, $site->id ) ) {
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
		$site = $this->get_site( (int) $args[0] );
		$user = $this->get_user( $args[1] );

		$sites = get_user_meta( $user->ID, WP_Separate_User_Base::SITE_META_KEY, false );
		if ( ! in_array( $site->id, $sites ) ) {
			$this->error( 'User %d does not exist on site %s', $user->ID, $this->format_site_name( $site ) );
		}

		if ( wp_sub_remove_user_from_site( $user->ID, $site->id ) ) {
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
	 */
	public function list_user_networks( $args ) {
		$user     = $this->get_user( $args[0] );
		$networks = get_user_meta( $user->ID, WP_Separate_User_Base::NETWORK_META_KEY, false );

		$table = $this->create_table( array( 'ID', 'Name', 'URL' ) );
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
	 */
	public function list_user_sites( $args ) {
		$user  = $this->get_user( $args[0] );
		$sites = get_user_meta( $user->ID, WP_Separate_User_Base::SITE_META_KEY, false );

		$table = $this->create_table( array( 'ID', 'Name', 'URL', 'User role' ) );
		foreach ( $sites as $id ) {
			$s = get_site( $id );
			if ( $s instanceof WP_Site ) {
				$user->for_site( $s->id );
				$table->addRow( array( $s->id, $s->blogname, get_home_url( $s->id ), join( ', ', $user->roles ) ) );
			} else {
				$table->addRow( array( $id, 'Site not found', '' ) );
			}
		}

		$table->display();
	}

	/**
	 * Added users to sites and network based on their existing roles
	 *
	 * @subcommand add-users-from-roles
	 */
	public function add_users_to_sites_from_roles() {
		$added_to_network = array();
		$added_to_site    = array();

		$sites_query = new WP_Site_Query(
			array(
				'number'            => false,
				'update_site_cache' => false,
			)
		);

		$found_sites = $sites_query->get_sites();

		$progress = $this->create_progress_bar(  'Processing sites', $found_sites  );

		foreach ( $found_sites as $site ) {
			$progress->tick();

			/* @var WP_Site $site */
			$users = new WP_User_Query(
				array(
					'blog_id'                          => $site->blog_id,
					'number'                           => false,
					'fields'                           => 'ids',
					'wp_sub_disable_query_integration' => true,
				)
			);

			$found_users          = 0;
			$add_users_to_network = get_network_option( $site->network_id, 'wp_sub_add_users_to_network' );

			foreach ( $users->get_results() as $user_id ) {
				$user_id = (int) $user_id;

				if ( $add_users_to_network ) {
					if ( ! isset( $added_to_network[ $site->network_id ] ) ) {
						 $added_to_network[ $site->network_id ] = 0;
					}

					$added_to_network[ $site->network_id ] += (int) wp_sub_add_user_to_network(
						$user_id,
						(int) $site->network_id
					);
				} else {
					$found_users += wp_sub_add_user_to_site( $user_id, (int) $site->blog_id );
				}
			}

			if ( $found_users ) {
				$added_to_site[ $site->blog_id ] = array(
					$site->blog_id,
					$site->blogname,
					$site->siteurl,
					$found_users,
				);
			}
		}

		$progress->finish();

		if ( empty( $added_to_network ) && empty( $added_to_site ) ) {
			$this->error( 'No users were added' );
		}

		if ( $added_to_site ) {
			$this->success( 'Added users to %d sites', count( $added_to_site ) );
			$site_table = $this->create_table( array( 'ID', 'Name', 'URL', 'Added users' ) );
			$site_table->setRows( $added_to_site );
			$site_table->display();
		}

		if ( $added_to_network ) {
			$this->success( 'Added users to %d networks', $added_to_site );
			$network_table = $this->create_table( array( 'ID', 'Name', 'URL', 'Added users' ) );
			foreach ( $added_to_network as $id => $users ) {
				$network = WP_Network::get_instance( $id );
				$network_table->addRow(
					array(
						$id,
						$network->site_name,
						$network->domain . $network->path,
						$users,
					)
				);
			}
			$network_table->display();
		}
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

		if ( get_network_option( $network->id, 'wp_sub_add_users_to_network' ) ) {
			$this->error( 'Network registration already enabled for network %s', $this->format_network_name( $network ) );
		}

		if ( $r = add_network_option( $network->id, 'wp_sub_add_users_to_network', 1 ) ) {
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
	 * Allows deletion of orphaned users.
	 *
	 * [--yes]
	 * Skip the deletion confirmation
	 *
	 * [--site-id=<int>]
	 * Directly provided a site id, skipping the selector.
	 *
	 * @subcommand delete-orphaned-users
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function delete_orphaned_users( $args, $assoc_args ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			[
				'site-id' => '',
			]
		);

		// Get all orphaned users
		$orphaned_sites = wp_sub_get_orphaned_users();

		// Bail of no users were found
		if ( empty( $orphaned_sites ) ) {
			$this->error( 'No orphaned users found' );
		}


		// Create a table of site IDs that have orphaned users
		$table = $this->create_table(
			array(
				'id'    => 'Site ID',
				'users' => 'User count',
			)
		);

		foreach ( $orphaned_sites as $site_id => $users ) {
			$table->addRow(
				array(
					'id'    => $site_id,
					'users' => count( $users ),
				)
			);
		}
		$table->display();

		// Allow user to select a site id
		$selected = $assoc_args['site-id'];
		while ( ! array_key_exists( $selected, $orphaned_sites ) ) {
			$selected = \cli\prompt( 'Select a site ID' );
		}

		// Get confirmation
		$this->confirm(
			sprintf(
				'Are you sure you want to delete %d users associated with site id \'%d\'',
				count( $orphaned_sites[ $selected ] ),
				$selected
			),
			$assoc_args
		);

		// Delete users
		$progress = $this->create_progress_bar( 'Deleting users', count( $orphaned_sites[ $selected ] ) );
		foreach ( $orphaned_sites[ $selected ] as $user_id ) {
			$progress->tick();
			wpmu_delete_user( $user_id );
		}

		$progress->finish();

		$this->success( 'Deleted %d users', count( $orphaned_sites[ $selected ] ) );
	}

	/**
	 * Created a CLI success message
	 *
	 * @codeCoverageIgnore
	 */
	protected function success() {
		WP_CLI::success( call_user_func_array( 'sprintf', func_get_args() ) );
	}

	/**
	 * Created a CLI error
	 *
	 * @codeCoverageIgnore
	 */
	protected function error() {
		WP_CLI::error( call_user_func_array( 'sprintf', func_get_args() ) );
	}

	/**
	 * Ask for confirmation before running a destructive operation.
	 *
	 * @param $question
	 * @param array $assoc_args
	 */
	protected function confirm( $question, $assoc_args = array() ) {
		WP_CLI::confirm( $question, $assoc_args );
	}

	/**
	 * Creates a table with the given headers
	 *
	 * @param array $headers
	 *
	 * @return \cli\Table
	 * @codeCoverageIgnore
	 */
	protected function create_table( array $headers ) {
		$table = new \cli\Table();
		$table->setHeaders( $headers );
		return $table;
	}

	/**
	 * @param string $message
	 * @param $count
	 *
	 * @return \cli\progress\Bar
	 * @codeCoverageIgnore
	 */
	protected function create_progress_bar( string $message, $count ) {
		if ( ! is_numeric( $count ) && $count instanceof Countable ) {
			$count = count( $count );
		}

		return WP_CLI\Utils\make_progress_bar( $message, $count );
	}

	/**
	 * Returns the network for the given ID or triggers a CLI error
	 *
	 * @param int $id
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
		return sprintf( '"%s" (%d)', $site->blogname, $site->id );
	}

	/**
	 * @param int|string $id
	 *
	 * @return WP_User|null
	 */
	protected function get_user( $id ) {
		add_filter( 'wp_sub_user_exists', '__return_true' );
		add_filter( 'wp_sub_enabled', '__return_false' );

		if ( is_numeric( $id ) ) {
			$user = get_user_by( 'id', $id );
		} elseif ( filter_var( $id, FILTER_VALIDATE_EMAIL ) ) {
			$user = get_user_by( 'email', $id );
		} else {
			$user = get_user_by( 'login', $id );
		}

		if ( ! $user instanceof WP_User ) {
			$this->error( 'User %s not found', $id );
		}

		remove_filter( 'wp_sub_user_exists', '__return_true' );
		remove_filter( 'wp_sub_enabled', '__return_false' );

		return $user;
	}
}
