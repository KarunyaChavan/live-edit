# LiveEdit for Gutenberg

> Edit WordPress where your content lives.

LiveEdit is a WordPress plugin that brings inline frontend editing to Gutenberg-powered websites. Instead of opening the Block Editor for every small content change, authorized users can edit text, images, and other supported block content directly on the page they are viewing.

The goal is simple: make quick content updates as intuitive as editing a document while keeping Gutenberg as the source of truth.

---

## Features

### Inline Text Editing
Edit headings, paragraphs, and other supported text blocks directly on the frontend.

### Frontend Image Replacement
Replace images without opening the Block Editor.

### Button Editing *(Planned)*
Update button labels, links, and styles inline.

### Edit Mode
The website looks exactly the same for visitors. Editing tools only appear when an authorized user enables **Edit Mode** from the WordPress Admin Bar.

### Role-Based Permissions
Only users with the appropriate capabilities can enter Edit Mode and publish changes.

---

## How It Works

1. Visit any published page.
2. Enable **Edit Mode** from the WordPress Admin Bar.
3. Click a supported block.
4. Edit the content directly on the page.
5. Save your changes.

LiveEdit updates the underlying Gutenberg block attributes, allowing the Block Editor and frontend to stay perfectly in sync.

---

## Supported Blocks

Current support includes:

- Paragraph
- Heading

Planned support:

- Image
- Button
- List
- Quote
- Cover
- Columns
- Group
- Custom Blocks

---

## Why LiveEdit?

Traditional WordPress editing requires users to:

- Navigate to the dashboard
- Open the Block Editor
- Locate the correct page
- Find the correct block
- Make a small edit
- Save and return to the frontend

LiveEdit removes that workflow entirely by allowing content edits where the content actually lives.

It is designed for:

- Content editors
- Marketing teams
- Agency clients
- Website administrators

while reducing the chances of accidentally modifying layouts or block settings.

---

## Installation

Clone the repository into your WordPress plugins directory.

```bash
git clone https://github.com/KarunyaChavan/live-edit.git
```

Install PHP and JavaScript dependencies.

```bash
cd live-edit

composer install
npm install
```

Build the production assets.

```bash
npm run build
```

Activate the plugin from **Plugins → Installed Plugins**.

---

## Development

Start the JavaScript development server.

```bash
npm run start:js
```

Build production assets.

```bash
npm run build:js
```

Lint JavaScript.

```bash
npm run lint:js
```

Lint PHP.

```bash
composer run lint:php
```

---

## Contributing

Contributions, bug reports, and feature requests are welcome.

If you'd like to contribute:

1. Fork the repository.
2. Create a feature branch.
3. Make your changes.
4. Submit a Pull Request.

Please follow the existing coding standards for both PHP and JavaScript.
