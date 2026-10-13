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
				'intro' => __( 'Forms that work out the institutional email address of São Paulo students and teachers, of the city schools (RME/SP) or of the state schools (SEDUC-SP). The address is worked out in the visitor\'s browser, so nothing typed reaches the site.', 'institutional-email' ),
				'items' => array(
					array( '[' . Shortcode::TAG . ']', __( 'Places the form on any page or post, opening with a profile selector for students and teachers; with only one form offered, it shows that one. The Copy button on the General tab copies it.', 'institutional-email' ) ),
					array( 'form="students"', __( 'Shows only the students\' form, with no selector; form="teachers" shows only the teachers\'.', 'institutional-email' ) ),
					array( 'network="state"', __( 'Serves the state schools on that page, whatever the General tab says; network="city" serves the city schools.', 'institutional-email' ) ),
					array( __( 'Standalone HTML', 'institutional-email' ), __( 'Every release also publishes a single HTML file with both forms and the profile selector, for an iframe on a site without WordPress; add ?form=students or ?form=teachers to its address for one form alone, and ?network=state for the state schools. It uses the built-in defaults, not these settings.', 'institutional-email' ) ),
				),
			),
			'form'     => array(
				'title' => __( 'Using the form', 'institutional-email' ),
				'icon'  => 'email',
				'intro' => __( 'What a person sees on the page.', 'institutional-email' ),
				'items' => array(
					array( __( 'Students', 'institutional-email' ), __( 'Full name and date of birth.', 'institutional-email' ) ),
					array( __( 'Teachers', 'institutional-email' ), __( 'Full name, then the network: the CPF for the partner network, the 7 digits of the RF for the direct network, or the CPF for an intern.', 'institutional-email' ) ),
					array( __( 'State students', 'institutional-email' ), __( 'The RA and its check digit, as the SED shows them, and the date of birth, which is also the initial password. A link points to the SED page that looks the RA up.', 'institutional-email' ) ),
					array( __( 'State teachers', 'institutional-email' ), __( 'No form: an explanation that the address is created on the first access to the SED, and a link there.', 'institutional-email' ) ),
					array( __( 'Errors', 'institutional-email' ), __( 'Each field says what is wrong, and a status line announces it to screen readers.', 'institutional-email' ) ),
					array( __( 'Result', 'institutional-email' ), __( 'The address and the initial password, each with a Copy button, and a link to the platform where they are used first. Changing a field hides the result until it is worked out again.', 'institutional-email' ) ),
				),
			),
			'general'  => array(
				'title' => __( 'General', 'institutional-email' ),
				'icon'  => 'settings',
				'tab'   => 'general',
				'intro' => __( 'Which school network the site serves, which forms it offers and who may use them.', 'institutional-email' ),
				'items' => array(
					array( __( 'School network', 'institutional-email' ), __( 'City schools (RME/SP) or state schools (SEDUC-SP). A shortcode\'s network="…" can choose the other one for a single page.', 'institutional-email' ) ),
					array( __( 'Forms offered', 'institutional-email' ), __( 'A shortcode that asks for a form that is not offered shows the first one that is.', 'institutional-email' ) ),
					array( __( 'Teachers\' form options', 'institutional-email' ), __( 'Which of the partner network, the direct network and interns the city teachers\' form offers. With only one, the form asks for it without a choice.', 'institutional-email' ) ),
					array( __( 'Google Analytics', 'institutional-email' ), __( 'Off by default, and then nothing of Google\'s is loaded. Switched on with a measurement ID (G-XXXXXXXXXX), the pages that show a form load Google\'s tag and record each address worked out, with only the form and the network.', 'institutional-email' ) ),
					array( __( 'Who can use the form', 'institutional-email' ), __( 'Everyone who can see the page, or logged-in users only; the others see a link to log in.', 'institutional-email' ) ),
					array( __( 'Dark Mode', 'institutional-email' ), __( 'Off by default. On, or Auto when the operating system is set to dark, this settings screen and the form on the site turn dark. The Design tab\'s colours apply to the light look only.', 'institutional-email' ) ),
				),
				'note'  => __( 'The on/off switches and Dark Mode save as soon as they change. Everything else saves with "Save Changes".', 'institutional-email' ),
			),
			'rules'    => array(
				'title' => __( 'Address rules', 'institutional-email' ),
				'icon'  => 'email',
				'tab'   => 'rules',
				'intro' => __( 'How the address is built. In the city schools accents are dropped, and only the letters a to z of each name reach it.', 'institutional-email' ),
				'items' => array(
					array( __( 'Students', 'institutional-email' ), __( 'First name, the initial of each middle name that is long enough, last name, a dot, and the date of birth as DDMMYYYY. Maria Aparecida da Silva, born on 7 March 2012: mariaasilva.07032012.', 'institutional-email' ) ),
					array( __( 'Teachers', 'institutional-email' ), __( 'First and last name, a dot, and the RF or the CPF digits. Maria da Silva, RF 5181780: mariasilva.5181780.', 'institutional-email' ) ),
					array( __( 'Interns', 'institutional-email' ), __( 'As a partner-network teacher, with an "e" before the CPF digits: mariasilva.e52998224725.', 'institutional-email' ) ),
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
					array( __( 'RF check digit', 'institutional-email' ), __( 'The seventh digit of an RF is a check digit. On by default, an RF whose check digit does not match is refused, so a typo does not become an address that does not exist.', 'institutional-email' ) ),
					array( __( 'State schools', 'institutional-email' ), __( 'A student\'s address is the RA padded with zeros to 12 digits, its check digit and "sp", at al.educacao.sp.gov.br; the initial password is the date of birth (DDMMYYYY). These rules have nothing to set.', 'institutional-email' ) ),
				),
			),
			'texts'    => array(
				'title' => __( 'Texts', 'institutional-email' ),
				'icon'  => 'list',
				'tab'   => 'texts',
				'intro' => __( 'What the form says.', 'institutional-email' ),
				'items' => array(
					array( __( 'Next step', 'institutional-email' ), __( 'The platform the form links to below the result. Without an address, no next step is shown.', 'institutional-email' ) ),
					array( __( 'Texts', 'institutional-email' ), __( 'Any label, hint or message, grouped by school network and by the form or option that shows it. An empty field keeps the built-in text, which follows the site\'s language.', 'institutional-email' ) ),
				),
			),
			'design'   => array(
				'title' => __( 'Design', 'institutional-email' ),
				'icon'  => 'palette',
				'tab'   => 'design',
				'intro' => __( 'How the form looks on the site\'s pages.', 'institutional-email' ),
				'items' => array(
					array( __( 'Customise', 'institutional-email' ), __( 'Off by default: the form keeps its own look. Switched on, it uses the width, colours, corners and font set here; switching it off clears the customisation, so the values go back to their defaults.', 'institutional-email' ) ),
					array( __( 'Colours', 'institutional-email' ), __( 'Of the buttons and links, the button text, the field borders, the hints, the errors and the result, in the light look; in dark mode the form keeps its own dark colours. A pair hard to read (below 4.5:1, as WCAG asks) is saved with a warning naming it.', 'institutional-email' ) ),
					array( __( 'Form width', 'institutional-email' ), __( 'The widest the form grows on a page, in pixels; on a narrower screen it fills the space it has.', 'institutional-email' ) ),
					array( __( 'Rounded corners and font', 'institutional-email' ), __( 'The radius of the fields, buttons and result, and the font: the site theme\'s (the default), the plugin\'s system font, or Arial, the same three choices as Free Form Certificate\'s forms.', 'institutional-email' ) ),
				),
			),
			'privacy'  => array(
				'title' => __( 'Privacy', 'institutional-email' ),
				'icon'  => 'shield',
				'intro' => __( 'Nothing typed in the form is sent to the site, and the plugin stores no personal data.', 'institutional-email' ),
				'items' => array(
					array( __( 'In the browser', 'institutional-email' ), __( 'The address is worked out on the visitor\'s device, and nothing is kept after the page is closed.', 'institutional-email' ) ),
					array( __( 'With Google Analytics', 'institutional-email' ), __( 'If an administrator switches it on, Google receives each visit to a page with a form, as on any site that uses it, and an event saying a form worked out an address. What was typed and the address are never sent.', 'institutional-email' ) ),
					array( __( 'On the site', 'institutional-email' ), __( 'Only these settings are stored, in one option that is deleted when the plugin is.', 'institutional-email' ) ),
				),
			),
			'updates'  => array(
				'title' => __( 'Updates', 'institutional-email' ),
				'icon'  => 'sync',
				'intro' => __( 'New versions arrive through the normal WordPress update screen, from the plugin\'s GitHub releases.', 'institutional-email' ),
				'items' => array(
					array( __( 'Checked package', 'institutional-email' ), __( 'Each package is checked against its published SHA-256 before it is installed.', 'institutional-email' ) ),
					array( __( 'Automatic updates', 'institutional-email' ), __( 'Turned on or off with WordPress\'s own link on the Plugins screen.', 'institutional-email' ) ),
					array( __( 'View details', 'institutional-email' ), __( 'Shows the description, installation steps and screenshots, in your language when the plugin ships a translation of them. A screenshot opens larger without leaving the window: the arrows move between them, and the wheel, a pinch or a click zooms in.', 'institutional-email' ) ),
				),
			),
		);
	}

	/**
	 * Print the contents list and the cards.
	 */
	public static function render(): void {
		$topics = self::topics();

		// On a wide screen the contents is a sticky column beside the topics;
		// on a narrow one it stays above them (assets/css/admin-settings.css).
		echo '<div class="ie-doc-layout">';
		// Quick navigation, as in Free Form Certificate's documentation: one
		// row per topic with its icon, and a search box that filters both the
		// rows and the cards (assets/js/admin-settings.js).
		printf( '<nav class="card ie-doc-contents" aria-label="%1$s"><h2>%2$s<span>%1$s</span></h2>', esc_attr__( 'Quick Navigation', 'institutional-email' ), Icons::svg( 'search' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup from the icon registry.
		printf( '<p class="ie-doc-search"><input type="search" id="ie-doc-search" placeholder="%1$s" aria-label="%2$s" /></p><ul>', esc_attr__( 'Search documentation…', 'institutional-email' ), esc_attr__( 'Search documentation', 'institutional-email' ) );
		foreach ( $topics as $id => $topic ) {
			printf( '<li><a href="#ie-doc-%1$s">%2$s%3$s</a></li>', esc_attr( $id ), Icons::svg( $topic['icon'], 16 ), esc_html( $topic['title'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup from the icon registry.
		}
		printf( '</ul><p class="ie-doc-search-empty" hidden>%s</p></nav>', esc_html__( 'No topic matches this search.', 'institutional-email' ) );

		echo '<div class="ie-doc-topics">';
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
		echo '</div></div>';

		self::credits();
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
