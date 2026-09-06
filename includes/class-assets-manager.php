<?php
/**
 * Assets Manager Class.
 *
 * @package BlazeWidgets\Core
 */

namespace BlazeWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets_Manager
 */
class Assets_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ] );
		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_frontend_assets' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_menu_icon_css' ] );
	}

	/**
	 * Enqueue the branded sidebar menu icon tile on every admin screen.
	 *
	 * The top-level Blaze menu must carry its gradient tile from the very
	 * first load (other admin pages do not load the page-level admin.css).
	 */
	public function enqueue_admin_menu_icon_css(): void {
		if ( file_exists( BLAZE_WIDGETS_PATH . 'assets/css/admin-menu.css' ) ) {
			wp_enqueue_style(
				'blaze-widgets-admin-menu',
				BLAZE_WIDGETS_URL . 'assets/css/admin-menu.css',
				[],
				BLAZE_WIDGETS_VERSION
			);
		}
	}

	/**
	 * Enqueue admin UI styles/scripts only on Blaze Widgets admin pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ): void {
		if ( false === strpos( $hook, 'blaze-widgets' ) ) {
			return;
		}

		if ( file_exists( BLAZE_WIDGETS_PATH . 'assets/css/admin.css' ) ) {
			wp_enqueue_style(
				'blaze-widgets-admin',
				BLAZE_WIDGETS_URL . 'assets/css/admin.css',
				[],
				BLAZE_WIDGETS_VERSION
			);
		}

		if ( file_exists( BLAZE_WIDGETS_PATH . 'assets/js/admin.js' ) ) {
			wp_enqueue_script(
				'blaze-widgets-admin',
				BLAZE_WIDGETS_URL . 'assets/js/admin.js',
				[],
				BLAZE_WIDGETS_VERSION,
				true
			);
		}
	}

	/**
	 * Register all widget CSS and JS scripts without enqueuing globally.
	 * They will be loaded on-demand by Elementor via get_style_depends() and get_script_depends().
	 */
	public function register_frontend_assets(): void {
		// This runs on both wp_enqueue_scripts and elementor/frontend/after_register_styles.
		// Register once per request to avoid duplicate option reads and registrations.
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;

		// Example Card Widget Assets.
		wp_register_style(
			'blaze-widgets-example-card',
			BLAZE_WIDGETS_URL . 'widgets/example-card/assets/example-card.css',
			[],
			BLAZE_WIDGETS_VERSION
		);

		wp_register_script(
			'blaze-widgets-example-card',
			BLAZE_WIDGETS_URL . 'widgets/example-card/assets/example-card.js',
			[ 'elementor-frontend' ],
			BLAZE_WIDGETS_VERSION,
			true
		);

		// Dynamic custom widgets with JavaScript: register a registered script handle
		// with an inline handler hooked into Elementor's frontend element_ready.
		// That way the JS executes in BOTH the frontend and the Elementor editor preview.
		$custom_widgets = Custom_Widget_Storage::get_all();
		foreach ( $custom_widgets as $slug => $config ) {
			if ( empty( $config['active'] ) || empty( $config['js'] ) ) {
				continue;
			}

			$handle    = 'blaze-custom-widget-' . sanitize_key( $slug );
			$elem_name = 'blaze-' . sanitize_key( $slug );
			$clean_js  = $config['js'];

			// Register a virtual script handle with elementor-frontend as dependency.
			wp_register_script( $handle, false, [ 'elementor-frontend' ], BLAZE_WIDGETS_VERSION, true );

			// Wrap the author-provided JS inside Elementor's element_ready lifecycle.
			// If the user's code expects a function, we invoke it with the $scope.
			// If the user's code was an IIFE, we still scope it.
			$wrapper = "(function () {
	'use strict';
	function initCustomWidget(\$scope) {
		var root = \$scope && \$scope[0] ? \$scope[0].querySelector('.blaze-" . esc_js( $slug ) . "') : null;
		if (!root) { return; }
		try {
			(function (host, \$scope) {
				" . $clean_js . "
			})(root, \$scope);
		} catch (err) {
			if (window.console && console.warn) { console.warn('[Blaze Widgets] Error running " . esc_js( $slug ) . " handler:', err); }
		}
	}
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/" . esc_js( $elem_name ) . ".default',
				initCustomWidget
			);
		}
	});
})();";
			wp_add_inline_script( $handle, $wrapper );
		}
	}

	/**
	 * Enqueue editor-specific assets.
	 */
	public function enqueue_editor_assets(): void {
		if ( file_exists( BLAZE_WIDGETS_PATH . 'assets/css/admin.css' ) ) {
			wp_enqueue_style(
				'blaze-widgets-admin',
				BLAZE_WIDGETS_URL . 'assets/css/admin.css',
				[],
				BLAZE_WIDGETS_VERSION
			);
		}

		if ( file_exists( BLAZE_WIDGETS_PATH . 'assets/js/admin.js' ) ) {
			wp_enqueue_script(
				'blaze-widgets-admin',
				BLAZE_WIDGETS_URL . 'assets/js/admin.js',
				[],
				BLAZE_WIDGETS_VERSION,
				true
			);
		}
	}
}
