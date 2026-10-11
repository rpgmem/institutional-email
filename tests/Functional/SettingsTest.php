<?php

declare(strict_types=1);

namespace IE\Tests\Functional;

use InstitutionalEmail\Admin\SettingsPage;
use InstitutionalEmail\Config;
use InstitutionalEmail\Settings;
use InstitutionalEmail\Shortcode;
use WP_UnitTestCase;

/**
 * The settings inside a real WordPress: saving through the Settings API,
 * applying to the form, access, and the screen itself.
 *
 * @covers \InstitutionalEmail\Settings
 * @covers \InstitutionalEmail\Admin\SettingsPage
 * @covers \InstitutionalEmail\Admin\Documentation
 * @covers \InstitutionalEmail\Shortcode::render
 */
final class SettingsTest extends WP_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		SettingsPage::registerSetting();
	}

	protected function tearDown(): void {
		delete_option( Settings::OPTION );
		wp_set_current_user( 0 );
		unset( $_GET['tab'] );
		parent::tearDown();
	}

	public function test_saving_goes_through_the_sanitizer(): void {
		update_option( Settings::OPTION, array( 'middle_min' => '99', 'domain' => '<b>x', 'access' => 'nope', 'platform_url' => 'javascript:alert(1)' ) );
		$stored = get_option( Settings::OPTION );

		self::assertSame( Settings::MIDDLE_MIN_MAX, $stored['middle_min'] );
		self::assertSame( Config::DOMAIN, $stored['domain'] );
		self::assertSame( Settings::ACCESS_EVERYONE, $stored['access'] );
		self::assertSame( '', $stored['platform_url'] );
	}

	public function test_an_install_that_never_saved_gets_the_defaults(): void {
		self::assertSame( Settings::defaults(), Settings::get() );
		self::assertSame( Config::defaults(), Settings::siteConfig() );
	}

	public function test_the_form_uses_the_stored_settings(): void {
		update_option( Settings::OPTION, array( 'domain' => 'example.org', 'strings' => array( 'submit' => 'Go' ) ) );

		$html = do_shortcode( '[institutional_email]' );
		self::assertSame( 1, preg_match( '/data-ie-config="([^"]*)"/', $html, $m ) );
		$config = json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
		self::assertSame( 'example.org', $config['domain'] );
		self::assertSame( 'Go', $config['strings']['submit'] );
	}

	public function test_logged_in_only_keeps_the_form_from_visitors(): void {
		update_option( Settings::OPTION, array( 'access' => Settings::ACCESS_LOGGED_IN ) );

		self::assertStringContainsString( 'ie-login-required', do_shortcode( '[institutional_email]' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		self::assertStringContainsString( 'data-ie-config', do_shortcode( '[institutional_email]' ) );
	}

	public function test_the_screen_is_for_administrators_only(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		self::assertSame( '', $this->renderScreen() );
	}

	public function test_the_screen_has_a_tab_per_section_and_boxed_headings_with_icons(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$html = $this->renderScreen();

		foreach ( array_keys( SettingsPage::tabs() ) as $id ) {
			self::assertStringContainsString( 'id="ie-tab-' . $id . '"', $html );
			self::assertStringContainsString( 'id="ie-panel-' . $id . '"', $html );
		}
		self::assertSame( 1, substr_count( $html, 'ie-tabs__tab is-active' ) );
		self::assertStringContainsString( 'class="ie-tabs__tab is-active" id="ie-tab-general"', $html );

		preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/s', $html, $headings );
		self::assertGreaterThanOrEqual( count( SettingsPage::tabs() ), count( $headings[1] ) );
		foreach ( $headings[1] as $heading ) {
			self::assertStringStartsWith( '<svg class="ie-icon"', $heading );
		}
		self::assertSame( count( $headings[1] ), preg_match_all( '/<div class="card[^"]*"><h2[ >]/', $html ) );
		self::assertSame( count( SettingsPage::tabs() ), preg_match_all( '/data-ie-tab="[a-z]+"><svg class="ie-icon"/', $html ) );

		// A text box for every string the form shows.
		self::assertSame( count( Config::strings() ), substr_count( $html, 'name="' . Settings::OPTION . '[strings][' ) );
		self::assertStringContainsString( 'value="[' . Shortcode::TAG . ']" readonly', $html );
	}

	public function test_the_tab_parameter_chooses_the_first_panel(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_GET['tab'] = 'texts';
		self::assertStringContainsString( 'class="ie-tabs__panel is-active" id="ie-panel-texts"', $this->renderScreen() );

		$_GET['tab'] = '<script>';
		self::assertStringContainsString( 'class="ie-tabs__panel is-active" id="ie-panel-general"', $this->renderScreen() );
	}

	public function test_the_documentation_tab_has_nothing_to_save(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_GET['tab'] = 'documentation';
		$html        = $this->renderScreen();
		self::assertStringContainsString( 'class="ie-tabs__panel is-active" id="ie-panel-documentation"', $html );
		self::assertStringContainsString( '<div class="ie-submit" hidden>', $html );
		self::assertStringContainsString( 'id="ie-doc-overview"', $html );
		self::assertStringContainsString( 'Alex Meusburger', $html );

		unset( $_GET['tab'] );
		self::assertStringContainsString( '<div class="ie-submit">', $this->renderScreen(), 'The other tabs keep Save Changes.' );
	}

	public function test_a_refused_domain_is_reported(): void {
		global $wp_settings_errors;
		$wp_settings_errors = array();

		update_option( Settings::OPTION, array( 'domain' => 'nope' ) );

		$errors = get_settings_errors( Settings::OPTION );
		self::assertSame( 'domain_rejected', $errors[0]['code'] ?? '' );
		self::assertSame( Config::DOMAIN, Settings::get()['domain'] );
	}

	public function test_the_assets_load_on_the_screen_only(): void {
		SettingsPage::enqueue( 'index.php' );
		self::assertFalse( wp_script_is( 'institutional-email-admin', 'enqueued' ) );

		SettingsPage::enqueue( 'settings_page_' . SettingsPage::SLUG );
		self::assertTrue( wp_script_is( 'institutional-email-admin', 'enqueued' ) );
		self::assertTrue( wp_style_is( 'institutional-email-admin', 'enqueued' ) );
	}

	private function renderScreen(): string {
		ob_start();
		SettingsPage::render();
		return (string) ob_get_clean();
	}
}
