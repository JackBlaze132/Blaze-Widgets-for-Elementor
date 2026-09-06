<?php
/**
 * Category Manager Class.
 *
 * @package BlazeWidgets\Core
 */

namespace BlazeWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Category_Manager
 */
class Category_Manager {

	/**
	 * Category slug.
	 */
	const CATEGORY_SLUG = 'blaze-widgets';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Elementor 3.5.0+ uses elementor/elements/categories_registered.
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
	}

	/**
	 * Register Blaze Widgets category with Elementor.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager instance.
	 */
	public function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			self::CATEGORY_SLUG,
			[
				'title' => esc_html__( 'Blaze Widgets', 'blaze-widgets-for-elementor' ),
				'icon'  => 'fa fa-plug',
			]
		);
	}
}
