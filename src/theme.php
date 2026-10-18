<?php
/**
 * The dark-mode switch, shared by the settings screen and the form.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail;

/**
 * Puts `ie-dark-mode` on <html> according to the `dark_mode` setting.
 *
 * One inline script with no file behind it, printed by the settings screen in
 * the head and by the shortcode before the form's script, and inlined into
 * the standalone HTML. The stylesheets key every dark rule on the class,
 * never on the operating system's media query, so the setting stays the one
 * source of truth: the system is asked only when the setting is "auto".
 *
 * Where Free Form Certificate is active, its own `dark_mode` setting is that
 * source instead, so an operator sets the theme in one place: the plugin
 * reads it at runtime and never requires that plugin.
 */
final class Theme {

	public const HANDLE = 'institutional-email-theme';

	public const CSS_CLASS = 'ie-dark-mode';

	/**
	 * Free Form Certificate's settings option, which carries its `dark_mode`.
	 */
	public const FFC_OPTION = 'ffc_settings';

	/**
	 * Whether Free Form Certificate is active. Its main file defines
	 * `FFC_VERSION` as it loads, so the constant is there exactly when the
	 * plugin is active, on every request, from `plugins_loaded` on.
	 */
	public static function ffcActive(): bool {
		return defined( 'FFC_VERSION' );
	}

	/**
	 * Free Form Certificate's dark mode: `off`, `on` or `auto`; null when
	 * that plugin is not active. An unknown or missing value reads as "off",
	 * which is also that plugin's own default.
	 */
	public static function ffcMode(): ?string {
		if ( ! self::ffcActive() ) {
			return null;
		}
		$settings = get_option( self::FFC_OPTION, array() );
		$mode     = is_array( $settings ) ? ( $settings['dark_mode'] ?? 'off' ) : 'off';
		return in_array( $mode, Settings::DARK_MODES, true ) ? (string) $mode : 'off';
	}

	/**
	 * The dark mode in effect: Free Form Certificate's where it is active,
	 * this plugin's own setting otherwise.
	 */
	public static function mode(): string {
		return self::ffcMode() ?? (string) Settings::get()['dark_mode'];
	}

	/**
	 * Enqueue the script with the stored setting.
	 *
	 * @param bool $in_footer False on the settings screen, where the class
	 *                        must land before the page paints; true for the
	 *                        shortcode, which renders after the head and whose
	 *                        form is drawn by a footer script anyway.
	 */
	public static function enqueue( bool $in_footer ): void {
		if ( wp_script_is( self::HANDLE, 'enqueued' ) ) {
			return;
		}
		wp_register_script( self::HANDLE, false, array(), Plugin::VERSION, array( 'in_footer' => $in_footer ) );
		wp_enqueue_script( self::HANDLE );
		wp_add_inline_script( self::HANDLE, self::script( self::mode(), ! self::ffcActive() ) );
	}

	/**
	 * The script that puts the class on <html>.
	 *
	 * "on" and "off" are decided here; only "auto" asks the operating system,
	 * and it keeps listening, so switching the system theme repaints the open
	 * page. A saved change arrives as `ie:setting-saved` and repaints too.
	 * An unknown value is treated as "off", so only a known word is printed.
	 *
	 * @param string $mode         The `dark_mode` in effect.
	 * @param bool   $follow_saves Whether a save of the local field repaints:
	 *                             not while Free Form Certificate decides.
	 */
	public static function script( string $mode, bool $follow_saves = true ): string {
		if ( ! in_array( $mode, Settings::DARK_MODES, true ) ) {
			$mode = 'off';
		}
		return '(function(mode){'
			. 'var root=document.documentElement;'
			. 'var query=window.matchMedia?window.matchMedia("(prefers-color-scheme: dark)"):null;'
			. 'function apply(){root.classList.toggle("' . self::CSS_CLASS . '",mode==="on"||(mode==="auto"&&!!query&&query.matches));}'
			. 'apply();'
			. 'if(query&&query.addEventListener){query.addEventListener("change",apply);}'
			. ( $follow_saves ? 'document.addEventListener("ie:setting-saved",function(e){if(e.detail&&e.detail.key==="dark_mode"){mode=String(e.detail.value);apply();}});' : '' )
			. '})("' . $mode . '");';
	}
}
