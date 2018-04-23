<?php

namespace WP_SUB\Tests;

use Exception,
    WP_SUB\CLI,
	WP_UnitTestCase,
	ArrayObject;

require_once BASE_DIR . '/vendor/wp-cli/wp-cli/php/class-wp-cli-command.php';
require_once __DIR__ . '/../../includes/class-cli.php';

class CLI_Test extends WP_UnitTestCase {

	/**
	 * @var CLI
	 */
	protected $cli;

	protected $success = '';

	protected $user;

	/**
	 * @var ArrayObject
	 */
	public $table_rows;

	public function setUp() {
		parent::setUp();

		$this->success = '';
		$this->error   = '';

		$builder = $this->getMockBuilder( CLI::class );
		$builder->setMethods( array( 'success', 'error', 'create_progress_bar', 'create_table' ) );
		$this->cli = $builder->getMock();

		$this->cli->method( 'error' )
		          ->will(
			          $this->returnCallback(
				          function( $text ) {
					          throw new Exception( $text );
				          }
			          )
		          );

		$this->cli->method( 'success' )
		          ->will(
			          $this->returnCallback(
				          function( $text ) {
					          $this->success = $text;
				          }
			          )
		          );

		$this->cli->method( 'create_progress_bar' )
		          ->will(
			          $this->returnCallback(
				          function() {
					          return new \WP_CLI\NoOp();
				          }
			          )
		          );


		$this->table_rows = new ArrayObject( array() );
		global $table_rows;
		$table_rows = $this->table_rows;

		$this->cli->method( 'create_table' )
		          ->will(
			          $this->returnCallback(
				          function () {
					          return new class {

									public function setRows( array $rows ) {
										global $table_rows;
										$table_rows[ spl_object_hash( $this ) ] = $rows;
									}

									public function addRow( array $row ) {
										global $table_rows;
										$table_rows[ spl_object_hash( $this ) ][] = $row;
									}

									public function display() {}
					          };
				          }
			          )
		          );


		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$this->user = self::factory()->user->create();
	}

	/*
	 * Add user to network
	 */

	public function test_add_user_to_network_success() {
		$this->cli->add_user_to_network( array( 1, $this->user ), array() );

		$this->assertTrue( wp_sub_user_exists_on_network( $this->user, 1 ) );
		$this->assertNotEmpty( $this->success );
	}

	public function test_add_user_to_network_already_added() {
		$this->expectException( 'Exception' );
		wp_sub_add_user_to_network( $this->user, 1 );
		$this->cli->add_user_to_network( array( 1, $this->user ), array() );
	}

	public function test_add_user_to_network_invalid_network() {
		$this->expectException( 'Exception' );
		$this->cli->add_user_to_network( array( 999, $this->user ), array() );
	}

	public function test_add_user_to_network_invalid_user() {
		$this->expectException( 'Exception' );
		$this->cli->add_user_to_network( array( 1, 999 ), array() );
	}

	/*
	 * List user networks
	 */

	public function test_list_user_networks() {
		$user = self::factory()->user->create();

		$n1 = get_current_network_id();
		$n2 = self::factory()->network->create();
		$n3 = self::factory()->network->create();

		wp_sub_add_user_to_network( $user, $n1 );
		wp_sub_add_user_to_network( $user, $n3 );

		$this->cli->list_user_networks( array( $user ) );

		$rows = $this->get_table_rows();
		$this->assertEquals( $rows[0][0], $n1 );
		$this->assertEquals( $rows[1][0], $n3 );
	}

	/*
	 * Remove user from network
	 */

	public function test_remove_user_from_network_success() {
		wp_sub_add_user_to_network( $this->user, 1 );

		$this->cli->remove_user_from_network( array( 1, $this->user ), array() );

		$this->assertFalse( wp_sub_user_exists_on_network( $this->user, 1 ) );
		$this->assertNotEmpty( $this->success );
	}

	public function test_remove_user_from_network_not_on_network() {
		$this->expectException( 'Exception' );
		$this->cli->remove_user_from_network( array( 1, $this->user ), array() );
	}

	public function test_remove_user_from_network_invalid_network() {
		$this->expectException( 'Exception' );
		$this->cli->remove_user_from_network( array( 999, $this->user ), array() );
	}

	public function test_remove_user_from_network_invalid_user() {
		$this->expectException( 'Exception' );
		$this->cli->remove_user_from_network( array( 1, 999 ), array() );
	}

	/*
	 * Add user to site
	 */

	public function test_add_user_to_site_success() {
		$this->cli->add_user_to_site( array( 1, $this->user ), array() );

		$this->assertTrue( wp_sub_user_exists_on_site( $this->user, 1 ) );
		$this->assertNotEmpty( $this->success );
	}

	public function test_add_user_to_site_already_added() {
		$this->expectException( 'Exception' );
		wp_sub_add_user_to_site( $this->user, 1 );
		$this->cli->add_user_to_site( array( 1, $this->user ), array() );
	}

	public function test_add_user_to_site_invalid_site() {
		$this->expectException( 'Exception' );
		$this->cli->add_user_to_site( array( 999, $this->user ), array() );
	}

	public function test_add_user_to_site_invalid_user() {
		$this->expectException( 'Exception' );
		$this->cli->add_user_to_site( array( 1, 999 ), array() );
	}

	/*
	 * List user sites
	 */

	public function test_list_user_sites() {
		$user = self::factory()->user->create();

		$s1 = self::factory()->blog->create();
		$s2 = self::factory()->blog->create();
		$s3 = self::factory()->blog->create();

		wp_sub_add_user_to_site( $user, $s1 );
		wp_sub_add_user_to_site( $user, $s3 );

		$this->cli->list_user_sites( array( $user ) );

		$rows = $this->get_table_rows();
		$this->assertEquals( $rows[0][0], $s1 );
		$this->assertEquals( $rows[1][0], $s3 );
	}

	/*
	 * Remove user from site
	 */

	public function test_remove_user_from_site_success() {
		wp_sub_add_user_to_site( $this->user, 1 );

		$this->cli->remove_user_from_site( array( 1, $this->user ), array() );

		$this->assertFalse( wp_sub_user_exists_on_site( $this->user, 1 ) );
		$this->assertNotEmpty( $this->success );
	}

	public function test_remove_user_from_site_not_on_site() {
		$this->expectException( 'Exception' );
		$this->cli->remove_user_from_site( array( 1, $this->user ), array() );
	}

	public function test_remove_user_from_site_invalid_site() {
		$this->expectException( 'Exception' );
		$this->cli->remove_user_from_site( array( 999, $this->user ), array() );
	}

	public function test_remove_user_from_site_invalid_user() {
		$this->expectException( 'Exception' );
		$this->cli->remove_user_from_site( array( 1, 999 ), array() );
	}

	/*
	 * Enable/disable network users
	 */

	public function test_enable_network_registration_success() {
		delete_network_option( 1, 'wp_sub_add_users_to_network' );

		$this->cli->enable_network_registration( array( 1 ), array() );
		$this->assertEquals( 1, get_network_option( 1, 'wp_sub_add_users_to_network' ) );

		$this->assertNotEmpty( $this->success );
	}

	public function test_enable_network_registration_already_enabled() {
		$this->expectException( 'Exception' );

		update_network_option( 1, 'wp_sub_add_users_to_network', 1 );
		$this->cli->enable_network_registration( array( 1 ), array() );
	}

	public function test_disable_network_registration_success() {
		$this->cli->disable_network_registration( array( 1 ), array() );
		$this->assertFalse( get_network_option( 1, 'wp_sub_add_users_to_network' ) );
		$this->assertNotEmpty( $this->success );
	}

	public function test_disable_network_registration_already_disabled() {
		$this->expectException( 'Exception' );
		delete_network_option( 1, 'wp_sub_add_users_to_network' );
		$this->cli->disable_network_registration( array( 1 ), array() );
	}

	/* Adding users from existing installations */

	public function test_add_users_from_roles() {
		update_network_option( 1, 'wp_sub_add_users_to_network', 0 );

		add_filter( 'wp_sub_user_exists', '__return_true' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$s1 = get_current_blog_id();
		$s2 = self::factory()->blog->create();

		add_user_to_blog( $s2, $u1, 'administrator' );

		// Create a second network
		$n2 = self::factory()->network->create_and_get();
		$this->assertTrue( add_network_option( $n2->id, 'wp_sub_add_users_to_network', 1 ) );
		$s4 = self::factory()->blog->create( array( 'site_id' => $n2->id, 'domain' => $n2->domain ) );
		$s5 = self::factory()->blog->create( array( 'site_id' => $n2->id, 'domain' => $n2->domain ) );

		$this->assertTrue( add_user_to_blog( $s4, $u1, 'subscriber' ) );
		$this->assertTrue( add_user_to_blog( $s5, $u2, 'subscriber' ) );

		remove_filter( 'wp_sub_user_exists', '__return_true' );

		$this->cli->add_users_to_sites_from_roles();

		$site_rows = $this->get_table_rows( 0 );

		$this->assertEquals( $s1, $site_rows[0][0] );
		$this->assertEquals( 5, $site_rows[0][3] ); // 4 Users created here + 1 default

		$this->assertEquals( $s2, $site_rows[1][0] );
		$this->assertEquals( 1, $site_rows[1][3] );

		$network_rows = $this->get_table_rows( 1 );
		$this->assertEquals( $n2->id, $network_rows[0][0] );
		$this->assertEquals( 2, $network_rows[0][3] );
	}

	/**
	 * Returns table rows created by CLI tables
	 *
	 * @param int $table_index
	 *
	 * @return array
	 */
	protected function get_table_rows( $table_index = 0 ) {
		$tables = array_values( $this->table_rows->getArrayCopy() );
		return array_values( $tables[ $table_index ] );
	}
}
