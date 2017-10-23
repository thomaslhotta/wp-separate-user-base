<?php
namespace WP_SUB\Tests;

use WP_UnitTestCase,
	WP_SUB\WP_Separate_User_Base;

class Functions_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		update_network_option( 1, 'wp_sub_add_users_to_network', 0 );
	}

	public function tearDown() {
		parent::tearDown();

		update_network_option( 1, 'wp_sub_add_users_to_network', 1 );
	}

	public function test_wp_sub_user_exists_not_exists() {
		$user = self::factory()->user->create();
		$blog = self::factory()->blog->create();

		$this->assertFalse( wp_sub_user_exists( $user, 1, $blog ) );
	}

	public function test_wp_sub_user_exists_allowed_super_admin() {
		$user = self::factory()->user->create();
		grant_super_admin( $user );

		$blog = self::factory()->blog->create();

		$this->assertTrue( wp_sub_user_exists( $user, 1, $blog ) );
	}

	public function test_wp_sub_user_exists_allowed_on_network() {
		update_network_option( 1, 'wp_sub_add_users_to_network', 1 );

		$user = self::factory()->user->create();
		$blog = self::factory()->blog->create();

		$this->assertTrue( wp_sub_user_exists( $user, 1, $blog ) );
	}

	public function test_wp_sub_user_exists_allowed_on_site() {
		$user = self::factory()->user->create();

		$this->assertTrue( wp_sub_user_exists( $user, 1, get_current_blog_id() ) );
	}

	public function test_wp_sub_user_exists_on_network_not_exist() {
		$user = self::factory()->user->create();
		$this->assertFalse( wp_sub_user_exists_on_network( $user, 1 ) );
	}

	public function test_wp_sub_user_exists_on_network_exist() {
		$user = self::factory()->user->create();
		add_user_meta( $user, WP_Separate_User_Base::NETWORK_META_KEY, 1 );
		$this->assertTrue( wp_sub_user_exists_on_network( $user, 1 ) );
	}

	public function test_wp_sub_user_exists_on_site_not_exists() {
		$user = self::factory()->user->create();
		$blog = self::factory()->blog->create();
		$this->assertFalse( wp_sub_user_exists_on_site( $user, $blog ) );
	}

	public function test_wp_sub_user_exists_on_site_exists() {
		$user = self::factory()->user->create();
		$blog = self::factory()->blog->create();
		add_user_meta( $user, WP_Separate_User_Base::SITE_META_KEY, $blog );
		$this->assertTrue( wp_sub_user_exists_on_site( $user, $blog ) );
	}
}
