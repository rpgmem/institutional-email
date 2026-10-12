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
	 * The options of the city teachers' form, by id, with the name the
	 * screen shows.
	 *
	 * @return array<string, string>
	 */
	public static function teacherNetworkLabels(): array {
		return array(
			'partner' => __( 'Partner network: the CPF', 'institutional-email' ),
			'direct'  => __( 'Direct network: the RF', 'institutional-email' ),
			'intern'  => __( 'Interns: the CPF, with an "e" before it', 'institutional-email' ),
		);
	}

	/**
	 * The form's colours, by key, with the name the screen shows.
	 *
	 * @return array<string, string>
	 */
	public static function colorLabels(): array {
		return array(
			'accent'      => __( 'Buttons and links', 'institutional-email' ),
			'accent_text' => __( 'Button text', 'institutional-email' ),
			'border'      => __( 'Field borders', 'institutional-email' ),
			'muted'       => __( 'Hints', 'institutional-email' ),
			'error'       => __( 'Errors', 'institutional-email' ),
			'ok_bg'       => __( 'Result background', 'institutional-email' ),
			'ok_border'   => __( 'Result border', 'institutional-email' ),
			'value_bg'    => __( 'Address and password background', 'institutional-email' ),
		);
	}

	/**
	 * The school networks, by id, with the name the screen shows.
	 *
	 * @return array<string, string>
	 */
	public static function networkLabels(): array {
		return array(
			'city'  => __( 'São Paulo city schools (RME/SP)', 'institutional-email' ),
			'state' => __( 'São Paulo state schools (SEDUC-SP)', 'institutional-email' ),
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
					/* translators: 1: the teachers' shortcode, 2: the state schools' shortcode. */
					__( 'Paste it into any page or post: it shows the students\' form. For the teachers\' form, use %1$s. To serve the other school network on one page, add network="state" or network="city", as in %2$s.', 'institutional-email' ),
					'<code>[' . esc_html( Shortcode::TAG ) . ' form="teachers"]</code>',
					'<code>[' . esc_html( Shortcode::TAG ) . ' network="state"]</code>'
				),
				array( 'code' => array() )
			)
		);

		echo '<tr><th scope="row">' . esc_html__( 'School network', 'institutional-email' ) . '</th><td><fieldset>';
		foreach ( self::networkLabels() as $value => $label ) {
			printf(
				'<label><input type="radio" name="%1$s[network]" value="%2$s"%3$s> %4$s</label><br>',
				esc_attr( $name ),
				esc_attr( $value ),
				checked( $settings['network'], $value, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'Which addresses the forms work out on this site. City schools: students from name and date of birth, teachers from RF or CPF. State schools: students from the RA and its check digit; teachers are pointed to the SED, where their address is created.', 'institutional-email' ) . '</p>';
		echo '</fieldset></td></tr>';

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

		echo '<tr><th scope="row">' . esc_html__( 'Teachers\' form options', 'institutional-email' ) . '</th><td><fieldset>';
		foreach ( self::teacherNetworkLabels() as $id => $label ) {
			echo '<p>';
			self::toggle(
				array(
					'name'    => $name . '[teacher_networks][]',
					'value'   => $id,
					'checked' => in_array( $id, $settings['teacher_networks'], true ),
					'label'   => $label,
					'key'     => 'teacher_networks',
				)
			);
			echo '</p>';
		}
		echo '<p class="description">' . esc_html__( 'Who the teachers\' form serves in the city schools. With only one ticked, the form asks for it without a choice; with none ticked, all three are offered.', 'institutional-email' ) . '</p>';
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
		echo '</tbody></table></div>';

		self::openCard( __( 'Google Analytics', 'institutional-email' ), 'chart' );
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row">%1$s</th><td><input type="hidden" name="%2$s[analytics]" value="0">',
			esc_html__( 'Tracking', 'institutional-email' ),
			esc_attr( $name )
		);
		self::toggle(
			array(
				'name'    => $name . '[analytics]',
				'checked' => $settings['analytics'],
				'label'   => __( 'Load Google Analytics on the pages that show a form', 'institutional-email' ),
				'key'     => 'analytics',
			)
		);
		echo '<p class="description">' . esc_html__( 'Off, nothing of Google\'s reaches the page. On, the page loads Google\'s tag with the ID below and records each address worked out, with only the form and the school network: never a name, a number or the address. Google then receives each visit, so say so in the site\'s privacy policy. Leave it off if the site already loads Google Analytics on every page.', 'institutional-email' ) . '</p></td></tr>';
		printf(
			'<tr><th scope="row"><label for="ie-analytics-id">%1$s</label></th><td><input type="text" id="ie-analytics-id" class="regular-text code" name="%2$s[analytics_id]" value="%3$s" placeholder="G-XXXXXXXXXX" autocomplete="off"><p class="description">%4$s</p></td></tr>',
			esc_html__( 'Measurement ID', 'institutional-email' ),
			esc_attr( $name ),
			esc_attr( $settings['analytics_id'] ),
			esc_html__( 'From Google Analytics: Admin, Data streams, the site\'s stream. Only the ID, not the code snippet. Without an ID nothing is loaded, even switched on.', 'institutional-email' )
		);
		echo '</tbody></table></div></div>';

		// Rules.
		self::openPanel( 'rules', $active );
		self::openCard( __( 'City schools (RME/SP)', 'institutional-email' ), 'email' );
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
		printf(
			'<tr><th scope="row">%1$s</th><td><input type="hidden" name="%2$s[check_rf_digit]" value="0">',
			esc_html__( 'RF check digit', 'institutional-email' ),
			esc_attr( $name )
		);
		self::toggle(
			array(
				'name'    => $name . '[check_rf_digit]',
				'checked' => $settings['check_rf_digit'],
				'label'   => __( 'Refuse an RF whose seventh digit does not match the first six', 'institutional-email' ),
				'key'     => 'check_rf_digit',
			)
		);
		echo '<p class="description">' . esc_html__( 'Catches a mistyped RF before it becomes an address that does not exist. The rule is the one Free Form Certificate uses; turn it off only if a genuine RF is refused.', 'institutional-email' ) . '</p></td></tr>';
		echo '</tbody></table></div>';

		self::openCard( __( 'Initial password (city schools)', 'institutional-email' ), 'key' );
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row"><label for="ie-password">%1$s</label></th><td><input type="text" id="ie-password" class="regular-text code" name="%2$s[password]" value="%3$s" maxlength="%4$d" autocomplete="off" required><p class="description">%5$s</p></td></tr>',
			esc_html__( 'Password', 'institutional-email' ),
			esc_attr( $name ),
			esc_attr( $settings['password'] ),
			(int) Settings::PASSWORD_MAX,
			esc_html__( 'The password a new account starts with, shown beside the address. It is public: everyone who opens the form sees it.', 'institutional-email' )
		);
		echo '</tbody></table></div>';

		self::openCard( __( 'State schools (SEDUC-SP)', 'institutional-email' ), 'info' );
		echo '<dl class="ie-rules">';
		printf(
			'<dt>%1$s</dt><dd>%2$s</dd>',
			esc_html__( 'Students', 'institutional-email' ),
			esc_html(
				sprintf(
					/* translators: 1: the address suffix, 2: the domain. */
					__( 'The RA, padded with zeros to 12 digits, its check digit (0 to 9 or X), then "%1$s" @ %2$s. The initial password is the date of birth (DDMMYYYY).', 'institutional-email' ),
					Config::STATE_SUFFIX,
					Config::STATE_DOMAIN
				)
			)
		);
		printf(
			'<dt>%1$s</dt><dd>%2$s <a href="%3$s" target="_blank" rel="noopener noreferrer">%4$s</a></dd>',
			esc_html__( 'Teachers', 'institutional-email' ),
			esc_html__( 'No rule to apply: the address is created on the first access to the SED, with the SED password. The form explains this and links to', 'institutional-email' ),
			esc_url( Config::STATE_TEACHER_URL ),
			esc_html( (string) preg_replace( '#^https?://#', '', Config::STATE_TEACHER_URL ) )
		);
		echo '</dl>';
		echo '<p class="description">' . esc_html__( 'These follow the state rules and have nothing to set; their texts are on the Texts tab.', 'institutional-email' ) . '</p>';
		echo '</div></div>';

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

		$defaults_text = Config::strings();
		foreach ( self::textGroups() as $i => $card ) {
			self::openCard( $card['title'], 'list' );
			if ( 0 === $i ) {
				echo '<p class="description">' . esc_html(
					sprintf(
						/* translators: %d: maximum number of characters. */
						__( 'Change any text the form shows, up to %d characters. Leave a field empty to keep the built-in text, which follows the site\'s language.', 'institutional-email' ),
						Settings::TEXT_MAX
					)
				) . '</p>';
			}
			echo '<div class="ie-table-scroll"><table class="widefat ie-texts-table"><thead><tr>';
			echo '<th scope="col">' . esc_html__( 'Built-in text', 'institutional-email' ) . '</th>';
			echo '<th scope="col">' . esc_html__( 'Text on this site', 'institutional-email' ) . '</th>';
			echo '</tr></thead>';
			foreach ( $card['groups'] as $group ) {
				printf( '<tbody><tr class="ie-texts-group"><th scope="colgroup" colspan="2">%s</th></tr>', esc_html( $group['label'] ) );
				foreach ( $group['keys'] as $key ) {
					$text = $defaults_text[ $key ];
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
				echo '</tbody>';
			}
			echo '</table></div></div>';
		}
		echo '</div>';

		// Design.
		self::openPanel( 'design', $active );
		self::openCard( __( 'Design', 'institutional-email' ), 'palette' );
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row">%1$s</th><td><input type="hidden" name="%2$s[custom_style]" value="0">',
			esc_html__( 'Customise', 'institutional-email' ),
			esc_attr( $name )
		);
		self::toggle(
			array(
				'name'    => $name . '[custom_style]',
				'checked' => $settings['custom_style'],
				'label'   => __( 'Use the width, colours, corners and font below', 'institutional-email' ),
				'key'     => 'custom_style',
			)
		);
		echo '<p class="description">' . esc_html__( 'Off, the form keeps its own look, and the values below go back to their defaults: switching it off clears the customisation. The standalone page always keeps its own look.', 'institutional-email' ) . '</p></td></tr>';
		// Rendered hidden when the design is off, so the rows never flash
		// before the script hides them.
		$shown = $settings['custom_style'] ? '' : ' hidden';
		printf(
			'<tr data-ie-shown-by="custom_style"%8$s><th scope="row"><label for="ie-form-width">%1$s</label></th><td><input type="number" id="ie-form-width" class="small-text" name="%2$s[form_width]" value="%3$d" data-ie-default="%7$d" min="%4$d" max="%5$d" step="10" required> px<p class="description">%6$s</p></td></tr>',
			esc_html__( 'Form width', 'institutional-email' ),
			esc_attr( $name ),
			(int) $settings['form_width'],
			(int) Settings::FORM_WIDTH_MIN,
			(int) Settings::FORM_WIDTH_MAX,
			/* translators: 1: default width, 2: minimum, 3: maximum, in pixels. */
			esc_html( sprintf( __( 'The widest the form grows on a page; on a narrower screen it fills the space it has. Default: %1$d px, from %2$d to %3$d. The standalone page always fills its iframe.', 'institutional-email' ), Config::FORM_WIDTH, Settings::FORM_WIDTH_MIN, Settings::FORM_WIDTH_MAX ) ),
			(int) Config::FORM_WIDTH,
			esc_attr( $shown )
		);
		foreach ( self::colorLabels() as $key => $label ) {
			printf(
				'<tr data-ie-shown-by="custom_style"%7$s><th scope="row"><label for="ie-color-%1$s">%2$s</label></th><td><input type="color" id="ie-color-%1$s" name="%3$s[colors][%1$s]" value="%4$s" data-ie-default="%6$s"> <code>%5$s</code></td></tr>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( $name ),
				esc_attr( $settings['colors'][ $key ] ),
				/* translators: %s: the default colour, #rrggbb. */
				esc_html( sprintf( __( 'Default: %s', 'institutional-email' ), Config::COLORS[ $key ] ) ),
				esc_attr( Config::COLORS[ $key ] ),
				esc_attr( $shown )
			);
		}
		printf(
			'<tr data-ie-shown-by="custom_style"%7$s><th scope="row"><label for="ie-radius">%1$s</label></th><td><input type="number" id="ie-radius" class="small-text" name="%2$s[radius]" value="%3$d" data-ie-default="%6$d" min="0" max="%4$d" required> px<p class="description">%5$s</p></td></tr>',
			esc_html__( 'Rounded corners', 'institutional-email' ),
			esc_attr( $name ),
			(int) $settings['radius'],
			(int) Settings::RADIUS_MAX,
			/* translators: %d: the default radius, in pixels. */
			esc_html( sprintf( __( 'Of the fields, the buttons and the result; 0 makes them square. Default: %d px.', 'institutional-email' ), Config::RADIUS ) ),
			(int) Config::RADIUS,
			esc_attr( $shown )
		);
		echo '<tr data-ie-shown-by="custom_style"' . esc_attr( $shown ) . '><th scope="row">' . esc_html__( 'Font', 'institutional-email' ) . '</th><td><fieldset>';
		foreach ( array(
			'form'  => __( 'The form\'s own (Arial)', 'institutional-email' ),
			'theme' => __( 'The site theme\'s', 'institutional-email' ),
		) as $value => $label ) {
			printf(
				'<label><input type="radio" name="%1$s[font]" value="%2$s" data-ie-default="%5$s"%3$s> %4$s</label><br>',
				esc_attr( $name ),
				esc_attr( $value ),
				checked( $settings['font'], $value, false ),
				esc_html( $label ),
				esc_attr( Config::FONTS[0] )
			);
		}
		echo '</fieldset></td></tr>';
		echo '</tbody></table></div></div>';

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
	 * The Texts tab's layout: one card per school network, then the texts
	 * every form shares, each split by the form or option that shows them.
	 * Every key of Config::strings() appears exactly once, which
	 * tests/Unit/TextGroupsTest.php checks.
	 *
	 * @return list<array{title: string, groups: list<array{label: string, keys: list<string>}>}>
	 */
	public static function textGroups(): array {
		return array(
			array(
				'title'  => __( 'City schools (RME/SP)', 'institutional-email' ),
				'groups' => array(
					array(
						'label' => __( 'Students', 'institutional-email' ),
						'keys'  => array( 'studentName' ),
					),
					array(
						'label' => __( 'Teachers', 'institutional-email' ),
						'keys'  => array( 'teacherName', 'teacherResult', 'networkLegend', 'networkHint' ),
					),
					array(
						'label' => __( 'Teachers: partner network', 'institutional-email' ),
						'keys'  => array( 'networkPartner' ),
					),
					array(
						'label' => __( 'Teachers: direct network', 'institutional-email' ),
						'keys'  => array( 'networkDirect', 'rf', 'rfHint', 'error_rf', 'error_rfCheck' ),
					),
					array(
						'label' => __( 'Interns', 'institutional-email' ),
						'keys'  => array( 'networkIntern', 'internName', 'internResult' ),
					),
					array(
						'label' => __( 'CPF: partner network and interns', 'institutional-email' ),
						'keys'  => array( 'cpf', 'error_cpf' ),
					),
					array(
						'label' => __( 'Students, teachers and interns', 'institutional-email' ),
						'keys'  => array( 'error_name', 'passwordResult' ),
					),
				),
			),
			array(
				'title'  => __( 'State schools (SEDUC-SP)', 'institutional-email' ),
				'groups' => array(
					array(
						'label' => __( 'Students', 'institutional-email' ),
						'keys'  => array( 'stateRa', 'stateRaHint', 'stateRaDigit', 'stateRaDigitHint', 'stateRaLookup', 'statePasswordResult', 'error_ra', 'error_raDigit' ),
					),
					array(
						'label' => __( 'Teachers', 'institutional-email' ),
						'keys'  => array( 'stateTeacherIntro', 'stateTeacherLink' ),
					),
				),
			),
			array(
				'title'  => __( 'Both networks', 'institutional-email' ),
				'groups' => array(
					array(
						'label' => __( 'Profile selector', 'institutional-email' ),
						'keys'  => array( 'profileLabel', 'profileStudents', 'profileTeachers' ),
					),
					array(
						'label' => __( 'Students', 'institutional-email' ),
						'keys'  => array( 'birthDate', 'error_birthDate', 'studentResult' ),
					),
					array(
						'label' => __( 'Buttons and messages', 'institutional-email' ),
						'keys'  => array( 'submit', 'copyEmail', 'copyPassword', 'emailCopied', 'passwordCopied', 'copyFailed', 'ready', 'fixErrors', 'nextStep', 'privacy' ),
					),
				),
			),
		);
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
			'design'        => array(
				'label' => __( 'Design', 'institutional-email' ),
				'icon'  => 'palette',
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
