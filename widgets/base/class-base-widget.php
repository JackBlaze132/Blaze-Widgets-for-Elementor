<?php
/**
 * Abstract Base Widget Class.
 *
 * @package BlazeWidgets\Widgets\Base
 */

namespace BlazeWidgets\Widgets\Base;

use Elementor\Widget_Base;
use BlazeWidgets\Core\Category_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Base_Widget
 */
abstract class Base_Widget extends Widget_Base {

	/**
	 * Get widget categories.
	 *
	 * Defaults to the custom Blaze Widgets category.
	 *
	 * @return array
	 */
	public function get_categories(): array {
		return [ Category_Manager::CATEGORY_SLUG ];
	}

	/**
	 * Retrieve widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-code';
	}

	/**
	 * Helper method to safely render URL attributes from Elementor link control.
	 *
	 * @param array  $link_settings Elementor link control array.
	 * @param string $element_key Key name for add_render_attribute.
	 * @return void
	 */
	protected function add_link_render_attributes( array $link_settings, string $element_key ): void {
		if ( empty( $link_settings['url'] ) ) {
			return;
		}

		$this->add_link_attributes( $element_key, $link_settings );
	}
}
