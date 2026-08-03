# LiveEdit for Gutenberg

> **Edit WordPress where your content lives.**

LiveEdit is a modern frontend editing layer for WordPress Gutenberg blocks. It transforms any published page into an editable canvas for users with appropriate permissions, giving you a true "What You See Is What You Get" (WYSIWYG) editing experience akin to Notion or Webflow.

## Why LiveEdit?

WordPress has evolved into a full CMS, yet the editing experience often requires users to leave the page they’re viewing, navigate the backend, and find the corresponding block just to fix a simple typo. LiveEdit removes that friction.

- **No wp-admin:** Stay on the frontend.
- **No context switching:** Edit the page you are looking at.
- **Client-friendly:** Prevent layout breaks by only exposing inline text and image editing controls to clients.

## Features (Roadmap)

- **Inline Text Editing:** Click any heading or paragraph. Start typing. Save instantly.
- **One-Click Image Replacement:** Hover an image, click replace, and update directly from the Media Library.
- **Button Editing:** Edit button labels, links, and styles without opening the editor.
- **Seamless View Mode:** The frontend looks exactly as intended for visitors. An "Edit Mode" toggle in the Admin Bar enables editing only when needed.
- **Permission Aware:** Only authorized roles (Administrators, Editors) can toggle Edit Mode.

## Installation

1. Clone or download this repository into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/KarunyaChavan/live-edit.git
   ```
2. Navigate to the plugin directory and install dependencies:
   ```bash
   cd live-edit
   npm install
   composer install
   ```
3. Build the assets:
   ```bash
   npm run build:js
   ```
4. Activate the plugin through the 'Plugins' menu in WordPress.

## Development

This plugin relies on the `@wordpress/scripts` package for building modern JavaScript (React) tailored for the Gutenberg block editor.

### Commands

- `npm run start:js` - Run webpack in watch mode for development.
- `npm run build:js` - Build production-ready assets.
- `npm run lint:js` - Lint JavaScript files using modern WordPress Coding Standards (WPCS).
- `composer run lint:php` - Lint PHP files using PHP_CodeSniffer.

## License

This project is licensed under the GPL-2.0-or-later License.
