<?php
/**
 * Plugin Name:       Institutional Email
 * Plugin URI:        https://github.com/rpgmem/institutional-email
 * Description:       Forms, placed with a shortcode, that work out the institutional email address of students and teachers of the São Paulo city schools (RME/SP), from their name and date of birth, RF or CPF, and of the state schools (SEDUC-SP), from the student's RA.
 * Version:           1.4.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            Alex Meusburger
 * Author URI:        https://github.com/rpgmem
 * License:           AGPLv3 or later
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.html
 * Text Domain:       institutional-email
 * Domain Path:       /languages
 *
 * @package InstitutionalEmail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * The plugin version, beside the header WordPress reads as text. The two are
 * bumped together; readme.txt's Stable tag is the third site.
 */
define( 'INSTITUTIONAL_EMAIL_VERSION', '1.4.0' );

/*
 * Inline autoloader for the InstitutionalEmail\ namespace, so the plugin
 * ships no Composer runtime. A class maps to a kebab-case file under src/:
 *
 *   InstitutionalEmail\Plugin             -> src/plugin.php
 *   InstitutionalEmail\Admin\SettingsPage -> src/admin/settings-page.php
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'InstitutionalEmail\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$parts    = explode( '\\', substr( $class_name, strlen( $prefix ) ) );
		$basename = array_pop( $parts );
		$file     = strtolower( (string) preg_replace( '/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', '-', $basename ) );
		$dir      = $parts ? implode( '/', array_map( 'strtolower', $parts ) ) . '/' : '';
		$path     = __DIR__ . '/src/' . $dir . $file . '.php';
		if ( is_file( $path ) ) {
			require $path;
		}
	}
);

\InstitutionalEmail\Plugin::boot( __FILE__ );
