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
	 * Inject frontend attributes (like data-live-edit-id).
	 *
	 * @param string $block_content The HTML content of the block.
	 * @param int    $block_id      The unique ID for the live edit instance.
	 *
	 * @return string Modified block HTML.
	 */
	public function inject_frontend_data( $block_content, $block_id );

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
