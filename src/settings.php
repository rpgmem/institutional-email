<?php
/**
 * The administrator's settings.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail;

/**
 * Stores what an administrator changed and applies it to the configuration.
 *
 * Only the differences from the defaults are meaningful: an install that has
 * never saved the settings page generates exactly the default configuration,
 * which is also what the standalone HTML carries.
 */
final class Settings {

	public const OPTION = 'institutional_email_settings';

	public const ACCESS_EVERYONE = 'everyone';

	public const ACCESS_LOGGED_IN = 'logged_in';

	public const MIDDLE_MIN_MIN = 1;

	public const MIDDLE_MIN_MAX = 10;

	public const PASSWORD_MAX = 64;

	public const NAME_MAX = 60;

	public const TEXT_MAX = 300;

	/**
	 * A domain name: dot-separated labels of letters, digits and inner
	 * hyphens, ending in a top-level label of two letters or more.
	 */
	public const DOMAIN_PATTERN = '/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/';

	/**
	 * The stored settings, completed with their defaults.
	 *
	 * @return array{network: string, forms: list<string>, access: string, domain: string, password: string, middle_min: int, check_rf_digit: bool, teacher_networks: list<string>, platform_name: string, platform_url: string, strings: array<string, string>}
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * The settings an install starts with.
	 *
	 * @return array{network: string, forms: list<string>, access: string, domain: string, password: string, middle_min: int, check_rf_digit: bool, teacher_networks: list<string>, platform_name: string, platform_url: string, strings: array<string, string>}
	 */
	public static function defaults(): array {
		return array(
			'network'          => Config::NETWORKS[0],
			'forms'            => Config::FORMS,
			'access'           => self::ACCESS_EVERYONE,
			'domain'           => Config::DOMAIN,
			'password'         => Config::PASSWORD,
			'middle_min'       => Config::MIDDLE_MIN,
			// The RF's check digit is checked unless an administrator turns it off.
			'check_rf_digit'   => true,
			'teacher_networks' => Config::TEACHER_NETWORKS,
			// Empty means the translated default name.
			'platform_name'    => '',
			'platform_url'     => Config::PLATFORM_URL,
			// Only the texts an administrator changed; the rest stay translated.
			'strings'          => array(),
		);
	}

	/**
	 * Clean settings from the form or from storage. Anything unknown is
	 * dropped and anything out of range is brought back into it, so a stored
	 * value written by an older version still yields a usable configuration.
	 *
	 * @param mixed $input Raw settings.
	 * @return array{network: string, forms: list<string>, access: string, domain: string, password: string, middle_min: int, check_rf_digit: bool, teacher_networks: list<string>, platform_name: string, platform_url: string, strings: array<string, string>}
	 */
	public static function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();

		$forms = array_values( array_intersect( Config::FORMS, array_map( 'strval', (array) ( $input['forms'] ?? Config::FORMS ) ) ) );
		if ( array() === $forms ) {
			// A site with no form is a broken page; offer them all instead.
			$forms = Config::FORMS;
		}

		$teachers = array_values( array_intersect( Config::TEACHER_NETWORKS, array_map( 'strval', (array) ( $input['teacher_networks'] ?? Config::TEACHER_NETWORKS ) ) ) );
		if ( array() === $teachers ) {
			// A teachers' form with no option cannot work out an address.
			$teachers = Config::TEACHER_NETWORKS;
		}

		$domain = self::domain( $input['domain'] ?? Config::DOMAIN );

		$password = is_string( $input['password'] ?? null ) ? trim( sanitize_text_field( $input['password'] ) ) : '';
		$password = '' !== $password ? mb_substr( $password, 0, self::PASSWORD_MAX ) : Config::PASSWORD;

		$middle = (int) ( $input['middle_min'] ?? Config::MIDDLE_MIN );

		$access = (string) ( $input['access'] ?? self::ACCESS_EVERYONE );

		$network = (string) ( $input['network'] ?? Config::NETWORKS[0] );

		return array(
			'network'          => in_array( $network, Config::NETWORKS, true ) ? $network : Config::NETWORKS[0],
			'forms'            => $forms,
			'access'           => self::ACCESS_LOGGED_IN === $access ? self::ACCESS_LOGGED_IN : self::ACCESS_EVERYONE,
			'domain'           => '' !== $domain ? $domain : Config::DOMAIN,
			'password'         => $password,
			'middle_min'       => min( self::MIDDLE_MIN_MAX, max( self::MIDDLE_MIN_MIN, $middle ) ),
			// Absent means \"never saved\": keep the default. The form posts a
			// hidden 0 before the checkbox, so unticking it is never absent.
			'check_rf_digit'   => array_key_exists( 'check_rf_digit', $input ) ? ! empty( $input['check_rf_digit'] ) : true,
			'teacher_networks' => $teachers,
			'platform_name'    => mb_substr( sanitize_text_field( is_string( $input['platform_name'] ?? null ) ? $input['platform_name'] : '' ), 0, self::NAME_MAX ),
			// Absent means "never saved": keep the default. An emptied field
			// is a choice: the form then shows no next step.
			'platform_url'     => array_key_exists( 'platform_url', $input ) ? self::url( $input['platform_url'] ) : Config::PLATFORM_URL,
			'strings'          => self::strings( $input['strings'] ?? array() ),
		);
	}

	/**
	 * The Settings API callback: sanitize, and tell the administrator about
	 * a value that was refused.
	 *
	 * WordPress runs the callback twice on the first save of an option, so
	 * a notice already queued is not added again.
	 *
	 * @param mixed $input Posted settings.
	 * @return array<string, mixed>
	 */
	public static function save( $input ): array {
		$input = is_array( $input ) ? $input : array();

		$domain = is_scalar( $input['domain'] ?? null ) ? trim( (string) $input['domain'] ) : '';
		if ( '' !== $domain && '' === self::domain( $domain ) ) {
			self::notice(
				'domain_rejected',
				sprintf(
					/* translators: %s: the domain typed. */
					__( 'The domain was not saved, because it is not a domain name: %s', 'institutional-email' ),
					sanitize_text_field( $domain )
				)
			);
		}

		$url = is_scalar( $input['platform_url'] ?? null ) ? trim( (string) $input['platform_url'] ) : '';
		if ( '' !== $url && '' === self::url( $url ) ) {
			self::notice(
				'url_rejected',
				sprintf(
					/* translators: %s: the address typed. */
					__( 'The platform address was not saved, because it is not an http or https address: %s', 'institutional-email' ),
					sanitize_text_field( $url )
				)
			);
		}

		return self::sanitize( $input );
	}

	/**
	 * Apply settings to a configuration.
	 *
	 * @param array<string, mixed> $config   The default configuration.
	 * @param array<string, mixed> $settings Sanitized settings.
	 * @return array<string, mixed> The configuration the form uses.
	 */
	public static function apply( array $config, array $settings ): array {
		$config['domain']          = (string) $settings['domain'];
		$config['password']        = (string) $settings['password'];
		$config['middleMin']       = (int) $settings['middle_min'];
		$config['network']         = (string) $settings['network'];
		$config['checkRfDigit']    = (bool) $settings['check_rf_digit'];
		$config['teacherNetworks'] = $settings['teacher_networks'];
		$config['platformUrl']     = (string) $settings['platform_url'];
		if ( '' !== $settings['platform_name'] ) {
			$config['platformName'] = (string) $settings['platform_name'];
		}
		foreach ( $settings['strings'] as $key => $text ) {
			if ( isset( $config['strings'][ $key ] ) ) {
				$config['strings'][ $key ] = $text;
			}
		}
		return $config;
	}

	/**
	 * The configuration this site's forms use: the defaults with the stored
	 * settings applied.
	 *
	 * @return array<string, mixed>
	 */
	public static function siteConfig(): array {
		return self::apply( Config::defaults(), self::get() );
	}

	/**
	 * A domain as stored: lower case, without a leading "@"; '' when it is
	 * not a domain name.
	 *
	 * @param mixed $raw Typed domain.
	 */
	public static function domain( $raw ): string {
		$domain = is_scalar( $raw ) ? ltrim( strtolower( trim( (string) $raw ) ), '@' ) : '';
		return 1 === preg_match( self::DOMAIN_PATTERN, $domain ) ? $domain : '';
	}

	/**
	 * An http or https address; '' for anything else.
	 *
	 * @param mixed $raw Typed address.
	 */
	public static function url( $raw ): string {
		$url = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( '' === $url ) {
			return '';
		}
		$clean = esc_url_raw( $url, array( 'http', 'https' ) );
		return '' !== $clean && false !== filter_var( $clean, FILTER_VALIDATE_URL ) ? $clean : '';
	}

	/**
	 * The text overrides: known keys only, plain text, and nothing that
	 * merely repeats the default, so a later change of the default reaches
	 * the site.
	 *
	 * @param mixed $input Raw overrides, key => text.
	 * @return array<string, string>
	 */
	private static function strings( $input ): array {
		$out      = array();
		$defaults = Config::strings();
		foreach ( is_array( $input ) ? $input : array() as $key => $text ) {
			if ( ! is_string( $key ) || ! isset( $defaults[ $key ] ) || ! is_scalar( $text ) ) {
				continue;
			}
			$text = mb_substr( trim( sanitize_text_field( (string) $text ) ), 0, self::TEXT_MAX );
			if ( '' !== $text && $text !== $defaults[ $key ] ) {
				$out[ $key ] = $text;
			}
		}
		return $out;
	}

	/**
	 * Add a settings notice unless it is already queued.
	 *
	 * @param string $code    Notice code.
	 * @param string $message Message.
	 */
	private static function notice( string $code, string $message ): void {
		if ( ! function_exists( 'add_settings_error' ) ) {
			return;
		}
		$queued = function_exists( 'get_settings_errors' ) ? array_column( (array) get_settings_errors( self::OPTION ), 'code' ) : array();
		if ( ! in_array( $code, $queued, true ) ) {
			add_settings_error( self::OPTION, $code, $message );
		}
	}
}
