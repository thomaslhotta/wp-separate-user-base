<?php
namespace WP_SUB\Tests;

use WP_UnitTestCase,
	WP_SUB\WP_Separate_User_Base;

class Pluggable_Functions_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		add_filter( 'wp_sub_add_user_access_meta', '__return_false' );
	}

	public function test_invalid_user_id() {
		$this->assertFalse( get_user_by( 'id', 9999999 ) );
	}

	public function test_user_not_on_networks() {
		$this->assertFalse( get_user_by( 'id', self::factory()->user->create() ) );
	}

	public function test_user_on_networks() {
		$user = self::factory()->user->create();

		update_user_meta( $user, WP_Separate_User_Base::NETWORK_META_KEY, 1 );
		$this->assertInstanceOf( 'WP_User', get_user_by( 'id', $user ) );
	}

	public function test_user_not_on_site() {
		self::factory()->blog->create();
		$this->assertFalse( get_user_by( 'id', self::factory()->user->create() ) );
	}

	public function test_user_on_site() {
		$user = self::factory()->user->create();
		$blog = self::factory()->blog->create();

		update_user_meta( $user, WP_Separate_User_Base::SITE_META_KEY, $blog );

		switch_to_blog( $blog );
		$allowed = get_user_by( 'id', $user );
		restore_current_blog();

		$this->assertInstanceOf( 'WP_User', $allowed );
	}
}
