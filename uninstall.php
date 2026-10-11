<?php
/**
 * Removes everything the plugin stored.
 *
 * WordPress runs this file when the plugin is deleted from the Plugins screen.
 * Each option the plugin writes is deleted here, in the same change that
 * adds it; none yet. The plugin stores nothing about the people who use the form.
 *
 * @package InstitutionalEmail
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
