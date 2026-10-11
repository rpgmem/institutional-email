<?php
/**
 * Settings → Institutional Email → Documentation.
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
 * The Documentation tab: a contents list and one card per topic, read from
 * one ordered registry, so the list and the cards cannot disagree. A topic
 * that explains a settings tab names it in `tab`, which is how the guard in
 * tests/Unit/DocumentationTest.php knows every tab is documented.
 */
final class Documentation {

	/**
	 * The topics, in order: id => title, icon, the settings tab it explains
	 * (if any), an introduction, term/text pairs and an optional note.
	 *
	 * @return array<string, array{title: string, icon: string, tab?: string, intro: string, items: list<array{0: string, 1: string}>, note?: string}>
	 */
	public static function topics(): array {
		return array(
			'overview' => array(
				'title' => __( 'Overview', 'institutional-email' ),
				'icon'  => 'info',
				'intro' => __( 'Forms that work out the institutional email address of RME/SP students and teachers. The address is worked out in the visitor\'s browser, so nothing typed reaches the site.', 'institutional-email' ),
				'items' => array(
					array( '[' . Shortcode::TAG . ']', __( 'Places the students\' form on any page or post. The Copy button on the General tab copies it.', 'institutional-email' ) ),
					array( 'form="teachers"', __( 'Places the teachers\' form instead. form="students" is the default.', 'institutional-email' ) ),
					array( __( 'Standalone HTML', 'institutional-email' ), __( 'Every release also publishes a single HTML file with both forms, for an iframe on a site without WordPress; add ?form=teachers to its address for the teachers\' form. It uses the built-in defaults, not these settings.', 'institutional-email' ) ),
				),
			),
			'form'     => array(
				'title' => __( 'Using the form', 'institutional-email' ),
				'icon'  => 'email',
				'intro' => __( 'What a person sees on the page.', 'institutional-email' ),
				'items' => array(
					array( __( 'Students', 'institutional-email' ), __( 'Full name and date of birth.', 'institutional-email' ) ),
					array( __( 'Teachers', 'institutional-email' ), __( 'Full name, then the network: the CPF for the partner network, or the 7 digits of the RF for the direct network.', 'institutional-email' ) ),
					array( __( 'Errors', 'institutional-email' ), __( 'Each field says what is wrong, and a status line announces it to screen readers.', 'institutional-email' ) ),
					array( __( 'Result', 'institutional-email' ), __( 'The address and the initial password, each with a Copy button, and a link to the platform where they are used first. Changing a field hides the result until it is worked out again.', 'institutional-email' ) ),
				),
			),
			'general'  => array(
				'title' => __( 'General', 'institutional-email' ),
				'icon'  => 'settings',
				'tab'   => 'general',
				'intro' => __( 'Which forms the site offers and who may use them.', 'institutional-email' ),
				'items' => array(
					array( __( 'Forms offered', 'institutional-email' ), __( 'A shortcode that asks for a form that is not offered shows the first one that is.', 'institutional-email' ) ),
					array( __( 'Who can use the form', 'institutional-email' ), __( 'Everyone who can see the page, or logged-in users only; the others see a link to log in.', 'institutional-email' ) ),
				),
				'note'  => __( 'The on/off switches save as soon as they are flipped. Everything else saves with "Save Changes".', 'institutional-email' ),
			),
			'rules'    => array(
				'title' => __( 'Address rules', 'institutional-email' ),
				'icon'  => 'email',
				'tab'   => 'rules',
				'intro' => __( 'How the address is built. Accents are dropped, and only the letters a to z of each name reach it.', 'institutional-email' ),
				'items' => array(
					array( __( 'Students', 'institutional-email' ), __( 'First name, the initial of each middle name that is long enough, last name, a dot, and the date of birth as DDMMYYYY. Maria Aparecida da Silva, born on 7 March 2012: mariaasilva.07032012.', 'institutional-email' ) ),
					array( __( 'Teachers', 'institutional-email' ), __( 'First and last name, a dot, and the RF or the CPF digits. Maria da Silva, RF 1234567: mariasilva.1234567.', 'institutional-email' ) ),
					array(
						__( 'Middle names that count', 'institutional-email' ),
						sprintf(
							/* translators: 1: minimum, 2: maximum, 3: default number of letters. */
							__( 'From %1$d to %2$d letters (%3$d by default). Shorter middle names, such as "da" and "dos", are left out of a student\'s address.', 'institutional-email' ),
							Settings::MIDDLE_MIN_MIN,
							Settings::MIDDLE_MIN_MAX,
							Config::MIDDLE_MIN
						),
					),
					array( __( 'Domain and password', 'institutional-email' ), __( 'What comes after the "@", and the password a new account starts with. The password is public: everyone who opens the form sees it.', 'institutional-email' ) ),
				),
			),
			'texts'    => array(
				'title' => __( 'Texts', 'institutional-email' ),
				'icon'  => 'list',
				'tab'   => 'texts',
				'intro' => __( 'What the form says.', 'institutional-email' ),
				'items' => array(
					array( __( 'Next step', 'institutional-email' ), __( 'The platform the form links to below the result. Without an address, no next step is shown.', 'institutional-email' ) ),
					array( __( 'Texts', 'institutional-email' ), __( 'Any label, hint or message. An empty field keeps the built-in text, which follows the site\'s language.', 'institutional-email' ) ),
				),
			),
			'privacy'  => array(
				'title' => __( 'Privacy', 'institutional-email' ),
				'icon'  => 'shield',
				'intro' => __( 'Nothing typed in the form is sent to the site, and the plugin stores no personal data.', 'institutional-email' ),
				'items' => array(
					array( __( 'In the browser', 'institutional-email' ), __( 'The address is worked out on the visitor\'s device, and nothing is kept after the page is closed.', 'institutional-email' ) ),
					array( __( 'On the site', 'institutional-email' ), __( 'Only these settings are stored, in one option that is deleted when the plugin is.', 'institutional-email' ) ),
				),
			),
		);
	}

	/**
	 * Print the contents list and the cards.
	 */
	public static function render(): void {
		$topics = self::topics();

		printf( '<div class="card ie-doc-contents"><h2>%1$s<span>%2$s</span></h2><ul>', Icons::svg( 'book' ), esc_html__( 'Contents', 'institutional-email' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup from the icon registry.
		foreach ( $topics as $id => $topic ) {
			printf( '<li><a href="#ie-doc-%1$s">%2$s</a></li>', esc_attr( $id ), esc_html( $topic['title'] ) );
		}
		echo '</ul>';
		self::credits();
		echo '</div>';

		foreach ( $topics as $id => $topic ) {
			printf(
				'<div class="card ie-doc"><h2 id="ie-doc-%1$s">%2$s<span>%3$s</span></h2><p>%4$s</p>',
				esc_attr( $id ),
				Icons::svg( $topic['icon'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup from the icon registry.
				esc_html( $topic['title'] ),
				esc_html( $topic['intro'] )
			);
			echo '<dl>';
			foreach ( $topic['items'] as $item ) {
				printf( '<dt>%1$s</dt><dd>%2$s</dd>', esc_html( $item[0] ), esc_html( $item[1] ) );
			}
			echo '</dl>';
			if ( isset( $topic['tab'] ) ) {
				printf(
					'<p class="ie-doc-goto"><a href="%1$s" data-ie-goto="%2$s">%3$s</a></p>',
					esc_url( add_query_arg( 'tab', $topic['tab'] ) ),
					esc_attr( $topic['tab'] ),
					/* translators: %s: settings tab name. */
					esc_html( sprintf( __( 'Open the %s tab', 'institutional-email' ), SettingsPage::tabs()[ $topic['tab'] ]['label'] ?? $topic['title'] ) )
				);
			}
			if ( isset( $topic['note'] ) ) {
				printf( '<p class="description">%s</p>', esc_html( $topic['note'] ) );
			}
			echo '</div>';
		}
	}

	/**
	 * The author and the repository, read from the plugin header: the lines
	 * WordPress shows on the Plugins screen, so the two cannot disagree.
	 */
	private static function credits(): void {
		$meta = get_file_data(
			Plugin::file(),
			array(
				'author'     => 'Author',
				'author_uri' => 'Author URI',
				'plugin_uri' => 'Plugin URI',
			)
		);
		echo '<p class="ie-doc-credits">';
		printf(
			/* translators: %s: plugin author's name, linked to the author's page. */
			esc_html__( 'Developed by %s.', 'institutional-email' ),
			'<a href="' . esc_url( $meta['author_uri'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $meta['author'] ) . '</a>'
		);
		echo ' ';
		printf(
			/* translators: %s: link to the plugin's source repository. */
			esc_html__( 'Source code and issue tracker: %s', 'institutional-email' ),
			'<a href="' . esc_url( $meta['plugin_uri'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( (string) preg_replace( '#^https?://#', '', $meta['plugin_uri'] ) ) . '</a>'
		);
		echo '</p>';
	}
}
