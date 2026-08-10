<?php
/**
 * Singleton trait which implements Singleton pattern in any class in which this trait is used.
 *
 * Provides a final get_instance() entry point backed by late static
 * binding and prevents direct instantiation and cloning in classes
 * that make use of this trait.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc\Traits;

trait Singleton {

	/**
	 * Protected class constructor to prevent direct object creation
	 *
	 * This is meant to be overridden in the classes which implement
	 * this trait. This is ideal for doing stuff that you only want to
	 * do once, such as hooking into actions and filters, etc.
	 */
	protected function __construct() {
	}

	/**
	 * Prevent object cloning
	 */
	final protected function __clone() {
	}

	/**
	 * This method returns new or existing Singleton instance
	 * of the class for which it is called. This method is set
	 * as final intentionally, it is not meant to be overridden.
	 *
	 * @return object Singleton instance of the class.
	 */
	final public static function get_instance() {

		/**
		 * Collection of instance.
		 *
		 * @var array
		 */
		static $instance = [];

		/**
		 * If this trait is implemented in a class which has multiple
		 * sub-classes then static::$_instance will be overwritten with the most recent
		 * sub-class instance. Thanks to late static binding
		 * we use get_called_class() to grab the called class name, and store
		 * a key=>value pair for each `classname => instance` in self::$_instance
		 * for each sub-class.
		 */
		$called_class = get_called_class();

		if ( ! isset( $instance[ $called_class ] ) ) {

			$instance[ $called_class ] = new $called_class();

			/**
			 * Dependent items can use the live_edit_singleton_init_{$called_class} hook to execute code
			 */
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Dynamic hook name derived from the called class.
			do_action( sprintf( 'live_edit_singleton_init_%s', $called_class ) );

		}

		return $instance[ $called_class ];
	}
} // End trait
