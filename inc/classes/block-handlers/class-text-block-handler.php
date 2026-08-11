<?php
/**
 * Text Block Handler.
 *
 * Handles live editing for text-based blocks like Paragraphs and Headings.
 *
 * @package live-edit
 */

namespace Live_Edit\Inc\Block_Handlers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Text_Block_Handler
 */
class Text_Block_Handler implements Block_Handler_Interface {

	/**
	 * Get supported block names.
	 *
	 * @return array List of supported block names.
	 */
	public function get_supported_blocks() {
		return [ 'core/paragraph', 'core/heading' ];
	}

	/**
	 * Inject frontend attributes (like data-live-edit-id).
	 *
	 * @param string $block_content The HTML content of the block.
	 * @param int    $block_id      The unique ID for the live edit instance.
	 *
	 * @return string Modified block HTML.
	 */
	public function inject_frontend_data( $block_content, $block_id ) {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_content;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_content );

		if ( $tags->next_tag() ) {
			$tags->set_attribute( 'data-live-edit-id', (string) $block_id );
			$tags->set_attribute( 'data-live-edit-type', 'text' );
			return $tags->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Update the block with new data.
	 *
	 * @param array $block        The parsed block array.
	 * @param array $payload_data The data received from the REST API.
	 *
	 * @return array The updated block array.
	 */
	public function update_block( $block, $payload_data ) {
		$text_content = isset( $payload_data['text_content'] ) ? $payload_data['text_content'] : '';

		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block;
		}

		$sanitized_html = wp_kses_post( $text_content );

		// The original block HTML might have attributes we need to keep.
		// We replace the inner HTML while keeping the outer tag structure intact.
		$tags = new \WP_HTML_Tag_Processor( $block['innerHTML'] );

		if ( $tags->next_tag() ) {
			$tag_name   = $tags->get_tag();
			$attributes = '';

			// WP_HTML_Tag_Processor in 6.2/6.3 doesn't have a direct way to get all attributes or replace inner content easily.
			// So we can extract the full opening tag, then reconstruct it.
			// Since we want to drop Regex entirely, let's use DOMDocument or a safer approach.
			// Wait, WP_HTML_Tag_Processor updates attributes. To replace inner HTML:
			// actually we can just capture the opening tag, and use the tag name to make the closing tag.
			// Is there a better way without DOMDocument?
			// Since Gutenberg provides the block content as `innerHTML`, for a paragraph it is `<p ...>...</p>`.

			// Let's use preg_match just for the opening tag but correctly handling the closing tag,
			// OR we can use DOMDocument.
			// DOMDocument is safer for modifying innerHTML.
			$dom           = new \DOMDocument();
			$dom->encoding = 'UTF-8';

			// Suppress warnings for HTML5 elements.
			$libxml_previous_state = libxml_use_internal_errors( true );

			// Wrap in a div with proper encoding to ensure multibyte string safety.
			$dom->loadHTML( '<?xml encoding="UTF-8"><div>' . $block['innerHTML'] . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			libxml_clear_errors();
			libxml_use_internal_errors( $libxml_previous_state );

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Built-in DOMDocument property.
			$wrapper = $dom->documentElement; // This is the <div> we added.
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Built-in DOMDocument property.
			if ( $wrapper && $wrapper->firstChild ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Built-in DOMDocument property.
				$element = $wrapper->firstChild;

				// Clear existing children.
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Built-in DOMDocument property.
				while ( $element->hasChildNodes() ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Built-in DOMDocument property.
					$element->removeChild( $element->firstChild );
				}

				// Create a document fragment with the new sanitized HTML.
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXML( $sanitized_html );
				$element->appendChild( $fragment );

				// Save HTML, stripping the wrapper div.
				$new_html = '';
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Built-in DOMDocument property.
				foreach ( $wrapper->childNodes as $node ) {
					$new_html .= $dom->saveHTML( $node );
				}

				$block['innerHTML'] = $new_html;
				if ( isset( $block['innerContent'][0] ) && is_string( $block['innerContent'][0] ) ) {
					$block['innerContent'][0] = $block['innerHTML'];
				}
			}
		}

		return $block;
	}
}
