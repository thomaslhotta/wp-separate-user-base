<?php
namespace WP_SUB\Tests;

use WP_User_Query,
	WP_UnitTestCase,
	WP_SUB\WP_Separate_User_Base;

class User_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		update_network_option( 1, 'wp_sub_add_users_to_network', 0 );
	}

	public function tearDown() {
		update_network_option( 1, 'wp_sub_add_users_to_network', 1 );
		set_current_screen( 'front' );
		parent::tearDown();
	}

	public function test_add_user_meta_query_network_admin() {
		set_current_screen( 'edit-users-network' );

		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		remove_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$query = new WP_User_Query(
			array(
				'blog_id' => 0,
				'fields' => 'IDs',
			)
		);
		$users = $query->get_results();
		$this->assertContains( $u1, $users );
		$this->assertContains( $u2, $users );
	}

	public function test_add_user_meta_query_disable_query_modifications() {
		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		remove_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$site = self::factory()->blog->create();
		add_user_meta( $u1, WP_Separate_User_Base::SITE_META_KEY, $site );

		switch_to_blog( $site );

		$query = new WP_User_Query(
			array(
				'blog_id' => 0,
				'fields'  => 'IDs',
				'include' => array( $u1, $u2 ), // User 1 is created before we can set up our conditions
				'wp_sub_disable_query_integration' => true,
			)
		);
		$users = $query->get_results();

		restore_current_blog();

		$this->assertCount( 2, $users );
		$this->assertContains( $u1, $users );
		$this->assertContains( $u2, $users );
	}

	public function test_add_user_meta_query_on_sub_site() {
		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		remove_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$site = self::factory()->blog->create();
		add_user_meta( $u1, WP_Separate_User_Base::SITE_META_KEY, $site );

		switch_to_blog( $site );

		$query = new WP_User_Query(
			array(
				'blog_id' => 0,
				'fields'  => 'IDs',
				'include' => array( $u1, $u2 ), // User 1 is created before we can set up our conditions
			)
		);
		$users = $query->get_results();

		restore_current_blog();

		$this->assertCount( 1, $users );
		$this->assertContains( $u1, $users );
	}

	public function test_add_user_access_meta_disabled() {
		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$user = self::factory()->user->create();

		$this->assertEmpty( get_user_meta( $user, WP_Separate_User_Base::SITE_META_KEY, false ) );
	}

	public function test_add_user_access_meta_add_to_network() {
		update_network_option( 1, 'wp_sub_add_users_to_network', 1 );

		$user = self::factory()->user->create();

		$this->assertTrue( wp_sub_user_exists_on_network( $user,1 ) );
	}

	public function test_add_user_access_meta_add_to_site() {
		$user = self::factory()->user->create();

		$this->assertTrue( wp_sub_user_exists_on_site( $user,get_current_blog_id() ) );
	}
}
