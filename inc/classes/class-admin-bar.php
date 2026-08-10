<?php
/**
 * Admin Bar class.
 *
 * Adds the Live Edit toggle and Save Changes nodes to the frontend admin
 * bar for users who can edit the current post.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc;

use Live_Edit\Inc\Traits\Singleton;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Bar
 */
class Admin_Bar {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		// Hook late so it appears near the end of the admin bar.
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_items' ], 100 );
	}

	/**
	 * Add Live Edit toggle and Save button to the admin bar.
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar object.
	 *
	 * @return void
	 */
	public function add_admin_bar_items( $admin_bar ) {
		// Only display on the frontend on singular posts/pages.
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();

		// Check if the user has permission to edit the current post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Add Live Edit toggle.
		$admin_bar->add_node(
			[
				'id'    => 'live-edit-toggle',
				'title' => '<span class="ab-label live-edit__label">' . __( 'Live Edit', 'live-edit' ) . '</span><span class="live-edit__switch"><span class="live-edit__slider"></span></span>',
				'href'  => '#',
				'meta'  => [
					'class' => 'live-edit__toggle',
					'title' => __( 'Toggle Live Edit mode', 'live-edit' ),
				],
			]
		);

		// Add Save button (initially hidden via CSS).
		$admin_bar->add_node(
			[
				'id'    => 'live-edit-save',
				'title' => '<span class="ab-label live-edit__label">' . __( 'Save Changes', 'live-edit' ) . '</span>',
				'href'  => '#',
				'meta'  => [
					'class' => 'live-edit__save live-edit__save--hidden', // Hidden by default; JS removes the modifier when live edit is activated.
					'title' => __( 'Save your live edits', 'live-edit' ),
				],
			]
		);
	}
}
