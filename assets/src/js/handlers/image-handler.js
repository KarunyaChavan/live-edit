/**
 * Image Block Handler.
 *
 * Opens the WordPress Media Library and tracks image replacements.
 */
export default class ImageHandler {
	/**
	 * Check if this handler supports the given element.
	 *
	 * @param {HTMLElement} element
	 * @return {boolean} True if supported.
	 */
	supports(element) {
		return element.dataset.liveEditType === 'image';
	}

	/**
	 * Enable image selection.
	 *
	 * @param {HTMLElement} element
	 * @param {Map}         dirtyBlocks
	 */
	enable(element, dirtyBlocks) {
		element.classList.add('live-edit__block');
		element._liveEditClickHandler = this.openMediaFrame.bind(
			this,
			element,
			dirtyBlocks
		);
		element.addEventListener('click', element._liveEditClickHandler);
	}

	/**
	 * Disable image selection.
	 *
	 * @param {HTMLElement} element
	 */
	disable(element) {
		element.classList.remove('live-edit__block');

		if (element._liveEditClickHandler) {
			element.removeEventListener('click', element._liveEditClickHandler);
			delete element._liveEditClickHandler;
		}
	}

	/**
	 * Open the WordPress Media Library.
	 *
	 * @param {HTMLElement} element
	 * @param {Map}         dirtyBlocks
	 * @param {Event}       event
	 */
	openMediaFrame(element, dirtyBlocks, event) {
		event.preventDefault();

		if (!window.wp || !window.wp.media) {
			return;
		}

		const strings = window.liveEditData?.strings || {};
		const frame = window.wp.media({
			title: strings.selectImage || 'Select image',
			button: { text: strings.useImage || 'Use image' },
			library: { type: 'image' },
			multiple: false,
		});

		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			const image = element.querySelector('img');
			const sizeSlug = element.dataset.liveEditSize || 'full';
			const selectedSize =
				attachment.sizes?.[sizeSlug] ||
				attachment.sizes?.full ||
				attachment;

			if (image) {
				image.removeAttribute('srcset');
				image.src = selectedSize.url;
				image.alt = attachment.alt || '';
			}

			dirtyBlocks.set(element.dataset.liveEditId, {
				blockIndex: parseInt(element.dataset.liveEditId, 10),
				blockName: element.dataset.liveEditBlock,
				operation: 'update_attributes',
				attributes: {
					id: attachment.id,
					alt: attachment.alt || '',
				},
			});
		});

		frame.open();
	}
}
