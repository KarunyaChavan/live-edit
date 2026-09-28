<?php
/**
 * Video Block Handler.
 *
 * Handles frontend replacement of core video blocks.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc\Block_Handlers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Video_Block_Handler
 */
class Video_Block_Handler implements Block_Handler_Interface {

	/**
	 * Get supported block names.
	 *
	 * @return array List of supported block names.
	 */
	public function get_supported_blocks() {
		return [ 'core/video' ];
	}

	/**
	 * Validate an incoming video update.
	 *
	 * @param array $block        The parsed block array.
	 * @param array $payload_data The data received from the REST API.
	 *
	 * @return true|\WP_Error True when valid, otherwise an error.
	 */
	public function validate_update( $block, $payload_data ) {
		$attributes = $payload_data['attributes'] ?? [];
		$attachment = isset( $attributes['id'] ) ? get_post( absint( $attributes['id'] ) ) : null;

		if ( 'update_attributes' !== ( $payload_data['operation'] ?? '' ) || empty( $attachment ) || 'attachment' !== $attachment->post_type || ! current_user_can( 'edit_post', $attachment->ID ) ) {
			return new \WP_Error(
				'invalid_video_update',
				__( 'A valid editable video attachment is required.', 'live-edit' ),
				[ 'status' => 400 ]
			);
		}

		if ( 0 !== strpos( (string) get_post_mime_type( $attachment ), 'video/' ) ) {
			return new \WP_Error(
				'invalid_video_type',
				__( 'The selected attachment is not a video.', 'live-edit' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Inject frontend editing metadata.
	 *
	 * @param string $block_content The HTML content of the block.
	 * @param int    $block_id      The unique ID for the live edit instance.
	 * @param string $block_name    The registered block name.
	 * @param array  $block_attrs   The parsed block attributes.
	 *
	 * @return string Modified block HTML.
	 */
	public function inject_frontend_data( $block_content, $block_id, $block_name, $block_attrs = [] ) {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_content;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_content );

		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'data-live-edit-id', (string) $block_id );
			$tags->set_attribute( 'data-live-edit-type', 'video' );
			$tags->set_attribute( 'data-live-edit-block', $block_name );
			return $tags->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Update a video block with a selected attachment.
	 *
	 * @param array $block        The parsed block array.
	 * @param array $payload_data The data received from the REST API.
	 *
	 * @return array The updated block array.
	 */
	public function update_block( $block, $payload_data ) {
		$attributes  = $payload_data['attributes'];
		$attachment  = get_post( absint( $attributes['id'] ) );
		$block_attrs = $block['attrs'] ?? [];
		$video_url   = esc_url_raw( wp_get_attachment_url( $attachment->ID ) );

		$block_attrs['id']  = $attachment->ID;
		$block_attrs['src'] = $video_url;
		$block['attrs']     = $block_attrs;
		$block['innerHTML'] = $this->update_video_markup( $block['innerHTML'], $block_attrs, $video_url );

		if ( isset( $block['innerContent'][0] ) && is_string( $block['innerContent'][0] ) ) {
			$block['innerContent'][0] = $block['innerHTML'];
		}

		return $block;
	}

	/**
	 * Replace video source attributes while preserving the block wrapper markup.
	 *
	 * @param string $block_html  The existing block HTML.
	 * @param array  $block_attrs The updated block attributes.
	 * @param string $video_url    The selected video URL.
	 *
	 * @return string Updated block HTML.
	 */
	private function update_video_markup( $block_html, $block_attrs, $video_url ) {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_html;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_html );
		if ( ! $tags->next_tag( 'video' ) ) {
			return $block_html;
		}

		$tags->set_attribute( 'src', $video_url );
		if ( isset( $block_attrs['poster'] ) ) {
			$tags->set_attribute( 'poster', esc_url_raw( $block_attrs['poster'] ) );
		}

		while ( $tags->next_tag( 'source' ) ) {
			$tags->set_attribute( 'src', $video_url );
		}

		return $tags->get_updated_html();
	}
}
