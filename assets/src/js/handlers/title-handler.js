/**
 * Title Block Handler.
 *
 * Handles contenteditable initialization and input tracking for the post title.
 */
export default class TitleHandler {
	/**
	 * Check if this handler supports the given element.
	 *
	 * @param {HTMLElement} element
	 * @return {boolean} True if supported.
	 */
	supports(element) {
		return (
			element.dataset.liveEditField === 'title' ||
			element.classList.contains('wp-block-post-title') ||
			element.classList.contains('entry-title')
		);
	}

	/**
	 * Enable editing for the element.
	 *
	 * @param {HTMLElement} element
	 * @param {Map}         dirtyBlocks
	 */
	enable(element, dirtyBlocks) {
		element.setAttribute('contenteditable', 'plaintext-only');
		element.classList.add('live-edit__block');
		element.dataset.liveEditField = 'title';

		// Bind event handlers, saving references so we can remove them later.
		element._liveEditInputHandler = this.handleInput.bind(
			this,
			element,
			dirtyBlocks
		);
		element._liveEditClickHandler = this.preventNav.bind(this);

		element.addEventListener('input', element._liveEditInputHandler);
		element.addEventListener('click', element._liveEditClickHandler);
	}

	/**
	 * Disable editing for the element.
	 *
	 * @param {HTMLElement} element
	 */
	disable(element) {
		element.removeAttribute('contenteditable');
		element.classList.remove('live-edit__block');
		// We can leave dataset.liveEditField intact.

		if (element._liveEditInputHandler) {
			element.removeEventListener('input', element._liveEditInputHandler);
			delete element._liveEditInputHandler;
		}

		if (element._liveEditClickHandler) {
			element.removeEventListener('click', element._liveEditClickHandler);
			delete element._liveEditClickHandler;
		}
	}

	/**
	 * Handle input events to mark the block as dirty.
	 *
	 * @param {HTMLElement} element
	 * @param {Map}         dirtyBlocks
	 */
	handleInput(element, dirtyBlocks) {
		dirtyBlocks.set('title', {
			field: 'title',
			content: element.innerText,
		});
	}

	/**
	 * Prevent navigation on links inside editable blocks.
	 *
	 * @param {Event} e
	 */
	preventNav(e) {
		if (e.target.closest('a')) {
			e.preventDefault();
		}
	}
}
