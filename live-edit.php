<?php
/**
 * Plugin Name: Live Edit
 * Description: Live Edit - Edit what you see, where you see it. Inline frontend editor for WordPress Gutenberg blocks.
 * Plugin URI:  https://github.com/KarunyaChavan/live-edit
 * Author:      Karunya Chavan
 * Author URI:  https://github.com/KarunyaChavan
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version:     1.0
 * Text Domain: live-edit
 * Domain Path: /languages
 *
 * @package live-edit
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define( 'LIVE_EDIT_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'LIVE_EDIT_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant
require_once LIVE_EDIT_PATH . '/inc/helpers/autoloader.php';
require_once LIVE_EDIT_PATH . '/inc/helpers/custom-functions.php';
// phpcs:enable WordPressVIPMinimum.Files.IncludingFile.UsingCustomConstant

/**
 * To load plugin manifest class.
 *
 * @return void
 */
function live_edit_plugin_loader() {
	\Live_Edit\Inc\Plugin::get_instance();
}

live_edit_plugin_loader();
