<?php

declare(strict_types=1);

namespace IE\Tests\Functional;

use InstitutionalEmail\Config;
use InstitutionalEmail\Shortcode;
use WP_UnitTestCase;

/**
 * The shortcode inside a real WordPress: registered on init, rendered
 * through do_shortcode, and loading its assets only where it is used.
 *
 * @covers \InstitutionalEmail\Shortcode
 */
final class ShortcodeTest extends WP_UnitTestCase {

	public function test_the_shortcode_is_registered(): void {
		self::assertTrue( shortcode_exists( Shortcode::TAG ) );
	}

	public function test_a_page_shows_the_teachers_form_and_loads_the_assets(): void {
		self::assertFalse( wp_script_is( Shortcode::HANDLE, 'enqueued' ) );

		$html = do_shortcode( '[institutional_email form="teachers"]' );

		self::assertStringContainsString( 'data-ie-form="teachers"', $html );
		self::assertSame( 1, preg_match( '/data-ie-config="([^"]*)"/', $html, $m ) );
		self::assertSame( Config::defaults(), json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true ) );
		self::assertTrue( wp_script_is( Shortcode::HANDLE, 'enqueued' ) );
		self::assertTrue( wp_style_is( Shortcode::HANDLE, 'enqueued' ) );
	}

	public function test_an_unknown_form_falls_back_to_the_students_form(): void {
		self::assertStringContainsString( 'data-ie-form="students"', do_shortcode( '[institutional_email form="<x>"]' ) );
	}
}
