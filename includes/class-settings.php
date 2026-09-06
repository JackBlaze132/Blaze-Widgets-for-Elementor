<?php
/**
 * Settings and Atomic Edit Options Handler.
 *
 * @package BlazeWidgets\Core
 */

namespace BlazeWidgets\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	/**
	 * Settings option name.
	 */
	const OPTION_KEY = 'blaze_widgets_settings';

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public static function get_all(): array {
		$defaults = [
			'enable_atomic_edit' => false,
			'atomic_edit_mode'   => 'isolated',
		];
		$stored = get_option( self::OPTION_KEY, [] );
		return wp_parse_args( is_array( $stored ) ? $stored : [], $defaults );
	}

	/**
	 * Check if Atomic Edit compatibility is enabled.
	 *
	 * Defaults to false (disabled by default).
	 *
	 * @return bool
	 */
	public static function is_atomic_edit_enabled(): bool {
		$settings = self::get_all();
		return ! empty( $settings['enable_atomic_edit'] );
	}

	/**
	 * Update settings.
	 *
	 * @param array $new_settings Settings array.
	 * @return bool
	 */
	public static function update( array $new_settings ): bool {
		$clean = [
			'enable_atomic_edit' => ! empty( $new_settings['enable_atomic_edit'] ),
			'atomic_edit_mode'   => sanitize_key( $new_settings['atomic_edit_mode'] ?? 'isolated' ),
		];
		return update_option( self::OPTION_KEY, $clean );
	}
}
