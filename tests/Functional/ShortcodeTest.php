<?php

declare(strict_types=1);

namespace IE\Tests\Functional;

use InstitutionalEmail\Config;
use InstitutionalEmail\Settings;
use InstitutionalEmail\Shortcode;
use InstitutionalEmail\Theme;
use WP_UnitTestCase;

/**
 * The shortcode inside a real WordPress: registered on init, rendered
 * through do_shortcode, and loading its assets only where it is used.
 *
 * @covers \InstitutionalEmail\Shortcode
 * @covers \InstitutionalEmail\Theme
 */
final class ShortcodeTest extends WP_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		// Enqueues from earlier tests outlive them.
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
	}

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

	public function test_a_custom_design_reaches_the_stylesheet_once_and_in_the_light_look(): void {
		update_option( Settings::OPTION, array( 'custom_style' => true, 'colors' => array( 'accent' => '#aa0000' ) ) );

		self::assertStringNotContainsString( 'style=', do_shortcode( '[institutional_email]' ), 'The colours left the style attribute, where the dark block could not override them.' );
		do_shortcode( '[institutional_email]' );

		$after = wp_styles()->get_data( Shortcode::HANDLE, 'after' );
		self::assertIsArray( $after );
		self::assertCount( 1, $after, 'Two forms on a page add the rule once.' );
		self::assertStringContainsString( ':root:not(.ie-dark-mode) .ie-generator { --ie-accent: #aa0000;', $after[0] );
	}

	public function test_the_dark_mode_switch_loads_with_the_form_and_runs_before_it(): void {
		update_option( Settings::OPTION, array( 'dark_mode' => 'on' ) );

		do_shortcode( '[institutional_email]' );

		self::assertTrue( wp_script_is( Theme::HANDLE, 'enqueued' ) );
		self::assertContains( Theme::HANDLE, wp_scripts()->registered[ Shortcode::HANDLE ]->deps );
		self::assertStringEndsWith( '})("on");', implode( '', (array) wp_scripts()->get_data( Theme::HANDLE, 'after' ) ) );
	}
}
