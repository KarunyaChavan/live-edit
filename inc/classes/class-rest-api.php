<?php
/**
 * REST API class.
 *
 * Registers the REST endpoint that persists live-edited block content,
 * enforces nonce-based permission checks and injects editing IDs into
 * the frontend content markup.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc;

use Live_Edit\Inc\Traits\Singleton;
use WP_Error;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_API
 */
class Rest_API {

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
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		// Priority 8 ensures this runs before do_blocks.
		add_filter( 'the_content', [ $this, 'inject_live_edit_ids' ], 8 );
	}

	/**
	 * Inject data-live-edit-id attributes into supported blocks for frontend syncing.
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function inject_live_edit_ids( $content ) {
		$post_id = get_the_ID();
		// Only inject if the user has edit capabilities for this specific post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $content;
		}

		// Only inject on the main singular query so block indexes stay aligned with the save-side traversal.
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
		$supported_blocks = [ 'core/paragraph', 'core/heading' ];

		foreach ( $blocks as &$block ) {
			if ( in_array( $block['blockName'], $supported_blocks, true ) ) {
				$html = trim( $block['innerHTML'] );

				// Split the opening tag into tag name (group 1) and the rest of the
				// attributes up to the closing ">" (group 2), so the live edit ID can
				// be inserted directly after the tag name. Anchored at ^ so only the
				// first tag of the block is ever matched.
				if ( preg_match( '/^(<[a-zA-Z0-9]+)([^>]*>)/', $html, $matches ) ) {
					$block['innerHTML'] = $matches[1] . ' data-live-edit-id="' . $counter . '"' . $matches[2] . substr( $html, strlen( $matches[0] ) );
					if ( isset( $block['innerContent'][0] ) && is_string( $block['innerContent'][0] ) ) {
						$block['innerContent'][0] = $block['innerHTML'];
					}
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
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'live-edit/v1',
			'/update-block',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_block' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'post_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'blocks'  => [
						'required' => true,
						'type'     => 'array',
						'items'    => [
							'type'       => 'object',
							'properties' => [
								'block_index'  => [
									'type'              => 'integer',
									'sanitize_callback' => 'absint',
								],
								'field'        => [
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_text_field',
								],
								'text_content' => [
									'type' => 'string',
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Check permissions for the REST route.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		$post_id = $request->get_param( 'post_id' );

		// Check if user has edit capability for this specific post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to edit this post.', 'live-edit' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Handle batched block updates.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function update_block( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$blocks  = $request->get_param( 'blocks' );

		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return new WP_Error(
				'missing_parameters',
				__( 'Blocks array must be provided.', 'live-edit' ),
				[ 'status' => 400 ]
			);
		}

		// wp_check_post_lock is an admin function, we need to include it.
		if ( ! function_exists( 'wp_get_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}

		// Check if the post is currently being edited by someone else (locked).
		if ( wp_check_post_lock( $post_id ) ) {
			return new WP_Error(
				'post_locked',
				__( 'This post is currently being edited by another user. Please try again later.', 'live-edit' ),
				[ 'status' => 403 ]
			);
		}

		// Get the latest post content.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'live-edit' ),
				[ 'status' => 404 ]
			);
		}

		$parsed_blocks   = parse_blocks( $post->post_content );
		$content_updated = false;

		$update_args = [
			'ID' => $post_id,
		];

		foreach ( $blocks as $block_data ) {
			$field        = isset( $block_data['field'] ) ? $block_data['field'] : null;
			$block_index  = isset( $block_data['block_index'] ) ? $block_data['block_index'] : null;
			$text_content = isset( $block_data['text_content'] ) ? $block_data['text_content'] : '';

			if ( null === $block_index && null === $field ) {
				continue;
			}

			// Handle direct field updates (like Title).
			if ( 'title' === $field ) {
				$update_args['post_title'] = wp_slash( $text_content );
				continue;
			}

			// Traverse and update the target block.
			$counter = 0;
			$updated = $this->traverse_and_update_block( $parsed_blocks, $block_index, $text_content, $counter );

			if ( true === $updated ) {
				$content_updated = true;
			} elseif ( 'invalid_format' === $updated ) {
				return new WP_Error(
					'block_update_failed',
					__( 'The block content format is invalid and cannot be updated.', 'live-edit' ),
					[ 'status' => 400 ]
				);
			}
		}

		if ( $content_updated ) {
			$new_post_content            = serialize_blocks( $parsed_blocks );
			$update_args['post_content'] = wp_slash( $new_post_content );
		}

		if ( empty( $update_args['post_title'] ) && empty( $update_args['post_content'] ) ) {
			return new WP_Error(
				'no_changes',
				__( 'No valid updates found.', 'live-edit' ),
				[ 'status' => 400 ]
			);
		}

		// Update the post, ensuring a revision is created.
		$result = wp_update_post( $update_args, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			[
				'success' => true,
				'message' => __( 'Blocks updated successfully.', 'live-edit' ),
			]
		);
	}

	/**
	 * Traverse blocks depth-first to find and update the n-th editable block safely.
	 *
	 * @param array  $blocks       Array of parsed blocks.
	 * @param int    $target_index The block index to find.
	 * @param string $new_text     The new text content (which may contain HTML).
	 * @param int    $counter      Current block counter (passed by reference).
	 *
	 * @return bool|string True if updated, false if not found, 'invalid_format' if match fails.
	 */
	private function traverse_and_update_block( &$blocks, $target_index, $new_text, &$counter ) {
		$supported_blocks = [ 'core/paragraph', 'core/heading' ];

		foreach ( $blocks as &$block ) {
			if ( in_array( $block['blockName'], $supported_blocks, true ) ) {
				if ( $counter === $target_index ) {
					$html = trim( $block['innerHTML'] );

					// Extract the whole opening tag ("<p class="x">") into one group so
					// it can be rebuilt verbatim around the new content. The greedy
					// [^>]* stops at the first ">", which avoids lazy matching that
					// could consume into nested block content on a failed match.
					if ( preg_match( '/^(<[a-zA-Z0-9]+[^>]*>)/', $html, $matches ) ) {
						$open_tag = $matches[1];

						// Capture the tag name ("<p ..." → "p") so the matching closing
						// tag ("</p>") can be reconstructed for the updated innerHTML.
						preg_match( '/^<([a-zA-Z0-9]+)/', $open_tag, $tag_match );
						$tag_name  = $tag_match[1];
						$close_tag = "</{$tag_name}>";

						// Sanitize the HTML content safely using wp_kses_post to preserve allowed inline tags.
						$sanitized_html = wp_kses_post( $new_text );

						$block['innerHTML'] = $open_tag . $sanitized_html . $close_tag;
					} else {
						// False success guard: if it didn't match the regex, we couldn't edit it.
						return 'invalid_format';
					}

					// Update innerContent to match innerHTML for correct serialization.
					if ( isset( $block['innerContent'][0] ) && is_string( $block['innerContent'][0] ) ) {
						$block['innerContent'][0] = $block['innerHTML'];
					}

					return true;
				}
				++$counter;
			}

			// Traverse inner blocks (depth-first).
			if ( ! empty( $block['innerBlocks'] ) ) {
				$result = $this->traverse_and_update_block( $block['innerBlocks'], $target_index, $new_text, $counter );
				if ( true === $result || 'invalid_format' === $result ) {
					return $result;
				}
			}
		}

		return false;
	}
}
