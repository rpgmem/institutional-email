<?php
/**
 * The [institutional_email] shortcode.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail;

/**
 * Places a form on a page.
 *
 * The shortcode prints an empty container that carries the configuration;
 * the script draws the form inside it. What a person types stays in their
 * browser, so the plugin never receives or stores it.
 */
final class Shortcode {

	public const TAG = 'institutional_email';

	public const HANDLE = 'institutional-email';

	/**
	 * Hook the shortcode.
	 */
	public static function register(): void {
		add_shortcode( self::TAG, array( self::class, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * Attribute `form`: `students` (the default) or `teachers`. A form the
	 * settings do not offer, or an unknown value, falls back to the first
	 * offered one rather than printing nothing.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string The container markup.
	 */
	public static function render( $atts = array() ): string {
		$atts     = shortcode_atts( array( 'form' => Config::FORMS[0] ), is_array( $atts ) ? $atts : array(), self::TAG );
		$settings = Settings::get();

		if ( Settings::ACCESS_LOGGED_IN === $settings['access'] && ! is_user_logged_in() ) {
			return sprintf(
				'<p class="ie-login-required">%1$s <a href="%2$s">%3$s</a></p>',
				esc_html__( 'Log in to find an institutional email.', 'institutional-email' ),
				esc_url( wp_login_url( (string) get_permalink() ) ),
				esc_html__( 'Log in', 'institutional-email' )
			);
		}

		$form = self::form( (string) $atts['form'], $settings['forms'] );

		self::enqueue();

		return sprintf(
			'<div class="ie-generator" data-ie-form="%1$s" data-ie-config="%2$s"><noscript>%3$s</noscript></div>',
			esc_attr( $form ),
			esc_attr( (string) wp_json_encode( Settings::apply( Config::defaults(), $settings ) ) ),
			esc_html__( 'This form needs JavaScript.', 'institutional-email' )
		);
	}

	/**
	 * An offered form id.
	 *
	 * @param string   $form    Requested form.
	 * @param string[] $offered Forms the settings offer, in order; every form when empty.
	 */
	public static function form( string $form, array $offered = array() ): string {
		$offered = array() !== $offered ? $offered : Config::FORMS;
		$form    = strtolower( trim( $form ) );
		return in_array( $form, $offered, true ) ? $form : $offered[0];
	}

	/**
	 * Load the script and the stylesheet, only on pages that show a form.
	 */
	private static function enqueue(): void {
		wp_enqueue_style( self::HANDLE, Plugin::url() . 'assets/css/institutional-email.css', array(), Plugin::assetVersion( 'assets/css/institutional-email.css' ) );
		wp_enqueue_script( self::HANDLE, Plugin::url() . 'assets/js/institutional-email.js', array(), Plugin::assetVersion( 'assets/js/institutional-email.js' ), array( 'in_footer' => true ) );
	}
}
