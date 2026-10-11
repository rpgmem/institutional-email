<?php

declare(strict_types=1);

namespace IE\Tests\Functional;

use InstitutionalEmail\Admin\Autosave;
use InstitutionalEmail\Admin\SettingsPage;
use InstitutionalEmail\Config;
use InstitutionalEmail\Settings;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;
use WP_Ajax_UnitTestCase;

/**
 * The toggles' AJAX endpoint inside a real WordPress: nonce, capability,
 * storage, and the refusals that must reach the screen.
 *
 * @covers \InstitutionalEmail\Admin\Autosave
 * @group ajax
 */
final class AutosaveTest extends WP_Ajax_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		SettingsPage::registerSetting();
		Autosave::register();
		$_POST = array();
	}

	protected function tearDown(): void {
		delete_option( Settings::OPTION );
		$_POST = array();
		parent::tearDown();
	}

	public function test_an_administrator_saves_the_forms_and_keeps_the_rest(): void {
		update_option( Settings::OPTION, array( 'domain' => 'example.org' ) );
		$this->_setRole( 'administrator' );
		$response = $this->post( 'forms', array( 'teachers' ) );

		self::assertTrue( $response['success'] );
		self::assertSame( array( 'teachers' ), $response['data']['value'] );
		self::assertSame( array( 'teachers' ), Settings::get()['forms'] );
		self::assertSame( 'example.org', Settings::get()['domain'] );
	}

	public function test_no_form_ticked_answers_with_both(): void {
		$this->_setRole( 'administrator' );
		self::assertSame( Config::FORMS, $this->post( 'forms', array() )['data']['value'] );
	}

	public function test_a_key_that_is_not_a_toggle_is_refused_with_a_message(): void {
		$this->_setRole( 'administrator' );
		$response = $this->post( 'password', 'x' );

		self::assertFalse( $response['success'] );
		self::assertNotEmpty( $response['data']['message'] );
		self::assertSame( Config::PASSWORD, Settings::get()['password'] );
	}

	public function test_an_editor_is_refused(): void {
		$this->_setRole( 'editor' );
		$response = $this->post( 'forms', array( 'teachers' ) );

		self::assertFalse( $response['success'] );
		self::assertSame( Config::FORMS, Settings::get()['forms'] );
	}

	public function test_a_wrong_nonce_is_refused(): void {
		$this->_setRole( 'administrator' );
		$response = $this->post( 'forms', array( 'teachers' ), 'wrong' );

		self::assertFalse( $response['success'] );
		self::assertSame( Config::FORMS, Settings::get()['forms'] );
	}

	/**
	 * Post one toggle and decode the JSON answer.
	 *
	 * @param string          $key   Toggle key.
	 * @param string|string[] $value Posted value.
	 * @param string|null     $nonce Nonce; a valid one when null.
	 * @return array<string, mixed>
	 */
	private function post( string $key, $value, ?string $nonce = null ): array {
		$_POST = array(
			'nonce' => $nonce ?? wp_create_nonce( Autosave::ACTION ),
			'key'   => $key,
			'value' => $value,
		);
		try {
			$this->_handleAjax( Autosave::ACTION );
		} catch ( WPAjaxDieContinueException | WPAjaxDieStopException $e ) {
			unset( $e );
		}
		$response             = json_decode( $this->_last_response, true );
		$this->_last_response = '';
		self::assertIsArray( $response, 'The endpoint answered no JSON.' );
		return $response;
	}
}
