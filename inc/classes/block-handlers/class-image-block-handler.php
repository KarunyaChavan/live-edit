<?php
/**
 * Image Block Handler.
 *
 * Handles frontend replacement of core image blocks.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc\Block_Handlers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Image_Block_Handler
 */
class Image_Block_Handler implements Block_Handler_Interface {

	/**
	 * Get supported block names.
	 *
	 * @return array List of supported block names.
	 */
	public function get_supported_blocks() {
		return [ 'core/image' ];
	}

	/**
	 * Validate an incoming image update.
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
				'invalid_image_update',
				__( 'A valid editable image attachment is required.', 'live-edit' ),
				[ 'status' => 400 ]
			);
		}

		if ( 0 !== strpos( (string) get_post_mime_type( $attachment ), 'image/' ) ) {
			return new \WP_Error(
				'invalid_image_type',
				__( 'The selected attachment is not an image.', 'live-edit' ),
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
			$tags->set_attribute( 'data-live-edit-type', 'image' );
			$tags->set_attribute( 'data-live-edit-block', $block_name );

			if ( ! empty( $block_attrs['sizeSlug'] ) ) {
				$tags->set_attribute( 'data-live-edit-size', sanitize_key( $block_attrs['sizeSlug'] ) );
			}

			return $tags->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Update an image block with a selected attachment.
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
		$alt         = isset( $attributes['alt'] ) ? sanitize_text_field( $attributes['alt'] ) : get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

		$block_attrs['id']  = $attachment->ID;
		$block_attrs['url'] = esc_url_raw( wp_get_attachment_url( $attachment->ID ) );
		$block_attrs['alt'] = $alt;

		if ( isset( $attributes['caption'] ) ) {
			$block_attrs['caption'] = wp_kses_post( $attributes['caption'] );
		}

		$block['attrs']     = $block_attrs;
		$block['innerHTML'] = $this->update_image_markup( $block['innerHTML'], $block_attrs, $attachment->ID, $alt );

		if ( isset( $block['innerContent'][0] ) && is_string( $block['innerContent'][0] ) ) {
			$block['innerContent'][0] = $block['innerHTML'];
		}

		return $block;
	}

	/**
	 * Replace the image attributes while preserving the block wrapper markup.
	 *
	 * @param string $block_html  The existing block HTML.
	 * @param array  $block_attrs The updated block attributes.
	 * @param int    $attachment_id The selected attachment ID.
	 * @param string $alt          The selected image alt text.
	 *
	 * @return string Updated block HTML.
	 */
	private function update_image_markup( $block_html, $block_attrs, $attachment_id, $alt ) {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_html;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_html );
		if ( ! $tags->next_tag( 'img' ) ) {
			return $block_html;
		}

		$tags->set_attribute( 'src', $block_attrs['url'] );
		$tags->set_attribute( 'alt', $alt );
		$class_name = $tags->get_attribute( 'class' );
		if ( $class_name ) {
			$tags->set_attribute( 'class', preg_replace( '/\bwp-image-\d+\b/', 'wp-image-' . $attachment_id, $class_name ) );
		}

		return $tags->get_updated_html();
	}
}
