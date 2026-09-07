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
		add_action( 'pre_current_active_plugins', [ $this, 'render_plugins_promo' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_promo' ] );
	}

	/**
	 * Render the Ko-fi promo below the Plugins page title.
	 *
	 * WordPress fires `pre_current_active_plugins` right after the
	 * `<h1>Plugins</h1>` header and `<hr class="wp-header-end">`, which is the
	 * native slot for integrated (non-floating) notices on that screen.
	 */
	public function render_plugins_promo(): void {
		if ( ! is_admin() ) {
			return;
		}
		$this->render_kofi_banner();
	}

	/**
	 * Register the promo as a native Dashboard widget.
	 *
	 * Rendered inside the dashboard widget grid (below the "Dashboard"
	 * title) instead of floating at the top via `admin_notices`.
	 */
	public function register_dashboard_promo(): void {
		wp_add_dashboard_widget(
			'blaze-kofi-promo',
			esc_html__( 'Support Blaze Widgets', 'blaze-widgets-for-elementor' ),
			[ $this, 'render_dashboard_promo' ],
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Output the promo banner inside the dashboard widget.
	 */
	public function render_dashboard_promo(): void {
		$this->render_kofi_banner();
	}

	/**
	 * Dismissible Ko-fi banner.
	 *
	 * `$button_only` renders just the ko-fi button widget (used when the
	 * banner is integrated into a Blaze page header) without the message
	 * text, the dismiss button, or the enclosing banner shell.
	 *
	 * Visible by default; admin.js hides it only if the user already
	 * dismissed it on this browser. Showing it by default means a banner
	 * is never lost to a JS / localStorage failure.
	 *
	 * @param bool $button_only Render only the ko-fi button widget.
	 */
	public function render_kofi_banner( bool $button_only = false ): void {
		if ( $button_only ) {
			$this->render_kofi_header_widget();
			return;
		}
		?>
		<div class="blaze-kofi" id="blaze-kofi-banner">
			<p class="blaze-kofi__message">
				<?php esc_html_e( 'If you like', 'blaze-widgets-for-elementor' ); ?>
				<strong class="blaze-kofi__brand"><?php esc_html_e( 'Blaze Widgets', 'blaze-widgets-for-elementor' ); ?></strong>
				<?php esc_html_e( ', please consider to', 'blaze-widgets-for-elementor' ); ?>
			</p>
			<?php $this->render_kofi_banner_widget(); ?>
			<button type="button" class="blaze-kofi__close" data-blaze-kofi-close aria-label="<?php esc_attr_e( 'Dismiss', 'blaze-widgets-for-elementor' ); ?>">
				<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
			</button>
		</div>
		<script type="text/javascript">
			// Hide immediately for visitors who dismissed the banner. Waiting for
			// footer scripts would flash the banner for one render on every load.
			try {
				if ( window.localStorage.getItem( 'blaze_widgets_kofi_banner_dismissed_v2' ) === '1' ) {
					document.getElementById( 'blaze-kofi-banner' ).setAttribute( 'hidden', '' );
				}
			} catch ( e ) {}
		</script>
		<?php
	}

	/**
	 * The ko-fi widget button shown in the Blaze page headers.
	 *
	 * Always rendered, so the button stays visible permanently even for
	 * visitors who dismissed the banner elsewhere on the site.
	 */
	public function render_kofi_header_widget(): void {
		?>
		<span class="blaze-kofi__widget">
			<script type="text/javascript" src="https://storage.ko-fi.com/cdn/widget/Widget_2.js"></script>
			<script type="text/javascript">
				kofiwidget2.init( '<?php echo esc_js( __( 'Buy me a coffee', 'blaze-widgets-for-elementor' ) ); ?>', '#ffffff', 'I2I612K2L0' );
				kofiwidget2.draw();
			</script>
		</span>
		<?php
	}

	/**
	 * The ko-fi widget button inside the banner.
	 *
	 * Skips drawing for visitors who already dismissed the banner — the
	 * banner is hidden for them anyway, so this saves the ko-fi script and
	 * iframe load on every Plugins/Dashboard visit.
	 */
	public function render_kofi_banner_widget(): void {
		?>
		<span class="blaze-kofi-banner__widget">
			<script type="text/javascript" src="https://storage.ko-fi.com/cdn/widget/Widget_2.js"></script>
			<script type="text/javascript">
				(function () {
					var dismissed = false;
					try {
						dismissed = window.localStorage.getItem( 'blaze_widgets_kofi_banner_dismissed_v2' ) === '1';
					} catch ( e ) {
						dismissed = false;
					}
					if ( dismissed ) {
						return;
					}
					kofiwidget2.init( '<?php echo esc_js( __( 'Buy me a coffee', 'blaze-widgets-for-elementor' ) ); ?>', '#ffffff', 'I2I612K2L0' );
					kofiwidget2.draw();
				})();
			</script>
		</span>
		<?php
	}

	/**
	 * Small right-aligned author credit card.
	 *
	 * Used inside the Blaze admin pages as a subtle in-page footer instead
	 * of replacing the WordPress admin footer text.
	 */
	public function render_footer_card(): void {
		$github = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.55v-2.17c-3.2.7-3.88-1.54-3.88-1.54-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.75 1.18 1.75 1.18 1.02 1.75 2.68 1.25 3.33.95.1-.74.4-1.25.73-1.54-2.55-.29-5.23-1.28-5.23-5.68 0-1.26.45-2.28 1.18-3.09-.12-.29-.51-1.46.11-3.05 0 0 .96-.31 3.15 1.18a10.9 10.9 0 0 1 2.87-.39c.97 0 1.95.13 2.87.39 2.19-1.49 3.15-1.18 3.15-1.18.62 1.59.23 2.76.11 3.05.73.81 1.18 1.83 1.18 3.09 0 4.4-2.68 5.38-5.24 5.67.41.35.78 1.05.78 2.12v3.14c0 .3.2.66.8.55A11.51 11.51 0 0 0 23.5 12C23.5 5.65 18.35.5 12 .5z"/></svg>';
		?>
		<div class="blaze-footer-card">
			<span class="blaze-footer-card__text">
				<?php esc_html_e( 'Crafted with', 'blaze-widgets-for-elementor' ); ?>
				<span class="blaze-footer-heart" aria-hidden="true">&#10084;</span>
				<?php esc_html_e( 'by', 'blaze-widgets-for-elementor' ); ?>
			</span>
			<a class="blaze-footer-credit" href="https://github.com/JackBlaze132" target="_blank" rel="noopener noreferrer">
				Blaze
				<span class="screen-reader-text"><?php esc_html_e( 'GitHub profile', 'blaze-widgets-for-elementor' ); ?></span>
				<?php echo $github; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
		<?php
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
			BLAZE_WIDGETS_URL . 'assets/img/blaze-icon.svg',
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
		<div class="wrap blaze-wrap">
			<header class="blaze-header">
				<span class="blaze-header__logo">
					<img src="<?php echo esc_url( BLAZE_WIDGETS_URL . 'assets/img/blaze-icon.svg' ); ?>" alt="" width="30" height="30">
				</span>
				<div class="blaze-header__titles">
					<h1><?php esc_html_e( 'Blaze Widgets & Components', 'blaze-widgets-for-elementor' ); ?></h1>
					<p><?php esc_html_e( 'Your custom Elementor component library, all in one place.', 'blaze-widgets-for-elementor' ); ?></p>
				</div>
				<span class="blaze-header__action">
					<?php $this->render_kofi_header_widget(); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets-builder' ) ); ?>" class="blaze-btn blaze-btn--primary">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Create New Component', 'blaze-widgets-for-elementor' ); ?>
					</a>
				</span>
			</header>

			<?php if ( isset( $_GET['status'] ) ) : ?>
				<div class="blaze-notices">
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
				</div>
			<?php endif; ?>

			<div class="blaze-dashboard">
				<!-- Left column: Widget Table -->
				<section class="blaze-table">
					<div class="blaze-card">
						<div class="blaze-card__header">
							<span class="dashicons dashicons-layout" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Active Widgets Library', 'blaze-widgets-for-elementor' ); ?></h2>
						</div>
						<div class="blaze-card__body" style="padding-top: 12px;">
							<table class="wp-list-table widefat fixed table-view-list">
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
										<td><span class="blaze-table__title"><?php esc_html_e( 'Blaze Example Card', 'blaze-widgets-for-elementor' ); ?></span></td>
										<td>
											<span class="blaze-table__type">
												<span class="dashicons dashicons-lock" aria-hidden="true"></span>
												<?php esc_html_e( 'Built-in Module', 'blaze-widgets-for-elementor' ); ?>
											</span>
										</td>
										<td><span class="blaze-table__handle">blaze-example-card</span></td>
										<td><span class="blaze-pill blaze-pill--active"><?php esc_html_e( 'Active', 'blaze-widgets-for-elementor' ); ?></span></td>
										<td><span class="blaze-table__type"><?php esc_html_e( 'Code-managed', 'blaze-widgets-for-elementor' ); ?></span></td>
									</tr>

									<!-- Custom User Created Widgets -->
									<?php if ( ! empty( $custom_widgets ) ) : ?>
										<?php foreach ( $custom_widgets as $w_slug => $widget ) : ?>
											<tr>
												<td><span class="blaze-table__title"><?php echo esc_html( $widget['title'] ?? $w_slug ); ?></span></td>
												<td>
													<span class="blaze-table__type">
														<span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
														<?php esc_html_e( 'Custom Component', 'blaze-widgets-for-elementor' ); ?>
													</span>
												</td>
												<td><span class="blaze-table__handle">blaze-<?php echo esc_html( $w_slug ); ?></span></td>
												<td>
													<?php if ( ! empty( $widget['active'] ) ) : ?>
														<span class="blaze-pill blaze-pill--active"><?php esc_html_e( 'Active', 'blaze-widgets-for-elementor' ); ?></span>
													<?php else : ?>
														<span class="blaze-pill blaze-pill--inactive"><?php esc_html_e( 'Inactive', 'blaze-widgets-for-elementor' ); ?></span>
													<?php endif; ?>
												</td>
												<td>
													<div class="blaze-actions">
														<a class="blaze-action" href="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets-builder&edit=' . $w_slug ) ); ?>" title="<?php esc_attr_e( 'Edit widget', 'blaze-widgets-for-elementor' ); ?>">
															<span class="dashicons dashicons-edit" aria-hidden="true"></span>
															<span class="screen-reader-text"><?php esc_html_e( 'Edit', 'blaze-widgets-for-elementor' ); ?></span>
														</a>
														<a class="blaze-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blaze-widgets&action=export_blaze_widget&slug=' . $w_slug ), 'blaze_export_widget_' . $w_slug ) ); ?>" title="<?php esc_attr_e( 'Export widget as JSON', 'blaze-widgets-for-elementor' ); ?>">
															<span class="dashicons dashicons-download" aria-hidden="true"></span>
															<span class="screen-reader-text"><?php esc_html_e( 'Export', 'blaze-widgets-for-elementor' ); ?></span>
														</a>
														<a class="blaze-action blaze-action--danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blaze-widgets&action=delete_blaze_widget&slug=' . $w_slug ), 'blaze_delete_widget_' . $w_slug ) ); ?>" title="<?php esc_attr_e( 'Delete widget', 'blaze-widgets-for-elementor' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to remove this widget?', 'blaze-widgets-for-elementor' ); ?>');">
															<span class="dashicons dashicons-trash" aria-hidden="true"></span>
															<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'blaze-widgets-for-elementor' ); ?></span>
														</a>
													</div>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</section>

				<!-- Right column: Upload / Import + Tips -->
				<aside class="blaze-sidebar">
					<div class="blaze-card">
						<div class="blaze-card__header">
							<span class="dashicons dashicons-upload" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Upload Custom Component', 'blaze-widgets-for-elementor' ); ?></h2>
						</div>
						<div class="blaze-card__body">
							<div class="blaze-upload">
								<div class="blaze-upload__icon">
									<span class="dashicons dashicons-portfolio" aria-hidden="true"></span>
								</div>
								<p><?php esc_html_e( 'Upload a widget definition (.json) exported from Blaze Widgets or created manually.', 'blaze-widgets-for-elementor' ); ?></p>
								<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets' ) ); ?>">
									<?php wp_nonce_field( 'blaze_import_widget_nonce' ); ?>
									<input type="hidden" name="blaze_action" value="import_widget">
									<input type="file" name="widget_file" accept=".json" required>
									<button type="submit" class="blaze-btn blaze-btn--primary">
										<span class="dashicons dashicons-upload" aria-hidden="true"></span>
										<?php esc_html_e( 'Import & Register', 'blaze-widgets-for-elementor' ); ?>
									</button>
								</form>
								<p style="margin-top: 12px; margin-bottom: 0;">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=blaze-widgets&action=download_blaze_template' ), 'blaze_download_template_nonce' ) ); ?>" class="blaze-btn">
										<span class="dashicons dashicons-download" aria-hidden="true"></span>
										<?php esc_html_e( 'Download Blank Template', 'blaze-widgets-for-elementor' ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>

					<div class="blaze-card">
						<div class="blaze-card__header">
							<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Quick Tips', 'blaze-widgets-for-elementor' ); ?></h2>
						</div>
						<div class="blaze-card__body">
							<ul class="blaze-tips">
								<li>
									<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
									<span><?php esc_html_e( 'All active widgets appear under the "Blaze Widgets" category in Elementor.', 'blaze-widgets-for-elementor' ); ?></span>
								</li>
								<li>
									<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
									<span><?php esc_html_e( 'Dynamic tags (ACF, featured image, post meta) are supported natively on every control.', 'blaze-widgets-for-elementor' ); ?></span>
								</li>
							</ul>
						</div>
					</div>

					<?php $this->render_footer_card(); ?>
				</aside>
			</div>

			<footer class="blaze-footer-pattern blaze-footer-pattern--wave" aria-hidden="true"></footer>
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
		<div class="wrap blaze-wrap blaze-builder">
			<header class="blaze-header">
				<span class="blaze-header__logo">
					<img src="<?php echo esc_url( BLAZE_WIDGETS_URL . 'assets/img/blaze-icon.svg' ); ?>" alt="" width="30" height="30">
				</span>
				<div class="blaze-header__titles">
					<h1><?php echo $widget ? esc_html__( 'Edit Custom Component', 'blaze-widgets-for-elementor' ) : esc_html__( 'Create New Custom Component', 'blaze-widgets-for-elementor' ); ?></h1>
					<p><?php esc_html_e( 'Define your controls, HTML template with Dynamic Tag placeholders, scoped CSS, and scripts.', 'blaze-widgets-for-elementor' ); ?></p>
				</div>
				<span class="blaze-header__action">
					<?php $this->render_kofi_header_widget(); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets' ) ); ?>" class="blaze-btn">
						<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Back to Library', 'blaze-widgets-for-elementor' ); ?>
					</a>
				</span>
			</header>

			<div class="blaze-card">
				<div class="blaze-card__body">
					<div class="blaze-reference">
						<h3>
							<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
							<?php esc_html_e( 'Template reference', 'blaze-widgets-for-elementor' ); ?>
						</h3>
						<p>
							<?php esc_html_e( 'Control types:', 'blaze-widgets-for-elementor' ); ?>
							<code>text</code>, <code>textarea</code>, <code>wysiwyg</code>, <code>url</code>, <code>media</code>, <code>color</code>, <code>select</code>, <code>switcher</code>, <code>number</code>, <code>repeater</code>, <code>typography</code>, <code>border</code>, <code>box_shadow</code>.
						</p>
						<p>
							<?php esc_html_e( 'Placeholders:', 'blaze-widgets-for-elementor' ); ?>
							<code>{{title}}</code> <?php esc_html_e( '(text)', 'blaze-widgets-for-elementor' ); ?>,
							<code>{{description.html}}</code> <?php esc_html_e( '(rich)', 'blaze-widgets-for-elementor' ); ?>,
							<code>{{image}}</code> <?php esc_html_e( '(URL)', 'blaze-widgets-for-elementor' ); ?>.
							<code>typography</code>/<code>border</code>/<code>box_shadow</code> <?php esc_html_e( 'use', 'blaze-widgets-for-elementor' ); ?> <code>"selector"</code>.
						</p>
						<p>
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

						<p class="blaze-builder__submit">
							<button type="submit" class="blaze-btn blaze-btn--primary">
								<span class="dashicons dashicons-saved" aria-hidden="true"></span>
								<?php esc_html_e( 'Save & Register Component', 'blaze-widgets-for-elementor' ); ?>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets' ) ); ?>" class="blaze-btn"><?php esc_html_e( 'Cancel', 'blaze-widgets-for-elementor' ); ?></a>
						</p>
					</form>
				</div>
			</div>

			<?php $this->render_footer_card(); ?>

			<footer class="blaze-footer-pattern blaze-footer-pattern--wave" aria-hidden="true"></footer>
		</div>
		<?php
	}

	/**
	 * Render Settings Page.
	 */
	public function render_settings_page(): void {
		$settings           = Settings::get_all();
		$enable_atomic_edit = ! empty( $settings['enable_atomic_edit'] );
		$atomic_mode        = $settings['atomic_edit_mode'] ?? 'isolated';
		?>
		<div class="wrap blaze-wrap blaze-settings">
			<header class="blaze-header">
				<span class="blaze-header__logo">
					<img src="<?php echo esc_url( BLAZE_WIDGETS_URL . 'assets/img/blaze-icon.svg' ); ?>" alt="" width="30" height="30">
				</span>
				<div class="blaze-header__titles">
					<h1><?php esc_html_e( 'Blaze Widgets Settings', 'blaze-widgets-for-elementor' ); ?></h1>
					<p><?php esc_html_e( 'Configure general behaviors and optional integrations for your component library.', 'blaze-widgets-for-elementor' ); ?></p>
				</div>
				<span class="blaze-header__action">
					<?php $this->render_kofi_header_widget(); ?>
				</span>
			</header>

			<?php if ( isset( $_GET['status'] ) && 'settings_saved' === $_GET['status'] ) : ?>
				<div class="blaze-notices">
					<div class="notice notice-success is-dismissible">
						<p><?php esc_html_e( 'Settings updated successfully.', 'blaze-widgets-for-elementor' ); ?></p>
					</div>
				</div>
			<?php endif; ?>

			<div class="blaze-card">
				<div class="blaze-card__header">
					<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
					<h2><?php esc_html_e( 'Atomic Edit Compatibility', 'blaze-widgets-for-elementor' ); ?></h2>
				</div>
				<div class="blaze-card__body">
					<p class="description">
						<?php esc_html_e( 'Atomic Edit allows fine-grained micro-component editing and attributes injection. This feature is experimental and disabled by default.', 'blaze-widgets-for-elementor' ); ?>
					</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=blaze-widgets-settings' ) ); ?>">
						<?php wp_nonce_field( 'blaze_save_settings_nonce' ); ?>
						<input type="hidden" name="blaze_action" value="save_settings">

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

						<p class="blaze-builder__submit">
							<button type="submit" class="blaze-btn blaze-btn--primary">
								<span class="dashicons dashicons-saved" aria-hidden="true"></span>
								<?php esc_html_e( 'Save Settings', 'blaze-widgets-for-elementor' ); ?>
							</button>
						</p>
					</form>
				</div>
			</div>

			<?php $this->render_footer_card(); ?>

			<footer class="blaze-footer-pattern blaze-footer-pattern--wave" aria-hidden="true"></footer>
		</div>
		<?php
	}
}
