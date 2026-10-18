<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use InstitutionalEmail\Icons;
use InstitutionalEmail\Settings;
use PHPUnit\Framework\TestCase;

/**
 * The stylesheets behind the buttons and icons (#89): the generated icon
 * sheet, the family's tokens, their contrast in both themes, and no emoji
 * or dashicon left in the plugin's own markup.
 *
 * @covers \InstitutionalEmail\Icons
 */
final class ButtonStylesTest extends TestCase {

	/**
	 * The palettes the family is drawn in: sheet => [light block, dark block].
	 */
	private const PALETTES = array(
		'admin-settings.css'      => array( ':root {', ':root.ie-dark-mode {' ),
		'institutional-email.css' => array( '.ie-generator {', ':root.ie-dark-mode .ie-generator {' ),
	);

	public function test_the_icon_sheet_is_what_the_registry_writes(): void {
		$path = dirname( __DIR__, 2 ) . '/assets/css/icons.css';
		if ( getenv( 'IE_UPDATE_ICON_CSS' ) ) {
			file_put_contents( $path, Icons::stylesheet() );
		}
		self::assertSame( Icons::stylesheet(), (string) file_get_contents( $path ), 'Regenerate with IE_UPDATE_ICON_CSS=1 vendor/bin/phpunit --filter ButtonStylesTest.' );
		foreach ( Icons::MASKED as $name ) {
			self::assertStringContainsString( '.ie-icon-' . $name . '::before {', Icons::stylesheet() );
		}
	}

	public function test_the_script_knows_the_same_icons_as_the_registry(): void {
		$js = (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/js/institutional-email.js' );
		self::assertSame( 1, preg_match( '/var BUTTON_ICONS = \[([^\]]*)\];/', $js, $m ) );
		preg_match_all( "/'([a-z-]+)'/", $m[1], $names );
		self::assertSame( Icons::MASKED, $names[1] );
	}

	/**
	 * Every icon class the markup and the scripts name is one the sheet
	 * draws; a class it does not know renders as nothing.
	 */
	public function test_every_icon_class_used_is_drawn(): void {
		$used = array();
		foreach ( self::sources() as $file ) {
			$source = (string) file_get_contents( $file );
			// PHP: the icon inside a Button call; the tab icons are inline SVG.
			preg_match_all( '/Button::(?:html|render)\((.*?)\n\t*\)/s', $source, $calls );
			foreach ( $calls[1] as $call ) {
				preg_match_all( "/'icon'\s*=>\s*'([a-z-]+)'/", $call, $m );
				$used = array_merge( $used, $m[1] );
			}
			// JS: the form's button() and the viewer's controls.
			preg_match_all( "/icon:\s*'([a-z-]+)'|button\(\s*'[a-z-]+',\s*labels\.[a-zA-Z]+,\s*'([a-z-]+)'\s*\)/", $source, $m );
			$used = array_merge( $used, array_filter( array_merge( $m[1], $m[2] ) ) );
		}
		// Recounted by hand: the screen's Save Changes and Copy; the form's
		// submit, SED link and two copies; the viewer's five controls.
		self::assertCount( 11, $used, 'The scan lost a button; fix it before trusting the check below.' );
		foreach ( $used as $name ) {
			self::assertTrue( Icons::masked( $name ), "{$name} has no .ie-icon-{$name} rule." );
		}
	}

	public function test_no_emoji_or_dashicon_is_left_in_the_plugins_own_markup(): void {
		$files = self::sources();
		self::assertGreaterThanOrEqual( 15, count( $files ) );
		foreach ( $files as $file ) {
			$source = (string) file_get_contents( $file );
			self::assertStringNotContainsString( 'dashicons', $source, $file );
			self::assertSame( 0, preg_match( '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}]/u', $source ), "{$file} carries an emoji." );
		}
	}

	/**
	 * The family declares no colour or size of its own: it reads the
	 * `--ie-btn-*` tokens, and both palettes map every one of them, or the
	 * whole declaration would drop.
	 */
	public function test_both_palettes_map_every_token_the_family_reads(): void {
		$css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/css/buttons.css' );
		preg_match_all( '/var\(--([a-z0-9-]+)\)/', $css, $read );
		$read = array_values( array_unique( $read[1] ) );
		self::assertNotEmpty( $read );
		foreach ( $read as $token ) {
			self::assertStringStartsWith( 'ie-btn-', $token );
		}
		self::assertSame( 0, preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', (string) preg_replace( '#/\*.*?\*/#s', '', $css ) ), 'A colour literal in the family.' );
		foreach ( self::PALETTES as $sheet => $blocks ) {
			$light = self::declarations( $sheet, $blocks[0] );
			foreach ( $read as $token ) {
				self::assertArrayHasKey( $token, $light, "{$sheet} does not map --{$token}." );
			}
		}
	}

	public function test_the_copy_buttons_text_colour_is_the_forms(): void {
		self::assertSame( \InstitutionalEmail\Config::TEXT_COLOR, self::declarations( 'institutional-email.css', '.ie-generator {' )['ie-text'], 'Settings::weakContrasts() measures the copy buttons with it.' );
	}

	/**
	 * Each category's text on its ground, at rest and on hover, in both
	 * themes of both palettes: the dark block merged over the light one,
	 * as the browser does, and every var() resolved.
	 *
	 * @dataProvider looks
	 *
	 * @param string $sheet Palette's sheet.
	 * @param bool   $dark  Whether to measure the dark look.
	 */
	public function test_the_categories_read_well( string $sheet, bool $dark ): void {
		$blocks = self::PALETTES[ $sheet ];
		$tokens = self::declarations( $sheet, $blocks[0] );
		if ( $dark ) {
			$tokens = array_merge( $tokens, self::declarations( $sheet, $blocks[1] ) );
		}
		$pairs = array(
			array( 'ie-btn-primary-text', 'ie-btn-primary-bg' ),
			array( 'ie-btn-primary-text', 'ie-btn-primary-hover' ),
			array( 'ie-btn-neutral-text', 'ie-btn-neutral-bg' ),
			array( 'ie-btn-disabled-text', 'ie-btn-disabled-bg' ),
		);
		foreach ( $pairs as $pair ) {
			$front = self::resolve( $pair[0], $tokens );
			$back  = self::resolve( $pair[1], $tokens );
			$ratio = Settings::contrast( $front, $back );
			self::assertGreaterThanOrEqual( 4.5, $ratio, sprintf( '%s: %s on %s, %.2f:1', $sheet, $pair[0], $pair[1], $ratio ) );
		}
	}

	/**
	 * The four looks.
	 *
	 * @return array<string, array{string, bool}>
	 */
	public static function looks(): array {
		return array(
			'admin light' => array( 'admin-settings.css', false ),
			'admin dark'  => array( 'admin-settings.css', true ),
			'form light'  => array( 'institutional-email.css', false ),
			'form dark'   => array( 'institutional-email.css', true ),
		);
	}

	/**
	 * A token's #rrggbb value, following var() references.
	 *
	 * @param string                $token  Token name, without the dashes.
	 * @param array<string, string> $tokens Declarations.
	 */
	private static function resolve( string $token, array $tokens ): string {
		for ( $hops = 0; $hops < 5; $hops++ ) {
			self::assertArrayHasKey( $token, $tokens );
			$value = $tokens[ $token ];
			if ( 1 === preg_match( '/^#[0-9a-f]{6}$/', $value ) ) {
				return $value;
			}
			self::assertSame( 1, preg_match( '/^var\(--([a-z0-9-]+)\)$/', $value, $m ), "--{$token}: {$value}" );
			$token = $m[1];
		}
		self::fail( "--{$token} does not resolve." );
	}

	/**
	 * Token => value declared in the block that opens with $selector.
	 *
	 * @param string $sheet    Sheet under assets/css.
	 * @param string $selector Opening of the block.
	 * @return array<string, string>
	 */
	private static function declarations( string $sheet, string $selector ): array {
		$css   = (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/css/' . $sheet );
		$start = strpos( $css, $selector );
		self::assertNotFalse( $start, $selector );
		$block = substr( $css, $start, (int) strpos( $css, "\n}", $start ) - $start );
		preg_match_all( '/--(ie-[a-z0-9-]+):\s*([^;]+);/', $block, $m );
		return array_combine( $m[1], array_map( 'trim', $m[2] ) );
	}

	/**
	 * The plugin's own PHP and the readable scripts.
	 *
	 * @return list<string>
	 */
	private static function sources(): array {
		$root  = dirname( __DIR__, 2 );
		$files = array_merge(
			glob( $root . '/src/*.php' ) ?: array(),
			glob( $root . '/src/*/*.php' ) ?: array(),
			array_filter( glob( $root . '/assets/js/*.js' ) ?: array(), static fn( string $f ): bool => ! str_ends_with( $f, '.min.js' ) )
		);
		sort( $files );
		return $files;
	}
}
