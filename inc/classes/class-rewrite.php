<?php
/**
 * Rewrite class.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc;

use Live_Edit\Inc\Traits\Singleton;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Rewrite
 */
class Rewrite {

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
	protected function setup_hooks() {}
}
