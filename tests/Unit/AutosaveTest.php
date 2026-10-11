<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InstitutionalEmail\Admin\Autosave;
use InstitutionalEmail\Config;
use InstitutionalEmail\Settings;
use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * A toggle saves only the key it owns, and leaves the rest as it was.
 *
 * @covers \InstitutionalEmail\Admin\Autosave
 */
final class AutosaveTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $s ): string => trim( $s ) );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		require_once __DIR__ . '/../Stubs/wp-error.php';
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_forms_are_saved_and_the_rest_kept(): void {
		$settings           = Settings::defaults();
		$settings['domain'] = 'example.org';

		$saved = Autosave::apply( $settings, 'forms', array( 'teachers' ) );

		self::assertIsArray( $saved );
		self::assertSame( array( 'teachers' ), $saved['forms'] );
		self::assertSame( 'example.org', $saved['domain'] );
	}

	public function test_no_form_ticked_offers_both(): void {
		$saved = Autosave::apply( Settings::defaults(), 'forms', '' );
		self::assertIsArray( $saved );
		self::assertSame( Config::FORMS, $saved['forms'] );
	}

	public function test_a_key_that_is_not_a_toggle_is_refused(): void {
		foreach ( array( 'domain', 'password', '', 'forms.x' ) as $key ) {
			self::assertInstanceOf( WP_Error::class, Autosave::apply( Settings::defaults(), $key, '1' ), $key );
		}
	}
}
