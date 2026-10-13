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
	 * Google's tag, enqueued only when Analytics is on.
	 */
	public const GTAG_HANDLE = 'institutional-email-gtag';

	/**
	 * Hook the shortcode.
	 */
	public static function register(): void {
		add_shortcode( self::TAG, array( self::class, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * Attribute `form`: `students` or `teachers` shows that form alone. Left
	 * out, the form opens with a profile selector over the forms the settings
	 * offer, or shows the only one offered. A form the settings do not offer,
	 * or an unknown value, falls back to the first offered one rather than
	 * printing nothing.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string The container markup.
	 */
	public static function render( $atts = array() ): string {
		$atts     = shortcode_atts(
			array(
				'form'    => '',
				'network' => '',
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);
		$settings = Settings::get();

		if ( Settings::ACCESS_LOGGED_IN === $settings['access'] && ! is_user_logged_in() ) {
			return sprintf(
				'<p class="ie-login-required">%1$s <a href="%2$s">%3$s</a></p>',
				esc_html__( 'Log in to find an institutional email.', 'institutional-email' ),
				esc_url( wp_login_url( (string) get_permalink() ) ),
				esc_html__( 'Log in', 'institutional-email' )
			);
		}

		$form    = self::choice( (string) $atts['form'], $settings['forms'] );
		$network = self::network( (string) $atts['network'], $settings['network'] );

		self::enqueue( $settings );

		return sprintf(
			'<div class="ie-generator" data-ie-form="%1$s" data-ie-network="%4$s" data-ie-config="%2$s"><noscript>%3$s</noscript></div>',
			esc_attr( $form ),
			esc_attr( (string) wp_json_encode( Settings::apply( Config::defaults(), $settings ) ) ),
			esc_html__( 'This form needs JavaScript.', 'institutional-email' ),
			esc_attr( $network )
		);
	}

	/**
	 * A known school network: the shortcode's `network` when it names one,
	 * else the site's.
	 *
	 * @param string $asked Requested network: `city` or `state`, or '' for the site's.
	 * @param string $site    The site's network.
	 */
	public static function network( string $asked, string $site ): string {
		$asked = strtolower( trim( $asked ) );
		if ( in_array( $asked, Config::NETWORKS, true ) ) {
			return $asked;
		}
		return in_array( $site, Config::NETWORKS, true ) ? $site : Config::NETWORKS[0];
	}

	/**
	 * The form a container shows: '' to let the visitor choose, when no form
	 * was asked for and more than one is offered; otherwise an offered form.
	 *
	 * @param string   $asked   Requested form; '' when the shortcode names none.
	 * @param string[] $offered Forms the settings offer, in order; every form when empty.
	 */
	public static function choice( string $asked, array $offered = array() ): string {
		$offered = array() !== $offered ? $offered : Config::FORMS;
		if ( '' === trim( $asked ) && count( $offered ) > 1 ) {
			return '';
		}
		return self::form( $asked, $offered );
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
	 * Load the script, the dark-mode switch and the stylesheet, with the
	 * Design tab's rule when it is on, only on pages that show a form;
	 * Google's tag too when the settings turn Analytics on, and nothing of
	 * Google's otherwise.
	 *
	 * @param array<string, mixed> $settings Sanitized settings.
	 */
	private static function enqueue( array $settings ): void {
		wp_enqueue_style( self::HANDLE, Plugin::url() . Plugin::asset( 'assets/css/institutional-email.css' ), array(), Plugin::assetVersion( Plugin::asset( 'assets/css/institutional-email.css' ) ) );
		// The Design tab's width, colours, corners and font, as custom
		// properties the stylesheet reads. The colours are scoped to the light
		// theme: a colour picked against white is not one to draw on the dark
		// ground, and a property set in a style attribute could not be
		// overridden by the dark block. A page with two forms needs it once.
		$rule  = Settings::styleRule( $settings );
		$added = wp_styles()->get_data( self::HANDLE, 'after' );
		if ( '' !== $rule && ! ( is_array( $added ) && in_array( $rule, $added, true ) ) ) {
			wp_add_inline_style( self::HANDLE, $rule );
		}
		Theme::enqueue( true );
		wp_enqueue_script( self::HANDLE, Plugin::url() . Plugin::asset( 'assets/js/institutional-email.js' ), array( Theme::HANDLE ), Plugin::assetVersion( Plugin::asset( 'assets/js/institutional-email.js' ) ), array( 'in_footer' => true ) );

		if ( ! Settings::analyticsOn( $settings ) ) {
			return;
		}
		$id = (string) $settings['analytics_id'];
		// Google's own loader, with no version argument: the URL is Google's.
		wp_enqueue_script( self::GTAG_HANDLE, 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $id ), array(), null, array( 'strategy' => 'async' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- a third-party URL that versions itself.
		wp_add_inline_script(
			self::GTAG_HANDLE,
			'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . (string) wp_json_encode( $id ) . ');'
		);
	}
}
