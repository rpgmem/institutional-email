<?php
/**
 * Saves a settings toggle the moment it is flipped.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail\Admin;

use InstitutionalEmail\Settings;
use WP_Error;

/**
 * The AJAX half of the settings screen's toggles.
 *
 * Every toggle is also a named field of the screen's form, so "Save Changes"
 * posts its current state and never undoes what a toggle already saved. Only
 * toggles save here; text fields still wait for the form.
 */
final class Autosave {

	public const ACTION = 'institutional_email_autosave';

	/**
	 * The keys a toggle may save on its own.
	 */
	public const KEYS = array( 'forms', 'teacher_networks', 'check_rf_digit', 'analytics', 'custom_style' );

	/**
	 * Hook the handler. Logged-in only: there is no `nopriv` variant.
	 */
	public static function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( self::class, 'handle' ) );
	}

	/**
	 * The AJAX request: check the nonce and the capability, apply, store.
	 */
	public static function handle(): void {
		if ( false === check_ajax_referer( self::ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'The page expired. Reload it and try again.', 'institutional-email' ) ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change these settings.', 'institutional-email' ) ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified by check_ajax_referer() above.
		$key   = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$value = isset( $_POST['value'] ) ? map_deep( wp_unslash( $_POST['value'] ), 'sanitize_text_field' ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$settings = self::apply( Settings::get(), $key, $value );
		if ( $settings instanceof WP_Error ) {
			wp_send_json_error( array( 'message' => $settings->get_error_message() ), 400 );
		}

		update_option( Settings::OPTION, $settings );
		wp_send_json_success( array( 'value' => Settings::get()[ $key ] ) );
	}

	/**
	 * Apply one toggle to the settings.
	 *
	 * @param array<string, mixed> $settings Sanitized settings.
	 * @param string               $key      `forms`, `teacher_networks`, `check_rf_digit`, `analytics` or `custom_style`.
	 * @param mixed                $value    The ticked ids, or '1' / '0'.
	 * @return array<string, mixed>|WP_Error The sanitized settings, or why the change was refused.
	 */
	public static function apply( array $settings, string $key, $value ) {
		if ( ! in_array( $key, self::KEYS, true ) ) {
			return new WP_Error( 'unknown_key', __( 'This setting cannot be saved on its own. Use "Save Changes".', 'institutional-email' ) );
		}
		if ( 'forms' === $key || 'teacher_networks' === $key ) {
			$settings[ $key ] = array_map( 'strval', is_array( $value ) ? $value : array() );
		} else {
			$settings[ $key ] = is_scalar( $value ) && in_array( (string) $value, array( '1', 'true', 'on' ), true );
		}
		return Settings::sanitize( $settings );
	}
}
