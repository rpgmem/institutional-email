<?php
/**
 * Settings → Institutional Email.
 *
 * @package InstitutionalEmail
 */

declare(strict_types=1);

namespace InstitutionalEmail\Admin;

use InstitutionalEmail\Config;
use InstitutionalEmail\Plugin;
use InstitutionalEmail\Settings;
use InstitutionalEmail\Shortcode;

/**
 * The settings screen. It saves through the Settings API (options.php), which
 * checks the nonce `settings_fields()` prints and the `manage_options`
 * capability before Settings::save() runs.
 */
final class SettingsPage {

	public const SLUG = 'institutional-email';

	public const GROUP = 'institutional_email';

	/**
	 * Hook the screen.
	 */
	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'registerSetting' ) );
		add_action( 'admin_menu', array( self::class, 'addPage' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( Plugin::file() ), array( self::class, 'actionLinks' ) );
	}

	/**
	 * Register the option with its sanitizer.
	 */
	public static function registerSetting(): void {
		register_setting(
			self::GROUP,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'save' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Add the page under Settings.
	 */
	public static function addPage(): void {
		add_options_page(
			__( 'Institutional Email', 'institutional-email' ),
			__( 'Institutional Email', 'institutional-email' ),
			'manage_options',
			self::SLUG,
			array( self::class, 'render' )
		);
	}

	/**
	 * A Settings link on the Plugins screen.
	 *
	 * @param array<int|string, string> $links Existing links.
	 * @return array<int|string, string>
	 */
	public static function actionLinks( array $links ): array {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) ), esc_html__( 'Settings', 'institutional-email' ) )
		);
		return $links;
	}

	/**
	 * The screen's stylesheet and script, on this screen only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( string $hook ): void {
		if ( 'settings_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'institutional-email-admin', Plugin::url() . 'assets/css/admin-settings.css', array(), Plugin::assetVersion( 'assets/css/admin-settings.css' ) );
		wp_enqueue_script( 'institutional-email-admin', Plugin::url() . 'assets/js/admin-settings.js', array(), Plugin::assetVersion( 'assets/js/admin-settings.js' ), array( 'in_footer' => true ) );
		wp_localize_script(
			'institutional-email-admin',
			'ieAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => Autosave::ACTION,
				'nonce'   => wp_create_nonce( Autosave::ACTION ),
				'saving'  => __( 'Saving…', 'institutional-email' ),
				'saved'   => __( 'Saved', 'institutional-email' ),
				'failed'  => __( 'Not saved', 'institutional-email' ),
				'copied'  => __( 'Copied', 'institutional-email' ),
			)
		);
	}

	/**
	 * The forms, by id, with the name the screen shows.
	 *
	 * @return array<string, string>
	 */
	public static function formLabels(): array {
		return array(
			'students' => __( 'Students: full name and date of birth', 'institutional-email' ),
			'teachers' => __( 'Teachers: full name and CPF or RF', 'institutional-email' ),
		);
	}

	/**
	 * Render the page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::get();
		$defaults = Config::defaults();
		$name     = Settings::OPTION;

		echo '<div class="wrap ie-settings">';
		echo '<h1>' . esc_html__( 'Institutional Email', 'institutional-email' ) . '</h1>';
		echo '<p>' . esc_html__( 'These settings apply to every form on the site. The standalone HTML page keeps the built-in defaults.', 'institutional-email' ) . '</p>';

		echo '<form method="post" action="options.php">';
		settings_fields( self::GROUP );

		$active = self::activeTab();
		echo '<div class="ie-tabs">';
		self::nav( $active );
		echo '<div class="ie-tabs__panels">';

		// General.
		self::openPanel( 'general', $active );
		self::openCard( __( 'Form', 'institutional-email' ), 'settings' );
		echo '<table class="form-table" role="presentation"><tbody>';

		printf(
			'<tr><th scope="row"><label for="ie-shortcode">%1$s</label></th><td><input type="text" id="ie-shortcode" class="regular-text code" value="%2$s" readonly> <button type="button" class="button ie-copy" data-ie-copy="ie-shortcode">%3$s</button><p class="description">%4$s</p></td></tr>',
			esc_html__( 'Shortcode', 'institutional-email' ),
			esc_attr( '[' . Shortcode::TAG . ']' ),
			esc_html__( 'Copy', 'institutional-email' ),
			wp_kses(
				sprintf(
					/* translators: %s: the teachers' shortcode. */
					__( 'Paste it into any page or post: it shows the students\' form. For the teachers\' form, use %s.', 'institutional-email' ),
					'<code>[' . esc_html( Shortcode::TAG ) . ' form="teachers"]</code>'
				),
				array( 'code' => array() )
			)
		);

		echo '<tr><th scope="row">' . esc_html__( 'Forms offered', 'institutional-email' ) . '</th><td><fieldset>';
		foreach ( self::formLabels() as $id => $label ) {
			echo '<p>';
			self::toggle(
				array(
					'name'    => $name . '[forms][]',
					'value'   => $id,
					'checked' => in_array( $id, $settings['forms'], true ),
					'label'   => $label,
					'key'     => 'forms',
				)
			);
			echo '</p>';
		}
		echo '<p class="description">' . esc_html__( 'A shortcode that asks for a form that is not offered shows the first one that is. With none ticked, both are offered.', 'institutional-email' ) . '</p>';
		echo '</fieldset></td></tr>';

		echo '<tr><th scope="row">' . esc_html__( 'Who can use the form', 'institutional-email' ) . '</th><td><fieldset>';
		foreach ( array(
			Settings::ACCESS_EVERYONE  => __( 'Everyone who can see the page', 'institutional-email' ),
			Settings::ACCESS_LOGGED_IN => __( 'Logged-in users only', 'institutional-email' ),
		) as $value => $label ) {
			printf(
				'<label><input type="radio" name="%1$s[access]" value="%2$s"%3$s> %4$s</label><br>',
				esc_attr( $name ),
				esc_attr( $value ),
				checked( $settings['access'], $value, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset></td></tr>';
		echo '</tbody></table></div></div>';

		// Rules.
		self::openPanel( 'rules', $active );
		self::openCard( __( 'Address', 'institutional-email' ), 'email' );
		echo '<p class="description">' . esc_html__( 'A student\'s address is the first name, the initial of each longer middle name and the last name, then a dot and the date of birth (DDMMYYYY). A teacher\'s is the first and the last name, then a dot and the RF or the CPF.', 'institutional-email' ) . '</p>';
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row"><label for="ie-domain">%1$s</label></th><td><input type="text" id="ie-domain" class="regular-text code" name="%2$s[domain]" value="%3$s" placeholder="%4$s" required><p class="description">%5$s</p></td></tr>',
			esc_html__( 'Domain', 'institutional-email' ),
			esc_attr( $name ),
			esc_attr( $settings['domain'] ),
			esc_attr( Config::DOMAIN ),
			/* translators: %s: the default domain. */
			esc_html( sprintf( __( 'What comes after the "@". Default: %s.', 'institutional-email' ), Config::DOMAIN ) )
		);
		printf(
			'<tr><th scope="row"><label for="ie-middle-min">%1$s</label></th><td><input type="number" id="ie-middle-min" class="small-text" name="%2$s[middle_min]" value="%3$d" min="%4$d" max="%5$d" required> %6$s<p class="description">%7$s</p></td></tr>',
			esc_html__( 'Middle names that count', 'institutional-email' ),
			esc_attr( $name ),
			(int) $settings['middle_min'],
			(int) Settings::MIDDLE_MIN_MIN,
			(int) Settings::MIDDLE_MIN_MAX,
			esc_html__( 'letters or more', 'institutional-email' ),
			/* translators: %d: the default number of letters. */
			esc_html( sprintf( __( 'Only for students. A middle name this long or longer gives its initial to the address; shorter ones, such as "da" and "dos", are left out. Default: %d.', 'institutional-email' ), Config::MIDDLE_MIN ) )
		);
		echo '</tbody></table></div>';

		self::openCard( __( 'Initial password', 'institutional-email' ), 'key' );
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row"><label for="ie-password">%1$s</label></th><td><input type="text" id="ie-password" class="regular-text code" name="%2$s[password]" value="%3$s" maxlength="%4$d" autocomplete="off" required><p class="description">%5$s</p></td></tr>',
			esc_html__( 'Password', 'institutional-email' ),
			esc_attr( $name ),
			esc_attr( $settings['password'] ),
			(int) Settings::PASSWORD_MAX,
			esc_html__( 'The password a new account starts with, shown beside the address. It is public: everyone who opens the form sees it.', 'institutional-email' )
		);
		echo '</tbody></table></div></div>';

		// Texts.
		self::openPanel( 'texts', $active );
		self::openCard( __( 'Next step', 'institutional-email' ), 'link' );
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row"><label for="ie-platform-name">%1$s</label></th><td><input type="text" id="ie-platform-name" class="regular-text" name="%2$s[platform_name]" value="%3$s" placeholder="%4$s" maxlength="%5$d"></td></tr>',
			esc_html__( 'Platform name', 'institutional-email' ),
			esc_attr( $name ),
			esc_attr( $settings['platform_name'] ),
			esc_attr( (string) $defaults['platformName'] ),
			(int) Settings::NAME_MAX
		);
		printf(
			'<tr><th scope="row"><label for="ie-platform-url">%1$s</label></th><td><input type="url" id="ie-platform-url" class="regular-text code" name="%2$s[platform_url]" value="%3$s" placeholder="%4$s"><p class="description">%5$s</p></td></tr>',
			esc_html__( 'Platform address', 'institutional-email' ),
			esc_attr( $name ),
			esc_attr( $settings['platform_url'] ),
			esc_attr( Config::PLATFORM_URL ),
			esc_html__( 'Where the new account is used first; the form links to it below the result. Leave it empty to show no next step.', 'institutional-email' )
		);
		echo '</tbody></table></div>';

		self::openCard( __( 'Texts', 'institutional-email' ), 'list' );
		echo '<p class="description">' . esc_html(
			sprintf(
				/* translators: %d: maximum number of characters. */
				__( 'Change any text the form shows, up to %d characters. Leave a field empty to keep the built-in text, which follows the site\'s language.', 'institutional-email' ),
				Settings::TEXT_MAX
			)
		) . '</p>';
		echo '<div class="ie-table-scroll"><table class="widefat striped ie-texts-table"><thead><tr>';
		echo '<th scope="col">' . esc_html__( 'Built-in text', 'institutional-email' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Text on this site', 'institutional-email' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( Config::strings() as $key => $text ) {
			printf(
				'<tr><td><label for="ie-text-%1$s">%2$s</label></td><td><input type="text" id="ie-text-%1$s" class="large-text" name="%3$s" value="%4$s" placeholder="%5$s" maxlength="%6$d"></td></tr>',
				esc_attr( $key ),
				esc_html( $text ),
				esc_attr( $name . '[strings][' . $key . ']' ),
				esc_attr( $settings['strings'][ $key ] ?? '' ),
				esc_attr( $text ),
				(int) Settings::TEXT_MAX
			);
		}
		echo '</tbody></table></div></div></div>';

		// Documentation: nothing to save, so the panel hides the button.
		self::openPanel( 'documentation', $active );
		Documentation::render();
		echo '</div>';

		echo '</div></div>';
		echo '<div class="ie-submit"' . ( 'documentation' === $active ? ' hidden' : '' ) . '>';
		submit_button();
		echo '</div>';
		echo '</form></div>';
	}

	/**
	 * The tabs, in order: id => label and icon.
	 *
	 * @return array<string, array{label: string, icon: string}>
	 */
	public static function tabs(): array {
		return array(
			'general'       => array(
				'label' => __( 'General', 'institutional-email' ),
				'icon'  => 'settings',
			),
			'rules'         => array(
				'label' => __( 'Address rules', 'institutional-email' ),
				'icon'  => 'email',
			),
			'texts'         => array(
				'label' => __( 'Texts', 'institutional-email' ),
				'icon'  => 'list',
			),
			'documentation' => array(
				'label' => __( 'Documentation', 'institutional-email' ),
				'icon'  => 'book',
			),
		);
	}

	/**
	 * The tab to open: `?tab=` when it names one, else the first.
	 *
	 * Every tab is in the one form, so switching tabs never loses an edit;
	 * the parameter only chooses which panel shows first, for example after
	 * "Save Changes" returns to the screen.
	 */
	private static function activeTab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only: chooses the panel shown first.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		return array_key_exists( $tab, self::tabs() ) ? $tab : 'general';
	}

	/**
	 * The vertical tab menu. Each tab is a link, so it also works without
	 * JavaScript, where every panel shows anyway.
	 *
	 * @param string $active Active tab id.
	 */
	private static function nav( string $active ): void {
		echo '<ul class="ie-tabs__nav" role="tablist">';
		foreach ( self::tabs() as $id => $tab ) {
			$is = $id === $active;
			printf(
				'<li role="presentation"><a class="ie-tabs__tab%1$s" id="ie-tab-%2$s" href="%3$s" role="tab" aria-controls="ie-panel-%2$s" aria-selected="%4$s" data-ie-tab="%2$s">%5$s<span class="ie-tabs__label">%6$s</span></a></li>',
				$is ? ' is-active' : '',
				esc_attr( $id ),
				esc_url( admin_url( 'options-general.php?page=' . self::SLUG . '&tab=' . $id ) ),
				$is ? 'true' : 'false',
				Icons::svg( $tab['icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup from the icon registry.
				esc_html( $tab['label'] )
			);
		}
		echo '</ul>';
	}

	/**
	 * Open a tab's panel; the caller closes the `div`.
	 *
	 * @param string $id     Tab id.
	 * @param string $active Active tab id.
	 */
	private static function openPanel( string $id, string $active ): void {
		printf(
			'<div class="ie-tabs__panel%1$s" id="ie-panel-%2$s" role="tabpanel" aria-labelledby="ie-tab-%2$s">',
			$id === $active ? ' is-active' : '',
			esc_attr( $id )
		);
	}

	/**
	 * Open a boxed section whose heading carries the icon of its subject;
	 * the caller closes the `div`.
	 *
	 * @param string $title Heading.
	 * @param string $icon  Icon name.
	 */
	private static function openCard( string $title, string $icon ): void {
		printf(
			'<div class="card"><h2>%1$s<span>%2$s</span></h2>',
			Icons::svg( $icon ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup from the icon registry.
			esc_html( $title )
		);
	}

	/**
	 * An on/off switch: a real checkbox, so the form still posts it, drawn as
	 * a track. With a `key` it also saves the moment it is flipped.
	 *
	 * @param array{name: string, value?: string, checked: bool, label?: string, key?: string} $args Toggle.
	 */
	private static function toggle( array $args ): void {
		$label = (string) ( $args['label'] ?? '' );
		$key   = (string) ( $args['key'] ?? '' );
		printf(
			'<label class="ie-toggle"><input type="checkbox" name="%1$s" value="%2$s"%3$s%4$s><span class="ie-toggle-track" aria-hidden="true"></span>%5$s</label>',
			esc_attr( $args['name'] ),
			esc_attr( $args['value'] ?? '1' ),
			checked( $args['checked'], true, false ),
			'' !== $key ? ' data-ie-autosave="' . esc_attr( $key ) . '"' : '',
			'' !== $label ? '<span class="ie-toggle-label">' . esc_html( $label ) . '</span>' : ''
		);
	}
}
