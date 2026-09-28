/**
 * Text Block Handler.
 *
 * Handles contenteditable initialization and input tracking for text-based blocks.
 */
export default class TextHandler {
	/**
	 * Check if this handler supports the given element.
	 *
	 * @param {HTMLElement} element
	 * @return {boolean} True if supported.
	 */
	supports(element) {
		return (
			element.dataset.liveEditType === 'text' ||
			(!element.dataset.liveEditField &&
				element.hasAttribute('data-live-edit-id'))
		);
	}

	/**
	 * Enable editing for the element.
	 *
	 * @param {HTMLElement} element
	 * @param {Map}         dirtyBlocks
	 */
	enable(element, dirtyBlocks) {
		element.setAttribute('contenteditable', 'true');
		element.classList.add('live-edit__block');

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
		const index = element.dataset.liveEditId;
		dirtyBlocks.set(index, {
			blockIndex: parseInt(index, 10),
			blockName: element.dataset.liveEditBlock,
			operation: 'update_content',
			content: element.innerHTML,
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
