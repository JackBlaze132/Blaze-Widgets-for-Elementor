<?php
/**
 * Widget Manager Class.
 *
 * Handles registering both code-based widgets and dynamic custom uploaded/created widgets.
 *
 * @package BlazeWidgets\Core
 */

namespace BlazeWidgets\Core;

use BlazeWidgets\Widgets\Dynamic_Custom_Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget_Manager
 */
class Widget_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
	}

	/**
	 * List of code-based widget class names to register.
	 *
	 * @return array<int, class-string<\Elementor\Widget_Base>>
	 */
	public function get_widget_classes(): array {
		return [
			\BlazeWidgets\Widgets\Example_Card::class,
		];
	}

	/**
	 * Register widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager instance.
	 */
	public function register_widgets( $widgets_manager ): void {
		// 1. Register code-based widgets.
		$widget_classes = $this->get_widget_classes();

		/**
		 * Filter registered widget classes before registration.
		 *
		 * @param array $widget_classes List of widget class names.
		 */
		$widget_classes = apply_filters( 'blaze_widgets_registered_classes', $widget_classes );

		foreach ( $widget_classes as $widget_class ) {
			if ( class_exists( $widget_class ) ) {
				$widgets_manager->register( new $widget_class() );
			}
		}

		// 2. Register dynamic custom widgets created / uploaded via Admin UI.
		$custom_widgets = Custom_Widget_Storage::get_all();
		foreach ( $custom_widgets as $config ) {
			if ( ! empty( $config['active'] ) && ! empty( $config['slug'] ) ) {
				$widgets_manager->register( new Dynamic_Custom_Widget( [], null, $config ) );
			}
		}
	}
}
