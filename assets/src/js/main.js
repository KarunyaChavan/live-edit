/* eslint-disable import/no-unresolved, import/no-extraneous-dependencies, import/no-duplicates */
/**
 * Live Edit Frontend Engine
 */

/**
 * Internal dependencies
 */
import '../css/main.scss';
/* eslint-enable import/no-unresolved, import/no-extraneous-dependencies, import/no-duplicates */

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

		this.toggleBtn = document.querySelector( '#wp-admin-bar-live-edit-toggle a' );
		this.saveBtn = document.querySelector( '#wp-admin-bar-live-edit-save' );

		if ( ! this.toggleBtn ) {
			return;
		}

		this.init();
	}

	/**
	 * Initialize event listeners.
	 */
	init() {
		this.toggleBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			this.toggle();
		} );

		if ( this.saveBtn ) {
			const saveLink = this.saveBtn.querySelector( 'a' );
			if ( saveLink ) {
				saveLink.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					this.save();
				} );
			}
		}

		// Prevent accidental navigation with unsaved changes.
		window.addEventListener( 'beforeunload', ( e ) => {
			if ( this.dirtyBlocks.size > 0 ) {
				e.preventDefault();
				e.returnValue = '';
			}
		} );
	}

	/**
	 * Toggle the live edit state.
	 */
	toggle() {
		this.isActive = ! this.isActive;
		document.body.classList.toggle( 'live-edit--active', this.isActive );

		if ( this.isActive ) {
			this.enableEditing();
			if ( this.saveBtn ) {
				this.saveBtn.classList.remove( 'live-edit__save--hidden' );
			}
		} else {
			this.disableEditing();
			if ( this.saveBtn ) {
				this.saveBtn.classList.add( 'live-edit__save--hidden' );
			}
		}
	}

	/**
	 * Get the live edit blocks injected by the server.
	 *
	 * @return {NodeList} List of editable block elements.
	 */
	getBlocks() {
		return document.querySelectorAll( '[data-live-edit-id]' );
	}

	/**
	 * Enable editing on supported elements.
	 */
	enableEditing() {
		const titleEl = document.querySelector( '.wp-block-post-title, .entry-title' );
		if ( titleEl ) {
			titleEl.setAttribute( 'contenteditable', 'plaintext-only' );
			titleEl.classList.add( 'live-edit__block' );
			titleEl.dataset.liveEditField = 'title';
			titleEl.addEventListener( 'input', this.handleInput );
			titleEl.addEventListener( 'click', this.preventNav );
		}

		const blocks = this.getBlocks();
		blocks.forEach( ( block ) => {
			block.setAttribute( 'contenteditable', 'true' );
			block.classList.add( 'live-edit__block' );
			block.addEventListener( 'input', this.handleInput );
			block.addEventListener( 'click', this.preventNav );
		} );
	}

	/**
	 * Disable editing.
	 */
	disableEditing() {
		const titleEl = document.querySelector( '.wp-block-post-title, .entry-title' );
		if ( titleEl ) {
			titleEl.removeAttribute( 'contenteditable' );
			titleEl.classList.remove( 'live-edit__block' );
			titleEl.removeEventListener( 'input', this.handleInput );
			titleEl.removeEventListener( 'click', this.preventNav );
		}

		const blocks = this.getBlocks();
		blocks.forEach( ( block ) => {
			block.removeAttribute( 'contenteditable' );
			block.classList.remove( 'live-edit__block' );
			block.removeEventListener( 'input', this.handleInput );
			block.removeEventListener( 'click', this.preventNav );
		} );
	}

	/**
	 * Handle content input.
	 *
	 * @param {Event} e
	 */
	handleInput = ( e ) => {
		const block = e.currentTarget;
		if ( block.dataset.liveEditField === 'title' ) {
			this.dirtyBlocks.set( 'title', {
				field: 'title',
				textContent: block.innerText,
			} );
		} else {
			const index = block.dataset.liveEditId;
			this.dirtyBlocks.set( index, {
				blockIndex: parseInt( index, 10 ),
				textContent: block.innerHTML,
			} );
		}
	};

	/**
	 * Prevent navigation on links inside editable blocks.
	 *
	 * @param {Event} e
	 */
	preventNav = ( e ) => {
		if ( e.target.closest( 'a' ) ) {
			e.preventDefault();
		}
	};

	/**
	 * Display a toast notification.
	 *
	 * @param {string}  message
	 * @param {boolean} isError
	 */
	showToast( message, isError = false ) {
		const toast = document.createElement( 'div' );
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
		toast.style.fontFamily = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
		document.body.appendChild( toast );

		setTimeout( () => {
			toast.remove();
		}, 3000 );
	}

	/**
	 * Briefly flash a state modifier on the save button element.
	 *
	 * @param {string} modifier Modifier class to apply.
	 */
	flashSaveState( modifier ) {
		if ( ! this.saveBtn ) {
			return;
		}

		this.saveBtn.classList.add( modifier );
		window.setTimeout( () => {
			this.saveBtn.classList.remove( modifier );
		}, 2000 );
	}

	/**
	 * Save dirty blocks to REST API.
	 */
	async save() {
		if ( this.dirtyBlocks.size === 0 ) {
			this.showToast( 'No changes to save.' );
			return;
		}

		const data = window.liveEditData;
		if ( ! data ) {
			this.showToast( 'Live Edit: missing localized data.', true );
			return;
		}

		const saveLink = this.saveBtn.querySelector( 'a' );
		const originalText = saveLink.textContent;
		saveLink.textContent = 'Saving...';
		saveLink.style.pointerEvents = 'none';

		let hasError = false;

		try {
			for ( const [ , value ] of this.dirtyBlocks.entries() ) {
				const payload = {
					post_id: data.postId,
					text_content: value.textContent,
				};

				if ( value.field ) {
					payload.field = value.field;
				} else {
					payload.block_index = value.blockIndex;
				}

				try {
					const response = await fetch( data.restUrl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': data.nonce,
						},
						body: JSON.stringify( payload ),
					} );

					if ( ! response.ok ) {
						hasError = true;
					}
				} catch ( error ) {
					hasError = true;
				}
			}
		} finally {
			saveLink.textContent = originalText;
			saveLink.style.pointerEvents = 'auto';
		}

		if ( ! hasError ) {
			this.dirtyBlocks.clear();
			this.flashSaveState( 'live-edit__save--success' );
			this.showToast( 'Changes saved successfully!' );
		} else {
			this.flashSaveState( 'live-edit__save--error' );
			this.showToast( 'Some changes failed to save.', true );
		}
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	// eslint-disable-next-line no-new
	new LiveEdit();
} );
