<div align="center">

<img src="assets/img/blaze-logo.svg" alt="Blaze Widgets for Elementor" width="288">

A modular and scalable library of custom widgets for Elementor.

<a href='https://ko-fi.com/I2I612K2L0' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://storage.ko-fi.com/cdn/kofi3.png?v=6' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

[![Download](https://img.shields.io/badge/Download-Latest%20Release-39DC48?style=for-the-badge&logo=github&logoColor=white)](https://github.com/JackBlaze132/Blaze-Widgets-for-Elementor/releases)

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![Elementor](https://img.shields.io/badge/Elementor-3.5%2B-92003B?style=for-the-badge&logo=elementor&logoColor=white)](https://elementor.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-See%20LICENSE-39DC48?style=for-the-badge)](LICENSE)

</div>

Blaze Widgets for Elementor is designed as a reusable component library, not as a single-widget plugin. Each widget lives in its own self-contained module, and adding new widgets is predictable and isolated.

## Requirements

- WordPress 5.9 or later
- PHP 7.4 or later
- Elementor 3.5.0 or later
- Elementor Pro is not required

## Installation

1. Download or clone the plugin.
2. Zip the `blaze-widgets-for-elementor/` folder.
3. In WordPress, go to **Plugins → Add New → Upload Plugin**.
4. Upload `blaze-widgets-for-elementor.zip`.
5. Activate the plugin.
6. Open Elementor; the **Blaze Widgets** category will appear in the panel.

If Elementor is not installed or active, the plugin displays an admin notice instead of producing a fatal error.

## Architecture

```
blaze-widgets-for-elementor/
│
├── blaze-widgets-for-elementor.php   Bootstrap (constants, requirements, autoloader, init)
├── uninstall.php                     Safe uninstall handler (no destructive operations)
├── README.md                         This file
├── includes/
│   ├── class-plugin.php              Main Plugin singleton orchestrating all components
│   ├── class-widget-manager.php      Centralized widget registry and registration
│   ├── class-assets-manager.php      Registers all CSS/JS assets (enqueued on-demand)
│   ├── class-category-manager.php    Registers "Blaze Widgets" category in Elementor
│   ├── class-custom-widget-storage.php  CRUD/import/export/template for custom components
│   ├── class-settings.php            Plugin + Atomic Edit settings storage
│   └── class-admin-menu.php          Admin menu, builder UI, uploader, settings page
├── widgets/
│   ├── base/
│   └── class-base-widget.php         Base Widget class with shared behavior
│   └── example-card/
│       ├── class-example-card.php    The widget class
│       ├── assets/
│       │   ├── example-card.css      Widget-specific styles
│       │   └── example-card.js       Widget-specific scripts
│       └── README.md                 Widget-specific documentation
├── assets/
│   ├── css/admin.css                  Plugin admin/editor styles (loaded only in editor)
│   └── js/admin.js                    Plugin admin/editor script
└── languages/                         Translation files (.pot, .po, .mo)
```

### Bootstrap Flow

```
WordPress loads
    ↓
plugins_loaded hook
    ↓
blaze_widgets_init()
    ↓
Check PHP / WP / Elementor requirements
    ↓
BlazeWidgets\Core\Plugin::instance()
    ↓
Category_Manager  →  registers "Blaze Widgets" category
Assets_Manager    →  registers CSS/JS handles (not enqueued globally)
Widget_Manager    →  registers code widgets + dynamic custom widgets
Admin_Menu        →  admin component library UI (list, builder, uploader, settings)
```

The main plugin file (`blaze-widgets-for-elementor.php`) defines constants, runs requirement checks, registers a PSR-style autoloader, and boots the `Plugin` singleton. All core logic lives in the `includes/` classes.

## Custom Component Library (Admin UI)

After activation, a **Blaze Widgets** menu appears in the WordPress admin:

- **Widgets & Components** — list of built-in and custom widgets, JSON import, export, and delete.
- **Add New Component** — visual builder to define controls (JSON), HTML template with placeholders, scoped CSS, and optional JavaScript.
- **Settings** — general + Atomic Edit compatibility (disabled by default).

### Creating a component from the panel

1. Go to **Blaze Widgets → Add New Component**.
2. Set the title (slug is auto-generated) and an Elementor icon class.
3. Define controls as a JSON array:

```json
[
  {
    "id": "title",
    "label": "Title",
    "type": "text",
    "tab": "content",
    "default": "My Custom Title"
  },
  {
    "id": "featured_image",
    "label": "Featured Image",
    "type": "media",
    "tab": "content"
  },
  {
    "id": "description",
    "label": "Description",
    "type": "textarea",
    "tab": "content",
    "default": "Desc"
  },
  {
    "id": "link",
    "label": "Button Link",
    "type": "url",
    "tab": "content",
    "default": "#"
  },
  {
    "id": "text_color",
    "label": "Text Color",
    "type": "color",
    "tab": "style",
    "default": "#1f2937",
    "selectors": {
      "{{WRAPPER}} .my-widget__title": "color: {{VALUE}};"
    }
  }
]
```

Supported `type` values: `text`, `textarea`, `wysiwyg`, `url`, `media`, `color`, `select`, `switcher`, `number`, `repeater`, `typography`, `border`, `box_shadow`. Every control supports Elementor Dynamic Tags automatically. Add a `"section": "Some Label"` key to group controls into named sections within a tab (controls with `"tab": "style"` form the **Style** tab). Group controls use a `"selector"` instead of `"selectors"`:

```json
{ "id": "title_typography", "label": "Title Typography", "type": "typography", "tab": "style", "section": "Typography", "selector": "{{WRAPPER}} .my-widget__title" }
{ "id": "box", "label": "Card Border", "type": "border", "tab": "style", "section": "Container", "selector": "{{WRAPPER}} .my-widget" }
```

4. Write the HTML template using placeholders:

```
<div class="my-widget">
  <img src="{{image}}" alt="{{title}}" class="my-widget__image">
  <h3 class="my-widget__title">{{title}}</h3>
  <div class="my-widget__desc">{{description.html}}</div>
  <a href="{{link}}" class="my-widget__button">Learn More</a>
</div>
```

Placeholder conventions:
- `{{title}}` — plain text, escaped with `esc_html()`
- `{{description.html}}` — rich text (WYSIWYG/textarea), sanitized with `wp_kses_post()`
- `{{image}}` / `{{link}}` — media/URL controls resolve to their URL, escaped with `esc_url()`
- `{{link.url}}` — same as above, explicit URL accessor
- `{{link.attributes}}` — renders a ready-to-use `target`/`rel` snippet from the URL control's "Open in new window" (`is_external`) and "Add nofollow" flags; external links automatically get `noopener noreferrer`. Example: `<a href="{{link.url}}" {{link.attributes}}>Learn more</a>`. Returns an empty string when no flags are set.

### Repeatable groups (repeater)

To let the user add **any number of identical blocks** (panels, team members, features), declare a `repeater` control with its child fields:

```json
{
  "id": "items",
  "label": "Panels",
  "type": "repeater",
  "tab": "content",
  "section": "Content",
  "title_field": "{{{ badge }}} - {{{ title }}}",
  "fields": [
    { "id": "image", "label": "Image", "type": "media" },
    { "id": "badge", "label": "Badge Number", "type": "text", "default": "1" },
    { "id": "title", "label": "Title", "type": "text", "default": "Heading" },
    { "id": "text", "label": "Text", "type": "textarea", "default": "Body copy" }
  ]
}
```

Then loop the block in the HTML template — the inner placeholders resolve against each row:

```
{{#items}}
<div class="bw-diff__item" style="background-image:url('{{image}}')">
  <span class="bw-diff__badge">{{badge}}</span>
  <h3>{{title}}</h3>
  <p>{{text}}</p>
</div>
{{/items}}
```

There is no hard limit on row count: with 1, 5, or 50 rows the engine renders exactly that many blocks. A ready-made example lives in `templates/blaze-difference-accordion.json` (import it via the upload box).

### Server-rendered "expanded" row (editor-friendly)

Inline widget JavaScript does not execute inside the Elementor editor preview, so an accordion defaulting to a collapsed state would look broken there. To render the initial expanded row on the server, declare it on the repeater control:

```json
{
  "id": "items",
  "type": "repeater",
  "active_class": "bw-diff__item--active",
  "active_setting": "initial_active",
  "fields": [ ... ]
}
```

`active_setting` names another control (usually a `number`) holding the zero-based index to mark. In the template, place the class token on the row:

```
{{#items}}
<div class="bw-diff__item {{items_active_class}}">...</div>
{{/items}}
```

The engine injects `active_class` (or nothing) per row before interpolation. This works in the editor, the frontend, and with JS disabled.

5. Add scoped CSS under a namespaced class (e.g. `.my-widget`).
6. Add optional JavaScript (runs once in the widget wrapper).
7. Save. The component is now registered in Elementor under **Blaze Widgets**.

### Uploading a component

Use **Download Blank Template** to get a reference `.json` file, edit it, then upload it on the **Widgets & Components** page. Exported widgets can be re-imported on other sites.

### Atomic Edit compatibility

The **Settings → Atomic Edit** tab lets you enable Atomic Edit data-attribute injection on custom components. It is **disabled by default** and only affects custom components, never built-in widgets.

## Namespaces

- `BlazeWidgets` - Plugin root
- `BlazeWidgets\Core` - Core infrastructure (`Plugin`, `Widget_Manager`, `Assets_Manager`, `Category_Manager`)
- `BlazeWidgets\Widgets` - Concrete widgets
- `BlazeWidgets\Widgets\Base` - Base widget class

## Prefixes

| Type          | Prefix             | Example                       |
|---------------|--------------------|-------------------------------|
| PHP functions | `blaze_widgets_`   | `blaze_widgets_init`          |
| CSS handles   | `blaze-widgets-`   | `blaze-widgets-example-card`  |
| CSS classes   | `blaze-`           | `.blaze-example-card`         |
| Text domain   | -                  | `blaze-widgets-for-elementor` |

## Widget Registration System

All widgets are registered through the centralized `Widget_Manager` class (`includes/class-widget-manager.php`). The registry is a simple, readable array:

```php
public function get_widget_classes(): array {
    return [
        \BlazeWidgets\Widgets\Example_Card::class,
    ];
}
```

### How to Add a New Widget

1. Create a directory: `widgets/my-widget/`.
2. Create the widget class: `widgets/my-widget/class-my-widget.php`.
3. Use `BlazeWidgets\Widgets` as the namespace.
4. Extend `BlazeWidgets\Widgets\Base\Base_Widget`.
5. Implement `get_name()`, `get_title()`, `register_controls()`, and `render()`.
6. Add widget assets (CSS/JS) in `widgets/my-widget/assets/` if needed.
7. Register the CSS/JS handles in `Assets_Manager::register_frontend_assets()`.
8. Add the widget class to the array in `Widget_Manager::get_widget_classes()`.
9. Set `get_style_depends()` and/or `get_script_depends()` on the widget class if assets are needed.
10. Verify rendering in frontend, editor, and preview.

### Example Skeleton

```php
<?php
namespace BlazeWidgets\Widgets;

use BlazeWidgets\Widgets\Base\Base_Widget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_Widget extends Base_Widget {

    public function get_name(): string {
        return 'blaze-my-widget';
    }

    public function get_title(): string {
        return esc_html__( 'Blaze My Widget', 'blaze-widgets-for-elementor' );
    }

    public function get_icon(): string {
        return 'eicon-star';
    }

    public function get_style_depends(): array {
        return [ 'blaze-widgets-my-widget' ];
    }

    protected function register_controls(): void {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__( 'Content', 'blaze-widgets-for-elementor' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );
        // Add controls here...
        $this->end_controls_section();
    }

    protected function render(): void {
        ?>
        <div class="blaze-my-widget">
            <?php // Render output here... ?>
        </div>
        <?php
    }
}
```

## Blaze Example Card

The `Blaze Example Card` widget (`widgets/example-card/`) is the reference implementation. It demonstrates the full architecture:

### Content Controls

- **Image** (with `Group_Control_Image_Size` and Dynamic Tag support)
- **Title** (with selectable HTML tag, Dynamic Tag support)
- **Description** (textarea, Dynamic Tag support)
- **Button Text** (Dynamic Tag support)
- **Button URL** (Elementor URL control: `is_external`, `nofollow`, Dynamic Tag support)

### Style Controls

- **Container**: Background color, alignment, padding, margin, border, border radius, box shadow
- **Image**: Width, height, object fit, border radius, spacing (all responsive)
- **Title**: Color, typography, spacing
- **Description**: Color, typography, spacing
- **Button**: Typography, text color, background color, border, border radius, padding, hover states (text color, background, border color), transition duration

### Semantic HTML Output

```html
<article class="blaze-example-card">
    <div class="blaze-example-card__media">
        <img ... />
    </div>
    <div class="blaze-example-card__content">
        <h3 class="blaze-example-card__title">...</h3>
        <div class="blaze-example-card__description">...</div>
        <a class="blaze-example-card__button">...</a>
    </div>
</article>
```

### CSS

All styles are namespaced under `.blaze-example-card` using BEM conventions. No global selectors are used.

### Assets

- `example-card.css` and `example-card.js` are registered (not enqueued) in `Assets_Manager`.
- The widget declares them via `get_style_depends()` and `get_script_depends()`.
- Elementor only enqueues them when the widget is actually used on a page.

## Dynamic Tags

All applicable controls enable Dynamic Tags (`'dynamic' => [ 'active' => true ]`). This means fields like Title, Description, Image, and Button URL can be populated from ACF, custom fields, featured images, post titles, and other Elementor dynamic sources. ACF is not a hard dependency.

## Security

All output uses appropriate escaping:
- `esc_html()` for text
- `wp_kses_post()` for rich content descriptions
- `esc_attr()` for attributes
- `esc_url()` via Elementor's URL control
- `add_link_attributes()` for links

No user-controlled value is output without escaping.

## Internationalization

All user-facing strings use the `blaze-widgets-for-elementor` text domain with `esc_html__()`, `esc_html_e()`, and `wp_kses_post()` where appropriate.

## Uninstall

Plugin options created by Blaze Widgets (`blaze_widgets_custom_definitions` for custom components and `blaze_widgets_settings`) are removed on uninstall. Plugin content such as Elementor page data and uploaded media created with the widgets is never deleted.

## Quality Checklist

- [x] PHP 7.4 compatible
- [x] WordPress 5.9+ compatible
- [x] Elementor 3.5.0+ compatible
- [x] No hardcoded Elementor dependencies
- [x] All output escaped
- [x] Semantic HTML
- [x] BEM CSS namespacing
- [x] Responsive controls
- [x] Dynamic Tags support
- [x] Conditional asset loading
- [x] PSR-style autoloader
- [x] No jQuery dependency
- [x] Translation-ready

## Credit

Created with ♥ by [Blaze](https://github.com/JackBlaze132). If this plugin saves you time, [buy me a coffee](https://ko-fi.com/I2I612K2L0).

## License

See [LICENSE](LICENSE) for the full text. In short:

- The software itself must stay **free** — no selling this plugin or forks of it as a software product.
- **Template Packs** built with the plugin (collections of components you author in the admin UI) are user content and **may be sold** as content packages, because they are not source code of the Software.
- Free to use on WordPress with Elementor, free to fork, free to modify for your own use.
- Derivative Works distributed publicly must also be free and must credit the original source.
