<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InstitutionalEmail\Config;
use InstitutionalEmail\Plugin;
use InstitutionalEmail\Shortcode;
use PHPUnit\Framework\TestCase;

/**
 * The shortcode prints a container the script fills, and only ever one of
 * the known forms.
 *
 * @covers \InstitutionalEmail\Shortcode
 * @covers \InstitutionalEmail\Settings
 */
final class ShortcodeTest extends TestCase {

	/**
	 * What get_option() returns for the settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $stored = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->alias( static fn( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'shortcode_atts' )->alias( static fn( array $pairs, array $atts ): array => array_merge( $pairs, array_intersect_key( $atts, $pairs ) ) );
		Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.test/wp-content/plugins/institutional-email/' );
		Functions\when( 'get_option' )->alias( fn() => $this->stored );
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $s ): string => trim( strip_tags( $s ) ) );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'plugin_dir_path' )->justReturn( dirname( __DIR__, 2 ) . '/' );

		$file = new \ReflectionProperty( Plugin::class, 'file' );
		$file->setAccessible( true );
		$file->setValue( null, dirname( __DIR__, 2 ) . '/institutional-email.php' );
	}

	protected function tearDown(): void {
		// Mockery expectations are assertions too; PHPUnit does not count them.
		$this->addToAssertionCount( \Mockery::getContainer()->mockery_getExpectationCount() );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider forms
	 */
	public function test_form_accepts_only_a_known_form( string $asked, string $expected ): void {
		self::assertSame( $expected, Shortcode::form( $asked ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function forms(): array {
		return array(
			'students'        => array( 'students', 'students' ),
			'teachers'        => array( 'teachers', 'teachers' ),
			'case and spaces' => array( '  Teachers ', 'teachers' ),
			'unknown'         => array( 'staff', 'students' ),
			'empty'           => array( '', 'students' ),
		);
	}

	public function test_form_falls_back_to_the_first_offered_form(): void {
		self::assertSame( 'teachers', Shortcode::form( 'students', array( 'teachers' ) ) );
		self::assertSame( 'teachers', Shortcode::form( 'teachers', array( 'teachers' ) ) );
	}

	public function test_a_form_the_settings_do_not_offer_shows_the_offered_one(): void {
		$this->stored = array( 'forms' => array( 'teachers' ) );
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );

		self::assertStringContainsString( 'data-ie-form="teachers"', Shortcode::render( array( 'form' => 'students' ) ) );
	}

	public function test_the_settings_reach_the_configuration(): void {
		$this->stored = array( 'domain' => 'Example.ORG', 'middle_min' => 3, 'platform_url' => '' );
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );

		self::assertSame( 1, preg_match( '/data-ie-config="([^"]*)"/', Shortcode::render(), $m ) );
		$config = json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
		self::assertSame( 'example.org', $config['domain'] );
		self::assertSame( 3, $config['middleMin'] );
		self::assertSame( '', $config['platformUrl'] );
	}

	public function test_logged_in_only_asks_a_visitor_to_log_in_and_loads_nothing(): void {
		$this->stored = array( 'access' => 'logged_in' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/page/' );
		Functions\when( 'wp_login_url' )->alias( static fn( string $to ): string => 'https://example.test/wp-login.php?redirect_to=' . rawurlencode( $to ) );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\expect( 'wp_enqueue_script' )->never();

		$html = Shortcode::render();
		self::assertStringStartsWith( '<p class="ie-login-required">', $html );
		self::assertStringContainsString( 'wp-login.php?redirect_to=https%3A%2F%2Fexample.test%2Fpage%2F', $html );
		self::assertStringNotContainsString( 'data-ie-config', $html );
	}

	public function test_logged_in_only_shows_the_form_to_a_logged_in_user(): void {
		$this->stored = array( 'access' => 'logged_in' );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );

		self::assertStringContainsString( 'data-ie-config', Shortcode::render() );
	}

	public function test_network_takes_the_shortcode_then_the_site(): void {
		self::assertSame( 'state', Shortcode::network( 'state', 'city' ) );
		self::assertSame( 'city', Shortcode::network( ' City ', 'state' ) );
		self::assertSame( 'state', Shortcode::network( '', 'state' ) );
		self::assertSame( 'state', Shortcode::network( 'federal', 'state' ) );
		self::assertSame( 'city', Shortcode::network( '', 'nope' ) );
	}

	public function test_the_shortcode_can_serve_the_state_on_one_page(): void {
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );

		self::assertStringContainsString( 'data-ie-network="state"', Shortcode::render( array( 'network' => 'state' ) ) );
		$this->stored = array( 'network' => 'state' );
		self::assertStringContainsString( 'data-ie-network="state"', Shortcode::render() );
		self::assertStringContainsString( 'data-ie-network="city"', Shortcode::render( array( 'network' => 'city' ) ) );
	}

	public function test_register_hooks_the_tag(): void {
		Functions\expect( 'add_shortcode' )->once()->with( 'institutional_email', array( Shortcode::class, 'render' ) );
		Shortcode::register();
	}

	public function test_render_prints_the_container_with_the_configuration_and_enqueues_the_assets(): void {
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'institutional-email', \Mockery::type( 'string' ), array(), \Mockery::type( 'string' ) );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'institutional-email', \Mockery::type( 'string' ), array(), \Mockery::type( 'string' ), array( 'in_footer' => true ) );

		$html = Shortcode::render( array( 'form' => 'teachers' ) );

		self::assertMatchesRegularExpression( '/^' . preg_quote( Plugin::VERSION, '/' ) . '\.\d+$/', Plugin::assetVersion( 'assets/js/institutional-email.js' ) );
		self::assertStringStartsWith( '<div class="ie-generator" data-ie-form="teachers" data-ie-network="city" style="--ie-form-width: 640px" data-ie-config="', $html );
		self::assertSame( 1, preg_match( '/data-ie-config="([^"]*)"/', $html, $m ) );
		self::assertSame( Config::defaults(), json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true ) );
		self::assertStringContainsString( '<noscript>This form needs JavaScript.</noscript>', $html );
	}

	public function test_render_without_attributes_shows_the_students_form(): void {
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );

		self::assertStringContainsString( 'data-ie-form="students"', Shortcode::render( '' ) );
	}
}
