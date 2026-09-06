<?php
/**
 * Admin Menu and Component Management UI.
 *
 * Provides:
 * - List of widgets (code-based + custom created/uploaded)
 * - Create / Edit dynamic component builder
 * - JSON / ZIP import and export
 * - Settings tab with Atomic Edit compatibility toggle (disabled by default)
 *
 * @package BlazeWidgets\Core
 */

namespace BlazeWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Menu
 */
class Admin_Menu {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
	}

	/**
	 * Register Blaze Widgets admin menus.
	 */
	public function register_admin_menus(): void {
		add_menu_page(
			__( 'Blaze Widgets', 'blaze-widgets-for-elementor' ),
			__( 'Blaze Widgets', 'blaze-widgets-for-elementor' ),
			'manage_options',
			'blaze-widgets',
			[ $this, 'render_dashboard_page' ],
			'dashicons-screenoptions',
			58
		);

		add_submenu_page(
			'blaze-widgets',
			__( 'Widgets & Components', 'blaze-widgets-for-elementor' ),
			__( 'Widgets & Components', 'blaze-widgets-for-elementor' ),
			'manage_options',
			'blaze-widgets',
			[ $this, 'render_dashboard_page' ]
		);

		add_submenu_page(
			'blaze-widgets',
			__( 'Add New Component', 'blaze-widgets-for-elementor' ),
			__( 'Add New Component', 'blaze-widgets-for-elementor' ),
			'manage_options',
			'blaze-widgets-builder',
			[ $this, 'render_builder_page' ]
		);

		add_submenu_page(
			'blaze-widgets',
			__( 'Settings & Integrations', 'blaze-widgets-for-elementor' ),
			__( 'Settings', 'blaze-widgets-for-elementor' ),
			'manage_options',
			'blaze-widgets-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Handle form submissions (Save, Import, Delete, Export, Settings).
	 */
	public function handle_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle Save / Update Widget.
		if ( isset( $_POST['blaze_action'] ) && 'save_widget' === $_POST['blaze_action'] ) {
			check_admin_referer( 'blaze_save_widget_nonce' );

			$controls = [];
			if ( ! empty( $_POST['controls_json'] ) ) {
				$decoded = json_decode( wp_unslash( $_POST['controls_json'] ), true );
				if ( is_array( $decoded ) ) {
					$controls = $decoded;
				}
			}

			$widget_data = [
				'slug'        => sanitize_key( $_POST['slug'] ?? '' ),
				'title'       => sanitize_text_field( $_POST['title'] ?? '' ),
				'icon'        => sanitize_text_field( $_POST['icon'] ?? 'eicon-code' ),
				'category'    => sanitize_text_field( $_POST['category'] ?? 'blaze-widgets' ),
				'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
				'html_tpl'    => wp_unslash( $_POST['html_tpl'] ?? '' ),
				'css'         => wp_unslash( $_POST['css'] ?? '' ),
				'js'          => wp_unslash( $_POST['js'] ?? '' ),
				'active'      => ! empty( $_POST['active'] ),
				'controls'    => $controls,
			];

			$result = Custom_Widget_Storage::save( $widget_data );
			if ( true === $result ) {
				wp_safe_redirect( admin_url( 'admin.php?page=blaze-widgets&status=saved' ) );
				exit;
			} else {
				wp_die( esc_html( $result ) );
			}
		}

		// Handle Delete Widget.
		if ( isset( $_GET['action'] ) && 'delete_blaze_widget' === $_GET['action'] && ! empty( $_GET['slug'] ) ) {
			check_admin_referer( 'blaze_delete_widget_' . $_GET['slug'] );
			Custom_Widget_Storage::delete( sanitize_key( $_GET['slug'] ) );
			wp_safe_redirect( admin_url( 'admin.php?page=blaze-widgets&status=deleted' ) );
			exit;
		}

		// Handle Export Widget JSON.
		if ( isset( $_GET['action'] ) && 'export_blaze_widget' === $_GET['action'] && ! empty( $_GET['slug'] ) ) {
			check_admin_referer( 'blaze_export_widget_' . $_GET['slug'] );
			$slug = sanitize_key( $_GET['slug'] );
			$json = Custom_Widget_Storage::export_json( $slug );
			if ( $json ) {
				nocache_headers();
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename=blaze-' . $slug . '-widget.json' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );
				echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				exit;
			}
		}

		// Handle Blank Template Download.
		if ( isset( $_GET['action'] ) && 'download_blaze_template' === $_GET['action'] ) {
			check_admin_referer( 'blaze_download_template_nonce' );
			$template = Custom_Widget_Storage::get_blank_template();
			nocache_headers();
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=blaze-component-template.json' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			echo wp_json_encode( $template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		// Handle JSON File Import.
		if ( isset( $_POST['blaze_action'] ) && 'import_widget' === $_POST['blaze_action'] ) {
			check_admin_referer( 'blaze_import_widget_nonce' );

			if ( ! empty( $_FILES['widget_file']['tmp_name'] ) ) {
				$file_content = file_get_contents( $_FILES['widget_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFileSystemReading.file_get_contents_file_get_contents
				$res          = Custom_Widget_Storage::import( $file_content );
				if ( true === $res ) {
					wp_safe_redirect( admin_url( 'admin.php?page=blaze-widgets&status=imported' ) );
					exit;
				} else {
					wp_die( esc_html( is_string( $res ) ? $res : 'Import failed.' ) );
				}
			}
		}

		// Handle Settings Update (Atomic Edit etc).
		if ( isset( $_POST['blaze_action'] ) && 'save_settings' === $_POST['blaze_action'] ) {
			check_admin_referer( 'blaze_save_settings_nonce' );

			$settings = [
				'enable_atomic_edit' => ! empty( $_POST['enable_atomic_edit'] ),
				'atomic_edit_mode'   => sanitize_key( $_POST['atomic_edit_mode'] ?? 'isolated' ),
			];

			Settings::update( $settings );
			wp_safe_redirect( admin_url( 'admin.php?page=blaze-widgets-settings&status=settings_saved' ) );
			exit;
		}
	}

	/**
	 * Render Widgets Dashboard / List Page.
	 */
	public function render_dashboard_page(): void {
		$custom_widgets = Custom_Widget_Storage::get_all();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Blaze Widgets & Components', 'blaze-widgets-for-elementor' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets-builder' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Create New Component', 'blaze-widgets-for-elementor' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['status'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						if ( 'saved' === $_GET['status'] ) {
							esc_html_e( 'Widget saved successfully and registered for Elementor!', 'blaze-widgets-for-elementor' );
						} elseif ( 'deleted' === $_GET['status'] ) {
							esc_html_e( 'Widget removed successfully.', 'blaze-widgets-for-elementor' );
						} elseif ( 'imported' === $_GET['status'] ) {
							esc_html_e( 'Widget imported and registered successfully!', 'blaze-widgets-for-elementor' );
						}
						?>
					</p>
				</div>
			<?php endif; ?>

			<div style="display: flex; gap: 20px; margin-top: 20px;">
				<!-- Left column: Widget Table -->
				<div style="flex: 2;">
					<h2><?php esc_html_e( 'Active Widgets Library', 'blaze-widgets-for-elementor' ); ?></h2>
					<table class="wp-list-table widefat fixed striped table-view-list">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title & Name', 'blaze-widgets-for-elementor' ); ?></th>
								<th><?php esc_html_e( 'Type', 'blaze-widgets-for-elementor' ); ?></th>
								<th><?php esc_html_e( 'Slug / Handle', 'blaze-widgets-for-elementor' ); ?></th>
								<th><?php esc_html_e( 'Status', 'blaze-widgets-for-elementor' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'blaze-widgets-for-elementor' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<!-- Core Built-in Widgets -->
							<tr>
								<td><strong><?php esc_html_e( 'Blaze Example Card', 'blaze-widgets-for-elementor' ); ?></strong></td>
								<td><span class="dashicons dashicons-lock" title="Built-in code widget"></span> <?php esc_html_e( 'Built-in Module', 'blaze-widgets-for-elementor' ); ?></td>
								<td><code>blaze-example-card</code></td>
								<td><span style="color: green; font-weight: bold;"><?php esc_html_e( 'Active', 'blaze-widgets-for-elementor' ); ?></span></td>
								<td><em><?php esc_html_e( 'Code-managed', 'blaze-widgets-for-elementor' ); ?></em></td>
							</tr>

							<!-- Custom User Created Widgets -->
							<?php if ( ! empty( $custom_widgets ) ) : ?>
								<?php foreach ( $custom_widgets as $w_slug => $widget ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $widget['title'] ?? $w_slug ); ?></strong></td>
										<td><span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e( 'Custom Component', 'blaze-widgets-for-elementor' ); ?></td>
										<td><code>blaze-<?php echo esc_html( $w_slug ); ?></code></td>
										<td>
											<?php if ( ! empty( $widget['active'] ) ) : ?>
												<span style="color: green; font-weight: bold;"><?php esc_html_e( 'Active', 'blaze-widgets-for-elementor' ); ?></span>
											<?php else : ?>
												<span style="color: #999;"><?php esc_html_e( 'Inactive', 'blaze-widgets-for-elementor' ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets-builder&edit=' . $w_slug ) ); ?>">
												<?php esc_html_e( 'Edit', 'blaze-widgets-for-elementor' ); ?>
											</a> | 
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blaze-widgets&action=export_blaze_widget&slug=' . $w_slug ), 'blaze_export_widget_' . $w_slug ) ); ?>">
												<?php esc_html_e( 'Export', 'blaze-widgets-for-elementor' ); ?>
											</a> | 
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blaze-widgets&action=delete_blaze_widget&slug=' . $w_slug ), 'blaze_delete_widget_' . $w_slug ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to remove this widget?', 'blaze-widgets-for-elementor' ); ?>');" style="color: #a00;">
												<?php esc_html_e( 'Delete', 'blaze-widgets-for-elementor' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Right column: Upload / Import Box -->
				<div style="flex: 1;">
					<div class="postbox" style="padding: 16px;">
						<h2><?php esc_html_e( 'Upload Custom Component', 'blaze-widgets-for-elementor' ); ?></h2>
						<p><?php esc_html_e( 'Upload a custom widget definition (.json template) exported from Blaze Widgets or created manually.', 'blaze-widgets-for-elementor' ); ?></p>
						<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets' ) ); ?>">
							<?php wp_nonce_field( 'blaze_import_widget_nonce' ); ?>
							<input type="hidden" name="blaze_action" value="import_widget">
							<p>
								<input type="file" name="widget_file" accept=".json" required>
							</p>
							<p>
								<button type="submit" class="button button-primary">
									<span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
									<?php esc_html_e( 'Import & Register', 'blaze-widgets-for-elementor' ); ?>
								</button>
							</p>
						</form>
						<p>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blaze-widgets&action=download_blaze_template' ), 'blaze_download_template_nonce' ) ); ?>" class="button">
								<span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'Download Blank Template', 'blaze-widgets-for-elementor' ); ?>
							</a>
						</p>
					</div>

					<div class="postbox" style="padding: 16px;">
						<h3><?php esc_html_e( 'Quick Tips', 'blaze-widgets-for-elementor' ); ?></h3>
						<ul style="list-style: disc; padding-left: 20px;">
							<li><?php esc_html_e( 'All active widgets automatically appear under the "Blaze Widgets" category in Elementor.', 'blaze-widgets-for-elementor' ); ?></li>
							<li><?php esc_html_e( 'You can define custom dynamic fields with dynamic tags (ACF, featured image, post meta) supported natively.', 'blaze-widgets-for-elementor' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Create / Edit Component Builder Page.
	 */
	public function render_builder_page(): void {
		$edit_slug = isset( $_GET['edit'] ) ? sanitize_key( $_GET['edit'] ) : '';
		$widget    = $edit_slug ? Custom_Widget_Storage::get( $edit_slug ) : null;

		$slug        = $widget['slug'] ?? '';
		$title       = $widget['title'] ?? '';
		$icon        = $widget['icon'] ?? 'eicon-code';
		$category    = $widget['category'] ?? 'blaze-widgets';
		$description = $widget['description'] ?? '';
		$html_tpl    = $widget['html_tpl'] ?? '<div class="my-custom-box">\n  <h3>{{title}}</h3>\n  <p>{{text}}</p>\n</div>';
		$css         = $widget['css'] ?? '.my-custom-box {\n  padding: 20px;\n  background: #f9fafb;\n  border-radius: 8px;\n}';
		$js          = $widget['js'] ?? '';
		$active      = isset( $widget['active'] ) ? ! empty( $widget['active'] ) : true;
		$controls    = isset( $widget['controls'] ) ? wp_json_encode( $widget['controls'], JSON_PRETTY_PRINT ) : wp_json_encode(
			[
				[
					'id'      => 'title',
					'label'   => 'Title',
					'type'    => 'text',
					'tab'     => 'content',
					'default' => 'Custom Component Title',
				],
				[
					'id'      => 'text',
					'label'   => 'Description Text',
					'type'    => 'textarea',
					'tab'     => 'content',
					'default' => 'Description with Elementor Dynamic Tags support.',
				],
			],
			JSON_PRETTY_PRINT
		);
		?>
		<div class="wrap">
			<h1><?php echo $widget ? esc_html__( 'Edit Custom Component', 'blaze-widgets-for-elementor' ) : esc_html__( 'Create New Custom Component', 'blaze-widgets-for-elementor' ); ?></h1>
			<p><?php esc_html_e( 'Define your Elementor widget controls, HTML template with Dynamic Tag placeholders, scoped CSS, and scripts.', 'blaze-widgets-for-elementor' ); ?></p>

			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Template reference:', 'blaze-widgets-for-elementor' ); ?></strong><br>
					<?php esc_html_e( 'Control types:', 'blaze-widgets-for-elementor' ); ?>
					<code>text</code>, <code>textarea</code>, <code>wysiwyg</code>, <code>url</code>, <code>media</code>, <code>color</code>, <code>select</code>, <code>switcher</code>, <code>number</code>, <code>repeater</code>, <code>typography</code>, <code>border</code>, <code>box_shadow</code>.<br>
					<?php esc_html_e( 'Placeholders:', 'blaze-widgets-for-elementor' ); ?>
					<code>{{title}}</code> <?php esc_html_e( '(text)', 'blaze-widgets-for-elementor' ); ?>,
					<code>{{description.html}}</code> <?php esc_html_e( '(rich)', 'blaze-widgets-for-elementor' ); ?>,
					<code>{{image}}</code> <?php esc_html_e( '(URL)', 'blaze-widgets-for-elementor' ); ?>.
					<code>typography</code>/<code>border</code>/<code>box_shadow</code> use <code>"selector"</code>.<br>
					<?php esc_html_e( 'Repeaters:', 'blaze-widgets-for-elementor' ); ?>
					<code>{"type":"repeater","fields":[...]}</code> <?php esc_html_e( 'looped with', 'blaze-widgets-for-elementor' ); ?>
					<code>{{#items}}...{{/items}}</code>. <?php esc_html_e( 'Server-rendered expanded row:', 'blaze-widgets-for-elementor' ); ?>
					<code>"active_class"</code> + <code>"active_setting"</code> + <code>"active_index_base"</code> + <code>{{items_active_class}}</code>.
				</p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets' ) ); ?>" data-blaze-builder>
				<?php wp_nonce_field( 'blaze_save_widget_nonce' ); ?>
				<input type="hidden" name="blaze_action" value="save_widget">

				<table class="form-table">
					<tr>
						<th scope="row"><label for="title"><?php esc_html_e( 'Component Title', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<input name="title" id="title" type="text" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required placeholder="e.g., Blaze Hero Banner">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="slug"><?php esc_html_e( 'Unique Slug', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<input name="slug" id="slug" type="text" class="regular-text" value="<?php echo esc_attr( $slug ); ?>" <?php echo $widget ? 'readonly' : 'required'; ?> placeholder="e.g., hero-banner">
							<p class="description"><?php esc_html_e( 'Unique identifier. Will be registered in Elementor as blaze-{slug}.', 'blaze-widgets-for-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="icon"><?php esc_html_e( 'Elementor Icon Class', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<input name="icon" id="icon" type="text" class="regular-text" value="<?php echo esc_attr( $icon ); ?>" placeholder="eicon-code, eicon-banner, etc.">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="controls_json"><?php esc_html_e( 'Controls Definition (JSON)', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<textarea name="controls_json" id="controls_json" rows="8" class="large-text code"><?php echo esc_textarea( $controls ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Array of Elementor controls (types: text, textarea, wysiwyg, url, media, color, select, switcher). All support Dynamic Tags.', 'blaze-widgets-for-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="html_tpl"><?php esc_html_e( 'HTML Template', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<textarea name="html_tpl" id="html_tpl" rows="8" class="large-text code"><?php echo esc_textarea( $html_tpl ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Use {{control_id}} placeholders to inject dynamic control values, e.g., {{title}}, {{image.url}}, {{button_url.url}}.', 'blaze-widgets-for-elementor' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="css"><?php esc_html_e( 'Scoped CSS', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<textarea name="css" id="css" rows="6" class="large-text code"><?php echo esc_textarea( $css ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="js"><?php esc_html_e( 'Widget JavaScript (Optional)', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<textarea name="js" id="js" rows="4" class="large-text code"><?php echo esc_textarea( $js ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Active in Elementor', 'blaze-widgets-for-elementor' ); ?></th>
						<td>
							<label>
								<input name="active" type="checkbox" value="1" <?php checked( $active ); ?>>
								<?php esc_html_e( 'Enable this widget in the Elementor editor and frontend', 'blaze-widgets-for-elementor' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save & Register Component', 'blaze-widgets-for-elementor' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'blaze-widgets-for-elementor' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Settings & Integrations Page (Atomic Edit, etc).
	 */
	public function render_settings_page(): void {
		$settings           = Settings::get_all();
		$enable_atomic_edit = ! empty( $settings['enable_atomic_edit'] );
		$atomic_mode        = $settings['atomic_edit_mode'] ?? 'isolated';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Blaze Widgets Settings & Integrations', 'blaze-widgets-for-elementor' ); ?></h1>
			<p><?php esc_html_e( 'Configure general behaviors and optional integrations for your custom component library.', 'blaze-widgets-for-elementor' ); ?></p>

			<?php if ( isset( $_GET['status'] ) && 'settings_saved' === $_GET['status'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings updated successfully.', 'blaze-widgets-for-elementor' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets-settings' ) ); ?>">
				<?php wp_nonce_field( 'blaze_save_settings_nonce' ); ?>
				<input type="hidden" name="blaze_action" value="save_settings">

				<h2 class="title"><?php esc_html_e( 'Atomic Edit Compatibility', 'blaze-widgets-for-elementor' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Atomic Edit allows fine-grained micro-component editing and attributes injection. This feature is experimental and disabled by default.', 'blaze-widgets-for-elementor' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Atomic Edit', 'blaze-widgets-for-elementor' ); ?></th>
						<td>
							<label>
								<input name="enable_atomic_edit" type="checkbox" value="1" <?php checked( $enable_atomic_edit ); ?>>
								<strong><?php esc_html_e( 'Enable Atomic Edit mode on Blaze custom components', 'blaze-widgets-for-elementor' ); ?></strong>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, components will output data-atomic-edit attributes for compatibility with atomic editing tools.', 'blaze-widgets-for-elementor' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="atomic_edit_mode"><?php esc_html_e( 'Atomic Edit Mode', 'blaze-widgets-for-elementor' ); ?></label></th>
						<td>
							<select name="atomic_edit_mode" id="atomic_edit_mode">
								<option value="isolated" <?php selected( $atomic_mode, 'isolated' ); ?>><?php esc_html_e( 'Isolated (Standard)', 'blaze-widgets-for-elementor' ); ?></option>
								<option value="inline" <?php selected( $atomic_mode, 'inline' ); ?>><?php esc_html_e( 'Inline DOM editing', 'blaze-widgets-for-elementor' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'blaze-widgets-for-elementor' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}
}
