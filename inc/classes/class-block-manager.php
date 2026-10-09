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
use Live_Edit\Inc\Block_Handlers\Image_Block_Handler;
use Live_Edit\Inc\Block_Handlers\Text_Block_Handler;
use Live_Edit\Inc\Block_Handlers\Video_Block_Handler;

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
			new Image_Block_Handler(),
			new Video_Block_Handler(),
		];

		foreach ( $handlers as $handler ) {
			foreach ( $handler->get_supported_blocks() as $block_name ) {
				$this->handlers[ $block_name ] = $handler;
			}
		}
	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		// Priority 8 ensures this runs before do_blocks.
		add_filter( 'the_content', [ $this, 'inject_live_edit_ids' ], 8 );
	}

	/**
	 * Inject frontend attributes (like data-live-edit-id) dynamically into post content.
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function inject_live_edit_ids( $content ) {
		$post_id = get_the_ID();
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $content;
		}

		if ( ! is_singular() || ! is_main_query() || get_queried_object_id() !== $post_id ) {
			return $content;
		}

		$blocks  = parse_blocks( $content );
		$counter = 0;
		$blocks  = $this->traverse_and_inject_ids( $blocks, $counter );

		return serialize_blocks( $blocks );
	}

	/**
	 * Traverse parsed blocks and inject data-live-edit-id.
	 *
	 * @param array $blocks  Array of blocks.
	 * @param int   $counter Reference to the counter.
	 * @return array Modified blocks array.
	 */
	private function traverse_and_inject_ids( $blocks, &$counter ) {
		foreach ( $blocks as &$block ) {
			$block_name = $block['blockName'];

			if ( ! empty( $block_name ) && isset( $this->handlers[ $block_name ] ) ) {
				$handler            = $this->handlers[ $block_name ];
				$block['innerHTML'] = $handler->inject_frontend_data( $block['innerHTML'], $counter, $block_name, $block['attrs'] ?? [] );
				if ( isset( $block['innerContent'][0] ) && is_string( $block['innerContent'][0] ) ) {
					$block['innerContent'][0] = $block['innerHTML'];
				}
				++$counter;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->traverse_and_inject_ids( $block['innerBlocks'], $counter );
			}
		}

		return $blocks;
	}

	/**
	 * Traverse and update block.
	 *
	 * @param array $blocks       Array of blocks.
	 * @param int   $target_index Target block index.
	 * @param array $payload_data Incoming payload data.
	 * @param int   $counter      Current counter.
	 *
	 * @return bool|\WP_Error True if updated, false if not found, or an error.
	 */
	public function traverse_and_update_block( &$blocks, $target_index, $payload_data, &$counter ) {
		foreach ( $blocks as &$block ) {
			$block_name = $block['blockName'];

			if ( ! empty( $block_name ) && isset( $this->handlers[ $block_name ] ) ) {
				if ( $counter === $target_index ) {
					if ( isset( $payload_data['block_name'] ) && $block_name !== $payload_data['block_name'] ) {
						return new \WP_Error(
							'block_mismatch',
							__( 'The requested block no longer matches the page content.', 'live-edit' ),
							[ 'status' => 409 ]
						);
					}

					$handler = $this->handlers[ $block_name ];
					$valid   = $handler->validate_update( $block, $payload_data );

					if ( is_wp_error( $valid ) ) {
						return $valid;
					}

					$block = $handler->update_block( $block, $payload_data );
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
