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
 * Where Free Form Certificate is active, its `dark_mode` decides the theme;
 * without it, this plugin's own setting does, as before (#89).
 *
 * `FFC_VERSION` cannot be undefined once a test defines it, so every test
 * that needs that plugin active runs in a process of its own.
 *
 * @covers \InstitutionalEmail\Theme
 * @covers \InstitutionalEmail\Settings
 * @covers \InstitutionalEmail\Admin\Autosave
 */
final class FfcDarkModeTest extends TestCase {

	/**
	 * Option name => what get_option() returns.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $s ): string => trim( strip_tags( (string) $s ) ) );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		require_once __DIR__ . '/../Stubs/wp-error.php';
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_without_ffc_the_local_setting_decides_as_before(): void {
		self::assertFalse( defined( 'FFC_VERSION' ), 'This test must run where Free Form Certificate is absent.' );
		$this->options = array(
			Settings::OPTION  => array( 'dark_mode' => 'on' ),
			Theme::FFC_OPTION => array( 'dark_mode' => 'off' ),
		);

		self::assertFalse( Theme::ffcActive() );
		self::assertNull( Theme::ffcMode(), 'An option left behind by a deactivated FFC is not read.' );
		self::assertSame( 'on', Theme::mode() );

		$saved = Autosave::apply( Settings::defaults(), 'dark_mode', 'auto' );
		self::assertIsArray( $saved );
		self::assertSame( 'auto', $saved['dark_mode'], 'The local field still saves on its own.' );
	}

	public function test_without_ffc_the_switch_follows_a_save(): void {
		$this->options = array( Settings::OPTION => array( 'dark_mode' => 'auto' ) );
		$script        = '';
		Functions\when( 'wp_script_is' )->justReturn( false );
		Functions\when( 'wp_register_script' )->justReturn( true );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );
		Functions\when( 'wp_add_inline_script' )->alias(
			static function ( string $handle, string $js ) use ( &$script ): bool {
				$script = $js;
				return true;
			}
		);

		Theme::enqueue( false );

		self::assertStringEndsWith( '})("auto");', $script );
		self::assertStringContainsString( 'ie:setting-saved', $script );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_with_ffc_its_setting_decides_and_the_switch_ignores_local_saves(): void {
		define( 'FFC_VERSION', '6.41.0' );
		$this->options = array(
			Settings::OPTION  => array( 'dark_mode' => 'off' ),
			Theme::FFC_OPTION => array( 'dark_mode' => 'on' ),
		);
		$script        = '';
		Functions\when( 'wp_script_is' )->justReturn( false );
		Functions\when( 'wp_register_script' )->justReturn( true );
		Functions\when( 'wp_enqueue_script' )->justReturn( null );
		Functions\when( 'wp_add_inline_script' )->alias(
			static function ( string $handle, string $js ) use ( &$script ): bool {
				$script = $js;
				return true;
			}
		);

		self::assertTrue( Theme::ffcActive() );
		self::assertSame( 'on', Theme::ffcMode() );
		self::assertSame( 'on', Theme::mode(), 'FFC\'s value wins over the local one.' );

		Theme::enqueue( true );
		self::assertStringEndsWith( '})("on");', $script );
		self::assertStringNotContainsString( 'ie:setting-saved', $script, 'The disabled local field cannot repaint the page.' );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_with_ffc_an_unknown_or_missing_value_reads_as_off(): void {
		define( 'FFC_VERSION', '6.41.0' );
		$this->options = array( Settings::OPTION => array( 'dark_mode' => 'on' ) );
		self::assertSame( 'off', Theme::ffcMode(), 'No FFC option: that plugin\'s own default.' );

		$this->options[ Theme::FFC_OPTION ] = array( 'dark_mode' => '"); alert(1); ("' );
		self::assertSame( 'off', Theme::ffcMode() );

		$this->options[ Theme::FFC_OPTION ] = 'garbage';
		self::assertSame( 'off', Theme::ffcMode() );

		$this->options[ Theme::FFC_OPTION ] = array( 'dark_mode' => 'auto' );
		self::assertSame( 'auto', Theme::mode() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_with_ffc_the_local_field_does_not_autosave(): void {
		define( 'FFC_VERSION', '6.41.0' );

		$refused = Autosave::apply( Settings::defaults(), 'dark_mode', 'on' );
		self::assertInstanceOf( WP_Error::class, $refused );
		self::assertSame( 'controlled_by_ffc', $refused->get_error_code() );

		$saved = Autosave::apply( Settings::defaults(), 'analytics', '1' );
		self::assertIsArray( $saved, 'The other toggles still save.' );
	}

	/**
	 * The disabled field posts nothing: Save Changes keeps the stored choice
	 * instead of reading the absence as "off".
	 */
	public function test_save_keeps_the_stored_mode_when_the_field_posts_nothing(): void {
		Functions\when( 'get_settings_errors' )->justReturn( array() );
		Functions\when( 'add_settings_error' )->justReturn( null );

		$this->options = array( Settings::OPTION => array( 'dark_mode' => 'auto' ) );
		self::assertSame( 'auto', Settings::save( array( 'domain' => 'example.org' ) )['dark_mode'] );

		$this->options = array();
		self::assertSame( 'off', Settings::save( array( 'domain' => 'example.org' ) )['dark_mode'], 'Nothing stored: the default.' );

		$this->options = array( Settings::OPTION => array( 'dark_mode' => 'auto' ) );
		self::assertSame( 'on', Settings::save( array( 'dark_mode' => 'on' ) )['dark_mode'], 'A posted value is still saved.' );
	}
}
