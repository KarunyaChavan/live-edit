/**
 * Video Block Handler.
 *
 * Opens the WordPress Media Library and tracks video replacements.
 */
export default class VideoHandler {
	/**
	 * Check if this handler supports the given element.
	 *
	 * @param {HTMLElement} element
	 * @return {boolean} True if supported.
	 */
	supports(element) {
		return element.dataset.liveEditType === 'video';
	}

	/**
	 * Enable video selection.
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
	 * Disable video selection.
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
			title: strings.selectVideo || 'Select video',
			button: { text: strings.useVideo || 'Use video' },
			library: { type: 'video' },
			multiple: false,
		});

		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			const video = element.querySelector('video');

			if (video) {
				video.src = attachment.url;
				video.load();
			}

			dirtyBlocks.set(element.dataset.liveEditId, {
				blockIndex: parseInt(element.dataset.liveEditId, 10),
				blockName: element.dataset.liveEditBlock,
				operation: 'update_attributes',
				attributes: {
					id: attachment.id,
					src: attachment.url,
				},
			});
		});

		frame.open();
	}
}
