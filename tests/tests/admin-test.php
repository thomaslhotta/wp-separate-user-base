<?php
namespace WP_SUB\Tests;

use WP_UnitTestCase;
use	WP_SUB\Admin;
use WP_User;
use WP_Site;
use WP_Network;
use Symfony\Component\DomCrawler\Crawler;

class Admin_Test extends WP_UnitTestCase {

	protected Admin $admin;

	protected WP_User $admin_user;

	protected WP_User $user1;
	protected WP_User $user2;
	protected WP_User $user3;

	protected WP_Site $site_1;

	protected WP_Site $site_2;

	protected WP_Network $network_2;

	public function setUp() :void {
		parent::setUp();
		$this->admin = new Admin();

		$this->admin_user = self::factory()->user->create_and_get( array( 'role' => 'administrator' ) );
		grant_super_admin( $this->admin_user->ID );

		delete_network_option( 1, 'wp_sub_add_users_to_network' );

		$this->user1 = self::factory()->user->create_and_get();
		$this->user2 = self::factory()->user->create_and_get();
		$this->user3 = self::factory()->user->create_and_get();
		wp_sub_remove_user_from_site( $this->user1->ID, get_current_blog_id() );
		wp_sub_remove_user_from_site( $this->user2->ID, get_current_blog_id() );
		wp_sub_remove_user_from_site( $this->user3->ID, get_current_blog_id() );


		$this->site_1 = self::factory()->blog->create_and_get();
		$this->site_2 = self::factory()->blog->create_and_get();

		$this->network_2 = self::factory()->network->create_and_get();
	}

	public function test_manage_user_section_test_render() {
		wp_set_current_user( $this->admin_user->ID );
		wp_sub_add_user_to_site( $this->user1->ID, $this->site_1->id );


		ob_start();
		$this->admin->manage_user_section( $this->user1 );
		$html = ob_get_contents();
		ob_end_clean();

		$crawler = new Crawler($html);

		$site_add_options = $crawler->filter('select[name^="wp_sub_site_ids"] option');
		$site_ids_to_add = $site_add_options->extract( ['value'] );

		// Check that correct number of site options are rendered.
		$this->assertEqualsCanonicalizing(
			[
				$this->site_2->id,
				get_main_site_id()
			],
			wp_parse_id_list( $site_ids_to_add )
		);

		// Check that the correct site is added.
		$this->assertCount(
			1,
			$crawler->filter(
				'input[type="hidden"][name^="wp_sub_site_ids"]'
			)
		);

		$network_add_options = $crawler->filter('select[name^="wp_sub_network_ids-add"] option');
		$network_ids_to_add = $network_add_options->extract( ['value'] );

		// Check that correct number of network options are rendered.
		$this->assertEqualsCanonicalizing(
			[
				'1',
				$this->network_2->id
			],
			wp_parse_id_list( $network_ids_to_add )
		);


		// Assert no network is added.
		$this->assertCount(
			0,
			$crawler->filter(
				'input[type="hidden"][name^="wp_sub_network_ids"]'
			)
		);
	}

	public function test_update_site_ids() {
		wp_set_current_user( $this->admin_user->ID );

		wp_sub_add_user_to_site( $this->user1->ID, get_main_site_id() );
		wp_sub_add_user_to_network( $this->user1->ID, get_main_network_id() );

		$admin = $this->createPartialMock( Admin::class, [ 'get_id_array_from_post' ] );
		$admin->method( 'get_id_array_from_post' )->willReturnMap(
			[
				[ Admin::POST_SITE_IDS, [ $this->site_1->id, $this->site_2->id ] ],
				[ Admin::POST_NETWORK_IDS, [ $this->network_2->id ] ],
			]
		);

		$admin->manage_user_section_update( $this->user1->ID );

		$this->assertEquals(
			[ $this->site_1->id, $this->site_2->id ],
			wp_sub_get_user_sites( $this->user1->ID )
		);

		$this->assertEquals(
			[ $this->network_2->id ],
			wp_sub_get_user_networks( $this->user1->ID )
		);
	}
}
