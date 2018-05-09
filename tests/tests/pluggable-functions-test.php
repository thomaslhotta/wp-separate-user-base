<?php
namespace WP_SUB\Tests;

use WP_UnitTestCase,
	WP_SUB\WP_Separate_User_Base;

class Pluggable_Functions_Test extends WP_UnitTestCase {

	protected $user_1;

	protected $user_2;

	protected $user_3;

	protected $blog_2;

	public function setUp() {
		parent::setUp();

		// Don't auto-add users
		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );

		$this->user_1 = self::factory()->user->create(
			array(
				'user_login'    => 'user1',
				'user_email'    => 'user1@test.local',
				'user_nicename' => 'u1',
			)
		);
		$this->assertInternalType( 'int', $this->user_1 );

		$this->user_2 = self::factory()->user->create(
			array(
				'user_login' => 'user2',
				'user_email' => 'user2@test.local',
				'user_nicename' => 'u2',
			)
		);
		$this->assertInternalType( 'int', $this->user_2 );

		$this->blog_2 = self::factory()->blog->create();

		wp_cache_flush();
	}

	/**
	 * @covers ::get_user_by
	 * @covers WP_User::get_data_by
	 */
	public function test_get_user_by_not_on_network() {
		$this->users_dont_exist_on_network( 'id', [ $this->user_1, $this->user_2 ] );
		$this->users_dont_exist_on_network( 'login', [ 'user1', 'user2' ] );
		$this->users_dont_exist_on_network( 'email', [ 'user1@test.local', 'user2@test.local' ] );
		$this->users_dont_exist_on_network( 'slug', [ 'u1', 'u2' ] );
	}

	/**
	 * @covers ::get_user_by
	 */
	public function test_get_user_by_invalid_value() {
		$this->users_dont_exist_on_network( 'id', [ 999 ] );
		$this->users_dont_exist_on_network( 'login', [ 'user999' ] );
		$this->users_dont_exist_on_network( 'email', [ 'user999@test.local' ] );
		$this->users_dont_exist_on_network( 'slug', [ 'u999' ] );
	}

	/**
	 * @covers ::get_user_by
	 */
	public function test_get_user_by_user_on_networks() {
		wp_sub_add_user_to_network( $this->user_1, 1 );


		$this->users_exist( 'id', [ $this->user_1 => $this->user_1 ] );
		$this->users_exist( 'login', [ 'user1' => $this->user_1 ] );
		$this->users_exist( 'email', [ 'user1@test.local' => $this->user_1 ] );
		$this->users_exist( 'slug', [ 'u1' => $this->user_1 ] );

		$this->users_dont_exist_on_network( 'id', [ $this->user_2 ] );
		$this->users_dont_exist_on_network( 'login', [ 'user2' ] );
		$this->users_dont_exist_on_network( 'email', [ 'user2@test.local' ] );
		$this->users_dont_exist_on_network( 'slug', [ 'u2' ] );

		switch_to_blog( $this->blog_2 );

		$this->users_exist( 'id', [ $this->user_1 => $this->user_1 ] );
		$this->users_exist( 'login', [ 'user1' => $this->user_1 ] );
		$this->users_exist( 'email', [ 'user1@test.local' => $this->user_1 ] );
		$this->users_exist( 'slug', [ 'u1' => $this->user_1 ] );
	}

	/**
	 * @covers ::get_user_by
	 */
	public function test_user_on_site() {
		wp_sub_add_user_to_site( $this->user_1, $this->blog_2 );

		$this->users_dont_exists( 'id', [ $this->user_1, $this->user_2 ] );
		$this->users_dont_exists( 'login', [ 'user1', 'user2' ] );
		$this->users_dont_exists( 'email', [ 'user1@test.local', 'user2@test.local' ] );
		$this->users_dont_exists( 'slug', [ 'u1', 'u2' ] );

		switch_to_blog( $this->blog_2 );

		$this->users_exist( 'id', [ $this->user_1 => $this->user_1 ] );
		$this->users_exist( 'login', [ 'user1' => $this->user_1 ] );
		$this->users_exist( 'email', [ 'user1@test.local' => $this->user_1 ] );
		$this->users_exist( 'slug', [ 'u1' => $this->user_1 ] );
	}

	/**
	 * @covers ::get_user_by
	 */
	public function test_get_user_by_collisions() {
		wp_sub_add_user_to_site( $this->user_1, 1 );
		wp_sub_add_user_to_site( $this->user_2, 1 );

		switch_to_blog( $this->blog_2 );

		$this->user_3 = self::factory()->user->create(
			array(
				'user_login'    => 'user1.1',
				'user_email'    => 'user1@test.local',
				'user_nicename' => 'u3',
			)
		);

		wp_sub_add_user_to_site( $this->user_3, $this->blog_2 );

		$this->assertInternalType( 'int', $this->user_3 );

		$this->users_exist( 'id', array( $this->user_3 => $this->user_3 ) );
		$this->users_exist( 'login', array( 'user1.1' => $this->user_3 ) );
		$this->users_exist( 'email', array( 'user1@test.local' => $this->user_3 ) ); // Same email, but different user
		$this->users_exist( 'slug', array( 'u3' => $this->user_3 ) );

		$this->users_dont_exists( 'id', array( $this->user_1, $this->user_2 ) );
		$this->users_dont_exists( 'login', array( 'user2' ) );
		$this->users_dont_exists( 'email', array( 'user2@test.local' ) );
		$this->users_dont_exists( 'slug', array( 'u1', 'u2' ) );
	}

	/**
	 * @covers ::username_exists
	 */
	public function test_username_exists() {
		wp_sub_add_user_to_site( $this->user_1, $this->blog_2 );
		switch_to_blog( $this->blog_2 );

		$this->users_exist( 'login', array( 'user1' => $this->user_1 ) );
		$this->users_dont_exists( 'login', array( 'user2' ) );

		$this->assertEquals( $this->user_2, username_exists( 'user2' ) );
	}

	/**
	 * @covers ::username_exists
	 */
	public function test_prevent_user_with_same_user_login() {
		wp_sub_add_user_to_site( $this->user_1, $this->blog_2 );
		switch_to_blog( $this->blog_2 );

		/* @var \WP_Error $error */
		$error = self::factory()->user->create(
			array(
				'user_login'    => 'user1',
			)
		);

		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertContains( 'existing_user_login', $error->get_error_codes() );
	}


	protected function users_dont_exist_on_network( $by, array $ids ) {
		$this->users_dont_exists( $by, $ids );

		switch_to_blog( $this->blog_2 );

		$this->users_dont_exists( $by, $ids );
	}

	protected function users_dont_exists( $by, $ids ) {
		foreach ( $ids as $id ) {
			$this->assertFalse( get_user_by( $by, $id ) );

			// Repeat to test cache
			$this->assertFalse( get_user_by( $by, $id ) );
		}
	}

	protected function users_exist( $by, $ids ) {
		foreach ( $ids as $id => $user_id ) {
			$this->assertEquals( $user_id, get_user_by( $by, $id )->ID );

			// Repeat to test cache
			$this->assertEquals( $user_id, get_user_by( $by, $id )->ID );
		}
	}
}
