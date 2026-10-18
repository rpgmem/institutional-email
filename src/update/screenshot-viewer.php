<?php
/**
 * Screenshot viewer in the "View details" modal.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail\Update;

use InstitutionalEmail\Plugin;

/**
 * Loads the viewer that opens the modal's screenshots in an overlay instead
 * of a new tab (`assets/js/screenshots.js`).
 *
 * The modal is core's `plugin-install.php?tab=plugin-information` page, shown
 * in an iframe, and it fires `admin_enqueue_scripts` like any admin screen.
 * Its sections pass through kses, which strips scripts, so the viewer has to
 * arrive as an enqueued file. It loads only on that page and only for this
 * plugin's slug, so no other plugin's modal and no other screen carries it.
 */
final class ScreenshotViewer {

	/**
	 * Script and style handle.
	 */
	public const HANDLE = 'institutional-email-screenshots';

	/**
	 * Register the enqueue hook.
	 *
	 * Default priority: nothing else on that page depends on the order.
	 */
	public static function register(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Enqueue the viewer on this plugin's "View details" modal.
	 *
	 * @param string $hook_suffix The admin page being loaded.
	 */
	public static function enqueue( string $hook_suffix ): void {
		if ( ! self::isOurModal( $hook_suffix ) ) {
			return;
		}

		// The controls draw the registry's icons as masks.
		Plugin::enqueueIconStyles();
		wp_enqueue_style( self::HANDLE, Plugin::url() . Plugin::asset( 'assets/css/screenshots.css' ), array( Plugin::ICONS_HANDLE ), Plugin::assetVersion( Plugin::asset( 'assets/css/screenshots.css' ) ) );
		wp_enqueue_script( self::HANDLE, Plugin::url() . Plugin::asset( 'assets/js/screenshots.js' ), array(), Plugin::assetVersion( Plugin::asset( 'assets/js/screenshots.js' ) ), array( 'in_footer' => true ) );
		wp_localize_script(
			self::HANDLE,
			'ieScreenshots',
			array(
				'dialog'   => __( 'Screenshots', 'institutional-email' ),
				'close'    => __( 'Close', 'institutional-email' ),
				'previous' => __( 'Previous screenshot', 'institutional-email' ),
				'next'     => __( 'Next screenshot', 'institutional-email' ),
				'zoomIn'   => __( 'Zoom in', 'institutional-email' ),
				'zoomOut'  => __( 'Zoom out', 'institutional-email' ),
				/* translators: %1$s: number of the screenshot shown, %2$s: number of screenshots */
				'counter'  => __( '%1$s of %2$s', 'institutional-email' ),
			)
		);
	}

	/**
	 * Is this the "View details" modal for this plugin?
	 *
	 * A read-only check of the page's own query arguments: core's modal link
	 * carries no nonce, and nothing is changed here beyond loading two files.
	 *
	 * @param string $hook_suffix The admin page being loaded.
	 */
	private static function isOurModal( string $hook_suffix ): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only routing on core's own modal URL.
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$plugin = isset( $_GET['plugin'] ) ? sanitize_key( wp_unslash( $_GET['plugin'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return 'plugin-install.php' === $hook_suffix && 'plugin-information' === $tab && GithubUpdater::SLUG === $plugin;
	}
}
