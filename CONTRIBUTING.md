# Contributing to LiveEdit

Thanks for taking the time to contribute! Bug reports, feature requests, and pull requests are all welcome.

## Table of Contents

- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)

---

## Development Setup

1. Fork and clone the repository.

```bash
git clone https://github.com/KarunyaChavan/live-edit.git
cd live-edit
```

2. Install dependencies.

```bash
composer install
npm install
```

3. Build the production assets.

```bash
npm run build
```

4. Activate the plugin from **Plugins → Installed Plugins** in WordPress.

---

## Project Structure

```text
live-edit/
├── assets/
│   ├── src/
│   │   ├── js/            # Frontend JavaScript (ES6)
│   │   └── css/           # SCSS styles (BEM naming convention)
│   └── build/             # Compiled assets (do not edit)
├── inc/
│   ├── classes/           # PHP classes
│   └── traits/            # Shared traits (e.g. Singleton)
├── languages/             # Translation files
├── .stylelintrc.json      # Stylelint configuration
├── phpcs.xml.dist         # PHP_CodeSniffer configuration
└── webpack.config.js      # Webpack configuration
```

---

## Coding Standards

This project follows the official WordPress Coding Standards for PHP, JavaScript, and SCSS. Please run all linters before submitting a pull request.

### PHP (WordPress Coding Standards)

Run PHPCS:

```bash
vendor/bin/phpcs
```

Automatically fix fixable issues:

```bash
vendor/bin/phpcbf
```

**Guidelines**

- Use PSR-4 autoloading with the `Live_Edit\Inc` namespace.
- Register hooks using `add_action()` and `add_filter()`.
- Use proper internationalization:

```php
__( 'Text', 'live-edit' );
```

- Use `@package live-edit` in every PHP file.
- Register hooks inside `setup_hooks()`.
- Keep classes focused on a single responsibility.

---

### JavaScript

Run ESLint:

```bash
npm run lint:js
```

Automatically fix issues:

```bash
npm run lint:js:fix
```

---

### SCSS

Run Stylelint:

```bash
npm run lint:css
```

Automatically fix issues:

```bash
npm run lint:css:fix
```

---

### Run All Linters

```bash
npm run lint
```

---

## CSS Naming Convention

LiveEdit follows the **BEM (Block Element Modifier)** methodology.

Examples:

- Block: `live-edit`
- Element: `live-edit__toggle`
- Element: `live-edit__switch`
- Element: `live-edit__block`
- Modifier: `live-edit--active`
- Modifier: `live-edit__save--hidden`

Do not introduce new top-level CSS classes outside the `live-edit` block.

---

## Testing

Start the local WordPress environment.

```bash
npm run wp-env start
```

### Manual Verification Checklist

1. Log in as an Administrator.
2. Visit a published post or page.
3. Verify the **Live Edit** toggle appears in the WordPress Admin Bar.
4. Enable **Edit Mode**.
5. Confirm supported blocks become editable.
6. Edit some content and click **Save**.
7. Refresh the page and verify changes persist.
8. Log out and confirm no editing UI is visible to visitors.

---

## Pull Request Process

### 1. Create a feature branch

```bash
git checkout -b feature/your-feature
```

### 2. Commit using Conventional Commits

Examples:

```text
feat(core): add inline image replacement
fix(ui): make save state short-lived
docs: improve installation guide
refactor(api): simplify save handler
```

### 3. Run all checks

```bash
vendor/bin/phpcs
npm run lint
npm run build
```

### 4. Push your branch

Open a Pull Request against the `main` branch.

### 5. Describe your changes

Please include:

- What changed
- Why it changed
- Screenshots (if applicable)
- Manual testing steps
- Related issue(s)

---

## Before Opening a Pull Request

Please verify the following:

- [ ] `vendor/bin/phpcs` passes without errors
- [ ] `npm run lint` passes without errors
- [ ] `npm run build` completes successfully
- [ ] Feature has been manually tested
- [ ] No `console.log()`, `var_dump()`, or other debug statements remain
- [ ] Documentation has been updated if necessary

---

Thank you for helping improve LiveEdit!
