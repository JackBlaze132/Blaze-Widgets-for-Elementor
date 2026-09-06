<?php
/**
 * Main Plugin Class.
 *
 * @package BlazeWidgets\Core
 */

namespace BlazeWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Category manager instance.
	 *
	 * @var Category_Manager
	 */
	public $categories;

	/**
	 * Assets manager instance.
	 *
	 * @var Assets_Manager
	 */
	public $assets;

	/**
	 * Widget manager instance.
	 *
	 * @var Widget_Manager
	 */
	public $widgets;

	/**
	 * Admin menu instance.
	 *
	 * @var Admin_Menu
	 */
	public $admin;

	/**
	 * Get single instance of class.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Initialize core components.
	 */
	private function init_components(): void {
		$this->categories = new Category_Manager();
		$this->assets     = new Assets_Manager();
		$this->widgets    = new Widget_Manager();

		if ( is_admin() ) {
			$this->admin = new Admin_Menu();
		}
	}

	/**
	 * Initialize WordPress and Elementor hooks.
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Load plugin textdomain for internationalization.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'blaze-widgets-for-elementor',
			false,
			dirname( BLAZE_WIDGETS_BASENAME ) . '/languages'
		);
	}
}
