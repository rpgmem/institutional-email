<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use InstitutionalEmail\Config;
use PHPUnit\Framework\TestCase;

/**
 * The script has no text of its own: every string it shows is a key of
 * Config::strings(). A key the script reads and the configuration lacks
 * renders as "undefined", which no other test would see.
 *
 * @covers \InstitutionalEmail\Config
 */
final class ConfigTest extends TestCase {

	private const SCRIPT = __DIR__ . '/../../assets/js/institutional-email.js';

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		// Mockery expectations are assertions too; PHPUnit does not count them.
		$this->addToAssertionCount( \Mockery::getContainer()->mockery_getExpectationCount() );
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_the_defaults_carry_the_rules_parameters(): void {
		$config = Config::defaults();

		self::assertSame( 'edu.sme.prefeitura.sp.gov.br', $config['domain'] );
		self::assertSame( '12345678', $config['password'] );
		self::assertSame( 'https://classroom.google.com/', $config['platformUrl'] );
		self::assertSame( 'Google Classroom', $config['platformName'] );
		self::assertSame( Config::strings(), $config['strings'] );
	}

	public function test_every_string_the_script_reads_exists(): void {
		$read = $this->keysTheScriptReads();
		self::assertNotEmpty( $read, 'The scan found no `s.key` reads: the script changed shape.' );

		$missing = array_diff( $read, array_keys( Config::strings() ) );
		self::assertSame( array(), array_values( $missing ), 'Keys the script reads and the configuration lacks.' );
	}

	public function test_every_configured_string_is_read_by_the_script(): void {
		$unused = array_diff( array_keys( Config::strings() ), $this->keysTheScriptReads() );
		self::assertSame( array(), array_values( $unused ), 'Strings nothing shows: delete them or use them.' );
	}

	public function test_no_string_is_empty(): void {
		foreach ( Config::strings() as $key => $text ) {
			self::assertNotSame( '', trim( $text ), $key );
		}
	}

	/**
	 * The keys of `config.strings` the script reads: the direct `s.key` reads,
	 * plus `error_<field>` for every field key it can report, since those are
	 * built as `s[ 'error_' + key ]`.
	 *
	 * @return list<string>
	 */
	private function keysTheScriptReads(): array {
		$js = (string) file_get_contents( self::SCRIPT );
		self::assertStringContainsString( "s[ 'error_' + key ]", $js, 'The error strings are no longer read by field key.' );

		preg_match_all( '/\bs\.([A-Za-z]+)\b/', $js, $direct );
		preg_match_all( "/errors\\.push\\( '([A-Za-z]+)' \\)/", $js, $fields );
		self::assertNotEmpty( $fields[1], 'The scan found no reported field keys.' );

		$keys = array_merge(
			$direct[1],
			array_map( static fn( string $field ): string => 'error_' . $field, $fields[1] )
		);
		$keys = array_values( array_unique( $keys ) );
		sort( $keys );
		return $keys;
	}
}
