<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InstitutionalEmail\Admin\SettingsPage;
use InstitutionalEmail\Config;
use PHPUnit\Framework\TestCase;

/**
 * The Texts tab shows every text the form can show, each exactly once, under
 * the network and the form or option that shows it. A key missing from the
 * layout would be a text nobody can change; a key listed twice would be two
 * boxes posting the same field.
 *
 * @covers \InstitutionalEmail\Admin\SettingsPage
 */
final class TextGroupsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_every_text_is_listed_exactly_once(): void {
		$listed = array();
		foreach ( SettingsPage::textGroups() as $card ) {
			self::assertNotSame( array(), $card['groups'], $card['title'] . ' has no group.' );
			foreach ( $card['groups'] as $group ) {
				self::assertNotSame( array(), $group['keys'], $group['label'] . ' has no text.' );
				foreach ( $group['keys'] as $key ) {
					$listed[] = $key;
				}
			}
		}

		self::assertSame( array(), array_keys( array_filter( array_count_values( $listed ), static fn( int $n ): bool => $n > 1 ) ), 'Listed twice.' );
		self::assertSame( array(), array_values( array_diff( array_keys( Config::strings() ), $listed ) ), 'Not listed.' );
		self::assertSame( array(), array_values( array_diff( $listed, array_keys( Config::strings() ) ) ), 'Not a text of the form.' );
	}

	public function test_a_card_per_network_then_the_shared_texts(): void {
		self::assertSame(
			array( 'City schools (RME/SP)', 'State schools (SEDUC-SP)', 'Both networks' ),
			array_column( SettingsPage::textGroups(), 'title' )
		);
	}
}
