/**
 * Live Edit frontend engine.
 *
 * Toggles inline editing mode on the frontend, tracks edited blocks in a
 * dirty map, provides save feedback via the admin bar and toasts, and
 * persists changes through the plugin's REST endpoint.
 */

/**
 * Internal dependencies
 */
import '../css/main.scss';
import blockRegistry from './block-registry';

/**
 * Class representing the Live Edit frontend manager.
 */
class LiveEdit {
	/**
	 * Constructor. Initializes state, maps, buttons, and event listeners.
	 */
	constructor() {
		this.isActive = false;
		this.dirtyBlocks = new Map();

		this.toggleBtn = document.querySelector(
			'#wp-admin-bar-live-edit-toggle a'
		);
		this.saveBtn = document.querySelector('#wp-admin-bar-live-edit-save');

		if (!this.toggleBtn) {
			return;
		}

		this.init();
	}

	/**
	 * Initialize event listeners.
	 */
	init() {
		this.toggleBtn.addEventListener('click', (e) => {
			e.preventDefault();
			this.toggle();
		});

		if (this.saveBtn) {
			const saveLink = this.saveBtn.querySelector('a');
			if (saveLink) {
				saveLink.addEventListener('click', (e) => {
					e.preventDefault();
					this.save();
				});
			}
		}

		// Prevent accidental navigation with unsaved changes.
		window.addEventListener('beforeunload', (e) => {
			if (this.dirtyBlocks.size > 0) {
				e.preventDefault();
				e.returnValue = '';
			}
		});
	}

	/**
	 * Toggle the live edit state.
	 */
	toggle() {
		this.isActive = !this.isActive;
		document.body.classList.toggle('live-edit--active', this.isActive);

		if (this.isActive) {
			this.enableEditing();
			if (this.saveBtn) {
				this.saveBtn.classList.remove('live-edit__save--hidden');
			}
		} else {
			this.disableEditing();
			if (this.saveBtn) {
				this.saveBtn.classList.add('live-edit__save--hidden');
			}
		}
	}

	/**
	 * Get the live edit blocks injected by the server.
	 *
	 * @return {NodeList} List of editable block elements.
	 */
	getBlocks() {
		return document.querySelectorAll('[data-live-edit-id]');
	}

	/**
	 * Enable editing on supported elements.
	 */
	enableEditing() {
		const titleEl = document.querySelector(
			'.wp-block-post-title, .entry-title'
		);
		const blocks = Array.from(this.getBlocks());
		const allElements = [...blocks];

		if (titleEl) {
			allElements.push(titleEl);
		}

		allElements.forEach((element) => {
			const handler = blockRegistry.getHandlerFor(element);
			if (handler) {
				handler.enable(element, this.dirtyBlocks);
				element._liveEditHandler = handler;
			}
		});
	}

	/**
	 * Disable editing.
	 */
	disableEditing() {
		const titleEl = document.querySelector(
			'.wp-block-post-title, .entry-title'
		);
		const blocks = Array.from(this.getBlocks());
		const allElements = [...blocks];

		if (titleEl) {
			allElements.push(titleEl);
		}

		allElements.forEach((element) => {
			if (element._liveEditHandler) {
				element._liveEditHandler.disable(element);
				delete element._liveEditHandler;
			}
		});
	}

	/**
	 * Display a toast notification.
	 *
	 * @param {string}  message
	 * @param {boolean} isError
	 */
	showToast(message, isError = false) {
		const toast = document.createElement('div');
		toast.textContent = message;
		toast.style.position = 'fixed';
		toast.style.bottom = '20px';
		toast.style.right = '20px';
		toast.style.padding = '12px 24px';
		toast.style.background = isError ? '#d63638' : '#00a32a';
		toast.style.color = '#fff';
		toast.style.borderRadius = '4px';
		toast.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
		toast.style.zIndex = '999999';
		toast.style.fontSize = '14px';
		toast.style.fontFamily =
			'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
		document.body.appendChild(toast);

		setTimeout(() => {
			toast.remove();
		}, 3000);
	}

	/**
	 * Briefly flash a state modifier on the save button element.
	 *
	 * @param {string} modifier Modifier class to apply.
	 */
	flashSaveState(modifier) {
		if (!this.saveBtn) {
			return;
		}

		this.saveBtn.classList.add(modifier);
		window.setTimeout(() => {
			this.saveBtn.classList.remove(modifier);
		}, 2000);
	}

	/**
	 * Save dirty blocks to REST API.
	 */
	async save() {
		if (this.dirtyBlocks.size === 0) {
			this.showToast('No changes to save.');
			return;
		}

		const data = window.liveEditData;
		if (!data) {
			this.showToast('Live Edit: missing localized data.', true);
			return;
		}

		const saveLink = this.saveBtn.querySelector('a');
		const originalText = saveLink.textContent;
		saveLink.textContent = 'Saving...';
		saveLink.style.pointerEvents = 'none';

		let hasError = false;

		try {
			const blocksToUpdate = [];
			for (const [, value] of this.dirtyBlocks.entries()) {
				const blockData = {
					text_content: value.textContent,
				};

				if (value.field) {
					blockData.field = value.field;
				} else {
					blockData.block_index = value.blockIndex;
				}
				blocksToUpdate.push(blockData);
			}

			const payload = {
				post_id: data.postId,
				blocks: blocksToUpdate,
			};

			const response = await fetch(data.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': data.nonce,
				},
				body: JSON.stringify(payload),
			});

			if (!response.ok) {
				hasError = true;
			}
		} catch {
			hasError = true;
		} finally {
			saveLink.textContent = originalText;
			saveLink.style.pointerEvents = 'auto';
		}

		if (!hasError) {
			this.dirtyBlocks.clear();
			this.flashSaveState('live-edit__save--success');
			this.showToast('Changes saved successfully!');
		} else {
			this.flashSaveState('live-edit__save--error');
			this.showToast('Some changes failed to save.', true);
		}
	}
}

document.addEventListener('DOMContentLoaded', () => {
	new LiveEdit();
});
