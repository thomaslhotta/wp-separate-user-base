<?php
namespace WP_SUB\Tests;

use WP_User;
use WP_User_Query;
use WP_UnitTestCase;
use WPDieException;
use WP_SUB\WP_Separate_User_Base;
use ArrayObject;

class User_Test extends WP_UnitTestCase {

	public function setUp() : void {
		parent::setUp();

		update_network_option( 1, 'wp_sub_add_users_to_network', 0 );
	}

	public function tearDown() : void {
		update_network_option( 1, 'wp_sub_add_users_to_network', 1 );
		set_current_screen( 'front' );
		wp_cache_init();

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
		$users = array_map( 'intval', $query->get_results() );
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
		$users = array_map( 'intval', $query->get_results() );

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
		$users = array_map( 'intval', $query->get_results() );

		restore_current_blog();

		$this->assertCount( 1, $users );
		$this->assertContains( $u1, $users );
	}

	public function test_add_user_meta_query_wrapping() {
		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$u1 = self::factory()->user->create();
		add_user_meta( $u1, 'testval', 1 );
		$u2 = self::factory()->user->create();

		remove_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$site = self::factory()->blog->create();

		wp_sub_add_user_to_site( $u1, $site );

		switch_to_blog( $site );

		$query = new WP_User_Query(
			array(
				'blog_id'    => 0,
				'fields'     => 'IDs',
				'include'    => array( $u1, $u2 ), // User 1 is created before we can set up our conditions
				'meta_query' => array(
					array(
						'key' => 'testval',
					),
				),
			)
		);
		$users = array_map('intval', $query->get_results());

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

	public function test_cache_modification() {
		$blog2 = self::factory()->blog->create();

		$this->assertTrue( wp_cache_set( 'testkey', 'test_data', 'useremail' ) );

		$this->assertEquals( 'test_data', wp_cache_get( 'testkey', 'useremail' ) );

		switch_to_blog( $blog2 );

		$found = null;
		$this->assertFalse( wp_cache_get( 'testkey', 'useremail', false, $found ) );
		$this->assertFalse( $found );

		restore_current_blog();

		$found = null;
		$this->assertEquals( 'test_data', wp_cache_get( 'testkey', 'useremail', false, $found ) );
		$this->assertTrue( $found );
	}

	public function test_cache_modification_not_supported() {
		$cache = $GLOBALS['wp_object_cache'];
		$GLOBALS['wp_object_cache'] = new \stdClass();

		$users = new \WP_SUB\User();

		$thrown = false;
		try {
			$users->modify_cache_groups();
		} catch ( WPDieException $exception ) {
			$thrown = true;
		} finally {
			$GLOBALS['wp_object_cache'] = $cache;
			wp_cache_init();
			restore_current_blog();
		}

		$this->assertTrue( $thrown );
	}

	/**
	 * Tests that the WP_User::get_data_by query has not changed
	 *
	 * @covers WP_User::get_data_by
	 */
	public function test_WP_User_get_data_by_signature() {
		global $wpdb;
		wp_sub_add_user_to_network( 1, 1 );

		$queries = $this->hook_queries();

		$data = WP_User::get_data_by( 'id', 1 );
		$this->assertEquals( 1, $data->ID );


		$variants = array(
			"SELECT * FROM $wpdb->users WHERE ID = '1'",
			"SELECT * FROM $wpdb->users WHERE ID = '1' LIMIT 1",
		);

		$this->assertContains( $queries[0], $variants );
	}

	/**
	 * @covers WP_User::get_data_by
	 */
	public function test_WP_User_get_data_by_caching_id() {
		$blog_2 = self::factory()->blog->create();

		$queries = $this->hook_queries();

		$user_id = self::factory()->user->create(
			array(
				'user_login'    => 'user1',
				'user_email'    => 'user1@test.local',
				'user_nicename' => 'u1',
			)
		);

		wp_sub_add_user_to_network( $user_id, 1 );

		wp_cache_flush();

		$this->assert_get_data_by_not_cached( 'id', $user_id, $user_id );
		$this->assert_get_data_by_cached( 'id', $user_id, $user_id );

		switch_to_blog( $blog_2 );
		$this->assert_get_data_by_cached( 'id', $user_id, $user_id );
	}

	/**
	 * @covers WP_User::get_data_by
	 */
	public function test_WP_User_get_data_by_caching_email() {
		$blog_2 = self::factory()->blog->create();

		$queries = $this->hook_queries();

		$user_id = self::factory()->user->create(
			array(
				'user_login'    => 'user1',
				'user_email'    => 'user1@test.local',
				'user_nicename' => 'u1',
			)
		);

		wp_sub_add_user_to_network( $user_id, 1 );

		wp_cache_flush();

		$this->assert_get_data_by_not_cached( 'email', 'user1@test.local', $user_id );
		$this->assert_get_data_by_cached( 'email', 'user1@test.local', $user_id );

		switch_to_blog( $blog_2 );
		$this->assert_get_data_by_not_cached( 'email', 'user1@test.local', $user_id );
	}

	/**
	 * @covers WP_User::get_data_by
	 */
	public function test_WP_User_get_data_by_caching_slug() {
		$blog_2 = self::factory()->blog->create();

		$queries = $this->hook_queries();

		$user_id = self::factory()->user->create(
			array(
				'user_login'    => 'user1',
				'user_email'    => 'user1@test.local',
				'user_nicename' => 'u1',
			)
		);

		wp_sub_add_user_to_network( $user_id, 1 );

		wp_cache_flush();

		$this->assert_get_data_by_not_cached( 'slug', 'u1', $user_id );
		$this->assert_get_data_by_cached( 'slug', 'u1', $user_id );

		switch_to_blog( $blog_2 );
		$this->assert_get_data_by_cached( 'slug', 'u1', $user_id );
	}

	public function test_signups_deleted() {
		global $wpdb;

		wpmu_signup_user(
			'test1',
			'test1@gmail.com',
			array()
		);

		wpmu_signup_user(
			'test2',
			'test2@gmail.com',
			array()
		);

		wpmu_signup_user(
			'test3',
			'test3@gmail.com',
			array()
		);

		$signups = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->signups, ARRAY_A );
		$this->assertCount( 3, $signups );
		wpmu_activate_signup( $signups[0]['activation_key'] );

		$signups = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->signups, ARRAY_A );
		$this->assertCount( 2, $signups );
		$this->assertEmpty( $signups[0]['active'] );
		$this->assertEmpty( $signups[1]['active'] );
	}

	protected function assert_get_data_by_cached( $by, $id, $check_user_id ) {
		global $wp_object_cache;

		$misses = $wp_object_cache->cache_misses;
		$hits   = $wp_object_cache->cache_hits;

		$data = \WP_User::get_data_by( $by, $id );
		$this->assertEquals( $misses, $wp_object_cache->cache_misses );
		// For all non id queries two cache hits should occur
		$this->assertEquals( $hits + ( 'id' === $by ? 1 : 2 ), $wp_object_cache->cache_hits );

		if ( $check_user_id ) {
			$this->assertEquals( $check_user_id, $data->ID );
		}
	}

	protected function assert_get_data_by_not_cached( $by, $id, $check_user_id ) {
		global $wp_object_cache, $wpdb;

		$query = $this->hook_queries();



		$misses = $wp_object_cache->cache_misses;
		$hits   = $wp_object_cache->cache_hits;

		$data = WP_User::get_data_by( $by, $id );
		$this->assertEquals( $misses + 1, $wp_object_cache->cache_misses, 'Expected cache miss' );
		$this->assertEquals( $hits, $wp_object_cache->cache_hits, 'Expected cache hit' );

		$last_query = $query->getArrayCopy();
		$last_query = reset( $last_query );

		$this->assertStringContainsString( "SELECT * FROM $wpdb->users WHERE", $last_query );
		$this->assertStringContainsString( (string) $id, $last_query );

		if ( $check_user_id ) {
			$this->assertEquals( $check_user_id, $data->ID );
		}
	}

	/**
	 * @return ArrayObject
	 */
	protected function hook_queries() {
		$queries = new ArrayObject( array() );

		add_filter(
			'query',
			function( $sql ) use ( $queries ) {
				$queries->append( $sql );
				return $sql;
			},
			1
		);

		return $queries;
	}
}
