<?php
/**
 * Dynamic Custom Widget Definition Storage.
 *
 * Handles saving, loading, importing, exporting, and deleting custom dynamic widgets.
 *
 * @package BlazeWidgets\Core
 */

namespace BlazeWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Custom_Widget_Storage
 */
class Custom_Widget_Storage {

	/**
	 * Option key for custom widgets.
	 */
	const OPTION_KEY = 'blaze_widgets_custom_definitions';

	/**
	 * Upload sub-directory for custom widget assets.
	 */
	const ASSETS_DIR = 'blaze-widgets';

	/**
	 * Slugs reserved by built-in or system widgets. Custom components must
	 * not use them, otherwise registering a dynamic widget would collide
	 * with an already-registered Elementor widget name.
	 *
	 * @var array<string>
	 */
	const RESERVED_SLUGS = [ 'example-card', 'custom-widget' ];

	/**
	 * Get all stored custom widget configurations.
	 *
	 * @return array<string, array> Keyed by widget slug.
	 */
	public static function get_all(): array {
		$widgets = get_option( self::OPTION_KEY, [] );
		return is_array( $widgets ) ? $widgets : [];
	}

	/**
	 * Get a single custom widget definition by slug.
	 *
	 * @param string $slug Widget slug.
	 * @return array|null
	 */
	public static function get( string $slug ): ?array {
		$all = self::get_all();
		return $all[ $slug ] ?? null;
	}

	/**
	 * Save (create or update) a custom widget definition.
	 *
	 * @param array $widget_data Widget configuration data.
	 * @return bool|string True on success or error message string.
	 */
	public static function save( array $widget_data ) {
		$slug = sanitize_key( $widget_data['slug'] ?? '' );
		if ( empty( $slug ) ) {
			return __( 'Widget slug is required.', 'blaze-widgets-for-elementor' );
		}

		if ( in_array( $slug, self::RESERVED_SLUGS, true ) ) {
			return sprintf(
				/* translators: %s: Reserved widget slug. */
				__( 'The slug "%s" is reserved by a built-in Blaze widget. Choose a different one.', 'blaze-widgets-for-elementor' ),
				$slug
			);
		}

		$title = sanitize_text_field( $widget_data['title'] ?? '' );
		if ( empty( $title ) ) {
			return __( 'Widget title is required.', 'blaze-widgets-for-elementor' );
		}

		$all = self::get_all();

		$clean_data = [
			'slug'        => $slug,
			'title'       => $title,
			'icon'        => sanitize_text_field( $widget_data['icon'] ?? 'eicon-code' ),
			'category'    => sanitize_text_field( $widget_data['category'] ?? 'blaze-widgets' ),
			'description' => sanitize_textarea_field( $widget_data['description'] ?? '' ),
			'controls'    => isset( $widget_data['controls'] ) && is_array( $widget_data['controls'] ) ? $widget_data['controls'] : [],
			'html_tpl'    => $widget_data['html_tpl'] ?? '',
			'css'         => $widget_data['css'] ?? '',
			'js'          => $widget_data['js'] ?? '',
			'active'      => ! empty( $widget_data['active'] ),
			'updated_at'  => current_time( 'mysql' ),
		];

		$all[ $slug ] = $clean_data;
		update_option( self::OPTION_KEY, $all );

		return true;
	}

	/**
	 * Delete a custom widget definition.
	 *
	 * @param string $slug Widget slug.
	 * @return bool
	 */
	public static function delete( string $slug ): bool {
		$all = self::get_all();
		if ( isset( $all[ $slug ] ) ) {
			unset( $all[ $slug ] );
			return update_option( self::OPTION_KEY, $all );
		}
		return false;
	}

	/**
	 * Export a widget definition to JSON.
	 *
	 * @param string $slug Widget slug.
	 * @return string|false JSON string or false on failure.
	 */
	public static function export_json( string $slug ) {
		$widget = self::get( $slug );
		if ( ! $widget ) {
			return false;
		}

		$export_package = [
			'schema_version' => '1.0.0',
			'generator'      => 'Blaze Widgets for Elementor',
			'widget'         => $widget,
		];

		return wp_json_encode( $export_package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Get a blank widget definition template for import / manual creation.
	 *
	 * @return array
	 */
	public static function get_blank_template(): array {
		return [
			'schema_version' => '1.0.0',
			'generator'      => 'Blaze Widgets for Elementor',
			'widget'         => [
				'slug'        => 'my-custom-component',
				'title'       => 'My Custom Component',
				'icon'        => 'eicon-code',
				'category'    => 'blaze-widgets',
				'description' => 'Description of the component.',
				'active'      => true,
				'controls'    => [
					[
						'id'      => 'title',
						'label'   => 'Title',
						'type'    => 'text',
						'tab'     => 'content',
						'default' => 'My Custom Title',
					],
					[
						'id'      => 'description',
						'label'   => 'Description',
						'type'    => 'textarea',
						'tab'     => 'content',
						'default' => 'Custom description supports Elementor Dynamic Tags.',
					],
					[
						'id'      => 'link',
						'label'   => 'Button Link',
						'type'    => 'url',
						'tab'     => 'content',
						'default' => '#',
					],
					[
						'id'      => 'text_color',
						'label'   => 'Text Color',
						'type'    => 'color',
						'tab'     => 'style',
						'default' => '#1f2937',
						'selectors' => [
							'{{WRAPPER}} .my-custom-component__title' => 'color: {{VALUE}};',
						],
					],
				],
				'html_tpl'    => '<div class="my-custom-component">
	<h3 class="my-custom-component__title">{{title}}</h3>
	<p class="my-custom-component__description">{{description}}</p>
	<a href="{{link.url}}" class="my-custom-component__button">{{link_text}}</a>
</div>',
				'css'         => '.my-custom-component {
	padding: 24px;
	background: #ffffff;
	border-radius: 12px;
}
.my-custom-component__title {
	margin: 0 0 12px;
	color: #1f2937;
}
.my-custom-component__description {
	color: #4b5563;
}
.my-custom-component__button {
	display: inline-block;
	margin-top: 16px;
	padding: 10px 20px;
	background: #2563eb;
	color: #ffffff;
	text-decoration: none;
	border-radius: 6px;
}',
				'js'          => '',
			],
		];
	}

	/**
	 * Import a widget from JSON payload or array.
	 *
	 * @param string|array $input JSON string or parsed array.
	 * @return bool|string True on success or error string.
	 */
	public static function import( $input ) {
		if ( is_string( $input ) ) {
			$data = json_decode( $input, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return __( 'Invalid JSON payload.', 'blaze-widgets-for-elementor' );
			}
		} else {
			$data = $input;
		}

		$widget_data = $data['widget'] ?? $data;

		if ( empty( $widget_data['slug'] ) || empty( $widget_data['title'] ) ) {
			return __( 'The imported file must contain at least a slug and a title.', 'blaze-widgets-for-elementor' );
		}

		return self::save( $widget_data );
	}
}
