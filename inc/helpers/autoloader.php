<?php
/**
 * Autoloader file for plugin.
 *
 * Registers a namespace-based autoloader that maps Live_Edit\ class
 * names to their corresponding files under the plugin's inc/ directory.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Auto loader function.
 *
 * @param string $class_name Source namespace.
 *
 * @return void
 */
function autoloader( $class_name = '' ) {

	$resource_path  = false;
	$namespace_root = 'Live_Edit\\';
	$resource       = trim( $class_name, '\\' );

	if ( empty( $resource ) || strpos( $resource, '\\' ) === false || strpos( $resource, $namespace_root ) !== 0 ) {
		// Not our namespace, bail out.
		return;
	}

	// Remove our root namespace.
	$resource = str_replace( $namespace_root, '', $resource );

	$path = explode(
		'\\',
		str_replace( '_', '-', strtolower( $resource ) )
	);

	/**
	 * Time to determine which type of resource path it is,
	 * so that we can deduce the correct file path for it.
	 */
	if ( empty( $path[0] ) || empty( $path[1] ) ) {
		return;
	}

	$directory = '';
	$file_name = '';

	if ( 'inc' === $path[0] ) {

		switch ( $path[1] ) {
			case 'traits':
				$directory = 'traits';
				$file_name = sprintf( 'trait-%s', trim( strtolower( $path[2] ) ) );
				break;

			case 'block-handlers':
				$directory  = 'classes/block-handlers';
				$class_part = trim( strtolower( $path[2] ) );
				if ( str_ends_with( $class_part, '-interface' ) ) {
					$file_name = sprintf( 'interface-%s', str_replace( '-interface', '', $class_part ) );
				} else {
					$file_name = sprintf( 'class-%s', $class_part );
				}
				break;

			// phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment -- Fall-through to default is intentional for generic class loading.
			case 'plugin-configs':
				/**
				 * If there is class name provided for specific directory then load that.
				 * otherwise find in inc/ directory.
				 */
				if ( ! empty( $path[2] ) ) {
					$directory = sprintf( 'classes/%s', $path[1] );
					$file_name = sprintf( 'class-%s', trim( strtolower( $path[2] ) ) );
					break;
				}
			default:
				$directory = 'classes';
				$file_name = sprintf( 'class-%s', trim( strtolower( $path[1] ) ) );
				break;
		}

		$resource_path = sprintf( '%s/inc/%s/%s.php', untrailingslashit( LIVE_EDIT_PATH ), $directory, $file_name );

	}

	$resource_path_valid = validate_file( $resource_path );
	// For Windows platform, validate_file returns 2 so we've added this condition as well.
	if ( ! empty( $resource_path ) && file_exists( $resource_path ) && ( 0 === $resource_path_valid || 2 === $resource_path_valid ) ) {
		// We are already making sure that the file exists and it's valid.
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable, PEAR.Files.IncludingFile.BracketsNotRequired -- Path is validated via validate_file() and file_exists() above; parentheses kept for clarity.
		require_once( $resource_path );
	}
}

spl_autoload_register( '\Live_Edit\Inc\Helpers\autoloader' );
