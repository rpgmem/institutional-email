<?php
/**
 * Build the standalone HTML form.
 *
 *   php bin/build-standalone.php <version> <out-file> [locale]
 *
 * With `-` as <out-file> it prints only the configuration, as JSON: the JS
 * tests read the real configuration that way rather than a copy.
 *
 * The output is one self-contained HTML file, made to be embedded in an
 * iframe: the stylesheet and the script are inlined, and the configuration
 * is the plugin's own default (`Config::defaults()`), translated into
 * <locale> (default pt_BR) from the bundled catalogue. It shows the
 * students' form, or the teachers' with `?form=teachers` in its address.
 *
 * Runs without WordPress: the one function Config needs is shimmed below.
 *
 * @package InstitutionalEmail\Build
 */

declare(strict_types=1);

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$ie_version = $argv[1] ?? '';
$ie_out     = $argv[2] ?? '';
$ie_locale  = $argv[3] ?? 'pt_BR';
$ie_root    = dirname( __DIR__ );

if ( '' === $ie_version || '' === $ie_out ) {
	fwrite( STDERR, "usage: php bin/build-standalone.php <version> <out-file> [locale]\n" );
	exit( 1 );
}

// The translations, from the same catalogue WordPress reads.
$ie_catalogue           = $ie_root . '/languages/institutional-email-' . $ie_locale . '.l10n.php';
$GLOBALS['ie_messages'] = array();
if ( 'en_US' !== $ie_locale ) {
	if ( ! is_readable( $ie_catalogue ) ) {
		fwrite( STDERR, "No catalogue for locale {$ie_locale}: {$ie_catalogue}\n" );
		exit( 1 );
	}
	$ie_l10n                = require $ie_catalogue;
	$GLOBALS['ie_messages'] = is_array( $ie_l10n['messages'] ?? null ) ? $ie_l10n['messages'] : array();
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translate a string from the loaded catalogue.
	 *
	 * @param string $text   Source string.
	 * @param string $domain Text domain (unused).
	 */
	function __( string $text, string $domain = 'default' ): string { // phpcs:ignore
		$translated = $GLOBALS['ie_messages'][ $text ] ?? '';
		return '' !== $translated ? $translated : $text;
	}
}

define( 'ABSPATH', $ie_root . '/' );
require $ie_root . '/src/config.php';

$ie_config = \InstitutionalEmail\Config::defaults();

if ( '-' === $ie_out ) {
	echo json_encode( $ie_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ), "\n";
	exit( 0 );
}

/**
 * Read a file the build inlines, or stop.
 *
 * @param string $path Absolute path.
 */
function ie_read( string $path ): string {
	$contents = file_get_contents( $path );
	if ( false === $contents ) {
		fwrite( STDERR, "Cannot read {$path}\n" );
		exit( 1 );
	}
	return $contents;
}

$ie_css   = ie_read( $ie_root . '/assets/css/institutional-email.css' );
$ie_js    = str_ireplace( '</script', '<\/script', ie_read( $ie_root . '/assets/js/institutional-email.js' ) );
$ie_json  = json_encode( $ie_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR );
$ie_lang  = str_replace( '_', '-', $ie_locale );
$ie_title = __( 'Institutional Email', 'institutional-email' );
$ie_e     = static fn( string $s ): string => htmlspecialchars( $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );

$ie_html = '<!DOCTYPE html>
<html lang="' . $ie_e( $ie_lang ) . '">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="generator" content="Institutional Email ' . $ie_e( $ie_version ) . '">
<title>' . $ie_e( $ie_title ) . '</title>
<style>
body { margin: 0; padding: 16px; background: #ffffff; color: #1d2327; }
' . $ie_css . '
/* In an iframe the embedding page decides the width. */
.ie-generator { max-width: none; }
</style>
</head>
<body>
<!-- Institutional Email ' . $ie_e( $ie_version ) . ', standalone build. https://github.com/rpgmem/institutional-email -->
<div class="ie-generator" data-ie-form="students" data-ie-form-param="form" data-ie-config="' . $ie_e( $ie_json ) . '"><noscript>' . $ie_e( __( 'This form needs JavaScript.', 'institutional-email' ) ) . '</noscript></div>
<script>
' . $ie_js . '
</script>
</body>
</html>
';

if ( false === file_put_contents( $ie_out, $ie_html ) ) {
	fwrite( STDERR, "Cannot write {$ie_out}\n" );
	exit( 1 );
}
echo $ie_out, "\n";
