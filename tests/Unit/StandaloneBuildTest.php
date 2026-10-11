<?php

declare(strict_types=1);

namespace IE\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The standalone HTML is the iframe deliverable: one file, nothing fetched,
 * the plugin's own default configuration translated from the bundled
 * catalogue. It is built by running the real script, as the release does.
 *
 * @coversNothing
 */
final class StandaloneBuildTest extends TestCase {

	private const ROOT = __DIR__ . '/../..';

	private string $out = '';

	protected function setUp(): void {
		parent::setUp();
		$this->out = (string) tempnam( sys_get_temp_dir(), 'ie-standalone-' );
	}

	protected function tearDown(): void {
		if ( is_file( $this->out ) ) {
			unlink( $this->out );
		}
		parent::tearDown();
	}

	public function test_the_build_is_self_contained_and_translated(): void {
		[ $status ] = $this->build( array( '9.9.9', $this->out ) );
		self::assertSame( 0, $status );

		$html = (string) file_get_contents( $this->out );
		self::assertStringStartsWith( '<!DOCTYPE html>', $html );
		self::assertStringContainsString( '<html lang="pt-BR">', $html );
		self::assertStringContainsString( '<meta name="generator" content="Institutional Email 9.9.9">', $html );
		self::assertStringContainsString( '<title>E-mail Institucional</title>', $html );
		self::assertStringContainsString( (string) file_get_contents( self::ROOT . '/assets/css/institutional-email.css' ), $html, 'The stylesheet is inlined.' );
		self::assertStringContainsString( 'function studentEmail', $html, 'The script is inlined.' );
		self::assertStringContainsString( 'data-ie-form-param="form"', $html, 'The address can choose the form.' );
		self::assertDoesNotMatchRegularExpression( '/<(?:script|link)[^>]+(?:src|href)=/i', $html, 'Nothing is fetched.' );

		self::assertSame( 1, preg_match( '/data-ie-config="([^"]*)"/', $html, $m ) );
		$config = json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
		self::assertSame( 'edu.sme.prefeitura.sp.gov.br', $config['domain'] );
		self::assertNotSame( 'Find the email', $config['strings']['submit'], 'The strings come from the pt_BR catalogue.' );
	}

	public function test_dash_prints_the_configuration(): void {
		[ $status, $stdout ] = $this->build( array( '1.0.0', '-', 'en_US' ) );
		self::assertSame( 0, $status );
		$config = json_decode( $stdout, true );
		self::assertSame( 'Find the email', $config['strings']['submit'] );
	}

	public function test_an_unknown_locale_or_missing_arguments_fail(): void {
		self::assertSame( 1, $this->build( array( '1.0.0', '-', 'xx_XX' ) )[0] );
		self::assertSame( 1, $this->build( array( '1.0.0' ) )[0] );
	}

	/**
	 * Run the build script.
	 *
	 * @param list<string> $args Arguments.
	 * @return array{int, string} Exit status and standard output.
	 */
	private function build( array $args ): array {
		$command = array_merge( array( PHP_BINARY, self::ROOT . '/bin/build-standalone.php' ), $args );
		$process = proc_open( $command, array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
		self::assertIsResource( $process );
		$stdout = (string) stream_get_contents( $pipes[1] );
		stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		return array( proc_close( $process ), $stdout );
	}
}
