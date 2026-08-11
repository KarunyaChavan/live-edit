<?php
/**
 * Block Manager class.
 *
 * Manages block handlers and delegates block updates and frontend injections.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc;

use Live_Edit\Inc\Traits\Singleton;
use Live_Edit\Inc\Block_Handlers\Text_Block_Handler;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Block_Manager
 */
class Block_Manager {

	use Singleton;

	/**
	 * Registered block handlers.
	 *
	 * @var array
	 */
	private $handlers = [];

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->register_handlers();
		$this->setup_hooks();
	}

	/**
	 * Register available block handlers.
	 *
	 * @return void
	 */
	private function register_handlers() {
		$handlers = [
			new Text_Block_Handler(),
			// New handlers (e.g. Media_Block_Handler) can be added here in the future.
		];

		foreach ( $handlers as $handler ) {
			foreach ( $handler->get_supported_blocks() as $block_name ) {
				$this->handlers[ $block_name ] = $handler;
			}
		}
	}

	/**
	 * Counter for block indexes.
	 *
	 * @var int
	 */
	private $block_counter = 0;

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		// Reset counter before content rendering.
		add_filter( 'the_content', [ $this, 'reset_counter' ], 1 );
		// Use render_block to inject our frontend attributes securely without regex.
		add_filter( 'render_block', [ $this, 'inject_frontend_attributes' ], 10, 2 );
	}

	/**
	 * Reset block counter.
	 *
	 * @param string $content The content.
	 * @return string The content.
	 */
	public function reset_counter( $content ) {
		$this->block_counter = 0;
		return $content;
	}

	/**
	 * Inject frontend attributes (like data-live-edit-id) dynamically.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string Modified block content.
	 */
	public function inject_frontend_attributes( $block_content, $block ) {
		$post_id = get_the_ID();
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $block_content;
		}

		if ( ! is_singular() || ! is_main_query() || get_queried_object_id() !== $post_id ) {
			return $block_content;
		}

		$block_name = $block['blockName'];

		// If it's a supported block, we increment the counter and inject.
		// Wait, all blocks are counted in DFS whether supported or not, so we must increment for EVERY block
		// if we want the index to match parse_blocks() traversal!
		// Actually, in the old code `class-rest-api.php`:
		// `if ( in_array( $block['blockName'], $supported_blocks, true ) ) { ... ++$counter; }`
		// The old code ONLY incremented the counter for SUPPORTED blocks!
		// So we must do the same.

		if ( empty( $block_name ) || ! isset( $this->handlers[ $block_name ] ) ) {
			return $block_content;
		}

		$handler          = $this->handlers[ $block_name ];
		$modified_content = $handler->inject_frontend_data( $block_content, $this->block_counter );

		++$this->block_counter;

		return $modified_content;
	}

	/**
	 * Traverse and update block.
	 *
	 * @param array $blocks       Array of blocks.
	 * @param int   $target_index Target block index.
	 * @param array $payload_data Incoming payload data.
	 * @param int   $counter      Current counter.
	 *
	 * @return bool|string True if updated, false if not found.
	 */
	public function traverse_and_update_block( &$blocks, $target_index, $payload_data, &$counter ) {
		foreach ( $blocks as &$block ) {
			$block_name = $block['blockName'];

			if ( ! empty( $block_name ) && isset( $this->handlers[ $block_name ] ) ) {
				if ( $counter === $target_index ) {
					$handler = $this->handlers[ $block_name ];
					$block   = $handler->update_block( $block, $payload_data );
					return true;
				}
				++$counter;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$result = $this->traverse_and_update_block( $block['innerBlocks'], $target_index, $payload_data, $counter );
				if ( true === $result ) {
					return $result;
				}
			}
		}

		return false;
	}
}
