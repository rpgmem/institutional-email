<?php

declare(strict_types=1);

namespace IE\Tests\Functional;

use InstitutionalEmail\Admin\SettingsPage;
use InstitutionalEmail\Config;
use InstitutionalEmail\Plugin;
use InstitutionalEmail\Settings;
use InstitutionalEmail\Shortcode;
use InstitutionalEmail\Theme;
use WP_UnitTestCase;

/**
 * The settings inside a real WordPress: saving through the Settings API,
 * applying to the form, access, and the screen itself.
 *
 * @covers \InstitutionalEmail\Settings
 * @covers \InstitutionalEmail\Admin\SettingsPage
 * @covers \InstitutionalEmail\Admin\Documentation
 * @covers \InstitutionalEmail\Shortcode::render
 * @covers \InstitutionalEmail\Theme
 * @covers \InstitutionalEmail\Button
 */
final class SettingsTest extends WP_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		SettingsPage::registerSetting();
	}

	protected function tearDown(): void {
		delete_option( Settings::OPTION );
		delete_option( Theme::FFC_OPTION );
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

	public function test_the_network_reaches_the_form_and_a_shortcode_can_override_it(): void {
		update_option( Settings::OPTION, array( 'network' => 'state', 'check_rf_digit' => '0' ) );

		$html = do_shortcode( '[institutional_email]' );
		self::assertStringContainsString( 'data-ie-network="state"', $html );
		self::assertSame( 1, preg_match( '/data-ie-config="([^"]*)"/', $html, $m ) );
		$config = json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
		self::assertSame( 'state', $config['network'] );
		self::assertFalse( $config['checkRfDigit'] );
		self::assertSame( Config::STATE_DOMAIN, $config['state']['domain'] );

		self::assertStringContainsString( 'data-ie-network="city"', do_shortcode( '[institutional_email network="city"]' ) );
	}

	public function test_google_analytics_loads_only_when_switched_on_with_an_id(): void {
		// A fresh script registry, so no earlier test's enqueue answers for this one.
		$GLOBALS['wp_scripts'] = null;
		do_shortcode( '[institutional_email]' );
		self::assertFalse( wp_script_is( Shortcode::GTAG_HANDLE, 'enqueued' ), 'Off by default.' );

		update_option( Settings::OPTION, array( 'analytics' => '1', 'analytics_id' => 'G-TEST1234' ) );
		do_shortcode( '[institutional_email]' );
		self::assertTrue( wp_script_is( Shortcode::GTAG_HANDLE, 'enqueued' ) );
		self::assertStringContainsString( 'G-TEST1234', wp_scripts()->registered[ Shortcode::GTAG_HANDLE ]->src );
	}

	public function test_the_form_width_reaches_the_stylesheet(): void {
		$GLOBALS['wp_styles'] = null;
		update_option( Settings::OPTION, array( 'form_width' => '900' ) );
		do_shortcode( '[institutional_email]' );
		self::assertFalse( wp_styles()->get_data( Shortcode::HANDLE, 'after' ), 'Without the Design switch on, the stylesheet keeps its own width.' );

		update_option( Settings::OPTION, array( 'form_width' => '900', 'custom_style' => '1' ) );
		do_shortcode( '[institutional_email]' );
		self::assertStringStartsWith( '.ie-generator { --ie-form-width: 900px; ', implode( '', (array) wp_styles()->get_data( Shortcode::HANDLE, 'after' ) ) );
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
		self::assertSame( count( $headings[1] ), preg_match_all( '/<(?:div|nav) class="card[^"]*"[^>]*><h2[ >]/', $html ) );
		self::assertSame( count( SettingsPage::tabs() ), preg_match_all( '/data-ie-tab="[a-z]+"><svg class="ie-icon"/', $html ) );

		// A text box for every string the form shows.
		self::assertSame( count( Config::strings() ), substr_count( $html, 'name="' . Settings::OPTION . '[strings][' ) );
		self::assertStringContainsString( 'value="[' . Shortcode::TAG . ']" readonly', $html );
		self::assertStringContainsString( 'name="' . Settings::OPTION . '[network]" value="state"', $html );
		self::assertStringContainsString( 'name="' . Settings::OPTION . '[form_width]" value="640"', $html );
		self::assertStringContainsString( 'data-ie-autosave="check_rf_digit"', $html );
		self::assertStringContainsString( 'data-ie-autosave="analytics"', $html );
		self::assertStringContainsString( 'data-ie-autosave="custom_style"', $html );
		self::assertSame( count( Config::COLORS ), preg_match_all( '/type="color" id="ie-color-/', $html ) );
		self::assertStringContainsString( 'name="' . Settings::OPTION . '[analytics_id]"', $html );
		self::assertSame( count( Config::TEACHER_NETWORKS ), substr_count( $html, 'data-ie-autosave="teacher_networks"' ) );
	}

	public function test_the_design_rows_are_hidden_from_the_server_until_switched_on(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$off = $this->renderScreen();
		self::assertSame( substr_count( $off, 'data-ie-shown-by="custom_style"' ), substr_count( $off, 'data-ie-shown-by="custom_style" hidden' ), 'No row flashes before the script runs.' );

		update_option( Settings::OPTION, array( 'custom_style' => '1' ) );
		self::assertStringNotContainsString( 'data-ie-shown-by="custom_style" hidden', $this->renderScreen() );
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

	public function test_the_screen_draws_its_buttons_from_the_family_and_loads_it(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$html = $this->renderScreen();

		self::assertStringContainsString( '<p class="submit"><button type="submit" class="ie-btn ie-btn-primary ie-icon-save" id="submit" name="submit">Save Changes</button></p>', $html );
		self::assertStringContainsString( '<button type="button" class="ie-btn ie-btn-neutral ie-icon-copy ie-copy" data-ie-copy="ie-shortcode">Copy</button>', $html );
		self::assertStringNotContainsString( 'class="button', $html, 'No core button is left in the plugin\'s own markup.' );
		self::assertStringNotContainsString( 'dashicons', $html );

		SettingsPage::enqueue( 'settings_page_' . SettingsPage::SLUG );
		self::assertTrue( wp_style_is( Plugin::BUTTONS_HANDLE, 'enqueued' ) );
		self::assertTrue( wp_style_is( Plugin::ICONS_HANDLE, 'enqueued' ) );
	}

	public function test_without_ffc_the_dark_mode_field_is_the_plugins_own(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( Settings::OPTION, array( 'dark_mode' => 'auto' ) );
		$html = $this->renderScreen();

		self::assertStringContainsString( '<select id="ie-dark-mode" name="' . Settings::OPTION . '[dark_mode]" data-ie-autosave="dark_mode">', $html );
		self::assertStringContainsString( '<option value="auto" selected=\'selected\'>', $html );
		self::assertStringNotContainsString( 'id="ie-dark-mode-ffc"', $html );
	}

	/**
	 * Free Form Certificate active: its value shows, disabled, with the note,
	 * and Save Changes keeps the plugin's own stored choice. FFC_VERSION
	 * cannot be undefined, hence the process of its own.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_with_ffc_the_field_is_disabled_and_a_save_keeps_the_stored_choice(): void {
		define( 'FFC_VERSION', '6.41.0' );
		update_option( Theme::FFC_OPTION, array( 'dark_mode' => 'on' ) );
		update_option( Settings::OPTION, array( 'dark_mode' => 'auto' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$html = $this->renderScreen();
		self::assertStringContainsString( '<select id="ie-dark-mode" disabled aria-describedby="ie-dark-mode-ffc">', $html );
		self::assertStringContainsString( '<option value="on" selected=\'selected\'>', $html, 'The field shows the mode in effect.' );
		self::assertStringContainsString( '<p class="description" id="ie-dark-mode-ffc"><strong>Controlled by Free Form Certificate</strong>', $html );
		self::assertSame( 'on', Theme::mode() );

		// What the form posts: everything but the disabled field.
		update_option( Settings::OPTION, array( 'domain' => 'example.org' ) );
		$stored = get_option( Settings::OPTION );
		self::assertSame( 'example.org', $stored['domain'] );
		self::assertSame( 'auto', $stored['dark_mode'], 'The disabled field did not overwrite the stored choice.' );
	}

	private function renderScreen(): string {
		ob_start();
		SettingsPage::render();
		return (string) ob_get_clean();
	}
}
