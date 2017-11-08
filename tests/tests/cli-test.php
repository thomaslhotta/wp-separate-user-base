<?php

namespace WP_SUB\Tests;

use PHPUnit\Runner\Exception;
use WP_SUB\CLI,
	WP_UnitTestCase;

require_once BASE_DIR . '/vendor/wp-cli/wp-cli/php/class-wp-cli-command.php';
require_once __DIR__ . '/../../includes/class-cli.php';

class CLI_Test extends WP_UnitTestCase {

	/**
	 * @var CLI
	 */
	protected $cli;

	protected $success = '';

	protected $user;

	public function setUp() {
		parent::setUp();

		$this->success = '';
		$this->error   = '';

		$builder = $this->getMockBuilder( CLI::class );
		$builder->setMethods( array( 'success', 'error' ) );
		$this->cli = $builder->getMock();

		$this->cli->method( 'error' )
		          ->will(
			          $this->returnCallback(
				          function ( $text ) {
					          throw new Exception( $text );
				          }
			          )
		          );

		$this->cli->method( 'success' )
		          ->will(
			          $this->returnCallback(
				          function ( $text ) {
					          $this->success = $text;
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
}
