<?php
/**
 * Dynamic Custom Elementor Widget Class.
 *
 * Instantiated dynamically for each user-created/uploaded custom widget.
 *
 * @package BlazeWidgets\Widgets
 */

namespace BlazeWidgets\Widgets;

use BlazeWidgets\Widgets\Base\Base_Widget;
use BlazeWidgets\Core\Custom_Widget_Storage;
use BlazeWidgets\Core\Settings;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Repeater;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dynamic_Custom_Widget
 */
class Dynamic_Custom_Widget extends Base_Widget {

	/**
	 * Widget configuration.
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Constructor.
	 *
	 * Elementor re-creates widget instances from stored page data with only the
	 * first two arguments, so a missing config is restored from storage using
	 * the element's widgetType. Otherwise the widget would render empty.
	 *
	 * @param array      $data   Widget data.
	 * @param array|null $args   Widget arguments.
	 * @param array      $config Stored dynamic widget configuration (optional).
	 */
	public function __construct( $data = [], $args = null, array $config = [] ) {
		if ( empty( $config ) ) {
			$config = $this->resolve_config( $data );
		}

		$this->config = $config;
		parent::__construct( $data, $args );
	}

	/**
	 * Restore the widget configuration from the stored defnitions.
	 *
	 * The widgetType carries the blaze-<slug> name Elementor persisted, which
	 * maps directly back to the Custom Widget Storage key.
	 *
	 * @param array|null $data Element data as passed by Elementor.
	 * @return array
	 */
	protected function resolve_config( $data ): array {
		$widget_type = is_array( $data ) && ! empty( $data['widgetType'] ) ? (string) $data['widgetType'] : '';

		if ( 0 === strpos( $widget_type, 'blaze-' ) ) {
			$slug = substr( $widget_type, 6 );
			// The generic fallback name has no stored definition.
			if ( 'custom-widget' !== $slug ) {
				$stored = Custom_Widget_Storage::get( $slug );
				if ( is_array( $stored ) && ! empty( $stored ) ) {
					return $stored;
				}
			}
		}

		// Safe fallback so an empty shell never fatals.
		return [
			'slug'        => 'custom-widget',
			'title'       => esc_html__( 'Blaze Custom Widget', 'blaze-widgets-for-elementor' ),
			'icon'        => 'eicon-code',
			'category'    => 'blaze-widgets',
			'controls'    => [],
			'html_tpl'    => '',
			'css'         => '',
			'js'          => '',
			'active'      => true,
		];
	}

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'blaze-' . ( $this->config['slug'] ?? 'custom-widget' );
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->config['title'] ?? esc_html__( 'Blaze Custom Widget', 'blaze-widgets-for-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return ! empty( $this->config['icon'] ) ? $this->config['icon'] : 'eicon-code';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories(): array {
		$cat = ! empty( $this->config['category'] ) ? $this->config['category'] : 'blaze-widgets';
		return [ $cat ];
	}

	/**
	 * Get script dependencies.
	 *
	 * When a dynamic custom widget defines JS, Assets_Manager registers a
	 * handle blaze-custom-widget-<slug> hooked into Elementor's element_ready.
	 * Declaring it here instructs Elementor to enqueue it on-demand in both
	 * the frontend and the editor preview.
	 *
	 * @return array
	 */
	public function get_script_depends(): array {
		$slug = sanitize_key( $this->config['slug'] ?? '' );
		if ( ! empty( $slug ) && ! empty( $this->config['js'] ) ) {
			return [ 'blaze-custom-widget-' . $slug ];
		}
		return [];
	}

	/**
	 * Register controls based on JSON / builder configuration.
	 */
	protected function register_controls(): void {
		$controls = $this->config['controls'] ?? [];

		// Group controls by tab -> named section -> items.
		$tabs = [
			'content' => Controls_Manager::TAB_CONTENT,
			'style'   => Controls_Manager::TAB_STYLE,
		];

		$grouped = [];
		foreach ( $controls as $ctrl ) {
			if ( ! is_array( $ctrl ) ) {
				continue;
			}

			$tab      = isset( $ctrl['tab'] ) && 'style' === $ctrl['tab'] ? 'style' : 'content';
			$section  = sanitize_text_field( $ctrl['section'] ?? ( 'style' === $tab ? esc_html__( 'Style', 'blaze-widgets-for-elementor' ) : esc_html__( 'Content', 'blaze-widgets-for-elementor' ) ) );
			$grouped[ $tab ][ $section ][] = $ctrl;
		}

		$used_section_ids = [];

		foreach ( $tabs as $tab_key => $tab_constant ) {
			if ( empty( $grouped[ $tab_key ] ) ) {
				continue;
			}

			foreach ( $grouped[ $tab_key ] as $section_label => $section_controls ) {
				$section_id = 'bw_custom_' . $tab_key . '_' . sanitize_key( $section_label );
				$section_id = substr( $section_id, 0, 50 );

				// Ensure unique section ids across all sections.
				$base_id = $section_id;
				$i       = 2;
				while ( isset( $used_section_ids[ $section_id ] ) ) {
					$section_id = $base_id . '_' . $i;
					$i++;
				}
				$used_section_ids[ $section_id ] = true;

				$this->start_controls_section(
					$section_id,
					[
						'label' => $section_label,
						'tab'   => $tab_constant,
					]
				);

				foreach ( $section_controls as $ctrl ) {
					$this->add_dynamic_control( $ctrl );
				}

				$this->end_controls_section();
			}
		}
	}

	/**
	 * Add a single control from configuration definition.
	 *
	 * Supports scalar control types plus the "repeater" type for repeatable groups.
	 *
	 * @param array $ctrl Control definition.
	 */
	protected function add_dynamic_control( array $ctrl ): void {
		$id = sanitize_key( $ctrl['id'] ?? '' );
		if ( empty( $id ) ) {
			return;
		}

		$type = $ctrl['type'] ?? 'text';

		if ( 'repeater' === $type ) {
			$this->add_dynamic_repeater( $id, $ctrl );
			return;
		}

		// Group controls configure several properties at once via add_group_control().
		if ( in_array( $type, [ 'typography', 'border', 'box_shadow' ], true ) ) {
			$this->add_dynamic_group_control( $id, $ctrl, $type );
			return;
		}

		$args = $this->resolve_control_args( $ctrl );
		$this->add_control( $id, $args );
	}

	/**
	 * Register an Elementor group control (typography / border / box shadow).
	 *
	 * @param string $id   Control id (used as the group's registration name).
	 * @param array  $ctrl Control definition.
	 * @param string $type Group type: typography | border | box_shadow.
	 */
	protected function add_dynamic_group_control( string $id, array $ctrl, string $type ): void {
		$label    = sanitize_text_field( $ctrl['label'] ?? ucfirst( str_replace( '_', ' ', $id ) ) );
		$selector = sanitize_text_field( $ctrl['selector'] ?? '{{WRAPPER}}' );

		switch ( $type ) {
			case 'typography':
				$group = Group_Control_Typography::get_type();
				break;
			case 'border':
				$group = Group_Control_Border::get_type();
				break;
			case 'box_shadow':
				$group = Group_Control_Box_Shadow::get_type();
				break;
			default:
				return;
		}

		$this->add_group_control(
			$group,
			[
				'name'     => $id,
				'label'    => $label,
				'selector' => $selector,
			]
		);
	}

	/**
	 * Register a repeatable group control from its configuration.
	 *
	 * @param string $id   Repeater control id.
	 * @param array  $ctrl Repeater definition with a "fields" list.
	 */
	protected function add_dynamic_repeater( string $id, array $ctrl ): void {
		$repeater = new Repeater();

		$fields = $ctrl['fields'] ?? [];
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$field_id = sanitize_key( $field['id'] ?? '' );
			if ( empty( $field_id ) ) {
				continue;
			}

			// Repeater fields cannot carry Elementor selectors.
			$args = $this->resolve_control_args( $field, true );
			$repeater->add_control( $field_id, $args );
		}

		$this->add_control(
			$id,
			[
				'label'        => sanitize_text_field( $ctrl['label'] ?? ucfirst( str_replace( '_', ' ', $id ) ) ),
				'type'         => Controls_Manager::REPEATER,
				'fields'       => $repeater->get_controls(),
				'title_field'  => ! empty( $ctrl['title_field'] ) ? sanitize_text_field( $ctrl['title_field'] ) : '#' . $id,
				'default'      => $ctrl['default'] ?? [],
			]
		);
	}

	/**
	 * Convert a control definition array into Elementor control arguments.
	 *
	 * @param array $ctrl        Control definition.
	 * @param bool  $in_repeater Whether this control is a child of a repeater.
	 * @return array
	 */
	protected function resolve_control_args( array $ctrl, bool $in_repeater = false ): array {
		$id    = sanitize_key( $ctrl['id'] ?? '' );
		$label = sanitize_text_field( $ctrl['label'] ?? ucfirst( str_replace( '_', ' ', $id ) ) );
		$type  = $ctrl['type'] ?? 'text';

		$args = [
			'label'       => $label,
			'description' => sanitize_text_field( $ctrl['description'] ?? '' ),
			'dynamic'     => [
				'active' => true,
			],
		];

		switch ( $type ) {
			case 'textarea':
				$args['type'] = Controls_Manager::TEXTAREA;
				$args['rows'] = $ctrl['rows'] ?? 5;
				if ( array_key_exists( 'default', $ctrl ) ) {
					$args['default'] = $ctrl['default'];
				}
				break;

			case 'wysiwyg':
				$args['type'] = Controls_Manager::WYSIWYG;
				if ( array_key_exists( 'default', $ctrl ) ) {
					$args['default'] = $ctrl['default'];
				}
				break;

			case 'url':
				$args['type'] = Controls_Manager::URL;
				$args['default'] = [
					'url'         => $ctrl['default'] ?? '#',
					'is_external' => false,
					'nofollow'    => false,
				];
				break;

			case 'media':
				$args['type']    = Controls_Manager::MEDIA;
				$args['default'] = [
					'url' => Utils::get_placeholder_image_src(),
				];
				break;

			case 'color':
				$args['type'] = Controls_Manager::COLOR;
				if ( array_key_exists( 'default', $ctrl ) ) {
					$args['default'] = $ctrl['default'];
				}
				break;

			case 'select':
				$args['type']    = Controls_Manager::SELECT;
				$args['options'] = $ctrl['options'] ?? [];
				if ( array_key_exists( 'default', $ctrl ) ) {
					$args['default'] = $ctrl['default'];
				}
				break;

			case 'switcher':
				$args['type']         = Controls_Manager::SWITCHER;
				$args['label_on']     = esc_html__( 'Yes', 'blaze-widgets-for-elementor' );
				$args['label_off']    = esc_html__( 'No', 'blaze-widgets-for-elementor' );
				$args['return_value'] = 'yes';
				$args['default']      = $ctrl['default'] ?? 'yes';
				break;

			case 'number':
				$args['type']    = Controls_Manager::NUMBER;
				$args['default'] = $ctrl['default'] ?? 0;
				break;

			default:
				// "text" and any unknown types fall back to a single-line text input.
				$args['type'] = Controls_Manager::TEXT;
				if ( array_key_exists( 'default', $ctrl ) ) {
					$args['default'] = $ctrl['default'];
				}
				break;
		}

		// Repeater children cannot use selectors; only top-level style controls can.
		if ( ! $in_repeater && ! empty( $ctrl['selectors'] ) && is_array( $ctrl['selectors'] ) ) {
			$args['selectors'] = $ctrl['selectors'];
		}

		return $args;
	}

	/**
	 * Render the widget output based on HTML template and settings.
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$html_tpl = $this->config['html_tpl'] ?? '';
		$css      = $this->config['css'] ?? '';
		$js       = $this->config['js'] ?? '';
		$slug     = sanitize_key( $this->config['slug'] ?? 'custom-widget' );

		// Replace placeholders in HTML template like {{title}}, {{image.url}}, {{button_url.url}}.
		$rendered_html = $this->compile_template( $html_tpl, $settings );

		// Atomic Edit compatibility attributes (enabled via Settings, disabled by default).
		$atomic_attr = '';
		if ( Settings::is_atomic_edit_enabled() ) {
			$atomic_attr = ' data-atomic-edit data-widget-slug="' . esc_attr( $slug ) . '"';
		}

		// Optional per-widget class suffix.
		$wrapper_class = 'blaze-' . $slug;

		// Output scoped CSS if defined inline.
		if ( ! empty( $css ) ) {
			// Defense-in-depth: block style-tag breakout and live tags, and strip
			// @import rules, expression() and script-style URL schemes from url().
			$safe_css = preg_replace( '#</style[^>]*>#i', '', $css );
			$safe_css = preg_replace( '#<(\s*/?\s*)(script|iframe|object|embed|style)\b[^>]*>#i', '', $safe_css );
			$safe_css = preg_replace( '#@import\b[^;]*;?#i', '', $safe_css );
			$safe_css = preg_replace( '#expression\s*\(#i', '', $safe_css );
			$safe_css = preg_replace( '#url\s*\(\s*(["\']?)\s*(javascript|vbscript)\s*:#i', 'url($1', $safe_css );
			echo '<style>' . $safe_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<div class="' . esc_attr( $wrapper_class ) . '"' . $atomic_attr . '>';
		echo $rendered_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';

		// JS is registered and enqueued by Assets_Manager via get_script_depends()
		// so that it runs in both the frontend and the Elementor editor preview.
		// Inline scripts do NOT execute in the editor, so we do not print them here.
	}

	/**
	 * Compile an HTML template with placeholders and iteration blocks.
	 *
	 * Template syntax:
	 *  - {{control_id}}          plain text (esc_html)
	 *  - {{control_id.url}}      URL string (esc_url)
	 *  - {{control_id.html}}     trusted rich content (wp_kses_post)
	 *  - {{#repeater_id}}...{{/repeater_id}}
	 *                            loops the inner block once per repeater row
	 *
	 * @param string $template HTML template.
	 * @param array  $settings Evaluated Elementor settings.
	 * @return string Compiled HTML.
	 */
	protected function compile_template( string $template, array $settings ): string {
		// Expand repeatable sections first: {{#items}}...{{/items}}.
		$template = preg_replace_callback(
			'~\{\{\s*\#([a-zA-Z0-9_]+)\s*\}\}(.*?)\{\{\s*/\1\s*\}\}~s',
			function ( $matches ) use ( $settings ) {
				$repeater_id = $matches[1];
				$inner       = $matches[2];
				$rows        = $settings[ $repeater_id ] ?? [];

				if ( ! is_array( $rows ) || empty( $rows ) ) {
					return '';
				}

				// A repeater may nominate one row as "expanded/active" on the server
				// so the initial state renders even where widget JS does not run
				// (Elementor editor preview). Config: active_class + active_setting.
				$active_status = $this->get_repeater_active_status( $repeater_id, $settings );

				$output = '';
				foreach ( $rows as $index => $row ) {
					$row_inner = $inner;

					if ( $active_status ) {
						$row_class  = ( $index === $active_status['index'] ) ? $active_status['class'] : '';
						$token      = '{{' . $repeater_id . '_active_class}}';
						$row_inner  = str_replace( $token, $row_class, $row_inner );
					}

					$output .= $this->compile_simple( $row_inner, is_array( $row ) ? $row : [] );
				}
				return $output;
			},
			$template
		);

		return $this->compile_simple( $template, $settings );
	}

	/**
	 * Resolve the server-rendered active row for a repeater loop.
	 *
	 * Returns null unless the repeater control declared both an active_class and
	 * a sibling setting holding the zero-based index to mark.
	 *
	 * @param string $repeater_id Repeater control id.
	 * @param array  $settings    Evaluated settings.
	 * @return array|null Array with 'class' and 'index' keys, or null.
	 */
	protected function get_repeater_active_status( string $repeater_id, array $settings ): ?array {
		$active_class   = '';
		$active_setting = '';
		$index_base     = 0;

		foreach ( $this->config['controls'] ?? [] as $ctrl ) {
			if ( ! is_array( $ctrl ) || ( $ctrl['id'] ?? '' ) !== $repeater_id ) {
				continue;
			}
			if ( ! empty( $ctrl['active_class'] ) ) {
				$active_class = sanitize_key( $ctrl['active_class'] );
			}
			if ( ! empty( $ctrl['active_setting'] ) ) {
				$active_setting = sanitize_key( $ctrl['active_setting'] );
			}
			if ( isset( $ctrl['active_index_base'] ) ) {
				$index_base = (int) $ctrl['active_index_base'];
			}
		}

		if ( '' === $active_class || '' === $active_setting ) {
			return null;
		}

		$raw_index = isset( $settings[ $active_setting ] ) ? (int) $settings[ $active_setting ] : $index_base;
		$index     = $raw_index - $index_base;
		if ( $index < 0 ) {
			$index = 0;
		}

		return [ 'class' => $active_class, 'index' => $index ];
	}

	/**
	 * Resolve simple {{placeholder}} tokens against a settings/row context.
	 *
	 * @param string $template HTML template.
	 * @param array  $context  Settings array or a repeater row.
	 * @return string Compiled HTML.
	 */
	protected function compile_simple( string $template, array $context ): string {
		return preg_replace_callback(
			'/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/',
			function ( $matches ) use ( $context ) {
				$raw_key   = $matches[1];
				$key_parts = explode( '.', $raw_key );

				// Rich content mode for wysiwyg/textarea values: {{control_id.html}}.
				$is_html = isset( $key_parts[1] ) && 'html' === strtolower( $key_parts[1] );
				if ( $is_html ) {
					$field = $context[ $key_parts[0] ] ?? null;
					return is_string( $field ) ? wp_kses_post( $field ) : '';
				}

				// {{control_id.attributes}} renders a ready target/rel snippet from
				// a URL control (is_external / nofollow), so templates do not need
				// to reimplement Elementor's link behavior by hand.
				if ( 'attributes' === end( $key_parts ) ) {
					$parent_parts = array_slice( $key_parts, 0, -1 );
					$parent       = $context;
					foreach ( $parent_parts as $k ) {
						if ( is_array( $parent ) && isset( $parent[ $k ] ) ) {
							$parent = $parent[ $k ];
						} else {
							$parent = null;
							break;
						}
					}
					if ( is_array( $parent ) && isset( $parent['url'] ) ) {
						return $this->build_url_attributes( $parent );
					}
					return '';
				}

				$val = $context;

				foreach ( $key_parts as $k ) {
					if ( is_array( $val ) && isset( $val[ $k ] ) ) {
						$val = $val[ $k ];
					} else {
						return '';
					}
				}

				// Media / URL arrays resolve to their "url" scalar.
				if ( is_array( $val ) && isset( $val['url'] ) ) {
					return esc_url( (string) $val['url'] );
				}

				if ( is_scalar( $val ) ) {
					return esc_html( (string) $val );
				}

				return '';
			},
			$template
		);
	}

	/**
	 * Build a target/rel attribute snippet from a URL control's value.
	 *
	 * Mirrors Elementor's link behavior: external links open in a new tab and
	 * receive noopener/noreferrer; the nofollow flag adds rel="nofollow".
	 *
	 * @param array $url_data URL control value (url, is_external, nofollow).
	 * @return string Attribute snippet (empty when no flags are set).
	 */
	protected function build_url_attributes( array $url_data ): string {
		$rel_tokens = [];

		if ( ! empty( $url_data['is_external'] ) ) {
			$rel_tokens[] = 'noopener';
			$rel_tokens[] = 'noreferrer';
		}

		if ( ! empty( $url_data['nofollow'] ) ) {
			$rel_tokens[] = 'nofollow';
		}

		$attributes = '';

		if ( ! empty( $url_data['is_external'] ) ) {
			$attributes .= ' target="_blank"';
		}

		if ( $rel_tokens ) {
			$attributes .= ' rel="' . esc_attr( implode( ' ', $rel_tokens ) ) . '"';
		}

		return $attributes;
	}
}
