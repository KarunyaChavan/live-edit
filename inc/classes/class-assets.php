<?php
/**
 * Assets class.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc;

use Live_Edit\Inc\Traits\Singleton;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Assets
 */
class Assets {

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

		/**
		 * Action
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

	}

	/**
	 * To enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		// Only enqueue on frontend singular posts/pages if user has edit capabilities.
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		wp_register_script(
			'live-edit-script',
			LIVE_EDIT_URL . '/assets/build/js/main.js',
			[],
			file_exists( LIVE_EDIT_PATH . '/assets/build/js/main.js' ) ? filemtime( LIVE_EDIT_PATH . '/assets/build/js/main.js' ) : '1.0',
			true
		);

		wp_register_style(
			'live-edit-style',
			LIVE_EDIT_URL . '/assets/build/js/main.css',
			[],
			file_exists( LIVE_EDIT_PATH . '/assets/build/js/main.css' ) ? filemtime( LIVE_EDIT_PATH . '/assets/build/js/main.css' ) : '1.0'
		);

		wp_enqueue_script( 'live-edit-script' );
		wp_enqueue_style( 'live-edit-style' );

		// Localize script with necessary data.
		wp_localize_script(
			'live-edit-script',
			'liveEditData',
			[
				'restUrl' => esc_url_raw( rest_url( 'live-edit/v1/update-block' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'postId'  => $post_id,
			]
		);

	}

	/**
	 * To enqueue scripts and styles in admin.
	 *
	 * @param string $hook_suffix Admin page name.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {

		wp_register_script(
			'live-edit-admin-script',
			LIVE_EDIT_URL . '/assets/build/js/admin.js',
			[],
			file_exists( LIVE_EDIT_PATH . '/assets/build/js/admin.js' ) ? filemtime( LIVE_EDIT_PATH . '/assets/build/js/admin.js' ) : '1.0',
			true
		);

		wp_register_style(
			'live-edit-admin-style',
			LIVE_EDIT_URL . '/assets/build/css/admin.css',
			[],
			file_exists( LIVE_EDIT_PATH . '/assets/build/css/admin.css' ) ? filemtime( LIVE_EDIT_PATH . '/assets/build/css/admin.css' ) : '1.0'
		);

		wp_enqueue_script( 'live-edit-admin-script' );
		wp_enqueue_style( 'live-edit-admin-style' );

	}
}
