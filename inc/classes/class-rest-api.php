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
								'block_index' => [
									'type'              => 'integer',
									'sanitize_callback' => 'absint',
								],
								'block_name'  => [
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_key',
								],
								'operation'   => [
									'type' => 'string',
									'enum' => [ 'update_content', 'update_attributes' ],
								],
								'content'     => [
									'type' => 'string',
								],
								'attributes'  => [
									'type'                 => 'object',
									'additionalProperties' => true,
								],
								'field'       => [
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_text_field',
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
			$text_content = isset( $block_data['content'] ) ? $block_data['content'] : '';

			if ( null === $block_index && null === $field ) {
				continue;
			}

			// Handle direct field updates (like Title).
			if ( 'title' === $field ) {
				$update_args['post_title'] = wp_slash( $text_content );
				continue;
			}

			// Traverse and update the target block via Block Manager.
			$counter = 0;
			$updated = Block_Manager::get_instance()->traverse_and_update_block( $parsed_blocks, $block_index, $block_data, $counter );

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			if ( $updated ) {
				$content_updated = true;
			}
		}

		if ( $content_updated ) {
			$new_post_content            = serialize_blocks( $parsed_blocks );
			$update_args['post_content'] = wp_slash( $new_post_content );
		}

		if ( ! isset( $update_args['post_title'] ) && ! isset( $update_args['post_content'] ) ) {
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
}
