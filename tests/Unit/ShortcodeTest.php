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
 */
final class ShortcodeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->alias( static fn( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'shortcode_atts' )->alias( static fn( array $pairs, array $atts ): array => array_merge( $pairs, array_intersect_key( $atts, $pairs ) ) );
		Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.test/wp-content/plugins/institutional-email/' );
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

	public function test_register_hooks_the_tag(): void {
		Functions\expect( 'add_shortcode' )->once()->with( 'institutional_email', array( Shortcode::class, 'render' ) );
		Shortcode::register();
	}

	public function test_render_prints_the_container_with_the_configuration_and_enqueues_the_assets(): void {
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'institutional-email', \Mockery::type( 'string' ), array(), \Mockery::type( 'string' ) );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'institutional-email', \Mockery::type( 'string' ), array(), \Mockery::type( 'string' ), array( 'in_footer' => true ) );

		$html = Shortcode::render( array( 'form' => 'teachers' ) );

		self::assertMatchesRegularExpression( '/^' . preg_quote( Plugin::VERSION, '/' ) . '\.\d+$/', Plugin::assetVersion( 'assets/js/institutional-email.js' ) );
		self::assertStringStartsWith( '<div class="ie-generator" data-ie-form="teachers" data-ie-config="', $html );
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
