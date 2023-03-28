<?php
namespace WP_SUB\Tests;

use WP_UnitTestCase;
use	WP_SUB\WP_Separate_User_Base;

class Functions_Test extends WP_UnitTestCase {

	public function setUp() : void {
		parent::setUp();

		update_network_option( 1, 'wp_sub_add_users_to_network', 0 );
	}

	public function tearDown() : void {
		parent::tearDown();

		update_network_option( 1, 'wp_sub_add_users_to_network', 1 );
	}

	public function test_wp_sub_user_exists_not_exists() : void {
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

	public function test_wp_sub_get_orphaned_users() {
		update_network_option( 1, 'wp_sub_add_users_to_network', 0 );

		$s1 = get_current_blog_id();
		$s2 = self::factory()->blog->create();
		$s3 = self::factory()->blog->create();

		// User blog 1
		$u1 = self::factory()->user->create();

		// Orphaned user
		$u2 = self::factory()->user->create();
		wp_sub_remove_user_from_site( $u2, $s1 );
		wp_sub_add_user_to_site( $u2, $s2 );

		// Orphaned
		$u3 = self::factory()->user->create();
		wp_sub_remove_user_from_site( $u3, $s1 );
		wp_sub_add_user_to_site( $u3, $s2 );

		// Orphaned on site 2 but not on site 3
		$u4 = self::factory()->user->create();
		wp_sub_remove_user_from_site( $u4, $s1 );
		wp_sub_add_user_to_site( $u4, $s2 );
		wp_sub_add_user_to_site( $u4, $s3 );

		// User blog 3
		$u5 = self::factory()->user->create();
		wp_sub_remove_user_from_site( $u5, $s1 );
		wp_sub_remove_user_from_site( $u5, $s2 );
		wp_sub_add_user_to_site( $u5, $s3 );

		wpmu_delete_blog( $s2, true );

		$users = wp_sub_get_orphaned_users();
		$this->assertCount( 1, $users );
		$this->assertArrayHasKey( $s2, $users );

		$this->assertCount( 2, $users[ $s2 ] );
		$this->assertContains( $u2, $users[ $s2 ] );
		$this->assertContains( $u3, $users[ $s2 ] );

		wpmu_delete_blog( $s3, true );
		$users = wp_sub_get_orphaned_users();
		$this->assertCount( 2, $users );

		// Site 2 should now container 3 users
		$this->assertCount( 3, $users[ $s2 ] );
		$this->assertContains( $u2, $users[ $s2 ] );
		$this->assertContains( $u4, $users[ $s2 ] );

		// Site 3 should now container 3 users
		$this->assertCount( 2, $users[ $s3 ] );
		$this->assertContains( $u4, $users[ $s3 ] );
		$this->assertContains( $u5, $users[ $s3 ] );
	}
}
