<?php
/**
 * Uninstall Blaze Widgets for Elementor.
 *
 * Triggered when the plugin is deleted via the WordPress Admin.
 *
 * @package BlazeWidgets
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Blaze Widgets stores only its own plugin options: custom component
// definitions and settings. These are plugin data (not user content such
// as posts or Elementor page meta), so they are removed on uninstall.

delete_option( 'blaze_widgets_custom_definitions' );
delete_option( 'blaze_widgets_settings' );
