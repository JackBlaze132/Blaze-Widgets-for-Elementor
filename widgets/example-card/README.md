# Blaze Example Card Widget

The `Blaze Example Card` is the reference widget implementation for **Blaze Widgets for Elementor**.

## Features

- **Dynamic Tag Support**: Title, Image, Description, and Button URL support Elementor Dynamic Tags (ACF, Pods, Custom Fields, Post Meta).
- **Responsive Controls**: Spacing, alignment, typography, dimensions, and border radiuses adjust per device breakpoint (Desktop, Tablet, Mobile).
- **Semantic HTML**: Renders `<article>`, `<h*>` tags, `<p>`, and `<a>` elements with BEM-namespaced classes.
- **On-Demand Assets**: `example-card.css` and `example-card.js` are registered with WordPress and loaded on-demand by Elementor only when this widget is placed on a page.
- **Zero Global Style Conflicts**: All styles scoped under `.blaze-example-card`.

## Files

- `class-example-card.php` - Elementor widget class extending `Base_Widget`.
- `assets/example-card.css` - Widget styles.
- `assets/example-card.js` - Widget script and Elementor editor/frontend hook handler.
