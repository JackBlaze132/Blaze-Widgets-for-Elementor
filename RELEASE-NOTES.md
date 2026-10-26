# 📦 Release Notes

## 🚀 v1.1.0 — Responsive Components

This release brings opt-in responsive styling to the Dynamic Custom Widget Engine, so template authors can define tablet and mobile values for the same control without writing per-widget PHP. It also fixes slide height handling in the Slider template.

### 📱 Responsive Custom Widgets
- **Opt-in responsive controls**: Flag a simple style control in a template with `"responsive": true` to register it through Elementor's responsive control API. Existing templates and controls are unaffected.
- **Per-device defaults**: Templates can declare `tablet_default` and `mobile_default` so tablet and mobile layouts keep their intended values out of the box.
- **Supported types**: text, number, select, and color style controls that use Elementor selectors.
- **Slider template**: slide roundness, minimum/maximum height, arrow size/roundness, and indicator size/gap are now fully responsive across desktop, tablet, and mobile.

### 🛠️ Fixes & Polish
- **Slide maximum height now works**: the hard-coded minimum height no longer overrides `max-height`, so the slider can be capped to any value.
- **Indicator space collapses**: hiding the slider indicators removes the reserved strip so the slide fills the full area, and the arrows stay vertically centered.

## 🚀 v1.0.0 — The Blaze Ignition

This release marks the arrival of Blaze Widgets for Elementor as a production-ready, modular component library for Elementor. Instead of a single-widget plugin, Blaze is architected as a scalable ecosystem: a lightweight core, a centralized widget manager, isolated per-widget modules, and an admin UI to build and manage your own components.

## ⚡ Core Engine & Modular Architecture
- **Centralized Widget Manager**: All widgets register through one predictable registry, so adding a widget never touches the plugin bootstrap or unrelated modules.
- **Isolated Widget Modules**: Each widget lives in its own directory with its own assets and README — designed to scale to dozens of widgets without architectural debt.
- **Dynamic Custom Widget Engine**: Templates, controls, and scoped CSS/JS drive fully custom Elementor widgets from data — no per-widget PHP required.
- **Reference Widgets**: Shipped with a Blaze Example Card as a documented starting point for new modules.
- **Base Widget Class**: Shared, reusable behavior for every Blaze widget without widget-specific logic leaking upward.

## 🎨 UI/UX & Interaction Design
- **Blaze Brand System**: The admin was redesigned around a signature green gradient, custom logos, and a dark ink palette.
- **Component Library Dashboard**: A dedicated library view lists code-based and custom widgets with quick actions.
- **Component Builder**: Create and edit controls, HTML templates with Dynamic Tag placeholders, scoped CSS, and scripts — then export as JSON or ZIP.
- **JSON / ZIP Import & Export**: Share components between sites and back up your library.
- **Settings Panel**: Includes the experimental Atomic Edit Compatibility toggle (disabled by default for safety).
- **Community & Support Credit**: A "Crafted with ♥ by Blaze" footer card across admin pages.

## 💖 Funding the Project
- **Ko-fi Support Banner**: A dismissible banner on the WordPress Dashboard and Plugins pages (localStorage memory, no backend state).
- **Permanent Ko-fi Button**: On every Blaze page header, the button stays visible even after the banner is dismissed elsewhere.
- **README Hero & CTA**: Branded hero with ko-fi button and status badges.

## ⚙️ Performance & Integration
- **Lazy Asset Loading**: Widget CSS/JS load only when the widget is actually used — never site-wide.
- **Elementor-Native APIs**: Uses native controls, Dynamic Tags, responsive controls, and selector systems instead of reinventing them.
- **Graceful Dependency Handling**: Safe notices when PHP, WordPress, or Elementor minimum versions are missing — never a fatal error.
- **Clean Uninstall**: Removes all plugin options (custom component definitions and settings) when the plugin is deleted, leaving no orphaned data.
- **Translation-Ready**: Every user-facing string is internationalized under `blaze-widgets-for-elementor`.
- **Clear Licensing**: Free software license with a dedicated clause allowing paid distribution of Template Packs you author.

## ✅ Summary
This release establishes Blaze Widgets for Elementor as a stable, maintainable component platform, delivering:

- A scalable architecture where each new widget is an isolated, predictable module.
- A full admin suite to build, import, export, and manage custom Elementor widgets.
- Performance-first asset handling and strict Elementor/WordPress API compatibility.
- A sustainable funding path via Ko-fi and license clarity for paid template packs.

### New Contributors
@JackBlaze132 made their first contribution by architecting the modular widget core and designing the Blaze brand system.