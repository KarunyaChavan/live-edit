<?php
/**
 * Plugin manifest class.
 *
 * Bootstrap entry point of the plugin. Instantiates all other plugin
 * classes when the plugin is loaded.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc;

use Live_Edit\Inc\Traits\Singleton;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
class Plugin {

	use Singleton;

	/**
	 * Construct method.
	 */
	protected function __construct() {

		// Load plugin classes.
		Assets::get_instance();
		Rewrite::get_instance();
		SEO::get_instance();
		Admin_Bar::get_instance();
		Rest_API::get_instance();
	}
}
