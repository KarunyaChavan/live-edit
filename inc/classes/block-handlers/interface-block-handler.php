<?php
/**
 * Block Handler Interface.
 *
 * Defines the contract that all block handlers must implement to support
 * frontend live editing for specific Gutenberg blocks.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc\Block_Handlers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Interface Block_Handler_Interface
 */
interface Block_Handler_Interface {

	/**
	 * Get supported block names.
	 *
	 * @return array List of supported block names.
	 */
	public function get_supported_blocks();

	/**
	 * Validate an incoming block update.
	 *
	 * @param array $block        The parsed block array.
	 * @param array $payload_data The data received from the REST API.
	 *
	 * @return true|\WP_Error True when valid, otherwise an error.
	 */
	public function validate_update( $block, $payload_data );

	/**
	 * Inject frontend attributes (like data-live-edit-id).
	 *
	 * @param string $block_content The HTML content of the block.
	 * @param int    $block_id      The unique ID for the live edit instance.
	 * @param string $block_name    The registered block name.
	 * @param array  $block_attrs   The parsed block attributes.
	 *
	 * @return string Modified block HTML.
	 */
	public function inject_frontend_data( $block_content, $block_id, $block_name, $block_attrs = [] );

	/**
	 * Update the block with new data.
	 *
	 * @param array $block        The parsed block array.
	 * @param array $payload_data The data received from the REST API.
	 *
	 * @return array The updated block array.
	 */
	public function update_block( $block, $payload_data );
}
