<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InstitutionalEmail\Admin\Autosave;
use InstitutionalEmail\Settings;
use InstitutionalEmail\Theme;
use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * Dark mode for the settings screen: the setting, its autosave, the head
 * script that applies it, and the palette that makes it dark.
 *
 * @covers \InstitutionalEmail\Settings
 * @covers \InstitutionalEmail\Admin\Autosave
 * @covers \InstitutionalEmail\Theme
 */
final class DarkModeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $s ): string => trim( strip_tags( (string) $s ) ) );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( static fn( $v ): string => (string) json_encode( $v ) );
		if ( ! class_exists( WP_Error::class ) ) {
			require __DIR__ . '/../Stubs/wp-error.php';
		}
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_the_setting_starts_off_and_keeps_only_known_values(): void {
		self::assertSame( 'off', Settings::defaults()['dark_mode'] );
		foreach ( Settings::DARK_MODES as $mode ) {
			self::assertSame( $mode, Settings::sanitize( array( 'dark_mode' => $mode ) )['dark_mode'] );
		}
		self::assertSame( 'off', Settings::sanitize( array( 'dark_mode' => 'dim' ) )['dark_mode'] );
		self::assertSame( 'off', Settings::sanitize( array( 'dark_mode' => array( 'on' ) ) )['dark_mode'] );
		self::assertSame( 'off', Settings::sanitize( array() )['dark_mode'] );
	}

	public function test_it_saves_on_its_own_and_refuses_an_unknown_value(): void {
		$on = Autosave::apply( Settings::defaults(), 'dark_mode', 'auto' );
		self::assertIsArray( $on );
		self::assertSame( 'auto', $on['dark_mode'] );

		$refused = Autosave::apply( Settings::defaults(), 'dark_mode', 'dim' );
		self::assertInstanceOf( WP_Error::class, $refused );
		self::assertSame( 'unknown_value', $refused->get_error_code() );
	}

	public function test_the_head_script_applies_the_mode_and_follows_a_save(): void {
		$script = Theme::script( 'auto' );
		self::assertStringEndsWith( '})("auto");', $script );
		self::assertStringContainsString( 'classList.toggle("ie-dark-mode",', $script );
		self::assertStringContainsString( 'prefers-color-scheme: dark', $script );
		self::assertStringContainsString( 'addEventListener("ie:setting-saved"', $script );

		self::assertStringEndsWith( '})("off");', Theme::script( '"); alert(1); ("' ), 'An unknown value is never printed.' );
	}

	/**
	 * The dark block only redefines tokens the light palette declares, and
	 * every token the sheet reads is declared: an undeclared custom property
	 * drops the whole declaration, so the element would lose its colour.
	 */
	public function test_the_dark_palette_redefines_declared_tokens_only(): void {
		$css   = (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/css/admin-settings.css' );
		$light = self::tokens( $css, ':root {' );
		$dark  = self::tokens( $css, ':root.ie-dark-mode {' );

		self::assertNotEmpty( $dark );
		self::assertSame( array(), array_values( array_diff( $dark, $light ) ), 'A dark token with no light value.' );

		preg_match_all( '/var\(--(ie-[a-z0-9-]+)\)/', $css, $used );
		self::assertNotEmpty( $used[1] );
		self::assertSame( array(), array_values( array_diff( array_unique( $used[1] ), $light ) ), 'A token read but never declared.' );
	}

	/**
	 * The form's dark block redefines colour tokens the light block declares,
	 * and nothing else. Nothing in the sheet keys on the operating system.
	 */
	public function test_the_form_dark_palette_redefines_declared_tokens_only(): void {
		$css   = self::formCss();
		$light = self::tokens( $css, '.ie-generator {' );
		$dark  = self::tokens( $css, ':root.ie-dark-mode .ie-generator {' );

		self::assertSame( array(), array_values( array_diff( $dark, $light ) ) );
		self::assertStringNotContainsString( 'prefers-color-scheme', $css, 'The setting decides, not the operating system.' );
	}

	/**
	 * Every pair the form draws text with reads at 4.5:1 or more, in both
	 * looks: the dark block merged over the light one, as the browser does.
	 *
	 * @dataProvider looks
	 *
	 * @param bool $dark Whether to measure the dark look.
	 */
	public function test_the_form_pairs_read_well( bool $dark ): void {
		$light = self::values( self::formCss(), '.ie-generator {' );
		$dark  = $dark ? array_merge( $light, self::values( self::formCss(), ':root.ie-dark-mode .ie-generator {' ) ) : $light;
		$pairs = array(
			array( 'ie-text', 'ie-surface' ),
			array( 'ie-muted', 'ie-surface' ),
			array( 'ie-accent', 'ie-surface' ),
			array( 'ie-error', 'ie-surface' ),
			array( 'ie-accent-text', 'ie-accent' ),
			array( 'ie-text', 'ie-ok-bg' ),
			array( 'ie-accent', 'ie-ok-bg' ),
			array( 'ie-text', 'ie-value-bg' ),
			array( 'ie-text', 'ie-field-bg' ),
			array( 'ie-placeholder', 'ie-field-bg' ),
			array( 'ie-muted', 'ie-value-bg' ),
		);
		foreach ( $pairs as $pair ) {
			$ratio = Settings::contrast( $dark[ $pair[0] ], $dark[ $pair[1] ] );
			self::assertGreaterThanOrEqual( 4.5, $ratio, sprintf( '%s on %s: %.2f:1', $pair[0], $pair[1], $ratio ) );
		}
	}

	/**
	 * The two looks.
	 *
	 * @return array<string, array{bool}>
	 */
	public static function looks(): array {
		return array(
			'light' => array( false ),
			'dark'  => array( true ),
		);
	}

	public function test_the_script_names_the_class_the_stylesheets_read(): void {
		self::assertStringContainsString( '.' . Theme::CSS_CLASS . ' .ie-generator', self::formCss() );
		self::assertStringContainsString( '"' . Theme::CSS_CLASS . '"', Theme::script( 'on' ) );
	}

	/**
	 * The form's stylesheet.
	 */
	private static function formCss(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/css/institutional-email.css' );
	}

	/**
	 * Token => value declared in the block that opens with $selector.
	 *
	 * @param string $css      Stylesheet.
	 * @param string $selector Opening of the block.
	 * @return array<string, string>
	 */
	private static function values( string $css, string $selector ): array {
		$start = strpos( $css, $selector );
		self::assertNotFalse( $start, $selector );
		$block = substr( $css, $start, (int) strpos( $css, "\n}", $start ) - $start );
		preg_match_all( '/--(ie-[a-z0-9-]+):\s*(#[0-9a-f]{6});/', $block, $m );
		return array_combine( $m[1], $m[2] );
	}

	/**
	 * Token names declared in the block that opens with $selector.
	 *
	 * @param string $css      Stylesheet.
	 * @param string $selector Opening of the block.
	 * @return list<string>
	 */
	private static function tokens( string $css, string $selector ): array {
		$start = strpos( $css, $selector );
		self::assertNotFalse( $start, $selector );
		$block = substr( $css, $start, (int) strpos( $css, "\n}", $start ) - $start );
		preg_match_all( '/--(ie-[a-z0-9-]+):/', $block, $m );
		return $m[1];
	}
}
