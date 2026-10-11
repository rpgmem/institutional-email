<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InstitutionalEmail\Config;
use InstitutionalEmail\Settings;
use PHPUnit\Framework\TestCase;

/**
 * Stored settings are cleaned on every read and every save, so an odd value
 * can never reach the form.
 *
 * @covers \InstitutionalEmail\Settings
 */
final class SettingsTest extends TestCase {

	/**
	 * Notices added through add_settings_error().
	 *
	 * @var list<array{string, string}>
	 */
	private array $notices = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $s ): string => trim( (string) preg_replace( '/\s+/', ' ', strip_tags( $s ) ) ) );
		Functions\when( 'esc_url_raw' )->alias(
			static fn( string $url, array $protocols ): string => in_array( (string) wp_parse_url_scheme( $url ), $protocols, true ) ? $url : ''
		);
		Functions\when( 'get_settings_errors' )->alias( fn(): array => array_map( static fn( array $n ): array => array( 'code' => $n[0] ), $this->notices ) );
		Functions\when( 'add_settings_error' )->alias(
			function ( string $setting, string $code, string $message ): void {
				$this->notices[] = array( $code, $message );
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_nothing_stored_is_the_defaults(): void {
		self::assertSame( Settings::defaults(), Settings::sanitize( array() ) );
		self::assertSame( Settings::defaults(), Settings::sanitize( 'garbage' ) );
	}

	public function test_the_defaults_change_nothing_in_the_configuration(): void {
		self::assertSame( Config::defaults(), Settings::apply( Config::defaults(), Settings::defaults() ) );
	}

	public function test_teacher_options_keep_the_known_ones_in_their_own_order(): void {
		self::assertSame( array( 'partner', 'intern' ), Settings::sanitize( array( 'teacher_networks' => array( 'intern', 'x', 'partner' ) ) )['teacher_networks'] );
		self::assertSame( Config::TEACHER_NETWORKS, Settings::sanitize( array( 'teacher_networks' => array() ) )['teacher_networks'], 'None ticked offers all three.' );
		self::assertSame( Config::TEACHER_NETWORKS, Settings::sanitize( array() )['teacher_networks'], 'Never saved offers all three.' );
		self::assertSame( array( 'direct' ), Settings::apply( Config::defaults(), Settings::sanitize( array( 'teacher_networks' => array( 'direct' ) ) ) )['teacherNetworks'] );
	}

	public function test_forms_keep_the_known_ones_in_their_own_order(): void {
		self::assertSame( array( 'students', 'teachers' ), Settings::sanitize( array( 'forms' => array( 'teachers', 'x', 'students' ) ) )['forms'] );
		self::assertSame( array( 'teachers' ), Settings::sanitize( array( 'forms' => array( 'teachers' ) ) )['forms'] );
		self::assertSame( Config::FORMS, Settings::sanitize( array( 'forms' => array() ) )['forms'], 'None ticked offers both.' );
	}

	/**
	 * @dataProvider domains
	 */
	public function test_the_domain_is_a_domain_name_or_the_default( string $typed, string $stored ): void {
		self::assertSame( $stored, Settings::sanitize( array( 'domain' => $typed ) )['domain'] );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function domains(): array {
		return array(
			'lower-cased'   => array( ' Escola.Example.ORG ', 'escola.example.org' ),
			'leading @'     => array( '@example.org', 'example.org' ),
			'no dot'        => array( 'localhost', Config::DOMAIN ),
			'with a path'   => array( 'example.org/x', Config::DOMAIN ),
			'markup'        => array( '<b>example.org', Config::DOMAIN ),
			'empty'         => array( '', Config::DOMAIN ),
		);
	}

	public function test_the_network_is_city_or_state(): void {
		self::assertSame( 'state', Settings::sanitize( array( 'network' => 'state' ) )['network'] );
		self::assertSame( 'city', Settings::sanitize( array( 'network' => 'federal' ) )['network'] );
		self::assertSame( 'city', Settings::sanitize( array() )['network'] );
		self::assertSame( 'state', Settings::apply( Config::defaults(), Settings::sanitize( array( 'network' => 'state' ) ) )['network'] );
	}

	public function test_the_rf_check_digit_is_on_unless_turned_off(): void {
		self::assertTrue( Settings::sanitize( array() )['check_rf_digit'], 'Never saved is on.' );
		self::assertFalse( Settings::sanitize( array( 'check_rf_digit' => '0' ) )['check_rf_digit'] );
		self::assertTrue( Settings::sanitize( array( 'check_rf_digit' => '1' ) )['check_rf_digit'] );
		self::assertFalse( Settings::apply( Config::defaults(), Settings::sanitize( array( 'check_rf_digit' => '0' ) ) )['checkRfDigit'] );
	}

	public function test_numbers_are_brought_into_range(): void {
		self::assertSame( Settings::MIDDLE_MIN_MIN, Settings::sanitize( array( 'middle_min' => '0' ) )['middle_min'] );
		self::assertSame( Settings::MIDDLE_MIN_MAX, Settings::sanitize( array( 'middle_min' => '99' ) )['middle_min'] );
		self::assertSame( 3, Settings::sanitize( array( 'middle_min' => '3' ) )['middle_min'] );
	}

	public function test_the_form_width_stays_in_range_and_a_cleared_field_keeps_the_default(): void {
		self::assertSame( Config::FORM_WIDTH, Settings::sanitize( array() )['form_width'] );
		self::assertSame( Config::FORM_WIDTH, Settings::sanitize( array( 'form_width' => '' ) )['form_width'], 'A cleared field is not zero pixels.' );
		self::assertSame( Settings::FORM_WIDTH_MIN, Settings::sanitize( array( 'form_width' => '10' ) )['form_width'] );
		self::assertSame( Settings::FORM_WIDTH_MAX, Settings::sanitize( array( 'form_width' => '99999' ) )['form_width'] );
		self::assertSame( 800, Settings::sanitize( array( 'form_width' => '800' ) )['form_width'] );
	}

	public function test_the_password_is_plain_text_bounded_and_never_empty(): void {
		self::assertSame( 'abc 123', Settings::sanitize( array( 'password' => ' <i>abc</i> 123 ' ) )['password'] );
		self::assertSame( Config::PASSWORD, Settings::sanitize( array( 'password' => '   ' ) )['password'] );
		self::assertSame( Settings::PASSWORD_MAX, mb_strlen( Settings::sanitize( array( 'password' => str_repeat( 'x', 200 ) ) )['password'] ) );
	}

	public function test_the_platform_address_is_http_or_empty(): void {
		self::assertSame( 'https://example.org/x', Settings::sanitize( array( 'platform_url' => ' https://example.org/x ' ) )['platform_url'] );
		self::assertSame( '', Settings::sanitize( array( 'platform_url' => 'javascript:alert(1)' ) )['platform_url'] );
		self::assertSame( '', Settings::sanitize( array( 'platform_url' => '' ) )['platform_url'], 'Emptied is a choice.' );
		self::assertSame( Config::PLATFORM_URL, Settings::sanitize( array() )['platform_url'], 'Never saved is the default.' );
	}

	public function test_texts_keep_only_real_changes_to_known_keys(): void {
		$strings = Config::strings();
		$clean   = Settings::sanitize(
			array(
				'strings' => array(
					'submit'  => ' <b>Go</b> ',
					'ready'   => $strings['ready'],
					'privacy' => '',
					'unknown' => 'x',
					7         => 'x',
					'rf'      => array( 'x' ),
				),
			)
		)['strings'];

		self::assertSame( array( 'submit' => 'Go' ), $clean );
		self::assertSame( Settings::TEXT_MAX, mb_strlen( Settings::sanitize( array( 'strings' => array( 'submit' => str_repeat( 'x', 999 ) ) ) )['strings']['submit'] ) );
	}

	public function test_apply_writes_the_settings_into_the_configuration(): void {
		$settings = Settings::sanitize(
			array(
				'domain'        => 'example.org',
				'password'      => 'secret',
				'middle_min'    => 2,
				'platform_name' => 'Moodle',
				'platform_url'  => 'https://moodle.example.org/',
				'strings'       => array( 'submit' => 'Go' ),
			)
		);
		$config   = Settings::apply( Config::defaults(), $settings );

		self::assertSame( 'example.org', $config['domain'] );
		self::assertSame( 'secret', $config['password'] );
		self::assertSame( 2, $config['middleMin'] );
		self::assertSame( 'Moodle', $config['platformName'] );
		self::assertSame( 'https://moodle.example.org/', $config['platformUrl'] );
		self::assertSame( 'Go', $config['strings']['submit'] );
		self::assertSame( Config::strings()['ready'], $config['strings']['ready'] );
	}

	public function test_save_reports_a_refused_domain_and_address_once(): void {
		$input = array( 'domain' => 'not a domain', 'platform_url' => 'ftp://x.example.org' );
		$saved = Settings::save( $input );
		Settings::save( $input );

		self::assertSame( Config::DOMAIN, $saved['domain'] );
		self::assertSame( '', $saved['platform_url'] );
		self::assertSame( array( 'domain_rejected', 'url_rejected' ), array_column( $this->notices, 0 ) );
		self::assertStringContainsString( 'not a domain', $this->notices[0][1] );
	}

	public function test_save_is_quiet_for_good_values(): void {
		Settings::save( array( 'domain' => 'example.org', 'platform_url' => 'https://example.org' ) );
		self::assertSame( array(), $this->notices );
	}
}

/**
 * The scheme of an address, for the esc_url_raw() stub.
 *
 * @param string $url Address.
 */
function wp_parse_url_scheme( string $url ): string {
	return strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) );
}
