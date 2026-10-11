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
	 * Attribute `form`: `students` (the default) or `teachers`. An unknown
	 * value falls back to the default rather than printing nothing.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string The container markup.
	 */
	public static function render( $atts = array() ): string {
		$atts = shortcode_atts( array( 'form' => 'students' ), is_array( $atts ) ? $atts : array(), self::TAG );
		$form = self::form( (string) $atts['form'] );

		self::enqueue();

		return sprintf(
			'<div class="ie-generator" data-ie-form="%1$s" data-ie-config="%2$s"><noscript>%3$s</noscript></div>',
			esc_attr( $form ),
			esc_attr( (string) wp_json_encode( Config::defaults() ) ),
			esc_html__( 'This form needs JavaScript.', 'institutional-email' )
		);
	}

	/**
	 * A known form id.
	 *
	 * @param string $form Requested form.
	 */
	public static function form( string $form ): string {
		$form = strtolower( trim( $form ) );
		return in_array( $form, Config::FORMS, true ) ? $form : Config::FORMS[0];
	}

	/**
	 * Load the script and the stylesheet, only on pages that show a form.
	 */
	private static function enqueue(): void {
		wp_enqueue_style( self::HANDLE, Plugin::url() . 'assets/css/institutional-email.css', array(), Plugin::assetVersion( 'assets/css/institutional-email.css' ) );
		wp_enqueue_script( self::HANDLE, Plugin::url() . 'assets/js/institutional-email.js', array(), Plugin::assetVersion( 'assets/js/institutional-email.js' ), array( 'in_footer' => true ) );
	}
}
