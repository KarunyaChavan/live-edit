/**
 * Block Registry.
 *
 * Maintains a registry of available block handlers and delegates
 * element initialization to them.
 */
import TextHandler from './handlers/text-handler';
import TitleHandler from './handlers/title-handler';
import ImageHandler from './handlers/image-handler';
import VideoHandler from './handlers/video-handler';

class BlockRegistry {
	constructor() {
		this.handlers = [];
		this.registerDefaultHandlers();
	}

	/**
	 * Register the default supported block handlers.
	 */
	registerDefaultHandlers() {
		this.register(new TitleHandler());
		this.register(new ImageHandler());
		this.register(new VideoHandler());
		this.register(new TextHandler());
	}

	/**
	 * Register a new block handler.
	 *
	 * @param {Object} handler
	 */
	register(handler) {
		this.handlers.push(handler);
	}

	/**
	 * Get the appropriate handler for a given element.
	 *
	 * @param {HTMLElement} element
	 * @return {Object|null} Handler object or null if none.
	 */
	getHandlerFor(element) {
		return (
			this.handlers.find((handler) => handler.supports(element)) || null
		);
	}
}

export default new BlockRegistry();
