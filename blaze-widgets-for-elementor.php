<?php
/**
 * Plugin Name: Blaze Widgets for Elementor
 * Plugin URI: https://github.com/blaze/blaze-widgets-for-elementor
 * Description: A modular and scalable library of custom widgets for Elementor.
 * Version: 1.0.0
 * Author: Blaze
 * Author URI: https://blaze.example.com
 * Text Domain: blaze-widgets-for-elementor
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 *
 * @package BlazeWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLAZE_WIDGETS_VERSION', '1.0.0' );
define( 'BLAZE_WIDGETS_MINIMUM_PHP_VERSION', '7.4' );
define( 'BLAZE_WIDGETS_MINIMUM_ELEMENTOR_VERSION', '3.5.0' );
define( 'BLAZE_WIDGETS_MINIMUM_WP_VERSION', '5.9' );
define( 'BLAZE_WIDGETS_FILE', __FILE__ );
define( 'BLAZE_WIDGETS_PATH', plugin_dir_path( __FILE__ ) );
define( 'BLAZE_WIDGETS_URL', plugin_dir_url( __FILE__ ) );
define( 'BLAZE_WIDGETS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for BlazeWidgets namespace.
 */
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'BlazeWidgets\\';
		$base_dir = BLAZE_WIDGETS_PATH;

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$parts          = explode( '\\', $relative_class );
		$class_name     = array_pop( $parts );

		// Convert ClassName / Class_Name to class-class-name.php format.
		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		// Map namespace hierarchy to directory structure.
		if ( empty( $parts ) ) {
			$path = $base_dir . 'includes/' . $file_name;
		} elseif ( 'Core' === $parts[0] ) {
			array_shift( $parts );
			$sub_path = empty( $parts ) ? '' : strtolower( implode( '/', $parts ) ) . '/';
			$path     = $base_dir . 'includes/' . $sub_path . $file_name;
		} elseif ( 'Widgets' === $parts[0] ) {
			array_shift( $parts );
			if ( ! empty( $parts ) && 'Base' === $parts[0] ) {
				$path = $base_dir . 'widgets/base/' . $file_name;
			} else {
				$widget_dir = strtolower( str_replace( '_', '-', $class_name ) );
				$path       = $base_dir . 'widgets/' . $widget_dir . '/' . $file_name;
			}

			// System widgets such as Dynamic_Custom_Widget live in widgets/base/.
			if ( ! file_exists( $path ) ) {
				$base_path = $base_dir . 'widgets/base/' . $file_name;
				if ( file_exists( $base_path ) ) {
					$path = $base_path;
				}
			}
		} else {
			$sub_path = strtolower( implode( '/', $parts ) ) . '/';
			$path     = $base_dir . 'includes/' . $sub_path . $file_name;
		}

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

/**
 * Check requirements and initialize the plugin.
 */
function blaze_widgets_init() {
	// Check PHP version.
	if ( version_compare( PHP_VERSION, BLAZE_WIDGETS_MINIMUM_PHP_VERSION, '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				$message = sprintf(
					/* translators: 1: Plugin name, 2: PHP version */
					esc_html__( '"%1$s" requires PHP version %2$s or greater.', 'blaze-widgets-for-elementor' ),
					'<strong>' . esc_html__( 'Blaze Widgets for Elementor', 'blaze-widgets-for-elementor' ) . '</strong>',
					BLAZE_WIDGETS_MINIMUM_PHP_VERSION
				);
				printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
			}
		);
		return;
	}

	// Check WordPress version.
	global $wp_version;
	if ( version_compare( $wp_version, BLAZE_WIDGETS_MINIMUM_WP_VERSION, '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				$message = sprintf(
					/* translators: 1: Plugin name, 2: WordPress version */
					esc_html__( '"%1$s" requires WordPress version %2$s or greater.', 'blaze-widgets-for-elementor' ),
					'<strong>' . esc_html__( 'Blaze Widgets for Elementor', 'blaze-widgets-for-elementor' ) . '</strong>',
					BLAZE_WIDGETS_MINIMUM_WP_VERSION
				);
				printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
			}
		);
		return;
	}

	// Check if Elementor is installed and active.
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action(
			'admin_notices',
			function () {
				$message = sprintf(
					/* translators: 1: Plugin name, 2: Elementor */
					esc_html__( '"%1$s" requires %2$s to be installed and activated.', 'blaze-widgets-for-elementor' ),
					'<strong>' . esc_html__( 'Blaze Widgets for Elementor', 'blaze-widgets-for-elementor' ) . '</strong>',
					'<strong>' . esc_html__( 'Elementor', 'blaze-widgets-for-elementor' ) . '</strong>'
				);
				printf( '<div class="notice notice-warning"><p>%s</p></div>', wp_kses_post( $message ) );
			}
		);
		return;
	}

	// Check Elementor version.
	if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, BLAZE_WIDGETS_MINIMUM_ELEMENTOR_VERSION, '<' ) ) {
		add_action(
			'admin_notices',
			function () {
				$message = sprintf(
					/* translators: 1: Plugin name, 2: Elementor version */
					esc_html__( '"%1$s" requires Elementor version %2$s or greater.', 'blaze-widgets-for-elementor' ),
					'<strong>' . esc_html__( 'Blaze Widgets for Elementor', 'blaze-widgets-for-elementor' ) . '</strong>',
					BLAZE_WIDGETS_MINIMUM_ELEMENTOR_VERSION
				);
				printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
			}
		);
		return;
	}

	// Instantiate plugin singleton.
	\BlazeWidgets\Core\Plugin::instance();
}

add_action( 'plugins_loaded', 'blaze_widgets_init' );
